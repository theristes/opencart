<?php
define('VERSION', '4.1.0.1');

if (is_file('config.php')) {
	require_once('config.php');
}

require_once(DIR_SYSTEM . 'startup.php');

require_once(DIR_SYSTEM . 'framework.php');
