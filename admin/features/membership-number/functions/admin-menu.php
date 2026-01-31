<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Membership Number Admin Menu
 */

/**
 * Add membership number submenu
 */
function csi_add_generated_id_menu() {
    add_submenu_page(
        'csi-dashboard',
        __('Membership Number Settings', 'custom-signup-plugin'),
        __('Membership Number Settings', 'custom-signup-plugin'),
        'manage_options',
        'csi-membership-number-settings',
        'csi_render_generated_id_page'
    );
}
add_action('admin_menu', 'csi_add_generated_id_menu', 10);

/**
 * Enqueue membership number assets
 */
function csi_enqueue_generated_id_assets($hook) {
    // Check both possible hook names
    if ($hook !== 'csi-dashboard_page_csi-membership-number-settings' && 
        $hook !== 'custom-signup_page_csi-membership-number-settings') {
        // Also check by page parameter as fallback
        if (!isset($_GET['page']) || $_GET['page'] !== 'csi-membership-number-settings') {
            return;
        }
    }
    
    // Ensure Bootstrap is loaded (from global assets)
    wp_enqueue_style('csi-bootstrap');
    wp_enqueue_script('csi-bootstrap');
    
    wp_enqueue_style(
        'csi-membership-number-style',
        CSI_PLUGIN_URL . 'admin/features/membership-number/assets/css/admin.css',
        ['csi-bootstrap', 'csi-admin-global'],
        CSI_VERSION
    );
    
    wp_enqueue_script(
        'csi-membership-number-script',
        CSI_PLUGIN_URL . 'admin/features/membership-number/assets/js/admin.js',
        ['jquery', 'csi-bootstrap', 'csi-admin-global'],
        CSI_VERSION,
        true
    );
    
    wp_localize_script('csi-membership-number-script', 'csiMembershipNumber', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('csi_membership_number_nonce')
    ]);
}
add_action('admin_enqueue_scripts', 'csi_enqueue_generated_id_assets');

/**
 * Handle abbreviation CRUD operations (like old code)
 */
function csi_handle_abbreviation_crud() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Handle CREATE/UPDATE
    if (isset($_POST['csi_save_abbreviation']) && check_admin_referer('csi_abbreviation_nonce')) {
        $membership_type = !empty($_POST['edit_membership']) ? sanitize_text_field($_POST['edit_membership']) : 
                          (!empty($_POST['membership_type']) ? sanitize_text_field($_POST['membership_type']) : '');
        $abbreviation = isset($_POST['abbreviation']) ? sanitize_text_field($_POST['abbreviation']) : '';
        
        if ($membership_type && $abbreviation) {
            csi_save_abbreviation($membership_type, $abbreviation);
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Abbreviation saved successfully.', 'custom-signup-plugin') . '</p></div>';
            });
        }
    }

    // Handle DELETE
    if (isset($_POST['csi_delete_abbreviation']) && check_admin_referer('csi_delete_abbreviation_nonce')) {
        $membership_type = isset($_POST['membership_type']) ? sanitize_text_field($_POST['membership_type']) : '';
        
        if (!empty($membership_type)) {
            csi_delete_abbreviation($membership_type);
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Abbreviation deleted successfully.', 'custom-signup-plugin') . '</p></div>';
            });
        }
    }

    // Handle Generate All
    if (isset($_POST['csi_generate_all']) && check_admin_referer('csi_generate_all_nonce')) {
        $membership_type = isset($_POST['membership_type']) ? sanitize_text_field($_POST['membership_type']) : '';
        
        if (!empty($membership_type)) {
            $count = csi_generate_all_for_membership($membership_type);
            add_action('admin_notices', function() use ($count) {
                echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(__('Generated IDs for %d users.', 'custom-signup-plugin'), $count) . '</p></div>';
            });
        }
    }

    // Handle Generate for New Users
    if (isset($_POST['csi_generate_new']) && check_admin_referer('csi_generate_new_nonce')) {
        $membership_type = isset($_POST['membership_type']) ? sanitize_text_field($_POST['membership_type']) : '';
        
        if (!empty($membership_type)) {
            $count = csi_generate_for_new_users($membership_type);
            add_action('admin_notices', function() use ($count) {
                echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(__('Generated IDs for %d new users.', 'custom-signup-plugin'), $count) . '</p></div>';
            });
        }
    }
}
add_action('admin_init', 'csi_handle_abbreviation_crud');

/**
 * Render membership number page
 */
function csi_render_generated_id_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    include CSI_PLUGIN_DIR . 'admin/features/membership-number/views/settings.php';
}
