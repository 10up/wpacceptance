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
	 * Check that we can add a featured image pre-gutenberg UI
	 */
	protected function _testFeaturedImagePreGutenberg() {
		$actor = $this->openBrowserPage();

		$actor->login();

		$actor->moveTo( 'wp-admin/post-new.php' );

		$actor->fillField( '#title', 'Test Post' );

		$actor->click( '#set-post-thumbnail' );

		$actor->waitUntilElementVisible( '.media-modal-content' );

		$actor->attachFile( '.media-modal-content input[type="file"]', __DIR__ . '/img/10up-logo.jpg' );

		$actor->waitUntilElementEnabled( '.media-modal-content .media-button-select' );

		$actor->click( '.media-modal-content .media-button-select' );

		$actor->waitUntilElementVisible( '#remove-post-thumbnail' );

		$actor->scrollTo( 0, 0 );

		$actor->click( '#publish' );

		$actor->waitUntilElementVisible( '#wpadminbar' );

		$actor->seeElement( '#postimagediv img' );
	}

	/**
	 * Test uploading a featured image to a post.
	 */
	protected function _testFeaturedImage() {
		$actor = $this->openBrowserPage();

		$actor->login();

		$actor->moveTo( 'wp-admin/post-new.php' );

		$actor->click( '.nux-dot-tip__disable' );

		$actor->typeInField( '#post-title-0', 'Test Post Image' );

		$elements = $actor->getElements( '.components-panel__body-toggle' );

		foreach ( $elements as $element ) {
			$actor->click( $element );
		}

		$actor->waitUntilElementVisible( '.editor-post-featured-image__toggle' );

		$actor->click( '.editor-post-featured-image__toggle' );

		$actor->waitUntilElementVisible( '.upload-ui' );

		$actor->attachFile( '.media-modal-content input[type="file"]', __DIR__ . '/img/10up-logo.jpg' );

		$actor->waitUntilElementEnabled( '.media-button-select' );

		$actor->click( '.media-button-select' );

		$actor->waitUntilElementVisible( '.editor-post-publish-panel__toggle' );

		$actor->waitUntilElementEnabled( '.editor-post-publish-panel__toggle' );

		$actor->click( '.editor-post-publish-panel__toggle' );

		$actor->waitUntilElementVisible( '.editor-post-publish-button' );

		$actor->waitUntilElementEnabled( '.editor-post-publish-button' );

		$actor->click( '.editor-post-publish-button' );

		$actor->waitUntilElementVisible( '.components-notice' );

		$actor->seeText( 'Post published', '.components-notice__content' );

		$actor->waitUntilElementVisible( '.editor-post-featured-image img' );

		$actor->seeElement( '.editor-post-featured-image img' );
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

	/**
	 * Test that permalinks can be changed in the backend
	 */
	protected function _testChangePermalinks() {
		$actor = $this->openBrowserPage();

		$actor->login();

		$actor->moveTo( 'wp-admin/options-permalink.php' );

		$actor->checkOptions( 'input[value="/%year%/%monthnum%/%day%/%postname%/"]' );

		$actor->click( '#submit' );

		$actor->waitUntilElementVisible( '#wpadminbar' );

		$actor->seeCheckboxIsChecked( 'input[value="/%year%/%monthnum%/%day%/%postname%/"]' );

		$actor->click( 'input[value="/%postname%/"]' );

		$actor->click( '#submit' );

		$actor->waitUntilElementVisible( '#wpadminbar' );

		$actor->seeCheckboxIsChecked( 'input[value="/%postname%/"]' );
	}
}
