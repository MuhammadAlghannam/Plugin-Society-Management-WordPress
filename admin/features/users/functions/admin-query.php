<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * User Query Functions
 * Handles user queries, filtering, and pagination
 */

/**
 * Get filtered and paginated users for admin table
 * 
 * @param array $args Query arguments
 * @return array Users and pagination data
 */
function csi_get_admin_users($args = []) {
    $defaults = [
        'search' => '',
        'filter' => '', // Filter by membership type
        'payment_status_filter' => '', // Filter by payment status
        'user_status_filter' => '', // Filter by user status
        'page' => 1,
        'per_page' => 20,
        'orderby' => 'registered',
        'order' => 'DESC'
    ];
    
    $args = wp_parse_args($args, $defaults);
    $search = sanitize_text_field($args['search']);
    $filter = sanitize_text_field($args['filter']);
    $payment_status_filter = sanitize_text_field($args['payment_status_filter']);
    $user_status_filter = sanitize_text_field($args['user_status_filter']);
    $page = max(1, intval($args['page']));
    $per_page = intval($args['per_page']); // Allow -1 for all users
    $offset = ($per_page > 0) ? (($page - 1) * $per_page) : 0;
    
    // Base query args
    $membership_types = [
        'student',
        'early_investigator',
        'postdoctoral',
        'scientist',
        'industry',
        'honorary'
    ];
    
    // Apply filter if provided
    if (!empty($filter)) {
        $membership_types = [$filter];
    }
    
    $query_args = [
        'meta_key' => 'membership',
        'meta_value' => $membership_types,
        'meta_compare' => 'IN',
        'orderby' => $args['orderby'],
        'order' => $args['order']
    ];
    
    // Only apply pagination if per_page is positive
    if ($per_page > 0) {
        $query_args['number'] = $per_page;
        $query_args['offset'] = $offset;
    } else {
        // Get all users (no pagination)
        $query_args['number'] = -1;
    }
    
    // Add search if provided
    // Note: We don't use WP_User_Query 'search' parameter here because it restricts results to core columns (login, email, nicename).
    // We fetch all users matching the filter and then search in PHP (below) to support meta fields like membership_number.
    
    // Get all matching users first (for search filtering)
    $count_args = $query_args;
    $count_args['number'] = -1;
    $count_args['offset'] = 0;
    $all_users = get_users($count_args);
    
    // Filter by meta fields (search, payment status, user status)
    $filtered_all = [];
    foreach ($all_users as $user) {
        // Filter by payment status
        if (!empty($payment_status_filter)) {
            $user_payment_status = get_user_meta($user->ID, 'payment_status', true) ?: 'pending';
            if ($user_payment_status !== $payment_status_filter) {
                continue;
            }
        }
        
        // Filter by user status
        if (!empty($user_status_filter)) {
            $user_status = get_user_meta($user->ID, 'user_status', true) ?: 'not_active';
            if ($user_status !== $user_status_filter) {
                continue;
            }
        }
        
        // Filter by search if provided
        if (!empty($search)) {
            $search_lower = trim(strtolower($search));
            $membership_labels = [
                'student' => 'student membership',
                'early_investigator' => 'early investigator membership',
                'postdoctoral' => 'postdoctoral membership',
                'scientist' => 'scientist membership',
                'industry' => 'industry members',
                'honorary' => 'honorary membership'
            ];
            
            $fullname_meta = get_user_meta($user->ID, 'fullname', true);
            $title_meta = get_user_meta($user->ID, 'title', true);
            $specialty_meta = get_user_meta($user->ID, 'specialty', true);
            $phone_meta = get_user_meta($user->ID, 'phone', true);
            $membership_meta = get_user_meta($user->ID, 'membership', true);
            
            // Ensure all values are strings before using strtolower and strpos
            $fullname = $fullname_meta ? strtolower((string) $fullname_meta) : '';
            $title = $title_meta ? strtolower((string) $title_meta) : '';
            $specialty = $specialty_meta ? strtolower((string) $specialty_meta) : '';
            $phone = $phone_meta ? strtolower((string) $phone_meta) : '';
            $membership = $membership_meta ? strtolower((string) $membership_meta) : '';
            $membership_label = isset($membership_labels[$membership]) ? $membership_labels[$membership] : ($membership ?: '');
            
            // Get membership_number
            $membership_number = get_user_meta($user->ID, 'membership_number', true);
            if (empty($membership_number)) {
                $membership_number = (string) $user->ID;
            }
            $membership_number_lower = strtolower((string) $membership_number);
            
            $user_id_str = (string) $user->ID;
            $user_email_lower = $user->user_email ? strtolower($user->user_email) : '';
            
            $matches_search = (
                ($fullname && strpos($fullname, $search_lower) !== false) ||
                ($title && strpos($title, $search_lower) !== false) ||
                ($specialty && strpos($specialty, $search_lower) !== false) ||
                ($phone && strpos($phone, $search_lower) !== false) ||
                ($membership_label && strpos($membership_label, $search_lower) !== false) ||
                ($user_email_lower && strpos($user_email_lower, $search_lower) !== false) ||
                $membership_number_lower === $search_lower ||
                ($membership_number_lower && strpos($membership_number_lower, $search_lower) !== false) ||
                $user_id_str === $search_lower
            );
            
            if (!$matches_search) {
                continue;
            }
        }
        
        $filtered_all[] = $user;
    }
    $all_users = $filtered_all;
    
    $total_users = count($all_users);
    
    // Apply pagination only if per_page is positive
    if ($per_page > 0) {
        $users = array_slice($all_users, $offset, $per_page);
        $total_pages = ceil($total_users / $per_page);
    } else {
        // Return all users
        $users = $all_users;
        $total_pages = 1;
    }
    
    return [
        'users' => $users,
        'total' => $total_users,
        'page' => $page,
        'per_page' => $per_page > 0 ? $per_page : $total_users,
        'total_pages' => $total_pages
    ];
}

/**
 * Get membership type label
 */
function csi_get_membership_type_label($type) {
    $types = [
        'student' => __('Student membership', 'custom-signup-plugin'),
        'early_investigator' => __('Early investigator membership', 'custom-signup-plugin'),
        'postdoctoral' => __('Postdoctoral membership', 'custom-signup-plugin'),
        'scientist' => __('Scientist membership', 'custom-signup-plugin'),
        'industry' => __('Industry members', 'custom-signup-plugin'),
        'honorary' => __('Honorary membership', 'custom-signup-plugin')
    ];
    return isset($types[$type]) ? $types[$type] : $type;
}
