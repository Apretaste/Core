<?php

use Mailgun\Mailgun;

class Utils
{
	/**
	 * Returns a valid Apretaste email to send an email
	 *
	 * @author salvipascual
	 * @return String, email address
	 */
	public function getValidEmailAddress()
	{
		$connection = new Connection();
		$sql = "SELECT email FROM jumper WHERE status='SendReceive' OR status='SendOnly' ORDER BY sent_count ASC LIMIT 1";
		$result = $connection->deepQuery($sql);
		return $result[0]->email;
	}


	/**
	 * Format a link to be an Apretaste mailto
	 *
	 * @author salvipascual
	 * @param String , name of the service
	 * @param String , name of the subservice, if needed
	 * @param String , pharse to search, if needed
	 * @param String , body of the email, if necessary
	 * @return String, link to add to the href section
	 */
	public function getLinkToService($service, $subservice=false, $parameter=false, $body=false)
	{
		$link = "mailto:".$this->getValidEmailAddress()."?subject=".strtoupper($service);
		if ($subservice) $link .= " $subservice";
		if ($parameter) $link .= " $parameter";
		if ($body) $link .= "&body=$body";
		return $link;
	}


	/**
	 * Check if the service exists
	 * 
	 * @author salvipascual
	 * @param String, name of the service
	 * @return Boolean, true if service exist
	 * */
	public function serviceExist($serviceName)
	{
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];
		return file_exists("$wwwroot/services/$serviceName/config.xml");
	}


	/**
	 * Check if the Person exists in the database
	 * 
	 * @author salvipascual
	 * @param String $personEmail, email of the person
	 * @return Boolean, true if Person exist
	 * */
	public function personExist($personEmail)
	{
		$connection = new Connection();
		$res = $connection->deepQuery("SELECT email FROM person WHERE LOWER(email)=LOWER('$personEmail')");
		return count($res) > 0;
	}


	/**
	 * Check if the Person was invited and is still pending 
	 *
	 * @author salvipascual
	 * @param String $personEmail, email of the person
	 * @return Boolean, true if Person invitation is pending
	 * */
	public function checkPendingInvitation($email)
	{
		$connection = new Connection();
		$res = $connection->deepQuery("SELECT * FROM invitations WHERE email_invited='$email' AND used=0");
		return count($res) > 0;
	}


	/**
	 * Get a person's profile
	 *
	 * @author salvipascual
	 * @return Array or false
	 * */
	public function getPerson($email)
	{
		// get the person
		$connection = new Connection();
		$person = $connection->deepQuery("SELECT * FROM person WHERE email = '$email'");

		// return false if there is no person with that email
		if (count($person)==0) return false;
		else $person = $person[0];

		// get number of tickets for the raffle adquired by the user
		$tickets = $connection->deepQuery("SELECT count(*) as tickets FROM ticket WHERE raffle_id is NULL AND email = '$email'");
		$tickets = $tickets[0]->tickets;

		// get the person's full name
		$fullName = "{$person->first_name} {$person->middle_name} {$person->last_name} {$person->mother_name}";
		$fullName = trim(preg_replace("/\s+/", " ", $fullName));

		// get the image of the person
		$image = NULL;
		$thumbnail = NULL;
		if($person->picture)
		{
			$di = \Phalcon\DI\FactoryDefault::getDefault();
			$wwwroot = $di->get('path')['root'];

			if(file_exists("$wwwroot/public/profile/$email.jpg")) 
			{
				$image = "$wwwroot/public/profile/$email.jpg";
			}

			if(file_exists("$wwwroot/public/profile/thumbnail/$email.jpg"))
			{ 
				$thumbnail = "$wwwroot/public/profile/thumbnail/$email.jpg";
			}
		}

		// get the interests as an array
		$person->interests = preg_split('@,@', $person->interests, NULL, PREG_SPLIT_NO_EMPTY);

		// remove all whitespaces at the begining and ending
		foreach ($person as $key=>$value)
		{
			if( ! is_array($value)) $person->$key = trim($value); 
		}

		// add elements to the response
		$person->full_name = $fullName;
		$person->picture = $image;
		$person->thumbnail = $thumbnail;
		$person->raffle_tickets = $tickets;
		return $person;
	}


	/**
	 * Get the path to a service. 
	 * 
	 * @author salvipascual
	 * @param String $serviceName, name of the service to access
	 * @return String, path to the service, or false if the service do not exist
	 * */
	public function getPathToService($serviceName)
	{
		// get the path to service 
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];
		$path = "$wwwroot/services/$serviceName";

		// check if the path exist and return it
		if(file_exists($path)) return $path;
		else return false;
	}


	/**
	 * Return the current Raffle or false if no Raffle was found
	 * 
	 * @author salvipascual
	 * @return Array or false
	 * */
	public function getCurrentRaffle()
	{
		// get the raffle
		$connection = new Connection();
		$raffle = $connection->deepQuery("SELECT * FROM raffle WHERE CURRENT_TIMESTAMP BETWEEN start_date AND end_date");

		// return false if there is no open raffle
		if (count($raffle)==0) return false;
		else $raffle = $raffle[0];

		// get number of tickets opened
		$openedTickets = $connection->deepQuery("SELECT count(*) as opened_tickets FROM ticket WHERE raffle_id is NULL");
		$openedTickets = $openedTickets[0]->opened_tickets;

		// get the image of the raffle
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];
		$raffleImage = "$wwwroot/public/raffle/" . md5($raffle->raffle_id) . ".jpg";

		// add elements to the response
		$raffle->tickets = $openedTickets;
		$raffle->image = $raffleImage;

		return $raffle;
	}


	/**
	 * Generate a new random hash. Mostly to be used for temporals
	 *
	 * @author salvipascual
	 * @return String
	 */
	public function generateRandomHash()
	{
		$rand = rand(0, 1000000);
		$today = date('full');
		return md5($rand . $today);
	}


	/**
	 * Reduce image size and optimize the image quality
	 * 
	 * @TODO Find an faster image optimization solution
	 * @author salvipascual
	 * @param String $imagePath, path to the image
	 * */
	public function optimizeImage($imagePath, $width="", $height="")
	{
		if(empty($width) && empty($height)) $resize = "";
		else $resize = "-resize ".$width."x".$height;

		shell_exec("/usr/bin/convert $resize ".$imagePath."[0] ".$imagePath);
	}


	/**
	 * Add a new subscriber to the email list in Mail Lite
	 * 
	 * @author salvipascual
	 * @param String email
	 * */
	public function subscribeToEmailList($email)
	{
		// get the path to the www folder
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];

		// get the key from the config
		$mailerLiteKey = $di->get('config')['mailerlite']['key'];

		// adding the new subscriber to the list
		include_once "$wwwroot/lib/mailerlite-api-php-v1/ML_Subscribers.php";
		$ML_Subscribers = new ML_Subscribers($mailerLiteKey);
		$subscriber = array('email' => $email, 'resubscribe' => 1);
		$ML_Subscribers->setId("1266487")->add($subscriber);
	}


	/**
	 * Delete a subscriber from the email list in Mail Lite
	 * 
	 * @author salvipascual
	 * @param String email
	 * */
	public function unsubscribeFromEmailList($email)
	{
		// get the path to the www folder
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];

		// get the key from the config
		$mailerLiteKey = $di->get('config')['mailerlite']['key'];

		// adding the new subscriber to the list
		include_once "$wwwroot/lib/mailerlite-api-php-v1/ML_Subscribers.php";
		$ML_Subscribers = new ML_Subscribers($mailerLiteKey);		
		$ML_Subscribers->setId("1266487")->remove($email);
	}


	/**
	 * Get the pieces of names from the full name
	 *
	 * @author hcarras
	 * @param String $name, full name
	 * @return Array [$firstName, $middleName, $lastName, $motherName]
	 * */
	public function fullNameToNamePieces($name)
	{
		$namePieces = explode(" ", $name);
		$newNamePieces = array();
		$tmp = "";

		foreach ($namePieces as $piece)
		{
			$tmp .= "$piece ";
		
			if(in_array(strtoupper($piece), array("DE","LA","Y","DEL")))
			{
				continue;
			}
			else
			{
				$newNamePieces[] = $tmp;
				$tmp = "";
			}
		}

		$firstName = "";
		$middleName = "";
		$lastName = "";
		$motherName = "";

		if(count($newNamePieces)>=4)
		{
			$firstName = $newNamePieces[0];
			$middleName = $newNamePieces[1];
			$lastName = $newNamePieces[2];
			$motherName = $newNamePieces[3];
		}

		if(count($newNamePieces)==3)
		{
			$firstName = $newNamePieces[0];
			$lastName = $newNamePieces[1];
			$motherName = $newNamePieces[2];
		}

		if(count($newNamePieces)==2)
		{
			$firstName = $newNamePieces[0];
			$lastName = $newNamePieces[1];
		}

		if(count($newNamePieces)==1)
		{
			$firstName = $newNamePieces[0];
		}

		return array($firstName, $middleName, $lastName, $motherName);
	}


	/**
	 * Checks if an email can be delivered to a certain mailbox
	 *
	 * @author salvipascual
	 * @param String $to, email address of the receiver
	 * @param Enum $direction, in or out, if we check an email received or sent
	 * @return String delivability: ok, hard-bounce, soft-bounce, spam, no-reply, loop, unknown
	 * */
	public function deliveryStatus($to, $direction="out")
	{
		// save the final response. If not ok, will return on the LogErrorAndReturn tag
		$response = '';

		// create a new connection object
		$connection = new Connection();

		// block people following the example email
		if($to == "su@amigo.cu") {$response = 'hard-bounce'; goto LogErrorAndReturn;}

		// block email from/to our customer support 
		if($to == "soporte@apretaste.com" ||
			$to == "comentarios@apretaste.com" ||
			$to == "contacto@apretaste.com" ||
			$to == "soporte@apretastes.com" ||
			$to == "comentarios@apretastes.com" ||
			$to == "contacto@apretastes.com" ||
			$to == "support@apretaste.zendesk.com" || 
			$to == "support@apretaste.com" ||
			$to == "apretastesoporte@gmail.com"
		) {$response = 'loop'; goto LogErrorAndReturn;}

		// block intents to email the deamons
		if(stripos($to,"mailer-daemon@")!==false || 
			stripos($to,"communicationservice.nl")!==false
		) {$response = 'hard-bounce'; goto LogErrorAndReturn;}

		// check if the email is formatted properly
		if ( ! filter_var($to, FILTER_VALIDATE_EMAIL)) {$response = 'hard-bounce'; goto LogErrorAndReturn;}

		// block no reply emails
		if(stripos($to,"not-reply")!==false ||
			stripos($to,"notreply")!==false ||
			stripos($to,"No_Reply")!==false ||
			stripos($to,"Do_Not_Reply")!==false ||
			stripos($to,"no-reply")!==false ||
			stripos($to,"noreply")!==false ||
			stripos($to,"no-responder")!==false ||
			stripos($to,"noresponder")!==false
		) {$response = 'no-reply'; goto LogErrorAndReturn;}

		// block any previouly dropped email
		$res = $connection->deepQuery("SELECT email FROM delivery_dropped WHERE email='$to'");
		if( ! empty($res)) {$response = 'loop'; goto LogErrorAndReturn;}

		// block emails from apretaste to apretaste
		$mailboxes = $connection->deepQuery("SELECT email FROM jumper");
		foreach($mailboxes as $m) if($to == $m->email) {$response = 'loop'; goto LogErrorAndReturn;}
/*
		// check for valid domain
		$mgClient = new Mailgun("pubkey-f04b8b05d4030df391a8578062aac53e");
		$result = $mgClient->get("address/validate", array('address' => $to));
		if( ! $result->http_response_body->is_valid) {$response = 'hard-bounce'; goto LogErrorAndReturn;}
*/
		// check deeper for new people. Only check deeper the outgoing emails
		if( ! $this->personExist($to) && $direction=="out")
		{
			// use the cache if the email was checked before
			$res = $connection->deepQuery("SELECT status FROM delivery_checked WHERE email='$to' LIMIT 1");

			// if the email hasen't been tested before, check
			if(empty($res))
			{
				$di = \Phalcon\DI\FactoryDefault::getDefault();
				$key = $di->get('config')['emailvalidator']['key'];
				$result = json_decode(@file_get_contents("https://api.email-validator.net/api/verify?EmailAddress=$to&APIKey=$key"));
				if($result)
				{
					// save all emails tested by the email validador to ensure no errors are happening
					$status = $result->status;
					$connection->deepQuery("INSERT INTO delivery_checked (email,status) VALUES ('$to','$status')");
				}
				else
				{
					throw new Exception("Error connecting emailvalidator for user $to at ".date());
				}
			}
			else // for emails previously tested, use the cache
			{
				$status = $res[0]->status;
			}

			// get the result for each status code
			if($status == 114) {$response = 'unknown'; goto LogErrorAndReturn;} // usually national email
			if($status > 300 && $status < 399) {$response = 'soft-bounce'; goto LogErrorAndReturn;}
			if($status > 400 && $status < 499) {$response = 'hard-bounce'; goto LogErrorAndReturn;}
		}

		// when no errors were found
		return 'ok';

		// log errors in the database before returning
		// and YES, I am using GOTO
		LogErrorAndReturn:
		$connection->deepQuery("INSERT INTO delivery_dropped(email,reason,description) VALUES ('$to','$response','$direction')");
		return $response;
	}


	/**
	 * Get the completion percentage of a profile
	 *
	 * @author kuma, updated by salvipascual
	 * @param String, email of the person
	 * @return Number, percentage of completion
	 * */
	public function getProfileCompletion($email)
	{
		$profile = $this->getPerson($email);
		$percent = 0;

		if($profile)
		{
			$keys = get_object_vars($profile);
			$parts = 0;
			$total = count($keys);

			foreach($keys as $key=>$value)
			{
				// do not count non-required values
				if(
					$key == "middle_name" ||
					$key == "mother_name" ||
					$key == "about_me" ||
					$key == "updated_by_user" ||
					$key == "raffle_tickets" ||
					$key == "last_update_date" ||
					$key == "phone" ||
					$key == "cellphone" ||
					$key == "credit"
				) {$total--; continue;}

				// add non-empty values to the formula 
				if( ! empty($value)) $parts++;
			}

			// calculate percentage
			$percent = $parts / $total * 100;
		}
		return $percent;
	}
}
