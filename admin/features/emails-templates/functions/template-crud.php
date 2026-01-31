<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Email Template CRUD Functions
 */

/**
 * Get all email templates
 */
function csi_get_email_templates() {
    global $wpdb;
    $table_name = CSI_Database::get_table_name('csi_email_templates');
    
    return $wpdb->get_results("
        SELECT * FROM {$table_name}
        ORDER BY created_at DESC
    ", ARRAY_A);
}

/**
 * Get active email templates (returns all templates for backward compatibility)
 */
function csi_get_active_email_templates() {
    return csi_get_email_templates();
}

/**
 * Get email template by ID
 */
function csi_get_email_template($template_id) {
    global $wpdb;
    $table_name = CSI_Database::get_table_name('csi_email_templates');
    
    return $wpdb->get_row($wpdb->prepare("
        SELECT * FROM {$table_name}
        WHERE id = %d
    ", $template_id), ARRAY_A);
}

/**
 * Save email template
 */
function csi_save_email_template($data) {
    global $wpdb;
    $table_name = CSI_Database::get_table_name('csi_email_templates');
    
    $defaults = [
        'template_name' => '',
        'from_name' => '',
        'from_email' => '',
        'subject' => '',
        'body_html' => '',
        'placeholders_json' => ''
    ];
    
    $data = wp_parse_args($data, $defaults);
    
    // Sanitize
    $template_name = sanitize_text_field($data['template_name']);
    $from_name = sanitize_text_field($data['from_name']);
    $from_email = sanitize_email($data['from_email']);
    $subject = sanitize_text_field($data['subject']);
    $body_html = wp_kses_post($data['body_html']);
    $placeholders_json = sanitize_text_field($data['placeholders_json']);
    
    if (empty($template_name) || empty($subject) || empty($body_html)) {
        return new WP_Error('missing_fields', __('Template name, subject, and body are required.', 'custom-signup-plugin'));
    }
    
    // Check for duplicate template name
    $existing = $wpdb->get_var($wpdb->prepare("
        SELECT id FROM {$table_name}
        WHERE template_name = %s AND id != %d
    ", $template_name, isset($data['id']) ? intval($data['id']) : 0));
    
    if ($existing) {
        return new WP_Error('duplicate_name', __('Template name already exists.', 'custom-signup-plugin'));
    }
    
    $template_data = [
        'template_name' => $template_name,
        'from_name' => $from_name,
        'from_email' => $from_email ?: get_option('admin_email'),
        'subject' => $subject,
        'body_html' => $body_html,
        'placeholders_json' => $placeholders_json
    ];
    
    if (isset($data['id']) && $data['id'] > 0) {
        // Update
        $result = $wpdb->update(
            $table_name,
            $template_data,
            ['id' => intval($data['id'])]
        );
        
        return $result !== false ? intval($data['id']) : new WP_Error('update_failed', __('Failed to update template.', 'custom-signup-plugin'));
    } else {
        // Insert
        $result = $wpdb->insert($table_name, $template_data);
        
        return $result ? $wpdb->insert_id : new WP_Error('insert_failed', __('Failed to create template.', 'custom-signup-plugin'));
    }
}

/**
 * Delete email template
 */
function csi_delete_email_template($template_id) {
    global $wpdb;
    $table_name = CSI_Database::get_table_name('csi_email_templates');
    
    return $wpdb->delete($table_name, ['id' => intval($template_id)]);
}

/**
 * Handle template form submission
 */
function csi_handle_template_form() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    if (isset($_POST['csi_save_template']) && check_admin_referer('csi_save_template_nonce')) {
        $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
        
        $data = [
            'id' => $template_id,
            'template_name' => $_POST['template_name'] ?? '',
            'from_name' => $_POST['from_name'] ?? '',
            'from_email' => $_POST['from_email'] ?? '',
            'subject' => $_POST['subject'] ?? '',
            'body_html' => $_POST['body_html'] ?? '',
            'placeholders_json' => $_POST['placeholders_json'] ?? ''
        ];
        
        $result = csi_save_email_template($data);
        
        if (is_wp_error($result)) {
            csi_notify_error($result->get_error_message());
        } else {
            csi_notify_success(__('Template saved successfully.', 'custom-signup-plugin'));
            wp_redirect(admin_url('admin.php?page=csi-email-templates'));
            exit;
        }
    }
}
add_action('admin_init', 'csi_handle_template_form');

/**
 * Handle template delete
 */
function csi_handle_template_delete() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    if (isset($_POST['csi_delete_template']) && check_admin_referer('csi_delete_template_nonce')) {
        $template_id = intval($_POST['template_id']);
        
        $result = csi_delete_email_template($template_id);
        
        if ($result) {
            csi_notify_success(__('Template deleted successfully.', 'custom-signup-plugin'));
        } else {
            csi_notify_error(__('Failed to delete template.', 'custom-signup-plugin'));
        }
        
        wp_redirect(admin_url('admin.php?page=csi-email-templates'));
        exit;
    }
}
add_action('admin_init', 'csi_handle_template_delete');

