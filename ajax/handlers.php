<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Centralized AJAX Handlers
 * Registers all AJAX actions for the plugin
 */

/**
 * Register AJAX handlers
 */
function csi_register_ajax_handlers() {
    // Registration form processing
    add_action('wp_ajax_csi_process_signup', 'csi_process_signup_form');
    add_action('wp_ajax_nopriv_csi_process_signup', 'csi_process_signup_form');
    
    // Email existence check
    add_action('wp_ajax_csi_check_email_exists', 'csi_check_email_exists');
    add_action('wp_ajax_nopriv_csi_check_email_exists', 'csi_check_email_exists');
    
    // Renewal submission
    add_action('wp_ajax_csi_submit_renewal', 'csi_handle_renewal_submission');
}

add_action('init', 'csi_register_ajax_handlers');

/**
 * Check if email exists
 */
function csi_check_email_exists() {
    // Check nonce - allow both 'security' and 'nonce' parameter names
    $nonce_param = isset($_POST['security']) ? 'security' : (isset($_POST['nonce']) ? 'nonce' : '');
    if ($nonce_param) {
        check_ajax_referer('csi_signup_nonce', $nonce_param);
    }
    
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    if (empty($email)) {
        wp_send_json_error(['message' => 'Email is required']);
    }
    
    if (!is_email($email)) {
        wp_send_json_error(['message' => 'Invalid email format']);
    }
    
    $exists = email_exists($email);
    wp_send_json_success(['exists' => (bool) $exists]);
}

/**
 * Process signup form
 * This function is implemented in shortcodes/registration/functions/form-processing.php
 * The AJAX actions are registered here but the function is defined in form-processing.php
 */
