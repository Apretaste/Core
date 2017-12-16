<?php

class Render
{
	/**
	 * Based on a subject process a request and return the Response
	 *
	 * @author salvipascual
	 * @param String $email
	 * @param String $subject
	 * @param String $body
	 * @param String[] $attachments
	 * @return [Service, Response[]]
	 */
	public static function runRequest($email, $subject, $body, $attachments)
	{
		// sanitize subject and body to avoid mysql injections
		$utils = new Utils();
		$subject = $utils->sanitize($subject);
		$body = $utils->sanitize($body);

		// get the name of the service or alias based on the subject line
		$subjectPieces = explode(" ", $subject);
		$serviceName = strtolower($subjectPieces[0]);
		unset($subjectPieces[0]);

		// get the service name, or use default service if the service does not exist
		$serviceName = $utils->serviceExist($serviceName);
		if( ! $serviceName) $serviceName = "ayuda";

		// include the service code
		$pathToService = $utils->getPathToService($serviceName);
		include_once "$pathToService/service.php";

		// get the subservice
		$subServiceName = "";
		if(isset($subjectPieces[1]) && ! preg_match('/\?|\(|\)|\\\|\/|\.|\$|\^|\{|\}|\||\!/', $subjectPieces[1])){
			$serviceClassMethods = get_class_methods($serviceName);
			$tempSubService = trim($subjectPieces[1]);
			if(preg_grep("/^_$tempSubService$/i", $serviceClassMethods)){
				$subServiceName = strtolower($tempSubService);
				unset($subjectPieces[1]);
			}
		}

		// get the service query
		$query = trim(implode(" ", $subjectPieces));
		$query = substr($query, 0, 1024);

		// get the language of the user
		$connection = new Connection();
		$result = $connection->query("SELECT username, lang FROM person WHERE email = '$email'");
		$lang = isset($result[0]->lang) ? $result[0]->lang : "es";
		$username = isset($result[0]->username) ? $result[0]->username : "";

		// create a new Request object
		$request = new Request();
		$request->email = $email;
		$request->username = $username;
		$request->subject = $subject;
		$request->body = $body;
		$request->attachments = $attachments;
		$request->service = $serviceName;
		$request->subservice = trim($subServiceName);
		$request->query = $query;
		$request->lang = $lang;

		// create a new Service Object with info from the database
		$result = $connection->query("SELECT * FROM service WHERE name = '$serviceName'");
		$service = new $serviceName();
		$service->serviceName = $serviceName;

		if (isset($result[0]))
		{
			$service->serviceDescription = $result[0]->description;
			$service->creatorEmail = $result[0]->creator_email;
			$service->serviceCategory = $result[0]->category;
			$service->insertionDate = $result[0]->insertion_date;
		} else {
			$service->serviceDescription = '';
			$service->creatorEmail = 'soporte@apretaste.com';
			$service->serviceCategory = 'service';
			$service->insertionDate = date('Y-m-d');
		}

		$service->pathToService = $pathToService;
		$service->utils = $utils;
		$service->request = $request;

		// run the service and get the Response
		$subserviceFunction = "_$subServiceName";

		if(empty($subServiceName) || ! method_exists($service, $subserviceFunction) ) $response = $service->_main($request);
		else $response = $service->$subserviceFunction($request);

		// get only the first response
		// @TODO remove when services send only one response
		if(is_array($response)) $response = $response[0];

		// create and return the response
		$return = new stdClass();
		$return->service = $service;
		$return->response = $response;
		return $return;
	}

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
	public static function renderHTML($service, Response $response)
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
			"APRETASTE_SERVICE_CREATOR" => $service->creatorEmail,
			"APRETASTE_EMAIL" => $validEmailAddress,
			"APRETASTE_EMAIL_LIST" => isset($person->mail_list) ? $person->mail_list==1 : 0,
			"APRETASTE_SUPPORT_EMAIL" => $utils->getSupportEmailAddress(),
			// app only variables
			"APP_VERSION" => floatval($service->appversion),
			"APP_LATEST_VERSION" => floatval($di->get('config')['global']['appversion']),
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

		// render the template
		$rendered = $smarty->fetch($response->layout);

		// add link popups for the web
		if($di->get('environment') == "web") {
			$linkPopup = file_get_contents("$wwwroot/app/layouts/web_link_popup.phtml");
			$rendered = str_replace("</body>", "$linkPopup</body>", $rendered);
			$rendered = str_replace('{$APRETASTE_SERVICE_NAME}', strtolower($service->serviceName), $rendered);
		}

		// remove tabs, double spaces and break lines
		return preg_replace('/\s+/S', " ", $rendered);
	}

	/**
	 * Read the response and return the JSON content
	 *
	 * @author salvipascual
	 * @param Response $response, response object to render
	 * @throw Exception
	 */
	public static function renderJSON($response)
	{
		if(empty($response->json)) return json_encode($response->content, JSON_PRETTY_PRINT);
		else return $response->json;
	}
}
