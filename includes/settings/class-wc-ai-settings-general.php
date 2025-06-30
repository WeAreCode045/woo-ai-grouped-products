// includes/settings/class-wc-ai-settings-general.php
<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_AI_Settings_General extends WC_AI_Settings {
    /**
     * Constructor.
     */
    public function __construct() {
        $this->id = 'general';
        $this->label = __('General', 'woo-ai-grouped-products');
        
        $this->settings = array(
            'general' => array(
                'title' => __('General Settings', 'woo-ai-grouped-products'),
                'fields' => array(
                    array(
                        'id' => 'wc_ai_enable_auto_processing',
                        'title' => __('Enable Auto Processing', 'woo-ai-grouped-products'),
                        'type' => 'checkbox',
                        'default' => 'no',
                        'desc' => __('Automatically process products on a schedule', 'woo-ai-grouped-products'),
                    ),
                    array(
                        'id' => 'wc_ai_processing_frequency',
                        'title' => __('Processing Frequency', 'woo-ai-grouped-products'),
                        'type' => 'select',
                        'options' => array(
                            'hourly' => __('Hourly', 'woo-ai-grouped-products'),
                            'twicedaily' => __('Twice Daily', 'woo-ai-grouped-products'),
                            'daily' => __('Daily', 'woo-ai-grouped-products'),
                        ),
                        'default' => 'daily',
                        'desc' => __('How often to automatically process products', 'woo-ai-grouped-products'),
                    ),
                ),
            ),
            'processing' => array(
                'title' => __('Processing Settings', 'woo-ai-grouped-products'),
                'fields' => array(
                    array(
                        'id' => 'wc_ai_min_title_similarity',
                        'title' => __('Minimum Title Similarity', 'woo-ai-grouped-products'),
                        'type' => 'number',
                        'default' => 85,
                        'min' => 50,
                        'max' => 100,
                        'step' => 1,
                        'desc' => __('Minimum similarity percentage for product titles to be grouped', 'woo-ai-grouped-products'),
                    ),
                    array(
                        'id' => 'wc_ai_batch_size',
                        'title' => __('Batch Size', 'woo-ai-grouped-products'),
                        'type' => 'number',
                        'default' => 20,
                        'min' => 1,
                        'max' => 100,
                        'step' => 1,
                        'desc' => __('Number of products to process in each batch', 'woo-ai-grouped-products'),
                    ),
                ),
            ),
        );
        
        add_filter('wc_ai_settings_tabs', array($this, 'add_settings_tab'), 10, 1);
        add_action('wc_ai_settings_' . $this->id, array($this, 'output'));
        add_action('wc_ai_settings_save_' . $this->id, array($this, 'save'));
    }
    
    /**
     * Add settings tab.
     */
    public function add_settings_tab($tabs) {
        $tabs[$this->id] = $this->label;
        return $tabs;
    }
    
    /**
     * Output settings.
     */
    public function output() {
        WC_Admin_Settings::output_fields($this->get_settings());
    }
    
    /**
     * Save settings.
     */
    public function save() {
        $settings = $this->get_settings();
        WC_Admin_Settings::save_fields($settings);
    }
}

return WC_AI_Settings_General::instance();