<?php

use Phalcon\Mvc\Controller;
require_once '../lib/Linfo/standalone_autoload.php';

class ManageController extends Controller
{
	// do not let anonymous users pass
	public function initialize()
	{
		$security = new Security();
		$security->enforceLogin();
	}

	/**
	 * Dashboard
	 */
	public function indexAction()
	{
		$connection = new Connection();

		// get data for the blocks
		$numberActiveUsers = $connection->query("SELECT COUNT(email) as cnt FROM person WHERE active=1");
		$numberTotalUsers = $connection->query("SELECT COUNT(email) as cnt FROM person");
		$numberUserProfiles = $connection->query("SELECT COUNT(email) as cnt FROM person WHERE last_update_date IS NOT NULL AND active=1");
		$emailsNotSentLastWeek = $connection->query("SELECT COUNT(id) AS cnt FROM delivery WHERE delivery_code<>'200' AND delivery_code<>'555' AND request_date > (CURDATE()-INTERVAL 7 DAY)");
		$emailsNotReceivedByTheAppLastWeek = $connection->query("SELECT COUNT(id) AS cnt FROM delivery WHERE delivery_code='555' AND request_date > (CURDATE()-INTERVAL 7 DAY)");
		$creditsOffered = $connection->query("SELECT SUM(credit) AS cnt FROM person WHERE active=1");
		$queryRunningAds = $connection->query("SELECT COUNT(active) AS cnt FROM ads WHERE active=1");
		$supportNewCount = $connection->query("SELECT COUNT(id) AS cnt FROM support_tickets WHERE status='NEW'");
		$supportPendingCount = $connection->query("SELECT COUNT(id) AS cnt FROM support_tickets WHERE status='PENDING'");
		$mailListRegisteredUsers = $connection->query("SELECT COUNT(email) as cnt FROM person WHERE active=1 and appversion<>'' and mail_list=1");
		$openedSurveysCount = $connection->query("SELECT COUNT(id) AS cnt FROM _survey WHERE deadline > CURRENT_TIMESTAMP AND active=1");
		$openedContestsCount = $connection->query("SELECT COUNT(id) AS cnt FROM _concurso WHERE end_date > CURRENT_TIMESTAMP");
		$isMonthlyRaffleOpen = $connection->query("SELECT raffle_id AS cnt FROM raffle WHERE end_date > CURRENT_TIMESTAMP");
		$openedAlerts = $connection->query("SELECT COUNT(id) as cnt FROM alerts WHERE fixed=0");
		$tasksWidget = $connection->query("SELECT task, DATEDIFF(CURRENT_DATE, executed) as days, delay, frequency FROM task_status");
		$hddFreeSpace = $this->getFreeSpaceHDD();
		$trelloByDept = $this->getTrelloTasksByDepartment();
		$fbPostCurrentMonth = $this->getPostFacebookPageCurrentMonth();

		// send data to the view
		$this->view->title = "Dashboard";
		$this->view->numberActiveUsers = $numberActiveUsers[0]->cnt;
		$this->view->numberTotalUsers = $numberTotalUsers[0]->cnt;
		$this->view->numberUserProfiles = $numberUserProfiles[0]->cnt;
		$this->view->emailsNotSentLastWeek = $emailsNotSentLastWeek[0]->cnt;
		$this->view->emailsNotReceivedByTheAppLastWeek = $emailsNotReceivedByTheAppLastWeek[0]->cnt;
		$this->view->creditsOffered = $creditsOffered[0]->cnt;
		$this->view->queryRunningAds = $queryRunningAds[0]->cnt;
		$this->view->supportNewCount = $supportNewCount[0]->cnt;
		$this->view->supportPendingCount = $supportPendingCount[0]->cnt;
		$this->view->mailListRegisteredUsers = $mailListRegisteredUsers[0]->cnt;
		$this->view->openedSurveysCount = $openedSurveysCount[0]->cnt;
		$this->view->openedContestsCount = $openedContestsCount[0]->cnt;
		$this->view->isMonthlyRaffleOpen = empty($isMonthlyRaffleOpen) ? "off" : "on";
		$this->view->tasksWidget = $tasksWidget;
		$this->view->openedAlerts = $openedAlerts[0]->cnt;
		$this->view->trelloByDept = $trelloByDept;
		$this->view->fbPostCurrentMonth = $fbPostCurrentMonth;
		$this->view->hddFreeSpace = $hddFreeSpace;
	}

