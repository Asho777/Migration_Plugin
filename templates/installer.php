<?php
/**
 * WordPress Site Migration Installer
 * 
 * This script handles the installation of a migrated WordPress site.
 * Upload this file along with your backup to the destination server.
 */

// Security and configuration
if (!defined('WSM_INSTALLER')) {
    define('WSM_INSTALLER', true);
}

// Configuration
$config = array(
    'max_execution_time' => 300,
    'memory_limit' => '512M',
    'backup_file' => '{{BACKUP_FILENAME}}',
    'original_url' => '{{ORIGINAL_URL}}',
    'step' => isset($_GET['step']) ? (int)$_GET['step'] : 1
);

// Set PHP limits
@set_time_limit($config['max_execution_time']);
@ini_set('memory_limit', $config['memory_limit']);

// Check if backup file exists
if (!file_exists($config['backup_file'])) {
    // Try to find any backup file
    $backup_files = glob('*_backup_*.zip');
    if (!empty($backup_files)) {
        $config['backup_file'] = $backup_files[0];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WordPress Site Migration Installer</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .header p {
            font-size: 16px;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        .content {
            padding: 40px;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
            position: relative;
        }
        
        .step-indicator::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 20%;
            right: 20%;
            height: 2px;
            background: #e2e8f0;
            z-index: 0;
        }
        
        .step {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 15px;
            font-weight: bold;
            position: relative;
            z-index: 1;
            transition: all 0.3s ease;
            border: 3px solid #fff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .step.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: scale(1.1);
        }
        
        .step.completed {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
        }
        
        .step.completed::after {
            content: '✓';
            font-size: 18px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2d3748;
        }
        
        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            text-decoration: none;
            color: white;
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #718096 0%, #4a5568 100%);
            box-shadow: 0 4px 12px rgba(113, 128, 150, 0.3);
        }
        
        .btn-secondary:hover {
            box-shadow: 0 6px 20px rgba(113, 128, 150, 0.4);
        }
        
        .alert {
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 8px;
            border-left: 4px solid;
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .alert-success {
            background: linear-gradient(135deg, #f0fff4 0%, #c6f6d5 100%);
            border-left-color: #48bb78;
            color: #22543d;
        }
        
        .alert-error {
            background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
            border-left-color: #f56565;
            color: #742a2a;
        }
        
        .alert-warning {
            background: linear-gradient(135deg, #fffbeb 0%, #fef5e7 100%);
            border-left-color: #ed8936;
            color: #744210;
        }
        
        .progress {
            width: 100%;
            height: 25px;
            background: #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            margin: 25px 0;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #48bb78, #38a169);
            width: 0%;
            transition: width 0.5s ease;
            border-radius: 12px;
            position: relative;
            overflow: hidden;
        }
        
        .progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255,255,255,0.2),
                transparent
            );
            animation: shimmer 2s infinite;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        .requirements-list {
            list-style: none;
            background: #f7fafc;
            border-radius: 8px;
            padding: 20px;
        }
        
        .requirements-list li {
            padding: 15px 0;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background-color 0.3s ease;
        }
        
        .requirements-list li:last-child {
            border-bottom: none;
        }
        
        .requirements-list li:hover {
            background-color: rgba(102, 126, 234, 0.05);
            border-radius: 6px;
        }
        
        .status-ok {
            color: #48bb78;
            font-weight: bold;
            display: flex;
            align-items: center;
        }
        
        .status-ok::before {
            content: '✓';
            margin-right: 5px;
            font-size: 18px;
        }
        
        .status-error {
            color: #f56565;
            font-weight: bold;
            display: flex;
            align-items: center;
        }
        
        .status-error::before {
            content: '✗';
            margin-right: 5px;
            font-size: 18px;
        }
        
        .text-center {
            text-align: center;
        }
        
        .mt-3 {
            margin-top: 30px;
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
            margin-right: 10px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .card {
            background: #f7fafc;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid #e2e8f0;
        }
        
        .card h3 {
            color: #2d3748;
            margin-bottom: 15px;
        }
        
        .card ol {
            padding-left: 20px;
        }
        
        .card ol li {
            margin-bottom: 10px;
            color: #4a5568;
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 8px;
            }
            
            .header {
                padding: 30px 20px;
            }
            
            .header h1 {
                font-size: 24px;
            }
            
            .content {
                padding: 30px 20px;
            }
            
            .step {
                width: 40px;
                height: 40px;
                margin: 0 10px;
            }
            
            .btn {
                width: 100%;
                text-align: center;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>WordPress Site Migration</h1>
            <p>Professional site migration and restoration tool</p>
        </div>
        
        <div class="content">
            <div class="step-indicator">
                <div class="step <?php echo $config['step'] == 1 ? 'active' : ($config['step'] > 1 ? 'completed' : ''); ?>">
                    <?php echo $config['step'] > 1 ? '' : '1'; ?>
                </div>
                <div class="step <?php echo $config['step'] == 2 ? 'active' : ($config['step'] > 2 ? 'completed' : ''); ?>">
                    <?php echo $config['step'] > 2 ? '' : '2'; ?>
                </div>
                <div class="step <?php echo $config['step'] == 3 ? 'active' : ($config['step'] > 3 ? 'completed' : ''); ?>">
                    <?php echo $config['step'] > 3 ? '' : '3'; ?>
                </div>
                <div class="step <?php echo $config['step'] == 4 ? 'active' : ($config['step'] > 4 ? 'completed' : ''); ?>">
                    <?php echo $config['step'] > 4 ? '' : '4'; ?>
                </div>
            </div>
            
            <?php
            switch ($config['step']) {
                case 1:
                    step1_requirements($config);
                    break;
                case 2:
                    step2_database($config);
                    break;
                case 3:
                    step3_extraction($config);
                    break;
                case 4:
                    step4_completion($config);
                    break;
                default:
                    step1_requirements($config);
            }
            ?>
        </div>
    </div>
    
    <script>
        // Auto-refresh for progress steps
        if (window.location.search.includes('step=3')) {
            setTimeout(function() {
                window.location.href = '?step=4';
            }, 3000);
        }
        
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    const button = form.querySelector('button[type="submit"]');
                    if (button) {
                        button.innerHTML = '<span class="loading"></span>Processing...';
                        button.disabled = true;
                    }
                });
            });
        });
    </script>
