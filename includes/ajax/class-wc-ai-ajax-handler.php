<?php
if (!defined('ABSPATH')) {
    exit;
}

abstract class WC_AI_Ajax_Handler {
    /**
     * AJAX action prefix.
     *
     * @var string
     */
    protected $prefix = 'wc_ai_';
    
    /**
     * Register AJAX hooks.
     */
    public function __construct() {
        $this->register_hooks();
    }
    
    /**
     * Register AJAX hooks.
     */
    protected function register_hooks() {
        $ajax_events = $this->get_ajax_events();
        
        foreach ($ajax_events as $ajax_event => $nopriv) {
            add_action('wp_ajax_' . $this->prefix . $ajax_event, array($this, $ajax_event));
            
            if ($nopriv) {
                add_action('wp_ajax_nopriv_' . $this->prefix . $ajax_event, array($this, $ajax_event));
            }
        }
    }
    
    /**
     * Get AJAX events.
     */
    abstract protected function get_ajax_events();
    
    /**
     * Verify AJAX nonce.
     */
    protected function verify_nonce() {
        if (!isset($_REQUEST['security']) || !wp_verify_nonce($_REQUEST['security'], 'wc_ai_ajax_nonce')) {
            wp_send_json_error(array(
                'message' => __('Invalid nonce', 'woo-ai-grouped-products')
            ));
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array(
                'message' => __('Insufficient permissions', 'woo-ai-grouped-products')
            ));
        }
    }
    
    /**
     * Send JSON response.
     */
    protected function send_json_response($data = null, $status_code = null) {
        if (!is_null($data)) {
            wp_send_json_success($data, $status_code);
        } else {
            wp_send_json_success();
        }
    }
    
    /**
     * Send JSON error.
     */
    protected function send_json_error($message = '', $status_code = 400) {
        wp_send_json_error(array(
            'message' => $message ?: __('An error occurred', 'woo-ai-grouped-products')
        ), $status_code);
    }
}