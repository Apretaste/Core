<?php 

use Mailgun\Mailgun;

class Email
{
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
		$status = $this->deliveryStatus($to);
		if($status != 'ok')
		{
			$connection = new Connection();
			$connection->deepQuery("INSERT INTO delivery_error(email,direction,reason) VALUES ('$to','out','$status')");
			return;
		}

		// select the from email using the jumper
		$from = $this->nextEmail($to);
		$domain = explode("@", $from)[1];

		// create the list of images
		if( ! empty($images)) $images = array('inline' => $images);

		// crate the list of attachments
		// TODO add list of attachments

		// create the array send
		$message = array(
			"from" => "Apretaste <$from>",
			"to" => $to,
			"subject" => $subject,
			"html" => $body,
			"o:tracking" => false,
			"o:tracking-clicks" => false,
			"o:tracking-opens" => false
		);

		// get the key from the config
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$mailgunKey = $di->get('config')['mailgun']['key'];

		// send the email via MailGun
		$mgClient = new Mailgun($mailgunKey);
		$result = $mgClient->sendMessage($domain, $message, $images);
	}


	/**
	 * Checks if an email can be delivered to certain mailbox
	 * 
	 * @author salvipascual
	 * @param String $to, email address of the receiver
	 * @param String $subject, subject of the email
	 * @param String $body, body of the email in HTML
	 * @return String delivability status: ok, hard-bounce, soft-bounce, spam, no-reply, loop, unknown
	 * */
	public function deliveryStatus($to)
	{
		// block people following the example email
		if($to == "su@amigo.cu") return 'hard-bounce';

		// block intents to email the deamons
		if(stripos($to,"mailer-daemon@")!==false || stripos($to,"communicationservice.nl")!==false) return 'hard-bounce';

		// block no reply emails
		if(stripos($to,"not-reply")!==false ||
			stripos($to,"notreply")!==false ||
			stripos($to,"No_Reply")!==false ||
			stripos($to,"Do_Not_Reply")!==false ||
			stripos($to,"no-reply")!==false ||
			stripos($to,"noreply")!==false ||
			stripos($to,"no-responder")!==false ||
			stripos($to,"noresponder")!==false
		) return 'no-reply';

		// block any previouly dropped email
		$connection = new Connection();
		$res = $connection->deepQuery("SELECT email FROM delivery_dropped WHERE email='$to'");
		if( ! empty($res)) return 'loop';

		// block emails from apretaste to apretaste
		$mailboxes = $connection->deepQuery("SELECT email FROM jumper");
		foreach($mailboxes as $m) if($to == $m->email) return 'loop';

		// check for valid domain
		$mgClient = new Mailgun("pubkey-5ogiflzbnjrljiky49qxsiozqef5jxp7");
		$result = $mgClient->get("address/validate", array('address' => $to));
		if( ! $result->http_response_body->is_valid) return 'hard-bounce';

		// check NEW emails deeper (only for new people)
		$utils = new Utils();
		if( ! $utils->personExist($to))
		{
			// save all emails tested by the email validador to ensure no errors are happening 
			$connection->deepQuery("INSERT INTO ___emailvalidator_checked_emails (email) VALUES ('$to')");

			$di = \Phalcon\DI\FactoryDefault::getDefault();
			$key = $di->get('config')['emailvalidator']['key'];
			$result = json_decode(@file_get_contents("https://api.email-validator.net/api/verify?EmailAddress=$to&APIKey=$key"));
			if($result && $result->status == 114) return 'unknown'; // usually national email
			if($result && $result->status > 300 && $result->status < 399) return 'soft-bounce';
			if($result && $result->status > 400 && $result->status < 499) return 'hard-bounce';
		}

		// return ok
		return 'ok';
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
		$result = $connection->deepQuery("SELECT * FROM jumper WHERE (status='SendReceive' OR status='SendOnly') AND blocked_domains NOT LIKE '%$domain%' ORDER BY sent_count ASC LIMIT 1");

		// increase the send counter
		$email = $result[0]->email;
		$today = date("Y-m-d H:i:s");
		$connection->deepQuery("UPDATE jumper SET sent_count=sent_count+1, last_usage='$today' WHERE email='$email'");

		return $email;
	}
}
