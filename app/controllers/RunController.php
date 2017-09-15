<?php

use Phalcon\Mvc\Controller;

class RunController extends Controller
{
	/**
	 * Executes an html request the outside. Display the HTML on screen
	 *
	 * @author salvipascual
	 * @get String $subject, subject line of the email
	 * @get String $body, body of the email
	 */
	public function displayAction()
	{
		$email = "html@apretaste.com";
		$subject = $this->request->get("subject");
		$body = $this->request->get("body");

		// run the request and get the service and responses
		$utils = new Utils();
		$ret = $utils->runRequest($email, $subject, $body, array());
		$service = $ret->service;
		$responses = $ret->responses;

		// create a new render
		$render = new Render();

		// render the template and echo on the screen
		$html = "";
		for ($i=0; $i<count($responses); $i++)
		{
			// clean the empty fields in the response
			$rightEmail = empty($responses[$i]->email) ? $email : $responses[$i]->email;
			$subject = empty($responses[$i]->subject) ? "Respuesta del servicio $service->serviceName" : $responses[$i]->subject;

			$html .= "<br/><center><small><b>To:</b> " . $rightEmail . ". <b>Subject:</b> " . $subject . "</small></center><br/>";
			$html .= $render->renderHTML($service, $responses[$i]);
//			$html .= serialize($responses[$i]);
			if($i < count($responses)-1) $html .= "<br/><hr/><br/>";
		}

		// create the footer text
		$usage = nl2br(str_replace('{APRETASTE_EMAIL}', $service->utils->getValidEmailAddress(), $service->serviceUsage));
		$html .= "<br/><hr><center><p><b>XML DEBUG</b></p><small>";
		$html .= "<p><b>Owner: </b>{$service->creatorEmail}</p>";
		$html .= "<p><b>Category: </b>{$service->serviceCategory}</p>";
		$html .= "<p><b>Description: </b>{$service->serviceDescription}</p>";
		$html .= "<p><b>Usage: </b><br/>$usage</p></small></center>";

		// display the HTML
		die($html);
	}

