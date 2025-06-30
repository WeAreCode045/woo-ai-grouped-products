// assets/js/admin.js
jQuery(document).ready(function($) {
    var isProcessing = false;
    var processingInterval = null;
    
    // Start processing
    $('#wc-ai-start-processing').on('click', function(e) {
        e.preventDefault();
        
        if (isProcessing) {
            return;
        }
        
        // Get settings
        var minSimilarity = parseInt($('#wc-ai-min-similarity').val()) || 85;
        var batchSize = parseInt($('#wc-ai-batch-size').val()) || 20;
        
        // Validate inputs
        if (minSimilarity < 50 || minSimilarity > 100) {
            alert('Minimum similarity must be between 50 and 100');
            return;
        }
        
        if (batchSize < 1 || batchSize > 100) {
            alert('Batch size must be between 1 and 100');
            return;
        }
        
        // Show processing UI
        startProcessingUI();
        
        // Send AJAX request
        $.ajax({
            url: wcAIGroupedProducts.ajax_url,
            type: 'POST',
            data: {
                action: 'wc_ai_start_processing',
                security: wcAIGroupedProducts.nonce,
                min_similarity: minSimilarity,
                batch_size: batchSize
            },
            success: function(response) {
                if (response.success) {
                    updateStatus();
                } else {
                    stopProcessingUI();
                    alert(response.data.message || 'An error occurred');
                }
            },
            error: function() {
                stopProcessingUI();
                alert('An error occurred while starting the process');
            }
        });
    });
    
    // Stop processing
    $('#wc-ai-stop-processing').on('click', function(e) {
        e.preventDefault();
        
        if (!isProcessing) {
            return;
        }
        
        // Send AJAX request
        $.ajax({
            url: wcAIGroupedProducts.ajax_url,
            type: 'POST',
            data: {
                action: 'wc_ai_stop_processing',
                security: wcAIGroupedProducts.nonce
            },
            complete: function() {
                stopProcessingUI();
            }
        });
    });
    
    // Update processing status
    function updateStatus() {
        if (!isProcessing) {
            return;
        }
        
        $.ajax({
            url: wcAIGroupedProducts.ajax_url,
            type: 'POST',
            data: {
                action: 'wc_ai_check_status',
                security: wcAIGroupedProducts.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateProcessingUI(response.data);
                    
                    // Continue checking status if still processing
                    if (response.data.status === 'processing') {
                        setTimeout(updateStatus, 2000);
                    } else {
                        stopProcessingUI();
                    }
                } else {
                    stopProcessingUI();
                    alert(response.data.message || 'An error occurred while checking status');
                }
            },
            error: function() {
                stopProcessingUI();
                alert('An error occurred while checking status');
            }
        });
    }
    
    // Start processing UI
    function startProcessingUI() {
        isProcessing = true;
        $('#wc-ai-start-processing').prop('disabled', true);
        $('#wc-ai-stop-processing').show();
        $('.woo-ai-processing-status').show();
        $('.woo-ai-processing-log').show();
        $('#wc-ai-processing-log-content').empty();
        
        // Start checking status
        updateStatus();
    }
    
    // Stop processing UI
    function stopProcessingUI() {
        isProcessing = false;
        $('#wc-ai-start-processing').prop('disabled', false);
        $('#wc-ai-stop-processing').hide();
    }
    
    // Update processing UI
    function updateProcessingUI(data) {
        // Update progress bar
        var progress = data.progress || 0;
        $('.woo-ai-progress-bar__fill').css('width', progress + '%');
        $('.woo-ai-progress-percent').text(progress + '%');
        
        // Update counts
        if (data.processed !== undefined && data.total !== undefined) {
            $('.woo-ai-progress-counts').text('(' + data.processed + '/' + data.total + ')');
        }
        
        // Update stats
        if (data.stats) {
            if (data.stats.total_products !== undefined) {
                $('#wc-ai-total-products').text(data.stats.total_products);
            }
            if (data.stats.processed_products !== undefined) {
                $('#wc-ai-processed-products').text(data.stats.processed_products);
            }
            if (data.stats.variable_products !== undefined) {
                $('#wc-ai-variable-products').text(data.stats.variable_products);
            }
        }
        
        // Update log
        if (data.logs && data.logs.length) {
            var $logContent = $('#wc-ai-processing-log-content');
            data.logs.forEach(function(log) {
                $logContent.prepend('<div class="log-entry">[' + log.time + '] ' + log.message + '</div>');
            });
            
            // Auto-scroll to bottom
            $logContent.scrollTop($logContent[0].scrollHeight);
        }
    }
    
    // Initialize
    function init() {
        // Load initial stats
        $.ajax({
            url: wcAIGroupedProducts.ajax_url,
            type: 'POST',
            data: {
                action: 'wc_ai_get_stats',
                security: wcAIGroupedProducts.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateProcessingUI({ stats: response.data });
                }
            }
        });
    }
    
    // Initialize on page load
    init();
});