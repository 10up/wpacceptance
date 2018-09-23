# Install

WP Assure is easiest to use as a global Composer package. Assuming you have Composer/MySQL installed and SSH keys setup within GitHub/10up organiziation, do the following:

Install WP Assure as a dependency on your project
  ```
  composer require 10up/wpassure:dev-master --dev
  ```
If global Composer scripts are not in your path, add them:

```
export PATH=~/.composer/vendor/bin:$PATH
```

If you are using VVV, add global Composer scripts to your path with this command:

```
export PATH=~/.config/composer/vendor/bin:$PATH
```
