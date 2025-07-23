<?php
/**
 * Plugin Name: WP Site Migrator Pro
 * Plugin URI: https://example.com/wp-site-migrator
 * Description: Professional WordPress site migration and cloning plugin. Complete website backup, migration, and restoration solution.
 * Version: 1.0.0
 * Author: Your Company
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-site-migrator
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WSM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WSM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WSM_VERSION', '1.0.0');
define('WSM_PLUGIN_FILE', __FILE__);

// Main plugin class
class WPSiteMigrator {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_wsm_start_backup', array($this, 'start_backup'));
        add_action('wp_ajax_wsm_download_backup', array($this, 'download_backup'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        load_plugin_textdomain('wp-site-migrator', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function activate() {
        // Create backup directory
        $upload_dir = wp_upload_dir();
        $backup_dir = $upload_dir['basedir'] . '/wp-site-migrator';
        if (!file_exists($backup_dir)) {
            wp_mkdir_p($backup_dir);
        }
        
        // Create .htaccess to protect backup directory
        $htaccess_content = "Order deny,allow\nDeny from all";
        file_put_contents($backup_dir . '/.htaccess', $htaccess_content);
        
        // Create index.php to prevent directory listing
        $index_content = "<?php\n// Silence is golden.";
        file_put_contents($backup_dir . '/index.php', $index_content);
    }
    
    public function deactivate() {
        // Clean up temporary files
        $this->cleanup_old_backups();
    }
    
    public function add_admin_menu() {
        add_management_page(
            __('WP Site Migrator', 'wp-site-migrator'),
            __('Site Migrator', 'wp-site-migrator'),
            'manage_options',
            'wp-site-migrator',
            array($this, 'admin_page')
        );
    }
    
    public function enqueue_scripts($hook) {
        if ($hook !== 'tools_page_wp-site-migrator') {
            return;
        }
        
        wp_enqueue_script('wsm-admin', WSM_PLUGIN_URL . 'assets/admin.js', array('jquery'), WSM_VERSION, true);
        wp_enqueue_style('wsm-admin', WSM_PLUGIN_URL . 'assets/admin.css', array(), WSM_VERSION);
        
        wp_localize_script('wsm-admin', 'wsm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wsm_nonce'),
            'strings' => array(
                'backup_started' => __('Backup process started...', 'wp-site-migrator'),
                'backup_complete' => __('Backup completed successfully!', 'wp-site-migrator'),
                'backup_error' => __('Backup failed. Please try again.', 'wp-site-migrator'),
            )
        ));
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('WP Site Migrator Pro', 'wp-site-migrator'); ?></h1>
            
            <div class="wsm-container">
                <div class="wsm-card">
                    <h2><?php _e('Create Complete Site Backup', 'wp-site-migrator'); ?></h2>
                    <p><?php _e('This will create a complete backup of your website including all files, database, and media.', 'wp-site-migrator'); ?></p>
                    
                    <div class="wsm-backup-options">
                        <label>
                            <input type="checkbox" id="include-uploads" checked>
                            <?php _e('Include Media Files (wp-content/uploads)', 'wp-site-migrator'); ?>
                        </label>
                        <label>
                            <input type="checkbox" id="include-themes" checked>
                            <?php _e('Include Themes', 'wp-site-migrator'); ?>
                        </label>
                        <label>
                            <input type="checkbox" id="include-plugins" checked>
                            <?php _e('Include Plugins', 'wp-site-migrator'); ?>
                        </label>
                        <label>
                            <input type="checkbox" id="include-database" checked>
                            <?php _e('Include Database', 'wp-site-migrator'); ?>
                        </label>
                    </div>
                    
                    <button id="start-backup" class="button button-primary button-large">
                        <?php _e('Start Complete Backup', 'wp-site-migrator'); ?>
                    </button>
                    
                    <div id="backup-progress" class="wsm-progress" style="display: none;">
                        <div class="wsm-progress-bar">
                            <div class="wsm-progress-fill"></div>
                        </div>
                        <div class="wsm-progress-text">Initializing...</div>
                    </div>
                </div>
                
                <div class="wsm-card">
                    <h2><?php _e('Previous Backups', 'wp-site-migrator'); ?></h2>
                    <div id="backup-list">
                        <?php $this->display_backup_list(); ?>
                    </div>
                </div>
                
                <div class="wsm-card">
                    <h2><?php _e('Migration Instructions', 'wp-site-migrator'); ?></h2>
                    <ol>
                        <li><?php _e('Create a complete backup using the button above', 'wp-site-migrator'); ?></li>
                        <li><?php _e('Download the generated migration package', 'wp-site-migrator'); ?></li>
                        <li><?php _e('Upload both files (migration.zip and installer.php) to your new server via FTP', 'wp-site-migrator'); ?></li>
                        <li><?php _e('Navigate to yournewtomain.com/installer.php in your browser', 'wp-site-migrator'); ?></li>
                        <li><?php _e('Follow the installation wizard to complete the migration', 'wp-site-migrator'); ?></li>
                    </ol>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function display_backup_list() {
        $upload_dir = wp_upload_dir();
        $backup_dir = $upload_dir['basedir'] . '/wp-site-migrator';
        
        if (!is_dir($backup_dir)) {
            echo '<p>' . __('No backups found.', 'wp-site-migrator') . '</p>';
            return;
        }
        
        $backups = glob($backup_dir . '/backup_*.zip');
        
        if (empty($backups)) {
            echo '<p>' . __('No backups found.', 'wp-site-migrator') . '</p>';
            return;
        }
        
        // Sort by modification time (newest first)
        usort($backups, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>' . __('Backup Date', 'wp-site-migrator') . '</th><th>' . __('Size', 'wp-site-migrator') . '</th><th>' . __('Actions', 'wp-site-migrator') . '</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($backups as $backup) {
            $filename = basename($backup);
            $date = date('Y-m-d H:i:s', filemtime($backup));
            $size = $this->format_bytes(filesize($backup));
            $download_url = wp_nonce_url(
                admin_url('admin-ajax.php?action=wsm_download_backup&file=' . urlencode($filename)),
                'wsm_download_nonce'
            );
            
            echo '<tr>';
            echo '<td>' . esc_html($date) . '</td>';
            echo '<td>' . esc_html($size) . '</td>';
            echo '<td><a href="' . esc_url($download_url) . '" class="button">' . __('Download', 'wp-site-migrator') . '</a></td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    public function start_backup() {
        check_ajax_referer('wsm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'wp-site-migrator'));
        }
        
        // Increase limits
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');
        
        $backup_id = 'backup_' . date('Y-m-d_H-i-s');
        $upload_dir = wp_upload_dir();
        $backup_dir = $upload_dir['basedir'] . '/wp-site-migrator/' . $backup_id;
        
        // Create backup directory
        wp_mkdir_p($backup_dir);
        
        try {
            // Step 1: Export database
            $this->export_database($backup_dir);
            
            // Step 2: Copy WordPress files
            $this->copy_wordpress_files($backup_dir);
            
            // Step 3: Create configuration file
            $this->create_config_file($backup_dir);
            
            // Step 4: Create installer
            $this->create_installer($backup_dir);
            
            // Step 5: Create final zip
            $zip_file = $this->create_final_zip($backup_dir, $backup_id);
            
            // Clean up temporary directory
            $this->delete_directory($backup_dir);
            
            wp_send_json_success(array(
                'message' => __('Backup completed successfully!', 'wp-site-migrator'),
                'download_url' => wp_nonce_url(
                    admin_url('admin-ajax.php?action=wsm_download_backup&file=' . urlencode(basename($zip_file))),
                    'wsm_download_nonce'
                )
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(__('Backup failed: ', 'wp-site-migrator') . $e->getMessage());
        }
    }
    
    private function export_database($backup_dir) {
        global $wpdb;
        
        $sql_file = $backup_dir . '/database.sql';
        $handle = fopen($sql_file, 'w');
        
        if (!$handle) {
            throw new Exception(__('Could not create database export file', 'wp-site-migrator'));
        }
        
        // Write SQL header
        fwrite($handle, "-- WordPress Database Export\n");
        fwrite($handle, "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n");
        fwrite($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
        fwrite($handle, "SET time_zone = \"+00:00\";\n\n");
        
        // Get all tables
        $tables = $wpdb->get_results("SHOW TABLES", ARRAY_N);
        
        foreach ($tables as $table) {
            $table_name = $table[0];
            
            // Get table structure
            $create_table = $wpdb->get_row("SHOW CREATE TABLE `$table_name`", ARRAY_N);
            fwrite($handle, "\n-- Table structure for table `$table_name`\n");
            fwrite($handle, "DROP TABLE IF EXISTS `$table_name`;\n");
            fwrite($handle, $create_table[1] . ";\n\n");
            
            // Get table data in chunks to handle large tables
            $offset = 0;
            $chunk_size = 1000;
            
            do {
                $rows = $wpdb->get_results("SELECT * FROM `$table_name` LIMIT $chunk_size OFFSET $offset", ARRAY_A);
                
                if (!empty($rows)) {
                    if ($offset === 0) {
                        fwrite($handle, "-- Dumping data for table `$table_name`\n");
                    }
                    
                    foreach ($rows as $row) {
                        $values = array();
                        foreach ($row as $value) {
                            if ($value === null) {
                                $values[] = 'NULL';
                            } else {
                                $values[] = "'" . $wpdb->_real_escape($value) . "'";
                            }
                        }
                        fwrite($handle, "INSERT INTO `$table_name` VALUES (" . implode(', ', $values) . ");\n");
                    }
                }
                
                $offset += $chunk_size;
            } while (count($rows) === $chunk_size);
            
            fwrite($handle, "\n");
        }
        
        fclose($handle);
    }
    
    private function copy_wordpress_files($backup_dir) {
        $wp_root = ABSPATH;
        $files_dir = $backup_dir . '/files';
        wp_mkdir_p($files_dir);
        
        // Exclude directories
        $exclude = array(
            $backup_dir,
            $wp_root . 'wp-content/cache',
            $wp_root . 'wp-content/uploads/wp-site-migrator',
            $wp_root . 'wp-content/backup',
            $wp_root . 'wp-content/backups'
        );
        
        // Copy WordPress core files and wp-content
        $this->copy_directory($wp_root, $files_dir, $exclude);
    }
    
    private function copy_directory($src, $dst, $exclude = array()) {
        if (!is_dir($src)) {
            return;
        }
        
        $dir = opendir($src);
        if (!$dir) {
            return;
        }
        
        wp_mkdir_p($dst);
        
        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                $src_file = rtrim($src, '/') . '/' . $file;
                $dst_file = rtrim($dst, '/') . '/' . $file;
                
                // Check if file/directory should be excluded
                $excluded = false;
                foreach ($exclude as $exclude_path) {
                    if (strpos($src_file, rtrim($exclude_path, '/')) === 0) {
                        $excluded = true;
                        break;
                    }
                }
                
                if ($excluded) {
                    continue;
                }
                
                if (is_dir($src_file)) {
                    $this->copy_directory($src_file, $dst_file, $exclude);
                } else {
                    @copy($src_file, $dst_file);
                }
            }
        }
        closedir($dir);
    }
    
    private function create_config_file($backup_dir) {
        $config = array(
            'site_url' => get_site_url(),
            'home_url' => get_home_url(),
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'mysql_version' => $this->get_mysql_version(),
            'backup_date' => date('Y-m-d H:i:s'),
            'backup_version' => WSM_VERSION,
            'table_prefix' => $GLOBALS['wpdb']->prefix
        );
        
        file_put_contents($backup_dir . '/config.json', json_encode($config, JSON_PRETTY_PRINT));
    }
    
    private function get_mysql_version() {
        global $wpdb;
        return $wpdb->get_var("SELECT VERSION()");
    }
    
    private function create_installer($backup_dir) {
        $installer_content = $this->get_installer_template();
        file_put_contents($backup_dir . '/installer.php', $installer_content);
    }
    
    private function get_installer_template() {
        ob_start();
        include WSM_PLUGIN_PATH . 'templates/installer.php';
        return ob_get_clean();
    }
    
    private function create_final_zip($backup_dir, $backup_id) {
        $upload_dir = wp_upload_dir();
        $zip_file = $upload_dir['basedir'] . '/wp-site-migrator/' . $backup_id . '.zip';
        
        if (!class_exists('ZipArchive')) {
            throw new Exception(__('ZipArchive class not found. Please install PHP ZIP extension.', 'wp-site-migrator'));
        }
        
        $zip = new ZipArchive();
        if ($zip->open($zip_file, ZipArchive::CREATE) !== TRUE) {
            throw new Exception(__('Could not create zip file', 'wp-site-migrator'));
        }
        
        $this->add_directory_to_zip($zip, $backup_dir, '');
        $zip->close();
        
        return $zip_file;
    }
    
    private function add_directory_to_zip($zip, $dir, $base_path) {
        $files = scandir($dir);
        
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $file_path = $dir . '/' . $file;
                $zip_path = $base_path . $file;
                
                if (is_dir($file_path)) {
                    $zip->addEmptyDir($zip_path);
                    $this->add_directory_to_zip($zip, $file_path, $zip_path . '/');
                } else {
                    $zip->addFile($file_path, $zip_path);
                }
            }
        }
    }
    
    public function download_backup() {
        check_admin_referer('wsm_download_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'wp-site-migrator'));
        }
        
        $filename = sanitize_file_name($_GET['file']);
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/wp-site-migrator/' . $filename;
        
        if (!file_exists($file_path)) {
            wp_die(__('File not found', 'wp-site-migrator'));
        }
        
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        readfile($file_path);
        exit;
    }
    
    private function delete_directory($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $file_path = $dir . '/' . $file;
            if (is_dir($file_path)) {
                $this->delete_directory($file_path);
            } else {
                @unlink($file_path);
            }
        }
        
        @rmdir($dir);
    }
    
    private function cleanup_old_backups() {
        $upload_dir = wp_upload_dir();
        $backup_dir = $upload_dir['basedir'] . '/wp-site-migrator';
        
        if (!is_dir($backup_dir)) {
            return;
        }
        
        $backups = glob($backup_dir . '/backup_*.zip');
        
        // Keep only the 5 most recent backups
        if (count($backups) > 5) {
            usort($backups, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            $old_backups = array_slice($backups, 0, -5);
            foreach ($old_backups as $backup) {
                @unlink($backup);
            }
        }
    }
    
    private function format_bytes($size, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
}

// Initialize the plugin
function wp_site_migrator_init() {
    return WPSiteMigrator::get_instance();
}

// Start the plugin
wp_site_migrator_init();
?>
