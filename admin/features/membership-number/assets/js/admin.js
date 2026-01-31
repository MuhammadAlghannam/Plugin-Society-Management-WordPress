/**
 * Membership Number Settings JavaScript
 * Based on old generated-id-settings.js
 */

jQuery(document).ready(function ($) {
    var initialized = false;
    var useFallback = false;
    var abbreviationModal = null;
    var generateModal = null;
    var abbreviationModalElement = null;
    var generateModalElement = null;

    function initMembershipNumberSettings() {
        if (initialized) return;

        if (typeof bootstrap === 'undefined') {
            if (!useFallback) {
                initFallbackModals($);
                useFallback = true;
            }
            initialized = true;
            return;
        }

        // Get modal elements
        abbreviationModalElement = document.getElementById('csi-abbreviation-modal');
        generateModalElement = document.getElementById('csi-generate-modal');

        // Initialize modals - try to get existing or create new
        if (abbreviationModalElement) {
            abbreviationModal = bootstrap.Modal.getInstance(abbreviationModalElement);
            if (!abbreviationModal) {
                abbreviationModal = new bootstrap.Modal(abbreviationModalElement);
            }
        }

        if (generateModalElement) {
            generateModal = bootstrap.Modal.getInstance(generateModalElement);
            if (!generateModal) {
                generateModal = new bootstrap.Modal(generateModalElement);
            }
        }

        // Add Abbreviation button click
        $('#csi-add-abbreviation-btn').off('click').on('click', function (e) {
            e.preventDefault();
            if (!abbreviationModal && abbreviationModalElement) {
                abbreviationModal = new bootstrap.Modal(abbreviationModalElement);
            }
            if (!abbreviationModal) {
                console.error('Abbreviation modal not available');
                return false;
            }
            $('#csi-abbreviation-modal-label').text('Add Abbreviation');
            $('#csi-abbreviation-form')[0].reset();
            $('#edit-membership').val('');
            $('#abbreviation-membership').val('').prop('disabled', false);
            abbreviationModal.show();
            return false;
        });

        // Edit Abbreviation - use event delegation
        $(document).off('click', '.csi-edit-abbreviation').on('click', '.csi-edit-abbreviation', function (e) {
            e.preventDefault();
            if (!abbreviationModal && abbreviationModalElement) {
                abbreviationModal = new bootstrap.Modal(abbreviationModalElement);
            }
            if (!abbreviationModal) {
                console.error('Abbreviation modal not available');
                return false;
            }
            var membership = $(this).data('membership');
            var abbreviation = $(this).data('abbreviation');

            $('#csi-abbreviation-modal-label').text('Edit Abbreviation');
            $('#edit-membership').val(membership);
            $('#abbreviation-membership').val(membership).prop('disabled', true);
            $('#abbreviation-input').val(abbreviation);
            abbreviationModal.show();
            return false;
        });

        // Generate IDs button click
        $('#csi-generate-ids-btn').off('click').on('click', function (e) {
            e.preventDefault();
            if (!generateModal && generateModalElement) {
                generateModal = new bootstrap.Modal(generateModalElement);
            }
            if (!generateModal) {
                console.error('Generate modal not available');
                return false;
            }
            $('#generate-info, #generate-warning').hide();
            $('#generate-membership, #generate-new-membership, #generate-all-membership').val('');
            generateModal.show();
            return false;
        });

        // Update generate forms when membership type changes
        $('#generate-membership').off('change').on('change', function () {
            var selectedOption = $(this).find('option:selected');
            var membership = $(this).val();
            var abbreviation = selectedOption.data('abbreviation');
            var lastNumber = selectedOption.data('last-number');

            $('#generate-new-membership').val(membership);
            $('#generate-all-membership').val(membership);

            if (membership && abbreviation !== undefined) {
                var nextNumber = parseInt(lastNumber) + 1;
                var nextId = abbreviation.toUpperCase() + String(nextNumber).padStart(4, '0');
                $('#generate-info').html(
                    '<div style="background: #f8f9fa; padding: 12px; border-radius: 4px; border-left: 3px solid #0073aa;">' +
                    '<strong>Abbreviation:</strong> ' + abbreviation.toUpperCase() + '<br>' +
                    '<strong>Last Number:</strong> ' + lastNumber + '<br>' +
                    '<strong>Next ID will be:</strong> ' + nextId +
                    '</div>'
                ).show();
                $('#generate-warning').show();
            } else {
                $('#generate-info, #generate-warning').hide();
            }
        });

        // Add loading spinner to form submits
        function addLoadingState(form, loadingText) {
            var $submitButton = form.find('button[type="submit"]');
            if ($submitButton.length === 0) return;

            var originalText = $submitButton.html();
            $submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + loadingText);

            setTimeout(function () {
                if (!form[0].checkValidity()) {
                    $submitButton.prop('disabled', false).html(originalText);
                }
            }, 100);
        }

        $('#csi-abbreviation-form').off('submit').on('submit', function () {
            addLoadingState($(this), 'Saving...');
        });

        $('#csi-generate-new-form').off('submit').on('submit', function () {
            addLoadingState($(this), 'Generating...');
        });

        $('#csi-generate-all-form').off('submit').on('submit', function () {
            addLoadingState($(this), 'Generating All...');
        });

        initialized = true;
    }

    // Wait for assets or retry
    if (typeof window.CSI !== 'undefined' && typeof window.CSI.waitForAssets === 'function') {
        window.CSI.waitForAssets(initMembershipNumberSettings);
    } else if (typeof bootstrap !== 'undefined') {
        initMembershipNumberSettings();
    } else {
        setTimeout(function () {
            initMembershipNumberSettings();
        }, 500);
    }
});

