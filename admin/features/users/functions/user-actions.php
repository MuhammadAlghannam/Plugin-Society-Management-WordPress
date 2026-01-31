<?php
/**
 * User Actions Handler
 * Handles activate, deactivate, delete, and payment status actions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle user actions (activate/deactivate, payment status, delete)
 */
function csi_handle_user_actions() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Handle activate/deactivate
    if (isset($_POST['csi_user_action'], $_POST['csi_user_id'], $_POST['csi_action'])) {
        $user_id = intval($_POST['csi_user_id']);
        $action = sanitize_text_field($_POST['csi_action']);
        
        if (!wp_verify_nonce($_POST['csi_user_action_nonce'], 'csi_' . $action . '_user_' . $user_id)) {
            wp_die(__('Security check failed', 'custom-signup-plugin'));
        }
        
        $new_status = ($action === 'activate') ? 'active' : 'not_active';
        $old_status = get_user_meta($user_id, 'user_status', true);
        
        update_user_meta($user_id, 'user_status', $new_status);
        wp_cache_delete($user_id, 'user_meta');
        clean_user_cache($user_id);
        
        // Log history
        if (function_exists('csi_log_membership_event')) {
            csi_log_membership_event($user_id, 'status_changed', [
                'old_status' => $old_status,
                'new_status' => $new_status
            ]);
        }
    }
    
    // Handle payment status update
    if (isset($_POST['csi_update_payment'], $_POST['csi_user_id'], $_POST['csi_payment_action'])) {
        $user_id = intval($_POST['csi_user_id']);
        $payment_action = sanitize_text_field($_POST['csi_payment_action']);
        
        if (!wp_verify_nonce($_POST['csi_payment_nonce'], 'csi_payment_status_' . $user_id)) {
            wp_die(__('Security check failed', 'custom-signup-plugin'));
        }
        
        $old_payment_status = get_user_meta($user_id, 'payment_status', true);
        update_user_meta($user_id, 'payment_status', $payment_action);
        wp_cache_delete($user_id, 'user_meta');
        clean_user_cache($user_id);
        
        // Log history
        if (function_exists('csi_log_membership_event')) {
            csi_log_membership_event($user_id, 'payment_status_changed', [
                'old_status' => $old_payment_status,
                'new_status' => $payment_action
            ]);
        }
        
        // Auto-set membership dates when payment status changes to "paid"
        if ($payment_action === 'paid' && $old_payment_status !== 'paid') {
            update_user_meta($user_id, 'membership_start_date', date('Y-m-d'));
            update_user_meta($user_id, 'membership_end_date', date('Y-m-d', strtotime('+1 year')));
        }
    }
    
    // Handle membership renewal approval
    if (isset($_POST['csi_approve_renewal'], $_POST['csi_user_id'])) {
        $user_id = intval($_POST['csi_user_id']);
        
        if (!wp_verify_nonce($_POST['csi_renewal_nonce'], 'csi_approve_renewal_' . $user_id)) {
            wp_die(__('Security check failed', 'custom-signup-plugin'));
        }
        
        // Approve renewal
        csi_handle_membership_renewal($user_id);
    }
    
    // Handle delete
    if (isset($_POST['csi_delete_user'], $_POST['csi_delete_user_id'])) {
        $user_id = intval($_POST['csi_delete_user_id']);
        
        if (!isset($_POST['csi_delete_nonce']) || !wp_verify_nonce($_POST['csi_delete_nonce'], 'csi_delete_user_' . $user_id)) {
            wp_die(__('Security check failed', 'custom-signup-plugin'));
        }
        
        // Don't delete current user or non-existent users
        if ($user_id == get_current_user_id() || !get_user_by('ID', $user_id)) {
            wp_safe_redirect(admin_url('admin.php?page=membership-applications'));
            exit;
        }
        
        require_once(ABSPATH . 'wp-admin/includes/user.php');
        wp_delete_user($user_id);
        wp_safe_redirect(admin_url('admin.php?page=membership-applications'));
        exit;
    }
}
add_action('admin_init', 'csi_handle_user_actions');

/**
 * Get user membership number, auto-generate if missing
 */
function csi_get_user_membership_number($user_id) {
    $membership_number = get_user_meta($user_id, 'membership_number', true);
    
    // If no membership number exists, try to generate one
    if (empty($membership_number)) {
        $membership_type = get_user_meta($user_id, 'membership', true);
        if (!empty($membership_type)) {
            // Auto-generate membership number
            if (function_exists('csi_assign_membership_number')) {
                $membership_number = csi_assign_membership_number($user_id, $membership_type);
            } elseif (function_exists('csi_assign_generated_id')) {
                // Backward compatibility
                $membership_number = csi_assign_generated_id($user_id, $membership_type);
            }
        }
    }
    
    // Fallback to user ID if still empty
    return !empty($membership_number) ? $membership_number : $user_id;
}

/**
 * Get user profile data
 */
