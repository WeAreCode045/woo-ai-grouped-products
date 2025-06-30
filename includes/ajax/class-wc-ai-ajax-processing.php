// includes/ajax/class-wc-ai-ajax-processing.php
<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_AI_Ajax_Processing extends WC_AI_Ajax_Handler {
    /**
     * Get AJAX events.
     */
    protected function get_ajax_events() {
        return array(
            'start_processing' => false,
            'stop_processing' => false,
            'check_status' => false,
        );
    }
    
    /**
     * Start processing products.
     */
    public function start_processing() {
        $this->verify_nonce();
        
        // Get parameters
        $params = wp_parse_args($_REQUEST, array(
            'min_similarity' => 85,
            'batch_size' => 20,
        ));
        
        // Validate parameters
        $min_similarity = absint($params['min_similarity']);
        $batch_size = absint($params['batch_size']);
        
        if ($min_similarity < 50 || $min_similarity > 100) {
            $this->send_json_error(__('Invalid minimum similarity value', 'woo-ai-grouped-products'));
        }
        
        if ($batch_size < 1 || $batch_size > 100) {
            $this->send_json_error(__('Invalid batch size', 'woo-ai-grouped-products'));
        }
        
        // Start processing
        try {
            $processor = WC_AI_Product_Matcher::instance();
            $result = $processor->start_processing($min_similarity, $batch_size);
            
            $this->send_json_response($result);
        } catch (Exception $e) {
            $this->send_json_error($e->getMessage());
        }
    }
    
    /**
     * Stop processing.
     */
    public function stop_processing() {
        $this->verify_nonce();
        
        try {
            $processor = WC_AI_Product_Matcher::instance();
            $result = $processor->stop_processing();
            
            $this->send_json_response($result);
        } catch (Exception $e) {
            $this->send_json_error($e->getMessage());
        }
    }
    
    /**
     * Check processing status.
     */
    public function check_status() {
        $this->verify_nonce();
        
        try {
            $processor = WC_AI_Product_Matcher::instance();
            $status = $processor->get_processing_status();
            
            $this->send_json_response($status);
        } catch (Exception $e) {
            $this->send_json_error($e->getMessage());
        }
    }
}

new WC_AI_Ajax_Processing();