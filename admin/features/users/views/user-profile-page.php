<?php
/**
 * User Profile Page View
 * Displays user profile information with status management
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    return;
}

// Get user data
$user_data = csi_get_user_profile_data($user_id);
$user_status = $user_data['user_status'] ?: 'not_active';
$payment_status = $user_data['payment_status'] ?: 'pending';
?>

<div class="wrap csi-admin-wrapper">
    <h1 class="mb-3">
        <?php _e('User Profile', 'custom-signup-plugin'); ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=membership-applications')); ?>" class="page-title-action">
            <?php _e('← Back to Users', 'custom-signup-plugin'); ?>
        </a>
    </h1>
    
    <!-- Tabs -->
    <ul class="nav nav-tabs mb-3" id="user-profile-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab">
                <?php _e('Personal Information', 'custom-signup-plugin'); ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="membership-tab" data-bs-toggle="tab" data-bs-target="#membership" type="button" role="tab">
                <?php _e('Membership', 'custom-signup-plugin'); ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="files-tab" data-bs-toggle="tab" data-bs-target="#files" type="button" role="tab">
                <?php _e('Files & Documents', 'custom-signup-plugin'); ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">
                <?php _e('History', 'custom-signup-plugin'); ?>
            </button>
        </li>
    </ul>
    
    <!-- Tab Content -->
    <div class="tab-content" id="user-profile-tab-content">
        
        <!-- Personal Information Tab -->
        <div class="tab-pane fade show active" id="personal" role="tabpanel">
            
            <!-- User Status Box -->
            <div class="card mb-3 border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <strong><?php _e('User Status', 'custom-signup-plugin'); ?></strong>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="badge <?php echo $user_status === 'active' ? 'bg-success' : 'bg-secondary'; ?> px-3 py-2">
                            <?php echo $user_status === 'active' ? __('Active', 'custom-signup-plugin') : __('Not Active', 'custom-signup-plugin'); ?>
                        </span>
                        <div class="csi-status-buttons">
                            <form method="post" action="">
                                <?php 
                                $action = $user_status === 'active' ? 'deactivate' : 'activate';
                                wp_nonce_field('csi_' . $action . '_user_' . $user_id, 'csi_user_action_nonce'); 
                                ?>
                                <input type="hidden" name="csi_user_id" value="<?php echo esc_attr($user_id); ?>">
                                <input type="hidden" name="csi_action" value="<?php echo $action; ?>">
                                <button type="submit" name="csi_user_action" class="btn <?php echo $user_status === 'active' ? 'btn-warning' : 'btn-success'; ?> csi-status-btn">
                                    <?php echo $user_status === 'active' ? __('Deactivate', 'custom-signup-plugin') : __('Activate', 'custom-signup-plugin'); ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Personal Info Card -->
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <?php
                        $personal_fields = [
                            'fullname' => __('Full Name', 'custom-signup-plugin'),
                            'email' => __('Email', 'custom-signup-plugin'),
                            'title' => __('Title', 'custom-signup-plugin'),
                            'specialty' => __('Specialty', 'custom-signup-plugin'),
                            'phone' => __('Phone', 'custom-signup-plugin'),
                            'dob' => __('Date of Birth', 'custom-signup-plugin'),
                            'country' => __('Country', 'custom-signup-plugin'),
                            'institute' => __('Institute', 'custom-signup-plugin'),
                        ];
                        
                        foreach ($personal_fields as $field => $label): ?>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo $label; ?></label>
                                <input type="text" class="form-control" value="<?php echo esc_attr($user_data[$field]); ?>" readonly>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="col-md-12 mb-3">
                            <label class="form-label"><?php _e('Home Address', 'custom-signup-plugin'); ?></label>
                            <textarea class="form-control" rows="2" readonly><?php echo esc_textarea($user_data['home_address']); ?></textarea>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label"><?php _e('Work Address', 'custom-signup-plugin'); ?></label>
                            <textarea class="form-control" rows="2" readonly><?php echo esc_textarea($user_data['work_address']); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Membership Tab -->
        <div class="tab-pane fade" id="membership" role="tabpanel">

            <!-- Payment Status Box -->
            <div class="card mb-3 border-0 shadow-sm">
                <div class="card-header bg-info text-white">
                    <strong><?php _e('Payment Status', 'custom-signup-plugin'); ?></strong>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="badge <?php 
                            if ($payment_status === 'paid') echo 'bg-success';
                            elseif ($payment_status === 'pending') echo 'bg-warning text-dark';
                            elseif ($payment_status === 'inreview') echo 'bg-info text-white';
                            else echo 'bg-danger';
                        ?> px-3 py-2">
                            <?php 
                                if ($payment_status === 'inreview') echo __('In Review', 'custom-signup-plugin');
                                else echo ucfirst($payment_status); 
                            ?>
                        </span>
                        <div class="csi-status-buttons">
                            <?php 
                            $payment_options = [
                                'paid' => 'btn-success', 
                                'pending' => 'btn-warning', 
                                'inreview' => 'btn-info',
                                'declined' => 'btn-danger'
                            ];
                            foreach ($payment_options as $status => $btn_class):
                                if ($payment_status !== $status): ?>
                                    <form method="post" action="" style="display: inline-block;">
                                        <?php wp_nonce_field('csi_payment_status_' . $user_id, 'csi_payment_nonce'); ?>
                                        <input type="hidden" name="csi_user_id" value="<?php echo esc_attr($user_id); ?>">
                                        <input type="hidden" name="csi_payment_action" value="<?php echo $status; ?>">
                                        <button type="submit" name="csi_update_payment" class="btn <?php echo $btn_class; ?> csi-status-btn">
                                            <?php 
                                                if ($status === 'inreview') echo __('In Review', 'custom-signup-plugin');
                                                else echo ucfirst($status); 
                                            ?>
                                        </button>
                                    </form>
                                <?php endif;
                            endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Renewal Request Card -->
            <?php if ($payment_status === 'inreview'): ?>
            <div class="card mb-3 border-info shadow-sm">
                <div class="card-header bg-info text-white">
                    <strong><?php _e('Renewal Request', 'custom-signup-plugin'); ?></strong>
                </div>
                <div class="card-body">
                    <p><?php _e('This user has requested membership renewal. Please review the payment receipt and approve if valid.', 'custom-signup-plugin'); ?></p>
                    
                    <div class="mb-3">
                        <strong><?php _e('Payment Receipt:', 'custom-signup-plugin'); ?></strong>
                        <?php 
                        $receipt_found = false;
                        if (!empty($user_data['files'])) {
                            foreach ($user_data['files'] as $key => $file) {
                                if ($key === 'payment_receipt_id' || (isset($file['name']) && strpos($file['name'], 'Payment Receipt') !== false)) {
                                    $file_url = isset($file['url']) ? $file['url'] : '';
                                    if ($file_url) {
                                        echo '<a href="' . esc_url($file_url) . '" target="_blank" class="btn btn-sm btn-outline-primary ms-2">' . __('View Receipt', 'custom-signup-plugin') . '</a>';
                                        $receipt_found = true;
                                        break;
                                    }
                                }
                            }
                        }
                        if (!$receipt_found) {
                            echo '<span class="text-muted ms-2">' . __('No receipt found', 'custom-signup-plugin') . '</span>';
                        }
                        ?>
                    </div>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('csi_approve_renewal_' . $user_id, 'csi_renewal_nonce'); ?>
                        <input type="hidden" name="csi_user_id" value="<?php echo esc_attr($user_id); ?>">
                        <button type="submit" name="csi_approve_renewal" class="btn btn-primary" onclick="return confirm('<?php _e('Are you sure you want to approve this renewal? This will update membership dates and set status to active.', 'custom-signup-plugin'); ?>');">
                            <?php _e('Renew Membership (Approve)', 'custom-signup-plugin'); ?>
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Membership Info Card -->
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <?php
                        $membership_fields = [
                            'membership_number' => __('Membership Number', 'custom-signup-plugin'),
                            'membership_type' => __('Membership Type', 'custom-signup-plugin'),
                            'registration_type' => __('Registration Type', 'custom-signup-plugin'),
                            'payment_method' => __('Payment Method', 'custom-signup-plugin'),
                            'membership_start_date' => __('Membership Start Date', 'custom-signup-plugin'),
                            'membership_end_date' => __('Membership End Date', 'custom-signup-plugin'),
                            'registration_date' => __('Registration Date', 'custom-signup-plugin'),
                        ];
                        
                        foreach ($membership_fields as $field => $label):
                            $value = $user_data[$field] ?? '-';
                            
                            // Format registration type
                            if ($field === 'registration_type' && $value) {
                                $value = ucfirst(str_replace('_', ' ', $value));
                            }
                            
                            // Format registration date
                            if ($field === 'registration_date' && $value) {
                                $value = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($value));
                            }
                            
                            // Default to dash if empty
                            if (empty($value)) {
                                $value = '-';
                            }
                        ?>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><strong><?php echo $label; ?></strong></label>
                                <p class="form-control-plaintext"><?php echo esc_html($value); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Files Tab -->
        <div class="tab-pane fade" id="files" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <?php 
                    $files = $user_data['files'];
                    if (!empty($files)): ?>
                        <div class="list-group">
                            <?php foreach ($files as $file):
                                if (is_array($file) && isset($file[0])):
                                    foreach ($file as $f):
                                        if (isset($f['url'])): ?>
                                            <a href="<?php echo esc_url($f['url']); ?>" target="_blank" class="list-group-item list-group-item-action">
                                                <?php echo esc_html($f['name']); ?>
                                            </a>
                                        <?php endif;
                                    endforeach;
                                elseif (isset($file['url'])): ?>
                                    <a href="<?php echo esc_url($file['url']); ?>" target="_blank" class="list-group-item list-group-item-action">
                                        <?php echo esc_html($file['name']); ?>
                                    </a>
                                <?php endif;
                            endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0"><?php _e('No files uploaded', 'custom-signup-plugin'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- History Tab -->
        <div class="tab-pane fade" id="history" role="tabpanel">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong><?php _e('Membership History', 'custom-signup-plugin'); ?></strong>
                    <?php 
                    if (function_exists('csi_get_user_membership_history')) {
                        $history = csi_get_user_membership_history($user_id);
                        if (!empty($history)): ?>
                            <form method="post" action="" style="display: inline-block;">
                                <?php wp_nonce_field('csi_export_history_' . $user_id, 'csi_history_export_nonce'); ?>
                                <input type="hidden" name="csi_user_id" value="<?php echo esc_attr($user_id); ?>">
                                <button type="submit" name="csi_export_history" class="btn btn-primary btn-sm">
                                    <span class="dashicons dashicons-download" style="vertical-align: middle; margin-right: 4px;"></span>
                                    <?php _e('Export History', 'custom-signup-plugin'); ?>
                                </button>
                            </form>
                        <?php endif;
                    } else {
                        $history = [];
                    }
                    ?>
                </div>
                <div class="card-body">
                    <?php 
                    if (function_exists('csi_get_user_membership_history')) {
                        if (!empty($history)): ?>
                            <div class="csi-timeline">
                                <?php foreach ($history as $event): 
                                    $formatted = csi_format_history_event($event);
                                ?>
                                <div class="csi-timeline-item">
                                    <div class="csi-timeline-icon bg-<?php echo esc_attr($formatted['class']); ?>">
                                        <span class="dashicons <?php echo esc_attr($formatted['icon']); ?>"></span>
                                    </div>
                                    <div class="csi-timeline-content">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <h5 class="mb-0"><?php echo esc_html($formatted['title']); ?></h5>
                                            <small class="text-muted"><?php echo esc_html($formatted['date']); ?></small>
                                        </div>
                                        <p class="mb-0 text-muted"><?php echo esc_html($formatted['description']); ?></p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0"><?php _e('No history available.', 'custom-signup-plugin'); ?></p>
                        <?php endif;
                    } else {
                        echo '<p class="text-danger">History function not available.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Existing styles... */
