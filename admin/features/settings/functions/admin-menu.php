<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings Admin Menu
 */

/**
 * Add settings submenu
 */
function csi_add_settings_menu() {
    add_submenu_page(
        'csi-dashboard',
        __('Settings', 'custom-signup-plugin'),
        __('Settings', 'custom-signup-plugin'),
        'manage_options',
        'csi-settings',
        'csi_render_settings_page'
    );
}
add_action('admin_menu', 'csi_add_settings_menu', 20);

/**
 * Enqueue settings assets
 */
function csi_enqueue_settings_assets($hook) {
    if ($hook !== 'custom-signup_page_csi-settings') {
        return;
    }
    
    wp_enqueue_script(
        'csi-settings-script',
        CSI_PLUGIN_URL . 'admin/features/settings/assets/js/admin.js',
        ['jquery', 'csi-admin-global'],
        CSI_VERSION,
        true
    );
    
    wp_enqueue_style(
        'csi-settings-style',
        CSI_PLUGIN_URL . 'admin/features/settings/assets/css/admin.css',
        ['csi-admin-global'],
        CSI_VERSION
    );
    
    wp_localize_script('csi-settings-script', 'csiSettings', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('csi_settings_nonce')
    ]);
}
add_action('admin_enqueue_scripts', 'csi_enqueue_settings_assets');

/**
 * Render settings page
 */
function csi_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Handle form submission
    if (isset($_POST['csi_save_settings']) && check_admin_referer('csi_save_settings_nonce')) {
        csi_save_settings();
    }
    
    // Handle reminder template save
    if (isset($_POST['csi_save_reminder_template']) && check_admin_referer('csi_save_reminder_template_nonce')) {
        $reminder_template_id = isset($_POST['reminder_template_id']) ? intval($_POST['reminder_template_id']) : 0;
        update_option('csi_reminder_template_id', $reminder_template_id);
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Reminder email template saved successfully.', 'custom-signup-plugin') . '</p></div>';
    }
    
    // Handle renewal template save
    if (isset($_POST['csi_save_renewal_template']) && check_admin_referer('csi_save_renewal_template_nonce')) {
        $renewal_template_id = isset($_POST['renewal_template_id']) ? intval($_POST['renewal_template_id']) : 0;
        update_option('csi_renewal_template_id', $renewal_template_id);
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Renewal email template saved successfully.', 'custom-signup-plugin') . '</p></div>';
    }
    
    include CSI_PLUGIN_DIR . 'admin/features/settings/views/settings.php';
}

/**
 * Save settings
 */
function csi_save_settings() {
    $email = isset($_POST['csi_smtp_email']) ? sanitize_email($_POST['csi_smtp_email']) : '';
    $password = isset($_POST['csi_smtp_password']) ? sanitize_text_field($_POST['csi_smtp_password']) : '';
    
    update_option('csi_smtp_email', $email);
    
    // Only update password if provided (allow empty to keep existing)
    if (!empty($password)) {
        update_option('csi_smtp_password', $password);
    }
    
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully.', 'custom-signup-plugin') . '</p></div>';
}
