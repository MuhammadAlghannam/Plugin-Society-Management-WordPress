<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Form Processing Handler
 * Processes the registration form submission
 */

/**
 * Handle file upload
 */
function csi_handle_file_upload($file_key, $index = null) {
    if (!function_exists('wp_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }
    
    $uploadedfile = $index !== null ? 
        [
            'name'     => $_FILES[$file_key]['name'][$index],
            'type'     => $_FILES[$file_key]['type'][$index],
            'tmp_name' => $_FILES[$file_key]['tmp_name'][$index],
            'error'    => $_FILES[$file_key]['error'][$index],
            'size'     => $_FILES[$file_key]['size'][$index]
        ] : 
        $_FILES[$file_key];
    
    $upload_overrides = ['test_form' => false];
    $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
    
    if ($movefile && !isset($movefile['error'])) {
        $file_path = $movefile['file'];
        $attachment = [
            'post_mime_type' => $movefile['type'],
            'post_title'     => preg_replace('/\.[^.]+$/', '', basename($file_path)),
            'post_content'   => '',
            'post_status'    => 'inherit'
        ];
        
        $attach_id = wp_insert_attachment($attachment, $file_path);
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
        wp_update_attachment_metadata($attach_id, $attach_data);
        
        return $attach_id;
    }
    
    return false;
}

/**
 * Process signup form
 */
function csi_process_signup_form() {
    // Start clean
    ob_start();
    
    try {
        // Verify nonce
        check_ajax_referer('csi_signup_nonce', 'security');
        
        $errors = [];
        $form_data = [];
        
        // Define required fields based on registration type
        $base_required_fields = [
            'csi_fullname' => __('Full Name', 'custom-signup-plugin'),
            'csi_title' => __('Title', 'custom-signup-plugin'),
            'csi_specialty' => __('Specialty', 'custom-signup-plugin'),
            'csi_email' => __('Email', 'custom-signup-plugin'),
            'csi_phone' => __('Phone', 'custom-signup-plugin'),
            'csi_dob' => __('Date of Birth', 'custom-signup-plugin'),
            'csi_home_address' => __('Home Address', 'custom-signup-plugin'),
            'csi_work_address' => __('Work Address', 'custom-signup-plugin'),
            'csi_institute' => __('Institute', 'custom-signup-plugin'),
            'csi_country' => __('Country', 'custom-signup-plugin'),
            'csi_registration_type' => __('Registration Type', 'custom-signup-plugin'),
            'csi_membership' => __('Membership', 'custom-signup-plugin'),
            'csi_signature' => __('Signature', 'custom-signup-plugin'),
            'csi_password' => __('Password', 'custom-signup-plugin'),
            'csi_payment_method' => __('Payment Method', 'custom-signup-plugin')
        ];
        
        // Add conditional required fields based on registration type
        $registration_type = isset($_POST['csi_registration_type']) ? sanitize_text_field($_POST['csi_registration_type']) : '';
        
        // Validate and sanitize text inputs
        foreach ($base_required_fields as $field => $label) {
            // Skip membership validation for student registration
            if ($field === 'csi_membership' && $registration_type === 'student') {
                $form_data['membership'] = 'student';
                continue;
            }
            
            if (empty($_POST[$field])) {
                $errors[$field] = $label . ' ' . __('is required.', 'custom-signup-plugin');
            } else {
                if ($field === 'csi_password') {
                    $form_data['password'] = trim($_POST['csi_password']);
                } else {
                    $form_data[str_replace('csi_', '', $field)] = sanitize_text_field($_POST[$field]);
                }
            }
        }
        
        // Set up WordPress upload directory
        $upload_dir = wp_upload_dir();
        $user_upload_dir = $upload_dir['basedir'] . '/member-uploads';
        if (!file_exists($user_upload_dir)) {
            wp_mkdir_p($user_upload_dir);
        }
        
        // Define file upload configurations
        $file_configs = [
            'csi_photo' => [
                'label' => __('Personal Photo', 'custom-signup-plugin'),
                'max_size' => 2 * 1024 * 1024, // 2MB
                'allowed_types' => ['image/jpeg', 'image/png']
            ],
            'csi_cv' => [
                'label' => __('CV', 'custom-signup-plugin'),
                'max_size' => 15 * 1024 * 1024, // 15MB
                'allowed_types' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
            ],
            'csi_payment_receipt' => [
                'label' => __('Payment Receipt', 'custom-signup-plugin'),
                'max_size' => 80 * 1024 * 1024, // 80MB
                'allowed_types' => ['image/jpeg', 'image/png'],
                'optional' => true // Mark as optional
            ],
            'csi_student_card' => [
                'label' => __('Student Card', 'custom-signup-plugin'),
                'max_size' => 2 * 1024 * 1024, // 2MB
                'allowed_types' => ['image/jpeg', 'image/png']
            ]
        ];
        
        // Special handling for ID scan (multiple files)
        $id_scan_max_size = 20 * 1024 * 1024; // 20MB
        
        // Process regular file uploads
        foreach ($file_configs as $field => $config) {
            // Skip student card if not student registration
            if ($field === 'csi_student_card' && $registration_type !== 'student') {
                continue;
            }
            
            if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
                // If optional and no file, skip
                if (!empty($config['optional'])) {
                    continue;
                }
                
                $errors[$field] = $config['label'] . ' ' . __('is required.', 'custom-signup-plugin');
                continue;
            }
            
            if ($_FILES[$field]['size'] > $config['max_size']) {
                $errors[$field] = $config['label'] . ' ' . sprintf(__('exceeds maximum file size of %s', 'custom-signup-plugin'), size_format($config['max_size']));
                continue;
            }
            
            if (!in_array($_FILES[$field]['type'], $config['allowed_types'])) {
                $errors[$field] = $config['label'] . ' ' . __('has invalid file type.', 'custom-signup-plugin');
                continue;
            }
        }
        
        // Handle ID scan files separately
        if (isset($_FILES['csi_id_scan'])) {
            $has_valid_id_scan = false;
            
            foreach ($_FILES['csi_id_scan']['name'] as $key => $value) {
                if ($_FILES['csi_id_scan']['error'][$key] === UPLOAD_ERR_OK) {
                    if ($_FILES['csi_id_scan']['size'][$key] > $id_scan_max_size) {
                        $errors['csi_id_scan'] = sprintf(__('ID Scan file %d exceeds maximum size of %s', 'custom-signup-plugin'), ($key + 1), size_format($id_scan_max_size));
                        continue;
                    }
                    
                    if (!in_array($_FILES['csi_id_scan']['type'][$key], ['image/jpeg', 'image/png'])) {
                        $errors['csi_id_scan'] = sprintf(__('ID Scan file %d has invalid file type.', 'custom-signup-plugin'), ($key + 1));
                        continue;
                    }
                    
                    $has_valid_id_scan = true;
                }
            }
            
            if (!$has_valid_id_scan) {
                $errors['csi_id_scan'] = sprintf(__('Please provide at least one valid ID scan (max %s per file)', 'custom-signup-plugin'), size_format($id_scan_max_size));
            }
        }
        
        // Additional validation
        if (!empty($form_data['email']) && !is_email($form_data['email'])) {
            $errors['csi_email'] = __('Invalid email address.', 'custom-signup-plugin');
        }
        if (!empty($form_data['email']) && email_exists($form_data['email'])) {
            $errors['csi_email'] = __('This email is already registered.', 'custom-signup-plugin');
        }
        if (isset($_POST['csi_password']) && strlen($_POST['csi_password']) < 8) {
            $errors['csi_password'] = __('Password must be at least 8 characters long.', 'custom-signup-plugin');
        }

        // Handle errors
        if (!empty($errors)) {
            ob_clean();
            wp_send_json_error([
                'message' => implode('<br>', $errors)
            ]);
            exit;
        }
        
        // Create username from email
        $original_username = sanitize_user(current(explode('@', $form_data['email'])), true);
        $username = $original_username;
        $counter = 1;
        while (username_exists($username)) {
            $username = $original_username . $counter++;
        }
        
        // Create user
        $user_data = [
            'user_login' => $username,
            'user_email' => $form_data['email'],
            'user_pass' => $form_data['password'],
            'display_name' => $form_data['fullname'],
            'role' => 'subscriber'
        ];
        
        $user_id = wp_insert_user($user_data);
        
        if (is_wp_error($user_id)) {
            throw new Exception($user_id->get_error_message());
        }
        
        // Set default user status to 'not_active'
        update_user_meta($user_id, 'user_status', 'not_active');
        
        // Auto-generate membership number based on membership type or use provided one
        if (!empty($form_data['membership'])) {
            // This function will be in membership-number feature
            if (function_exists('csi_assign_membership_number')) {
                csi_assign_membership_number($user_id, $form_data['membership']);
            } elseif (function_exists('csi_assign_generated_id')) {
                // Backward compatibility
                csi_assign_generated_id($user_id, $form_data['membership']);
            }
        }
        
        // Store registration type
        update_user_meta($user_id, 'registration_type', $registration_type);
        
        // Handle student card upload for student registration
        if ($registration_type === 'student' && isset($_FILES['csi_student_card'])) {
            $student_card_id = csi_handle_file_upload('csi_student_card');
            if ($student_card_id) {
                update_user_meta($user_id, 'student_card_id', $student_card_id);
            }
        }
        
        // Store fullname as user meta
        update_user_meta($user_id, 'fullname', $form_data['fullname']);
        
        // Handle file uploads
        $file_fields = ['csi_photo', 'csi_cv', 'csi_payment_receipt'];
        if ($registration_type === 'student') {
            $file_fields[] = 'csi_student_card';
        }
        
        foreach ($file_fields as $field) {
            if (!empty($_FILES[$field]['name'])) {
                $file_id = csi_handle_file_upload($field);
                if ($file_id) {
                    update_user_meta($user_id, str_replace('csi_', '', $field) . '_id', $file_id);
                    $file_url = wp_get_attachment_url($file_id);
                    update_user_meta($user_id, str_replace('csi_', '', $field), $file_url);
                }
            }
        }
        
        // Handle ID scans (multiple files)
        if (!empty($_FILES['csi_id_scan']['name'][0])) {
            $id_scan_ids = [];
            $id_scan_urls = [];
            foreach ($_FILES['csi_id_scan']['name'] as $key => $value) {
                if (!empty($value)) {
                    $id_scan_id = csi_handle_file_upload('csi_id_scan', $key);
                    if ($id_scan_id) {
                        $id_scan_ids[] = $id_scan_id;
                        $id_scan_urls[] = wp_get_attachment_url($id_scan_id);
                    }
                }
            }
            if (!empty($id_scan_ids)) {
                update_user_meta($user_id, 'id_scan_ids', $id_scan_ids);
                update_user_meta($user_id, 'id_scans', $id_scan_urls);
            }
        }
        
        // Add user meta
        $meta_fields = [
            'title', 'specialty', 'phone', 'dob', 'home_address',
            'work_address', 'institute', 'country', 'membership',
            'signature', 'payment_method'
        ];
        
        foreach ($meta_fields as $field) {
            if (isset($form_data[$field])) {
                update_user_meta($user_id, $field, $form_data[$field]);
            }
        }
        
        // Dynamic payment status: if receipt uploaded -> inreview, else -> pending
        $has_receipt = get_user_meta($user_id, 'payment_receipt_id', true);
        $initial_payment_status = $has_receipt ? 'inreview' : 'pending';
        update_user_meta($user_id, 'payment_status', $initial_payment_status);
        
        // Log history
        if (function_exists('csi_log_membership_event')) {
            csi_log_membership_event($user_id, 'registration', [
                'registration_type' => $registration_type,
                'membership_type' => $form_data['membership'] ?? $registration_type,
                'payment_status' => $initial_payment_status
            ]);
            
            if ($initial_payment_status === 'inreview') {
                csi_log_membership_event($user_id, 'payment_status_changed', [
                    'old_status' => 'pending',
                    'new_status' => 'inreview',
                    'reason' => 'registration_receipt_upload'
                ]);
            }
        }
        
        ob_clean();
        wp_send_json_success([
            'message' => __('Thank you for your submission!', 'custom-signup-plugin')
        ]);
        exit;
        
    } catch (Exception $e) {
        ob_clean();
        error_log('Registration failed: ' . $e->getMessage());
        wp_send_json_error([
            'message' => __('Registration failed: ', 'custom-signup-plugin') . $e->getMessage()
        ]);
        exit;
    }
}
