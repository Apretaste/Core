<?php

use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;

/**
 * Revolico Crawler Task
 *
 * @author kuma updated by salvipascual
 * @version 2.0
 */

class revolicoTask extends \Phalcon\Cli\Task
{
	private $revolicoURL = "https://revolico.com/";
	private $client;

	/**
	 * Crawler client
	 *
	 * @return \Goutte\Client
	 */
	public function getClient()
	{
		if (is_null($this->client))
		{
			$this->client = new Client();
			$guzzle = new GuzzleClient(["verify" => false]);
			$this->client->setClient($guzzle);
		}
		return $this->client;
	}

	/**
	 * Main action
	 *
	 * @param array $revolicoMainUrls
	 */
	public function mainAction($revolicoMainUrls = null)
	{
		// empty(null) === true && empty([]) === null
		if (empty($revolicoMainUrls))
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
		echo "\n\nREVOLICO CRAWLER STARTED\n";

		// variable to store the total number of posts
		$totalPosts = 0;
		$totalPages = 0;

		// for each main url
		foreach ($revolicoMainUrls as $url)
		{
			echo "CRAWLING $url\n";

			// get the list of pages that have not been inserted yet
			try
			{
				$crawler = $this->getClient()->request('GET', $url);

				// get the latest page count
				$lastPage = $crawler->filter('[title="Final"]')->attr('href');
				echo "LAST PAGE $lastPage\n";
				$pagesTotal = intval(preg_replace('/[^0-9]+/', '', $lastPage), 10);
				echo "PAGES TOTAL $pagesTotal \n";
				echo "TODAY " . $this->getTodaysDateSpanishString();

				// get all valid links
				for($n = 1; $n < $pagesTotal; $n ++)
				{
					echo "PAGE $n\n";

					// only crawl for today
					$site  = $this->getUrl($url . "pagina-$n.html");
					$exist = stripos($site, $this->getTodaysDateSpanishString());
					if( ! $exist)
					{
						$months = ["Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"];
						$today = explode(" ", date("j n Y", strtotime("-1 days")));
						$sdate = $today[0] . " de " . $months[ $today[1] - 1 ] . " del " . $today[2];

						if(stripos($site, $sdate) === false) break;
					}

					// move to the next page
					$crawler = $this->getClient()->request('GET', $url . "pagina-$n.html");

					// get all results for that page
					$nodes = $crawler->filter('td a:not(.pwtip)');
					for($i = 0; $i < count($nodes); $i ++)
					{
						$href = $nodes->eq($i)->attr('href');

						if($href == 'javascript:') continue;

						// delete double /
						$ru = $this->revolicoURL;
						if($href[0] == "/" && $ru[ strlen($ru) - 1 ] == "/") $href = substr($href, 1);

						// get the url from the list
						$totalPages ++;

						echo "SAVING PAGE $totalPages: {$ru}$href \n";

						$data = false;

						// get the page's data and images
						try
						{
							$data = $this->crawlRevolicoURL($ru . $href);
						} catch(Exception $e)
						{
							echo "EXCEPTION: ".$e->getMessage();
							//echo "[ERROR] Page {$pages[$i]} request error \n";
						}

						if($data !== false)
						{
							// save the data into the database
							$this->saveToDatabase($data);
						}
						else
						{
							echo "[ERROR] Page $ru.$href request error \n";
						}

						echo "\tMEMORY USED: " . Utils::getFriendlySize(memory_get_usage(true)) . "\n";
					}
				}
			} catch(Exception $e)
			{
				echo "[FATAL] Could not request the URL $url \n";
			}
		}

		// ending message, log and time
		$totalTime = (time() - $timeCrawlerStart) / 60; // time in minutes
		$totalMem = Utils::getFriendlySize(memory_get_usage(true));
		$message = "CRAWLER ENDED - EXECUTION TIME: $totalTime min - NEW POSTS: $totalPosts - TOTAL MEMORY USED: $totalMem";
		$this->saveCrawlerLog($message);
		echo "\n\n$message\n\n";

		// save the status in the database
		Connection::query("UPDATE task_status SET executed=CURRENT_TIMESTAMP, delay='$totalTime', `values`='$totalPosts' WHERE task='revolico'");
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


	/**
	 * Crawler
	 *
	 * @param string $url
	 *
	 * @return mixed
	 */
	private function crawlRevolicoURL($url)
	{

		$timeStart = time();

		// create crawler
		try
		{
			while (strpos($url, '//') !== false)
				$url = str_replace("//", "/", $url);

			$url = str_replace("http:/", "http://", $url);
			$url = str_replace("https:/", "https://", $url);

			echo "[RevolicoTask] Real url crawled $url\n";
			$crawler = $this->getClient()->request('GET', $url);
		}
		catch (Exception $e)
		{
			echo "[RevolicoTask] crawRevolicoURL exception: ".$e->getMessage()."\n";
			return false;
		}


		if ($crawler->filter('.headingText')->count() < 1)
		{
			echo "[CRAWLER] No ad title \n";
			return false;
		}

		if ($crawler->filter('.showAdText')->count() < 1)
		{
			echo "[CRAWLER] No ad text \n";
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
		$owner = "";
		$province = "";

		// get email
		$email = Utils::getEmailFromText($title);
		if (empty($email)) $email = Utils::getEmailFromText($body);

		// get the phone number
		$phone = Utils::getPhoneFromText($title);
		if (empty($phone)) $phone = Utils::getPhoneFromText($body);

		// get the cell number
		$cell = Utils::getCellFromText($title);
		if (empty($cell)) $cell = Utils::getCellFromText($body);

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
					$date = $this->dateSpanishToMySQL($data);
					break;
				}
				case "Nombre:":
				{
					$owner = str_replace("'", "",$data);
					if (stripos($owner,'<![CDATA[')!== false)
						$owner = "";
					else $owner = substr($owner,0,15);
					break;
				}

				case "Tel�fono:":
				{
					if (empty($phone)) $phone = Utils::getPhoneFromText($data);
					if (empty($cell)) $cell = Utils::getCellFromText($data);
					break;
				}
				case "Email:":
				{
					$email = '';
					if (empty($email))
						$data = Utils::getEmailFromText($data);
					if ($data !== false)
						$email = $data;
					else
						$email = 'desconocido@apretaste.com';

					echo "EMAIL $email \n";
					break;
				}
			}
		}

