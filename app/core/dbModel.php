<?php
namespace app\core;

abstract class dbModel extends Model {
    
    # Children of this class are coupled to a database table. These return attributes and primaryKeys of that class/table
    abstract public static function tableName(): string;
    abstract public static function attributes(): array;
    abstract public static function primaryKey(): string;
    
    # Creates a new record in a database
    public function save() {

        $tableName = $this->tableName();
        // Could read the database schema to get columns dynamically
        $attributes = $this->attributes();

        # According to the parameters passed, INSERT INTO table
        $params = array_map(fn($attr) => ":$attr", $attributes);
        $statement = self::prepare("INSERT INTO $tableName (".implode(',', $attributes).") 
            VALUES (".implode(',', $params).")");

        foreach ($attributes as $attribute) {
            $statement->bindValue(":$attribute", $this->{$attribute});
        }

        $statement->execute();
        return true;
    }

    # Finds an instance of the parameter $where in the databases table according to the attribute name
    public static function findOne($where) {
        $tableName = static::tableName();
        $attributes = array_keys($where);
        $sql_where = implode("AND ", array_map(fn($attr) => "$attr = :$attr", $attributes));
        $statement = self::prepare("SELECT * FROM $tableName WHERE $sql_where");

        # For arrays, when the $where variable has many different attributes to check against
        foreach($where as $key => $value) {
            $statement->bindValue(":$key", $value);
        }

        $statement->execute();
        return $statement->fetchObject(static::class);
    }

    # Executes a prepared statement and returns it
    public static function prepare($sql) {
        return Application::$app->db->pdo->prepare($sql);
    }

    # Enables creation of READ operations in database
    public function read($attributes, $where = []) {
        // Take the table of the model requesting it
        $tableName = $this->tableName();

        $sql = "SELECT $attributes FROM $tableName";

        if (!empty($where)) {
            $conditions = implode(' AND ', $where);
            $sql .= " WHERE $conditions";
        }

        $sql .= ";";

        $statement = self::prepare($sql);
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_CLASS, static::class);
    }

    public function delete($where = [], $params=[]) {
        $tableName = $this->tableName();

        $sql = "DELETE FROM $tableName";

        if (!empty($where)) {
            $conditions = implode(' AND ', $where);
            $sql .= " WHERE $conditions";
        }

        $sql .= ";";

        $statement = self::prepare($sql);

        return $statement->execute($params);
    }

    public function update(array $set = [], array $params = [], string $where = '') {
        $tableName = $this->tableName();

        $sql = "UPDATE $tableName SET " . implode(', ', $set);
        
        if (!empty($where)) {
            $sql .= " WHERE $where";
        }

        $sql .= ";";

        $statement = self::prepare($sql);

        return $statement->execute($params);   
    }
}
?>