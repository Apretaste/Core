<?php

use Phalcon\Mvc\Controller;
  
class ManageController extends Controller
{
	/**
	 * Index for the manage system
	 * */
	public function indexAction()
	{
		$this->view->title = "Home";
	}


	/**
	 * Audience
	 * */
	public function audienceAction()
	{
		$connection = new Connection();
	
		// Weekly visitors
		$queryWeecly = "SELECT *
		FROM (SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 6 DAY), '%a') AS Weekday UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 5 DAY), '%a') AS Weekday UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 4 DAY), '%a') AS Weekday UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 3 DAY), '%a') AS Weekday UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 2 DAY), '%a') AS Weekday UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 1 DAY), '%a') AS Weekday UNION
		SELECT DATE_FORMAT(now(), '%a') as Weekday) AS Weekdays LEFT JOIN
			(SELECT DATE_FORMAT(request_time, '%a') as DataWeekday, count(request_time) as TimesRequested
			FROM  utilization
			WHERE (request_time >= DATE_SUB(now( ) , INTERVAL 7 DAY))
		group by date(request_time)) AS DataWeek
		ON DataWeek.DataWeekday = Weekdays.Weekday";
	
		$visitorsWeeclyObj = $connection->deepQuery($queryWeecly);
		foreach($visitorsWeeclyObj as $weeklyvisits)
		{
			if($weeklyvisits->TimesRequested != NULL)
				$visitorsWeecly[] = ["day"=>$weeklyvisits->Weekday, "emails"=>$weeklyvisits->TimesRequested];
			else
				$visitorsWeecly[] = ["day"=>$weeklyvisits->Weekday, "emails"=> 0];
		}
		//End weekly visitors
	
		// Montly visitors
		$queryMonthly = "SELECT *
		FROM (SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 11 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 10 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 9 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 8 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 7 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 6 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 5 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 4 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 3 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 2 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 1 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(now(), '%b-%Y') as Month) AS Months LEFT JOIN
			(SELECT DATE_FORMAT(request_time,'%b-%Y') as dataName, count(request_time) as TimesRequested
			FROM utilization
			WHERE PERIOD_DIFF(DATE_FORMAT(NOW(), '%Y%m'), DATE_FORMAT(request_time, '%Y%m'))<12
			group by EXTRACT(MONTH FROM date(request_time))
		ORDER BY request_time) AS dataMonth
		ON dataMonth.dataName = Months.Month";
		$visitorsMonthlyObj = $connection->deepQuery($queryMonthly);

		foreach($visitorsMonthlyObj as $visits)
		{
			if($visits->TimesRequested != NULL)
				$visitorsMonthly[] = ["month"=>$visits->Month, "emails"=>$visits->TimesRequested];
			else
				$visitorsMonthly[] = ["month"=>$visits->Month, "emails"=> 0];
		}
		//End Monthly Visitors
	
		// New users per month
		$queryNewUsers = "SELECT *
		FROM (SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 11 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 10 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 9 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 8 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 7 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 6 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 5 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 4 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 3 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 2 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 1 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(now(), '%b-%Y') as Month) AS Months LEFT JOIN
			(SELECT DATE_FORMAT(insertion_date,'%b-%Y') as dataName, count(insertion_date) as TimeInserted
			FROM person
			WHERE PERIOD_DIFF(DATE_FORMAT(NOW(), '%Y%m'), DATE_FORMAT(insertion_date, '%Y%m'))<12
			group by EXTRACT(MONTH FROM date(insertion_date))
		ORDER BY insertion_date) AS dataMonth
		ON dataMonth.dataName = Months.Month";
		$newUsersMonthly = $connection->deepQuery($queryNewUsers);
	
		foreach($newUsersMonthly as $newUsersList)
		{
			if($newUsersList->TimeInserted != NULL)
				$newUsers[] = ["month"=>$newUsersList->Month, "emails"=>$newUsersList->TimeInserted];
			else
				$newUsers[] = ["month"=>$newUsersList->Month, "emails"=> 0];
		}
		//End new users per month
	
		//Current number of Users
		$queryCurrentNoUsers = "SELECT COUNT(email) as CountUsers FROM person";
		$currentNoUsers = $connection->deepQuery($queryCurrentNoUsers);
		//End Current number of Users

		// Get services usage monthly
		$queryMonthlyServiceUsage = "SELECT *
		FROM (SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 11 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 10 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 9 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 8 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 7 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 6 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 5 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 4 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 3 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 2 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 1 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(now(), '%b-%Y') as Month) AS Months LEFT JOIN
			(SELECT DATE_FORMAT(request_time,'%b-%Y') as DataName, COUNT(service) as ServicesCount
			FROM utilization
			WHERE PERIOD_DIFF(DATE_FORMAT(NOW(), '%Y%m'), DATE_FORMAT(request_time, '%Y%m'))<12
			group by EXTRACT(MONTH FROM date(request_time))
		ORDER BY request_time) AS dataMonth
		ON dataMonth.dataName = Months.Month";
		$MonthlyServiceUseage = $connection->deepQuery($queryMonthlyServiceUsage);
	
		foreach($MonthlyServiceUseage as $serviceList)
		{
			if($serviceList->ServicesCount != NULL)
				$servicesUsageMonthly[] = ["service"=>$serviceList->Month, "usage"=>$serviceList->ServicesCount];
			else
				$servicesUsageMonthly[] = ["service"=>$serviceList->Month, "usage"=> 0];
		}

		// Active domains last 4 Month
		$queryAciteDomain = "SELECT domain as Domains, count(domain) as DomainCount
		FROM utilization
		WHERE PERIOD_DIFF(DATE_FORMAT(NOW(), '%Y%m'), DATE_FORMAT(request_time, '%Y%m'))<4
		GROUP BY domain";
		$ActiveDomains = $connection->deepQuery($queryAciteDomain);

		foreach($ActiveDomains as $domainList)
			$activeDomainsMonthly[] = ["domain"=>$domainList->Domains, "usage"=>$domainList->DomainCount];
		//End Active domains monthly
	
		// Bounce rate
		$queryBounceRate = "SELECT *
		FROM (SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 11 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 10 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 9 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 8 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 7 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 6 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 5 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 4 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 3 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 2 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 1 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(now(), '%b-%Y') as Month) AS Months LEFT JOIN
		(SELECT CONCAT(SUBSTRING(DATE_FORMAT(request_time, '%b'),1,3),DATE_FORMAT(request_time,'-%Y')) as dateName,
		Count(Distinct requestor) AS RequestCount
		FROM utilization
		WHERE PERIOD_DIFF(DATE_FORMAT(NOW(), '%Y%m'), DATE_FORMAT(request_time, '%Y%m'))<12
		GROUP BY YEAR(request_time), MONTH(request_time)) AS dataMonth
		ON dataMonth.dateName=Months.Month";
		$dataBounceRate = $connection->deepQuery($queryBounceRate);

		foreach($dataBounceRate as $monthBounceList)
		{
			if($monthBounceList->RequestCount != NULL)
			$bounceRateMontly[] = ["month"=>$monthBounceList->Month, "emails"=>$monthBounceList->RequestCount];
			else
			$bounceRateMontly[] = ["month"=>$monthBounceList->Month, "emails"=> 0];
		}
		//End Bounce rate

		//Updated profiles
		$queryUpdatedProfiles = "SELECT *
		FROM (SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 11 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 10 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 9 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 8 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 7 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 6 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 5 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 4 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 3 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 2 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 1 MONTH), '%b-%Y') as Month UNION
		SELECT DATE_FORMAT(now(), '%b-%Y') as Month) AS Months LEFT JOIN
		(SELECT CONCAT(SUBSTRING(DATE_FORMAT(last_update_date, '%b'),1,3),DATE_FORMAT(last_update_date,'-%Y')) as dateName,
		Count(email) AS UpdatedCount
		FROM person
		WHERE PERIOD_DIFF(DATE_FORMAT(NOW(), '%Y%m'), DATE_FORMAT(last_update_date, '%Y%m'))<12
		GROUP BY YEAR(last_update_date), MONTH(last_update_date)) AS dataMonth
		ON dataMonth.dateName=Months.Month";
		$updatedProfiles = $connection->deepQuery($queryUpdatedProfiles);

		foreach($updatedProfiles as $updatedProfile)
		{
			if($updatedProfile->UpdatedCount != NULL)
			$updatedProfilesMontly[] = ["month"=>$updatedProfile->Month, "emails"=>$updatedProfile->UpdatedCount];
			else
			$updatedProfilesMontly[] = ["month"=>$updatedProfile->Month, "emails"=> 0];
		}
		//End Updated profiles

		//Current number of running ads
		$queryRunningAds = "SELECT COUNT(active) AS CountAds FROM ads WHERE active=1";
		$runningAds = $connection->deepQuery($queryRunningAds);
		//End Current number of running ads

		// send variables to the view
		$this->view->title = "Audience";
		$this->view->visitorsWeecly = $visitorsWeecly;
		$this->view->visitorsMonthly = $visitorsMonthly;
		$this->view->newUsers = $newUsers;
		$this->view->currentNumberOfActiveUsers = $currentNoUsers[0]->CountUsers;
		$this->view->servicesUsageMonthly = $servicesUsageMonthly;
		$this->view->activeDomainsMonthly = $activeDomainsMonthly;
		$this->view->bounceRateMontly = $bounceRateMontly;
		$this->view->updatedProfilesMontly = $updatedProfilesMontly;
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
		$queryUsersWithProfile = "SELECT COUNT(email) AS PersonWithProfiles FROM person WHERE updated_by_user IS NOT NULL";
		$usersWithProfile = $connection->deepQuery($queryUsersWithProfile);

		//Users without profiles
		$queryUsersWithOutProfile = "SELECT COUNT(email) AS PersonWithOutProfiles FROM person WHERE updated_by_user IS NULL";
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
		$queryPrefilesPerPravince = "SELECT COUNT(email) as EmailCount,
		CASE province
			WHEN 'PINAR_DEL_RIO' THEN 'Pinar del Río'
			WHEN 'HAVANA' THEN 'Ciudad de La Habana'
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
			WHEN 'ISLA_DA_LA_JUVENTUD' THEN 'Isla de la Juventud'
		END AS ProvinceName
		FROM person
		WHERE province IS NOT NULL
		GROUP by province";
		$prefilesPerPravinceList = $connection->deepQuery($queryPrefilesPerPravince);
	
		foreach($prefilesPerPravinceList as $profilesList)
			$profilesPerProvince[] = ["region"=>$profilesList->ProvinceName, "profiles"=>$profilesList->EmailCount];
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

			$this->view->title = "Search for a profile";
		}
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

		$queryServices = "SELECT name, description, creator_email, category, deploy_key, insertion_date FROM service";
		$services = $connection->deepQuery($queryServices);

		$this->view->title = "List of services (" . count($services) . ")";
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

			// get the deploy key
			$deployKey = $this->request->getPost("deploykey");

			// deploy the service
			try
			{
				$deployResults = $deploy->deployServiceFromZip($zipPath, $deployKey, $zipName);
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
			$deployKey = $deployResults["deployKey"];
			$email = new Email();
			$email->sendEmail($creatorEmail, "Your service $serviceName was deployed", "<h1>Service deployed</h1><p>Your service $serviceName was deployed on $today. Your Deploy Key is $deployKey. Please keep your Deploy Key secured as per you will need it to upgrade or remove your service later on.</p><p>Thank you for using Apretaste</p>");

			// redirect to the upload page with success message
			$this->view->deployingMesssage = "Service deployed successfully. Your new deploy key is $deployKey. Please copy your deploy key now and keep it secret. Without your deploy key you will not be able to update your Service later on";
		}
	}
}