</body>
</html>

<?php
// Step 1: Requirements Check
function step1_requirements($config) {
    $requirements = array(
        'PHP Version >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'ZIP Extension' => extension_loaded('zip'),
        'MySQL Extension' => extension_loaded('mysqli') || extension_loaded('mysql'),
        'Write Permissions' => is_writable('.'),
        'Backup File Found' => !empty($config['backup_file']) && file_exists($config['backup_file'])
    );
    
    $all_passed = true;
    foreach ($requirements as $req => $status) {
        if (!$status) {
            $all_passed = false;
            break;
        }
    }
    
    echo '<h2 style="color: #2d3748; margin-bottom: 20px;">System Requirements Check</h2>';
    echo '<p style="margin-bottom: 30px; color: #4a5568;">Please ensure all requirements are met before proceeding with the installation.</p>';
    
    if (!empty($config['backup_file'])) {
        echo '<div class="card">';
        echo '<h3>Backup Information</h3>';
        echo '<p><strong>Backup File:</strong> ' . htmlspecialchars($config['backup_file']) . '</p>';
        echo '<p><strong>Original Site:</strong> ' . htmlspecialchars($config['original_url']) . '</p>';
        echo '<p><strong>File Size:</strong> ' . formatBytes(filesize($config['backup_file'])) . '</p>';
        echo '</div>';
    }
    
    echo '<ul class="requirements-list">';
    foreach ($requirements as $requirement => $status) {
        echo '<li>';
        echo '<span>' . $requirement . '</span>';
        echo '<span class="' . ($status ? 'status-ok' : 'status-error') . '">';
        echo $status ? 'OK' : 'FAILED';
        echo '</span>';
        echo '</li>';
    }
    echo '</ul>';
    
    if ($all_passed) {
        echo '<div class="alert alert-success">✓ All requirements met! You can proceed with the installation.</div>';
        echo '<div class="text-center mt-3">';
        echo '<a href="?step=2" class="btn">Continue to Database Setup</a>';
        echo '</div>';
    } else {
        echo '<div class="alert alert-error">⚠ Some requirements are not met. Please fix the issues above before continuing.</div>';
        
        if (empty($config['backup_file']) || !file_exists($config['backup_file'])) {
            echo '<div class="alert alert-warning">';
            echo '<strong>Missing Backup File:</strong> Please ensure you have uploaded the backup ZIP file to the same directory as this installer.';
            echo '</div>';
        }
    }
}

