<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database Management System
 * Handles all database table creation and management
 */
class CSI_Database {
    
    /**
     * Create all database tables
     */
    public static function create_tables() {
        self::create_membership_numbers_table();
        self::create_email_templates_table();
        self::create_email_reminders_table();
        self::create_membership_history_table();
        self::migrate_generated_ids_to_membership_numbers();
    }
    
    /**
     * Create membership numbers table
     */
    private static function create_membership_numbers_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'csi_membership_numbers';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id INT(11) NOT NULL AUTO_INCREMENT,
            membership_type VARCHAR(50) NOT NULL,
            abbreviation VARCHAR(10) NOT NULL,
            last_number INT(11) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY membership_type (membership_type)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Migrate data from old table to new table (one-time migration)
     */
    private static function migrate_generated_ids_to_membership_numbers() {
        global $wpdb;
        
        $old_table = $wpdb->prefix . 'csi_generated_ids';
        $new_table = $wpdb->prefix . 'csi_membership_numbers';
        
        // Check if old table exists
        $old_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
            DB_NAME,
            $old_table
        ));
        
        // Check if new table has data
        $new_count = $wpdb->get_var("SELECT COUNT(*) FROM $new_table");
        
        // Migrate data if old table exists and new table is empty
        if ($old_exists && $new_count == 0) {
            // Migrate data from old table
            $wpdb->query("INSERT INTO $new_table SELECT * FROM $old_table");
        }
        
        // Always migrate user meta if needed (safe to run multiple times)
        $migrated = get_option('csi_membership_number_meta_migrated', false);
        if (!$migrated) {
            $wpdb->query("
                UPDATE {$wpdb->usermeta} 
                SET meta_key = 'membership_number' 
                WHERE meta_key = 'generated_id'
            ");
            update_option('csi_membership_number_meta_migrated', true);
        }
    }
    
    /**
     * Create email templates table
     */
    private static function create_email_templates_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'csi_email_templates';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            template_name VARCHAR(191) NOT NULL,
            from_name VARCHAR(191) DEFAULT NULL,
            from_email VARCHAR(191) DEFAULT NULL,
            subject VARCHAR(255) NOT NULL,
            body_html LONGTEXT NOT NULL,
            placeholders_json LONGTEXT DEFAULT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY template_name (template_name),
            KEY is_active (is_active)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create email reminders table
     */
    private static function create_email_reminders_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'csi_email_reminders';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            template_id BIGINT(20) UNSIGNED NOT NULL,
            reminder_type VARCHAR(50) DEFAULT 'membership_renewal',
            sent_at DATETIME DEFAULT NULL,
            membership_date DATE NOT NULL,
            reminder_date DATE NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY template_id (template_id),
            KEY reminder_date (reminder_date),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create membership history table
     */
    private static function create_membership_history_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'csi_membership_history';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            event_type VARCHAR(50) NOT NULL,
            event_data LONGTEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY event_type (event_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Check if table exists
     */
    public static function table_exists($table_name) {
        global $wpdb;
        
        $table = $wpdb->prefix . $table_name;
        
        // Try to query the table - if it doesn't exist, this will return null
        $result = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        
        return $result === $table;
    }
    
    /**
     * Get table name with prefix
     */
    public static function get_table_name($table_name) {
        global $wpdb;
        return $wpdb->prefix . $table_name;
    }
}
