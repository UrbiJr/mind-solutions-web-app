document.addEventListener("DOMContentLoaded", () => {

    /* AUTH & SETTINGS */

    /* validate sign up fields using regex patterns */

    const submitButton = document.getElementById('signUp');
    const emailPassword = document.querySelectorAll('input[type="email"], input[type="password"]');
    const signUpInputs = document.querySelectorAll('.sign-up-form input');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirmPassword');
    const patterns = {
        email: /^([a-z\d\.-]+)@([a-z\d-]+)\.([a-z]{2,8})(\.[a-z]{2,8})?$/,
        password: /^[\d\w@!-]{8,20}$/i,
        currentPassword: /^.+$/i,
    };

    function updateSubmitButton() {
        if (submitButton) {
            const allValid = [...signUpInputs].every((input) => input.classList.contains('is-valid'));
            if (allValid) {
                submitButton.removeAttribute('disabled');
            } else {
                submitButton.setAttribute('disabled', true);
            }
        }
    }

    emailPassword.forEach((input) => {
        if (input) {
            input.addEventListener('keyup', (e) => {
                validate(e.target, patterns[e.target.attributes.id.value]);
                updateSubmitButton();
            });
        }
    });

    if (confirmPassword) {
        confirmPassword.addEventListener('keyup', (e) => {
            if (e.target.value === password.value) {
                e.target.className = 'form-control is-valid';
            } else {
                e.target.className = 'form-control is-invalid';
            }
            updateSubmitButton();
        });
    }

    emailPassword.forEach((input) => {
        if (input) {
            validate(input, patterns[input.id]);
            updateSubmitButton();
        }
    });

    /*
        validate terms of use checkbox.
    */
    const checkbox = document.getElementById('agreeTermsOfUse');

    if (checkbox) {
        checkbox.addEventListener('change', (e) => {
            if (e.target.checked) {
                e.target.className = 'form-check-input is-valid';
            } else {
                e.target.className = 'form-check-input is-invalid';
            }
            updateSubmitButton();
        });
    }
});