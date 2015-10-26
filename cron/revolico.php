<?php

// include composer
use GuzzleHttp\Stream\Utils;

include_once __DIR__."/../vendor/autoload.php";

// create a new client
use Goutte\Client;
$client = new Client();

// get connection params from the file
$configs = parse_ini_file (__DIR__."/../configs/config.ini");

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

echo "\n\nREVOLICO CRAWLER STARTED\n";

// for each main url
foreach ($revolicoMainUrls as $url)
{
	echo "CRAWLING $url\n";

	// get the list of pages that have not been inserted yet
	$pages = getRevolicoPagesFromMainURL($url, $client);

	echo "PROCESSING URLs $url\n";

	// for every page
	for($i=0; $i<count($pages); $i++)
	{
		echo "SAVING PAGE $i/".count($pages)."\n";

		// get the page's data and images
		$data = crawlRevolicoURL($pages[$i], $client);

		// save the data into the database
		saveToDatabase($data, $conn);

		echo "MEMORY: " . convert(memory_get_usage(true)) . "\n";
	}
}

echo "\n\nREVOLICO CRAWLER ENDED\n\n";

// close the connection
mysqli_close($conn);



/* * * * * * * * * * * * * * * * * * * * * * 
 * CORE FUNCTION, PLEASE 
 * HANDLE WITH EXTREME CARE 
 * * * * * * * * * * * * * * * * * * * * * */


function getRevolicoPagesFromMainURL($url, $client) {
	// get the latest page count
	$crawler = $client->request('GET', $url);
	$lastPage = $crawler->filter('[title="Final"]')->attr('href');
	$pagesTotal = intval(preg_replace('/[^0-9]+/', '', $lastPage), 10); 

	// get the number of pages to parse
	$pagesCount = $pagesTotal < 51 ? $pagesTotal : 51; // only get the first 50 pages

	// get all valid links
	$links = array();
	for ($n=1; $n<$pagesCount; $n++)
	{
		echo "PAGE $n\n";

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


function crawlRevolicoURL($url, $client) {
	// create crawler
	$crawler = $client->request('GET', $url);

	// get title
	$title = trim($crawler->filter('.headingText')->text());
	$title = str_replace("'", "\'", $title);

	// get body
	$body = trim($crawler->filter('.showAdText')->text());
	$body = str_replace("'", "\'", $body);

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
			saveCrawlerLog(date("Y-m-d H:i:s") . " Could not save image $path");
		}
	}

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
		"category" => "", // TODO get the category
		"url" => $url
	);
}


function saveToDatabase($data, $conn) {
	// create the query to insert only if it is not repeated in the last month
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

	// save into the database, log on error
	if ( ! mysqli_query($conn, $sql)) saveCrawlerLog(mysqli_error($conn));
}



/* * * * * * * * * * * * * * * * * * * * * * 
 * SUPPORTING FUNCTIONS
 * DO NOT TOUCH UNLESS YOU
 * KNOW WHAT YOU ARE DOING! 
 * * * * * * * * * * * * * * * * * * * * * */



function saveCrawlerLog($message){
	$errorPath = dirname(__DIR__) . "/logs/crawler.log";
	file_put_contents($errorPath, $message."\n", FILE_APPEND);
}


function dateSpanishToMySQL($spanishDate){
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


function getEmailFromText($text){
	$pattern = "/(?:[a-z0-9!#$%&'*+=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+=?^_`{|}~-]+)*|\"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*\")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/";
	preg_match($pattern, $text, $matches);

	if( ! empty($matches)) return $matches[0];
	else return false;
}


function getCellFromText($text){
	$cleanText = preg_replace('/[^A-Za-z0-9\-]/', '', $text); // remove symbols and spaces
	$pattern = "/5(2|3)\d{6}/"; // every 8 digits numbers starting by 52 or 53
	preg_match($pattern, $cleanText, $matches);

	if( ! empty($matches)) return $matches[0];
	else return false;
}


function getPhoneFromText($text){
	$cleanText = preg_replace('/[^A-Za-z0-9\-]/', '', $text); // remove symbols and spaces
	$pattern = "/(48|33|47|32|7|31|47|24|45|23|42|22|43|21|41|46)\d{6,7}/";
	preg_match($pattern, $cleanText, $matches);

	if( ! empty($matches)) return $matches[0];
	else return false;
}


function getProvinceFromPhone($phone){
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
