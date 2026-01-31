<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SMTP Configuration
 * Configures PHPMailer to use Gmail SMTP
 */

/**
 * Configure PHPMailer
 */
function csi_configure_smtp($phpmailer) {
    $email = get_option('csi_smtp_email');
    $password = get_option('csi_smtp_password');
    
    // Only configure if settings are present
    if (!empty($email) && !empty($password)) {
        $phpmailer->isSMTP();
        $phpmailer->Host = 'smtp.gmail.com';
        $phpmailer->SMTPAuth = true;
        $phpmailer->Port = 587;
        $phpmailer->Username = $email;
        $phpmailer->Password = $password;
        $phpmailer->SMTPSecure = 'tls';
        
        // Set From address to the SMTP email to ensure deliverability
        $phpmailer->From = $email;
        
        // If FromName is not set or default WordPress, use site name
        if (empty($phpmailer->FromName) || $phpmailer->FromName === 'WordPress') {
            $phpmailer->FromName = get_bloginfo('name');
        }

        // Enable verbose debug output if we're debugging
        // $phpmailer->SMTPDebug = 2; // Enable this only for testing!
    }
}
add_action('phpmailer_init', 'csi_configure_smtp');

/**
 * Capture SMTP Errors
 */
add_action('wp_mail_failed', function($wp_error) {
    // Log error to error_log for debugging
    error_log('CSI SMTP Error: ' . $wp_error->get_error_message());
});
