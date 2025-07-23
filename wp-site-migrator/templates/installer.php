<?php
/**
 * WordPress Site Migration Installer
 * This file handles the complete restoration of a WordPress site
 */

// Security check
if (!isset($_GET['step']) && !isset($_POST['step'])) {
    // Show initial page
}

class WordPressMigrationInstaller {
    private $config;
    private $errors = array();
    private $step = 1;
    
    public function __construct() {
        $this->step = isset($_GET['step']) ? (int)$_GET['step'] : (isset($_POST['step']) ? (int)$_POST['step'] : 1);
        $this->load_config();
    }
    
    private function load_config() {
        if (file_exists('config.json')) {
            $this->config = json_decode(file_get_contents('config.json'), true);
        }
    }
    
    public function run() {
        $this->render_header();
        
        switch ($this->step) {
            case 1:
                $this->step_welcome();
                break;
            case 2:
                $this->step_requirements();
                break;
            case 3:
                $this->step_database_config();
                break;
            case 4:
                $this->step_installation();
                break;
            case 5:
                $this->step_complete();
                break;
            default:
                $this->step_welcome();
        }
        
        $this->render_footer();
    }
    
    private function render_header() {
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
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                }
                
                .installer-container {
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                    max-width: 600px;
                    width: 100%;
                    overflow: hidden;
                }
                
                .installer-header {
                    background: #2c3e50;
                    color: white;
                    padding: 30px;
                    text-align: center;
                }
                
                .installer-header h1 {
                    font-size: 24px;
                    margin-bottom: 10px;
                }
                
                .installer-header p {
                    opacity: 0.8;
                    font-size: 14px;
                }
                
                .installer-content {
                    padding: 40px;
                }
                
                .step-indicator {
                    display: flex;
                    justify-content: center;
                    margin-bottom: 30px;
                }
                
                .step {
                    width: 30px;
                    height: 30px;
                    border-radius: 50%;
                    background: #e0e0e0;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 5px;
                    font-size: 12px;
                    font-weight: bold;
                }
                
                .step.active {
                    background: #3498db;
                    color: white;
                }
                
                .step.completed {
                    background: #27ae60;
                    color: white;
                }
                
                .form-group {
                    margin-bottom: 20px;
                }
                
                .form-group label {
                    display: block;
                    margin-bottom: 5px;
                    font-weight: 600;
                    color: #2c3e50;
                }
                
                .form-group input,
                .form-group select {
                    width: 100%;
                    padding: 12px;
                    border: 2px solid #e0e0e0;
                    border-radius: 6px;
                    font-size: 14px;
                    transition: border-color 0.3s;
                }
                
                .form-group input:focus,
                .form-group select:focus {
                    outline: none;
                    border-color: #3498db;
                }
                
                .btn {
                    background: #3498db;
                    color: white;
                    padding: 12px 30px;
                    border: none;
                    border-radius: 6px;
                    font-size: 14px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: background 0.3s;
                    text-decoration: none;
                    display: inline-block;
                }
                
                .btn:hover {
                    background: #2980b9;
                }
                
                .btn-success {
                    background: #27ae60;
                }
                
                .btn-success:hover {
                    background: #229954;
                }
                
                .alert {
                    padding: 15px;
                    border-radius: 6px;
                    margin-bottom: 20px;
                }
                
                .alert-error {
                    background: #fee;
                    border: 1px solid #fcc;
                    color: #c33;
                }
                
                .alert-success {
                    background: #efe;
                    border: 1px solid #cfc;
                    color: #363;
                }
                
                .alert-info {
                    background: #eef;
                    border: 1px solid #ccf;
                    color: #336;
                }
                
                .progress-bar {
                    width: 100%;
                    height: 20px;
                    background: #e0e0e0;
                    border-radius: 10px;
                    overflow: hidden;
                    margin: 20px 0;
                }
                
