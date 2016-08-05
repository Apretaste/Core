<?php

use Goutte\Client;

/**
 * Revolico Crawler Task
 * 
 * @author kuma
 * @version 2.0
 */

class revolicoTask extends \Phalcon\Cli\Task
{

	private $revolicoURL = "http://lok.myvnc.com/";
	private $client;
	private $connection;
	public $utils;

	/**
	 * Main action 
	 */
	public function mainAction($revolicoMainUrls = null)
	{		
		$this->utils = new Utils();
		$this->client = new Client();
		$this->connection = new Connection();
				
		if (is_null($revolicoMainUrls))
		{
			// starting points for the crawler
			$revolicoMainUrls = array(
				$this->revolicoURL.'computadoras/',
				$this->revolicoURL.'compra-venta/',
				$this->revolicoURL.'servicios/',
				$this->revolicoURL.'autos/',
				$this->revolicoURL.'empleos/',
				$this->revolicoURL.'vivienda/'
			);
		}
		
		// starting time and message
		$timeCrawlerStart = time();
		$timeStart = microtime(true);
		echo "\n\nREVOLICO CRAWLER STARTED\n";
		
		// variable to store the total number of posts
		$totalPosts = 0;
		
		// for each main url
		foreach ($revolicoMainUrls as $url)
		{
			echo "CRAWLING $url\n";
			
			// get the list of pages that have not been inserted yet
			$pages = $this->getRevolicoPagesFromMainURL($url);
			$totalPages = count($pages);
			
			// calculate the total number of posts
			$totalPosts += count($pages);
			
			echo "PROCESSING URLs $url\n";
			
			// for every page
			for ($i = 0; $i < $totalPages; $i ++)
			{
				echo "SAVING PAGE $i/" . $totalPages . " {$pages[$i]} \n";
				
				// get the page's data and images
				try 
				{
					$data = $this->crawlRevolicoURL($pages[$i]);
				} 
				catch (Exception $e)
				{
					echo "[ERROR] Page {$pages[$i]} request error \n";
				}
				
				if ($dada !== false)
				{
					// save the data into the database
					$this->saveToDatabase($data);
				}
				else 
				{
					echo "[ERROR] Page {$pages[$i]} request error \n";
				}
				
				echo "\tMEMORY USED: " . $this->utils->getFriendlySize(memory_get_usage(true)) . "\n";
			}
		}

		// ending message, log and time
		$totalTime = (time() - $timeCrawlerStart) / 60; // time in minutes
		$totalMem = $this->utils->getFriendlySize(memory_get_usage(true));
		$message = "CRAWLER ENDED - EXECUTION TIME: $totalTime min - NEW POSTS: $totalPosts - TOTAL MEMORY USED: $totalMem";
		$this->saveCrawlerLog($message);
		echo "\n\n$message\n\n";

		// save the status in the database
		$timeDiff = time() - $timeStart;
		$this->connection->deepQuery("UPDATE task_status SET executed=CURRENT_TIMESTAMP, delay='$totalTime', `values`='$totalPosts' WHERE task='revolico'");
	}

	/**
	 * Proccess specific sections in revlico site
	 * 
	 * @param array $sectionNames
	 */
	public function sectionAction($sectionNames = array()){
		$revolicoMainUrls = array();
		
		foreach($sectionNames as $sn)
		{
			$revolicoMainUrls[] = $this->revolicoURL.$sn."/";
		}
		
		return $this->mainAction($revolicoMainUrls);
	}
	
	/*
	 * * * * * * * * * * * * * * * * * * * * *
	 * CORE FUNCTION, PLEASE
	 * HANDLE WITH EXTREME CARE
	 * * * * * * * * * * * * * * * * * * * * *
	 */
	private function getRevolicoPagesFromMainURL($url)
	{
		$links = array();
		
		try
		{
			$crawler = $this->client->request('GET', $url);
			
			// get the latest page count
			$lastPage = $crawler->filter('[title="Final"]')->attr('href');
			$pagesTotal = intval(preg_replace('/[^0-9]+/', '', $lastPage), 10);
			
			// get all valid links
			for ($n = 1; $n < $pagesTotal; $n ++)
			{
				echo "PAGE $n\n";
				
				// only crawl for today
				$site = file_get_contents($url . "pagina-$n.html");
				$exist = stripos($site, $this->utils->getTodaysDateSpanishString());
				if ( ! $exist) return $links;
				
				// move to the next page
				$crawler = $this->client->request('GET', $url . "pagina-$n.html");
				
				// get all results for that page
				$nodes = $crawler->filter('td a:not(.pwtip)');
				for ($i = 0; $i < count($nodes); $i ++)
				{
					$href = $nodes->eq($i)->attr('href');
					
					// delete double /
					$ru = $this->revolicoURL;
					if ($href[0] == "/" && $ru[count($ru)-1] == "/")
						$href = substr($href, 1);
					
					// get the url from the list
					$links[] = $ru. $href;
				}
			}
		} catch(Exception $e)
		{
			echo "[FATAL] Could not request the URL $url \n";
		}
		return $links;
	}

