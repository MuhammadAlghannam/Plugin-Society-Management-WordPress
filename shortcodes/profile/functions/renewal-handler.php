<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle renewal submission
 */
function csi_handle_renewal_submission() {
    check_ajax_referer('csi_renewal_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => __('You must be logged in to renew.', 'custom-signup-plugin')]);
    }
    
    $user_id = get_current_user_id();
    
    // Handle file upload
    if (!isset($_FILES['csi_payment_receipt']) || $_FILES['csi_payment_receipt']['error'] === UPLOAD_ERR_NO_FILE) {
        wp_send_json_error(['message' => __('Payment receipt is required.', 'custom-signup-plugin')]);
    }
    
    // Check file size and type
    $file = $_FILES['csi_payment_receipt'];
    $max_size = 80 * 1024 * 1024; // 80MB
    $allowed_types = ['image/jpeg', 'image/png'];
    
    if ($file['size'] > $max_size) {
        wp_send_json_error(['message' => __('File size exceeds 80MB limit.', 'custom-signup-plugin')]);
    }
    
    if (!in_array($file['type'], $allowed_types)) {
        wp_send_json_error(['message' => __('Invalid file type.', 'custom-signup-plugin')]);
    }
    
    // Upload file
    if (!function_exists('wp_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }
    
    $upload_overrides = ['test_form' => false];
    $movefile = wp_handle_upload($file, $upload_overrides);
    
    if (!$movefile || isset($movefile['error'])) {
        wp_send_json_error(['message' => __('File upload failed.', 'custom-signup-plugin')]);
    }
    
    // Create attachment
    $file_path = $movefile['file'];
    $attachment = [
        'post_mime_type' => $movefile['type'],
        'post_title'     => preg_replace('/\.[^.]+$/', '', basename($file_path)),
        'post_content'   => '',
        'post_status'    => 'inherit',
        'post_author'    => $user_id
    ];
    
    $attach_id = wp_insert_attachment($attachment, $file_path);
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
    wp_update_attachment_metadata($attach_id, $attach_data);
    
    // Update user meta
    update_user_meta($user_id, 'payment_receipt_id', $attach_id);
    update_user_meta($user_id, 'payment_receipt', wp_get_attachment_url($attach_id));
    
    $old_payment_status = get_user_meta($user_id, 'payment_status', true);
    update_user_meta($user_id, 'payment_status', 'inreview');
    
    // Clear caches to ensure status update is visible
    wp_cache_delete($user_id, 'user_meta');
    clean_user_cache($user_id);
    
    // Log history
    if (function_exists('csi_log_membership_event')) {
        csi_log_membership_event($user_id, 'renewal_submitted', [
            'receipt_id' => $attach_id,
            'old_payment_status' => $old_payment_status
        ]);
        
        csi_log_membership_event($user_id, 'payment_status_changed', [
            'old_status' => $old_payment_status,
            'new_status' => 'inreview'
        ]);
    }
    
    wp_send_json_success(['message' => __('Your renew membership process is under review now.', 'custom-signup-plugin')]);
}
