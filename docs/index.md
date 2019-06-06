# WP Acceptance

*(Beta) A team scalable solution for reliable WordPress acceptance testing.*

WP Acceptance is a toolkit that empowers developers and CI pipelines to test codebases using version controlled acceptance tests and sharable environments.

---

## Requirements

* PHP 7.2+
* mysqli PHP extension
* Docker
* Node >= 8 (WP Acceptance uses Puppeteer behind the scenes)

[WP Local Docker](https://github.com/10up/wp-local-docker) is highly recommended as the local development environment but not required.

## Installation

WP Acceptance is easiest to use as a project-level Composer package:

1\. Since WP Acceptance is in beta, you will need to set your project minimum stability to `dev`:
```
composer config minimum-stability dev
```

2\. Next, require the WP Acceptance package:
```
composer require 10up/wpacceptance:dev-master --dev
```

3\. Finally, verify and run WP Acceptance by calling the script in the Composer bin directory:
```
./vendor/bin/wpacceptance
```

After installation, you will want to [setup WP Acceptance on a project](#project-setup).

## Project Setup

After [installing WP Acceptance](https://wpacceptance.readthedocs.io/en/latest/install/), you need to setup your project and development workflow.

1\. Spin up your local environment. WP Acceptance will use your local if run with the  `--local` flag. We highly recommend [WP Local Docker](https://github.com/10up/wp-local-docker).

2\. Decide where you want to initialize WP Acceptance (create wpacceptance.json) which must be the root of your version controlled repository. This is usually in `wp-content/`, a theme, or a plugin. `wp-content/` might make most sense if you are developing an entire website. Let's assume we are initializing our WP Acceptance project in `wp-content/` and have installed WP Acceptance in the same directory.

Navigate to `wp-content` in the command line. Run the following command:
```
./vendor/bin/wpacceptance init
```

3\. You will be presented with some command prompts. Choose a project slug and select the defaults for the other options. When the command is finished, there will be a `wpacceptance.json` file in `wp-content` as well as a `tests` directory and an example test, `tests/ExampleTest.php`.

WP Acceptance reads `wpacceptance.json` every time tests are run. The file must contain both `name` and `tests` properties in JSON format. `name` is the name of your test suite, and it must be unique. `wpacceptance.json` can define `environment_instructions` OR `snapshot_id`. This is explained in [Workflow, Environment Instructions, and Snapshots](#workflow-environment-instructions-and-snapshots). `tests` points to your test files. WP Acceptance tests are written in PHP and PHPUnit based.

*There are a few important rules for wpacceptance.json:*

* WP Acceptance can use environment instructions or snapshots, not both.
* If you are using environment instructions, `project_path` must be defined in `wpacceptance.json`.
* If you are using snapshots, `wpacceptance.json` and the actual tests __must__ exist within the codebase you are testing.
* `wpacceptance.json` __must__ be located in the root of your version controlled codebase. Typically this means `wpacceptance.json` is in the root of a theme, plugin, or `wp-content` directory.

4\. Now let's run our tests to make sure everything works:
```
./vendor/bin/wpacceptance run --local
```

You should see your tests passing.

If you just want to run tests locally, you are done. If you want to have a teammate run your test suite or integrate with a CI process, you will need to decide on using either environment instrutions or snapshots (check out [Snapshots vs. Environment Instructions](#snapshots-vs-environment-instructions)).

### Environment Instructions

5\. In `wpacceptance.json`, create a property named `environment_instructions`. `environment_instructions` takes an array of "instructions". Instructions are processed via [WP Instructions](https://github.com/10up/wpinstructions). Here's a simple example:

```
{
	"environment_instructions": [
		"install wordpress where site url is http://wpacceptance.test and home url is http://wpacceptance.test",
		"install theme where theme name is twentynineteen"
	]
}
```

The first instruction MUST be installing WordPress. Install wordpress must include a site and home url which can be anything. For documentation and usage on supported instructions, see [WP Instructions](https://github.com/10up/wpinstructions).

`environment_instructions` can also take an array of arrays of instructions. This is useful if you want to run your tests across multiple environments:

```
{
	"environment_instructions": [
		[
			"install wordpress where version is 4.9 and site url is http://wpacceptance.test and home url is http://wpacceptance.test"
		],
		[
			"install wordpress where version is latest and site url is http://wpacceptance.test and home url is http://wpacceptance.test"
		]
	]
}
```

6\. Next you need to tell WP Acceptance where your project should be mounted. For example, if my `wpacceptance.json` is the root of `wp-content`, I would set `project_path` like so:

```
{
	"project_path": "%WP_ROOT%/wp-content"
}
```

### Snapshots

5\. Let's create a `primary` snapshot to commit to our repository. In order to do this, we will need [WP Snapshots](https://github.com/10up/wpsnapshots) configured. WP Snapshots handles the transportation and storage of snapshots. Run the following command to configure WP Snapshots (if it's not already configured):
```
./vendor/bin/wpsnapshots configure <repository-name>
```

You will need to create a repository if you don't have one. At 10up, we use `10up` as our `<repository-name>`.

6\. Now that we are ready with WP Snapshots, let's run our tests again but this time saving the snapshot ID to our `wpacceptance.json` and pushing the snapshot to our remote repository:
```
./vendor/bin/wpacceptance run --local --save
```

After our tests pass, you will see the snapshot get pushed upstream.

7\. Commit the snapshot ID to your repository and push the new commit upstream.

8\. Now that you have a snapshot ID in your `wpacceptance.json`, you can run your test suite without having a local environment running:
```
./vendor/bin/wpacceptance run
```

You should create new snapshots when new features, plugins, content types, etc. are added to your web application.

*Note:* Make sure you run WP Acceptance on your HOST machine, not within another Docker environment.

## Workflow, Environment Instructions, and Snapshots

There are three scenarios or workflows for running WP Acceptance:

1. Testing a codebase using your local environment (files and database).
2. Testing a codebase against a set of environment instructions.
3. Testing a codebase against a "primary" snapshot.

The power of WP Acceptance is working with a team or CI process that is testing it's code against a set of environment instructions or snapshot (you can also test against multiple sets of environment instructions or snapshots). Of course, in order for this to be successful the environment instructions or snapshot(s) must be kept relevant which is the responsiblity of the development team. For example, when new content types are added, content should be added via new environment instructions or a new snapshot created.

Environment instructions are a simple set of instructions for defining an environment e.g. install WordPress, download twentynineteen theme, activate plugin, etc (supported instructions are documented in [WP Instructions](https://github.com/10up/wpinstructions)). A snapshot is a database/file package where everything e.g. WP version, theme, plugins, are preset. You can use environment instructions OR snapshots, not both. Read [Snapshots vs. Environment Instructions](#snapshots-vs-environment-instructions).

To test a codebase on your local environment, you would run the following command in the directory of `wpacceptance.json`:
```
wpacceptance run --local
```

The `--local` flag will force WP Acceptance to ignore environment instructions or snapshot ID defined in `wpacceptance.json`. If you are using a snapshot workflow, you can use the `--save` flag which will make WP Acceptance create a new snapshot from your local and save the ID to `wpacceptance.json`. After saving a new snapshot to `wpacceptance.json`, you will want to commit and push the change upstream.

To test a codebase against environment instructions (assuming `environment_instructions` and `project_path` are defined in `wpacceptance.json`), you would run the following command in the directory of `wpacceptance.json`:
```
wpacceptance run
```

To test a codebase against snapshot(s) (assuming `snapshot_id` or `snapshots` is defined in `wpacceptance.json`), you would simply run the following command in the directory of `wpacceptance.json`:
```
wpacceptance run
```

You can only run WP Acceptance against snapshots that contain some version of the codebase your are testing. This means snapshots must contain `wpacceptance.json` with the same `name` as the one you are running.

## wpacceptance.json File

`wpacceptance.json` is the "configuration" file read by WP Acceptance. This file is required for each project. Whenever a test suite is run via the `run` command, `wpacceptance.json` is processed.

Here's what `wpacceptance.json` looks like

```json
{
	"name": "example-suite",
	"tests": [
		"tests\/*.php"
 	],
	"snapshot_id": "...",
	"snapshots": [
		{
			"snapshot_name": "name",
			"snapshot_id": "ID"
		}
	],
	"environment_instructions": [
		[
			"..."
		]
	],
	"project_path": "%WP_ROOT%/wp-content",
	"exclude": [
		"node_modules",
		"vendor"
	],
	"enforce_clean_db": true,
	"disable_clean_db": false,
	"repository": "10up",
	"bootstrap": "./bootstrap.php",
	"before_scripts": [
		"composer install",
		"npm run build"
	]
}
```

* `name` (required) - Name of test suite.
* `tests` (required) - This is an array of path(s) to tests. Each path in the array is processed via PHP `glob`. `*.php` will include every PHP file in the directory. Sholud be relative to `wpacceptance.json`.
* `snapshot_id` - Snapshot to test against. If the `run` command is executed without the `--local` flag, this snapshot ID will be used (if no `environment_instructions` are defined). This will override `snapshots` if set.
* `snapshot_name` - Snapshot name to test against. If the `run` command is executed without the `--local` flag, this snapshot would be used. Must reference a snapshot name in `snapshots`.
* `snapshots` - You can specify multiple snapshots to test your code against.
* `environment_instructions` - Instructions for creating environment to test against. If the `run` command is executed without the `--local` flag, these instructions will be used to create the environment assuming no `snapshot_id` is set. Supported instructions are documented in [WP Instructions](https://github.com/10up/wpinstructions). This can be an array of array to test against multiple environments.
* `project_path` - Absolute path to your `wpacceptance.json` directory where the path to your WP directory is `%WP_ROOT%`. This should like something like `%WP_ROOT%/wp-content`. This property is required when using environment instructions.
* `exclude` - WP Acceptance copys all the files in your repository into the snapshot for testing. There may be directories you want to exclude to speed things up e.g. `node_modules` and `vendor`. Should be relative to `wpacceptance.json`.
* `enforce_clean_db` - If set to `true`, a "clean" DB will be used for each test in the suite. "clean" means the untampered DB from the snapshot.
* `disable_clean_db` - Will force WP Snapshots to disable "clean" DB functionality. By default, a clean DB is created even if `enforce_clean_db` is false since there is a test method for refreshing the DB.
* `bootstrap` - Path to bootstrap file. This file will be executed before test execution begins. Should be relative to `wpacceptance.json`.
* `before_scripts` - An array of scripts to run in the same directory as `wpacceptance.json` before running tests.
* `repository` - You can optionally specify a WP Snapshots repository.

## Writing Tests

WP Acceptance tests are based on [PHPUnit](https://phpunit.de/). Here is a simple example of a test:

```php
class ExampleTest extends \WPAcceptance\PHPUnit\TestCase {

	/**
	 * Example test
	 */
	public function testExample() {
		$this->assertTrue( true );
	}
}
```

You can place tests in whatever files you choose (as long as `tests` points to the right place in `wpacceptance.json`). However, per PHPUnit defaults, your test code must be in a class (or classes across multiple files) with name(s) that end in `Test`. Inside your test class(es), each test method must begin with `test`.

All your tests must extend `\WPAcceptance\PHPUnit\TestCase`. `\WPAcceptance\PHPUnit\TestCase` extends `\PHPUnit\Framework\TestCase` so you will have access to all the standard PHPUnit methods e.g. `assertTrue`, `assertEquals`, etc.

Along with standard PHPUnit functionality, you have WP Acceptance special methods/functions/classes:

### Actor

The most poweful WP Acceptance functionality is provided by the `Actor` class and let's you interact as a Chrome browser user with your website.

A new Actor must be initialized for each test and is done like so:
```php
public function testExample() {
	$I = $this->openBrowserPage();
}
```

`openBrowserPage` does take an optional array of arguments. In particular, you can choose the browser size: `openBrowserPage( [ 'screen_size' => 'small' ] )`.

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

If using snapshots, the `wpsnapshots` user is always available as a super admin with password, `password`. If using environment instructions, the `admin` user is always available as a super admin with password, `password`.

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

WP Acceptance not only let's you test UI elements but how your web application interacts with the database as well.

We can assert that new posts (or other custom post types) were created:
```php
$last_id = self::getLastPostId( [ 'post_type' => 'post' ] );

// Interact with website....

self::assertNewPostsExist( $last_id, [ 'post_type' => 'post' ], 'No new post.' );
```

`self::assertNewPostsExist` checks for new database items after `$last_id`.

### Full API Documentation

To read about all WP Acceptance testing related methods, look at the [source code Actor class](https://github.com/10up/wpacceptance/blob/master/src/classes/PHPUnit/Actor.php).

### Examples

For detailed test examples, look at the [example test suite](https://github.com/10up/wpacceptance/tree/master/example/twentyseventeen).

## Commands

* __wpacceptance run__ [&lt;PATH TO wpacceptance.json DIRECTORY&gt;] [--local] [--snapshot_id=&lt;WPSNAPSHOT ID&gt;] [--enforce_clean_db] [--cache_environment] [--skip_environment_cache] [--db_host=&lt;DATABASE HOST&gt;] [--verbose] [--wp_directory=&lt;PATH TO WP DIRECTORY&gt;] [--save] [--force_save] [--filter_tests=&lt;TEST FILTER&gt;] [--filter_test_files=&lt;TEST FILE FILTER&gt;] [--repository=&lt;REPOSITORY&gt;] [--mysql_wait_time=&lt;MYSQL WAIT TIME&gt;] [--screenshot_on_failure] [--environment_id=&lt;ENVIRONMENT ID&gt;] --show_browser] [--slowmo=&lt;TIME IN MILLISECONDS&gt;] - Runs a test suite.
	* `<PATH TO wpacceptance.json DIRECTORY>` - Path to `wpacceptance.json`, defaults to current working directory.
	* `--local` - Runs your test suite against your local environment.
	 * `--verbose`, `-v`, `-vv`, `-vvv` - Run with various degrees of verbosity.
	* `--save` - If tests are successful, save snapshot ID to `wpacceptance.json` and push snapshot upstream.
	* `--force_save` - Save snapshot ID to `wpacceptance.json` and push snapshot upstream no matter what.
	* `--wp_directory` - Path to WordPress. If unset, will search up the directory tree until wp-config.php is found
	* `--snapshot_id` - Optionally run tests against a snapshot ID.
	* `--snapshot_name` - Optionally run tests against a named snapshot from the snapshots array.
	* `--enforce_clean_db` - Use clean database for each test.
	* `--cache_environment` - Keep the environment alive so it can be reused later. A cache environment can be used if the config being run is the exact same.
	* `--skip_environment_cache` - Ensures a fresh environment is used on each run even if a cached one exists. This also will prevent environment caching. This is useful if you are running WP Acceptance multiple times on the same server e.g. shared GitLab runner.
	* `--filter_tests` - Filter tests to run. Is analagous to PHPUnit --filter.
	* `--filter_test_files` - Comma separate test files to execute. If used all other test files will be ignored.
	* `--screenshot_on_failure` - Take a screenshot when a test fails or an error occurs. Screenshot will be placed in `screenshots/` directory from the current working directory.
	* `--repository` - WP Snapshots repository to use.
	* `--environment_id` - Manually specify environment ID. Useful for CI.
	* `--mysql_wait_time` - Set how long WP Acceptance should wait for MySQL to become available in seconds.
	* `--show_browser` - Show browser during tests. This is very useful for debugging failing tests.
	* `--slowmo` - Slow testing down so interactions can be more easily viewed in browser. Specify value in milliseconds.

* __wpacceptance init__ [--path] - Initialize a new test suite.
	* `--path` - Optional path to init directory.

* __wpacceptance destroy__  &ltenvironment_id&gt; [--all] - Stop and destroy running WP Acceptance environment(s)
	* `<environment_id>` - ID of environment to destroy.
	* `[--all]` - Destroy all WP Acceptance environments.

## Speed of Testing

Unfortunately, good test suites can take awhile to run. WP Acceptance has to do a lot of work in order to setup an environment for testing. Here are some tips for getting your test suite to run faster:

* If using snapshots, keep them as small as possible. If your snapshot database is 1GB that means WP Acceptance will have to execute a massive SQL file.
* Utilize environment caching on your local machine. When you run WP Acceptance, use the `--cache_environment` flag. This will force WP Acceptance to reuse the same environment as long as the suite configuration hasn't changed.
* In your suite configuration, exclude unnecessary files and directories such as `node_modules` and `vendor`.
* Don't require a clean database for each test. Set `disable_clean_db` to false in your suite configuration.
* Remember, using `--local` will force tests to take much longer.

## Local Test Development

Here are some tips for writing tests locally:

* Optimize your test suite for speed as much as possible. See [Speed of Testing](#speed-of-testing).
* Always use `--cache_environment`. Similarly, `--local` should be used sparingly as a new snapshot will need to be created each time.
* If Docker starts running slowly or you get weird errors. stop and remove all containers: `docker stop $(docker ps -a -q) && docker rm $(docker ps -a -q)`. Then run a system prune: `docker system prune`. If this doesn't fix things, restart Docker. Worst case scenario you made need to prune volumes. Beware pruning volumes will delete all WP Local Docker environment databases you have.
* If you run into browser/Puppeteer interaction errors, run your tests with the `--show_browser` flag to see what's happening.
* Most Puppeteer errors happen because an element is covered by another element making it unclickable or the page is still loading. If you are dealing with fading elements, a simple PHP sleep, `usleep( 500 );`, works great.

## Continuous Integration

WP Acceptance is a great addition to your CI process.

### Travis CI

Here are examples of `.travis.yml` that includes WP Acceptance:

Here is `run-wpacceptance.sh` which will retry WP Acceptance up to 3 times if environment errors occur:
```bash
for i in 1 2 3; do
	./vendor/bin/wpacceptance run

	EXIT_CODE=$?

	if [ $EXIT_CODE -gt 1 ]; then
		echo "Retrying..."
		sleep 3
	else
		break
	fi
done

exit $EXIT_CODE
```

### Travis with Environment Instructions

```yaml
language: php
php:
  - 7.2
env:
  global:
  - WP_VERSION=master
  - WP_VERSION=4.7
before_script:
  - composer install
script:
  - bash run-wpacceptance.sh; fi
sudo: required
services: docker
```

### Travis with Snapshots

```yaml
language: php
php:
  - 7.2
before_script:
  - composer install
script:
  - if [ -n "$AWS_ACCESS_KEY" ]; then ./vendor/bin/wpsnapshots configure 10up --aws_key=$AWS_ACCESS_KEY --aws_secret=$SECRET_ACCESS_KEY --user_name=Travis --user_email=travis@10up.com; fi
  - if [ -n "$AWS_ACCESS_KEY" ]; then bash run-wpacceptance.sh; fi
sudo: required
services: docker
```

Make sure you replace `REPO_NAME` with your WP Snapshots repository name. You will also need to define `AWS_ACCESS_KEY` and `SECRET_ACCESS_KEY` as hidden Travis environmental variables in your Travis project settings.

### GitLab

WP Acceptance works well with GitLab as well. The only difference is, if using snapshots, when running `wpsnapshots configure`, you need to prefix the command with an environmental variable `WPSNAPSHOTS_DIR`: `WPSNAPSHOTS_DIR=/builds/${CI_PROJECT_NAMESPACE}/.wpsnapshots/ wpsnapshots configure`.

## Snapshots vs. Environment Instructions

Snapshots and environmental instructions are two different tools for creating shareable environments that empower team members and/or CI processes to test consistently against the same environment. Read more about the two workflows [above](#workflow-environment-instructions-and-snapshots). Here are some reasons to use one workflow over the other:

* If you don't have access to AWS or don't want to deal with the complexities of AWS, use environment instructions.
* If you need non-authenticated, public contributors to run tests against your environment, use environment instructions.
* If you are building a website for a client and the code isn't public, use snapshots.
* If your environment requires highly specific data e.g. menus, options, certain post set, etc, use snapshots.
