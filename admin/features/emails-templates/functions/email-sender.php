<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Email Sender
 * Handles sending emails using templates
 */

require_once CSI_PLUGIN_DIR . 'admin/features/emails-templates/functions/placeholder-replacer.php';

/**
 * Send email to user using template
 */
function csi_send_template_email($template_id, $user_id, $additional_data = []) {
    $template = csi_get_email_template($template_id);
    
    if (!$template) {
        return new WP_Error('template_not_found', __('Template not found.', 'custom-signup-plugin'));
    }
    
    $user = get_user_by('ID', $user_id);
    if (!$user) {
        return new WP_Error('user_not_found', __('User not found.', 'custom-signup-plugin'));
    }
    
    // Replace placeholders
    $subject = csi_replace_placeholders($template['subject'] ?? '', $user_id);
    $body = csi_replace_placeholders($template['body_html'] ?? '', $user_id);
    
    // Ensure subject and body are strings
    $subject = $subject ?: '';
    $body = $body ?: '';
    
    // Apply additional data replacements
    if (!empty($additional_data)) {
        foreach ($additional_data as $key => $value) {
            $value = $value ?: '';
            $subject = str_replace('{' . $key . '}', $value, $subject);
            $body = str_replace('{' . $key . '}', $value, $body);
        }
    }
    
    // Set email headers
    $headers = ['Content-Type: text/html; charset=UTF-8'];
    
    // Get sender info
    $from_name = !empty($template['from_name']) ? $template['from_name'] : get_bloginfo('name');
    
    // Determine from email (prioritize SMTP settings, fallback to admin email)
    $smtp_email = get_option('csi_smtp_email');
    $from_email = !empty($smtp_email) ? $smtp_email : get_option('admin_email');
    
    $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
    $headers[] = 'Reply-To: ' . $from_email;
    
    // Send email
    $result = wp_mail($user->user_email, $subject, $body, $headers);
    
    if ($result) {
        return true;
    } else {
        return new WP_Error('send_failed', __('Failed to send email.', 'custom-signup-plugin'));
    }
}

/**
 * Send bulk emails
 */
function csi_send_bulk_emails($template_id, $user_ids, $batch_size = 50) {
    $results = [
        'success' => 0,
        'failed' => 0,
        'errors' => []
    ];
    
    $batches = array_chunk($user_ids, $batch_size);
    
    foreach ($batches as $batch) {
        foreach ($batch as $user_id) {
            $result = csi_send_template_email($template_id, $user_id);
            
            if (is_wp_error($result)) {
                $results['failed']++;
                $results['errors'][] = sprintf(__('User %d: %s', 'custom-signup-plugin'), $user_id, $result->get_error_message());
            } else {
                $results['success']++;
            }
        }
        
        // Small delay between batches to avoid overwhelming the server
        if (count($batches) > 1) {
            sleep(1);
        }
    }
    
    return $results;
}

/**
 * AJAX: Send bulk email
 */
function csi_ajax_send_bulk_email() {
    check_ajax_referer('csi_global_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Insufficient permissions', 'custom-signup-plugin')]);
    }
    
    $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
    $user_ids = isset($_POST['user_ids']) ? array_map('intval', $_POST['user_ids']) : [];
    
    if (!$template_id || empty($user_ids)) {
        wp_send_json_error(['message' => __('Template ID and user IDs are required.', 'custom-signup-plugin')]);
    }
    
    $results = csi_send_bulk_emails($template_id, $user_ids);
    
    $message = sprintf(
        __('Emails sent: %d successful, %d failed.', 'custom-signup-plugin'),
        $results['success'],
        $results['failed']
    );
    
    wp_send_json_success([
        'message' => $message,
        'results' => $results
    ]);
}
add_action('wp_ajax_csi_send_bulk_email', 'csi_ajax_send_bulk_email');
