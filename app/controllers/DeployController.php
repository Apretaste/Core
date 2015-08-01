<?php

use Phalcon\Mvc\Controller;

class DeployController extends Controller
{
	public function indexAction()
	{
		$this->view->setLayout('analytics');
		$this->view->deployingError = $this->dispatcher->getParam(0);
	}

	public function submitAction()
	{
		// check the file is a valid zip
		$fileNameArray = explode(".", $_FILES["service"]["name"]);
		$extensionIsZip = strtolower(end($fileNameArray)) == "zip";
		$isZipFile = true; //$_FILES["service"]["type"] == "application/octet-stream"; // @TODO check if it is a valid zip file
		if ( ! $isZipFile || ! $extensionIsZip)
		{
			return $this->dispatcher->forward(array(
				"action" => "index",
				"params" => array("The file is not a valid zip")
			));
		}

		// check the service zip size is less than 1MB
		if ($_FILES["service"]["size"] > 1048576)
		{
			return $this->dispatcher->forward(array(
				"action" => "index",
				"params" => array("The file is too big. Our limit is 1 MB")
			));
		}

		// check for errors
		if ($_FILES["service"]["error"] > 0)
		{
			return $this->dispatcher->forward(array(
				"action" => "index",
				"params" => array("Unknow errors uploading your service. Please try again")
			));
		}

		// include and initialice Deploy class
		$deploy = new Deploy();

		// get the zip name and path
		$wwwroot = $this->di->get('path')['root'];
		$zipPath = "$wwwroot/temp/" . $deploy->generateDeployKey() . ".zip";
		$zipName = basename($zipPath);
die("$zipPath, $zipName");
		// save file
		if (isset($_FILES["service"]["name"])) $zipName = $_FILES["service"]["name"];
		move_uploaded_file($_FILES["service"]["tmp_name"], $zipPath);
		if ( ! file_exists($zipPath))
		{
			return $this->dispatcher->forward(array(
				"action" => "index",
				"params" => array("There was a problem uploading the file")
			));
		}

		// get the deploy key
		$deployKey = $_POST['deploykey'];

		die("$zipPath, $deployKey, $zipName");
		
		// deploy the service
		//
		// @TODO uncomment error message once the deploy is tested and error-free
		//
		//try{
		$deployKey = $deploy->deployServiceFromZip($zipPath, $deployKey, $zipName);
		/*
		}catch (Exception $e){
			$error = '&error=' . preg_replace("/\r|\n/", "", $e->getMessage());
			header("Location: {$framework->createLink('admin', 'deploy', $error)}");
			exit;
		}
		*/

		// redirect to the upload page with success message 
		return $this->dispatcher->forward(array(
				"action" => "index",
				"params" => array("Service deployed successfully. Your new deploy key is $deployKey. Please copy your deploy key now and keep it secret. Without your deploy key you will not be able to update your Service later on")
		));
	}
}
