# Cookbook

## Standard Tests

WP Acceptance contains a number of [standard tests](https://github.com/10up/wpacceptance/tree/master/src/classes/PHPUnit/StandardTests). You can invoke the standard tests your own to quickly create a powerful testing library for your WordPress plugin. Here's an example:

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

## Testing in the WordPress Admin

This test creates a post and makes sure it's published.

```php
/**
 * @testdox I am able to publish a post.
 */
function testPostPublish() {
	$I = $this->openBrowserPage();

	$I->loginAs( 'wpsnapshots' ); // The username would be `admin` if using instructions.

	$I->moveTo( 'wp-admin/post-new.php' );

	$I->fillField( '#title', 'Test Post' );

	$I->setElementAttribute( '#content', 'value', 'Test content' );

	$I->click( '#publish', true );

	$I->waitUntilElementVisible( '.notice-success' );

	$I->seeText( 'Post published', '.notice-success' );
}
```

Note that the `wpsnapshots` user is always available as a super admin when using a snapshot. If using instructions, `admin` user is always available.

This test tests adding media to a post and setting it as the featured image:

```php
/**
 * @testdox I am able to attach a featured image to a post.
 */
function testFeaturedImage() {
	$I = $this->openBrowserPage();

	$I->loginAs( 'wpsnapshots' ); // The username would be `admin` if using instructions.

	$I->moveTo( 'wp-admin/post-new.php' );

	$I->fillField( '#title', 'Test Post' );

	// Set featured image
	$I->click( '#set-post-thumbnail' );
	$I->attachFile( '.media-modal-content input[type="file"]', PATH_TO_IMAGE );

	$I->waitUntilElementEnabled( '.media-modal-content .media-button-select' );

	$I->click( '.media-modal-content .media-button-select' );

	$I->waitUntilElementVisible( '#remove-post-thumbnail' );

	$I->click( '#publish', true );

	$I->waitUntilElementVisible( '#wpadminbar' );

	// See featured image
	$I->seeElement( '#postimagediv img' );
}
```
