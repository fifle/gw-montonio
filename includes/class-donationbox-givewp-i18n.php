<?php

/**
 * Define the internationalization functionality
 *
 * @link       http://fleisher.ee
 * @since      1.0.0
 *
 * @package    donationbox_Givewp
 * @subpackage donationbox_Givewp/includes
 */

/**
 * Define the internationalization functionality.
 *
 * @since      1.0.0
 * @package    donationbox_Givewp
 * @subpackage donationbox_Givewp/includes
 * @author     Pavel <pavel@fleisher.ee>
 */
class donationbox_Givewp_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'donationbox-givewp',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}

}
