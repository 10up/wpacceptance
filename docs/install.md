# Install

WP Assure is easiest to use as a project-level Composer package:

1. Since WP Assure is in beta, you will need to set your project minimum stability to `dev`:
```
composer config minimum-stability dev
```

2. Next, require the WP Assure package:
```
composer require 10up/wpassure:dev-master --dev
```

3. Finally, verify and run WP Assure by calling the script in the Composer bin directory:
```
./vendor/bin/wpassure
```

After installation, you will want to [setup WP Assure on a project](https://wpassure.readthedocs.io/en/latest/project-setup/).
