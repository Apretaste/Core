<?php

use Phalcon\Mvc\Controller;
use Aws\Sns\Message;
use Aws\Sns\MessageValidator;

class RunController extends Controller
{
	private $fromName;
	private $fromEmail;
	private $toEmail;
	private $messageId;
	private $subject;
	private $body;
	private $attachments = array();
	private $idEmail; // @TODO remove after unifiying the tables

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

		// get the email from the session or redirect to login
		$security = new Security();
		$user = $security->getUser();
		if($user) $this->fromEmail = $user->email;
		else {header("Location:/login?redirect={$this->subject}"); exit;}

		// set the running environment
		$this->di->set('environment', function() {return "web";});

		// run the request
		$utils = new Utils();
		$ret = $utils->runRequest($this->fromEmail, $this->subject, '', []);

		// render the response
		$render = new Render();
		$html = $render->renderHTML($ret->service, $ret->response);
		die($html);
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
		$utils = new Utils();
		$email = $utils->detokenize($token);
		if( ! $email) die('{"code":"error","message":"bad authentication"}');

		// save the API log
		$wwwroot = $this->di->get('path')['root'];
		$haveAttachments = empty($attachment) ? 0 : 1;
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/api.log");
		$logger->log("User:$email, Subject:$subject, Attachments:$haveAttachments");
		$logger->close();

		// some services cannot be called from the API
		$serviceName = strtoupper(explode(" ", $subject)[0]);
		if (strtoupper($serviceName) == 'EXCLUYEME') die('{"code":"error","message":"service not accesible"}');

		// check if the user is blocked
		$connection = new Connection();
		$blocked = $connection->query("SELECT email FROM person WHERE email='$email' AND blocked=1");
		if(count($blocked)>0) die('{"code":"error","message":"user blocked"}');

		// download attachments and save the paths in the array
		$attach = array();
		if ($attachment)
		{
			// get the path for the image
			$wwwroot = $this->di->get('path')['root'];
			$temp = "$wwwroot/temp/".$utils->generateRandomHash().".jpg";

			// clean base64 string
			$data = explode(',', $attachment);
			$data = isset($data[1]) ? $data[1] : $data[0];

			// save base64 string as a JPG image
			$im = imagecreatefromstring(base64_decode($data));
			imagejpeg($im, $temp);
			imagedestroy($im);

			// optimize the image and grant full permits
			$utils->optimizeImage($temp);
			chmod($temp, 0777);

			$attach[] = $temp;
		}

		// run the request and get the service and first response
		$ret = $utils->runRequest($email, $subject, $body, $attach);
		$service = $ret->service;
		$response = $ret->response;

		// respond by email, if there is an email to send
		$render = new Render();
		if($response->email && $response->render)
		{
			$sender = new Email();
			$sender->to = $response->email;
			$sender->subject = $response->subject;
			$sender->body = $render->renderHTML($service, $response);
			$sender->send();
		}

		// update last access time to current
		$connection->query("UPDATE person SET last_access=CURRENT_TIMESTAMP WHERE email='$email'");

