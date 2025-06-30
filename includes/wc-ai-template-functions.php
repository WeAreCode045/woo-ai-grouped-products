<?php
/**
 * Template functions for WooCommerce AI Grouped Products.
 *
 * @package WC_AI_Grouped_Products
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get the admin URL for the plugin.
 *
 * @param array $args Query args to add to the URL.
 * @return string
 */
function wc_ai_grouped_products_get_admin_url($args = array()) {
    return WC_AI_Admin::get_admin_url($args);
}

/**
 * Get the plugin settings.
 *
 * @param string $key Setting key.
 * @param mixed  $default Default value if setting doesn't exist.
 * @return mixed
 */
function wc_ai_grouped_products_get_setting($key = '', $default = null) {
    $settings = get_option('wc_ai_grouped_products_settings', array());
    
    if (empty($key)) {
        return $settings;
    }
    
    return isset($settings[$key]) ? $settings[$key] : $default;
}

/**
 * Check if a product is processed by the plugin.
 *
 * @param int $product_id Product ID.
 * @return bool
 */
function wc_ai_is_product_processed($product_id) {
    return 'yes' === get_post_meta($product_id, '_woo_ai_processed', true);
}

/**
 * Get the variable product ID for a processed product.
 *
 * @param int $product_id Product ID.
 * @return int|false Variable product ID or false if not found.
 */
function wc_ai_get_variable_product_id($product_id) {
    return get_post_meta($product_id, '_woo_ai_variable_product_id', true);
}

/**
 * Get the processing status for the current batch.
 *
 * @return array
 */
function wc_ai_get_processing_status() {
    $status = get_option('wc_ai_grouped_products_processing_status', array(
        'status' => 'idle',
        'message' => '',
        'progress' => 0,
        'processed' => 0,
        'total' => 0,
    ));
    
    return $status;
}

/**
 * Get the number of products that can be processed.
 *
 * @return int
 */
function wc_ai_get_processable_products_count() {
    $args = array(
        'status' => 'publish',
        'limit' => -1,
        'type' => 'simple',
        'return' => 'ids',
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => '_woo_ai_processed',
                'compare' => 'NOT EXISTS',
            ),
            array(
                'key' => '_woo_ai_processed',
                'value' => '1',
                'compare' => '!=',
            ),
        ),
    );
    
    $products = wc_get_products($args);
    return count($products);
}

/**
 * Get the number of variable products created by the plugin.
 *
 * @return int
 */
function wc_ai_get_created_variable_products_count() {
    $args = array(
        'status' => 'publish',
        'limit' => -1,
        'type' => 'variable',
        'return' => 'ids',
        'meta_query' => array(
            array(
                'key' => '_wc_ai_created',
                'value' => '1',
                'compare' => '=',
            ),
        ),
    );
    
    $products = wc_get_products($args);
    return count($products);
}

/**
 * Get the number of processed products.
 *
 * @return int
 */
function wc_ai_get_processed_products_count() {
    global $wpdb;
    
    $count = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s",
            '_woo_ai_processed',
            '1'
        )
    );
    
    return absint($count);
}

/**
 * Get the admin page URL.
 *
 * @param array $args Query args.
 * @return string
 */
function wc_ai_get_admin_page_url($args = array()) {
    $defaults = array(
        'page' => 'wc-ai-grouped-products',
    );
    
    $args = wp_parse_args($args, $defaults);
    
    return add_query_arg($args, admin_url('admin.php'));
}

/**
 * Display the admin notice.
 *
 * @param string $message Message to display.
 * @param string $type Notice type (error, warning, success, info).
 * @param bool   $dismissible Whether the notice is dismissible.
 */
function wc_ai_admin_notice($message, $type = 'info', $dismissible = true) {
    $class = 'notice notice-' . esc_attr($type);
    if ($dismissible) {
        $class .= ' is-dismissible';
    }
    
    printf(
        '<div class="%1$s"><p>%2$s</p></div>',
        esc_attr($class),
        wp_kses_post($message)
    );
}

