# WP Assure

*(Beta) A team scalable solution for reliable WordPress acceptance testing.*

WP Assure is a toolkit that empowers developers and CI pipelines to test codebases using version controlled acceptance tests and sharable, defined file and database snapshots.

---

## Requirements

* PHP 7.1+
* mysqli PHP extension
* Docker

[WP Local Docker](https://github.com/10up/wp-local-docker) is highly recommended as the local development environment but not required.

## Installation

WP Assure is easiest to use as a project-level Composer package:

1\. Since WP Assure is in beta, you will need to set your project minimum stability to `dev`:
```
composer config minimum-stability dev
```

2\. Next, require the WP Assure package:
```
composer require 10up/wpassure:dev-master --dev
```

3\. Finally, verify and run WP Assure by calling the script in the Composer bin directory:
```
./vendor/bin/wpassure
```

After installation, you will want to [setup WP Assure on a project](https://wpassure.readthedocs.io/en/latest/project-setup/).

## Project Setup

After [installing WP Assure](https://wpassure.readthedocs.io/en/latest/install/), you need to setup your project and development workflow.

1\. Spin up your local environment. WP Assure will use your local if run with the  `--local` flag. We highly recommend [WP Local Docker](https://github.com/10up/wp-local-docker).

2\. Decide where you want to initialize WP Assure (create wpassure.json) which must be the root of your version controlled repository. This is usually in `wp-content/`, a theme, or a plugin. `wp-content/` might make most sense if you are developing an entire website. Let's assume we are initializing our WP Assure project in `wp-content/` and have installed WP Assure in the same directory.

Navigate to `wp-content` in the command line. Run the following command:
```
./vendor/bin/wpassure init
```

3\. You will be presented with some command prompts. Choose a project slug and select the defaults for the other options. When the command is finished, there will be a `wpassure.json` file in `wp-content` as well as a `tests` directory and an example test, `tests/ExampleTest.php`.

WP Assure reads `wpassure.json` every time tests are run. The file must contain both `name` and `tests` properties in JSON format. `name` is the name of your test suite, and it must be unique. `snapshot_id` is optional and is explained in [Workflow and Snapshots](https://wpassure.readthedocs.io/en/latest/workflow-snapshots/). `tests` points to your test files. WP Assure tests are written in PHP and PHPUnit based.

*There are a few important rules for wpassure.json:*

* `wpassure.json` and the actual tests __must__ exist within the codebase you are testing.
* `wpassure.json` __must__ be located in the root of your version controlled codebase (unless you set `repo_path`, see below). Typically this means `wpassure.json` is in the root of a theme, plugin, or `wp-content` directory.

4\. Now let's run our tests to make sure everything works:
```
./vendor/bin/wpassure run --local
```

You should see your tests passing.

5\. Now let's create a `primary` snapshot to commit to our repository. In order to do this, we will need [WP Snapshots](https://github.com/10up/wpsnapshots) configured. WP Snapshots handles the transportation and storage of snapshots. Run the following command to configure WP Snapshots (if it's not already configured):
```
./vendor/bin/wpsnapshots configure <repository-name>
```

You will need to create a repository if you don't have one. At 10up, we use `10up` as our `<repository-name>`.

6\. Now that we are ready with WP Snapshots, let's run our tests again but this time saving the snapshot ID to our `wpassure.json` and pushing the snapshot to our remote repository:
```
./vendor/bin/wpassure run --local --save
```

After our tests pass, you will see the snapshot get pushed upstream.

7\. Commit the snapshot ID to your repository and push the new commit upstream.

8\. Now that you have a snapshot ID in your `wpassure.json`, you can run your test suite without having a local environment running:
```
./vendor/bin/wpassure run
```

You should create new snapshots when new features, plugins, content types, etc. are added to your web application.

## Workflow and Snapshots

There are two scenarios or workflows for running WP Assure:

1. Testing a codebase using your local environment (files and database).
2. Testing a codebase against a "primary" snapshot.

The power of WP Assure is working with a team that is all testing it's code against one *primary snapshot*. Of course, in order for this to be successful the primary snapshot must be kept relevant which is the responsiblity of the development team. For example, when new content types are added, content should be added and a new primary snapshot created.

To test a codebase on your local environment, you would run the following command in the directory of `wpassure.json`:
```
wpassure run --local --save
```

The `--local` flag will force WP Assure to ignore a snapshot ID defined in `wpassure.json`. The `--save` flag will make WP Assure create a new snapshot from your local and save the ID to `wpassure.json` (overwritting any old ID). After saving a new primary snapshot to `wpassure.json`, you will want to commit and push the change upstream.

To test a codebase on a primary snapshot, you would simply run the following command in the directory of `wpassure.json`:
```
wpassure run
```

You can only run WP Assure against snapshots that contain some version of the codebase your are testing. This means the snapshot must contain `wpassure.json` with the same `name` as the one you are running.

## wpassure.json File

`wpassure.json` is the "configuration" file read by WP Assure. This file is required for each project. Whenever a test suite is run via the `run` command, `wpassure.json` is processed.

Here's what `wpassure.json` looks like

```json
{
	"name": "example-suite",
	"tests": [
		"tests\/*.php"
 	],
	"snapshot_id": "...",
	"exclude": [
		...
	],
	"test_clean_db": true,
	"bootstrap": "./bootstrap.php",
	"repo_path": "%WP_ROOT%/wp-content",
	"before_scripts": [
		...
	]
}
```

* `name` (required) - Name of test suite.
* `tests` (required) - This is an array of path(s) to tests. Each path in the array is processed via PHP `glob`. `*.php` will include every PHP file in the directory. Sholud be relative to `wpassure.json`.
* `snapshot_id` - "Primary" snapshot to test again. If the `run` command is executed without the `--local` flag, this snapshot ID will be used.
* `exclude` - WP Assure copys all the files in your repository into the snapshot for testing. There may be directories you want to include to speed things up e.g. `node_modules` and `vendor`. Should be relative `wp_assure.json`.
* `test_clean_db` - If set to `true`, a "clean" DB will be used for each test in the suite. "clean" means the untampered DB from the snapshot.
* `bootstrap` - Path to bootstrap file. This file will be executed before test execution begins. Should be relative to `wpassure.json`.
* `repo_path` - The path to the root of your repository. WP Assure needs to know where to insert your codebase into the snapshot. If `repo_path` is not provided, it assumes `wpassure.json` is in the root of your repo. `repo_path` can be relative (from your `wpassure.json` file) or you can use the `%WP_ROOT%` variable to set the path.
* `before_scripts` - An array of scripts to run in the same directory as `wpassure.json` before running tests.

## Writing Tests

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

### Actor

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

### Database

WP Assure not only let's you test UI elements but how your web application interacts with the database as well.

We can assert that new posts (or other custom post types) were created:
```php
$last_id = self::getLastPostId( [ 'post_type' => 'post' ] );

// Interact with website....

self::assertNewPostsExist( $last_id, [ 'post_type' => 'post' ], 'No new post.' );
```

`self::assertNewPostsExist` checks for new database items after `$last_id`.

### Full API Documentation

To read about all WP Assure testing related methods, look at the [source code Actor class](https://github.com/10up/wpassure/blob/master/src/classes/PHPUnit/Actor.php).

### Examples

For detailed test examples, look at the [example test suite](https://github.com/10up/wpassure/tree/master/example/twentyseventeen).

## Commands

* __wpassure run__ [&lt;PATH TO wpassure.json DIRECTORY&gt;] [--local] [--snapshot_id=&lt;WPSNAPSHOT ID&gt;] [--test_clean_db] [--preserve_containers] [--db_host=&lt;DATABASE HOST&gt;] [--verbose] [--wp_directory=&lt;PATH TO WP DIRECTORY&gt;] [--save] [--force_save] [--filter_tests=&lt;TEST FILTER&gt;] [--filter_test_files=<TEST FILE FILTER>] - Runs a test suite.
	* `<PATH TO wpassure.json DIRECTORY>` - Path to `wpassure.json`, defaults to current working directory.
	* `--local` - Runs your test suite against your local environment.
	 * `--verbose`, `-v`, `-vv`, `-vvv` - Run with various degrees of verbosity.
	* `--save` - If tests are successful, save snapshot ID to `wpassure.json` and push snapshot upstream.
	* `--force_save` - Save snapshot ID to `wpassure.json` and push snapshot upstream no matter what.
	* `--wp_directory` - Path to WordPress. If unset, will search up the directory tree until wp-config.php is found
	* `--snapshot_id` - Optionally run tests against a snapshot ID.
	* `--test_clean_db` - Use clean database for each test.
	* `--preserve_containers` - Don't stop/remove containers on run completion.
	* `--filter_tests` - Filter tests to run. Is analagous to PHPUnit --filter.
	* `--filter_test_files` - Comma separate test files to execute. If used all other test files will be ignored.
  
* __wpassure init__ [--path] - Initialize a new test suite.
	* `--path` - Optional path to init direftory.



