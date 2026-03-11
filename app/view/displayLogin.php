<?php
    if (!isset($model)) {
        $model = new \app\model\UserModel();
    }
?>

<head>
    <link rel="stylesheet" href="/css/displayLogin.css">
</head>

<div class="d-flex min-vh-100 login-wrapper">

    <!-- Left Panel: transparent, sits over background.jpg -->
    <aside class="d-flex align-items-center login-left" aria-label="GrayRock Training Centre information">
        <div class="login-left-content">
            <img src="/logo.svg" alt="GrayRock logo" class="login-logo mb-4">
            <div class="ps-3">
                <h2 class="fw-bold text-white ls-wide mb-3 fs-2">TRAINING CENTRE</h2>
                <p class="text-white-50 mb-2 fs-5">Expand your influence. Sharpen your expertise.</p>
                <ul class="text-white-50 ps-3 fs-5" aria-label="Platform highlights">
                    <li>Access exclusive courses curated for GrayRock personnel</li>
                    <li>Develop the skills that keep the world running smoothly</li>
                    <li>View, enrol and create courses of all types</li>
                </ul>
            </div>
        </div>
    </aside>

    <!-- Right Panel: white card -->
    <main class="d-flex align-items-center justify-content-center bg-white login-right">
        <div class="login-form-box p-5 w-100">
            <h1 class="fw-bold ls-wide text-dark mb-5 display-5" id="signin-heading">SIGN IN</h1>

            <?php $form = \app\core\form\Form::begin('', "post", ['aria-labelledby' => 'signin-heading', 'novalidate' => 'novalidate']); ?>
                <div class="mb-4">
                    <?php echo $form->field($model, 'email')->emailField() ?>
                </div>
                <div class="mb-4">
                    <?php echo $form->field($model, 'password')->passwordField() ?>
                </div>
                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn login-btn px-5 py-2 fs-5" aria-label="Sign in to GrayRock Training Centre">Sign in</button>
                </div>
            <?php $form->end(); ?>
        </div>
    </main>

</div>