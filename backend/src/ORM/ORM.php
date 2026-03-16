<?php
declare(strict_types=1);

namespace App\ORM;

use DateTimeImmutable;
use PDO;

class ORM {
    protected static PDO $pdo;
    protected string $table;
    protected ?int $id = null;
    private array $data = [];
    private array $relationsCache = [];

    protected static array $allowedColumns = [];
    protected static array $hiddenColumns = [];
    protected static array $columnTypes = [];

    protected static array $primitiveMap = [
        'integer' => 'integer',
        'int' => 'integer',
        'bool' => 'boolean',
        'boolean' => 'boolean',
        'double' => 'double',
        'float' => 'float',
        'string' => 'string',
    ];

    protected static array $dateFormats = [
        'datetime' => 'Y-m-d H:i:s',
        'date' => 'Y-m-d',
        'time' => 'H:i:s',
    ];

    private bool $dirty = false;
    private array $originalData = [];

    public function __construct(array $data = []) {
        $this->table = (string)$this->getTable();
        if($data) {
            $this->originalData = $this->data = $this->castRowFromStorage($data);
            $this->id = $this->data['id'] ?? null;
        }
    }

    public function __get($name): mixed {
        return $this->data[$name] ?? null;
    }

    public function __set($name, $value): void {
        if(isset($this->id)) {
            $original = $this->originalData[$name] ?? null;
            if($original !== $value) {
                $this->dirty = true;
            }
        }

        $type = static::$columnTypes[$name] ?? null;
        if($type !== null) {
            $lower = strtolower($type);
            if(isset(self::$dateFormats[$lower])) {
                if(\is_string($value)){
                    try {
                        $value = new DateTimeImmutable($value);
                    } catch (\Throwable $e) {}
                }
            } elseif(isset(self::$primitiveMap[$lower])) {
                settype($value, self::$primitiveMap[$lower]);
            }
        }

        $this->data[$name] = $value;
    }

    public static function setPDO(PDO $pdo): void {
        static::$pdo = $pdo;
    }

    public static function getTable(): string {
        return static::$table;
    }

    protected function clearRelationsCache(): void {
        $this->relationsCache = [];
    }

    public static function transaction(callable $callback): mixed {
        if (!isset(static::$pdo)) {
            throw new \RuntimeException("PDO not set");
        }

        try {
            static::$pdo->beginTransaction();
            $result = $callback();
            static::$pdo->commit();
            return $result;
        } catch(\Throwable $e) {
            static::$pdo->rollBack();
            throw $e;
        }
    }

    public function save(): ORM {
        if (!$this->id) {
            $this->insert();
        } else {
            if ($this->dirty) {
                $this->update();
                $this->dirty = false;
                $this->originalData = $this->data;
            }
        }
        return $this;
    }

    private function insert(): void {
        $intersect = array_intersect_key($this->data, array_flip(static::$allowedColumns));
        $columns = array_values($intersect);
        $placeholders = array_map(fn($c) => ":$c", $columns);

        $columnsSql = implode(', ', $columns);
        $placeholdersSql = implode(', ', $placeholders);

        $query = "INSERT INTO $this->table ($columnsSql) VALUES ($placeholdersSql)";
        $stmt = static::$pdo->prepare($query);

        $params = [];
        foreach($columns as $col) {
            $params[$col] = $this->prepareValueForStorage($col, $this->data[$col]);
        }

        $stmt->execute($params);
        $this->id = (int) static::$pdo->lastInsertId();
        $this->originalData = $this->data;
        $this->clearRelationsCache();
    }

    private function update(): void {
        $set = [];
        $params = [];
        foreach($this->data as $key => $value) {
            if ($key === 'id') continue;
            if (!\in_array($key, static::$allowedColumns)) continue;
            if ($value === ($this->originalData[$key] ?? null)) continue;
            $set[] = "$key = :$key";
            $params[$key] = $this->prepareValueForStorage($key, $value);
        }

        if (empty($set)) return;

        $params['id'] = $this->id;
        $query = "UPDATE $this->table SET " . implode(',', $set) . " WHERE id = :id";
        $stmt = static::$pdo->prepare($query);
        $stmt->execute($params);
        $this->clearRelationsCache();
    }

    public function delete(): ORM {
        if($this->id) {
            $stmt = static::$pdo->prepare("DELETE FROM $this->table WHERE id = :id");
            $stmt->execute(['id' => $this->id]);
        }
        $this->clearRelationsCache();
        return $this;
    }