	/**
	 * Crawler
	 *
	 * @param unknown $url        	
	 */
	private function crawlRevolicoURL($url)
	{
		
		$timeStart = time();
		
		// create crawler
		try
		{
			$crawler = $this->client->request('GET', $url);
		} 
		catch (Exception $e)
		{
			return false;			
		}
		
		// get title
		$title = trim($crawler->filter('.headingText')->text());
		
		// get body
		$body = trim($crawler->filter('.showAdText')->text());
		
		// declare a whole bunch of empty variables
		$price = "";
		$currency = "";
		$date = "";
		$email = "";
		$owner = "";
		$phone = "";
		$cell = "";
		$province = "";
		
		// get email
		$email = $this->utils->getEmailFromText($title);
		if (empty($email)) $email = $this->utils->getEmailFromText($body);
		
		// get the phone number
		$phone = $this->utils->getPhoneFromText($title);
		if (empty($phone)) $phone = $this->utils->getPhoneFromText($body);
		
		// get the cell number
		$cell = $this->utils->getCellFromText($title);
		if (empty($cell)) $cell = $this->utils->getCellFromText($body);
		
		// get all code into lineBloks
		$nodes = $crawler->filter('#lineBlock');
		for ($i = 0; $i < count($nodes); $i ++)
		{
			// get the current node
			$node = $nodes->eq($i);

			// get the header and data of the block
			$header = trim($node->filter('.headingText2')->text());
			$data = trim($node->filter('.normalText')->text());

			// handle the data depending the header
			switch ($header)
			{
				case "Precio:":
					{
						$price = preg_replace("/[^0-9]/", "", $data);
						$currency = ! empty($price) ? "CUC" : "";
						break;
					}
				case "Fecha:":
					{
						$date = $this->utils->dateSpanishToMySQL($data);
						break;
					}
				case "Nombre:":
					{
						$owner = $data;
						break;
					}

				case "Teléfono:":
					{
						if (empty($phone)) $phone = $this->utils->getPhoneFromText($data);
						if (empty($cell)) $cell = $this->utils->getCellFromText($data);
						break;
					}
				case "Email:":
					{
						if (empty($email)) $email = $data;
						break;
					}
			}
		}
		
		// get the province
		if (empty($province) && ! empty($phone)) $province = $this->utils->getProvinceFromPhone($phone);
		
		// download images
		$pictures = $crawler->filter('.view img');
		for ($i = 0; $i < count($pictures); $i ++)
		{
			// get the current picture
			$picURl = "{$this->revolicoURL}{$pictures->eq($i)->attr('src')}";
			
			// get the path
			$path = dirname(__DIR__) . "/public/tienda/" . md5($url) . "_" . ($i + 1) . ".jpg";
			
			// save image in the temp folder
			$file = file_get_contents($picURl);
			$insert = file_put_contents($path, $file);
			
			if ( ! $insert)
			{
				// save error log
				$this->saveCrawlerLog(" Could not save image $path");
			}
		}
		
		$timeEnd = time();
		$timeDiff = $timeEnd - $timeStart;
		echo "\tCRAWL TIME: $timeDiff \n";
		
		// return all values
		return array(
			"date" => $date,
			"price" => $price,
			"currency" => $currency,
			"owner" => $owner,
			"phone" => $phone,
			"cell" => $cell,
			"email" => $email,
			"province" => $province,
			"title" => $title,
			"body" => $body,
			"images" => count($pictures),
			"category" => $this->classify("$title $body"),
			"url" => $url
		);
	}

