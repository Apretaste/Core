<?php

class Deploy
{
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
		$utils = new Utils();
		if ($utils->serviceExist($service['serviceName']))
		{
			// clean database and files if the service existed before
			$this->removeService($service);
		}

		// create a new deploy key
		$utils = new Utils();

		// add the new service
		$this->addService($service, $pathToZip, $pathToService);

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

		// create a new connection
		$connection = new Connection();

		// remove the service from the services table
		$connection->deepQuery("DELETE FROM service WHERE name='{$service['serviceName']}'");

		// clean service-specific tables
		foreach ($service['database'] as $table)
		{
			$connection->deepQuery("DROP TABLE IF EXISTS __{$service['serviceName']}_{$table['name']};");
		}

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
	 * @param Service
	 * @param String , the path to the location of the zip
	 * @param String , the path to the location of the files
	 * */
	public function addService($service, $pathToZip, $pathToService)
	{
		// get the path
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];

		// create a new connection
		$connection = new Connection();

		// save the new service in the database
		$insertUserQuery = "INSERT INTO service (name,description,usage_text,creator_email,category) VALUES ('{$service['serviceName']}','{$service['serviceDescription']}','{$service['serviceUsage']}','{$service['creatorEmail']}','{$service['serviceCategory']}')";
		$connection->deepQuery($insertUserQuery);

		// copy files to the service folder and remove temp files
		rename($pathToService, "$wwwroot/services/{$service['serviceName']}");
		unlink($pathToZip);

		// create the service specific tables
		$query = "";
		foreach ($service['database'] as $table)
		{
			$tname = "__{$service['serviceName']}_{$table['name']}";
			$query = "CREATE TABLE $tname (";
			foreach ($table['columns'] as $column)
			{
				$length = empty($column['length']) ? "" : "({$column['length']})";
				$query .= "{$column['name']} {$column['type']} $length,";
			}

			$query = rtrim($query, ",") . ");";
			file_put_contents("query.log", $query);
			$connection->deepQuery($query);
		}
	}

	/**
	 * Load all data from the XML and return an array with the information
	 *
	 * @author salvipascual
	 * @param String $pathToXML, path to load the XML file
	 * @return array, xml data
	 * @throw Exception
	 * */
	public function loadFromXML($pathToXML)
	{
		// check if the XML file exists
		if ( ! file_exists($pathToXML)) throw new Exception ("Cannot read configs.xml file");

		// get a php object from the XML
		$xml = simplexml_load_file($pathToXML);
		$XMLData = array();

		// get the tables if they exist
		if (isset($xml->database))
		{
			$tables = array();
			foreach ($xml->database->table as $table)
			{
				$newtable = array("name"=>trim((String)$table->attributes()->name), "columns"=>NULL);
				$columns = array();

				foreach ($table->column as $column)
				{
					$columns[] = array(
						"name"=>trim((String)$column),
						"type"=>trim((String)$column->attributes()->type), 
						"length"=>trim((String)$column->attributes()->length)
					);
					$newtable['columns'] = $columns;
				}
				$tables[] = $newtable;
			}
			$XMLData['database'] = $tables;
		}
		else
		{
			$XMLData['database'] = array();
		}

		// get the main data of the service
		$XMLData['serviceName'] = strtolower(trim((String)$xml->serviceName));
		$XMLData['creatorEmail'] = trim((String)$xml->creatorEmail);
		$XMLData['serviceDescription'] = trim((String)$xml->serviceDescription);
		$XMLData['serviceUsage'] = trim((String)$xml->serviceUsage);
		$XMLData['serviceCategory'] = trim((String)$xml->serviceCategory);

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
