<?php
define('VERSION', '4.1.0.1');

require_once('config.php');


echo 'DB_DATABASE: ' . DB_DATABASE . '<br>';
echo 'DB_HOSTNAME: ' . DB_HOSTNAME . '<br>';
exit;


require_once(DIR_SYSTEM . 'startup.php');

require_once(DIR_SYSTEM . 'framework.php');
