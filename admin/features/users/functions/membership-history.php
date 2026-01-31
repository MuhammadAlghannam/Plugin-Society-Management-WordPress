<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Membership History Functions
 * Handles tracking and retrieving membership history events
 */

/**
 * Log a membership history event
 * 
 * @param int $user_id User ID
 * @param string $event_type Event type (registration, renewal_submitted, renewal_approved, status_changed, payment_changed, reminder_sent, membership_expired)
 * @param mixed $event_data Additional data to store (array or object will be JSON encoded)
 * @return int|false Insert ID on success, false on failure
 */
function csi_log_membership_event($user_id, $event_type, $event_data = []) {
    global $wpdb;
    $table_name = CSI_Database::get_table_name('csi_membership_history');
    
    // Ensure table exists
    if (!CSI_Database::table_exists('csi_membership_history')) {
        CSI_Database::create_tables();
    }
    
    // Encode data if not empty
    $data_json = !empty($event_data) ? json_encode($event_data) : null;
    
    $result = $wpdb->insert(
        $table_name,
        [
            'user_id' => $user_id,
            'event_type' => $event_type,
            'event_data' => $data_json,
            'created_at' => current_time('mysql')
        ]
    );
    
    return $result ? $wpdb->insert_id : false;
}

/**
 * Get user membership history
 * 
 * @param int $user_id User ID
 * @return array Array of history events
 */
