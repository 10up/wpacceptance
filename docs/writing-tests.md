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

All your tests must extend `\WPAssure\PHPUnit\TestCase`. `\WPAssure\PHPUnit\TestCase` extends `\PHPUnit\Framework\TestCase` so you will have access to all the standard PHPUnit methods e.g. `assertTrue`, `assertEquals`, etc.

You can place tests in whatever files you choose (as long as `tests` points to the right place in `wpassure.json`). However, per PHPUnit defaults, your test code must be in a class (or classes across multiple files) with name(s) that end in `Test`. Inside your test class(es), each test method must begin with `test`.
