<?php 

class Render {
	/**
	 * Render the template and return the HTML content
	 *
	 * @author salvipascual
	 * @param String $serviceName, name of the service
	 * @param Response $response, response object to render
	 * @throw Exception
	 */
	public function renderHTML($serviceName, $response) {
		// get the path
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];

		// creating and configuring a new Smarty object
		$smarty = new Smarty;
		$smarty->setTemplateDir("$wwwroot/app/layouts/");
		$smarty->setCompileDir("$wwwroot/temp/templates_c/");
		$smarty->setCacheDir("$wwwroot/temp/cache/");

		// disabling cache and debugging
		$smarty->force_compile = true;
		$smarty->debugging = false;
		$smarty->caching = false;		

		// list the system variables
		$utils = new Utils();
		$systemVariables = array(
			"_USER_TEMPLATE" => "$wwwroot/services/$serviceName/templates/{$response->template}",
			"_SERVICE_NAME" => strtoupper($serviceName),
			"_SERVICE_EMAIL" => $utils->getValidEmailAddress(),
			"_SERVICE_RELATED" => $this->getServicesRelatedArray($serviceName),
			"_CURRENT_YEAR" => date("Y"),
			"_SERVICE_SUPPORT_EMAIL" => $di->get("config")["contact"]["support"],
			"hr" => '<hr style="border:1px solid #D0D0D0; margin:0px;"/>',
			"separatorLinks" => '<span class="separador-links" style="color: #A03E3B;">&nbsp;|&nbsp;</span>',
			"space10" => '<div class="space_10">&nbsp;</div>',
			"space15" => '<div class="space_15" style="margin-bottom: 15px;">&nbsp;</div>',
			"space30" => '<div class="space_30" style="margin-bottom: 30px;">&nbsp;</div>',
		);

		// merge all variable sets and assign them to Smarty
		$templateVariables = array_merge($systemVariables, $response->content);
		$smarty->assign($templateVariables);

		// renderig and removing tabs, double spaces and break lines
		$renderedTemplate = $smarty->fetch("email_default.tpl");
		return preg_replace('/\s+/S', " ", $renderedTemplate);
	}

	/**
	 * Read the response and return the JSON content
	 *
	 * @author salvipascual
	 * @param Response $response, response object to render
	 * @throw Exception
	 */
	public function renderJSON($response){
		return json_encode($response->content);
	}

	/**
	 * Get three services related and return an array with them
	 * 
	 * @author salvipascual
	 * @param String $serviceName, name of the service
	 * @return Array
	 */
	private function getServicesRelatedArray($serviceName){
		// get last 3 services inserted with the same category
		$query = "SELECT name FROM service 
			WHERE category = (SELECT category FROM service WHERE name='$serviceName')
			AND name <> '$serviceName'
			ORDER BY insertion_date
			LIMIT 3";
		$connection = new Connection();
		$result = $connection->deepQuery($query);

		// create returning array
		$servicesRelates = array();
		foreach($result as $res) $servicesRelates[] = $res->name;

		// return the array
		return $servicesRelates;
	}
}
