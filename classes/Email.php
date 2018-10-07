<?php

use Nette\Mail\Message;

class Email
{
	public $id;
	public $userId;
	public $from;
	public $to;
	public $requestDate;
	public $queryId;
	public $subject;
	public $body;
	public $replyId; // id to reply
	public $attachments = []; // array of paths
	public $images = []; // array of paths
	public $method;
	public $sent; // date

	/**
	 * Select a provider automatically and send an email
	 * @author salvipascual
	 * @return string {"code", "message"}
	 */
	public function send()
	{
		// respond to people in Cuba
		$isCuba = substr($this->to, -3) === ".cu";
		if($isCuba) {
			$res = $this->sendEmailViaWebmail();
			if($res->code != "200") $res = $this->sendEmailViaGmail();
		// respond to people outside Cuba
		} else {
			$res = $this->sendEmailViaAmazon();
		}

		// update the record on the delivery table
		$res->message = Connection::escape($res->message, 254);
		Connection::query("
			UPDATE delivery SET
			delivery_code='{$res->code}',
			delivery_message='{$res->message}',
			delivery_method='{$this->method}',
			delivery_date = CURRENT_TIMESTAMP
			WHERE id='{$this->queryId}'");

		// create an alert if the email failed
		if($res->code != "200") Utils::createAlert("Sending failed  METHOD:{$this->method} | MESSAGE:{$res->message} | FROM:{$this->from} | TO:{$this->to} | DATE:{$this->requestDate}");

		// return {code, message} structure
		return $res;
	}

	/**
	 * Overload of the function send() for backward compatibility
	 *
	 * @author salvipascual
	 * @param string $to, email address of the receiver
	 * @param string $subject, subject of the email
	 * @param string $body, body of the email in HTML
	 * @param array $images, paths to the images to embeb
	 * @param array $attachments, paths to the files to attach
	 *
	 * @return mixed
	 */
	public function sendEmail($to, $subject, $body, $images = [], $attachments = [])
	{
		$this->to = $to;
		$this->subject = $subject;
		$this->body = $body;
		$this->images = $images;
		$this->attachments = $attachments;

		return $this->send();
	}

	/**
	 * Sends alerts. Do not use for regular emails!
	 *
	 * @author salvipascual
	 */
	public function sendAlert()
	{
		$di = \Phalcon\DI\FactoryDefault::getDefault();

		// create message
		$mail = new Message;
		$mail->setFrom('noreply@apretaste.com');
		$mail->addTo($di->get('config')['global']['alerts']);
		$mail->setSubject(utf8_encode(substr($this->subject, 0, 80)));
		$mail->setHtmlBody($this->body, false);

		// send mail
		$mailer = new Nette\Mail\SmtpMailer([
			'host' => "email-smtp.us-east-1.amazonaws.com",
			'username' => $di->get('config')['amazon']['access'],
			'password' => $di->get('config')['amazon']['secret'],
			'port' => '465',
			'secure' => 'ssl'
		]);

		$mailer->send($mail, false);
	}

	/**
	 * Load a template and send it as email
	 *
	 * @author salvipascual
	 * @param string $template, path to the template
	 * @param array $params, variables for the template
	 * @param string $layout, path to the layout
	 *
	 * @return mixed
	 */
	public function sendFromTemplate($template, $params = [], $layout = "email_empty.tpl")
	{
		// create the response object
		$response = new Response();
		$response->email = $this->to;
		$response->setResponseSubject($this->subject);
		$response->setEmailLayout($layout);
		$response->createFromTemplate($template, $params);
		$response->internal = true;

		// get the body from the template
		$response->images = $this->images;
		$html = Render::renderHTML(new Service(), $response);
		$this->images = $response->images;

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
		$this->method = "amazon";

		// get the Amazon params
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$host = "email-smtp.us-east-1.amazonaws.com";
		$user = $di->get('config')['amazon']['access'];
		$pass = $di->get('config')['amazon']['secret'];
		$port = '465';
		$security = 'ssl';

		// send the email using smtp
		$this->from = 'noreply@apretaste.com';
		return $this->smtp($host, $user, $pass, $port, $security);
	}

	/**
	 * Sends an email using Gmail
	 *
	 * @author salvipascual
	 * @return string {"code", "message"}
	 */
	public function sendEmailViaGmail()
	{
		$this->method = "gmail";

		// get attachment if exist
		$attachment = false;
		if( ! empty($this->attachments[0])) $attachment = $this->attachments[0];

		// get a random body, else Gmail will give an error
		$this->body = Utils::randomSentence();

		// send via Gmail
		$gmailClient = new GmailClient();
		$output = $gmailClient->send($this->to, $this->subject, $this->body, $attachment);

		// create notice if Gmail fails
		if($output->code != "200") Utils::createAlert("[{$this->method}] {$output->message}");
		return $output;
	}

	/**
	 * Sends an email using Nauta webmail
	 *
	 * @author salvipascual
	 * @return string {"code", "message"}
	 */
	public function sendEmailViaWebmail()
	{
		$this->method = "hillary";

		// borrow a random Nauta account
		$auth = Connection::query("
			SELECT B.email, A.pass
			FROM authentication A JOIN person B
			ON A.person_id = B.id
			WHERE B.active = 1
			AND B.last_access > DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 30 DAY)
			AND B.email LIKE '%nauta.cu'
			AND A.appname = 'apretaste'
			AND A.pass IS NOT NULL AND A.pass <> ''
			ORDER BY RAND() LIMIT 1")[0];

		// get user and pass decrypted
		$user = explode("@", $auth->email)[0];
		$pass = Utils::decrypt($auth->pass);

		// connect to the client
		$client = new NautaClient($user, $pass);
		$tries = 3; // number of times to try if the email fails
		$output = new stdClass();

		// login and send the email
		TryAgain:
		if ($client->login())
		{
			// prepare the attachment
			$attach = empty($this->attachments) ? false : $this->attachments[0];

			// send email and logout
			$res = $client->send($this->to, $this->subject, $this->body, $attach);

			// create response
			if($res) {
				$output->code = "200";
				$output->message = "Sent to {$this->to} with $tries tries left";
			} else {
				$output->code = "520";
				$output->message = "Error sending to {$this->to}";
				Utils::createAlert("[{$this->method}] {$output->message}");
			}
		}
		else
		{
			// try sending the email again
			if($tries-- <= 0) goto TryAgain;

			// if the client cannot login show error
			$output = new stdClass();
			$output->code = "510";
			$output->message = "Error connecting to Webmail";
		}

		// create notice that the service failed
		return $output;
	}

	/**
	 * Configures the contents to be sent as a ZIP attached instead of directly in the body of the message
	 *
	 * @author salvipascual
	 * @return String, path to the file created
	 */
	public function setContentAsZipAttachment()
	{
		// get a random name for the file and folder
		$zipFile = Utils::getTempDir() . substr(md5(rand() . date('dHhms')), 0, 8) . ".zip";
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
		$this->attachments = [$zipFile];
		$this->body = "";

		// return the path to the file
		return $zipFile;
	}

	/**
	 * Handler to send email using SMTP
	 *
	 * @author salvipascual
	 */
	public function smtp($host, $user, $pass, $port, $security)
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
			Utils::createAlert("[{$this->method}] {$output->message}");
		}

		return $output;
	}
}
