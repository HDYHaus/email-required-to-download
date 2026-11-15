<?php
/**
 * Plugin Name: Email Required to Download
 * Plugin URI: https://hdyhaus.com/wp-plugins/email-required-to-download
 * Description: Require an email address before visitors can download a file.
 * Version: 1.2.1
 * Author: HDY Haus
 * Author URI: https://hdyhaus.com/wp-plugins/email-required-to-download
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: email-required-to-download
 * Requires at least: 6.3
 * Requires PHP: 7.4
 *
 * @package EmailRequiredDownload
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ERD_VERSION', '1.2.1' );
define( 'ERD_DB_VERSION', '1.2.0' );
define( 'ERD_PLUGIN_FILE', __FILE__ );
define( 'ERD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ERD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once ERD_PLUGIN_DIR . 'includes/class-erd-activator.php';
require_once ERD_PLUGIN_DIR . 'includes/class-erd-repository.php';
require_once ERD_PLUGIN_DIR . 'includes/class-erd-tokens.php';
require_once ERD_PLUGIN_DIR . 'includes/class-erd-admin.php';
require_once ERD_PLUGIN_DIR . 'includes/class-erd-frontend.php';
require_once ERD_PLUGIN_DIR . 'includes/class-erd-block.php';
require_once ERD_PLUGIN_DIR . 'includes/class-erd-plugin.php';

register_activation_hook( __FILE__, array( 'ERD_Activator', 'activate' ) );

add_action(
	'plugins_loaded',
	static function () {
		ERD_Plugin::instance()->init();
	}
);
