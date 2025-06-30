// includes/admin/class-wc-ai-admin.php
<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_AI_Admin {
    /**
     * Constructor.
     */
    public function __construct() {
        $this->pages = new WC_AI_Admin_Pages();
        $this->assets = new WC_AI_Admin_Assets();
        $this->notices = new WC_AI_Admin_Notices();
    }
}