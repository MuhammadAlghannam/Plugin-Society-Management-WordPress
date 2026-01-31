<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dashboard Admin Menu
 */

/**
 * Add dashboard main menu (first position)
 */
function csi_add_dashboard_menu() {
    add_menu_page(
        __('Society Management', 'custom-signup-plugin'),
        __('Society Management', 'custom-signup-plugin'),
        'manage_options',
        'csi-dashboard',
        'csi_render_dashboard_page',
        'dashicons-chart-area',
        30
    );
    
    // Add Dashboard as first submenu item
    add_submenu_page(
        'csi-dashboard',
        __('Dashboard', 'custom-signup-plugin'),
        __('Dashboard', 'custom-signup-plugin'),
        'manage_options',
        'csi-dashboard',
        'csi_render_dashboard_page'
    );
}
add_action('admin_menu', 'csi_add_dashboard_menu', 1);

/**
 * Enqueue dashboard assets
 */
function csi_enqueue_dashboard_assets($hook) {
    if ($hook !== 'toplevel_page_csi-dashboard') {
        return;
    }
    
    wp_enqueue_script(
        'csi-dashboard-script',
        CSI_PLUGIN_URL . 'admin/features/dashboard/assets/js/dashboard.js',
        ['jquery', 'csi-admin-global'],
        CSI_VERSION,
        true
    );
    
    wp_enqueue_script(
        'csi-dashboard-charts',
        CSI_PLUGIN_URL . 'admin/features/dashboard/assets/js/charts.js',
        ['jquery', 'csi-chartjs', 'csi-dashboard-script'],
        CSI_VERSION,
        true
    );
    
    wp_enqueue_style(
        'csi-dashboard-style',
        CSI_PLUGIN_URL . 'admin/features/dashboard/assets/css/admin.css',
        ['csi-admin-global'],
        CSI_VERSION
    );
    
    // Localize script with analytics data
    wp_localize_script('csi-dashboard-script', 'csiDashboard', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('csi_dashboard_nonce'),
        'stats' => csi_get_dashboard_stats()
    ]);
}
add_action('admin_enqueue_scripts', 'csi_enqueue_dashboard_assets');

/**
 * Render dashboard page
 */
function csi_render_dashboard_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    include CSI_PLUGIN_DIR . 'admin/features/dashboard/views/dashboard.php';
}
