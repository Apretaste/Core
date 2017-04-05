<?php

use Mailgun\Mailgun;
use Nette\Mail\Message;

class Email
{
	public $group = 'apretaste';
	public $messageid = NULL; // ID of the email to create a reply
	public $domain = NULL; // force a domain for test purposes

	/**
	 * Creates a new database connection for the class
	 */
	private $conn;
	public function __construct()
	{
		$this->conn = new Connection();
	}

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
		if($di->get('environment') == "sandbox") return true;

		// get the domain for the email to send
		$emailDomain = explode("@", $to)[1];
		$return = false;

		// redirect Nauta by gmail
		if($emailDomain == "nauta.cu")
		{
			$return = $this->sendEmailViaGmail($to, $subject, $body, $images, $attachments);
		}

		// all others OR if Nauta fails by Mailgun
		if( ! $return)
		{
			$return = $this->sendEmailViaMailgun($to, $subject, $body, $images, $attachments);
		}

		// save a trace that the email was sent
		$haveImages = empty($images) ? 0 : 1;
		$haveAttachments = empty($attachments) ? 0 : 1;
		$type = $return->type;
		$from = $return->from;
		$domain = $return->domain;
		$this->conn->deepQuery("
			INSERT INTO delivery_sent(mailbox,user,subject,images,attachments,domain,type)
			VALUES ('$from','$to','$subject','$haveImages','$haveAttachments','$domain','$type')");

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
	 * Set the group to respond based on the user's email address
	 *
	 * @author salvipascual
	 * @param String $email, user's email
	 * */
	public function setEmailGroup($email)
	{
		// @TODO find a right way to do this when needed
		return "apretaste";
	}

	/**
	 * Sends an email using Mailgun
	 *
	 * @author salvipascual
	 * @return Boolean
	 */
	private function sendEmailViaMailgun($to, $subject, $body, $images, $attachments)
	{
		// get the name and domain from the email
		$emailParts = explode("@", $to);
		$emailName = $emailParts[0];
		$emailDomain = $emailParts[1];

		// get the domain with less usage
		$result = $this->conn->deepQuery("
			SELECT domain
			FROM domain
			WHERE active = 1
			AND `group` = '{$this->group}'
			AND blacklist NOT LIKE '%$emailDomain%'
			ORDER BY last_usage ASC LIMIT 1");

		// increase the send counter
		$domain = $result[0]->domain;
		$this->conn->deepQuery("
			UPDATE domain
			SET sent_count=sent_count+1, last_usage=CURRENT_TIMESTAMP
			WHERE domain='$domain'");

		// create the new from email
		$user = str_replace(array(".", "+", "-"), "", $emailName);
		$seed = rand(80, 98);
		$from = "{$user}{$seed}@{$domain}";

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
		try{$mgClient->delete("$domain/bounces/$to");} catch(Exception $e){}

		// send email
		try{
			$mgClient->sendMessage($domain, $message, $embedded);
		} catch (Exception $e) {
			// log error and try email another way
			error_log("MAIGUN: Error sending from: $from to $to with subject $subject and error: ".$e->getMessage());
			return $this->sendEmailViaGmail($to, $subject, $body, $images, $attachments);
		}

		// create the returning structure
		$return = new stdClass();
		$return->type = "mailgun";
		$return->from = $from;
		$return->domain = $domain;
		return $return;
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
		$gmail = $connection->deepQuery("
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
			// log error and try email another way
			error_log("GMAIL Error sending from: $from to $to with subject $subject and error: ".$e->getMessage());
			return $this->sendEmailViaMailgun($to, $subject, $body, $images, $attachments);
		}

		// update the daily record
		$lastDate = date("Y-m-d", strtotime($gmail[0]->last_usage));
		$currentDate = date("Y-m-d");
		$daily = $lastDate == $currentDate ? "daily=daily+1," : "";
		$connection->deepQuery("
			UPDATE delivery_gmail
			SET sent=sent+1, $daily last_usage=CURRENT_TIMESTAMP
			WHERE name='{$gmail[0]->name}'");

		// create the returning structure
		$return = new stdClass();
		$return->type = "gmail";
		$return->from = $from;
		$return->domain = $gmail[0]->name;
		return $return;
	}
}
