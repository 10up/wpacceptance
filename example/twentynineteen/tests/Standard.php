<?php
/**
 * Test standard WP functionality
 *
 * @package wpacceptance
 */

/**
 * PHPUnit test class
 */
class StandardTest extends \WPAcceptance\PHPUnit\TestCase {

	/**
	 * @testdox I see required HTML tags on front end.
	 */
	public function testRequiredHTMLTagsOnFrontEnd() {
		parent::_testRequiredHTMLTags();
	}

	/**
	 * @testdox I can log in.
	 */
	public function testLogin() {
		parent::_testLogin();
	}

	/**
	 * @testdox I see the admin bar
	 */
	public function testAdminBarOnFront() {
		parent::_testAdminBarOnFront();
	}

	/**
	 * @testdox I can save my profile
	 */
	public function testProfileSave() {
		parent::_testProfileSave();
	}

	/**
	 * @testdox I can install a plugin
	 */
	public function testInstallPlugin() {
		parent::_testInstallPlugin();
	}

	/**
	 * @testdox I can change the site title
	 */
	public function testChangeSiteTitle() {
		parent::_testChangeSiteTitle();
	}

	/**
	 * @testdox I can change site title and description in the customizer
	 */
	public function testCustomizerCanUpdateIdentity() {
		parent::_testCustomizerCanUpdateIdentity();
	}
}
