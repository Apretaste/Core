<?php

/**
 * Email Extractor task
 * 
 * @author kuma
 * @version 1.0
 */
class ExtractorTask extends \Phalcon\Cli\Task
{
	private $showLog = true;

	/**
	 * Main action
	 */
	public function mainAction()
	{
		// get path to the www folder
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];

		// inicialize supporting classes
		$timeStart = microtime(true);
		$addressesCount = 0;
		$tempFolder = "$wwwroot/temp/email_extractor";
		$logFile = "$tempFolder/extractor.log";
		$listFile = "$tempFolder/files.list";
		$memoryLimit = str_replace(array(
			'M',
			'K',
			'B'
		), '', ini_get('memory_limit') . '') * 1;
		$db = new Connection();
		
		$this->log("Starting email extractor...");
		$this->log("Temp folder: $tempFolder");
		$this->log("Log filer: $logFile");
		$this->log("List of files: $listFile");
		
		// preparing temporal folder
		if ( ! file_exists($tempFolder))
		{
			mkdir($tempFolder);
		}
		
		// list of sites
		$sites = array(
			'porlalivre' => 'http://porlalivre.com'
		);
		
		// proccess each site
		foreach ($sites as $prefix => $site)
		{
			$this->log("Starting mirror of $site, saving in $tempFolder/$prefix", 'WGET');
			
			// change dir to temp folder
			chdir($tempFolder);
			
			// create a mirror of the site (without page resources)
			shell_exec("wget --no-check-certificate -P $prefix -o $logFile -mk -A .html,.htm,.php,.jsf,.jsp,.aspx $site");
			
			// return to www root
			chdir($wwwroot);
			
			// get duration of wget
			$this->log("Finish " . (microtime(true) - $timeStart) . "secs", 'WGET');
			
			if (file_exists("$tempFolder/$prefix"))
			{
				
				// remove the last list
				if (file_exists($listFile))
				{
					unlink($listFile);
				}
				
				$this->log("Creating list of files that will be proccessed...");
				
				// change dir to downloaded folder
				chdir("$tempFolder/$prefix");
				
				// create list of downloaded files
				if (strncasecmp(PHP_OS, 'WIN', 3) == 0)
				{
					// for local develop
					shell_exec("dir /s /b /aa > $listFile");
				}
				else
				{
					// for production
					shell_exec("find . -type f > $listFile");
				}
				
				// return to www root
				chdir($wwwroot);
				
				// computing total of files
				$total = 0;
				$f = fopen($listFile, "r");
				
				while ( ! feof($f))
				{
					$filename = trim(fgets($f));
					$total ++;
				}
				fclose($f);
				
				$this->log("Proccessing $total files");
				
				// proccessing the mirror
				$i = 0;
				$f = fopen($listFile, "r");
				$lastPercent = 0;
				
				while ( ! feof($f))
				{
					$filename = trim(fgets($f));
					
					$i ++;
					
					// computing progress
					$percent = number_format($i / $total * 100, 0) * 1;
					if ($percent > $lastPercent)
					{
						$this->log("Proccessed $i / $total = $percent % ");
						$lastPercent = $percent;
					}
					
					if ($filename != '')
					{
						$fileFullName = "$tempFolder/$prefix/$filename";
						
						// checking if filename is a full path (differents results from Windows/dir and Linux/find)
						if ( ! file_exists($fileFullName) && file_exists($filename))
						{
							$fileFullName = $filename;
						}
						
						// checking if file exists
						if ( ! file_exists($fileFullName))
						{
							continue;
						}

						// checking file size
						$fileSize = filesize($fileFullName) / 1024 / 1024;
						
						if ($fileSize > $memoryLimit && $memoryLimit > 0)
						{
							$this->log("Ingoring big file: $fileFullName ({$fileSize}M)");
							continue;
						}

						$f2 = fopen($fileFullName, "r");

						while ( ! feof($f2))
						{
							$content = fgets($f2);

							$addresses = $this->getAddressFrom($content);
							$addressesCount += count($addresses); 

							foreach ($addresses as $a)
							{
								$exists = $db->deepQuery("SELECT * FROM person WHERE email = '$a';");

								if ($exists === false || empty($exists) || ! isset($exists[0]))
								{
									$db->deepQuery("INSERT IGNORE INTO autoinvitations (email,source) VALUES ('$a','PORLALIVRE');");
								}
							}
						}
						
						fclose($f2);
					}
				}
				fclose($f);
			}
		}

		// save the status in the database
		$timeDiff = time() - $timeStart;
		
		$db->deepQuery("UPDATE task_status SET executed=CURRENT_TIMESTAMP, delay='$timeDiff', `values`='$addressesCount' WHERE task='extractor'");
	}

	/**
	 * Show log messages
	 *
	 * @param string $message
	 * @param string $icon
	 */
	private function log($message, $icon = 'INFO')
	{
		if ($this->showLog === true)
		{
			echo "[$icon] " . date("Y-m-d h:i:s") . " $message\n";
		}
	}

	/**
	 * Check if a string is an email address
	 *
	 * @author kuma
	 * @version 1.0
	 * @param string $email        	
	 * @return boolean
	 */
	private function checkAddress($email)
	{
		$regex = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/';
		
		if (preg_match($regex, $email))
			return true;
		
		return false;
	}

	/*
	 * Extract email addresses from the text
	 *
	 * @author kuma
	 * @version 1.0
	 * @param string $text
	 * @return array
	 */
	private function getAddressFrom($text)
	{
		$chars = '1234567890abcdefghijklmnopqrstuvwxyz._-@ ';
		$text = strtolower($text);
		
		// Cleanning the text
		for ($i = 0; $i < 256; $i ++)
		{
			if (stripos($chars, chr($i)) === false)
			{
				$text = str_replace(chr($i), ' ', $text);
			}
		}
		
		$text = trim(str_replace(array(
			". ",
			" .",
			"- ",
			"_ "
		), " ", " $text "));
		
		// extract all phrases from text
		$words = explode(' ', $text);
		
		// checking each phrase
		$addresses = array();
		foreach ($words as $w)
		{
			if (trim($w) === '')
				continue;
		
			if ($this->checkAddress($w) === true && strpos($w, '@') !== false)
				$addresses[] = $w;
		}
		
		return $addresses;
	}
}