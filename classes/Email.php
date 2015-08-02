<?php 

class Email {
	/**
	 * Sends an email using Mandrill
	 * @author salvipascual
	 * @param String $to, email address of the receiver
	 * @param String $subject, subject of the email
	 * @param String $body, body of the email in HTML
	 * @throw Mandrill_Error
	 * */
	public function sendEmail($to, $subject, $body) {
		// TODO select the from email using the jumper
		$from="soporte@apretaste.com";

		// create the array send 	
		$message = array(
			'html' => $body,
			'text' => '',
			'subject' => $subject,
			'from_email' => $from,
			'from_name' => 'Apretaste',
			'to' => array(array('email'=>$to,'name'=>'','type'=>'to'))
		);

		// queque the email to be sent by Mandrill
		try {
			$mandrill = new Mandrill('SPiwa91zBAXLXaAKM_z0Lw');
			$mandrill->messages->send($message, false);
		} catch(Mandrill_Error $e) {
			echo 'A mandrill error occurred: ' . get_class($e) . ' - ' . $e->getMessage();
			throw $e;
		}
	}
}
