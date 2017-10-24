<?php

class Render
{
	/**
	 * Render the template and return the HTML content
	 *
	 * @author salvipascual
	 * @author kuma
	 * @param Service $service, service to be rendered
	 * @param Response $response, response object to render
	 * @return String, template in HTML
	 * @throw Exception
	 */
	public function renderHTML($service, Response $response)
	{
		// if the response includes json, don't render HTML
		// this is used mainly to build email APIs
		if($response->json) return $response->json;

		// set the email of the response if empty
		if(empty($response->email)) $response->email = $service->request->email;

		// get the path
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];

		// select the right file to load
		if($response->internal) $userTemplateFile = "$wwwroot/app/templates/{$response->template}";
		else $userTemplateFile = "{$service->pathToService}/templates/{$response->template}";

		// if we are rendering the whole HTML
		if ($response->html)
		{
			$tempTemp = "$wwwroot/temp/layout-".uniqid().".tpl";
			file_put_contents($tempTemp, $response->html);
			$response->layout = $tempTemp;
		}

		// get the internal layout if exit
		$internalLayoutPath = "{$service->pathToService}/layouts/{$response->layout}";
		if(file_exists($internalLayoutPath)) $response->layout = $internalLayoutPath;

		// creating and configuring a new Smarty object
		$smarty = new Smarty;
		$smarty->addPluginsDir("$wwwroot/app/plugins/");
		$smarty->setTemplateDir("$wwwroot/app/layouts/");
		$smarty->setCompileDir("/tmp/"); // "$wwwroot/temp/templates_c/"
		$smarty->setCacheDir("$wwwroot/temp/cache/");
		$smarty->setConfigDir("$wwwroot/configs/");

		// disabling cache and debugging
		$smarty->force_compile = true;
		$smarty->debugging = false;
		$smarty->caching = false;

		// get the person
		$utils = new Utils();
		$person = $utils->getPerson($response->email);
		$username = isset($person->username) ? "@{$person->username}" : "";

		// get a valid email address
		$validEmailAddress = $utils->getValidEmailAddress($username);

		// list the system variables
		$systemVariables = array(
			// system variables
			"WWWROOT" => $wwwroot,
			"APRETASTE_ENVIRONMENT" => $di->get('environment'),
			// template variables
			"APRETASTE_USER_TEMPLATE" => $userTemplateFile,
			"APRETASTE_SERVICE_NAME" => strtoupper($service->serviceName),
			"APRETASTE_SERVICE_RELATED" => $this->getServicesRelatedArray($service->serviceName),
			"APRETASTE_SERVICE_CREATOR" => $service->creatorEmail,
			"APRETASTE_EMAIL" => $validEmailAddress,
			"APRETASTE_EMAIL_LIST" => isset($person->mail_list) ? $person->mail_list==1 : 0,
			"APRETASTE_SUPPORT_EMAIL" => $utils->getSupportEmailAddress(),
			// user variables
			"num_notifications" => $utils->getNumberOfNotifications($response->email),
			'USER_USERNAME' => $username,
			'USER_NAME' => isset($person->first_name) ? $person->first_name : (isset($person->username) ? "@{$person->username}" : ""),
			'USER_FULL_NAME' => isset($person->full_name) ? $person->full_name : "",
			'USER_EMAIL' => isset($person->email) ? $person->email : "",
			'USER_MAILBOX' => $utils->getUserPersonalAddress($response->email),
			'CURRENT_USER' => isset($person->email) ? $person : false
		);

		// merge all variable sets and assign them to Smarty
		$templateVariables = array_merge($systemVariables, $response->content);
		$smarty->assign($templateVariables);

		// rendering and removing tabs, double spaces and break lines
		$renderedTemplate = $smarty->fetch($response->layout);
		$renderedTemplate = str_replace("{APRETASTE_EMAIL}", $validEmailAddress, $renderedTemplate);
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
		$query = "
			SELECT name FROM service
			WHERE category = (SELECT category FROM service WHERE name='$serviceName')
			AND name <> '$serviceName'
			AND name <> 'excluyeme'
			AND listed = 1
			ORDER BY RAND()
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
