* __wpassure run__ [&lt;PATH TO wpassure.json DIRECTORY&gt;] [--local] [--snapshot_id=&lt;WPSNAPSHOT ID&gt;] [--test_clean_db] [--preserve_containers] [--db_host=&lt;DATABASE HOST&gt;] [--verbose] [--wp_directory=&lt;PATH TO WP DIRECTORY&gt;] [--save] [--force_save] [--filter_tests=&lt;TEST FILTER&gt;] [--filter_test_files=<TEST FILE FILTER>]
  Runs a test suite.

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
  
* __wpassure init__ [--path]
  Initialize a new test suite.
  
  * `--path` - Optional path to init direftory.



