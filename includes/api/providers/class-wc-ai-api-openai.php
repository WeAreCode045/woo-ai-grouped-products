// includes/api/providers/class-wc-ai-api-openai.php
<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_AI_API_OpenAI extends WC_AI_API_Base {
    /**
     * API base URL.
     *
     * @var string
     */
    protected $api_url = 'https://api.openai.com/v1';
    
    /**
     * Test the API connection.
     */
    public function test_connection() {
        try {
            $response = $this->request('/models', array(
                'method' => 'GET',
                'timeout' => 10,
            ));
            
            return array(
                'success' => true,
                'message' => __('Successfully connected to OpenAI API', 'woo-ai-grouped-products'),
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => sprintf(__('Failed to connect to OpenAI API: %s', 'woo-ai-grouped-products'), $e->getMessage()),
            );
        }
    }
    
    /**
     * Generate text using the OpenAI API.
     */
    public function generate_text($prompt, $model = 'gpt-3.5-turbo', $max_tokens = 1000, $temperature = 0.7) {
        $response = $this->request('/chat/completions', array(
            'method' => 'POST',
            'body' => json_encode(array(
                'model' => $model,
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => $prompt,
                    ),
                ),
                'max_tokens' => $max_tokens,
                'temperature' => $temperature,
            )),
        ));
        
        return isset($response['choices'][0]['message']['content']) 
            ? trim($response['choices'][0]['message']['content'])
            : '';
    }
}