<?php

use Mailgun\Mailgun;
use Nette\Mail\Message;

class Email
{
	public $group = 'apretaste';
	public $messageid = NULL; // ID of the email to create a reply
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
	 * */
	public function sendEmail($to, $subject, $body, $images=array(), $attachments=array())
	{
		// do not email if there is an error
		$utils = new Utils();
		$status = $utils->deliveryStatus($to);
		if($status != 'ok') return false;

		// never send emails from the sandbox
		$di = \Phalcon\DI\FactoryDefault::getDefault();
//		if($di->get('environment') == "sandbox") return true;
		$connection = new Connection();

		// get the name and domain from the email
		$emailParts = explode("@", $to);
		$emailName = $emailParts[0];
		$emailDomain = $emailParts[1];

		// if the recepient is from cuba, rotate
		if(substr($emailDomain, -3) === ".cu")
		{
			// if domain is enforced, get the provider
			if($this->domain) {
				$result = $connection->query("SELECT sender FROM domain WHERE domain = '{$this->domain}'");
				$provider = $result[0]->sender;
			// if domain is not enforced, get the next domain and provider
			} else {
				$result = $connection->query("
					SELECT domain, sender
					FROM domain
					WHERE active = 1
					AND `group` = '{$this->group}'
					AND blacklist NOT LIKE '%$emailDomain%'
					ORDER BY last_usage ASC LIMIT 1");
				$this->domain = $result[0]->domain;
				$provider = $result[0]->sender;
			}

			// create a random email $from
			$user = str_replace(array(".", "+", "-"), "", $emailName);
			$seed = rand(80, 98);
			$from = "{$user}{$seed}@{$this->domain}";

			// send the email using the provider
			if($provider == "mailgun") $this->sendEmailViaMailgun($from, $to, $subject, $body, $images, $attachments);
			elseif($provider == "amazon") $this->sendEmailViaAmazon($from, $to, $subject, $body, $images, $attachments);
			elseif($provider == "gmail") $this->sendEmailViaGmail($to, $subject, $body, $images, $attachments);
			else return $utils->createAlert("No provider to respond to $to with subject $subject", "ERROR");
		}
		// respond to recipients outside Cuba
		else
		{
			$from = "respuesta@apretaste.com";
			$provider = "amazon";
			$this->domain = "apretaste.com";
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
	 * Sends an email using Gmail
	 *
	 * @author salvipascual
	 * @return Boolean
	 */
	private function sendEmailViaGmail($to, $subject, $body, $images, $attachments)
	{
		// get next domain
		$connection = new Connection();
		$gmail = $connection->query("
			SELECT * FROM delivery_gmail
			WHERE active=1
			AND (daily < 30 || date(last_usage) < curdate())
			AND TIMESTAMPDIFF(MINUTE,last_usage,NOW()) > 10
			ORDER BY last_usage ASC
			LIMIT 1");

		// do not continue for empty responses
		if(empty($gmail)) return false;

		// create mailer
		$from = "{$gmail[0]->name}@gmail.com";
		$mailer = new Nette\Mail\SmtpMailer([
			'host' => 'smtp.gmail.com',
			'username' => $from,
			'password' => $gmail[0]->password,
			'secure' => 'ssl'
		]);

		// create message
		$mail = new Message;
		$mail->setFrom($from);
		$mail->addTo($to);
		$mail->setSubject($subject);
		$mail->setHtmlBody($body);

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

		// update the daily record
		$lastDate = date("Y-m-d", strtotime($gmail[0]->last_usage));
		$currentDate = date("Y-m-d");
		$daily = $lastDate == $currentDate ? "daily=daily+1," : "";
		$connection->query("
			UPDATE delivery_gmail
			SET sent=sent+1, $daily last_usage=CURRENT_TIMESTAMP
			WHERE name='{$gmail[0]->name}'");

		return true;
	}

	/**
	 * Sends an email using Amazon SES
	 *
	 * @author salvipascual
	 * @return Boolean
	 */
	private function sendEmailViaAmazon($from, $to, $subject, $body, $images, $attachments)
	{
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
		// @TODO

		// add attachments
		foreach ($attachments as $attachment) {
			$m->addAttachmentFromData($attachment->name, $attachment->content, $attachment->type);
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
