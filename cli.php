<?php

use Phalcon\Di\FactoryDefault\Cli as CliDI;
use Phalcon\Cli\Console as ConsoleApp;
use Phalcon\Config\Adapter\Ini as ConfigIni;

// set the date to come in Spanish
setlocale(LC_TIME, "es_ES");

// define path to application directory
defined('APPLICATION_PATH') || define('APPLICATION_PATH', realpath(dirname(__FILE__)));
define('VERSION', '1.0.0');

// include composer
include_once APPLICATION_PATH . "/vendor/autoload.php";

// register the autoloader and tell it to register the tasks directory
$loader = new \Phalcon\Loader();
$loader->registerDirs([
	APPLICATION_PATH . '/tasks',
	APPLICATION_PATH . '/classes/'
]);
$loader->register();

// making the config global
$di = new CliDI();
$di->set('config', function () {
	return new ConfigIni(APPLICATION_PATH . '/configs/config.ini');
});

// create a console application
$console = new ConsoleApp();
$console->setDI($di);

// process the console arguments
$arguments = [];
foreach ($argv as $k => $arg) {
	if ($k == 1) $arguments['task'] = $arg;
	if ($k == 2) $arguments['action'] = $arg;
	if ($k >= 3) $arguments['params'][] = $arg;
}

// define global constants for the current task and action
define('CURRENT_TASK',   (isset($argv[1]) ? $argv[1] : null));
define('CURRENT_ACTION', (isset($argv[2]) ? $argv[2] : null));

// load the task selected
try
{
	$console->handle($arguments);
}
catch (\Phalcon\Exception $e)
{
	echo $e->getMessage();
	exit(255);
}