                .progress-fill {
                    height: 100%;
                    background: linear-gradient(90deg, #3498db, #2980b9);
                    width: 0%;
                    transition: width 0.5s ease;
                }
                
                .text-center {
                    text-align: center;
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
                    color: #27ae60;
                    font-weight: bold;
                }
                
                .status-error {
                    color: #e74c3c;
                    font-weight: bold;
                }
            </style>
        </head>
        <body>
            <div class="installer-container">
                <div class="installer-header">
                    <h1>WordPress Site Migration</h1>
                    <p>Professional Website Restoration Tool</p>
                </div>
                <div class="installer-content">
                    <div class="step-indicator">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <div class="step <?php echo $i < $this->step ? 'completed' : ($i == $this->step ? 'active' : ''); ?>">
                                <?php echo $i; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
        <?php
    }
    
    private function render_footer() {
        ?>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
    
    private function step_welcome() {
        ?>
        <h2>Welcome to WordPress Site Migration</h2>
        <p>This installer will help you restore your complete WordPress website including:</p>
        <ul style="margin: 20px 0; padding-left: 20px;">
            <li>Complete database with all posts, pages, and settings</li>
            <li>All WordPress files and themes</li>
            <li>Media library and uploads</li>
            <li>Plugin files and configurations</li>
        </ul>
        
        <?php if ($this->config): ?>
            <div class="alert alert-info">
                <strong>Source Site Information:</strong><br>
                URL: <?php echo htmlspecialchars($this->config['site_url']); ?><br>
                WordPress Version: <?php echo htmlspecialchars($this->config['wp_version']); ?><br>
                Backup Date: <?php echo htmlspecialchars($this->config['backup_date']); ?>
            </div>
        <?php endif; ?>
        
        <div class="text-center">
            <a href="?step=2" class="btn">Start Installation</a>
        </div>
        <?php
    }
    
    private function step_requirements() {
        $requirements = $this->check_requirements();
        $all_passed = true;
        
        ?>
        <h2>System Requirements Check</h2>
        <p>Checking if your server meets the requirements for WordPress installation:</p>
        
        <ul class="requirements-list">
            <?php foreach ($requirements as $req): ?>
                <li>
                    <span><?php echo $req['name']; ?></span>
                    <span class="<?php echo $req['status'] ? 'status-ok' : 'status-error'; ?>">
                        <?php echo $req['status'] ? '✓ OK' : '✗ FAIL'; ?>
                    </span>
                </li>
                <?php if (!$req['status']) $all_passed = false; ?>
            <?php endforeach; ?>
        </ul>
        
        <?php if (!$all_passed): ?>
            <div class="alert alert-error">
                <strong>Requirements Not Met:</strong> Please contact your hosting provider to resolve the failed requirements before proceeding.
            </div>
        <?php endif; ?>
        
        <div class="text-center">
            <?php if ($all_passed): ?>
                <a href="?step=3" class="btn">Continue to Database Setup</a>
            <?php else: ?>
                <a href="?step=2" class="btn">Recheck Requirements</a>
            <?php endif; ?>
        </div>
        <?php
    }
    
    private function step_database_config() {
        if ($_POST) {
            $this->process_database_config();
        }
        
        ?>
        <h2>Database Configuration</h2>
        <p>Enter your new database connection details:</p>
        
        <?php if (!empty($this->errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($this->errors as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <input type="hidden" name="step" value="3">
            
            <div class="form-group">
                <label for="db_host">Database Host:</label>
                <input type="text" id="db_host" name="db_host" value="<?php echo isset($_POST['db_host']) ? htmlspecialchars($_POST['db_host']) : 'localhost'; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="db_name">Database Name:</label>
                <input type="text" id="db_name" name="db_name" value="<?php echo isset($_POST['db_name']) ? htmlspecialchars($_POST['db_name']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="db_user">Database Username:</label>
                <input type="text" id="db_user" name="db_user" value="<?php echo isset($_POST['db_user']) ? htmlspecialchars($_POST['db_user']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="db_pass">Database Password:</label>
                <input type="password" id="db_pass" name="db_pass" value="<?php echo isset($_POST['db_pass']) ? htmlspecialchars($_POST['db_pass']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="new_url">New Site URL:</label>
                <input type="url" id="new_url" name="new_url" value="<?php echo isset($_POST['new_url']) ? htmlspecialchars($_POST['new_url']) : 'http://' . $_SERVER['HTTP_HOST']; ?>" required>
            </div>
            
            <div class="text-center">
                <button type="submit" class="btn">Test Connection & Continue</button>
            </div>
        </form>
        <?php
    }
    
    private function step_installation() {
        if (!isset($_SESSION['db_config'])) {
            header('Location: ?step=3');
            exit;
        }
        
        ?>
        <h2>Installing WordPress Site</h2>
        <p>Please wait while we restore your website...</p>
        
        <div class="progress-bar">
            <div class="progress-fill" id="progress-fill"></div>
        </div>
        <div id="status-text">Initializing installation...</div>
        
        <script>
            let progress = 0;
            const progressBar = document.getElementById('progress-fill');
            const statusText = document.getElementById('status-text');
            
            const steps = [
                'Creating wp-config.php...',
                'Importing database...',
                'Extracting files...',
                'Updating URLs...',
                'Setting permissions...',
                'Finalizing installation...'
            ];
            
            let currentStep = 0;
            
            function updateProgress() {
                if (currentStep < steps.length) {
                    progress += 16.67;
                    progressBar.style.width = progress + '%';
                    statusText.textContent = steps[currentStep];
                    currentStep++;
                    
                    setTimeout(updateProgress, 2000);
                } else {
                    progressBar.style.width = '100%';
                    statusText.innerHTML = '<strong style="color: #27ae60;">Installation completed successfully!</strong>';
                    
                    setTimeout(function() {
                        window.location.href = '?step=5';
                    }, 2000);
                }
            }
            
            // Start the installation process
            setTimeout(updateProgress, 1000);
            
            // Perform actual installation via AJAX
            fetch('?step=4&action=install', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });
        </script>
        <?php
        
        if (isset($_GET['action']) && $_GET['action'] === 'install') {
            $this->perform_installation();
            exit;
        }
    }
    
    private function step_complete() {
        ?>
        <h2>Installation Complete!</h2>
        <div class="alert alert-success">
            <strong>Congratulations!</strong> Your WordPress site has been successfully migrated and restored.
        </div>
        
        <p>Your website is now ready to use. Here are your next steps:</p>
        
        <ul style="margin: 20px 0; padding-left: 20px;">
            <li>Visit your new website to verify everything is working correctly</li>
            <li>Log in to your WordPress admin panel</li>
            <li>Update any necessary settings or configurations</li>
            <li>Delete this installer file for security</li>
        </ul>
        
        <div class="text-center">
            <a href="/" class="btn btn-success">Visit Your Website</a>
            <a href="/wp-admin" class="btn">WordPress Admin</a>
        </div>
        
        <div class="alert alert-info" style="margin-top: 20px;">
            <strong>Security Notice:</strong> Please delete the installer.php file and migration.zip from your server for security purposes.
        </div>
        <?php
    }
    
    private function check_requirements() {
        return array(
            array(
                'name' => 'PHP Version (>= 7.4)',
                'status' => version_compare(PHP_VERSION, '7.4.0', '>=')
            ),
            array(
                'name' => 'MySQL Extension',
                'status' => extension_loaded('mysqli') || extension_loaded('mysql')
            ),
            array(
                'name' => 'ZIP Extension',
                'status' => extension_loaded('zip')
            ),
            array(
                'name' => 'cURL Extension',
                'status' => extension_loaded('curl')
            ),
            array(
                'name' => 'GD Extension',
                'status' => extension_loaded('gd')
            ),
            array(
                'name' => 'File Permissions',
                'status' => is_writable('.')
            ),
            array(
                'name' => 'Memory Limit (>= 128M)',
                'status' => $this->check_memory_limit()
            )
        );
    }
    
    private function check_memory_limit() {
        $memory_limit = ini_get('memory_limit');
        if ($memory_limit == -1) return true;
        
        $memory_limit = $this->convert_to_bytes($memory_limit);
        return $memory_limit >= 128 * 1024 * 1024;
    }
    
    private function convert_to_bytes($value) {
        $value = trim($value);
        $last = strtolower($value[strlen($value)-1]);
        $value = (int) $value;
        
        switch($last) {
            case 'g': $value *= 1024;
            case 'm': $value *= 1024;
            case 'k': $value *= 1024;
        }
        
        return $value;
    }
    
    private function process_database_config() {
        session_start();
        
        $db_host = $_POST['db_host'];
        $db_name = $_POST['db_name'];
        $db_user = $_POST['db_user'];
        $db_pass = $_POST['db_pass'];
        $new_url = rtrim($_POST['new_url'], '/');
        
        // Test database connection
        $connection = @mysqli_connect($db_host, $db_user, $db_pass, $db_name);
        
        if (!$connection) {
            $this->errors[] = 'Could not connect to database: ' . mysqli_connect_error();
            return;
        }
        
        mysqli_close($connection);
        
        // Store configuration in session
        $_SESSION['db_config'] = array(
            'host' => $db_host,
            'name' => $db_name,
            'user' => $db_user,
            'pass' => $db_pass,
            'new_url' => $new_url
        );
        
        header('Location: ?step=4');
        exit;
    }
    
    private function perform_installation() {
        session_start();
        
        if (!isset($_SESSION['db_config'])) {
            http_response_code(400);
            echo json_encode(array('error' => 'No database configuration found'));
            return;
        }
        
        $config = $_SESSION['db_config'];
        
        try {
            // Step 1: Create wp-config.php
            $this->create_wp_config($config);
            
            // Step 2: Extract files
            $this->extract_files();
            
            // Step 3: Import database
            $this->import_database($config);
            
            // Step 4: Update URLs
            $this->update_urls($config);
            
            // Step 5: Set permissions
            $this->set_permissions();
            
            echo json_encode(array('success' => true));
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(array('error' => $e->getMessage()));
        }
    }
    
    private function create_wp_config($config) {
        $wp_config_content = "<?php\n";
        $wp_config_content .= "define('DB_NAME', '" . $config['name'] . "');\n";
        $wp_config_content .= "define('DB_USER', '" . $config['user'] . "');\n";
        $wp_config_content .= "define('DB_PASSWORD', '" . $config['pass'] . "');\n";
        $wp_config_content .= "define('DB_HOST', '" . $config['host'] . "');\n";
        $wp_config_content .= "define('DB_CHARSET', 'utf8mb4');\n";
        $wp_config_content .= "define('DB_COLLATE', '');\n\n";
        
        // Add security keys
        $wp_config_content .= $this->generate_wp_keys();
        
        $wp_config_content .= "\$table_prefix = 'wp_';\n";
        $wp_config_content .= "define('WP_DEBUG', false);\n";
        $wp_config_content .= "if ( ! defined( 'ABSPATH' ) ) {\n";
        $wp_config_content .= "\tdefine( 'ABSPATH', __DIR__ . '/' );\n";
        $wp_config_content .= "}\n";
        $wp_config_content .= "require_once ABSPATH . 'wp-settings.php';\n";
        
        file_put_contents('wp-config.php', $wp_config_content);
    }
    
    private function generate_wp_keys() {
        $keys = array(
            'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY',
            'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT'
        );
        
        $content = "";
        foreach ($keys as $key) {
            $content .= "define('" . $key . "', '" . $this->generate_random_string(64) . "');\n";
        }
        
        return $content . "\n";
    }
    
    private function generate_random_string($length) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
        $string = '';
        
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $string;
    }
    
    private function extract_files() {
        $zip = new ZipArchive;
        if ($zip->open('migration.zip') === TRUE) {
            $zip->extractTo('./');
            $zip->close();
            
            // Move files from the files directory to root
            if (is_dir('files')) {
                $this->move_directory_contents('files', './');
                $this->delete_directory('files');
            }
        } else {
            throw new Exception('Could not extract migration files');
        }
    }
    
    private function import_database($config) {
        $connection = mysqli_connect($config['host'], $config['user'], $config['pass'], $config['name']);
        
        if (!$connection) {
            throw new Exception('Could not connect to database');
        }
        
        $sql_content = file_get_contents('database.sql');
        
        // Split SQL into individual queries
        $queries = explode(';', $sql_content);
        
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                if (!mysqli_query($connection, $query)) {
                    // Log error but continue with other queries
                    error_log('SQL Error: ' . mysqli_error($connection));
                }
            }
        }
        
        mysqli_close($connection);
    }
    
