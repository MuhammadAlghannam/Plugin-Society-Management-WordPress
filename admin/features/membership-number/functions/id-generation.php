<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Membership Number Generation Functions
 */

/**
 * Get all abbreviations from database
 */
function csi_get_all_abbreviations() {
    global $wpdb;
    $table_name = CSI_Database::get_table_name('csi_membership_numbers');
    
    if (!CSI_Database::table_exists('csi_membership_numbers')) {
        CSI_Database::create_tables();
    }
    
    return $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY membership_type ASC", ARRAY_A) ?: [];
}

/**
 * Get or create abbreviation record
 */
function csi_get_abbreviation($membership_type) {
    global $wpdb;
    $table_name = CSI_Database::get_table_name('csi_membership_numbers');
    
    if (!CSI_Database::table_exists('csi_membership_numbers')) {
        CSI_Database::create_tables();
    }
    
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE membership_type = %s", $membership_type), ARRAY_A);
}

/**
 * Get abbreviation data for a specific membership type (alias for compatibility)
 */
function csi_get_abbreviation_data($membership_type) {
    return csi_get_abbreviation($membership_type);
}

/**
 * Get abbreviation string only
 */
function csi_get_abbreviation_string($membership_type) {
    $record = csi_get_abbreviation($membership_type);
    return $record ? $record['abbreviation'] : '';
}

/**
 * Get last number for a membership type
 */
function csi_get_last_number($membership_type) {
    $record = csi_get_abbreviation($membership_type);
    return $record ? intval($record['last_number']) : 0;
}

/**
 * Update last number for a membership type
 */
function csi_update_last_number($membership_type, $number) {
    global $wpdb;
    $table_name = CSI_Database::get_table_name('csi_membership_numbers');
    
    $result = $wpdb->update(
        $table_name,
        ['last_number' => intval($number)],
        ['membership_type' => $membership_type],
        ['%d'],
        ['%s']
    );
    
    return $result !== false;
}

/**
 * Save or update abbreviation (like old code)
 */
function csi_save_abbreviation($membership_type, $abbreviation) {
    return csi_update_abbreviation($membership_type, $abbreviation, null);
}

/**
 * Delete abbreviation entry
 */
function csi_delete_abbreviation($membership_type) {
    global $wpdb;
    $table_name = CSI_Database::get_table_name('csi_membership_numbers');
    
    $result = $wpdb->delete(
        $table_name,
        ['membership_type' => $membership_type],
        ['%s']
    );
    
    return $result !== false;
}

/**
 * Generate next ID for a membership type (like old code)
 * Format: {abbreviation}{number}
 */
function csi_generate_next_id($membership_type) {
    $abbreviation = csi_get_abbreviation_string($membership_type);
    
    if (empty($abbreviation)) {
        return false;
    }
    
    $last_number = csi_get_last_number($membership_type);
    $next_number = $last_number + 1;
    
    // Update last number in database
    csi_update_last_number($membership_type, $next_number);
    
    // Return formatted ID: abbreviation + number (4 digits padded)
    return strtoupper($abbreviation) . str_pad($next_number, 4, '0', STR_PAD_LEFT);
}

/**
 * Update abbreviation and last number
 */
function csi_update_abbreviation($membership_type, $abbreviation, $last_number = null) {
    global $wpdb;
    $table_name = CSI_Database::get_table_name('csi_membership_numbers');
    
    $existing = csi_get_abbreviation($membership_type);
    
    if ($existing) {
        $data = [
            'abbreviation' => $abbreviation
        ];
        
        if ($last_number !== null) {
            $data['last_number'] = intval($last_number);
        }
        
        $result = $wpdb->update(
            $table_name,
            $data,
            ['membership_type' => $membership_type]
        );
        
        return $result !== false;
    } else {
        $result = $wpdb->insert(
            $table_name,
            [
                'membership_type' => $membership_type,
                'abbreviation' => $abbreviation,
                'last_number' => $last_number !== null ? intval($last_number) : 0
            ]
        );
        
        return $result !== false;
    }
}

/**
 * Generate membership number for user
 */
