<?php

// include composer
use GuzzleHttp\Stream\Utils;

include_once __DIR__."/../vendor/autoload.php";

// create a new client
use Goutte\Client;
$client = new Client();

// get connection params from the file
$configs = parse_ini_file(__DIR__."/../configs/config.ini", true)['database'];

// Create connection
$conn = mysqli_connect($configs['host'], $configs['user'], $configs['password'], $configs['database']);
if ( ! $conn) saveCrawlerLog("Connection failed: " . mysqli_connect_error());
mysqli_set_charset($conn, "utf8");

// starting points for the crawler
$revolicoMainUrls= array(
	'http://www.revolico.com/computadoras/',
	'http://www.revolico.com/compra-venta/',
	'http://www.revolico.com/servicios/',
	'http://www.revolico.com/autos/',
	'http://www.revolico.com/empleos/',
	'http://www.revolico.com/vivienda/'
);

// starting time and message
$timeCrawlerStart  = time();
echo "\n\nREVOLICO CRAWLER STARTED\n";

// variable to store the total number of posts
$totalPosts = 0;

// for each main url
foreach ($revolicoMainUrls as $url)
{
	echo "CRAWLING $url\n";

	// get the list of pages that have not been inserted yet
	$pages = getRevolicoPagesFromMainURL($url);

	// calculate the total number of posts
	$totalPosts += count($pages);

	echo "PROCESSING URLs $url\n";

	// for every page
	for($i=0; $i<count($pages); $i++)
	{
		echo "SAVING PAGE $i/".count($pages)."\n";

		// get the page's data and images
		$data = crawlRevolicoURL($pages[$i]);

		// save the data into the database
		saveToDatabase($data);

		echo "\tMEMORY USED: " . convert(memory_get_usage(true)) . "\n";
	}
}

// close the connection
mysqli_close($conn);

// ending message, log and time
$totalTime = (time() - $timeCrawlerStart) / 60; // time in minutes
$totalMem = convert(memory_get_usage(true));
$message = "CRAWLER ENDED - EXECUTION TIME: $totalTime min - NEW POSTS: $totalPosts - TOTAL MEMORY USED: $totalMem";
saveCrawlerLog($message);
echo "\n\n$message\n\n";

// save last run time
$tmpRunPath = dirname(__DIR__) . "/temp/crawler.revolico.last.run";
file_put_contents($tmpRunPath, date("Y-m-d H:i:s")."|$totalTime|$totalPosts|$totalMem");


/* * * * * * * * * * * * * * * * * * * * * * 
 * CORE FUNCTION, PLEASE 
 * HANDLE WITH EXTREME CARE 
 * * * * * * * * * * * * * * * * * * * * * */


function getRevolicoPagesFromMainURL($url)
{
	global $client;

	$crawler = $client->request('GET', $url);

	// get the latest page count
	$lastPage = $crawler->filter('[title="Final"]')->attr('href');
	$pagesTotal = intval(preg_replace('/[^0-9]+/', '', $lastPage), 10); 

	// get all valid links
	$links = array();
	for ($n=1; $n<$pagesTotal; $n++)
	{
		echo "PAGE $n\n";

		// only crawl for today
		$site = file_get_contents($url . "pagina-$n.html");
		$exist = stripos($site, getTodaysDateSpanishString());
		if( ! $exist) return $links;

		// move to the next page
		$crawler = $client->request('GET', $url . "pagina-$n.html");

		// get all results for that page
		$nodes = $crawler->filter('td a:not(.pwtip)');
		for ($i=0; $i<count($nodes); $i++)
		{
			// get the url from the list
			$links[] = "http://www.revolico.com" . $nodes->eq($i)->attr('href');
		}
	}

	return $links;
}


