<?php
/**
 * Standard customizer
 *
 * @package wpacceptance
 */

namespace WPAcceptance\PHPUnit\StandardTests;

/**
 * PHPUnit test class
 */
trait Customizer {

	/**
	 * Test that someone can update site name/tagline in customizer.
	 */
	protected function _testCustomizerCanUpdateIdentity() {
		$actor = $this->openBrowserPage();

		$actor->login();

		$actor->moveTo( 'wp-admin/customize.php' );

		$actor->waitUntilElementVisible( '#customize-theme-controls' );

		$actor->click( '#accordion-section-title_tagline' );

		$actor->waitUntilElementVisible( '#_customize-input-blogname' );

		$actor->typeInField( '#_customize-input-blogname', 'New Site Name' );

		$actor->typeInField( '#_customize-input-blogdescription', 'New tagline' );

		$actor->waitUntilPropertyContains( 'New tagline', '#_customize-input-blogdescription', 'value' );

		$actor->waitUntilPropertyContains( 'New Site Name', '#_customize-input-blogname', 'value' );

		$actor->waitUntilElementEnabled( '#save' );

		$actor->click( '#save' );

		$actor->waitUntilPropertyContains( 'Published', '#save', 'value' );

		$actor->moveTo( 'wp-admin/options-general.php' );

		$actor->seeValueInAttribute( '#blogname', 'value', 'New Site Name' );

		$actor->seeValueInAttribute( '#blogdescription', 'value', 'New tagline' );
	}
}
