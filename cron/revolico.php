<?php  


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


// include composer
include_once __DIR__."/../vendor/autoload.php";

// create a new client
use Goutte\Client;
$client = new Client();

// create crawler
$crawler = $client->request('GET', 'http://www.revolico.com/computadoras/tarjeta-de-video/audi-12697252.html');
//$crawler = $client->request('GET', 'http://www.revolico.com/computadoras/monitor/cable-hdmi-de-2-metros-nuevo-en-su-estuche-eddy-o-roxana-5325879-12697536.html');

/*
    'http://www.revolico.com/computadoras/',
    'http://www.revolico.com/compra-venta/',
    'http://www.revolico.com/servicios/',
    'http://www.revolico.com/autos/',
    'http://www.revolico.com/empleos/',
    'http://www.revolico.com/vivienda/' 
*/

// get title
$title = trim($crawler->filter('.headingText')->text());


// get body
$body = trim($crawler->filter('.showAdText')->text());


// declare a whole bunch of empty variables
$price = ""; $currency = ""; 
$date = ""; $email = ""; $name = ""; 
$phone = ""; $cell = "";


// get email
$email = getEmailFromText($title);
if( ! $email) $email = getEmailFromText($body);


// get all code into lineBloks
$nodes = $crawler->filter('#lineBlock');
for ($i=0; $i<count($nodes); $i++)
{
	// get the node
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
			$name = $data;
			break;
		}

		case "Teléfono:": {
			$phone = $data;
			$cell = $data;
			break;
		}

		case "Email:": {
			if( ! $email) $email = $data;
			break;
		}
	}
}




echo "TITLE: $title\n\n\n";
echo "FECHA: $date\n\n\n";
echo "PRECIO: $price\n\n\n";
echo "CURRENCY: $currency\n\n\n";
echo "NOMBRE: $name\n\n\n";
echo "PHONE: $phone\n\n\n";
echo "CELL: $cell\n\n\n";
echo "EMAIL: $email\n\n\n";
echo "BODY: $body\n\n\n";

