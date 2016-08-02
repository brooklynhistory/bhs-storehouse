<?php
/**
 * Plugin Name: BHS Storehouse
 * Version: 0.1-alpha
 * Description: Manage and serve assets exported from PastPerfect.
 * Author: Boone Gorges
 * Author URI: https://boone.gorg.es
 * Plugin URI: https://brooklynhistory.org
 * Text Domain: bhs-storehouse
 * Domain Path: /languages
 * @package bhs-storehouse
 */

define( 'BHSSH_VERSION', '0.1-alpha' );
define( 'BHSSH_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Bootstraps the plugin.
 *
 * Performs a PHP version check, and then registers the autoloader and loads the application.
 *
 * @since 1.0.0
 */
function bhssh_bootstrap() {
	if ( version_compare( PHP_VERSION, '5.3', '<' ) && current_user_can( 'install_plugins' ) ) {
		add_action( 'admin_notices', 'bhssh_php_admin_notice' );
		return;
	}

	require dirname( __FILE__ ) . '/autoload.php';
	require dirname( __FILE__ ) . '/load.php';
}
add_action( 'plugins_loaded', 'bhssh_bootstrap' );

/**
 * Render a PHP compatibility notice.
 *
 * Meant to fire at 'admin_notices'.
 *
 * PHP 5.2 compatible.
 *
 * @since 1.0.0
 */
function bhssh_php_admin_notice() {
	?>
	<div class="notice notice-error is-dismissable">
		<p><?php esc_html_e( 'BHS Storehouse requires PHP 5.3 or higher. Please contact your webhost.', 'bhs-storehouse' ); ?></p>
	</div>
	<?php
}
