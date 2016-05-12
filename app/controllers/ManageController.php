<?php

use Phalcon\Mvc\Controller;

class ManageController extends Controller
{
	/**
	 * Index for the manage system
	 * */
	public function indexAction()
	{
		$connection = new Connection();
		$wwwroot = $this->di->get('path')['root'];

		// START revolico widget
		$revolicoCrawlerFile = "$wwwroot/temp/crawler.revolico.last.run";
		$revolicoCrawler = array();
		if(file_exists($revolicoCrawlerFile))
		{
			$details = file_get_contents($revolicoCrawlerFile);
			$details = explode("|", $details);

			$revolicoCrawler["LastRun"] = date("D F j, h:i A", strtotime($details[0])); 
			$revolicoCrawler["TimeBehind"] = (time() - strtotime($details[0])) / 60 / 60; 
			$revolicoCrawler["RuningTime"] = number_format($details[1], 2);
			$revolicoCrawler["PostsDownloaded"] = $details[2];
			$revolicoCrawler["RuningMemory"] = $details[3];
		}
		// END revolico widget

		// START delivery status widget
		$delivered = $connection->deepQuery("SELECT COUNT(id) as sent FROM delivery_sent WHERE inserted > DATE_SUB(NOW(), INTERVAL 7 DAY)");
		$dropped = $connection->deepQuery("SELECT COUNT(*) AS number, reason FROM delivery_dropped  WHERE inserted > DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY reason");
		$delivery = array("delivered"=>$delivered[0]->sent);
		foreach ($dropped as $r) $delivery[$r->reason] = $r->number;
		$failurePercentage = ((isset($delivery['hardfail']) ? $delivery['hardfail'] : 0) * 100) / $delivered[0]->sent;
		// END delivery status widget

		// START remarketing widget
		$rmStatus = $connection->deepQuery("SELECT * FROM task_status WHERE task = 'remarketing'")[0];
		$rmStatus->behind = (time() - strtotime($rmStatus->executed)) / 60 / 60;
		// END remarketing widget

		$this->view->title = "Home";
		$this->view->revolicoCrawler = $revolicoCrawler;
		$this->view->delivery = $delivery;
		$this->view->deliveryFailurePercentage = number_format($failurePercentage, 2);
		$this->view->remarketingStatus = $rmStatus;
	}


	/**
	 * Audience
	 * */
	public function audienceAction()
	{
		$connection = new Connection();

		// START weekly visitors
		$query = 
			"SELECT A.received, B.sent, A.inserted
			FROM (SELECT count(*) as received, DATE(request_time) as inserted FROM utilization GROUP BY DATE(request_time) ORDER BY inserted DESC LIMIT 7) A
			LEFT JOIN (SELECT count(*) as sent, DATE(inserted) as inserted FROM delivery_sent GROUP BY DATE(inserted) ORDER BY inserted DESC LIMIT 7) B
			ON A.inserted = B.inserted";
		$visits = $connection->deepQuery($query);
		$visitorsWeecly = array();
		foreach($visits as $visit)
		{
			if( ! $visit->received) $visit->received = 0;
			if( ! $visit->sent) $visit->sent = 0;
			$visitorsWeecly[] = ["date"=>date("D jS", strtotime($visit->inserted)), "received"=>$visit->received, "sent"=>$visit->sent];
		}
		$visitorsWeecly = array_reverse($visitorsWeecly);
		// END weekly visitors


		// START monthly visitors
		$query =
			"SELECT A.received, B.sent, A.inserted
			FROM (SELECT count(*) as received, DATE_FORMAT(request_time,'%Y-%m') as inserted FROM utilization GROUP BY DATE_FORMAT(request_time,'%Y-%m') ORDER BY inserted DESC LIMIT 30) A
			LEFT JOIN (SELECT count(*) as sent, DATE_FORMAT(inserted,'%Y-%m') as inserted FROM delivery_sent GROUP BY DATE_FORMAT(inserted,'%Y-%m') ORDER BY inserted DESC LIMIT 30) B
			ON A.inserted = B.inserted";
		$visits = $connection->deepQuery($query);
		$visitorsMonthly = array();
		foreach($visits as $visit)
		{
			if( ! $visit->received) $visit->received = 0;
			if( ! $visit->sent) $visit->sent = 0;
			$visitorsMonthly[] = ["date"=>date("M Y", strtotime($visit->inserted)), "received"=>$visit->received, "sent"=>$visit->sent];
		}
		$visitorsMonthly = array_reverse($visitorsMonthly);
		// End monthly Visitors


		// START monthly unique visitors
		$query =
			"SELECT A.unique_visitors, B.new_visitors, A.inserted
			FROM (SELECT COUNT(DISTINCT requestor) as unique_visitors, DATE_FORMAT(request_time,'%Y-%m') as inserted FROM utilization GROUP BY DATE_FORMAT(request_time,'%Y-%m') ORDER BY inserted DESC LIMIT 30) A
			JOIN (SELECT COUNT(DISTINCT email) as new_visitors, DATE_FORMAT(insertion_date,'%Y-%m') as inserted FROM person GROUP BY DATE_FORMAT(insertion_date,'%Y-%m') ORDER BY inserted DESC LIMIT 30) B
			ON A.inserted = B.inserted";
		$visits = $connection->deepQuery($query);
		$newUsers = array();
		foreach($visits as $visit)
		{
			$newUsers[] = ["date"=>date("M Y", strtotime($visit->inserted)), "unique_visitors"=>$visit->unique_visitors, "new_visitors"=>$visit->new_visitors];
		}
		$newUsers = array_reverse($newUsers);
		// END monthly unique visitors


		// START current number of Users
		$queryCurrentNoUsers = "SELECT COUNT(email) as CountUsers FROM person";
		$currentNoUsers = $connection->deepQuery($queryCurrentNoUsers);
		// END Current number of Users


		// START monthly services usage
		$query = "SELECT service, COUNT(service) as times_used FROM utilization WHERE request_time > DATE_SUB(NOW(), INTERVAL 1 MONTH) GROUP BY service DESC";
		$visits = $connection->deepQuery($query);
		$servicesUsageMonthly = array();
		foreach($visits as $visit)
		{
			$servicesUsageMonthly[] = ["service"=>$visit->service, "usage"=>$visit->times_used];
		}
		// END monthly services usage


		// START active domains last 4 months
		$query = 
			"SELECT domain, count(domain) as times_used
			FROM utilization
			WHERE PERIOD_DIFF(DATE_FORMAT(NOW(), '%Y%m'), DATE_FORMAT(request_time, '%Y%m')) < 4
			GROUP BY domain
			ORDER BY times_used DESC";
		$visits = $connection->deepQuery($query);
		$activeDomainsMonthly = array();
		foreach($visits as $visit)
		{
			$activeDomainsMonthly[] = ["domain"=>$visit->domain, "usage"=>$visit->times_used];
		}
		// END active domains last 4 months


		// START bounce rate
		$query = "SELECT B.* FROM (";
		for($i=0; $i<12; $i++)
		{
			$date = date("Y-m", strtotime("-$i months"));
			$query .= "SELECT COUNT(A.b) as bounced, '$date' as date FROM (SELECT COUNT(requestor) as b FROM utilization WHERE DATE_FORMAT(request_time,'%Y-%m') = '$date' GROUP BY requestor HAVING b = 1) A";
			if($i!=11) $query .= " UNION ";
		}
		$query .= ") B WHERE bounced > 0 ORDER BY date";
		$visits = $connection->deepQuery($query);
		$bounceRateMonthly = array();
		foreach($visits as $visit)
		{
			$bounceRateMonthly[] = ["date"=>$visit->date, "bounced"=>$visit->bounced];
		}
		//End bounce rate


		// START updated profiles
		$query =
		"SELECT count(email) as num_profiles, DATE_FORMAT(last_update_date,'%Y-%m') as last_update
		FROM person
		WHERE last_update_date IS NOT NULL
		GROUP BY last_update
		ORDER BY last_update DESC
		LIMIT 30";
		$visits = $connection->deepQuery($query);
		$updatedProfilesMonthly = array();
		foreach($visits as $visit)
		{
			$updatedProfilesMonthly[] = ["date"=>date("M Y", strtotime($visit->last_update)), "profiles"=>$visit->num_profiles];
		}
		$updatedProfilesMonthly = array_reverse($updatedProfilesMonthly);
		// END updated profiles


		// START current number of running ads
		$queryRunningAds = "SELECT COUNT(active) AS CountAds FROM ads WHERE active=1";
		$runningAds = $connection->deepQuery($queryRunningAds);
		// END current number of running ads


		// send variables to the view
		$this->view->title = "Audience";
		$this->view->visitorsWeecly = $visitorsWeecly;
		$this->view->visitorsMonthly = $visitorsMonthly;
		$this->view->newUsers = $newUsers;
		$this->view->currentNumberOfActiveUsers = $currentNoUsers[0]->CountUsers;
		$this->view->servicesUsageMonthly = $servicesUsageMonthly;
		$this->view->activeDomainsMonthly = $activeDomainsMonthly;
		$this->view->bounceRateMonthly = $bounceRateMonthly;
		$this->view->updatedProfilesMonthly = $updatedProfilesMonthly;
		$this->view->currentNumberOfRunningaAds = $runningAds[0]->CountAds;
	}


