<?php

class BaseTest extends WP_UnitTestCase {

	function test_class_exists() {
		$this->assertTrue( class_exists( 'Is_WDS_Admin') );
	}
	
	function test_get_instance() {
		$this->assertTrue( is_wds_admin() instanceof Is_WDS_Admin );
	}
}
