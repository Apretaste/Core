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

try
{
	//Read configuration
	$config = new ConfigIni('../configs/config.ini');

	//Register autoLoader for Analytics
	$loaderAnalytics = new Loader();
	$loaderAnalytics->registerDirs(array(
		'../classes/',
		'../app/controllers/'
	))->register();

	//Create Run DI
	$di = new FactoryDefault();

	// Setup the view component for Analytics
	$di->set('view', function () {
		$analyticsViews = new View();
		$analyticsViews->setViewsDir('../app/views/');
		$analyticsViews->setLayoutsDir('../app/layouts/');
		return $analyticsViews;
	});

	// Handle the request
	$application = new Application($di);

	echo $application->handle()->getContent();
}
catch(\Exception $e)
{
	echo "PhalconException: ", $e->getMessage();	
}
