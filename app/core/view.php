<?php

namespace app\core;

class View {
    public string $title = '';

        # Displays the page and any extra parameters passed to it
    public function renderView($view, $params = []) {
        $viewContent = $this->renderOnlyView($view, $params); 
        $layoutContent = $this->layoutContent(); # Renders the common layout for a page of that view
        return str_replace('{{content}}', $viewContent, $layoutContent);
    }

    # Renders the main layout content which icludes repeated elements like header and footer
    protected function layoutContent() {
        $layout = Application::$app->layout;
        if (Application::$app->controller) {
            $layout = Application::$app->controller->layout;
        }
        ob_start(); # Captures output in an internal buffer
        include_once Application::$ROOT_DIR . "\app\\view\\layouts\\$layout.php"; # File layout goes into active buffer
        return ob_get_clean(); # Returns the buffer content as a string and clears it
    }
    
    # Displays the content to be viewed without the header, footer or layout
    protected function renderOnlyView($view, $params) {
        # Stores the parameters as variables to be used in the fields of the view file
        foreach ($params as $key => $value) {
            $$key = $value;
        }

        # Loads view entirely, loading in parameters afterwards
        # then returning everything in the buffer (HTML page) before cleaning and closing it
        ob_start();
        include_once Application::$ROOT_DIR . "\app\\view\\$view.php";
        return ob_get_clean();
    }
}

?>