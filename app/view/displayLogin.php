<?php
if (!isset($model)) {
    $model = new \app\model\UserModel();
}
?>

<head>
    <link rel="stylesheet" href="/css/displayLogin.css">
</head>

<div class="d-flex min-vh-100 login-wrapper">

    <!-- Left Panel -->
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

    <!-- Right Panel -->
    <main class="d-flex align-items-center justify-content-center bg-white login-right">
        <div class="login-form-box p-5 w-100">
            <h1 class="fw-bold ls-wide text-dark mb-5 display-5" id="signin-heading">SIGN IN</h1>

            <?php $form = \app\core\form\Form::begin('', "post", [
                'id'               => 'login-form',
                'aria-labelledby'  => 'signin-heading',
                'novalidate'       => 'novalidate'
            ]); ?>
                <div class="mb-4">
                    <?php echo $form->field($model, 'email')->emailField() ?>
                </div>
                <div class="mb-4">
                    <?php echo $form->field($model, 'password')->passwordField() ?>
                </div>

                <!-- Error message area -->
                <div id="login-error" class="alert alert-danger d-none" role="alert"></div>

                <div class="d-flex justify-content-end align-items-center mt-4 gap-3">
                    <!-- Spinner — hidden until submit -->
                    <div id="login-spinner" class="login-spinner d-none" aria-label="Signing in…">
                        <div class="spinner-wheel"></div>
                    </div>
                    <button type="submit" id="login-btn" class="btn login-btn px-5 py-2 fs-5"
                        aria-label="Sign in to GrayRock Training Centre">Sign in</button>
                </div>
            <?php $form->end(); ?>
        </div>
    </main>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form    = document.querySelector('#login-form');
    const btn     = document.querySelector('#login-btn');
    const spinner = document.querySelector('#login-spinner');
    const errorEl = document.querySelector('#login-error');

    function setLoading(loading) {
        btn.disabled     = loading;
        spinner.classList.toggle('d-none', !loading);
    }

    function showError(message) {
        errorEl.textContent = message;
        errorEl.classList.remove('d-none');
    }

    function clearError() {
        errorEl.textContent = '';
        errorEl.classList.add('d-none');
        form.querySelectorAll('.invalid-input').forEach(el => el.classList.remove('invalid-input'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        clearError();
        setLoading(true);

        fetch('', {
            method: 'POST',
            body:   new FormData(form)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Keep spinner going during redirect
                window.location.href = data.redirect;
                return;
            }

            setLoading(false);

            // Show field-level errors if present
            if (data.errors && typeof data.errors === 'object') {
                let firstMessage = null;
                Object.entries(data.errors).forEach(([field, messages]) => {
                    const input = form.querySelector(`[name="${field}"]`);
                    const msg   = Array.isArray(messages) ? messages[0] : messages;
                    if (input) {
                        input.classList.add('invalid-input');
                        const fb = input.closest('.form-group')?.querySelector('.invalid-feedback');
                        if (fb) fb.textContent = msg;
                    }
                    if (!firstMessage) firstMessage = msg;
                });
                if (firstMessage) showError(firstMessage);
            } else {
                showError('Invalid email or password.');
            }
        })
        .catch(() => {
            setLoading(false);
            showError('Something went wrong. Please try again.');
        });
    });

    // Clear errors when user starts typing
    form.querySelectorAll('input').forEach(input => {
        input.addEventListener('input', clearError);
    });
});
</script>