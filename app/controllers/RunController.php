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
		if($this->di->get('environment') == "sandbox") $result .= '<div style="color:white; background-color:red; font-weight:bold; display:inline-block; padding:5px; position:absolute; top:10px; right:10px; z-index:99">SANDBOX</div>';
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
		$email = $this->request->get("email");
		$attachments = $this->request->get("attachments");
		if(empty($email)) $email = "api@apretaste.com";

		// create attachment as an object
		$attach = array();
		if ( ! empty($attachments))
		{
			// save image into the filesystem
			$wwwroot = $this->di->get('path')['root'];
			$utils = new Utils();
			$filePath = "$wwwroot/temp/".$utils->generateRandomHash().".jpg";
			$content = file_get_contents($attachments);
			imagejpeg(imagecreatefromstring($content), $filePath);

			// optimize the image
			$utils->optimizeImage($filePath);

			// grant full access to the file
			chmod($filePath, 0777);

			// create new object
			$object = new stdClass();
			$object->path = $filePath;
			$object->type = image_type_to_mime_type(IMAGETYPE_JPEG);
			$attach = array($object);
		}

		// update last access time to current and erase reminder
		$connection = new Connection();
		$connection->deepQuery(
			"START TRANSACTION;
				UPDATE person SET last_access=CURRENT_TIMESTAMP WHERE email='$email';
				DELETE FROM reminder WHERE email='$email';
			COMMIT;");

		// some services cannot be used via the API
		if (stripos($subject, 'excluyeme') !== false)
		{
			die("You cannot call this service from the API");
		}

		$result = $this->renderResponse($email, $subject, "API", $body, $attach, "json");
		echo $result;
	}

	/**
	 * Handle webhook requests
	 * @author salvipascual
	 * */
	public function webhookAction()
	{
		// get the mandrill json structure from the post
		$mandrill_events = $_POST['mandrill_events'];

		// get values from the json
		$event = json_decode($mandrill_events);
		$fromEmail = $event[0]->msg->from_email;
		$fromName = isset($event[0]->msg->from_name) ? $event[0]->msg->from_name : "";
		$toEmail = $event[0]->msg->email;
		$subject = isset($event[0]->msg->headers->Subject) ? $event[0]->msg->headers->Subject : "";
		$body = isset($event[0]->msg->text) ? $event[0]->msg->text : "";
		$filesAttached = empty($event[0]->msg->attachments) ? array() : $event[0]->msg->attachments;
		$attachments = array();

		// do not continue procesing the email if the sender is not valid
		$utils = new Utils();
		$status = $utils->deliveryStatus($fromEmail, 'in');
		if($status != 'ok') return;

		// if there are attachments, download them all and create the files in the temp folder 
		if(count($filesAttached)>0)
		{
			// save the attached files and create the response array
			$wwwroot = $this->di->get('path')['root'];
			foreach ($filesAttached as $key=>$values)
			{
				$mimeType = $values->type;
				$content = $values->content;
				$mimeTypePieces = explode("/",$mimeType);
				$fileType = $mimeTypePieces[0];
				$extension = $mimeTypePieces[1];
				$fileNameNoExtension = $utils->generateRandomHash();

				// convert images to jpg and save it to temporal
				if($fileType == "image")
				{
					// save the image as a jpg file
					$mimeType = image_type_to_mime_type(IMAGETYPE_JPEG);
					$filePath = "$wwwroot/temp/$fileNameNoExtension.jpg";
					imagejpeg(imagecreatefromstring(base64_decode($content)), $filePath);

					// optimize the image
					$utils->optimizeImage($filePath);
				}
				else // save any other file to the temporals
				{
					$filePath = "$wwwroot/temp/$fileNameNoExtension.$extension";
					$ifp = fopen($filePath, "wb");
					fwrite($ifp, base64_decode($content));
					fclose($ifp);
				}

				// grant full access to the file
				chmod($filePath, 0777);

				// create new object
				$object = new stdClass();
				$object->path = $filePath;
				$object->type = $mimeType;
				$attachments[] = $object;
			}
		}

		// update the counter of emails received from that mailbox
		$today = date("Y-m-d H:i:s");
		$connection = new Connection();
		$connection->deepQuery("UPDATE jumper SET received_count=received_count+1, last_usage='$today' WHERE email='$toEmail'");

		// save the webhook log
		$wwwroot = $this->di->get('path')['root'];
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/webhook.log");
		$logger->log("From: $fromEmail, Subject: $subject\n$mandrill_events\n\n");
		$logger->close();

		// execute the query
		$this->renderResponse($fromEmail, $subject, $fromName, $body, $attachments, "email");
	}

	/**
	 * Respond to a request based on the parameters passed
	 * @author salvipascual
	 * */
	private function renderResponse($email, $subject, $sender="", $body="", $attachments=array(), $format="html")
	{
		// get the time when the service started executing
		$execStartTime = date("Y-m-d H:i:s");

		// remove double spaces and apostrophes from the subject
		// sorry apostrophes break the SQL code :-( 
		$subject = trim(preg_replace('/\s{2,}/', " ", preg_replace('/\'|`/', "", $subject)));

		// get the name of the service based on the subject line
		$subjectPieces = explode(" ", $subject);
		$serviceName = strtolower($subjectPieces[0]);
		unset($subjectPieces[0]);

		// check the service requested actually exists
		$utils = new Utils();
		if( ! $utils->serviceExist($serviceName))
		{
			$serviceName = "ayuda";
		}

		// include the service code
		$wwwroot = $this->di->get('path')['root'];
		include "$wwwroot/services/$serviceName/service.php";

		// check if a subservice is been invoked
		$subServiceName = "";
		if(isset($subjectPieces[1]) && ! preg_match('/\?|\(|\)|\\\|\/|\.|\$|\^|\{|\}|\||\!/', $subjectPieces[1]))
		{
			$serviceClassMethods = get_class_methods($serviceName);
			if(preg_grep("/^_{$subjectPieces[1]}$/i", $serviceClassMethods))
			{
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
		$request->subservice = trim($subServiceName);
		$request->query = trim($query);

		// connect to the database
		$connection = new Connection();

		// get the path to the service
		$servicePath = $utils->getPathToService($serviceName);

		// get details of the service
		if($this->di->get('environment') == "sandbox")
		{
			// get details of the service from the XML file
			$xml = simplexml_load_file("$servicePath/config.xml");
			$serviceCreatorEmail = trim((String)$xml->creatorEmail);
			$serviceDescription = trim((String)$xml->serviceDescription);
			$serviceCategory = trim((String)$xml->serviceCategory);
			$serviceUsageText = trim((String)$xml->serviceUsage);
			$showAds = isset($xml->showAds) && $xml->showAds==0 ? 0 : 1;
			$serviceInsertionDate = date("Y/m/d H:m:s");
		}
		else
		{
			// get details of the service from the database
			$sql = "SELECT * FROM service WHERE name = '$serviceName'";
			$result = $connection->deepQuery($sql);
			
			$serviceCreatorEmail = $result[0]->creator_email;
			$serviceDescription = $result[0]->description;
			$serviceCategory = $result[0]->category;
			$serviceUsageText = $result[0]->usage_text;
			$serviceInsertionDate = $result[0]->insertion_date;
			$showAds = $result[0]->ads == 1; // @TODO run when deploying a service
		}

		// create a new service Object of the user type
		$userService = new $serviceName();
		$userService->serviceName = $serviceName;
		$userService->serviceDescription = $serviceDescription;
		$userService->creatorEmail = $serviceCreatorEmail;
		$userService->serviceCategory = $serviceCategory;
		$userService->serviceUsage = $serviceUsageText;
		$userService->insertionDate = $serviceInsertionDate;
		$userService->pathToService = $servicePath;
		$userService->showAds = $showAds;
		$userService->utils = $utils;

		// run the service and get a response
		if(empty($subServiceName))
		{
			$response = $userService->_main($request);
		}
		else
		{
			$subserviceFunction = "_$subServiceName";
			$response = $userService->$subserviceFunction($request);
		}

		// a service can return an array of Response or only one.
		// we always treat the response as an array
		$responses = is_array($response) ? $response : array($response);

		// clean the empty fields in the response  
		foreach($responses as $rs)
		{
			$rs->email = empty($rs->email) ? $email : $rs->email;
			$rs->subject = empty($rs->subject) ? "Respuesta del servicio $serviceName" : $rs->subject;
		}

		// create a new render
		$render = new Render();

		// render the template and echo on the screen
		if($format == "html")
		{
			$html = "";
			for ($i=0; $i<count($responses); $i++)
			{
				$html .= "<br/><center><small><b>To:</b> " . $responses[$i]->email . ". <b>Subject:</b> " . $responses[$i]->subject . "</small></center><br/>";
				$html .= $render->renderHTML($userService, $responses[$i]);
				if($i < count($responses)-1) $html .= "<br/><hr/><br/>";
			}

			$usage = nl2br(str_replace('{APRETASTE_EMAIL}', $utils->getValidEmailAddress(), $serviceUsageText));
			$html .= "<br/><hr><center><p><b>XML DEBUG</b></p><small>";
			$html .= "<p><b>Owner: </b>$serviceCreatorEmail</p>";
			$html .= "<p><b>Category: </b>$serviceCategory</p>";
			$html .= "<p><b>Description: </b>$serviceDescription</p>";
			$html .= "<p><b>Usage: </b><br/>$usage</p></small></center>";

			return $html;
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
			$emailSender = new Email();

			// get params for the email and send the response emails
			foreach($responses as $rs)
			{
				if($rs->render) // ommit default Response()
				{
					// save impressions in the database
					$ads = $rs->getAds();
					if($userService->showAds && ! empty($ads))
					{
						$sql = "";
						if( ! empty($ads[0])) $sql .= "UPDATE ads SET impresions=impresions+1 WHERE id='{$ads[0]->id}';";
						if( ! empty($ads[1])) $sql .= "UPDATE ads SET impresions=impresions+1 WHERE id='{$ads[1]->id}';";
						$connection->deepQuery($sql);
					}

					// prepare the email variable
					$emailTo = $rs->email;
					$subject = $rs->subject;
					$images = $rs->images;
					$attachments = $rs->attachments;
					$body = $render->renderHTML($userService, $rs);

					// remove dangerous characters that may break the SQL code
					$subject = trim(preg_replace('/\'|`/', "", $subject));

					// send the response email 
					$emailSender->sendEmail($emailTo, $subject, $body, $images, $attachments);
				}
			}

			// get the person, false if the person does not exist 
			$person = $utils->getPerson($email);

			// if the person exist in Apretaste
			$setActive = "";
			if ($person !== false)
			{
				// if the person is inactive and he/she is not trying to opt-out
				if( ! $person->active && $serviceName != "excluyeme")
				{
					// make the person active again 
					$setActive = "active=1,";

					//  add to the email list in Mail Lite
					$utils->subscribeToEmailList($email);
				}

				// update last access time to current and erase reminder
				$connection->deepQuery("
					START TRANSACTION;
					UPDATE person SET $setActive last_access=CURRENT_TIMESTAMP WHERE email='$email';
					DELETE FROM reminder WHERE email='$email';
					COMMIT;");
			}
			else // if the person accessed for the first time, insert him/her
			{
				// create a unique username
				$username = $utils->usernameFromEmail($email);

				// save the new Person
				$sql = "INSERT INTO person (email, username, last_access) VALUES ('$email', '$username', CURRENT_TIMESTAMP)";
				$connection->deepQuery($sql);

			   	// check if the person was invited to use Apretaste
				$sql = "SELECT * FROM invitations WHERE email_invited = '$email' AND used='0'";
				$invitations = $connection->deepQuery($sql);
				if(count($invitations)>0)
				{
					// create tickets for all the invitors. When a person 
					// is invited by more than one person, they all get tickets
					$sql = "START TRANSACTION;";
					foreach ($invitations as $invite)
					{
						// create the query
						$sql .= "INSERT INTO ticket (email, paid) VALUES ('{$invite->email_inviter}', 0);";
						$sql .= "UPDATE person SET credit=credit+0.25 WHERE email='{$invite->email_inviter}';";
						$sql .= "UPDATE invitations SET used='1' WHERE invitation_id = '{$invite->invitation_id}';";

						// email the invitor
						$body = "<h1>Nuevo ticket para nuestra Rifa</h1><p>Su contacto {$invite->email_invited} ha usado Apretaste por primera vez gracias a su invitaci&oacute;n, por lo cual hemos agregado a su perfil un ticket para nuestra rifa y 25&cent; en cr&eacute;dito de Apretaste.</p><p>Muchas gracias por invitar a sus amigos, y gracias por usar Apretaste</p>";
						$emailSender->sendEmail($invite->email_inviter, "Ha ganado un ticket para nuestra Rifa", $body);
					}
					$sql .= "COMMIT;";
					$connection->deepQuery($sql);
				}

				//  add to the email list in Mail Lite
				$utils->subscribeToEmailList($email);
			}

			// calculate execution time when the service stopped executing
			$currentTime = new DateTime();
			$startedTime = new DateTime($execStartTime);
			$executionTime = $currentTime->diff($startedTime)->format('%H:%I:%S');

			// get the user email domainEmail
			$emailPieces = explode("@", $email);
			$domain = $emailPieces[1];

			// save the logs on the utilization table
			$safeQuery = $connection->escape($query);
			$sql = "INSERT INTO utilization	(service, subservice, query, requestor, request_time, response_time, domain, ad_top, ad_botton) VALUES ('$serviceName','$subServiceName','$safeQuery','$email','$execStartTime','$executionTime','$domain','','')";
			$connection->deepQuery($sql);

			// return positive answer to prove the email was quequed
			return true;
		}

		// false if no action could be taken
		return false;
	}
}
