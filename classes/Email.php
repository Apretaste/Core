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

		// respond via Amazon to people outside Cuba
		if( ! $isCuba)
		{
			$res = $this->sendEmailViaAmazon();
		}
		// if sending a campaign email
		elseif($this->group == 'campaign')
		{
			$res = $this->sendEmailViaGmail();
		}
		// if responding to the Support
		elseif($this->group == 'support')
		{
			$res = $this->sendEmailViaGmail();
		}
		// if responding to Marti
		elseif($this->group == 'danger')
		{
			$this->subject = $utils->randomSentence();
			if($isNauta) $this->setContentAsAttachment();
			$res = $this->sendEmailViaGmail();
		}
		// for all other Nauta emails
		elseif($isNauta)
		{
			$this->setContentAsAttachment();
			$res = $this->sendEmailViaMailjet();
		}
		// for all other Cuban emails
		else
		{
			$res = $this->sendEmailViaAlias();
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
			$subject = str_replace("'", "", $this->subject);
			$attachments = count($this->attachments);
			$images = count($this->images);
			$connection->query("INSERT INTO delivery_sent (mailbox, user, subject, images, attachments, `group`, origin) VALUES ('{$this->from}','{$this->to}','$subject','$images','$attachments','{$this->group}','{$this->id}')");
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
	 * Sends an email using Amazon SES
	 *
	 * @author salvipascual
	 * @return {"code", "message"}
	 */
	public function sendEmailViaAmazon()
	{
		// get the Postmark params
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$host = "email-smtp.us-east-1.amazonaws.com";
		$user = $di->get('config')['amazon']['access'];
		$pass = $di->get('config')['amazon']['secret'];
		$port = '465';
		$security = 'ssl';

		// select the from part if empty
		if(empty($this->from)) $this->from = 'noreply@apretaste.com';

		// send the email using smtp
		return $this->smtp($host, $user, $pass, $port, $security);
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
		// get the Postmark params
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$host = "smtp.postmarkapp.com";
		$key = $di->get('config')['postmark']['key'];
		$port = '2525';
		$security = 'STARTTLS';

		// select the from part @TODO make this automatically
		if(empty($this->from)) $this->from = "noreply@pizarra.me";

		// send the email using smtp
		return $this->smtp($host, $key, $key, $port, $security);
	}

	/**
	 * Sends an email using Sendinblue
	 *
	 * @author salvipascual
	 * @return {"code", "message"}
	 */
	public function sendEmailViaSendinblue()
	{
		// get the Sendinblue params
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$host = 'smtp-relay.sendinblue.com';
		$user = $di->get('config')['sendinblue']['user'];
		$pass = $di->get('config')['sendinblue']['pass'];
		$port = '587';

		// select the from part @TODO make this automatically
		if(empty($this->from)) $this->from = "webmailcuba@gmail.com";

		// send the email using smtp
		return $this->smtp($host, $user, $pass, $port, '');
	}

	/**
	 * Sends an email using SendGrid
	 *
	 * @author salvipascual
	 * @return {"code", "message"}
	 */
	public function sendEmailViaSendGrid()
	{
		// get the SendGrid params
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$host = "smtp.sendgrid.net";
		$user = "apikey";
		$pass = $di->get('config')['sendgrid']['key'];
		$port = '465';
		$security = 'ssl';

		// create the from using the email
		$username = str_replace(array('.','+'), '', explode('@', $this->to)[0]);
		if(empty($this->from)) $this->from = "$username@gmail.com";

		// send the email using smtp
		return $this->smtp($host, $user, $pass, $port, $security);
	}

	/**
	 * Sends an email using Mailjet
	 *
	 * @author salvipascual
	 * @return {"code", "message"}
	 */
	public function sendEmailViaMailjet()
	{
		// get the Mailjet params
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$host = "in-v3.mailjet.com";
		$user = $di->get('config')['mailjet']['user'];
		$pass = $di->get('config')['mailjet']['pass'];
		$port = '587';
		$security = 'tsl';

		// select the from part @TODO make this automatically
		if(empty($this->from)) $this->from = "alfonsedalong@gmail.com";

		// send the email using smtp
		return $this->smtp($host, $user, $pass, $port, $security);
	}

	/**
	 * Sends an email using Gmail
	 *
	 * @author salvipascual
	 * @return {"code", "message"}
	 */
	public function sendEmailViaGmail()
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
			// get the list of available nodes to use
			$nodes = $connection->query("
				SELECT * FROM nodes_output A JOIN nodes B
				ON A.node = B.`key`
				WHERE A.active = '1'
				AND A.`limit` > A.daily
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

		// alert the team if no email can be used
		if(empty($node)) {
			$output = new stdClass();
			$output->code = "515";
			$output->message = "No active email to reach {$this->to}";

			$utils = new Utils();
			$utils->createAlert($output->message, "ERROR");
			return $output;
		}

		// send the email using smtp
		$output = $this->smtp($node->host, $node->user, $node->pass, '', 'ssl');

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
	 * Sends an email using TurboSMTP
	 *
	 * @author salvipascual
	 * @return {"code", "message"}
	 */
	public function sendEmailViaTurboSmtp()
	{
		// get the node of the from address
		if(empty($this->from)) {
			$nodes = $connection->query("
				SELECT * FROM nodes_output A JOIN nodes B
				ON A.node = B.`key`
				WHERE A.active = '1'
				AND A.`limit` > A.daily
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
			$this->from = $node->email;
		}
die($this->from);

		// get the Turbo SMTP params
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$host = "smtp.postmarkapp.com";
		$key = $di->get('config')['postmark']['key'];
		$port = '2525';
		$security = 'STARTTLS';

		// send the email using smtp
		return $this->smtp($host, $key, $key, $port, $security);


		// alert the team if no email can be used
		if(empty($node)) {
			$output = new stdClass();
			$output->code = "515";
			$output->message = "No active email to reach {$this->to}";

			$utils = new Utils();
			$utils->createAlert($output->message, "ERROR");
			return $output;
		}

		// send the email using smtp
		$output = $this->smtp($node->host, $node->user, $node->pass, '', 'ssl');

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
	 * Send email using SMTP
	 *
	 * @author salvipascual
	 */
	private function smtp($host, $user, $pass, $port, $security)
	{
		// create mailer
		$mailer = new Nette\Mail\SmtpMailer([
			'host' => $host,
			'username' => $user,
			'password' => $pass,
			'port' => $port,
			'secure' => $security
		]);

		// subject has to be UTF-8
		$this->subject = utf8_encode($this->subject);

		// create message
		$mail = new Message;
		$mail->setFrom($this->from);
		$mail->addTo($this->to);
		$mail->setSubject($this->subject);
		$mail->setHtmlBody($this->body, false);
		$mail->setReturnPath($this->from);
		$mail->setHeader('X-Mailer', '');
		$mail->setHeader('Sender', $this->from);
		$mail->setHeader('In-Reply-To', $this->replyId);
		$mail->setHeader('References', $this->replyId);

		// add images to the template
		foreach ($this->images as $image) {
			if (file_exists($image)) {
				$inline = $mail->addEmbeddedFile($image);
				$inline->setHeader("Content-ID", basename($image));
			}
		}

		// add attachments
		foreach ($this->attachments as $attachment) {
			if (file_exists($attachment)) $mail->addAttachment($attachment);
		}

		// create the response code and message
		$output = new stdClass();
		$output->code = "200";
		$output->message = "Sent to {$this->to}";

		// send email
		try{
			$mailer->send($mail, false);
		}catch (Exception $e){
			$output->code = "500";
			$output->message = $e->getMessage();

			$utils = new Utils();
			$utils->createAlert($e->getMessage(), "ERROR");
		}

		return $output;
	}

	/**
	 * Configures the contents to be sent as a ZIP attached instead of directly in the body of the message
	 *
	 * @author salvipascual
	 */
	public function setContentAsAttachment()
	{
		// get temp path
		$utils = new Utils();
		$tmpFile = $utils->getTempDir() . rand(1000000,9999999) . ".zip";

		// create the zip file
		$zip = new ZipArchive;
		$zip->open($tmpFile, ZipArchive::CREATE);
		$zip->addFromString("respuesta.html",  $this->body);
		$zip->close();

		// create the body part and attachments
		$this->body = "A peticion de muchos usuarios que no reciben HTML, estamos probando adjuntar las respuestas al email. La respuesta viene comprimida como ZIP para ahorrarle saldo. Por favor abra el archivo adjunto para ver su respuesta. Si no se abre el adjunto, instale WinZip en su telefono. Comunique sus inquietudes al soporte y le atenderemos.";
		$this->attachments[] = $tmpFile;
	}
}
