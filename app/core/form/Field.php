<?php
namespace app\core\form;
use app\core\Model;

class Field {
    public const TYPE_TEXT = 'text';
    public const TYPE_PASSWORD = 'password';
    public const TYPE_NUMBER = 'number';
    public const TYPE_EMAIL = 'email';
    public const TYPE_DATE = 'datetime-local" min="2024-06-01T08:30';

    public string $type;
    public Model $model;
    public string $attribute;

    public function __construct(\app\core\Model $model, string $attribute) {
        $this->type = self::TYPE_TEXT;
        $this->model = $model;
        $this->attribute = $attribute;
    }

    public function __toString() {
        return sprintf('
            <div class="form-group">
                <br>
                <label>%s</label>
                <input type="%s" name="%s" value="%s" class="%s">
                <div class="invalid-feedback">
                    %s
                </div>
            </div>
        ', 
            $this->attribute, 
            $this->type,
            $this->attribute,
            $this->model->{$this->attribute},
            $this->model->hasError($this->attribute) ? 'invalid-input' : '',
            $this->model->getFirstError($this->attribute)
        );
    }

    public function passwordField() {
        $this->type = self::TYPE_PASSWORD;
        return $this;
    }

    public function dateField() {
        $this->type = self::TYPE_DATE;
        return $this;
    }

    public function emailField() {
        $this->type = self::TYPE_EMAIL;
        return $this;
    }
}

?>