<?php
declare(strict_types=1);

namespace App\ORM;

use DateTimeImmutable;
use PDO;
use Throwable;

class ORM {
    protected static PDO $pdo;
    protected static string $table;
    protected ?int $id = null;
    private array $data = [];
    private array $relationsCache = [];

    protected static array $allowedColumns = [];
    protected static array $hiddenColumns = [];
    protected static array $columnTypes = [];

    protected static array $dateFormats = [
        'datetime' => 'Y-m-d H:i:s',
        'date' => 'Y-m-d',
        'time' => 'H:i:s',
    ];

    private bool $dirty = false;
    private array $originalData = [];

    public function __construct(array $data = []) {
        if($data) {
            $this->originalData = $this->data = $this->castRowFromStorage($data);
            $this->id = $this->data['id'] ?? null;
        }
    }

    public function __get($name): mixed {
        return $this->data[$name] ?? null;
    }

    public function __set($name, $value): void {
        $columnType = static::$columnTypes[$name] ?? null;
        $prepared = $value;
        $canonical = self::canonicalType($columnType);

        if($canonical !== null) {
            if(self::isDateType($canonical)) {
                $format = self::getDateFormatForType($canonical);
                $parsed = $this->parseDateValue($value, $format);
                if($parsed !== null) {
                    $prepared = $parsed;
                }
            } elseif(($ptype = self::canonicalPrimitiveForType($canonical)) !== null) {
                $tmp = $value;
                settype($tmp, $ptype);
                $prepared = $tmp;
            }
        }

        if($this->id !== null) {
            $original = $this->originalData[$name] ?? null;
            if(!$this->valuesAreEqual($original, $prepared, $canonical)) {
                $this->dirty = true;
            }
        }

        $this->data[$name] = $prepared;
    }

    public static function setPDO(PDO $pdo): void { static::$pdo = $pdo; }
    protected function clearRelationsCache(): void { $this->relationsCache = [];}

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
        $table = static::$table;
        $subset = array_intersect(array_keys($this->data), static::$allowedColumns);
        $columns = array_values($subset);
        if(empty($columns)) {
            throw new \Exception("No columns to insert to table {$table}");
        }

        foreach($columns as $col) {
            if(!preg_match('/^[a-zA-Z0-9_]+$/', $col)) {
                throw new \Exception("Invalid column name: $col");
            }
        }

        $placeholders = array_map(fn($c) => ":$c", $columns);
        $columnsSql = implode(', ', $columns);
        $placeholdersSql = implode(', ', $placeholders);

        $query = "INSERT INTO {$table} ($columnsSql) VALUES ($placeholdersSql)";
        $stmt = static::$pdo->prepare($query);

        $params = [];
        foreach($columns as $col) {
            $params[$col] = $this->prepareValueForStorage($col, $this->data[$col]);
        }

