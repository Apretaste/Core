<?php

use Phalcon\Mvc\Controller;
use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
use \Phalcon\Logger\Adapter\File;

class RunController extends Controller
{
	private $deliveryId; // id of the table delivery
	private $execStartTime; // time when the service started

	private $fromName;
	private $fromEmail;
	private $person;
	private $toEmail;
	private $subject;
	private $body;
	private $attachment;
	private $partition;
	private $idEmail;
	private $resPath = false; // path to the response zip
	private $sendEmails = true;

	/**
	 * Initialize basic attributes for the service
	 * @author salvipascual
	 */
	public function beforeExecuteRoute()
	{
		// create a unique ID for each petition
		$this->deliveryId = strval(random_int(100,999)).substr(strval(time()),4);

		// get the time when the service started executing
		$this->execStartTime = date("Y-m-d H:i:s");
	}

	/**
	 * Calculate the procesing time after execution
	 * @author salvipascual
	 */
	public function afterExecuteRoute()
	{
		// get the current execution time
		$currentTime = new DateTime();
		$startedTime = new DateTime($this->execStartTime);
		$executionTime = $currentTime->diff($startedTime)->format('%H:%I:%S');

		// save the final running time
		Connection::query("
			UPDATE delivery 
			SET request_date='{$this->execStartTime}', process_time='$executionTime' 
			WHERE id={$this->deliveryId}");
	}

	/**
	 * Receives an HTTP petition and display to the web
	 *
	 * @author salvipascual
	 * @get String $subject, subject line of the email
	 */
	public function webAction()
	{
		// get the service to load
		$command = $this->request->get("cm");
		$data = $this->request->get("dt");
		$token = $this->request->get('token');
		$redirect = $this->request->get('rd');

		// if empty get the default service
		if(empty($command)) $command = "SERVICIOS";
		else $command = str_replace("_", " ", $command);

		if(empty($redirect)) $redirect = true;
		else $redirect = ($redirect==='false')?false:true;

		// try login by token or load from the session
		if($token) {
			$user = Security::loginByToken($token);
			if(is_string($user)) {
				echo $user;
				header("Location:/login?redirect=$command");
				exit;
			}
		}
		else $user = Security::getUser();

		// if user is not logged, redirect to login page
		if($user) $person = Utils::getPerson($user->email);
		else {header("Location:/login?redirect=$command"); exit;}

		// create the input
		$input = new Input();
		$input->command = $command;
		$input->data = $data ? json_decode(urldecode(base64_decode(($data)))) : new stdClass();
		$input->redirect = $redirect;
		$input->environment = "web";
		$input->ostype = "web";
		$input->method = "http";
		$input->apptype = "web";

		// run the service and get the response
		$response = Utils::runService($person, $input);

		// get public and internal paths
		$wwwroot = $this->di->get('path')['root'];
		$wwwhttp = $this->di->get('path')['http'];
		$servicePath = Utils::getPathToService($response->serviceName);

		if($response->template && $redirect) {
			// get the HTML template and the JS and CSS files
			$templateHTML = file_get_contents($response->template);

			// get the layout and put the template inside
			if(empty($response->layout)) $response->layout = "$wwwroot/app/layouts/web_layout.ejs";
			$layoutHTML = file_get_contents($response->layout);
			$layoutHTML = str_replace('{{APP_TEMPLATE_CODE}}', $templateHTML, $layoutHTML);

			// get the HTML starting point
			$startHTML = file_get_contents("$wwwroot/app/layouts/web_start.ejs");
			$startCSS = file_get_contents("$servicePath/styles.css");
			$startJS = file_get_contents("$servicePath/scripts.js");

			// replace shortags on the HTML code
			$startHTML = str_replace('{{APP_LAYOUT_CODE}}', $layoutHTML, $startHTML);
			$startHTML = str_replace('{{APP_SERVICE_NAME}}', $response->serviceName, $startHTML);
			$startHTML = str_replace('{{APP_SERVICE_PATH}}/images', '{{APP_IMAGE_PATH}}', $startHTML);
			$startHTML = str_replace('{{APP_RESOURCES}}', "$wwwhttp/", $startHTML);
			$startHTML = str_replace('{{APP_IMAGE_PATH}}', "$wwwhttp/temp/", $startHTML);
			$startHTML = str_replace('{{APP_JSON_RESPONSE}}', $response->json, $startHTML);
			$startHTML = str_replace('{{APP_TEMPLATE_CSS}}', $startCSS, $startHTML);
			$startHTML = str_replace('{{APP_TEMPLATE_JS}}', $startJS, $startHTML);

			// display the template on screen
			echo $startHTML;
		}

		// create a new entry on the delivery table
		Connection::query("
			INSERT INTO delivery (id, id_person, environment, email_subject)
			VALUES ({$this->deliveryId}, {$person->id}, 'web', '$command')");
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

		// get the user from the token
		$user = Security::loginByToken($token);

		// error if the token is incorrect
		if(is_string($user)){
			echo $user;
			return;
		}

		// error if no files were sent
		if(empty($_FILES['attachments'])) {
			echo '{"code":"301", "message":"No content file was received"}';
			return;
		}

		// create attachments array
		$file = Utils::getTempDir().'attachments/'.$_FILES['attachments']['name'];
		move_uploaded_file($_FILES['attachments']['tmp_name'], $file);
		$this->attachment = $file;
		$this->fromEmail = $user->email;
		$this->person = Utils::getPerson($user->email);

		// create a new entry on the delivery table
		Connection::query("
			INSERT INTO delivery (id, id_person, environment) 
			VALUES ({$this->deliveryId}, {$this->person->id}, 'app')");

		// execute the service as app
		$this->sendEmails = false;
		$log = $this->runApp();

		// save final data to the db
		Connection::query("
			UPDATE delivery SET 
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
	 * Receives an email petition from Apretaste Webhook and responds via email
	 *
	 * @author ricardo@apretaste.org
	 * @param POST data from Apretaste webhook
	 */
	public function emailAction()
	{
		// save the email as a MIME object
		$message = json_decode(file_get_contents('php://input'), true);
		$file = Utils::getTempDir() . 'mails/' . Utils::generateRandomHash();
		file_put_contents($file, $message["data"]);

		// parse the incoming email
		$this->parseEmail($file);
		
		// get the person from email
		$this->person = Utils::getPerson($this->fromEmail);

		if ($this->person){ //if person exists
			// do not respond to blocked accounts or not allowed domains
			if($this->person->blocked || !Utils::isAllowedDomain($this->fromEmail)) return;
			
			// update last access time and make person active
			Connection::query("UPDATE person SET active=1, last_access=CURRENT_TIMESTAMP WHERE id={$this->person->id}");
		} else {
			// create a unique username and save the new person
			$username = Utils::usernameFromEmail($this->fromEmail);
			Connection::query("
				INSERT INTO person (email, username, last_access, source)
				VALUES ('{$this->fromEmail}', '$username', CURRENT_TIMESTAMP, '$environment')");
			
			// get the person from email
			$this->person = Utils::getPerson($this->fromEmail);

			// add the welcome notification
			Utils::addNotification($this->person->id, "PERFIL", "Bienvenido a Apretaste", "PERFIL bienvenido");
		}

		// get the environment from the mailbox
		$mailbox = str_replace(".", "", explode("@", $this->toEmail)[0]);
		$res = Connection::query("SELECT environment FROM delivery_input WHERE email='$mailbox'");
		$environment = empty($res) ? "app" : $res[0]->environment;

		// if the app is reporting a failure
		if($environment == "failure") {
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
			return;
		}

		// insert a new email in the delivery table
		$this->deliveryId = strval(random_int(100,999)).substr(strval(time()),4);
		$attach = $this->attachment ? basename($this->attachment) : "";
		$domain = explode("@", $this->fromEmail)[1];
		Connection::query("
			INSERT INTO delivery (id, id_person, mailbox, request_domain, environment, email_subject, email_body, email_attachments)
			VALUES ({$this->deliveryId}, {$this->person->id}, '{$this->toEmail}', '$domain', '$environment', '{$this->subject}', '{$this->body}', '$attach')");

		// execute the right environment type
		if($environment == "support") $log = $this->runSupport();
		else $log = $this->runApp();

		// display the webhook log
		$wwwroot = $this->di->get('path')['root'];
		$logger = new File("$wwwroot/logs/webhook.log");
		$logger->log("Environmet:$environment | From:{$this->fromEmail} To:{$this->toEmail} | $log");
		$logger->close();
	}

	/**
	 * Fires when we receive a an email from the app
	 */
	private function runApp()
	{
		// do not return if attachment is not received
		if(!file_exists($this->attachment)) 
			return Utils::createAlert("[RunController::runApp] Error ZIP file was not received for delivery ID: {$this->deliveryId}");

		// get path to the folder to save
		$requestFile = ""; $keyFile = ""; $attachs = []; 
		$folderName = str_replace(".zip", "", basename($this->attachment));

		// get the text file and attached files
		$temp = Utils::getTempDir();
		$zip = new ZipArchive;
		$result = $zip->open($this->attachment);
		if ($result === true) {
			for($i = 0; $i < $zip->numFiles; $i++) {
				$filename = $zip->getNameIndex($i);
				if($filename == "request.json") $requestFile = $filename;
				else if($filename == "AES.key") $keyFile = $filename;
				else $attachs[$filename] = "$temp/attachments/$folderName/$filename";
			}

			// extract file contents
			$zip->extractTo("$temp/attachments/$folderName");
			$zip->close();
		} 
		else return Utils::createAlert("[RunController::runApp] Error when open ZIP file {$this->attachment} (error code: $result)");

		// decrypt the AES key if exists, and then decrypt the request
		if(isset($keyFile)){
			$keyFile = file_get_contents("$temp/attachments/$folderName/$keyFile");
			$AESkey = base64_decode(Cryptor::decryptRSA($this->person->id, $keyFile, 'server'));
			$jsonFileDecoded = Cryptor::decryptAES($AESkey, file_get_contents("$temp/attachments/$folderName/$requestFile"));
			// get the request data the JSON decoded file
			$input = json_decode($jsonFileDecoded);
		}
		// compatibility hack while the beta is running
		else $input = json_decode(file_get_contents("$temp/attachments/$folderName/$requestFile"));

		$input->osversion = filter_var($input->osversion, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
		$input->files = $attachs;
		$input->environment = "app";
		if(!isset($input->data)) $input->data = new stdClass();
		if(!isset($input->redirect)) $input->redirect = true;

		// save Nauta password if passed
		if($input->token) {
			$nautaPass = base64_decode($input->token);
			$encryptPass = Utils::encrypt($nautaPass);
			$auth = Connection::query("SELECT id FROM authentication WHERE person_id='{$this->person->id}' AND appname='apretaste'");
			if(empty($auth)) Connection::query("INSERT INTO authentication (person_id, pass, appname, platform, version) VALUES ('{$this->person->id}', '$encryptPass', 'apretaste', '{$input->ostype}', '{$input->osversion}')");
			else Connection::query("UPDATE authentication SET pass='$encryptPass', platform='{$input->ostype}', version='{$input->osversion}' WHERE person_id='{$this->person->id}' AND appname='apretaste'");
		}

		// make the app params global
		$this->di->set('appversion', function() use($input) { return $input->appversion; });
		$this->di->set('ostype', function() use($input) { return $input->ostype; });
		$this->di->set('method', function() use($input) { return $input->method; });

		// update the version of the app used
		Connection::query("UPDATE person SET appversion='{$input->appversion}' WHERE id='{$this->person->id}'");

		// check if the request is a reload
		$isReload = $input->command == "reload";
		if ($isReload) {
			$response = new Response();
			$attachService = false;
			Cryptor::saveAppRSAKey($this->person->id, $input->publicKey);
		}
		else {
			// process the service
			$response = Utils::runService($this->person, $input);
			// send the EJS templates for new versions
			$attachService = Utils::getServiceVersion($response->serviceName) > $input->serviceversion;
		}

		// if the request needs an email back
		if(($response->render && $input->redirect) || $isReload){
			// get extra data for the app and create an attachment file for the data structure
			$appData = Utils::getAppData($this->person, $input, $response);

			$this->resPath = Utils::generateZipResponse($response, $appData, $attachService);
			if($this->sendEmails){
				// prepare and send the email
				$email = new Email();
				$email->to = $this->fromEmail;
				$email->requestDate = $this->execStartTime;
				$email->deliveryId = $this->deliveryId;
				$email->subject = $this->subject;
				$email->body = $this->body;
				$email->response = $response;
				$email->attachments = [$this->resPath];
				$email->send();
			}
		}

		// update values in the delivery table
		$input->data = json_encode($input->data);
		$safeQuery = Connection::escape($input->data, 1024);
		Connection::query("
			UPDATE delivery SET
			request_service='{$input->command}',
			request_query='$safeQuery',
			request_method='{$input->method}'
			WHERE id={$this->deliveryId}");

		// return message for the log
		$input->token = "";
		$nautaPass = isset($nautaPass) ? "Yes" : "No";
		$log = "DeliveryId:{$this->deliveryId} | Ticket:{$this->subject}";
		foreach ($input as $key => $value) if(is_string($value)) $log .= " | $key:$value";
		return  $log;
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
