// includes/settings/class-wc-ai-settings.php
<?php
if (!defined('ABSPATH')) {
    exit;
}

abstract class WC_AI_Settings {
    /**
     * The single instance of the class.
     *
     * @var object
     */
    protected static $instance = null;

    /**
     * Settings array.
     *
     * @var array
     */
    protected $settings = array();

    /**
     * Get class instance.
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * Get settings array.
     */
    public function get_settings() {
        return $this->settings;
    }

    /**
     * Sanitize settings.
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        if (empty($this->settings)) {
            return $sanitized;
        }
        
        foreach ($this->settings as $section) {
            if (empty($section['fields'])) {
                continue;
            }
            
            foreach ($section['fields'] as $field) {
                if (empty($field['id'])) {
                    continue;
                }
                
                $value = isset($input[$field['id']]) ? $input[$field['id']] : '';
                
                switch ($field['type']) {
                    case 'text':
                    case 'email':
                    case 'url':
                        $sanitized[$field['id']] = sanitize_text_field($value);
                        break;
                        
                    case 'number':
                        $sanitized[$field['id']] = is_numeric($value) ? floatval($value) : '';
                        break;
                        
                    case 'checkbox':
                        $sanitized[$field['id']] = isset($input[$field['id']]) ? 'yes' : 'no';
                        break;
                        
                    case 'textarea':
                        $sanitized[$field['id']] = wp_kses_post($value);
                        break;
                        
                    default:
                        $sanitized[$field['id']] = $value;
                        break;
                }
            }
        }
        
        return $sanitized;
    }
}