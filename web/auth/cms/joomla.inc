<?php

global $auth;

define('_JEXEC', 1);
define('JPATH_BASE', realpath(MRBS_ROOT . DS . $auth['joomla']['rel_path']));
 
require_once JPATH_BASE . DS . 'includes' . DS . 'defines.php';
require_once JPATH_BASE . DS . 'includes' . DS . 'framework.php';

