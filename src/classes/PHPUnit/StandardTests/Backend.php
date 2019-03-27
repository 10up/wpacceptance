<?php
/**
 * Standard back end tests
 *
 * @package wpacceptance
 */

namespace WPAcceptance\PHPUnit\StandardTests;

/**
 * PHPUnit test class
 */
trait Backend {

	/**
	 * Test that someone can login
	 */
	protected function _testLogin() {
		$actor = $this->openBrowserPage();

		$actor->login();

		$actor->seeElement( '#wpadminbar', 'Could not login.' );
	}

	/**
	 * Test admin bar shows on the front end
	 */
	protected function _testAdminBarOnFront() {
		$actor = $this->openBrowserPage();

		$actor->login();

		$actor->moveTo( '/' );

		$actor->seeElement( '#wpadminbar', 'Admin bar not showing on front end.' );
	}

	/**
	 * Test that a post actually publishes pre-gutenberg
	 */
	protected function _testPostPublishPreGutenberg() {
		$actor = $this->openBrowserPage();

		$actor->login();

		$actor->moveTo( 'wp-admin/post-new.php' );

		$actor->waitUntilElementVisible( '#title' );

		$actor->fillField( '#title', 'Test Post' );

		$actor->setElementAttribute( '#content', 'value', 'Test content' );

		$actor->click( '#publish' );

		$actor->waitUntilElementVisible( '.notice-success' );

		$actor->seeText( 'Post published', '.notice-success' );
	}

	/**
	 * Test that a post actually publishes with gutenberg
	 */
	protected function _testPostPublish() {
		$actor = $this->openBrowserPage();

		$actor->login();

		$actor->moveTo( 'wp-admin/post-new.php' );

		$actor->click( '.nux-dot-tip__disable' );

		$actor->typeInField( '#post-title-0', 'Test' );

		usleep( 100 );

		$actor->waitUntilElementVisible( '.editor-post-publish-panel__toggle' );

		$actor->waitUntilElementEnabled( '.editor-post-publish-panel__toggle' );

		$actor->click( '.editor-post-publish-panel__toggle' );

		$actor->waitUntilElementVisible( '.editor-post-publish-button' );

		$actor->waitUntilElementEnabled( '.editor-post-publish-button' );

		$actor->click( '.editor-post-publish-button' );

		$actor->waitUntilElementVisible( '.components-notice' );

		$actor->seeText( 'Post published', '.components-notice__content' );
	}

	/**
	 * Test that a user can edit their profile
	 */
	protected function _testProfileSave() {
		$actor = $this->openBrowserPage();

		$actor->login();

		$actor->moveTo( '/wp-admin/profile.php' );

		$actor->waitUntilElementVisible( '#wpadminbar' );

		$actor->fillField( '#first_name', 'Test Name' );

		$actor->click( '#submit' );

		$actor->waitUntilElementVisible( '#wpadminbar' );

		$actor->seeValueInAttribute( '#first_name', 'value', 'Test Name', 'Profile field did not update.' );
	}

	/**
	 * Test that a plugin can be installed
	 */
	protected function _testInstallPlugin() {
		$actor = $this->openBrowserPage();

		$actor->login();

		$actor->moveTo( 'wp-admin/plugin-install.php' );

		$actor->waitUntilElementVisible( '.plugin-install-popular' );

		$actor->click( '.plugin-install-popular' );

		$actor->waitUntilElementVisible( '#the-list' );

		$plugins = $actor->getElements( '.plugin-card' );

		$slug = null;

		foreach ( $plugins as $plugin ) {
			$first_button = $actor->getElement( '.button', $plugin );

			if ( is_array( $first_button ) ) {
				$first_button = $first_button[0];
			}

			$button_classes = $actor->getElementAttribute( $first_button, 'class' );

			if ( false !== strpos( $button_classes, 'install' ) ) {
				$slug = preg_replace( '#^.*plugin-card-([^\s]+).*$#', '$1', $actor->getElementAttribute( $plugin, 'class' ) );
			}
		}

		if ( empty( $slug ) ) {
			$this->fail();
		}

		$actor->click( '.plugin-card-' . $slug . ' .install-now' );

		$actor->waitUntilElementVisible( '.plugin-card-' . $slug . ' .activate-now' );

		$actor->click( '.plugin-card-' . $slug . ' .activate-now' );

		$actor->waitUntilElementVisible( 'tr[data-slug="' . $slug . '"] .deactivate' );

		$this->assertTrue( true );
	}

	/**
	 * Test that the site title can be changed
	 */
	protected function _testChangeSiteTitle() {
		$actor = $this->openBrowserPage();

		$actor->login();

		$actor->moveTo( 'wp-admin/options-general.php' );

		$actor->fillField( '#blogname', 'Updated Title' );

		$actor->click( '#submit' );

		$actor->waitUntilElementVisible( '#wp-admin-bar-site-name' );

		$actor->seeText( 'Updated Title', '#wp-admin-bar-site-name a' );
	}
}