// Step 2: Database Configuration
function step2_database($config) {
    if ($_POST) {
        $db_host = trim($_POST['db_host']);
        $db_name = trim($_POST['db_name']);
        $db_user = trim($_POST['db_user']);
        $db_pass = $_POST['db_pass'];
        $site_url = rtrim(trim($_POST['site_url']), '/');
        
        // Test database connection
        $connection = @mysqli_connect($db_host, $db_user, $db_pass, $db_name);
        
        if ($connection) {
            // Save configuration
            $config_content = "<?php\n";
            $config_content .= "define('DB_NAME', '" . addslashes($db_name) . "');\n";
            $config_content .= "define('DB_USER', '" . addslashes($db_user) . "');\n";
            $config_content .= "define('DB_PASSWORD', '" . addslashes($db_pass) . "');\n";
            $config_content .= "define('DB_HOST', '" . addslashes($db_host) . "');\n";
            $config_content .= "define('SITE_URL', '" . addslashes($site_url) . "');\n";
            $config_content .= "define('ORIGINAL_URL', '" . addslashes($config['original_url']) . "');\n";
            
            file_put_contents('migration_config.php', $config_content);
            
            echo '<div class="alert alert-success">✓ Database connection successful!</div>';
            echo '<div class="text-center mt-3">';
            echo '<a href="?step=3" class="btn">Start Migration Process</a>';
            echo '</div>';
            
            mysqli_close($connection);
            return;
        } else {
            echo '<div class="alert alert-error">✗ Database connection failed: ' . mysqli_connect_error() . '</div>';
        }
    }
    
    echo '<h2 style="color: #2d3748; margin-bottom: 20px;">Database Configuration</h2>';
    echo '<p style="margin-bottom: 30px; color: #4a5568;">Enter your database details and new site URL for the migrated website.</p>';
    
    echo '<form method="post">';
    echo '<div class="form-group">';
    echo '<label>Database Host:</label>';
    echo '<input type="text" name="db_host" value="localhost" required placeholder="Usually localhost">';
    echo '</div>';
    
    echo '<div class="form-group">';
    echo '<label>Database Name:</label>';
    echo '<input type="text" name="db_name" required placeholder="Your database name">';
    echo '</div>';
    
    echo '<div class="form-group">';
    echo '<label>Database Username:</label>';
    echo '<input type="text" name="db_user" required placeholder="Database username">';
    echo '</div>';
    
    echo '<div class="form-group">';
    echo '<label>Database Password:</label>';
    echo '<input type="password" name="db_pass" placeholder="Database password (leave empty if none)">';
    echo '</div>';
    
    echo '<div class="form-group">';
    echo '<label>New Site URL:</label>';
    $current_url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
    echo '<input type="url" name="site_url" value="' . $current_url . '" required placeholder="https://your-new-domain.com">';
    echo '</div>';
    
    echo '<div class="text-center mt-3">';
    echo '<button type="submit" class="btn">Test Connection & Continue</button>';
    echo '</div>';
    echo '</form>';
}

