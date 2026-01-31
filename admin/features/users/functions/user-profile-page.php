<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * User Profile Admin Page
 * Creates a separate admin page for viewing/editing user profiles
 */

/**
 * Add user profile submenu
 */
function csi_add_user_profile_menu() {
    add_submenu_page(
        null, // Hidden from menu
        __('User Profile', 'custom-signup-plugin'),
        __('User Profile', 'custom-signup-plugin'),
        'manage_options',
        'csi-user-profile',
        'csi_render_user_profile_page'
    );
}
add_action('admin_menu', 'csi_add_user_profile_menu', 10);

/**
 * Enqueue user profile page assets
 */
function csi_enqueue_user_profile_assets($hook) {
    if ($hook !== 'custom-signup_page_csi-user-profile') {
        return;
    }
    
    wp_enqueue_script(
        'csi-user-profile-page',
        CSI_PLUGIN_URL . 'admin/features/users/assets/js/user-profile.js',
        ['jquery', 'csi-admin-global'],
        CSI_VERSION,
        true
    );
    
    wp_enqueue_style(
        'csi-user-profile-style',
        CSI_PLUGIN_URL . 'admin/features/users/assets/css/admin.css',
        ['csi-admin-global'],
        CSI_VERSION
    );
    
    // Localize script
    wp_localize_script('csi-user-profile-page', 'csiUserProfile', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('csi_global_nonce'),
        'usersListUrl' => admin_url('admin.php?page=membership-applications')
    ]);
}
add_action('admin_enqueue_scripts', 'csi_enqueue_user_profile_assets');

/**
 * Render user profile page
 */
function csi_render_user_profile_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    
    if (!$user_id) {
        wp_die(__('Invalid user ID', 'custom-signup-plugin'));
    }
    
    $user = get_user_by('ID', $user_id);
    if (!$user) {
        wp_die(__('User not found', 'custom-signup-plugin'));
    }
    
    include CSI_PLUGIN_DIR . 'admin/features/users/views/user-profile-page.php';
}
