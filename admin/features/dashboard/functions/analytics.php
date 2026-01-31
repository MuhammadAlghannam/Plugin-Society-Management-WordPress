<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Analytics Functions
 * Calculate and return dashboard statistics
 */

/**
 * Get all dashboard statistics
 * 
 * @return array Statistics data
 */
function csi_get_dashboard_stats() {
    // Check cache first
    $cache_key = 'csi_dashboard_stats_' . date('Y-m-d');
    $cached = get_transient($cache_key);
    
    if ($cached !== false) {
        return $cached;
    }
    
    $stats = [
        'users' => csi_get_user_stats(),
        'payments' => csi_get_payment_stats(),
        'membership_types' => csi_get_membership_distribution(),
        'registration_types' => csi_get_registration_type_stats(),
        'time_based' => csi_get_time_based_stats(),
        'payment_methods' => csi_get_payment_method_stats(),
        'countries' => csi_get_country_distribution(),
        'recent_activity' => csi_get_recent_activity()
    ];
    
    // Cache for 5 minutes
    set_transient($cache_key, $stats, 300);
    
    return $stats;
}

/**
 * Get user statistics
 */
function csi_get_user_stats() {
    global $wpdb;
    
    $membership_types = ['student', 'early_investigator', 'postdoctoral', 'scientist', 'industry', 'honorary'];
    $placeholders = implode(',', array_fill(0, count($membership_types), '%s'));
    
    $total_query = $wpdb->prepare("
        SELECT COUNT(DISTINCT u.ID) as total
        FROM {$wpdb->users} u
        JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
        WHERE um.meta_key = 'membership'
          AND um.meta_value IN ($placeholders)
    ", $membership_types);
    
    $total = $wpdb->get_var($total_query);
    
    $active_query = $wpdb->prepare("
        SELECT COUNT(DISTINCT u.ID) as active
        FROM {$wpdb->users} u
        JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id
        JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id
        WHERE um1.meta_key = 'membership'
          AND um1.meta_value IN ($placeholders)
          AND um2.meta_key = 'user_status'
          AND um2.meta_value = 'active'
    ", $membership_types);
    
    $active = $wpdb->get_var($active_query);
    $inactive = $total - $active;
    $active_percentage = $total > 0 ? round(($active / $total) * 100, 1) : 0;
    
    return [
        'total' => (int) $total,
        'active' => (int) $active,
        'inactive' => (int) $inactive,
        'active_percentage' => $active_percentage
    ];
}

/**
 * Get payment statistics
 */
function csi_get_payment_stats() {
    global $wpdb;
    
    $membership_types = ['student', 'early_investigator', 'postdoctoral', 'scientist', 'industry', 'honorary'];
    $placeholders = implode(',', array_fill(0, count($membership_types), '%s'));
    
    $paid_query = $wpdb->prepare("
        SELECT COUNT(DISTINCT u.ID) as paid
        FROM {$wpdb->users} u
        JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id
        JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id
        WHERE um1.meta_key = 'membership'
          AND um1.meta_value IN ($placeholders)
          AND um2.meta_key = 'payment_status'
          AND um2.meta_value = 'paid'
    ", $membership_types);
    
    $paid = $wpdb->get_var($paid_query);
    
    $pending_query = $wpdb->prepare("
        SELECT COUNT(DISTINCT u.ID) as pending
        FROM {$wpdb->users} u
        JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id
        JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id
        WHERE um1.meta_key = 'membership'
          AND um1.meta_value IN ($placeholders)
          AND um2.meta_key = 'payment_status'
          AND um2.meta_value = 'pending'
    ", $membership_types);
    
    $pending = $wpdb->get_var($pending_query);
    
    $inreview_query = $wpdb->prepare("
        SELECT COUNT(DISTINCT u.ID) as inreview
        FROM {$wpdb->users} u
        JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id
        JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id
        WHERE um1.meta_key = 'membership'
          AND um1.meta_value IN ($placeholders)
          AND um2.meta_key = 'payment_status'
          AND um2.meta_value = 'inreview'
    ", $membership_types);
    
    $inreview = $wpdb->get_var($inreview_query);
    
    $declined_query = $wpdb->prepare("
        SELECT COUNT(DISTINCT u.ID) as declined
        FROM {$wpdb->users} u
        JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id
        JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id
        WHERE um1.meta_key = 'membership'
          AND um1.meta_value IN ($placeholders)
          AND um2.meta_key = 'payment_status'
          AND um2.meta_value = 'declined'
    ", $membership_types);
    
    $declined = $wpdb->get_var($declined_query);
    
    $total = $paid + $pending + $inreview + $declined;
    $success_rate = $total > 0 ? round(($paid / $total) * 100, 1) : 0;
    
    return [
        'paid' => (int) $paid,
        'pending' => (int) $pending,
        'inreview' => (int) $inreview,
        'declined' => (int) $declined,
        'total' => (int) $total,
        'success_rate' => $success_rate
    ];
}

/**
 * Get membership type distribution
 */
function csi_get_membership_distribution() {
    global $wpdb;
    
    $membership_types = ['student', 'early_investigator', 'postdoctoral', 'scientist', 'industry', 'honorary'];
    $distribution = [];
    
    foreach ($membership_types as $type) {
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT user_id)
            FROM {$wpdb->usermeta}
            WHERE meta_key = 'membership'
              AND meta_value = %s
        ", $type));
        
        $distribution[$type] = (int) $count;
    }
    
    return $distribution;
}

/**
 * Get registration type statistics
 */
function csi_get_registration_type_stats() {
    global $wpdb;
    
    $types = ['new', 'renew', 'student'];
    $stats = [];
    
    foreach ($types as $type) {
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT user_id)
            FROM {$wpdb->usermeta}
            WHERE meta_key = 'registration_type'
              AND meta_value = %s
        ", $type));
        
        $stats[$type] = (int) $count;
    }
    
    return $stats;
}

/**
 * Get time-based statistics
 */
function csi_get_time_based_stats() {
    global $wpdb;
    
    $this_month_start = date('Y-m-01');
    $this_month_end = date('Y-m-t');
    $last_month_start = date('Y-m-01', strtotime('-1 month'));
    $last_month_end = date('Y-m-t', strtotime('-1 month'));
    $this_year_start = date('Y-01-01');
    $this_year_end = date('Y-12-31');
    
    $this_month = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT ID)
        FROM {$wpdb->users}
        WHERE DATE(user_registered) BETWEEN %s AND %s
    ", $this_month_start, $this_month_end));
    
    $last_month = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT ID)
        FROM {$wpdb->users}
        WHERE DATE(user_registered) BETWEEN %s AND %s
    ", $last_month_start, $last_month_end));
    
    $this_year = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT ID)
        FROM {$wpdb->users}
        WHERE DATE(user_registered) BETWEEN %s AND %s
    ", $this_year_start, $this_year_end));
    
    $growth_rate = $last_month > 0 ? round((($this_month - $last_month) / $last_month) * 100, 1) : 0;
    
    // Get last 12 months data for chart
    $monthly_data = [];
    for ($i = 11; $i >= 0; $i--) {
        $month_start = date('Y-m-01', strtotime("-$i months"));
        $month_end = date('Y-m-t', strtotime("-$i months"));
        $month_label = date('M Y', strtotime("-$i months"));
        
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT ID)
            FROM {$wpdb->users}
            WHERE DATE(user_registered) BETWEEN %s AND %s
        ", $month_start, $month_end));
        
        $monthly_data[] = [
            'month' => $month_label,
            'count' => (int) $count
        ];
    }
    
    return [
        'this_month' => (int) $this_month,
        'last_month' => (int) $last_month,
        'this_year' => (int) $this_year,
        'growth_rate' => $growth_rate,
        'monthly_data' => $monthly_data
    ];
}

