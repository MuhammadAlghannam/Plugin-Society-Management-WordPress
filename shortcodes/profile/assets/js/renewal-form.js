jQuery(document).ready(function($) {
    const $form = $('#csi-renewal-form');
    const $submitBtn = $('#csi-renewal-submit-btn');
    const $spinner = $submitBtn.find('.spinner-border');

    $form.on('submit', function(e) {
        e.preventDefault();

        // Validate file
        const fileInput = document.getElementById('csi-payment-receipt');
        if (!fileInput.files.length) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please select a payment receipt.'
                });
            } else {
                alert('Please select a payment receipt.');
            }
            return;
        }

        const file = fileInput.files[0];
        const maxSize = 80 * 1024 * 1024; // 80MB
        const allowedTypes = ['image/jpeg', 'image/png'];

        if (file.size > maxSize) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'File size exceeds 80MB limit.'
                });
            } else {
                alert('File size exceeds 80MB limit.');
            }
            return;
        }

        if (!allowedTypes.includes(file.type)) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Invalid file type. Only JPEG and PNG are allowed.'
                });
            } else {
                alert('Invalid file type. Only JPEG and PNG are allowed.');
            }
            return;
        }

        // Prepare data
        const formData = new FormData(this);
        formData.append('action', 'csi_submit_renewal');
        formData.append('nonce', csiRenewal.nonce);

        // Reset state
        $submitBtn.prop('disabled', true);
        $spinner.removeClass('d-none');

        $.ajax({
            url: csiRenewal.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Show SweetAlert2 success message
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.data.message || 'Your renew membership process is under review now.',
                            timer: 3000,
                            showConfirmButton: true
                        }).then(function() {
                            location.reload();
                        });
                    } else {
                        alert(response.data.message || 'Your renew membership process is under review now.');
                        location.reload();
                    }
                } else {
                    // Show error with SweetAlert2
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.data.message || 'An error occurred.'
                        });
                    } else {
                        alert(response.data.message || 'An error occurred.');
                    }
                    $submitBtn.prop('disabled', false);
                    $spinner.addClass('d-none');
                }
            },
            error: function() {
                // Show error with SweetAlert2
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Server error. Please try again.'
                    });
                } else {
                    alert('Server error. Please try again.');
                }
                $submitBtn.prop('disabled', false);
                $spinner.addClass('d-none');
            }
        });
    });
});
