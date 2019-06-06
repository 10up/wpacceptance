# Cookbook

## Standard Tests

WP Acceptance contains a number of [standard tests](https://github.com/10up/wpacceptance/tree/master/src/classes/PHPUnit/StandardTests). You can invoke the standard tests your own to quickly create a powerful testing library for your WordPress application. Here's an example:

```php
class StandardTests extends \WPAcceptance\PHPUnit\TestCase {

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
}

```

## Examples

### Test post publishing

This test creates a post and makes sure it's published.

```php
/**
 * @testdox I am able to publish a post pre-gutenberg.
 */
function testPostPublish() {
	$I = $this->openBrowserPage();

	$I->login();

	$I->moveTo( 'wp-admin/post-new.php' );

	$I->click( '.nux-dot-tip__disable' );

	$I->typeInField( '#post-title-0', 'Test' );

	usleep( 100 );

	$I->waitUntilElementVisible( '.editor-post-publish-panel__toggle' );

	$I->waitUntilElementEnabled( '.editor-post-publish-panel__toggle' );

	$I->click( '.editor-post-publish-panel__toggle' );

	$I->waitUntilElementVisible( '.editor-post-publish-button' );

	$I->waitUntilElementEnabled( '.editor-post-publish-button' );

	$I->click( '.editor-post-publish-button' );

	$I->waitUntilElementVisible( '.components-notice' );

	$I->seeText( 'Post published', '.components-notice__content' );
}
```

### Test setting a featured image

This test tests adding media to a post and setting it as the featured image:

```php
/**
 * @testdox I am able to attach a featured image to a post.
 */
function testFeaturedImage() {
	$I = $this->openBrowserPage();

	$I->login();

	$I->moveTo( 'wp-admin/post-new.php' );

	$I->click( '.nux-dot-tip__disable' );

	$I->typeInField( '#post-title-0', 'Test Post Image' );

	$elements = $I->getElements( '.components-panel__body-toggle' );

	foreach ( $elements as $element ) {
		$I->click( $element );
	}

	$I->waitUntilElementVisible( '.editor-post-featured-image__toggle' );

	$I->click( '.editor-post-featured-image__toggle' );

	$I->waitUntilElementVisible( '.upload-ui' );

	$I->attachFile( '.media-modal-content input[type="file"]', __DIR__ . '/img/10up-logo.jpg' );

	$I->waitUntilElementEnabled( '.media-button-select' );

	$I->click( '.media-button-select' );

	$I->waitUntilElementVisible( '.editor-post-publish-panel__toggle' );

	$I->waitUntilElementEnabled( '.editor-post-publish-panel__toggle' );

	$I->click( '.editor-post-publish-panel__toggle' );

	$I->waitUntilElementVisible( '.editor-post-publish-button' );

	$I->waitUntilElementEnabled( '.editor-post-publish-button' );

	$I->click( '.editor-post-publish-button' );

	$I->waitUntilElementVisible( '.components-notice' );

	$I->seeText( 'Post published', '.components-notice__content' );

	$I->waitUntilElementVisible( '.editor-post-featured-image img' );

	$I->seeElement( '.editor-post-featured-image img' );
}
```

### Test that a post is showing the proper content on the front end

Here we actually look up a post in the database and make sure that it's proper title is displayed on the front end.

```php
/**
 * @testdox I see the proper post title displed on the front end.
 */
function testPostDisplay() {
	$I = $this->openBrowserPage();

	$post = self::selectRowsWhere( [ 'ID' => 1 ], 'posts' );

	$I->moveTo( '/?p=1' ); // This will redirect to proper permalink

	$I->seeText( $post->post_title, 'h1.title' );
}
```

### Test an update in the customizer

Here we update the site identity in the customizer and make sure the change is reflected.

```php
/**
 * @testdox I can update site identity in the customizer.
 */
protected function testCustomizerCanUpdateIdentity() {
	$I = $this->openBrowserPage();

	$I->login();

	$I->moveTo( 'wp-admin/customize.php' );

	$I->waitUntilElementVisible( '#customize-theme-controls' );

	$I->click( '#accordion-section-title_tagline' );

	$I->waitUntilElementVisible( '#_customize-input-blogname' );

	$I->typeInField( '#_customize-input-blogname', 'New Site Name' );

	$I->typeInField( '#_customize-input-blogdescription', 'New tagline' );

	$I->waitUntilPropertyContains( 'New tagline', '#_customize-input-blogdescription', 'value' );

	$I->waitUntilPropertyContains( 'New Site Name', '#_customize-input-blogname', 'value' );

	$I->waitUntilElementEnabled( '#save' );

	$I->click( '#save' );

	$I->waitUntilPropertyContains( 'Published', '#save', 'value' );

	$I->moveTo( 'wp-admin/options-general.php' );

	$I->seeValueInAttribute( '#blogname', 'value', 'New Site Name' );

	$I->seeValueInAttribute( '#blogdescription', 'value', 'New tagline' );
}
```
