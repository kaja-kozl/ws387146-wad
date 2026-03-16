<?php
namespace app\core;

class RawExpr
{
    public function __construct(public readonly string $sql) {}
}

abstract class dbModel extends Model
{
    abstract public static function tableName(): string;
    abstract public static function attributes(): array;
    abstract public static function primaryKey(): string;

    public function __construct()
    {
        if (property_exists($this, 'uid') && empty($this->uid)) {
            $this->uid = $this->generateUuid();
        }
    }

    protected function generateUuid(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public function save(): bool
    {
        $tableName  = static::tableName();
        $attributes = static::attributes();
        $params     = array_map(fn($attr) => ":$attr", $attributes);

        $statement = static::prepare(
            "INSERT INTO $tableName (" . implode(',', $attributes) . ")
             VALUES (" . implode(',', $params) . ")"
        );

        foreach ($attributes as $attribute) {
            $statement->bindValue(":$attribute", $this->{$attribute});
        }

        $statement->execute();
        return true;
    }

    public static function findOne(array $where): mixed
    {
        $tableName = static::tableName();
        $sql_where = implode(' AND ', array_map(fn($attr) => "$attr = :$attr", array_keys($where)));
        $statement = static::prepare("SELECT * FROM $tableName WHERE $sql_where");

        foreach ($where as $key => $value) {
            $statement->bindValue(":$key", $value);
        }

        $statement->execute();
        return $statement->fetchObject(static::class);
    }

    # Accepts [column => value] pairs for bound conditions,
    # or [column => new RawExpr('...')] for SQL expressions like >= NOW()
    public function read(string $attributes, array $where = []): array
    {
        $tableName  = static::tableName();
        $sql        = "SELECT $attributes FROM $tableName";
        $params     = [];

        if (!empty($where)) {
            $conditions = [];
            foreach ($where as $col => $val) {
                if ($val instanceof RawExpr) {
                    $conditions[] = "$col {$val->sql}";
                } else {
                    $conditions[]    = "$col = :$col";
                    $params[":$col"] = $val;
                }
            }
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $statement = static::prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll(\PDO::FETCH_CLASS, static::class);
    }

    # Accepts positional params via $params for IN clauses and other
    # cases that cannot be expressed as simple column => value pairs
    public function readRaw(string $sql, array $params = []): array
    {
        $statement = static::prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll(\PDO::FETCH_CLASS, static::class);
    }

    public function delete(array $where = [], array $params = []): bool
    {
        $tableName = static::tableName();
        $sql       = "DELETE FROM $tableName";

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        return static::prepare($sql)->execute($params);
    }

    public function update(array $set = [], array $params = [], string $where = ''): bool
    {
        $tableName = static::tableName();
        $sql       = "UPDATE $tableName SET " . implode(', ', $set);

        if (!empty($where)) {
            $sql .= " WHERE $where";
        }

        return static::prepare($sql)->execute($params);
    }

    protected static function prepare(string $sql): \PDOStatement
    {
        return Application::$app->db->prepare($sql);
    }
}
?>