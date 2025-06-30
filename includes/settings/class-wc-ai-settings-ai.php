// includes/settings/class-wc-ai-settings-ai.php
<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_AI_Settings_AI extends WC_AI_Settings {
    /**
     * Constructor.
     */
    public function __construct() {
        $this->id = 'ai';
        $this->label = __('AI Integration', 'woo-ai-grouped-products');
        
        $this->settings = array(
            'openai' => array(
                'title' => __('OpenAI Settings', 'woo-ai-grouped-products'),
                'fields' => array(
                    array(
                        'id' => 'wc_ai_openai_api_key',
                        'title' => __('OpenAI API Key', 'woo-ai-grouped-products'),
                        'type' => 'password',
                        'default' => '',
                        'desc' => __('Enter your OpenAI API key', 'woo-ai-grouped-products'),
                        'custom_attributes' => array(
                            'autocomplete' => 'off',
                        ),
                    ),
                    array(
                        'id' => 'wc_ai_openai_model',
                        'title' => __('Model', 'woo-ai-grouped-products'),
                        'type' => 'select',
                        'options' => array(
                            'gpt-4' => 'GPT-4',
                            'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
                        ),
                        'default' => 'gpt-3.5-turbo',
                        'desc' => __('Select which model to use', 'woo-ai-grouped-products'),
                    ),
                    array(
                        'id' => 'wc_ai_openai_temperature',
                        'title' => __('Temperature', 'woo-ai-grouped-products'),
                        'type' => 'number',
                        'default' => 0.7,
                        'min' => 0,
                        'max' => 2,
                        'step' => 0.1,
                        'desc' => __('Controls randomness (0 = deterministic, 2 = very random)', 'woo-ai-grouped-products'),
                    ),
                    array(
                        'id' => 'wc_ai_openai_max_tokens',
                        'title' => __('Max Tokens', 'woo-ai-grouped-products'),
                        'type' => 'number',
                        'default' => 1000,
                        'min' => 100,
                        'max' => 4000,
                        'step' => 100,
                        'desc' => __('Maximum number of tokens to generate in the response', 'woo-ai-grouped-products'),
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
        $settings = $this->get_settings();
        
        echo '<div class="woo-ai-settings-tabs">';
        
        // Output tab navigation
        echo '<nav class="nav-tab-wrapper woo-nav-tab-wrapper">';
        foreach ($settings as $section_id => $section) {
            $class = ($section_id === key($settings)) ? 'nav-tab nav-tab-active' : 'nav-tab';
            echo '<a href="#' . esc_attr($section_id) . '" class="' . esc_attr($class) . '">' . esc_html($section['title']) . '</a>';
        }
        echo '</nav>';
        
        // Output tab content
        echo '<div class="woo-ai-settings-sections">';
        foreach ($settings as $section_id => $section) {
            echo '<div id="' . esc_attr($section_id) . '" class="woo-ai-settings-section">';
            echo '<h2>' . esc_html($section['title']) . '</h2>';
            
            if (!empty($section['desc'])) {
                echo '<p>' . wp_kses_post($section['desc']) . '</p>';
            }
            
            WC_Admin_Settings::output_fields($section['fields']);
            
            echo '</div>';
        }
        echo '</div>';
        
        echo '</div>';
        
        // Add JavaScript for tab switching
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Handle tab clicks
            $('.woo-ai-settings-tabs .nav-tab').on('click', function(e) {
                e.preventDefault();
                
                // Update active tab
                $('.woo-ai-settings-tabs .nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                // Show active section
                var target = $(this).attr('href');
                $('.woo-ai-settings-section').hide();
                $(target).show();
                
                // Update URL hash
                if (history.pushState) {
                    history.pushState(null, null, target);
                } else {
                    window.location.hash = target;
                }
            });
            
            // Check for hash on page load
            if (window.location.hash) {
                var tab = $('.woo-ai-settings-tabs .nav-tab[href="' + window.location.hash + '"]');
                if (tab.length) {
                    tab.trigger('click');
                    return;
                }
            }
            
            // Default to first tab
            $('.woo-ai-settings-tabs .nav-tab:first').trigger('click');
        });
        </script>
        <?php
    }
    
    /**
     * Save settings.
     */
    public function save() {
        $settings = $this->get_settings();
        
        foreach ($settings as $section_id => $section) {
            WC_Admin_Settings::save_fields($section['fields']);
        }
    }
    
    /**
     * Get settings array.
     */
    public function get_settings() {
        return $this->settings;
    }
}

return WC_AI_Settings_AI::instance();