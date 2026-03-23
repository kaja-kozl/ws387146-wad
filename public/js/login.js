// Attaches event listeners to login form
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

    // Handles form submissions, shows loading state, and displays errors
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        clearError();

        // Show a loading spinner while waiting for the response
        setLoading(true);

        // POST request is sent to the same route with the form data
        fetch('', {
            method: 'POST',
            body:   new FormData(form)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Redirects to the specified URL on successful login
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