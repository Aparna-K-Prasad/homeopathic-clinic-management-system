document.addEventListener("DOMContentLoaded", function () {
    const generalForm = document.getElementById('general-info-form');
    const contactForm = document.getElementById('contact-details-form');

    // Reusable function to set error messages
    function setError(input, message) {
        const errorElement = document.getElementById(`${input.id}-error`);
        errorElement.textContent = message;

        // Apply different colors for left and right sections
        if (input.closest('.left-section')) {
            errorElement.style.color = 'rgb(176, 62, 62)'; // Red for left section
        } else if (input.closest('.right-section')) {
            errorElement.style.color = 'white'; // White for right section
        }
    }

    // Generic input validation (skipping marital-status)
    function validateInput(input, pattern = null, customMessage = null) {
        if (input.id === 'marital-status') return true; // Skip marital-status

        if (input.value.trim() === "") {
            setError(input, `${input.placeholder || 'This field'} is required.`);
            return false;
        } else if (pattern && !pattern.test(input.value.trim())) {
            setError(input, customMessage || "Invalid input.");
            return false;
        } else {
            setError(input, "");
            return true;
        }
    }

    // Add blur event listeners for real-time validation
    document.querySelectorAll('input, select, textarea').forEach(input => {
        input.addEventListener('blur', () => validateInput(input));
    });

    // Specific validations for phone and email
    const phonePattern = /^[0-9]{10}$/;
    const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

    document.getElementById('phone').addEventListener('blur', function () {
        validateInput(this, phonePattern, "Please enter a valid phone number (10 digits).");
    });

    document.getElementById('email').addEventListener('blur', function () {
        if (this.value.trim() === "") {
            setError(this, ""); // Clear error if empty
            return true; // Skip validation for empty field
        }
        validateInput(this, emailPattern, "Please enter a valid email address.");
    });
    

    // Age calculation on DOB change
    document.getElementById('dob').addEventListener('change', calculateAge);

    function calculateAge() {
        const dobInput = document.getElementById('dob');
        const dob = new Date(dobInput.value);
        const today = new Date();
        let age = today.getFullYear() - dob.getFullYear();
        let monthDiff = today.getMonth() - dob.getMonth();
        let dayDiff = today.getDate() - dob.getDate();

        if (dayDiff < 0) {
            monthDiff--;
            dayDiff += new Date(today.getFullYear(), today.getMonth(), 0).getDate();
        }

        if (monthDiff < 0) {
            age--;
            monthDiff += 12;
        }

        const ageDisplay = age > 0 ? `${age} Y` :
            monthDiff > 0 ? `${monthDiff} M` : `${dayDiff} D`;

        document.getElementById('age').value = ageDisplay || "0 D";
    }

    // Form submission handlers with validation
    function handleSubmit(event, form, validations) {
        let isValid = true;
        validations.forEach(validate => {
            if (!validate()) isValid = false;
        });

        if (!isValid) event.preventDefault(); // Prevent submission if invalid
    }

    // General form submission validation
    generalForm.addEventListener('submit', function (event) {
        handleSubmit(event, this, [
            () => validateInput(document.getElementById('name')),
            () => validateInput(document.getElementById('dob')),
            () => validateInput(document.getElementById('blood-group')) // Marital-status skipped
        ]);
    });

    // Contact form submission validation
    contactForm.addEventListener('submit', function (event) {
        handleSubmit(event, this, [
            () => validateInput(document.getElementById('phone'), phonePattern, "Please enter a valid phone number (10 digits)."),
            () => validateInput(document.getElementById('email'), emailPattern, "Please enter a valid email address."),
            () => validateInput(document.getElementById('address'))
        ]);
    });
});
