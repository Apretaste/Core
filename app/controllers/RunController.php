<?php

use Phalcon\Mvc\Controller;
use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
use \Phalcon\Logger\Adapter\File;

class RunController extends Controller
{
	private $fromName;
	private $fromEmail;
	private $person;
	private $toEmail;
	private $execStartTime;
	private $deliveryId; // id of the table delivery
	private $subject;
	private $body;
	private $attachment;
	private $partition;
	private $idEmail;
	private $resPath = false; // path to the response zip
	private $sendEmails = true;

	/**
	 * Receives an HTTP petition and display to the web
	 *
	 * @author salvipascual
	 * @get String $subject, subject line of the email
	 */
	public function displayAction()
	{
		// get the service to load
		$this->subject = $this->request->get("subject");
		$token = $this->request->get('token');

		// try login by token or load from the session
		$security = new Security();
		if($token) $user = $security->loginByToken($token);
		else $user = $security->getUser();

		// if user is not logged, redirect to login page
		if($user) $person = Utils::getPerson($user->email);
		else {header("Location:/login?redirect={$this->subject}"); exit;}

		// set the running environment
		$this->di->set('environment', function() {return "web";});

		// run the request & render the response
		$ret = Render::runRequest($person, $this->subject, '', []);
		echo Render::renderHTML($ret->service, $ret->response);
	}

	/**
	 * Receives an HTTP petition and returns a JSON
	 *
	 * @author salvipascual
	 * @get String $token
	 * @get String $subject
	 * @get String $body
	 * @get String $attachments
	 */
	public function apiAction()
	{
		// allow JS clients to use the API
		header("Access-Control-Allow-Origin: *");

		// set the running environment
		$this->di->set('environment', function() {return "api";});

		// get params from GET (or from the encripted API)
		$token = $this->request->get("token");
		$subject = $this->request->get("subject");
		$body = $this->request->get("body");
		$attachment = $this->request->get("attachment");

		// if is not encrypted, get the email from the token
		$email = Utils::detokenize($token);
		if(empty($token) || empty($email)) {
			echo '{"code":"error","message":"bad authentication"}';
			return false;
		}

		// save the API log
		$wwwroot = $this->di->get('path')['root'];
		$haveAttachments = empty($attachment) ? 0 : 1;
		$logger = new File("$wwwroot/logs/api.log");
		$logger->log("User:$email, Subject:$subject, Attachments:$haveAttachments");
		$logger->close();

		// some services cannot be called from the API
		$serviceName = strtoupper(explode(" ", $subject)[0]);
		if (strtoupper($serviceName) == 'EXCLUYEME') {
			echo '{"code":"error","message":"service not accesible"}';
			return false;
		}

		// check if the user is blocked
		$person = Utils::getPerson($email);
		if($person->blocked) {
			echo '{"code":"error","message":"user blocked"}';
			return false;
		}

		// download attachments and save the paths in the array
		$attach = [];
		if ($attachment)
		{
			// get the path for the image
			$temp = Utils::getTempDir().'attach_images/'.Utils::generateRandomHash().".jpg";

			// clean base64 string
			$data = explode(',', $attachment);
			$data = isset($data[1]) ? $data[1] : $data[0];

			// save base64 string as a JPG image
			$im = imagecreatefromstring(base64_decode($data));
			imagejpeg($im, $temp);
			imagedestroy($im);
			$attach[] = $temp;
		}

		// run the request and get the service and first response
		$ret = Render::runRequest($person, $subject, $body, $attach);
		$service = $ret->service;
		$response = $ret->response;

		// respond by email, if there is an email to send
		if($response->email && $response->render)
		{
			$sender = new Email();
			$sender->to = $response->email;
			$sender->subject = $response->subject;
			$sender->body = Render::renderHTML($service, $response);
			$sender->send();
		}

		// update last access time to current
		Connection::query("UPDATE person SET last_access=CURRENT_TIMESTAMP WHERE id='{$person->id}'");

		// respond to the API
		if($response->render) echo Render::renderJSON($response);
		else echo '{"code":"ok"}';
	}

