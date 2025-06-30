<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_AI_Admin_Assets {
    /**
     * Constructor.
     */
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }
    
    /**
     * Enqueue admin assets.
     */
    public function enqueue_assets($hook) {
        if (strpos($hook, 'wc-ai-grouped-products') === false) {
            return;
        }
        
        // Styles
        wp_enqueue_style(
            'wc-ai-admin',
            WC_AI_GROUPED_PRODUCTS_URL . 'assets/css/admin.css',
            array(),
            WC_AI_GROUPED_PRODUCTS_VERSION
        );
        
        // Scripts
        wp_enqueue_script(
            'wc-ai-admin',
            WC_AI_GROUPED_PRODUCTS_URL . 'assets/js/admin.js',
            array('jquery'),
            WC_AI_GROUPED_PRODUCTS_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('wc-ai-admin', 'wcAIGroupedProducts', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wc_ai_ajax_nonce'),
            'i18n' => array(
                'processing' => __('Processing...', 'woo-ai-grouped-products'),
                'complete' => __('Process Complete!', 'woo-ai-grouped-products'),
                'error' => __('An error occurred', 'woo-ai-grouped-products'),
            ),
        ));
    }
}