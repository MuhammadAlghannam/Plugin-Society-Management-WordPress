<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    return;
}

// Get all templates (DataTables will handle pagination and search client-side)
$templates = csi_get_email_templates();
?>

<div class="wrap csi-admin-wrapper">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <!-- Actions -->
    <div class="tablenav top mb-3">
        <div class="alignleft">
            <!-- Space for future filters if needed -->
        </div>
        <div class="alignright">
            <a href="<?php echo esc_url(admin_url('admin.php?page=csi-email-templates&action=add')); ?>" class="button button-primary">
                <?php _e('Add New Template', 'custom-signup-plugin'); ?>
            </a>
        </div>
        <br class="clear">
    </div>
    
    <!-- Templates Table -->
    <table class="table table-striped table-hover table-bordered" id="templates-table" style="width:100%">
        <thead>
            <tr>
                <th><?php _e('Template Name', 'custom-signup-plugin'); ?></th>
                <th><?php _e('Subject', 'custom-signup-plugin'); ?></th>
                <th><?php _e('From Email', 'custom-signup-plugin'); ?></th>
                <th><?php _e('Updated At', 'custom-signup-plugin'); ?></th>
                <th><?php _e('Actions', 'custom-signup-plugin'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($templates)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 20px;">
                        <?php _e('No templates found.', 'custom-signup-plugin'); ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($templates as $template): ?>
                    <tr>
                        <td><strong><?php echo esc_html($template['template_name'] ?? ''); ?></strong></td>
                        <td><?php echo esc_html($template['subject'] ?? ''); ?></td>
                        <td><?php echo esc_html($template['from_email'] ?? get_option('admin_email')); ?></td>
                        <td><?php 
                            $updated_at = $template['updated_at'] ?? '';
                            if (!empty($updated_at)) {
                                $timestamp = strtotime($updated_at);
                                if ($timestamp !== false) {
                                    echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $timestamp));
                                } else {
                                    echo esc_html($updated_at);
                                }
                            } else {
                                echo '—';
                            }
                        ?></td>
                        <td class="csi-actions-cell">
                            <div class="csi-actions-wrapper d-flex gap-2">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=csi-email-templates&action=edit&id=' . $template['id'])); ?>" class="csi-action-icon" title="<?php _e('Edit Template', 'custom-signup-plugin'); ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                </a>
                                <form method="post" action="" class="csi-action-form" onsubmit="return confirm('<?php _e('Are you sure you want to delete this template?', 'custom-signup-plugin'); ?>');">
                                    <?php wp_nonce_field('csi_delete_template_nonce'); ?>
                                    <input type="hidden" name="template_id" value="<?php echo esc_attr($template['id']); ?>">
                                    <button type="submit" name="csi_delete_template" class="csi-action-icon" title="<?php _e('Delete Template', 'custom-signup-plugin'); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

