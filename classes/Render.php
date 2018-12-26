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
	public static function runRequest($email, $subject, $body, $attachments, $input=false)
	{
		// sanitize subject and body to avoid mysql injections
		$subject = Utils::sanitize($subject);
		$body = Utils::sanitize($body);

		// get the name of the service or alias based on the subject line
		$subjectPieces = explode(" ", $subject);
		$serviceName = strtolower($subjectPieces[0]);
		unset($subjectPieces[0]);

		// get the service name, or use default service if the service does not exist
		$serviceName = Utils::serviceExist($serviceName);
		if( ! $serviceName) $serviceName = "servicios";

		// include the service code
		$pathToService = Utils::getPathToService($serviceName);
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

		// create the params array
		$params = explode("|", $query);
		$query = str_replace("|", " ", $query); // backward compatibility

		// get user details
		$result = Connection::query("SELECT id, username, lang FROM person WHERE email = '$email'");
		$userId = $result[0]->id;
		$lang = isset($result[0]->lang) ? $result[0]->lang : "es";
		$username = isset($result[0]->username) ? $result[0]->username : "";

		// get the current environment
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$environment = $di->get('environment');

		// create a new Request object
		$request = new Request();
		$request->userId = $userId;
		$request->email = $email;
		$request->username = $username;
		$request->subject = $subject;
		$request->body = $body;
		$request->attachments = $attachments;
		$request->service = $serviceName;
		$request->subservice = trim($subServiceName);
		$request->query = $query;
		$request->params = $params;
		$request->lang = $lang;
		$request->environment = $environment; // web|app|api
		$request->appversion = empty($input->appversion) ? 1 : floatval($input->appversion);
		$request->ostype = empty($input->ostype) ? "" : $input->ostype; // android|ios
		$request->apptype = empty($input->apptype) ? "original" : $input->apptype; // original|single
		$request->appmethod = empty($input->method) ? "email" : $input->method; // email|http

		// create a new Service Object with info from the database
		$result = Connection::query("SELECT * FROM service WHERE name = '$serviceName'");
		$service = new $serviceName();
		$service->serviceName = $serviceName;

		if (isset($result[0]))
		{
			$service->serviceDescription = $result[0]->description;
			$service->creatorEmail = $result[0]->creator_email;
			$service->serviceCategory = $result[0]->category;
			$service->insertionDate = $result[0]->insertion_date;
			$service->tpl_version = $result[0]->tpl_version;
		} else {
			$service->serviceDescription = '';
			$service->creatorEmail = 'soporte@apretaste.com';
			$service->serviceCategory = 'service';
			$service->insertionDate = date('Y-m-d');
			$service->tpl_version = 0;
		}

		$service->pathToService = $pathToService;
		$service->utils = new Utils();
		$service->social = new Social();
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
	 * @param Service $service, service to be rendered
	 * @param Response $response, response object to render
	 * @return String, template in HTML
	 * @throw Exception
	 */
	public static function renderHTML($service, Response &$response)
	{
		// if the response includes json, don't render HTML
		// this is used mainly to build email APIs
		if($response->json) return $response->json;

		// set the email of the response if empty
		if(empty($response->email)) $response->email = $service->request->email;

		// get the path
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$environment = $di->get('environment');
		$wwwroot = $di->get('path')['root'];

		// select the right file to load
		if($response->internal) $userTemplateFile = "$wwwroot/app/templates/{$response->template}";
		else $userTemplateFile = "{$service->pathToService}/templates/{$response->template}";

		// get the person
		$person = Utils::getPerson($response->email);
		$username = isset($person->username) ? "@{$person->username}" : "";

		// list the system variables
		$systemVariables = [
			// system variables
			"WWWROOT" => $wwwroot,
			"_ENVIRONMENT" => $environment, // app|web|api
			// template variables
			"RAW_TEMPLATE" => file_get_contents($userTemplateFile),
			"SERVICE_NAME" => strtolower($service->serviceName),
			"SERVICE_CREATOR" => $service->creatorEmail,
			"DELIVERY_EMAIL" => Utils::getValidEmailAddress(),
			"EMAIL_LIST" => isset($person->mail_list) ? $person->mail_list==1 : 0,
			"SUPPORT_EMAIL" => Utils::getSupportEmailAddress(),
			"JSON_DATA" => $response->content,
			"PLACE_TEMPLATE" => "<div id=\"tpl-output\"></div>",
			// app only variables
			"APP_VERSION" => empty($service->input->appversion) ? 1 : floatval($service->input->appversion),
			"APP_LATEST_VERSION" => floatval($di->get('config')['global']['appversion']),
			"APP_OS" => empty($service->input->ostype) ? "" : $service->input->ostype, // android|ios
			"APP_TYPE" => empty($service->input->apptype) ? "original" : $service->input->apptype, // original|single
			"APP_METHOD" => empty($service->input->method) ? "email" : $service->input->method, // email|http
			"APP_IMG_DIR" => $environment=="app"?"APP_IMG_DIR":"",
			// user variables
			"NUM_NOTIFICATIONS" => Utils::getNumberOfNotifications($person->id),
			"USER_USERNAME" => $username,
			"USER_NAME" => isset($person->first_name) ? $person->first_name : (isset($person->username) ? "@{$person->username}" : ""),
			"USER_FULL_NAME" => isset($person->full_name) ? $person->full_name : "",
			"USER_EMAIL" => isset($person->email) ? $person->email : "",
			// advertisement
			"TOP_AD" => self::getAd($service)
		];

		// render the template
		$layout = file_get_contents($response->layout);
		$systemVariables['RAW_TEMPLATE'] = str_replace('{$PLACE_TEMPLATE}', $systemVariables['RAW_TEMPLATE'], $layout);


		// add link popups for the web
		if($environment == "web") {
			// get page content
			$mainHTML = "<div id ='tpl-output' class='container'></div>";
			$mainHTML .= file_get_contents("$wwwroot/app/layouts/web_includes.phtml");

			// replace system variables
			foreach ($systemVariables as $key=>$value) {
				if(is_array($value)) continue;
				$mainHTML = str_replace('{$'.$key.'}', $value, $mainHTML);
			}

			// add at the end of the <body> of the page
			$rendered = $mainHTML;
		}

		// optimize images for the app
		self::optimizeImages($response, $rendered, $service);

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
		if(empty($response->json)) {
			$json = json_encode($response->content, JSON_PRETTY_PRINT);
			return str_replace("\/", "/", $json); // remove \ from the URLs
		} else return $response->json;
	}

	/**
	 * Optimize images of response and associated html
	 * @param $response
	 * @param $html
	 * @param $service
	 */
	private static function optimizeImages(&$response, &$html, $service)
	{
		// get the image quality
		$userId = $service->request->userId;
		$res = Connection::query("SELECT img_quality FROM person WHERE id=$userId");
		if(empty($res)) $quality = "ORIGINAL";
		else $quality = $res[0]->img_quality;

		// do not optmize for original quality
		if($quality == "ORIGINAL") return false;

		// get rid of images
		if($quality == "SIN_IMAGEN") $response->images = [];
		else
		{
			// get link to the http
			$di = \Phalcon\DI\FactoryDefault::getDefault();
			$wwwhttp = $di->get('path')['http'];

			// setup params for the app
			$format = $service->request->environment=='app' && $service->request->ostype=='android' ? 'webp' : 'jpg';
			$quality = $service->request->environment=='app' ? 15 : 50;

			// create thumbnails for images
			$images = [];
			if(is_array($response->images)) foreach ($response->images as $file)
			{
				if(file_exists($file))
				{
					// thumbnail the image or use thumbnail cache
					$thumbnail = Utils::getTempDir()."thumbnails/".pathinfo(basename($file), PATHINFO_FILENAME).".$format";

					// optimize image or use the optimized cache
					if( ! file_exists($thumbnail)) {
						Utils::optimizeImage($file, $thumbnail, $quality);
						if( ! file_exists($thumbnail)) {
							Utils::createAlert("[Render::optimizeImages] file cannot be optimized: $file");
							$thumbnail = $file;
						}
					}

					// use the image only if it can be compressed
					$better = (filesize($file) > filesize($thumbnail)) ? $thumbnail : $file;
					$images[] = $better;
					$original = basename($file);
					$better = basename($better);

					if (stripos($html, "'$wwwhttp/temp/$original'") !== false || stripos($html, "\"$wwwhttp/temp/$original\"") !== false) {
						$wwwroot = $di->get('path')['root'];
						@copy($thumbnail,"$wwwroot/public/temp/$better");
					}

					// replace old image in the html by new image
					$html = str_replace([
						"'$original'",
						"\"$original\"",
						"'cid:$original'",
						"\"cid:$original\"",
						"'$wwwhttp/temp/$original'",
						"\"$wwwhttp/temp/$original\""
					],[
						"'$better'",
						"\"$better\"",
						"'cid:$better'",
						"\"cid:$better\"",
						"'$wwwhttp/temp/$better'",
						"\"$wwwhttp/temp/$better\""
					], $html);
				}
			}
			$response->images = $images;
		}
	}

	/**
	 * Get the most current ad to be shown in the response
	 *
	 * @author salvipascual
	 * @return Array
	 */
	public static function getAd($service)
	{
		// get the current environment
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$environment = $di->get('environment');

		// get app versions
		$appVersion = empty($service->input->appversion) ? 1 : floatval($service->input->appversion);
		$appLatestVersion = floatval($di->get('config')['global']['appversion']);
		$appType = empty($service->input->apptype) ? "original" : $service->input->apptype;

		// only show ads on the original app
		if($appType != "original") return false;

		// display ads 80% of the times
		$displayDownloadApp = rand(0, 100) < 20;

		// display message if app is not to the latest version
		if($appVersion < $appLatestVersion && $displayDownloadApp) {
			return [
				"icon" => "&DownArrow;",
				"text" => "App desactualizada; baje la version " . number_format($appLatestVersion, 1),
				"caption" => "Descargar",
				"link" => "APP"
			];
		}
		// else display an ad
		else {
			// get a random add
			$ad = Connection::query("
				SELECT id, icon, title
				FROM ads
				WHERE active=1
				AND paid=1
				AND (expires IS NULL OR expires > CURRENT_TIMESTAMP)
				ORDER BY RAND()
				LIMIT 1");

			// do nothing if there are no ads
			if(empty($ad)) return false;
			else $ad = $ad[0];

			// return the ad
			return [
				"icon" => $ad->icon,
				"text" => $ad->title,
				"caption" => "Ver",
				"link" => "PUBLICIDAD VER " . $ad->id
			];
		}
	}
}
