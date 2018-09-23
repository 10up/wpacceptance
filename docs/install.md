# Install

WP Assure is easiest to use as a global Composer package. Assuming you have Composer/MySQL installed and SSH keys setup within GitHub/10up organiziation, do the following:

1. Add the 10up/wpassure repository as a global Composer repository:
  ```
  composer global config repositories.wpsnapshots vcs https://github.com/10up/wpassure
  ```
2. Install WP Assure as a global Composer package:
  ```
  composer global require 10up/wpassure:dev-master -n
  ```
If global Composer scripts are not in your path, add them:

```
export PATH=~/.composer/vendor/bin:$PATH
```

If you are using VVV, add global Composer scripts to your path with this command:

```
export PATH=~/.config/composer/vendor/bin:$PATH
```
