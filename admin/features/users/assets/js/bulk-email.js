/**
 * Bulk Email JavaScript
 * Handles bulk email sending from users table
 */

(function ($) {
    'use strict';

    // Ensure CSI object exists
    window.CSI = window.CSI || {};

    $(document).ready(function () {
        // Handle preview email button
        $('#preview-email-btn').on('click', function () {
            var templateId = $('#email-template-selector').val();
            var selectedUsers = getSelectedUserIds();

            if (!templateId) {
                if (typeof CSI !== 'undefined' && CSI.Swal) {
                    CSI.Swal.error('Error', 'Please select an email template');
                }
                return;
            }

            if (selectedUsers.length === 0) {
                if (typeof CSI !== 'undefined' && CSI.Swal) {
                    CSI.Swal.error('Error', 'Please select at least one user');
                }
                return;
            }

            previewEmail(templateId, selectedUsers);
        });

        // Handle send email button
        $('#send-email-btn').on('click', function () {
            var templateId = $('#email-template-selector').val();
            var selectedUsers = getSelectedUserIds();

            if (!templateId) {
                if (typeof CSI !== 'undefined' && CSI.Swal) {
                    CSI.Swal.error('Error', 'Please select an email template');
                }
                return;
            }

            if (selectedUsers.length === 0) {
                if (typeof CSI !== 'undefined' && CSI.Swal) {
                    CSI.Swal.error('Error', 'Please select at least one user');
                }
                return;
            }

            sendBulkEmail(templateId, selectedUsers);
        });
    });

    /**
     * Get selected user IDs
     */
    function getSelectedUserIds() {
        var userIds = [];
        $('.user-checkbox:checked').each(function () {
            userIds.push($(this).val());
        });
        return userIds;
    }

    /**
     * Preview email
     */
    function previewEmail(templateId, userIds) {
        // This will be implemented when email templates feature is ready
        if (typeof CSI !== 'undefined' && CSI.Swal) {
            CSI.Swal.info('Preview', 'Email preview will be available when email templates are configured');
        }
    }

    /**
     * Send bulk email
     */
    function sendBulkEmail(templateId, userIds) {
        if (typeof CSI !== 'undefined' && CSI.Swal) {
            CSI.Swal.confirm(
                'Send Bulk Email',
                'Are you sure you want to send this email to ' + userIds.length + ' selected user(s)?',
                'Yes, send it!',
                'Cancel'
            ).then(function (result) {
                if (result.isConfirmed) {
                    window.CSI.sendBulkEmailBatched(templateId, userIds);
                }
            });
        } else {
            // Fallback if Swal is not available
            if (confirm('Are you sure you want to send this email to ' + userIds.length + ' selected user(s)?')) {
                window.CSI.sendBulkEmailBatched(templateId, userIds);
            }
        }
    }

    /**
     * Send bulk email in batches
     * @param {number} templateId
     * @param {Array} userIds
     */
    window.CSI.sendBulkEmailBatched = function (templateId, userIds) {
        if (!templateId || !userIds || userIds.length === 0) {
            return;
        }

        var batchSize = 5;
        var delay = 5000; // 5 seconds
        var totalUsers = userIds.length;
        var batches = [];

        for (var i = 0; i < totalUsers; i += batchSize) {
            batches.push(userIds.slice(i, i + batchSize));
        }

        var totalBatches = batches.length;
        var currentBatchIndex = 0;
        var successCount = 0;
        var failureCount = 0;
        var isCancelled = false;

        // Initialize modal
        var $modal = $('#csi-email-progress-modal');
        var $progressBar = $('#csi-email-progress-bar');
        var $progressText = $('#csi-email-progress-text');
        var $progressDetails = $('#csi-email-progress-details');
        var $closeBtn = $('#csi-cancel-email-sending');

        // Reset modal state
        $progressBar.css('width', '0%').attr('aria-valuenow', 0).text('0%');
        $progressText.text('Preparing to send ' + totalUsers + ' emails...');
        $progressDetails.html('');
        $closeBtn.prop('disabled', true).text('Close');

        // Show modal
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var modalInstance = bootstrap.Modal.getInstance($modal[0]);
            if (!modalInstance) {
                modalInstance = new bootstrap.Modal($modal[0], {
                    backdrop: 'static',
                    keyboard: false
                });
            }
            modalInstance.show();
        } else {
            $modal.show();
            $modal.addClass('show');
            $('body').append('<div class="modal-backdrop fade show"></div>');
        }

        function processBatch() {
            if (isCancelled) return;

            if (currentBatchIndex >= totalBatches) {
                finishProcess();
                return;
            }

            var batch = batches[currentBatchIndex];
            var batchNum = currentBatchIndex + 1;

            $progressText.text('Sending batch ' + batchNum + ' of ' + totalBatches + '...');

            $.ajax({
                url: csiGlobal.ajaxurl,
                type: 'POST',
                data: {
                    action: 'csi_send_bulk_email',
                    template_id: templateId,
                    user_ids: batch,
                    nonce: csiGlobal.nonce
                },
                success: function (response) {
                    if (response.success) {
                        successCount += response.data.results.success;
                        failureCount += response.data.results.failed;

                        if (response.data.results.errors && response.data.results.errors.length > 0) {
                            $progressDetails.append('<div class="text-danger small">' + response.data.results.errors.join('<br>') + '</div>');
                        }
                    } else {
                        failureCount += batch.length;
                        $progressDetails.append('<div class="text-danger small">Batch ' + batchNum + ' failed: ' + (response.data.message || 'Unknown error') + '</div>');
                    }
                },
                error: function () {
                    failureCount += batch.length;
                    $progressDetails.append('<div class="text-danger small">Batch ' + batchNum + ' network error</div>');
                },
                complete: function () {
                    currentBatchIndex++;
                    var progress = Math.round((currentBatchIndex / totalBatches) * 100);
                    $progressBar.css('width', progress + '%').attr('aria-valuenow', progress).text(progress + '%');

                    if (currentBatchIndex < totalBatches) {
                        $progressText.text('Waiting ' + (delay / 1000) + ' seconds...');
                        setTimeout(processBatch, delay);
                    } else {
                        finishProcess();
                    }
                }
            });
        }

        function finishProcess() {
            $progressBar.removeClass('progress-bar-animated');
            $progressText.text('Completed!');

            var summaryHtml = '<div class="alert alert-' + (failureCount > 0 ? 'warning' : 'success') + '">';
            summaryHtml += '<strong>Finished:</strong> ' + successCount + ' sent, ' + failureCount + ' failed.';
            summaryHtml += '</div>';

            $progressDetails.prepend(summaryHtml);
            $closeBtn.prop('disabled', false);

            // Clear selection
            $('.user-checkbox').prop('checked', false);
            $('#select-all-users').prop('checked', false);
            localStorage.removeItem('csi_selected_users');
            $('#csi-selected-info').hide();

            // Trigger change on a checkbox to update UI if listeners are active
            $('#select-all-users').trigger('change');
        }

        // Start processing
        processBatch();

        // Allow user to close manually after completion
        $closeBtn.off('click').on('click', function () {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                var bsModal = bootstrap.Modal.getInstance($modal[0]);
                if (bsModal) bsModal.hide();
            } else {
                $modal.hide();
                $modal.removeClass('show');
                $('.modal-backdrop').remove();
            }
            // Reload page to refresh state
            window.location.reload();
        });
    };

})(jQuery);
