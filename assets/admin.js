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
    
    // Show spinner and initial text
    progressText.html('<div class="wsm-spinner"></div> Initializing backup...');
    
    // Simulate progress updates with spinner
    let progress = 0;
    const progressInterval = setInterval(function() {
      progress += Math.random() * 10;
      if (progress > 90) {
        progress = 90;
      }
      progressBar.css('width', progress + '%');
      
      let statusText = '';
      if (progress < 20) {
        statusText = 'Exporting database...';
      } else if (progress < 40) {
        statusText = 'Copying WordPress files...';
      } else if (progress < 60) {
        statusText = 'Copying media files...';
      } else if (progress < 80) {
        statusText = 'Creating installer...';
      } else {
        statusText = 'Finalizing backup...';
      }
      
      progressText.html('<div class="wsm-spinner"></div> ' + statusText);
    }, 800);
    
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
      timeout: 300000, // 5 minutes timeout
      success: function(response) {
        clearInterval(progressInterval);
        progressBar.css('width', '100%');
        
        if (response.success) {
          progressText.html('<span class="wsm-status-success">✓ Backup completed successfully!</span>');
          
          setTimeout(function() {
            progressText.html(
              '<span class="wsm-status-success">✓ Backup completed!</span><br>' +
              '<a href="' + response.data.backup_url + '" class="button button-primary" style="margin-right: 10px; margin-top: 10px;">Download Backup</a>' +
              '<button class="button button-secondary wsm-download-installer-btn" data-backup="' + response.data.backup_filename.replace('.zip', '').replace('_backup_', '_backup_') + '" style="margin-top: 10px;">Download Installer</button>'
            );
          }, 1500);
          
          // Refresh backup list after 5 seconds
          setTimeout(function() {
            location.reload();
          }, 6000);
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
  
  // Handle installer download
  $(document).on('click', '.wsm-btn-installer, .wsm-download-installer-btn', function(e) {
    e.preventDefault();
    
    const button = $(this);
    const backupName = button.data('backup');
    const originalText = button.html();
    
    button.prop('disabled', true);
    button.html('<span class="dashicons dashicons-update"></span> <span class="wsm-btn-text">Loading...</span>');
    
    // Create a temporary form to download the installer
    const downloadUrl = wsm_ajax.ajax_url + 
      '?action=wsm_download_installer' +
      '&nonce=' + encodeURIComponent(wsm_ajax.nonce) +
      '&backup_name=' + encodeURIComponent(backupName);
    
    // Create temporary link and trigger download
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = 'installer.php';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Reset button after a short delay
    setTimeout(function() {
      button.prop('disabled', false);
      button.html(originalText);
    }, 2000);
  });
  
  // Handle backup deletion
  $(document).on('click', '.wsm-delete-backup', function() {
    if (!confirm('Are you sure you want to delete this backup? This action cannot be undone.')) {
      return;
    }
    
    const button = $(this);
    const backupName = button.data('backup');
    const row = button.closest('tr');
    const originalText = button.html();
    
    button.prop('disabled', true);
    button.html('<span class="dashicons dashicons-update"></span> <span class="wsm-btn-text">Deleting...</span>');
    
    $.ajax({
      url: wsm_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'wsm_delete_backup',
        nonce: wsm_ajax.nonce,
        backup_name: backupName
      },
      success: function(response) {
        if (response.success) {
          row.fadeOut(300, function() {
            row.remove();
            
            // Check if table is empty
            if ($('.wp-list-table tbody tr').length === 0) {
              $('.wsm-card:nth-child(2)').html(
                '<h2>Existing Backups</h2>' +
                '<p>No backups found. Create your first backup above.</p>'
              );
            }
          });
        } else {
          alert('Failed to delete backup: ' + response.data);
          button.prop('disabled', false);
          button.html(originalText);
        }
      },
      error: function() {
        alert('Failed to delete backup. Please try again.');
        button.prop('disabled', false);
        button.html(originalText);
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
