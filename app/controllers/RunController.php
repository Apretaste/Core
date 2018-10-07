<?php

use Phalcon\Mvc\Controller;
use Aws\Sns\Message;
use Aws\Sns\MessageValidator;

class RunController extends Controller
{
	private $fromName;
	private $fromEmail;
	private $fromEmailId;
	private $toEmail;
	private $execStartTime;
	private $queryId;
	private $messageId;
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
		if($user) $this->fromEmail = $user->email;
		else {header("Location:/login?redirect={$this->subject}"); exit;}

		// set the running environment
		$this->di->set('environment', function() {return "web";});

		// run the request & render the response
		$ret = Render::runRequest($this->fromEmail, $this->subject, '', []);
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
		$utils = new Utils();
		$email = $utils->detokenize($token);
		if(empty($token) || empty($email)) {
			echo '{"code":"error","message":"bad authentication"}';
			return false;
		}

		// save the API log
		$wwwroot = $this->di->get('path')['root'];
		$haveAttachments = empty($attachment) ? 0 : 1;
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/api.log");
		$logger->log("User:$email, Subject:$subject, Attachments:$haveAttachments");
		$logger->close();

		// some services cannot be called from the API
		$serviceName = strtoupper(explode(" ", $subject)[0]);
		if (strtoupper($serviceName) == 'EXCLUYEME') {
			echo '{"code":"error","message":"service not accesible"}';
			return false;
		}

		// check if the user is blocked
		$blocked = Connection::query("SELECT email FROM person WHERE email='$email' AND blocked=1");
		if(count($blocked)>0) {
			echo '{"code":"error","message":"user blocked"}';
			return false;
		}