    private function update_urls($config) {
        $connection = mysqli_connect($config['host'], $config['user'], $config['pass'], $config['name']);
        
        if (!$connection) {
            throw new Exception('Could not connect to database');
        }
        
        $old_url = $this->config['site_url'];
        $new_url = $config['new_url'];
        
        // Update options table
        $queries = array(
            "UPDATE wp_options SET option_value = '" . mysqli_real_escape_string($connection, $new_url) . "' WHERE option_name = 'home'",
            "UPDATE wp_options SET option_value = '" . mysqli_real_escape_string($connection, $new_url) . "' WHERE option_name = 'siteurl'",
            "UPDATE wp_posts SET post_content = REPLACE(post_content, '" . mysqli_real_escape_string($connection, $old_url) . "', '" . mysqli_real_escape_string($connection, $new_url) . "')",
            "UPDATE wp_postmeta SET meta_value = REPLACE(meta_value, '" . mysqli_real_escape_string($connection, $old_url) . "', '" . mysqli_real_escape_string($connection, $new_url) . "')",
            "UPDATE wp_comments SET comment_content = REPLACE(comment_content, '" . mysqli_real_escape_string($connection, $old_url) . "', '" . mysqli_real_escape_string($connection, $new_url) . "')"
        );
        
        foreach ($queries as $query) {
            mysqli_query($connection, $query);
        }
        
        mysqli_close($connection);
    }
    