// Step 3: Extraction and Installation
function step3_extraction($config) {
    if (!file_exists('migration_config.php')) {
        echo '<div class="alert alert-error">Configuration file not found. Please go back to database setup.</div>';
        echo '<div class="text-center mt-3">';
        echo '<a href="?step=2" class="btn btn-secondary">Back to Database Setup</a>';
        echo '</div>';
        return;
    }
    
    include 'migration_config.php';
    
    echo '<h2 style="color: #2d3748; margin-bottom: 20px;">Installing Your Website</h2>';
    echo '<p style="margin-bottom: 30px; color: #4a5568;">Please wait while we restore your website. This may take a few minutes...</p>';
    
    echo '<div class="progress">';
    echo '<div class="progress-bar" style="width: 100%;"></div>';
    echo '</div>';
    
    echo '<div style="text-align: center; margin: 20px 0;">';
    echo '<div class="loading"></div>';
    echo '<span>Processing migration...</span>';
    echo '</div>';
    
    // Perform the actual migration
    $result = perform_migration($config['backup_file']);
    
    if ($result['success']) {
        echo '<div class="alert alert-success">✓ Migration completed successfully!</div>';
        echo '<div class="text-center mt-3">';
        echo '<a href="?step=4" class="btn">Complete Installation</a>';
        echo '</div>';
    } else {
        echo '<div class="alert alert-error">✗ Migration failed: ' . $result['error'] . '</div>';
        echo '<div class="text-center mt-3">';
        echo '<a href="?step=2" class="btn btn-secondary">Back to Database Setup</a>';
        echo '</div>';
    }
}

// Step 4: Completion
function step4_completion($config) {
    if (!file_exists('migration_config.php')) {
        echo '<div class="alert alert-error">Configuration file not found.</div>';
        return;
    }
    
    include 'migration_config.php';
    
    echo '<h2 style="color: #2d3748; margin-bottom: 20px;">🎉 Migration Complete!</h2>';
    echo '<div class="alert alert-success">Your WordPress site has been successfully migrated and is ready to use!</div>';
    
    echo '<div class="card">';
    echo '<h3>Next Steps:</h3>';
    echo '<ol>';
    echo '<li><strong>Security:</strong> Delete this installer file and backup files from your server</li>';
    echo '<li><strong>DNS:</strong> Update your DNS settings to point to this server</li>';
    echo '<li><strong>SSL:</strong> Configure SSL certificate for your domain</li>';
    echo '<li><strong>Testing:</strong> Check your site functionality and permalinks</li>';
    echo '<li><strong>Updates:</strong> Update any hardcoded URLs in your content if needed</li>';
    echo '</ol>';
    echo '</div>';
    
    echo '<div class="card">';
    echo '<h3>Site Information:</h3>';
    echo '<p><strong>New Site URL:</strong> <a href="' . SITE_URL . '" target="_blank">' . SITE_URL . '</a></p>';
    echo '<p><strong>Admin Dashboard:</strong> <a href="' . SITE_URL . '/wp-admin" target="_blank">' . SITE_URL . '/wp-admin</a></p>';
    echo '<p><strong>Original Site:</strong> ' . (defined('ORIGINAL_URL') ? ORIGINAL_URL : 'Unknown') . '</p>';
    echo '</div>';
    
    echo '<div class="text-center mt-3">';
    echo '<a href="' . SITE_URL . '" class="btn" target="_blank">Visit Your Site</a>';
    echo '<a href="' . SITE_URL . '/wp-admin" class="btn btn-secondary" target="_blank" style="margin-left: 10px;">Admin Dashboard</a>';
    echo '</div>';
    
    // Clean up
    @unlink('migration_config.php');
}

