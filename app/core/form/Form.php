<?php
namespace app\core\form;
use app\core\model;

class Form {
    
    # Produces HTML for beginning of a form including how it will be requested
    public static function begin($action, $method) {
        echo sprintf('<form action="%s" method="%s">', $action, $method);
        return new Form();
    }

    public static function end() {
        echo '</form>';
    }

    # Creates a field mapped to a model and one of its attributes
    public function field(Model $model, $attribute) {
        return new Field($model, $attribute);
    }
}

?>