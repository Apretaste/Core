<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class GmailClient
{
	/**
	 * Sends an email
	 *
	 * @param String $to
	 * @param String $subject
	 * @param String $body
	 * @param mixed $attachment
	 * @return mixed
	 */
	public function send($to, $subject, $body, $attachment=false)
	{
        // get the gmail account to use
        $from = $this->getEmailFrom();
        if($from->code != "200") return $from;

        // load access token
        $client = $this->getClient();
        $client->setAccessToken($from->accessToken);

        // refresh the token if it's expired
        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());

            $accessTokenJSON = json_encode($client->getAccessToken());
			Connection::query("UPDATE delivery_gmail SET access_token='$accessTokenJSON', token_created=CURRENT_TIMESTAMP WHERE email='{$from->email}'");
        }

        //prepare the mail with PHPMailer
        $fakeSenderName = Utils::randomSentence(1);
        $mail = new PHPMailer();
        $mail->CharSet = "UTF-8";
        $mail->Encoding = "base64";
        $mail->setFrom($from->email, $fakeSenderName);
        $mail->addAddress($to);
        $mail->addReplyTo("$fakeSenderName@gmail.com", $fakeSenderName);
        if($attachment) $mail->addAttachment($attachment);
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body = $body;

        //create the MIME Message
        $mail->preSend();
        $mime = $mail->getSentMIMEMessage();
        $mime = rtrim(strtr(base64_encode($mime), '+/', '-_'), '=');

        //create the Gmail Message
        $message = new Google_Service_Gmail_Message();
        $message->setRaw($mime);

        // send the email
        try {
            $service = new Google_Service_Gmail($client);
            $service->users_messages->send('me', $message);
        } catch (Exception $e) {
            // put account on hold
            Connection::query("UPDATE delivery_gmail SET active=0 WHERE email='{$from->email}'");

            // return error
            $output = new stdClass();
            $output->code = "500";
            $output->from = $from->email;
            $output->message = "[GmailClient] Error sending: " . $e->getMessage();
            return $output;
        }

        // mark the email as sent
        Connection::query("UPDATE delivery_gmail SET sent=sent+1, last_sent=CURRENT_TIMESTAMP WHERE email='{$from->email}'");

        // respond with possitive message
        $output = new stdClass();
        $output->code = "200";
        $output->from = $from->email;
        $output->message = "[GmailClient] Email sent";
        return $output;
    }

    /**
     * Selects a Gmail from the database to work as sender
     * 
     * @author salvipascual
     */
    private function getEmailFrom()
    {
        // every new day set the daily counter back to zero
		Connection::query("UPDATE delivery_gmail SET sent=0 WHERE DATE(last_sent) < DATE(CURRENT_TIMESTAMP)");

		// get an available gmail account randomly
		$gmail = Connection::query("
			SELECT * FROM delivery_gmail
			WHERE active = 1
			AND `limit` > sent
            AND access_token IS NOT NULL
            AND access_token <> ''
            ORDER BY RAND() LIMIT 1");

        // error if no account can be used
        $output = new stdClass();
        if(empty($gmail)) 
        {
			$output->code = "515";
			$output->message = "[GmailClient] No active Gmail account to use";
        } 
        // convert access token to JSON and return
        else 
        {
            $output->code = "200";
            $output->email = $gmail[0]->email;
            $output->accessToken = json_decode($gmail[0]->access_token, true);
        }

        return $output;
    }

    /**
     * Get the Gmail client, to 
     * */
    public function getClient() 
    {
        // get the configs file
        $configs = dirname($_SERVER['DOCUMENT_ROOT']) . '/configs' . '/client_secret.json';

        // connect to Google client
        $client = new Google_Client();
        $client->setApplicationName('Gmail API PHP Quickstart');
        $client->setScopes(Google_Service_Gmail::GMAIL_SEND);
        $client->setAuthConfig($configs);
        $client->setAccessType('offline');

        return $client;
    }
}