// Migration function
function perform_migration($backup_file) {
    try {
        include 'migration_config.php';
        
        if (!file_exists($backup_file)) {
            throw new Exception('Backup file not found: ' . $backup_file);
        }
        
        // Extract backup
        $zip = new ZipArchive;
        $result = $zip->open($backup_file);
        
        if ($result === TRUE) {
            $zip->extractTo('./');
            $zip->close();
        } else {
            throw new Exception('Cannot extract backup file. Error code: ' . $result);
        }
        
        // Import database
        if (file_exists('database.sql')) {
            $connection = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            if (!connection) {
                throw new Exception('Database connection failed: ' . mysqli_connect_error());
            }
            
            $sql = file_get_contents('database.sql');
            
            // Replace URLs in database
            if (defined('ORIGINAL_URL') && ORIGINAL_URL) {
                $sql = str_replace(ORIGINAL_URL, SITE_URL, $sql);
                // Also replace serialized URLs
                $sql = str_replace(
                    serialize(ORIGINAL_URL),
                    serialize(SITE_URL),
                    $sql
                );
            }
            
            // Execute SQL in chunks
            $queries = explode(";\n", $sql);
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query)) {
                    if (!mysqli_query($connection, $query)) {
                        error_log('SQL Error: ' . mysqli_error($connection) . ' Query: ' . substr($query, 0, 100));
                    }
                }
            }
            
            mysqli_close($connection);
            unlink('database.sql');
        }
        
        // Generate wp-config.php
        generate_wp_config();
        
        return array('success' => true);
        
    } catch (Exception $e) {
        error_log('Migration error: ' . $e->getMessage());
        return array('success' => false, 'error' => $e->getMessage());
    }
}

// Generate wp-config.php
function generate_wp_config() {
    $wp_config = "<?php\n";
    $wp_config .= "/**\n * WordPress Configuration - Generated by Migration Tool\n */\n\n";
    
    $wp_config .= "// Database settings\n";
    $wp_config .= "define('DB_NAME', '" . DB_NAME . "');\n";
    $wp_config .= "define('DB_USER', '" . DB_USER . "');\n";
    $wp_config .= "define('DB_PASSWORD', '" . DB_PASSWORD . "');\n";
    $wp_config .= "define('DB_HOST', '" . DB_HOST . "');\n";
    $wp_config .= "define('DB_CHARSET', 'utf8');\n";
    $wp_config .= "define('DB_COLLATE', '');\n\n";
    
    // Add security keys
    $wp_config .= "// Security keys\n";
    $wp_config .= "define('AUTH_KEY',         '" . wp_generate_password(64, true, true) . "');\n";
    $wp_config .= "define('SECURE_AUTH_KEY',  '" . wp_generate_password(64, true, true) . "');\n";
    $wp_config .= "define('LOGGED_IN_KEY',    '" . wp_generate_password(64, true, true) . "');\n";
    $wp_config .= "define('NONCE_KEY',        '" . wp_generate_password(64, true, true) . "');\n";
    $wp_config .= "define('AUTH_SALT',        '" . wp_generate_password(64, true, true) . "');\n";
    $wp_config .= "define('SECURE_AUTH_SALT', '" . wp_generate_password(64, true, true) . "');\n";
    $wp_config .= "define('LOGGED_IN_SALT',   '" . wp_generate_password(64, true, true) . "');\n";
    $wp_config .= "define('NONCE_SALT',       '" . wp_generate_password(64, true, true) . "');\n\n";
    
    $wp_config .= "// WordPress settings\n";
    $wp_config .= "\$table_prefix = 'wp_';\n";
    $wp_config .= "define('WP_DEBUG', false);\n";
    $wp_config .= "define('WP_DEBUG_LOG', false);\n";
    $wp_config .= "define('WP_DEBUG_DISPLAY', false);\n\n";
    
    $wp_config .= "// Absolute path\n";
    $wp_config .= "if ( ! defined( 'ABSPATH' ) ) {\n";
    $wp_config .= "\tdefine( 'ABSPATH', __DIR__ . '/' );\n";
    $wp_config .= "}\n\n";
    
    $wp_config .= "// WordPress initialization\n";
    $wp_config .= "require_once ABSPATH . 'wp-settings.php';\n";
    
    file_put_contents('wp-config.php', $wp_config);
}

// Helper functions
function wp_generate_password($length = 12, $special_chars = true, $extra_special_chars = false) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    if ($special_chars) {
        $chars .= '!@#$%^&*()';
    }
    if ($extra_special_chars) {
        $chars .= '-_ []{}<>~`+=,.;:/?|';
    }
    
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= substr($chars, wp_rand(0, strlen($chars) - 1), 1);
    }
    
    return $password;
}

function wp_rand($min = 0, $max = 0) {
    return mt_rand($min, $max);
}

function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}
?>
