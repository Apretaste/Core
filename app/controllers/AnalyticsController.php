<?php

use Phalcon\Mvc\Controller;
  
class AnalyticsController extends Controller
{
    public function indexAction()
    {
		//Include simple.phtml Layout
		$this->view->setLayout('simple');
    }

    public function audienceAction()
    {
        $connection = new Connection();
        
        // Weekly visitors
		//DONE and Revised
        $queryWeecly = "SELECT*
						FROM (SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 6 DAY), '%a') AS Weekday
							UNION
							  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 5 DAY), '%a') AS Weekday
							UNION
							  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 4 DAY), '%a') AS Weekday
							UNION
							  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 3 DAY), '%a') AS Weekday
							UNION
							  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 2 DAY), '%a') AS Weekday
							UNION
							  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 1 DAY), '%a') AS Weekday
							UNION
								SELECT DATE_FORMAT(now(), '%a') as Weekday) AS Weekdays
						LEFT JOIN
						(SELECT DATE_FORMAT(request_time, '%a') as DataWeekday, count(request_time) as TimesRequested
						FROM  `utilization` 
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
		//DONE Revised 
        $queryMonthly = "SELECT *
						FROM (SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 11 MONTH), '%b-%Y') as Month
							  UNION
							 SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 10 MONTH), '%b-%Y') as Month
							UNION
							  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 9 MONTH), '%b-%Y') as Month
							UNION 
							  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 8 MONTH), '%b-%Y') as Month
							 UNION
							  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 7 MONTH), '%b-%Y') as Month
							UNION 
							  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 6 MONTH), '%b-%Y') as Month
							 UNION
							  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 5 MONTH), '%b-%Y') as Month
							 UNION
							  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 4 MONTH), '%b-%Y') as Month
							 UNION
							  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 3 MONTH), '%b-%Y') as Month
							 UNION
							  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 2 MONTH), '%b-%Y') as Month
							UNION 
							  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 1 MONTH), '%b-%Y') as Month
							 UNION
							  SELECT DATE_FORMAT(now(), '%b-%Y') as Month) AS Months
						LEFT JOIN
						(SELECT DATE_FORMAT(request_time,'%b-%Y') as dataName, count(request_time) as TimesRequested 
						 FROM `utilization` 
						 WHERE PERIOD_DIFF(DATE_FORMAT(NOW(), '%Y%m'), DATE_FORMAT(`request_time`, '%Y%m'))<12 
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
		//DONE and Revise
		$queryNewUsers = "SELECT *
							FROM (SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 11 MONTH), '%b-%Y') as Month
								  UNION
								 SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 10 MONTH), '%b-%Y') as Month
								UNION
								  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 9 MONTH), '%b-%Y') as Month
								UNION 
								  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 8 MONTH), '%b-%Y') as Month
								 UNION
								  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 7 MONTH), '%b-%Y') as Month
								UNION 
								  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 6 MONTH), '%b-%Y') as Month
								 UNION
								  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 5 MONTH), '%b-%Y') as Month
								 UNION
								  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 4 MONTH), '%b-%Y') as Month
								 UNION
								  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 3 MONTH), '%b-%Y') as Month
								 UNION
								  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 2 MONTH), '%b-%Y') as Month
								UNION 
								  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 1 MONTH), '%b-%Y') as Month
								 UNION
								  SELECT DATE_FORMAT(now(), '%b-%Y') as Month) AS Months
							LEFT JOIN
							(SELECT DATE_FORMAT(insertion_date,'%b-%Y') as dataName, count(insertion_date) as TimeInserted
							 FROM `person`
							 WHERE PERIOD_DIFF(DATE_FORMAT(NOW(), '%Y%m'), DATE_FORMAT(`insertion_date`, '%Y%m'))<12
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
		$queryCurrentNoUsers = "SELECT COUNT(email) as CountUsers
								FROM `person`";
		$currentNoUsers = $connection->deepQuery($queryCurrentNoUsers);
		//End Current number of Users

        // Get services usage monthly
		//DONE and Revise
		$queryMonthlyServiceUsage = "SELECT *
									FROM (SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 11 MONTH), '%b-%Y') as Month
										  UNION
										 SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 10 MONTH), '%b-%Y') as Month
										UNION
										  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 9 MONTH), '%b-%Y') as Month
										UNION 
										  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 8 MONTH), '%b-%Y') as Month
										 UNION
										  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 7 MONTH), '%b-%Y') as Month
										UNION 
										  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 6 MONTH), '%b-%Y') as Month
										 UNION
										  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 5 MONTH), '%b-%Y') as Month
										 UNION
										  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 4 MONTH), '%b-%Y') as Month
										 UNION
										  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 3 MONTH), '%b-%Y') as Month
										 UNION
										  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 2 MONTH), '%b-%Y') as Month
										UNION 
										  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 1 MONTH), '%b-%Y') as Month
										 UNION
										  SELECT DATE_FORMAT(now(), '%b-%Y') as Month) AS Months
									LEFT JOIN
										(SELECT DATE_FORMAT(request_time,'%b-%Y') as DataName, COUNT(service) as ServicesCount 
										FROM utilization
										WHERE PERIOD_DIFF(DATE_FORMAT(NOW(), '%Y%m'), DATE_FORMAT(`request_time`, '%Y%m'))<12
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
		/*$servicesUsageMonthly = 
				[["service"=>"Sep", "usage"=>75],
				["service"=>"Oct", "usage"=>85],
				["service"=>"Nov", "usage"=>100],
				["service"=>"Dec", "usage"=>89],
				["service"=>"Jan", "usage"=>77],
				["service"=>"Feb", "usage"=>90],
				["service"=>"Mar", "usage"=>73],
				["service"=>"Apr", "usage"=>88],
				["service"=>May, "usage"=>80],
				["service"=>"Jun", "usage"=>79],
				["service"=>"Jul", "usage"=>48],
				["service"=>Aug, "usage"=>1]];*/
		//End Get services usage monthly

        // Active domains last 4 Month
		//DONE and Revise
		$queryAciteDomain = "SELECT domain as Domains, count(domain) as DomainCount 
							FROM `utilization` 
							WHERE PERIOD_DIFF(DATE_FORMAT(NOW(), '%Y%m'), DATE_FORMAT(`request_time`, '%Y%m'))<4
							GROUP BY domain";
		$ActiveDomains = $connection->deepQuery($queryAciteDomain);
		
		foreach($ActiveDomains as $domainList)
			$activeDomainsMonthly[] = ["domain"=>$domainList->Domains, "usage"=>$domainList->DomainCount];
		//End Active domains monthly

        // Bounce rate
		//DONE and Revised
		
		//Month Count Variable		
		$queryBounceRate = "SELECT *
						FROM (SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 11 MONTH), '%b-%Y') as Month
							UNION
								SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 10 MONTH), '%b-%Y') as Month
							UNION
								SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 9 MONTH), '%b-%Y') as Month
							UNION 
								SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 8 MONTH), '%b-%Y') as Month
							UNION
								SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 7 MONTH), '%b-%Y') as Month
							UNION 
								SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 6 MONTH), '%b-%Y') as Month
							UNION
								SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 5 MONTH), '%b-%Y') as Month
							UNION
								SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 4 MONTH), '%b-%Y') as Month
							UNION
								SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 3 MONTH), '%b-%Y') as Month
							UNION
								SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 2 MONTH), '%b-%Y') as Month
							UNION 
								SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 1 MONTH), '%b-%Y') as Month
							UNION
								SELECT DATE_FORMAT(now(), '%b-%Y') as Month) AS Months
						LEFT JOIN
							(SELECT CONCAT(SUBSTRING(DATE_FORMAT(request_time, '%b'),1,3),DATE_FORMAT(`request_time`,'-%Y')) as dateName, 
									Count(Distinct requestor) AS RequestCount
							FROM `utilization`
							WHERE PERIOD_DIFF(DATE_FORMAT(NOW(), '%Y%m'), DATE_FORMAT(`request_time`, '%Y%m'))<12
							GROUP BY YEAR(`request_time`), MONTH(`request_time`)) AS dataMonth
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
		//DONE and Revised
		$queryUpdatedProfiles = "SELECT *
								FROM (SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 11 MONTH), '%b-%Y') as Month
									  UNION
									 SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 10 MONTH), '%b-%Y') as Month
									UNION
									  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 9 MONTH), '%b-%Y') as Month
									UNION 
									  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 8 MONTH), '%b-%Y') as Month
									 UNION
									  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 7 MONTH), '%b-%Y') as Month
									UNION 
									  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 6 MONTH), '%b-%Y') as Month
									 UNION
									  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 5 MONTH), '%b-%Y') as Month
									 UNION
									  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 4 MONTH), '%b-%Y') as Month
									 UNION
									  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 3 MONTH), '%b-%Y') as Month
									 UNION
									  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 2 MONTH), '%b-%Y') as Month
									UNION 
									  SELECT DATE_FORMAT(DATE_SUB(now(), INTERVAL 1 MONTH), '%b-%Y') as Month
									 UNION
									  SELECT DATE_FORMAT(now(), '%b-%Y') as Month) AS Months
								LEFT JOIN
								(SELECT CONCAT(SUBSTRING(DATE_FORMAT(last_update_date, '%b'),1,3),DATE_FORMAT(`last_update_date`,'-%Y')) as dateName, 
								Count(email) AS UpdatedCount
								FROM `person`
								WHERE PERIOD_DIFF(DATE_FORMAT(NOW(), '%Y%m'), DATE_FORMAT(`last_update_date`, '%Y%m'))<12
								GROUP BY YEAR(`last_update_date`), MONTH(`last_update_date`)) AS dataMonth
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
		$queryRunningAds = "SELECT COUNT(active) AS CountAds
							FROM ads 
							WHERE active = 'TRUE'";
		$runningAds = $connection->deepQuery($queryRunningAds);
		//End Current number of running ads
		
        // send variables to the view
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

    public function profileAction()
    {
		$connection = new Connection();
		
        // Users with profile vs users without profile
		//Users with profiles
		$queryUsersWithProfile = "SELECT COUNT(email) AS PersonWithProfiles
								 FROM `person` 
								 WHERE updated_by_user IS NOT NULL";
		$usersWithProfile = $connection->deepQuery($queryUsersWithProfile);
		
		//Users without profiles
		$queryUsersWithOutProfile = "SELECT COUNT(email) AS PersonWithOutProfiles
									 FROM `person` 
									 WHERE updated_by_user IS NULL";	
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
											WHEN 'CIEGO_DE_AVILA' THEN 'Ciego de �?vila'
											WHEN 'CAMAGUEY' THEN 'Camagüey'
											WHEN 'LAS_TUNAS' THEN 'Las Tunas'
											WHEN 'HOLGUIN' THEN 'Holguín'
											WHEN 'GRANMA' THEN 'Granma'
											WHEN 'SANTIAGO_DE_CUBA' THEN 'Santiago de Cuba'
											WHEN 'GUANTANAMO' THEN 'Guantánamo'
											WHEN 'ISLA_DA_LA_JUVENTUD' THEN 'Isla de la Juventud'
										END AS ProvinceName
										FROM `person`
										WHERE province IS NOT NULL
										GROUP by province";
		$prefilesPerPravinceList = $connection->deepQuery($queryPrefilesPerPravince);
		
		foreach($prefilesPerPravinceList as $profilesList)
			$profilesPerProvince[] = ["region"=>$profilesList->ProvinceName, "profiles"=>$profilesList->EmailCount];
		// numbers of profiles per province

        // send variables to the view
        $this->view->usersWithProfile = $usersWithProfile[0]->PersonWithProfiles;
        $this->view->usersWithoutProfile = $usersWithOutProfile[0]->PersonWithOutProfiles;
        $this->view->usersWithProfileVsUsersWithoutProfile = $usersWithProfileVsUsersWithoutProfile;
        $this->view->profilesData = $profilesData;
        $this->view->profilesPerProvince = $profilesPerProvince;
    }   
    
    //Action for Analytic for Services
    public function servicesAction()
    {
		$connection = new Connection();
		
		//Services
		$queryServices = "SELECT description AS Description, creator_email AS Creator, category AS Category, deploy_key AS DeployKey, insertion_date AS InsertionDate
							FROM service";
		$services = $connection->deepQuery($queryServices);
		
		foreach($services as $servicesList)
			$serviceList[] = ["description"=>$servicesList->Description, "creator"=>$servicesList->Creator, "category"=>$servicesList->Category, "deployKey"=>$servicesList->DeployKey, "insertionDate"=>$servicesList->InsertionDate];
		
		$this->view->servicesData = $serviceList;
		//End Service
    }
    
    //Action for Analytics for Ads
    public function adsAction()
    {
        $connection = new Connection();
		
		//Ads active
		$queryAdsActive = "SELECT owner AS Owner, time_inserted AS InsertedDay, title AS Tittle, impresions AS Impressions, paid_date AS DayPaid, expiration_date AS ExpirationDate
							FROM ads";
		$adsActive = $connection->deepQuery($queryAdsActive);
		
		foreach($adsActive as $adsActiveList)
			$adsList[] = ["owner"=>$adsActiveList->Owner, "insetedDate"=>$adsActiveList->InsertedDay, "tittle"=>$adsActiveList->Tittle, "impressions"=>$adsActiveList->Impressions, "dayPaid"=>$adsActiveList->DayPaid, "expiration"=>$adsActiveList->ExpirationDate];
		//End Ads active
		
		$this->view->adsData = $adsList;
    }

	//Action for Analytic for Search a Person
	public function profilesearchAction()
	{
		if($this->request->isPost())
		{
			$connection = new Connection();
			$email = $this->request->getPost("email");
			
			$querryProfileSearch = "SELECT first_name, middle_name, last_name, mother_name, date_of_birth,  TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) AS Age, gender, phone, eyes, skin, body_type, hair, city, province, about_me, credit, picture, email
									FROM `person` 
									WHERE email = '$email'";
			$profileSearch = $connection->deepQuery($querryProfileSearch);
			
			if($profileSearch)
			{
				//If the picture exist return the email, if not, return 0
				$picture = $profileSearch[0]->picture;
				($picture == 1)? $this->view->email = $profileSearch[0]->email : $this->view->email = 0;
				
				$firstName = $profileSearch[0]->first_name;
				($firstName != "NULL")? $this->view->firstName = $firstName : $this->view->firstName = 0;
				
				$middleName = $profileSearch[0]->middle_name;
				($middleName != "NULL")? $this->view->middleName = $middleName : $this->view->middleName = 0;
				
				$lastName = $profileSearch[0]->last_name;
				($lastName != "NULL")? $this->view->lastName = $lastName : $this->view->lastName = 0;
				
				$motherName = $profileSearch[0]->mother_name;
				($motherName != "NULL")? $this->view->motherName = $motherName : $this->view->$motherName = 0;
				
				$dob = $profileSearch[0]->date_of_birth;
				($dob != "NULL")? $this->view->dob = $dob : $this->view->dob = 0;
				
				$age = $profileSearch[0]->Age;
				($age != "NULL")? $this->view->age = $age : $this->view->age = 0;
				
				$gender = $profileSearch[0]->gender;
				($gender != "NULL")? $this->view->gender = $gender : $this->view->gender = 0;
				
				$phone = $profileSearch[0]->phone;
				($phone != "NULL")? $this->view->phone = $phone : $this->view->$this->view->phone = 0;
						
				$eyes = $profileSearch[0]->eyes;
				($eyes != "NULL")? $this->view->eyes = $eyes : $this->view->eyes = 0;
						
				$skin = $profileSearch[0]->skin;
				($skin != "NULL")? $this->view->skin = $skin : $this->view->skin = 0;
						
				$body = $profileSearch[0]->body_type;
				($body != "NULL")? $this->view->body = $body : $this->view->body = 0;
						
				$hair = $profileSearch[0]->hair;
				($hair != "NULL")? $this->view->hair = $hair : $this->view->hair = 0;
						
				$city = $profileSearch[0]->city;
				($city != "NULL")? $this->view->city = $city : $this->view->city = 0;
						
				$province = $profileSearch[0]->province;
				($province != "NULL")? $this->view->province = $province : $this->view->province = 0;
						
				$aboutMe = $profileSearch[0]->about_me;
				($aboutMe != "NULL")? $this->view->aboutMe = $aboutMe : $this->view->aboutMe = 0;
						
				$credit = $profileSearch[0]->credit;
				($credit != "NULL")? $this->view->credit = $credit : $this->view->credit = 0;
			}
			else
			{
				$this->view->noProfileFound = 0;
			}		
		}
	}
}
