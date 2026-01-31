<?php
/**
 * Plugin Name: Society Management
 * Description: A comprehensive society management plugin with membership management, email templates, and analytics
 * Version: 1.6
 * Author: Muhammad Samir
 * Text Domain: custom-signup-plugin
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CSI_VERSION', '1.6');
define('CSI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CSI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CSI_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class Custom_Signup_Plugin {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Global systems (load first)
        require_once CSI_PLUGIN_DIR . 'admin/assets/global-assets.php';
        require_once CSI_PLUGIN_DIR . 'includes/functions/notifications.php';
        require_once CSI_PLUGIN_DIR . 'includes/functions/database.php';
        $csi_rest_countries = CSI_PLUGIN_DIR . 'includes/api/rest-countries.php';
        if (file_exists($csi_rest_countries)) {
            require_once $csi_rest_countries;
        }
        require_once CSI_PLUGIN_DIR . 'includes/functions/countries.php';
        
        // AJAX handlers
        require_once CSI_PLUGIN_DIR . 'ajax/handlers.php';
        
        // Admin features
        $this->load_admin_features();
        
        // Shortcodes
        $this->load_shortcodes();
        
        // Load registration form processing
        $registration_processing = CSI_PLUGIN_DIR . 'shortcodes/registration/functions/form-processing.php';
        if (file_exists($registration_processing)) {
            require_once $registration_processing;
        }
        
        // Load registration functions
        $registration_functions = CSI_PLUGIN_DIR . 'shortcodes/registration/functions/functions.php';
        if (file_exists($registration_functions)) {
            require_once $registration_functions;
        }
        
        // Load profile functions
        $profile_functions = CSI_PLUGIN_DIR . 'shortcodes/profile/functions/functions.php';
        if (file_exists($profile_functions)) {
            require_once $profile_functions;
        }

        // Load renewal handler
        $renewal_handler = CSI_PLUGIN_DIR . 'shortcodes/profile/functions/renewal-handler.php';
        if (file_exists($renewal_handler)) {
            require_once $renewal_handler;
        }
        
        // Load profile PDF library
        $profile_pdf = CSI_PLUGIN_DIR . 'shortcodes/profile/functions/pdf-library.php';
        if (file_exists($profile_pdf)) {
            require_once $profile_pdf;
        }
    }
    
    /**
     * Load admin features
     */
    private function load_admin_features() {
        $features = [
            'dashboard',
            'users',
            'membership-number',
            'emails-templates',
            'settings'
        ];
        
        foreach ($features as $feature) {
            $functions_file = CSI_PLUGIN_DIR . "admin/features/{$feature}/functions/admin-menu.php";
            if (file_exists($functions_file)) {
                require_once $functions_file;
            }
            
            // Load additional function files for users
            if ($feature === 'users') {
                $additional_files = [
                    'admin-query.php',
                    'user-actions.php',
                    'import-handler.php',
                    'user-profile-page.php',
                    'membership-history.php',
                    'membership-expiry.php'
                ];
                foreach ($additional_files as $file) {
                    $file_path = CSI_PLUGIN_DIR . "admin/features/{$feature}/functions/{$file}";
                    if (file_exists($file_path)) {
                        require_once $file_path;
                    }
                }
            }
            
            // Load analytics for dashboard
            if ($feature === 'dashboard') {
                $analytics_file = CSI_PLUGIN_DIR . "admin/features/{$feature}/functions/analytics.php";
                if (file_exists($analytics_file)) {
                    require_once $analytics_file;
                }
            }
            
            // Load membership number generation for membership-number
            if ($feature === 'membership-number') {
                $id_generation_file = CSI_PLUGIN_DIR . "admin/features/{$feature}/functions/id-generation.php";
                if (file_exists($id_generation_file)) {
                    require_once $id_generation_file;
                }
            }
            
            // Load email template functions
            if ($feature === 'emails-templates') {
                $template_files = [
                    'template-crud.php',
                    'placeholder-replacer.php',
                    'email-sender.php',
                    'automatic-reminders.php'
                ];
                foreach ($template_files as $file) {
                    $file_path = CSI_PLUGIN_DIR . "admin/features/{$feature}/functions/{$file}";
                    if (file_exists($file_path)) {
                        require_once $file_path;
                    }
                }
            }

            // Load settings functions
            if ($feature === 'settings') {
                $settings_files = [
                    'smtp-config.php'
                ];
                foreach ($settings_files as $file) {
                    $file_path = CSI_PLUGIN_DIR . "admin/features/{$feature}/functions/{$file}";
                    if (file_exists($file_path)) {
                        require_once $file_path;
                    }
                }
            }
        }
    }
    
    /**
     * Load shortcodes
     */
    private function load_shortcodes() {
        $shortcodes = [
            'registration',
            'profile'
        ];
        
        foreach ($shortcodes as $shortcode) {
            $shortcode_file = CSI_PLUGIN_DIR . "shortcodes/{$shortcode}/functions/shortcode.php";
            if (file_exists($shortcode_file)) {
                require_once $shortcode_file;
            }
        }
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, ['CSI_Database', 'create_tables']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        // Ensure tables exist on admin pages
        add_action('admin_init', [$this, 'ensure_tables_exist'], 1);
        
        // Initialize on plugins_loaded
        add_action('plugins_loaded', [$this, 'init'], 20);
        
        // Load text domain
        add_action('init', [$this, 'load_textdomain'], 100);
    }
    
    /**
     * Ensure database tables exist
     */
    public function ensure_tables_exist() {
        if (!CSI_Database::table_exists('csi_membership_numbers') || !CSI_Database::table_exists('csi_membership_history')) {
            CSI_Database::create_tables();
        }
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Ensure database tables exist
        if (!CSI_Database::table_exists('csi_membership_numbers')) {
            CSI_Database::create_tables();
        }
        
        // Plugin initialized
        do_action('csi_plugin_loaded');
    }
    
    /**
     * Load text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'custom-signup-plugin',
            false,
            dirname(CSI_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('csi_daily_reminder_check');
    }
}

// Initialize plugin
Custom_Signup_Plugin::get_instance();
