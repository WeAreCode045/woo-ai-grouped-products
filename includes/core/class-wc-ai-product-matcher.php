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
        $product_ids = $this->get_products_to_process($batch_size);
        
        if (empty($product_ids)) {
            return array(
                'status' => 'completed',
                'message' => __('No products to process', 'woo-ai-grouped-products'),
                'processed' => 0,
                'total' => 0,
            );
        }
        
        // Mark products as processing
        $this->mark_products_processing($product_ids);
        
        // Process products in background
        $this->process_products_async($product_ids, $min_similarity);
        
        return array(
            'status' => 'processing',
            'message' => sprintf(__('Processing %d products', 'woo-ai-grouped-products'), count($product_ids)),
            'processed' => 0,
            'total' => count($product_ids),
        );
    }
    
    /**
     * Get products to process.
     */
    private function get_products_to_process($limit = 20) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} 
            WHERE post_type = 'product' 
            AND post_status = 'publish'
            AND ID NOT IN (
                SELECT post_id FROM {$wpdb->postmeta} 
                WHERE meta_key = '_wc_ai_processed' 
                AND meta_value = '1'
            )
            ORDER BY ID ASC
            LIMIT %d",
            $limit
        );
        
        return $wpdb->get_col($query);
    }
    
    /**
     * Mark products as processing.
     */
    private function mark_products_processing($product_ids) {
        if (empty($product_ids)) {
            return;
        }
        
        foreach ($product_ids as $product_id) {
            update_post_meta($product_id, '_wc_ai_processing', '1');
        }
    }
    
    /**
     * Process products asynchronously.
     */
    private function process_products_async($product_ids, $min_similarity) {
        // In a real implementation, you would use WP Background Processing
        // or WP Cron to process products in the background
        // This is a simplified example
        
        // For now, we'll just simulate processing
        foreach ($product_ids as $product_id) {
            // Simulate processing
            sleep(1);
            
            // Mark as processed
            update_post_meta($product_id, '_wc_ai_processed', '1');
            delete_post_meta($product_id, '_wc_ai_processing');
        }
    }
    
    /**
     * Stop processing.
     */
    public function stop_processing() {
        // In a real implementation, you would stop any background processes
        return array(
            'status' => 'stopped',
            'message' => __('Processing stopped', 'woo-ai-grouped-products'),
        );
    }
    
    /**
     * Get processing status.
     */
    public function get_processing_status() {
        global $wpdb;
        
        $total = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_type = 'product' 
            AND post_status = 'publish'"
        );
        
        $processed = $wpdb->get_var(
            "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} 
            WHERE meta_key = '_wc_ai_processed' 
            AND meta_value = '1'"
        );
        
        $processing = $wpdb->get_var(
            "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} 
            WHERE meta_key = '_wc_ai_processing' 
            AND meta_value = '1'"
        );
        
        $progress = $total > 0 ? round(($processed / $total) * 100) : 0;
        
        return array(
            'status' => $processing > 0 ? 'processing' : 'idle',
            'progress' => $progress,
            'processed' => $processed,
            'processing' => $processing,
            'total' => $total,
            'stats' => array(
                'total_products' => $total,
                'processed_products' => $processed,
                'variable_products' => $this->get_variable_products_count(),
            ),
        );
    }
    
    /**
     * Get count of variable products.
     */
    private function get_variable_products_count() {
        global $wpdb;
        
        return $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_type = 'product' 
            AND post_status = 'publish'
            AND ID IN (
                SELECT post_parent FROM {$wpdb->posts} 
                WHERE post_type = 'product_variation' 
                AND post_status = 'publish'
            )"
        );
    }

    /**
     * Get product brand.
     */
    private function get_brand($product) {
        // Try to get brand from product meta
        $brand = $product->get_meta('_brand');
        
        if (empty($brand)) {
            // Try to get brand from product attributes
            $attributes = $product->get_attributes();
            if (isset($attributes['pa_brand'])) {
                $terms = wc_get_product_terms($product->get_id(), 'pa_brand', array('fields' => 'names'));
                if (!empty($terms)) {
                    $brand = reset($terms);
                }
            }
        }
        
        if (empty($brand)) {
            // Try to get brand from product brand taxonomy
            $terms = get_the_terms($product->get_id(), 'product_brand');
            if (!is_wp_error($terms) && !empty($terms)) {
                $brand = $terms[0]->name;
            }
        }
        
        return !empty($brand) ? $brand : __('Unknown Brand', 'woo-ai-grouped-products');
    }
    
    /**
     * Group products by similarity.
     */
    private function group_products($products, $min_similarity) {
        $groups = array();
        
        foreach ($products as $product) {
            $product_title = $product->get_name();
            $product_brand = $this->get_brand($product);
            $product_categories = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'names'));
            
            $found_group = false;
            
            // Try to find a matching group
            foreach ($groups as &$group) {
                $similarity = $this->calculate_similarity(
                    $product_title,
                    $product_brand,
                    $product_categories,
                    $group['title'],
                    $group['brand'],
                    $group['categories']
                );
                
                if ($similarity >= $min_similarity) {
                    $group['products'][] = $product->get_id();
                    $found_group = true;
                    break;
                }
            }
            
            // Create new group if no match found
            if (!$found_group) {
                $groups[] = array(
                    'title' => $product_title,
                    'brand' => $product_brand,
                    'categories' => $product_categories,
                    'products' => array($product->get_id()),
                );
            }
        }
        
        // Filter out groups with only one product
        return array_filter($groups, function($group) {
            return count($group['products']) > 1;
        });
    }
    
    /**
     * Calculate similarity between products.
     */
    private function calculate_similarity($title1, $brand1, $categories1, $title2, $brand2, $categories2) {
        // Calculate title similarity using Levenshtein distance
        $max_length = max(strlen($title1), strlen($title2));
        $title_similarity = $max_length > 0 
            ? (1 - levenshtein($title1, $title2) / $max_length) * 100 
            : 0;
        
        // Brand similarity (exact match = 100, no match = 0)
        $brand_similarity = strtolower($brand1) === strtolower($brand2) ? 100 : 0;
        
        // Category similarity (percentage of matching categories)
        $matching_categories = array_intersect($categories1, $categories2);
        $total_categories = array_unique(array_merge($categories1, $categories2));
        $category_similarity = count($total_categories) > 0 
            ? (count($matching_categories) / count($total_categories)) * 100 
            : 0;
        
        // Weighted average (title: 60%, brand: 25%, categories: 15%)
        return ($title_similarity * 0.6) + ($brand_similarity * 0.25) + ($category_similarity * 0.15);
    }
    
    /**
     * Create variable products from groups.
     */
    private function create_variable_products($groups) {
        $count = 0;
        
        foreach ($groups as $group) {
            try {
                $variable_product = WC_AI_Variable_Product::instance();
                $product_id = $variable_product->create_from_products($group['products']);
                
                if ($product_id) {
                    $count++;
                    
                    // Log success
                    $this->log(sprintf(
                        __('Created variable product #%d from %d products', 'woo-ai-grouped-products'),
                        $product_id,
                        count($group['products'])
                    ));
                }
            } catch (Exception $e) {
                // Log error
                $this->log(sprintf(
                    __('Failed to create variable product: %s', 'woo-ai-grouped-products'),
                    $e->getMessage()
                ));
            }
        }
        
        return $count;
    }
    
    /**
     * Log a message.
     */
    private function log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('[%s] %s', 'WC AI Grouped Products', $message));
        }
    }
}