	/**
	 * Receives a petition via http from the app
	 *
	 * @author salvipascual
	 * @param GET data from app
	 */
	public function appAction()
	{
		// get the token from the URL
		$token = trim($this->request->get("token"));

		// get the time when the service started executing
		$this->execStartTime = date("Y-m-d H:i:s");

		// get the user from the token
		$security = new Security();
		$user = $security->loginByToken($token);

		// error if the token is incorrect
		if(empty($user)) {
			echo '{"code":"300", "message":"Bad or empty token"}';
			return false;
		}

		// error if no files were sent
		if(empty($_FILES['attachments'])) {
			echo '{"code":"301", "message":"No content file was received"}';
			return false;
		}

		// create attachments array
		$file = Utils::getTempDir().'attachments/'.$_FILES['attachments']['name'];
		move_uploaded_file($_FILES['attachments']['tmp_name'], $file);
		$this->attachment = $file;
		$this->fromEmail = $user->email;
		$this->person = Utils::getPerson($user->email);

		// create a new entry on the delivery table
		$this->deliveryId = strval(random_int(100,999)).substr(strval(time()),4);
		Connection::query("
			INSERT INTO delivery (id, id_person, request_date, environment) 
			VALUES ({$this->deliveryId}, {$this->person->id}, '{$this->execStartTime}', 'app')");

		// set up environment
		$this->di->set('environment', function() {return "app";});

		// execute the service as app
		$this->sendEmails = false;
		$log = $this->runApp();

		// get the current execution time
		$currentTime = new DateTime();
		$startedTime = new DateTime($this->execStartTime);
		$executionTime = $currentTime->diff($startedTime)->format('%H:%I:%S');

		// save final data to the db
		Connection::query("
			UPDATE delivery SET 
			process_time='$executionTime',
			delivery_code='200',
			delivery_method='http',
			delivery_date=CURRENT_TIMESTAMP
			WHERE id={$this->deliveryId}");

		// move the file to the public temp folder
		$path = "";
		if($this->resPath) {
			copy($this->resPath, Utils::getPublicTempDir().basename($this->resPath));
			$path = Utils::getPublicTempDir('http').basename($this->resPath);
		}

		// display the webhook log
		$wwwroot = $this->di->get('path')['root'];
		$logger = new File("$wwwroot/logs/webhook.log");
		$logger->log("Environmet:app | From:{$this->fromEmail} To:{$this->toEmail} | $log");
		$logger->close();

		// display ok response
		echo '{"code":"200", "message":"", "file":"'.$path.'"}';
	}

	/**
	 * Fires when we receive a an email from the app
	 */
	private function runApp()
	{
		// error if no attachment is received
		if( ! file_exists($this->attachment)) {
			$output = new stdClass();
			$output->code = "515";
			$output->message = "Error on attached file";
			echo json_encode($output);
			return false;
		}

		// get path to the folder to save
		$requestFile = ""; $attachs = [];
		$folderName = str_replace(".zip", "", basename($this->attachment));

		// get the text file and attached files
		$temp = Utils::getTempDir();
		$zip = new ZipArchive;
		$result = $zip->open($this->attachment);
		if ($result === true) {
			for($i = 0; $i < $zip->numFiles; $i++) {
				$filename = $zip->getNameIndex($i);
				if(substr($filename, -5) == ".json") $requestFile = $filename;
				else $attachs[] = "$temp/attachments/$folderName/$filename";
			}

			// extract file contents
			$zip->extractTo("$temp/attachments/$folderName");
			$zip->close();
		} else {
			return Utils::createAlert("[RunController::runApp] Error when open ZIP file {$this->attachment} (error code: $result)");
		}

		// get the request data from the JSON file
		$requestData = json_decode(file_get_contents("$temp/attachments/$folderName/$requestFile"));
		$requestData->osversion = filter_var($requestData->osversion, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

		// save Nauta password if passed
		if($requestData->token) {
			$nautaPass = base64_decode($requestData->token);
			$encryptPass = Utils::encrypt($nautaPass);
			$auth = Connection::query("SELECT id FROM authentication WHERE person_id='{$this->person->id}' AND appname='apretaste'");
			if(empty($auth)) Connection::query("INSERT INTO authentication (person_id, pass, appname, platform, version) VALUES ('{$this->person->id}', '$encryptPass', 'apretaste', '{$requestData->ostype}', '{$requestData->osversion}')");
			else Connection::query("UPDATE authentication SET pass='$encryptPass', platform='{$requestData->ostype}', version='{$requestData->osversion}' WHERE person_id='{$this->person->id}' AND appname='apretaste'");
		}

		// make the app params global
		$this->di->set('appversion', function() use($requestData) { return $requestData->appversion; });
		$this->di->set('ostype', function() use($requestData) { return $requestData->ostype; });
		$this->di->set('method', function() use($requestData) { return $requestData->method; });

		// update the version of the app used
		Connection::query("UPDATE person SET appversion='{$requestData->appversion}' WHERE id='{$this->person->id}'");

		$isReload = $requestData->command=="reload";
		// run the request if it's not a reload
		if (!$isReload){
			$response = Render::runRequest($this->person, $requestData->command, '', $attachs, $requestData);
			
			// send the service's EJS templates if the stored version doesn't match
			if($response->service->version != $requestData->serviceversion) $response->attachService = true;
		}
		else $response = new Response();

		// if the request needs an email back
		if($response->render || $isReload){
			// get extra data for the app and create an attachment file for the data structure
			$responseData = Utils::getAppData($this->person, $requestData, $response->service, $response);
			$response->dataFile = "$temp/extra/".substr(md5(date('dHhms').rand()), 0, 8).".json";
			file_put_contents($response->dataFile, $responseData);

			//optimize images
			Render::optimizeImages($response->images, $this->person->img_quality, $requestData->ostype);

			$this->resPath = $response->generateZipResponse();			
			if($this->sendEmails){
				// prepare and send the email
				$email = new Email();
				$email->to = $this->fromEmail;
				$email->requestDate = $this->execStartTime;
				$email->deliveryId = $this->deliveryId;
				$email->subject = $this->subject;
				$email->body = $this->body;
				$email->response = $response;
				$email->images = $response->images;
				$email->attachments = [$this->resPath];
				$email->send();
			}
		}

		// update values in the delivery table
		if(!$isReload){
			$safeQuery = Connection::escape($response->service->request->query, 1024);
			Connection::query("
				UPDATE delivery SET
				request_service='{$response->service->name}',
				request_subservice='{$response->service->request->subservice}',
				request_query='$safeQuery',
				request_method='{$requestData->method}'
				WHERE id={$this->deliveryId}");
		}
		else {
			Connection::query("
				UPDATE delivery SET
				request_service='reload',
				request_subservice='',
				request_query='',
				request_method='{$requestData->method}'
				WHERE id={$this->deliveryId}");
		}
		

		// return message for the log
		$requestData->token = "";
		$nautaPass = isset($nautaPass) ? "Yes" : "No";
		$log = "DeliveryId:{$this->deliveryId} | Ticket:{$this->subject}";
		foreach ($requestData as $key => $value) if(is_string($value)) $log .= " | $key:$value";
		return  $log;
	}

	/**
	 * Fires when the app reports an email was not received
	 */
	private function runFailure()
	{
		// update code for failure emails
		Connection::query("
			UPDATE delivery 
			SET delivery_code='555' 
			WHERE id_person={$this->person->id} 
			AND email_subject='{$this->subject}'");

		// display the webhook log
		$wwwroot = $this->di->get('path')['root'];
		$logger = new File("$wwwroot/logs/webhook.log");
		$logger->log("Environmet:FAILURE | From:{$this->fromEmail} To:{$this->toEmail} | Subject:{$this->subject}");
		$logger->close();

		// create entry in the error log
		Utils::createAlert("[RunController::runFailure] Failure reported by {$this->fromEmail} with subject {$this->subject}. Reported to {$this->toEmail}", "NOTICE");
	}

	/**
	 * Fires when an email is sent to the support team
	 */
	private function runSupport()
	{
		// save the new ticket into the database
		$subject = Connection::escape($this->subject, 250);
		$body = Connection::escape($this->body, 1024);
		Connection::query("INSERT INTO support_tickets (`from`, subject, body) VALUES ('{$this->fromEmail}', '$subject', '$body')");

		// save the new ticket in the reports table
		$mysqlDateToday = date("Y-m-d");
		Connection::query("
			INSERT IGNORE INTO support_reports (inserted) VALUES ('$mysqlDateToday');
			UPDATE support_reports SET new_count = new_count+1 WHERE inserted = '$mysqlDateToday';");

		// return message for the log
		return "Support ticket created";
	}

	/**
	 * Receives an email petition from Apretaste Webhook and responds via email
	 *
	 * @author ricardo@apretaste.org
	 * @param POST data from Apretaste webhook
	 */
	public function ApWebhookAction()
	{
		// get the time when the service started executing
		$this->execStartTime = date("Y-m-d H:i:s");

		// save the email as a MIME object
		$message = json_decode(file_get_contents('php://input'), true);
		$file = Utils::getTempDir() . 'mails/' . Utils::generateRandomHash();
		file_put_contents($file, $message["data"]);

		// parse the incoming email
		$this->parseEmail($file);

		
		$this->person = Utils::getPerson($this->fromEmail);

		if ($this->person) { //if person exists
			// do not respond to blocked accounts
			if($this->person->blocked) return false;
			
			// update last access time and make person active
			Connection::query("UPDATE person SET active=1, last_access=CURRENT_TIMESTAMP WHERE id={$this->person->id}");
		} else {
			// create a unique username and save the new person
			$username = Utils::usernameFromEmail($this->fromEmail);
			Connection::query("INSERT INTO person (email, username, last_access, source)
							   VALUES ('{$this->fromEmail}', '$username', CURRENT_TIMESTAMP, '$environment')");
			
			$this->person = Utils::getPerson();
			// add the welcome notification
			Utils::addNotification($this->person->id, "Bienvenido", "Bienvenido a Apretaste", "WEB bienvenido.apretaste.com");
		}

		// get the environment from the mailbox
		$mailbox = str_replace(".", "", explode("+", explode("@", $this->toEmail)[0])[0]);
		$res = Connection::query("SELECT environment FROM delivery_input WHERE email='$mailbox'");
		$environment = empty($res) ? "invalid" : $res[0]->environment;

		// set the running environment
		$this->di->set('environment', function() use($environment) {return $environment;});

		// if the app is reporting a failure
		if($environment == "failure") {
			$this->runFailure();
			return false;
		}

		// insert a new email in the delivery table
		$this->deliveryId = strval(random_int(100,999)).substr(strval(time()),4);
		$attach = $this->attachment ? basename($this->attachment) : "";
		$domain = explode("@", $this->fromEmail)[1];
		Connection::query("
			INSERT INTO delivery (id, id_person, request_date, mailbox, request_domain, environment, email_subject, email_body, email_attachments)
			VALUES ({$this->deliveryId}, {$this->person->id}, '{$this->execStartTime}', '{$this->toEmail}', '$domain', '$environment', '{$this->subject}', '{$this->body}', '$attach')");

		// execute the right environment type
		if($environment == "app") $log = $this->runApp();
		else $log = $this->runSupport();

		// save execution time to the db
		$currentTime = new DateTime();
		$startedTime = new DateTime($this->execStartTime);
		$executionTime = $currentTime->diff($startedTime)->format('%H:%I:%S');
		Connection::query("UPDATE delivery SET process_time='$executionTime' WHERE id={$this->deliveryId}");

		// display the webhook log
		$wwwroot = $this->di->get('path')['root'];
		$logger = new File("$wwwroot/logs/webhook.log");
		$logger->log("Environmet:$environment | From:{$this->fromEmail} To:{$this->toEmail} | $log");
		$logger->close();
	}

	/**
	 * Parse a MIME email type and save info to the class
	 *
	 * @author ricardo
	 */
	private function parseEmail($emailPath)
	{
		// parse the file
		$parser = new PhpMimeMailParser\Parser();
		$parser->setPath($emailPath);
		$from = $parser->getAddresses('from');
		$fromEmail = $from[0]['address'];
		$fromName = $from[0]['display'];
		$subject = $parser->getHeader('subject');
		$body = trim($parser->getMessageBody('text'));
		$attachs = $parser->getAttachments();

		// get the TO address
		$to = $parser->getAddresses('to');
		if(empty($to)) $to = $parser->getAddresses('Delivered-To');
		$toEmail = $to[0]['address'];

		// save the attachment to the temp folder
		if($attachs) {
			$att = $parser->saveAttachments(Utils::getTempDir()."attachments/");
			$ext = pathinfo($att[0], PATHINFO_EXTENSION);
			$newFile = Utils::getTempDir().'attachments/'.Utils::generateRandomHash().'.'.$ext;
			rename($att[0], $newFile);
			$this->attachment = $newFile;
		}

		// save variables in the class
		$this->fromName = $fromName;
		$this->fromEmail = $fromEmail;
		$this->toEmail = $toEmail;
		$this->subject = Connection::escape(trim(preg_replace('/\s{2,}/', " ", preg_replace('/\'|`/', "", $subject))), 1023);
		$this->body = str_replace("'", "", $body);
	}
}
