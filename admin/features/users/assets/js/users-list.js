/**
 * Users List JavaScript
 * Handles DataTables, search, filters, and row clicks
 */

(function ($) {
    'use strict';

    $(document).ready(function () {
        // Ensure modals are hidden on page load
        $('.modal').each(function () {
            if (!$(this).hasClass('show')) {
                $(this).css('display', 'none');
            }
        });

        // Ensure Bootstrap is loaded
        function checkBootstrap() {
            if (typeof bootstrap === 'undefined' && typeof $.fn.modal === 'undefined') {
                console.warn('Bootstrap not loaded, retrying...');
                setTimeout(checkBootstrap, 100);
                return false;
            }
            return true;
        }
        checkBootstrap();

        // Wait for DataTables to be available
        function initDataTable() {
            if (typeof $.fn.DataTable !== 'undefined') {
                // Destroy existing DataTable if it exists
                if ($.fn.DataTable.isDataTable('#users-table')) {
                    $('#users-table').DataTable().destroy();
                }

                // Verify table structure before initializing
                var $table = $('#users-table');
                var headerCols = $table.find('thead th').length;
                var firstRowCols = $table.find('tbody tr:first td').length;

                // Skip initialization if table structure is invalid
                if (headerCols === 0) {
                    console.warn('CSI: Table header not found, retrying...');
                    setTimeout(initDataTable, 100);
                    return;
                }

                // Initialize DataTable without pagination (using backend pagination)
                var table = $table.DataTable({
                    paging: false, // Disable DataTables pagination (using backend pagination)
                    searching: false, // Disable DataTables search (using custom search above)
                    lengthChange: false, // Remove "Show X entries" selector
                    info: false, // Disable DataTables info (using custom pagination)
                    dom: 'rt', // Only table body
                    columnDefs: [
                        { orderable: false, targets: '_all' } // Disable sorting on all columns
                    ],
                    // Handle empty table
                    drawCallback: function (settings) {
                        var api = this.api();
                        if (api.rows().count() === 0) {
                            // Table is empty, ensure structure is correct
                            var headerCount = api.columns().count();
                            var tbody = $table.find('tbody');
                            if (tbody.find('tr').length === 0 || tbody.find('tr td').length !== headerCount) {
                                // Re-add empty message if needed
                                tbody.html('<tr><td colspan="' + headerCount + '" style="text-align: center; padding: 20px;"><strong>No users found</strong></td></tr>');
                            }
                        }
                    }
                });
            } else {
                // Retry after a short delay
                setTimeout(initDataTable, 100);
            }
        }

        // Initialize DataTables after a short delay to ensure DOM is ready
        setTimeout(initDataTable, 50);

        // Make table rows clickable (except action column)
        $(document).on('click', '#users-table tbody tr', function (e) {
            // Don't trigger if clicking on checkbox, button, or link
            if ($(e.target).closest('input[type="checkbox"], button, a, .csi-action-icon').length) {
                return;
            }

            var userId = $(this).data('user-id');
            if (userId) {
                // Navigate to user profile admin page
                var adminUrl = csiUsers.adminUrl || admin_url('admin.php?page=csi-user-profile');
                window.location.href = adminUrl + '&user_id=' + userId;
            }
        });

        // Handle delete button clicks
        $(document).on('click', '.csi-delete-icon', function (e) {
            e.stopPropagation();
            var userId = $(this).data('user-id');
            var userName = $(this).data('user-name');
            showDeleteConfirmation(userId, userName);
        });

        // Handle import button
        $('#csi-import-users-btn').on('click', function (e) {
            e.preventDefault();
            // Try CSI Bootstrap helper first
            if (typeof CSI !== 'undefined' && CSI.Bootstrap) {
                CSI.Bootstrap.showModal('#csi-import-modal');
            } else if (typeof bootstrap !== 'undefined') {
                // Fallback to direct Bootstrap
                var modalElement = document.querySelector('#csi-import-modal');
                if (modalElement) {
                    var modal = new bootstrap.Modal(modalElement);
                    modal.show();
                }
            } else {
                // Last resort: use jQuery if Bootstrap isn't loaded
                $('#csi-import-modal').modal('show');
            }
        });

        // Initialize selected users from localStorage
        var selectedUsersKey = 'csi_selected_users';

        function getSelectedUsers() {
            var stored = localStorage.getItem(selectedUsersKey);
            return stored ? JSON.parse(stored) : [];
        }

        function saveSelectedUsers(userIds) {
            localStorage.setItem(selectedUsersKey, JSON.stringify(userIds));
            updateSelectedInfo();
        }

        function addSelectedUser(userId) {
            var selected = getSelectedUsers();
            if (selected.indexOf(userId) === -1) {
                selected.push(userId);
                saveSelectedUsers(selected);
            }
        }

        function removeSelectedUser(userId) {
            var selected = getSelectedUsers();
            var index = selected.indexOf(userId);
            if (index > -1) {
                selected.splice(index, 1);
                saveSelectedUsers(selected);
            }
        }

        function clearSelectedUsers() {
            localStorage.removeItem(selectedUsersKey);
            $('.user-checkbox').prop('checked', false);
            $('#select-all-users').prop('checked', false);
            updateSelectedInfo();
            updateBulkActions();
        }

        function updateSelectedInfo() {
            var selected = getSelectedUsers();
            var count = selected.length;
            var $info = $('#csi-selected-info');
            var $count = $('#csi-selected-count');

            if (count > 0) {
                $count.text(count + ' ' + (count === 1 ? 'user selected' : 'users selected'));
                $info.show();
            } else {
                $info.hide();
            }
        }

        // Restore checkboxes on page load
        function restoreCheckboxes() {
            var selected = getSelectedUsers();
            $('.user-checkbox').each(function () {
                var userId = $(this).val();
                if (selected.indexOf(userId) > -1) {
                    $(this).prop('checked', true);
                }
            });

            // Update select all checkbox
            var allChecked = $('.user-checkbox').length > 0 &&
                $('.user-checkbox:checked').length === $('.user-checkbox').length;
            $('#select-all-users').prop('checked', allChecked);

            updateSelectedInfo();
        }

        // Restore on page load
        restoreCheckboxes();

        // Handle select all checkbox
        $('#select-all-users').on('change', function () {
            var isChecked = $(this).prop('checked');
            $('.user-checkbox').each(function () {
                var userId = $(this).val();
                $(this).prop('checked', isChecked);
                if (isChecked) {
                    addSelectedUser(userId);
                } else {
                    removeSelectedUser(userId);
                }
            });
            updateBulkActions();
        });

        // Handle individual checkboxes
        $(document).on('change', '.user-checkbox', function () {
            var userId = $(this).val();
            var isChecked = $(this).prop('checked');

            if (isChecked) {
                addSelectedUser(userId);
            } else {
                removeSelectedUser(userId);
            }

            // Update select all checkbox
            var totalCheckboxes = $('.user-checkbox').length;
            var checkedCount = $('.user-checkbox:checked').length;
            $('#select-all-users').prop('checked', totalCheckboxes > 0 && checkedCount === totalCheckboxes);

            updateBulkActions();
        });

        // Handle clear selection button
        $('#csi-clear-selection').on('click', function (e) {
            e.preventDefault();
            clearSelectedUsers();
        });

        /**
         * Update bulk actions visibility and state
         */
        function updateBulkActions() {
            var selectedUsers = getSelectedUsers();
            var checkedCount = selectedUsers.length;
            var templateId = $('#email-template-selector').val();

            if (checkedCount > 0 && templateId) {
                $('#doaction').show();
            } else {
                $('#doaction').hide();
            }
        }

        // Handle email template selection
        $('#email-template-selector').on('change', function () {
            updateBulkActions();
        });

        // Handle bulk actions form submission
        $(document).on('submit', '#bulk-actions-form', function (e) {
            e.preventDefault(); // Prevent default submission

            // Get all selected users from localStorage (across all pages)
            var checkedBoxes = getSelectedUsers();

            if (checkedBoxes.length === 0) {
                if (typeof CSI !== 'undefined' && CSI.Swal) {
                    CSI.Swal.error('Error', 'Please select at least one user');
                } else {
                    alert('Please select at least one user');
                }
                return false;
            }

            var templateId = $('#email-template-selector').val();
            var action = $(this).find('input[name="action"]').val();

            if (action === 'send_email') {
                if (!templateId) {
                    if (typeof CSI !== 'undefined' && CSI.Swal) {
                        CSI.Swal.error('Error', 'Please select an email template');
                    } else {
                        alert('Please select an email template');
                    }
                    return false;
                }

                if (typeof CSI !== 'undefined' && CSI.Swal) {
                    CSI.Swal.confirm(
                        'Send Bulk Email',
                        'Are you sure you want to send this email to ' + checkedBoxes.length + ' selected user(s)?',
                        'Yes, send it!',
                        'Cancel'
                    ).then(function (result) {
                        if (result.isConfirmed) {
                            if (window.CSI && window.CSI.sendBulkEmailBatched) {
                                window.CSI.sendBulkEmailBatched(templateId, checkedBoxes);
                            } else {
                                console.error('CSI.sendBulkEmailBatched is not defined');
                                alert('Error: Bulk email function not available.');
                            }
                        }
                    });
                } else {
                    if (confirm('Are you sure you want to send this email to ' + checkedBoxes.length + ' selected user(s)?')) {
                        if (window.CSI && window.CSI.sendBulkEmailBatched) {
                            window.CSI.sendBulkEmailBatched(templateId, checkedBoxes);
                        } else {
                            console.error('CSI.sendBulkEmailBatched is not defined');
                            alert('Error: Bulk email function not available.');
                        }
                    }
                }
            } else {
                // For other actions, submit normally
                // Remove any existing user_ids inputs
                $('#bulk-actions-form').find('input[name="user_ids[]"]').remove();

                // Add hidden inputs for each user ID
                checkedBoxes.forEach(function (userId) {
                    $('#bulk-actions-form').append($('<input>', {
                        type: 'hidden',
                        name: 'user_ids[]',
                        value: userId
                    }));
                });

                this.submit(); // Submit the form programmatically
            }
        });

        // Handle modal close buttons
        $('[data-bs-dismiss="modal"]').on('click', function () {
            var modal = $(this).closest('.modal');
            if (modal.length) {
                if (typeof bootstrap !== 'undefined') {
                    var bsModal = bootstrap.Modal.getInstance(modal[0]);
                    if (bsModal) {
                        bsModal.hide();
                    } else {
                        modal.removeClass('show').css('display', 'none');
                        $('.modal-backdrop').remove();
                    }
                } else {
                    modal.removeClass('show').css('display', 'none');
                    $('.modal-backdrop').remove();
                }
            }
        });

        // Handle backdrop click to close modal
        $(document).on('click', '.modal-backdrop', function () {
            $('.modal.show').each(function () {
                // Don't close static backdrops (like our progress modal)
                if ($(this).attr('data-bs-backdrop') === 'static') {
                    return;
                }

                if (typeof bootstrap !== 'undefined') {
                    var bsModal = bootstrap.Modal.getInstance(this);
                    if (bsModal) {
                        bsModal.hide();
                    }
                } else {
                    $(this).removeClass('show').css('display', 'none');
                    $('.modal-backdrop').remove();
                }
            });
        });
    });

    /**
     * Show delete confirmation
     */
    function showDeleteConfirmation(userId, userName) {
        if (typeof CSI === 'undefined' || !CSI.Swal) {
            return;
        }

        CSI.Swal.confirm(
            'Delete User',
            'Are you sure you want to permanently delete ' + userName + '? This action cannot be undone.',
            'Yes, delete it!',
            'Cancel'
        ).then(function (result) {
            if (result.isConfirmed) {
                deleteUser(userId);
            }
        });
    }

    /**
     * Delete user
     */
    function deleteUser(userId) {
        // Get nonce from the delete button's data attribute
        var $deleteBtn = $('.csi-delete-icon[data-user-id="' + userId + '"]');
        var nonce = $deleteBtn.data('delete-nonce');

        if (!nonce) {
            console.error('Delete nonce not found for user:', userId);
            if (typeof CSI !== 'undefined' && CSI.Swal) {
                CSI.Swal.error('Error', 'Security token not found. Please refresh the page and try again.');
            }
            return;
        }

        var form = $('<form>', {
            method: 'POST',
            action: ''
        });

        form.append($('<input>', {
            type: 'hidden',
            name: 'csi_delete_user',
            value: '1'
        }));

        form.append($('<input>', {
            type: 'hidden',
            name: 'csi_delete_user_id',
            value: userId
        }));

        form.append($('<input>', {
            type: 'hidden',
            name: 'csi_delete_nonce',
            value: nonce
        }));

        $('body').append(form);
        form.submit();
    }

})(jQuery);
