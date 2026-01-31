<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    return;
}

$abbreviations = csi_get_all_abbreviations();
$membership_types = array(
    'student' => 'Student membership',
    'early_investigator' => 'Early Investigator membership',
    'postdoctoral' => 'Postdoctoral membership',
    'scientist' => 'Scientist membership',
    'industry' => 'Industry members',
    'honorary' => 'Honorary membership'
);
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <!-- Buttons Section -->
    <div class="tablenav top">
        <div class="alignleft">
            <p>
                <strong>Manage abbreviations and generate IDs for membership types.</strong>
            </p>
        </div>
        <div class="alignright">
            <button type="button" class="button" id="csi-add-abbreviation-btn">Add Abbreviation</button>
            <button type="button" class="button button-primary" id="csi-generate-ids-btn" style="margin-left: 10px;">Generate IDs</button>
        </div>
        <br class="clear">
    </div>

    <!-- Abbreviations Table -->
    <table class="widefat fixed striped">
        <thead>
            <tr>
                <th>Membership Type</th>
                <th>Abbreviation</th>
                <th>Last Generated Number</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($abbreviations)): ?>
                <tr>
                    <td colspan="4" style="text-align: center; padding: 20px;">
                        No abbreviations configured. Click "Add Abbreviation" to get started.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($abbreviations as $abbr): ?>
                    <tr>
                        <td><?php echo esc_html($membership_types[$abbr['membership_type']] ?? $abbr['membership_type']); ?></td>
                        <td><strong><?php echo esc_html($abbr['abbreviation']); ?></strong></td>
                        <td><?php echo esc_html($abbr['last_number']); ?></td>
                        <td class="d-flex gap-2">
                            <button type="button" class="button button-small csi-edit-abbreviation" 
                                    data-membership="<?php echo esc_attr($abbr['membership_type']); ?>"
                                    data-abbreviation="<?php echo esc_attr($abbr['abbreviation']); ?>">
                                Edit
                            </button>
                            <form method="post" action="" style="display: inline-block; margin-left: 5px;">
                                <?php wp_nonce_field('csi_delete_abbreviation_nonce'); ?>
                                <input type="hidden" name="membership_type" value="<?php echo esc_attr($abbr['membership_type']); ?>">
                                <button type="submit" name="csi_delete_abbreviation" class="button button-small" 
                                        onclick="return confirm('Are you sure you want to delete this abbreviation?');">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add/Edit Abbreviation Modal -->
<div class="modal fade" id="csi-abbreviation-modal" tabindex="-1" aria-labelledby="csi-abbreviation-modal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="csi-abbreviation-modal-label">Add Abbreviation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="" id="csi-abbreviation-form">
                <div class="modal-body">
                    <?php wp_nonce_field('csi_abbreviation_nonce'); ?>
                    <input type="hidden" name="csi_save_abbreviation" value="1">
                    <input type="hidden" name="edit_membership" id="edit-membership" value="">
                    
                    <div class="mb-3">
                        <label for="abbreviation-membership" class="form-label"><strong>Membership Type *</strong></label>
                        <select class="form-control" name="membership_type" id="abbreviation-membership" required>
                            <option value="">Select Membership Type</option>
                            <?php foreach ($membership_types as $key => $label): ?>
                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="abbreviation-input" class="form-label"><strong>Abbreviation *</strong></label>
                        <input type="text" class="form-control" name="abbreviation" id="abbreviation-input" 
                               placeholder="e.g., st, ei, pd" maxlength="10" required>
                        <small class="form-text text-muted">Enter a short abbreviation (max 10 characters)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Generate IDs Modal -->
<div class="modal fade" id="csi-generate-modal" tabindex="-1" aria-labelledby="csi-generate-modal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="csi-generate-modal-label">Generate IDs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="generate-membership" class="form-label"><strong>Select Membership Type *</strong></label>
                    <select class="form-control" name="membership_type" id="generate-membership" required>
                        <option value="">Select Membership Type</option>
                        <?php foreach ($membership_types as $key => $label): ?>
                            <?php 
                            $abbr_data = csi_get_abbreviation_data($key);
                            if ($abbr_data): 
                            ?>
                                <option value="<?php echo esc_attr($key); ?>" 
                                        data-abbreviation="<?php echo esc_attr($abbr_data['abbreviation']); ?>"
                                        data-last-number="<?php echo esc_attr($abbr_data['last_number']); ?>">
                                    <?php echo esc_html($label); ?> (<?php echo esc_html($abbr_data['abbreviation']); ?>)
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="generate-info" style="display: none; margin-top: 15px; margin-bottom: 15px;"></div>
                
                <div class="alert alert-warning" style="display: none;" id="generate-warning">
                    <strong>Warning:</strong> This will regenerate IDs for ALL users of this membership type, overwriting existing IDs.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="post" action="" style="display: inline-block;" id="csi-generate-new-form">
                    <?php wp_nonce_field('csi_generate_new_nonce'); ?>
                    <input type="hidden" name="csi_generate_new" value="1">
                    <input type="hidden" name="membership_type" id="generate-new-membership" value="">
                    <button type="submit" class="btn btn-primary">Generate</button>
                </form>
                <form method="post" action="" style="display: inline-block;" id="csi-generate-all-form">
                    <?php wp_nonce_field('csi_generate_all_nonce'); ?>
                    <input type="hidden" name="csi_generate_all" value="1">
                    <input type="hidden" name="membership_type" id="generate-all-membership" value="">
                    <button type="submit" class="btn btn-danger" 
                            onclick="return confirm('Are you sure you want to regenerate ALL IDs for this membership type? This will overwrite existing IDs.');">
                        Generate All
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.modal {
    z-index: 100000 !important;
}
.modal-backdrop {
    z-index: 99999 !important;
}
</style>