<?php
namespace app\core\form;
use app\core\Model;

class Field {
    public const TYPE_TEXT = 'text';
    public const TYPE_PASSWORD = 'password';
    public const TYPE_NUMBER = 'number';
    public const TYPE_EMAIL = 'email';
    public const TYPE_DATE = 'datetime-local" min="2024-06-01T08:30';
    public const TYPE_SELECT = 'select';

    public string $type;
    public Model $model;
    public string $attribute;
    public array $options = [];

    public function __construct(\app\core\Model $model, string $attribute) {
        $this->type = self::TYPE_TEXT;
        $this->model = $model;
        $this->attribute = $attribute;
    }

    public function __toString() {
        if ($this->type === self::TYPE_SELECT) {
            return $this->renderSelect();
        }
        return $this->renderInput();
    }

    private function renderInput() {
        $class = $this->model->hasError($this->attribute) ? 'invalid-input' : '';
        return sprintf('
            <div class="form-group">
                <br>
                <label>%s</label>
                <input type="%s" name="%s" value="%s"%s>
                <div class="invalid-feedback">
                    %s
                </div>
            </div>
        ', 
            $this->model->labels()[$this->attribute] ?? $this->attribute, 
            $this->type,
            $this->attribute,
            $this->model->{$this->attribute},
            $class ? ' class="' . $class . '"' : '',
            $this->model->getFirstError($this->attribute)
        );
    }

    private function renderSelect() {
        $options = '';
        foreach ($this->options as $value => $label) {
            $selected = $this->model->{$this->attribute} == $value ? 'selected' : '';
            $options .= sprintf('<option value="%s" %s>%s</option>', $value, $selected, $label);
        }
        return sprintf('
            <div class="form-group">
                <br>
                <label>%s</label>
                <select name="%s" class="%s">
                    <option value="">Select %s</option>
                    %s
                </select>
                <div class="invalid-feedback">
                    %s
                </div>
            </div>
        ', 
            $this->model->labels()[$this->attribute] ?? $this->attribute,
            $this->attribute,
            $this->model->hasError($this->attribute) ? 'invalid-input' : '',
            $this->attribute,
            $options,
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

    public function dropDownField(array $options = []) {
        $this->type = self::TYPE_SELECT;
        $this->options = $options;
        return $this;
    }
}

?>