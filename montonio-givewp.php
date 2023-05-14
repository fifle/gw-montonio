<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://donate.jewish.ee
 * @since             1.0.0
 * @package           montonio_Givewp
 *
 * @wordpress-plugin
 * Plugin Name:       Montonio for GiveWP
 * Plugin URI:
 * Description:       Adding Montonio gateway to GiveWP plugin for your crowdfundings. Montonio supports all Baltic and Finnish payment methods (banklink, pangalink). Just activate it and you can start accepting donations with Montonio.
 * Version:           1.0.0
 * Author:            Pavel Fleisher
 * Author URI:        http://fleisher.ee
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       montonio-givewp
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'montonio_GIVEWP_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-montonio-givewp-activator.php
 */
function activate_montonio_givewp() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-montonio-givewp-activator.php';
	montonio_Givewp_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-montonio-givewp-deactivator.php
 */
function deactivate_montonio_givewp() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-montonio-givewp-deactivator.php';
	montonio_Givewp_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_montonio_givewp' );
register_deactivation_hook( __FILE__, 'deactivate_montonio_givewp' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-montonio-givewp.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_montonio_givewp() {

	$plugin = new montonio_Givewp();
	$plugin->run();

}
run_montonio_givewp();
