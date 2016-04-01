<?php

use Phalcon\Di\FactoryDefault\Cli as CliDI;
use Phalcon\Cli\Console as ConsoleApp;
use Phalcon\Config\Adapter\Ini as ConfigIni;

// set the date to come in Spanish
setlocale(LC_TIME, "es_ES");

define('VERSION', '1.0.0');

// Using the CLI factory default services container
$di = new CliDI();

// Define path to application directory
defined('APPLICATION_PATH') || define('APPLICATION_PATH', realpath(dirname(__FILE__)));

// include composer
include_once APPLICATION_PATH . "/vendor/autoload.php";

// Register the autoloader and tell it to register the tasks directory
$loader = new \Phalcon\Loader();
$loader->registerDirs(
	array(
		APPLICATION_PATH . '/tasks',
		APPLICATION_PATH . '/classes/'
	)
);
$loader->register();

// Creating the global path to the root folder
$di->set('path', function () {
	return array(
		"root" => APPLICATION_PATH,
		"http" => "https://apretaste.com"
	);
});

// Making the config global
$di->set('config', function (){
	return new ConfigIni(APPLICATION_PATH . '/configs/config.ini');
});

// Setup the database service
$config = $di->get('config');
$di->set('db', function () use($config) {
	return new \Phalcon\Db\Adapter\Pdo\Mysql(
		array(
			"host" => $config['database']['host'],
			"username" => $config['database']['user'],
			"password" => $config['database']['password'],
			"dbname" => $config['database']['database']
		));
});

// get the environment
$di->set('environment', function() use ($config) {
	if (isset($config['global']['environment'])) return $config['global']['environment'];
	else return "production";
});

// Create a console application
$console = new ConsoleApp();
$console->setDI($di);

// Process the console arguments
$arguments = array();
foreach ($argv as $k => $arg)
{
	if ($k == 1) $arguments['task'] = $arg;
	if ($k == 2) $arguments['action'] = $arg;
	if ($k >= 3) $arguments['params'][] = $arg;
}

// Define global constants for the current task and action
define('CURRENT_TASK',   (isset($argv[1]) ? $argv[1] : null));
define('CURRENT_ACTION', (isset($argv[2]) ? $argv[2] : null));

// Load the task selected 
try
{
	$console->handle($arguments);
}
catch (\Phalcon\Exception $e)
{
	echo $e->getMessage();
	exit(255);
}