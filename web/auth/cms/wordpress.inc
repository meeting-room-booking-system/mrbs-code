<?php

global $theme, $auth;

define('WP_USE_THEMES', false);

// WordPress unsets the $theme variable so we need to save it and restore it afterwards.
$mrbs_theme = $theme;
require_once MRBS_ROOT . '/'. $auth['wordpress']['rel_path'] . '/wp-load.php';
$theme = $mrbs_theme;
