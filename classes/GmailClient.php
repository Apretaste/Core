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
        $account = Connection::query("SELECT email, server_ip FROM delivery_gmail WHERE daily<490 AND active=1 AND TIMESTAMPDIFF(SECOND,last_sent,NOW())>5");

        if(empty($account)){
            $output->code = "520";
            $output->message = "[GmailClient] No active account";
            return $output;
        }

        $from = $account[0]->email;
        $ip = $account[0]->server_ip;

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

        $post = [
            'to' => $to,
            'headers' => json_encode($headers),
            'body' => $body,
            'token' => \Phalcon\DI\FactoryDefault::getDefault()->get('config')['gmail-failover']['token']
        ];

        $ch = curl_init("http://{$ip}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

        $output = curl_exec($ch);

        if (curl_error($ch)) {
            $error_msg = curl_error($ch);
            $output->code = "500";
            $output->message = "[GmailClient] curl error: $error_msg";
        }
        else $output = json_decode($output);

        curl_close($ch);

        if($output->code=="200")
            Connection::query("UPDATE delivery_gmail SET daily=daily+1, sent=sent+1, last_sent=CURRENT_TIMESTAMP WHERE email='$from'");
        else{
            $output->message = Connection::escape($output->message,500);
            Connection::query("UPDATE delivery_gmail SET last_error='{$output->message}', active=0 WHERE email='$from'");
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
