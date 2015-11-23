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

/*
	SEND VIA MANDRILL
	public function sendEmail($to, $subject, $body, $images=array(), $attachments=array())
	{
		// select the from email using the jumper
		$from = $this->nextEmail($to);

		// create the list of images
		$messageImages = array();
		if( ! empty($images))
		{
			foreach ($images as $image)
			{
				$type = image_type_to_mime_type(exif_imagetype($image));
				$name = basename($image);
				$content = base64_encode(file_get_contents($image));
				$messageImages[] = array ('type' => $type, 'name' => $name, 'content' => $content); 
			}
		}

		// crate the list of attachments
		// TODO add list of attachments

		// create the array send 	
		$message = array(
			'html' => $body,
			'subject' => $subject,
			'from_email' => $from,
			'from_name' => 'Apretaste',
			'to' => array(array('email'=>$to,'name'=>'','type'=>'to')),
			'images' => $messageImages
		);

		// get the key from the config
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$mandrillKey = $di->get('config')['mandrill']['key'];

		// send the email via Mandrill
		try 
		{
			$mandrill = new Mandrill($mandrillKey);
			$result = $mandrill->messages->send($message, false);
		}
		catch(Mandrill_Error $e)
		{
			echo 'An error sending your email occurred: ' . get_class($e) . ' - ' . $e->getMessage();
			throw $e;
		}
	}
*/

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

		// check for valid domain
		$mgClient = new Mailgun("pubkey-5ogiflzbnjrljiky49qxsiozqef5jxp7");
		$result = $mgClient->get("address/validate", array('address' => $to));
		if( ! $result->http_response_body->is_valid) return 'hard-bounce';

		// block intents to email the deamons
		if(stripos($to,"mailer-daemon@")!==false || stripos($to,"communicationservice.nl")!==false) return 'hard-bounce';

		// block no reply emails
		if(stripos($to,"not-reply")!==false ||
			stripos($to,"notreply")!==false ||
			stripos($to,"no-reply")!==false ||
			stripos($to,"noreply")!==false ||
			stripos($to,"no-responder")!==false ||
			stripos($to,"noresponder")!==false
		) return 'no-reply';

		// block emails from apretaste to apretaste
		$connection = new Connection();
		$mailboxes = $connection->deepQuery("SELECT email FROM jumper");
		foreach($mailboxes as $m) if($to == $m->email) return 'loop';

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
