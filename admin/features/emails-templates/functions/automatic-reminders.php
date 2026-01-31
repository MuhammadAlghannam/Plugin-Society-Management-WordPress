<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Automatic Reminders System
 * Sends reminder emails 11 months after membership start date
 */

require_once CSI_PLUGIN_DIR . 'admin/features/emails-templates/functions/email-sender.php';

/**
 * Schedule daily reminder check
 */
function csi_schedule_reminder_check() {
    if (!wp_next_scheduled('csi_daily_reminder_check')) {
        wp_schedule_event(time(), 'daily', 'csi_daily_reminder_check');
    }
}
add_action('wp', 'csi_schedule_reminder_check');

/**
 * Daily reminder check
 */
function csi_daily_reminder_check() {
    global $wpdb;
    
    // Get reminder template (you can configure this)
    $reminder_template_id = get_option('csi_reminder_template_id', 0);
    
    if (!$reminder_template_id) {
        return; // No template configured
    }
    
    // Calculate date 11 months from now
    $reminder_date = date('Y-m-d', strtotime('+11 months'));
    
    // Get users whose membership_start_date is 11 months ago
    $users = $wpdb->get_results($wpdb->prepare("
        SELECT DISTINCT u.ID, um.meta_value as membership_start_date
        FROM {$wpdb->users} u
        JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
        LEFT JOIN {$wpdb->prefix}csi_email_reminders er ON u.ID = er.user_id 
            AND er.template_id = %d 
            AND er.reminder_date = %s
            AND er.status = 'sent'
        WHERE um.meta_key = 'membership_start_date'
          AND DATE(um.meta_value) = DATE_SUB(%s, INTERVAL 11 MONTH)
          AND er.id IS NULL
        LIMIT 50
    ", $reminder_template_id, $reminder_date, $reminder_date), ARRAY_A);
    
    foreach ($users as $user_data) {
        $user_id = intval($user_data['ID']);
        $membership_date = $user_data['membership_start_date'];
        
        // Create reminder record
        $reminder_id = csi_create_reminder_record($user_id, $reminder_template_id, $membership_date, $reminder_date);
        
        if ($reminder_id) {
            // Send reminder email
            $result = csi_send_template_email($reminder_template_id, $user_id);
            
            if (!is_wp_error($result)) {
                // Mark as sent
                csi_update_reminder_status($reminder_id, 'sent');
                
                // Log history
                if (function_exists('csi_log_membership_event')) {
                    $template = csi_get_email_template($reminder_template_id);
                    $template_name = $template ? $template['template_name'] : 'Unknown';
                    
                    csi_log_membership_event($user_id, 'reminder_sent', [
                        'template_id' => $reminder_template_id,
                        'template_name' => $template_name,
                        'reminder_date' => $reminder_date
                    ]);
                }
            } else {
                // Mark as failed
                csi_update_reminder_status($reminder_id, 'failed');
            }
        }
    }
}
add_action('csi_daily_reminder_check', 'csi_daily_reminder_check');

/**
 * Create reminder record
 */
function csi_create_reminder_record($user_id, $template_id, $membership_date, $reminder_date) {
    global $wpdb;
    $table_name = CSI_Database::get_table_name('csi_email_reminders');
    
    // Check if reminder already exists
    $existing = $wpdb->get_var($wpdb->prepare("
        SELECT id FROM {$table_name}
        WHERE user_id = %d
          AND template_id = %d
          AND reminder_date = %s
    ", $user_id, $template_id, $reminder_date));
    
    if ($existing) {
        return $existing;
    }
    
    $result = $wpdb->insert(
        $table_name,
        [
            'user_id' => $user_id,
            'template_id' => $template_id,
            'reminder_type' => 'membership_renewal',
            'membership_date' => $membership_date,
            'reminder_date' => $reminder_date,
            'status' => 'pending'
        ]
    );
    
    return $result ? $wpdb->insert_id : false;
}

/**
 * Update reminder status
 */
function csi_update_reminder_status($reminder_id, $status) {
    global $wpdb;
    $table_name = CSI_Database::get_table_name('csi_email_reminders');
    
    $data = ['status' => $status];
    
    if ($status === 'sent') {
        $data['sent_at'] = current_time('mysql');
    }
    
    return $wpdb->update(
        $table_name,
        $data,
        ['id' => $reminder_id]
    );
}

/**
 * Get user reminders
 */
function csi_get_user_reminders($user_id) {
    global $wpdb;
    $table_name = CSI_Database::get_table_name('csi_email_reminders');
    
    return $wpdb->get_results($wpdb->prepare("
        SELECT er.*, et.template_name
        FROM {$table_name} er
        LEFT JOIN {$wpdb->prefix}csi_email_templates et ON er.template_id = et.id
        WHERE er.user_id = %d
        ORDER BY er.reminder_date DESC
    ", $user_id), ARRAY_A);
}

/**
 * Get reminder statistics
 */
function csi_get_reminder_stats() {
    global $wpdb;
    $table_name = CSI_Database::get_table_name('csi_email_reminders');
    
    $stats = [
        'total' => $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}"),
        'sent' => $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status = 'sent'"),
        'pending' => $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status = 'pending'"),
        'failed' => $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status = 'failed'")
    ];
    
    return $stats;
}