		// download attachments and save the paths in the array
		$attach = [];
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
			$attach[] = $temp;
		}

		// run the request and get the service and first response
		$ret = Render::runRequest($email, $subject, $body, $attach);
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
		Connection::query("UPDATE person SET last_access=CURRENT_TIMESTAMP WHERE email='$email'");

		// respond to the API
		if($response->render) echo Render::renderJSON($response);
		else echo '{"code":"ok"}';
	}

	/**
	 * Receives an email petition and responds via email
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
		$this->queryId = strval(random_int(100,999)).substr(strval(time()),4);

		// get the user from the token
		$security = new Security();
		$user = $security->loginByToken($token);

		// error if the token is incorrect
		if(empty($user)) {
			echo '{"code":"300", "message":"Bad or empty token"}';
			return false;
		}

		// do not respond to blocked accounts
		$blocked = Connection::query("SELECT email FROM person WHERE email='{$user->email}' AND blocked=1");
		if($blocked) return false;

		// error if no files were sent
		if(empty($_FILES['attachments'])) {
			echo '{"code":"301", "message":"No content file was received"}';
			return false;
		}

		// mark the domain as used
		$inputDomain = $_SERVER['HTTP_HOST'];
		Connection::query("UPDATE delivery_input SET received=received+1 WHERE email='$inputDomain'");

		// create attachments array
		$utils = new Utils();
		$file = $utils->getTempDir().$_FILES['attachments']['name'];
		move_uploaded_file($_FILES['attachments']['tmp_name'], $file);
		$this->attachment = $file;
		$this->fromEmail = $user->email;
		$this->fromEmailId = $utils->personExist($this->fromEmail);

		// create a new entry on the delivery table
		Connection::query("INSERT INTO delivery (id, id_person, request_date, environment) VALUES ({$this->queryId}, {$this->fromEmailId}, '{$this->execStartTime}', 'appnet')");

		// set up environment
		$this->di->set('environment', function() {return "appnet";});

		// execute the service as app
		$this->sendEmails = false;
		$this->runApp();

		// get the current execution time
		$currentTime = new DateTime();
		$startedTime = new DateTime($this->execStartTime);
		$executionTime = $currentTime->diff($startedTime)->format('%H:%I:%S');

		// save final data to the db
		Connection::query("
			UPDATE delivery
			SET process_time='$executionTime',
			delivery_code='200',
			delivery_method='internet',
			delivery_date=CURRENT_TIMESTAMP
			WHERE id={$this->queryId}");

		// move the file to the public temp folder
		$path = "";
		if($this->resPath) {
			copy($this->resPath, $utils->getPublicTempDir().basename($this->resPath));
			$path = $utils->getPublicTempDir('http').basename($this->resPath);
		}

		// display ok response
		echo '{"code":"200", "message":"", "file":"'.$path.'"}';
	}

	/**
	 * Receives an email petition from Amazon Webhook and responds via email
	 *
	 * @author salvipascual
	 * @param POST data from webhook
	 */
	public function webhookAction(){
		// get data from Amazon AWS webhook
		$this->callAmazonWebhook();
		$this->webhookProcessRequest();
	}

	/**
	 * Fires when we receive a an email from the app
	 */
	private function runApp()
	{
		// error if no attachment is received
		if($this->attachment && file_exists($this->attachment)) $attachEmail = $this->attachment;
		else {
			$output = new stdClass();
			$output->code = "515";
			$output->message = "Error on attached file";
			echo json_encode($output);
			return false;
		}

		// get path to the folder to save
		$utils = new Utils();
		$temp = $utils->getTempDir();
		$textFile = ""; $attachs = [];
		$folderName = str_replace(".zip", "", basename($attachEmail));

		// get the text file and attached files
		$zip = new ZipArchive;
		$result = $zip->open($attachEmail);
		if ($result === true) {
			for($i = 0; $i < $zip->numFiles; $i++) {
				$filename = $zip->getNameIndex($i);
				if(substr($filename, -4) == ".txt") $textFile = $filename;
				else $attachs[] = "$temp/$folderName/$filename";
			}

			// extract file contents
			$zip->extractTo("$temp/$folderName");
			$zip->close();
		} else {
			return $utils->createAlert("[RunController::runApp] Error when open ZIP file $attachEmail (error code: $result)");
		}

		// get the input if the data is a JSON [if $textFile == "", $input will be NULL]
		$input = json_decode(file_get_contents("$temp/$folderName/$textFile"));
		if($input) {
			if( ! isset($input->ostype)) $input->ostype = "android";
			if( ! isset($input->method)) $input->method = "email";
			if( ! isset($input->apptype)) $input->apptype = "original";
			if( ! isset($input->timestamp)) $input->timestamp = time();
			$input->osversion = (isset($input->osversion))?filter_var($input->osversion, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION):"";
			$input->nautaPass = base64_decode($input->token);
		// get the input if the data is plain text (version <= 2.5)
		} else {
			$input = new stdClass();
			$input->osversion = false;
			$input->ostype = "android";
			$input->method = "email";
			$input->apptype = "original";
			$input->timestamp = time(); // get only notifications

			$file = file("$temp/$folderName/$textFile");
			if (isset($file[0])) $input->command = trim($file[0]);
			else return $utils->createAlert("[RunController::runApp] Empty file $temp/$folderName/$textFile");
			$input->appversion = isset($file[1]) && is_numeric(trim($file[1])) ? trim($file[1]) : "";
			$input->nautaPass = empty($file[2]) ? false : base64_decode(trim($file[2]));
		}

		// save Nauta password if passed
		if($input->nautaPass) {
			$encryptPass = $utils->encrypt($input->nautaPass);
			$auth = Connection::query("SELECT id FROM authentication WHERE person_id='{$this->fromEmailId}' AND appname='apretaste'");
			if(empty($auth)) Connection::query("INSERT INTO authentication (person_id, pass, appname, platform, version) VALUES ('{$this->fromEmailId}', '$encryptPass', 'apretaste', '{$input->ostype}', '{$input->osversion}')");
			else Connection::query("UPDATE authentication SET pass='$encryptPass', platform='{$input->ostype}', version='{$input->osversion}' WHERE person_id='{$this->fromEmailId}' AND appname='apretaste'");
		}

		// update the version of the app used
		if (isset($input->appversion))
			Connection::query("UPDATE person SET appversion='{$input->appversion}' WHERE id='{$this->fromEmailId}'");


		// run the request
		$ret = Render::runRequest($this->fromEmail, $input->command, '', $attachs, $input);
		$service = $ret->service;
		$response = $ret->response;

		// if the request needs an email back
		if($response->render)
		{
			// set the layout for the app
			$response->setEmailLayout('email_app.tpl');

			// is there is a cache time, add it
			if($response->cache) {
				$cache = "$temp{$response->cache}.cache";
				file_put_contents($cache, "");
				$response->attachments[] = $cache;
			}

			// render the HTML, unless it is a status call
			if($input->command == "status") $this->body = "{}";
			else {
				$service->input = $input;
				$this->body = Render::renderHTML($service, $response);
			}

			// get extra data for the app
			// if the old version is calling status, do not get extra data
			// @TODO remove when we get rid of the old version
			$isPerfilStatus = substr($input->command, 0, strlen("perfil status")) === "perfil status";
			if($isPerfilStatus) $extra = "{}";
			else {
				$res = Utils::getExternalAppData($this->fromEmail, $input->timestamp, $service);
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
			$email->userId = $this->fromEmailId;
			$email->to = $this->fromEmail;
			$email->requestDate = $this->execStartTime;
			$email->queryId = $this->queryId;
			$email->subject = $this->subject;
			$email->body = $this->body;
			$email->replyId = $this->messageId;
			$email->images = $response->images;
			$email->attachments = $response->attachments;
			$this->resPath = $email->setContentAsZipAttachment();
			if($this->sendEmails) $email->send();
		}

		// update values in the delivery table
		$safeQuery = Connection::escape($service->request->query);
		Connection::query("
			UPDATE delivery SET
			request_service='{$service->serviceName}',
			request_subservice='{$service->request->subservice}',
			request_query='$safeQuery',
			request_method='{$input->method}'
			WHERE id={$this->queryId}");

		// return message for the log
		$hasNautaPass = $input->nautaPass ? 1 : 0;
		if( ! isset($input->appversion)) $input->appversion = "Unknown";
		$log = "Text:{$input->command}, Ticket:{$this->subject}, Version:{$input->appversion}, NautaPass:$hasNautaPass";
		return $log;
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
			WHERE id_person={$this->fromEmailId} 
			AND email_subject='{$this->subject}'");

		// update counter for failures
		$email = str_replace(".", "", explode("+", explode("@", $this->toEmail)[0])[0]);
		Connection::query("UPDATE delivery_input SET received=received+1 WHERE email='$email'");

		// if failed email is a Gmail account, make it inactive
		$gmailMailbox = Connection::query("
			SELECT delivery_message 
			FROM delivery 
			WHERE id_person='{$this->fromEmailId}'
			AND delivery_method='gmail'
			AND email_subject='{$this->subject}'");
		$gmailMailbox = empty($gmailMailbox[0]->mailbox) ? "" : $gmailMailbox[0]->mailbox;
		if($gmailMailbox) Connection::query("UPDATE delivery_gmail SET active=0 WHERE email='$gmailMailbox'");

		// create entry in the error log
		$gmailMessage = "Send using Gmail inbox $gmailMailbox";
		Utils::createAlert("[RunController::runFailure] Failure reported by {$this->fromEmail} with subject {$this->subject}. Reported to {$this->toEmail}. $gmailMessage", "NOTICE");

		// calculate failure percentage
		$failuresCount = 0;
		$last100codes = Connection::query("SELECT delivery_code FROM delivery WHERE TIMESTAMPDIFF(WEEK,request_date,NOW())=0 LIMIT 100");
		foreach ($last100codes as $row) if($row->delivery_code == '555') $failuresCount++;

		// alert developers if failures are over 20%
		if($failuresCount > 20) {
			$text = "[RunController::runFailure] APP FAILURE OVER 20%: Users may not be receiving responses";
			Utils::createAlert($text, "ERROR");
		}
	}

	/**
	 * Fires when we receive an email sent directly to Apretaste
	 *
	 * @return string
	 */
	private function runEmail()
	{
		// run the request and get the service and response
		$ret = Render::runRequest($this->fromEmail, $this->subject, $this->body, [$this->attachment]);
		$service = $ret->service;
		$response = $ret->response;

		// if the request needs an email back
		if($response->render)
		{
			// render the HTML body
			$body = Render::renderHTML($service, $response);

			// prepare and send the email
			$email = new Email();
			$email->id = $this->idEmail;
			$email->userId = $this->fromEmailId;
			$email->to = $this->fromEmail;
			$email->requestDate = $this->execStartTime;
			$email->queryId = $this->queryId;
			$email->subject = $response->subject;
			$email->body = $body;
			$email->images = $response->images;
			$email->attachments = $response->attachments;
			$email->replyId = $this->messageId;
			$email->send();
		}

		// update values in the delivery table
		$safeQuery = Connection::escape($service->request->query);
		Connection::query("
			UPDATE delivery SET
			request_service='{$service->serviceName}',
			request_subservice='{$service->request->subservice}',
			request_query='$safeQuery'
			WHERE id={$this->idEmail}");

		// return message for the log
		return "Email received and sent";
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
	 * Process the webhooks petitions and responds via email
	 */
	private function webhookProcessRequest()
	{
		// get the time when the service started executing and set the partition of the delivery table
		$this->execStartTime = date("Y-m-d H:i:s");
		$this->queryId = strval(random_int(100,999)).substr(strval(time()),4);

		// do not respond to blocked accounts
		$blocked = Connection::query("SELECT email FROM person WHERE email='{$this->fromEmail}' AND blocked=1");
		if($blocked) return false;

		// get the person's numeric ID
		$utils = new Utils();
		$this->fromEmailId = $utils->personExist($this->fromEmail);

		// get the environment from the email
		$email = str_replace(".", "", explode("+", explode("@", $this->toEmail)[0])[0]);
		$res = Connection::query("SELECT environment FROM delivery_input WHERE email='$email'");
		$environment = empty($res) ? "default" : $res[0]->environment;

		// set the running environment
		$this->di->set('environment', function() use($environment) {return $environment;});

		// if the app is reporting a failure
		if($environment == "failure") {
			$this->runFailure();
			return false;
		}

		// stop procesing if the sender is invalid
		if($environment != "app") { // no need to test for the app
			$status = $utils->deliveryStatus($this->fromEmail, $this->fromEmailId);
			if($status != 'ok') return $utils->createAlert("ALERT: {$this->fromEmail} failed with status $status");
		}

		// update the number of emails received
		Connection::query("UPDATE delivery_input SET received=received+1 WHERE email='$email'");

		// update last access time and make person active
		if ($this->fromEmailId) { //if person exists
			Connection::query("UPDATE person SET active=1, last_access=CURRENT_TIMESTAMP WHERE id={$this->fromEmailId}");
		}
		else {
			// create a unique username and save the new person
			$username = $utils->usernameFromEmail($this->fromEmail);
			$this->fromEmailId = Connection::query("
				INSERT INTO person (email, username, last_access, source)
				VALUES ('{$this->fromEmail}', '$username', CURRENT_TIMESTAMP, '$environment')");

			// add the welcome notification
			$utils->addNotification($this->fromEmail, "Bienvenido", "Bienvenido a Apretaste", "WEB bienvenido.apretaste.com");
		}

		// insert a new email in the delivery table and get the ID
		$attach = $this->attachment ? basename($this->attachment) : "";
		$domain = explode("@", $this->fromEmail)[1];
		$this->subject = substr($this->subject, 0, 1023);
		$this->idEmail = Connection::query("
			INSERT INTO delivery (id, id_person, request_date, mailbox, request_domain, environment, email_id, email_subject, email_body, email_attachments)
			VALUES ({$this->queryId}, {$this->fromEmailId}, '{$this->execStartTime}', '{$this->toEmail}', '$domain', '$environment', '{$this->messageId}', '{$this->subject}', '{$this->body}', '$attach')");

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
		$startedTime = new DateTime($this->execStartTime);
		$executionTime = $currentTime->diff($startedTime)->format('%H:%I:%S');
		Connection::query("UPDATE delivery SET process_time='$executionTime' WHERE id={$this->queryId}");

		// display the webhook log
		$wwwroot = $this->di->get('path')['root'];
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/webhook.log");
		$logger->log("$environment | From:{$this->fromEmail} To:{$this->toEmail} | $log");
		$logger->close();
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
		$this->parseEmail($temp."mails/".$keyname, "amazon.log");
	}

	/**
	 * Receives an email petition from Apretaste Webhook and responds via email
	 *
	 * @author ricardo@apretaste.org
	 * @param POST data from Apretaste webhook
	 */
	public function ApWebhookAction()
	{
		ignore_user_abort(true);
		// get data from our webhook
		$this->callAPWebhook();
		$this->webhookProcessRequest();
	}

	/**
	 * Read the email from the Apretaste webhook
	 */
	private function callAPWebhook(){
		// get the message object
		$message = json_decode(file_get_contents('php://input'), true);

		// get the temp folder
		$utils = new Utils();
		$temp = $utils->getTempDir();
		$file = $temp.'mails/'.$utils->generateRandomHash();
		file_put_contents($file, $message["data"]);
		
		$this->parseEmail($file, "ApWebhook.log");
	}

	private function parseEmail($emailPath, $log){
		$utils = new Utils();
		$temp = $utils->getTempDir();
		
		// parse the file
		$parser = new PhpMimeMailParser\Parser();
		$parser->setPath($emailPath);
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
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/$log");
		$logger->log("\nID:$messageId\nFROM:$fromEmail\nTO:$toEmail\nSUBJECT:$subject\n------------\n$body\n\n");
		$logger->close();

		// save the attachment to the temp folder
		if($attachs) {
			$att = $parser->saveAttachments($temp."attachments/");
			$ext = pathinfo($att[0], PATHINFO_EXTENSION);
			$newFile = $temp.'attachments/'.$utils->generateRandomHash().'.'.$ext;
			rename($att[0], $newFile);
			$this->attachment = $newFile;
		}

		// save variables in the class
		$this->fromName = $fromName;
		$this->fromEmail = $fromEmail;
		$this->toEmail = $toEmail;
		$this->subject = trim(preg_replace('/\s{2,}/', " ", preg_replace('/\'|`/', "", $subject)));
		$this->body = str_replace("'", "", $body);
	}
}
