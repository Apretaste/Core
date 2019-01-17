<?php
use \Phalcon\DI\FactoryDefault;

class Deploy{

	/**
	 * Extracts and deploys a new service to the service directory
	 *
	 * @param String $path, path to the zip file of the service
	 * @return array, results of the deploy [serviceName, creatorEmail]
	 */
	public function deployServiceFromZip($pathToZip, $zipName)
	{
		// extract file to the temp folder
		$pathToService = $this->extractServiceZip($pathToZip);
		$configFile = "$pathToService/config.json";

		// get the service config from the JSON
		$service = json_decode(file_get_contents($configFile));

		// remove the current project if it exist
		$updating = false;
		if (Utils::serviceExist($service->name)){
			$this->removeService($service);
			$updating = true;
		}

		// add the new service
		$this->addService($service, $pathToZip, $pathToService, $updating);

		// remove temp service folder
		@system("rmdir ". escapeshellarg($dir) . " /s /q"); // windows version
		@system("rm -rfv " . escapeshellarg($pathToService)); // linux version

		// return deploy results
		return array(
			"serviceName"=>$service->name,
			"creatorEmail"=>$service->creator
		);
	}

	public static function updateServiceInfo($servicePath){
		$configFile = "$servicePath/config.json";
		$service = json_decode(file_get_contents($configFile));

		$service->listed = intval($service->listed);

		// save the new service in the database
		Connection::query("INSERT INTO service (name,description,creator_email,category,version,listed)
		VALUES ('{$service->name}','{$service->description}','{$service->creator}','{$service->category}','{$service->version}','{$service->listed}')
		ON DUPLICATE KEY UPDATE
		description='{$service->description}',creator_email='{$service->creator}',category='{$service->category}',
		version='{$service->version}',listed='{$service->listed}'");
	}

	/**
	 * Extract the service zip and return the path to it
	 *
	 * @author salvipascual
	 * @param String, path of the zipped folder
	 * @return String, path to the folder unzipped
	 * @throws Exception
	 */
	public function extractServiceZip($pathToZip){
		// get the path
		$di = FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];

		$zip = new ZipArchive ();
		if ($zip->open($pathToZip) === TRUE)
		{
			// unzip service to the temp folder
			$pathToService = "$wwwroot/temp/" . md5($pathToZip);
			$zip->extractTo($pathToService);
			$zip->close();

			// add all permissions
			chmod($pathToService, 0777);

			return $pathToService;
		}
		else
		{
			throw new Exception ("Cannot read zip file");
		}
	}

	/**
	 * Remove a service from the filesystem and database
	 *
	 * @author salvipascual
	 * @param Service
	 * */
	public function removeService($service){
		// get the path
		$wwwroot = FactoryDefault::getDefault()->get('path')['root'];

		// remove the service from the services table
		Connection::query("DELETE FROM service WHERE name='{$service->name}'");

		// remove the service folder
		$dir = "$wwwroot/services/{$service->name}";
		if (file_exists($dir)){
			@system("rmdir ". escapeshellarg($dir) . " /s /q"); // windows version
			@system("rm -rfv " . escapeshellarg($dir)); // linux version
		}
	}

	/**
	 * Add a new service to the filesystem, database and create the specific service tables
	 *
	 * @author salvipascual
	 * @author kuma
	 * @param Service
	 * @param String , the path to the location of the zip
	 * @param String , the path to the location of the files
	 * @paran Boolean , if service are updating
	 * */
	public function addService($service, $pathToZip, $pathToService, $updating = false){
		// get the path
		$wwwroot = FactoryDefault::getDefault()->get('path')['root'];
		$service->listed = intval($service->listed);

		// save the new service in the database
		Connection::query("INSERT INTO service (name,description,creator_email,category,version,listed)
		VALUES ('{$service->name}','{$service->description}','{$service->creator}','{$service->category}','{$service->version}','{$service->listed}')");

		// copy files to the service folder and remove temp files
		rename($pathToService, "$wwwroot/services/{$service->name}");
		unlink($pathToZip);
	}
}
