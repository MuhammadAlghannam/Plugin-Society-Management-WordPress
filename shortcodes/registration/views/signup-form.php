<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="csi-signup-container">
    <div id="submission-message" style="display: none;" class="submission-message alert"></div>
    <form method="post" action="" class="csi-signup-form" enctype="multipart/form-data" id="csi-signup-form">
        <?php wp_nonce_field('csi_signup_action', 'csi_signup_nonce'); ?>
        
        <!-- Personal Information -->
        <h3><?php _e('Personal Information', 'custom-signup-plugin'); ?></h3>
        
        <!-- Registration Type -->
        <div class="form-row mb-3">
            <div class="form-group">
                <label for="csi-registration-type"><?php _e('Registration Type', 'custom-signup-plugin'); ?> <span class="required-asterisk">*</span></label>
                <select name="csi_registration_type" id="csi-registration-type" class="form-control" required>
                    <option value=""><?php _e('Select Registration Type', 'custom-signup-plugin'); ?></option>
                    <option value="new"><?php _e('New Registration', 'custom-signup-plugin'); ?></option>
                    <option value="student"><?php _e('Student Registration', 'custom-signup-plugin'); ?></option>
                </select>
                <span class="validation-message" style="display: none;"></span>
            </div>
        </div>
        
        <div class="form-row mb-3">
            <div class="form-group">
                <label for="csi-fullname"><?php _e('Full Name', 'custom-signup-plugin'); ?> - <?php _e('اسم رباعي', 'custom-signup-plugin'); ?> <span class="required-asterisk">*</span></label>
                <input type="text" name="csi_fullname" id="csi-fullname" class="form-control" required>
                <span class="validation-message" style="display: none;"></span>
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="csi-title"><?php _e('Title', 'custom-signup-plugin'); ?> - <?php _e('وظيفة', 'custom-signup-plugin'); ?> <span class="required-asterisk">*</span></label>
                <input type="text" name="csi_title" id="csi-title" class="form-control" required>
                <span class="validation-message" style="display: none;"></span>
            </div>
            <div class="col-md-6">
                <label for="csi-specialty"><?php _e('Specialty', 'custom-signup-plugin'); ?> - <?php _e('تخصص', 'custom-signup-plugin'); ?> <span class="required-asterisk">*</span></label>
                <input type="text" name="csi_specialty" id="csi-specialty" class="form-control" required>
                <span class="validation-message" style="display: none;"></span>
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="csi-email"><?php _e('Email', 'custom-signup-plugin'); ?> <span class="required-asterisk">*</span></label>
                <input type="email" name="csi_email" id="csi-email" class="form-control" required>
                <span class="validation-message" style="display: none;"></span>
            </div>
            <div class="col-md-6">
                <label for="csi-phone"><?php _e('Phone', 'custom-signup-plugin'); ?> <span class="required-asterisk">*</span></label>
                <input type="tel" name="csi_phone" id="csi-phone" class="form-control" required>
                <span class="validation-message" style="display: none;"></span>
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="csi-dob"><?php _e('Date of Birth', 'custom-signup-plugin'); ?> <span class="required-asterisk">*</span></label>
                <input type="date" name="csi_dob" id="csi-dob" class="form-control" required>
                <span class="validation-message" style="display: none;"></span>
            </div>
            <div class="col-md-6">
                <label for="csi-country"><?php _e('Country', 'custom-signup-plugin'); ?> <span class="required-asterisk">*</span></label>
                <select name="csi_country" id="csi-country" class="form-control" required>
                    <option value=""><?php _e('Select Country', 'custom-signup-plugin'); ?></option>
                    <?php
                    $countries = csi_get_countries();
                    asort($countries);
                    foreach ($countries as $code => $name) {
                        echo '<option value="' . esc_attr($code) . '">' . esc_html($name) . '</option>';
                    }
                    ?>
                </select>
                <span class="validation-message" style="display: none;"></span>
            </div>
        </div>
        
        <div class="form-row mb-3">
            <label for="csi-home-address"><?php _e('Home Address', 'custom-signup-plugin'); ?> <span class="required-asterisk">*</span></label>
            <input type="text" name="csi_home_address" id="csi-home-address" class="form-control" required>
            <span class="validation-message" style="display: none;"></span>
        </div>
        
        <div class="form-row mb-3">
            <label for="csi-work-address"><?php _e('Work Address', 'custom-signup-plugin'); ?> <span class="required-asterisk">*</span></label>
            <input type="text" name="csi_work_address" id="csi-work-address" class="form-control" required>
            <span class="validation-message" style="display: none;"></span>
        </div>
        
        <div class="form-row mb-3">
            <label for="csi-institute"><?php _e('Institute', 'custom-signup-plugin'); ?> <span class="required-asterisk">*</span></label>
            <input type="text" name="csi_institute" id="csi-institute" class="form-control" required>
            <span class="validation-message" style="display: none;"></span>
        </div>
        
        <!-- Password -->
        <div class="form-row mb-3">
            <label for="csi-password"><?php _e('Password', 'custom-signup-plugin'); ?> <span class="required-asterisk">*</span></label>
            <input type="password" name="csi_password" id="csi-password" class="form-control" minlength="8" required>
            <small class="field-description">(Min 8 characters)</small>
            <span class="validation-message" style="display: none;"></span>
        </div>
        
        <!-- Membership Type -->
        <h3><?php _e('Membership Information', 'custom-signup-plugin'); ?></h3>
        
        <div class="form-row mb-3" id="membership-type-field">
            <label for="csi-membership"><?php _e('Membership Type', 'custom-signup-plugin'); ?> <span class="required-asterisk">*</span></label>
            <select name="csi_membership" id="csi-membership" class="form-control" required>
                <option value=""><?php _e('Select Membership Type', 'custom-signup-plugin'); ?></option>
                <option value="student"><?php _e('Student', 'custom-signup-plugin'); ?></option>
                <option value="early_investigator"><?php _e('Early Investigator', 'custom-signup-plugin'); ?></option>
                <option value="postdoctoral"><?php _e('Postdoctoral', 'custom-signup-plugin'); ?></option>
                <option value="scientist"><?php _e('Scientist', 'custom-signup-plugin'); ?></option>
                <option value="industry"><?php _e('Industry', 'custom-signup-plugin'); ?></option>
                <option value="honorary"><?php _e('Honorary', 'custom-signup-plugin'); ?></option>
            </select>
            <span class="validation-message" style="display: none;"></span>
        </div>
        
        <!-- Payment Information -->
        <h3><?php _e('Payment Information', 'custom-signup-plugin'); ?></h3>
        
        <div class="form-row mb-3">
            <label><?php _e('Payment Method', 'custom-signup-plugin'); ?> <span class="required-asterisk">*</span></label>
            <div class="radio-group">
                <label>
                    <input type="radio" name="csi_payment_method" value="insta" required>
                    InstaPay
                </label>
            </div>
            <div id="payment-details" class="mt-3">
                <!-- Payment details will be shown here via JavaScript -->
            </div>
            <span class="validation-message" style="display: none;"></span>
        </div>
        
        <!-- File Uploads -->
        <h3><?php _e('Documents', 'custom-signup-plugin'); ?></h3>
        
        <div class="form-row mb-3">
            <label for="csi-photo"><?php _e('Personal Photo', 'custom-signup-plugin'); ?> <span class="required-asterisk">*</span></label>
            <input type="file" name="csi_photo" id="csi-photo" class="form-control" accept="image/jpeg,image/png" required>
            <small class="field-description">(Max 2MB, JPEG/PNG)</small>
            <span class="validation-message" style="display: none;"></span>
        </div>
        
        <div class="form-row mb-3">
            <label for="csi-cv"><?php _e('CV', 'custom-signup-plugin'); ?> <span class="required-asterisk">*</span></label>
            <input type="file" name="csi_cv" id="csi-cv" class="form-control" accept=".pdf,.doc,.docx" required>
            <small class="field-description">(Max 15MB, PDF/DOC/DOCX)</small>
            <span class="validation-message" style="display: none;"></span>
        </div>
        
        <div class="form-row mb-3">
            <label for="csi-id-scan"><?php _e('ID Scans', 'custom-signup-plugin'); ?> <span class="required-asterisk">*</span></label>
            <input type="file" name="csi_id_scan[]" id="csi-id-scan" class="form-control" accept="image/jpeg,image/png" multiple required>
            <small class="field-description">(Max 20MB each, JPEG/PNG, Multiple files allowed)</small>
            <span class="validation-message" style="display: none;"></span>
        </div>
        
        <div class="form-row mb-3" id="student-card-field" style="display: none;">
            <label for="csi-student-card"><?php _e('Student Card', 'custom-signup-plugin'); ?> <span class="required-asterisk">*</span></label>
            <input type="file" name="csi_student_card" id="csi-student-card" class="form-control" accept="image/jpeg,image/png">
            <small class="field-description">(Max 2MB, JPEG/PNG)</small>
            <span class="validation-message" style="display: none;"></span>
        </div>
        
        <div class="form-row mb-3">
            <label for="csi-payment-receipt"><?php _e('Payment Receipt', 'custom-signup-plugin'); ?></label>
            <input type="file" name="csi_payment_receipt" id="csi-payment-receipt" class="form-control" accept="image/jpeg,image/png">
            <small class="field-description">(Max 80MB, JPEG/PNG, Optional)</small>
            <span class="validation-message" style="display: none;"></span>
        </div>
        
        <!-- Signature -->
        <div class="form-row mb-3">
            <label for="csi-signature"><?php _e('Signature', 'custom-signup-plugin'); ?> <span class="required-asterisk">*</span></label>
            <input type="text" name="csi_signature" id="csi-signature" class="form-control" required>
            <span class="validation-message" style="display: none;"></span>
        </div>
        
        <!-- Submit Button -->
        <div class="form-row mb-3 text-center">
            <button type="submit" class="btn btn-primary btn-lg"><?php _e('Submit Application', 'custom-signup-plugin'); ?></button>
        </div>
    </form>
</div>
