<?php

use Phalcon\Mvc\Controller;

class DeveloperController extends Controller
{
	// do not let anonymous users pass
	public function initialize()
	{
		$security = new Security();
		$security->enforceLogin();
		$this->view->setLayout("manage");
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

	/**
	 * Show the mysql error log
	 */
	public function mysqlAction()
	{
		// get the error logs file
		$wwwroot = $this->di->get('path')['root'];
		$logfile = "$wwwroot/logs/badqueries.log";

		// tail the log file
		$numlines = "51";
		$cmd = "tail -$numlines '$logfile'";
		$errors = preg_split('/^\[/m', shell_exec($cmd));

		// format output to look better
		$output = array();
		foreach ($errors as $err)
		{
			if(strlen($err) < 5) continue;
			$line = htmlentities($err);
			$line = nl2br($line);
			$line = "<b>[".substr_replace($line,"]</b>",strpos($line, "]"),1);
			$output[] = $line;
		}

		// reverse to show latest first & remove incomplete
		$output = array_reverse($output);
		array_pop($output);

		$this->view->title = "Lastest 50 mysql errors";
		$this->view->output = $output;
		$this->view->pick(['developer/errors']);
	}

	public function alertsAction()
	{
		$default_query = "SELECT * FROM alerts WHERE fixed = 0 AND datediff(created, CURRENT_DATE) <=7 ORDER BY created DESC;";
		$sql = $default_query;
		$query = '';
		if ($this->request->isPost())
		{
			$query = $this->request->getPost('filter');
			if (!is_null($query))
				$sql = "SELECT * FROM alerts WHERE fixed = 0 AND (text LIKE '%{$query}%' OR type = '$query') ORDER BY created DESC LIMIT 30 ;";

			$fixes = $this->request->getPost('fixed');
			if (!is_null($fixes))
				foreach ($fixes as $fixed)
					Connection::query("UPDATE alerts SET fixed = 1, fixed_date = CURRENT_TIMESTAMP WHERE id = '$fixed';");

		}

		$alerts = Connection::query($sql);
		$this->view->no_results = false;
		if (count($alerts) == 0)
		{
			$alerts = Connection::query($default_query);
			$this->view->no_results = true;
		}

		$total = Connection::query("SELECT count(*) as total FROM alerts;");
		$total = $total[0]->total;

		$this->view->total = $total;
		$this->view->fixed = $fixed;
		$this->view->percentage = $total;
		$this->view->query = $query;
		$this->view->title = "Alerts ($fixed / $total)";
		$this->view->alerts = $alerts;
	}

}
