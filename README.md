# WP Assure

WP Assure is a toolkit that empowers developers and CI pipelines to test codebases using version controlled acceptance tests and sharable, defined file and database snapshots.

## Requirements

* PHP 5.6+
* mysqli PHP extension
* [WP Snapshots](https://github.com/10up/wpsnapshots)
* Docker

[WP Local Docker](https://github.com/10up/wp-local-docker) is highly recommended as the local development environment but not required.

## How It Works

There are many acceptance tests frameworks out there. They all have one major flaw - everyone executing the acceptance tests must be running the exact same code on the exact same database and environment to guarantee the same results. Ensuring a team of developers (and a CI pipeline) are all using the same database in the same environment has been nearly impossible until now. WP Assure is unique in that it allows you to run your acceptance tests against a codebase in a defined, distributable file and database [snapshot](https://github.com/10up/wpsnapshots).

To use WP Assure, you run the command `wpassure run`. The `wpassure run` command looks for a file named `wpassure.json` in the current working directory. `wpassure.json` looks like this:

```
{
    "name": "example-suite",
    "tests": [
        "tests\/*"
    ],
    "snapshot_id": "..." /* Optional */
}
```

`wpassure.json` must contain both `name` and `tests` properties in JSON format. `name` is the name of your test suite, and it must be unique. `snapshot_id` is optional and is explained in __Workflow__ below. `tests` points to your test files. WP Assure tests are written in PHP and PHPUnit based.

*There are a few important rules for wpassure.json:*

* `wpassure.json` and the actual tests __must__ exist within the codebase you are testing.
* `wpassure.json` __must__ be located in the root of your version controlled codebase. Typically this means `wpassure.json` is in the root of a theme, plugin, `wp-content` directory.

### Workflow

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
