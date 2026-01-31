/**
 * Registration Form Scripts
 */
jQuery(document).ready(function ($) {
    'use strict';

    const form = document.querySelector('.csi-signup-form');
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    const emailInput = document.getElementById('csi-email');
    let emailExists = false;

    // Add validation message spans after each input
    inputs.forEach(input => {
        if (!input.nextElementSibling || !input.nextElementSibling.classList.contains('validation-message')) {
            const span = document.createElement('span');
            span.className = 'validation-message text-danger';
            span.style.display = 'none';
            input.parentNode.insertBefore(span, input.nextElementSibling);
        }
    });

    // Show validation messages on input
    inputs.forEach(input => {
        ['input', 'blur'].forEach(event => {
            input.addEventListener(event, function () {
                validateInput(this);
            });
        });
    });

    function validateInput(input) {
        const validationMessage = input.nextElementSibling;
        if (!input.validity.valid) {
            let message = '';
            if (input.validity.valueMissing) {
                message = 'This field is required';
            } else if (input.validity.typeMismatch) {
                if (input.type === 'email') {
                    message = 'Please enter a valid email address';
                } else {
                    message = 'Please enter a valid ' + input.type;
                }
            } else if (input.validity.tooShort) {
                message = 'Must be at least ' + input.getAttribute('minlength') + ' characters';
            }
            validationMessage.textContent = message;
            validationMessage.style.display = 'block';
            input.classList.add('is-invalid');
            return false;
        } else {
            validationMessage.style.display = 'none';
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
            return true;
        }
    }

    // Check email existence
    emailInput.addEventListener('blur', function () {
        checkEmailExists(this.value);
    });

    // Also check on input change (with debounce)
    let emailCheckTimeout;
    emailInput.addEventListener('input', function () {
        emailExists = false;
        const validationMessage = emailInput.nextElementSibling;
        validationMessage.style.display = 'none';
        emailInput.classList.remove('is-invalid');

        // Clear previous timeout
        if (emailCheckTimeout) {
            clearTimeout(emailCheckTimeout);
        }

        // Check email after user stops typing (500ms delay)
        if (this.value && this.validity.valid) {
            emailCheckTimeout = setTimeout(() => {
                checkEmailExists(this.value);
            }, 500);
        }
    });

    // Function to check email existence
    function checkEmailExists(email) {
        if (!email || !emailInput.validity.valid) {
            return;
        }

        $.ajax({
            url: csiAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'csi_check_email_exists',
                email: email,
                security: csiAjax.security
            },
            success: function (response) {
                const validationMessage = emailInput.nextElementSibling;
                // Handle both response formats (success wrapper or direct)
                const exists = (response.success && response.data && response.data.exists) ||
                    (response.exists !== undefined ? response.exists : false);

                if (exists) {
                    emailExists = true;
                    validationMessage.textContent = 'This email is already registered';
                    validationMessage.style.display = 'block';
                    emailInput.classList.add('is-invalid');
                    emailInput.classList.remove('is-valid');
                } else {
                    emailExists = false;
                    validationMessage.style.display = 'none';
                    emailInput.classList.remove('is-invalid');
                    emailInput.classList.add('is-valid');
                }
            },
            error: function () {
                // On error, still allow form submission but server will validate
                console.error('Error checking email');
            }
        });
    }

    // Handle registration type change
    $('#csi-registration-type').on('change', function () {
        const type = $(this).val();
        const membershipSelect = $('#csi-membership');

        // Show/hide student card field
        if (type === 'student') {
            $('#student-card-field').show();
            $('#csi-student-card').prop('required', true);
            $('#membership-type-field').show();
            // Set membership to student and make it readonly
            membershipSelect.val('student');
            membershipSelect.prop('disabled', true);
            membershipSelect.prop('required', false);
            membershipSelect.css('background-color', '#f5f5f5');
            membershipSelect.css('cursor', 'not-allowed');
        } else {
            $('#student-card-field').hide();
            $('#csi-student-card').prop('required', false);
            $('#membership-type-field').show();
            // Enable membership selection
            membershipSelect.prop('disabled', false);
            membershipSelect.prop('required', true);
            membershipSelect.css('background-color', '');
            membershipSelect.css('cursor', '');
            // Clear value if it was set to student
            if (membershipSelect.val() === 'student') {
                membershipSelect.val('');
            }
        }

        // Update payment details if payment method is selected
        const selectedPayment = $('input[name="csi_payment_method"]:checked').val();
        if (selectedPayment) {
            updatePaymentDetails(selectedPayment, type);
        }
    });

    // Payment method change handler
    $('input[name="csi_payment_method"]').on('change', function () {
        const regType = $('#csi-registration-type').val();
        updatePaymentDetails($(this).val(), regType);
    });

    // Function to update payment details
    function updatePaymentDetails(paymentMethod, regType) {
        const detailsDiv = $('#payment-details');

        if (paymentMethod === 'insta') {
            let amount = '1,100';
            if (regType === 'student') {
                amount = '400';
            }
            detailsDiv.html('<div class="payment-info"><ul>' +
                '<li>1. Open "InstaPay"</li>' +
                '<li>2. Select "Send Money"</li>' +
                '<li>3. Choose "Bank Account"</li>' +
                '<li>4. Select Bank "Qatar National Bank S.A.E"</li>' +
                '<li>5. Enter This Account Number "0014820315504011"</li>' +
                '<li>6. Enter Receiver Name "Egyptian Society For Extracell"</li>' +
                '<li>7. Enter Amount Of "' + amount + '"</li>' +
                '<li>8. Click "Next"</li>' +
                '</ul></div>');
        } else {
            detailsDiv.html('');
        }
    }

    // Form submission
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        // Get submit button
        const submitButton = form.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.innerHTML;
        const originalButtonDisabled = submitButton.disabled;

        // Show loading state on button
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Submitting...';
        submitButton.style.opacity = '0.7';
        submitButton.style.cursor = 'not-allowed';

        // Enable disabled fields before submission so their values are included
        const membershipSelect = document.getElementById('csi-membership');
        const wasDisabled = membershipSelect && membershipSelect.disabled;

        if (wasDisabled) {
            membershipSelect.disabled = false;
        }

        let hasErrors = false;

        // Check all inputs
        inputs.forEach(input => {
            if (!validateInput(input)) {
                hasErrors = true;
            }
        });

        // Final email validation check before submission
        if (emailInput.value && emailInput.validity.valid) {
            // Perform a final synchronous check to ensure email doesn't exist
            let finalEmailCheck = false;
            $.ajax({
                url: csiAjax.ajaxurl,
                type: 'POST',
                async: false, // Synchronous for final validation
                data: {
                    action: 'csi_check_email_exists',
                    email: emailInput.value,
                    security: csiAjax.security
                },
                success: function (response) {
                    const exists = (response.success && response.data && response.data.exists) ||
                        (response.exists !== undefined ? response.exists : false);
                    if (exists) {
                        finalEmailCheck = true;
                        emailExists = true;
                    }
                }
            });

            if (finalEmailCheck) {
                hasErrors = true;
                const validationMessage = emailInput.nextElementSibling;
                validationMessage.textContent = 'This email is already registered';
                validationMessage.style.display = 'block';
                emailInput.classList.add('is-invalid');
            }
        }

        // Check if email exists (from previous checks)
        if (emailExists) {
            hasErrors = true;
            const validationMessage = emailInput.nextElementSibling;
            validationMessage.textContent = 'This email is already registered';
            validationMessage.style.display = 'block';
            emailInput.classList.add('is-invalid');
        }

        if (hasErrors) {
            // Reset button state
            submitButton.disabled = originalButtonDisabled;
            submitButton.innerHTML = originalButtonText;
            submitButton.style.opacity = '';
            submitButton.style.cursor = '';

            // Re-disable field if there are errors
            if (wasDisabled && membershipSelect && $('#csi-registration-type').val() === 'student') {
                membershipSelect.disabled = true;
            }
            return;
        }

        // Submit form via AJAX
        const formData = new FormData(form);
        formData.append('action', 'csi_process_signup');
        formData.append('security', csiAjax.security);

        $.ajax({
            url: csiAjax.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                // Reset button state
                submitButton.disabled = originalButtonDisabled;
                submitButton.innerHTML = originalButtonText;
                submitButton.style.opacity = '';
                submitButton.style.cursor = '';

                if (response.success) {
                    // Clear form inputs
                    form.reset();

                    // Remove all validation classes (green checkmarks and red X marks)
                    form.querySelectorAll('.is-valid, .is-invalid').forEach(function (element) {
                        element.classList.remove('is-valid', 'is-invalid');
                    });

                    // Hide all validation messages
                    form.querySelectorAll('.validation-message').forEach(function (element) {
                        element.style.display = 'none';
                    });

                    // Reset email exists flag
                    emailExists = false;

                    // Reset membership select if it was disabled
                    if (wasDisabled && membershipSelect && $('#csi-registration-type').val() === 'student') {
                        membershipSelect.disabled = true;
                    }

                    // Show SweetAlert2 success message
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.data.message || 'Thank you for your submission!',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#0073aa',
                            timer: 3000,
                            timerProgressBar: true,
                            didClose: function () {
                                // Redirect to login page after SweetAlert2 closes
                                window.location.href = '/login';
                            }
                        }).then(function () {
                            // Also redirect if user clicks OK before timer
                            window.location.href = '/login';
                        });
                    } else {
                        // Fallback to regular alert if SweetAlert2 is not loaded
                        alert(response.data.message || 'Thank you for your submission!');
                        setTimeout(function () {
                            window.location.href = '/login';
                        }, 2000);
                    }

                    // Scroll to top of form
                    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                } else {
                    // Show error with SweetAlert2
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            html: response.data.message || 'An error occurred. Please try again.',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#dc3545'
                        });
                    } else {
                        // Fallback to regular alert
                        alert(response.data.message || 'An error occurred. Please try again.');
                    }
                }
            },
            error: function () {
                // Reset button state
                submitButton.disabled = originalButtonDisabled;
                submitButton.innerHTML = originalButtonText;
                submitButton.style.opacity = '';
                submitButton.style.cursor = '';

                // Show error with SweetAlert2
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An error occurred. Please try again.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#dc3545'
                    });
                } else {
                    // Fallback to regular alert
                    alert('An error occurred. Please try again.');
                }
            }
        });
    });
});
