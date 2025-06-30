// includes/core/class-wc-ai-product-matcher.php
<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_AI_Product_Matcher {
    /**
     * The single instance of the class.
     *
     * @var WC_AI_Product_Matcher
     */
    private static $instance = null;
    
    /**
     * Get the singleton instance.
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Start processing products.
     */
    public function start_processing($min_similarity, $batch_size) {
        // Get products to process
        $products = $this->get_products_to_process($batch_size);
        
        if (empty($products)) {
            return array(
                'success' => false,
                'message' => __('No products to process', 'woo-ai-grouped-products'),
            );
        }
        
        // Log start of processing
        $this->log(sprintf(__('Starting to process %d products', 'woo-ai-grouped-products'), count($products)));
        
        // Group products
        $groupings = $this->group_products($products, $min_similarity);
        
        // Create variable products
        $variables_created = $this->create_variable_products($groupings);
        
        // Mark products as processed
        $this->mark_products_processed($products);
        
        return array(
            'success' => true,
            'message' => sprintf(
                _n('Processed %d product', 'Processed %d products', count($products), 'woo-ai-grouped-products'),
                count($products)
            ),
            'variables_created' => $variables_created,
            'products_processed' => count($products),
        );
    }
    
    /**
     * Get products to process.
     */
    protected function get_products_to_process($limit = 20) {
        $args = array(
            'status' => 'publish',
            'limit' => $limit,
            'type' => 'simple',
            'return' => 'objects',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_woo_ai_processed',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key' => '_woo_ai_processed',
                    'value' => '0',
                    'compare' => '=',
                ),
            ),
        );
        
        return wc_get_products($args);
    }
    
    /**
     * Group products by similarity.
     */
    protected function group_products($products, $min_similarity) {
        // First try AI-based grouping if enabled
        if ($this->is_ai_enabled()) {
            try {
                $api = new WC_AI_API_OpenAI($this->get_openai_api_key());
                return $api->generate_product_groupings($products, array(
                    'min_similarity' => $min_similarity,
                ));
            } catch (Exception $e) {
                $this->log(sprintf(__('AI grouping failed: %s', 'woo-ai-grouped-products'), $e->getMessage()));
                // Fall back to basic grouping
            }
        }
        
        // Basic grouping by category and brand
        $groups = array();
        
        foreach ($products as $product) {
            $category = $this->get_primary_category($product);
            $brand = $this->get_brand($product);
            
            $key = md5($category . '|' . $brand);
            
            if (!isset($groups[$key])) {
                $groups[$key] = array(
                    'name' => $brand . ' - ' . $category,
                    'product_ids' => array(),
                    'common_attributes' => array(
                        'category' => $category,
                        'brand' => $brand,
                    ),
                );
            }
            
            $groups[$key]['product_ids'][] = $product->get_id();
        }
        
        return array('groups' => array_values($groups));
    }
    
    /**
     * Create variable products from groups.
     */
    protected function create_variable_products($groupings) {
        if (empty($groupings['groups'])) {
            return 0;
        }
        
        $count = 0;
        
        foreach ($groupings['groups'] as $group) {
            if (count($group['product_ids']) < 2) {
                continue; // Skip groups with less than 2 products
            }
            
            try {
                $variable_product = new WC_AI_Variable_Product();
                $variable_product->create_from_products($group);
                $count++;
                
                $this->log(sprintf(
                    __('Created variable product from %d products', 'woo-ai-grouped-products'),
                    count($group['product_ids'])
                ));
            } catch (Exception $e) {
                $this->log(sprintf(
                    __('Failed to create variable product: %s', 'woo-ai-grouped-products'),
                    $e->getMessage()
                ));
            }
        }
        
        return $count;
    }
    
    /**
     * Mark products as processed.
     */
    protected function mark_products_processed($products) {
        foreach ($products as $product) {
            $product->update_meta_data('_woo_ai_processed', '1');
            $product->save();
        }
    }
    
    /**
     * Check if AI processing is enabled.
     */
    protected function is_ai_enabled() {
        return !empty($this->get_openai_api_key());
    }
    
    /**
     * Get OpenAI API key.
     */
    protected function get_openai_api_key() {
        return get_option('wc_ai_openai_api_key', '');
    }
    
    /**
     * Get product's primary category.
     */
    protected function get_primary_category($product) {
        $categories = $product->get_category_ids();
        return !empty($categories) ? get_term($categories[0])->name : __('Uncategorized', 'woo-ai-grouped-products');
    }
    
    /**
     * Get product brand.
     */
    protected function get_brand($product) {
        $brand = $product->get_meta('_brand');
        
        if (empty($brand)) {
            $brand_terms = wp_get_post_terms($product->get_id(), 'product_brand');
            if (!empty($brand_terms) && !is_wp_error($brand_terms)) {
                $brand = $brand_terms[0]->name;
            }
        }
        
        return !empty($brand) ? $brand : __('No Brand', 'woo-ai-grouped-products');
    }
    
    /**
     * Log a message.
     */
    protected function log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[WC AI Grouped Products] ' . $message);
        }
    }
}