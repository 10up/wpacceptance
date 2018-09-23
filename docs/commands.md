# Commands

__wpassure run__ [<PATH TO wpassure.json DIRECTORY>] [--local] [--snapshot_id=<WPSNAPSHOT ID>] [--db_host=<DATABASE HOST>] [--verbose] [--wp_directory=<PATH TO WP DIRECTORY>] [--save]

Run a WPAssure test suite. If you want to run on an existing WordPress installation, leave out `--snapshot_id`.

Example `wpassure.json`:

```
{
	"snapshot-id": "8sdoh2tsld223ttsd",
	"tests": "tests/js/*"
}
```
