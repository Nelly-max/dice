
/*
|--------------------------------------------------------------------------
| PASSWORD VISIBILITY
|--------------------------------------------------------------------------
*/

const centeredFormsContainer = document.querySelector('.centered-forms-container');
const pwShowHide = document.querySelectorAll('.showHidePw');
const pwFields = document.querySelectorAll('.password');
const signUp = document.querySelector('.signup-link');
const login = document.querySelector('.login-link');

pwShowHide.forEach(function (eyeIcon) {
    eyeIcon.addEventListener('click', function () {
        const shouldShowPassword = Array.from(pwFields).some(function (field) {
            return field.type === 'password';
        });

        pwFields.forEach(function (field) {
            field.type = shouldShowPassword ? 'text' : 'password';
        });

        pwShowHide.forEach(function (icon) {
            // works with both Font Awesome (fa-eye) and the older Unicons (uil-eye) icons
            if (shouldShowPassword) {
                icon.classList.replace('uil-eye-slash', 'uil-eye');
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            } else {
                icon.classList.replace('uil-eye', 'uil-eye-slash');
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            }
        });
    });
});


/*
|--------------------------------------------------------------------------
| SIGNUP / LOGIN FORM SWITCHING
|--------------------------------------------------------------------------
*/

if (signUp && centeredFormsContainer) {
    signUp.addEventListener('click', function (event) {
        event.preventDefault();
        centeredFormsContainer.classList.add('active');
    });
}

if (login && centeredFormsContainer) {
    login.addEventListener('click', function (event) {
        event.preventDefault();
        centeredFormsContainer.classList.remove('active');
    });
}


/*
|--------------------------------------------------------------------------
| OTP FORM
|--------------------------------------------------------------------------
*/