function csi_get_user_profile_data($user_id) {
    $user = get_user_by('ID', $user_id);
    if (!$user) {
        return [];
    }
    
    return [
        'user_id' => $user_id,
        'fullname' => get_user_meta($user_id, 'fullname', true),
        'title' => get_user_meta($user_id, 'title', true),
        'specialty' => get_user_meta($user_id, 'specialty', true),
        'email' => $user->user_email,
        'phone' => get_user_meta($user_id, 'phone', true),
        'dob' => get_user_meta($user_id, 'dob', true),
        'home_address' => get_user_meta($user_id, 'home_address', true),
        'work_address' => get_user_meta($user_id, 'work_address', true),
        'institute' => get_user_meta($user_id, 'institute', true),
        'country' => get_user_meta($user_id, 'country', true),
        'membership_type' => csi_get_membership_type_label(get_user_meta($user_id, 'membership', true)),
        'registration_type' => get_user_meta($user_id, 'registration_type', true),
        'membership_number' => csi_get_user_membership_number($user_id),
        'generated_id' => csi_get_user_membership_number($user_id), // Backward compatibility
        'user_status' => get_user_meta($user_id, 'user_status', true) ?: 'not_active',
        'payment_status' => get_user_meta($user_id, 'payment_status', true) ?: 'pending',
        'payment_method' => get_user_meta($user_id, 'payment_method', true),
        'registration_date' => $user->user_registered,
        'membership_start_date' => get_user_meta($user_id, 'membership_start_date', true),
        'membership_end_date' => get_user_meta($user_id, 'membership_end_date', true),
        'files' => csi_get_user_files($user_id),
    ];
}

/**
 * Get user files
 */
function csi_get_user_files($user_id) {
    $files = [];
    
    $file_types = [
        'photo_id' => __('Personal Photo', 'custom-signup-plugin'),
        'cv_id' => __('CV', 'custom-signup-plugin'),
        'student_card_id' => __('Student Card', 'custom-signup-plugin'),
        'receipt_id' => __('Payment Receipt (Old)', 'custom-signup-plugin'),
        'payment_receipt_id' => __('Payment Receipt', 'custom-signup-plugin'),
    ];
    
    foreach ($file_types as $meta_key => $name) {
        $file_id = get_user_meta($user_id, $meta_key, true);
        if ($file_id) {
            $files[$meta_key] = [
                'id' => $file_id,
                'url' => wp_get_attachment_url($file_id),
                'name' => $name
            ];
        }
    }
    
    // Handle multiple ID scans
    $id_scan_ids = get_user_meta($user_id, 'id_scan_ids', true);
    if (is_array($id_scan_ids) && !empty($id_scan_ids)) {
        $files['id_scans'] = [];
        foreach ($id_scan_ids as $index => $scan_id) {
            $files['id_scans'][] = [
                'id' => $scan_id,
                'url' => wp_get_attachment_url($scan_id),
                'name' => __('ID Scan', 'custom-signup-plugin') . ' ' . ($index + 1)
            ];
        }
    }
    
    return $files;
}

/**
 * Handle membership renewal approval
 * 
 * @param int $user_id User ID
 */
function csi_handle_membership_renewal($user_id) {
    // 1. Update status to active
    $old_status = get_user_meta($user_id, 'user_status', true);
    update_user_meta($user_id, 'user_status', 'active');
    
    // 2. Update payment status to paid
    $old_payment_status = get_user_meta($user_id, 'payment_status', true);
    update_user_meta($user_id, 'payment_status', 'paid');
    
    // 3. Update dates
    $new_start_date = date('Y-m-d');
    $new_end_date = date('Y-m-d', strtotime('+12 months')); // 12 months from now
    
    update_user_meta($user_id, 'membership_start_date', $new_start_date);
    update_user_meta($user_id, 'membership_end_date', $new_end_date);
    
    // Clear caches
    wp_cache_delete($user_id, 'user_meta');
    clean_user_cache($user_id);
    
    // 4. Log history
    if (function_exists('csi_log_membership_event')) {
        csi_log_membership_event($user_id, 'status_changed', [
            'old_status' => $old_status,
            'new_status' => 'active',
            'reason' => 'renewal_approval'
        ]);
        
        csi_log_membership_event($user_id, 'payment_status_changed', [
            'old_status' => $old_payment_status,
            'new_status' => 'paid',
            'reason' => 'renewal_approval'
        ]);
        
        csi_log_membership_event($user_id, 'renewal_approved', [
            'new_start_date' => $new_start_date,
            'new_end_date' => $new_end_date
        ]);
    }
    
    // 5. Send renewal email
    $renewal_template_id = get_option('csi_renewal_template_id', 0);
    if ($renewal_template_id) {
        if (!function_exists('csi_send_template_email')) {
            require_once CSI_PLUGIN_DIR . 'admin/features/emails-templates/functions/email-sender.php';
        }
        
        csi_send_template_email($renewal_template_id, $user_id);
    }
    
    // Notify admin
    csi_notify_success(__('Membership renewed successfully.', 'custom-signup-plugin'));
}
