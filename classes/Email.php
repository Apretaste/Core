<?php

use Nette\Mail\Message;
use Phalcon\Di\FactoryDefault;
use Phalcon\Logger\Adapter\File;

class Email {

  public $from;

  public $to;

  public $requestDate;

  public $deliveryId;

  public $subject;

  public $body;

  public $replyId; // id to reply

  public $attachments = []; // array of paths

  public $images = []; // array of paths

  public $method;

  public $sent; // date

  /**
   * Select a provider automatically and send an email
   *
   * @author salvipascual
   * @return string {"code", "message"}
   */
  public function send() {
    // respond to people in Cuba
    if (substr($this->to, -3) === ".cu") {

      // try sending via Webmail
      //$res = $this->sendEmailViaWebmail();

      // try sending ssh
	   $res = $this->sendEmailViaGmail();
      // if ($res->code != 200) $res = $this->sendEmailViaSSH();

      // failover to Gmail
      // $active = \Phalcon\DI\FactoryDefault::getDefault()->get('config')['gmail-failover']['active'];

    }
    // respond to people outside Cuba
    else {
      $res = $this->sendEmailViaAmazon();
    }

    // update the record on the delivery table
    $res->message = Connection::escape($res->message, 254);

    if (isset($this->deliveryId)) {
      Connection::query("
	    UPDATE delivery SET
	    delivery_code='{$res->code}',
	    delivery_message='{$res->message}',
	    delivery_method='{$this->method}',
	    delivery_date = CURRENT_TIMESTAMP
	    WHERE id={$this->deliveryId}");
    }

    // create an alert if the email failed
    if ($res->code != "200") {
      Utils::createAlert("Sending failed  METHOD:{$this->method} | MESSAGE:{$res->message} | FROM:{$this->from} | TO:{$this->to} | DATE:{$this->requestDate}");
    }

    // return {code, message} structure
    return $res;
  }

  /**
   * Overload of the function send() for backward compatibility
   *
   * @author salvipascual
   *
   * @param string $to , email address of the receiver
   * @param string $subject , subject of the email
   * @param string $body , body of the email in HTML
   * @param array $images , paths to the images to embeb
   * @param array $attachments , paths to the files to attach
   *
   * @return mixed
   */
  public function sendEmail($to, $subject, $body, $images = [], $attachments = []) {
    $this->to          = $to;
    $this->subject     = $subject;
    $this->body        = $body;
    $this->images      = $images;
    $this->attachments = $attachments;

    return $this->send();
  }

  /**
   * Sends alerts. Do not use for regular emails!
   *
   * @author salvipascual
   */
  public function sendAlert() {
    // check if email alerts are enabled
    $di          = \Phalcon\DI\FactoryDefault::getDefault();
    $emailAlerts = $di->get('config')['global']['enable_email_alerts'];
    if (empty(trim($emailAlerts))) {
      return FALSE;
    }

    // create message
    $mail = new Message;
    $mail->setFrom('noreply@apretaste.com');
    $mail->addTo($di->get('config')['global']['alerts']);
    $mail->setSubject(utf8_encode(substr($this->subject, 0, 80)));
    $mail->setHtmlBody($this->body, FALSE);

    // send mail
    $mailer = new Nette\Mail\SmtpMailer([
      'host'     => "email-smtp.us-east-1.amazonaws.com",
      'username' => $di->get('config')['amazon']['access'],
      'password' => $di->get('config')['amazon']['secret'],
      'port'     => '465',
      'secure'   => 'ssl',
    ]);

    $mailer->send($mail, FALSE);
  }

  /**
   * Load a template and send it as email
   *
   * @author salvipascual
   *
   * @param string $template , path to the template
   * @param array $params , variables for the template
   * @param string $layout , path to the layout
   *
   * @return mixed
   */
  public function sendFromTemplate($template, $params = [], $layout = "email_empty.tpl") {
    // create the response object
    $response        = new Response();
    $response->email = $this->to;
    $response->setResponseSubject($this->subject);
    $response->setEmailLayout($layout);
    $response->createFromTemplate($template, $params);
    $response->internal = TRUE;

    // get the body from the template
    $response->images = $this->images;
    $html             = Render::renderHTML(new Service(), $response);
    $this->images     = $response->images;

    // send the email
    $this->body = $html;
    return $this->send();
  }

  /**
   * Sends an email using Amazon SES
   *
   * @author salvipascual
   * @return {"code", "message"}
   */
  public function sendEmailViaAmazon() {
    $this->method = "amazon";

    // get the Amazon params
    $di       = \Phalcon\DI\FactoryDefault::getDefault();
    $host     = "email-smtp.us-east-1.amazonaws.com";
    $user     = $di->get('config')['amazon']['access'];
    $pass     = $di->get('config')['amazon']['secret'];
    $port     = '465';
    $security = 'ssl';

    // send the email using smtp
    $this->from = 'noreply@apretaste.com';
    return $this->smtp($host, $user, $pass, $port, $security);
  }

    /**
     * Sends an email using the servers raid
     *
     * @author kumahacker
     * @return string {"code", "message"}
     */
    public function sendEmailViaGmail()
    {
	$this->method = "gmail";

	// clean Gmail table
	Connection::query("update delivery_gmail set active = 1 where last_error = 'Forbidden';");
	Connection::query("UPDATE delivery_gmail SET daily = 0, sent_limit = sent_limit + 10 WHERE sent_limit < 110 AND daily > 0 AND last_sent < CURRENT_DATE AND active = 1;");
	Connection::query("UPDATE delivery_gmail SET daily = 0 WHERE daily > 0 AND last_sent < CURRENT_DATE AND active = 1;");
	Connection::query("UPDATE delivery_gmail SET active = 1, last_error = '' 
							    WHERE last_error LIKE '%Missing configuration%' 
		    				    OR (TIMESTAMPDIFF(HOUR, last_sent, now()) >= 24 AND TIMESTAMPDIFF(HOUR, last_sent, now()) <= 72);");

	// save to the log
	$di = FactoryDefault::getDefault();
	$wwwroot = $di->get('path')['root'];
	$logger = new File("$wwwroot/logs/nodemailer.log");

	// set the sending method
	$response = (object) [
	    "code" => 500,
	    "message" => "Email not sent",
	];

	// get a random body if no body was passed
	if(empty($this->body)) $this->body = Utils::randomSentence();

	$result = Connection::query("SELECT * FROM delivery_gmail where active = 1 and daily < sent_limit and TIME_TO_SEC(timediff(now(),last_sent)) > 30 order by last_sent asc limit 1");

	if (isset($result[0])) {
	    $account = $result[0];
	    $person = Utils::getPerson($this->to);
	    $toName = "{$person->first_name} {$person->middle_name} {$person->last_name} {$person->mother_name}";

	    // prepare data to be sent
	    $data = (object) [
		"From" => $account->email,
		"FromName" => $account->name,
		"Username" => $account->email,
		"Password" => $account->password,
		"ReplyTo" => (object) [
		    "Address" => $this->to,
		    "Name" => $toName
		],
		"Addresses" => [
		    (object) [
			"Name" => $toName,
			"Address" => $this->to,
			"Type" => "Address"
		    ]
		],
		"Subject" => $this->subject,
		"WordWrap" => 78,
		"Body" => base64_encode($this->body),
		"Attachments" => array_map(function($attach) {
		    return (object) [
			"Name"    => basename($attach),
			"Content" => base64_encode(file_get_contents($attach))
		    ];
		}, $this->attachments ?? [])
	    ];

	    $logger->log("From: {$account->email} To: $this->to");

	    $di = FactoryDefault::getDefault();
	    $api_key = $di->get('config')['nodemailer']['api_key'];
	    $response = Utils::postJSON("http://{$account->server_ip}/?api_key=$api_key", $data);
	    $response = json_decode($response);
	    $last_response = Connection::escape(serialize($response));

	    Connection::query("UPDATE delivery_gmail SET last_sent = now(), sent = sent + 1, daily = daily + 1, last_error = '$last_response' WHERE email = '{$account->email}'");

	    // deactivate the account
	    if ($response->code != 200) Connection::query("UPDATE delivery_gmail SET active = 0 WHERE email = '{$account->email}'");
	    else Connection::query("UPDATE delivery SET mailer_account = '{$account->email}' WHERE id = '{$this->deliveryId}'");
	} else {
	    $response = (object) [
		"code" => 500,
		"message" => "No more active account",
	    ];
	}

	if (($response->code ?? 500) != 200) {
	    $output = (object) [
		"code" => 520,
		"message" => "Error sending to {$this->to}: ".json_encode($response)
	    ];

	    Utils::createAlert("[{$this->method}] {$output->code} {$output->message}");
	    $logger->log(json_encode($output)."\n");

	    return $output;
	}

	$logger->log(json_encode($response)."\n");

	return $response;
    }

  /**
   * Sends an email using Nauta webmail
   *
   * @author salvipascual
   * @return string {"code", "message"}
   */
  public function sendEmailViaWebmail() {
    $this->method = "hillary";

    // borrow a random Nauta account
    /*	$auth = Connection::query("
        SELECT B.email, A.pass
        FROM authentication A JOIN person B
        ON A.person_id = B.id
        WHERE B.active = 1
        AND B.last_access > DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 30 DAY)
        AND B.email LIKE '%nauta.cu'
        AND A.appname = 'apretaste'
        AND A.pass IS NOT NULL AND A.pass <> ''
        ORDER BY RAND() LIMIT 1")[0];*/

    $auth = Connection::query("
	    SELECT B.email, A.pass
	    FROM authentication A JOIN person B
	    ON A.person_id = B.id
	    WHERE B.email = '{$this->to}'
	    AND A.appname = 'apretaste'
	    AND A.pass IS NOT NULL AND A.pass <> ''
	    LIMIT 1")[0];

    // get user and pass decrypted
    $user = explode("@", $auth->email)[0];
    $pass = Utils::decrypt($auth->pass);

    // connect to the client
    $client = new NautaClient($user, $pass, TRUE, FALSE);
    $tries  = 3; // number of times to try if the email fails
    $output = new stdClass();

    // login and send the email
    TryAgain:
    if ($client->login()) {
      if ($tries < 2) {
        $client = new NautaClient($user, $pass, TRUE, TRUE);
      }

      // prepare the attachment
      $attach = empty($this->attachments) ? FALSE : $this->attachments[0];

      // send email and logout
      $res = $client->send($this->to, $this->subject, $this->body, $attach);

      // create response
      if ($res) {
        $output->code    = "200";
        $output->message = "Sent to {$this->to} with $tries tries left";
      }
      else {
        $output->code    = "520";
        $output->message = "Error sending to {$this->to}";
        Utils::createAlert("[{$this->method}] {$output->code} {$output->message}");
      }
    }
    else {
      // try sending the email again
      if ($tries-- <= 0) {
        goto TryAgain;
      }

      // if the client cannot login show error
      $output          = new stdClass();
      $output->code    = "510";
      $output->message = "Error connecting to Webmail for {$this->to}";
      Utils::createAlert("[{$this->method}] {$output->code} {$output->message}");
    }

    // create notice that the service failed
    return $output;
  }

  /**
   * Configures the contents to be sent as a ZIP attached instead of directly
   * in the body of the message
   *
   * @author salvipascual
   * @return String, path to the file created
   */
  public function setContentAsZipAttachment() {
    // get a random name for the file and folder
    $zipFile  = Utils::getTempDir() . substr(md5(rand() . date('dHhms')), 0, 8) . ".zip";
    $htmlFile = substr(md5(date('dHhms') . rand()), 0, 8) . ".html";

    // create the zip file
    $zip = new ZipArchive;
    $zip->open($zipFile, ZipArchive::CREATE);
    $zip->addFromString($htmlFile, $this->body);

    // all files and attachments
    if (is_array($this->images)) {
      foreach ($this->images as $i) {
        $zip->addFile($i, basename($i));
      }
    }
    if (is_array($this->attachments)) {
      foreach ($this->attachments as $a) {
        $zip->addFile($a, basename($a));
      }
    }

    // close the zip file
    $zip->close();

    // add to the attachments and clean the body
    $this->attachments = [$zipFile];
    $this->body        = "";

    // return the path to the file
    return $zipFile;
  }

  /**
   * Handler to send email using SMTP
   *
   * @author salvipascual
   */
  public function smtp($host, $user, $pass, $port, $security) {
    // create mailer
    $mailer = new Nette\Mail\SmtpMailer([
      'host'     => $host,
      'username' => $user,
      'password' => $pass,
      'port'     => $port,
      'secure'   => $security,
    ]);

    // subject has to be UTF-8
    $this->subject = utf8_encode($this->subject);

    // create message
    $mail = new Message;
    $mail->setFrom($this->from);
    $mail->addTo($this->to);
    $mail->setSubject($this->subject);
    $mail->setHtmlBody($this->body, FALSE);
    $mail->setReturnPath($this->from);
    $mail->setHeader('X-Mailer', '');
    $mail->setHeader('Sender', $this->from);
    $mail->setHeader('In-Reply-To', $this->replyId);
    $mail->setHeader('References', $this->replyId);

    // add images to the template
    if (is_array($this->images)) {
      foreach ($this->images as $image) {
        if (file_exists($image)) {
          $inline = $mail->addEmbeddedFile($image);
          $inline->setHeader("Content-ID", basename($image));
        }
      }
    }

    // add attachments
    if (is_array($this->attachments)) {
      foreach ($this->attachments as $attachment) {
        if (file_exists($attachment)) {
          $mail->addAttachment($attachment);
        }
      }
    }

    // create the response code and message
    $output          = new stdClass();
    $output->code    = "200";
    $output->message = "Sent to {$this->to}";

    // send email
    try {
      $mailer->send($mail, FALSE);
    } catch (Exception $e) {
      $output->code    = "500";
      $output->message = $e->getMessage();

      // create notice that the service failed
      Utils::createAlert("[{$this->method}] {$output->message}");
    }

    return $output;
  }

  /**
   * Send via SSH
   *
   * @return mixed
   */
  public function sendEmailViaSSH() {

    $di = \Phalcon\DI\FactoryDefault::getDefault();
    $wwwroot = $di->get('path')['root'];
    $logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/ssh2smtp.log");

    $this->method = "ssh";

    // rotate failover
    Connection::query("UPDATE person SET failover = 0 
                          WHERE datediff(current_date, date(failover_date)) > 7
                          AND failover = 2;");

    $tries = 4;
    do
    {
      $tries--;

      // get last failover
      $failover = Connection::query("
          SELECT B.email, A.pass
          FROM authentication A JOIN person B
          ON A.person_id = B.id
          WHERE B.failover = 2;
      ");

      if (isset($failover[0]) && isset($failover[0]->email))
      {
        $auth = $failover[0];
      }
      else // borrow a random Nauta account
      {
        $auth = Connection::query("
          SELECT B.email, A.pass
          FROM authentication A JOIN person B
          ON A.person_id = B.id
          WHERE B.active = 1
          -- AND B.last_access > DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 30 DAY)
          AND B.email LIKE '%nauta.cu'
          AND A.appname = 'apretaste'
          AND A.pass IS NOT NULL AND A.pass <> ''
          AND B.failover = 1
          ORDER BY RAND() LIMIT 1")[0];

          if (!isset($auth->email))
          {
            // no more failovers, reset accounts
            Connection::query("UPDATE person set failover = 1 WHERE email LIKE '%nauta.cu';");
            $tries--;
            continue;
          }

          Connection::query("UPDATE person set failover = 2, failover_date = current_date WHERE email = '{$auth->email}';");
      }

      $body = Connection::query("
      SELECT text FROM `_pizarra_notes`
      ORDER BY RAND() LIMIT 1;");

      $body = $body[0]->text;

      // get pass decrypted
      $pass = Utils::decrypt($auth->pass);

      //Initialise the cURL var
      $ch = curl_init();

      //Get the response from cURL
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

      //Set the Url
      curl_setopt($ch, CURLOPT_URL, 'http://10.0.0.10/web2smtp/');

      $attach = empty($this->attachments) ? FALSE : $this->attachments[0];
      $attach = basename($attach);

      $person_from = Utils::getPerson($auth->email);
      $person_to = Utils::getPerson($this->to);

      $postData = [
        'smtp_user'  => $auth->email,
        'smtp_passw' => $pass,
        'to'         => $this->to,
        'subject'    => $this->subject,
        'body'       => $body,
        'attachment' => $attach,
        'from_name' => $person_from->full_name,
        'to_name' => $person_to->full_name
      ];

      $logger->log("SEND post to ssh webhook: ".json_encode($postData)."\n");

      curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

      // Execute the request
      $response = curl_exec($ch);
      $response = json_decode($response);
      $response->code = $response->result ? 200 : 500;

      // marcar failover utilizado
      if ($response->code != 200)
      {
        // trash failover
        Connection::query("UPDATE person set failover = 0 WHERE email = '{$auth->email}';");
      } else
        Connection::query("UPDATE person set failover = 1 WHERE failover = 2 and email <> '{$auth->email}';");

    } while ($response->code != 200 && $tries > 0);

    $logger->log("RESPONSE: ".json_encode($response)."\n");

    $logger->close();

    return $response;
  }
}
