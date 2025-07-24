<?php
/**
 * Plugin Name: WP Site Migrator Pro
 * Plugin URI: https://yourcompany.com/wp-site-migrator
 * Description: Professional WordPress site migration and cloning plugin. Complete website backup, migration, and restoration solution.
 * Version: 1.0.0
 * Author: Your Company
 * Author URI: https://yourcompany.com
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
define('WSM_VERSION', '1.0.0');
define('WSM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WSM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WSM_BACKUP_DIR', WP_CONTENT_DIR . '/wsm-backups/');
define('WSM_BACKUP_URL', WP_CONTENT_URL . '/wsm-backups/');

class WP_Site_Migrator {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_wsm_start_backup', array($this, 'ajax_start_backup'));
        add_action('wp_ajax_wsm_delete_backup', array($this, 'ajax_delete_backup'));
        add_action('wp_ajax_wsm_download_installer', array($this, 'ajax_download_installer'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        load_plugin_textdomain('wp-site-migrator', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function activate() {
        // Create backup directory
        if (!file_exists(WSM_BACKUP_DIR)) {
            wp_mkdir_p(WSM_BACKUP_DIR);
        }
        
        // Create .htaccess to protect backup directory
        $htaccess_content = "Order deny,allow\nDeny from all\n<Files ~ \"\\.(zip|php)$\">\nAllow from all\n</Files>";
        file_put_contents(WSM_BACKUP_DIR . '.htaccess', $htaccess_content);
        
        // Create index.php for security
        file_put_contents(WSM_BACKUP_DIR . 'index.php', '<?php // Silence is golden');
    }
    
    public function deactivate() {
        // Clean up old backups on deactivation
        $this->cleanup_old_backups();
    }
    
    public function add_admin_menu() {
        add_management_page(
            __('Site Migrator', 'wp-site-migrator'),
            __('Site Migrator', 'wp-site-migrator'),
            'manage_options',
            'wp-site-migrator',
            array($this, 'admin_page')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'tools_page_wp-site-migrator') {
            return;
        }
        
        wp_enqueue_script('wsm-admin', WSM_PLUGIN_URL . 'assets/admin.js', array('jquery'), WSM_VERSION, true);
        wp_enqueue_style('wsm-admin', WSM_PLUGIN_URL . 'assets/admin.css', array(), WSM_VERSION);
        
        wp_localize_script('wsm-admin', 'wsm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wsm_nonce')
        ));
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('WP Site Migrator Pro', 'wp-site-migrator'); ?></h1>
            
            <div class="wsm-container">
                <!-- Backup Creation Card -->
                <div class="wsm-card">
                    <h2><?php _e('Create New Backup', 'wp-site-migrator'); ?></h2>
                    <p><?php _e('Create a complete backup of your WordPress site including database, files, and media.', 'wp-site-migrator'); ?></p>
                    
                    <div class="wsm-backup-options">
                        <label>
                            <input type="checkbox" id="include-database" checked>
                            <?php _e('Include Database', 'wp-site-migrator'); ?>
                        </label>
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
                    </div>
                    
                    <button id="start-backup" class="button button-primary button-large">
                        <?php _e('Start Complete Backup', 'wp-site-migrator'); ?>
                    </button>
                    
                    <div id="backup-progress" class="wsm-progress" style="display: none;">
                        <div class="wsm-progress-bar">
                            <div class="wsm-progress-fill"></div>
                        </div>
                        <div class="wsm-progress-text"><?php _e('Initializing backup...', 'wp-site-migrator'); ?></div>
                    </div>
                </div>
                
                <!-- Existing Backups Card -->
                <div class="wsm-card">
                    <h2><?php _e('Existing Backups', 'wp-site-migrator'); ?></h2>
                    <?php $this->display_backup_list(); ?>
                </div>
                
                <!-- Instructions Card -->
                <div class="wsm-card">
                    <h2><?php _e('Migration Instructions', 'wp-site-migrator'); ?></h2>
                    <ol>
                        <li><?php _e('Create a backup using the form above', 'wp-site-migrator'); ?></li>
                        <li><?php _e('Download both the backup file and installer file', 'wp-site-migrator'); ?></li>
                        <li><?php _e('Upload both files to your new server via FTP', 'wp-site-migrator'); ?></li>
                        <li><?php _e('Run the installer.php file in your browser', 'wp-site-migrator'); ?></li>
                        <li><?php _e('Follow the installation wizard to complete the migration', 'wp-site-migrator'); ?></li>
                    </ol>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function display_backup_list() {
        $backups = $this->get_backup_list();
        
        if (empty($backups)) {
            echo '<p>' . __('No backups found. Create your first backup above.', 'wp-site-migrator') . '</p>';
            return;
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . __('Backup Name', 'wp-site-migrator') . '</th>';
        echo '<th>' . __('Date Created', 'wp-site-migrator') . '</th>';
        echo '<th>' . __('Size', 'wp-site-migrator') . '</th>';
        echo '<th>' . __('Actions', 'wp-site-migrator') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($backups as $backup) {
            echo '<tr>';
            echo '<td>' . esc_html($backup['name']) . '</td>';
            echo '<td>' . esc_html($backup['date']) . '</td>';
            echo '<td>' . esc_html($backup['size']) . '</td>';
            echo '<td class="wsm-actions-cell">';
            
            // Download Backup button
            if (file_exists($backup['backup_path'])) {
                echo '<a href="' . esc_url($backup['backup_url']) . '" class="wsm-btn wsm-btn-primary wsm-btn-download">';
                echo '<span class="dashicons dashicons-download"></span> ' . __('Download Backup', 'wp-site-migrator');
                echo '</a>';
            }
            
            // Download Installer button
            if (file_exists($backup['installer_path'])) {
                echo '<button class="wsm-btn wsm-btn-secondary wsm-btn-installer" data-backup="' . esc_attr($backup['basename']) . '">';
                echo '<span class="dashicons dashicons-admin-tools"></span> ' . __('Download Installer', 'wp-site-migrator');
                echo '</button>';
            }
            
            // Delete button
            echo '<button class="wsm-btn wsm-btn-danger wsm-delete-backup" data-backup="' . esc_attr($backup['basename']) . '">';
            echo '<span class="dashicons dashicons-trash"></span> ' . __('Delete', 'wp-site-migrator');
            echo '</button>';
            
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
    }
    
    private function get_backup_list() {
        $backups = array();
        
        if (!is_dir(WSM_BACKUP_DIR)) {
            return $backups;
        }
        
        $files = scandir(WSM_BACKUP_DIR);
        
        foreach ($files as $file) {
            if (substr($file, -4) === '.zip' && strpos($file, '_backup_') !== false) {
                $backup_path = WSM_BACKUP_DIR . $file;
                $installer_file = str_replace('_backup_', '_installer_', str_replace('.zip', '.php', $file));
                $installer_path = WSM_BACKUP_DIR . $installer_file;
                
                $backups[] = array(
                    'name' => $file,
                    'basename' => pathinfo($file, PATHINFO_FILENAME),
                    'date' => date('Y-m-d H:i:s', filemtime($backup_path)),
                    'size' => $this->format_bytes(filesize($backup_path)),
                    'backup_path' => $backup_path,
                    'backup_url' => WSM_BACKUP_URL . $file,
                    'installer_path' => $installer_path,
                    'installer_url' => WSM_BACKUP_URL . $installer_file
                );
            }
        }
        
        // Sort by date (newest first)
        usort($backups, function($a, $b) {
            return filemtime($b['backup_path']) - filemtime($a['backup_path']);
        });
        
        return $backups;
    }
    
    private function format_bytes($size, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
    
    public function ajax_start_backup() {
        check_ajax_referer('wsm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'wp-site-migrator'));
        }
        
        $include_uploads = isset($_POST['include_uploads']) && $_POST['include_uploads'] === 'true';
        $include_themes = isset($_POST['include_themes']) && $_POST['include_themes'] === 'true';
        $include_plugins = isset($_POST['include_plugins']) && $_POST['include_plugins'] === 'true';
        $include_database = isset($_POST['include_database']) && $_POST['include_database'] === 'true';
        
        $result = $this->create_backup($include_uploads, $include_themes, $include_plugins, $include_database);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    public function ajax_delete_backup() {
        check_ajax_referer('wsm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'wp-site-migrator'));
        }
        
        $backup_name = sanitize_text_field($_POST['backup_name']);
        
        // Delete backup file
        $backup_file = WSM_BACKUP_DIR . $backup_name . '.zip';
        if (file_exists($backup_file)) {
            unlink($backup_file);
        }
        
        // Delete installer file
        $installer_file = WSM_BACKUP_DIR . str_replace('_backup_', '_installer_', $backup_name) . '.php';
        if (file_exists($installer_file)) {
            unlink($installer_file);
        }
        
        wp_send_json_success(__('Backup deleted successfully', 'wp-site-migrator'));
    }
    
    public function ajax_download_installer() {
        check_ajax_referer('wsm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'wp-site-migrator'));
        }
        
        $backup_name = sanitize_text_field($_POST['backup_name']);
        $installer_filename = str_replace('_backup_', '_installer_', $backup_name) . '.php';
        $installer_path = WSM_BACKUP_DIR . $installer_filename;
        
        if (file_exists($installer_path)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $installer_filename . '"');
            header('Content-Length: ' . filesize($installer_path));
            readfile($installer_path);
            exit;
        }
        
        wp_die(__('Installer file not found', 'wp-site-migrator'));
    }
    
    private function create_backup($include_uploads, $include_themes, $include_plugins, $include_database) {
        try {
            // Get site name for filename
            $site_url = get_site_url();
            $site_name = parse_url($site_url, PHP_URL_HOST);
            $site_name = str_replace(array('.', '-'), '_', $site_name);
            
            $timestamp = date('YmdHis'); // Removed hyphens
            $backup_filename = $site_name . '_backup_' . $timestamp . '.zip';
            $installer_filename = $site_name . '_installer_' . $timestamp . '.php';
            
            $backup_path = WSM_BACKUP_DIR . $backup_filename;
            $installer_path = WSM_BACKUP_DIR . $installer_filename;
            
            // Create backup ZIP
            $backup_zip = new ZipArchive();
            if ($backup_zip->open($backup_path, ZipArchive::CREATE) !== TRUE) {
                throw new Exception(__('Cannot create backup file', 'wp-site-migrator'));
            }
            
            // Add database export
            if ($include_database) {
                $db_export = $this->export_database();
                $backup_zip->addFromString('database.sql', $db_export);
            }
            
            // Add WordPress files
            $this->add_wordpress_files_to_zip($backup_zip, $include_uploads, $include_themes, $include_plugins);
            
            $backup_zip->close();
            
            // Create installer PHP file
            $installer_content = $this->generate_installer_script($backup_filename, $site_url);
            file_put_contents($installer_path, $installer_content);
            
            // Clean up old backups
            $this->cleanup_old_backups();
            
            return array(
                'success' => true,
                'backup_url' => WSM_BACKUP_URL . $backup_filename,
                'installer_url' => WSM_BACKUP_URL . $installer_filename,
                'backup_filename' => $backup_filename,
                'installer_filename' => $installer_filename
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    private function export_database() {
        global $wpdb;
        
        $tables = $wpdb->get_results('SHOW TABLES', ARRAY_N);
        $sql_dump = '';
        
        $sql_dump .= "-- WordPress Database Export\n";
        $sql_dump .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
        $sql_dump .= "-- Original URL: " . get_site_url() . "\n\n";
        $sql_dump .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $sql_dump .= "SET time_zone = \"+00:00\";\n\n";
        
        foreach ($tables as $table) {
            $table_name = $table[0];
            
            // Get table structure
            $create_table = $wpdb->get_row("SHOW CREATE TABLE `$table_name`", ARRAY_N);
            $sql_dump .= "\n-- Table structure for table `$table_name`\n";
            $sql_dump .= "DROP TABLE IF EXISTS `$table_name`;\n";
            $sql_dump .= $create_table[1] . ";\n\n";
            
            // Get table data
            $rows = $wpdb->get_results("SELECT * FROM `$table_name`", ARRAY_A);
            
            if (!empty($rows)) {
                $sql_dump .= "-- Dumping data for table `$table_name`\n";
                
                foreach ($rows as $row) {
                    $values = array();
                    foreach ($row as $value) {
                        if ($value === null) {
                            $values[] = 'NULL';
                        } else {
                            $values[] = "'" . $wpdb->_real_escape($value) . "'";
                        }
                    }
                    $sql_dump .= "INSERT INTO `$table_name` VALUES (" . implode(', ', $values) . ");\n";
                }
                $sql_dump .= "\n";
            }
        }
        
        return $sql_dump;
    }
    
    private function add_wordpress_files_to_zip($zip, $include_uploads, $include_themes, $include_plugins) {
        $root_path = ABSPATH;
        
        // Add WordPress core files
        $this->add_directory_to_zip($zip, $root_path, '', array(
            'wp-content/uploads' => !$include_uploads,
            'wp-content/themes' => !$include_themes,
            'wp-content/plugins' => !$include_plugins,
            'wp-content/wsm-backups' => true,
            'wp-config.php' => true // We'll generate a new one
        ));
        
        // Add wp-content selectively
        if ($include_uploads) {
            $uploads_dir = WP_CONTENT_DIR . '/uploads/';
            if (is_dir($uploads_dir)) {
                $this->add_directory_to_zip($zip, $uploads_dir, 'wp-content/uploads/');
            }
        }
        
        if ($include_themes) {
            $themes_dir = WP_CONTENT_DIR . '/themes/';
            if (is_dir($themes_dir)) {
                $this->add_directory_to_zip($zip, $themes_dir, 'wp-content/themes/');
            }
        }
        
        if ($include_plugins) {
            $plugins_dir = WP_CONTENT_DIR . '/plugins/';
            if (is_dir($plugins_dir)) {
                $this->add_directory_to_zip($zip, $plugins_dir, 'wp-content/plugins/', array(
                    'wp-site-migrator' => true // Exclude this plugin
                ));
            }
        }
    }
    
    private function add_directory_to_zip($zip, $source_path, $zip_path = '', $exclude = array()) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source_path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            $file_path = $file->getRealPath();
            $relative_path = substr($file_path, strlen($source_path));
            $zip_file_path = $zip_path . $relative_path;
            
            // Check exclusions
            $should_exclude = false;
            foreach ($exclude as $exclude_path => $exclude_flag) {
                if ($exclude_flag && strpos($relative_path, $exclude_path) === 0) {
                    $should_exclude = true;
                    break;
                }
            }
            
            if ($should_exclude) {
                continue;
            }
            
            if ($file->isDir()) {
                $zip->addEmptyDir($zip_file_path);
            } else {
                $zip->addFile($file_path, $zip_file_path);
            }
        }
    }
    
    private function generate_installer_script($backup_filename, $original_url) {
        $installer_template = file_get_contents(WSM_PLUGIN_DIR . 'templates/installer.php');
        
        // Replace placeholders
        $installer_template = str_replace('{{BACKUP_FILENAME}}', $backup_filename, $installer_template);
        $installer_template = str_replace('{{ORIGINAL_URL}}', $original_url, $installer_template);
        
        return $installer_template;
    }
    
    private function cleanup_old_backups() {
        if (!is_dir(WSM_BACKUP_DIR)) {
            return;
        }
        
        $files = array_merge(
            glob(WSM_BACKUP_DIR . '*.zip'),
            glob(WSM_BACKUP_DIR . '*.php')
        );
        
        // Keep only the 5 most recent backups (10 files total - 5 zips + 5 installers)
        if (count($files) > 10) {
            usort($files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            
            $files_to_delete = array_slice($files, 10);
            foreach ($files_to_delete as $file) {
                if (basename($file) !== 'index.php' && basename($file) !== '.htaccess') {
                    unlink($file);
                }
            }
        }
    }
}

// Initialize the plugin
new WP_Site_Migrator();
