<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Global WordPress Notification System
 * Handles all notifications across the plugin
 */
class CSI_Notifications {
    
    private static $instance = null;
    private static $transient_key = 'csi_admin_notifications';
    private static $transient_expiry = 300; // 5 minutes
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize notification system
     */
    public function __construct() {
        add_action('admin_notices', [$this, 'display_notifications']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_notification_assets']);
        add_action('wp_ajax_csi_add_notification', [$this, 'ajax_add_notification']);
        add_action('wp_ajax_csi_dismiss_notification', [$this, 'ajax_dismiss_notification']);
    }
    
    /**
     * Add a notification
     * 
     * @param string $message Notification message
     * @param string $type Notification type (success, error, warning, info)
     * @param bool $dismissible Whether notification can be dismissed
     * @param int $duration Auto-dismiss duration in seconds (0 = no auto-dismiss)
     * @return bool Success status
     */
    public static function add($message, $type = 'info', $dismissible = true, $duration = 0) {
        $notifications = get_transient(self::$transient_key);
        if (!is_array($notifications)) {
            $notifications = [];
        }
        
        $notification = [
            'id' => uniqid('csi_notice_'),
            'message' => wp_kses_post($message), // Allow HTML in messages
            'type' => sanitize_text_field($type),
            'dismissible' => (bool) $dismissible,
            'duration' => absint($duration),
            'timestamp' => current_time('mysql'),
            'dismissed' => false
        ];
        
        $notifications[] = $notification;
        
        // Limit to last 50 notifications
        if (count($notifications) > 50) {
            $notifications = array_slice($notifications, -50);
        }
        
        return set_transient(self::$transient_key, $notifications, self::$transient_expiry);
    }
    
    /**
     * Add success notification
     */
    public static function success($message, $dismissible = true, $duration = 5000) {
        return self::add($message, 'success', $dismissible, $duration);
    }
    
    /**
     * Add error notification
     */
    public static function error($message, $dismissible = true, $duration = 0) {
        return self::add($message, 'error', $dismissible, $duration);
    }
    
    /**
     * Add warning notification
     */
    public static function warning($message, $dismissible = true, $duration = 5000) {
        return self::add($message, 'warning', $dismissible, $duration);
    }
    
    /**
     * Add info notification
     */
    public static function info($message, $dismissible = true, $duration = 3000) {
        return self::add($message, 'info', $dismissible, $duration);
    }
    
    /**
     * Display notifications
     */
    public function display_notifications() {
        $notifications = get_transient(self::$transient_key);
        if (!is_array($notifications) || empty($notifications)) {
            return;
        }
        
        foreach ($notifications as $notification) {
            // Skip dismissed notifications
            if (!empty($notification['dismissed'])) {
                continue;
            }
            
            $class = 'notice notice-' . esc_attr($notification['type']);
            if ($notification['dismissible']) {
                $class .= ' is-dismissible';
            }
            
            $notification_id = esc_attr($notification['id']);
            $duration = absint($notification['duration']);
            $data_attrs = '';
            
            if ($duration > 0) {
                $data_attrs = ' data-auto-dismiss="' . $duration . '"';
            }
            
            echo '<div class="' . $class . ' csi-notification" data-notification-id="' . $notification_id . '"' . $data_attrs . '>';
            echo '<p>' . wp_kses_post($notification['message']) . '</p>';
            if ($notification['dismissible']) {
                echo '<button type="button" class="notice-dismiss csi-dismiss-notification" data-notification-id="' . $notification_id . '">';
                echo '<span class="screen-reader-text">Dismiss this notice.</span>';
                echo '</button>';
            }
            echo '</div>';
        }
    }
    
    /**
     * Enqueue notification assets
     */
    public function enqueue_notification_assets($hook) {
        // Only on our plugin pages
        $our_pages = [
            'toplevel_page_membership-applications',
            'csi-dashboard_page_membership-applications',
            'custom-signup_page_membership-applications',
            'custom-signup_page_csi-dashboard',
            'custom-signup_page_csi-membership-number-settings',
            'custom-signup_page_csi-email-templates',
        ];
        
        // Also check by page parameter as fallback
        $is_our_page = in_array($hook, $our_pages);
        if (!$is_our_page && isset($_GET['page'])) {
            $page = sanitize_text_field($_GET['page']);
            $our_page_slugs = ['membership-applications', 'csi-dashboard', 'csi-membership-number-settings', 'csi-email-templates'];
            $is_our_page = in_array($page, $our_page_slugs);
        }
        
        if (!$is_our_page) {
            return;
        }
        
        wp_enqueue_script(
            'csi-notifications',
            CSI_PLUGIN_URL . 'includes/functions/js/notifications.js',
            ['jquery'],
            CSI_VERSION,
            true
        );
        
        wp_localize_script('csi-notifications', 'csiNotifications', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('csi_notification_nonce')
        ]);
    }
    
