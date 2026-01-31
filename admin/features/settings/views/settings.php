<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$email = get_option('csi_smtp_email', '');
$has_password = !empty(get_option('csi_smtp_password'));

$current_reminder_template_id = get_option('csi_reminder_template_id', 0);
$current_renewal_template_id = get_option('csi_renewal_template_id', 0);
$templates = function_exists('csi_get_email_templates') ? csi_get_email_templates() : [];

// Get current reminder template details
$current_reminder_template = null;
if ($current_reminder_template_id > 0) {
    foreach ($templates as $template) {
        if ($template['id'] == $current_reminder_template_id) {
            $current_reminder_template = $template;
            break;
        }
    }
}

// Get current renewal template details
$current_renewal_template = null;
if ($current_renewal_template_id > 0) {
    foreach ($templates as $template) {
        if ($template['id'] == $current_renewal_template_id) {
            $current_renewal_template = $template;
            break;
        }
    }
}
?>

<div class="wrap csi-admin-wrapper">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="card" style="max-width: 100%;">
        <div class="card-header">
            <h3><?php _e('Gmail SMTP Configuration', 'custom-signup-plugin'); ?></h3>
        </div>
        <div class="card-body">
            <p><?php _e('Configure your Gmail account to send emails securely via SMTP.', 'custom-signup-plugin'); ?></p>
            
            <?php if (!empty($email)): ?>
            <div class="alert alert-info d-flex align-items-center gap-2 mb-4" role="alert">
                <strong><?php _e('Current SMTP Email:', 'custom-signup-plugin'); ?></strong>
                <code><?php echo esc_html($email); ?></code>
                <?php if ($has_password): ?>
                    <span class="badge bg-success ms-2"><?php _e('Password Configured', 'custom-signup-plugin'); ?></span>
                <?php else: ?>
                    <span class="badge bg-warning ms-2"><?php _e('Password Not Set', 'custom-signup-plugin'); ?></span>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="alert alert-warning mb-4" role="alert">
                <strong><?php _e('No SMTP email configured yet.', 'custom-signup-plugin'); ?></strong>
                <?php _e('Please configure your Gmail SMTP settings below.', 'custom-signup-plugin'); ?>
            </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <?php wp_nonce_field('csi_save_settings_nonce'); ?>
                <div class="d-flex gap-2">
                    <div class="mb-3 flex-grow-1">
                        <label for="csi_smtp_email" class="form-label"><?php _e('Gmail Address', 'custom-signup-plugin'); ?></label>
                        <input type="email" class="form-control" id="csi_smtp_email" name="csi_smtp_email" value="<?php echo esc_attr($email); ?>" required placeholder="example@gmail.com" style="max-width: 500px;">
                        <small class="form-text text-muted"><?php _e('The email address that will be used to send system emails.', 'custom-signup-plugin'); ?></small>
                    </div>
                    
                    <div class="mb-3 flex-grow-1">
                        <label for="csi_smtp_password" class="form-label"><?php _e('App Password', 'custom-signup-plugin'); ?></label>
                        <input type="password" class="form-control" id="csi_smtp_password" name="csi_smtp_password" value="" placeholder="<?php echo $has_password ? __('(Password set - leave blank to keep unchanged)', 'custom-signup-plugin') : __('Enter your App Password', 'custom-signup-plugin'); ?>" style="max-width: 500px;">
                        <small class="form-text text-muted">
                            <?php _e('Use a Gmail App Password, not your regular login password.', 'custom-signup-plugin'); ?>
                            <a href="https://support.google.com/accounts/answer/185833" target="_blank"><?php _e('How to create an App Password', 'custom-signup-plugin'); ?></a>
                        </small>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" name="csi_save_settings" class="button button-primary button-large">
                        <?php _e('Save Settings', 'custom-signup-plugin'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Reminder Email Settings -->
    <div class="card mt-4" style="max-width: 100%;">
        <div class="card-header">
            <h3><?php _e('Reminder Email Settings', 'custom-signup-plugin'); ?></h3>
        </div>
        <div class="card-body">
            <p><?php _e('Configure the email template that will be sent automatically to users 11 months after their membership start date (12-month membership).', 'custom-signup-plugin'); ?></p>
            
            <?php if ($current_reminder_template): ?>
            <div class="alert alert-info d-flex align-items-center gap-2 mb-4" role="alert">
                <strong><?php _e('Current Reminder Template:', 'custom-signup-plugin'); ?></strong>
                <code><?php echo esc_html($current_reminder_template['template_name']); ?></code>
                <span class="badge bg-success ms-2"><?php _e('Active', 'custom-signup-plugin'); ?></span>
            </div>
            <?php else: ?>
            <div class="alert alert-warning mb-4" role="alert">
                <strong><?php _e('No reminder email template configured yet.', 'custom-signup-plugin'); ?></strong>
                <?php _e('Please select a template below to enable automatic reminder emails.', 'custom-signup-plugin'); ?>
            </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <?php wp_nonce_field('csi_save_reminder_template_nonce'); ?>
                <div class="mb-3">
                    <label for="reminder_template_id" class="form-label">
                        <strong><?php _e('Reminder Email Template', 'custom-signup-plugin'); ?></strong>
                    </label>
                    <select name="reminder_template_id" id="reminder_template_id" class="form-control" style="max-width: 500px;">
                        <option value="0"><?php _e('-- Select Template --', 'custom-signup-plugin'); ?></option>
                        <?php foreach ($templates as $template): ?>
                            <option value="<?php echo esc_attr($template['id']); ?>" <?php selected($current_reminder_template_id, $template['id']); ?>>
                                <?php echo esc_html($template['template_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted">
                        <?php _e('Select the email template to use for automatic membership renewal reminders.', 'custom-signup-plugin'); ?>
                    </small>
                </div>
                
                <div class="mt-4">
                    <button type="submit" name="csi_save_reminder_template" class="button button-primary button-large">
                        <?php _e('Save Reminder Template', 'custom-signup-plugin'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Renewal Email Settings -->
    <div class="card mt-4" style="max-width: 100%;">
        <div class="card-header">
            <h3><?php _e('Renewal Email Settings', 'custom-signup-plugin'); ?></h3>
        </div>
        <div class="card-body">
            <p><?php _e('Configure the email template that will be sent automatically to users after they renew their membership.', 'custom-signup-plugin'); ?></p>
            
            <?php if ($current_renewal_template): ?>
            <div class="alert alert-info d-flex align-items-center gap-2 mb-4" role="alert">
                <strong><?php _e('Current Renewal Template:', 'custom-signup-plugin'); ?></strong>
                <code><?php echo esc_html($current_renewal_template['template_name']); ?></code>
                <span class="badge bg-success ms-2"><?php _e('Active', 'custom-signup-plugin'); ?></span>
            </div>
            <?php else: ?>
            <div class="alert alert-warning mb-4" role="alert">
                <strong><?php _e('No renewal email template configured yet.', 'custom-signup-plugin'); ?></strong>
                <?php _e('Please select a template below to enable automatic renewal emails.', 'custom-signup-plugin'); ?>
            </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <?php wp_nonce_field('csi_save_renewal_template_nonce'); ?>
                <div class="mb-3">
                    <label for="renewal_template_id" class="form-label">
                        <strong><?php _e('Renewal Email Template', 'custom-signup-plugin'); ?></strong>
                    </label>
                    <select name="renewal_template_id" id="renewal_template_id" class="form-control" style="max-width: 500px;">
                        <option value="0"><?php _e('-- Select Template --', 'custom-signup-plugin'); ?></option>
                        <?php foreach ($templates as $template): ?>
                            <option value="<?php echo esc_attr($template['id']); ?>" <?php selected($current_renewal_template_id, $template['id']); ?>>
                                <?php echo esc_html($template['template_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted">
                        <?php _e('Select the email template to use for automatic membership renewal confirmations.', 'custom-signup-plugin'); ?>
                    </small>
                </div>
                
                <div class="mt-4">
                    <button type="submit" name="csi_save_renewal_template" class="button button-primary button-large">
                        <?php _e('Save Renewal Template', 'custom-signup-plugin'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
