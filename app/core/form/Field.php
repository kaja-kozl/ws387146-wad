<?php
namespace app\core\form;
use app\core\Model;

class Field {
    # Lists types of fields
    public const TYPE_TEXT = 'text';
    public const TYPE_PASSWORD = 'password';
    public const TYPE_NUMBER = 'number';
    public const TYPE_EMAIL = 'email';
    public const TYPE_DATE = 'datetime-local" min="2024-06-01T08:30';
    public const TYPE_SELECT = 'select';
    public const TYPE_TEXTAREA = 'textarea';

    public string $type;
    public Model $model;
    public string $attribute;
    public array $options = [];
    public ?string $value = null;
    public bool $readonly = false;

    public function __construct(Model $model, string $attribute) {
        $this->type = self::TYPE_TEXT;
        $this->model = $model;
        $this->attribute = $attribute;
    }

    public function __toString() {
        if ($this->type === self::TYPE_SELECT) {
            return $this->renderSelect();
        }
        if ($this->type === self::TYPE_TEXTAREA) {
            return $this->renderTextarea();
        }
        return $this->renderInput();
    }

    public function setValue(string $value) {
        $this->value = $value;
        return $this;
    }

    # Boilerplate for how the field is to be rendered on the page
    private function renderInput() {
        $class = $this->model->hasError($this->attribute) ? 'invalid-input' : '';
        $value = $this->value ?? $this->model->{$this->attribute};

        return sprintf('
            <div class="form-group">
                <br>
                <label>%s</label>
                <input type="%s" name="%s" value="%s"%s %s>
                <div class="invalid-feedback">
                    %s
                </div>
            </div>
        ', 
            $this->model->labels()[$this->attribute] ?? $this->attribute, 
            $this->type,
            $this->attribute,
            $value,
            $class ? ' class="' . $class . '"' : '',
            $this->readonly ? 'readonly' : '',  // Native HTML readonly for inputs
            $this->model->getFirstError($this->attribute)
        );
    }

    private function renderTextarea() {
        $class = $this->model->hasError($this->attribute) ? 'invalid-input' : '';
        $value = $this->value ?? $this->model->{$this->attribute};

        return sprintf('
            <div class="form-group">
                <br>
                <label>%s</label>
                <textarea name="%s"%s %s>%s</textarea>
                <div class="invalid-feedback">
                    %s
                </div>
            </div>
        ',
            $this->model->labels()[$this->attribute] ?? $this->attribute,
            $this->attribute,
            $class ? ' class="' . $class . '"' : '',
            $this->readonly ? 'readonly' : '',
            htmlspecialchars($value),
            $this->model->getFirstError($this->attribute)
        );
    }

    private function renderSelect() {
        $currentValue = $this->value ?? $this->model->{$this->attribute};

        $options = '';
        foreach ($this->options as $value => $label) {
            $selected = $currentValue == $value ? 'selected' : '';
            $options .= sprintf('<option value="%s" %s>%s</option>', $value, $selected, $label);
        }

        // <select> doesn't support readonly natively — instead we render a visible
        // disabled select for appearance, plus a hidden input to actually submit the value
        if ($this->readonly) {
            return sprintf('
                <div class="form-group">
                    <br>
                    <label>%s</label>
                    <select class="%s" disabled>
                        %s
                    </select>
                    <input type="hidden" name="%s" value="%s">
                    <div class="invalid-feedback">
                        %s
                    </div>
                </div>
            ',
                $this->model->labels()[$this->attribute] ?? $this->attribute,
                $this->model->hasError($this->attribute) ? 'invalid-input' : '',
                $options,
                $this->attribute,
                htmlspecialchars($currentValue),
                $this->model->getFirstError($this->attribute)
            );
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

    # If fields required are of a different, call this when creating the form
    public function textareaField() {
        $this->type = self::TYPE_TEXTAREA;
        return $this;
    }

    public function passwordField() {
        $this->type = self::TYPE_PASSWORD;
        return $this;
    }

    public function numberField() {
        $this->type = self::TYPE_NUMBER;
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

    public function readonly(bool $condition = true) {
        $this->readonly = $condition;
        return $this;
    }
}

?>