/**
 * Get the plugin's asset URL.
 *
 * @param string $path Path to the asset relative to the assets directory.
 * @return string
 */
function wc_ai_get_asset_url($path = '') {
    return WC_AI_GROUPED_PRODUCTS_URL . 'assets/' . ltrim($path, '/');
}

/**
 * Get the plugin's template path.
 *
 * @return string
 */
function wc_ai_get_template_path() {
    return apply_filters('wc_ai_grouped_products_template_path', WC_AI_GROUPED_PRODUCTS_PATH . 'templates/');
}

/**
 * Get template part.
 *
 * @param string $slug Template slug.
 * @param string $name Template name (default: '').
 * @param array  $args Arguments to pass to the template.
 */
function wc_ai_get_template_part($slug, $name = '', $args = array()) {
    $template = '';
    $template_path = wc_ai_get_template_path();
    $plugin_path = WC_AI_GROUPED_PRODUCTS_PATH . 'templates/';
    
    // Look in yourtheme/slug-name.php and yourtheme/woocommerce-ai-grouped-products/slug-name.php
    if ($name) {
        $template = locate_template(array(
            "{$slug}-{$name}.php",
            $template_path . "{$slug}-{$name}.php"
        ));
    }
    
    // Get default slug-name.php
    if (!$template && $name && file_exists($plugin_path . "{$slug}-{$name}.php")) {
        $template = $plugin_path . "{$slug}-{$name}.php";
    }
    
    // If template file doesn't exist, look in yourtheme/slug.php and yourtheme/woocommerce-ai-grouped-products/slug.php
    if (!$template) {
        $template = locate_template(array(
            "{$slug}.php",
            $template_path . "{$slug}.php"
        ));
    }
    
    // Allow 3rd party plugins to filter template file from their plugin.
    $template = apply_filters('wc_ai_get_template_part', $template, $slug, $name);
    
    if ($template) {
        load_template($template, false, $args);
    }
}

/**
 * Get template.
 *
 * @param string $template_name Template name.
 * @param array  $args Arguments to pass to the template.
 * @param string $template_path Template path.
 * @param string $default_path Default path.
 */
function wc_ai_get_template($template_name, $args = array(), $template_path = '', $default_path = '') {
    if (!empty($args) && is_array($args)) {
        extract($args); // @codingStandardsIgnoreLine
    }
    
    $located = wc_ai_locate_template($template_name, $template_path, $default_path);
    
    if (!file_exists($located)) {
        /* translators: %s template */
        _doing_it_wrong(__FUNCTION__, sprintf(esc_html__('%s does not exist.', 'woo-ai-grouped-products'), '<code>' . $located . '</code>'), '1.0.0');
        return;
    }
    
    // Allow 3rd party plugin filter template file from their plugin.
    $located = apply_filters('wc_ai_get_template', $located, $template_name, $args, $template_path, $default_path);
    
    do_action('wc_ai_before_template_part', $template_name, $template_path, $located, $args);
    
    include $located;
    
    do_action('wc_ai_after_template_part', $template_name, $template_path, $located, $args);
}

/**
 * Locate a template and return the path for inclusion.
 *
 * @param string $template_name Template name.
 * @param string $template_path Template path. (default: '').
 * @param string $default_path Default path. (default: '').
 * @return string
 */
function wc_ai_locate_template($template_name, $template_path = '', $default_path = '') {
    if (!$template_path) {
        $template_path = wc_ai_get_template_path();
    }
    
    if (!$default_path) {
        $default_path = WC_AI_GROUPED_PRODUCTS_PATH . 'templates/';
    }
    
    // Look within passed path within the theme - this is priority.
    $template = locate_template(
        array(
            trailingslashit($template_path) . $template_name,
            $template_name,
        )
    );
    
    // Get default template/
    if (!$template) {
        $template = $default_path . $template_name;
    }
    
    // Return what we found.
    return apply_filters('wc_ai_locate_template', $template, $template_name, $template_path);
}