	/**
	 * Profile
	 * */
	public function profileAction()
	{
		$connection = new Connection();
	
		// Users with profile vs users without profile
		//Users with profiles
		$queryUsersWithProfile = "SELECT COUNT(email) AS PersonWithProfiles FROM person WHERE updated_by_user = 1";
		$usersWithProfile = $connection->deepQuery($queryUsersWithProfile);

		//Users without profiles
		$queryUsersWithOutProfile = "SELECT COUNT(email) AS PersonWithOutProfiles FROM person WHERE updated_by_user = 0";
		$usersWithOutProfile = $connection->deepQuery($queryUsersWithOutProfile);
		//End Users with profile vs users without profile

		// Profile completion
		$queryProfileData = "
			SELECT 'Name' AS Caption, COUNT(first_name) AS Number FROM person WHERE updated_by_user IS NOT NULL AND (first_name IS NOT NULL OR last_name IS NOT NULL OR middle_name IS NOT NULL OR mother_name IS NOT NULL)
			UNION
			SELECT 'DOB' AS Caption, COUNT(date_of_birth) AS Number FROM person WHERE updated_by_user IS NOT NULL AND date_of_birth IS NOT NULL
			UNION 
			SELECT 'Gender' AS Caption, COUNT(gender) AS Number FROM person WHERE updated_by_user IS NOT NULL AND gender IS NOT NULL
			UNION
			SELECT 'Phone' AS Caption, COUNT(phone) AS Number FROM person WHERE updated_by_user IS NOT NULL AND phone IS NOT NULL
			UNION
			SELECT 'Eyes' AS Caption, COUNT(eyes) AS Number FROM person WHERE updated_by_user IS NOT NULL AND eyes IS NOT NULL
			UNION
			SELECT 'Skin' AS Caption, COUNT(skin) AS Number FROM person WHERE updated_by_user IS NOT NULL AND skin IS NOT NULL
			UNION
			SELECT 'Body' AS Caption, COUNT(body_type) AS Number FROM person
			UNION 
			SELECT 'Picture' AS Picture, COUNT(picture) AS Number FROM person WHERE picture=1";
		$profileData = $connection->deepQuery($queryProfileData);

		foreach($profileData as $profilesList)
		{
			$percent = ($profilesList->Number * 100)/$usersWithProfile[0]->PersonWithProfiles;
			$percentFormated = number_format($percent, 2);
			$profilesData[] = ["caption"=>$profilesList->Caption, "number"=>$profilesList->Number, "percent"=>$percentFormated];
		}
		//End Profile completion
	
		// Numbers of profiles per province
		$queryPrefilesPerPravince = 
		"SELECT c.ProvCount,
			CASE c.mnth
				WHEN 'PINAR_DEL_RIO' THEN 'Pinar del RÃ­o'
				WHEN 'LA_HABANA' THEN 'Ciudad de La Habana'
				WHEN 'ARTEMISA' THEN 'CU-X01'
				WHEN 'MAYABEQUE' THEN 'CU-X02'
				WHEN 'MATANZAS' THEN 'Matanzas'
				WHEN 'VILLA_CLARA' THEN 'Villa Clara'
				WHEN 'CIENFUEGOS' THEN 'Cienfuegos'
				WHEN 'SANCTI_SPIRITUS' THEN 'Sancti SpÃ­ritus'
				WHEN 'CIEGO_DE_AVILA' THEN 'Ciego de Ã�vila'
				WHEN 'CAMAGUEY' THEN 'CamagÃ¼ey'
				WHEN 'LAS_TUNAS' THEN 'Las Tunas'
				WHEN 'HOLGUIN' THEN 'HolguÃ­n'
				WHEN 'GRANMA' THEN 'Granma'
				WHEN 'SANTIAGO_DE_CUBA' THEN 'Santiago de Cuba'
				WHEN 'GUANTANAMO' THEN 'GuantÃ¡namo'
				WHEN 'ISLA_DE_LA_JUVENTUD' THEN 'Isla de la Juventud'
			END as NewProv
		FROM (SELECT count(b.province) as ProvCount, a.mnth
				FROM(
					SELECT 'PINAR_DEL_RIO' mnth
					UNION ALL
					SELECT 'LA_HABANA' mnth
					UNION ALL
					SELECT 'ARTEMISA' mnth
					UNION ALL
					SELECT 'MAYABEQUE' mnth
					UNION ALL
					SELECT 'MATANZAS' mnth
					UNION ALL
					SELECT 'VILLA_CLARA' mnth
					UNION ALL
					SELECT 'CIENFUEGOS' mnth
					UNION ALL
					SELECT 'SANCTI_SPIRITUS' mnth
					UNION ALL
					SELECT 'CIEGO_DE_AVILA' mnth
					UNION ALL									
					SELECT 'CAMAGUEY' mnth
					UNION ALL
					SELECT 'LAS_TUNAS' mnth
					UNION ALL
					SELECT 'HOLGUIN' mnth
					UNION ALL
					SELECT 'GRANMA' mnth
					UNION ALL
					SELECT 'SANTIAGO_DE_CUBA' mnth
					UNION ALL
					SELECT 'GUANTANAMO' mnth
					UNION ALL
					SELECT 'ISLA_DE_LA_JUVENTUD' mnth
				) a
				LEFT JOIN person b
					ON BINARY a.mnth = BINARY b.province AND
						b.province IS not NULL AND 
						b.province IN ('PINAR_DEL_RIO', 'LA_HABANA', 'ARTEMISA', 'MAYABEQUE', 'MATANZAS', 'VILLA_CLARA', 'CIENFUEGOS', 'SANCTI_SPIRITUS', 'CIEGO_DE_AVILA', 'CAMAGUEY', 'LAS_TUNAS', 'HOLGUIN', 'GRANMA', 'SANTIAGO_DE_CUBA', 'GUANTANAMO', 'ISLA_DE_LA_JUVENTUD') 
			GROUP  BY b.province) as c";
		$prefilesPerPravinceList = $connection->deepQuery($queryPrefilesPerPravince);
	
		foreach($prefilesPerPravinceList as $profilesList)
		{
			if($profilesList->ProvCount != 0)
				$profilesPerProvince[] = ["region"=>$profilesList->NewProv, "profiles"=>$profilesList->ProvCount];
			else
				$profilesPerProvince[] = ["region"=>$profilesList->NewProv, "profiles"=>0];
		}
		// numbers of profiles per province
	
		// send variables to the view
		$this->view->title = "Profile";
		$this->view->usersWithProfile = $usersWithProfile[0]->PersonWithProfiles;
		$this->view->usersWithoutProfile = $usersWithOutProfile[0]->PersonWithOutProfiles;
		$this->view->profilesData = $profilesData;
		$this->view->profilesPerProvince = $profilesPerProvince;
	}


