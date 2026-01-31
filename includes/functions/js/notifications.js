/**
 * Global WordPress Notification System - JavaScript
 */
(function ($) {
  'use strict';

  // Add to global namespace
  window.CSI = window.CSI || {};

  /**
   * Notification Helper
   */
  CSI.Notifications = {
    /**
     * Add notification via AJAX
     */
    add: function (message, type, dismissible, duration) {
      return $.ajax({
        url: csiNotifications.ajaxurl,
        type: 'POST',
        data: {
          action: 'csi_add_notification',
          message: message,
          type: type || 'info',
          dismissible: dismissible !== false,
          duration: duration || 0,
          nonce: csiNotifications.nonce
        }
      });
    },

    /**
     * Show success notification
     */
    success: function (message, dismissible, duration) {
      return this.add(message, 'success', dismissible, duration || 5000);
    },

    /**
     * Show error notification
     */
    error: function (message, dismissible, duration) {
      return this.add(message, 'error', dismissible, duration || 0);
    },

    /**
     * Show warning notification
     */
    warning: function (message, dismissible, duration) {
      return this.add(message, 'warning', dismissible, duration || 5000);
    },

    /**
     * Show info notification
     */
    info: function (message, dismissible, duration) {
      return this.add(message, 'info', dismissible, duration || 3000);
    },

    /**
     * Dismiss notification
     */
    dismiss: function (notificationId) {
      return $.ajax({
        url: csiNotifications.ajaxurl,
        type: 'POST',
        data: {
          action: 'csi_dismiss_notification',
          notification_id: notificationId,
          nonce: csiNotifications.nonce
        }
      });
    }
  };

  // Document ready
  $(document).ready(function () {
    // Handle auto-dismiss
    $('.csi-notification[data-auto-dismiss]').each(function () {
      var $notice = $(this);
      var duration = parseInt($notice.data('auto-dismiss'));

      if (duration > 0) {
        setTimeout(function () {
          $notice.fadeOut(300, function () {
            var notificationId = $notice.data('notification-id');
            if (notificationId) {
              CSI.Notifications.dismiss(notificationId);
            }
            $notice.remove();
          });
        }, duration);
      }
    });

    // Handle manual dismiss
    $(document).on('click', '.csi-dismiss-notification', function (e) {
      e.preventDefault();
      var $button = $(this);
      var $notice = $button.closest('.csi-notification');
      var notificationId = $notice.data('notification-id');

      if (notificationId) {
        CSI.Notifications.dismiss(notificationId).done(function () {
          $notice.fadeOut(300, function () {
            $notice.remove();
          });
        });
      } else {
        $notice.fadeOut(300, function () {
          $notice.remove();
        });
      }
    });
  });

})(jQuery);