		// get the province
		if (empty($province) && ! empty($phone)) $province = Utils::getProvinceFromPhone($phone);

		// download images
		$pictures = $crawler->filter('.view img');
		for ($i = 0; $i < count($pictures); $i ++)
		{
			// get the current picture
			$picURl = "{$this->revolicoURL}{$pictures->eq($i)->attr('src')}";

			// get the path
			$path = dirname(__DIR__) . "/public/tienda/" . md5($url) . "_" . ($i + 1) . ".jpg";

			// save image in the temp folder
			$file = $this->getUrl($picURl);
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
		return [
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
		];
	}

	/**
	 * Save to database
	 *
	 * @param mixed $data
	 */
	private function saveToDatabase($data)
	{
		Connection::query("DELETE FROM _tienda_post WHERE date_time_posted IS NULL;");
		Connection::query("DELETE FROM _tienda_post WHERE date_time_posted < DATE_SUB(NOW(), INTERVAL 30 day);");

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
		$title = Connection::escape($data['title'], 250);
		$body = Connection::escape($data['body'], 1000);

		if (trim($data['province'])=='')
			$data['province'] = 'LA_HABANA';

		if (substr($title,0,4) == 'auto') $title = substr($title,4);
		$title = trim($title);
		$title = trim($title, "_!#$ .");
		$data['currency'] = empty($data['currency']) ? "CUC" : "{$data['currency']}";
		$data['title'] = $title;
		$data['body'] = $body;
		$data['price'] = (empty(trim($data['price'])) ? 0: trim($data['price'])) * 1.0;

		// remove duplicated
		Connection::query("DELETE FROM _tienda_post WHERE ad_title = '{$data['title']}' AND (contact_email_1 = '{{$data['email']}}' OR source = 'revolico')");

		// insert
		/*
		$sql = "INSERT INTO _tienda_post (contact_name, contact_email_1, contact_phone, contact_cellphone, location_province, ad_title, ad_body, currency,  category,	number_of_pictures, price,   date_time_posted, source,	  source_url )
				  SELECT				  {owner},	  {email},		 {phone},	   {cell},			{province},		{title},  {body},  {currency}, {category}, {images},		   {price}, {date},		   'revolico',  {url}
				  FROM _tienda_post
				  WHERE NOT EXISTS (SELECT id FROM _tienda_post WHERE ad_title = '{title}' AND (contact_email_1 = '{email}' OR source = 'revolico'))
				  LIMIT 0,1";
		*/

		$sql = "INSERT INTO _tienda_post (contact_name, contact_email_1, contact_phone, contact_cellphone, location_province, ad_title, ad_body, currency,  category,	number_of_pictures, price,   date_time_posted, source,	  source_url )
								  VALUES ({owner},	  {email},		 {phone},	   {cell},			{province},		{title},  {body},  {currency}, {category}, {images},		   {price}, {date},		   'revolico',  {url});";

		foreach($data as $key => $value) $sql = str_replace("{{$key}}", "'$value'", $sql);

		try {
			// save into the database, log on error
			@Connection::query($sql);
		} catch(Exception $ex)
		{
			echo "[RevolicoTask] Exception inserting ad ".$ex->getMessage()."\n";
		}

		$timeEnd = time();
		$timeDiff = $timeEnd - $timeStart;
		echo "\tDB TIME: $timeDiff\n";
	}