#user-profile-tab-content,
#user-profile-tab-content .tab-pane,
#user-profile-tab-content .card {
    width: 100% !important;
    max-width: 100% !important;
}

.csi-admin-wrapper .card-header {
    font-weight: 600;
    padding: 12px 20px;
}

.csi-status-buttons {
    display: flex;
    gap: 8px;
}

.csi-status-btn {
    min-width: 100px;
    padding: 8px 16px !important;
    font-size: 13px !important;
    font-weight: 500;
    border: none;
    border-radius: 4px;
}

/* Timeline Styles */
.csi-timeline {
    position: relative;
    padding-left: 20px;
}

.csi-timeline-item {
    position: relative;
    padding-bottom: 30px;
    padding-left: 40px;
}

.csi-timeline-item:before {
    content: '';
    position: absolute;
    left: 15px;
    top: 30px;
    bottom: -15px;
    width: 2px;
    background-color: #e9ecef;
}

.csi-timeline-item:last-child:before {
    display: none;
}

.csi-timeline-icon {
    position: absolute;
    left: 0;
    top: 0;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    z-index: 1;
}

.csi-timeline-icon .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.csi-timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    border-left: 3px solid #dee2e6;
}

@media (max-width: 576px) {
    .csi-admin-wrapper .card-body .d-flex {
        flex-direction: column;
        gap: 10px;
    }
    .csi-status-buttons {
        width: 100%;
        flex-wrap: wrap;
    }
    .csi-status-btn {
        flex: 1;
    }
}
</style>
