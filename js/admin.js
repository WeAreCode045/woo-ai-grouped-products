jQuery(document).ready(function($) {
    var isProcessing = false;
    var totalGroups = 0;
    var processedGroups = 0;

    // Start the product grouping process
    $('#start-grouping').on('click', function(e) {
        e.preventDefault();
        
        if (isProcessing) {
            return;
        }
        
        if (!confirm('This will analyze and group your products. It may take a while. Continue?')) {
            return;
        }
        
        isProcessing = true;
        $('#progress-text').text(wcAIGroupedProducts.processing);
        $('#progress-bar').show();
        $('#start-grouping').prop('disabled', true);
        
        // Start the process
        processBatch();
    });
    
    function processBatch() {
        $.ajax({
            url: wcAIGroupedProducts.ajax_url,
            type: 'POST',
            data: {
                action: 'process_product_grouping',
                nonce: wcAIGroupedProducts.nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    processedGroups += response.data.variables ? response.data.variables.length : 0;
                    
                    // Update progress
                    if (response.data.variables && response.data.variables.length > 0) {
                        var html = '<h3>' + response.data.message + '</h3><ul>';
                        
                        $.each(response.data.variables, function(i, variable) {
                            html += '<li><a href="' + wcAIGroupedProducts.admin_url + 'post.php?post=' + 
                                   variable.id + '&action=edit" target="_blank">' + 
                                   variable.title + '</a> - ' + variable.count + ' variations</li>';
                        });
                        
                        html += '</ul>';
                        $('#results').append(html);
                    } else {
                        $('#results').append('<p>' + response.data.message + '</p>');
                    }
                    
                    // Scroll to bottom to show progress
                    $('html, body').animate({
                        scrollTop: $(document).height()
                    }, 1000);
                    
                    // Check if we're done
                    if (response.data.complete) {
                        completeProcessing(response.data.message);
                    } else {
                        // Process next batch
                        setTimeout(processBatch, 500);
                    }
                } else {
                    showError(response.data);
                }
            },
            error: function(xhr, status, error) {
                showError('Error: ' + error);
            }
        });
    }
    
    function updateProgress(processed, total) {
        var percentage = Math.round((processed / total) * 100);
        $('#progress-bar-inner').css('width', percentage + '%').text(percentage + '%');
    }
    
    function completeProcessing(message) {
        isProcessing = false;
        $('#progress-text').text(wcAIGroupedProducts.complete + ' ' + message);
        $('#start-grouping').prop('disabled', false);
        updateProgress(100, 100);
    }
    
    function showError(message) {
        isProcessing = false;
        $('#progress-text').text('Error: ' + message);
        $('#start-grouping').prop('disabled', false);
        $('#progress-bar-inner').css('background-color', '#dc3232');
    }
});
