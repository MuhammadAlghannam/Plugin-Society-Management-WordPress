<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Email Templates Admin Menu
 */

/**
 * Add email templates submenu
 */
function csi_add_email_templates_menu() {
    add_submenu_page(
        'csi-dashboard',
        __('Email Templates', 'custom-signup-plugin'),
        __('Email Templates', 'custom-signup-plugin'),
        'manage_options',
        'csi-email-templates',
        'csi_render_email_templates_page'
    );
}
add_action('admin_menu', 'csi_add_email_templates_menu', 15);

/**
 * Enqueue email templates assets
 */
function csi_enqueue_email_templates_assets($hook) {
    if ($hook !== 'custom-signup_page_csi-email-templates') {
        return;
    }
    
    wp_enqueue_script(
        'csi-email-templates-script',
        CSI_PLUGIN_URL . 'admin/features/emails-templates/assets/js/admin.js',
        ['jquery', 'csi-admin-global'],
        CSI_VERSION,
        true
    );
    
    wp_enqueue_style(
        'csi-email-templates-style',
        CSI_PLUGIN_URL . 'admin/features/emails-templates/assets/css/admin.css',
        ['csi-admin-global'],
        CSI_VERSION
    );
    
    wp_localize_script('csi-email-templates-script', 'csiEmailTemplates', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('csi_email_templates_nonce'),
        'i18n' => [
            'search' => __('Search:', 'custom-signup-plugin'),
            'showEntries' => __('Show _MENU_ entries', 'custom-signup-plugin'),
            'showing' => __('Showing _START_ to _END_ of _TOTAL_ entries', 'custom-signup-plugin'),
            'noEntries' => __('No entries found', 'custom-signup-plugin'),
            'filtered' => __('(filtered from _MAX_ total entries)', 'custom-signup-plugin'),
            'first' => __('First', 'custom-signup-plugin'),
            'last' => __('Last', 'custom-signup-plugin'),
            'next' => __('Next', 'custom-signup-plugin'),
            'previous' => __('Previous', 'custom-signup-plugin'),
            'emptyTable' => __('No templates found.', 'custom-signup-plugin')
        ]
    ]);
}
add_action('admin_enqueue_scripts', 'csi_enqueue_email_templates_assets');

/**
 * Render email templates page
 */
function csi_render_email_templates_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
    $template_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    switch ($action) {
        case 'add':
        case 'edit':
            include CSI_PLUGIN_DIR . 'admin/features/emails-templates/views/template-edit.php';
            break;
        default:
            include CSI_PLUGIN_DIR . 'admin/features/emails-templates/views/templates-list.php';
            break;
    }
}
