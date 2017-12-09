<?php

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
	public $method;
	public $sent; // date

	/**
	 * Select a provider automatically and send an email
	 * @author salvipascual
	 * @return {"code", "message"}
	 */
	public function send()
	{
		// check if the email is from Nauta or Cuba
		$isCuba = substr($this->to, -3) === ".cu";
		$isNauta = substr($this->to, -9) === "@nauta.cu";

		// respond via Amazon to people outside Cuba
		if( ! $isCuba) $res = $this->sendEmailViaAmazon();
		// if is Nauta and we have the user's password
		elseif($isNauta) {
			$res = $this->sendEmailViaWebmail();
			if($res->code != "200") $res = $this->sendEmailViaSparkPost();
			if($res->code != "200") $res = $this->sendEmailViaGmail();
		}
		// for all other Cuban emails
		else $res = $this->sendEmailViaAlias();

		// update the database with the email sent
		$res->message = str_replace("'", "", $res->message); // single quotes break the SQL
		$connection = new Connection();
		$connection->query("
			START TRANSACTION;
			UPDATE delivery SET
			delivery_code='{$res->code}',
			delivery_message='{$res->message}',
			delivery_method='{$this->method}',
			delivery_date = CURRENT_TIMESTAMP
			WHERE id='{$this->id}';
			COMMIT;");

		// create an alert if the email failed
		if($res->code != "200" && $res->code != "515") {
			$utils = new Utils();
			$utils->createAlert("Sending failed  METHOD:{$this->method} | MESSAGE:{$res->message} | FROM:{$this->from} | TO:{$this->to} | ID:{$this->id}", "ERROR");
		}

		// return {code, message} structure
		return $res;
	}

	/**
	 * Overload of the function send() for backward compatibility
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
	 * Load a template and send it as email
	 *
	 * @author salvipascual
	 * @param String $template, path to the template
	 * @param Array $params, variables for the template
	 * @param String $layout, path to the layout
	 */
	public function sendFromTemplate($template, $params=[], $layout="email_empty.tpl")
	{
		// create the response object
		$response = new Response();
		$response->email = $this->to;
		$response->setResponseSubject($this->subject);
		$response->setEmailLayout($layout);
		$response->createFromTemplate($template, $params);
		$response->internal = true;

		// get the body from the template
		$render = new Render();
		$html = $render->renderHTML(new Service(), $response);

		// send the email
		$this->body = $html;
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
		// get the Amazon params
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$host = "email-smtp.us-east-1.amazonaws.com";
		$user = $di->get('config')['amazon']['access'];
		$pass = $di->get('config')['amazon']['secret'];
		$port = '465';
		$security = 'ssl';

		// send the email using smtp
		if(empty($this->method)) $this->method = "amazon";
		if(empty($this->from)) $this->from = 'noreply@apretaste.com';
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
		// list of aliases
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
		$this->method = "alias";
		$this->from = "$alias@gmail.com";
		return $this->sendEmailViaAmazon();
	}

	/**
	 * Sends an email using SparkPost
	 *
	 * @author salvipascual
	 * @return {"code", "message"}
	 */
	public function sendEmailViaSparkPost()
	{
		// get the Amazon params
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$host = "smtp.sparkpostmail.com";
		$user = $di->get('config')['sparkpost']['user'];
		$pass = $di->get('config')['sparkpost']['key'];
		$port = '587';
		$security = 'STARTTLS';

		// get a random word
		$utils = new Utils();
		$word = $utils->randomSentence(1);

		// send the email using smtp
		$this->method = "sparkpost";
		$this->from = "$word@cubazone.info";
		return $this->smtp($host, $user, $pass, $port, $security);
	}

	/**
	 * Sends an email using Gmail by an external node
	 *
	 * @author salvipascual
	 * @return {"code", "message"}
	 */
	public function sendEmailViaGmail()
	{
		$this->method = "gmail";

		// every new day set the daily counter back to zero
		$connection = new Connection();
		$connection->query("UPDATE delivery_gmail SET daily=0 WHERE DATE(last_sent) < DATE(CURRENT_TIMESTAMP)");

		// get an available gmail account randomly
		$gmail = $connection->query("
			SELECT * FROM delivery_gmail
			WHERE active = 1
			AND `limit` > daily
			AND (blocked_until IS NULL OR CURRENT_TIMESTAMP >= blocked_until)
			ORDER BY RAND() LIMIT 1");

		// error if no account can be used
		if(empty($gmail)) {
			$output = new stdClass();
			$output->code = "515";
			$output->message = "NODE: No active account to send to {$this->to}";
			return $output;
		} $gmail = $gmail[0];

		// transform images to base64
		$imagesToUpload = array();
		if(is_array($this->images)) foreach ($this->images as $image) {
			$item = new stdClass();
			$item->type = file_exists($image) ? mime_content_type($image) : '';
			$item->name = basename($image);
			$item->content = file_exists($image) ? base64_encode(file_get_contents($image)) : '';
			$imagesToUpload[] = $item;
		}

		// transform attachments to base64
		$attachmentsToUpload = array();
		foreach ($this->attachments as $attachment) {
			$item = new stdClass();
			$item->type = file_exists($attachment) ? mime_content_type($attachment) : '';
			$item->name = basename($attachment);
			$item->content = file_exists($attachment) ? base64_encode(file_get_contents($attachment)) : '';
			$attachmentsToUpload[] = $item;
		}

		// create the email array request
		$params['key'] = $gmail->node_key;
		$params['from'] = $gmail->email;
		$params['host'] = "smtp.gmail.com";
		$params['user'] = $gmail->email;
		$params['pass'] = $gmail->password;
		$params['id'] = $this->id;
		$params['messageid'] = $this->replyId;
		$params['to'] = $this->to;
		$params['subject'] = $this->subject;
		$params['body'] = base64_encode($this->body);
		$params['attachments'] = serialize($attachmentsToUpload);
		$params['images'] = serialize($imagesToUpload);

		// contact the Sender to send the email
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "{$gmail->node_ip}/send.php");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$output = json_decode(curl_exec($ch));
		curl_close ($ch);

		// treat node unreachable error
		if(empty($output)) {
			$output = new stdClass();
			$output->code = "504";
			$output->message = "Error reaching Node to email {$this->to} with ID {$this->id}";
		}

		// update delivery time if OK
		if($output->code == "200") {
			$connection->query("UPDATE delivery_gmail SET daily=daily+1, sent=sent+1, last_sent=CURRENT_TIMESTAMP, last_error=NULL WHERE email='{$gmail->email}'");
		// insert in drops emails and add 24h of waiting time
		}else{
			// save error in the database
			$lastError = str_replace("'", "", "CODE:{$output->code} | MESSAGE:{$output->message}");
			$blockedUntil = date("Y-m-d H:i:s", strtotime("+24 hours"));
			$connection->query("UPDATE delivery_gmail SET blocked_until='$blockedUntil', last_error='$lastError' WHERE email='{$gmail->email}'");

			// create notice that the service failed
			$utils = new Utils();
			$utils->createAlert("[{$this->method}] {$output->message}");
		}

		return $output;
	}

	/**
	 * Sends an email using Nauta webmail
	 *
	 * @author salvipascual
	 * @return {"code", "message"}
	 */
	public function sendEmailViaWebmail()
	{
		$this->method = "hillary";

		// get the user's Nauta password
		$utils = new Utils();
		$pass = $utils->getNautaPassword($this->to);
		if( ! $pass) {
			$output = new stdClass();
			$output->code = "300";
			$output->message = "No password for {$this->to}";

			$utils->createAlert("[{$this->method}] {$output->message}");
			return $output;
		}

		// connect to the client
		$user = explode("@", $this->to)[0];
		$client = new NautaClient($user, $pass);

		// login and send the email
		if ($client->login())
		{
			// prepare the attachment
			$attach = empty($this->attachments) ? false : $this->attachments[0];

			// send email and logout
			$res = $client->send($this->to, $this->subject, $this->body, $attach);

			// create response
			$output = new stdClass();
			if($res) {
				$output->code = "200";
				$output->message = "Sent to {$this->to}";
			} else {
				$output->code = "520";
				$output->message = "Error sending to {$this->to}";
				$utils->createAlert("[{$this->method}] {$output->message}");
			}
		}
		// if the client cannot login show error
		else
		{
			$output = new stdClass();
			$output->code = "510";
			$output->message = "Error connecting to Webmail";
			$utils->createAlert("[{$this->method}] {$output->message}");
		}

		// create notice that the service failed
		return $output;
	}

	/**
	 * Configures the contents to be sent as a ZIP attached instead of directly in the body of the message
	 *
	 * @author salvipascual
	 */
	public function setContentAsZipAttachment()
	{
		// get a random name for the file and folder
		$utils = new Utils();
		$zipFile = $utils->getTempDir() . substr(md5(rand() . date('dHhms')), 0, 8) . ".zip";
		$htmlFile = substr(md5(date('dHhms') . rand()), 0, 8) . ".html";

		// create the zip file
		$zip = new ZipArchive;
		$zip->open($zipFile, ZipArchive::CREATE);
		$zip->addFromString($htmlFile, $this->body);

		// all files and attachments
		if (is_array($this->images)) foreach ($this->images as $i) $zip->addFile($i, basename($i));
		if (is_array($this->attachments)) foreach ($this->attachments as $a) $zip->addFile($a, basename($a));

		// close the zip file
		$zip->close();

		// add to the attachments and clean the body
		$this->attachments = array($zipFile);
		$this->body = "";
	}

	/**
	 * Adjust the quality of the images on the email body
	 *
	 * @author salvipascual
	 */
	public function setImageQuality()
	{
		// get the image quality
		$connection = new Connection();
		$quality = $connection->query("SELECT img_quality FROM person WHERE email='{$this->to}'");
		if(empty($quality)) $quality = "ORIGINAL";
		else $quality = $quality[0]->img_quality;

		// get rid of images
		if($quality == "SIN_IMAGEN")
		{
			$this->images = array();
		}

		// create thumbnails for images
		if($quality == "REDUCIDA")
		{
			$utils = new Utils();
			$images = array();
			if(is_array($this->images)) foreach ($this->images as $file)
			{
				// thumbnail the image or use thumbnail cache
				$thumbnail = $utils->getTempDir() . "thumbnails/" . basename($file);
				if( ! file_exists($thumbnail)) {
					copy($file, $thumbnail);
					$utils->optimizeImage($thumbnail, 100);
				}

				// use the image only if it can be compressed
				$images[] = (filesize($file) > filesize($thumbnail)) ? $thumbnail : $file;
			}
			$this->images = $images;
		}
	}

	/**
	 * Handler to send email using SMTP
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
		if(is_array($this->images)) foreach ($this->images as $image) {
			if (file_exists($image)) {
				$inline = $mail->addEmbeddedFile($image);
				$inline->setHeader("Content-ID", basename($image));
			}
		}

		// add attachments
		if(is_array($this->attachments)) foreach ($this->attachments as $attachment) {
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

			// create notice that the service failed
			$utils = new Utils();
			$utils->createAlert("[{$this->method}] {$output->message}");
		}

		return $output;
	}
}
