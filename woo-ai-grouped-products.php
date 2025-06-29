<?php
/**
 * Plugin Name: WooCommerce AI Grouped Products
 * Plugin URI: https://yourwebsite.com/woo-ai-grouped-products
 * Description: Automatically groups similar WooCommerce products into variable products based on title, category, and brand.
 * Version: 1.0.0
 * Author: Code045
 * Author URI: https://code045.nl
 * Text Domain: woo-ai-grouped-products
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 7.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Make sure WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

class WC_AI_Grouped_Products {
    private static $instance = null;
    private $min_title_similarity = 85; // Minimum title similarity percentage
    private $brand_attributes = array('brand', 'merk', 'marque', 'marca'); // Brand attribute names in different languages

    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_process_product_grouping', array($this, 'process_product_grouping_ajax'));
    }

    public function enqueue_admin_scripts($hook) {
        if ('woocommerce_page_wc-ai-grouped-products' !== $hook) {
            return;
        }

        wp_enqueue_script(
            'wc-ai-grouped-products-admin',
            plugin_dir_url(__FILE__) . 'js/admin.js',
            array('jquery'),
            '1.0.0',
            true
        );

        wp_localize_script('wc-ai-grouped-products-admin', 'wcAIGroupedProducts', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wc_ai_grouped_products_nonce'),
            'processing' => __('Processing...', 'woo-ai-grouped-products'),
            'complete' => __('Process Complete!', 'woo-ai-grouped-products'),
        ));
    }

    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('AI Grouped Products', 'woo-ai-grouped-products'),
            __('AI Grouped Products', 'woo-ai-grouped-products'),
            'manage_woocommerce',
            'wc-ai-grouped-products',
            array($this, 'admin_page')
        );
    }

    public function register_settings() {
        register_setting('wc_ai_grouped_products', 'wc_ai_grouped_products_options');
    }

    public function admin_page() {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <div class="card">
                <h2><?php esc_html_e('Group Similar Products', 'woo-ai-grouped-products'); ?></h2>
                <p><?php esc_html_e('This tool will analyze your products and group similar ones into variable products based on title, category, and brand.', 'woo-ai-grouped-products'); ?></p>
                <p class="submit">
                    <button type="button" id="start-grouping" class="button button-primary">
                        <?php esc_html_e('Start Grouping Products', 'woo-ai-grouped-products'); ?>
                    </button>
                    <span id="progress-text"></span>
                </p>
                <div id="progress-bar" style="width: 100%; background-color: #f1f1f1; margin-top: 10px; display: none;">
                    <div id="progress-bar-inner" style="width: 0%; height: 30px; background-color: #4CAF50; text-align: center; line-height: 30px; color: white;">0%</div>
                </div>
                <div id="results" style="margin-top: 20px;"></div>
            </div>
        </div>
        <?php
    }

    public function process_product_grouping_ajax() {
        check_ajax_referer('wc_ai_grouped_products_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Insufficient permissions', 'woo-ai-grouped-products'));
        }

        // Get all simple products
        $args = array(
            'status' => 'publish',
            'limit' => -1,
            'type' => 'simple',
            'return' => 'ids',
        );

        $product_ids = wc_get_products($args);
        $total_products = count($product_ids);
        $processed = 0;
        $grouped_products = array();

        // First pass: Group products by category and brand
        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);
            if (!$product) continue;

            $brand = $this->get_product_brand($product);
            $categories = $this->get_product_categories($product);
            $title = $product->get_name();

            // Create a unique key for each brand + category combination
            foreach ($categories as $category) {
                $group_key = md5(strtolower($brand . '|' . $category));
                if (!isset($grouped_products[$group_key])) {
                    $grouped_products[$group_key] = array(
                        'brand' => $brand,
                        'category' => $category,
                        'products' => array(),
                    );
                }
                $grouped_products[$group_key]['products'][$product_id] = $title;
            }
        }

        // Second pass: Within each group, find similar products
        $product_groups = array();
        foreach ($grouped_products as $group) {
            if (count($group['products']) < 2) continue;

            $products = $group['products'];
            $processed_products = array();

            foreach ($products as $product_id => $title) {
                if (in_array($product_id, $processed_products)) continue;

                $similar_products = array($product_id);
                $base_title = $title;

                // Compare with other products in the same group
                foreach ($products as $compare_id => $compare_title) {
                    if ($product_id === $compare_id || in_array($compare_id, $processed_products)) continue;

                    $similarity = $this->calculate_title_similarity($base_title, $compare_title);
                    if ($similarity >= $this->min_title_similarity) {
                        $similar_products[] = $compare_id;
                        $processed_products[] = $compare_id;
                    }
                }

                if (count($similar_products) > 1) {
                    $product_groups[] = array(
                        'category' => $group['category'],
                        'brand' => $group['brand'],
                        'products' => $similar_products,
                    );
                }

                $processed_products[] = $product_id;
            }
        }

        // Third pass: Create variable products for each group
        $created_variables = array();
        foreach ($product_groups as $group) {
            if (count($group['products']) < 2) continue;

            $variable_product_id = $this->create_variable_product($group);
            if ($variable_product_id) {
                $created_variables[] = array(
                    'id' => $variable_product_id,
                    'title' => get_the_title($variable_product_id),
                    'count' => count($group['products'])
                );
            }

            wp_cache_flush();
        }

        wp_send_json_success(array(
            'message' => sprintf(
                _n('Created %d variable product', 'Created %d variable products', count($created_variables), 'woo-ai-grouped-products'),
                count($created_variables)
            ),
            'variables' => $created_variables,
        ));
    }

    private function get_product_brand($product) {
        // Check for brand in product attributes
        $attributes = $product->get_attributes();
        
        foreach ($this->brand_attributes as $brand_attr) {
            if (isset($attributes[$brand_attr])) {
                $attribute = $attributes[$brand_attr];
                if ($attribute->is_taxonomy()) {
                    $terms = wc_get_product_terms($product->get_id(), $attribute->get_name(), array('fields' => 'names'));
                    if (!empty($terms) && !is_wp_error($terms)) {
                        return $terms[0];
                    }
                }
            }
        }
        
        // If no brand attribute found, try to extract from title or return empty
        return '';
    }

    private function get_product_categories($product) {
        $categories = array();
        $terms = get_the_terms($product->get_id(), 'product_cat');
        
        if ($terms && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                $categories[] = $term->term_id;
            }
        }
        
        return $categories;
    }

    private function calculate_title_similarity($title1, $title2) {
        similar_text(
            strtolower(trim($title1)),
            strtolower(trim($title2)),
            $percent
        );
        return $percent;
    }

    private function create_variable_product($group) {
        if (empty($group['products'])) {
            return false;
        }

        // Get the first product as a base for the variable product
        $base_product_id = $group['products'][0];
        $base_product = wc_get_product($base_product_id);
        
        if (!$base_product) {
            return false;
        }

        // Create a new variable product
        $variable_product = new WC_Product_Variable();
        $variable_product->set_name($this->get_common_title($group['products']));
        $variable_product->set_status('publish');
        $variable_product->set_catalog_visibility('visible');
        $variable_product->set_category_ids($group['category']);
        $variable_product->set_manage_stock(true);
        $variable_product->set_stock_status('instock');
        
        // Copy other properties from the base product
        $variable_product->set_description($base_product->get_description());
        $variable_product->set_short_description($base_product->get_short_description());
        
        // Set the brand if it exists
        if (!empty($group['brand'])) {
            // Find the brand attribute
            $attribute = $this->find_or_create_attribute('brand');
            if ($attribute) {
                $attribute_name = 'pa_' . $attribute->attribute_name;
                $variable_product->set_attributes(array(
                    $attribute_name => array(
                        'name' => $attribute_name,
                        'value' => '',
                        'is_visible' => '1',
                        'is_variation' => '1',
                        'is_taxonomy' => '1'
                    )
                ));
                
                // Set the brand term
                wp_set_object_terms($variable_product->get_id(), $group['brand'], $attribute_name);
            }
        }
        
        // Save the variable product
        $variable_product_id = $variable_product->save();
        
        if (is_wp_error($variable_product_id)) {
            return false;
        }
        
        // Process variations
        $this->create_variations($variable_product_id, $group['products']);
        
        // Set the parent-child relationship
        foreach ($group['products'] as $child_id) {
            $child = wc_get_product($child_id);
            if ($child) {
                $child->set_parent_id($variable_product_id);
                $child->save();
            }
        }
        
        return $variable_product_id;
    }
    
    private function get_common_title($product_ids) {
        if (empty($product_ids)) {
            return '';
        }
        
        $titles = array();
        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
                $titles[] = $product->get_name();
            }
        }
        
        if (empty($titles)) {
            return '';
        }
        
        // Find the longest common substring among all titles
        $common = $titles[0];
        $len = strlen($common);
        
        foreach ($titles as $title) {
            $len = min($len, strlen($title));
            while ($len > 0 && strpos($title, substr($common, 0, $len)) !== 0) {
                $len--;
            }
            if ($len === 0) {
                break;
            }
            $common = substr($common, 0, $len);
        }
        
        // Clean up the common title
        $common = trim($common, " -_");
        
        // If we couldn't find a good common title, use the first product's title
        if (empty($common) || strlen($common) < 5) {
            return get_the_title($product_ids[0]);
        }
        
        return $common;
    }
    
    private function find_or_create_attribute($attribute_name) {
        global $wpdb;
        
        // Check if the attribute already exists
        $attribute = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s",
            $attribute_name
        ));
        
        if ($attribute) {
            return $attribute;
        }
        
        // Create the attribute
        $args = array(
            'name' => $attribute_name,
            'slug' => 'pa_' . $attribute_name,
            'type' => 'select',
            'order_by' => 'menu_order',
            'has_archives' => false
        );
        
        $id = wc_create_attribute($args);
        
        if (is_wp_error($id)) {
            return false;
        }
        
        // Clear the cache
        delete_transient('wc_attribute_taxonomies');
        
        // Register the taxonomy
        $taxonomy_name = wc_attribute_taxonomy_name($attribute_name);
        register_taxonomy($taxonomy_name, array('product'), array(
            'label' => ucfirst($attribute_name),
            'hierarchical' => true,
            'show_ui' => true,
            'query_var' => true,
            'rewrite' => array('slug' => sanitize_title($attribute_name)),
        ));
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s",
            $attribute_name
        ));
    }
    
    private function create_variations($variable_product_id, $product_ids) {
        if (empty($product_ids)) {
            return;
        }
        
        $variable_product = wc_get_product($variable_product_id);
        if (!$variable_product) {
            return;
        }
        
        $variation_attributes = array();
        
        // Get all possible attributes from the simple products
        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);
            if (!$product) continue;
            
            $attributes = $product->get_attributes();
            
            foreach ($attributes as $attribute_name => $attribute) {
                $attribute_name = str_replace('pa_', '', $attribute_name);
                
                // Skip brand attribute
                if (in_array($attribute_name, $this->brand_attributes)) {
                    continue;
                }
                
                if (!isset($variation_attributes[$attribute_name])) {
                    $variation_attributes[$attribute_name] = array(
                        'name' => $attribute_name,
                        'values' => array(),
                        'is_taxonomy' => $attribute->is_taxonomy(),
                    );
                }
                
                if ($attribute->is_taxonomy()) {
                    $terms = wc_get_product_terms($product_id, $attribute->get_name(), array('fields' => 'names'));
                    if (!empty($terms) && !is_wp_error($terms)) {
                        $variation_attributes[$attribute_name]['values'] = array_merge(
                            $variation_attributes[$attribute_name]['values'],
                            $terms
                        );
                    }
                } else {
                    $values = $attribute->get_options();
                    if (!empty($values)) {
                        $variation_attributes[$attribute_name]['values'] = array_merge(
                            $variation_attributes[$attribute_name]['values'],
                            $values
                        );
                    }
                }
                
                // Remove duplicates
                $variation_attributes[$attribute_name]['values'] = array_unique($variation_attributes[$attribute_name]['values']);
            }
        }
        
        // Filter out attributes that don't have multiple values
        foreach ($variation_attributes as $key => $attribute) {
            if (count($attribute['values']) <= 1) {
                unset($variation_attributes[$key]);
            }
        }
        
        // If no variation attributes found, use default ones (color, size)
        if (empty($variation_attributes)) {
            $variation_attributes = array(
                'color' => array(
                    'name' => 'color',
                    'values' => array(),
                    'is_taxonomy' => false,
                ),
                'size' => array(
                    'name' => 'size',
                    'values' => array(),
                    'is_taxonomy' => false,
                )
            );
        }
        
        // Set the variation attributes for the variable product
        $attributes = array();
        foreach ($variation_attributes as $attribute) {
            $attribute_name = $attribute['is_taxonomy'] ? 'pa_' . $attribute['name'] : $attribute['name'];
            
            $attributes[$attribute_name] = array(
                'name' => $attribute_name,
                'value' => '',
                'position' => 0,
                'is_visible' => 1,
                'is_variation' => 1,
                'is_taxonomy' => $attribute['is_taxonomy'] ? 1 : 0,
            );
            
            if ($attribute['is_taxonomy']) {
                // Ensure the taxonomy is registered
                if (!taxonomy_exists($attribute_name)) {
                    register_taxonomy($attribute_name, 'product_variation', array(
                        'hierarchical' => false,
                        'label' => ucfirst($attribute['name']),
                        'query_var' => true,
                        'rewrite' => array('slug' => sanitize_title($attribute['name'])),
                    ));
                }
                
                // Add the terms
                foreach ($attribute['values'] as $term_name) {
                    if (!term_exists($term_name, $attribute_name)) {
                        wp_insert_term($term_name, $attribute_name);
                    }
                }
            } else {
                $attributes[$attribute_name]['value'] = implode('|', array_map('wc_clean', $attribute['values']));
            }
        }
        
        $variable_product->set_attributes($attributes);
        $variable_product->save();
        
        // Create variations for each product
        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);
            if (!$product) continue;
            
            $variation = new WC_Product_Variation();
            $variation->set_parent_id($variable_product_id);
            
            // Set variation attributes
            $variation_attributes = array();
            foreach ($attributes as $attribute_name => $attribute) {
                $taxonomy = $attribute['is_taxonomy'] ? $attribute_name : 'attribute_' . $attribute_name;
                $value = '';
                
                if ($attribute['is_taxonomy']) {
                    $terms = wc_get_product_terms($product_id, $attribute_name, array('fields' => 'names'));
                    if (!empty($terms) && !is_wp_error($terms)) {
                        $value = $terms[0];
                    }
                } else {
                    $product_attributes = $product->get_attributes();
                    if (isset($product_attributes[$attribute_name])) {
                        $value = $product_attributes[$attribute_name]->get_options();
                        $value = is_array($value) ? current($value) : $value;
                    }
                }
                
                if (!empty($value)) {
                    $variation_attributes[$taxonomy] = $value;
                }
            }
            
            $variation->set_attributes($variation_attributes);
            
            // Copy product data to variation
            $variation->set_regular_price($product->get_regular_price());
            $variation->set_sale_price($product->get_sale_price());
            $variation->set_sku($product->get_sku());
            $variation->set_manage_stock($product->get_manage_stock());
            $variation->set_stock_quantity($product->get_stock_quantity());
            $variation->set_stock_status($product->get_stock_status());
            $variation->set_weight($product->get_weight());
            $variation->set_length($product->get_length());
            $variation->set_width($product->get_width());
            $variation->set_height($product->get_height());
            
            // Save EAN if it exists
            $ean = $product->get_meta('_ean');
            if (empty($ean)) {
                $ean = $product->get_meta('_barcode');
            }
            
            if (!empty($ean)) {
                $variation->update_meta_data('_ean', $ean);
            }
            
            // Save the variation
            $variation_id = $variation->save();
            
            if ($variation_id && !is_wp_error($variation_id)) {
                // Update the original product to be a draft
                wp_update_post(array(
                    'ID' => $product_id,
                    'post_status' => 'draft'
                ));
            }
        }
    }
}

// Initialize the plugin
function init_wc_ai_grouped_products() {
    return WC_AI_Grouped_Products::get_instance();
}
add_action('plugins_loaded', 'init_wc_ai_grouped_products');

// Activation hook
register_activation_hook(__FILE__, 'wc_ai_grouped_products_activate');
function wc_ai_grouped_products_activate() {
    // Schedule the initial grouping event
    if (!wp_next_scheduled('wc_ai_grouped_products_daily_event')) {
        wp_schedule_event(time(), 'daily', 'wc_ai_grouped_products_daily_event');
    }
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'wc_ai_grouped_products_deactivate');
function wc_ai_grouped_products_deactivate() {
    // Clear the scheduled event
    wp_clear_scheduled_hook('wc_ai_grouped_products_daily_event');
}

// Daily event to automatically group products
add_action('wc_ai_grouped_products_daily_event', 'wc_ai_grouped_products_process_daily');
function wc_ai_grouped_products_process_daily() {
    $plugin = WC_AI_Grouped_Products::get_instance();
    // This would need to be implemented to process products in batches
    // to avoid timeouts on large stores
}