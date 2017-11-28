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
		$renderedTemplate = $smarty->fetch($response->layout);

		// add link popups for the web
		if($di->get('environment') == "web") {
			$linkPopup = file_get_contents("$wwwroot/app/layouts/web_link_popup.phtml");
			$renderedTemplate = str_replace("</body>", "$linkPopup</body>", $renderedTemplate);
		}

		// remove tabs, double spaces and break lines
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
}