	/**
	 * Profile search
	 * */
	public function profilesearchAction()
	{
		if($this->request->isPost())
		{
			// get the email passed by post
			$connection = new Connection();
			$email = $this->request->getPost("email");

			// search for the user
			$querryProfileSearch = "SELECT first_name, middle_name, last_name, mother_name, date_of_birth, TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) AS Age, gender, phone, eyes, skin, body_type, hair, city, province, about_me, credit, picture, email FROM person WHERE email = '$email'";
			$profileSearch = $connection->deepQuery($querryProfileSearch);

			if($profileSearch)
			{
				//If the picture exist return the email, if not, return 0
				if($profileSearch[0]->picture == 1)
				{
					$this->view->picture = true;
					$this->view->email = $profileSearch[0]->email;
				}

				$this->view->firstName = $profileSearch[0]->first_name;
				$this->view->middleName = $profileSearch[0]->middle_name;
				$this->view->lastName = $profileSearch[0]->last_name;
				$this->view->motherName = $profileSearch[0]->mother_name;
				$this->view->dob = $profileSearch[0]->date_of_birth;
				$this->view->age = $profileSearch[0]->Age;
				$this->view->gender = $profileSearch[0]->gender;
				$this->view->phone = $profileSearch[0]->phone;
				$this->view->eyes = $profileSearch[0]->eyes;
				$this->view->skin = $profileSearch[0]->skin;
				$this->view->body = $profileSearch[0]->body_type;
				$this->view->hair = $profileSearch[0]->hair;
				$this->view->city = $profileSearch[0]->city;
				$this->view->province = $profileSearch[0]->province;
				$this->view->aboutMe = $profileSearch[0]->about_me;
				$this->view->credit = $profileSearch[0]->credit;
			}
			else
			{
				$this->view->profileNotFound = "Profile not found for user <b>$email</b>";
			}
		}

		$this->view->title = "Search for a profile";
	}


	/**
	 * List of raffles
	 * */
	public function rafflesAction()
	{
		$connection = new Connection();

		// List of raffles
		$query = 
			"SELECT A.item_desc, A.start_date, A.end_date, A.winner_1, A.winner_2, A.winner_3, count(B.raffle_id) as tickets
			FROM raffle A 
			LEFT JOIN ticket B
			ON A.raffle_id = B.raffle_id
			GROUP BY B.raffle_id
			ORDER BY end_date DESC";
		$visits = $connection->deepQuery($query);
		$raffleListCollection = array();
		foreach($visits as $visit)
		{
			$raffleListCollection[] = ["itemDesc"=>$visit->item_desc, "startDay"=>$visit->start_date, "finishDay"=>$visit->end_date, "winner1"=>$visit->winner_1, "winner2"=>$visit->winner_2, "winner3"=>$visit->winner_3, "tickets"=>$visit->tickets];
		}

		// get the current number of tickets
		$raffleCurrentTickets = $connection->deepQuery("SELECT count(ticket_id) as tickets FROM ticket WHERE raffle_id IS NULL");
		if($raffleListCollection[0]['tickets'] == 0) $raffleListCollection[0]['tickets'] = $raffleCurrentTickets[0]->tickets;

		// send values to the template
		$this->view->title = "List of raffles";
		$this->view->raffleListData = $raffleListCollection;
	}


	/**
	 * create raffle
	 * */
	public function createraffleAction()
	{
		if($this->request->isPost())
		{
			$description = $this->request->getPost("description");
			$startDate = $this->request->getPost("startDate") . " 00:00:00";
			$endDate = $this->request->getPost("endDate") . " 23:59:59";

			//Insert the Raffle
			$connection = new Connection();
			$queryInsertRaffle = "INSERT INTO raffle (item_desc, start_date, end_date) VALUES ('$description','$startDate','$endDate')";
			$insertRaffle = $connection->deepQuery($queryInsertRaffle);

			if($insertRaffle)
			{
				// get the last inserted raffle's id
				$queryGetRaffleID = "SELECT raffle_id FROM raffle WHERE item_desc = '$description' ORDER BY raffle_id DESC LIMIT 1";
				$getRaffleID = $connection->deepQuery($queryGetRaffleID);

				// get the picture name and path
				$wwwroot = $this->di->get('path')['root'];
				$fileName = md5($getRaffleID[0]->raffle_id);
				$picPath = "$wwwroot/public/raffle/$fileName.jpg";
				move_uploaded_file($_FILES["picture"]["tmp_name"], $picPath);

				// optimize the image
				$utils = new Utils();
				$utils->optimizeImage($picPath, 400);

				$this->view->raffleMessage = "Raffle inserted successfully";
			}
			else
			{
				$this->view->raffleError = "We had an error creating the raffle, please try again";
			}
		}

		$this->view->title = "Create raffle";
	}
		

