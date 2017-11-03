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
		$connection = new Connection();

		// weekly gross traffic
		$weeklyGrossTraffic = array();
		$visits = $connection->query("
			SELECT COUNT(id) as visitors, DATE(request_date) as inserted
			FROM delivery
			GROUP BY DATE(request_date)
			ORDER BY inserted DESC
			LIMIT 7");
		foreach($visits as $visit) {
			if( ! $visit->visitors) $visit->visitors = 0;
			$weeklyGrossTraffic[] = ["date"=>date("D jS", strtotime($visit->inserted)), "visitors"=>$visit->visitors];
		}
		$weeklyGrossTraffic = array_reverse($weeklyGrossTraffic);

		// monthly gross traffic
		$monthlyGrossTraffic = array();
		$visits = $connection->query("
			SELECT COUNT(id) AS visitors, DATE_FORMAT(request_date,'%Y-%m') AS inserted
			FROM delivery
			GROUP BY DATE_FORMAT(request_date,'%Y-%m')
			ORDER BY inserted
			DESC LIMIT 30");
		foreach($visits as $visit) {
			if( ! $visit->visitors) $visit->visitors = 0;
			$monthlyGrossTraffic[] = ["date"=>date("M Y", strtotime($visit->inserted)), "visitors"=>$visit->visitors];
		}
		$monthlyGrossTraffic = array_reverse($monthlyGrossTraffic);

		// monthly unique traffic
		$monthlyUniqueTraffic = array();
		$visits = $connection->query("
			SELECT COUNT(DISTINCT `user`) as visitors, DATE_FORMAT(request_date,'%Y-%m') as inserted
			FROM delivery
			GROUP BY DATE_FORMAT(request_date,'%Y-%m')
			ORDER BY inserted DESC LIMIT 30");
		foreach($visits as $visit) $newUsers[] = ["date"=>date("M Y", strtotime($visit->inserted)), "visitors"=>$visit->visitors];
		$monthlyUniqueTraffic = array_reverse($newUsers);

		// monthly new users
		$monthlyNewUsers = array();
		$visits = $connection->query("
			SELECT COUNT(DISTINCT email) AS visitors, DATE_FORMAT(insertion_date,'%Y-%m') AS inserted
			FROM person
			GROUP BY DATE_FORMAT(insertion_date,'%Y-%m')
			ORDER BY inserted DESC LIMIT 30");

		foreach($visits as $visit) $newUsers[] = ["date"=>date("M Y", strtotime($visit->inserted)), "visitors"=>$visit->visitors];
		$monthlyNewUsers = array_reverse($newUsers);

		// last 30 days environment usage
		$last30EnvironmentUsage = array();
		$visits = $connection->query("
			SELECT COUNT(id) AS number, environment
			FROM  delivery
			WHERE request_date > (NOW() - INTERVAL 1 MONTH)
			GROUP BY environment");
		foreach($visits as $visit) {
			$last30EnvironmentUsage[] = ["number"=>$visit->number, "environment"=>$visit->environment];
		}

		// app versions
		$appVersions = array();
		$versions = $connection->query("
			SELECT COUNT(email) AS people, appversion
			FROM person
			WHERE appversion <> ''
			GROUP BY appversion");
		foreach($versions as $version) {
			$appVersions[] = ["people"=>$version->people, "version"=>"Version {$version->appversion}"];
		}

		// Last 30 days of service usage
		$last30DaysServiceUsage = array();
		$visits = $connection->query("
			SELECT COUNT(id) AS `usage`, request_service AS service
			FROM delivery
			WHERE request_date > DATE_SUB(NOW(), INTERVAL 1 MONTH)
			GROUP BY request_service");
		foreach($visits as $visit) {
			$last30DaysServiceUsage[] = ["service"=>$visit->service, "usage"=>$visit->usage];
		}

		// Last week emails sent vs not sent
		$lastWeekEmailsSent = $connection->query("SELECT COUNT(id) AS cnt FROM delivery WHERE delivery_code = 200 AND request_date > DATE_SUB(NOW(), INTERVAL 7 DAY)");
		$lastWeekEmailsNotSent = $connection->query("SELECT COUNT(id) AS cnt FROM delivery WHERE (delivery_code <> 200 OR delivery_code IS NULL) AND request_date > DATE_SUB(NOW(), INTERVAL 7 DAY)");

		// Last 30 days active domains
		$last30DaysActiveDomains = array();
		$visits = $connection->query("
			SELECT COUNT(id) AS `usage`, request_domain AS domain
			FROM delivery
			WHERE request_date > DATE_SUB(NOW(), INTERVAL 1 MONTH)
			GROUP BY request_domain");
		foreach($visits as $visit) {
			$last30DaysActiveDomains[] = ["domain"=>$visit->domain, "usage"=>$visit->usage];
		}

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
	}

	/**
	 * Profile
	 */
	public function profileAction()
	{
		//Users with profiles
		$connection = new Connection();
		$usersWithProfile = $connection->query("SELECT COUNT(email) AS PersonWithProfiles FROM person WHERE updated_by_user = 1");

		//Users without profiles
		$usersWithOutProfile = $connection->query("SELECT COUNT(email) AS PersonWithOutProfiles FROM person WHERE updated_by_user = 0");

		// Profile completion
		$profileData = $connection->query("
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
		$prefilesPerPravinceList = $connection->query("
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
				GROUP  BY b.province) as c");

		foreach($prefilesPerPravinceList as $profilesList)
		{
			if($profilesList->ProvCount != 0) $profilesPerProvince[] = ["region"=>$profilesList->NewProv, "profiles"=>$profilesList->ProvCount];
			else $profilesPerProvince[] = ["region"=>$profilesList->NewProv, "profiles"=>0];
		}

		// START updated profiles
		$visits = $connection->query("
			SELECT count(email) as num_profiles, DATE_FORMAT(last_update_date,'%Y-%m') as last_update
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
