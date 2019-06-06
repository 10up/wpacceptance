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

	public function _testCustomizerCanUpdateIdentity() {
		$actor = $this->openBrowserPage();

		$actor->login();

		$actor->moveTo( 'wp-admin/customize.php' );

		$actor->waitUntilElementVisible( '#customize-theme-controls' );

		$actor->click( '#accordion-section-title_tagline' );

		$actor->waitUntilElementVisible( '#_customize-input-blogname' );

		$actor->fillField( '#_customize-input-blogname', 'New Site Name' );

		$actor->fillField( '#_customize-input-blogdescription', 'New tagline' );

		$actor->click( '#save' );

		$actor->waitUntilPropertyContains( 'Published', '#save', 'value' );

		$actor->moveTo( 'wp-admin/options-general.php' );

		$actor->seeValueInAttribute( '#blogname', 'value', 'New Site Name' );

		$actor->seeValueInAttribute( '#blogdescription', 'value', 'New tagline' );
	}
}
