<?php
namespace app\core;

abstract class dbModel extends Model {
    
    abstract public static function tableName(): string;

    abstract public function attributes(): array;
    
    // Creates a new record in the database
    public function save() {

        $tableName = $this->tableName();
        // Could read the database schema to get columns dynamically
        $attributes = $this->attributes();
        $params = array_map(fn($attr) => ":$attr", $attributes);
        $statement = self::prepare("INSERT INTO $tableName (".implode(',', $attributes).") 
            VALUES (".implode(',', $params).")");

        foreach ($attributes as $attribute) {
            $statement->bindValue(":$attribute", $this->{$attribute});
        }
        $statement->execute();
        return true;
    }

    // Executes a prepared statement and returns it
    public static function prepare($sql) {
        return Application::$app->db->pdo->prepare($sql);
    }
}
?>