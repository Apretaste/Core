<?php

use Phalcon\Mvc\Controller;

class ManageController extends Controller
{
	// do not let anonymous users pass
	public function initialize()
	{
		$security = new Security();
		$security->enforceLogin();
	}

	/**
	 * Index for the manage system
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
	}

	/**
	 * Deploy a new service or update an old one
	 */
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

			// deploy the service
			try
			{
				$deployResults = $deploy->deployServiceFromZip($zipPath, $zipName);
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
			$email = new Email();
			$email->sendEmail($creatorEmail, "Your service $serviceName was deployed", "<h1>Service deployed</h1><p>Your service $serviceName was deployed on $today.</p>");

			// redirect to the upload page with success message
			$this->view->deployingMesssage = "Service <b>$serviceName</b> deployed successfully.";
		}
	}

	/**
	 * Show the error log
	 */
	public function errorsAction()
	{
		// get the error logs file
		$wwwroot = $this->di->get('path')['root'];
		$logfile = "$wwwroot/logs/error.log";

		// tail the log file
		$numlines = "50";
		$cmd = "tail -$numlines '$logfile'";
		$errors = explode('<br />', nl2br(shell_exec($cmd)));

		// format output to look better
		$output = array();
		foreach ($errors as $err)
		{
			if(strlen($err) < 5) continue;
			$line = htmlentities($err);
			$line = "<b>".substr_replace($line,"]</b>",strpos($line, "]"),1);
			$output[] = $line;
		}

		// reverse to show latest first
		$output = array_reverse($output);

		$this->view->title = "Lastest $numlines errors";
		$this->view->output = $output;
	}
}