    /**
     * AJAX: Add notification
     */
    public function ajax_add_notification() {
        check_ajax_referer('csi_notification_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }
        
        $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'info';
        $dismissible = isset($_POST['dismissible']) ? (bool) $_POST['dismissible'] : true;
        $duration = isset($_POST['duration']) ? absint($_POST['duration']) : 0;
        
        if (empty($message)) {
            wp_send_json_error(['message' => 'Message is required']);
        }
        
        $result = self::add($message, $type, $dismissible, $duration);
        
        if ($result) {
            wp_send_json_success([
                'message' => 'Notification added',
                'notification' => [
                    'message' => $message,
                    'type' => $type
                ]
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to add notification']);
        }
    }
    
    /**
     * AJAX: Dismiss notification
     */
    public function ajax_dismiss_notification() {
        check_ajax_referer('csi_notification_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }
        
        $notification_id = isset($_POST['notification_id']) ? sanitize_text_field($_POST['notification_id']) : '';
        
        if (empty($notification_id)) {
            wp_send_json_error(['message' => 'Notification ID is required']);
        }
        
        $notifications = get_transient(self::$transient_key);
        if (!is_array($notifications)) {
            wp_send_json_error(['message' => 'No notifications found']);
        }
        
        foreach ($notifications as &$notification) {
            if ($notification['id'] === $notification_id) {
                $notification['dismissed'] = true;
                break;
            }
        }
        
        set_transient(self::$transient_key, $notifications, self::$transient_expiry);
        
        wp_send_json_success(['message' => 'Notification dismissed']);
    }
    
    /**
     * Clear all notifications
     */
    public static function clear_all() {
        return delete_transient(self::$transient_key);
    }
    
    /**
     * Get all notifications
     */
    public static function get_all() {
        return get_transient(self::$transient_key) ?: [];
    }
    
    /**
     * Get notifications by type
     */
    public static function get_by_type($type) {
        $notifications = self::get_all();
        return array_filter($notifications, function($notification) use ($type) {
            return $notification['type'] === $type && empty($notification['dismissed']);
        });
    }
}

// Initialize notification system
CSI_Notifications::get_instance();

/**
 * Helper functions for easy access
 */

/**
 * Add success notification
 */
function csi_notify_success($message, $dismissible = true, $duration = 5000) {
    return CSI_Notifications::success($message, $dismissible, $duration);
}

/**
 * Add error notification
 */
function csi_notify_error($message, $dismissible = true, $duration = 0) {
    return CSI_Notifications::error($message, $dismissible, $duration);
}

/**
 * Add warning notification
 */
function csi_notify_warning($message, $dismissible = true, $duration = 5000) {
    return CSI_Notifications::warning($message, $dismissible, $duration);
}

/**
 * Add info notification
 */
function csi_notify_info($message, $dismissible = true, $duration = 3000) {
    return CSI_Notifications::info($message, $dismissible, $duration);
}

/**
 * Add custom notification
 */
function csi_notify($message, $type = 'info', $dismissible = true, $duration = 0) {
    return CSI_Notifications::add($message, $type, $dismissible, $duration);
}
