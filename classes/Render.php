<?php

class Render
{
	/**
	 * Render the template and return the HTML content
	 *
	 * @author salvipascual
	 * @param Service $service, service to be rendered
	 * @param Response $response, response object to render
	 * @return String, template in HTML
	 * @throw Exception
	 */
	public function renderHTML($service, $response)
	{
		// if the response includes json, don't render HTML
		// this is used mainly to build email APIs
		if( ! empty($response->json)) return $response->json;

		// get the path
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];

		// select the right file to load
		if($response->internal) $userTemplateFile = "$wwwroot/app/templates/{$response->template}";
		else $userTemplateFile = "$wwwroot/services/{$service->serviceName}/templates/{$response->template}";

		// creating and configuring a new Smarty object
		$smarty = new Smarty;
		$smarty->addPluginsDir("$wwwroot/app/plugins/");
		$smarty->setTemplateDir("$wwwroot/app/layouts/");
		$smarty->setCompileDir("$wwwroot/temp/templates_c/");
		$smarty->setCacheDir("$wwwroot/temp/cache/");
		$smarty->setConfigDir("$wwwroot/configs/");

		// disabling cache and debugging
		$smarty->force_compile = true;
		$smarty->debugging = false;
		$smarty->caching = false;

		// getting the ads
		$ads = $service->showAds ? $response->getAds() : array();

		// get the status of the mail list
		$connection = new Connection();
		$status = $connection->deepQuery("SELECT mail_list FROM person WHERE email='{$response->email}'");
		$onEmailList = empty($status) ? false : $status[0]->mail_list == 1;

		// list the system variables
		$systemVariables = array(
			"APRETASTE_USER_TEMPLATE" => $userTemplateFile,
			"APRETASTE_SERVICE_NAME" => strtoupper($service->serviceName),
			"APRETASTE_SERVICE_RELATED" => $this->getServicesRelatedArray($service->serviceName),
			"APRETASTE_SERVICE_CREATOR" => $service->creatorEmail,
			"APRETASTE_ADS" => $ads,
			"APRETASTE_EMAIL_LIST" => $onEmailList,
			"WWWROOT" => $wwwroot
		);

		// merge all variable sets and assign them to Smarty
		$templateVariables = array_merge($systemVariables, $response->content);
		$smarty->assign($templateVariables);

		// renderig and removing tabs, double spaces and break lines
		$renderedTemplate = $smarty->fetch($response->layout);
		return preg_replace('/\s+/S', " ", $renderedTemplate);
	}

	/**
	 * Read the response and return the JSON content
	 *
	 * @author salvipascual
	 * @param Response $response, response object to render
	 * @throw Exception
	 */
	public function renderJSON($response)
	{
		if(empty($response->json)) return json_encode($response->content, JSON_PRETTY_PRINT);
		else return $response->json;
	}

	/**
	 * Get up to five services related and return an array with them
	 *
	 * @author salvipascual
	 * @param String $serviceName, name of the service
	 * @return Array
	 */
	private function getServicesRelatedArray($serviceName)
	{
		// harcoded return for the sandbox
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		if($di->get('environment') == "sandbox") return array('ayuda','nota','tienda','traducir','publicidad');

		// get last 5 services inserted with the same category
		$query = "SELECT name FROM service
			WHERE category = (SELECT category FROM service WHERE name='$serviceName')
			AND name <> '$serviceName'
			AND name <> 'excluyeme'
			AND listed = 1
			ORDER BY insertion_date
			LIMIT 5";
		$connection = new Connection();
		$result = $connection->deepQuery($query);

		// create returning array
		$servicesRelates = array();
		foreach($result as $res) $servicesRelates[] = $res->name;

		// return the array
		return $servicesRelates;
	}
}
