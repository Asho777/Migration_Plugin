jQuery(document).ready(function($) {
    let backupInProgress = false;
    
    $('#start-backup').on('click', function() {
        if (backupInProgress) {
            return;
        }
        
        const button = $(this);
        const progressContainer = $('#backup-progress');
        const progressBar = $('.wsm-progress-fill');
        const progressText = $('.wsm-progress-text');
        // Disable button and show progress
        button.prop('disabled', true);
        button.text('Creating Backup...');
        progressContainer.show();
        backupInProgress = true;
        
        // Simulate progress updates
        let progress = 0;
        const progressInterval = setInterval(function() {
            progress += Math.random() * 15;
            if (progress > 90) {
                progress = 90;
            }
            progressBar.css('width', progress + '%');
            
            if (progress < 20) {
                progressText.text('Exporting database...');
            } else if (progress < 40) {
                progressText.text('Copying WordPress files...');
            } else if (progress < 60) {
                progressText.text('Copying media files...');
            } else if (progress < 80) {
                progressText.text('Creating installer...');
            } else {
                progressText.text('Finalizing backup...');
            }
        }, 500);
        
        // Start backup process
        $.ajax({
            url: wsm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wsm_start_backup',
                nonce: wsm_ajax.nonce,
                include_uploads: $('#include-uploads').is(':checked'),
                include_themes: $('#include-themes').is(':checked'),
                include_plugins: $('#include-plugins').is(':checked'),
                include_database: $('#include-database').is(':checked')
            },
            success: function(response) {
                clearInterval(progressInterval);
                progressBar.css('width', '100%');
                progressText.text('Backup completed successfully!');
                
                if (response.success) {
                    setTimeout(function() {
                        progressText.html(
                            '<span class="wsm-status-success">✓ Backup completed!</span> ' +
                            '<a href="' + response.data.download_url + '" class="button button-primary">Download Backup</a>'
                        );
                    }, 1000);
                    
                    // Refresh backup list
                    location.reload();
                } else {
                    progressText.html('<span class="wsm-status-error">✗ ' + response.data + '</span>');
                }
            },
            error: function(xhr, status, error) {
                clearInterval(progressInterval);
                progressBar.css('width', '100%');
                progressText.html('<span class="wsm-status-error">✗ Backup failed: ' + error + '</span>');
            },
            complete: function() {
                button.prop('disabled', false);
                button.text('Start Complete Backup');
                backupInProgress = false;
            }
        });
    });
    
    // Handle backup option changes
    $('.wsm-backup-options input[type="checkbox"]').on('change', function() {
        const checkedCount = $('.wsm-backup-options input[type="checkbox"]:checked').length;
        if (checkedCount === 0) {
            alert('Please select at least one backup option.');
            $(this).prop('checked', true);
        }
    });
});
