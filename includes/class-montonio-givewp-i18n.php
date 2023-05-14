<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       http://donate.jewish.ee
 * @since      1.0.0
 *
 * @package    montonio_Givewp
 * @subpackage montonio_Givewp/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    montonio_Givewp
 * @subpackage montonio_Givewp/includes
 * @author     Pavel <pavel@fleisher.ee>
 */
class montonio_Givewp_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'montonio-givewp',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
