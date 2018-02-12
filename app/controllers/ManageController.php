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
		$creditsOffered = $connection->query("SELECT SUM(credit) AS cnt FROM person WHERE active=1");
		$queryRunningAds = $connection->query("SELECT COUNT(active) AS cnt FROM ads WHERE active=1");
		$supportNewCount = $connection->query("SELECT COUNT(id) AS cnt FROM support_tickets WHERE status='NEW'");
		$supportPendingCount = $connection->query("SELECT COUNT(id) AS cnt FROM support_tickets WHERE status='PENDING'");
		$mailListRegisteredUsers = $connection->query("SELECT COUNT(email) as cnt FROM person WHERE mail_list=1");
		$alertsTotal = $connection->query("SELECT COUNT(id) as cnt FROM alerts;");
		$alertsFixed = $connection->query("SELECT COUNT(id) as cnt FROM alerts WHERE fixed = 1;");

		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];
		$settings = \Linfo\Common::getVarFromFile($wwwroot.'/config/linfo.inc.php', 'settings');

		// free space hdd
		$linfo = new \Linfo\Linfo($settings);
		$parser = $linfo->getParser();
		$hd = $parser->getMounts();

		$hddFreeSpace = 0;
		foreach($hd as $mount)
		{
			if ($mount['mount'] == "/")
			{
				$hddFreeSpace = $mount['free_percent'];
				break;
			}
		}

		$hddFreeSpaceStatus = $hddFreeSpace < 10 ? "danger" : ($hddFreeSpace < 50 ? "warning" : "success");

		// get data for the Tasks widget
		$tasksWidget = $connection->query("SELECT task, DATEDIFF(CURRENT_DATE, executed) as days, delay, frequency FROM task_status");

		// send data to the view
		$this->view->title = "Dashboard";
		$this->view->numberActiveUsers = $numberActiveUsers[0]->cnt;
		$this->view->numberTotalUsers = $numberTotalUsers[0]->cnt;
		$this->view->numberUserProfiles = $numberUserProfiles[0]->cnt;
		$this->view->creditsOffered = $creditsOffered[0]->cnt;
		$this->view->queryRunningAds = $queryRunningAds[0]->cnt;
		$this->view->supportNewCount = $supportNewCount[0]->cnt;
		$this->view->supportPendingCount = $supportPendingCount[0]->cnt;
		$this->view->mailListRegisteredUsers = $mailListRegisteredUsers[0]->cnt;
		$this->view->tasksWidget = $tasksWidget;
		$this->view->alertsTotal = $alertsTotal[0]->cnt;
		$this->view->alertsFixed = $alertsFixed[0]->cnt;
		$this->view->hddFreeSpace = $hddFreeSpace;
		$this->view->hddFreeSpaceStatus = $hddFreeSpaceStatus;
	}
}
