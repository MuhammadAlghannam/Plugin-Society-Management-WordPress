<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registration Functions
 * Helper functions for registration shortcode
 */

/**
 * Enqueue registration form assets
 */
function csi_enqueue_registration_assets() {
    global $post;
    
    if (!$post || !has_shortcode($post->post_content, 'custom_signup')) {
        return;
    }
    
    wp_enqueue_style(
        'csi-registration-style',
        CSI_PLUGIN_URL . 'shortcodes/registration/assets/css/admin.css',
        [],
        CSI_VERSION
    );
    
    // Enqueue SweetAlert2 for frontend
    wp_enqueue_style(
        'csi-sweetalert2-frontend',
        'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
        [],
        '11.7.0'
    );
    
    wp_enqueue_script(
        'csi-sweetalert2-frontend',
        'https://cdn.jsdelivr.net/npm/sweetalert2@11',
        ['jquery'],
        '11.7.0',
        true
    );
    
    wp_enqueue_script(
        'csi-registration-script',
        CSI_PLUGIN_URL . 'shortcodes/registration/assets/js/script.js',
        ['jquery', 'csi-sweetalert2-frontend'],
        CSI_VERSION,
        true
    );
    
    wp_localize_script('csi-registration-script', 'csiAjax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'security' => wp_create_nonce('csi_signup_nonce')
    ]);
}
add_action('wp_enqueue_scripts', 'csi_enqueue_registration_assets');
