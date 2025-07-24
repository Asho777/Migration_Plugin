<?php
/**
 * WordPress Site Migration Installer
 * 
 * This script handles the installation of a migrated WordPress site.
 * Upload this file along with your backup to the destination server.
 */

// Security check
if (!defined('WSM_INSTALLER')) {
    define('WSM_INSTALLER', true);
}

// Configuration
$config = array(
    'max_execution_time' => 300,
    'memory_limit' => '512M',
    'backup_file' => '',
    'step' => isset($_GET['step']) ? (int)$_GET['step'] : 1
);

// Set PHP limits
@set_time_limit($config['max_execution_time']);
@ini_set('memory_limit', $config['memory_limit']);

// Find backup file
$backup_files = glob('*.zip');
foreach ($backup_files as $file) {
    if (strpos($file, '_backup_') !== false) {
        $config['backup_file'] = $file;
        break;
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
            background: #f1f1f1;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 800px;
            margin: 40px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .content {
            padding: 40px;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: bold;
            position: relative;
        }
        
        .step.active {
            background: #667eea;
            color: white;
        }
        
        .step.completed {
            background: #28a745;
            color: white;
        }
        
        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            right: -25px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 2px;
            background: #ddd;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
        }
        
        .btn {
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #5a6fd8;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        
        .progress {
            width: 100%;
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            margin: 20px 0;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            width: 0%;
            transition: width 0.3s ease;
        }
        
        .requirements-list {
            list-style: none;
        }
        
        .requirements-list li {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .status-ok {
            color: #28a745;
            font-weight: bold;
        }
        
        .status-error {
            color: #dc3545;
            font-weight: bold;
        }
        
        .text-center {
            text-align: center;
        }
        
        .mt-3 {
            margin-top: 20px;
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
                <div class="step <?php echo $config['step'] >= 1 ? 'active' : ''; ?> <?php echo $config['step'] > 1 ? 'completed' : ''; ?>">1</div>
                <div class="step <?php echo $config['step'] >= 2 ? 'active' : ''; ?> <?php echo $config['step'] > 2 ? 'completed' : ''; ?>">2</div>
                <div class="step <?php echo $config['step'] >= 3 ? 'active' : ''; ?> <?php echo $config['step'] > 3 ? 'completed' : ''; ?>">3</div>
                <div class="step <?php echo $config['step'] >= 4 ? 'active' : ''; ?> <?php echo $config['step'] > 4 ? 'completed' : ''; ?>">4</div>
            </div>
            
            <?php
            switch ($config['step']) {
                case 1:
                    include 'step1_requirements.php';
                    break;
                case 2:
                    include 'step2_database.php';
                    break;
                case 3:
                    include 'step3_extraction.php';
                    break;
                case 4:
                    include 'step4_completion.php';
                    break;
                default:
                    include 'step1_requirements.php';
            }
            ?>
        </div>
    </div>
    
    <script>
        // Auto-refresh for progress steps
        if (window.location.search.includes('step=3')) {
            setTimeout(function() {
                window.location.reload();
            }, 2000);
        }
    </script>
</body>
</html>

<?php
// Step 1: Requirements Check
if (!function_exists('step1_requirements')) {
    function step1_requirements() {
        global $config;
        
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
        
        echo '<h2>System Requirements Check</h2>';
        echo '<p>Please ensure all requirements are met before proceeding with the installation.</p>';
        
        echo '<ul class="requirements-list">';
        foreach ($requirements as $requirement => $status) {
            echo '<li>';
            echo '<span>' . $requirement . '</span>';
            echo '<span class="' . ($status ? 'status-ok' : 'status-error') . '">';
            echo $status ? '✓ OK' : '✗ FAILED';
            echo '</span>';
            echo '</li>';
        }
        echo '</ul>';
        
        if ($all_passed) {
            echo '<div class="alert alert-success">All requirements met! You can proceed with the installation.</div>';
            echo '<div class="text-center mt-3">';
            echo '<a href="?step=2" class="btn">Continue to Database Setup</a>';
            echo '</div>';
        } else {
            echo '<div class="alert alert-error">Some requirements are not met. Please fix the issues above before continuing.</div>';
        }
    }
}

// Step 2: Database Configuration
if (!function_exists('step2_database')) {
    function step2_database() {
        if ($_POST) {
            $db_host = $_POST['db_host'];
            $db_name = $_POST['db_name'];
            $db_user = $_POST['db_user'];
            $db_pass = $_POST['db_pass'];
            $site_url = $_POST['site_url'];
            
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
                
                file_put_contents('migration_config.php', $config_content);
                
                echo '<div class="alert alert-success">Database connection successful!</div>';
                echo '<div class="text-center mt-3">';
                echo '<a href="?step=3" class="btn">Start Migration</a>';
                echo '</div>';
                
                mysqli_close($connection);
                return;
            } else {
                echo '<div class="alert alert-error">Database connection failed: ' . mysqli_connect_error() . '</div>';
            }
        }
        
        echo '<h2>Database Configuration</h2>';
        echo '<p>Enter your database details and new site URL.</p>';
        
        echo '<form method="post">';
        echo '<div class="form-group">';
        echo '<label>Database Host:</label>';
        echo '<input type="text" name="db_host" value="localhost" required>';
        echo '</div>';
        
        echo '<div class="form-group">';
        echo '<label>Database Name:</label>';
        echo '<input type="text" name="db_name" required>';
        echo '</div>';
        
        echo '<div class="form-group">';
        echo '<label>Database Username:</label>';
        echo '<input type="text" name="db_user" required>';
        echo '</div>';
        
        echo '<div class="form-group">';
        echo '<label>Database Password:</label>';
        echo '<input type="password" name="db_pass">';
        echo '</div>';
        
        echo '<div class="form-group">';
        echo '<label>New Site URL:</label>';
        echo '<input type="url" name="site_url" value="http://' . $_SERVER['HTTP_HOST'] . '" required>';
        echo '</div>';
        
        echo '<div class="text-center mt-3">';
        echo '<button type="submit" class="btn">Test Connection & Continue</button>';
        echo '</div>';
        echo '</form>';
    }
}

// Step 3: Extraction and Installation
if (!function_exists('step3_extraction')) {
    function step3_extraction() {
        global $config;
        
        if (!file_exists('migration_config.php')) {
            echo '<div class="alert alert-error">Configuration file not found. Please go back to database setup.</div>';
            return;
        }
        
        include 'migration_config.php';
        
        echo '<h2>Installing Your Website</h2>';
        echo '<p>Please wait while we restore your website...</p>';
        
        echo '<div class="progress">';
        echo '<div class="progress-bar" style="width: 75%;"></div>';
        echo '</div>';
        
        // Perform the actual migration
        $success = perform_migration($config['backup_file']);
        
        if ($success) {
            echo '<div class="alert alert-success">Migration completed successfully!</div>';
            echo '<div class="text-center mt-3">';
            echo '<a href="?step=4" class="btn">Complete Installation</a>';
            echo '</div>';
        } else {
            echo '<div class="alert alert-error">Migration failed. Please check the error logs.</div>';
        }
    }
}

// Step 4: Completion
if (!function_exists('step4_completion')) {
    function step4_completion() {
        include 'migration_config.php';
        
        echo '<h2>Migration Complete!</h2>';
        echo '<div class="alert alert-success">Your WordPress site has been successfully migrated!</div>';
        
        echo '<h3>Next Steps:</h3>';
        echo '<ol>';
        echo '<li>Delete this installer file and backup files for security</li>';
        echo '<li>Update your DNS settings to point to this server</li>';
        echo '<li>Check your site configuration and permalinks</li>';
        echo '<li>Update any hardcoded URLs in your content</li>';
        echo '</ol>';
        
        echo '<div class="text-center mt-3">';
        echo '<a href="' . SITE_URL . '" class="btn" target="_blank">Visit Your Site</a>';
        echo '<a href="' . SITE_URL . '/wp-admin" class="btn btn-secondary" target="_blank">Admin Dashboard</a>';
        echo '</div>';
        
        // Clean up
        @unlink('migration_config.php');
    }
}

// Migration function
if (!function_exists('perform_migration')) {
    function perform_migration($backup_file) {
        try {
            include 'migration_config.php';
            
            // Extract backup
            $zip = new ZipArchive;
            if ($zip->open($backup_file) === TRUE) {
                $zip->extractTo('./');
                $zip->close();
            } else {
                throw new Exception('Cannot extract backup file');
            }
            
            // Import database
            if (file_exists('database.sql')) {
                $connection = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
                if (!$connection) {
                    throw new Exception('Database connection failed');
                }
                
                $sql = file_get_contents('database.sql');
                
                // Replace URLs in database
                $old_url = ''; // This would be extracted from the backup
                $sql = str_replace($old_url, SITE_URL, $sql);
                
                // Execute SQL
                mysqli_multi_query($connection, $sql);
                mysqli_close($connection);
                
                unlink('database.sql');
            }
            
            // Generate wp-config.php
            $wp_config = "<?php\n";
            $wp_config .= "define('DB_NAME', '" . DB_NAME . "');\n";
            $wp_config .= "define('DB_USER', '" . DB_USER . "');\n";
            $wp_config .= "define('DB_PASSWORD', '" . DB_PASSWORD . "');\n";
            $wp_config .= "define('DB_HOST', '" . DB_HOST . "');\n";
            $wp_config .= "define('DB_CHARSET', 'utf8');\n";
            $wp_config .= "define('DB_COLLATE', '');\n\n";
            
            // Add security keys
            $wp_config .= "define('AUTH_KEY',         '" . wp_generate_password(64, true, true) . "');\n";
            $wp_config .= "define('SECURE_AUTH_KEY',  '" . wp_generate_password(64, true, true) . "');\n";
            $wp_config .= "define('LOGGED_IN_KEY',    '" . wp_generate_password(64, true, true) . "');\n";
            $wp_config .= "define('NONCE_KEY',        '" . wp_generate_password(64, true, true) . "');\n";
            $wp_config .= "define('AUTH_SALT',        '" . wp_generate_password(64, true, true) . "');\n";
            $wp_config .= "define('SECURE_AUTH_SALT', '" . wp_generate_password(64, true, true) . "');\n";
            $wp_config .= "define('LOGGED_IN_SALT',   '" . wp_generate_password(64, true, true) . "');\n";
            $wp_config .= "define('NONCE_SALT',       '" . wp_generate_password(64, true, true) . "');\n\n";
            
            $wp_config .= "\$table_prefix = 'wp_';\n";
            $wp_config .= "define('WP_DEBUG', false);\n";
            $wp_config .= "if ( ! defined( 'ABSPATH' ) ) {\n";
            $wp_config .= "\tdefine( 'ABSPATH', __DIR__ . '/' );\n";
            $wp_config .= "}\n";
            $wp_config .= "require_once ABSPATH . 'wp-settings.php';\n";
            
            file_put_contents('wp-config.php', $wp_config);
            
            return true;
            
        } catch (Exception $e) {
            error_log('Migration error: ' . $e->getMessage());
            return false;
        }
    }
}

// Helper function for generating passwords
if (!function_exists('wp_generate_password')) {
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
}

if (!function_exists('wp_rand')) {
    function wp_rand($min = 0, $max = 0) {
        return mt_rand($min, $max);
    }
}
?>
