# Writing Tests

WP Assure tests are based on [PHPUnit](https://phpunit.de/). Here is a simple example of a test:

```php
class ExampleTest extends \WPAssure\PHPUnit\TestCase {

	/**
	 * Example test
	 */
	public function testExample() {
		$this->assertTrue( true );
	}
}
```

You can place tests in whatever files you choose (as long as `tests` points to the right place in `wpassure.json`). However, per PHPUnit defaults, your test code must be in a class (or classes across multiple files) with name(s) that end in `Test`. Inside your test class(es), each test method must begin with `test`.

All your tests must extend `\WPAssure\PHPUnit\TestCase`. `\WPAssure\PHPUnit\TestCase` extends `\PHPUnit\Framework\TestCase` so you will have access to all the standard PHPUnit methods e.g. `assertTrue`, `assertEquals`, etc.

Along with standard PHPUnit functionality, you have WP Assure special methods/functions/classes:

# Actor

The most poweful WP Assure functionality is provided by the `Actor` class and let's you interact as a Chrome browser user with your website.

A new Actor must be initialized for each test and is done like so:
```php
public function testExample() {
	$I = $this->getAnonymousUser();
}
```

`getAnonymousUser` does take an optional array of arguments. In particular, you can choose the browser size: `getAnonymousUser( [ 'screen_size' => 'small' ] )`.

With `$I` we can navigate to sections of our website:
```php
$I->moveTo( 'page-two' );
```

When you ask the browser to take an action that isn't instant, you will need to wait:

```php
$I->moveTo( 'page-two' );

$I->waitUntilElementVisible( 'body.page-two' );
```

The Actor can login to the WordPress admin:
```php
$I->loginAs( 'wpsnapshots' );
```

We can fill in form fields:
```php
$I->fillField( '.field-name', 'value' );
$I->checkOptions( '.checkbox-or-radio' );
$I->selectOptions( '.select', 'value-to-select' );
```

Since WP Assure is built on WP Snapshots, the `wpsnapshots` user is always available as a super admin with password, `password`.

Since `$I` is literally interacting with a browser, we can do anything a browser can: click on elements, follow links, get specific DOM elements, run JavaScript, resize the browser, refresh the page, interact with forms, move the mouse, interact with the keyboard, etc.

The Actor also contains methods for making assertions:
```php
$I->seeText( 'text' );
$I->dontSeeText( 'text' );
$I->seeText( 'text', '.element-to-search-within' );

$I->seeElement( '.element-path' );
$I->dontSeeElement( '.element-path' );

$I->seeLink( 'Link Text', 'http://url' );
$I->dontSeeLink( 'Link Text', 'http://url' );

$I->seeTextInUrl( 'Title Text' );
$I->dontSeeTextInUrl( 'Title Text' );
```

# Database

WP Assure not only let's you test UI elements but how your web application interacts with the database as well.

We can assert that new posts (or other custom post types) were created:
```php
$last_id = self::getLastPostId( [ 'post_type' => 'post' ] );

// Interact with website....

self::assertNewPostsExist( $last_id, [ 'post_type' => 'post' ], 'No new post.' );
```

`self::assertNewPostsExist` checks for new database items after `$last_id`.

# Full API Documentation

To read about all WP Assure testing related methods, look at the [source code Actor class](https://github.com/10up/wpassure/blob/master/src/classes/PHPUnit/Actor.php).

# Examples

For detailed test examples, look at the [example test suite](https://github.com/10up/wpassure/tree/master/example/twentyseventeen).