(function initializeOtpForm() {
    const form = document.getElementById('otp-form');

    if (!form || form.dataset.otpInitialized === 'true') {
        return;
    }

    const inputs = Array.from(form.querySelectorAll('.digit-input'));
    const hiddenOtp = form.querySelector('#otp');
    const countdown = form.querySelector('#otp-countdown');
    const resendLink = form.querySelector('#resend-otp');

    if (!inputs.length || !hiddenOtp) {
        return;
    }

    form.dataset.otpInitialized = 'true';

    const expiresAt = Number(form.dataset.expiresAt || 0);
    const resendUrl = form.dataset.resendUrl || '';

    /*
    |--------------------------------------------------------------------------
    | OTP CHARACTER VALIDATION
    |--------------------------------------------------------------------------
    | Allowed characters:
    | A-H, J-N, P-Z and digits 2-9.
    | Excludes I, O, 0 and 1.
    |--------------------------------------------------------------------------
    */

    function cleanOtp(value) {
        return String(value || '')
            .toUpperCase()
            .replace(/[^A-HJ-NP-Z2-9]/g, '');
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE HIDDEN OTP INPUT
    |--------------------------------------------------------------------------
    */

    function updateOtp() {
        hiddenOtp.value = inputs
            .map(function (input) {
                return input.value.toUpperCase();
            })
            .join('');

        inputs.forEach(function (input) {
            input.classList.toggle('filled', input.value !== '');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | INITIAL OTP INPUT STATE
    |--------------------------------------------------------------------------
    */

    inputs.forEach(function (input, index) {
        input.maxLength = 1;
        input.autocomplete = 'one-time-code';
        input.disabled = index !== 0;

        input.addEventListener('input', function () {
            const value = cleanOtp(this.value).slice(-1);

            this.value = value;

            updateOtp();

            if (value && index < inputs.length - 1) {
                inputs[index + 1].disabled = false;
                inputs[index + 1].focus();
            }
        });

        /*
        |--------------------------------------------------------------------------
        | BACKSPACE AND ARROW NAVIGATION
        |--------------------------------------------------------------------------
        */

        input.addEventListener('keydown', function (event) {
            if (event.key === 'Backspace') {
                if (this.value) {
                    this.value = '';
                    updateOtp();
                    return;
                }

                if (index > 0) {
                    event.preventDefault();

                    inputs[index - 1].disabled = false;
                    inputs[index - 1].focus();
                    inputs[index - 1].value = '';

                    updateOtp();
                }
            }

            if (event.key === 'ArrowLeft' && index > 0) {
                event.preventDefault();
                inputs[index - 1].focus();
            }

            if (event.key === 'ArrowRight' && index < inputs.length - 1) {
                event.preventDefault();

                if (inputs[index + 1].disabled) {
                    inputs[index + 1].disabled = false;
                }

                inputs[index + 1].focus();
            }
        });

        /*
        |--------------------------------------------------------------------------
        | PASTE OTP
        |--------------------------------------------------------------------------
        */

        input.addEventListener('paste', function (event) {
            event.preventDefault();

            const clipboard = event.clipboardData || window.clipboardData;

            if (!clipboard) {
                return;
            }

            const pasted = cleanOtp(
                clipboard.getData('text')
            ).slice(0, inputs.length);

            // Clear old values before inserting the pasted code.
            inputs.forEach(function (otpInput, i) {
                otpInput.value = pasted[i] || '';
                otpInput.disabled = i > pasted.length;
            });

            // Keep the next empty input available.
            const nextIndex = Math.min(pasted.length, inputs.length - 1);

            if (inputs[nextIndex]) {
                inputs[nextIndex].disabled = false;
            }

            updateOtp();

            if (pasted.length === inputs.length) {
                inputs[inputs.length - 1].focus();
            } else {
                inputs[nextIndex]?.focus();
            }
        });
    });

    /*
    |--------------------------------------------------------------------------
    | OTP FORM SUBMISSION
    |--------------------------------------------------------------------------
    */

    form.addEventListener('submit', function (event) {
        updateOtp();

        if (hiddenOtp.value.length !== inputs.length) {
            event.preventDefault();

            (window.notify ? window.notify.error : alert)(
                'Please enter all ' +
                inputs.length +
                ' characters of your verification code.'
            );

            const firstEmptyIndex = inputs.findIndex(function (input) {
                return !input.value;
            });

            const focusIndex = firstEmptyIndex === -1
                ? inputs.length - 1
                : firstEmptyIndex;

            inputs[focusIndex].disabled = false;
            inputs[focusIndex].focus();
        }
    });

    /*
    |--------------------------------------------------------------------------
    | OTP COUNTDOWN TIMER
    |--------------------------------------------------------------------------
    */

    if (window.otpTimerInterval) {
        clearInterval(window.otpTimerInterval);
        window.otpTimerInterval = null;
    }

    function updateTimer() {
        const remaining = Math.max(
            0,
            Math.ceil((expiresAt - Date.now()) / 1000)
        );

        if (remaining <= 0) {
            if (countdown) {
                countdown.textContent = '00:00';
                countdown.style.display = 'none';
            }

            if (resendLink) {
                resendLink.style.display = 'inline';
                resendLink.style.pointerEvents = 'auto';
            }

            if (window.otpTimerInterval) {
                clearInterval(window.otpTimerInterval);
                window.otpTimerInterval = null;
            }

            return;
        }

        if (countdown) {
            countdown.textContent =
                String(Math.floor(remaining / 60)).padStart(2, '0') +
                ':' +
                String(remaining % 60).padStart(2, '0');

            countdown.style.display = 'inline';
        }

        if (resendLink) {
            resendLink.style.display = 'none';
        }
    }

    updateTimer();

    if (expiresAt > Date.now()) {
        window.otpTimerInterval = setInterval(updateTimer, 1000);
    }

    /*
    |--------------------------------------------------------------------------
    | RESEND OTP
    |--------------------------------------------------------------------------
    */

    if (resendLink) {
        resendLink.addEventListener('click', function (event) {
            event.preventDefault();

            if (Date.now() < expiresAt || !resendUrl) {
                return;
            }

            const csrf = form.querySelector('input[name="_token"]')
                || document.querySelector('input[name="_token"]');

            if (!csrf) {
                (window.notify ? window.notify.error : alert)(
                    'Unable to request a new code. Please refresh the page.'
                );

                return;
            }

            // Prevent repeated clicks while submitting.
            if (resendLink.dataset.submitting === 'true') {
                return;
            }

            resendLink.dataset.submitting = 'true';

            const resendForm = document.createElement('form');

            resendForm.method = 'POST';
            resendForm.action = resendUrl;
            resendForm.style.display = 'none';

            const token = document.createElement('input');

            token.type = 'hidden';
            token.name = '_token';
            token.value = csrf.value;

            resendForm.appendChild(token);
            document.body.appendChild(resendForm);

            resendForm.submit();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | INITIAL FOCUS
    |--------------------------------------------------------------------------
    */

    const firstEmptyInput = inputs.find(function (input) {
        return !input.value;
    });

    if (firstEmptyInput) {
        firstEmptyInput.disabled = false;
        firstEmptyInput.focus();
    }
})();
