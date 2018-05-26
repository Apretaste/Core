<?php

use Phalcon\Mvc\Controller;

class AnalyticsController extends Controller
{
	// do not let anonymous users pass
	public function initialize(){
		$security = new Security();
		$security->enforceLogin();
		$this->view->setLayout("manage");
	}

	/**
	 * Audience
	 */
	public function audienceAction()
	{
		// get temp directory
		$utils = new Utils();
		$temp = $utils->getTempDir();

		//
		// WEEKLY GROSS TRAFFIC
		//
		$cache = $temp . "weeklyGrossTraffic" . date("YmdH") . ".cache";
		if(file_exists($cache)) $weeklyGrossTraffic = unserialize(file_get_contents($cache));
		else {
			// get info from the database
			$visits = Connection::query("
				SELECT COUNT(id) as visitors, DATE(request_date) as inserted
				FROM delivery
				GROUP BY DATE(request_date)
				ORDER BY inserted DESC
				LIMIT 7");

			// format as JSON
			$weeklyGrossTraffic = [];
			foreach($visits as $visit) {
				if( ! $visit->visitors) $visit->visitors = 0;
				$weeklyGrossTraffic[] = ["date"=>date("D jS", strtotime($visit->inserted)), "visitors"=>$visit->visitors];
			}

			// save cache
			$weeklyGrossTraffic = array_reverse($weeklyGrossTraffic);
			file_put_contents($cache, serialize($weeklyGrossTraffic));
		}

		//
		// MONTHLY GROSS TRAFFIC
		//
		$cache = $temp . "monthlyGrossTraffic" . date("Ymd") . ".cache";
		if(file_exists($cache)) $monthlyGrossTraffic = unserialize(file_get_contents($cache));
		else {
			// get info from the database
			$visits = Connection::query("
				SELECT COUNT(id) AS visitors, DATE_FORMAT(request_date,'%Y-%m') AS inserted
				FROM delivery
				GROUP BY DATE_FORMAT(request_date,'%Y-%m')
				ORDER BY inserted
				DESC LIMIT 4");

			// format as JSON
			$monthlyGrossTraffic = [];
			foreach($visits as $visit) {
				if( ! $visit->visitors) $visit->visitors = 0;
				$monthlyGrossTraffic[] = ["date"=>date("M Y", strtotime($visit->inserted)), "visitors"=>$visit->visitors];
			}

			// save cache
			$monthlyGrossTraffic = array_reverse($monthlyGrossTraffic);
			file_put_contents($cache, serialize($monthlyGrossTraffic));
		}

		//
		// MONTHLY UNIQUE TRAFFIC
		//
		$cache = $temp . "monthlyUniqueTraffic" . date("Ymd") . ".cache";
		if(file_exists($cache)) $monthlyUniqueTraffic = unserialize(file_get_contents($cache));
		else {
			// get info from the database
			$visits = Connection::query("
				SELECT COUNT(DISTINCT `user`) as visitors, DATE_FORMAT(request_date,'%Y-%m') as inserted
				FROM delivery
				GROUP BY DATE_FORMAT(request_date,'%Y-%m')
				ORDER BY inserted DESC LIMIT 4");

			// format as JSON
			$monthlyUniqueTraffic = [];
			foreach($visits as $visit) $monthlyUniqueTraffic[] = ["date"=>date("M Y", strtotime($visit->inserted)), "visitors"=>$visit->visitors];
			$monthlyUniqueTraffic = array_reverse($monthlyUniqueTraffic);

			// save cache
			file_put_contents($cache, serialize($monthlyUniqueTraffic));
		}

		//
		// MONTHLY NEW USERS
		//
		$cache = $temp . "monthlyNewUsers" . date("Ymd") . ".cache";
		if(file_exists($cache)) $monthlyNewUsers = unserialize(file_get_contents($cache));
		else {
			// get info from the database
			$visits = Connection::query("
				SELECT COUNT(DISTINCT email) AS visitors, DATE_FORMAT(insertion_date,'%Y-%m') AS inserted
				FROM person
				GROUP BY DATE_FORMAT(insertion_date,'%Y-%m')
				ORDER BY inserted DESC LIMIT 30");

			// format as JSON
			$monthlyNewUsers = [];
			foreach($visits as $visit) $monthlyNewUsers[] = ["date"=>date("M Y", strtotime($visit->inserted)), "visitors"=>$visit->visitors];
			$monthlyNewUsers = array_reverse($monthlyNewUsers);

			// save cache
			file_put_contents($cache, serialize($monthlyNewUsers));
		}

		//
		// LAST 30 DAYS ENVIRONMENT USAGE
		//
		$cache = $temp . "last30EnvironmentUsage" . date("Ymd") . ".cache";
		if(file_exists($cache)) $last30EnvironmentUsage = unserialize(file_get_contents($cache));
		else {
			// get info from the database
			$visits = Connection::query("
				SELECT COUNT(id) AS number, environment
				FROM  delivery
				WHERE request_date > (NOW() - INTERVAL 1 MONTH)
				GROUP BY environment");

			// format as JSON
			$last30EnvironmentUsage = [];
			foreach($visits as $visit) $last30EnvironmentUsage[] = ["number"=>$visit->number, "environment"=>$visit->environment];

			// save cache
			file_put_contents($cache, serialize($last30EnvironmentUsage));
		}

		//
		// APP VERSIONS
		//
		$cache = $temp . "appVersions" . date("Ymd") . ".cache";
		if(file_exists($cache)) $appVersions = unserialize(file_get_contents($cache));
		else {
			// get infom from the database
			$versions = Connection::query("
				SELECT COUNT(email) AS people, appversion
				FROM person
				WHERE appversion <> ''
				GROUP BY appversion");

			// format as JSON
			$appVersions = [];
			foreach($versions as $version) $appVersions[] = ["people"=>$version->people, "version"=>"v{$version->appversion}"];

			// save cache
			file_put_contents($cache, serialize($appVersions));
		}

		//
		// LAST 30 DAYS SERVICE USAGE
		//
		$cache = $temp . "last30DaysServiceUsage" . date("Ymd") . ".cache";
		if(file_exists($cache)) $last30DaysServiceUsage = unserialize(file_get_contents($cache));
		else {
			// get info from the database
			$visits = Connection::query("
				SELECT COUNT(id) AS `usage`, request_service AS service
				FROM delivery
				WHERE request_date > DATE_SUB(NOW(), INTERVAL 1 MONTH)
				GROUP BY request_service");

			// format as JSON
			$last30DaysServiceUsage = [];
			foreach($visits as $visit) $last30DaysServiceUsage[] = ["service"=>$visit->service, "usage"=>$visit->usage];

			// save cache
			file_put_contents($cache, serialize($last30DaysServiceUsage));
		}

		//
		// LAST WEEK EMAILS SENT
		//
		$cache = $temp . "lastWeekEmailsSent" . date("Ymd") . ".cache";
		if(file_exists($cache)) $lastWeekEmailsSent = unserialize(file_get_contents($cache));
		else {
			$lastWeekEmailsSent = Connection::query("SELECT COUNT(id) AS cnt FROM delivery WHERE delivery_code = 200 AND request_date > DATE_SUB(NOW(), INTERVAL 7 DAY)");
			file_put_contents($cache, serialize($lastWeekEmailsSent));
		}

		//
		// LAST WEEK EMAILS NOT SENT
		//
		$cache = $temp . "lastWeekEmailsNotSent" . date("Ymd") . ".cache";
		if(file_exists($cache)) $lastWeekEmailsNotSent = unserialize(file_get_contents($cache));
		else {
			$lastWeekEmailsNotSent = Connection::query("SELECT COUNT(id) AS cnt FROM delivery WHERE (delivery_code <> 200 OR delivery_code IS NULL) AND request_date > DATE_SUB(NOW(), INTERVAL 7 DAY)");
			file_put_contents($cache, serialize($lastWeekEmailsNotSent));
		}

		//
		// LAST 30 DAYS ACTIVE DOMAINS
		//
		$cache = $temp . "last30DaysActiveDomains" . date("Ymd") . ".cache";
		if(file_exists($cache)) $last30DaysActiveDomains = unserialize(file_get_contents($cache));
		else {
			// get info from the database
			$visits = Connection::query("
				SELECT COUNT(id) AS `usage`, request_domain AS domain
				FROM delivery
				WHERE request_date > DATE_SUB(NOW(), INTERVAL 1 MONTH)
				GROUP BY request_domain");

			// format as JSON
			$last30DaysActiveDomains = [];
			foreach($visits as $visit) {
				$last30DaysActiveDomains[] = ["domain"=>$visit->domain, "usage"=>$visit->usage];
			}

			// save cache
			file_put_contents($cache, serialize($last30DaysActiveDomains));
		}

		//
		// NUMBERS OF COUPONS USED
		//
		$numberCouponsUsed = [];
		$visits = Connection::query("SELECT COUNT(id) AS `usage`, coupon FROM _cupones_used GROUP BY coupon");
		foreach($visits as $visit) $numberCouponsUsed[] = ["coupon"=>$visit->coupon, "usage"=>$visit->usage];

		// send variables to the view
		$this->view->title = "Audience";
		$this->view->weeklyGrossTraffic = $weeklyGrossTraffic;
		$this->view->monthlyGrossTraffic = $monthlyGrossTraffic;
		$this->view->monthlyUniqueTraffic = $monthlyUniqueTraffic;
		$this->view->monthlyNewUsers = $monthlyNewUsers;
		$this->view->last30EnvironmentUsage = $last30EnvironmentUsage;
		$this->view->appVersions = $appVersions;
		$this->view->last30DaysServiceUsage = $last30DaysServiceUsage;
		$this->view->lastWeekEmailsSent = $lastWeekEmailsSent[0]->cnt;
		$this->view->lastWeekEmailsNotSent = $lastWeekEmailsNotSent[0]->cnt;
		$this->view->last30DaysActiveDomains = $last30DaysActiveDomains;
		$this->view->numberCouponsUsed = $numberCouponsUsed;
	}

	/**
	 * Profile
	 */
	public function profileAction()
	{
		// users with profiles
		$usersWithProfile = Connection::query("SELECT COUNT(email) AS PersonWithProfiles FROM person WHERE updated_by_user=1 AND active=1");

		// users without profiles
		$usersWithOutProfile = Connection::query("SELECT COUNT(email) AS PersonWithOutProfiles FROM person WHERE updated_by_user=0 AND active=1");

		// Profile completion
		$profileData = Connection::query("
			SELECT 'Name' AS Caption, COUNT(first_name) AS Number FROM person WHERE updated_by_user IS NOT NULL AND (first_name IS NOT NULL OR last_name IS NOT NULL OR middle_name IS NOT NULL OR mother_name IS NOT NULL) AND active=1
			UNION
			SELECT 'DOB' AS Caption, COUNT(date_of_birth) AS Number FROM person WHERE updated_by_user IS NOT NULL AND date_of_birth IS NOT NULL AND active=1
			UNION
			SELECT 'Gender' AS Caption, COUNT(gender) AS Number FROM person WHERE updated_by_user IS NOT NULL AND gender IS NOT NULL AND active=1
			UNION
			SELECT 'Phone' AS Caption, COUNT(phone) AS Number FROM person WHERE updated_by_user IS NOT NULL AND phone IS NOT NULL AND active=1
			UNION
			SELECT 'Eyes' AS Caption, COUNT(eyes) AS Number FROM person WHERE updated_by_user IS NOT NULL AND eyes IS NOT NULL AND active=1
			UNION
			SELECT 'Skin' AS Caption, COUNT(skin) AS Number FROM person WHERE updated_by_user IS NOT NULL AND skin IS NOT NULL AND active=1
			UNION
			SELECT 'Body' AS Caption, COUNT(body_type) AS Number FROM person WHERE active=1
			UNION
			SELECT 'Province' AS Caption, COUNT(province) AS Number FROM person WHERE active=1
			UNION
			SELECT 'Picture' AS Picture, COUNT(picture) AS Number FROM person WHERE picture=1 AND active=1");

		foreach($profileData as $profilesList)
		{
			$percent = ($profilesList->Number * 100)/$usersWithProfile[0]->PersonWithProfiles;
			$percentFormated = number_format($percent, 2);
			$profilesData[] = ["caption"=>$profilesList->Caption, "number"=>$profilesList->Number, "percent"=>$percentFormated];
		}

		// Numbers of profiles per province
		// https://en.wikipedia.org/wiki/ISO_3166-2:CU
		$prefilesPerPravinceList = Connection::query("
			SELECT c.ProvCount,
				CASE c.mnth
					WHEN 'PINAR_DEL_RIO' THEN 'Pinar del Río'
					WHEN 'LA_HABANA' THEN 'Ciudad de La Habana'
					WHEN 'ARTEMISA' THEN 'CU-X01'
					WHEN 'MAYABEQUE' THEN 'CU-X02'
					WHEN 'MATANZAS' THEN 'Matanzas'
					WHEN 'VILLA_CLARA' THEN 'Villa Clara'
					WHEN 'CIENFUEGOS' THEN 'Cienfuegos'
					WHEN 'SANCTI_SPIRITUS' THEN 'Sancti Spíritus'
					WHEN 'CIEGO_DE_AVILA' THEN 'Ciego de Ávila'
					WHEN 'CAMAGUEY' THEN 'Camagüey'
					WHEN 'LAS_TUNAS' THEN 'Las Tunas'
					WHEN 'HOLGUIN' THEN 'Holguín'
					WHEN 'GRANMA' THEN 'Granma'
					WHEN 'SANTIAGO_DE_CUBA' THEN 'Santiago de Cuba'
					WHEN 'GUANTANAMO' THEN 'Guantánamo'
					WHEN 'ISLA_DE_LA_JUVENTUD' THEN 'Isla de la Juventud'
				END as NewProv
			FROM (
				SELECT COUNT(b.province) as ProvCount, a.mnth
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
				ON BINARY a.mnth = BINARY b.province
				AND b.province IS not NULL
				AND b.active = 1
				AND b.province IN ('PINAR_DEL_RIO', 'LA_HABANA', 'ARTEMISA', 'MAYABEQUE', 'MATANZAS', 'VILLA_CLARA', 'CIENFUEGOS', 'SANCTI_SPIRITUS', 'CIEGO_DE_AVILA', 'CAMAGUEY', 'LAS_TUNAS', 'HOLGUIN', 'GRANMA', 'SANTIAGO_DE_CUBA', 'GUANTANAMO', 'ISLA_DE_LA_JUVENTUD')
			GROUP BY b.province, a.mnth) as c");

		foreach($prefilesPerPravinceList as $profilesList)
		{
			if($profilesList->ProvCount != 0) $profilesPerProvince[] = ["region"=>$profilesList->NewProv, "profiles"=>$profilesList->ProvCount];
			else $profilesPerProvince[] = ["region"=>$profilesList->NewProv, "profiles"=>0];
		}

		// START updated profiles
		$visits = Connection::query("
			SELECT COUNT(email) as num_profiles, DATE_FORMAT(last_update_date,'%Y-%m') as last_update
			FROM person
			WHERE last_update_date IS NOT NULL
			GROUP BY last_update
			ORDER BY last_update DESC
			LIMIT 30");
		$updatedProfilesMonthly = array();
		foreach($visits as $visit) {
			$updatedProfilesMonthly[] = ["date"=>date("M Y", strtotime($visit->last_update)), "profiles"=>$visit->num_profiles];
		}
		$updatedProfilesMonthly = array_reverse($updatedProfilesMonthly);

		// send variables to the view
		$this->view->title = "Profile";
		$this->view->usersWithProfile = $usersWithProfile[0]->PersonWithProfiles;
		$this->view->usersWithoutProfile = $usersWithOutProfile[0]->PersonWithOutProfiles;
		$this->view->profilesData = $profilesData;
		$this->view->profilesPerProvince = $profilesPerProvince;
		$this->view->updatedProfilesMonthly = $updatedProfilesMonthly;
	}
}
