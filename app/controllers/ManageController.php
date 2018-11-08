<?php

use Phalcon\Mvc\Controller;
use \Phalcon\DI;

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
		// get data for the blocks
		$numberActiveUsers = Connection::query("SELECT COUNT(id) as cnt FROM person WHERE active=1");
		$numberTotalUsers = Connection::query("SELECT COUNT(id) as cnt FROM person");
		$numberUserProfiles = Connection::query("SELECT COUNT(id) as cnt FROM person WHERE last_update_date IS NOT NULL AND active=1");
		$emailsNotSentLastWeek = Connection::query("SELECT COUNT(id_person) AS cnt FROM delivery WHERE delivery_code<>'200' AND delivery_code<>'555' AND request_date > (CURDATE()-INTERVAL 7 DAY)");
		$emailsNotReceivedByTheAppLastWeek = Connection::query("SELECT COUNT(id_person) AS cnt FROM delivery WHERE delivery_code='555' AND request_date > (CURDATE()-INTERVAL 7 DAY)");
		$creditsOffered = Connection::query("SELECT SUM(credit) AS cnt FROM person WHERE active=1");
		$queryRunningAds = Connection::query("SELECT COUNT(active) AS cnt FROM ads WHERE active=1");
		$supportNewCount = Connection::query("SELECT COUNT(id) AS cnt FROM support_tickets WHERE status='NEW'");
		$supportPendingCount = Connection::query("SELECT COUNT(id) AS cnt FROM support_tickets WHERE status='PENDING'");
		$mailListRegisteredUsers = Connection::query("SELECT COUNT(id) as cnt FROM person WHERE active=1 and appversion<>'' and mail_list=1");
		$openedSurveysCount = Connection::query("SELECT COUNT(id) AS cnt FROM _survey WHERE deadline > CURRENT_TIMESTAMP AND active=1");
		$openedContestsCount = Connection::query("SELECT COUNT(id) AS cnt FROM _concurso WHERE end_date > CURRENT_TIMESTAMP");
		$unsentStoreItems = Connection::query("SELECT COUNT(id) AS cnt FROM _tienda_orders WHERE received=0");
		$isMonthlyRaffleOpen = Connection::query("SELECT raffle_id AS cnt FROM raffle WHERE end_date > CURRENT_TIMESTAMP");

		// alerts
		// TODO: refactor to model
		$config = Di::getDefault()->get('config')['database_dev'];
		$db = new mysqli($config['host'], $config['user'], $config['password'], $config['database']);
		$openedAlerts = $db->query("SELECT COUNT(id) as cnt FROM alerts WHERE fixed=0");
		$openedAlerts = [$openedAlerts->fetch_object()];

		$tasksWidget = Connection::query("SELECT task, DATEDIFF(CURRENT_DATE, executed) as days, delay, frequency FROM task_status");
		$hddFreeSpace = $this->getFreeSpaceHDD();
		$unrespondedNotes = SupportController::getUnrespondedNotesToApretaste();
		$gmailAccountsActive = Connection::query("SELECT COUNT(email) AS cnt FROM delivery_gmail WHERE active=1");
		$gmailAccountsTotal = Connection::query("SELECT COUNT(email) AS cnt FROM delivery_gmail");

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
		$this->view->gmailAccountsActive = $gmailAccountsActive[0]->cnt;
		$this->view->gmailAccountsTotal = $gmailAccountsTotal[0]->cnt;
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
