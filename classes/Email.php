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
	 * @return {"code", "message"}
	 */
	public function send()
	{
		// validate email before sending
		$utils = new Utils();
		$status = $utils->deliveryStatus($this->to);
		if($status != 'ok') return false;

		// check if the email is from Nauta or Cuba
		$isNauta = substr($this->to, -9) === "@nauta.cu";
		$isCuba = substr($this->to, -3) === ".cu";

		// if comes from Piropazo or Pizarra, and is Cuban
		if($isCuba && $this->group == 'social')
		{
			$res = $this->sendEmailViaPostmark();
		}
		// send via Nodes to Nauta
		elseif($isNauta)
		{
			$this->subject = $utils->randomSentence();
			$res = $this->sendEmailViaNode();
		}
		// send using aliases for all other Cuban emails
		elseif($isCuba)
		{
			$res = $this->sendEmailViaAlias();
		}
		// respond via Amazon to recipients outside Cuba
		else
		{
			$this->from = 'Apretaste <noreply@apretaste.com>';
			$res = $this->sendEmailViaAmazon();
		}

		// update the object
		$this->tries++;
		$this->message = str_replace("'", "", $res->message); // single quotes break the SQL
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
			$connection->query("INSERT INTO delivery_sent (mailbox, user, subject, images, attachments, `group`, origin) VALUES ('{$this->from}','{$this->to}','{$this->subject}','$images','$attachments','{$this->group}','{$this->id}')");
		}
		// save a trace that the email failed and alert
		else
		{
			$connection->query("INSERT INTO delivery_dropped (email,sender,reason,`code`,description) VALUES ('{$this->to}','{$this->from}','failed','{$res->code}','{$this->message}')");
			$utils->createAlert("Sending failed MESSAGE:{$res->message} | FROM:{$this->from} | TO:{$this->to} | ID:{$this->id}", "ERROR");
		}

		// return {code, message} structure
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
	 * @return {"code", "message"}
	 */
	public function sendEmailViaNode()
	{
		// every new day set the daily counter back to zero
		$connection = new Connection();
		$connection->query("UPDATE nodes_output SET daily=0 WHERE DATE(last_sent) < DATE(CURRENT_TIMESTAMP)");

		// get the node of the from address
		if($this->from) {
			$node = $connection->query("SELECT * FROM nodes_output A JOIN nodes B ON A.node = B.key WHERE A.email = '{$this->from}'");
			if(isset($node[0])) $node = $node[0];
		}
		// if no from is passed, calculate
		else {
			// get the date of the last test
			$lastTest = $connection->query("SELECT inserted FROM test ORDER BY inserted DESC LIMIT 1")[0]->inserted;

			// get the list of available nodes to use
			$nodes = $connection->query("
				SELECT * FROM nodes_output A JOIN nodes B
				ON A.node = B.`key`
				WHERE A.active = '1'
				AND `group` LIKE '%{$this->group}%'
				AND A.`limit` > A.daily
				AND (('$lastTest' - INTERVAL 24 HOUR) <= A.last_test OR A.last_test IS NULL)
				AND (A.blocked_until IS NULL OR CURRENT_TIMESTAMP >= A.blocked_until)");

			// get your personal email
			$percent = 0; $node = false;
			$user = str_replace(array(".","+"), "", explode("@", $this->to)[0]);
			foreach ($nodes as $n) {
				$temp = str_replace(array(".","+"), "", explode("@", $n->email)[0]);
				similar_text ($temp, $user, $p);
				if($p > $percent) {
					$percent = $p;
					$node = $n;
				}
			}

			// save the from part in the object
			if($node) $this->from = $node->email;
		}

		// alert the team if no Node could be used
		$utils = new Utils();
		if(empty($node)) {
			$output = new stdClass();
			$output->code = "515";
			$output->message = "NODE: No active node to email {$this->to}";
			$utils->createAlert($output->message, "ERROR");
			return $output;
		}

		// transform images to base64
		$imagesToUpload = array();
		foreach ($this->images as $image) {
			$item = new stdClass();
			$item->type = mime_content_type($image);
			$item->name = basename($image);
			$item->content = base64_encode(file_get_contents($image));
			$imagesToUpload[] = $item;
		}

		// transform attachments to base64
		$attachmentsToUpload = array();
		foreach ($this->attachments as $attachment) {
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
		$params['to'] = $this->to;
		$params['subject'] = $this->subject;
		$params['body'] = base64_encode($this->body);
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

		// treat node unreachable error
		if(empty($output)) {
			$output = new stdClass();
			$output->code = "504";
			$output->message = "Error reaching {$node->name} to email {$this->to} with ID {$this->id}";
		}

		// update delivery time if OK
		if($output->code == "200") {
			$connection->query("UPDATE nodes_output SET daily=daily+1, sent=sent+1, last_sent=CURRENT_TIMESTAMP, last_error=NULL WHERE email='{$node->email}'");
		// insert in drops emails and add 24h of waiting time
		}else{
			$lastError = str_replace("'", "", "CODE:{$output->code} | MESSAGE:{$output->message}");
			$blockedUntil = date("Y-m-d H:i:s", strtotime("+24 hours"));
			$connection->query("UPDATE nodes_output SET blocked_until='$blockedUntil', last_error='$lastError' WHERE email='{$node->email}'");
		}

		return $output;
	}

	/**
	 * Sends an email using Amazon SES
	 *
	 * @author salvipascual
	 * @return {"code", "message"}
	 */
	public function sendEmailViaAmazon()
	{
		// clean special characters from the subject and shorten to 100 characters
		$this->subject = substr(preg_replace('/[^A-Za-z0-9\- ]/', '', $this->subject), 0, 100);

		// prepare email to be sent
		$m = new SimpleEmailServiceMessage();
		$m->addTo($this->to);
		$m->setFrom($this->from);
		$m->setSubject($this->subject);
		$m->setMessageFromString('Su cliente de correo no acepta HTML', $this->body);

		// set the encoding of the subject and the body
		$m->setSubjectCharset('ISO-8859-1');
		$m->setMessageCharset('ISO-8859-1');

		// embebbed images
		foreach ($this->images as $path) {
			$fileName = basename($path);
			$m->addAttachmentFromFile($fileName,$path,'application/octet-stream',"<$fileName>",'inline');
		}

		// add attachments
		foreach ($this->attachments as $path) {
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
			$msg = "AMAZON: Error sending from: {$this->from} to {$this->to} with subject {$this->subject} and error: ".$e->getMessage();
			$utils->createAlert($msg, "ERROR");
			$output->code = "500";
			$output->message = $e->getMessage();
		}

		return $output;
	}

	/**
	 * Sends an email using a Gmail alias via Amazon SES
	 *
	 * @author salvipascual
	 * @return {"code", "message"}
	 */
	public function sendEmailViaAlias()
	{
		// list of aliases @TODO read directly from Amazon
		$aliases = array('apre.taste+nenito','apretaste+ahora','apretaste+alfa','apretaste+aljuarismi','apretaste+angulo','apretaste+arquimedes','apretaste+beta','apretaste+bolzano','apretaste+bool','apretaste+brahmagupta','apretaste+brutal','apretaste+cantor','apretaste+cauchy','apretaste+chi','apretaste+colonia','apretaste+david','apretaste+delta','apretaste+descartes','apretaste+elias','apretaste+epsilon','apretaste+euclides','apretaste+euler','apretaste+fermat','apretaste+fibonacci','apretaste+fourier','apretaste+francisco','apretaste+gamma','apretaste+gauss','apretaste+gonzalo','apretaste+hilbert','apretaste+hipatia','apretaste+homero','apretaste+imperator','apretaste+isaac','apretaste+james','apretaste+jey','apretaste+kappa','apretaste+kepler','apretaste+key','apretaste+lambda','apretaste+leibniz','apretaste+lota','apretaste+luis','apretaste+manuel','apretaste+mu','apretaste+newton','apretaste+nombre','apretaste+nu','apretaste+ohm','apretaste+omega','apretaste+omicron','apretaste+oscar','apretaste+pablo','apretaste+peta','apretaste+phi','apretaste+pi','apretaste+poincare','apretaste+psi','apretaste+quote','apretaste+ramon','apretaste+rho','apretaste+riemann','apretaste+salomon','apretaste+sigma','apretaste+tales','apretaste+theta','apretaste+travis','apretaste+turing','apretaste+upsilon','apretaste+uva','apretaste+vacio','apretaste+viete','apretaste+weierstrass','apretaste+working','apretaste+xenon','apretaste+xi','apretaste+yeah','apretaste+zeta');

		// select an alias based on your personal email
		$percent = 0; $alias = NULL;
		$user = str_replace(array(".","+"), "", explode("@", $this->to)[0]);
		foreach ($aliases as $a) {
			similar_text ($a, $user, $p);
			if($p > $percent) {
				$percent = $p;
				$alias = $a;
			}
		}

		// send the email using Amazon
		$this->from = "$alias@gmail.com";
		return $this->sendEmailViaAmazon();
	}

	/**
	 * Sends an email using Postmark
	 *
	 * @author salvipascual
	 * @return {"code", "message"}
	 */
	public function sendEmailViaPostmark()
	{
		// get the Postmark key
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$key = $di->get('config')['postmark']['key'];

		// create mailer
		$mailer = new Nette\Mail\SmtpMailer([
			'host' => "smtp.postmarkapp.com",
			'username' => $key,
			'password' => "514aaca0-4e53-4e75-abf2-499419937e1c",
			'port' => '2525',
			'secure' => 'STARTTLS'
		]);

		// select the from part @TODO make this automatically
		$this->from = "noreply@pizarra.me";

		// create message
		$mail = new Message;
		$mail->setFrom($this->from);
		$mail->addTo($this->to);
		$mail->setSubject($this->subject);
		$mail->setHtmlBody($this->body);
		$mail->setReturnPath($this->from);
		$mail->setHeader('Sender', $this->from);
		$mail->setHeader('In-Reply-To', $this->replyId);
		$mail->setHeader('References', $this->replyId);

		// create the response code and message
		$output = new stdClass();
		$output->code = "200";
		$output->message = "POSTMARK: Sent to {$this->to}";

		// send email
		try{
			$mailer->send($mail, false);
		}catch (Exception $e){
			$output->code = "500";
			$output->message = "POSTMARK: " . $e->getMessage();
			$utils->createAlert($output->message, "ERROR");
		}

		return $output;
	}
}
