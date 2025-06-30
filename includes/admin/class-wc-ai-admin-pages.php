// includes/admin/class-wc-ai-admin-pages.php
<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_AI_Admin_Pages {
    /**
     * Constructor.
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_pages'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Add menu pages.
     */
    public function add_menu_pages() {
        add_menu_page(
            __('AI Grouped Products', 'woo-ai-grouped-products'),
            __('AI Grouped Products', 'woo-ai-grouped-products'),
            'manage_woocommerce',
            'wc-ai-grouped-products',
            array($this, 'render_dashboard_page'),
            'dashicons-randomize',
            56
        );
    }
    
    /**
     * Register settings.
     */
    public function register_settings() {
        register_setting('wc_ai_grouped_products_settings', 'wc_ai_grouped_products_options');
    }
    
    /**
     * Render dashboard page.
     */
    public function render_dashboard_page() {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }
        
        include WC_AI_GROUPED_PRODUCTS_PATH . 'templates/admin/dashboard/dashboard.php';
    }
}