	/**
	 * Save crawler log
	 *
	 * @param $message
	 */
	private function saveCrawlerLog($message)
	{
		$timestamp = date("Y-m-d H:i:s");
		$errorPath = dirname(__DIR__) . "/logs/crawler.log";
		$f = fopen($errorPath, 'a');
		fputs($f, "$timestamp - REVOLICO - $message\n");
		fclose($f);
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
		$map = [
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
		];

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

	/**
	 * Return remote content
	 *
	 * @param	   $url
	 * @param array $info
	 *
	 * @return mixed
	 */
	private function getUrl($url, &$info = [])
	{
		$url = str_replace("//", "/", $url);
		$url = str_replace("http:/","http://", $url);
		$url = str_replace("https:/","https://", $url);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);

		$default_headers = [
			"Cache-Control" => "max-age=0",
			"Origin" => "{$url}",
			"User-Agent" => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1985.125 Safari/537.36",
			"Content-Type" => "application/x-www-form-urlencoded"
		];

		$hhs = [];
		foreach ($default_headers as $key => $val)
			$hhs[] = "$key: $val";

		curl_setopt($ch, CURLOPT_HTTPHEADER, $hhs);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$html = curl_exec($ch);

		$info = curl_getinfo($ch);

		if ($info['http_code'] == 301)
			if (isset($info['redirect_url']) && $info['redirect_url'] != $url)
				return $this->getUrl($info['redirect_url'], $info);

		curl_close($ch);

		return $html;
	}

	/**
	 * Get today date in spanish
	 *
	 * @return string
	 */
	private function getTodaysDateSpanishString()
	{
		$months = ["Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"];
		$today = explode(" ", date("j n Y"));
		return $today[0] . " de " . $months[$today[1] - 1] . " del " . $today[2];
	}

	/**
	 * Convert date from spanish to mysql
	 *
	 * @param string $spanishDate
	 * @return string
	 */
	private function dateSpanishToMySQL($spanishDate)
	{
		$months = ["Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"];

		// separate each piece of the date
		$spanishDate = preg_replace("/\s+/", " ", $spanishDate);
		$spanishDate = str_replace(",", "", $spanishDate);
		$arrDate = explode(" ", $spanishDate);

		// create the standar, english date
		$month = array_search($arrDate[3], $months) + 1;
		$day = $arrDate[1];
		$year = $arrDate[5];
		$time = $arrDate[6] . " " . $arrDate[7];
		$date = "$month/$day/$year $time";

		// format and return date
		return date("Y-m-d H:i:s", strtotime($date));
	}
}