	/**
	 * List of services
	 * */
	public function servicesAction()
	{
		$connection = new Connection();

		$queryServices = 
			"SELECT A.name, A.description, A.creator_email, A.category, A.insertion_date, A.listed, B.times_used, B.avg_latency
			FROM service A
			LEFT JOIN (SELECT service, COUNT(service) as times_used, AVG(response_time) as avg_latency FROM utilization WHERE request_time > DATE_SUB(NOW(), INTERVAL 1 MONTH) GROUP BY service) B
			ON A.name = B.service
			ORDER BY B.times_used DESC";
		$services = $connection->deepQuery($queryServices);

		$this->view->title = "List of services (".count($services).")";
		$this->view->services = $services;
	}


	/**
	 * List of ads
	 * */
	public function adsAction()
	{
		$connection = new Connection();

		$queryAdsActive = "SELECT id, owner, time_inserted, title, clicks, impresions, paid_date, expiration_date FROM ads";
		$ads = $connection->deepQuery($queryAdsActive);

		$this->view->title = "List of ads";
		$this->view->ads = $ads;
	}


	/**
	 * Manage the ads
	 * */
	public function createadAction()
	{
		// handle the submit if an ad is posted
		if($this->request->isPost())
		{
			$adsOwner = $this->request->getPost("owner");
			$adsTittle = $this->request->getPost("title");
			$AdsDesc = $this->request->getPost("description");
			$today = date("Y-m-d H:i:s"); // date the ad was posted
			$expirationDay = date("Y-m-d H:i:s", strtotime("+1 months"));

			// insert the ad
			$connection = new Connection();
			$queryInsertAds = "INSERT INTO ads (owner, title, description, expiration_date, paid_date) VALUES ('$adsOwner','$adsTittle','$AdsDesc', '$expirationDay', '$today')";
			$insertAd = $connection->deepQuery($queryInsertAds);

			if($insertAd)
			{
				if($_FILES["picture"]['error'] === 0)
				{
					$queryGetAdsID = "SELECT id FROM ads WHERE owner='$adsOwner' ORDER BY id DESC LIMIT 1";
					$getAdID = $connection->deepQuery($queryGetAdsID);
	
					// save the image
					$fileName = md5($getAdID[0]->id); //Generate the picture name
					$wwwroot = $this->di->get('path')['root'];
					$picPath = "$wwwroot/public/ads/$fileName.jpg";
					move_uploaded_file($_FILES["picture"]["tmp_name"], $picPath);
	
					// optimize the image
					$utils = new Utils();
					$utils->optimizeImage($picPath, 728, 90);
				}

				// confirm by email that the ad was inserted
				$email = new Email();
				$email->sendEmail($adsOwner, "Your ad is now running on Apretaste", "<h1>Your ad is running</h1><p>Your ad <b>$adsTittle</b> was set to run $today.</p><p>Thanks for advertising using Apretaste.</p>");

				$this->view->adMesssage = "Your ad was posted successfully";
			}
			else
			{
				$this->view->adError = "We had an error posting your ad, please try again";
			}
		}

		$this->view->title = "Create ad";
	}


	/**
	 * Jumper
	 * */
	public function jumperAction()
	{
		$connection = new Connection();

		$queryJumper = "SELECT email, last_usage, sent_count, blocked_domains, status FROM jumper ORDER BY last_usage DESC";
		$jumperData = $connection->deepQuery($queryJumper);

		$this->view->title = "Jumper";
		$this->view->jumperData = $jumperData;
	}


	/**
	 * Deploy a new service or update an old one
	 * */
	public function deployAction()
	{
		$this->view->title = "Deploy a service";

		// handle the submit if a service is posted
		if($this->request->isPost())
		{
			// check the file is a valid zip
			$fileNameArray = explode(".", $_FILES["service"]["name"]);
			$extensionIsZip = strtolower(end($fileNameArray)) == "zip";
			if ( ! $extensionIsZip)
			{
				$this->view->deployingError = "The file is not a valid zip";
				return;
			}

			// check the service zip size is less than 1MB
			if ($_FILES["service"]["size"] > 1048576)
			{
				$this->view->deployingError = "The file is too big. Our limit is 1 MB";
				return;
			}

			// check for errors
			if ($_FILES["service"]["error"] > 0)
			{
				$this->view->deployingError = "Unknow errors uploading your service. Please try again";
				return;
			}

			// include and initialice Deploy class
			$deploy = new Deploy();

			// get the zip name and path
			$utils = new Utils();
			$wwwroot = $this->di->get('path')['root'];
			$zipPath = "$wwwroot/temp/" . $utils->generateRandomHash() . ".zip";
			$zipName = basename($zipPath);

			// save file
			if (isset($_FILES["service"]["name"])) $zipName = $_FILES["service"]["name"];
			move_uploaded_file($_FILES["service"]["tmp_name"], $zipPath);
			chmod($zipPath, 0777);

			// check if the file was moved correctly
			if ( ! file_exists($zipPath))
			{
				$this->view->deployingError = "There was a problem uploading the file";
				return;
			}

			// deploy the service
			try
			{
				$deployResults = $deploy->deployServiceFromZip($zipPath, $zipName);
			}
			catch (Exception $e)
			{
				$error = preg_replace("/\r|\n/", "", $e->getMessage());
				$this->view->deployingError = $error;
				return;
			}

			// send email to the user with the deploy key
			$today = date("Y-m-d H:i:s");
			$serviceName = $deployResults["serviceName"];
			$creatorEmail = $deployResults["creatorEmail"];
			$email = new Email();
			$email->sendEmail($creatorEmail, "Your service $serviceName was deployed", "<h1>Service deployed</h1><p>Your service $serviceName was deployed on $today.</p>");

			// redirect to the upload page with success message
			$this->view->deployingMesssage = "Service <b>$serviceName</b> deployed successfully.";
		}
	}

