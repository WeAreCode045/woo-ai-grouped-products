// templates/admin/settings/general.php
<?php
if (!defined('ABSPATH')) {
    exit;
}

$settings = get_option('wc_ai_settings', array());
$min_similarity = isset($settings['min_similarity']) ? $settings['min_similarity'] : 85;
$batch_size = isset($settings['batch_size']) ? $settings['batch_size'] : 20;
$enable_auto_processing = isset($settings['enable_auto_processing']) ? $settings['enable_auto_processing'] : 'no';
$processing_frequency = isset($settings['processing_frequency']) ? $settings['processing_frequency'] : 'daily';
?>

<table class="form-table">
    <tr>
        <th scope="row">
            <label for="wc_ai_min_similarity"><?php _e('Minimum Title Similarity', 'woo-ai-grouped-products'); ?></label>
        </th>
        <td>
            <input type="number" 
                   id="wc_ai_min_similarity" 
                   name="wc_ai_settings[min_similarity]" 
                   value="<?php echo esc_attr($min_similarity); ?>" 
                   min="50" 
                   max="100" 
                   step="1" 
                   class="small-text" />
            <span class="description">%</span>
            <p class="description"><?php _e('Minimum similarity percentage for product titles to be grouped', 'woo-ai-grouped-products'); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row">
            <label for="wc_ai_batch_size"><?php _e('Batch Size', 'woo-ai-grouped-products'); ?></label>
        </th>
        <td>
            <input type="number" 
                   id="wc_ai_batch_size" 
                   name="wc_ai_settings[batch_size]" 
                   value="<?php echo esc_attr($batch_size); ?>" 
                   min="1" 
                   max="100" 
                   step="1" 
                   class="small-text" />
            <p class="description"><?php _e('Number of products to process in each batch', 'woo-ai-grouped-products'); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row">
            <label for="wc_ai_enable_auto_processing"><?php _e('Enable Auto Processing', 'woo-ai-grouped-products'); ?></label>
        </th>
        <td>
            <input type="checkbox" 
                   id="wc_ai_enable_auto_processing" 
                   name="wc_ai_settings[enable_auto_processing]" 
                   value="yes" 
                   <?php checked($enable_auto_processing, 'yes'); ?> />
            <p class="description"><?php _e('Automatically process products on a schedule', 'woo-ai-grouped-products'); ?></p>
        </td>
    </tr>
    <tr class="wc-ai-processing-frequency" style="<?php echo $enable_auto_processing !== 'yes' ? 'display: none;' : ''; ?>">
        <th scope="row">
            <label for="wc_ai_processing_frequency"><?php _e('Processing Frequency', 'woo-ai-grouped-products'); ?></label>
        </th>
        <td>
            <select id="wc_ai_processing_frequency" name="wc_ai_settings[processing_frequency]" class="regular-text">
                <option value="hourly" <?php selected($processing_frequency, 'hourly'); ?>>
                    <?php _e('Hourly', 'woo-ai-grouped-products'); ?>
                </option>
                <option value="twicedaily" <?php selected($processing_frequency, 'twicedaily'); ?>>
                    <?php _e('Twice Daily', 'woo-ai-grouped-products'); ?>
                </option>
                <option value="daily" <?php selected($processing_frequency, 'daily'); ?>>
                    <?php _e('Daily', 'woo-ai-grouped-products'); ?>
                </option>
                <option value="weekly" <?php selected($processing_frequency, 'weekly'); ?>>
                    <?php _e('Weekly', 'woo-ai-grouped-products'); ?>
                </option>
            </select>
            <p class="description"><?php _e('How often to automatically process products', 'woo-ai-grouped-products'); ?></p>
        </td>
    </tr>
</table>

<script type="text/javascript">
jQuery(document).ready(function($) {
    $('#wc_ai_enable_auto_processing').on('change', function() {
        $('.wc-ai-processing-frequency').toggle($(this).is(':checked'));
    });
});
</script>