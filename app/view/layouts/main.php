<?php
use app\core\Application;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($this->title) ?> — GrayRock</title>
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous">
    <link rel="stylesheet" href="/css/main.css">
</head>

<body class="d-flex flex-column min-vh-100">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark site-navbar" aria-label="Main navigation">
        <div class="container-fluid px-4">

            <!-- Logo -->
            <a class="navbar-brand" href="/courses" aria-label="GrayRock home">
                <img src="/logo.svg" alt="GrayRock" class="navbar-logo">
            </a>

            <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <!-- Left links -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link<?= (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) === '/courses') ? ' active' : '' ?>"
                            href="/courses"
                            <?= (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) === '/courses') ? 'aria-current="page"' : '' ?>>
                            Courses
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) === '/profile') ? ' active' : '' ?>"
                            href="/profile"
                            <?= (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) === '/profile') ? 'aria-current="page"' : '' ?>>
                            Profile
                        </a>
                    </li>
                </ul>

                <!-- Right: user + logout -->
                <div class="d-flex align-items-center gap-3">
                    <?php if (Application::$app->user): ?>
                        <a class="navbar-user" href="/profile"
                            aria-label="Signed in as <?= htmlspecialchars(Application::$app->user->getDisplayName()) ?>">
                            <?= htmlspecialchars(Application::$app->user->getDisplayName()) ?>
                        </a>
                        <form method="POST" action="/logout" style="display:inline;">
                            <button type="submit" class="navbar-logout" aria-label="Log out of GrayRock">Logout</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <?php $successMessage = Application::$app->session->getFlash('success'); ?>
    <?php if ($successMessage !== null): ?>
        <div class="alert alert-info alert-dismissible fade show site-flash"
            role="alert" aria-live="polite" aria-atomic="true">
            <?= htmlspecialchars($successMessage['value']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close notification"></button>
        </div>
    <?php endif; ?>

    <!-- Page content -->
    <main class="flex-grow-1" id="main-content">
        {{content}}
    </main>

    <!-- Footer -->
    <footer class="site-footer" aria-label="Site footer">
        <div class="footer-inner">
            <p class="footer-copy">&copy; <?= date('Y') ?> GrayRock | All Rights Reserved</p>
            <nav class="footer-links" aria-label="Footer navigation">
                <a href="/privacy">Privacy</a>
                <a href="/cookies">Cookies</a>
                <a href="/help">Help</a>
            </nav>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>