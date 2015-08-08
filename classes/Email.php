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
	public function sendEmail($to, $subject, $body, $images=array(), $attachments=array()) {
		// TODO select the from email using the jumper
		$from="soporte@apretaste.com";

		// create the list of images
		$messageImages = array();
		if( ! empty($images))
		{
			foreach ($images as $image){
				$type = image_type_to_mime_type(exif_imagetype($image));
				$name = basename($image);
				$content = base64_encode(file_get_contents($image));
				$messageImages[] = array ('type' => $type, 'name' => $name, 'content' => $content); 
			}
		}

		// crate the list of attachments
		// TODO

		// create the array send 	
		$message = array(
			'html' => $body,
			'text' => '', // TODO convert from html to text automatically
			'subject' => $subject,
			'from_email' => $from,
			'from_name' => 'Apretaste',
			'to' => array(array('email'=>$to,'name'=>'','type'=>'to')),
			'images' => $messageImages
		);

		// queque the email to be sent by Mandrill
		try {
			$mandrill = new Mandrill('SPiwa91zBAXLXaAKM_z0Lw');
			$result = $mandrill->messages->send($message, false);
			// TODO log rejected emails
		} catch(Mandrill_Error $e) {
			echo 'A mandrill error occurred: ' . get_class($e) . ' - ' . $e->getMessage();
			throw $e;
		}
	}
}
