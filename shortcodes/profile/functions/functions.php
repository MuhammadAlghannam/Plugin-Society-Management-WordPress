<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Profile Functions
 * Helper functions for profile shortcode
 */

/**
 * Enqueue profile assets
 */
function csi_enqueue_profile_assets() {
    global $post;
    
    if (!$post || !has_shortcode($post->post_content, 'profile_info')) {
        return;
    }
    
    wp_enqueue_style(
        'csi-profile-style',
        CSI_PLUGIN_URL . 'shortcodes/profile/assets/css/admin.css',
        [],
        CSI_VERSION
    );

    // Enqueue frontend SweetAlert2
    wp_enqueue_style(
        'csi-sweetalert2-frontend',
        'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
        [],
        '11.7.0'
    );
    
    wp_enqueue_script(
        'csi-sweetalert2-frontend',
        'https://cdn.jsdelivr.net/npm/sweetalert2@11',
        [],
        '11.7.0',
        true
    );
    
    wp_enqueue_script(
        'csi-renewal-form',
        CSI_PLUGIN_URL . 'shortcodes/profile/assets/js/renewal-form.js',
        ['jquery', 'csi-sweetalert2-frontend'],
        CSI_VERSION,
        true
    );

    wp_localize_script('csi-renewal-form', 'csiRenewal', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('csi_renewal_nonce')
    ]);
}
add_action('wp_enqueue_scripts', 'csi_enqueue_profile_assets');

/**
 * Handle certificate download
 * Must run on 'init' hook before any output
 */
function csi_handle_certificate_download() {
    if (!isset($_POST['download_certificate'])) {
        return;
    }
    
    // Verify nonce for security
    if (!isset($_POST['profile_nonce']) || !wp_verify_nonce($_POST['profile_nonce'], 'profile_update')) {
        wp_die(__('Security check failed', 'custom-signup-plugin'));
    }
    
    // Verify user is logged in
    if (!is_user_logged_in()) {
        wp_die(__('You must be logged in to download your certificate.', 'custom-signup-plugin'));
    }
    
    // Verify user can download their own certificate
    $user_id = intval($_POST['user_id']);
    if ($user_id !== get_current_user_id()) {
        wp_die(__('You do not have permission to download this certificate.', 'custom-signup-plugin'));
    }
    
    // Check user status and payment status
    $user_status = get_user_meta($user_id, 'user_status', true) ?: 'not_active';
    $payment_status = get_user_meta($user_id, 'payment_status', true) ?: 'pending';
    
    if ($user_status !== 'active' || $payment_status !== 'paid') {
        wp_die(__('Your account must be active and payment must be paid to download the certificate.', 'custom-signup-plugin'));
    }
    
    $fullname = sanitize_text_field($_POST['fullname']);
    
    // Load the PDF library
    if (!function_exists('csi_generate_certificate')) {
        require_once(CSI_PLUGIN_DIR . 'shortcodes/profile/functions/pdf-library.php');
    }
    
    // Generate the PDF with the user's name
    if (function_exists('csi_generate_certificate')) {
        csi_generate_certificate($fullname);
    } else {
        wp_die(__('Certificate generation function not found.', 'custom-signup-plugin'));
    }
    
    exit;
}
add_action('init', 'csi_handle_certificate_download');
