<?php
namespace app\core\form;
use app\core\model;

class Form {
    
    # Produces HTML for beginning of a form including how it will be requested
    public static function begin($action, $method, $attributes = []) {
        $attrString = "";

        foreach ($attributes as $key => $value) {
            $attrString .= sprintf(' %s="%s"', $key, htmlspecialchars($value));
        }

        echo sprintf('<form action="%s" method="%s"%s>', 
            $action, 
            $method,
            $attrString);
            
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