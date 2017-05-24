<?php

use Mailgun\Mailgun;
use Nette\Mail\Message;

class Email
{
	public $id;
	public $from;
	public $to;
	public $subject;
	public $body;
	public $replyId; // id to reply
	public $attachments = array(); // array of paths
	public $images = array(); // array of paths
	public $group = 'apretaste';
	public $status = "new"; // new, sent, bounced
	public $message;
	public $tries = 0;
	public $created; // date
	public $sent; // date

	/**
	 * Select a provider automatically and send an email
	 * @author salvipascual
	 */
	public function send()
	{
		// validate email before sending
		$utils = new Utils();
		$status = $utils->deliveryStatus($this->to);
		if($status != 'ok') return false;

		// send via Nodes if the receipient is from Cuba
		if(substr($this->to, -3) === ".cu")
		{
			$res = $this->sendEmailViaNode($this->to, $this->subject, $this->body, $this->images, $this->attachments);
			$this->from = $res->email->from;
		}
		// respond via Amazon to recipients outside Cuba
		else
		{
			$this->from = 'Apretaste <noreply@apretaste.com>';
			$res = $this->sendEmailViaAmazon($this->from, $this->to, $this->subject, $this->body, $this->images, $this->attachments);
		}

		// update the object
		$this->tries++;
		$this->message = $res->message;
		$this->status = $res->code == "200" ? "sent" : "error";
		if($res->code == "200") $this->sent = date("Y-m-d H:i:s");

		// update the database with the email sent
		$connection = new Connection();
		$sentDate = $res->code == "200" ? "sent=CURRENT_TIMESTAMP," : "";
		$connection->query("UPDATE delivery_received SET $sentDate status='{$this->status}', message='{$this->message}', tries=tries+1 WHERE id='{$this->id}'");

		// save a trace that the email was sent
		if($res->code == "200")
		{
			$attachments = count($this->attachments);
			$images = count($this->images);
			$connection->query("
				INSERT INTO delivery_sent (mailbox, user, subject, images, attachments, `group`, origin)
				VALUES ('{$this->from}','{$this->to}','{$this->subject}','$images','$attachments','{$this->group}','{$this->id}')");
		}

		// return {code, message, email} structure
		return $res;
	}

	/**
	 * Overload of the send () function for backward compatibility
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
		$this->to = $to;
		$this->subject = $subject;
		$this->body = $body;
		$this->images = $images;
		$this->attachments = $attachments;
		return $this->send();
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
		$connection->query("UPDATE nodes_output SET daily=0 WHERE DATE(last_sent) < DATE(CURRENT_TIMESTAMP)");
		$nodes = $connection->query("
			SELECT * FROM nodes_output A JOIN nodes B
			ON A.node = B.`key`
			WHERE A.active = '1'
			AND `group` LIKE '%{$this->group}%'
			AND A.`limit` > A.daily
			AND (A.blocked_until IS NULL OR CURRENT_TIMESTAMP >= A.blocked_until)");

		// get your personal email
		$percent = 0; $node = NULL;
		$user = str_replace(array(".","+"), "", explode("@", $to)[0]);
		foreach ($nodes as $n) {
			$temp = str_replace(array(".","+"), "", explode("@", $n->email)[0]);
			similar_text ($temp, $user, $p);
			if($p > $percent) {
				$percent = $p;
				$node = $n;
			}
		}

		// alert the team if no Node could be used
		$utils = new Utils();
		if(empty($node)) {
			$output = new stdClass();
			$output->code = "515";
			$output->message = "NODE: No active node to email $to";
			$utils->createAlert($output->message, "ERROR");
			return $output;
		}

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

		// create the email array request
		$params['key'] = $node->key;
		$params['from'] = $node->email;
		$params['host'] = $node->host;
		$params['user'] = $node->user;
		$params['pass'] = $node->pass;
		$params['id'] = $this->id;
		$params['messageid'] = $this->replyId;
		$params['to'] = $to;
		$params['subject'] = $subject;
		$params['body'] = base64_encode($body);
		$params['attachments'] = serialize($attachmentsToUpload);
		$params['images'] = serialize($imagesToUpload);

		// contact the Sender to send the email
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "{$node->ip}/send.php");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$output = json_decode(curl_exec($ch));
		curl_close ($ch);

		// hanle errors
		if($output->code != "" && $output->code != "200") {
			// insert in drops emails and add 24h of waiting time
			$blockedUntil = date("Y-m-d H:i:s", strtotime("+24 hours"));
			$connection->query("
				UPDATE nodes_output SET blocked_until='$blockedUntil' WHERE email = '{$node->email}';
				INSERT INTO delivery_dropped(email,sender,reason,`code`,description)
				VALUES ('$to','{$node->email}','failed','{$output->code}','{$output->message}');");

			// alert error message if an error happens
			$errMsg = "NODE: Sending failed: {$output->message} FROM {$node->email} TO $to with ID {$this->id}";
			$utils->createAlert($errMsg, "ERROR");
		}else{
			// update delivery time
			$connection->query("UPDATE nodes_output SET daily=daily+1, sent=sent+1, last_sent=CURRENT_TIMESTAMP WHERE email='{$node->email}'");
		}

		return $output;
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

		// create the structure to return
		$output = new stdClass();
		$output->code = 200;
		$output->message = "";

		// send email
		try{
			$ses = new SimpleEmailService($accessKey, $secretKey);
			$ses->sendEmail($m);
		} catch (Exception $e) {
			$utils = new Utils();
			$msg = "AMAZON: Error sending from: $from to $to with subject $subject and error: ".$e->getMessage();
			$utils->createAlert($msg, "ERROR");
			$output->code = "500";
			$output->message = $e->getMessage();
		}

		return $output;
	}
}
