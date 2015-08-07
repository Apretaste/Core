<?php

use Phalcon\Mvc\Controller;

class DeployController extends Controller
{
	public function indexAction()
	{
		$this->view->setLayout('analytics');
		$this->view->deployingError = $this->request->get("e");
		$this->view->deployingMesssage = $this->request->get("m");
	}

	public function submitAction()
	{
		// check the file is a valid zip
		$fileNameArray = explode(".", $_FILES["service"]["name"]);
		$extensionIsZip = strtolower(end($fileNameArray)) == "zip";
		$isZipFile = true; //$_FILES["service"]["type"] == "application/octet-stream"; // @TODO check if it is a valid zip file
		if ( ! $isZipFile || ! $extensionIsZip)
		{
			return $this->response->redirect("deploy?e=The file is not a valid zip");
		}

		// check the service zip size is less than 1MB
		if ($_FILES["service"]["size"] > 1048576)
		{
			return $this->response->redirect("deploy?e=The file is too big. Our limit is 1 MB");
		}

		// check for errors
		if ($_FILES["service"]["error"] > 0)
		{
			return $this->response->redirect("deploy?e=Unknow errors uploading your service. Please try again");
		}

		// include and initialice Deploy class
		$deploy = new Deploy();

		// get the zip name and path
		$wwwroot = $this->di->get('path')['root'];
		$zipPath = "$wwwroot/temp/" . $deploy->generateDeployKey() . ".zip";
		$zipName = basename($zipPath);

		// save file
		if (isset($_FILES["service"]["name"])) $zipName = $_FILES["service"]["name"];
		move_uploaded_file($_FILES["service"]["tmp_name"], $zipPath);
		chmod($zipPath, 0777);
		
		// check if the file was moved correctly
		if ( ! file_exists($zipPath))
		{
			return $this->response->redirect("deploy?e=There was a problem uploading the file");
		}

		// get the deploy key
		$deployKey = $this->request->getPost("deploykey");

		// deploy the service
		try{
			$deployKey = $deploy->deployServiceFromZip($zipPath, $deployKey, $zipName);
		}catch (Exception $e){
			$error = preg_replace("/\r|\n/", "", $e->getMessage());
			return $this->response->redirect("deploy?e=$error");
		}

		// redirect to the upload page with success message 
		return $this->response->redirect("deploy?m=Service deployed successfully. Your new deploy key is $deployKey. Please copy your deploy key now and keep it secret. Without your deploy key you will not be able to update your Service later on");
	}
}
