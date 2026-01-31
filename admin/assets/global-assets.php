<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Global Asset Manager
 * Enqueues Bootstrap, DataTables, and SweetAlert2 globally
 * Prevents duplicate loading
 */
class CSI_Global_Assets {
    
    private static $instance = null;
    private static $assets_loaded = false;
    
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
     * Initialize global assets
     */
    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_global_assets'], 1);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets'], 1);
    }
    
    /**
     * Enqueue global admin assets
     * Loads on ALL admin pages
     */
    public function enqueue_global_assets($hook) {
        // Only load on our plugin pages or all admin pages (configurable)
        $load_on_all_admin = apply_filters('csi_load_assets_on_all_admin', false);
        
        if (!$load_on_all_admin) {
            // Check by page parameter (more reliable than hook)
            $current_page = isset($_GET['page']) ? $_GET['page'] : '';
            $our_pages = [
                'csi-dashboard',
                'membership-applications',
                'csi-membership-number-settings',
                'csi-email-templates',
                'csi-user-profile',
                'csi-settings',
            ];
            
            // Also check hook names as fallback
            $our_hooks = [
                'toplevel_page_csi-dashboard',
                'csi-dashboard_page_membership-applications',
                'custom-signup_page_membership-applications',
                'csi-dashboard_page_csi-membership-number-settings',
                'custom-signup_page_csi-membership-number-settings',
                'csi-dashboard_page_csi-email-templates',
                'custom-signup_page_csi-email-templates',
                'custom-signup_page_csi-user-profile',
                'csi-dashboard_page_csi-settings',
                'custom-signup_page_csi-settings',
            ];
            
            if (!in_array($current_page, $our_pages) && !in_array($hook, $our_hooks)) {
                return;
            }
        }
        
        // Prevent duplicate loading
        if (self::$assets_loaded) {
            return;
        }
        
        // Bootstrap 5.3.0 - Global
        wp_enqueue_style(
            'csi-bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            [],
            '5.3.0'
        );
        
        wp_enqueue_script(
            'csi-bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
            ['jquery'],
            '5.3.0',
            true
        );
        
        // DataTables - Global
        wp_enqueue_style(
            'csi-datatables',
            'https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css',
            ['csi-bootstrap'],
            '1.13.6'
        );
        
        wp_enqueue_script(
            'csi-datatables',
            'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js',
            ['jquery'],
            '1.13.6',
            true
        );
        
        wp_enqueue_script(
            'csi-datatables-bootstrap5',
            'https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js',
            ['csi-datatables', 'csi-bootstrap'],
            '1.13.6',
            true
        );
        
        // SweetAlert2 - Global
        wp_enqueue_style(
            'csi-sweetalert2',
            'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
            [],
            '11.7.0'
        );
        
        wp_enqueue_script(
            'csi-sweetalert2',
            'https://cdn.jsdelivr.net/npm/sweetalert2@11',
            [],
            '11.7.0',
            true
        );
        
        // Chart.js - Conditional (only on dashboard)
        if ($hook === 'toplevel_page_csi-dashboard') {
            wp_enqueue_script(
                'csi-chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
                [],
                '4.4.0',
                true
            );
        }
        
        // Global admin CSS
        wp_enqueue_style(
            'csi-admin-global',
            CSI_PLUGIN_URL . 'admin/assets/css/admin-global.css',
            ['csi-bootstrap', 'csi-datatables', 'csi-sweetalert2'],
            CSI_VERSION
        );
        
        // Global admin JS
        wp_enqueue_script(
            'csi-admin-global',
            CSI_PLUGIN_URL . 'admin/assets/js/admin-global.js',
            ['jquery', 'csi-bootstrap', 'csi-datatables', 'csi-sweetalert2'],
            CSI_VERSION,
            true
        );
        
        // Localize script with global data
        wp_localize_script('csi-admin-global', 'csiGlobal', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('csi_global_nonce'),
            'pluginUrl' => CSI_PLUGIN_URL,
            'assetsLoaded' => true
        ]);
        
        // Mark as loaded
        self::$assets_loaded = true;
        
        // Add filter to allow other plugins to check
        add_filter('csi_global_assets_loaded', '__return_true');
    }
    
    /**
     * Enqueue frontend assets (if needed)
     */
    public function enqueue_frontend_assets() {
        global $post;
        
        // Only load on pages with our shortcodes
        if (!$post) {
            return;
        }
        
        $post_content = $post->post_content ?? '';
        if (!has_shortcode($post_content, 'custom_signup') && 
            !has_shortcode($post_content, 'profile_info')) {
            return;
        }
        
        // Bootstrap for frontend forms
        wp_enqueue_style(
            'csi-bootstrap-frontend',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            [],
            '5.3.0'
        );
        
        wp_enqueue_script(
            'csi-bootstrap-frontend',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
            ['jquery'],
            '5.3.0',
            true
        );
        
        // SweetAlert2 for frontend
        wp_enqueue_style(
            'csi-sweetalert2-frontend',
            'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
            [],
            '11.7.0'
        );
        
        wp_enqueue_script(
            'csi-sweetalert2-frontend',
            'https://cdn.jsdelivr.net/npm/sweetalert2@11',
            [],
            '11.7.0',
            true
        );
    }
    
    /**
     * Check if global assets are loaded
     */
    public static function are_assets_loaded() {
        return self::$assets_loaded || apply_filters('csi_global_assets_loaded', false);
    }
    
    /**
     * Get asset dependencies
     */
    public static function get_dependencies() {
        return [
            'bootstrap' => ['csi-bootstrap'],
            'datatables' => ['csi-datatables', 'csi-datatables-bootstrap5'],
            'sweetalert2' => ['csi-sweetalert2'],
            'chartjs' => ['csi-chartjs']
        ];
    }
}

// Initialize global assets
CSI_Global_Assets::get_instance();
