<?php

use Mailgun\Mailgun;

class Email
{
	public $group = 'apretaste';
	public $messageid = NULL; // ID of the email to create a reply
	public $domain = NULL; // force a domain for test purposes

	/**
	 * Creates a new database connection for the class
	 */
	private $conn;
	public function __construct() {
		$this->conn = new Connection();
	}

	/**
	 * Sends an email using MailGun
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
		if($status != 'ok') return;

		// select the right email to use as From
		$from = $this->nextEmail($to);

		// create the list of images and attachments
		$embedded = array();
		if( ! empty($images)) $embedded['inline'] = $images;
		if( ! empty($attachments)) $embedded['attachment'] = $attachments;

		// create the array send
		$message = array(
			"from" => "Apretaste <$from>",
			"to" => $to,
			"subject" => $subject,
			"html" => $body,
			"o:tracking" => false,
			"o:tracking-clicks" => false,
			"o:tracking-opens" => false,
			"h:X-service" => "Apretaste"
		);

		// adding In-Reply-To header (creating conversation with the user)
		if ($this->messageid) $message["h:In-Reply-To"] = $this->messageid;

		// send the email via MailGun. Never send emails from the sandbox
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		if($di->get('environment') != "sandbox")
		{
			$mailgunKey = $di->get('config')['mailgun']['key'];
			$mgClient = new Mailgun($mailgunKey);
			$result = $mgClient->sendMessage($domain, $message, $embedded);
		}

		// save a trace that the email was sent
		$haveImages = empty($images) ? 0 : 1;
		$haveAttachments = empty($attachments) ? 0 : 1;
		$this->conn->deepQuery("INSERT INTO delivery_sent(mailbox,user,subject,images,attachments,domain) VALUES ('$from','$to','$subject','$haveImages','$haveAttachments','{$this->domain}')");
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
	 * Brings the next email to be used by Apretaste using an even distribution
	 *
	 * @author salvipascual
	 * @param String $email, user's email
	 * @return String, email to use
	 * */
	private function nextEmail($email)
	{
		// get the domain to generate the email
		if (empty($this->domain)) $this->nextDomain($email);

		// get the username part of the email and clean bad characters
		$user = explode("@", $email)[0];
		$user = str_replace(array(".", "+", "-"), "", $user);

		// generate a two digits number that looks like a year
		$seed = rand(80, 98);

		// create and return the email
		return "{$user}{$seed}@{$this->domain}";
	}

	/**
	 * Select the next domain using an even distribution
	 *
	 * @author salvipascual
	 * @param String $email, user's email
	 * */
	private function nextDomain($email)
	{
		// get the domain from the user's email
		$userDomain = explode("@", $email)[1];

		// get the domain with less usage
		$result = $this->conn->deepQuery("
			SELECT domain
			FROM domain
			WHERE active = 1
			AND `group` = '{$this->group}'
			AND blacklist NOT LIKE '%$userDomain%'
			ORDER BY last_usage ASC LIMIT 1");

		// increase the send counter
		$domain = $result[0]->domain;
		$this->conn->deepQuery("
			UPDATE domain
			SET sent_count=sent_count+1, last_usage=CURRENT_TIMESTAMP
			WHERE domain='$domain'");

		// choose the domain
		$this->domain = $domain;
	}
}
