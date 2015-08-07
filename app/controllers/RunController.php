<?php

use Phalcon\Mvc\Controller;
  
class RunController extends Controller
{
	public function indexAction()
	{
		// get the time when the service started executing
		$execStartTime = date("Y-m-d H:i:s");

		// get details of the incoming request
		$email = $this->request->get("email");
		$name = $this->request->get("name");
		$subject = $this->request->get("subject");
		$body = $this->request->get("body");
		$attachments = $this->request->get("attachments");
		$format = $this->request->get("format"); // [html|json|email]

		// do not let pass empty emails
		if(empty($email)){ throw new Exception("Email is required"); exit; }

		// set default values
		if(empty($attachments)) $attachments = array();
		if(empty($format)) $format = "email";

		// get the name of the service based on the subject line
		$subjectPieces = explode(" ", $subject);
		$serviceName = strtolower($subjectPieces[0]);
		unset($subjectPieces[0]);

		// check the service requested actually exists
		$utils = new Utils();
		if( ! $utils->serviceExist($serviceName)) {
			$serviceName = "ayuda";
		}

		// include the service code
		$wwwroot = $this->di->get('path')['root'];
		include "$wwwroot/services/$serviceName/service.php";

		// check if a subservice is been invoked
		$subServiceName = "";
		if(isset($subjectPieces[1])) { // some services are requested only with name
			$serviceClassMethods = get_class_methods($serviceName);
			if(preg_grep("/^_{$subjectPieces[1]}$/i", $serviceClassMethods)){
				$subServiceName = strtolower($subjectPieces[1]);
				unset($subjectPieces[1]);
			}
		}

		// get the service query
		$query = implode(" ", $subjectPieces);

		// create a new Request object
		$request = new Request();
		$request->email = $email;
		$request->name = $name;
		$request->subject = $subject;
		$request->body = $body;
		$request->attachments = $attachments;
		$request->service = $serviceName;
		$request->subservice = $subServiceName;
		$request->query = $query;

		// run the service and get a response
		$userService = new $serviceName();
		if(empty($subServiceName)) {
			$response = $userService->_main($request);
		}else{
			$subserviceFunction = "_$subServiceName";
			$response = $userService->$subserviceFunction($request);
		}

		// create a new render
		$render = new Render();

		// render the template and echo on the screen
		if($format == "html"){
			echo $render->renderHTML($serviceName, $response);
		}

		// echo the json on the screen
		if($format == "json"){
			echo $render->renderJSON($response);
		}

		// render the template email it to the user
		if($format == "email"){
			// get params for the email
			$from = "soporte@apretaste.com";
			$subject = "Respondiendo a su email con asunto: $serviceName";
			$body = $render->renderHTML($serviceName, $response);

			// send the email
			echo "send email"; // TODO send email
			echo $body;
		}

		// calculate execution time when the service stopped executing
		$currentTime = new DateTime();
		$startedTime = new DateTime($execStartTime);
		$executionTime = $currentTime->diff($startedTime)->format('%H:%I:%S');

		// get the user email domain
		$emailPieces = explode("@", $email);
		$domain = $emailPieces[1];

		// save the logs on the utilization table
		$sql = "INSERT INTO utilization	(service, subservice, query, requestor, request_time, response_time, domain, ad_top, ad_botton) VALUES ('$serviceName','$subServiceName','$query','$email','$execStartTime','$executionTime','$domain','','')";
		$connection = new Connection();
		$result = $connection->deepQuery($sql);
	}
}
