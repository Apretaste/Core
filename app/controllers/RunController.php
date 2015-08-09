<?php

use Phalcon\Mvc\Controller;

class RunController extends Controller
{
	public function indexAction()
	{
		echo "You cannot execute this resource directly. Access to run/display or run/api";
	}

	/**
	 * Executes an html request the outside. Display the HTML on screen
	 * @author salvipascual
	 * @param String $subject, subject line of the email
	 * @param String $body, body of the email
	 * */
	public function displayAction()
	{
		$subject = $this->request->get("subject");
		$body = $this->request->get("body");
		$result = $this->renderResponse("html@apretaste.com", $subject, "HTML", $body, array(), "html");
		echo $result;
	}

	/**
	 * Executes an API request. Display the JSON on screen
	 * @author salvipascual
	 * @param String $subject, subject line of the email
	 * @param String $body, body of the email
	 * */
	public function apiAction()
	{
		$subject = $this->request->get("subject");
		$body = $this->request->get("body");
		$result = $this->renderResponse("api@apretaste.com", $subject, "API", $body, array(), "json");
		echo $result;
	}

	/**
	 * Handle webhook requests
	 * @author salvipascual
	 * */
	public function webhookAction(){
		// get the mandrill json structure from the post
		$mandrill_events = $_POST['mandrill_events'];

		// get values from the json
		$event = json_decode($mandrill_events);
		$fromEmail = $event[0]->msg->from_email;
		$toEmail = $event[0]->msg->email;
		$sender = isset($event[0]->msg->headers->Sender) ? $event[0]->msg->headers->Sender : "";
		$subject = $event[0]->msg->headers->Subject;
		$body = $event[0]->msg->html;
		$attachments = array(); // TODO get the attachments

		// save the webhook log
		$wwwroot = $this->di->get('path')['root'];
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/webhook.log");
		$logger->log("From: $fromEmail, Subject: $subject\n$mandrill_events\n\n");
		$logger->close();

		// execute the query
		$this->renderResponse($fromEmail, $subject, $sender, $body, $attachments, "email");
	}

	/**
	 * Respond to a request based on the parameters passed
	 * @author salvipascual
	 * */
	private function renderResponse($email, $subject, $sender="", $body="", $attachments=array(), $format="html"){
		// get the time when the service started executing
		$execStartTime = date("Y-m-d H:i:s");

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
		$request->name = $sender;
		$request->subject = $subject;
		$request->body = $body;
		$request->attachments = $attachments;
		$request->service = $serviceName;
		$request->subservice = $subServiceName;
		$request->query = $query;

		// get details of the service from the database
		$connection = new Connection();
		$sql = "SELECT * FROM service WHERE name = '$serviceName'";
		$result = $connection->deepQuery($sql);

		// create a new service Object of the user type
		$userService = new $serviceName();
		$userService->serviceName = $serviceName;
		$userService->serviceDescription = $result[0]->description;
		$userService->creatorEmail = $result[0]->creator_email;
		$userService->serviceCategory = $result[0]->category;
		$userService->serviceUsage = $result[0]->usage_text;
		$userService->insertionDate = $result[0]->insertion_date;
		$userService->pathToService = $utils->getPathToService($serviceName);
		$userService->utils = $utils;

		// run the service and get a response
		if(empty($subServiceName)) {
			$response = $userService->_main($request);
		}else{
			$subserviceFunction = "_$subServiceName";
			$response = $userService->$subserviceFunction($request);
		}

		// create a new render
		$render = new Render();

		// render the template and echo on the screen
		if($format == "html")
		{
			return $render->renderHTML($userService, $response);
		}

		// echo the json on the screen
		if($format == "json")
		{
			return $render->renderJSON($response);
		}

		// render the template email it to the user
		// only save stadistics for email requests
		if($format == "email")
		{
			// get params for the email
			$subject = "Respondiendo a su email con asunto: $serviceName";
			$body = $render->renderHTML($userService, $response);
			$images = array_merge($response->images, $response->getAds());
			$attachments = $response->attachments;

			// send the email
			$emailSender = new Email();
			$emailSender->sendEmail($email, $subject, $body, $images, $attachments);

			// create the new Person if access for the first time
			if ($utils->personExist($email)){
				$sql = "INSERT INTO person (email) VALUES ('$email')";
				$connection->deepQuery($sql);
			}

			// calculate execution time when the service stopped executing
			$currentTime = new DateTime();
			$startedTime = new DateTime($execStartTime);
			$executionTime = $currentTime->diff($startedTime)->format('%H:%I:%S');

			// get the user email domainEmail
			$emailPieces = explode("@", $email);
			$domain = $emailPieces[1];

			// save the logs on the utilization table
			$sql = "INSERT INTO utilization	(service, subservice, query, requestor, request_time, response_time, domain, ad_top, ad_botton) VALUES ('$serviceName','$subServiceName','$query','$email','$execStartTime','$executionTime','$domain','','')";
			$connection->deepQuery($sql);

			// return positive answer to prove the email was quequed
			return true;
		}

		// false if no action could be taken
		return false;
	}
}
