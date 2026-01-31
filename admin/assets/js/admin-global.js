/**
 * Global Admin JavaScript
 * Provides helper functions for Bootstrap, DataTables, SweetAlert2, and Notifications
 */

(function ($) {
  'use strict';

  // Global namespace
  window.CSI = window.CSI || {};

  /**
   * DataTables Helper
   */
  CSI.DataTables = {
    /**
     * Initialize DataTable with default settings
     */
    init: function (selector, options) {
      if (!$.fn.DataTable) {
        console.error('DataTables is not loaded');
        return null;
      }

      // Default options
      var defaults = {
        responsive: true,
        pageLength: 20,
        language: {
          search: "Search:",
          lengthMenu: "Show _MENU_ entries",
          info: "Showing _START_ to _END_ of _TOTAL_ entries",
          infoEmpty: "No entries found",
          infoFiltered: "(filtered from _MAX_ total entries)",
          paginate: {
            first: "First",
            last: "Last",
            next: "Next",
            previous: "Previous"
          }
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        order: []
      };

      // Merge with custom options
      var settings = $.extend(true, {}, defaults, options);

      // Check if already initialized
      if ($.fn.DataTable.isDataTable(selector)) {
        return $(selector).DataTable();
      }

      return $(selector).DataTable(settings);
    },

    /**
     * Destroy DataTable if exists
     */
    destroy: function (selector) {
      if ($.fn.DataTable.isDataTable(selector)) {
        $(selector).DataTable().destroy();
      }
    }
  };

  /**
   * SweetAlert2 Helper
   */
  CSI.Swal = {
    /**
     * Show success notification
     */
    success: function (title, text, timer) {
      if (typeof Swal === 'undefined') {
        console.error('SweetAlert2 is not loaded');
        return;
      }

      return Swal.fire({
        icon: 'success',
        title: title || 'Success!',
        text: text || '',
        timer: timer || 3000,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
      });
    },

    /**
     * Show error notification
     */
    error: function (title, text) {
      if (typeof Swal === 'undefined') {
        console.error('SweetAlert2 is not loaded');
        return;
      }

      return Swal.fire({
        icon: 'error',
        title: title || 'Error!',
        text: text || 'Something went wrong',
        confirmButtonColor: '#d33'
      });
    },

    /**
     * Show warning confirmation
     */
    confirm: function (title, text, confirmText, cancelText) {
      if (typeof Swal === 'undefined') {
        console.error('SweetAlert2 is not loaded');
        return Promise.reject();
      }

      return Swal.fire({
        title: title || 'Are you sure?',
        text: text || "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: confirmText || 'Yes, do it!',
        cancelButtonText: cancelText || 'Cancel'
      });
    },

    /**
     * Show loading state
     */
    loading: function (title) {
      if (typeof Swal === 'undefined') {
        console.error('SweetAlert2 is not loaded');
        return;
      }

      return Swal.fire({
        title: title || 'Loading...',
        allowOutsideClick: false,
        didOpen: function () {
          Swal.showLoading();
        }
      });
    },

    /**
     * Show info notification
     */
    info: function (title, text) {
      if (typeof Swal === 'undefined') {
        console.error('SweetAlert2 is not loaded');
        return;
      }

      return Swal.fire({
        icon: 'info',
        title: title || 'Information',
        text: text || ''
      });
    }
  };

  /**
   * Bootstrap Helper
   */
  CSI.Bootstrap = {
    /**
     * Initialize Bootstrap modal
     */
    modal: function (selector, options) {
      if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap is not loaded');
        return null;
      }

      var element = document.querySelector(selector);
      if (!element) {
        console.error('Modal element not found: ' + selector);
        return null;
      }

      return new bootstrap.Modal(element, options || {});
    },

    /**
     * Show Bootstrap modal
     */
    showModal: function (selector) {
      // Check if Bootstrap is available
      if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap is not loaded');
        // Fallback: show modal using jQuery if available
        if (typeof $ !== 'undefined' && $.fn.modal) {
          $(selector).modal('show');
        } else {
          // Last resort: show with inline style
          var modalEl = document.querySelector(selector);
          if (modalEl) {
            modalEl.style.display = 'block';
            modalEl.classList.add('show');
            // Add backdrop
            var backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            document.body.appendChild(backdrop);
          }
        }
        return;
      }
      
      var modal = this.modal(selector);
      if (modal) {
        modal.show();
      }
    },

    /**
     * Hide Bootstrap modal
     */
    hideModal: function (selector) {
      // Check if Bootstrap is available
      if (typeof bootstrap === 'undefined') {
        // Fallback: hide modal using jQuery if available
        if (typeof $ !== 'undefined' && $.fn.modal) {
          $(selector).modal('hide');
        } else {
          // Last resort: hide with inline style
          var modalEl = document.querySelector(selector);
          if (modalEl) {
            modalEl.style.display = 'none';
            modalEl.classList.remove('show');
            // Remove backdrop
            var backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
              backdrop.remove();
            }
          }
        }
        return;
      }
      
      var modal = this.modal(selector);
      if (modal) {
        modal.hide();
      }
    }
  };

  /**
   * Notification Helper (combines WordPress notices + SweetAlert2)
   */
  CSI.Notify = {
    /**
     * Show success notification (WordPress + SweetAlert2)
     */
    success: function (message, useSwal) {
      // Add WordPress notification
      if (typeof CSI.Notifications !== 'undefined') {
        CSI.Notifications.success(message);
      }

      // Also show SweetAlert2 if requested
      if (useSwal && typeof CSI.Swal !== 'undefined') {
        CSI.Swal.success('Success!', message);
      }
    },

    /**
     * Show error notification (WordPress + SweetAlert2)
     */
    error: function (message, useSwal) {
      // Add WordPress notification
      if (typeof CSI.Notifications !== 'undefined') {
        CSI.Notifications.error(message);
      }

      // Also show SweetAlert2 if requested
      if (useSwal && typeof CSI.Swal !== 'undefined') {
        CSI.Swal.error('Error!', message);
      }
    },

    /**
     * Show warning notification (WordPress + SweetAlert2)
     */
    warning: function (message, useSwal) {
      // Add WordPress notification
      if (typeof CSI.Notifications !== 'undefined') {
        CSI.Notifications.warning(message);
      }

      // Also show SweetAlert2 if requested
      if (useSwal && typeof CSI.Swal !== 'undefined') {
        CSI.Swal.info('Warning', message);
      }
    },

    /**
     * Show info notification (WordPress + SweetAlert2)
     */
    info: function (message, useSwal) {
      // Add WordPress notification
      if (typeof CSI.Notifications !== 'undefined') {
        CSI.Notifications.info(message);
      }

      // Also show SweetAlert2 if requested
      if (useSwal && typeof CSI.Swal !== 'undefined') {
        CSI.Swal.info('Information', message);
      }
    }
  };

  /**
   * Check if all global assets are loaded
   */
  CSI.checkAssets = function () {
    var checks = {
      bootstrap: typeof bootstrap !== 'undefined',
      datatables: typeof $.fn.DataTable !== 'undefined',
      sweetalert2: typeof Swal !== 'undefined',
      chartjs: typeof Chart !== 'undefined',
      notifications: typeof CSI.Notifications !== 'undefined'
    };

    return checks;
  };

  /**
   * Wait for assets to load
   */
  CSI.waitForAssets = function (callback) {
    var maxAttempts = 50;
    var attempts = 0;

    var checkInterval = setInterval(function () {
      attempts++;
      var checks = CSI.checkAssets();

      if (checks.bootstrap && checks.datatables && checks.sweetalert2) {
        clearInterval(checkInterval);
        if (callback) callback();
      } else if (attempts >= maxAttempts) {
        clearInterval(checkInterval);
        console.error('Assets failed to load');
      }
    }, 100);
  };

  // Document ready
  $(document).ready(function () {
    // Log asset status (for debugging)
    if (typeof csiGlobal !== 'undefined' && csiGlobal.assetsLoaded) {
      console.log('CSI Global Assets Loaded');
      console.log('Asset Status:', CSI.checkAssets());
    }
  });

})(jQuery);
