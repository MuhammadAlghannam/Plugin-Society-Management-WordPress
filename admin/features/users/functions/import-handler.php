<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * CSV Import/Export Handler
 */

/**
 * Export users to CSV
 */
function csi_export_users_to_csv() {
    if (
        !current_user_can('manage_options') ||
        !isset($_POST['export_users']) ||
        !check_admin_referer('export_users_nonce')
    ) {
        return;
    }
    
    // Clean any previous output and start fresh
    if (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
    
    global $wpdb;
    
    $membership_types = ['student', 'early_investigator', 'postdoctoral', 'scientist', 'industry', 'honorary'];
    $placeholders = implode(',', array_fill(0, count($membership_types), '%s'));
    $query = $wpdb->prepare("
        SELECT u.ID, u.user_registered
        FROM {$wpdb->users} u
        JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
        WHERE um.meta_key = 'membership'
          AND um.meta_value IN ($placeholders)
        ORDER BY u.user_registered DESC
    ", $membership_types);
    $users = $wpdb->get_results($query);
    
    // Get user objects with email
    foreach ($users as $key => $user) {
        $user_obj = get_user_by('ID', $user->ID);
        if ($user_obj) {
            $users[$key]->user_email = $user_obj->user_email;
        }
    }
    
    if (empty($users)) {
        wp_die(__('No users found to export.', 'custom-signup-plugin'));
    }
    
    $filename = 'membership-applications-' . date('Y-m-d') . '.csv';
    $csv_data = "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
    
    // CSV header row
    $headers = [
        'ID', 'Full Name', 'Title', 'Specialty', 'Email', 'Phone',
        'Date of Birth', 'Home Address', 'Work Address', 'Institute',
        'Country', 'Membership Type', 'Registration Type', 'Membership Number',
        'Signature', 'Payment Method', 'Payment Status', 'Personal Photo', 'ID Scans',
        'CV', 'Student Card', 'Payment Receipt', 'Registration Date',
        'Status', 'Membership Number'
    ];
    $csv_data .= implode(',', array_map('csi_escape_csv_field', $headers)) . "\n";
    
    foreach ($users as $user) {
        // Get all meta fields, ensuring null values become empty strings
        $fullname = get_user_meta($user->ID, 'fullname', true) ?: '';
        $title = get_user_meta($user->ID, 'title', true) ?: '';
        $specialty = get_user_meta($user->ID, 'specialty', true) ?: '';
        $phone = get_user_meta($user->ID, 'phone', true) ?: '';
        $dob = get_user_meta($user->ID, 'dob', true) ?: '';
        $home_address = get_user_meta($user->ID, 'home_address', true) ?: '';
        $work_address = get_user_meta($user->ID, 'work_address', true) ?: '';
        $institute = get_user_meta($user->ID, 'institute', true) ?: '';
        $country = get_user_meta($user->ID, 'country', true) ?: '';
        $membership_raw = get_user_meta($user->ID, 'membership', true) ?: '';
        $registration_type = get_user_meta($user->ID, 'registration_type', true) ?: '';
        $signature = get_user_meta($user->ID, 'signature', true) ?: '';
        $payment_method = get_user_meta($user->ID, 'payment_method', true) ?: '';
        $payment_status = get_user_meta($user->ID, 'payment_status', true) ?: 'pending';
        $user_status = get_user_meta($user->ID, 'user_status', true) ?: 'not_active';
        $membership_number = get_user_meta($user->ID, 'membership_number', true) ?: $user->ID;
        
        // Retrieve file URLs - try attachment IDs first, then fallback to URLs
        $photo_id = get_user_meta($user->ID, 'photo_id', true);
        $cv_id = get_user_meta($user->ID, 'cv_id', true);
        $receipt_id = get_user_meta($user->ID, 'receipt_id', true);
        $id_scan_ids = get_user_meta($user->ID, 'id_scan_ids', true);
        $student_card_id = get_user_meta($user->ID, 'student_card_id', true);
        
        $photo_url = $photo_id ? wp_get_attachment_url($photo_id) : '';
        if (!$photo_url) {
            $photo_url = get_user_meta($user->ID, 'photo', true);
        }
        
        $cv_url = $cv_id ? wp_get_attachment_url($cv_id) : '';
        if (!$cv_url) {
            $cv_url = get_user_meta($user->ID, 'cv', true);
        }
        
        $receipt_url = $receipt_id ? wp_get_attachment_url($receipt_id) : '';
        if (!$receipt_url) {
            $receipt_url = get_user_meta($user->ID, 'payment_receipt', true);
        }
        
        $student_card_url = $student_card_id ? wp_get_attachment_url($student_card_id) : '';
        if (!$student_card_url) {
            $student_card_url = get_user_meta($user->ID, 'student_card', true);
        }
        
        $id_scan_urls = [];
        if (is_array($id_scan_ids) && !empty($id_scan_ids)) {
            foreach ($id_scan_ids as $id) {
                $url = wp_get_attachment_url($id);
                if ($url) {
                    $id_scan_urls[] = $url;
                }
            }
        }
        if (empty($id_scan_urls)) {
            $id_scan_urls_meta = get_user_meta($user->ID, 'id_scans', true);
            if (is_array($id_scan_urls_meta)) {
                $id_scan_urls = $id_scan_urls_meta;
            }
        }
        
        // Create Excel hyperlink formulas
        $photo_link = $photo_url ? '"=HYPERLINK(""' . esc_url($photo_url) . '"",""View"")"' : '';
        $cv_link = $cv_url ? '"=HYPERLINK(""' . esc_url($cv_url) . '"",""View"")"' : '';
        $receipt_link = $receipt_url ? '"=HYPERLINK(""' . esc_url($receipt_url) . '"",""View"")"' : '';
        $student_card_link = $student_card_url ? '"=HYPERLINK(""' . esc_url($student_card_url) . '"",""View"")"' : '';
        
        $id_scans_links = [];
        foreach ($id_scan_urls as $index => $url) {
            if ($url) {
                $id_scans_links[] = '"=HYPERLINK(""' . esc_url($url) . '"",""View ' . ($index + 1) . '"")"';
            }
        }
        $id_scans_string = implode(', ', $id_scans_links);
        
        // Build CSV row
        $data = [
            $user->ID,
            $fullname,
            $title,
            $specialty,
            $user->user_email,
            $phone,
            $dob,
            $home_address,
            $work_address,
            $institute,
            $country,
            csi_get_membership_type_label($membership_raw),
            ucfirst(str_replace('_', ' ', ($registration_type ?: ''))),
            $membership_number,
            $signature,
            $payment_method,
            $payment_status,
            $photo_link,
            $id_scans_string,
            $cv_link,
            $student_card_link,
            $receipt_link,
            $user->user_registered,
            $user_status,
            $membership_number
        ];
        
        $csv_data .= implode(',', array_map('csi_escape_csv_field', $data)) . "\n";
    }
    
    // Clean output buffer and send CSV headers
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Output CSV headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output CSV data
    echo $csv_data;
    exit();
}
add_action('admin_init', 'csi_export_users_to_csv');

/**
 * Download Import Template
 */
function csi_download_import_template() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Check if request is via POST with nonce or GET (direct link)
    $is_post = isset($_POST['download_template']) && check_admin_referer('download_template_nonce');
    $is_get = isset($_GET['download_template']);
    
    if ($is_post || $is_get) {
        $filename = 'membership-import-template-' . date('Y-m-d') . '.csv';
        
        // Try to use static file first, fallback to generated
        $template_file = CSI_PLUGIN_DIR . 'assets/excel/import-template.csv';
        if (file_exists($template_file)) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');
            readfile($template_file);
            exit();
        }
        
        // Fallback: Generate CSV
        $csv_data = "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
        
        // CSV header row (matching export format and import handler expectations)
        $headers = [
            'Full Name',
            'Title',
            'Specialty',
            'Email',
            'Phone',
            'Date of Birth',
            'Home Address',
            'Work Address',
            'Institute',
            'Country',
            'Membership Type',
            'Registration Type',
            'Membership Number',
            'Signature',
            'Payment Method',
            'Payment Status',
            'Personal Photo (URL)',
            'ID Scans (URLs comma-separated)',
            'CV (URL)',
            'Student Card (URL)',
            'Payment Receipt (URL)',
            'Registration Date',
            'Status',
            'Membership Number'
        ];
        $csv_data .= implode(',', $headers) . "\n";
        
        // Output CSV headers
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Print CSV data
        echo $csv_data;
        exit();
    }
}
add_action('admin_init', 'csi_download_import_template');

