<?php
// Header, footer, and other layout elements can go here as well as any common libraries
use app\core\Application;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h1>NavBar placeholder</h1>

    <div class="container"> 
        <?php 
            $successMessage = Application::$app->session->getFlash('success');
            if ($successMessage !== null): 
        ?>
            <div class="alert-success">
                <?php echo $successMessage['value']; ?>
            </div>
        <?php endif; ?>
    </div>

    {{content}}
</body>
</html>