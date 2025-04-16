<?php
define('VERSION', '4.1.0.1');

require_once('config.php');

require_once(DIR_SYSTEM . 'startup.php');


echo 'DB_DATABA_SE: ' . DB_DATABASE . '<br>';
echo 'DB_HOSTNAME: ' . DB_HOSTNAME . '<br>';
exit;

require_once(DIR_SYSTEM . 'framework.php');