	/**
	 * Send an email to the support group
	 *
	 * @author salvipascual
	 * @param POST Multiple Values
	 */
	public function supportAction()
	{
		// format the response when comes from mailgun
		$response = $this->formatMailgunWebhook($_POST);
		$fromEmail = trim($response->fromEmail);
		$subject = str_replace("'", "", $response->subject);
		$body = str_replace("'", "", $response->body);

		// do not allow empty queries
		if(empty($fromEmail)) return false;

		// save the new ticket into the database
		$connection = new Connection();
		$connection->query("INSERT INTO support_tickets (`from`, subject, body) VALUES ('$fromEmail', '$subject', '$body')");

		// save the new ticket in the reports table
		$mysqlDateToday = date("Y-m-d H:i:s");
		$connection->query("
			INSERT IGNORE INTO support_reports (inserted) VALUES ('$mysqlDateToday');
			UPDATE support_reports SET new_count = new_count+1 WHERE inserted = '$mysqlDateToday';");

		// save the support log
		$wwwroot = $this->di->get('path')['root'];
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/support.log");
		$logger->log("From:$fromEmail, Subject:$subject");
		$logger->close();

		// do not continue processing
		return true;
	}

	/**
	 * Executes an API request. Display the JSON on screen
	 *
	 * @author salvipascual
	 * @get String $subject, subject line of the email
	 * @get String $body, body of the email
	 * @get String $attachments
	 * @get String $token
	 */
	public function apiAction()
	{
		// allow JS clients to use the API
		header("Access-Control-Allow-Origin: *");

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
			$filePath = "$wwwroot/temp/".$utils->generateRandomHash().".jpg";

			// clean base64 string
			$data = explode(',', $attachment);
			$data = isset($data[1]) ? $data[1] : $data[0];

			// save base64 string as a JPG image
			$im = imagecreatefromstring(base64_decode($data));
			imagejpeg($im, $filePath);
			imagedestroy($im);

			// optimize the image and grant full permits
			$utils->optimizeImage($filePath);
			chmod($filePath, 0777);

			$attach[] = $filePath;
		}

		// create a new render
		$render = new Render();

		// run the request and get the service and first response
		$ret = $utils->runRequest($email, $subject, $body, $attach);
		$service = $ret->service;
		$response = $ret->responses[0];

		// respond by email, if there is an email to send
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
	 * Receives email from the webhook and parse it for the app
	 *
	 * @author salvipascual
	 * @param POST Multiple Values
	 */
	public function appAction()
	{
		// get the time when the service started executing
		$execStartTime = date("Y-m-d H:i:s");

		// make the system react in "mode app"
		$this->di->set('environment', function(){return "app";});

		// get the email params from the mailgun webhook
		$res = $this->formatMailgunWebhook($_POST);
		$fromEmail = $res->fromEmail;
		$toEmail = $res->toEmail;
		$ticket = $res->subject;
		$replyIdEmail = $res->messageId;
		$attachEmail = $res->attachments;
/*
		$fromEmail = "salvi.pascual@gmail.com";
		$toEmail = "apretaste@gmail.com";
		$ticket = "nobligonyu";
		$replyIdEmail = "09876543321";
		$attachEmail = array("/home/salvipascual/qwerty.zip");
*/
		// error if no attachment is received
		if(isset($attachEmail[0]) && file_exists($attachEmail[0])) {
			$attachEmail = $attachEmail[0];
		} else {
			$output = new stdClass();
			$output->code = "515";
			$output->message = "Error on attachment file";
			die(json_encode($output));
		}

		// do not continue procesing the email if the sender is not valid
		$utils = new Utils();
		$status = $utils->deliveryStatus($fromEmail, 'in');
		if($status != 'ok') die('{"code":"500", "message":"Error '.$status.' verifying email"}');

		// get path to the folder to save
		$textFile = ""; $attachs = array();
		$folderName = str_replace(".zip", "", basename($attachEmail));
		$temp = $utils->getTempDir();

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

		// save the new email in the database and get the ID
		$attachStr = implode(",", $attachs);
		$connection = new Connection();
		$idEmail = $connection->query("
			INSERT INTO delivery_received (user, mailbox, subject, messageid, attachments, webhook)
			VALUES ('$fromEmail', '$toEmail', '$ticket', '$replyIdEmail', '$attachStr', 'app')");

		// run the request and get the service and responses
		$file = file("$temp/$folderName/$textFile");
		$text = trim($file[0]);
		$version = empty($file[1]) ? "" : trim($file[1]);
		$nautaPass = empty($file[2]) ? false : base64_decode(trim($file[2]));

		// save Nauta password if passed
		if($nautaPass) {
			$encryptPass = $utils->encrypt($nautaPass);
			$connection->query("
				DELETE FROM authentication WHERE email = '$fromEmail' AND appname = 'apretaste';
				INSERT INTO authentication (email, pass, appname, platform) VALUES ('$fromEmail', '$encryptPass', 'apretaste', 'android');");
		}

		// update last access time to current and make person active
		$personExist = $utils->personExist($fromEmail);
		if ($personExist) {
			$connection->query("UPDATE person SET active=1, appversion='$version', last_access=CURRENT_TIMESTAMP WHERE email='$fromEmail'");
		} else {
			// create a unique username and save the new person
			$username = $utils->usernameFromEmail($fromEmail);
			$connection->query("INSERT INTO person (email, username, last_access, source, appversion) VALUES ('$fromEmail', '$username', CURRENT_TIMESTAMP, 'app', '$version')");
		}

		$ret = $utils->runRequest($fromEmail, $text, '', $attachs);
		$service = $ret->service;
		$response = $ret->responses[0];

		// save the apps log
		$hasNautaPass = $nautaPass ? 1 : 0;
		$wwwroot = $this->di->get('path')['root'];
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/app.log");
		$logger->log("From:$fromEmail, To:$toEmail, Text:$text, Ticket:$ticket, Version:$version, NautaPass:$hasNautaPass");
		$logger->close();

		// send email if can be rendered
		if($response->render) {
			// set the layout to blank
			$response->setEmailLayout('email_text.tpl');

			// is there is a cache time, add
			if($response->cache) {
				$cache = "$temp{$response->cache}.cache";
				file_put_contents($cache, "");
				$response->attachments[] = $cache;
			}

			// render the HTML
			$render = new Render();
			$body = $render->renderHTML($service, $response);

			// get notifications since last update
			$notifications = $connection->query("
				SELECT id, `text`, origin AS service, link, inserted_date AS received
				FROM notifications
				WHERE email='$fromEmail' AND viewed=0
				ORDER BY inserted_date DESC LIMIT 20");

			// create attached file for notifications
			if($notifications) {
				// mark pulled notifications as read
				$notifID = array();
				foreach ($notifications as $n) {$notifID[] = $n->id; unset($n->id);}
				$notifID = implode(",", $notifID);
				$connection->query("UPDATE notifications SET viewed=1, viewed_date=CURRENT_TIMESTAMP WHERE id IN ($notifID)");

				// create an attachment file for the notifications
				$ntfFile = $temp . substr(md5(date('dHhms') . rand()), 0, 8) . ".ntf";
				file_put_contents($ntfFile, json_encode($notifications));
				$response->attachments[] = $ntfFile;
			}

			// prepare and send the email
			$email = new Email();
			$email->id = $idEmail;
			$email->to = $fromEmail;
			$email->subject = $ticket;
			$email->body = $body;
			$email->replyId = $replyIdEmail;
			$email->group = $service->group;
			$email->images = $response->images;
			$email->attachments = $response->attachments;
			$email->app = true;
			$email->setContentAsZipAttachment();
			$output = $email->send();

			// add code & message to transaction
			$connection->query("
				UPDATE delivery_received SET
				code='{$output->code}', message='{$output->message}', sent=CURRENT_TIMESTAMP
				WHERE id='$idEmail'");
		}

		// calculate execution time when the service stopped executing
		$currentTime = new DateTime();
		$startedTime = new DateTime($execStartTime);
		$executionTime = $currentTime->diff($startedTime)->format('%H:%I:%S');

		// get the user email domainEmail
		$emailPieces = explode("@", $fromEmail);
		$domain = $emailPieces[1];

		// save the logs on the utilization table
		$safeQuery = $connection->escape($service->request->query);
		$connection->query("
			INSERT INTO utilization	(email_id, service, subservice, query, requestor, request_time, response_time, domain)
			VALUES ('$idEmail','{$service->serviceName}','{$service->request->subservice}','$safeQuery','$fromEmail','$execStartTime','$executionTime','$domain')");

		return true;
	}

	/**
	 * Receives email from the webhook and parse it for the email tool
	 *
	 * @author salvipascual
	 * @param POST Multiple Values
	 */
	public function mailgunAction()
	{
		// get the time when the service started executing
		$execStartTime = date("Y-m-d H:i:s");

		// get the email params from the mailgun webhook
		$res = $this->formatMailgunWebhook($_POST);
		$fromEmail = $res->fromEmail;
		$toEmail = $res->toEmail;
		$subjectEmail = $res->subject;
		$bodyEmail = $res->body;
		$replyIdEmail = $res->messageId;
		$attachEmail = $res->attachments;

		// do not continue procesing the email if the sender is not valid
		$utils = new Utils();
		$status = $utils->deliveryStatus($fromEmail, 'in');
		if($status != 'ok') return;

		// save the new email in the database and get the ID
		$connection = new Connection();
		$attachStr = implode(",", $attachEmail);
		$idEmail = $connection->query("
			INSERT INTO delivery_received (user, mailbox, subject, body, messageid, attachments)
			VALUES ('$fromEmail', '$toEmail', '$subjectEmail', '$bodyEmail', '$replyIdEmail', '$attachStr')");

		// save the webhook log
		$wwwroot = $this->di->get('path')['root'];
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/webhook.log");
		$logger->log("From:{$fromEmail}, To:{$toEmail}, Subject:{$subjectEmail}");
		$logger->close();

		// if the person exist in Apretaste
		$personExist = $utils->personExist($fromEmail);
		if ($personExist)
		{
			// update last access time to current and make person active
			$connection->query("UPDATE person SET active=1, last_access=CURRENT_TIMESTAMP WHERE email='$fromEmail'");
		}
		else // if the person accessed for the first time, insert him/her
		{
			$inviteSource = 'alone'; // alone if the user came by himself, no invitation
			$sql = "START TRANSACTION;"; // start the long query

			// check if the person was invited to Apretaste
			$invites = $connection->query("SELECT * FROM invitations WHERE email_invited='$fromEmail' AND used=0 ORDER BY invitation_time DESC");
			if(count($invites)>0)
			{
				// check how this user came to know Apretaste, for stadistics
				$inviteSource = $invites[0]->source;

				// give prizes to the invitations via service invitar
				// if more than one person invites X, they all get prizes
				foreach ($invites as $invite)
				{
					switch($invite->source)
					{
						case "internal":
						// assign tickets and credits
						$sql .= "INSERT INTO ticket (email, origin) VALUES ('{$invite->email_inviter}', 'RAFFLE');";
						$sql .= "UPDATE person SET credit=credit+0.25 WHERE email='{$invite->email_inviter}';";

						// email the invitor
						$newTicket = new Response();
						$newTicket->setResponseEmail($invite->email_inviter);
						$newTicket->setEmailLayout("email_simple.tpl");
						$newTicket->setResponseSubject("Ha ganado un ticket para nuestra Rifa");
						$newTicket->createFromTemplate("invitationWonTicket.tpl", array("guest"=>$fromEmail));
						$newTicket->internal = true;
						$responses[] = $newTicket;
						break;

						case "abroad":
						$newGuest = new Response();
						$newGuest->setResponseEmail($invite->email_inviter);
						$newGuest->setResponseSubject("Tu amigo ha atendido tu invitacion");

						$inviter = $utils->usernameFromEmail($invite->email_inviter);
						$pInviter = $utils->getPerson($invite->email_inviter);
						if (!isset($pInviter->name)) $pInviter->name = '';
						if ($pInviter !== false) if (trim($pInviter->name) !== '') $inviter = $pInviter->name;

						$pGuest = $utils->getPerson($fromEmail);
						$guest = $fromEmail;
						if ($pGuest) $guest = $pGuest->username;

						$newGuest->createFromTemplate("invitationNewGuest.tpl", array("inviter"=>$inviter, "guest"=>$guest, "guest_email" => $fromEmail));
						$newGuest->internal = true;
						$responses[] = $newGuest;
						break;
					}
				}

				// mark all opened invitations to that email as used
				$sql .= "UPDATE invitations SET used=1, used_time=CURRENT_TIMESTAMP WHERE email_invited='$fromEmail' AND used=0;";
			}

			// create a unique username and save the new person
			$username = $utils->usernameFromEmail($fromEmail);
			$sql .= "INSERT INTO person (email, username, last_access, source) VALUES ('$fromEmail', '$username', CURRENT_TIMESTAMP, '$inviteSource');";

			// save details of first visit
			$sql .= "INSERT INTO first_timers (email, source) VALUES ('$fromEmail', '$fromEmail');";

			// check list of promotor's emails
			$promoters = $connection->query("SELECT email FROM promoters WHERE email='$fromEmail' AND active=1;");
			$prize = count($promoters)>0;
			if ($prize)
			{
				// update the promotor
				$sql .= "UPDATE promoters SET `usage`=`usage`+1, last_usage=CURRENT_TIMESTAMP WHERE email='$fromEmail';";
				// add credit and tickets
				$sql .= "UPDATE person SET credit=credit+5, source='promoter' WHERE email='$fromEmail';";
				$sqlValues = "('$fromEmail', 'PROMOTER')";
				$sql .= "INSERT INTO ticket(email, origin) VALUES " . str_repeat($sqlValues . ",", 9) . "$sqlValues;";
			}

			// run the long query all at the same time
			$connection->query($sql."COMMIT;");

			// send the welcome email
			$welcome = new Response();
			$welcome->setResponseEmail($fromEmail);
			$welcome->setEmailLayout("email_simple.tpl");
			$welcome->setResponseSubject("Bienvenido a Apretaste");
			$welcome->createFromTemplate("welcome.tpl", array("email"=>$fromEmail, "prize"=>$prize, "source"=>$fromEmail));
			$welcome->internal = true;
			$responses[] = $welcome;
		}

		// run the request and get the service and responses
		$ret = $utils->runRequest($fromEmail, $subjectEmail, $bodyEmail, array());
		$service = $ret->service;
		$responses = $ret->responses;

		// create the new Email object
		$email = new Email();
		$email->id = $idEmail;
		$email->to = $fromEmail;
		$email->replyId = $replyIdEmail;
		$email->group = $service->group;

		// create a new Render object
		$render = new Render();

		// get params for the email and send the response emails
		foreach($responses as $rs)
		{
			// render the email
			if($rs->render) // ommit default Response()
			{
				// save impressions in the database
				$ads = $rs->getAds();
				if($service->showAds && ! empty($ads))
				{
					$sql = "";
					if( ! empty($ads[0])) $sql .= "UPDATE ads SET impresions=impresions+1 WHERE id='{$ads[0]->id}';";
					if( ! empty($ads[1])) $sql .= "UPDATE ads SET impresions=impresions+1 WHERE id='{$ads[1]->id}';";
					$connection->query($sql);
				}

				// prepare and send the email
				if($rs->email) $email->to = $rs->email;
				$email->subject = $rs->subject;
				$email->images = $rs->images;
				$email->attachments = $rs->attachments;
				$email->body = $render->renderHTML($service, $rs);
				$email->send();
			}
			// for the requests that don't send emails back to the user
			else
			{
				// mark email as done so we don't run it again
				$connection->query("UPDATE delivery_received SET status='done', sent=CURRENT_TIMESTAMP WHERE id='$idEmail'");
			}
		}

		// saves the openning date if the person comes from remarketing
		$connection->query("UPDATE remarketing SET opened=CURRENT_TIMESTAMP WHERE opened IS NULL AND email='$fromEmail'");

		// calculate execution time when the service stopped executing
		$currentTime = new DateTime();
		$startedTime = new DateTime($execStartTime);
		$executionTime = $currentTime->diff($startedTime)->format('%H:%I:%S');

		// get the user email domainEmail
		$emailPieces = explode("@", $fromEmail);
		$domain = $emailPieces[1];

		// get the top and bottom Ads
		$ads = isset($responses[0]->ads) ? $responses[0]->ads : array();
		$adTop = isset($ads[0]) ? $ads[0]->id : "NULL";
		$adBottom = isset($ads[1]) ? $ads[1]->id : "NULL";

		// save the logs on the utilization table
		$safeQuery = $connection->escape($service->request->query);
		$connection->query("
			INSERT INTO utilization (email_id, service, subservice, query, requestor, request_time, response_time, domain, ad_top, ad_bottom)
			VALUES ('$idEmail','{$service->serviceName}','{$service->request->subservice}','$safeQuery','$fromEmail','$execStartTime','$executionTime','$domain',$adTop,$adBottom)");
		return true;
	}

	/**
	 * Get the POST from MailGun and return the array of data
	 *
	 */
	private function formatMailgunWebhook($post)
	{
		// do not allow fake income messages
		if( ! isset($post['From'])) return false;

		// check where to acquire the field "To"
		$to = "";
		if(isset($post['To'])) $to = $post['To'];
		if(empty($to) && isset($post['Delivered-To'])) $to = $post['Delivered-To'];
		if(empty($to) && isset($post['Received'])) $to = $post['Received'];

		// filter email From and To
		$pattern = "/(?:[a-z0-9!#$%&'*+=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+=?^_`{|}~-]+)*|\"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*\")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/";
		preg_match_all($pattern, strtolower($post['From']), $emailFrom);
		preg_match_all($pattern, strtolower($to), $emailTo);

		// get values to the variables
		$fromEmail = $emailFrom[0][0];
		$toEmail = empty($emailTo[0][0]) ? "" : $emailTo[0][0];
		$fromName = trim(explode("<", $post['From'])[0]);
		$subject = $post['subject'];
		$body = isset($post['body-plain']) ? $post['body-plain'] : "";
		$attachmentCount = isset($post['attachment-count']) ? $post['attachment-count'] : 0;

		// clean incoming emails
		$fromEmail = str_replace("'", "", $fromEmail);
		$toEmail = str_replace("'", "", $toEmail);

		// treat emails to download the app
		// @TODO @HACK this is a hack and should not stay long term
		if($toEmail == "navegacuba@gmail.com") $subject = "app";

		// obtain the ID of the message to make it "respond" to the email
		$messageID = null;
		foreach($_POST as $k => $v){
			$k = strtolower($k);
			if ($k == 'message-id' || $k == 'messageid' || $k == 'id'){
				$messageID = $v;
				break;
			}
		}

		// download attachments to the temp folder
		$utils = new Utils();
		$wwwroot = $this->di->get('path')['root'];
		$attachments = array();
		for ($i=1; $i<=$attachmentCount; $i++)
		{
			// get the path for the image
			$originFilePath = $_FILES["attachment-$i"]["tmp_name"];
			$mimeTypePieces = explode("/", mime_content_type($originFilePath));
			$fileNameNoExtension = $utils->generateRandomHash();

			// convert images to jpg and save it to temporal
			if($mimeTypePieces[0] == "image")
			{
				$tmpfilePath = "$wwwroot/temp/$fileNameNoExtension.jpg";
				imagejpeg(imagecreatefromstring(file_get_contents($originFilePath)), $tmpfilePath);
				$utils->optimizeImage($tmpfilePath);
			}
			else // save any other file to the temporals
			{
				$tmpfilePath = "$wwwroot/temp/$fileNameNoExtension.$mimeTypePieces[1]";
				copy($originFilePath, $tmpfilePath);
			}

			// grant full access to the file
			chmod($tmpfilePath, 0777);
			$attachments[] = $tmpfilePath;
		}

		// remove weird chars and apostrophes that break the SQL code
		$subject = trim(preg_replace('/\s{2,}/', " ", preg_replace('/\'|`/', "", $subject)));
		$body = str_replace("'", "", $body);
		$messageID = str_replace("'", "", $messageID);

		// save the mailgun log
		$wwwroot = $this->di->get('path')['root'];
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/mailgun.log");
		$logger->log("From:$fromEmail, To:$toEmail, Subject:$subject\n".print_r($_POST, true)."\n\n");
		$logger->close();

		// respond with info
		$response = new stdClass();
		$response->fromEmail = $fromEmail;
		$response->fromName = $fromName;
		$response->toEmail = $toEmail;
		$response->subject = $subject;
		$response->body = $body;
		$response->attachments = $attachments;
		$response->messageId = $messageID;
		return $response;
	}
}
