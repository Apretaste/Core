<?php

use Phalcon\Loader;
use Phalcon\Mvc\View;
use Phalcon\Mvc\Application;
use Phalcon\DI\FactoryDefault;
use Phalcon\Mvc\Url as UrlProvider;
use Phalcon\Config\Adapter\Ini as ConfigIni;
use Phalcon\Session\Adapter\Files as Session;

// set the date to come in Spanish
setlocale(LC_TIME, "es_ES");

// include composer
include_once "../vendor/autoload.php";

// set the memory to be used by php
ini_set('memory_limit', '1024M');

/* @TODO antes de poner este code hay que probarlo con cada uno de los servicios
// handle php errors as exceptions
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
} set_error_handler("exception_error_handler");
 */

try
{
	//Register autoLoader for Analytics
	$loaderAnalytics = new Loader();
	$loaderAnalytics->registerDirs(array(
		'../classes/',
		'../app/controllers/'
	))->register();

	//Create Run DI
	$di = new FactoryDefault();

	// Creating the global path to the root folder
	$di->set('path', function () {
		$protocol = empty($_SERVER['HTTPS']) ? "http" : "https";
		return array(
			"root" => dirname(__DIR__),
			"http" => "$protocol://{$_SERVER['HTTP_HOST']}"
		);
	});

	// Making the config global
	$di->set('config', function () {
		return new ConfigIni('../configs/config.ini');
	});

	// starts a new session
	$di->setShared('session', function () {
		$session = new Session();
		$session->start();
		return $session;
	});

	// Setup the view component for Analytics
	$di->set('view', function () {
		$view = new View();
		$view->setLayoutsDir('../layouts/');
		$view->setViewsDir('../app/views/');
		return $view;
	});

	// Setup the database service
	$config = $di->get('config');
	$di->set('db', new \Phalcon\Db\Adapter\Pdo\Mysql([
		"host" => $config['database']['host'],
		"username" => $config['database']['user'],
		"password" => $config['database']['password'],
		"dbname"   => $config['database']['database']
	]));

	// Set the tier (sandbox | stage | production)
	$di->set('tier', function () use ($config) {
		if(isset($config['global']['tier'])) return $config['global']['tier'];
		else return "production";
	});

	// Set the environment (app | api | email | web | default)
	$di->set('environment', function () use ($config) {
		return "default";
	});

	// Set the routes
	$di->set('router', function () {
		require __DIR__ . '/../configs/routes.php';
		return $router;
	});

	// Handle the request
	$application = new Application($di);
	echo $application->handle()->getContent();
}
catch(Exception $e)
{
	$message = $e->getMessage();
	$utils = new Utils();

	// we assume is traying to access a service
	if ($e instanceof Phalcon\Mvc\Dispatcher\Exception)
	{
		// get the service name from the error message
		$service = strtolower(substr($message, 0, strpos($message, "Controller")));

		// check if the service or alias exists
		$service = $utils->serviceExist($service);
		if(empty($service)) $service = "servicios";

		// redirect to the service or to the services page
		header("Location:/run/display?subject=$service"); exit;
	}

	// log error
	$utils->createAlert(Debug::getReadableException($e), 'ERROR');

	// show 404 page
	header('HTTP/1.0 404 Not Found');
	echo "<h1>Error 404</h1><p>We apologize, but this page was not found.</p>";
}
