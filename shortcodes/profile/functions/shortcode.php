<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Profile Shortcode
 * [profile_info]
 */
function csi_profile_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<div class="alert alert-warning">' . __('You need to be logged in to view this page.', 'custom-signup-plugin') . '</div>';
    }
    
    $user_id = get_current_user_id();
    ob_start();
    include CSI_PLUGIN_DIR . 'shortcodes/profile/views/profile-info.php';
    return ob_get_clean();
}
add_shortcode('profile_info', 'csi_profile_shortcode');
