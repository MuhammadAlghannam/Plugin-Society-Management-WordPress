<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    return;
}

$template_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$template = $template_id > 0 ? csi_get_email_template($template_id) : null;
$is_edit = $template !== null;

$placeholders = [
    '{full_name}' => __('Full Name', 'custom-signup-plugin'),
    '{email}' => __('Email', 'custom-signup-plugin'),
    '{phone}' => __('Phone', 'custom-signup-plugin'),
    '{membership_number}' => __('Membership Number', 'custom-signup-plugin'),
    '{generated_id}' => __('Membership Number (deprecated)', 'custom-signup-plugin'),
    '{membership_type}' => __('Membership Type', 'custom-signup-plugin'),
    '{registration_type}' => __('Registration Type', 'custom-signup-plugin'),
    '{payment_status}' => __('Payment Status', 'custom-signup-plugin'),
    '{paid_date}' => __('Paid Date', 'custom-signup-plugin'),
    '{expiry_date}' => __('Expiry Date', 'custom-signup-plugin'),
    '{institute}' => __('Institute', 'custom-signup-plugin'),
    '{country}' => __('Country', 'custom-signup-plugin'),
    '{personal_photo_url}' => __('Personal Photo URL', 'custom-signup-plugin'),
    '{cv_url}' => __('CV URL', 'custom-signup-plugin'),
    '{id_scans_urls}' => __('ID Scans URLs', 'custom-signup-plugin'),
    '{student_card_url}' => __('Student Card URL', 'custom-signup-plugin'),
    '{payment_receipt_url}' => __('Payment Receipt URL', 'custom-signup-plugin'),
    '{files_table}' => __('Files Table (HTML)', 'custom-signup-plugin'),
    '{meta:KEY}' => __('Dynamic Meta (replace KEY with meta key)', 'custom-signup-plugin')
];
?>

<div class="wrap csi-admin-wrapper">
    <h1><?php echo $is_edit ? __('Edit Template', 'custom-signup-plugin') : __('Add New Template', 'custom-signup-plugin'); ?></h1>
    
    <form method="post" action="" id="template-form">
        <?php wp_nonce_field('csi_save_template_nonce'); ?>
        <input type="hidden" name="template_id" value="<?php echo esc_attr($template_id); ?>">
        
        <div class="card">
            <div class="card-header">
                <h3><?php _e('Template Information', 'custom-signup-plugin'); ?></h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="template_name" class="form-label"><?php _e('Template Name', 'custom-signup-plugin'); ?> *</label>
                    <input type="text" class="form-control" id="template_name" name="template_name" value="<?php echo esc_attr($template['template_name'] ?? ''); ?>" required>
                </div>
                
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="from_name" class="form-label"><?php _e('From Name', 'custom-signup-plugin'); ?></label>
                        <input type="text" class="form-control" id="from_name" name="from_name" value="<?php echo esc_attr($template['from_name'] ?? ''); ?>">
                        <small class="form-text text-muted"><?php _e('The sender name that will appear in emails.', 'custom-signup-plugin'); ?></small>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="subject" class="form-label"><?php _e('Subject', 'custom-signup-plugin'); ?> *</label>
                    <input type="text" class="form-control" id="subject" name="subject" value="<?php echo esc_attr($template['subject'] ?? ''); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="body_html" class="form-label"><?php _e('Body (HTML)', 'custom-signup-plugin'); ?> *</label>
                    <?php
                    $body_content = $template['body_html'] ?? '';
                    wp_editor($body_content, 'body_html', [
                        'textarea_name' => 'body_html',
                        'textarea_rows' => 15,
                        'media_buttons' => false,
                        'teeny' => false
                    ]);
                    ?>
                </div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h3><?php _e('Available Placeholders', 'custom-signup-plugin'); ?></h3>
            </div>
            <div class="card-body">
                <p><?php _e('Click on a placeholder to copy it to clipboard:', 'custom-signup-plugin'); ?></p>
                <div class="row" id="placeholders-list">
                    <?php foreach ($placeholders as $placeholder => $label): ?>
                        <div class="col-md-4 mb-2">
                            <code class="placeholder-item" data-placeholder="<?php echo esc_attr($placeholder); ?>" style="cursor: pointer; padding: 5px 10px; background: #f5f5f5; border-radius: 3px; display: inline-block;">
                                <?php echo esc_html($placeholder); ?>
                            </code>
                            <small class="text-muted"> - <?php echo esc_html($label); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <button type="submit" name="csi_save_template" class="button button-primary button-large">
                <?php _e('Save Template', 'custom-signup-plugin'); ?>
            </button>
            <a href="<?php echo esc_url(admin_url('admin.php?page=csi-email-templates')); ?>" class="button">
                <?php _e('Cancel', 'custom-signup-plugin'); ?>
            </a>
        </div>
    </form>
</div>

<style>
.csi-admin-wrapper,
.csi-admin-wrapper #template-form,
.csi-admin-wrapper .card {
    width: 100% !important;
    max-width: 100% !important;
}

.csi-admin-wrapper .card-header {
    font-weight: 600;
    padding: 12px 20px;
}

@media (max-width: 576px) {
    .csi-admin-wrapper {
        padding: 10px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Copy placeholder to clipboard
    $('.placeholder-item').on('click', function() {
        var placeholder = $(this).data('placeholder');
        var $temp = $('<input>');
        $('body').append($temp);
        $temp.val(placeholder).select();
        document.execCommand('copy');
        $temp.remove();
        
        if (typeof CSI !== 'undefined' && CSI.Swal) {
            CSI.Swal.success('Copied!', 'Placeholder copied to clipboard');
        }
    });
});
</script>
