`wpassure.json` is required for WP Assure to work. Whenever a test suite is run via the `run` command, `wpassure.json` is processed.

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
	"bootstrap": "./bootstrap.php"
}
```

* `name` (required) - Name of test suite.
* `tests` (required) - This is an array of path(s) to tests. Each path in the array is processed via PHP `glob`. `*` will include every test file in the directory.
* `snapshot_id` - "Primary" snapshot to test again. If the `run` command is executed without the `--local` flag, this snapshot ID will be used.
* `exclude` - WP Assure copys all the files in your repository into the snapshot for testing. There may be directories you want to include to speed things up e.g. `node_modules` and `vendor`.
* `test_clean_db` - If set to `true`, a "clean" DB will be used for each test in the suite. "clean" means the untampered DB from the snapshot.
* `bootstrap` - Path to bootstrap file. This file will be executed before test execution begins.
