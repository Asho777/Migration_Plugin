<?php
/**
 * Plugin Name: WP Site Migrator Pro
 * Plugin URI: https://example.com/wp-site-migrator
 * Description: Professional WordPress site migration and cloning plugin. Complete website backup, migration, and restoration solution.
 * Version: 1.0.0
 * Author: Your Company
 * License: GPL v2 or later
 * Text Domain: wp-site-migrator
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WSM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WSM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WSM_VERSION', '1.0.0');

// Main plugin class
class WPSiteMigrator {
    
    public function __construct() {
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
    }
    
    public function deactivate() {
        // Clean up temporary files
        $this->cleanup_old_backups();
    }
    
    public function add_admin_menu() {
        add_management_page(
            'WP Site Migrator',
            'Site Migrator',
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
        echo '<thead><tr><th>Backup Date</th><th>Size</th><th>Actions</th></tr></thead>';
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
            echo '<td><a href="' . esc_url($download_url) . '" class="button">Download</a></td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    public function start_backup() {
        check_ajax_referer('wsm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        // Set longer execution time
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        
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
                'message' => 'Backup completed successfully!',
                'download_url' => wp_nonce_url(
                    admin_url('admin-ajax.php?action=wsm_download_backup&file=' . urlencode(basename($zip_file))),
                    'wsm_download_nonce'
                )
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Backup failed: ' . $e->getMessage());
        }
    }
    
    private function export_database($backup_dir) {
        global $wpdb;
        
        $sql_file = $backup_dir . '/database.sql';
        $handle = fopen($sql_file, 'w');
        
        if (!$handle) {
            throw new Exception('Could not create database export file');
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
            
            // Get table data
            $rows = $wpdb->get_results("SELECT * FROM `$table_name`", ARRAY_A);
            
            if (!empty($rows)) {
                fwrite($handle, "-- Dumping data for table `$table_name`\n");
                
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
                fwrite($handle, "\n");
            }
        }
        
        fclose($handle);
    }
    
    private function copy_wordpress_files($backup_dir) {
        $wp_root = ABSPATH;
        $files_dir = $backup_dir . '/files';
        wp_mkdir_p($files_dir);
        
        // Copy WordPress core files and wp-content
        $this->copy_directory($wp_root, $files_dir, array(
            $backup_dir,
            $wp_root . 'wp-content/cache',
            $wp_root . 'wp-content/uploads/wp-site-migrator'
        ));
    }
    
    private function copy_directory($src, $dst, $exclude = array()) {
        $dir = opendir($src);
        wp_mkdir_p($dst);
        
        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                $src_file = $src . '/' . $file;
                $dst_file = $dst . '/' . $file;
                
                // Check if file/directory should be excluded
                $excluded = false;
                foreach ($exclude as $exclude_path) {
                    if (strpos($src_file, $exclude_path) === 0) {
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
                    copy($src_file, $dst_file);
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
            'backup_version' => WSM_VERSION
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
        return file_get_contents(WSM_PLUGIN_PATH . 'templates/installer.php');
    }
    
    private function create_final_zip($backup_dir, $backup_id) {
        $upload_dir = wp_upload_dir();
        $zip_file = $upload_dir['basedir'] . '/wp-site-migrator/' . $backup_id . '.zip';
        
        $zip = new ZipArchive();
        if ($zip->open($zip_file, ZipArchive::CREATE) !== TRUE) {
            throw new Exception('Could not create zip file');
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
            wp_die('Unauthorized');
        }
        
        $filename = sanitize_file_name($_GET['file']);
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/wp-site-migrator/' . $filename;
        
        if (!file_exists($file_path)) {
            wp_die('File not found');
        }
        
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file_path));
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
                unlink($file_path);
            }
        }
        
        rmdir($dir);
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
                unlink($backup);
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
new WPSiteMigrator();
?>
