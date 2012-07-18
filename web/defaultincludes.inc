<?php

// $Id$

// We use require for some files rather than require_once because the values that
// are assigned to variables will change depending on the context in which the file
// is called.

require "grab_globals.inc.php";
require_once "systemdefaults.inc.php";
require_once "areadefaults.inc.php";
require_once "config.inc.php";
require_once "language.inc";
require_once "trailer.inc";
require_once "internalconfig.inc.php";
require_once "theme.inc";
require_once "functions.inc";
require_once "dbsys.inc";
require_once "mrbs_auth.inc";
require "standard_vars.inc.php";

?>