# Cookbook

## Testing in the WordPress Admin

This test creates a post and makes sure it's published.

```php
/**
 * @testdox I am able to publish a post.
 */
function testPostPublish() {
	$I = $this->openBrowserPage();

	$I->loginAs( 'wpsnapshots' );

	$I->moveTo( 'wp-admin/post-new.php' );

	$I->fillField( '#title', 'Test Post' );

	$I->setElementAttribute( '#content', 'value', 'Test content' );

	$I->click( '#publish', true );

	$I->waitUntilElementVisible( '.notice-success' );

	$I->seeText( 'Post published', '.notice-success' );
}
```

Note that the `wpsnapshots` user is always available as a super admin.

This test tests adding media to a post and setting it as the featured image:

```php
/**
 * @testdox I am able to attach a featured image to a post.
 */
function testFeaturedImage() {
	$I = $this->openBrowserPage();

	$I->loginAs( 'wpsnapshots' );

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
