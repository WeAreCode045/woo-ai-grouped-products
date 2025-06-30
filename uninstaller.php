<?php
// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete options
delete_option('wc_ai_settings');

// Delete post meta
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_wc_ai_%'");

// Delete scheduled hooks
wp_clear_scheduled_hook('wc_ai_process_products');