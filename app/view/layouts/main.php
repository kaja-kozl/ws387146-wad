<?php
// Header, footer, and other layout elements can go here as well as any common libraries
use app\core\Application;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $this->title ?></title>
    <style>
        body {
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-size: 100% 100%;
        }
    </style>
</head>
<body>
    <h1>NavBar placeholder</h1> <!--Remember to remove certain options if the user is not logged in-->
    <h1> Username: <?php if (Application::$app->user) { echo Application::$app->user->getDisplayName(); } ?>
    <a href="/logout">Logout</a>

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