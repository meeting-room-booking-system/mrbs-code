# The sites directory

This directory is where site config files should be located when running in multisite mode.
Each site should have its subdirectory and within that should be placed its config file.
For example, if you have two sites "a" and "b", then the structure of this directory would be

- sites
  - a
    - config.inc.php
  - b
    - config.inc.php

The site's config.inc.php file overrides the global config.inc.php in the web directory, which in turn overrides systemdefaults.inc.php.
