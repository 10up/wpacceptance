# WP Assure

WP Assure is a toolkit that empowers developers and CI pipelines to test codebases using version controlled acceptance tests and sharable, defined file and database snapshots.

## How It Works

There are many acceptance tests frameworks out there. They all have one major flaw - everyone executing the acceptance tests must be running the exact same code on the exact same database and environment to guarantee the same results. Ensuring a team of developers (and a CI pipeline) are all using the same database in the same environment has been nearly impossible until now. WP Assure is unique in that it allows you to run your acceptance tests against a codebase in a defined, distributable file and database [snapshot](https://github.com/10up/wpsnapshots).

To use WP Assure, you run the command `wpassure run`. The `wpassure run` command looks for a file named `wpassure.json` in the current working directory. `wpassure.json` looks like this:

```
{
    "name": "example-suite",
    "tests": [
        "tests\/*"
    ],
    "snapshot_id": "..."
}
```

`wpassure.json` must contain both `name` and `tests` properties in JSON format. `name` is the name of your test suite, and it must be unique. `tests` points to your test files. WP Assure tests are written in PHP and PHPUnit based.

*There are a few important rules for wpassure.json:*

* `wpassure.json` and the actual tests __must__ exist within the codebase you are testing.
* `wpassure.json` __must__ be located in the root of your version controlled codebase. Typically this means `wpassure.json` is in the root of a theme, plugin, `wp-content` directory.

## Commands

__wpassure run__ [--local] [--snapshot_id=<WPSNAPSHOT ID>] [--db_host=<DATABASE HOST>] [--verbose] [--wp_directory=<PATH TO WP DIRECTORY>] [--suite_config_directory=<PATH TO wpassure.json DIRECTORY>] [--save]

Run a WPAssure test suite. If you want to run on an existing WordPress installation, leave out `--snapshot_id`.

Example `wpassure.json`:

```
{
	"snapshot-id": "8sdoh2tsld223ttsd",
	"tests": "tests/js/*"
}
```