function csi_get_user_membership_history($user_id) {
    global $wpdb;
    $table_name = CSI_Database::get_table_name('csi_membership_history');
    
    $results = $wpdb->get_results($wpdb->prepare("
        SELECT * FROM {$table_name}
        WHERE user_id = %d
        ORDER BY created_at DESC
    ", $user_id), ARRAY_A);
    
    // Decode JSON data
    if (!empty($results)) {
        foreach ($results as &$row) {
            if (!empty($row['event_data'])) {
                $row['event_data'] = json_decode($row['event_data'], true);
            } else {
                $row['event_data'] = [];
            }
        }
    }
    
    return $results ?: [];
}

/**
 * Format history event for display
 * 
 * @param array $event History event row
 * @return array Formatted event with title, description, icon, and class
 */
function csi_format_history_event($event) {
    $formatted = [
        'title' => '',
        'description' => '',
        'icon' => 'dashicons-info',
        'class' => 'info',
        'date' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($event['created_at']))
    ];
    
    $data = $event['event_data'];
    
    switch ($event['event_type']) {
        case 'registration':
            $formatted['title'] = __('Registration', 'custom-signup-plugin');
            $formatted['description'] = __('User registered successfully.', 'custom-signup-plugin');
            $formatted['icon'] = 'dashicons-welcome-write-blog';
            $formatted['class'] = 'primary';
            break;
            
        case 'renewal_submitted':
            $formatted['title'] = __('Renewal Submitted', 'custom-signup-plugin');
            $formatted['description'] = __('User submitted membership renewal request.', 'custom-signup-plugin');
            $formatted['icon'] = 'dashicons-upload';
            $formatted['class'] = 'warning';
            break;
            
        case 'renewal_approved':
            $formatted['title'] = __('Renewal Approved', 'custom-signup-plugin');
            $formatted['description'] = sprintf(
                __('Membership renewed by admin. New end date: %s', 'custom-signup-plugin'),
                isset($data['new_end_date']) ? date_i18n(get_option('date_format'), strtotime($data['new_end_date'])) : '-'
            );
            $formatted['icon'] = 'dashicons-awards';
            $formatted['class'] = 'success';
            break;
            
        case 'status_changed':
            $old_status = isset($data['old_status']) ? ucfirst(str_replace('_', ' ', $data['old_status'])) : '?';
            $new_status = isset($data['new_status']) ? ucfirst(str_replace('_', ' ', $data['new_status'])) : '?';
            
            $formatted['title'] = __('Status Changed', 'custom-signup-plugin');
            $formatted['description'] = sprintf(
                __('User status changed from "%s" to "%s".', 'custom-signup-plugin'),
                $old_status,
                $new_status
            );
            $formatted['icon'] = 'dashicons-admin-users';
            $formatted['class'] = $data['new_status'] === 'active' ? 'success' : 'secondary';
            break;
            
        case 'payment_status_changed':
            $old_status = isset($data['old_status']) ? ucfirst($data['old_status']) : '?';
            $new_status = isset($data['new_status']) ? ucfirst($data['new_status']) : '?';
            
            $formatted['title'] = __('Payment Status Updated', 'custom-signup-plugin');
            $formatted['description'] = sprintf(
                __('Payment status updated from "%s" to "%s".', 'custom-signup-plugin'),
                $old_status,
                $new_status
            );
            $formatted['icon'] = 'dashicons-money-alt';
            
            if ($data['new_status'] === 'paid') {
                $formatted['class'] = 'success';
            } elseif ($data['new_status'] === 'inreview') {
                $formatted['class'] = 'warning';
            } elseif ($data['new_status'] === 'failed' || $data['new_status'] === 'declined') {
                $formatted['class'] = 'danger';
            } else {
                $formatted['class'] = 'info';
            }
            break;
            
        case 'reminder_sent':
            $template_name = isset($data['template_name']) ? $data['template_name'] : __('Unknown Template', 'custom-signup-plugin');
            $formatted['title'] = __('Reminder Email Sent', 'custom-signup-plugin');
            $formatted['description'] = sprintf(
                __('Automatic reminder email sent using template: %s', 'custom-signup-plugin'),
                $template_name
            );
            $formatted['icon'] = 'dashicons-email-alt';
            $formatted['class'] = 'info';
            break;
            
        case 'membership_expired':
            $formatted['title'] = __('Membership Expired', 'custom-signup-plugin');
            $formatted['description'] = __('Membership period has ended. Status set to Not Active.', 'custom-signup-plugin');
            $formatted['icon'] = 'dashicons-clock';
            $formatted['class'] = 'danger';
            break;
            
        default:
            $formatted['title'] = ucfirst(str_replace('_', ' ', $event['event_type']));
            $formatted['description'] = '-';
            $formatted['icon'] = 'dashicons-info';
            $formatted['class'] = 'secondary';
    }
    
    return $formatted;
}

/**
 * Export user membership history to CSV
 * 
 * @param int $user_id User ID
 */
function csi_export_user_history_to_csv($user_id) {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have permission to perform this action.', 'custom-signup-plugin'));
    }
    
    // Verify user exists
    $user = get_user_by('ID', $user_id);
    if (!$user) {
        wp_die(__('User not found.', 'custom-signup-plugin'));
    }
    
    // Get user history
    $history = csi_get_user_membership_history($user_id);
    
    if (empty($history)) {
        wp_die(__('No history found to export.', 'custom-signup-plugin'));
    }
    
    // Get user name for filename
    $user_name = get_user_meta($user_id, 'fullname', true);
    if (empty($user_name)) {
        $user_name = $user->user_email;
    }
    $user_name = sanitize_file_name($user_name);
    
    // Clean any previous output
    if (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
    
    $filename = 'membership-history-' . $user_name . '-' . date('Y-m-d') . '.csv';
    $csv_data = "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
    
    // CSV header row
    $headers = [
        __('Date & Time', 'custom-signup-plugin'),
        __('Event Type', 'custom-signup-plugin'),
        __('Title', 'custom-signup-plugin'),
        __('Description', 'custom-signup-plugin')
    ];
    $csv_data .= implode(',', array_map('csi_escape_csv_field', $headers)) . "\n";
    
    // Add history rows
    foreach ($history as $event) {
        $formatted = csi_format_history_event($event);
        
        // Format date
        $date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($event['created_at']));
        
        // Format event type
        $event_type = ucfirst(str_replace('_', ' ', $event['event_type']));
        
        // Build CSV row
        $data = [
            $date,
            $event_type,
            $formatted['title'],
            $formatted['description']
        ];
        
        $csv_data .= implode(',', array_map('csi_escape_csv_field', $data)) . "\n";
    }
    
    // Clean output buffer and send CSV headers
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Output CSV headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output CSV data
    echo $csv_data;
    exit();
}

/**
 * Handle history export request
 */
function csi_handle_history_export() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    if (isset($_POST['csi_export_history'], $_POST['csi_user_id'])) {
        $user_id = intval($_POST['csi_user_id']);
        
        if (!isset($_POST['csi_history_export_nonce']) || !wp_verify_nonce($_POST['csi_history_export_nonce'], 'csi_export_history_' . $user_id)) {
            wp_die(__('Security check failed', 'custom-signup-plugin'));
        }
        
        csi_export_user_history_to_csv($user_id);
    }
}
add_action('admin_init', 'csi_handle_history_export');