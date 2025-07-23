=== WP Site Migrator Pro ===
Contributors: yourcompany
Tags: migration, backup, clone, restore, transfer
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional WordPress site migration and cloning plugin. Complete website backup, migration, and restoration solution.

== Description ==

WP Site Migrator Pro is a comprehensive WordPress migration plugin that allows you to create complete backups of your website and restore them on any server with just a few clicks.

**Key Features:**

* **Complete Site Backup**: Backs up all files, database, media, themes, and plugins
* **One-Click Migration**: Simple installation process on the destination server
* **Professional Interface**: Clean, modern admin interface
* **Secure Backups**: Protected backup directory with .htaccess
* **Progress Tracking**: Real-time backup and restoration progress
* **URL Replacement**: Automatically updates all URLs in the database
* **Requirements Check**: Validates server requirements before installation
* **Backup Management**: View and manage previous backups

**How It Works:**

1. **Create Backup**: Generate a complete backup of your WordPress site
2. **Download Package**: Get a migration package containing all files and installer
3. **Upload to New Server**: Transfer the migration files via FTP
4. **Run Installer**: Execute the installer script to restore your site
5. **Complete**: Your site is fully migrated and ready to use

**Perfect For:**

* Moving sites between hosting providers
* Creating staging environments
* Site cloning and duplication
* Complete site backups
* Development to production migrations

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wp-site-migrator` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Tools->Site Migrator screen to create backups and manage migrations

== Frequently Asked Questions ==

= What files are included in the backup? =

The backup includes:
- Complete WordPress database
- All WordPress core files
- wp-content directory (themes, plugins, uploads)
- wp-config.php (regenerated during restoration)

= Can I migrate to a different domain? =

Yes, the plugin automatically handles URL replacement during the migration process.

= What are the server requirements? =

- PHP 7.4 or higher
- MySQL/MariaDB database
- ZIP extension
- Sufficient disk space for backup files
- Write permissions

= Is the backup process secure? =

Yes, backup files are stored in a protected directory with .htaccess restrictions, and old backups are automatically cleaned up.

== Screenshots ==

1. Main migration interface
2. Backup creation process
3. Installation wizard
4. Requirements check
5. Database configuration
6. Migration progress

== Changelog ==

= 1.0.0 =
* Initial release
* Complete site backup functionality
* Professional installation wizard
* URL replacement system
* Requirements validation
* Progress tracking
* Backup management

== Upgrade Notice ==

= 1.0.0 =
Initial release of WP Site Migrator Pro.