	/**
	 * Show the dropped emails for the last 7 days
	 * */
	public function droppedAction()
	{
		// create the sql for the graph
		$sql = "";
		foreach (range(0,7) as $day)
		{
			$sql .= "
				SELECT DATE(inserted) as moment,
					SUM(case when reason = 'hard-bounce' then 1 else 0 end) as hardbounce,
					SUM(case when reason = 'soft-bounce' then 1 else 0 end) as softbounce,
					SUM(case when reason = 'spam' then 1 else 0 end) as spam,
					SUM(case when reason = 'no-reply' then 1 else 0 end) as noreply,
					SUM(case when reason = 'loop' then 1 else 0 end) as `loop`,
					SUM(case when reason = 'failure' then 1 else 0 end) as failure,
					SUM(case when reason = 'temporal' then 1 else 0 end) as temporal,
					SUM(case when reason = 'unknown' then 1 else 0 end) as unknown,
					SUM(case when reason = 'hardfail' then 1 else 0 end) as hardfail
				FROM delivery_dropped
				WHERE DATE(inserted) = DATE(DATE_SUB(NOW(), INTERVAL $day DAY))
				GROUP BY moment";
			if($day < 7) $sql .= " UNION ";
		}

		// get the delivery status per code
		$connection = new Connection();
		$dropped = $connection->deepQuery($sql);

		// create the array for the view
		$emailsDroppedChart = array();
		foreach($dropped as $d)
		{
			$emailsDroppedChart[] = [
				"date" => date("D j", strtotime($d->moment)),
				"hardbounce" => $d->hardbounce,
				"softbounce" => $d->softbounce,
				"spam" => $d->spam,
				"noreply" => $d->noreply,
				"loop" => $d->loop,
				"failure" => $d->failure,
				"temporal" => $d->temporal,
				"unknown" => $d->unknown,
				"hardfail" => $d->hardfail
			];
		}

		// get last 7 days of dropped emails
		$sql = "SELECT * FROM delivery_dropped WHERE inserted > DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY inserted DESC";
		$dropped = $connection->deepQuery($sql);

		// get last 7 days of emails received
		$connection = new Connection();
		$sql = "SELECT COUNT(id) as total FROM delivery_sent WHERE inserted > DATE_SUB(NOW(), INTERVAL 7 DAY)";
		$sent = $connection->deepQuery($sql)[0]->total;

		$this->view->title = "Dropped emails (Last 7 days)";
		$this->view->emailsDroppedChart = array_reverse($emailsDroppedChart);
		$this->view->droppedEmails = $dropped;
		$this->view->sentEmails = $sent;
		$this->view->failurePercentage = (count($dropped)*100)/$sent;
	}

	/**
	 * Show the error log
	 * */
	public function errorsAction()
	{
		// get the error logs file
		$wwwroot = $this->di->get('path')['root'];
		$logfile = "$wwwroot/logs/error.log";

		// tail the log file
		$numlines = "50";
		$cmd = "tail -$numlines '$logfile'";
		$errors = explode('<br />', nl2br(shell_exec($cmd)));

		// format output to look better
		$output = array();
		foreach ($errors as $err)
		{
			if(strlen($err) < 5) continue;
			$line = htmlentities($err);
			$line = "<b>".substr_replace($line,"]</b>",strpos($line, "]"),1);
			$output[] = $line;
		}

		// reverse to show latest first
		$output = array_reverse($output);

		$this->view->title = "Lastest $numlines errors";
		$this->view->output = $output;
	}

	/**
	 * List of surveys
	 * 
	 * @author kuma
	 */
	public function surveysAction()
	{
		$connection = new Connection();
		$this->view->message = false;
		$this->view->message_type = 'success';
		$option = $this->request->get('option');
		$sql = false;
		
		if($this->request->isPost())
		{
			switch ($option){
				case 'addSurvey':
					$customer = $this->request->getPost("surveyCustomer");
					$title = $this->request->getPost("surveyTitle");
					$deadline = $this->request->getPost("surveyDeadline");
					$sql = "INSERT INTO _survey (customer, title, deadline) VALUES ('$customer', '$title', '$deadline'); ";
					$this->view->message = 'The survey was inserted successfull';
					 
					break;
				case 'setSurvey':
					$customer = $this->request->getPost("surveyCustomer");
					$title = $this->request->getPost("surveyTitle");
					$deadline = $this->request->getPost("surveyDeadline");
					$id = $this->request->get('id');
					$sql = "UPDATE _survey SET customer = '$customer', title = '$title', deadline = '$deadline' WHERE id = '$id'; ";
					$this->view->message = 'The survey was updated successfull';
					break;
			}
		}
		 
		switch ($option){
			case "delSurvey":
				$id = $this->request->get('id');
				$sql = "START TRANSACTION;
						DELETE FROM _survey_answer WHERE question = (SELECT id FROM _survey_question WHERE _survey_question.survey = '$id');
						DELETE FROM _survey_question WHERE survey = '$id';
						DELETE FROM _survey WHERE id = '$id';
						COMMIT;";
				$this->view->message = 'The survey #'.$delete.' was deleted successfull';
				break;
			
			case "disable":
				$id = $this->request->get('id');
				$sql = "UPDATE _survey SET active = 0 WHERE id ='$id';";
				break;
			case "enable":
				$id = $this->request->get('id');
				$sql = "UPDATE _survey SET active = 1 WHERE id ='$id';";
				break;
		}
		
		if ($sql!==false) $connection->deepQuery($sql);
		
		$querySurveys = "SELECT * FROM _survey ORDER BY ID";
		 
		$surveys = $connection->deepQuery($querySurveys);
	
		$this->view->title = "List of surveys (".count($surveys).")";
		$this->view->surveys = $surveys;
	}

	/**
	 * Manage survey's questions and answers
	 * 
	 * @author kuma
	 */
	public function surveyQuestionsAction()
	{
		$connection = new Connection();
		$this->view->message = false;
		$this->view->message_type = 'success';
		
		$option = $this->request->get('option');
		$sql = false;
		if ($this->request->isPost()){
			
			switch($option){
				case "addQuestion":
					$survey = $this->request->getPost('survey');
					$title = $this->request->getPost('surveyQuestionTitle');
					$sql ="INSERT INTO _survey_question (survey, title) VALUES ('$survey','$title');";
					$this->view->message = "Question <b>$title</b> was inserted successfull";
				break;
				case "setQuestion":
					$question_id = $this->request->get('id');
					$title = $this->request->getPost('surveyQuestionTitle');
					$sql ="UPDATE _survey_question SET title = '$title' WHERE id = '$question_id';";
					$this->view->message = "Question <b>$title</b> was updated successfull";
					break;
				case "addAnswer":
					$question_id = $this->request->get('question');
					$title = $this->request->getPost('surveyAnswerTitle');
					$sql ="INSERT INTO _survey_answer (question, title) VALUES ('$question_id','$title');";
					$this->view->message = "Answer <b>$title</b> was inserted successfull";
				break;
				case "setAnswer":
					$answer_id = $this->request->get('id');
					$title = $this->request->getPost('surveyAnswerTitle');
					$sql = "UPDATE _survey_answer SET title = '$title' WHERE id = '$answer_id';";
					$this->view->message = "The answer was updated successfull";
				break;
			}
		}
		
		switch($option)
		{
			case "delAnswer":
				$answer_id = $this->request->get('id');
				$sql = "DELETE FROM _survey_answer WHERE id ='{$answer_id}'";
				$this->view->message = "The answer was deleted successfull";
			break;
			
			case "delQuestion":
				$question_id = $this->request->get('id');
				$sql = "START TRANSACTION; 
						DELETE FROM _survey_question WHERE id = '{$question_id}';
						DELETE FROM _survey_answer WHERE question ='{$question_id}';
						COMMIT;";
				$this->view->message = "The question was deleted successfull";
			break;
		}
		
		if ($sql!=false) $connection->deepQuery($sql);
	  
		$survey = $this->request->get('survey');
					
		$r = $connection->deepQuery("SELECT * FROM _survey WHERE id = '{$survey};'");
		if ($r !== false) {
			$sql = "SELECT * FROM _survey_question WHERE survey = '$survey' order by id;";
			$survey = $r[0];
			$questions = $connection->deepQuery($sql);
			if ($questions !== false) {
				
				foreach ($questions as $k=>$q){
					$answers = $connection->deepQuery("SELECT * FROM _survey_answer WHERE question = '{$q->id}';");
					if ($answers==false) $answers = array();
					$questions[$k]->answers=$answers;
				}
				
				$this->view->title = "Survey's questions";
				$this->view->survey = $survey;
				$this->view->questions = $questions;
			}
		}
	}

