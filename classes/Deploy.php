<?php

class Deploy
{
	public $utils = null;

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
		$utils = $this->getUtils();
		$connection = new Connection();

		// remove the current project if it exist
		$updating = false;
		if ($utils->serviceExist($service['serviceName']))
		{
			$this->removeService($service);
			$updating = true;
		}

		// check if alias exists as a service name or alias
		foreach ($service['serviceAlias'] as $alias)
		{
			// check if the alias already exists as a service name
			$r = $connection->deepQuery("SELECT * FROM service WHERE name = '$alias'");
			if (is_array($r) && isset($r[0]) && isset($r[0]->name))
			{
				throw new Exception("<b>SERVICE NOT DEPLOYED</b>: Service alias '$alias' exists as a service");
			}

			// check if the alias is already defined
			$r = $connection->deepQuery("SELECT * FROM service_alias WHERE alias = '$alias' AND service <> '{$service['serviceName']}'");
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
	 * Get Utils member (singleton)
	 *
	 * @author kuma
	 * @return Utils
	 */
	public function getUtils()
	{
		if (is_null($this->utils)) $this->utils = new Utils();
		return $this->utils;
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
		$utils = $this->getUtils();

		// get the path
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];

		// save the new service in the database
		$connection = new Connection();
		$insertUserQuery = "
			INSERT INTO service (name,description,usage_text,creator_email,category,`group`,listed,ads)
			VALUES ('{$service['serviceName']}','{$service['serviceDescription']}','{$service['serviceUsage']}','{$service['creatorEmail']}','{$service['serviceCategory']}','{$service['group']}','{$service['listed']}','{$service['showAds']}')";
		$connection->deepQuery($insertUserQuery);

		// clear old alias
		$sqlClear = "DELETE FROM service_alias WHERE alias <> '";
		$sqlClear .= implode("' AND alias <> '", $service['serviceAlias']);
		$sqlClear .= "' AND service = '{$service['serviceName']}' ;";
		$connection->deepQuery($sqlClear);

		// insert new alias
		foreach ($service['serviceAlias'] as $alias)
		{
			$connection->deepQuery("INSERT IGNORE INTO service_alias (service, alias) VALUES ('{$service['serviceName']}','$alias');");
		}

		// clear old ads
		$connection->deepQuery("DELETE FROM ads WHERE related_service = '{$service['serviceName']}';");

		// create the owner of ad
		$sql = "INSERT IGNORE INTO person (email, username, credit) VALUES ('soporte@apretaste.com', 'soporteap', 1000000);";
		$sql .= "UPDATE person SET credit = 1000000 WHERE email = 'soporte@apretaste.com';";

		$connection->deepQuery($sql);
		$serviceName = strtoupper($service['serviceName']);
		$serviceDesc = $connection->escape($service['serviceDescription']);
		$toaddress = $utils->getValidEmailAddress();

		// create an Ad for new service
		$body =  "<p>Hola,<br/><br/>Nos alegra decir que tenemos un servicio nuevo en Apretatse. El servicio es $serviceName y $serviceDesc. ";
		$body .= "Espero que le sea de su agrado, y si quiere saber mas al respecto, el enlace a continuacion le explicar&aacute; como se usa y detallar&aacute; m&aacute;s sobre el mismo.";
		$body .= '<center><a href="mailto:'.$toaddress.'?subject=AYUDA '.$serviceName.'">Conocer m&aacute;s sobre este servicio</a></center>';
		$body .= "<br/><br/>Gracias por usar Apretaste.<p>";

		if ($updating) {
			$body =  "<p>Hola,<br/><br/>Tenemos una actualizaci&oacute;n al servicio $serviceName en Apretaste!";
			$body .= "Con las actualizaciones vienen mejoras, nuevas funciones y soluciones a problemas antiguos. Espero que le sea de su agrado, y si quiere saber mas al respecto, el enlace a continuacion le explicar&aacute; como se usa y detallar&aacute; m&aacute;s sobre el mismo.";
			$body .= '<center><a href="mailto:'.$toaddress.'?subject=AYUDA '.$serviceName.'">Conocer m&aacute;s sobre este servicio</a></center>';
			$body .= "<br/><br/>Gracias por usar Apretaste.<p>";
		}

		$title = 'Presentando el servicio '.$serviceName.' a nuestros usuarios de Apretaste';

		if ($updating)
			$title = 'Buenas noticias! Hemos realizado mejoras al servicio '.$serviceName;

		$sql = "INSERT INTO ads (title,description,owner,expiration_date,related_service)
			    VALUES ('$title', '$body','soporte@apretaste.com', DATE_ADD(CURRENT_DATE, INTERVAL 1 WEEK), '{$service['serviceName']}');";

		$connection->deepQuery($sql);

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

		$utils = $this->getUtils();

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
			$alias = $utils->clearStr((String) $alias);

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
