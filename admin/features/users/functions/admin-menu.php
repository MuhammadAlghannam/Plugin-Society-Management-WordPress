<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Users Management Admin Menu
 */

/**
 * Add users submenu under dashboard
 */
function csi_add_users_admin_menu() {
    add_submenu_page(
        'csi-dashboard',
        __('Users', 'custom-signup-plugin'),
        __('Users', 'custom-signup-plugin'),
        'manage_options',
        'membership-applications',
        'csi_render_users_page'
    );
}
add_action('admin_menu', 'csi_add_users_admin_menu', 5);

/**
 * Enqueue users page assets
 */
function csi_enqueue_users_assets($hook) {
    // Check both possible hook names (submenu under csi-dashboard)
    if ($hook !== 'csi-dashboard_page_membership-applications' && $hook !== 'custom-signup_page_membership-applications') {
        // Also check by page parameter as fallback
        if (!isset($_GET['page']) || $_GET['page'] !== 'membership-applications') {
            return;
        }
    }
    
    wp_enqueue_script(
        'csi-users-script',
        CSI_PLUGIN_URL . 'admin/features/users/assets/js/users-list.js',
        ['jquery', 'csi-admin-global'],
        CSI_VERSION . '.1',
        true
    );
    
    wp_enqueue_script(
        'csi-bulk-email',
        CSI_PLUGIN_URL . 'admin/features/users/assets/js/bulk-email.js',
        ['jquery', 'csi-admin-global'],
        CSI_VERSION . '.1',
        true
    );
    
    wp_enqueue_style(
        'csi-users-style',
        CSI_PLUGIN_URL . 'admin/features/users/assets/css/admin.css',
        ['csi-admin-global'],
        CSI_VERSION
    );
    
    // Localize script
    wp_localize_script('csi-users-script', 'csiUsers', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('csi_users_nonce'),
        'adminUrl' => admin_url('admin.php?page=csi-user-profile')
    ]);
}
add_action('admin_enqueue_scripts', 'csi_enqueue_users_assets');

/**
 * Render users page
 */
function csi_render_users_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    include CSI_PLUGIN_DIR . 'admin/features/users/views/users-list.php';
}