    public static function find(array $filters = [], array $sort = [], int $page = 1, int $offset = 0): array {
        $table = static::getTable();
        $query = "SELECT * FROM $table WHERE 1=1";
        $prepared = static::prepareQuery($query, $filters);
        $query = $prepared['query'];
        $params = $prepared['params'];

        if(!empty($sort['sort'])) {
            self::validateColumn($sort['sort']);
            $order = strtoupper($sort['order'] ?? 'ASC');
            if($order !== 'ASC' && $order !== "DESC") {
                throw new \InvalidArgumentException("Incorrect sorting order!");
            }
            $query .= " ORDER BY {$sort['sort']} $order";
        }

        $perPage = $sort['perPage'] ?? 10;
        $query .= " LIMIT :limit OFFSET :offset";
        $params['limit'] = (int) $perPage;
        $params['offset'] = (int)(($page - 1) * $perPage + $offset);

        $stmt = static::$pdo->prepare($query);
        $stmt->execute($params);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $model = new static($row);
            $results[] = $model;
        }

        return $results;
    }

    public static function findByID(int $id): ?static {
        $results = static::find(['id' => $id]);
        return $results[0] ?? null;
    }

    public static function count(array $filters = []): int {
        $table = static::getTable();
        $query = "SELECT COUNT(*) FROM $table WHERE 1=1";
        $prepared = static::prepareQuery($query, $filters);
        $query = $prepared['query'];
        $params = $prepared['params'];

        $stmt = static::$pdo->prepare($query);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn(0);

    }

    public static function all(): array {
        $table = static::getTable();
        $stmt = static::$pdo->query("SELECT * FROM $table");
        $results = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = new static($row);
        }
        return $results;
    }

    public function hasMany(string $relatedClass, string $foreignKey, string $localKey = 'id'): array {
        $cacheKey = "hasMany:$relatedClass:$foreignKey:$localKey";
        if(isset($this->relationsCache[$cacheKey])) {
            return $this->relationsCache[$cacheKey];
        }

        $results = $relatedClass::find([$foreignKey => $this->{$localKey}]);
        $this->relationsCache[$cacheKey] = $results;
        return $results;
    }

    public function belongsTo(string $relatedClass, string $foreignKey, string $localKey): ?ORM {
        $cacheKey = "belongsTo:$relatedClass:$foreignKey:$localKey";
        if(isset($this->relationsCache[$cacheKey])) {
            return $this->relationsCache[$cacheKey];
        }

        $foreignValue = $this->{$foreignKey} ?? null;
        if($foreignValue === null) {
            return null;
        }

        $result = $relatedClass::findByID((int)$foreignValue);
        $this->relationsCache[$cacheKey] = $result;
        return $result ?? null;
    }

    private static function prepareQuery(string $query, array $filters = []):array {
        $params = [];

        foreach($filters as $column => $value) {
            static::validateColumn($column);

            if(!\is_array($value)) {
                $query .= " AND $column = :$column";
                $params[$column] = $value;
            } else {
                $placeholders = [];
                foreach ($value as $i => $v) {
                    $ph = "$column$i";
                    $placeholders[] = ":$ph";
                    $params[$ph] = $v;
                }
                $placeholders = implode(',', $placeholders);
                $query .= " AND $column IN ($placeholders)";
            }
        }

        return [
            "query" => $query,
            "params" => $params
        ];
    }

    private static function validateColumn(string $column): void {
        $isValid = preg_match('/^[a-zA-Z0-9_]+$/', $column);
        $isAllowed = \in_array($column, static::$allowedColumns, true);
        if (!$isValid || !$isAllowed) {
            throw new \InvalidArgumentException("Column '$column' not allowed");
        }
    }

    public function toArray(): array {
        $out = [];
        foreach($this->data as $col => $val) {
            if (\in_array($col, static::$hiddenColumns)) continue;

            $type = strtolower((string) (static::$columnTypes[$col] ?? null));
            if ($val instanceof DateTimeImmutable && isset(self::$dateFormats[$type])) {
                $format = self::$dateFormats[$type];
                $out[$col] = $val->format($format);
            } else {
                $out[$col] = $val;
            }
        }
        return $out;
    }

    private function castRowFromStorage(array $row): array {
        foreach($row as $col => $val) {
            $type = static::$columnTypes[$col] ?? null;
            if ($val === null) continue;
            if ($type === null) continue;

            $lower = strtolower((string) $type);
            if (isset(self::$dateFormats[$lower])){
                try {
                    $row[$col] = new DateTimeImmutable($val);
                } catch (\Throwable $e) {
                    $row[$col] = $val;
                }
            } elseif (isset(self::$primitiveMap[$lower])) {
                settype($val, self::$primitiveMap[$lower]);
                $row[$col] = $val;
            } else {
                $row[$col] = $val;
            }
        }
        return $row;
    }

    private function prepareValueForStorage(string $col, mixed $value): mixed {
        if ($value === null) return null;

        $type = static::$columnTypes[$col] ?? null;
        if ($type === null) return $value;

        $lower = strtolower((string) $type);
        if($value instanceof DateTimeImmutable && isset(self::$dateFormats[$lower])) {
            return $value->format(self::$dateFormats[$lower]);
        }

        if(isset(self::$dateFormats[$lower]) && \is_string($value)) {
            return $value;
        }

        if(isset(self::$primitiveMap[$lower])) {
            settype($value, self::$primitiveMap[$lower]);
            return $value;
        }
        return $value;
    }

}
