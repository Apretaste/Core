<?php
  /**************************************
   ** Apretaste's Bootstrap			   **
   ** Author: hcarras				   **
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
	  $config = new ConfigIni(APP_PATH . 'private/config.ini');
	  
	  
	  //Register autoLoader for Analytics
	  $loaderAnalytics = new Loader();
      $loaderAnalytics->registerDirs(array(
        '../packages/analytic/controlles/',
        '../packages/analytic/models/'
      ))->register();
	
	  //Create Run DI
	  $di = new FactoryDefault();
	  
	  // Setup a base URI so that all generated URIs include the "Core" folder
	  $di->set('url', function () {
		$url = new UrlProvider();
		$url->setBaseUri('/Core/');
		return $url;
	  });

	  // Setup the view component for Analytics
	  $di->set('view', function () {
        $analyticsViews = new View();
        $analyticsViews->setViewsDir('../packages/analytic/views/');
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
?>