        $stmt->execute($params);
        $this->id = (int) static::$pdo->lastInsertId();
        $this->data['id'] = $this->id;
        $this->originalData = $this->data;
        $this->clearRelationsCache();
    }

    private function update(): void {
        $set = [];
        $params = [];
        foreach($this->data as $key => $value) {
            if ($key === 'id') continue;
            if (!\in_array($key, static::$allowedColumns, true)) continue;
            if ($this->valuesAreEqual($this->originalData[$key] ?? null, $value, static::$columnTypes[$key] ?? null))
                continue;
            $set[] = "$key = :$key";
            $params[$key] = $this->prepareValueForStorage($key, $value);
        }

        if (empty($set)) return;

        $table = static::$table;
        $params['id'] = $this->id;
        $query = "UPDATE $table SET " . implode(', ', $set) . " WHERE id = :id";
        $stmt = static::$pdo->prepare($query);
        $stmt->execute($params);
        $this->clearRelationsCache();
    }

    public function delete(): ORM {
        if($this->id) {
            $table = static::$table;
            $stmt = static::$pdo->prepare("DELETE FROM $table WHERE id = :id");
            $stmt->execute(['id' => $this->id]);
        }
        $this->clearRelationsCache();
        return $this;
    }

    public static function find(array $filters = [], array $sort = [], int $page = 1, int $offset = 0): array {
        if($page < 1) {
            throw new \InvalidArgumentException("Page must be greater than 0");
        }
        if($offset < 0) {
            throw new \InvalidArgumentException("Offset must be greater than or equal to 0");
        }

        $table = static::$table;
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
        $table = static::$table;
        $query = "SELECT COUNT(*) FROM $table WHERE 1=1";
        $prepared = static::prepareQuery($query, $filters);
        $query = $prepared['query'];
        $params = $prepared['params'];

        $stmt = static::$pdo->prepare($query);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn(0);

    }

    public static function all(): array {
        $table = static::$table;
        $stmt = static::$pdo->query("SELECT * FROM {$table}");
        $results = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row = array_intersect_key($row, array_flip(static::$allowedColumns));
            $row = array_diff_key($row, array_flip(static::$hiddenColumns));
            if(empty($row)) {
                continue;
            }
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

    public function belongsTo(string $relatedClass, string $foreignKey, string $localKey = 'id'): ?ORM {
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
            if (\in_array($col, static::$hiddenColumns, true)) continue;

            $type = static::$columnTypes[$col] ?? null;
            $canonical = self::canonicalType($type);
            $format = self::getDateFormatForType($canonical);
            $isDateTimeImmutable = $val instanceof DateTimeImmutable && $format !== null;
            $out[$col] = $isDateTimeImmutable ? $val->format($format) : $val;
        }
        return $out;
    }

    private function castRowFromStorage(array $row): array {
        foreach($row as $col => $val) {
            $type = static::$columnTypes[$col] ?? null;
            if ($val === null) continue;
            if ($type === null) continue;

            $canonical = self::canonicalType($type);
            $dateFormat = self::getDateFormatForType($canonical);
            $primitiveType = self::canonicalPrimitiveForType($canonical);
            if ($dateFormat !== null) {
                try {
                    $row[$col] = new DateTimeImmutable($val);
                } catch (Throwable $e) {
                    $row[$col] = $val;
                }
            } elseif ($primitiveType !== null) {
                settype($val, $primitiveType);
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

        $canonical = self::canonicalType($type);
        $dateFormat = self::getDateFormatForType($canonical);
        if ($value instanceof DateTimeImmutable && $dateFormat !== null) {
            return $value->format($dateFormat);
        }

        $primitiveType = self::canonicalPrimitiveForType($canonical);
        if ($primitiveType !== null) {
            settype($value, $primitiveType);
            return $value;
        }

        return $value;
    }

    private static function canonicalType(?string $type): ?string {
        if ($type === null) return null;
        $t = strtolower($type);
        return match ($t) {
            'int' => 'integer',
            'integer' => 'integer',
            'bool' => 'boolean',
            'boolean' => 'boolean',
            'float' => 'double',
            'double' => 'double',
            'string' => 'string',
            'date' => 'date',
            'time' => 'time',
            'datetime' => 'datetime',
            default => $t,
        };
    }

    private static function isDateType(?string $type): bool {
        $t = self::canonicalType($type);
        return \in_array($t, ['date', 'time', 'datetime'], true);
    }

    private static function getDateFormatForType(?string $type): ?string {
        $t = self::canonicalType($type);
        if ($t === null) return null;
        return self::$dateFormats[$t] ?? null;
    }

    private function parseDateValue(mixed $value, ?string $format = null): ?DateTimeImmutable {
        if($value instanceof DateTimeImmutable) return $value;
        if(!\is_string($value)) return null;

        if($format !== null) {
            $dt = DateTimeImmutable::createFromFormat($format, $value);
            if ($dt !== false) return $dt;

            try {
                return new DateTimeImmutable($value);
            } catch(Throwable $e) {
                return null;
            }
        }

        try {
            return new DateTimeImmutable($value);
        } catch(Throwable $e) {
            return null;
        }
    }

    private function valuesAreEqual(mixed $original, mixed $value, ?string $colType = null): bool {
        if ($original === null && $value === null) return true;
        if ($original === null || $value === null) return false;

        $canonical = self::canonicalType($colType);
        if($canonical && \in_array($canonical, ['date', 'time', 'datetime'], true)) {
            $format = self::getDateFormatForType($canonical) ?? 'Y-m-d H:i:s';
            if($original instanceof DateTimeImmutable && $value instanceof DateTimeImmutable) {
                return $original->format($format) === $value->format($format);
            }
            if($original instanceof DateTimeImmutable && \is_string($value)) {
                $valueDate = $this->parseDateValue($value, $format);
                if ($valueDate === null) return false;
                return $original->format($format) === $valueDate->format($format);
            }
            if(\is_string($original) && $value instanceof DateTimeImmutable) {
                $originalDate = $this->parseDateValue($original, $format);
                if ($originalDate === null) return false;
                return $originalDate->format($format) === $value->format($format);
            }
            return (string)$original === (string)$value;
        }

        return $original === $value;
    }

    private static function canonicalPrimitiveForType(?string $type): ?string {
        $t = self::canonicalType($type);
        return match ($t) {
            'integer' => 'integer',
            'boolean' => 'boolean',
            'double' => 'double',
            'string' => 'string',
            default => null,
        };
    }

}
