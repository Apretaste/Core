<?php

/**
 * Add domains automatically when it is needed
 */

use Mailgun\Mailgun;

class autodomainTask extends \Phalcon\Cli\Task
{
	public function mainAction()
	{
		$timeStart  = time();

		// get the API key and start MailGun client
		$mailgunKey = $this->di->get('config')['mailgun']['key'];
		$mgClient = new Mailgun($mailgunKey);

		// start GoDaddy client params
		$GodaddyKey = $this->di->get('config')['godaddy']['key'];
		$GodaddySecret = $this->di->get('config')['godaddy']['secret'];

		//
		// get the list of domains to add
		//
		$connection = new Connection();
		$autoDomains = $connection->deepQuery("SELECT domain, `group`, default_service FROM autodomains");

		// loop the list of domains and add each one
		foreach ($autoDomains as $item)
		{
			$domain = $item->domain;
			$group = $item->group;
			$defaultService = $item->default_service;
			$log = "\n$domain\n";
			echo "\n$domain\n";

			//
			// add a new domain in MailGun and get array of records
			//
			try{
				$mgClient->post("domains", array('name' => $domain));
			}catch(Exception $e){}
			$result = $mgClient->get("domains/$domain");
			$records = array_merge($result->http_response_body->sending_dns_records, $result->http_response_body->receiving_dns_records);
			echo "\tNEW DOMAIN CREATED IN MAILGUN\n";
			$log .= "\tNEW DOMAIN CREATED IN MAILGUN\n";

			// create the records to update in Godaddy
			$GodaddyRecords = array();
			foreach ($records as $record)
			{
				// add TXT records
				if($record->record_type == "TXT")
				{
					$GodaddyRecords[] = array(
						"type" => "TXT",
						"name" => $record->name,
						"data" => $record->value
					);
				}

				// add MX records
				if($record->record_type == "MX")
				{
					$GodaddyRecords[] = array(
						"type" => "MX",
						"name" => "@",
						"data" => $record->value,
						"priority" => $record->priority
					);
				}

				// add CNAME records
				if($record->record_type == "CNAME")
				{
					$GodaddyRecords[] = array(
						"type" => "CNAME",
						"name" => $record->name,
						"data" => $record->value
					);
				}
			}

			//
			// update the new domain records in Godaddy
			//
			$url = "https://api.godaddy.com/v1/domains/$domain/records";
			$headers = array("Authorization:sso-key $GodaddyKey:$GodaddySecret", "Content-Type:application/json", "Accept:application/json");
			$fields = json_encode($GodaddyRecords, JSON_NUMERIC_CHECK);

			// fire the CURL request
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
			curl_setopt($ch, CURLOPT_HEADER, 1);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
			$output = curl_exec($ch);
			if($output === false) die("Curl error: " . curl_error($ch));
			curl_close($ch);
			echo "\tRECORDS UPDATED IN GODADDY\n";
			$log .= "\tRECORDS UPDATED IN GODADDY\n";

			//
			// verify the domain in MailGun
			//
			try{
				$mgClient->put("domains/$domain/verify", array());
			}catch(Exception $e){ die("Error verifying domain"); }
			echo "\tDOMAIN VERIFIED IN MAILGUN\n";
			$log .= "\tDOMAIN VERIFIED IN MAILGUN\n";

			//
			// configure the domain to work for Apretaste
			//
			$connection->deepQuery("
				INSERT INTO domain (domain, default_service, `group`) VALUES ('$domain', '$defaultService', '$group');
				DELETE FROM autodomains WHERE domain = '$domain';");
			echo "\tDOMAIN ADDING TO APRETASTE DATABASE\n";
			$log .= "\tDOMAIN ADDING TO APRETASTE DATABASE\n";

			//
			// add the bounce webhook in MailGun
			//
			try{
				$mgClient->post("domains/$domain/webhooks", array(
					'id'  => 'drop',
					'url' => 'https://apretaste.com/webhook/dropped'
				));
			}catch(Exception $e){}
			echo "\tWEBHOOKS CREATED IN MAILGUN\n\n";
			$log .= "\tWEBHOOKS CREATED IN MAILGUN\n\n";

			// saving the log
			$wwwroot = $this->di->get('path')['root'];
			$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/autodomain.log");
			$logger->log($log);
			$logger->close();
		}

		// get final delay
		$timeEnd = time();
		$timeDiff = $timeEnd - $timeStart;

		// save the status in the database
		$connection->deepQuery("UPDATE task_status SET executed=CURRENT_TIMESTAMP, delay='$timeDiff' WHERE task='autodomain'");
	}
}
