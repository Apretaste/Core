<?php

use Phalcon\Mvc\Controller;
  
class AnalyticsController extends Controller
{
    public function indexAction()
    {
    }

    public function audienceAction()
    {
        $connection = new Connection();
        
        // weekly visitors
        $queryWeecly = "SELECT DATE_FORMAT(request_time, '%a') as Weekday, count(request_time) as TimesRequested
                        FROM  `utilization` 
                        WHERE (request_time >= DATE_SUB( CURDATE( ) , INTERVAL 7 DAY))
                        group by date(request_time)
                      ";
        $visitorsWeeclyObj = $connection->deepQuery($queryWeecly);
		foreach($visitorsWeeclyObj as $weeklyvisits)
			$visitorsWeecly[] = ["day"=>$weeklyvisits->Weekday, "emails"=>$weeklyvisits->TimesRequested];
       
        //End // weekly visitors

        // montly visitors
        $queryMonthly = "SELECT DATE_FORMAT(request_time,'%b-%Y') as Month, count(request_time) as TimesRequested 
						FROM `utilization` 
						WHERE (request_time >= DATE_SUB( CURDATE( ) , INTERVAL 12 MONTH)) 
						group by EXTRACT(MONTH FROM date(request_time)) 
						ORDER BY request_time";
        $visitorsMonthlyObj = $connection->deepQuery($queryMonthly); 
                
        foreach($visitorsMonthlyObj as $visits)
			$visitorsMonthly[] = ["month"=>$visits->Month, "emails"=>$visits->TimesRequested];
		//End Monthly Visitors
		
        // New users per month
		$queryNewUsers = "SELECT DATE_FORMAT(insertion_date,'%b-%Y') as Month, count(insertion_date) as TimeInserted
							FROM `person`
							WHERE (insertion_date >= DATE_SUB( CURDATE( ) , INTERVAL 12 MONTH))
							group by EXTRACT(MONTH FROM date(insertion_date)) 
							ORDER BY insertion_date";
		$newUsersMonthly = $connection->deepQuery($queryNewUsers);
		
		foreach($newUsersMonthly as $newUsersList)
			$newUsers[] = ["month"=>$newUsersList->Month, "emails"=>$newUsersList->TimeInserted];
		//End new users per month
		
		//Current number of Users
		$queryCurrentNoUsers = "SELECT COUNT(email) as CountUsers
								FROM `person`";
		$currentNoUsers = $connection->deepQuery($queryCurrentNoUsers);
		//End Current number of Users

        // Get services usage monthly
		$queryMonthlyServiceUsage = "SELECT DATE_FORMAT(request_time,'%b-%Y') as Month, count(service) as ServicesCount 
									FROM `utilization` 
									WHERE (request_time >= DATE_SUB( CURDATE( ) , INTERVAL 12 MONTH))
									group by EXTRACT(MONTH FROM date(request_time)) 
									ORDER BY request_time";
		$MonthlyServiceUseage = $connection->deepQuery($queryMonthlyServiceUsage);
		foreach($MonthlyServiceUseage as $serviceList)
			$servicesUsageMonthly[] = ["service"=>$serviceList->Month, "usage"=>$serviceList->ServicesCount];
		//End Get services usage monthly

        // Active domains monthly
		$queryAciteDomain = "SELECT domain as Domains, count(domain) as DomainCount 
							FROM `utilization` 
							WHERE (request_time >= DATE_SUB( CURDATE( ) , INTERVAL 12 MONTH))
							group by domain";
		$ActiveDomains = $connection->deepQuery($queryAciteDomain);
		
		foreach($ActiveDomains as $domainList)
			$activeDomainsMonthly[] = ["domain"=>$domainList->Domains, "usage"=>$domainList->DomainCount];
		//End Active domains monthly

        // Bounce rate
		$queryBounceRate = "SELECT DATE_FORMAT(T.RequestTime, '%b') AS Month
							FROM (SELECT DATE(R.request_time) AS RequestTime, COUNT( R.requestor) AS RequestorCount
									FROM (SELECT request_time, requestor
											FROM utilization
											WHERE (request_time >= DATE_SUB( CURDATE( ) , INTERVAL 12 MONTH))
											ORDER BY `utilization`.`request_time`) R
									GROUP BY R.requestor) T
							WHERE T.RequestorCount = 1
							GROUP BY T.RequestTime";
		$bounceRate = $connection->deepQuery($queryBounceRate);
		
		//Month Count Variable
		$countJan = $countFeb = $countMar = $countApr = $countMay = $countJun = $countJul = $countAug = $countSep = $countOct = $countNov = $countDic = 0;
		
		foreach($bounceRate as $bounceCount)
		{
			if($bounceCount->Month == "Jan")
				$countJan++;
			if($bounceCount->Month == "Feb")
				$countFeb++;
			if($bounceCount->Month == "Mar")
				$countMar++;
			if($bounceCount->Month == "Apr")
				$countApr++;
			if($bounceCount->Month == "May")
				$countMay++;
			if($bounceCount->Month == "Jun")
				$countJun++;
			if($bounceCount->Month == "Jul")
				$countJul++;
			if($bounceCount->Month == "Aug")
				$countAug++;
			if($bounceCount->Month == "Sep")
				$countSep++;
			if($bounceCount->Month == "Oct")
				$countOct++;
			if($bounceCount->Month == "Nov")
				$countNov++;
			if($bounceCount->Month == "Dic")
				$countDic++;
		}
		
		$queryForMonth = "SELECT DISTINCT DATE_FORMAT(request_time, '%b') AS MonthOrder
							FROM utilization
							WHERE(request_time >= DATE_SUB( CURDATE( ) , INTERVAL 12 MONTH))
							ORDER BY request_time DESC";
		$monthOrder = $connection->deepQuery($queryForMonth);
		//print_r($monthOrder);
		//exit;
		foreach($monthOrder as $bounceList)
		{
			if($bounceList->MonthOrder == "Jan")
				$bounceRateMontly[] = ["month"=>$bounceList->MonthOrder, "emails"=>$countJan];
			if($bounceList->MonthOrder = "Feb")
				$bounceRateMontly[] = ["month"=>$bounceList->MonthOrder, "emails"=>$countFeb];
			if($bounceList->MonthOrder == "Mar")
				$bounceRateMontly[] = ["month"=>$bounceList->MonthOrder, "emails"=>$countMar];
			if($bounceList->MonthOrder == "Apr")
				$bounceRateMontly[] = ["month"=>$bounceList->MonthOrder, "emails"=>$countApr];
			if($bounceList->MonthOrder == "May")
				$bounceRateMontly[] = ["month"=>$bounceList->MonthOrder, "emails"=>$countMay];
			if($bounceList->MonthOrder == "Jun")
				$bounceRateMontly[] = ["month"=>$bounceList->MonthOrder, "emails"=>$countJun];
			if($bounceList->MonthOrder == "Jul")
				$bounceRateMontly[] = ["month"=>$bounceList->MonthOrder, "emails"=>$countJul];
			if($bounceList->MonthOrder == "Aug")
				$bounceRateMontly[] = ["month"=>$bounceList->MonthOrder, "emails"=>$countAug];
			if($bounceList->MonthOrder == "Sep")
				$bounceRateMontly[] = ["month"=>$bounceList->MonthOrder, "emails"=>$countSep];
			if($bounceList->MonthOrder == "Oct")
				$bounceRateMontly[] = ["month"=>$bounceList->MonthOrder, "emails"=>$countOct];
			if($bounceList->MonthOrder == "Nov")
				$bounceRateMontly[] = ["month"=>$bounceList->MonthOrder, "emails"=>$countNov];
			if($bounceList->MonthOrder == "Dic")
				$bounceRateMontly[] = ["month"=>$bounceList->MonthOrder, "emails"=>$countDic];
					
		}
		/*$bounceRateMontly = [
            ["month"=>"Jan", "emails"=>46],
            ["month"=>"Feb", "emails"=>47],
            ["month"=>"Mar", "emails"=>57],
            ["month"=>"Apr", "emails"=>32],
            ["month"=>"May", "emails"=>67],
            ["month"=>"Jun", "emails"=>46],
            ["month"=>"Jul", "emails"=>47],
            ["month"=>"Aug", "emails"=>37],
            ["month"=>"Sep", "emails"=>37],
            ["month"=>"Oct", "emails"=>46],
            ["month"=>"Nov", "emails"=>41],
            ["month"=>"Dec", "emails"=>24]
        ];*/
		//End Bounce rate

        // updated profiles
        $updatedProfilesMontly = [
            ["month"=>"Jan", "emails"=>46],
            ["month"=>"Feb", "emails"=>47],
            ["month"=>"Mar", "emails"=>575],
            ["month"=>"Apr", "emails"=>329],
            ["month"=>"May", "emails"=>675],
            ["month"=>"Jun", "emails"=>46],
            ["month"=>"Jul", "emails"=>47],
            ["month"=>"Aug", "emails"=>357],
            ["month"=>"Sep", "emails"=>372],
            ["month"=>"Oct", "emails"=>426],
            ["month"=>"Nov", "emails"=>41],
            ["month"=>"Dec", "emails"=>284]
        ];
		
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
											WHEN 'CIEGO_DE_AVILA' THEN 'Ciego de Ávila'
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

    //Action for Analytic for Search a Person
    public function searchPersonAction()
    {

    }
    
    //Action for Analytic for Services
    public function servicesAction()
    {

    }
    
    //Action for Analytics for Ads
    public function adsAction()
    {
        
    }
}
