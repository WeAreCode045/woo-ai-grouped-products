// templates/admin/settings/general.php
<?php
if (!defined('ABSPATH')) {
    exit;
}

$settings = WC_AI_Settings_General::instance();
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('wc_ai_grouped_products_settings');
        $settings->output();
        submit_button();
        ?>
    </form>
</div>