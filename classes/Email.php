<?php

use Mailgun\Mailgun;
use Nette\Mail\Message;

class Email
{
	public $group = 'apretaste';
	public $messageid = NULL; // ID of the email to create a reply
	public $from = NULL; // email of whom contacted Apretaste
	public $domain = false; // force a domain for test purposes

	/**
	 * Sends an email using MailGun
	 *
	 * @author salvipascual
	 * @param String $to, email address of the receiver
	 * @param String $subject, subject of the email
	 * @param String $body, body of the email in HTML
	 * @param Array $images, paths to the images to embeb
	 * @param Array $attachments, paths to the files to attach
	 */
	public function sendEmail($to, $subject, $body, $images=array(), $attachments=array())
	{
		// do not email if there is an error
		$utils = new Utils();
		$status = $utils->deliveryStatus($to);
		if($status != 'ok') return false;

		// never send emails from the sandbox
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		if($di->get('environment') == "sandbox") return true;
		$connection = new Connection();

		// if the recepient is from cuba, rotate
		if(substr($to, -9) === "@nauta.cu")
		{
			$response = $this->sendEmailViaNode($to, $subject, $body, $images, $attachments);
			$this->domain = isset($response->email->id) ? $response->email->id : "";
			$from = isset($response->email->from) ? $response->email->from : "";
			$provider = "node";
		}
		// for all other non-Nauta Cuban accounts
		elseif(substr($to, -3) === ".cu")
		{
			// get the name and domain from the email
			$emailParts = explode("@", $to);
			$emailName = $emailParts[0];
			$emailDomain = $emailParts[1];

			// get the right domain
			$result = $connection->query("
				SELECT domain, sender
				FROM domain
				WHERE active = 1
				AND sender <> 'gmail'
				AND blacklist NOT LIKE '%$emailDomain%'
				ORDER BY last_usage LIMIT 1");

			// error if no domain can be selected
			if(empty($result)) return $utils->createAlert("No provider to respond to $to with subject $subject", "ERROR");

			// create a random email $from
			$this->domain = $result[0]->domain;
			$provider = $result[0]->sender;
			$user = str_replace(array(".", "+", "-"), "", $emailName);
			$seed = rand(80, 98);
			$from = "{$user}{$seed}@{$this->domain}";

			// send the email using the provider
			if($provider == "mailgun") $this->sendEmailViaMailgun($from, $to, $subject, $body, $images, $attachments);
			elseif($provider == "amazon") $this->sendEmailViaAmazon($from, $to, $subject, $body, $images, $attachments);
		}
		// respond to recipients outside Cuba
		else
		{
			$provider = "amazon";
			$this->domain = "apretaste.com";
			$from = 'Apretaste <noreply@apretaste.com>';
			$this->sendEmailViaAmazon($from, $to, $subject, $body, $images, $attachments);
		}

		// save a trace that the email was sent AND increase the send counter for the domain
		$haveImages = empty($images) ? 0 : 1;
		$haveAttachments = empty($attachments) ? 0 : 1;
		$connection->query("
			INSERT INTO delivery_sent (mailbox,user,subject,images,attachments,domain,`group`,type)
			VALUES ('$from','$to','$subject','$haveImages','$haveAttachments','{$this->domain}','{$this->group}','$provider');
			UPDATE domain
			SET sent_count=sent_count+1, last_usage=CURRENT_TIMESTAMP
			WHERE domain='{$this->domain}'");

		return true;
	}

	/**
	 * Set the id to respond to an email. This is important
	 * to create conversations when replying to an email
	 *
	 * @author salvipascual
	 * @param String $id
	 * */
	public function setRespondEmailID($messageid)
	{
		$this->messageid = $messageid;
	}

	/**
	 * Force to use an specific domain
	 *
	 * @author salvipascual
	 * @param String $domain
	 * */
	public function setDomain($domain)
	{
		$this->domain = $domain;
	}

	/**
	 * Set the group to respond
	 *
	 * @author salvipascual
	 * @param String $group
	 * */
	public function setGroup($group)
	{
		$this->group = $group;
	}

	/**
	 * Sends an email using Mailgun
	 *
	 * @author salvipascual
	 * @return Boolean
	 */
	private function sendEmailViaMailgun($from, $to, $subject, $body, $images, $attachments)
	{
		// create the list of images and attachments
		$embedded = array();
		if( ! empty($images)) $embedded['inline'] = $images;
		if( ! empty($attachments)) $embedded['attachment'] = $attachments;

		// create the array send
		$message = array(
			"from" => $from,
			"to" => $to,
			"subject" => $subject,
			"html" => $body,
			"o:native-send" => true,
			"o:tracking" => false,
			"o:tracking-clicks" => false,
			"o:tracking-opens" => false
		);

		// adding In-Reply-To header (creating conversation with the user)
		if ($this->messageid) $message["h:In-Reply-To"] = $this->messageid;

		// get the API key and start MailGun client
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$mailgunKey = $di->get('config')['mailgun']['key'];
		$mgClient = new Mailgun($mailgunKey);

		// clear the email from the bounce list. We take will care of bad emails
		try{$mgClient->delete("{$this->domain}/bounces/$to");} catch(Exception $e){}

		// send email
		try{
			$mgClient->sendMessage($this->domain, $message, $embedded);
		} catch (Exception $e) {
			$utils = new Utils();
			$msg = "MAIGUN: Error sending from: $from to $to with subject $subject and error: ".$e->getMessage();
			return $utils->createAlert($msg, "ERROR");
		}

		return true;
	}

	/**
	 * Sends an email using our of our external nodes
	 *
	 * @author salvipascual
	 * @return Boolean
	 */
	private function sendEmailViaNode($to, $subject, $body, $images, $attachments)
	{
		// get the right node to use
		$connection = new Connection();
		$connection->query("UPDATE nodes SET daily=0 WHERE DATE(last_sent) < DATE(CURRENT_TIMESTAMP)");
		$nodes = $connection->query("
			SELECT * FROM nodes
			WHERE active = '1'
			AND `limit` > daily
			AND (blocked_until IS NULL OR CURRENT_TIMESTAMP >= blocked_until)");

		// get your personal email
		$percent = 0; $node = NULL;
		$user = str_replace(array(".","+"), "", explode("@", $to)[0]);
		foreach ($nodes as $n) {
			if($n->limit <= $n->daily) continue;
			$temp = str_replace(array(".","+"), "", explode("@", $n->from)[0]);
			similar_text ($temp, $user, $p);
			if($p > $percent) {
				$percent = $p;
				$node = $n;
			}
		}

		// alert the team if no Node could be used
		$utils = new Utils();
		if(empty($node)) return $utils->createAlert("NODE: No active node to email $to", "ERROR");

		// transform images to base64
		$imagesToUpload = array();
		foreach ($images as $image) {
			$item = new stdClass();
			$item->type = mime_content_type($image);
			$item->name = basename($image);
			$item->content = base64_encode(file_get_contents($image));
			$imagesToUpload[] = $item;
		}

		// transform attachments to base64
		$attachmentsToUpload = array();
		foreach ($attachments as $attachment) {
			$item = new stdClass();
			$item->type = mime_content_type($attachment);
			$item->name = basename($attachment);
			$item->content = base64_encode(file_get_contents($attachment));
			$attachmentsToUpload[] = $item;
		}

		// create transaction ID
		$id = str_replace(array("+",".","@","com"), "", $node->from).date("ymd").rand();

		// create the email array request
		$params['key'] = $node->key;
		$params['from'] = $node->from;
		$params['host'] = $node->host;
		$params['user'] = $node->user;
		$params['pass'] = $node->pass;
		$params['id'] = $id;
		$params['messageid'] = $this->messageid;
		$params['to'] = $to;
		$params['subject'] = $subject;
		$params['body'] = base64_encode($body);
		$params['attachments'] = serialize($attachmentsToUpload);
		$params['images'] = serialize($imagesToUpload);

		// contact the Sender to send the email
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "{$node->node}/send.php");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$output = json_decode(curl_exec($ch));
		curl_close ($ch);

		// hanle errors
		if($output->code != "" && $output->code != "200") {
			// alert error message if an error happens
			$errMsg = "NODE: Sending failed: {$output->message} FROM {$node->from} TO $to with ID $id";
			$utils->createAlert($errMsg, "ERROR");

			// when error, block for 24H and add one strike
			$blockedUntil = date("Y-m-d H:i:s", strtotime("+24 hours"));
			$connection->query("UPDATE nodes SET blocked_until='$blockedUntil', tries=tries+1 WHERE `from` = '{$node->from}'");

			// insert in drops emails and add 24h of waiting time
			$connection->query("
				INSERT INTO delivery_dropped(email,sender,reason,`code`,description)
				VALUES ('$to','{$node->from}','failed','{$output->code}','{$output->message}');");
		}else{
			// update delivery time
			$connection->query("UPDATE nodes SET daily=daily+1, sent=sent+1, last_sent=CURRENT_TIMESTAMP WHERE `from`='{$node->from}'");
		}

		return $output;
	}

	/**
	 * Sends an email using Gmail
	 *
	 * @author salvipascual
	 * @return Boolean
	 */
	private function sendEmailViaGmail($to, $subject, $body, $images, $attachments)
	{
		// get all available gmail domains
		$connection = new Connection();
		$gmails = $connection->query("SELECT domain FROM domain WHERE sender = 'gmail' AND active = '1'");

		// get the user part of the email
		$username = str_replace(array(".","+"), "", explode("@", $to)[0]);

		// get the most similar domain on the list
		$percent = 0;
		foreach ($gmails as $gmail) {
			$domain = explode("+", $gmail->domain)[1];
			similar_text ($domain, $username, $p);
			if($p > $percent) {
				$percent = $p;
				$this->domain = $gmail->domain;
			}
		}

		// get username and password
		$username = str_replace(".", "", explode("+", $this->domain)[0]);
		$password = $connection->query("SELECT password FROM gmail WHERE name = '$username'")[0]->password;

		// create mailer
		$from = "{$this->domain}@gmail.com";
		$mailer = new Nette\Mail\SmtpMailer([
			'host' => 'smtp.gmail.com',
			'username' => $username,
			'password' => $password,
			'secure' => 'ssl'
		]);

		// create message
		$mail = new Message;
		$mail->setFrom($from);
		$mail->addTo($to);
		$mail->setSubject($subject);
		$mail->setHtmlBody($body);
		$mail->setReturnPath($from);
		$mail->setHeader('Sender', $from);
		$mail->setHeader('In-Reply-To', $this->messageid);
		$mail->setHeader('References', $this->messageid);

		// add images to the template
		foreach ($images as $image) {
			$mail->addEmbeddedFile($image);
		}

		// add attachments
		foreach ($attachments as $attachment) {
			$mail->addAttachment($attachment);
		}

		// send email
		try{
			$mailer->send($mail, false);
		} catch (Exception $e) {
			$utils = new Utils();
			$msg = "GMAIL: Error sending from: $from to $to with subject $subject and error: ".$e->getMessage();
			return $utils->createAlert($msg, "ERROR");
		}

		return true;
	}

	/**
	 * Sends an email using Amazon SES
	 *
	 * @author salvipascual
	 * @return Boolean
	 */
	public function sendEmailViaAmazon($from, $to, $subject, $body, $images, $attachments)
	{
		// clean special characters from the subject and shorten to 100 characters
		$subject = substr(preg_replace('/[^A-Za-z0-9\- ]/', '', $subject), 0, 100);

		// prepare email to be sent
		$m = new SimpleEmailServiceMessage();
		$m->addTo($to);
		$m->setFrom($from);
		$m->setSubject($subject);
		$m->setMessageFromString('Su cliente de correo no acepta HTML', $body);

		// set the encoding of the subject and the body
		$m->setSubjectCharset('ISO-8859-1');
		$m->setMessageCharset('ISO-8859-1');

		// embebbed images
		foreach ($images as $path) {
			$fileName = basename($path);
			$m->addAttachmentFromFile($fileName,$path,'application/octet-stream',"<$fileName>",'inline');
		}

		// add attachments
		foreach ($attachments as $path) {
			$mimeType = mime_content_type($path);
			$fileName = basename($path);
			$m->addAttachmentFromFile($fileName, $path, $mimeType);
		}

		// get the API key and start MailGun client
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$accessKey = $di->get('config')['amazon']['access'];
		$secretKey = $di->get('config')['amazon']['secret'];

		// send email
		try{
			$ses = new SimpleEmailService($accessKey, $secretKey);
			$ses->sendEmail($m);
		} catch (Exception $e) {
			$utils = new Utils();
			$msg = "AMAZON: Error sending from: $from to $to with subject $subject and error: ".$e->getMessage();
			return $utils->createAlert($msg, "ERROR");
		}

		return true;
	}
}
