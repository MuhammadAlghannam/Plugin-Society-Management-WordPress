<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    return;
}

// Handle CSV export
if (isset($_POST['export_users']) && check_admin_referer('export_users_nonce')) {
    csi_export_users_to_csv();
    return;
}

// Get search and filter parameters
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$filter = isset($_GET['filter']) ? sanitize_text_field($_GET['filter']) : '';
$payment_status_filter = isset($_GET['payment_status']) ? sanitize_text_field($_GET['payment_status']) : '';
$user_status_filter = isset($_GET['user_status']) ? sanitize_text_field($_GET['user_status']) : '';
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 15;

// Get paginated users (backend pagination)
$query_result = csi_get_admin_users([
    'search' => $search,
    'filter' => $filter,
    'payment_status_filter' => $payment_status_filter,
    'user_status_filter' => $user_status_filter,
    'page' => $current_page,
    'per_page' => $per_page,
    'orderby' => 'registered',
    'order' => 'DESC'
]);

$users = $query_result['users'];
$total_users = $query_result['total'];
$total_pages = $query_result['total_pages'];

// Get all email templates for dropdown
$all_templates = [];
if (function_exists('csi_get_email_templates')) {
    $all_templates = csi_get_email_templates();
}
?>

<div class="wrap csi-admin-wrapper">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <!-- Shortcode Info and Actions -->
    <div class="tablenav top mb-3">
        <div class="alignleft">
            <p>
                <strong><?php _e('Shortcode:', 'custom-signup-plugin'); ?></strong>
                <?php _e('Use', 'custom-signup-plugin'); ?> <code>[custom_signup]</code> <?php _e('to display the registration form.', 'custom-signup-plugin'); ?>
            </p>
            <p>
                <strong><?php _e('Shortcode:', 'custom-signup-plugin'); ?></strong>
                <?php _e('Use', 'custom-signup-plugin'); ?> <code>[profile_info]</code> <?php _e('to display the profile.', 'custom-signup-plugin'); ?>
            </p>
        </div>
        <div class="alignright">
            <button type="button" class="button" id="csi-import-users-btn"><?php _e('Import Users', 'custom-signup-plugin'); ?></button>
            <form method="post" action="" style="margin-bottom: 0px;">
                <?php wp_nonce_field('export_users_nonce'); ?>
                <input type="submit" name="export_users" class="button button-primary" value="<?php _e('Export to CSV', 'custom-signup-plugin'); ?>">
            </form>
        </div>
        <br class="clear">
    </div>
    
    <!-- Bulk Email Actions -->
    <form method="post" action="" id="bulk-actions-form">
        <?php wp_nonce_field('csi_bulk_actions_nonce', 'csi_bulk_actions_nonce'); ?>
        <input type="hidden" name="action" value="send_email">
        <div class="tablenav top mb-3">
            <div class="alignleft actions bulkactions" style="display: flex; align-items: center; gap: 5px; flex-wrap: wrap;">
                <label for="email-template-selector" class="screen-reader-text"><?php _e('Select email template', 'custom-signup-plugin'); ?></label>
                <select name="email_template" id="email-template-selector" style="min-width: 200px;">
                    <option value=""><?php _e('Select Email Template', 'custom-signup-plugin'); ?></option>
                    <?php foreach ($all_templates as $template): ?>
                        <option value="<?php echo esc_attr($template['id']); ?>"><?php echo esc_html($template['template_name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" id="doaction" class="button button-primary action" value="<?php _e('Send', 'custom-signup-plugin'); ?>" style="display: none;">
            </div>
            <br class="clear">
        </div>
    </form>
    
    <?php
    // Handle bulk email action
    if (isset($_POST['action']) && $_POST['action'] === 'send_email' && check_admin_referer('csi_bulk_actions_nonce', 'csi_bulk_actions_nonce')) {
        $template_id = isset($_POST['email_template']) ? intval($_POST['email_template']) : 0;
        // Handle both array and non-array formats
        $user_ids = [];
        if (isset($_POST['user_ids'])) {
            if (is_array($_POST['user_ids'])) {
                $user_ids = array_map('intval', $_POST['user_ids']);
            } else {
                $user_ids = [intval($_POST['user_ids'])];
            }
        }
        
        if ($template_id > 0 && !empty($user_ids)) {
            require_once CSI_PLUGIN_DIR . 'admin/features/emails-templates/functions/email-sender.php';
            $results = csi_send_bulk_emails($template_id, $user_ids);
            
            if ($results['success'] > 0 || $results['failed'] > 0) {
                $message = sprintf(
                    __('Emails sent: %d successful, %d failed.', 'custom-signup-plugin'),
                    $results['success'],
                    $results['failed']
                );
                $notice_class = ($results['failed'] > 0) ? 'notice-warning' : 'notice-success';
                echo '<div class="notice ' . esc_attr($notice_class) . ' is-dismissible"><p>' . esc_html($message) . '</p></div>';
            }
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . __('Please select a template and at least one user.', 'custom-signup-plugin') . '</p></div>';
        }
    }
    ?>
    
    <!-- Search and Filter -->
    <div class="tablenav top mb-3">
        <div class="alignleft actions" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" id="csi-search-form" style="display: flex; align-items: center; gap: 5px; margin-bottom: 0px;">
                <input type="hidden" name="page" value="membership-applications">
                <?php if (!empty($filter)): ?>
                    <input type="hidden" name="filter" value="<?php echo esc_attr($filter); ?>">
                <?php endif; ?>
                <label class="screen-reader-text" for="csi-user-search"><?php _e('Search Users:', 'custom-signup-plugin'); ?></label>
                <input type="search" id="csi-user-search" name="search" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Search by name, email, phone, membership number...', 'custom-signup-plugin'); ?>" style="min-width: 250px;">
                <input type="submit" id="search-submit" class="button" value="<?php _e('Search Users', 'custom-signup-plugin'); ?>">
            </form>
            
            <!-- Combined Filters Form -->
            <?php
            $abbreviations = [];
            if (function_exists('csi_get_all_abbreviations')) {
                $abbreviations = csi_get_all_abbreviations();
            }
            $membership_types = [
                'student' => __('Student membership', 'custom-signup-plugin'),
                'early_investigator' => __('Early Investigator membership', 'custom-signup-plugin'),
                'postdoctoral' => __('Postdoctoral membership', 'custom-signup-plugin'),
                'scientist' => __('Scientist membership', 'custom-signup-plugin'),
                'industry' => __('Industry members', 'custom-signup-plugin'),
                'honorary' => __('Honorary membership', 'custom-signup-plugin')
            ];
            ?>
            <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" id="csi-filters-form" style="display: flex; align-items: center; gap: 5px; margin-bottom: 0px; flex-wrap: wrap;">
                <input type="hidden" name="page" value="membership-applications">
                <?php if (!empty($search)): ?>
                    <input type="hidden" name="search" value="<?php echo esc_attr($search); ?>">
                <?php endif; ?>
                
                <label for="csi-filter-abbreviation" class="screen-reader-text"><?php _e('Filter by Membership Type', 'custom-signup-plugin'); ?></label>
                <select name="filter" id="csi-filter-abbreviation" style="min-width: 200px;">
                    <option value=""><?php _e('All Membership Types', 'custom-signup-plugin'); ?></option>
                    <?php foreach ($abbreviations as $abbr): 
                        $membership_type = $abbr['membership_type'];
                        $abbreviation = $abbr['abbreviation'];
                        $label = isset($membership_types[$membership_type]) ? $membership_types[$membership_type] : $membership_type;
                        $display = $label . ' (' . strtoupper($abbreviation) . ')';
                    ?>
                        <option value="<?php echo esc_attr($membership_type); ?>" <?php selected($filter, $membership_type); ?>>
                            <?php echo esc_html($display); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <label for="csi-filter-payment-status" class="screen-reader-text"><?php _e('Filter by Payment Status', 'custom-signup-plugin'); ?></label>
                <select name="payment_status" id="csi-filter-payment-status" style="min-width: 150px;">
                    <option value=""><?php _e('All Payment Status', 'custom-signup-plugin'); ?></option>
                    <option value="pending" <?php selected($payment_status_filter, 'pending'); ?>><?php _e('Pending', 'custom-signup-plugin'); ?></option>
                    <option value="inreview" <?php selected($payment_status_filter, 'inreview'); ?>><?php _e('In Review', 'custom-signup-plugin'); ?></option>
                    <option value="paid" <?php selected($payment_status_filter, 'paid'); ?>><?php _e('Paid', 'custom-signup-plugin'); ?></option>
                    <option value="failed" <?php selected($payment_status_filter, 'failed'); ?>><?php _e('Failed', 'custom-signup-plugin'); ?></option>
                    <option value="declined" <?php selected($payment_status_filter, 'declined'); ?>><?php _e('Declined', 'custom-signup-plugin'); ?></option>
                </select>
                
                <label for="csi-filter-user-status" class="screen-reader-text"><?php _e('Filter by User Status', 'custom-signup-plugin'); ?></label>
                <select name="user_status" id="csi-filter-user-status" style="min-width: 150px;">
                    <option value=""><?php _e('All Status', 'custom-signup-plugin'); ?></option>
                    <option value="active" <?php selected($user_status_filter, 'active'); ?>><?php _e('Active', 'custom-signup-plugin'); ?></option>
                    <option value="not_active" <?php selected($user_status_filter, 'not_active'); ?>><?php _e('Not Active', 'custom-signup-plugin'); ?></option>
                </select>
                
                <input type="submit" class="button" value="<?php _e('Filter', 'custom-signup-plugin'); ?>">
            </form>
            
            <?php if (!empty($search) || !empty($filter) || !empty($payment_status_filter) || !empty($user_status_filter)): ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=membership-applications')); ?>" class="button"><?php _e('Clear', 'custom-signup-plugin'); ?></a>
            <?php endif; ?>
        </div>
        <br class="clear">
    </div>
    
    <!-- Selected Users Info -->
    <div id="csi-selected-info" class="csi-selected-info" style="display: none; margin-bottom: 15px; padding: 10px; background: #f0f0f1; border-left: 4px solid #2271b1; border-radius: 4px;">
        <span id="csi-selected-count" style="font-weight: 600; color: #2271b1;"></span>
        <button type="button" id="csi-clear-selection" class="button button-small" style="margin-left: 10px;">
            <?php _e('Clear Selection', 'custom-signup-plugin'); ?>
        </button>
    </div>
    
    <!-- Users Table -->
    <table class="table table-striped table-hover table-bordered" id="users-table" style="width:100%">
        <thead>
            <tr>
                <th style="width: 30px;">
                    <input type="checkbox" id="select-all-users" title="<?php _e('Select All', 'custom-signup-plugin'); ?>">
                </th>
                <th><?php _e('Membership Number', 'custom-signup-plugin'); ?></th>
                <th><?php _e('Full Name', 'custom-signup-plugin'); ?></th>
                <th><?php _e('Title', 'custom-signup-plugin'); ?></th>
                <th><?php _e('Specialty', 'custom-signup-plugin'); ?></th>
                <th><?php _e('Email', 'custom-signup-plugin'); ?></th>
                <th><?php _e('Phone', 'custom-signup-plugin'); ?></th>
                <th><?php _e('Membership', 'custom-signup-plugin'); ?></th>
                <th><?php _e('Payment Status', 'custom-signup-plugin'); ?></th>
                <th><?php _e('Status', 'custom-signup-plugin'); ?></th>
                <th><?php _e('Actions', 'custom-signup-plugin'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="11" style="text-align: center; padding: 20px;">
                        <?php if (!empty($search)): ?>
                            <strong><?php _e('No users found', 'custom-signup-plugin'); ?></strong> <?php _e('for', 'custom-signup-plugin'); ?> "<strong><?php echo esc_html($search); ?></strong>"
                        <?php else: ?>
                            <strong><?php _e('No users found', 'custom-signup-plugin'); ?></strong>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($users as $user):
                    $fullname = get_user_meta($user->ID, 'fullname', true) ?: '';
                    $title = get_user_meta($user->ID, 'title', true) ?: '';
                    $specialty = get_user_meta($user->ID, 'specialty', true) ?: '';
                    $phone = get_user_meta($user->ID, 'phone', true) ?: '';
                    $membership_raw = get_user_meta($user->ID, 'membership', true) ?: '';
                    $payment_status = get_user_meta($user->ID, 'payment_status', true) ?: 'pending';
                    $user_status = get_user_meta($user->ID, 'user_status', true) ?: 'not_active';
                    
                    // Get membership number, auto-generate if missing
                    if (function_exists('csi_get_user_membership_number')) {
                        $membership_number = csi_get_user_membership_number($user->ID);
                    } else {
                        $membership_number = get_user_meta($user->ID, 'membership_number', true) ?: $user->ID;
                    }
                    ?>
                    <tr class="csi-clickable-row" data-user-id="<?php echo esc_attr($user->ID); ?>">
                        <td>
                            <input type="checkbox" class="user-checkbox" value="<?php echo esc_attr($user->ID); ?>" data-user-id="<?php echo esc_attr($user->ID); ?>">
                        </td>
                        <td><strong><?php echo esc_html($membership_number); ?></strong></td>
                        <td><?php echo esc_html($fullname); ?></td>
                        <td><?php echo esc_html($title); ?></td>
                        <td><?php echo esc_html($specialty); ?></td>
                        <td><?php echo esc_html($user->user_email); ?></td>
                        <td><?php echo esc_html($phone); ?></td>
                        <td><?php echo esc_html(csi_get_membership_type_label($membership_raw)); ?></td>
                        <td>
                            <span class="payment-status status-<?php echo esc_attr($payment_status); ?>">
                                <?php echo esc_html(ucfirst($payment_status)); ?>
                            </span>
                        </td>
                        <td>
                            <span class="user-status status-<?php echo esc_attr($user_status); ?>">
                                <?php echo esc_html(ucfirst(str_replace('_', ' ', $user_status ?: ''))); ?>
                            </span>
                        </td>
                        <td class="csi-actions-cell">
                            <div class="csi-actions-wrapper">
                                <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $user->ID)); ?>" class="csi-action-icon" title="<?php _e('Edit User', 'custom-signup-plugin'); ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                </a>
                                
                            <button type="button" class="csi-action-icon csi-delete-icon" data-user-id="<?php echo esc_attr($user->ID); ?>" data-user-name="<?php echo esc_attr($fullname); ?>" data-delete-nonce="<?php echo esc_attr(wp_create_nonce('csi_delete_user_' . $user->ID)); ?>" title="<?php _e('Delete User', 'custom-signup-plugin'); ?>">
                                <span class="dashicons dashicons-trash"></span>
                            </button>

                                <?php
                                if ($user_status === 'active') {
                                    ?>
                                    <form method="post" action="" class="csi-action-form">
                                        <?php wp_nonce_field('csi_deactivate_user_' . $user->ID, 'csi_user_action_nonce'); ?>
                                        <input type="hidden" name="csi_user_id" value="<?php echo esc_attr($user->ID); ?>">
                                        <input type="hidden" name="csi_action" value="deactivate">
                                        <button type="submit" name="csi_user_action" class="button button-small" title="<?php _e('Deactivate User', 'custom-signup-plugin'); ?>">
                                            <?php _e('Deactivate', 'custom-signup-plugin'); ?>
                                        </button>
                                    </form>
                                    <?php
                                } else {
                                    ?>
                                    <form method="post" action="" class="csi-action-form">
                                        <?php wp_nonce_field('csi_activate_user_' . $user->ID, 'csi_user_action_nonce'); ?>
                                        <input type="hidden" name="csi_user_id" value="<?php echo esc_attr($user->ID); ?>">
                                        <input type="hidden" name="csi_action" value="activate">
                                        <button type="submit" name="csi_user_action" class="button button-small" title="<?php _e('Activate User', 'custom-signup-plugin'); ?>">
                                            <?php _e('Activate', 'custom-signup-plugin'); ?>
                                        </button>
                                    </form>
                                    <?php
                                }
                                ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="mt-4 d-flex justify-content-between align-items-center w-100">
                <span class="displaying-num">
                    <?php 
                    $start = (($current_page - 1) * $per_page) + 1;
                    $end = min($current_page * $per_page, $total_users);
                    printf(
                        _n('%s item', '%s items', $total_users, 'custom-signup-plugin'),
                        number_format_i18n($total_users)
                    );
                    ?>
                </span>
                <span class="pagination-links">
                    <?php
                    // Build base URL with all filters
                    $base_url = admin_url('admin.php');
                    $query_params = ['page' => 'membership-applications'];
                    if (!empty($search)) $query_params['search'] = $search;
                    if (!empty($filter)) $query_params['filter'] = $filter;
                    if (!empty($payment_status_filter)) $query_params['payment_status'] = $payment_status_filter;
                    if (!empty($user_status_filter)) $query_params['user_status'] = $user_status_filter;
                    
                    // First page
                    if ($current_page > 1) {
                        $first_url = add_query_arg(array_merge($query_params, ['paged' => 1]), $base_url);
                        echo '<a class="first-page button" href="' . esc_url($first_url) . '"><span class="screen-reader-text">' . __('First page', 'custom-signup-plugin') . '</span><span aria-hidden="true">«</span></a>';
                    } else {
                        echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>';
                    }
                    
                    // Previous page
                    if ($current_page > 1) {
                        $prev_url = add_query_arg(array_merge($query_params, ['paged' => $current_page - 1]), $base_url);
                        echo '<a class="prev-page button" href="' . esc_url($prev_url) . '"><span class="screen-reader-text">' . __('Previous page', 'custom-signup-plugin') . '</span><span aria-hidden="true">‹</span></a>';
                    } else {
                        echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>';
                    }
                    
                    // Current page info
                    echo '<span class="paging-input">';
                    echo '<span class="tablenav-paging-text">';
                    printf(
                        __('%1$s of %2$s', 'custom-signup-plugin'),
                        '<span class="current-page">' . number_format_i18n($current_page) . '</span>',
                        '<span class="total-pages">' . number_format_i18n($total_pages) . '</span>'
                    );
                    echo '</span>';
                    echo '</span>';
                    
                    // Next page
                    if ($current_page < $total_pages) {
                        $next_url = add_query_arg(array_merge($query_params, ['paged' => $current_page + 1]), $base_url);
                        echo '<a class="next-page button" href="' . esc_url($next_url) . '"><span class="screen-reader-text">' . __('Next page', 'custom-signup-plugin') . '</span><span aria-hidden="true">›</span></a>';
                    } else {
                        echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>';
                    }
                    
                    // Last page
                    if ($current_page < $total_pages) {
                        $last_url = add_query_arg(array_merge($query_params, ['paged' => $total_pages]), $base_url);
                        echo '<a class="last-page button" href="' . esc_url($last_url) . '"><span class="screen-reader-text">' . __('Last page', 'custom-signup-plugin') . '</span><span aria-hidden="true">»</span></a>';
                    } else {
                        echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>';
                    }
                    ?>
                </span>
        </div>
    <?php endif; ?>
</div>

<!-- Import Users Modal -->
<div class="modal fade" id="csi-import-modal" tabindex="-1" aria-labelledby="csi-import-modal-label" aria-hidden="true" style="display: none !important;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="csi-import-modal-label"><?php _e('Import Users', 'custom-signup-plugin'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><?php _e('Upload a CSV file to import users. Make sure your CSV file matches the template format.', 'custom-signup-plugin'); ?></p>
                <form method="post" action="" enctype="multipart/form-data" id="csi-import-form">
                    <?php wp_nonce_field('import_users_nonce'); ?>
                    <div class="mb-3">
                        <label for="csi-import-file" class="form-label"><strong><?php _e('Select CSV File:', 'custom-signup-plugin'); ?></strong></label>
                        <input type="file" class="form-control" name="import_file" id="csi-import-file" accept=".csv" required>
                    </div>
                    <div class="mb-3">
                        <a href="<?php echo esc_url(CSI_PLUGIN_URL . 'assets/excel/import-template.csv'); ?>" class="button" download><?php _e('Download Template', 'custom-signup-plugin'); ?></a>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php _e('Cancel', 'custom-signup-plugin'); ?></button>
                <button type="submit" form="csi-import-form" name="import_users" class="btn btn-primary"><?php _e('Import Users', 'custom-signup-plugin'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="csi-delete-modal" tabindex="-1" aria-labelledby="csi-delete-modal-label" aria-hidden="true" style="display: none !important;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="csi-delete-modal-label"><?php _e('Delete User', 'custom-signup-plugin'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><?php _e('Are you sure you want to permanently delete', 'custom-signup-plugin'); ?> <strong id="csi-delete-user-name"></strong>?</p>
                <p class="text-danger"><strong><?php _e('This action cannot be undone.', 'custom-signup-plugin'); ?></strong></p>
                <div id="csi-delete-form-container"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php _e('Cancel', 'custom-signup-plugin'); ?></button>
                <button type="submit" form="csi-delete-form" name="csi_delete_user" class="btn btn-danger"><?php _e('Delete User', 'custom-signup-plugin'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Email Progress Modal -->
<div class="modal fade" id="csi-email-progress-modal" tabindex="-1" aria-labelledby="csi-email-progress-modal-label" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false" style="display: none !important;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="csi-email-progress-modal-label"><?php _e('Sending Emails', 'custom-signup-plugin'); ?></h5>
            </div>
            <div class="modal-body">
                <p id="csi-email-progress-text"><?php _e('Preparing to send...', 'custom-signup-plugin'); ?></p>
                <div class="progress" style="height: 25px;">
                    <div id="csi-email-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                </div>
                <div id="csi-email-progress-details" class="mt-2 text-muted small"></div>
            </div>
            <div class="modal-footer">
                 <button type="button" class="btn btn-secondary" id="csi-cancel-email-sending" disabled><?php _e('Close', 'custom-signup-plugin'); ?></button>
            </div>
        </div>
    </div>
</div>
