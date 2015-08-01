<?php

include_once "$wwwroot/core/Connection.php";
include_once "$wwwroot/core/Servicer.php";
include_once "$wwwroot/classes/Service.php";

class Deploy
{
	private $db;
	private $servicer;

	/**
	 * Connects to the database when the class is created
	 */
	public function __construct()
	{
		// connects to the database Apretaste
		$this->db = new Connection ();
		$this->db->connectTo('apretaste');

		// create a new servicer
		$this->servicer = new Servicer ();
	}

	/**
	 * Extracts and deploys a new service to the service directory
	 *
	 * @param String $path, path to the zip file of the service
	 * @param String $deployKey, hash to avoid unauthorize updating of the service
	 * @return String, new deploy key
	 */
	public function deployServiceFromZip($pathToZip, $deployKey, $zipName)
	{
		// extract file to the temp folder
		$pathToService = $this->extractServiceZip($pathToZip);

		$file_name = str_replace(".zip", "", basename($zipName));

		if (!file_exists("$pathToService/config.xml") && file_exists("$pathToService/$file_name/config.xml"))
			$pathToService = "$pathToService/$file_name";

		$pathToXML = "$pathToService/config.xml";

		// check if no prohibed tags were used on the service PHP code
		if (!$this->checkForProhibedCode($pathToService))
			throw new Exception ("Insecure code was found. Please refer to the coding directions");

		// get the service data from the XML
		$service = new Service ();
		$service->loadFromXML($pathToXML);

		// remove the current project if it exist
		if ($this->servicer->serviceExist($service->name)) {
			// check if the deploy key is valid
			if (!$this->checkDeployValidity($service->name, $deployKey))
				throw new Exception ("Deploy key is invalid");

			// clean database and files if the service existed before
			$this->servicer->removeService($service);
		}

		// create a new deploy key
		$deployKey = $this->generateDeployKey();

		// add the new service
		$this->servicer->addService($service, $deployKey, $pathToZip, $pathToService);

		// remove temp service folder
		system("rm -rfv " . escapeshellarg($pathToService)); // linux version TODO change to PHP version

		return $deployKey;
	}

	/**
	 * Check that the app do not have dangeros PHP code
	 *
	 * @author salvipascual
	 * @param String $pathToService, path to the service to check
	 * @return Boolean true if the app is clean
	 */
	public function checkForProhibedCode($pathToService)
	{
		// TODO
		return true;
	}

	/**
	 * Generate a new deploy key
	 *
	 * @author salvipascual
	 * @return String
	 */
	public function generateDeployKey()
	{
		$rand = rand(0, 1000000);
		$today = date('full');
		return md5($rand . $today);
	}

	/**
	 * Extract the service zip and return the path to it
	 *
	 * @author salvipascual
	 * @param String, path of the zipped folder
	 * @return String, path to the folder unzipped
	 * @throws Exception
	 */
	public function extractServiceZip($pathToZip)
	{
		global $wwwroot;

		$zip = new ZipArchive ();

		if ($zip->open($pathToZip) === TRUE) {
			// unzip service to the temp folder
			$pathToService = "$wwwroot/temp/" . md5($pathToZip);
			$zip->extractTo($pathToService);
			$zip->close();

			// add all permissions
			chmod($pathToService, 0777);

			return $pathToService;
		} else {
			throw new Exception ("Cannot read zip file");
		}
	}

	/**
	 * When a service was already submitted, check the deploy key to prevent
	 * an unauthorized user from deploy it again.
	 * Do nothing if the service was not deployed before
	 *
	 * @param String $deployKey, hash to avoid unauthorize updating of the service
	 * @return Boolean, true if the service can be reloaded
	 */
	public function checkDeployValidity($serviceName, $deployKey)
	{
		// check if the plugin exist in the database and the deploy key is correct
		$res = $this->db->query("SELECT * FROM service WHERE name='$serviceName' AND deploy_key='$deployKey'");
		return count($res) > 0;
	}

	/**
	 * Check if the service exists in the database
	 *
	 * @author salvipascual
	 * @param String , name of the service
	 * @return Boolean, true if service exist
	 * */
	public function serviceExist($serviceName)
	{
		$res = $this->db->query("SELECT * FROM service WHERE LOWER(name)=LOWER('$serviceName')");
		return count($res) > 0;
	}
	
	/**
	 * Remove a service from the filesystem and database
	 *
	 * @author salvipascual
	 * @param Service
	 * */
	public function removeService($service)
	{
		global $wwwroot;
	
		// remove all service tables
		$this->db->query("DELETE FROM service WHERE name='{$service->name}'");
		$this->db->query("DELETE FROM subservice WHERE service='{$service->name}'");
	
		// clean app-specific tables
		foreach ($service->tables as $table)
			$this->db->query("DROP TABLE IF EXISTS services.{$service->name}_{$table->name};");
	
		// remove the service folder
		$dir = "$wwwroot/services/{$service->name}";
		if (file_exists($dir)) {
			//			system("rmdir ". escapeshellarg($dir) . " /s /q"); // TODO change to PHP version
			system("rm -rfv " . escapeshellarg($dir)); // linux version
		}
	}
	
	/**
	 * Add a new service to the filesystem, database and create the specific service tables
	 *
	 * @author salvipascual
	 * @param Service
	 * @param String , the key to deploy the service
	 * @param String , the path to the location of the zip
	 * @param String , the path to the location of the files
	 * */
	public function addService($service, $deployKey, $pathToZip, $pathToService)
	{
		global $wwwroot;
	
		$user = new User($service->author->email, false);
		$user->setFullName($service->author->fullName);
		$user->setCompany($service->author->company);
		$user->setPhone($service->author->phone);
	
		// save new service in the database
		$this->db->query("INSERT INTO service VALUES ('{$service->name}', '{$service->author->email}', '$deployKey', '{$service->description}', '{$service->account}', '{$service->category_id}', '{$service->license}', '{$service->support->email}', CURRENT_TIMESTAMP)");
		$user->save();
		$this->db->query("INSERT INTO support SELECT '{$service->support->email}', '{$service->support->fullName}', '{$service->support->company}', '{$service->support->phone}' WHERE NOT EXISTS(SELECT * FROM support WHERE email = '{$service->support->email}');");
	
		// save all subservices into the database
		foreach ($service->subnames as $subname)
			$this->db->query("INSERT INTO subservice VALUES ('{$service->name}', '{$subname->caption}', '{$subname->className}', 0)");
	
		// copy files to the service folder and removing temp files
		rename($pathToService, "$wwwroot/services/{$service->name}");
		unlink($pathToZip);
	
		// create the service specific tables
		$query = "";
		foreach ($service->tables as $table) {
			$tname = "services.{$service->name}_{$table->name}";
	
			$query = "CREATE TABLE $tname(";
			foreach ($table->columns as $column) {
				$length = empty($column->length) ? "" : "({$column->length})";
	
				$default = '';
				if (isset($column->default))
					$default = 'default ' . $column->default;
	
				if ($column->type == "uuid") {
					$column->type = "varchar";
					$default = "default get_uuid()";
				}
	
				$query .= "{$column->name} {$column->type} $length $default,"; // TODO: think about $length,";
			}
	
			$query = rtrim($query, ",") . ");";
			$this->db->query($query);
		}
	}
}
