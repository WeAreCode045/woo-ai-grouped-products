<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_AI_Variable_Product {
    /**
     * The single instance of the class.
     *
     * @var WC_AI_Variable_Product
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
     * Create a variable product from simple products.
     */
    public function create_from_products($product_ids, $attributes = array()) {
        if (empty($product_ids) || !is_array($product_ids)) {
            throw new Exception(__('Invalid product IDs', 'woo-ai-grouped-products'));
        }
        
        // Get the first product as base
        $base_product = wc_get_product($product_ids[0]);
        if (!$base_product) {
            throw new Exception(__('Invalid base product', 'woo-ai-grouped-products'));
        }
        
        // Create variable product
        $variable_product = new WC_Product_Variable();
        $variable_product->set_name($this->generate_variable_product_name($product_ids));
        $variable_product->set_status('publish');
        $variable_product->set_catalog_visibility('visible');
        
        // Set product categories
        $categories = $this->get_common_categories($product_ids);
        if (!empty($categories)) {
            $variable_product->set_category_ids($categories);
        }
        
        // Set product tags
        $tags = $this->get_common_tags($product_ids);
        if (!empty($tags)) {
            $variable_product->set_tag_ids($tags);
        }
        
        // Process attributes
        $attributes = $this->process_attributes($product_ids, $attributes);
        if (!empty($attributes)) {
            $variable_product->set_attributes($attributes);
        }
        
        // Save the variable product
        $variable_product_id = $variable_product->save();
        
        if (is_wp_error($variable_product_id)) {
            throw new Exception(__('Failed to create variable product', 'woo-ai-grouped-products'));
        }
        
        // Create variations
        $this->create_variations($variable_product_id, $product_ids, $attributes);
        
        return $variable_product_id;
    }
    
    /**
     * Generate a name for the variable product.
     */
    private function generate_variable_product_name($product_ids) {
        $names = array();
        
        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
                $names[] = $product->get_name();
            }
        }
        
        if (empty($names)) {
            return __('Variable Product', 'woo-ai-grouped-products');
        }
        
        // Find common prefix
        $prefix = $this->find_common_prefix($names);
        
        // If no common prefix, use the first product's name
        if (empty($prefix)) {
            return reset($names) . ' ' . __('(Variable)', 'woo-ai-grouped-products');
        }
        
        return trim($prefix) . ' ' . __('(Variable)', 'woo-ai-grouped-products');
    }
    
    /**
     * Find common prefix among strings.
     */
    private function find_common_prefix($strings) {
        if (empty($strings)) {
            return '';
        }
        
        $prefix = $strings[0];
        $count = count($strings);
        
        for ($i = 1; $i < $count; $i++) {
            while (strpos($strings[$i], $prefix) !== 0) {
                $prefix = substr($prefix, 0, -1);
                
                if (empty($prefix)) {
                    return '';
                }
            }
        }
        
        return $prefix;
    }
    
    /**
     * Get common categories for products.
     */
    private function get_common_categories($product_ids) {
        if (empty($product_ids)) {
            return array();
        }
        
        $categories = array();
        
        foreach ($product_ids as $product_id) {
            $product_categories = get_the_terms($product_id, 'product_cat');
            if (!is_wp_error($product_categories) && !empty($product_categories)) {
                $category_ids = wp_list_pluck($product_categories, 'term_id');
                if (empty($categories)) {
                    $categories = $category_ids;
                } else {
                    $categories = array_intersect($categories, $category_ids);
                    if (empty($categories)) {
                        break;
                    }
                }
            }
        }
        
        return array_values($categories);
    }
    
    /**
     * Get common tags for products.
     */
    private function get_common_tags($product_ids) {
        if (empty($product_ids)) {
            return array();
        }
        
        $tags = array();
        
        foreach ($product_ids as $product_id) {
            $product_tags = get_the_terms($product_id, 'product_tag');
            if (!is_wp_error($product_tags) && !empty($product_tags)) {
                $tag_ids = wp_list_pluck($product_tags, 'term_id');
                if (empty($tags)) {
                    $tags = $tag_ids;
                } else {
                    $tags = array_intersect($tags, $tag_ids);
                    if (empty($tags)) {
                        break;
                    }
                }
            }
        }
        
        return array_values($tags);
    }
    
    /**
     * Process product attributes.
     */
    private function process_attributes($product_ids, $attributes = array()) {
        $processed_attributes = array();
        
        // Process each attribute
        foreach ($attributes as $attribute_name => $attribute_values) {
            $attribute = new WC_Product_Attribute();
            $attribute->set_name($attribute_name);
            $attribute->set_options($attribute_values);
            $attribute->set_visible(true);
            $attribute->set_variation(true);
            
            $processed_attributes[sanitize_title($attribute_name)] = $attribute;
        }
        
        return $processed_attributes;
    }
    
    /**
     * Create variations for the variable product.
     */
    private function create_variations($variable_product_id, $product_ids, $attributes) {
        foreach ($product_ids as $product_id) {
            $variation = new WC_Product_Variation();
            $variation->set_parent_id($variable_product_id);
            
            // Set variation attributes
            $variation_attributes = $this->get_variation_attributes($product_id, $attributes);
            $variation->set_attributes($variation_attributes);
            
            // Copy product data
            $this->copy_product_data($product_id, $variation);
            
            // Save the variation
            $variation->save();
        }
    }
    
    /**
     * Get variation attributes for a product.
     */
    private function get_variation_attributes($product_id, $attributes) {
        $variation_attributes = array();
        $product = wc_get_product($product_id);
        
        foreach ($attributes as $attribute_name => $attribute_values) {
            $attribute_value = $this->get_product_attribute_value($product, $attribute_name);
            if ($attribute_value) {
                $variation_attributes[$attribute_name] = $attribute_value;
            }
        }
        
        return $variation_attributes;
    }
    
    /**
     * Get product attribute value.
     */
    private function get_product_attribute_value($product, $attribute_name) {
        $attributes = $product->get_attributes();
        
        foreach ($attributes as $attribute) {
            if ($attribute->get_name() === $attribute_name && $attribute->is_taxonomy()) {
                $terms = wp_get_post_terms($product->get_id(), $attribute_name, array('fields' => 'names'));
                return !empty($terms) ? $terms[0] : '';
            }
        }
        
        return '';
    }
    
    /**
     * Copy product data to variation.
     */
    private function copy_product_data($source_product_id, &$variation) {
        $product = wc_get_product($source_product_id);
        
        // Copy basic data
        $variation->set_regular_price($product->get_regular_price());
        $variation->set_sale_price($product->get_sale_price());
        $variation->set_stock_quantity($product->get_stock_quantity());
        $variation->set_manage_stock($product->get_manage_stock());
        $variation->set_stock_status($product->get_stock_status());
        $variation->set_weight($product->get_weight());
        $variation->set_length($product->get_length());
        $variation->set_width($product->get_width());
        $variation->set_height($product->get_height());
        
        // Copy meta data
        $meta_to_copy = array(
            '_sku',
            '_sale_price_dates_from',
            '_sale_price_dates_to',
            '_downloadable',
            '_download_limit',
            '_download_expiry',
            '_downloadable_files',
            '_download_type',
            '_purchase_note',
            '_product_attributes',
            '_product_image_gallery',
        );
        
        foreach ($meta_to_copy as $meta_key) {
            $meta_value = get_post_meta($source_product_id, $meta_key, true);
            if ($meta_value !== '') {
                $variation->update_meta_data($meta_key, $meta_value);
            }
        }
    }
}