<?php
namespace app\core;

class RawExpr
{
    public function __construct(public readonly string $sql) {}
}

abstract class dbModel extends Model
{
    // Subclasses use this to define their table structure and mapping
    abstract public static function tableName(): string;
    abstract public static function attributes(): array;
    abstract public static function primaryKey(): string;

    public function __construct()
    {
        if (property_exists($this, 'uid') && empty($this->uid)) {
            $this->uid = $this->generateUuid();
        }
    }

    // Generates a UUID v4 string - used by many models for unique identifiers
    protected function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    // Saves the current model instance to the database, assumes all attributes are set
    public function save(): bool
    {
        $tableName = static::tableName();
        $attributes = static::attributes();
        // Create an array of parameter placeholders which hold the same as the attibute names
        $params = array_map(fn($attr) => ":$attr", $attributes);

        // Prepared statement to prevent SQL injection, with bound parameters for each attribute
        $statement = static::prepare(
            "INSERT INTO $tableName (" . implode(',', $attributes) . ")
             VALUES (" . implode(',', $params) . ")"
        );


        // Bind the actual values of the model's attributes to the prepared statement parameters
        foreach ($attributes as $attribute) {
            $statement->bindValue(":$attribute", $this->{$attribute});
        }

        // Execute the statement and return true if successful, false otherwise
        $statement->execute();
        return true;
    }

    // Finds a single record based on the where conditions
    public static function findOne(array $where): mixed
    {
        $tableName = static::tableName();
        $sql_where = implode(' AND ', array_map(fn($attr) => "$attr = :$attr", array_keys($where)));
        $statement = static::prepare("SELECT * FROM $tableName WHERE $sql_where");

        // Bind the values for the where conditions to the prepared statement parameters
        foreach ($where as $key => $value) {
            $statement->bindValue(":$key", $value);
        }

        $statement->execute();

        // Fetch the result as an instance of the calling class and return it
        return $statement->fetchObject(static::class);
    }

    // Finds multiple records based on the where conditions, returns an array of model instances
    public function read(string $attributes, array $where = []): array
    {
        $tableName = static::tableName();
        $sql = "SELECT $attributes FROM $tableName";
        $params = [];

        // Build the WHERE clause based on the provided conditions
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

        // Prepare and execute the SQL statement with the bound parameters, then fetch results as model instances
        $statement = static::prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll(\PDO::FETCH_CLASS, static::class);
    }

    // Accepts positional params via $params for IN clauses and other
    // cases that cannot be expressed as simple column => value pairs
    public function readRaw(string $sql, array $params = []): array
    {
        $statement = static::prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll(\PDO::FETCH_CLASS, static::class);
    }

    // Deletes records based on the where conditions, returns true if successful
    public function delete(array $where = [], array $params = []): bool
    {
        $tableName = static::tableName();
        $sql       = "DELETE FROM $tableName";

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        return static::prepare($sql)->execute($params);
    }

    // Updates records based on the set and where conditions, returns true if successful
    public function update(array $set = [], array $params = [], string $where = ''): bool
    {
        $tableName = static::tableName();
        $sql = "UPDATE $tableName SET " . implode(', ', $set);

        if (!empty($where)) {
            $sql .= " WHERE $where";
        }

        return static::prepare($sql)->execute($params);
    }

    // Helper method to prepare SQL statements using the application's PDO instance
    protected static function prepare(string $sql): \PDOStatement
    {
        return Application::$app->db->prepare($sql);
    }
}
?>