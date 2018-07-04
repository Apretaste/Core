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
		$unsentStoreItems = $connection->query("SELECT COUNT(id) AS cnt FROM _tienda_orders WHERE received=0");
		$isMonthlyRaffleOpen = $connection->query("SELECT raffle_id AS cnt FROM raffle WHERE end_date > CURRENT_TIMESTAMP");
		$openedAlerts = $connection->query("SELECT COUNT(id) as cnt FROM alerts WHERE fixed=0");
		$tasksWidget = $connection->query("SELECT task, DATEDIFF(CURRENT_DATE, executed) as days, delay, frequency FROM task_status");
		$hddFreeSpace = $this->getFreeSpaceHDD();
		$unrespondedNotes = SupportController::getUnrespondedNotesToApretaste();

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
		$this->view->unsentStoreItems = $unsentStoreItems[0]->cnt;
		$this->view->isMonthlyRaffleOpen = empty($isMonthlyRaffleOpen) ? "off" : "on";
		$this->view->tasksWidget = $tasksWidget;
		$this->view->openedAlerts = $openedAlerts[0]->cnt;
		$this->view->hddFreeSpace = $hddFreeSpace;
		$this->view->unrespondedNotes = count($unrespondedNotes);
	}

	/**
	 * Submit a new item to mark as completed
	 */
	public function itemSubmitAction()
	{
		$item = $this->request->get("item");

		// get and insert item
		if($item) Connection::query("INSERT INTO widgets (item) VALUES ('$item')");

		// go back to the dashboard
		$this->response->redirect('manage');
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
		$settings = \Linfo\Common::getVarFromFile("$wwwroot/configs/linfo.inc.php", 'settings');

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
}
