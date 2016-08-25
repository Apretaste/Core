<?php

/**
 * Remarket to users who only used 20% of our visible services
 * 
 **/
class lessusageTask extends \Phalcon\Cli\Task
{

	public function mainAction()
	{
		// inicialize supporting classes
		$timeStart = time();
		$connection = new Connection();
		$email = new Email();
		$service = new Service();
		$service->showAds = true;
		$render = new Render();
		$response = new Response();
		$utils = new Utils();
		$wwwroot = $this->di->get('path')['root'];
		$log = "";
		
		// list and total of services
		$services = $connection->deepQuery("SELECT name, description FROM service WHERE name <> 'ayuda' AND name <> 'terminos' AND name <> 'excluyeme' AND listed = 1;");
		
		$arr = array();
		foreach ($services as $servicex)
		{
			$arr[$servicex->name] = $servicex;
		}
		
		$services = $arr;
		$total_services = count($services);
		
		// users by service
		$sql_users_by_service = "SELECT requestor, service FROM utilization WHERE service <> 'rememberme' GROUP BY requestor, service";
		
		// usage of services
		$sql_usage = "SELECT requestor, count(service) as part, $total_services as total FROM ($sql_users_by_service) subq1 GROUP BY requestor";
		
		// filtering by less usage
		$sql_less_usage = "SELECT requestor as email, (SELECT sent FROM remarketing WHERE remarketing.email = subq2.requestor ORDER BY sent DESC LIMIT 1) as last_remarketing FROM ($sql_usage) subq2 WHERE part/total <= 0.2 ";
		
		// filtering by last remarketing (one by month)
		$sql = "SELECT email FROM ($sql_less_usage) subq3 WHERE datediff(CURRENT_DATE, last_remarketing) > 30 or datediff(CURRENT_DATE, last_remarketing) is null group by email";
		
		$users = $connection->deepQuery("$sql;");
		
		// send the remarketing
		$log .= "\nLESS USAGE REMARKETING (" . count($users) . ")\n";
		foreach ($users as $person)
		{
			// all services
			$his_services = $services;
			
			// getting services used by user
			$services_of_user = $connection->deepQuery("SELECT service as name FROM ($sql_users_by_service) subq1 WHERE requestor = '{$person->email}';");
			
			// remove services used by user from the list
			foreach($services_of_user as $servicex){
				if (isset($his_services[$servicex->name]))
					unset($his_services[$servicex->name]);
			}
			
			// create the variabels to pass to the template
			$content = array(
				"services" => $his_services
			);
			
			// create html response
			$response->createFromTemplate('lessusage.tpl', $content);
			$response->internal = true;
			$html = $render->renderHTML($service, $response);
			
			// move remarketing to the next state and add $1 to his/her account
			$email->sendEmail($person->email, "Lo que te estas perdiendo en Apretaste", $html);
			
			// move remarketing to the next state and add +1 credits
			$connection->deepQuery("INSERT INTO remarketing(email, type) VALUES ('{$person->email}', 'LESSUSAGE');");
			
			// display notifications
			$log .= "\t{$person->email}\n";
		}
		
		// get final delay
		$timeEnd = time();
		$timeDiff = $timeEnd - $timeStart;
		
		// printing log
		$log .= "EXECUTION TIME: $timeDiff seconds\n\n";
		echo $log;
		
		// saving the log
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/lessusage.log");
		$logger->log($log);
		$logger->close();
		
		// save the status in the database
		$connection->deepQuery("UPDATE task_status SET executed=CURRENT_TIMESTAMP, delay='$timeDiff' WHERE task='lessusage'");
	}
}
