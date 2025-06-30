<?php
if (!defined('ABSPATH')) {
    exit;
}

abstract class WC_AI_API_Base {
    /**
     * API base URL.
     *
     * @var string
     */
    protected $api_url = '';
    
    /**
     * API key.
     *
     * @var string
     */
    protected $api_key = '';
    
    /**
     * Constructor.
     */
    public function __construct($api_key = '') {
        $this->api_key = $api_key;
    }
    
    /**
     * Make a request to the API.
     */
    protected function request($endpoint, $args = array(), $method = 'GET') {
        $url = trailingslashit($this->api_url) . ltrim($endpoint, '/');
        $args = wp_parse_args($args, array(
            'method' => $method,
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key,
            ),
        ));
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from API');
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code < 200 || $response_code >= 300) {
            $error_message = isset($data['error']['message']) 
                ? $data['error']['message'] 
                : 'API request failed with status code ' . $response_code;
            
            throw new Exception($error_message, $response_code);
        }
        
        return $data;
    }
    
    /**
     * Test the API connection.
     */
    abstract public function test_connection();
}