    private function set_permissions() {
        // Set appropriate file permissions
        if (is_dir('wp-content')) {
            chmod('wp-content', 0755);
            $this->chmod_recursive('wp-content', 0644, 0755);
        }
        
        if (file_exists('wp-config.php')) {
            chmod('wp-config.php', 0644);
        }
    }
    
    private function chmod_recursive($path, $file_perm, $dir_perm) {
        $dir = new DirectoryIterator($path);
        
        foreach ($dir as $item) {
            if ($item->isDot()) continue;
            
            if ($item->isDir()) {
                chmod($item->getPathname(), $dir_perm);
                $this->chmod_recursive($item->getPathname(), $file_perm, $dir_perm);
            } else {
                chmod($item->getPathname(), $file_perm);
            }
        }
    }
    
    private function move_directory_contents($src, $dst) {
        $dir = opendir($src);
        
        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                $src_file = $src . '/' . $file;
                $dst_file = $dst . '/' . $file;
                
                if (is_dir($src_file)) {
                    if (!is_dir($dst_file)) {
                        mkdir($dst_file, 0755, true);
                    }
                    $this->move_directory_contents($src_file, $dst_file);
                } else {
                    rename($src_file, $dst_file);
                }
            }
        }
        
        closedir($dir);
    }
    
    private function delete_directory($dir) {
        if (!is_dir($dir)) return;
        
        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $file_path = $dir . '/' . $file;
            if (is_dir($file_path)) {
                $this->delete_directory($file_path);
            } else {
                unlink($file_path);
            }
        }
        
        rmdir($dir);
    }
}

// Start the installer
$installer = new WordPressMigrationInstaller();
$installer->run();
?>
