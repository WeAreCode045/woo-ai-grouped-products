<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get processing status
$status = WC_AI_Product_Matcher::instance()->get_processing_status();
?>
<div class="wrap woo-ai-dashboard">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="woo-ai-dashboard__content">
        <div class="woo-ai-dashboard__main">
            <div class="woo-ai-dashboard__card">
                <h2><?php esc_html_e('Product Processing', 'woo-ai-grouped-products'); ?></h2>
                
                <div class="woo-ai-processing-controls">
                    <button type="button" class="button button-primary" id="wc-ai-start-processing">
                        <?php esc_html_e('Start Processing', 'woo-ai-grouped-products'); ?>
                    </button>
                    <button type="button" class="button" id="wc-ai-stop-processing" style="display: none;">
                        <?php esc_html_e('Stop Processing', 'woo-ai-grouped-products'); ?>
                    </button>
                </div>
                
                <div class="woo-ai-processing-status" style="margin-top: 20px; display: none;">
                    <div class="woo-ai-progress-bar">
                        <div class="woo-ai-progress-bar__fill" style="width: 0%;"></div>
                    </div>
                    <div class="woo-ai-progress-text">
                        <span class="woo-ai-progress-percent">0%</span>
                        <span class="woo-ai-progress-counts">(0/0)</span>
                    </div>
                </div>
                
                <div class="woo-ai-processing-log" style="margin-top: 20px; max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9; display: none;">
                    <pre id="wc-ai-processing-log-content" style="margin: 0; white-space: pre-wrap; word-wrap: break-word;"></pre>
                </div>
            </div>
        </div>
        
        <div class="woo-ai-dashboard__sidebar">
            <div class="woo-ai-dashboard__card">
                <h3><?php esc_html_e('Processing Settings', 'woo-ai-grouped-products'); ?></h3>
                
                <form id="wc-ai-processing-settings">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="wc-ai-min-similarity"><?php esc_html_e('Minimum Similarity', 'woo-ai-grouped-products'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="wc-ai-min-similarity" name="min_similarity" min="50" max="100" value="85" class="small-text" />
                                <span class="description">%</span>
                                <p class="description"><?php esc_html_e('Minimum title similarity percentage', 'woo-ai-grouped-products'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="wc-ai-batch-size"><?php esc_html_e('Batch Size', 'woo-ai-grouped-products'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="wc-ai-batch-size" name="batch_size" min="1" max="100" value="20" class="small-text" />
                                <p class="description"><?php esc_html_e('Number of products to process per batch', 'woo-ai-grouped-products'); ?></p>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
            
            <div class="woo-ai-dashboard__card">
                <h3><?php esc_html_e('Statistics', 'woo-ai-grouped-products'); ?></h3>
                
                <ul class="woo-ai-stats">
                    <li>
                        <span class="woo-ai-stat-label"><?php esc_html_e('Total Products:', 'woo-ai-grouped-products'); ?></span>
                        <span class="woo-ai-stat-value" id="wc-ai-total-products">0</span>
                    </li>
                    <li>
                        <span class="woo-ai-stat-label"><?php esc_html_e('Processed:', 'woo-ai-grouped-products'); ?></span>
                        <span class="woo-ai-stat-value" id="wc-ai-processed-products">0</span>
                    </li>
                    <li>
                        <span class="woo-ai-stat-label"><?php esc_html_e('Variable Products Created:', 'woo-ai-grouped-products'); ?></span>
                        <span class="woo-ai-stat-value" id="wc-ai-variable-products">0</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>