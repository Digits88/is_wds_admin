<?php

class BaseTest extends WP_UnitTestCase {

	function test_class_exists() {
		$this->assertTrue( class_exists( 'Is_WDS_Admin' ) );
	}

	function test_get_instance() {
		$this->assertTrue( wds_is_admin() instanceof Is_WDS_Admin );
	}

	/**
	 * Helper function to create a user, set it as the current user, and return the user
	 * @return object Created user object
	 */
	function get_test_user_as_current_user( $login = '' ) {

		if ( $login ) {
			$user = $this->factory->user->create_and_get( array( 'user_login' => $login ) );
		} else {
			$user = $this->factory->user->create_and_get();
		}

		wp_set_current_user( $user->ID );

		return $user;
	}

	/**
	 * Test that our helper function sets the created ID to the current user
	 */
	function test_helper_user_creator_sets_ID() {
		$user = $this->get_test_user_as_current_user();
		$this->assertEquals( $user->ID, get_current_user_id() );
	}

	/**
	 * Test that our helper function can set a specific username
	 */
	function test_helper_user_creator_creates_with_username() {
		$user = $this->get_test_user_as_current_user( 'this_is_a_username' );
		$this->assertEquals( $user->user_login, 'this_is_a_username' );
	}

	/**
	 * Test that the default cap name is 'is_wds_admin'
	 */
	function test_get_cap_name() {
		$this->assertEquals( wds_is_admin()->get_cap_name(), 'is_wds_admin' );
	}

	/**
	 * Test that a newly created user does not get our cap
	 */
	function test_normal_created_user_doesnt_get_cap() {
		$user = new WP_User( $this->get_test_user_as_current_user() );

		$this->assertFalse( $user->has_cap( 'is_wds_admin' ) );
	}

	/**
	 * Test that an admin is not a wds_admin
	 */
	function test_admin_is_not_wds_admin() {
		$sample_user = $this->factory->user->create_and_get( array( 'role' => 'administrator' ) );
		wp_set_current_user( $sample_user->ID );

		$this->assertFalse( is_wds_admin() );
	}

	/**
	 * Test that a user is not a wds_admin
	 */
	function test_is_not_wds_admin() {
		$user = $this->get_test_user_as_current_user();

		wp_set_current_user( $user->ID );

		$this->assertFalse( is_wds_admin() );
	}

	/**
	 * Test that the wds_admin user IS a wds_admin
	 */
	function test_is_wds_admin_by_username() {

		$user = $this->get_test_user_as_current_user( 'wds_admin' );
		wp_set_current_user( $user->ID );

		$this->assertTrue( is_wds_admin() );
	}

	/**
	 * Test that filtering the allowed usernames array works.
	 */
	function test_filtering_allowed_usernames() {

		add_filter( 'wds_is_admin_allowed_usernames', array( $this, 'filter_allowed_usernames' ) );

		$this->get_test_user_as_current_user( 'wds_admin_added_with_filter' );
		$this->assertTrue( is_wds_admin() );

		$this->get_test_user_as_current_user( 'wds_admin' );
		$this->assertTrue( is_wds_admin() );

		$this->get_test_user_as_current_user();
		$this->assertFalse( is_wds_admin() );

		remove_filter( 'wds_is_admin_allowed_usernames', array( $this, 'filter_allowed_usernames' ) );
	}

	function filter_allowed_usernames( $usernames ) {
		$usernames[] = 'wds_admin_added_with_filter';
		return $usernames;
	}
}