	/**
	 * Remarket
	 * 
	 * @author salvipascual
	 * */
	public function remarketingAction()
	{
		// create the sql for the graph
		$sqlSent = $sqlOpened = "";
		foreach (range(0,7) as $day)
		{
			$sqlSent .= "
				SELECT 
					DATE(sent) as moment,
					SUM(case when type = 'REMINDER1' then 1 else 0 end) as reminder1,
					SUM(case when type = 'REMINDER2' then 1 else 0 end) as reminder2,
					SUM(case when type = 'EXCLUDED' then 1 else 0 end) as excluded,
					SUM(case when type = 'INVITE' then 1 else 0 end) as invite,
					SUM(case when type = 'AUTOINVITE' then 1 else 0 end) as autoinvite,
					SUM(case when type = 'ERROR' then 1 else 0 end) as error
				FROM remarketing
				WHERE DATE(sent) = DATE(DATE_SUB(NOW(), INTERVAL $day DAY))
				GROUP BY moment";
			$sqlOpened .= "
				SELECT
					DATE(opened) as moment,
					SUM(case when type = 'REMINDER1' then 1 else 0 end) as reminder1,
					SUM(case when type = 'REMINDER2' then 1 else 0 end) as reminder2,
					SUM(case when type = 'EXCLUDED' then 1 else 0 end) as excluded,
					SUM(case when type = 'INVITE' then 1 else 0 end) as invite,
					SUM(case when type = 'AUTOINVITE' then 1 else 0 end) as autoinvite,
					SUM(case when type = 'ERROR' then 1 else 0 end) as error
				FROM remarketing
				WHERE DATE(opened) = DATE(DATE_SUB(NOW(), INTERVAL $day DAY))
				GROUP BY moment";
			if($day < 7) { $sqlSent .= " UNION "; $sqlOpened .= " UNION "; }
		}

		// get the delivery status per code
		$connection = new Connection();
		$sent = $connection->deepQuery($sqlSent);
		$opened = $connection->deepQuery($sqlOpened);

		// pass info to the view
		$this->view->title = "Remarketing";
		$this->view->sent = array_reverse($sent);
		$this->view->opened = array_reverse($opened);
	}

	/**
	 * add credits
	 * 
	 * @author kuma
	 * */
	public function addcreditAction()
	{
		$this->view->person = false;
		$this->view->title = "Add credit";
		$this->view->message = false;
		$this->view->message_type = 'success';
	
		if ($this->request->isPost())
		{
			$email = $this->request->getPost('email');
			$credit = $this->request->getPost('credit');
			
			if (is_null($credit) || $credit == 0)
			{
				$this->view->message = "Please, type the credit";
				$this->view->message_type = 'danger';
			}
			elseif ( ! is_null($email))
			{
				$utils = new Utils();
				$person = $utils->getPerson($email);
				
				if ($person !== false)
				{
					$confirm = $this->request->getPost('confirm');
					if (is_null($confirm))
					{
						if ($person->credit + $credit < 0)
						{
							$this->view->person = false;
							$this->view->message = "It is not possible to decrease <b>".number_format($credit, 2)."</b> from user's credit";
							$this->view->message_type = 'danger';
						}
						else
						{
							$this->view->person = $person;
							$this->view->credit = $credit;
							$this->view->newcredit = $credit + $person->credit;
						}
					}
					else
					{
						$db = new Connection();
						$sql = "UPDATE person SET credit = credit + $credit WHERE email = '$email';";
						$db->deepQuery($sql);
						$this->view->message = "User's credit updated successfull";
					}
				}
				else
				{
					$this->view->message = "User <b>$email</b> not found";
					$this->view->message_type = 'danger';
				}
			}
		}
	}

