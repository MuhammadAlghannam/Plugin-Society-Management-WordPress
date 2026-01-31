/**
 * Email Templates Admin JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Wait for DataTables to be available
        function initDataTable() {
            if (typeof $.fn.DataTable !== 'undefined') {
                // Destroy existing DataTable if it exists
                if ($.fn.DataTable.isDataTable('#templates-table')) {
                    $('#templates-table').DataTable().destroy();
                }

                // Verify table structure before initializing
                var $table = $('#templates-table');
                var headerCols = $table.find('thead th').length;

                // Skip initialization if table structure is invalid
                if (headerCols === 0) {
                    console.warn('CSI: Table header not found, retrying...');
                    setTimeout(initDataTable, 100);
                    return;
                }

                // Initialize DataTable with search enabled
                var table = $table.DataTable({
                    pageLength: 20,
                    order: [], // No default sorting
                    searching: true, // Enable DataTables search
                    lengthChange: true, // Show "Show X entries" selector
                    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>><"row"<"col-sm-12"rt>><"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>', // Length, Filter, Table, Info, Pagination
                    columnDefs: [
                        { orderable: false, targets: -1 } // Disable sorting on Actions column only
                    ],
                    language: {
                        search: (typeof csiEmailTemplates !== 'undefined' && csiEmailTemplates.i18n) ? csiEmailTemplates.i18n.search : 'Search:',
                        lengthMenu: (typeof csiEmailTemplates !== 'undefined' && csiEmailTemplates.i18n) ? csiEmailTemplates.i18n.showEntries : 'Show _MENU_ entries',
                        info: (typeof csiEmailTemplates !== 'undefined' && csiEmailTemplates.i18n) ? csiEmailTemplates.i18n.showing : 'Showing _START_ to _END_ of _TOTAL_ entries',
                        infoEmpty: (typeof csiEmailTemplates !== 'undefined' && csiEmailTemplates.i18n) ? csiEmailTemplates.i18n.noEntries : 'No entries found',
                        infoFiltered: (typeof csiEmailTemplates !== 'undefined' && csiEmailTemplates.i18n) ? csiEmailTemplates.i18n.filtered : '(filtered from _MAX_ total entries)',
                        paginate: {
                            first: (typeof csiEmailTemplates !== 'undefined' && csiEmailTemplates.i18n) ? csiEmailTemplates.i18n.first : 'First',
                            last: (typeof csiEmailTemplates !== 'undefined' && csiEmailTemplates.i18n) ? csiEmailTemplates.i18n.last : 'Last',
                            next: (typeof csiEmailTemplates !== 'undefined' && csiEmailTemplates.i18n) ? csiEmailTemplates.i18n.next : 'Next',
                            previous: (typeof csiEmailTemplates !== 'undefined' && csiEmailTemplates.i18n) ? csiEmailTemplates.i18n.previous : 'Previous'
                        },
                        emptyTable: (typeof csiEmailTemplates !== 'undefined' && csiEmailTemplates.i18n) ? csiEmailTemplates.i18n.emptyTable : 'No templates found.'
                    },
                    // Handle empty table
                    drawCallback: function (settings) {
                        var api = this.api();
                        if (api.rows().count() === 0) {
                            var headerCount = api.columns().count();
                            var tbody = $table.find('tbody');
                            if (tbody.find('tr').length === 0 || tbody.find('tr td').length !== headerCount) {
                                var emptyMsg = (typeof csiEmailTemplates !== 'undefined' && csiEmailTemplates.i18n) ? csiEmailTemplates.i18n.emptyTable : 'No templates found.';
                                tbody.html('<tr><td colspan="' + headerCount + '" style="text-align: center; padding: 20px;"><strong>' + emptyMsg + '</strong></td></tr>');
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
    });
    
})(jQuery);
