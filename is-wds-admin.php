<?php
/**
 * Plugin Name: is_wds_admin
 * Description: Adds a custom capability and some helper functions to determine if the current user is a privileged WDS user. NOT TO BE INSTALLED AS A NORMAL PLUGIN.
 * Version:     1.1
 * Author:      WebDevStudios
 * Author URI:  http://webdevstudios.com
 * License:     GPLv2
 */

/**
 * Copyright (c) 2016 WebDevStudios (email : contact@webdevstudios.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * Main initiation class.
 *
 * @since 1.0.0
 *
 * @var   string $version  Plugin version.
 * @var   string $basename Plugin basename.
 * @var   string $url      Plugin URL.
 * @var   string $path     Plugin Path.
 */
class Is_WDS_Admin {

	/**
	 * Current version.
	 *
	 * Always is set to what is in the plugin header.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	private $version = '';

	/**
	 * Plugin basename.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $basename = '';

	/**
	 * Privileged username.
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	public $privileged_users = array();

	/**
	 * Singleton instance of plugin.
	 *
	 * @var    Is_WDS_Admin
	 * @since  1.0.0
	 */
	protected static $single_instance = null;

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @since  1.0.0
	 * @return Is_WDS_Admin A single instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$single_instance ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	/**
	 * Sets up our plugin.
	 *
	 * @since  1.0.0
	 */
	protected function __construct() {
		$this->basename        = plugin_basename( __FILE__ );
		$this->privileged_user = 'wds_admin'; // This can be whatever user is the privileged user.
		$this->version         = $this->get_version();

		// Add capability if it does not exist for the given user.
		add_action( 'init', array( $this, 'add_cap_if_not_exists' ) );
	}

	/**
	 * Get the version of this plugin.
	 *
	 * @author Aubrey Portwood
	 * @since 1.0.0
	 *
	 * @return string The Version value in the header.
	 */
	private function get_version() {
		$headers = (object) get_file_data( __FILE__, array(
			'version' => 'Version',
		) );

		return $headers->version;
	}

	/**
	 * Add hooks and filters.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function get_cap_name() {
		return apply_filters( 'is_wds_admin_cap_name', 'is_wds_admin' );
	}

	/**
	 * Check if the current user is the privileged user.
	 *
	 * @since  1.0.0
	 * @return bool True/false depending if the current user is the one defined in the __construct as the privileged user.
	 */
	private function is_privileged_user() {
		if ( $this->is_wds_sso_user() ) {

			// This is a WDS SSO user, so they also are privileged.
			return true;
		}

		$current_user = wp_get_current_user();

		// Check if the current user is 'wds_admin'.
		if ( ! is_wp_error( $current_user ) ) {
			return $this->privileged_user === $current_user->user_login;
		}

		return false;
	}

	/**
	 * Are we working with a WDS SSO User?
	 *
	 * @author Aubrey Portwood
	 * @since  1.1.0
	 *
	 * @link https://github.com/WebDevStudios/wds-sso The WDS SSO Project.
	 *
	 * @return boolean True if we are, false if not.
	 */
	private function is_wds_sso_user() {
		if ( ! class_exists( '\WDS\SSO\User' ) ) {

			// The user class doesn't even exist.
			return false;
		}

		if ( ! property_exists( \WDS\SSO\app(), 'user' ) || ! method_exists( \WDS\SSO\app()->user, 'is_sso_user' ) ) {

			// The class isn't configured like we thought.
			return false;
		}

		return \WDS\SSO\app()->user->is_sso_user();
	}

	/**
	 * Add the 'is_wds_admin' capability if it doesn't exist.
	 *
	 * @since 1.0.0
	 */
	public function add_cap_if_not_exists() {

		// The current user.
		$user = wp_get_current_user(); // Get the WP_User object.

		// Check to see if the current user is defined as the privileged user and if they don't already have the 'is_wds_admin' capability.
		if ( $this->is_privileged_user() && ! $this->is_wds_admin() && is_a( $user, 'WP_User' ) ) {
			$user->add_cap( 'is_wds_admin' ); // Add the cap.
			return; // We did it.
		}

		if ( is_a( $user, 'WP_User' ) ) {

			// This user shouldn't be able to do this.
			$user->remove_cap( 'is_wds_admin' ); // Remove the cap.
		}
	}

	/**
	 * Master checker if the current user has the privileged capability.
	 *
	 * @since  1.0.0
	 *
	 * @return boolean Can the current user is_wds_admin?
	 */
	public function is_wds_admin() {
		return $this->is_privileged_user();
	}

	/**
	 * Magic getter for our object.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $field Field to get.
	 * @throws Exception Throws an exception if the field is invalid.
	 *
	 * @return mixed
	 */
	public function __get( $field ) {
		switch ( $field ) {
			case 'version':
				return $this->version;
			case 'basename':
			case 'privileged_user':
				return $this->$field;
			default:
				// @codingStandardsIgnoreLine: The concatenation below is good.
				throw new Exception( 'Invalid '. __CLASS__ . ' property: ' . $field );
		}
	}
}

/**
 * Grab the Is_WDS_Admin object and return it.
 *
 * Wrapper for Is_WDS_Admin::get_instance().
 *
 * @since  1.0.0
 * @return Is_WDS_Admin  Singleton instance of plugin class.
 */
function wds_is_admin() {
	return Is_WDS_Admin::get_instance();
}

/**
 * Checks if the current user is wds_admin and has special capabilities.
 *
 * @since  1.0.0
 * @return boolean Is the user WDS Admin?
 */
function is_wds_admin() {
	return wds_is_admin()->is_wds_admin();
}

// Kick it off.
wds_is_admin();
