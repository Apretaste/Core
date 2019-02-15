<?php

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
	public function send($to, $subject, $body, $attachment=false){
        $output = new stdClass();
        $account = Connection::query("SELECT email, password FROM delivery_gmail WHERE sent<490 AND active=1");
        
        if(empty($account)){
            $output->code = "520";
            $output->message = "[GmailClient] No active account";
            return $output;
        }

        $from = $account[0]->email;
        $password = $account[0]->password;

        $headers = array (
            'From' => $from,
            'To' => $to, 
            'Subject' => $subject);

        $crlf = "\n";
        $mime = new Mail_mime($crlf);
        $mime->setTXTBody($body);
        if($attachment) $mime->addAttachment($attachment,'application/zip');

        $body = $mime->get();
        $headers = $mime->headers($headers);
        
        $smtp = Mail::factory('smtp', array(
                'host' => 'ssl://smtp.gmail.com',
                'port' => '465',
                'auth' => true,
                'username' => $from,
                'password' => $password
            ));

        $mail = $smtp->send($to, $headers, $body);

        if (PEAR::isError($mail)) {
            $output->code = "500";
            $output->message = "[GmailClient] Error sending from $from: " . $mail->getMessage();
        } else {
            $output->code = "200";
            $output->message = $from;
            Connection::query("UPDATE delivery_gmail SET sent=sent+1, daily=daily+1 last_sent=CURRENT_TIMESTAMP WHERE email='$from'");
        }

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