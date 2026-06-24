document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('signupForm');
    const usernameField = document.getElementById('username');
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('cpassword');
    const showPasswordCheckbox = document.getElementById('showPassword');
    
    const usernameError = document.getElementById('usernameError');
    const passwordError = document.getElementById('passwordError');
    const confirmPasswordError = document.getElementById('confirmPasswordError');

    usernameField.addEventListener('input', () => validateUsername(usernameField.value));
    passwordField.addEventListener('input', () => validateField(passwordField, passwordError, validatePassword));
    confirmPasswordField.addEventListener('input', () => validateConfirmPassword());
    showPasswordCheckbox.addEventListener('change', togglePasswordVisibility);

    form.addEventListener('submit', event => {
        if (!validateUsername(usernameField.value) || !validateField(passwordField, passwordError, validatePassword) || !validateConfirmPassword()) {
            event.preventDefault();
        }
    });

    // Validate username
    function validateUsername(username) {
        usernameError.textContent = '';
        if (!validateEmail(username) && !validatePhone(username)) {
            usernameError.textContent = 'Invalid username. It should be a valid email or phone number.';
            return false;
        }
        checkUsernameAvailability(username);
        return true;
    }

    // Check username availability via AJAX
    function checkUsernameAvailability(username) {
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "/minipro/signlog/check_username.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        xhr.onload = () => {
            if (xhr.status === 200) {
                usernameError.textContent = xhr.responseText === "exists" ? "Username already exists." : 
                                            (xhr.responseText === "available" ? "Username is available." : "An error occurred.");
                usernameError.classList.toggle("text-danger", xhr.responseText !== "available");
                usernameError.classList.toggle("text-success", xhr.responseText === "available");
            } else {
                usernameError.textContent = "Server error, please try again.";
                usernameError.classList.add("text-danger");
            }
        };

        xhr.onerror = () => {
            usernameError.textContent = 'Network error. Please try again later.';
            usernameError.classList.add("text-danger");
        };

        xhr.send("username=" + encodeURIComponent(username));
    }

    // Validate password field
    function validateField(field, errorElement, validationFunc) {
        errorElement.textContent = '';
        if (!validationFunc(field.value)) {
            errorElement.innerHTML = 'Password must contain:<br>' + 'An uppercase letter, a lowercase letter, a number, a special character, and at least 6 characters.';
            return false;
        }
        return true;
    }

    // Validate confirm password
    function validateConfirmPassword() {
        confirmPasswordError.textContent = '';
        if (passwordField.value !== confirmPasswordField.value) {
            confirmPasswordError.textContent = 'Passwords do not match';
            return false;
        }
        return true;
    }

    // Toggle password visibility
    function togglePasswordVisibility() {
        const type = showPasswordCheckbox.checked ? 'text' : 'password';
        passwordField.type = type;
        confirmPasswordField.type = type;
    }

    // Validate email format
    function validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    // Validate phone number format
    function validatePhone(phone) {
        return /^\d{10}$/.test(phone);
    }

    // Validate password format
    function validatePassword(password) {
        return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]).{6,}$/.test(password);
    }
});
