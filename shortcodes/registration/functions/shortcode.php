<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registration Shortcode
 * [custom_signup]
 */
function csi_registration_shortcode($atts) {
    // If user is already logged in, show a message
    if (is_user_logged_in()) {
        return '<div class="alert alert-info">' . __('You are already logged in.', 'custom-signup-plugin') . ' <a href="' . wp_logout_url(get_permalink()) . '">' . __('Logout', 'custom-signup-plugin') . '</a></div>';
    }
    
    ob_start();
    include CSI_PLUGIN_DIR . 'shortcodes/registration/views/signup-form.php';
    return ob_get_clean();
}
add_shortcode('custom_signup', 'csi_registration_shortcode');