		// respond to the API
		if($response->render) die($render->renderJSON($response));
		else die('{"code":"ok"}');
	}

	/**
	 * Receives an email petition and responds via email
	 *
	 * @author salvipascual
	 * @param GET data from app
	 */
	public function appAction()
	{
		$this->fromEmail = $this->request->get("email");
		$text = $this->request->get("command");
		$appversion = $this->request->get("appversion");
		$osversion = $this->request->get("osversion");
		$timestamp = $this->request->get("timestamp");
		$token = $this->request->get("token"); // base64 nauta pass
		$this->attachments = []; // @TODO find the way to upload files

		// ensure the person has access
		$utils = new Utils();
		$pass = $utils->getNautaPassword($this->fromEmail);
		if($pass != base64_decode($token)) die('{"code":"300", "message":"El token es incorrecto o el usuario no existe"}');

		// error if we don't have the password
		if(empty($pass)) {
			$output = new stdClass();
			$output->code = "300";
			$output->message = "No password for {$this->to}";
			return $output;
		}

		// set the running environment
		$this->di->set('environment', function() {return 'app';});

		// update last access time and make person active
		$connection = new Connection();
		$personExist = $utils->personExist($this->fromEmail);
		if ($personExist) $connection->query("UPDATE person SET active=1, appversion='$appversion', last_access=CURRENT_TIMESTAMP WHERE email='{$this->fromEmail}'");
		else {
			// create a unique username, save the new person and add a notification
			$username = $utils->usernameFromEmail($this->fromEmail);
			$connection->query("INSERT INTO person (email, username, last_access, appversion, source) VALUES ('{$this->fromEmail}', '$username', CURRENT_TIMESTAMP, '$appversion', 'app')");
			$utils->addNotification($this->fromEmail, "Bienvenido", "Bienvenido a Apretaste", "WEB bienvenido.apretaste.com");
		}

		// run the request
		$utils = new Utils();
		$ret = $utils->runRequest($this->fromEmail, $text, '', $this->attachments);
		$service = $ret->service;
		$response = $ret->response;

		// if the request needs an email back
		if($response->render)
		{
			// set the layout to blank
			$response->setEmailLayout('email_text.tpl');
			$temp = $utils->getTempDir();

			// is there is a cache time, add it
			if($response->cache) {
				$cache = "{$temp}{$response->cache}.cache";
				file_put_contents($cache, "");
				$response->attachments[] = $cache;
			}

			// render the HTML, unless it is a status call
			if($text == "status") $this->body = "{}";
			else {
				$render = new Render();
				$this->body = $render->renderHTML($service, $response);
			}

			// get extra data for the app
			// if the old version is calling status, do not get extra data
			// @TODO remove when we get rid of the old version
			$isPerfilStatus = substr($text, 0, strlen("perfil status")) === "perfil status";
			if($isPerfilStatus) $extra = "{}";
			else {
				$res = $utils->getExternalAppData($this->fromEmail, $timestamp);
				$response->attachments = array_merge($response->attachments, $res["attachments"]);
				$extra = $res["json"];
			}

			// create an attachment file for the extra structure
			$ntfFile = $temp . substr(md5(date('dHhms') . rand()), 0, 8) . ".ext";
			file_put_contents($ntfFile, $extra);
			$response->attachments[] = $ntfFile;

			// get a random name for the file and folder
			$fileName = substr(md5(rand() . date('dHhms')), 0, 8) . ".zip";
			$zipFile = $utils->getPublicTempDir() . $fileName;
			$htmlFile = substr(md5(date('dHhms') . rand()), 0, 8) . ".html";

			// create the zip file
			$zip = new ZipArchive;
			$zip->open($zipFile, ZipArchive::CREATE);
			$zip->addFromString($htmlFile, $this->body);

			// add all attachments to the zip and close it
			foreach ($response->attachments as $a) $zip->addFile($a, basename($a));
			$zip->close();
		}

		// update values in the delivery table
		$safeQuery = $connection->escape($service->request->query);
		$connection->query("
			INSERT INTO delivery SET
			user='{$this->fromEmail}',
			request_service='{$service->serviceName}',
			request_subservice='{$service->request->subservice}',
			request_query='$safeQuery',
			environment='app',
			delivery_code='200',
			delivery_method='internet',
			delivery_date=CURRENT_TIMESTAMP");

		// display ok response
		$path = $response->render ? $utils->getPublicTempDir('http').$fileName : "";
		die('{"code":"200", "message":"", "render":"'.$response->render.'", "file":"'.$path.'"}');
	}

	/**
	 * Receives an email petition and responds via email
	 *
	 * @author salvipascual
	 * @param POST data from webhook
	 */
	public function webhookAction()
	{
		// get the time when the service started executing
		$execStartTime = date("Y-m-d H:i:s");

		// get data from Amazon AWS webhook
		$this->callAmazonWebhook();

		// get the environment from the email
		$email = str_replace(".", "", explode("+", explode("@", $this->toEmail)[0])[0]);
		$connection = new Connection();
		$res = $connection->query("SELECT environment FROM delivery_input WHERE email='$email'");
		$environment = empty($res) ? "default" : $res[0]->environment;

		// stop procesing if the sender is invalid
		$utils = new Utils();
		if($environment != "app") { // no need to test for the app
			$status = $utils->deliveryStatus($this->fromEmail);
			if($status != 'ok') return $utils->createAlert("ALERT: {$this->fromEmail} failed with status $status");
		}

		// update the number of emails received
		$connection->query("UPDATE delivery_input SET received=received+1 WHERE email='$email'");

		// update last access time and make person active
		$personExist = $utils->personExist($this->fromEmail);
		if ($personExist) $connection->query("UPDATE person SET active=1, last_access=CURRENT_TIMESTAMP WHERE email='{$this->fromEmail}'");
		else {
			// create a unique username and save the new person
			$username = $utils->usernameFromEmail($this->fromEmail);
			$connection->query("INSERT INTO person (email, username, last_access, source) VALUES ('{$this->fromEmail}', '$username', CURRENT_TIMESTAMP, '$environment')");

			// add the welcome notification
			$utils->addNotification($this->fromEmail, "Bienvenido", "Bienvenido a Apretaste", "WEB bienvenido.apretaste.com");
		}

		// insert a new email in the delivery table and get the ID
		$attachs = array();
		foreach ($this->attachments as $a) $attachs[] = basename($a);
		$attachsStr = implode(",", $attachs);
		$domain = explode("@", $this->fromEmail)[1];
		$this->idEmail = $connection->query("
			INSERT INTO delivery (user, mailbox, request_domain, environment, email_id, email_subject, email_body, email_attachments)
			VALUES ('{$this->fromEmail}', '{$this->toEmail}', '$domain', '$environment', '{$this->messageId}', '{$this->subject}', '{$this->body}', '$attachsStr')");

		// set the running environment
		$this->di->set('environment', function() use($environment) {return $environment;});

		// execute the right environment type
		if($environment == "app") $log = $this->runApp();
		elseif($environment == "email") $log = $this->runEmail();
		elseif($environment == "download") {
			$startWithApp = substr($this->subject, 0, strlen("app")) === "app";
			if( ! $startWithApp) $this->subject = "app";
			$log = $this->runEmail();
		}else $log = $this->runSupport();

		// save execution time to the db
		$currentTime = new DateTime();
		$startedTime = new DateTime($execStartTime);
		$executionTime = $currentTime->diff($startedTime)->format('%H:%I:%S');
		$connection->query("UPDATE delivery SET process_time='$executionTime' WHERE id='{$this->idEmail}'");

		// display the webhook log
		$wwwroot = $this->di->get('path')['root'];
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/webhook.log");
		$logger->log("$environment | From:{$this->fromEmail} To:{$this->toEmail} | $log");
		$logger->close();
	}

	/**
	 * Receives email from the webhook and parse it for the app
	 */
	private function runApp()
	{
		// error if no attachment is received
		if(isset($this->attachments[0]) && file_exists($this->attachments[0])) {
			$attachEmail = $this->attachments[0];
		}else{
			$output = new stdClass();
			$output->code = "515";
			$output->message = "Error on attachment file";
			die(json_encode($output));
		}

		// get path to the folder to save
		$utils = new Utils();
		$temp = $utils->getTempDir();
		$textFile = ""; $attachs = [];
		$folderName = str_replace(".zip", "", basename($attachEmail));

		// get the text file and attached files
		$zip = new ZipArchive;
		$zip->open($attachEmail);
		for($i = 0; $i < $zip->numFiles; $i++) {
			$filename = $zip->getNameIndex($i);
			if(substr($filename, -4) == ".txt") $textFile = $filename;
			else $attachs[] = "$temp/$folderName/$filename";
		}

		// extract file contents
		$zip->extractTo("$temp/$folderName");
		$zip->close();

		// get the input if the data is a JSON
		$input = json_decode(file_get_contents("$temp/$folderName/$textFile"));
		if($input) {
			$text = $input->command;
			$appversion = $input->appversion;
			$osversion = $input->osversion;
			$nautaPass = $input->token;
			$timestamp = $input->timestamp;
		}
		// get the input if the data is plain text (version <= 2.5)
		// @TODO remove when v2.5 is not in use anymore
		else {
			$file = file("$temp/$folderName/$textFile");
			$text = trim($file[0]);
			$appversion = empty($file[1]) ? "" : trim($file[1]);
			$osversion = false;
			$nautaPass = empty($file[2]) ? false : base64_decode(trim($file[2]));
			$timestamp = time(); // get only notifications
		}

		// save Nauta password if passed
		$connection = new Connection();
		if($nautaPass) {
			$encryptPass = $utils->encrypt($nautaPass);
			$connection->query("
				DELETE FROM authentication WHERE email = '{$this->fromEmail}' AND appname = 'apretaste';
				INSERT INTO authentication (email, pass, appname, platform, version) VALUES ('{$this->fromEmail}', '$encryptPass', 'apretaste', 'android', '$osversion');");
		}

		// update the version of the app used
		$connection->query("UPDATE person SET appversion='$appversion' WHERE email='{$this->fromEmail}'");

		// run the request
		$ret = $utils->runRequest($this->fromEmail, $text, '', $attachs);
		$service = $ret->service;
		$response = $ret->response;

		// if the request needs an email back
		if($response->render)
		{
			// set the layout to blank
			$response->setEmailLayout('email_text.tpl');

			// is there is a cache time, add it
			if($response->cache) {
				$cache = "$temp{$response->cache}.cache";
				file_put_contents($cache, "");
				$response->attachments[] = $cache;
			}

			// render the HTML, unless it is a status call
			if($text == "status") $this->body = "{}";
			else {
				$render = new Render();
				$this->body = $render->renderHTML($service, $response);
			}

			// get extra data for the app
			// if the old version is calling status, do not get extra data
			// @TODO remove when we get rid of the old version
			$isPerfilStatus = substr($text, 0, strlen("perfil status")) === "perfil status";
			if($isPerfilStatus) $extra = "{}";
			else {
				$res = $utils->getExternalAppData($this->fromEmail, $timestamp);
				$response->attachments = array_merge($response->attachments, $res["attachments"]);
				$extra = $res["json"];
			}

			// create an attachment file for the extra structure
			$ntfFile = $temp . substr(md5(date('dHhms') . rand()), 0, 8) . ".ext";
			file_put_contents($ntfFile, $extra);
			$response->attachments[] = $ntfFile;

			// prepare and send the email
			$email = new Email();
			$email->id = $this->idEmail;
			$email->to = $this->fromEmail;
			$email->subject = $this->subject;
			$email->body = $this->body;
			$email->replyId = $this->messageId;
			$email->images = $response->images;
			$email->attachments = $response->attachments;
			$email->setImageQuality();
			$email->setContentAsZipAttachment();
			$output = $email->send();
		}

		// update values in the delivery table
		$safeQuery = $connection->escape($service->request->query);
		$connection->query("
			UPDATE delivery SET
			request_service='{$service->serviceName}',
			request_subservice='{$service->request->subservice}',
			request_query='$safeQuery'
			WHERE id='{$this->idEmail}'");

		// return message for the log
		$hasNautaPass = $nautaPass ? 1 : 0;
		$log = "Text:$text, Ticket:{$this->subject}, Version:$appversion, NautaPass:$hasNautaPass";
		return $log;
	}

	/**
	 * Receives email from the webhook and parse it for the email tool
	 */
	private function runEmail()
	{
		// run the request and get the service and response
		$utils = new Utils();
		$ret = $utils->runRequest($this->fromEmail, $this->subject, $this->body, $this->attachments);
		$service = $ret->service;
		$response = $ret->response;

		// if the request needs an email back
		if($response->render)
		{
			// render the HTML body
			$render = new Render();
			$body = $render->renderHTML($service, $response);

			// prepare and send the email
			$email = new Email();
			$email->id = $this->idEmail;
			$email->to = $this->fromEmail;
			$email->subject = $response->subject;
			$email->body = $body;
			$email->images = $response->images;
			$email->attachments = $response->attachments;
			$email->replyId = $this->messageId;
			$email->setImageQuality();
			$email->send();
		}

		// update values in the delivery table
		$connection = new Connection();
		$safeQuery = $connection->escape($service->request->query);
		$connection->query("
			UPDATE delivery SET
			request_service='{$service->serviceName}',
			request_subservice='{$service->request->subservice}',
			request_query='$safeQuery'
			WHERE id='{$this->idEmail}'");

		// return message for the log
		return "Email received and sent";
	}

	/**
	 * Send the email to the support team
	 */
	private function runSupport()
	{
		// save the new ticket into the database
		$connection = new Connection();
		$connection->query("INSERT INTO support_tickets (`from`, subject, body) VALUES ('{$this->fromEmail}', '{$this->subject}', '{$this->body}')");

		// save the new ticket in the reports table
		$mysqlDateToday = date("Y-m-d H:i:s");
		$connection->query("
			INSERT IGNORE INTO support_reports (inserted) VALUES ('$mysqlDateToday');
			UPDATE support_reports SET new_count = new_count+1 WHERE inserted = '$mysqlDateToday';");

		// return message for the log
		return "Support ticket created";
	}

	/**
	 * Read the email from Amazon AWS
	 */
	private function callAmazonWebhook()
	{
		// get the message object
		$message = Message::fromRawPostData();
		$validator = new MessageValidator();
		if( ! $validator->isValid($message)) return false;
		// error_log(print_r($message,true)); // subscription

		// get the bucket and key from message
		$message = json_decode($message['Message']);
		$bucket = $message->Records[0]->s3->bucket->name;
		$keyname = $message->Records[0]->s3->object->key;

		// get the temp folder
		$utils = new Utils();
		$temp = $utils->getTempDir();

		// instantiate the client
		$s3Client = new Aws\S3\S3Client([
			'version'=>'2006-03-01',
			'region'=>'us-east-1',
			'credentials'=>[
				'key'=>$this->di->get('config')['sns']['key'],
				'secret'=>$this->di->get('config')['sns']['secret']
			]]);

		// save file from SNS to the temp folder
		$s3Client->getObject(array('Bucket'=>$bucket, 'Key'=>$keyname, 'SaveAs'=>$temp."mails/".$keyname));

		// parse the file
		$parser = new PhpMimeMailParser\Parser();
		$parser->setPath($temp."mails/".$keyname);
		$messageId = str_replace(array("<",">","'"), "", $parser->getHeader('message-id'));
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

		// display the Amazon SNS log
		$wwwroot = $this->di->get('path')['root'];
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/amazon.log");
		$logger->log("\nID:$messageId\nFROM:$fromEmail\nTO:$toEmail\nSUBJECT:$subject\n------------\n$body\n\n");
		$logger->close();

		// save attachments to the temp folder
		$attachments = array();
		if($attachs)
		{
			$attachs = $parser->saveAttachments($temp."attachments/");
			foreach ($attachs as $attach)
			{
				$name = $utils->generateRandomHash();
				$mimeType = mime_content_type($attach);

				// convert to JPG and optimize image
				if(substr($mimeType, 0, strlen("image")) === "image")
				{
					$newFile = $temp."attachments/$name.jpg";
					imagejpeg(imagecreatefromstring(file_get_contents($attach)), $newFile);
					$utils->optimizeImage($newFile);
					unlink($attach);
				}
				// save any other file to the temporals
				else
				{
					// rename the file
					$ext = pathinfo($attach, PATHINFO_EXTENSION);
					$newFile = $temp."attachments/$name.$ext";
					rename($attach, $newFile);
				}

				// add to array of attachments
				$attachments[] = $newFile;
			}
		}

		// save variables in the class
		$this->fromName = $fromName;
		$this->fromEmail = $fromEmail;
		$this->toEmail = $toEmail;
		$this->subject = trim(preg_replace('/\s{2,}/', " ", preg_replace('/\'|`/', "", $subject)));
		$this->body = str_replace("'", "", $body);
		$this->attachments = $attachments;
	}
}
