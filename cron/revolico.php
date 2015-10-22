<?php  

// include composer
use GuzzleHttp\Stream\Utils;

include_once __DIR__."/../vendor/autoload.php";

// create a new client
use Goutte\Client;
$client = new Client();


/*
'http://www.revolico.com/computadoras/',
'http://www.revolico.com/compra-venta/',
'http://www.revolico.com/servicios/',
'http://www.revolico.com/autos/',
'http://www.revolico.com/empleos/',
'http://www.revolico.com/vivienda/'
*/



// Create connection
$conn = mysqli_connect("127.0.0.1", "root", "root", "apretaste");
if ( ! $conn) saveCrawlerLog("Connection failed: " . mysqli_connect_error());
mysqli_set_charset($conn, "utf8");

/*
$url = 'http://www.revolico.com/computadoras/';
$res = getRevolicoPagesFromMainURL($url, $client);
exit;
*/

$url = 'http://www.revolico.com/compra-venta/celulares-lineas-accesorios/table-smartab-7-pulandroid-41cpu-1ghz512ram-ddr34gb53475313-7649-12660803.html';
$res = crawlRevolicoURL($url, $client);

// save into the database
$sql = 
"INSERT INTO _tienda_post (
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
	'{$res['owner']}',
	'{$res['email']}', 
	'{$res['phone']}', 
	'{$res['cell']}', 
	'{$res['province']}', 
	'{$res['title']}', 
	'{$res['body']}', 
	'', 
	'{$res['images']}', 
	'{$res['price']}', 
	'{$res['currency']}', 
	'{$res['date']}', 
	'revolico', 
	'{$res['url']}'
)";

if ( ! mysqli_query($conn, $sql)) saveCrawlerLog(mysqli_error($conn)); 

// close the connection
mysqli_close($conn);





/* * * * * * * * * * * * * * * * * * * * * * 
 * CORE FUNCTION, PLEASE 
 * HANDLE WITH EXTREME CARE 
 * * * * * * * * * * * * * * * * * * * * * */


function getRevolicoPagesFromMainURL($url, $client) {
	// load the contents of the incremental file in memory
	$incFilePath = __DIR__."/revolico.crawl";
	$inc = file($incFilePath, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);
	$inc = array_combine($inc, $inc);
	echo "INCREMENTAL FILE LOADED\n";

	// get the latest page
	$crawler = $client->request('GET', $url);
	$lastPage = $crawler->filter('[title="Final"]')->attr('href');
	$pagesCount = intval(preg_replace('/[^0-9]+/', '', $lastPage), 10);

	// get all valid links
	$links = array();
	for ($n=1; $n<$pagesCount; $n++)
	{
		echo "PAGE $n\n";

		// mark the seccion as already crawled
		$fullSectionMiss = true;

		// move to the next page
		$crawler = $client->request('GET', $url . "pagina-$n.html");

		// get all results for that page
		$nodes = $crawler->filter('td a:not(.pwtip)');
		for ($i=0; $i<count($nodes); $i++)
		{
			// get the url from the list
			$pageURL = "http://www.revolico.com" + $nodes->eq($i)->attr('href');

			if( ! isset($inc[md5($pageURL)])) 
			{
				// mark the page to be crawled
				$links[] = $pageURL;

				// append to the incremental file so we don't crawl the same page twice
				file_put_contents($incFilePath, md5($pageURL)."\n", FILE_APPEND | LOCK_EX);

				// mark the seccion as not fully crawled
				$fullSectionMiss = false;
			}
		}

		// do not keep crawling if a full section was already in the inc file
		if($fullSectionMiss) break;
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
	};

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
		"url" => $url
	);
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