	/**
	 * Reports for the ads
	 *
	 * @author kuma
	 * */
	public function adReportAction()
	{
		// getting ad's id
		$url = $_GET['_url']; 
		$id =  explode("/",$url);
		$id = intval($id[count($id)-1]);

		$db = new Connection();

		$ad = $db->deepQuery("SELECT * FROM ads WHERE id = $id;");
		$this->view->ad = false;
		
		if ($ad !== false)
		{
			$week = array(); 

			// @TODO fix field name in database: ad_bottom to ad_bottom
			$sql = "SELECT WEEKDAY(request_time) as w,
					count(usage_id) as total
					FROM utilization 
					WHERE (ad_top = $id OR ad_botton = $id)
					and service <> 'publicidad'
					and YEAR(request_time) = YEAR(CURRENT_DATE)
					GROUP BY w
			        ORDER BY w";

			$r = $db->deepQuery($sql);

			if (is_array($r))
			{
				foreach($r as $i)
				{
					if ( ! isset($week[$i->w])) $week[$i->w] = array('impressions'=>0,'clicks'=>0);
					$week[$i->w]['impressions'] = $i->total;
				}
			}

			$sql = "
				SELECT
				WEEKDAY(request_time) as w,
				count(usage_id) as total
				FROM utilization
				WHERE service = 'publicidad'
				and subservice is null
				and query * 1 = $id
				and YEAR(request_time) = YEAR(CURRENT_DATE)
				GROUP BY w";
			
			$r = $db->deepQuery($sql);
			if (is_array($r))
			{
				foreach($r as $i)
				{
					if ( ! isset($week[$i->w])) $week[$i->w] = array('impressions'=>0,'clicks'=>0);
					$week[$i->w]['clicks'] = $i->total;
				}
			}

			$this->view->weekly = $week;

			$month = array();

			$sql = "
				SELECT 
				MONTH(request_time) as m, count(usage_id) as total
				FROM utilization WHERE (ad_top = $id OR ad_botton = $id) 
    			and service <> 'publicidad'
    			and YEAR(request_time) = YEAR(CURRENT_DATE)
    			GROUP BY m";

			$r = $db->deepQuery($sql);

			if (is_array($r))
			{
				foreach($r as $i)
				{
					if ( ! isset($month[$i->m]))
						$month[$i->m] = array('impressions'=>0,'clicks'=>0);
					$month[$i->m]['impressions'] = $i->total;
				}
			}
			
			$sql = "
				SELECT 
				MONTH(request_time) as m,
				count(usage_id) as total
				FROM utilization
				WHERE service = 'publicidad'
				and subservice is null
				and query * 1 = $id
				and YEAR(request_time) = YEAR(CURRENT_DATE)
				GROUP BY m";
			
			$r = $db->deepQuery($sql);
			if (is_array($r))
			{
				foreach($r as $i)
				{
					if ( ! isset($month[$i->m]))
						$month[$i->m] = array('impressions'=>0,'clicks'=>0);
						$month[$i->m]['clicks'] = $i->total;

				}
			}

			// join sql
			$jsql = "SELECT * FROM utilization INNER JOIN person ON utilization.requestor = person.email 
			WHERE service = 'publicidad'
				and subservice is null
				and query * 1 = $id
				and YEAR(request_time) = YEAR(CURRENT_DATE)";

			// usage by age
			$sql = "SELECT IFNULL(YEAR(CURDATE()) - YEAR(subq.date_of_birth), 0) as a, COUNT(*) as t FROM ($jsql) AS subq GROUP BY a;";
			$r = $db->deepQuery($sql);
			
			$usage_by_age = array(
				'0-16' => 0,
				'17-21' => 0,
				'22-35' => 0,
				'36-55' => 0,
				'56-130' => 0
			);

			if ($r != false)
			{
				foreach($r as $item)
				{
					$a = $item->a;
					$t = $item->t;
					if ($a < 17) $usage_by_age['0-16'] += $t;
					if ($a > 16 && $a < 22) $usage_by_age['17-21'] += $t;
					if ($a > 21 && $a < 36) $usage_by_age['22-35'] += $t;
					if ($a > 35 && $a < 56) $usage_by_age['36-55'] += $t;
					if ($a > 55) $usage_by_age['56-130'] += $t;
				}
			}
			
			$this->view->usage_by_age = $usage_by_age;

			// usage by X (enums)
			$X = array('gender','skin','province','highest_school_level','marital_status','sexual_orientation','religion');

			foreach($X as $xx)
			{
				$usage = array();
				$r = $db->deepQuery("SELECT subq.$xx as a, COUNT(*) as t FROM ($jsql) AS subq WHERE subq.$xx IS NOT NULL GROUP BY subq.$xx;");

				if ($r != false)
				{
					foreach($r as $item) $usage[$item->a] = $item->t;
				}

				$p = "usage_by_$xx";
				$this->view->$p = $usage;
			}

			$this->view->weekly = $week;
			$this->view->monthly = $month;
			$this->view->title = "Ad report";
			$this->view->ad = $ad[0];
		}
	}

	/**
	 * Show the ads target
	 *
	 * @author kuma
	 * */
	public function adTageringAction()
	{
		// getting ad's id
		$url = $_GET['_url'];
		$id =  explode("/",$url);
		$id = intval($id[count($id)-1]);
		$db = new Connection();
		$ad = $db->deepQuery("SELECT * FROM ads WHERE id = $id;");
		$this->view->ad = false;

		if ($ad !== false)
		{
			if ($this->request->isPost())
			{
				$sql = "UPDATE ads SET ";
				$go = false;
				foreach($_POST as $key => $value)
				{
					if (isset($ad[0]->$key))
					{
						$go  = true;
						$sql .= " $key = '{$value}', ";
					}
				}
	
				if ($go)
				{
					$sql = substr($sql,0,strlen($sql)-2);
					$sql .= "WHERE id = $id;";
					$db->deepQuery($sql);
				}
				
				$ad = $db->deepQuery("SELECT * FROM ads WHERE id = $id;");
			}

			$this->view->title ="Ad targeting";
			$this->view->ad = $ad[0];
		}
	}

	
	/**
	 * Survey reports
	 */
	public function surveyReportAction(){
	    // getting ad's id
	    // @TODO: improve this!
	    $url = $_GET['_url'];
	    $id =  explode("/",$url);
	    $id = intval($id[count($id)-1]);
	    
	    $report = $this->getSurveyResults($id);

	    if ($report !== false){
	        $db = new Connection();
	        $survey = $db->deepQuery("SELECT * FROM _survey WHERE id = $id;");
	        $this->view->results = $report;
    	    $this->view->survey = $survey[0];
    	    $this->view->title = 'Survey report';
	    } else {
	        $this->survey = false;
	    }
	}
	
