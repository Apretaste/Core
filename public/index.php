<?php

/**************************************
** Apretaste's Bootstrap			 **
** Author: hcarras					 **
***************************************/
	
use Phalcon\Loader;
use Phalcon\Mvc\View;
use Phalcon\Mvc\Application;
use Phalcon\DI\FactoryDefault;
use Phalcon\Mvc\Url as UrlProvider;
use Phalcon\Config\Adapter\Ini as ConfigIni;

// include composer
include_once "../vendor/autoload.php";

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

	// Making the config global
	$di->set('config', function () {
		return new ConfigIni('../configs/config.ini');;
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
	$di->set('db', function () use ($config) {
		return new \Phalcon\Db\Adapter\Pdo\Mysql(array(
			"host"     => $config['database']['host'],
			"username" => $config['database']['user'],
			"password" => $config['database']['password'],
			"dbname"   => $config['database']['database']
		));
	});

	// Handle the request
	$application = new Application($di);

	echo $application->handle()->getContent();
}
catch(\Exception $e)
{
	echo "PhalconException: ", $e->getMessage();	
}
