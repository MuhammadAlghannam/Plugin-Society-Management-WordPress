<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$user = get_user_by('ID', $user_id);

// Get user meta
$fullname = get_user_meta($user_id, 'fullname', true) ?: $user->display_name;
$title = get_user_meta($user_id, 'title', true);
$specialty = get_user_meta($user_id, 'specialty', true);
$phone = get_user_meta($user_id, 'phone', true);
$dob = get_user_meta($user_id, 'dob', true);
$home_address = get_user_meta($user_id, 'home_address', true);
$work_address = get_user_meta($user_id, 'work_address', true);
$institute = get_user_meta($user_id, 'institute', true);
$country = get_user_meta($user_id, 'country', true);
$membership = get_user_meta($user_id, 'membership', true);
$user_status = get_user_meta($user_id, 'user_status', true) ?: 'not_active';
$payment_status = get_user_meta($user_id, 'payment_status', true) ?: 'pending';

// Get membership number (the code with approval)
if (function_exists('csi_get_user_membership_number')) {
    $membership_number = csi_get_user_membership_number($user_id);
} else {
    $membership_number = get_user_meta($user_id, 'membership_number', true);
}

// Get membership type label
$membership_type_label = '';
if (function_exists('csi_get_membership_type_label') && $membership) {
    $membership_type_label = csi_get_membership_type_label($membership);
} else {
    $membership_type_label = $membership ? ucwords(str_replace('_', ' ', $membership)) : '';
}
?>

<div class="csi-profile-container">
    <div class="row">
        <div class="col-md-6">
            <h3><?php _e('Personal Information', 'custom-signup-plugin'); ?></h3>
            <table class="table">
                <tr>
                    <th><?php _e('Full Name', 'custom-signup-plugin'); ?>:</th>
                    <td><?php echo esc_html($fullname); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Email', 'custom-signup-plugin'); ?>:</th>
                    <td><?php echo esc_html($user->user_email); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Title', 'custom-signup-plugin'); ?>:</th>
                    <td><?php echo esc_html($title); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Specialty', 'custom-signup-plugin'); ?>:</th>
                    <td><?php echo esc_html($specialty); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Phone', 'custom-signup-plugin'); ?>:</th>
                    <td><?php echo esc_html($phone); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Date of Birth', 'custom-signup-plugin'); ?>:</th>
                    <td><?php echo esc_html($dob); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Country', 'custom-signup-plugin'); ?>:</th>
                    <td><?php echo esc_html(csi_get_country_name($country)); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Institute', 'custom-signup-plugin'); ?>:</th>
                    <td><?php echo esc_html($institute ?: '-'); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Home Address', 'custom-signup-plugin'); ?>:</th>
                    <td><?php echo esc_html($home_address ?: '-'); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Work Address', 'custom-signup-plugin'); ?>:</th>
                    <td><?php echo esc_html($work_address ?: '-'); ?></td>
                </tr>
            </table>
        </div>
        
        <div class="col-md-6">
            <h3><?php _e('Membership Information', 'custom-signup-plugin'); ?></h3>
            <table class="table">
                <tr>
                    <th><?php _e('Membership Number', 'custom-signup-plugin'); ?>:</th>
                    <td><strong><?php echo esc_html($membership_number ?: '-'); ?></strong></td>
                </tr>
                <tr>
                    <th><?php _e('Membership Type', 'custom-signup-plugin'); ?>:</th>
                    <td><?php echo esc_html($membership_type_label ?: '-'); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Status', 'custom-signup-plugin'); ?>:</th>
                    <td>
                        <span class="user-status status-<?php echo esc_attr($user_status); ?>">
                            <?php echo esc_html(ucfirst(str_replace('_', ' ', $user_status))); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Payment Status', 'custom-signup-plugin'); ?>:</th>
                    <td>
                        <span class="payment-status status-<?php echo esc_attr($payment_status); ?>">
                            <?php echo esc_html(ucfirst($payment_status)); ?>
                        </span>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    
    <!-- Certificate Download -->
    <?php if ($user_status === 'active' && $payment_status === 'paid'): ?>
    <div class="mt-4">
        <form method="post" action="">
            <?php wp_nonce_field('profile_update', 'profile_nonce'); ?>
            <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
            <input type="hidden" name="fullname" value="<?php echo esc_attr($fullname); ?>">
            <button type="submit" name="download_certificate" class="btn btn-primary">
                <?php _e('Download Certificate', 'custom-signup-plugin'); ?>
            </button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Renewal Button -->
    <?php 
    // Check if eligible for renewal (not active, or declined/failed payment, or expired/near expiry)
    $show_renew = false;
    
    if ($user_status !== 'active') {
        $show_renew = true;
    }
    
    if ($payment_status === 'declined' || $payment_status === 'failed') {
        $show_renew = true;
    }
    
    // Also check date expiry if available
    if (!$show_renew && !empty($membership_end_date = get_user_meta($user_id, 'membership_end_date', true))) {
        $days_until_expiry = (strtotime($membership_end_date) - time()) / (60 * 60 * 24);
        if ($days_until_expiry < 30) { // Renew within 30 days of expiry
            $show_renew = true;
        }
    }

    // Don't show if already in review
    if ($payment_status === 'inreview') {
        $show_renew = false;
    }
    
    if ($show_renew): 
    ?>
    <div class="mt-4">
        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#csi-renewal-modal">
            <?php _e('Renew Membership', 'custom-signup-plugin'); ?>
        </button>
    </div>
    <?php elseif ($payment_status === 'inreview'): ?>
    <div class="mt-4 alert alert-info">
        <?php _e('Your renewal request is under review.', 'custom-signup-plugin'); ?>
    </div>
    <?php endif; ?>

    <!-- Renewal Modal -->
    <div class="modal fade" id="csi-renewal-modal" tabindex="-1" aria-labelledby="csi-renewal-modal-label" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="csi-renewal-modal-label"><?php _e('Renew Membership', 'custom-signup-plugin'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="csi-renewal-form" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="csi_submit_renewal">
                        
                        <div class="mb-3">
                            <label for="csi-payment-receipt" class="form-label"><?php _e('Payment Receipt', 'custom-signup-plugin'); ?> - <?php _e('إيصال الدفع', 'custom-signup-plugin'); ?> <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" name="csi_payment_receipt" id="csi-payment-receipt" accept="image/jpeg,image/png" required>
                            <div class="form-text"><?php _e('Max file size: 80 MB.', 'custom-signup-plugin'); ?></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php _e('Close', 'custom-signup-plugin'); ?></button>
                    <button type="submit" form="csi-renewal-form" class="btn btn-primary" id="csi-renewal-submit-btn">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        <?php _e('Submit Renewal', 'custom-signup-plugin'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