/**
 * Escape CSV field
 */
function csi_escape_csv_field($field) {
    // Handle null values
    if ($field === null) {
        return '';
    }
    
    $field = (string) $field;
    
    if ($field === '') {
        return $field;
    }
    
    // Check for Excel formulas
    if (preg_match('/"=HYPERLINK\(/', $field)) {
        return $field;
    }
    
    // Clean the field - handle null/empty values safely
    $field = wp_strip_all_tags($field, true);
    $field = trim($field);
    
    // Safely replace newlines (handle null)
    if ($field !== null && $field !== '') {
        $field = str_replace(["\r", "\n"], ' ', $field);
    }
    
    // Escape if needed - check for null/empty before strpos
    if ($field && $field !== '' && (strpos($field, ',') !== false || strpos($field, '"') !== false || strpos($field, "\n") !== false)) {
        $field = '"' . str_replace('"', '""', $field) . '"';
    }
    
    return $field;
}

/**
 * Handle CSV import
 */
function csi_handle_user_import() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    if (!isset($_POST['import_users']) || !check_admin_referer('import_users_nonce')) {
        return;
    }
    
    if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
        csi_notify_error(__('Error uploading file. Please try again.', 'custom-signup-plugin'));
        return;
    }
    
    $file = $_FILES['import_file'];
    
    if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'csv') {
        csi_notify_error(__('Invalid file type. Please upload a CSV file.', 'custom-signup-plugin'));
        return;
    }
    
    if ($file['size'] > 10 * 1024 * 1024) {
        csi_notify_error(__('File size exceeds 10MB limit.', 'custom-signup-plugin'));
        return;
    }
    
    // Open and parse CSV file
    $handle = fopen($file['tmp_name'], 'r');
    if ($handle === false) {
        csi_notify_error(__('Error reading CSV file.', 'custom-signup-plugin'));
        return;
    }
    
    // Read header row
    $headers = fgetcsv($handle);
    if ($headers === false) {
        fclose($handle);
        csi_notify_error(__('Invalid CSV format. Could not read headers.', 'custom-signup-plugin'));
        return;
    }
    
    $headers = array_map('strtolower', array_map('trim', $headers));
    $column_map = [];
    
    // Find required columns
    foreach (['email', 'full name'] as $required) {
        foreach ($headers as $index => $header) {
            if (stripos($header, $required) !== false) {
                $column_map[$required] = $index;
                break;
            }
        }
        if (!isset($column_map[$required])) {
            fclose($handle);
            csi_notify_error(sprintf(__('Required column not found: %s', 'custom-signup-plugin'), $required));
            return;
        }
    }
    
    // Map optional columns
    $optional_columns = [
        'title' => 'title',
        'specialty' => 'specialty',
        'phone' => 'phone',
        'date of birth' => 'dob',
        'home address' => 'home_address',
        'work address' => 'work_address',
        'institute' => 'institute',
        'country' => 'country',
        'membership type' => 'membership',
        'registration type' => 'registration_type',
        'membership number' => 'membership_number',
        'signature' => 'signature',
        'payment method' => 'payment_method',
        'payment status' => 'payment_status',
        'personal photo' => 'photo',
        'id scans' => 'id_scans',
        'cv' => 'cv',
        'student card' => 'student_card',
        'payment receipt' => 'payment_receipt',
        'status' => 'user_status',
        'generated id' => 'membership_number',
        'membership number' => 'membership_number'
    ];
    
    foreach ($optional_columns as $csv_header => $meta_key) {
        foreach ($headers as $index => $header) {
            if (stripos($header, $csv_header) !== false) {
                $column_map[$meta_key] = $index;
                break;
            }
        }
    }
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('=== CSI Import Started ===');
        error_log('CSI Import - Column Map: ' . print_r($column_map, true));
    }
    
    $imported_count = 0;
    $updated_count = 0;
    $error_count = 0;
    $errors = [];
    $existing_emails = []; // Track existing emails
    
    // Process each row
    $row_num = 1;
    while (($row = fgetcsv($handle)) !== false) {
        $row_num++;
        
        if (empty(array_filter($row))) {
            continue;
        }
        
        $email = isset($row[$column_map['email']]) ? sanitize_email(trim($row[$column_map['email']])) : '';
        if (empty($email) || !is_email($email)) {
            $errors[] = sprintf(__('Row %d: Invalid or missing email address.', 'custom-signup-plugin'), $row_num);
            $error_count++;
            continue;
        }
        
        $fullname = isset($row[$column_map['full name']]) ? sanitize_text_field(trim($row[$column_map['full name']])) : '';
        if (empty($fullname)) {
            $errors[] = sprintf(__('Row %d: Missing full name.', 'custom-signup-plugin'), $row_num);
            $error_count++;
            continue;
        }
        
        $existing_user = email_exists($email);
        
        if ($existing_user) {
            $user_id = $existing_user;
            $updated_count++;
            $existing_emails[] = $email; // Track existing email
        } else {
            $username = sanitize_user(current(explode('@', $email)), true);
            $original_username = $username;
            $counter = 1;
            while (username_exists($username)) {
                $username = $original_username . $counter++;
            }
            
            $user_id = wp_insert_user([
                'user_login' => $username,
                'user_email' => $email,
                'user_pass' => wp_generate_password(12, false),
                'display_name' => $fullname,
                'role' => 'subscriber'
            ]);
            
            if (is_wp_error($user_id)) {
                $errors[] = sprintf(__('Row %d: Failed to create user - %s', 'custom-signup-plugin'), $row_num, $user_id->get_error_message());
                $error_count++;
                continue;
            }
            $imported_count++;
        }
        
        update_user_meta($user_id, 'fullname', $fullname);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("CSI Import - Row $row_num: User ID $user_id, Email: $email, Name: $fullname");
        }
        
        // Update meta fields
        $meta_fields = [
            'title', 'specialty', 'phone', 'dob', 'home_address', 'work_address',
            'institute', 'country', 'registration_type', 'membership_number',
            'signature', 'payment_method'
        ];
        
        foreach ($meta_fields as $field) {
            if (isset($column_map[$field]) && isset($row[$column_map[$field]])) {
                $value = trim($row[$column_map[$field]]);
                if (!empty($value)) {
                    update_user_meta($user_id, $field, sanitize_text_field($value));
                }
            }
        }
        
        // Handle membership type
        $membership_value = '';
        if (isset($column_map['membership']) && isset($row[$column_map['membership']])) {
            $membership_label = trim($row[$column_map['membership']]);
            if (!empty($membership_label)) {
                $membership_value = csi_convert_membership_label_to_value($membership_label);
                update_user_meta($user_id, 'membership', $membership_value);
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("CSI Import - Row $row_num: Membership '$membership_label' -> '$membership_value'");
                }
            } else {
                $errors[] = sprintf(__('Row %d: Empty membership type for "%s" (%s). User will NOT appear in admin table.', 'custom-signup-plugin'), $row_num, $fullname, $email);
            }
        } else {
            $errors[] = sprintf(__('Row %d: Membership type column not found for "%s" (%s). User will NOT appear in admin table.', 'custom-signup-plugin'), $row_num, $fullname, $email);
        }
        
        wp_cache_delete($user_id, 'user_meta');
        clean_user_cache($user_id);
        
        // Handle file URLs
        foreach (['photo' => 'personal photo', 'cv' => 'cv', 'payment_receipt' => 'payment receipt', 'student_card' => 'student card'] as $meta_key => $csv_key) {
            if (isset($column_map[$meta_key]) && isset($row[$column_map[$meta_key]])) {
                $url = trim($row[$column_map[$meta_key]]);
                if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                    update_user_meta($user_id, $meta_key, esc_url_raw($url));
                }
            }
        }
        
        // Handle ID scans (comma-separated URLs)
        if (isset($column_map['id_scans']) && isset($row[$column_map['id_scans']])) {
            $id_scan_urls = array_filter(array_map('trim', explode(',', trim($row[$column_map['id_scans']]))), function($url) {
                return !empty($url) && filter_var($url, FILTER_VALIDATE_URL);
            });
            if (!empty($id_scan_urls)) {
                update_user_meta($user_id, 'id_scans', array_map('esc_url_raw', $id_scan_urls));
            }
        }
        
        // Handle status
        if (isset($column_map['user_status']) && isset($row[$column_map['user_status']])) {
            $status = strtolower(trim($row[$column_map['user_status']]));
            if (in_array($status, ['active', 'not_active'])) {
                update_user_meta($user_id, 'user_status', $status);
            } elseif (!$existing_user) {
                update_user_meta($user_id, 'user_status', 'not_active');
            }
        } elseif (!$existing_user) {
            update_user_meta($user_id, 'user_status', 'not_active');
        }
        
        // Handle payment status
        if (isset($column_map['payment_status']) && isset($row[$column_map['payment_status']])) {
            $payment_status = strtolower(trim($row[$column_map['payment_status']]));
            if (in_array($payment_status, ['paid', 'pending', 'declined'])) {
                update_user_meta($user_id, 'payment_status', $payment_status);
            } elseif (!$existing_user) {
                update_user_meta($user_id, 'payment_status', 'pending');
            }
        } elseif (!$existing_user) {
            update_user_meta($user_id, 'payment_status', 'pending');
        }
        
        // Handle Membership Number - auto-generate if empty
        $membership_number = '';
        if (isset($column_map['membership_number']) && isset($row[$column_map['membership_number']])) {
            $membership_number = trim($row[$column_map['membership_number']]);
        }
        
        // Auto-generate if empty and membership type exists
        if (empty($membership_number) && !empty($membership_value)) {
            if (function_exists('csi_assign_membership_number')) {
                $membership_number = csi_assign_membership_number($user_id, $membership_value);
            } elseif (function_exists('csi_assign_generated_id')) {
                // Backward compatibility
                $membership_number = csi_assign_generated_id($user_id, $membership_value);
            }
        } elseif (!empty($membership_number)) {
            // If provided in CSV, use it (but check for uniqueness)
            global $wpdb;
            $existing_user_with_id = $wpdb->get_var($wpdb->prepare(
                "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'membership_number' AND meta_value = %s AND user_id != %d",
                $membership_number,
                $user_id
            ));
            
            if (!$existing_user_with_id) {
                update_user_meta($user_id, 'membership_number', sanitize_text_field($membership_number));
            } else {
                // If duplicate, auto-generate instead
                if (function_exists('csi_assign_membership_number') && !empty($membership_value)) {
                    $membership_number = csi_assign_membership_number($user_id, $membership_value);
                } elseif (function_exists('csi_assign_generated_id') && !empty($membership_value)) {
                    // Backward compatibility
                    $membership_number = csi_assign_generated_id($user_id, $membership_value);
                }
            }
        }
    }
    
    fclose($handle);
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("=== CSI Import Completed: $imported_count imported, $updated_count updated, $error_count errors ===");
    }
    
    $message = sprintf(
        '<strong>%s</strong> %d %s, %d %s, %d %s.',
        __('Import completed:', 'custom-signup-plugin'),
        $imported_count,
        __('new users imported', 'custom-signup-plugin'),
        $updated_count,
        __('users updated', 'custom-signup-plugin'),
        $error_count,
        __('errors', 'custom-signup-plugin')
    );
    
    if ($imported_count > 0 || $updated_count > 0) {
        $message .= '<br><br><strong>✓ ' . __('Successfully processed users should now appear in the admin table.', 'custom-signup-plugin') . '</strong>';
        $message .= '<br><strong>' . __('Note:', 'custom-signup-plugin') . '</strong> ' . __('Users will only appear if they have a valid membership type (student, early_investigator, postdoctoral, scientist, industry, or honorary).', 'custom-signup-plugin');
    }
    
    // Add notification about existing emails
    if (!empty($existing_emails)) {
        $existing_count = count($existing_emails);
        $message .= '<br><br><strong>' . sprintf(_n('%d email already exists and was updated.', '%d emails already exist and were updated.', $existing_count, 'custom-signup-plugin'), $existing_count) . '</strong>';
    }
    
    if (!empty($errors) && count($errors) <= 10) {
        $message .= '<br><br><strong>' . __('Errors/Warnings:', 'custom-signup-plugin') . '</strong><ul>';
        foreach ($errors as $error) {
            $message .= '<li>' . esc_html($error) . '</li>';
        }
        $message .= '</ul>';
    } elseif (!empty($errors)) {
        $message .= '<br><br><strong>' . __('Note:', 'custom-signup-plugin') . '</strong> ' . sprintf(__('%d errors occurred. First 10 errors shown above.', 'custom-signup-plugin'), count($errors));
    }
    
    if ($imported_count === 0 && $updated_count === 0) {
        $message .= '<br><br><strong>' . __('No users were processed. Please check:', 'custom-signup-plugin') . '</strong><ul>';
        $message .= '<li>' . __('CSV file format is correct', 'custom-signup-plugin') . '</li>';
        $message .= '<li>' . __('CSV contains data rows (not just headers)', 'custom-signup-plugin') . '</li>';
        $message .= '<li>' . __('Email addresses are valid', 'custom-signup-plugin') . '</li>';
        $message .= '<li>' . __('Membership Type column exists and has valid values', 'custom-signup-plugin') . '</li>';
        $message .= '</ul>';
    }
    
    if ($error_count > 0) {
        csi_notify_warning($message);
    } else {
        csi_notify_success($message);
    }
}

/**
 * Convert membership label to value
 */
function csi_convert_membership_label_to_value($label) {
    $label = trim(strtolower($label));
    $mapping = [
        'student membership' => 'student',
        'early investigator membership' => 'early_investigator',
        'postdoctoral membership' => 'postdoctoral',
        'scientist membership' => 'scientist',
        'industry members' => 'industry',
        'honorary membership' => 'honorary',
        'student' => 'student',
        'early_investigator' => 'early_investigator',
        'early investigator' => 'early_investigator',
        'postdoctoral' => 'postdoctoral',
        'scientist' => 'scientist',
        'industry' => 'industry',
        'honorary' => 'honorary'
    ];
    return isset($mapping[$label]) ? $mapping[$label] : $label;
}

add_action('admin_init', 'csi_handle_user_import');
