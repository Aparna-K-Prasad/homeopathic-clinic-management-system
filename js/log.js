document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('login-form');
    const passwordField = document.getElementById('password');
    const errorMessage = document.getElementById('error-message');
    const showPasswordCheckbox = document.getElementById('showPassword');

    // Toggle password visibility
    showPasswordCheckbox.addEventListener('change', function () {
        passwordField.type = showPasswordCheckbox.checked ? 'text' : 'password';
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        const formData = new FormData(form);
        const urlParams = new URLSearchParams(window.location.search);
        const redirectToBooking = urlParams.get('redirectToBooking') === 'true';
        const redirectToReg = urlParams.get('redirectToReg') === 'true';  // Detect registration scenario

        // Append redirection parameters to the form data
        formData.append('redirectToBooking', redirectToBooking ? 'true' : 'false');
        formData.append('redirectToReg', redirectToReg ? 'true' : 'false');

        fetch('log.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.redirected) {
                window.location.href = response.url;
            } else {
                return response.text();
            }
        })
        .then(data => {
            if (data) {
                errorMessage.style.display = 'block';
                errorMessage.textContent = data;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            errorMessage.style.display = 'block';
            errorMessage.textContent = 'An unexpected error occurred.';
        });
    });
});
