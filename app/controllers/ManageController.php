<?php

use Phalcon\Mvc\Controller;

class ManageController extends Controller
{
	/**
	 * Index for the manage system
	 * */
	public function indexAction()
	{
		$wwwroot = $this->di->get('path')['root'];

		// get the last time the crawlers ran
		$revolicoCrawlerFile = "$wwwroot/temp/crawler.revolico.last.run";
		$revolicoCrawler = array();
		if(file_exists($revolicoCrawlerFile))
		{
			$details = file_get_contents($revolicoCrawlerFile);
			$details = explode("|", $details);

			$revolicoCrawler["LastRun"] = date("D F j, h:i A", strtotime($details[0])); 
			$revolicoCrawler["TimeBehind"] = time() - strtotime($details[0]) / 60 / 60; 
			$revolicoCrawler["RuningTime"] = number_format($details[1], 2);
			$revolicoCrawler["PostsDownloaded"] = $details[2];
			$revolicoCrawler["RuningMemory"] = $details[3];
		}

		$this->view->title = "Home";
		$this->view->revolicoCrawler = $revolicoCrawler;
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
			"SELECT A.all_visitors, B.unique_visitors, C.new_visitors, A.inserted
			FROM (SELECT COUNT(*) as all_visitors, DATE_FORMAT(request_time,'%Y-%m') as inserted FROM utilization GROUP BY DATE_FORMAT(request_time,'%Y-%m') ORDER BY inserted DESC LIMIT 30) A
			JOIN (SELECT COUNT(DISTINCT requestor) as unique_visitors, DATE_FORMAT(request_time,'%Y-%m') as inserted FROM utilization GROUP BY DATE_FORMAT(request_time,'%Y-%m') ORDER BY inserted DESC LIMIT 30) B
			JOIN (SELECT COUNT(DISTINCT email) as new_visitors, DATE_FORMAT(insertion_date,'%Y-%m') as inserted FROM person GROUP BY DATE_FORMAT(insertion_date,'%Y-%m') ORDER BY inserted DESC LIMIT 30) C
			ON A.inserted = B.inserted AND A.inserted = C.inserted";
		$visits = $connection->deepQuery($query);
		$newUsers = array();
		foreach($visits as $visit)
		{
			$newUsers[] = ["date"=>date("M Y", strtotime($visit->inserted)), "all_visitors"=>$visit->all_visitors, "unique_visitors"=>$visit->unique_visitors, "new_visitors"=>$visit->new_visitors];
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
		$queryBounceRate = "
		SELECT *
		FROM 
			(SELECT DATE_FORMAT(month.start - INTERVAL seq.seq MONTH,'%b-%Y') AS Month,
			DATE_FORMAT(month.start - INTERVAL seq.seq MONTH,'%Y-%m') AS toOrder
			FROM (
			   SELECT 11 AS seq UNION ALL
			   SELECT 10 UNION ALL
			   SELECT  9 UNION ALL
			   SELECT  8 UNION ALL
			   SELECT  7 UNION ALL
			   SELECT  6 UNION ALL
			   SELECT  5 UNION ALL
			   SELECT  4 UNION ALL
			   SELECT  3 UNION ALL
			   SELECT  2 UNION ALL
			   SELECT  1 UNION ALL
			   SELECT  0
			) seq
			JOIN (
			   SELECT CURRENT_DATE() - INTERVAL DAYOFMONTH(CURRENT_DATE()) - 1 DAY AS start
			) month) AS MonthData
		LEFT JOIN
			(SELECT CONCAT(SUBSTRING(DATE_FORMAT(request_time, '%b'),1,3),DATE_FORMAT(request_time,'-%Y')) as dateName,
			Count(Distinct requestor) AS RequestCount
			FROM utilization
			WHERE PERIOD_DIFF(DATE_FORMAT(NOW(), '%Y%m'), DATE_FORMAT(request_time, '%Y%m'))<12
			GROUP BY YEAR(request_time), MONTH(request_time)) AS dataMonth
			ON dataMonth.dateName = MonthData.Month
		WHERE TRUE = TRUE
		ORDER BY MonthData.toOrder";
		$dataBounceRate = $connection->deepQuery($queryBounceRate);
		foreach($dataBounceRate as $monthBounceList)
		{
			if($monthBounceList->RequestCount != NULL)
			$bounceRateMonthly[] = ["month"=>$monthBounceList->Month, "emails"=>$monthBounceList->RequestCount];
			else
			$bounceRateMonthly[] = ["month"=>$monthBounceList->Month, "emails"=> 0];
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
		$queryProfileData = "SELECT 'Name' AS Caption, COUNT(first_name) AS Number
		FROM person
		WHERE updated_by_user IS NOT NULL AND (first_name IS NOT NULL OR last_name IS NOT NULL OR middle_name IS NOT NULL OR mother_name IS NOT NULL)
		UNION
		SELECT 'DOB' AS Caption, COUNT(date_of_birth) AS Number
		FROM person
		WHERE updated_by_user IS NOT NULL AND date_of_birth IS NOT NULL
		UNION
		SELECT 'Gender' AS Caption, COUNT(gender) AS Number
		FROM person
		WHERE updated_by_user IS NOT NULL AND gender IS NOT NULL
		UNION
		SELECT 'Phone' AS Caption, COUNT(phone) AS Number
		FROM person
		WHERE updated_by_user IS NOT NULL AND phone IS NOT NULL
		UNION
		SELECT 'Eyes' AS Caption, COUNT(eyes) AS Number
		FROM person
		WHERE updated_by_user IS NOT NULL AND eyes IS NOT NULL
		UNION
		SELECT 'Skin' AS Caption, COUNT(skin) AS Number
		FROM person
		WHERE updated_by_user IS NOT NULL AND skin IS NOT NULL
		UNION
		SELECT 'Body' AS Caption, COUNT(body_type) AS Number
		FROM person";
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
				WHEN 'PINAR_DEL_RIO' THEN 'Pinar del Río'
				WHEN 'LA_HABANA' THEN 'Ciudad de La Habana'
				WHEN 'ARTEMISA' THEN 'CU-X01'
				WHEN 'MAYABEQUE' THEN 'CU-X02'
				WHEN 'MATANZAS' THEN 'Matanzas'
				WHEN 'VILLA_CLARA' THEN 'Villa Clara'
				WHEN 'CIENFUEGOS' THEN 'Cienfuegos'
				WHEN 'SANTI_SPIRITUS' THEN 'Sancti Spíritus'
				WHEN 'CIEGO_DE_AVILA' THEN 'Ciego de Ávila'
				WHEN 'CAMAGUEY' THEN 'Camagüey'
				WHEN 'LAS_TUNAS' THEN 'Las Tunas'
				WHEN 'HOLGUIN' THEN 'Holguín'
				WHEN 'GRANMA' THEN 'Granma'
				WHEN 'SANTIAGO_DE_CUBA' THEN 'Santiago de Cuba'
				WHEN 'GUANTANAMO' THEN 'Guantánamo'
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
					SELECT 'SANTI_SPIRITUS' mnth
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
					   b.province IN ('PINAR_DEL_RIO', 'LA_HABANA', 'ARTEMISA', 'MAYABEQUE', 'MATANZAS', 'VILLA_CLARA', 'CIENFUEGOS', 'SANTI_SPIRITUS', 'CIEGO_DE_AVILA', 'CAMAGUEY', 'LAS_TUNAS', 'HOLGUIN', 'GRANMA', 'SANTIAGO_DE_CUBA', 'GUANTANAMO', 'ISLA_DE_LA_JUVENTUD') 
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

		$queryraffleList = "SELECT item_desc, start_date, end_date, winner_1, winner_2, winner_3 FROM raffle ORDER BY end_date DESC";
		$raffleListData = $connection->deepQuery($queryraffleList);

		$raffleListCollection = array();
		foreach($raffleListData as $raffleListItem)
			$raffleListCollection[] = ["itemDesc"=>$raffleListItem->item_desc, "startDay"=>$raffleListItem->start_date, "finishDay"=>$raffleListItem->end_date, "winner1"=>$raffleListItem->winner_1, "winner2"=>$raffleListItem->winner_2, "winner3"=>$raffleListItem->winner_3];

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
				$picPath = "$wwwroot/public/raffle/$fileName.png";
				move_uploaded_file($_FILES["picture"]["tmp_name"], $picPath);

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
			LEFT JOIN (SELECT service, COUNT(service) as times_used, AVG(response_time) as avg_latency FROM utilization WHERE request_time > DATE_SUB(NOW(), INTERVAL 4 MONTH) GROUP BY service) B
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

		$queryAdsActive = "SELECT owner, time_inserted, title, impresions, paid_date, expiration_date FROM ads";
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
				$queryGetAdsID = "SELECT ads_id FROM ads WHERE owner = '$adsOwner' ORDER BY ads_id DESC LIMIT 1";
				$getAdID = $connection->deepQuery($queryGetAdsID);

				// save the image
				$fileName = md5($getAdID[0]->ads_id); //Generate the picture name
				$wwwroot = $this->di->get('path')['root'];
				$picPath = "$wwwroot/public/ads/$fileName.png";
				move_uploaded_file($_FILES["picture"]["tmp_name"], $picPath);

				// confirm by email that the ad was inserted
				$email = new Email();
				$email->sendEmail($adsOwner, "Your ad $adsTittle is now running", "<h1>Your ad is running!</h1><p>Your Ads $adsTittle was set to run $today.</p><p>Thanks for advertising using Apretaste.</p>");

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

		$queryJumper = "SELECT email, last_usage, sent_count, 'Errors' AS ErrorCount, blocked_domains, active FROM jumper";
		$jumperData = $connection->deepQuery($queryJumper);

		$this->view->title = "Jumper";
		$this->view->jumperData = $jumperData;
	}


	/**
	 * Toggle the status of the jumper
	 * */
	public function jumperToggleActiveStatusAction()
	{
		$email = $this->request->get("email");
		if($email)
		{
			$connection = new Connection();
			$query = "UPDATE jumper SET active = !active WHERE email = '$email'";
			$connection->deepQuery($query);
		}
		return $this->response->redirect('manage/jumper');
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
		// get last 7 days of dropped emails
		$connection = new Connection();
		$sql = "SELECT * FROM delivery_dropped WHERE inserted > DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY inserted DESC";
		$dropped = $connection->deepQuery($sql);

		// get last 7 days of emails sent
		$connection = new Connection();
		$sql = "SELECT count(usage_id) AS total FROM utilization WHERE request_time > DATE_SUB(NOW(), INTERVAL 7 DAY)";
		$sent = $connection->deepQuery($sql)[0]->total;

		$this->view->title = "Dropped emails (Last 7 days)";
		$this->view->droppedEmails = $dropped;
		$this->view->sentEmails = $sent;
		$this->view->failurePercentage = (count($dropped)*100)/$sent;
	}
}