function crawlRevolicoURL($url)
{
	global $client;

	$timeStart  = time();

	// create crawler
	$crawler = $client->request('GET', $url);

	// get title
	$title = trim($crawler->filter('.headingText')->text());

	// get body
	$body = trim($crawler->filter('.showAdText')->text());

	// declare a whole bunch of empty variables
	$price = ""; $currency = ""; $date = ""; $email = ""; $owner = ""; $phone = ""; $cell = ""; $province="";

	// get email
	$email = getEmailFromText($title);
	if(empty($email)) $email = getEmailFromText($body);

	// get the phone number
	$phone = getPhoneFromText($title);
	if(empty($phone)) $phone = getPhoneFromText($body);

	// get the cell number
	$cell = getCellFromText($title);
	if(empty($cell)) $cell = getCellFromText($body);

	// get all code into lineBloks
	$nodes = $crawler->filter('#lineBlock');
	for ($i=0; $i<count($nodes); $i++)
	{
		// get the current node
		$node = $nodes->eq($i);

		// get the header and data of the block
		$header = trim($node->filter('.headingText2')->text());
		$data = trim($node->filter('.normalText')->text());

		// handle the data depending the header
		switch ($header){
			case "Precio:": {
				$price = preg_replace("/[^0-9]/","", $data);
				$currency = !empty($price) ? "CUC" : "";
				break;
			}
			case "Fecha:": {
				$date = dateSpanishToMySQL($data);
				break;
			}
			case "Nombre:": {
				$owner = $data;
				break;
			}
			case "Teléfono:": {
				if(empty($phone)) $phone = getPhoneFromText($data);
				if(empty($cell)) $cell = getCellFromText($data);
				break;
			}
			case "Email:": {
				if(empty($email)) $email = $data;
				break;
			}
		}
	}

	// get the province
	if(empty($province) && !empty($phone)) $province = getProvinceFromPhone($phone);

	// download images
	$pictures = $crawler->filter('.view img');
	for ($i=0; $i<count($pictures); $i++)
	{
		// get the current picture
		$picURl = "http://www.revolico.com{$pictures->eq($i)->attr('src')}";

		// get the path
		$path = dirname(__DIR__) . "/public/tienda/" . md5($url) . "_" . ($i+1) . ".jpg";

		// save image in the temp folder
		$file = file_get_contents($picURl);
		$insert = file_put_contents($path, $file);

		if ( ! $insert)
		{
			// save error log
			saveCrawlerLog(" Could not save image $path");
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
		"category" => classify("$title $body");
		"url" => $url
	);
}


function saveToDatabase($data)
{
	global $conn;

	$timeStart  = time();

	// create the query to insert only if it is not repeated in the last month
/*
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
	) 
	SELECT 
		'{$data['owner']}',
		'{$data['email']}',
		'{$data['phone']}',
		'{$data['cell']}',
		'{$data['province']}',
		'{$data['title']}',
		'{$data['body']}',
		'{$data['category']}',
		'{$data['images']}',
		'{$data['price']}',
		'{$data['currency']}',
		'{$data['date']}',
		'revolico',
		'{$data['url']}'
	FROM _tienda_post
	WHERE (
		SELECT COUNT(id)
		FROM _tienda_post
		WHERE 
			date_time_posted > (NOW() - INTERVAL 1 MONTH) AND
			levenshtein('{$data['title']}', ad_title) < 10
		) = 0
	LIMIT 1";
*/

	// clean the body and title of characters that may break the query
	$title = $conn->real_escape_string($data['title']);
	$body = $conn->real_escape_string($data['body']);

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
	if ( ! mysqli_query($conn, $sql)) saveCrawlerLog(mysqli_error($conn) . "\nQUERY: $sql\n");

	$timeEnd = time();
	$timeDiff = $timeEnd - $timeStart;
	echo "\tDB TIME: $timeDiff\n";
}



/* * * * * * * * * * * * * * * * * * * * * * 
 * SUPPORTING FUNCTIONS
 * DO NOT TOUCH UNLESS YOU
 * KNOW WHAT YOU ARE DOING! 
 * * * * * * * * * * * * * * * * * * * * * */



function saveCrawlerLog($message)
{
	$timestamp = date("Y-m-d H:i:s");
	$errorPath = dirname(__DIR__) . "/logs/crawler.log";
	file_put_contents($errorPath, "$timestamp - REVOLICO - $message\n", FILE_APPEND);
}


function dateSpanishToMySQL($spanishDate)
{
	$months = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");

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
	return date("Y-m-d H:i:s", strtotime ($date));
}


function getTodaysDateSpanishString()
{
	$months = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
	$today = explode(" ", date("j n Y"));
	return $today[0] . " de " . $months[$today[1]-1] . " del " . $today[2]; 
}


function getEmailFromText($text)
{
	$pattern = "/(?:[a-z0-9!#$%&'*+=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+=?^_`{|}~-]+)*|\"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*\")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/";
	preg_match($pattern, $text, $matches);

	if( ! empty($matches)) return $matches[0];
	else return false;
}


function getCellFromText($text)
{
	$cleanText = preg_replace('/[^A-Za-z0-9\-]/', '', $text); // remove symbols and spaces
	$pattern = "/5(2|3)\d{6}/"; // every 8 digits numbers starting by 52 or 53
	preg_match($pattern, $cleanText, $matches);

	if( ! empty($matches)) return $matches[0];
	else return false;
}


function getPhoneFromText($text)
{
	$cleanText = preg_replace('/[^A-Za-z0-9\-]/', '', $text); // remove symbols and spaces
	$pattern = "/(48|33|47|32|7|31|47|24|45|23|42|22|43|21|41|46)\d{6,7}/";
	preg_match($pattern, $cleanText, $matches);

	if( ! empty($matches)) return $matches[0];
	else return false;
}


function getProvinceFromPhone($phone)
{
	if(strpos($phone, "7")==0) return 'LA_HABANA';
	if(strpos($phone, "21")==0) return 'GUANTANAMO';
	if(strpos($phone, "22")==0) return 'SANTIAGO_DE_CUBA';
	if(strpos($phone, "23")==0) return 'GRANMA';
	if(strpos($phone, "24")==0) return 'HOLGUIN';
	if(strpos($phone, "31")==0) return 'LAS_TUNAS';
	if(strpos($phone, "32")==0) return 'CAMAGUEY';
	if(strpos($phone, "33")==0) return 'CIEGO_DE_AVILA';
	if(strpos($phone, "41")==0) return 'SANTI_SPIRITUS';
	if(strpos($phone, "42")==0) return 'VILLA_CLARA';
	if(strpos($phone, "43")==0) return 'CIENFUEGOS';
	if(strpos($phone, "45")==0) return 'MATANZAS';
	if(strpos($phone, "46")==0) return 'ISLA_DE_LA_JUVENTUD';
	if(strpos($phone, "47")==0) return 'ARTEMISA';
	if(strpos($phone, "47")==0) return 'MAYABEQUE';
	if(strpos($phone, "48")==0) return 'PINAR_DEL_RIO';
}


function convert($size)
{
	$unit=array('b','kb','mb','gb','tb','pb');
	return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}


/**
 * Get a category for the post
 *
 * @author kuma
 * @param String, title and body concatenated
 * @return String category
 * */
function classify($text)
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

	foreach($map as $class => $kws)
	{
		$kws = explode(',',$kws);
		foreach($kws as $kw)
		{
			if (stripos($text,' '.$kw)!==false || stripos($text,' '.$kw)===0)
			{
				return $class;
			}
		}
	}

	return 'for_sale';
}