/**
 * Fallback modal handling when Bootstrap is not available
 */
function initFallbackModals($) {
    function showModal(modalId) {
        var $modal = $('#' + modalId);
        $modal.addClass('show').css('display', 'block');
        if ($('.modal-backdrop').length === 0) {
            $('body').addClass('modal-open').append('<div class="modal-backdrop fade show"></div>');
        }
    }

    function hideModal(modalId) {
        var $modal = $('#' + modalId);
        $modal.removeClass('show').css('display', 'none');
        $('body').removeClass('modal-open');
        $('.modal-backdrop').remove();
    }

    // Add Abbreviation button click
    $('#csi-add-abbreviation-btn').off('click').on('click', function (e) {
        e.preventDefault();
        $('#csi-abbreviation-modal-label').text('Add Abbreviation');
        $('#csi-abbreviation-form')[0].reset();
        $('#edit-membership').val('');
        $('#abbreviation-membership').val('').prop('disabled', false);
        showModal('csi-abbreviation-modal');
    });

    // Edit Abbreviation
    $(document).off('click', '.csi-edit-abbreviation').on('click', '.csi-edit-abbreviation', function (e) {
        e.preventDefault();
        var membership = $(this).data('membership');
        var abbreviation = $(this).data('abbreviation');

        $('#csi-abbreviation-modal-label').text('Edit Abbreviation');
        $('#edit-membership').val(membership);
        $('#abbreviation-membership').val(membership).prop('disabled', true);
        $('#abbreviation-input').val(abbreviation);
        showModal('csi-abbreviation-modal');
    });

    // Generate IDs button click
    $('#csi-generate-ids-btn').off('click').on('click', function (e) {
        e.preventDefault();
        $('#generate-info, #generate-warning').hide();
        $('#generate-membership, #generate-new-membership, #generate-all-membership').val('');
        showModal('csi-generate-modal');
    });

    // Close modals
    $(document).off('click', '.btn-close, [data-bs-dismiss="modal"]').on('click', '.btn-close, [data-bs-dismiss="modal"]', function () {
        var $modal = $(this).closest('.modal');
        hideModal($modal.attr('id'));
    });

    // Close modal on backdrop click
    $(document).off('click', '.modal').on('click', '.modal', function (e) {
        if ($(e.target).hasClass('modal')) {
            hideModal($(this).attr('id'));
        }
    });

    // Update generate forms when membership type changes
    $('#generate-membership').off('change').on('change', function () {
        var selectedOption = $(this).find('option:selected');
        var membership = $(this).val();
        var abbreviation = selectedOption.data('abbreviation');
        var lastNumber = selectedOption.data('last-number');

        $('#generate-new-membership').val(membership);
        $('#generate-all-membership').val(membership);

        if (membership && abbreviation !== undefined) {
            var nextNumber = parseInt(lastNumber) + 1;
            var nextId = abbreviation.toUpperCase() + String(nextNumber).padStart(4, '0');
            $('#generate-info').html(
                '<div style="background: #f8f9fa; padding: 12px; border-radius: 4px; border-left: 3px solid #0073aa;">' +
                '<strong>Abbreviation:</strong> ' + abbreviation.toUpperCase() + '<br>' +
                '<strong>Last Number:</strong> ' + lastNumber + '<br>' +
                '<strong>Next ID will be:</strong> ' + nextId +
                '</div>'
            ).show();
            $('#generate-warning').show();
        } else {
            $('#generate-info, #generate-warning').hide();
        }
    });
}
