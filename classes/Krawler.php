<?php

use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;

class Krawler {

	public $url = null;
	public $params = [];
	public $method = 'GET';
	public $client = null;
	public $crawler = null;

	/**
	 * Krawler constructor.
	 *
	 * @param string $url
	 * @param array $params
	 * @param string $method
	 */
	public function __construct($url = "", $params = [], $method = 'GET')
	{
		$this->url = $url;
		$this->params = $params;
		$this->method = $method;
		$this->getCrawler($url, $params, $method);
	}

	/**
	 * Crawler client
	 *
	 * @return \Goutte\Client
	 */
	public function getClient()
	{
		if (is_null($this->client)) {
			$this->client = new Client();
			$guzzle = new GuzzleClient(["verify" => false]);
			$this->client->setClient($guzzle);
		}
		return $this->client;
	}

	/**
	 * Get crawler for URL
	 *
	 * @param string $url
	 * @param string $method
	 * @param array $params
	 * @param bool $force
	 *
	 * @return \Symfony\Component\DomCrawler\Crawler
	 */
	public function getCrawler($url = "", $params = [], $method = 'GET', $force = false)
	{
		if (is_null($this->crawler) || $force)
		{
			$url = trim($url);
			if ($url != '' && $url[0] == '/') $url = substr($url, 1);
			$this->crawler = $this->getClient()->request($method, $url, $params);
		}

		return $this->crawler;
	}

	/**
	 * Selector
	 *
	 * @param $selector
	 * @return \Symfony\Component\DomCrawler\Crawler
	 */
	public function filter($selector)
	{
		return $this->getCrawler()->filter($selector);
	}

	/**
	 * Get remote content from URL
	 * @param $url
	 * @param array $info
	 * @return mixed
	 */
	public function getRemoteContent($url = null, &$info = [], $proxy = false)
	{
		if (is_null($url))
			$url = $this->url;

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

		if ($proxy!==false)
		{
			curl_setopt($ch, CURLOPT_PROXY, $proxy['host']);
			curl_setopt($ch, CURLOPT_PROXYTYPE, $proxy['type']);
		}

		$html = curl_exec($ch);
		$info = curl_getinfo($ch);

		if (isset($info['redirect_url'])
			&& $info['redirect_url'] != $url
			&& !empty($info['redirect_url']))
			return $this->getUrl($info['redirect_url'], $info);

		curl_close($ch);

		return $html;
	}
}
