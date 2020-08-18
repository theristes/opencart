<?php
// Site
$_['site_url']          = HTTP_SERVER;

// Database
$_['db_autostart']      = true;
$_['db_engine']         = DB_DRIVER; // mysqli, pdo or pgsql
$_['db_hostname']       = DB_HOSTNAME;
$_['db_username']       = DB_USERNAME;
$_['db_password']       = DB_PASSWORD;
$_['db_database']       = DB_DATABASE;
$_['db_port']           = DB_PORT;

// Session
$_['session_autostart'] = false;
$_['session_engine']    = 'db'; // db or file

// Error
$_['error_display']     = true;

// Actions
$_['action_pre_action'] = array(
	'startup/startup',
	'startup/error',
	'startup/event',
	'startup/sass',
	'startup/login',
	'startup/permission'
);

// Actions
$_['action_default']    = 'common/dashboard';

// Action Events
$_['action_event']      = array(
	'controller/*/before' => array(
		'event/language/before',
		'event/extension/controller'
	),
	'controller/*/after' => array(
		'event/language/after'
	),
	//'model/*/before' => array(
	//	'event/extension/model'
	//),
	//'view/*/before' => array(
	//	'event/extension/view'
	//),
	'view/*/before' => array(
		999 => 'event/language'
	),
	//'model/*/after' => array(
	//	'event/debug/before'
	//),
	//'model/*/after'  => array(
	//	'event/debug/after'
	//)
);
