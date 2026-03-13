<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://fleisher.ee
 * @since             1.0.0
 * @package           donationbox_Givewp
 *
 * @wordpress-plugin
 * Plugin Name:       Payment Gateway for Donationbox on GiveWP
 * Plugin URI:
 * Description:       Add-on for GiveWP Donation Plugin allows to accept payments via Donationbox in a lightbox iframe. Payments remain pending for manual admin review.
 * Version:           1.0.0
 * Author:            Pavel Fleisher
 * Author URI:        http://fleisher.ee
 * License:           GPL-3.0+
 * License URI:       https://spdx.org/licenses/GPL-3.0-or-later.html
 * Text Domain:       donationbox-givewp
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'DONATIONBOX_GIVEWP_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 */
function activate_donationbox_givewp() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-donationbox-givewp-activator.php';
	donationbox_Givewp_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_donationbox_givewp() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-donationbox-givewp-deactivator.php';
	donationbox_Givewp_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_donationbox_givewp' );
register_deactivation_hook( __FILE__, 'deactivate_donationbox_givewp' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-donationbox-givewp.php';

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
function run_donationbox_givewp() {

	$plugin = new donationbox_Givewp();
	$plugin->run();

}
run_donationbox_givewp();
