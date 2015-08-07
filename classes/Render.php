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

		// select the right file to load
		if($response->internal) $templateFile = "$wwwroot/app/templates/{$response->template}";
		else $templateFile = "$wwwroot/services/$serviceName/templates/{$response->template}";

		// if there is a template name, load its code
		if(file_exists($templateFile)) $this->templateSource = file_get_contents($templateFile);
		else throw new Exception("Invalid template {$this->templateName}");

		// get the empty template from the layout
		$layoutFile = "$wwwroot/app/layouts/email_default.tpl";
		$template = file_get_contents($layoutFile);

		// list the system variables
		$utils = new Utils();
		$systemVariables = array(
			'_SERVICE_NAME' => strtoupper($serviceName),
			'_SERVICE_EMAIL' => $utils->getValidEmailAddress(),
			'_SERVICE_SUPPORT_EMAIL' => $di->get('config')['contact']['support'],
			'_USER_TEMPLATE' => $this->templateSource
		);

		// list the template elements
		$templateVariables = array(
			"hr" => '<hr style="border:1px solid #D0D0D0; margin:0px;"/>',
			"separatorLinks" => '<span class="separador-links" style="color: #A03E3B;">&nbsp;|&nbsp;</span>',
			"space10" => '<div class="space_10">&nbsp;</div>',
			"space15" => '<div class="space_15" style="margin-bottom: 15px;">&nbsp;</div>',
			"space30" => '<div class="space_30" style="margin-bottom: 30px;">&nbsp;</div>',
		);

		// merge all variable sets
		$allVariables = array_merge(
			$systemVariables, 
			$templateVariables, 
			$this->getServicesRelatedArray($serviceName), 
			$response->content);

		// replace user variables
		foreach ($allVariables as $key=>$value) {
			$template = str_replace('{$'.$key.'}', $value, $template);
		}

		// remove all tabs, double spaces and break lines
		return preg_replace('/\s+/S', " ", $template);
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
		for ($i=0; $i<count($result); $i++){
			$serviceNumber = $i+1;
			$servicesRelates["_SERVICE_RELATED_$serviceNumber"] = $result[$i]->name;
		}

		return $servicesRelates;
	}
}
