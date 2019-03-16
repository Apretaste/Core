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

// process arguments for the current task and action
$arguments = [];
$arguments['task'] = $argv[1];
$arguments['action'] = "main";
$arguments['params'] = array_slice($argv, 2);

// load the task selected
try {
	$console->handle($arguments);
} catch (\Phalcon\Exception $e) {
	echo $e->getMessage();
	exit(255);
}
