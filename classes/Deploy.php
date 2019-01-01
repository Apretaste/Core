<?php

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
		$pathToXML = "$pathToService/config.xml";

		// get the service data from the XML
		$service = $this->loadFromXML($pathToXML);

		// remove the current project if it exist
		$updating = false;
		if (Utils::serviceExist($service['serviceName']))
		{
			$this->removeService($service);
			$updating = true;
		}

		// check if alias exists as a service name or alias
		foreach ($service['serviceAlias'] as $alias)
		{
			// check if the alias already exists as a service name
			$r = Connection::query("SELECT * FROM service WHERE name = '$alias'");
			if (is_array($r) && isset($r[0]) && isset($r[0]->name))
			{
				throw new Exception("<b>SERVICE NOT DEPLOYED</b>: Service alias '$alias' exists as a service");
			}

			// check if the alias is already defined
			$r = Connection::query("SELECT * FROM service_alias WHERE alias = '$alias' AND service <> '{$service['serviceName']}'");
			if (is_array($r) && isset($r[0]) && isset($r[0]->alias))
			{
				throw new Exception("<b>SERVICE NOT DEPLOYED</b>: Service alias '$alias' exists as an alias");
			}
		}

		// add the new service
		$this->addService($service, $pathToZip, $pathToService, $updating);

		// remove temp service folder
		@system("rmdir ". escapeshellarg($dir) . " /s /q"); // windows version
		@system("rm -rfv " . escapeshellarg($pathToService)); // linux version

		// return deploy results
		return array(
			"serviceName"=>$service["serviceName"],
			"creatorEmail"=>$service["creatorEmail"]
		);
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
		// get the path
		$di = \Phalcon\DI\FactoryDefault::getDefault();
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
	public function removeService($service)
	{
		// get the path
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];

		// remove the service from the services table
		Connection::query("DELETE FROM service WHERE name='{$service['serviceName']}'");

		// remove the service folder
		$dir = "$wwwroot/services/{$service['serviceName']}";
		if (file_exists($dir))
		{
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
	public function addService($service, $pathToZip, $pathToService, $updating = false)
	{

		// get the path
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];

		// save the new service in the database
		$insertUserQuery = "
			INSERT INTO service (name,description,creator_email,category,tpl_version,listed)
			VALUES ('{$service['serviceName']}','{$service['serviceDescription']}','{$service['creatorEmail']}','{$service['serviceCategory']}','{$service['tpl-version']}','{$service['listed']}')";
		Connection::query($insertUserQuery);

		// clear old alias
		$sqlClear = "DELETE FROM service_alias WHERE alias <> '";
		$sqlClear .= implode("' AND alias <> '", $service['serviceAlias']);
		$sqlClear .= "' AND service = '{$service['serviceName']}' ;";
		Connection::query($sqlClear);

		// insert new alias
		foreach ($service['serviceAlias'] as $alias)
		{
			Connection::query("INSERT IGNORE INTO service_alias (service, alias) VALUES ('{$service['serviceName']}','$alias');");
		}

		// copy files to the service folder and remove temp files
		rename($pathToService, "$wwwroot/services/{$service['serviceName']}");
		unlink($pathToZip);
	}

	/**
	 * Load all data from the XML and return an array with the information
	 *
	 * @author salvipascual
	 * @param String $pathToXML, path to load the XML file
	 * @return array, xml data
	 * @throws Exception
	 **/
	public function loadFromXML($pathToXML)
	{
		// check if the XML file exists
		if ( ! file_exists($pathToXML)) throw new Exception ("Cannot read configs.xml file");

		// get a php object from the XML
		$xml = simplexml_load_file($pathToXML);
		$XMLData = array();


		// get the main data of the service
		$XMLData['serviceName'] = strtolower(trim((String)$xml->serviceName));
		$XMLData['serviceAlias'] = isset($xml->serviceAliases) ? $xml->serviceAliases->alias: array();
		$XMLData['creatorEmail'] = trim((String)$xml->creatorEmail);
		$XMLData['serviceDescription'] = trim((String)$xml->serviceDescription);
		$XMLData['serviceUsage'] = trim((String)$xml->serviceUsage);
		$XMLData['serviceCategory'] = trim((String)$xml->serviceCategory);
		$XMLData['listed'] = isset($xml->listed) ? trim((String)$xml->listed) : 1;
		$XMLData['showAds'] = isset($xml->showAds) ? trim((String)$xml->showAds) : 1;
		$XMLData['group'] = isset($xml->group) ? trim((String)$xml->group) : "apretaste";

		// clear alias names
		$newarr = array();
		foreach ($XMLData['serviceAlias'] as $alias)
		{
			$alias = Utils::clearStr((String) $alias);

			if ($alias !== '')
				$newarr[] = $alias;
		}

		$XMLData['serviceAlias'] = $newarr;

		// check if the email is valid
		if ( ! filter_var($XMLData['creatorEmail'], FILTER_VALIDATE_EMAIL))
		{
			throw new Exception ("The email {$XMLData['creatorEmail']} is not valid.");
		}

		// check if the category is valid
		$categories = array('negocios','ocio','academico','social','comunicaciones','informativo','adulto','otros');
		if( ! in_array($XMLData['serviceCategory'], $categories))
		{
			throw new Exception ("Category {$XMLData['serviceCategory']} is not valid. Categories are: " . implode(", ", $categories));
		}

		return $XMLData;
	}
}