/**
 * Get payment method statistics
 */
function csi_get_payment_method_stats() {
    global $wpdb;
    
    $methods = $wpdb->get_results("
        SELECT meta_value as method, COUNT(DISTINCT user_id) as count
        FROM {$wpdb->usermeta}
        WHERE meta_key = 'payment_method'
          AND meta_value != ''
        GROUP BY meta_value
        ORDER BY count DESC
    ", ARRAY_A);
    
    $stats = [];
    foreach ($methods as $method) {
        $stats[$method['method']] = (int) $method['count'];
    }
    
    return $stats;
}

/**
 * Get country distribution
 */
function csi_get_country_distribution() {
    global $wpdb;
    
    $countries = $wpdb->get_results("
        SELECT meta_value as country, COUNT(DISTINCT user_id) as count
        FROM {$wpdb->usermeta}
        WHERE meta_key = 'country'
          AND meta_value != ''
        GROUP BY meta_value
        ORDER BY count DESC
        LIMIT 10
    ", ARRAY_A);
    
    $distribution = [];
    foreach ($countries as $country) {
        $distribution[$country['country']] = (int) $country['count'];
    }
    
    return $distribution;
}

/**
 * Get recent activity
 */
function csi_get_recent_activity() {
    global $wpdb;
    
    $membership_types = ['student', 'early_investigator', 'postdoctoral', 'scientist', 'industry', 'honorary'];
    $placeholders = implode(',', array_fill(0, count($membership_types), '%s'));
    
    $recent_registrations = $wpdb->get_results($wpdb->prepare("
        SELECT u.ID, u.user_registered, um.meta_value as fullname
        FROM {$wpdb->users} u
        JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id
        LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'fullname'
        WHERE um1.meta_key = 'membership'
          AND um1.meta_value IN ($placeholders)
        ORDER BY u.user_registered DESC
        LIMIT 10
    ", $membership_types), ARRAY_A);
    
    return [
        'registrations' => $recent_registrations
    ];
}
