// includes/ajax/class-wc-ai-ajax-settings.php
<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_AI_Ajax_Settings extends WC_AI_Ajax_Handler {
    /**
     * Get AJAX events.
     */
    protected function get_ajax_events() {
        return array(
            'save_settings' => false,
            'test_connection' => false,
            'get_settings' => false,
        );
    }
    
    /**
     * Save settings.
     */
    public function save_settings() {
        $this->verify_nonce();
        
        if (!isset($_POST['settings'])) {
            $this->send_json_error(__('No settings provided', 'woo-ai-grouped-products'));
        }
        
        $settings = $_POST['settings'];
        $sanitized_settings = array();
        
        // Sanitize and validate settings
        if (isset($settings['openai_api_key'])) {
            $sanitized_settings['openai_api_key'] = sanitize_text_field($settings['openai_api_key']);
        }
        
        // Save settings
        update_option('wc_ai_settings', $sanitized_settings);
        
        $this->send_json_success(array(
            'message' => __('Settings saved successfully', 'woo-ai-grouped-products')
        ));
    }
    
    /**
     * Test API connection.
     */
    public function test_connection() {
        $this->verify_nonce();
        
        if (!isset($_POST['api_key'])) {
            $this->send_json_error(__('API key is required', 'woo-ai-grouped-products'));
        }
        
        $api_key = sanitize_text_field($_POST['api_key']);
        $api = new WC_AI_API_OpenAI($api_key);
        
        try {
            $result = $api->test_connection();
            $this->send_json_success($result);
        } catch (Exception $e) {
            $this->send_json_error($e->getMessage());
        }
    }
    
    /**
     * Get settings.
     */
    public function get_settings() {
        $this->verify_nonce();
        
        $settings = get_option('wc_ai_settings', array());
        
        $this->send_json_success(array(
            'settings' => $settings
        ));
    }
}

new WC_AI_Ajax_Settings();