	/**
	 * Save to database
	 *
	 * @param mixed $data        	
	 */
	private function saveToDatabase($data)
	{		
		$timeStart = time();
		
		// create the query to insert only if it is not repeated in the last month
		/*
		 * $sql = "
		 * INSERT INTO _tienda_post (
		 * contact_name,
		 * contact_email_1,
		 * contact_phone,
		 * contact_cellphone,
		 * location_province,
		 * ad_title,
		 * ad_body,
		 * category,
		 * number_of_pictures,
		 * price,
		 * currency,
		 * date_time_posted,
		 * source,
		 * source_url
		 * )
		 * SELECT
		 * '{$data['owner']}',
		 * '{$data['email']}',
		 * '{$data['phone']}',
		 * '{$data['cell']}',
		 * '{$data['province']}',
		 * '{$data['title']}',
		 * '{$data['body']}',
		 * '{$data['category']}',
		 * '{$data['images']}',
		 * '{$data['price']}',
		 * '{$data['currency']}',
		 * '{$data['date']}',
		 * 'revolico',
		 * '{$data['url']}'
		 * FROM _tienda_post
		 * WHERE (
		 * SELECT COUNT(id)
		 * FROM _tienda_post
		 * WHERE
		 * date_time_posted > (NOW() - INTERVAL 1 MONTH) AND
		 * levenshtein('{$data['title']}', ad_title) < 10
		 * ) = 0
		 * LIMIT 1";
		 */

		// clean the body and title of characters that may break the query
		$title = $this->connection->escape($data['title']);
		$body = $this->connection->escape($data['body']);
		
		$sql = "
		INSERT INTO _tienda_post (
		contact_name,
		contact_email_1,
		contact_phone,
		contact_cellphone,
		location_province,
		ad_title,
		ad_body,
		category,
		number_of_pictures,
		price,
		currency,
		date_time_posted,
		source,
		source_url
		) VALUES (
		'{$data['owner']}',
		'{$data['email']}',
		'{$data['phone']}',
		'{$data['cell']}',
		'{$data['province']}',
		'$title',
		'$body',
		'{$data['category']}',
		'{$data['images']}',
		'{$data['price']}',
		'{$data['currency']}',
		'{$data['date']}',
		'revolico',
		'{$data['url']}'
		)";

		// save into the database, log on error
		$this->connection->deepQuery($sql);
		$timeEnd = time();
		$timeDiff = $timeEnd - $timeStart;
		echo "\tDB TIME: $timeDiff\n";
	}

	/*
	 * * * * * * * * * * * * * * * * * * * * *
	 * SUPPORTING FUNCTIONS
	 * DO NOT TOUCH UNLESS YOU
	 * KNOW WHAT YOU ARE DOING!
	 * * * * * * * * * * * * * * * * * * * * *
	 */
	private function saveCrawlerLog($message)
	{
		$timestamp = date("Y-m-d H:i:s");
		$errorPath = dirname(__DIR__) . "/logs/crawler.log";
		file_put_contents($errorPath, "$timestamp - REVOLICO - $message\n", FILE_APPEND);
	}

	/**
	 * Get a category for the post
	 *
	 * @author kuma
	 * @param String, $text	title and body concatenated
	 * @return String category
	 */
	private function classify($text)
	{
		$map = array(
			'computers' => 'laptop,pc,computadora,kit,mouse,teclado,usb,flash,memoria,sd,ram,micro,tarjeta de video,tarjeta de sonido,motherboard,display,impresora',
			'cars' => 'carro,auto,moto,bicicleta',
			'electronics' => 'equipo,ventilador,acondicionado,aire,televisor,tv,radio,musica,teatro en casa,bocina',
			'events' => 'evento,fiesta,concierto',
			'home' => 'cubiertos,mesa,muebles,silla,escaparate,cocina',
			'jobs' => 'trabajo,contrato,profesional',
			'phones' => 'bateria,celular,iphone,blu,android,ios,cell,rooteo,root,jailbreak,samsung galaxy,blackberry,sony erickson',
			'music_instruments' => 'guitarra,piano,trompeta,bajo,bateria',
			'places' => 'restaurant,bar,cibercafe,club',
			'software' => 'software,programa,juego de pc,juegos,instalador,mapa',
			'real_state' => 'casa,vivienda,permuto,apartamento,apto',
			'relationship' => 'pareja,amigo,novia,novio,singler',
			'services' => 'servicio,reparador,reparan,raparacion,taller,a domicilio,mensajero,taxi',
			'videogames' => 'nintendo,wii,playstation,ps2,xbox',
			'antiques' => 'colleci,antig,moneda,sello,carta,tarjeta',
			'books' => 'libro,revista,biblio',
			'for_sale' => 'venta,vendo,ganga'
		);
		
		foreach ($map as $class => $kws)
		{
			$kws = explode(',', $kws);
			foreach ($kws as $kw)
			{
				if (stripos($text, ' ' . $kw) !== false || stripos($text, ' ' . $kw) === 0)
				{
					return $class;
				}
			}
		}
		
		return 'for_sale';
	}
}
