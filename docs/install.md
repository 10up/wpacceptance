# Install

WP Assure is easiest to use as a project-level Composer package:

Since WP Assure is in beta, you will need to set your project minimum stability to `dev`:
```
composer config minimum-stability dev
```

Next, require the WP Assure package:
```
composer require 10up/wpassure:dev-master --dev
```

Finally, verify and run WP Assure by calling the script in the Composer bin directory:
```
./vendor/bin/wpassure
```