	/**
	 * Calculate and return survey's results
	 * 
	 * @author kuma
	 * @param integer $id
	 */
	private function getSurveyResults($id){
	    $db = new Connection();
	    $survey = $db->deepQuery("SELECT * FROM _survey WHERE id = $id;");
	     
	    $by_age = array(
	            '0-16' => 0,
	            '17-21' => 0,
	            '22-35' => 0,
	            '36-55' => 0,
	            '56-130' => 0
	    );
	     
	    if ($survey !== false){
	         
	        $enums = array(
	                'person.age' => 'By age',
	                'person.province' => "By location",
	                'person.gender' => 'By gender',
	                'person.highest_school_level' => 'By level of education'
	        );
	         
	        $report = array();
	         
	        foreach ($enums as $field => $enum_label){
	            $sql = "
	            SELECT
	            _survey.id AS survey_id,
	            _survey.title AS survey_title,
	            _survey_question.id AS question_id,
	            _survey_question.title AS question_title,
	            _survey_answer.id AS answer_id,
	            _survey_answer.title AS answer_title,
	            IFNULL($field,'_UNKNOW') AS pivote,
	            Count(_survey_answer_choosen.email) AS total
	            FROM
	            _survey Inner Join (_survey_question inner join ( _survey_answer inner join (_survey_answer_choosen inner join (select *, YEAR(CURDATE()) - YEAR(person.date_of_birth) as age from person) as person ON _survey_answer_choosen.email = person.email) on _survey_answer_choosen.answer = _survey_answer.id) ON _survey_question.id = _survey_answer.question)
	            ON _survey_question.survey = _survey.id
	            WHERE _survey.id = $id
	            GROUP BY
	            _survey.id,
	            _survey.title,
	            _survey_question.id,
	            _survey_question.title,
	            _survey_answer.id,
	            _survey_answer.title,
	            $field
	            ORDER BY _survey.id, _survey_question.id, _survey_answer.id, pivote";
	    	    
	            $r = $db->deepQuery($sql);
	             
	            $pivots = array();
	            $totals = array();
	            $results = array();
	            if ($r!==false){
	                foreach($r as $item){
	                    $item->total = intval($item->total);
	                    $q = intval($item->question_id);
	                    $a = intval($item->answer_id);
	                    if (!isset($results[$q]))
	                        $results[$q] = array(
	                                "i" => $q,
	                                "t" => $item->question_title,
	                                "a" => array(),
	                                "total" => 0
	                        );
	                         
	                        if (!isset($results[$q]['a'][$a]))
	                            $results[$q]['a'][$a] = array(
	                                    "i" => $a,
	                                    "t" => $item->answer_title,
	                                    "p" => array(),
	                                    "total" => 0
	                            );
	                             
	                            $pivot = $item->pivote;
	    
	                            if ($field == 'person.age'){
	                                if (trim($pivot)=='' || $pivot=='0' || $pivot =='NULL' || $pivot=='_UNKNOW') $pivot='UNKNOW';
	                                elseif ($pivot*1 < 17) $pivot = '0-16';
	                                elseif ($pivot*1 > 16 && $pivot*1 < 22) $pivot = '17-21';
	                                elseif ($pivot*1 > 21 && $pivot*1 < 36) $pivot = '22-35';
	                                elseif ($pivot*1 > 35 && $pivot*1 < 56) $pivot = '36-55';
	                                elseif ($pivot*1 > 55) $pivot = '56-130';
	                            }
	    
	                            $results[$q]['a'][$a]['p'][$pivot] = $item->total;
	                             
	                            if (!isset($totals[$a]))
	                                $totals[$a] = 0;
	                                 
	                                $totals[$a] += $item->total;
	                                $results[$q]['a'][$a]['total'] += $item->total;
	                                $results[$q]['total'] += $item->total;
	                                $pivots[$pivot] = str_replace("_"," ", $pivot);
	                }
	            }
	    
	            // fill details...
	            $sql = "
	            SELECT
	            _survey.id AS survey_id,
	            _survey.title AS survey_title,
	            _survey_question.id AS question_id,
	            _survey_question.title AS question_title,
	            _survey_answer.id AS answer_id,
	            _survey_answer.title AS answer_title
	            FROM
	            _survey Inner Join (_survey_question inner join
	            _survey_answer ON _survey_question.id = _survey_answer.question)
	            ON _survey_question.survey = _survey.id
	            WHERE _survey.id = $id
	            ORDER BY _survey.id, _survey_question.id, _survey_answer.id";
	    
	            $survey_details = $db->deepQuery($sql);
	    
	            foreach($survey_details as $item){
	                $q = intval($item->question_id);
	                $a = intval($item->answer_id);
	                if (!isset($results[$q]))
	                    $results[$q] = array(
	                            "i" => $q,
	                            "t" => $item->question_title,
	                            "a" => array()
	                    );
	                     
	                    if (!isset($results[$q]['a'][$a]))
	                        $results[$q]['a'][$a] = array(
	                                "i" => $a,
	                                "t" => $item->answer_title,
	                                "p" => array(),
	                                "total" => 0
	                        );
	                        if (!isset($totals[$a]))
	                            $totals[$a] = 0;
	            }
	            
	            $pivots['_UNKNOW'] = 'UNKNOW';	            
	            
	            asort($pivots);
	    
	            $report[$field] = array(
	                    'label' => $enum_label,
	                    'results' => $results,
	                    'pivots' => $pivots,
	                    'totals' => $totals
	            );
	            
	            // adding unknow labels
	             
	            foreach ($report[$field]['results'] as $k => $question){
	                foreach($question['a'] as $kk => $ans){
	                    $report[$field]['results'][$k]['a'][$kk]['p']['_UNKNOW'] = $totals[$ans['i']*1];
	                    foreach($ans['p'] as $kkk => $pivot){
	                        $report[$field]['results'][$k]['a'][$kk]['p']['_UNKNOW'] -= $pivot;
	                    }
	                }
	            }
	        }
	         
	        return $report;
	    } 
	    
	    return false;
	}
	
	/**
	 * Download survey's results as CSV
	 * 
	 * @author kuma
	 */
	public function surveyResultsCSVAction(){
	    // getting ad's id
	    // @TODO: improve this!
	    $url = $_GET['_url'];
	    $id =  explode("/",$url);
	    $id = intval($id[count($id)-1]);
	    $db = new Connection();
	    $survey = $db->deepQuery("SELECT * FROM _survey WHERE id = $id;");
	    $survey = $survey[0];
	    $results = $this->getSurveyResults($id);
	    $csv = array();
	    
	    $csv[0][0] = "Survey Results";
	    $csv[1][0] = $survey->title;
	    $csv[2][0] = "";

	     foreach ($results as $field => $result){
	        		
    		$csv[][0] = $result['label'];
            $row = array('','Total','Percentage');
          
            foreach ($result['pivots'] as $pivot => $label)
        		$row[] = $label; 
            
        	$csv[] = $row;
        	
        	foreach($result['results'] as $question){
            	$csv[][0] = $question['t'];
            	foreach($question['a'] as $ans) {
            		$row = array($ans['t'], $ans['total'], ($question['total'] ===0?0:number_format($ans['total'] / $question['total'] * 100, 1)));         
            	    foreach ($result['pivots'] as $pivot => $label) {
                		if (!isset($ans['p'][$pivot])) {
                			$row[] = "--";
                		} else { 
                		    $part = intval($ans['p'][$pivot]);
                		    $total = intval($ans['total']);
                		    $percent = $total === 0?0:$part/$total*100;
                		    $row[] = number_format($percent,1);
                		}
            	    }
            	    $csv[] = $row;
            	}
        	}
	     }
	    
	     
	    $csvtext = '';
	    foreach($csv as $i => $row){
	        foreach ($row as $j => $cell){
	            $csvtext .= '"'.$cell.'";';
	        }
	        $csvtext .="\n";
	    }
	    
	    header("Content-type: text/csv");
	    header("Content-Type: application/force-download");
	    header("Content-Type: application/octet-stream");
	    header("Content-Type: application/download");
	    header("Content-Disposition: attachment; filename=\"ap-survey-$id-results-".date("Y-m-d-h-i-s").".csv\"");
	    header("Content-Length: ".strlen($csvtext));
	    header("Pragma: public");
	    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	    header("Accept-Ranges: bytes");
	    
	    echo $csvtext;
	    
	    $this->view->disable();
	}	    

	public function surveyWhoUnfinishedAction()
	{
		// getting ad's id
		$url = $_GET['_url'];
		$id =  explode("/",$url);
		$id = intval($id[count($id)-1]);
		$db = new Connection();

	}
}
