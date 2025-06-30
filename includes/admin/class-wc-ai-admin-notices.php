// includes/admin/class-wc-ai-admin-notices.php
<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_AI_Admin_Notices {
    /**
     * Notices to display.
     *
     * @var array
     */
    private $notices = array();
    
    /**
     * Constructor.
     */
    public function __construct() {
        add_action('admin_notices', array($this, 'display_notices'));
    }
    
    /**
     * Add a notice.
     */
    public function add_notice($message, $type = 'success', $dismissible = true) {
        $this->notices[] = array(
            'message' => $message,
            'type' => $type,
            'dismissible' => $dismissible,
        );
    }
    
    /**
     * Display notices.
     */
    public function display_notices() {
        foreach ($this->notices as $notice) {
            $class = 'notice notice-' . $notice['type'];
            if ($notice['dismissible']) {
                $class .= ' is-dismissible';
            }
            
            printf(
                '<div class="%1$s"><p>%2$s</p></div>',
                esc_attr($class),
                esc_html($notice['message'])
            );
        }
    }
}