// woo-ai-grouped-products.php
<?php
/**
 * Plugin Name: Woo AI Grouped Products
 * Plugin URI: https://code045.nl/woo-ai-grouped-products
 * Description: Automatically groups similar WooCommerce products into variable products based on title, category, and brand.
 * Version: 1.2.0
 * Author: Code045
 * Author URI: https://code045.nl
 * Text Domain: woo-ai-grouped-products
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 7.0
 *
 * @package WC_AI_Grouped_Products
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants.
define('WC_AI_GROUPED_PRODUCTS_VERSION', '1.0.0');
define('WC_AI_GROUPED_PRODUCTS_FILE', __FILE__);
define('WC_AI_GROUPED_PRODUCTS_PATH', plugin_dir_path(__FILE__));
define('WC_AI_GROUPED_PRODUCTS_URL', plugin_dir_url(__FILE__));
define('WC_AI_GROUPED_PRODUCTS_BASENAME', plugin_basename(__FILE__));

/**
 * Autoloader for plugin classes.
 *
 * @param string $class_name The class name to load.
 */
function wc_ai_grouped_products_autoload($class_name) {
    if (0 !== strpos($class_name, 'WC_AI_')) {
        return;
    }

    $file = 'class-' . str_replace('_', '-', strtolower($class_name)) . '.php';
    $paths = array(
        'includes/',
        'includes/admin/',
        'includes/ajax/',
        'includes/api/',
        'includes/api/providers/',
        'includes/core/',
        'includes/settings/',
    );

    foreach ($paths as $path) {
        $full_path = WC_AI_GROUPED_PRODUCTS_PATH . $path . $file;
        if (file_exists($full_path)) {
            require_once $full_path;
            return;
        }
    }
}
spl_autoload_register('wc_ai_grouped_products_autoload');

/**
 * Main plugin class.
 */
class WC_AI_Grouped_Products {
    /**
     * The single instance of the class.
     *
     * @var WC_AI_Grouped_Products
     */
    private static $instance = null;

    /**
     * Admin instance.
     *
     * @var WC_AI_Admin
     */
    public $admin = null;

    /**
     * Settings instance.
     *
     * @var WC_AI_Settings
     */
    public $settings = null;

    /**
     * Product matcher instance.
     *
     * @var WC_AI_Product_Matcher
     */
    public $matcher = null;

    /**
     * Get the main plugin instance.
     *
     * @return WC_AI_Grouped_Products
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Include required files.
     */
    private function includes() {
        // Core classes
        require_once WC_AI_GROUPED_PRODUCTS_PATH . 'includes/core/class-wc-ai-product-matcher.php';
        require_once WC_AI_GROUPED_PRODUCTS_PATH . 'includes/core/class-wc-ai-variable-product.php';
    
        // Admin classes
        if (is_admin()) {
            // Base admin classes
            require_once WC_AI_GROUPED_PRODUCTS_PATH . 'includes/admin/class-wc-ai-admin.php';
            require_once WC_AI_GROUPED_PRODUCTS_PATH . 'includes/admin/class-wc-ai-admin-pages.php';
            require_once WC_AI_GROUPED_PRODUCTS_PATH . 'includes/admin/class-wc-ai-admin-assets.php';
            require_once WC_AI_GROUPED_PRODUCTS_PATH . 'includes/admin/class-wc-ai-admin-notices.php';
        
            // Settings
            require_once WC_AI_GROUPED_PRODUCTS_PATH . 'includes/settings/class-wc-ai-settings.php';
            require_once WC_AI_GROUPED_PRODUCTS_PATH . 'includes/settings/class-wc-ai-settings-general.php';
            require_once WC_AI_GROUPED_PRODUCTS_PATH . 'includes/settings/class-wc-ai-settings-ai.php';
        
            // AJAX
            require_once WC_AI_GROUPED_PRODUCTS_PATH . 'includes/ajax/class-wc-ai-ajax-handler.php';
            require_once WC_AI_GROUPED_PRODUCTS_PATH . 'includes/ajax/class-wc-ai-ajax-processing.php';
            require_once WC_AI_GROUPED_PRODUCTS_PATH . 'includes/ajax/class-wc-ai-ajax-settings.php';
        
            // API
            require_once WC_AI_GROUPED_PRODUCTS_PATH . 'includes/api/class-wc-ai-api-base.php';
            require_once WC_AI_GROUPED_PRODUCTS_PATH . 'includes/api/providers/class-wc-ai-api-openai.php';
        }
    }

    /**
     * Initialize hooks.
     */
    private function init_hooks() {
        // Load text domain
        add_action('init', array($this, 'load_plugin_textdomain'));
        
        // Initialize plugin components
        add_action('plugins_loaded', array($this, 'init_components'), 20);
        
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Initialize plugin components.
     */
    public function init_components() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }

        // Initialize core components
        $this->matcher = new WC_AI_Product_Matcher();
        
        // Initialize admin components
        if (is_admin()) {
            $this->admin = new WC_AI_Admin();
            $this->settings = new WC_AI_Settings_General();
        }
    }

    /**
     * Load plugin text domain.
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'woo-ai-grouped-products',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }

    /**
     * Display WooCommerce missing notice.
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="error">
            <p><?php _e('WooCommerce AI Grouped Products requires WooCommerce to be installed and active.', 'woo-ai-grouped-products'); ?></p>
        </div>
        <?php
    }

    /**
     * Plugin activation.
     */
    public static function activate() {
        // Create necessary database tables
        self::create_tables();
        
        // Schedule cron jobs
        if (!wp_next_scheduled('wc_ai_daily_processing')) {
            wp_schedule_event(time(), 'daily', 'wc_ai_daily_processing');
        }
    }

    /**
     * Plugin deactivation.
     */
    public static function deactivate() {
        // Clear scheduled hooks
        wp_clear_scheduled_hook('wc_ai_daily_processing');
    }

    /**
     * Create necessary database tables.
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wc_ai_processing_queue (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            attempts int(11) NOT NULL DEFAULT 0,
            last_attempt datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY product_id (product_id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Initialize the plugin.
WC_AI_Grouped_Products::instance();