function csi_assign_membership_number($user_id, $membership_type) {
    global $wpdb;
    
    if (!CSI_Database::table_exists('csi_membership_numbers')) {
        CSI_Database::create_tables();
    }
    
    $record = csi_get_abbreviation($membership_type);
    
    if (!$record) {
        $default_abbreviations = [
            'student' => 'STU', 'early_investigator' => 'EI', 'postdoctoral' => 'PD',
            'scientist' => 'SCI', 'industry' => 'IND', 'honorary' => 'HON'
        ];
        $abbreviation = isset($default_abbreviations[$membership_type]) ? $default_abbreviations[$membership_type] : 'MEM';
        csi_update_abbreviation($membership_type, $abbreviation, 0);
        $record = csi_get_abbreviation($membership_type);
    }
    
    if (!$record) {
        return false;
    }
    
    $new_number = intval($record['last_number']) + 1;
    $membership_number = strtoupper($record['abbreviation']) . str_pad($new_number, 4, '0', STR_PAD_LEFT);
    
    $wpdb->update(
        CSI_Database::get_table_name('csi_membership_numbers'),
        ['last_number' => $new_number],
        ['membership_type' => $membership_type]
    );
    
    update_user_meta($user_id, 'membership_number', $membership_number);
    return $membership_number;
}

/**
 * Backward compatibility: alias for old function name
 * @deprecated Use csi_assign_membership_number instead
 */
function csi_assign_generated_id($user_id, $membership_type) {
    return csi_assign_membership_number($user_id, $membership_type);
}

/**
 * Generate all IDs for a membership type
 * Resets counter and regenerates all IDs (overwrites existing)
 */
function csi_generate_all_for_membership($membership_type) {
    csi_update_last_number($membership_type, 0);
    
    $users = get_users([
        'meta_key' => 'membership',
        'meta_value' => $membership_type,
        'number' => -1
    ]);
    
    $count = 0;
    foreach ($users as $user) {
        $generated_id = csi_generate_next_id($membership_type);
        if ($generated_id) {
            update_user_meta($user->ID, 'membership_number', $generated_id);
            $count++;
        }
    }
    
    return $count;
}

/**
 * Generate IDs for new users only (users without membership_number)
 */
function csi_generate_for_new_users($membership_type) {
    $users = get_users([
        'meta_key' => 'membership',
        'meta_value' => $membership_type,
        'number' => -1
    ]);
    
    $count = 0;
    foreach ($users as $user) {
        if (empty(get_user_meta($user->ID, 'membership_number', true))) {
            $generated_id = csi_generate_next_id($membership_type);
            if ($generated_id) {
                update_user_meta($user->ID, 'membership_number', $generated_id);
                $count++;
            }
        }
    }
    
    return $count;
}

/**
 * AJAX: Generate membership numbers for users without membership numbers
 */
function csi_ajax_generate_missing_ids() {
    check_ajax_referer('csi_membership_number_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Insufficient permissions', 'custom-signup-plugin')]);
    }
    
    global $wpdb;
    
    // Get users with membership but no membership_number
    $membership_types = ['student', 'early_investigator', 'postdoctoral', 'scientist', 'industry', 'honorary'];
    $placeholders = implode(',', array_fill(0, count($membership_types), '%s'));
    
    $users = $wpdb->get_results($wpdb->prepare("
        SELECT u.ID, um.meta_value as membership
        FROM {$wpdb->users} u
        JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
        LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'membership_number'
        WHERE um.meta_key = 'membership'
          AND um.meta_value IN ($placeholders)
          AND (um2.meta_value IS NULL OR um2.meta_value = '')
        LIMIT 100
    ", $membership_types), ARRAY_A);
    
    $generated_count = 0;
    foreach ($users as $user) {
        if (csi_assign_membership_number($user['ID'], $user['membership'])) {
            $generated_count++;
        }
    }
    
    wp_send_json_success([
        'message' => sprintf(__('%d membership numbers generated successfully.', 'custom-signup-plugin'), $generated_count),
        'count' => $generated_count
    ]);
}
add_action('wp_ajax_csi_generate_missing_ids', 'csi_ajax_generate_missing_ids');
