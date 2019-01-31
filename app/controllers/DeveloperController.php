<?php

use Phalcon\Mvc\Controller;
use Phalcon\DI\FactoryDefault;

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
		$logfile = "/var/log/apache2/error.log";

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
		$default_query = "SELECT * FROM alerts WHERE fixed = 0 ORDER BY created DESC;";
		$sql = $default_query;
		$query = '';
		if ($this->request->isPost())
		{
			$query = $this->request->getPost('filter');
			$querySQL = Connection::escape($query);
			if (!is_null($query))
				$sql = "SELECT * FROM alerts WHERE fixed = 0 AND (text LIKE '%{$querySQL}%' OR type = '$querySQL') ORDER BY created DESC;";

			$fixes = $this->request->getPost('fixed');
			if (!is_null($fixes))
				foreach ($fixes as $fixed)
					Connection::query("UPDATE alerts SET fixed = 1, fixed_date = CURRENT_TIMESTAMP WHERE id = '$fixed';");

		}

		// alerts
		// TODO: refactor to model
		$config = $this->di->get('config')['database_dev'];
		$db = new mysqli($config['host'], $config['user'], $config['password'], $config['database']);
		$result = $db->query($sql);
		$alerts = [];
		while ($data = $result->fetch_object()) $alerts[] = $data;

		$this->view->no_results = false;
		if (count($alerts) == 0)
		{
			$alerts = Connection::query($default_query);
			$this->view->no_results = true;
		}

		$total = [$db->query("SELECT count(*) as total FROM alerts WHERE fixed =0;")->fetch_object()];
		$total = $total[0]->total;

		foreach($alerts as $alert) $alert->text = str_replace("|","<br/>", nl2br($alert->text));
		$this->view->total = $total;
		$this->view->query = $query;
		$this->view->title = "Alerts ($total)";
		$this->view->alerts = $alerts;
		$this->view->buttons = [["caption"=>"Fix all checked", "href"=>"#", "onclick"=>"$('#formAlerts').submit();"]];
	}

	/**
	* List of services
	*/
	public function servicesAction(){
		$action = $this->request->get("action");

		if(empty($action)){
			$services = Connection::query("SELECT * FROM service");
			$this->view->title = "List of services (".count($services).")";
			$this->view->services = $services;
		}
		else if($action=="create"){
			$this->view->disable();
			$service = $this->request->get("service");
			if(empty($service)) return "No service specified";

			$wwwroot = FactoryDefault::getDefault()->get('path')['root'];
			$servicesDir = "$wwwroot/services";

			if(file_exists("$servicesDir/$service/service.php")) return "Service exists in storage";
			
			$result = shell_exec("cd $servicesDir && git clone https://github.com/Apretaste/$service.git 2>&1");
			Deploy::updateServiceInfo("$servicesDir/$service");
			return $result;
		}
		else{
			$this->view->disable();
			$service = $this->request->get("service");
			$wwwroot = FactoryDefault::getDefault()->get('path')['root'];
			$servicePath = "$wwwroot/services/$service";
			if(!file_exists("$servicePath/service.php")) return "Service not found in storage";

			if($action=="update"){
				$result = shell_exec("cd $servicePath && git pull 2>&1");
				
				Deploy::updateServiceInfo($servicePath);
				return $result;
			}
			else if($action=="reset"){
				$commit = $this->request->get("commit");
				if(empty($commit)) $commit = "origin/master";
				$result = shell_exec("cd $servicePath && git reset --hard $commit 2>&1");

				Deploy::updateServiceInfo($servicePath);
				return $result;
			}
			else if($action=="checkout"){
				$branch = $this->request->get("branch");
				if(empty($branch)) $branch = "master";
				$result = shell_exec("cd $servicePath && git checkout $branch 2>&1");

				Deploy::updateServiceInfo($servicePath);
				return $result;
			}
		}
	}
}
