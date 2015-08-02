<?php

use Phalcon\Mvc\Controller;
  
class AnalyticsController extends Controller
{
	public function indexAction()
	{
	}

	public function audienceAction()
	{
		// weecly visitors
		$visitorsWeecly = [
			["day"=>"Sun", "emails"=>446],
			["day"=>"Mon", "emails"=>565],
			["day"=>"Tue", "emails"=>432],
			["day"=>"Wed", "emails"=>23],
			["day"=>"Thr", "emails"=>123],
			["day"=>"Fri", "emails"=>123],
			["day"=>"Sat", "emails"=>123]
		];

		// montly visitors
		$visitorsMonthly = [
			["month"=>"Jan", "emails"=>4667],
			["month"=>"Feb", "emails"=>467],
			["month"=>"Mar", "emails"=>657],
			["month"=>"Apr", "emails"=>3267],
			["month"=>"May", "emails"=>4667],
			["month"=>"Jun", "emails"=>46634],
			["month"=>"Jul", "emails"=>4667],
			["month"=>"Aug", "emails"=>437],
			["month"=>"Sep", "emails"=>367],
			["month"=>"Oct", "emails"=>4667],
			["month"=>"Nov", "emails"=>4667],
			["month"=>"Dec", "emails"=>24667]
		];

		// new user per month
		$newUsers = [
			["month"=>"Jan", "emails"=>467],
			["month"=>"Feb", "emails"=>467],
			["month"=>"Mar", "emails"=>657],
			["month"=>"Apr", "emails"=>3267],
			["month"=>"May", "emails"=>4667],
			["month"=>"Jun", "emails"=>4664],
			["month"=>"Jul", "emails"=>467],
			["month"=>"Aug", "emails"=>437],
			["month"=>"Sep", "emails"=>367],
			["month"=>"Oct", "emails"=>46],
			["month"=>"Nov", "emails"=>4667],
			["month"=>"Dec", "emails"=>24566]
		];

		// get services usage monthly
		$servicesUsageMonthly = [
			["service"=>"clima", "usage"=>446],
			["service"=>"wikipedia", "usage"=>565],
			["service"=>"revolico", "usage"=>432],
			["service"=>"chiste", "usage"=>23],
			["service"=>"traduccion", "usage"=>123],
		];

		// active domains monthly
		$activeDomainsMonthly = [
			["domain"=>"nauta.cu", "usage"=>446],
			["domain"=>"infomed.sld.cu", "usage"=>565],
			["domain"=>"enet.co.cu", "usage"=>432],
			["domain"=>"cubanacan.cu", "usage"=>23],
			["domain"=>"gmail.com", "usage"=>15],
		];

		// bounce rate
		$bounceRateMontly = [
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
		];

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

		// send variables to the view
		$this->view->visitorsWeecly = $visitorsWeecly;
		$this->view->visitorsMonthly = $visitorsMonthly;
		$this->view->newUsers = $newUsers;
		$this->view->currentNumberOfActiveUsers = 261578;
		$this->view->servicesUsageMonthly = $servicesUsageMonthly;
		$this->view->activeDomainsMonthly = $activeDomainsMonthly;
		$this->view->bounceRateMontly = $bounceRateMontly;
		$this->view->updatedProfilesMontly = $updatedProfilesMontly;
		$this->view->currentNumberOfRunningaAds = 150;
	}
	
	public function profileAction()
	{
		// users with profile vs users without profile\
		$usersWithProfile = 46466;
		$usersWithoutProfile = 455859;

		// profile completion
		$profilesData = [
			["caption"=>"Name", "number"=>12000, "percent"=>70],
			["caption"=>"DOB", "number"=>56, "percent"=>50],
			["caption"=>"Gender", "number"=>45, "percent"=>20],
			["caption"=>"Phone", "number"=>343, "percent"=>58],
			["caption"=>"Eyes", "number"=>234, "percent"=>12],
			["caption"=>"Skin", "number"=>898, "percent"=>54],
			["caption"=>"Body", "number"=>23, "percent"=>76],
			["caption"=>"Hair", "number"=>878, "percent"=>12],
			["caption"=>"City", "number"=>34, "percent"=>34],
			["caption"=>"Province", "number"=>76, "percent"=>14],
			["caption"=>"About Me", "number"=>23, "percent"=>54],
			["caption"=>"Picture", "number"=>545, "percent"=>6]
		];

		// numbers of profiles per province
		$profilesPerProvince = [
			["region"=>"Pinar del Río", "profiles"=>1324110],
			["region"=>"CU-X01", "profiles"=>959574],
			["region"=>"Ciudad de La Habana", "profiles"=>2761477],
			["region"=>"CU-X02", "profiles"=>907563],
			["region"=>"Matanzas", "profiles"=>655875],
			["region"=>"Cienfuegos", "profiles"=>607906],
			["region"=>"Villa Clara", "profiles"=>380181],
			["region"=>"Sancti Spíritus", "profiles"=>371282],
			["region"=>"Ciego de Ávila", "profiles"=>67370],
			["region"=>"Camagüey", "profiles"=>300],
			["region"=>"Las Tunas", "profiles"=>38262],
			["region"=>"Granma", "profiles"=>38262],
			["region"=>"Holguín", "profiles"=>38262],
			["region"=>"Santiago de Cuba", "profiles"=>3855262],
			["region"=>"Guantánamo", "profiles"=>38262],
			["region"=>"Isla de la Juventud", "profiles"=>3825562]
		];

		// send variables to the view
		$this->view->usersWithProfile = $usersWithProfile;
		$this->view->usersWithoutProfile = $usersWithoutProfile;
		$this->view->usersWithProfileVsUsersWithoutProfile = $usersWithProfileVsUsersWithoutProfile;
		$this->view->profilesData = $profilesData;
		$this->view->profilesPerProvince = $profilesPerProvince;
	}

	public function searchPersonAction()
	{
		
	}
	
	public function servicesAction()
	{
		
	}
}
