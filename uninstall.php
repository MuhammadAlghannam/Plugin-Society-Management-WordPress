<?php
/**
 * Uninstall script for Society Management
 * 
 * This file is executed when the plugin is deleted
 */

// Exit if not called from WordPress
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete database tables
global $wpdb;

$tables = [
    $wpdb->prefix . 'csi_membership_numbers',
    $wpdb->prefix . 'csi_email_templates',
    $wpdb->prefix . 'csi_email_reminders'
];

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$table}");
}

// Clear scheduled events
wp_clear_scheduled_hook('csi_daily_reminder_check');

// Delete transients
delete_transient('csi_admin_notifications');
delete_transient('csi_dashboard_stats');

// Optionally delete user meta (commented out for safety)
// $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'csi_%'");
