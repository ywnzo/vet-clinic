<?php
declare(strict_types=1);

namespace App\ORM;

use PDO;

class ORM {
    protected static PDO $pdo;
    protected string $table;
    protected ?int $id = null;
    private array $data = [];

    protected static array $allowedColumns = [];
    protected static array $columnTypes = [];

    private bool $dirty = false;
    private array $originalData = [];

    public function __construct(array $data = []) {
        $this->table = $this->getTable();
        if($data) {
            $this->originalData = $this->data = $data;
            $this->id = $data['id'] ?? null;
        }
    }

    public function __get($name): mixed {
        return $this->data[$name] ?? null;
    }

    public function __set($name, $value): void {
        if (isset($this->data[$name]) && $this->data[$name] !== $value) {
            $this->dirty = true;
        }
        if (isset(static::columnTypes[$name])) {
            settype($value, static::columnTypes[$name]);
        }
        $this->data[$name] = $value;
    }

    public static function setPDO(PDO $pdo): void {
        static::$pdo = $pdo;
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
        $columns = array_keys($this->data);
        $placeholders = array_map(fn($c) => ":$c", $columns);

        $columns = implode(', ', $columns);
        $placeholders = implode(',', $placeholders);

        $query = "INSERT INTO $this->table ($columns) VALUES ($placeholders)";
        $stmt = static::$pdo->prepare($query);
        $stmt->execute($this->data);
        $this->id = (int) static::$pdo->lastInsertId();
        $this->originalData = $this->data;
    }

    private function update(): void {
        $set = [];
        $params = [];
        foreach($this->data as $key => $value) {
            if ($key === 'id') continue;
            if ($value === ($this->originalData[$key] ?? null)) continue;
            $set[] = "$key = :$key";
            $params[$key] = $value;
        }

        if (empty($set)) return;

        $params['id'] = $this->id;

        $query = "UPDATE $this->table SET " . implode(',', $set) . " WHERE id = :id";
        $stmt = static::$pdo->prepare($query);
        $stmt->execute($params);
    }

    public function delete(): ORM {
        if($this->id) {
            $stmt = static::$pdo->prepare("DELETE FROM $this->table WHERE id = :id");
            $stmt->execute(['id' => $this->id]);
        }
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
        $table = self::getTable();
        $stmt = static::$pdo->query("SELECT * FROM $table");
        $results = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = new static($row);
        }
        return $results;
    }

    public static function getTable(): string {
        return strtolower((new \ReflectionClass(static::class)->getShortName())) . 's';
    }

    private static function prepareQuery(string $query, array $filters = []):array {
        $params = [];

        foreach($filters as $column => $value) {
            self::validateColumn($column);

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
        if (!\in_array($column, static::$allowedColumns, true)) {
            throw new \InvalidArgumentException("Column '$column' not allowed");
        }
    }

    public function toArray(): array {
        return $this->data;
    }
}