	/**
	 * Calculate free space in the hard drive
	 * @author kuma
	 */
	private function getFreeSpaceHDD()
	{
		// get settings
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];
		$settings = \Linfo\Common::getVarFromFile($wwwroot.'/configs/linfo.inc.php', 'settings');

		// get free space hdd
		$linfo = new \Linfo\Linfo($settings);
		$parser = $linfo->getParser();
		$hd = $parser->getMounts();

		$hddFreeSpace = 0;
		foreach($hd as $mount) {
			if ($mount['mount'] == "/") {
				$hddFreeSpace = $mount['free_percent'];
				break;
			}
		}

		return $hddFreeSpace;
	}

	/**
	 * Calculate Trello Tasks By Department
	 * @author salvipascual
	 */
	private function getTrelloTasksByDepartment()
	{
		// list of columns
		$columns = [
			'5a69efde4df6faae796ec70d' => 'WAITING', // active
			'559de2d76667b9ef5961fc49' => 'WAITING', // in progress
			'5701a436da6e5e1975c763a8' => 'WAITING', // test/deploy/waiting
			'5a733bac2e793f3e025ad445' => 'DONE']; // done

		// list of people in departments
		$departs = [
			'599db911ca87c84a002ae958' => 'TECHNOLOGY', // carlos
			'56f89c3fc304a94a72f7ce13' => 'TECHNOLOGY', // kuma
			'559e8f3ca982a4d5ecad3528' => 'CENTRAL_OPS', // salvi
			'577e9608ac30d2d5ec896e2b' => 'MARKETING']; // alex

		// create cache file
		$utils = new Utils();
		$cache = $utils->getTempDir() . "trello" . date("Ymd") . ".cache";

		// use cache if exist
		if(file_exists($cache)) $cardsJson = json_decode(file_get_contents($cache));
		else {
			// get the keys
			$di = \Phalcon\DI\FactoryDefault::getDefault();
			$apiKey = $di->get('config')['trello']['key'];
			$apiToken = $di->get('config')['trello']['token'];

			// get all the cards
			$krawler = new Krawler();
			$cards = $krawler->getRemoteContent("https://api.trello.com/1/boards/SfAGG9cZ/cards?key=$apiKey&token=$apiToken");
			$cardsJson = json_decode($cards);

			// save cache
			file_put_contents($cache, $cards);
		}

		// create array of results
		$results = [
			'TECHNOLOGY' => ['WAITING'=>0, 'DONE'=>0, 'TOTAL'=>0],
			'CENTRAL_OPS' => ['WAITING'=>0, 'DONE'=>0, 'TOTAL'=>0],
			'MARKETING' => ['WAITING'=>0, 'DONE'=>0, 'TOTAL'=>0]];

		// calculate results
		foreach ($cardsJson as $card) {
			// get status
			if(isset($columns[$card->idList])) $status = $columns[$card->idList];
			else continue;

			// get department
			foreach ($card->idMembers as $member) {
				if(isset($departs[$member])) $dept = $departs[$member];
				else continue;

				// increase counters in response
				$results[$dept][$status]++;
				$results[$dept]['TOTAL']++;
			}
		}

		return $results;
	}

	/**
	 * Get post in our Facebook page this month
	 * @author salvipascual
	 */
	private function getPostFacebookPageCurrentMonth()
	{
		// create cache file
		$utils = new Utils();
		$cache = $utils->getTempDir() . "fbposts" . date("YmdH") . ".cache";

		// use cache if exist
		if(file_exists($cache)) $posts = unserialize(file_get_contents($cache));
		else {
			$di = \Phalcon\DI\FactoryDefault::getDefault();
			$key = $di->get('config')['facebook']['key'];
			$secret = $di->get('config')['facebook']['secret'];

			// connect to facebook and get access token
			$fb = new \Facebook\Facebook(['app_id'=>$key, 'app_secret'=>$secret, 'default_graph_version'=>'v2.10']);
			$accessToken = $fb->getApp()->getAccessToken();

			// get posts from the first day of the month
			$time = strtotime (date("Y-m")."-01");
			$response = $fb->get("/285099284865702/posts?since=$time", $accessToken);
			$posts = json_decode($response->getBody())->data;

			// save cache
			file_put_contents($cache, serialize($posts));
		}

		return $posts;
	}
}
