<?php

use Mailgun\Mailgun;

class Email
{
	public $group = 'apretaste';
	public $messageid = null;

	/**
	 * Sends an email using MailGun
	 * @author salvipascual
	 * @param String $to, email address of the receiver
	 * @param String $subject, subject of the email
	 * @param String $body, body of the email in HTML
	 * @param Array $images, paths to the images to embeb
	 * @param Array $attachments, paths to the files to attach
	 * */
	public function sendEmail($to, $subject, $body, $images=array(), $attachments=array(), $from = null, $test = false)
	{
		// do not email if there is an error
		$utils = new Utils();
		$status = $utils->deliveryStatus($to);
		if($status != 'ok') return;

		// select the from email using the jumper
		// ... in this order ... for performance
		if (is_null($from))
			$from = $this->nextEmail($to);
		else 
			if (self::isJumper($from, $this->group)) 
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
		if ( ! is_null($this->messageid)) $message["h:In-Reply-To"] = $this->messageid;

		// send the email via MailGun. Never send emails from the sandbox
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		if($di->get('environment') != "sandbox")
		{
			$mailgunKey = $di->get('config')['mailgun']['key'];
			$mgClient = new Mailgun($mailgunKey);
			$result = $mgClient->sendMessage($domain, $message, $embedded);
		}

		if ( ! $test)
		{
			// save a trace that the email was sent
			$haveImages = empty($images) ? 0 : 1;
			$haveAttachments = empty($attachments) ? 0 : 1;
			$connection = new Connection();
			$connection->deepQuery("INSERT INTO delivery_sent(mailbox,user,subject,images,attachments,domain) VALUES ('$from','$to','$subject','$haveImages','$haveAttachments','$domain')");
		}
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
	 * Set the group to respond based on a mailbox
	 *
	 * @author salvipascual
	 * @param String $mailbox
	 * */
	public function setEmailGroup($mailbox)
	{
		// do not allow empty calls
		if(empty($mailbox)) return;

		// get group for the mailbox
		$connection = new Connection();
		$result = $connection->deepQuery("SELECT `group` FROM jumper WHERE email='$mailbox'");

		// set the group
		$this->group = $result[0]->group;
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
			AND `group` = '{$this->group}'
			AND blocked_domains NOT LIKE '%$domain%'
			ORDER BY last_usage ASC LIMIT 1");

		// increase the send counter
		$mailbox = $result[0]->email;
		$connection->deepQuery("
			UPDATE jumper
			SET sent_count=sent_count+1, last_usage=CURRENT_TIMESTAMP
			WHERE email='$mailbox'");

		return $mailbox;
	}
	/**
	 * Return TRUE if $email is a jumper
	 *  
	 * @author kuma
	 * @param string $email
	 * @return boolean
	 */
	static function isJumper($email, $group = 'apretaste'){
				
		// get the email with less usage
		$connection = new Connection();

		if (filter_var($email, FILTER_VALIDATE_EMAIL) !== false) 
		{
			$result = $connection->deepQuery("
				SELECT email
				FROM jumper
				WHERE (status='SendReceive' OR status='SendOnly')
				AND `group` = '{$group}'
				AND email = '$email';");
			
			if (isset($result[0]->email))
				return $result[0]->email === $email;
		}
		
		return false;
	}
}
