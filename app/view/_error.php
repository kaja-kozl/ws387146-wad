<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrayRock — <?php echo $exception->getCode() ?: 'Error' ?></title>
    <link rel="stylesheet" href="/css/_error.css">
</head>
<body>

<nav class="navbar">
    <span class="navbar-brand">Gray<span>Rock.</span></span>
</nav>

<main>
    <?php
    // Determine the error code, message, and type of error to customize the display accordingly
        $code    = $exception->getCode() ?: 500;
        $message = $exception->getMessage();
        $is404   = ($code === 404);
        $isDb    = ($exception instanceof \PDOException);

        $icon       = $is404 ? '🔍' : ($isDb ? '🔌' : '⚠');
        $iconClass  = $is404 ? 'error-icon--404' : '';
        $codeClass  = $is404 ? 'error-code--404' : '';

        $titles = [
            403 => 'Access Forbidden',
            404 => 'Page Not Found',
            503 => 'Service Unavailable',
        ];
        $title = $titles[$code] ?? ($isDb ? 'Database Unavailable' : 'Something Went Wrong');

        $hints = [
            403 => 'You don\'t have permission to view this page.',
            404 => 'The page you\'re looking for doesn\'t exist or has been moved.',
            503 => 'GrayRock cannot reach the database right now. Please try again shortly.',
        ];
        $hint = $hints[$code] ?? ($isDb
            ? 'GrayRock cannot connect to the database. This is likely temporary — please try again in a moment.'
            : 'An unexpected error occurred. Please try again or contact your administrator.');
    ?>
    <div class="error-card">
        <div class="error-icon <?= $iconClass ?>"><?= $icon ?></div>
        <p class="error-code <?= $codeClass ?>"><?= $code ?></p>
        <h1 class="error-title"><?= htmlspecialchars($title) ?></h1>
        <p class="error-message"><?= htmlspecialchars($hint) ?></p>
        <?php if ($isDb): ?>
        <div class="error-detail"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <a href="/" class="back-btn"><?= $is404 ? 'Go Home' : 'Try Again' ?></a>
    </div>
</main>

<footer>
    &copy; <?php echo date('Y'); ?> GrayRock | All Rights Reserved
</footer>

</body>
</html>