<?php
namespace app\core;

abstract class Model {
    public const RULE_REQUIRED = 'required';
    public const RULE_EMAIL = 'email';
    public const RULE_MIN = 'min';
    public const RULE_MAX = 'max';
    public const RULE_MATCH = 'match';
    public const RULE_UNIQUE = 'unique';
    public const RULE_UNIQUE_EXCEPT = 'unique_except';
    public const RULE_PASSWORD_COMPLEXITY = 'password_complexity';
    public const RULE_DATE_MIN = 'date_min';

    # Loads data from an array and assigns valid variables as attributes in an object
    # Used with form inputs
    public function loadData($data) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;  
            }
        }
    }

    # Demonstrates how form fields should be labelled (see child classes)
    public function labels(): array {
        return [];
    }

    # Demonstrates which rules apply to which fields
    abstract public function rules(): array;

    public array $errors = [];

    # All rules and how they are validated
    # When called, it checks through all the rules that exist for that model and verifies against them
    public function validate() {
        foreach ($this->rules() as $attribute => $rules) {
            $value = $this->{$attribute};
            foreach ($rules as $rule) {
                $ruleName = $rule;
                if (!is_string($ruleName)) {
                    $ruleName = $rule[0];
                }

                if ($ruleName === self::RULE_REQUIRED && !$value) {
                    $this->addError($attribute, self::RULE_REQUIRED);
                }

                if ($ruleName === self::RULE_EMAIL && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($attribute, self::RULE_EMAIL);
                }

                if ($ruleName === self::RULE_MIN && strlen($value) < $rule['min']) {
                    $this->addError($attribute, self::RULE_MIN, $rule);
                }

                if ($ruleName === self::RULE_MAX && strlen($value) > $rule['max']) {
                    $this->addError($attribute, self::RULE_MAX, $rule);
                }

                if ($ruleName === self::RULE_MATCH && $value !== $this->{$rule['match']}) {
                    $this->addError($attribute, self::RULE_MATCH, $rule);
                }

                if ($ruleName === self::RULE_UNIQUE) {
                    $className = $rule['class'];
                    $uniqueAttr = $rule['attribute'] ?? $attribute;
                    $tableName = $className::tableName();
                    $statement = Application::$app->db->prepare("SELECT * FROM $tableName WHERE $uniqueAttr = :$uniqueAttr");
                    $statement->bindValue(":$uniqueAttr", $value);
                    $statement->execute();
                    $record = $statement->fetchObject();
                    if ($record) {
                        $this->addError($attribute, self::RULE_UNIQUE, ['attribute' => $attribute]);
                    }
                }

                if ($ruleName === self::RULE_UNIQUE_EXCEPT) {
                    $className  = $rule['class'];
                    $uniqueAttr = $rule['attribute'] ?? $attribute;
                    $exceptAttr = $rule['except'];        // The column to exclude on e.g. 'uid'
                    $exceptVal  = $this->{$rule['except_value']}; // The value to exclude e.g. $this->uid
                    $tableName  = $className::tableName();
                    $statement  = Application::$app->db->pdo->prepare(
                        "SELECT * FROM $tableName WHERE $uniqueAttr = :val AND $exceptAttr != :except"
                    );
                    $statement->bindValue(':val',    $this->{$uniqueAttr});
                    $statement->bindValue(':except', $exceptVal);
                    $statement->execute();
                    if ($statement->fetchObject()) {
                        $this->addError($attribute, self::RULE_UNIQUE_EXCEPT, ['attribute' => $attribute]);
                    }
                }

                if ($ruleName === self::RULE_PASSWORD_COMPLEXITY) {
                    if (
                        strlen($value) < 8 ||
                        !preg_match('/[A-Z]/', $value) ||
                        !preg_match('/[a-z]/', $value) ||
                        !preg_match('/[0-9]/', $value) ||
                        !preg_match('/[\W_]/', $value)  // special character
                    ) {
                        $this->addError($attribute, self::RULE_PASSWORD_COMPLEXITY);
                    }
                }

                if ($ruleName === self::RULE_MATCH && $value !== $this->{$rule['match']}) {
                    $this->addError($attribute, self::RULE_MATCH, $rule);
                }

                if ($ruleName === self::RULE_DATE_MIN && $value < $this->{$rule['compare_date']}) {
                    $this->addError($attribute, self::RULE_DATE_MIN, $rule);
                }
            }
        }
        return empty($this->errors);
    }

    # Adds error messages when rules are not met on forms
    private function addError(string $attribute, string $rule, $params = []) {
        $message = $this->errorMessages()[$rule] ?? '';
        foreach ($params as $key => $value) {
            $message = str_replace("{{$key}}", $value, $message);
        }
        $this->errors[$attribute][] = $message;
    }

    public function addError_public(string $attribute, string $message) {
        $this->errors[$attribute][] = $message;
    }

    public function errorMessages() {
        return [
            self::RULE_REQUIRED => 'This field is required',
            self::RULE_EMAIL => 'This field must be a valid email address',
            self::RULE_MIN => 'Min length of this field must be {min}',
            self::RULE_MAX => 'Max length of this field must be {max}',
            self::RULE_MATCH => 'This field must be the same as {match}',
            self::RULE_UNIQUE => 'Record with this {attribute} already exists',
            self::RULE_UNIQUE_EXCEPT => 'Record with this {attribute} already exists',
            self::RULE_PASSWORD_COMPLEXITY => 'Password must be at least 8 characters and contain an uppercase letter, lowercase letter, number, and special character',
            self::RULE_DATE_MIN => 'End date must be after start date'
        ];
    }

    public function hasError($attribute)
    {
        return $this->errors[$attribute] ?? false;
    }

    public function getFirstError($attribute) {
        return $this->errors[$attribute][0] ?? false;
    }
}

?>