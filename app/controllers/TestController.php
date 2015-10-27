<?php

use Phalcon\Mvc\Controller;

class TestController extends Controller
{
	public function indexAction()
	{
	}

	public function getSynonyms($word){
		$accessKey = "MoIso6nqps7XB2SDqZ3Kx_CNOfga";
		$url="https://store.apicultur.com/api/sinonimosporpalabra/1.0.0/$word";

		$ch = curl_init();
		curl_setopt($ch,CURLOPT_HTTPHEADER,array('Accept: application/json', 'Authorization: Bearer ' . $accessKey));
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if($http_status == 200)
		{
			$synonyms = array();
			foreach(json_decode($response) as $synonym)
			{
				$synonyms[] = utf8_decode($synonym->valor);
			}
			return $synonyms;
		}
		else return NULL;	
	}
}
