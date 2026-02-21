<?php
/**
 * Sample Test
 *
 * @package ConnectCrmRealstate
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Sample test case.
 */
class SampleTest extends TestCase {

	/**
	 * Test that WordPress is loaded
	 */
	public function test_wordpress_loaded() {
		$this->assertTrue( function_exists( 'add_action' ) );
	}

	/**
	 * Test that plugin constants are defined
	 */
	public function test_plugin_constants_defined() {
		$this->assertTrue( defined( 'CCRMRE_VERSION' ) );
		$this->assertTrue( defined( 'CCRMRE_PLUGIN' ) );
		$this->assertTrue( defined( 'CCRMRE_PLUGIN_URL' ) );
		$this->assertTrue( defined( 'CCRMRE_PLUGIN_PATH' ) );
	}
}
