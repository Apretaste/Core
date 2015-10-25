<?php 

class Email {
	/**
	 * Sends an email using Mandrill
	 * @author salvipascual
	 * @param String $to, email address of the receiver
	 * @param String $subject, subject of the email
	 * @param String $body, body of the email in HTML
	 * @param Array $images, paths to the images to embeb
	 * @param Array $attachments, paths to the files to attach 
	 * @throw Mandrill_Error
	 * */
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
		// @TODO

		// create the array send 	
		$message = array(
			'html' => $body,
			'subject' => $subject,
			'from_email' => $from,
			'from_name' => 'Apretaste',
			'to' => array(array('email'=>$to,'name'=>'','type'=>'to')),
			'images' => $messageImages
		);

		// send the email via Mandrill
		try 
		{
			$mandrill = new Mandrill('SPiwa91zBAXLXaAKM_z0Lw'); // TODO put API_Key in the configuration
			$result = $mandrill->messages->send($message, false);
		} 
		catch(Mandrill_Error $e)
		{
			echo 'An error sending your email occurred: ' . get_class($e) . ' - ' . $e->getMessage();
			throw $e;
		}

		// log rejected emails
		$status = $result[0]["status"];
		if(in_array($status, array("rejected", "invalid")))
		{
			$email = $result[0]["email"];
			$reason = $result[0]["reject_reason"];
			$mandrillId = $result[0]["_id"];

			$connection = new Connection();
			$connection->deepQuery("INSERT INTO delivery_error(user_email,response_email,reason, mandrill_id ) VALUES ('$email','$from',$reason','$mandrillId')");
		}
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
		$result = $connection->deepQuery("SELECT * FROM jumper WHERE active=1 AND blocked_domains NOT LIKE '%$domain%' ORDER BY sent_count ASC LIMIT 1");

		// increase the email counter
		$email = $result[0]->email;
		$counter = $result[0]->sent_count + 1;
		$result = $connection->deepQuery("UPDATE jumper SET sent_count='$counter' WHERE email='$email'");

		return $email;
	}
}
