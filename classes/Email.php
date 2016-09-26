<?php 

use Mailgun\Mailgun;

class Email
{
	public $group = null;

	/**
	 * Sends an email using MailGun
	 * @author salvipascual
	 * @param String $to, email address of the receiver
	 * @param String $subject, subject of the email
	 * @param String $body, body of the email in HTML
	 * @param Array $images, paths to the images to embeb
	 * @param Array $attachments, paths to the files to attach 
	 * @param String $messageID, the id of request message from user
	 * */
	public function sendEmail($to, $subject, $body, $images=array(), $attachments=array(), $messageID = null)
	{
		// do not email if there is an error
		$utils = new Utils();
		$status = $utils->deliveryStatus($to);
		if($status != 'ok') return;

		// select the from email using the jumper
		$from = $this->nextEmail($to);
		$domain = explode("@", $from)[1];

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
		if ( ! is_null($messageID)) $message["h:In-Reply-To"] = $messageID;

		// get the key from the config
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$mailgunKey = $di->get('config')['mailgun']['key'];

		// send the email via MailGun. Never send emails from the sandbox
		if($di->get('environment') != "sandbox")
		{
			$mgClient = new Mailgun($mailgunKey);
			$result = $mgClient->sendMessage($domain, $message, $embedded);
		}

		// save a trace that the email was sent
		$haveImages = empty($images) ? 0 : 1;
		$haveAttachments = empty($attachments) ? 0 : 1;
		$connection = new Connection();
		$connection->deepQuery("INSERT INTO delivery_sent(mailbox,user,subject,images,attachments,domain) VALUES ('$from','$to','$subject','$haveImages','$haveAttachments','$domain')");
	}

	/**
	 * Brings the next email to be used by Apretaste using an even distribution
	 * 
	 * @author salvipascual
	 * @param String $email, Email of the user
	 * @return String, Email to use
	 * */
	private function nextEmail($email)
	{
		// get the domain from the user's email 
		$domain = explode("@", $email)[1];

		// get the email with less usage  
		$connection = new Connection();
		$result = $connection->deepQuery("
			SELECT email
			FROM jumper 
			WHERE (status='SendReceive' OR status='SendOnly') 
			AND blocked_domains NOT LIKE '%$domain%' 
			ORDER BY last_usage ASC LIMIT 1");

		// increase the send counter
		$email = $result[0]->email;
		$connection->deepQuery("
	        UPDATE jumper 
	        SET sent_count = sent_count + 1, last_usage = CURRENT_TIMESTAMP
	        WHERE email = '$email'");

		return $email;
	}

	/**
	 * Set the group to respond based on a mailbox
	 * 
	 * @author salvipascual
	 * @param String $mailbox
	 * */
	public function setEmailGroup($mailbox)
	{
		// get group for the mailbox
		$connection = new Connection();
		$result = $connection->deepQuery("SELECT group FROM jumper WHERE email='$mailbox'");

		// set the group
		$this->group = $result[0]->group;
		die($this->group);
	}
}
