<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Membership Expiry System
 * Handles automatic membership expiration
 */

/**
 * Schedule daily membership expiry check
 */
function csi_schedule_expiry_check() {
    if (!wp_next_scheduled('csi_daily_membership_expiry_check')) {
        wp_schedule_event(time(), 'daily', 'csi_daily_membership_expiry_check');
    }
}
add_action('wp', 'csi_schedule_expiry_check');

/**
 * Daily membership expiry check
 */
function csi_daily_membership_expiry_check() {
    global $wpdb;
    
    // Get users whose membership_end_date has passed (yesterday or earlier)
    // and who are currently active or payment status is paid
    $expired_users = $wpdb->get_results("
        SELECT DISTINCT u.ID, um.meta_value as membership_end_date
        FROM {$wpdb->users} u
        JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
        JOIN {$wpdb->usermeta} um_status ON u.ID = um_status.user_id
        WHERE um.meta_key = 'membership_end_date'
          AND DATE(um.meta_value) < DATE(NOW())
          AND um_status.meta_key = 'user_status'
          AND um_status.meta_value = 'active'
        LIMIT 100
    ", ARRAY_A);
    
    if (empty($expired_users)) {
        return;
    }
    
    foreach ($expired_users as $user_data) {
        $user_id = intval($user_data['ID']);
        $end_date = $user_data['membership_end_date'];
        
        // Double check payment status to be safe, although we mainly care about active status
        $payment_status = get_user_meta($user_id, 'payment_status', true);
        
        // Update statuses
        update_user_meta($user_id, 'user_status', 'not_active');
        update_user_meta($user_id, 'payment_status', 'declined');
        
        // Clear caches
        clean_user_cache($user_id);
        
        // Log to history
        if (function_exists('csi_log_membership_event')) {
            csi_log_membership_event($user_id, 'membership_expired', [
                'end_date' => $end_date,
                'previous_payment_status' => $payment_status
            ]);
        }
    }
}
add_action('csi_daily_membership_expiry_check', 'csi_daily_membership_expiry_check');

/**
 * Get days until expiry for a user
 * 
 * @param int $user_id User ID
 * @return int|false Days until expiry or false if no date
 */
function csi_get_days_until_expiry($user_id) {
    $end_date = get_user_meta($user_id, 'membership_end_date', true);
    
    if (empty($end_date)) {
        return false;
    }
    
    $end_timestamp = strtotime($end_date);
    $now = time();
    
    $diff = $end_timestamp - $now;
    return round($diff / (60 * 60 * 24));
}

/**
 * Check if membership is expired
 * 
 * @param int $user_id User ID
 * @return bool True if expired
 */
function csi_is_membership_expired($user_id) {
    $days = csi_get_days_until_expiry($user_id);
    return $days !== false && $days < 0;
}
