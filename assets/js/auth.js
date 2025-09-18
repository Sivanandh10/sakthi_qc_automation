// FILE: assets/js/auth.js

document.addEventListener('DOMContentLoaded', () => {

    // --- DOM ELEMENT SELECTORS ---
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const forgotForm = document.getElementById('forgot-form');

    const loginFormElement = document.getElementById('login-form-element');
    const registerFormElement = document.getElementById('register-form-element');
    const forgotFormElement = document.getElementById('forgot-form-element');
    
    // --- FORM SWITCHING AND ANIMATION ---
    gsap.from('.form-container', { duration: 1, y: 50, opacity: 0, ease: 'power3.out' });
    gsap.to(loginForm, { duration: 0.5, opacity: 1, delay: 0.5 });

    const switchForm = (formToHide, formToShow) => {
        gsap.to(formToHide, {
            duration: 0.4, opacity: 0, scale: 0.95, ease: 'power2.in',
            onComplete: () => {
                formToHide.classList.add('hidden');
                formToShow.classList.remove('hidden');
                gsap.fromTo(formToShow, 
                    { opacity: 0, scale: 1.05 },
                    { duration: 0.4, opacity: 1, scale: 1, ease: 'power2.out' }
                );
            }
        });
    };

    document.getElementById('show-register-btn').addEventListener('click', (e) => { e.preventDefault(); switchForm(loginForm, registerForm); });
    document.getElementById('show-forgot-btn').addEventListener('click', (e) => { e.preventDefault(); switchForm(loginForm, forgotForm); });
    document.getElementById('show-login-btn-from-register').addEventListener('click', (e) => { e.preventDefault(); switchForm(registerForm, loginForm); });
    document.getElementById('show-login-btn-from-forgot').addEventListener('click', (e) => { e.preventDefault(); switchForm(forgotForm, loginForm); });

    // --- TOASTER NOTIFICATION ---
    const showToaster = (message, type = 'error') => {
        const container = document.getElementById('toaster-container');
        const toaster = document.createElement('div');
        toaster.className = `toaster ${type}`;
        toaster.textContent = message;
        container.appendChild(toaster);
        setTimeout(() => toaster.classList.add('show'), 10);
        setTimeout(() => {
            toaster.classList.remove('show');
            toaster.addEventListener('transitionend', () => toaster.remove());
        }, 5000);
    };

    // --- VALIDATION ENGINE ---
    const setFieldValidity = (input, isValid, message) => {
        const group = input.closest('.input-group');
        const errorSpan = group.querySelector('.error-message');
        if (!isValid) {
            group.classList.add('error');
            errorSpan.textContent = message;
        } else {
            group.classList.remove('error');
            const defaultMessage = input.id === 'register-password' ? 'Password must be at least 8 characters.' : '';
            errorSpan.textContent = defaultMessage;
        }
    };
    
    // ** UPDATED Live validation with better error handling **
    const validateUniqueField = async (input) => {
        const field = input.name;
        const value = input.value.trim();
        if (value === '') {
            setFieldValidity(input, true); // Clear error if field is emptied
            return;
        }

        try {
            const response = await fetch(`api/validate_user.php?field=${field}&value=${encodeURIComponent(value)}`);
            const responseText = await response.text(); // Get the raw text response first

            if (!response.ok) {
                // This will catch 404 or 500 server errors
                throw new Error(`Server error: ${response.status}. Response: ${responseText}`);
            }

            const result = JSON.parse(responseText); // Now, try to parse the text as JSON
            
            if (result.exists) {
                setFieldValidity(input, false, `${field.replace('_', ' ')} is already taken.`);
            } else {
                setFieldValidity(input, true);
            }
        } catch (error) {
            // This will catch network failures OR JSON parsing errors
            console.error('Validation check failed:', error);
            // We don't show an error to the user, as the server will do the final check on submit.
        }
    };

    document.getElementById('register-email').addEventListener('blur', (e) => validateUniqueField(e.target));
    document.getElementById('register-empid').addEventListener('blur', (e) => validateUniqueField(e.target));
    
    // Live validation for password confirmation
    const validatePasswords = () => {
        const password = document.getElementById('register-password');
        const confirm = document.getElementById('register-password-confirm');
        
        // Check password strength
        if (password.value.length > 0 && password.value.length < 8) {
             setFieldValidity(password, false, "Password must be at least 8 characters.");
        } else {
             setFieldValidity(password, true);
        }

        // Check if passwords match
        if (confirm.value.length > 0 && password.value !== confirm.value) {
            setFieldValidity(confirm, false, "Passwords do not match.");
        } else {
            setFieldValidity(confirm, true);
        }
    };
    document.getElementById('register-password').addEventListener('input', validatePasswords);
    document.getElementById('register-password-confirm').addEventListener('input', validatePasswords);


    // --- PASSWORD TOGGLE ---
    document.querySelectorAll('.password-toggle').forEach(toggle => {
        toggle.addEventListener('click', () => {
            const group = toggle.closest('.input-group');
            const input = group.querySelector('input');
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            toggle.classList.toggle('fa-eye', isPassword);
            toggle.classList.toggle('fa-eye-slash', !isPassword);
        });
    });
    
    // --- AJAX FORM SUBMISSION ---
    const handleFormSubmit = async (formElement, url, successCallback) => {
        const submitButton = formElement.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.textContent;
        submitButton.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Processing...`;
        submitButton.disabled = true;

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(Object.fromEntries(new FormData(formElement)))
            });
            
            const responseText = await response.text();
            if (!response.ok) {
                 throw new Error(`Server error: ${response.status}. Response: ${responseText}`);
            }

            const result = JSON.parse(responseText);

            if (!result.success) {
                throw new Error(result.message || 'An unknown error occurred.');
            }
            
            showToaster(result.message, 'success');
            if (successCallback) successCallback(result);

        } catch (error) {
            showToaster(error.message, 'error');
        } finally {
            submitButton.innerHTML = originalButtonText;
            submitButton.disabled = false;
        }
    };
    
    // --- FORM EVENT LISTENERS ---
    // (These functions remain the same as the previous version)
    loginFormElement.addEventListener('submit', (e) => {
        e.preventDefault();
        handleFormSubmit(loginFormElement, 'api/login.php', (result) => {
            Swal.fire({
                title: 'Success!', text: result.message, icon: 'success',
                timer: 2000, showConfirmButton: false
            }).then(() => { window.location.href = result.redirect || 'dashboard.php'; });
        });
    });

    registerFormElement.addEventListener('submit', (e) => {
        e.preventDefault();
        validatePasswords(); // Final check
        if (registerFormElement.querySelector('.input-group.error')) {
            showToaster('Please fix the errors before submitting.', 'error');
            return;
        }
        handleFormSubmit(registerFormElement, 'api/register.php', () => {
             setTimeout(() => {
                switchForm(registerForm, loginForm);
                registerFormElement.reset();
             }, 2000);
        });
    });

    forgotFormElement.addEventListener('submit', (e) => {
        e.preventDefault();
        handleFormSubmit(forgotFormElement, 'api/forgot_password.php', () => {
            forgotFormElement.reset();
        });
    });
});