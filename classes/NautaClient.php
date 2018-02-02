<?php

/**
 * NautaClient
 *
 * Client for horde webmail
 *
 * @author  kumahacker
 * @author  salvipascual
 * @version 2.0
 */

class NautaClient
{
	private $user = null;
	private $pass = null;
	private $client = null;
	private $cookieFile = "";
	private $sessionFile = "";
	private $logoutToken = "";
	private $composeToken = "";
	private $captchaText = "";
	private $mobileToken = "";
	private $proxy_host = null;

	public function show() {
		echo "USER: {$this->user}<br/>";
		echo "PASS: {$this->pass}<br/>";
		echo "CLIENT: {$this->client}<br/>";
		echo "COOKIEFILE: {$this->cookieFile}<br/>";
		echo "SESSIONFILE: {$this->sessionFile}<br/>";
		echo "LOGOUTTOKEN: {$this->logoutToken}<br/>";
		echo "COMPOSETOKEN: {$this->composeToken}<br/>";
		echo "MOBILETOKEN: {$this->mobileToken}<br/>";
		echo "CAPTCHATEXT: {$this->captchaText}<br/>";
	}

	private $uriGame = [
		0 => [
			'base' => 'http://webmail.nauta.cu/',
			'captcha' => '/securimage/securimage_show.php',
			'login' => 'login.php',
			'loginParams' => "app=&login_post=1&url=&anchor_string=&ie_version=&horde_user={user}&horde_pass={pass}&captcha_code={captcha}&horde_select_view=smartmobile&new_lang=en_US",
			'compose' => 'imp/minimal.php?page=compose&u={token}',
			"composePost' => 'imp/minimal.php?page=compose&u={token}",
			'logout' => "login.php?horde_logout_token={token}&logout_reason=4"
		],
		1 => [
			'base' => 'https://webmail.nauta.cu/',
			'captcha' => false,
			'login' => 'horde/login.php',
			'loginParams' => "app=&login_post=1&url=&anchor_string=&ie_version=&horde_user={user}&horde_pass={pass}&horde_select_view=smartmobile&new_lang=en_US",
			'compose' => 'horde/imp/compose-mimp.php?u={token}',
			'composePost' => 'horde/imp/compose-mimp.php',
			'logout' => 'horde/imp/login.php?horde_logout_token={token}'
		],
		2 => [
			'base' => 'http://webmail.nauta.cu/',
			'captcha' => false,
			'login' => 'horde/login.php',
			'loginParams' => "app=&login_post=1&url=&anchor_string=&ie_version=&horde_user={user}&horde_pass={pass}&horde_select_view=smartmobile&new_lang=en_US",
			'compose' => 'horde/imp/compose-mimp.php?u={token}',
			'composePost' => 'horde/imp/compose-mimp.php',
			'logout' => 'horde/imp/login.php?horde_logout_token={token}'
		],
	];

	private $currentUriGame = 0;

	/**
	 * NautaClient constructor.
	 *
	 * @param string $user
	 * @param string $pass
	 */
	public function __construct($user = null, $pass = null)
	{
		// save global user/pass
		$this->user = $user;
		$this->pass = $pass;

		// init curl
		$this->client = curl_init();

		$this->setProxy();

		// save cookie file
		$utils = new Utils();
		$proxy_host = '';

		if (!is_null($this->proxy_host))
			$proxy_host = ".{$this->proxy_host}";

		$this->sessionFile = $utils->getTempDir() . "nautaclient/{$this->user}$proxy_host.session";
		$this->cookieFile = $utils->getTempDir() . "nautaclient/{$this->user}$proxy_host.cookie";

		$this->loadSession();

		curl_setopt($this->client, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($this->client, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->client, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($this->client, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($this->client, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($this->client, CURLOPT_COOKIEJAR, $this->cookieFile);
		curl_setopt($this->client, CURLOPT_COOKIEFILE, $this->cookieFile);

		// add default headers
		$this->setHttpHeaders();
		//$this->detectUriGame();
	}

	public function detectUriGame()
	{
		foreach ($this->uriGame as $key => $urls) {
			try {
				//echo "checking {$urls['base']}\n";
				$check = $this->checkLogin();
				$this->currentUriGame = $key;
				return $check;
			} catch (Exception $e) {
				continue;
			}
		}
	}

	/**
	 * Return the current set of URL of webmail
	 *
	 * @return mixed
	 */
	public function getUriGame()
	{
		return $this->uriGame[$this->currentUriGame];
	}

	/**
	 * Replace string with key/value pairs
	 *
	 * @param $str
	 * @param $params
	 *
	 * @return bool|mixed
	 */
	public function replaceParams($str, $params)
	{
		if ($str == false) return false;
		foreach ($params as $var => $value) {
			$str = str_replace('{' . $var . '}', $value, $str);
		}
		return $str;
	}

	/**
	 * Return base URL of webmail
	 *
	 * @return mixed
	 */
	public function getBaseUrl()
	{
		return $this->getUriGame()['base'];
	}

	/**
	 * Return the captcha URL
	 * @return mixed
	 */
	public function getCaptchaUrl()
	{
		$uri = $this->getUriGame()['captcha'];
		if ($uri == false) return false;
		return $this->getBaseUrl() . $uri;
	}

	/**
	 * Return base URL for login
	 * @return mixed
	 */
	public function getLoginUrl()
	{
		return $this->getBaseUrl() . $this->getUriGame()['login'];
	}

	/**
	 * Return login URI with replaced params
	 *
	 * @param array $params
	 *
	 * @return mixed
	 */
	public function getLoginParams($params = [])
	{
		return $this->replaceParams($this->getUriGame()['loginParams'], $params);
	}

	/**
	 * Return the compose URL
	 *
	 * @param $params
	 *
	 * @return mixed
	 */
	public function getComposeUrl($params = [])
	{
		return $this->getBaseUrl() . $this->replaceParams($this->getUriGame()['compose'], $params);
	}

	/**
	 * Return compost post url
	 *
	 * @param $params
	 *
	 * @return string
	 */
	public function getComposePostUrl($params = [])
	{
		return $this->getBaseUrl() . $this->replaceParams($this->getUriGame()['composePost'], $params);
	}

	/**
	 * Return logout url
	 *
	 * @param array $params
	 *
	 * @return string
	 */
	public function getLogoutUrl($params = [])
	{
		return $this->getBaseUrl() . $this->replaceParams($this->getUriGame()['logout'], $params);
	}

	/**
	 * Set proxy
	 *
	 * @param string $host
	 * @param int $type
	 */
	public function setProxy($host = null, $type = CURLPROXY_SOCKS5)
	{
		if (is_null($host)) {
			$di = \Phalcon\DI\FactoryDefault::getDefault();
			$www_root = $di->get('path')['root'];
			$configFile = "$www_root/configs/socks.json";
			if (file_exists($configFile)) {
				$proxies = file_get_contents("$www_root/configs/socks.json");
				$proxies = json_decode($proxies);

				//shuffle($proxies);
				foreach ($proxies as $proxy) {
					if (is_object($proxy)) $proxy = get_object_vars($proxy);

					$kk = new Krawler("http://example.com");
					$result = $kk->getRemoteContent("http://example.com", $info, [
						"host" => "{$proxy['host']}:{$proxy['port']}",
						"type" => CURLPROXY_SOCKS5
					]);

					if ($result !== false) {
						$host = "{$proxy['host']}:{$proxy['port']}";
						$this->proxy_host = $proxy['host'];
						break;
					}
				}
			}
		}

		if (!is_null($host)) {
			curl_setopt($this->client, CURLOPT_PROXY, $host);
			curl_setopt($this->client, CURLOPT_PROXYTYPE, $type);
		}
	}

	/**
	 * Login webmail
	 *
	 * @param bool $cliOfflineTest
	 *
	 * @return bool
	 */
	public function login($cliOfflineTest = false)
	{
		if ($this->checkLogin()) return true;

		// save the captcha image in the temp folder
		$utils = new Utils();
		$captchaImage = $utils->getTempDir() . "capcha/" . $utils->generateRandomHash() . ".jpg";
		$captchaUrl = $this->getCaptchaUrl();

		$img = false; // maybe, uri game have captcha url and webmail not
		if ($captchaUrl !== false) {
			curl_setopt($this->client, CURLOPT_URL, $captchaUrl);
			for($i =0; $i<3; $i++)
			{
				$img = curl_exec($this->client);
				if ($img !== false) break;
			}
		}

		$captchaText = '';

		if ($img !== false) {
			file_put_contents($captchaImage, $img);

			if ($cliOfflineTest) {
				echo "[INFO] Captcha image store in: $captchaImage \n";
				echo "Please enter captcha test:";
				$cli = fopen("php://stdin", "r");
				$captchaText = fgets($cli);
			} else {
				// break the captcha
				$captcha = $this->breakCaptcha($captchaImage);
				if ($captcha->code == "200") {
					$captchaText = $captcha->message;
					rename($captchaImage, $utils->getTempDir() . "capcha/$captchaText.jpg");
				} else {
					return $utils->createAlert("[NautaClient] Captcha error {$captcha->code} with message {$captcha->message}");
				}
			}
		}

		// send details to login
		if ($cliOfflineTest) echo $this->getLoginUrl();
		curl_setopt($this->client, CURLOPT_URL, $this->getLoginUrl());
		curl_setopt($this->client, CURLOPT_POSTFIELDS, $this->getLoginParams([
			'horde_user' => urlencode($this->user),
			'horde_pass' => urlencode($this->pass),
			'user' => urlencode($this->user),
			'pass' => urlencode($this->pass),
			'captcha' => urlencode($captchaText),
			'captcha_code' => urlencode($captchaText),
			'app' => '',
			'login_post' => '0',
			'url' => '',
			'anchor_string' => '',
			'horde_select_view' => 'smartmobile'  // mobile @salvi
		]));

		$response = false;
		for($i =0; $i<3; $i++)
		{
			$response = curl_exec($this->client);
			if ($response !== false) break;
		}

		if ($response === false) return false;

		if (stripos($response, 'digo de verificaci') !== false &&
			stripos($response, 'n incorrecto') !== false)
			return false;

		if (stripos($response, 'Login failed') !== false &&
			stripos($response, '<ul class="notices">') !== false)
			return false;

		// get tokens
		$this->mobileToken  = php::substring($response, '"token":"', '"}');
		$this->logoutToken  = php::substring($response, 'horde_logout_token=', '&');
		$this->composeToken = php::substring($response, 'u=', '">New');
		$this->captchaText = $captchaText;

		$this->saveSession();
		return true;
	}

	/**
	 * Check keep alive
	 *
	 * @throws Exception
	 * @return bool
	 */
	public function checkLogin()
	{
		$this->loadSession();
		//$url = "http://webmail.nauta.cu/services/ajax.php/imp/viewport";
		$url = "http://webmail.nauta.cu/";
		/*
		$params = [
			'view' => 'SU5CT1g',
			'viewport' => '{"view":"SU5CT1g","initial":1,"force":1,"slice":"1:30"}',
			'flag_config' => 1,
			'token' => $this->mobileToken
		];

		$params = [
			'all' => '0',
			'token' => $this->mobileToken
		];
		//var_dump($params);
		*/
		curl_setopt($this->client, CURLOPT_URL, $url);
		//curl_setopt($this->client, CURLOPT_POST, true);
		//curl_setopt($this->client, CURLOPT_POSTFIELDS, $params);
		curl_setopt($this->client, CURLOPT_RETURNTRANSFER, 1);

		for($i =0; $i<3; $i++)
		{
			$result = curl_exec($this->client);
			if ($result !== false) break;
		}

		//$info = curl_getinfo($this->client);
		//var_dump($info);
		//if ($infp['http_c'])
		//$output = gzdecode($result);
		// $output = $result;
		//$output = php::substring($output, '/*-secure-', '*/');
		//echo "CHECKLOGIN: ". $output;
		//$output = json_decode($output);
		//return stripos($output->response,'"tasks":') !== false;

		return stripos($result, '<form id="imp-compose-form"') !== false;

		/*curl_setopt($this->client, CURLOPT_URL, $this->getComposeUrl([
			'token' => $this->mobileToken
		]));*/

		/*$html = curl_exec($this->client);
		$html = "$html";
		echo $html;
		//echo gzdecode($html);
		if (stripos($html, 'Message Composition') === false
			&& stripos($html, '<form id="compose"') === false
			&& stripos($html, '<form id="imp-compose-form"') === false
		) return false;
		else return true;
		*/
	}

	/**
	 * Save session
	 */
	public function saveSession()
	{
		$sessionData = [
			'logoutToken' => $this->logoutToken,
			'composeToken' => $this->composeToken,
			'mobileToken' => $this->mobileToken
		];

		file_put_contents($this->sessionFile, serialize($sessionData));
	}

	/**
	 * Load session
	 *
	 * @return mixed
	 */
	public function loadSession()
	{
		if (!file_exists($this->sessionFile)) $this->saveSession();

		$sessionData = unserialize(file_get_contents($this->sessionFile));
//var_dump($sessionData);
		$this->logoutToken = $sessionData['logoutToken'];
		$this->composeToken = $sessionData['composeToken'];
		if (isset($sessionData['mobileToken'])) $this->mobileToken = $sessionData['mobileToken'];
		return $sessionData;
	}

	/**
	 * Send an email
	 *
	 * @param String $to
	 * @param String $subject
	 * @param String $body
	 * @param mixed $attachment
	 * @return mixed
	 */
	public function send($to, $subject, $body, $attachment=false)
	{
		// attaching file if exist
		$composeCache = "";
		$composeHmac = "";
		if($attachment)
		{
			// create emails params
			$url = 'http://webmail.nauta.cu/services/ajax.php/imp/addAttachment';
			$params['MAX_FILE_SIZE'] = "20971520";
			$params['file_upload'] = new CURLFile($attachment);
			$params['composeCache'] = "";
			$params['json_return'] = "true";
			$params['token'] = $this->mobileToken;

			// add stuff to cURL
			$this->setHttpHeaders(["Content-Type" => "multipart/form-data"]);
			curl_setopt($this->client, CURLOPT_URL, $url);
			curl_setopt($this->client, CURLOPT_SAFE_UPLOAD, true);
			curl_setopt($this->client, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($this->client, CURLOPT_POSTFIELDS, $params);
			curl_setopt($this->client, CURLOPT_RETURNTRANSFER, 1);

			$output = false;

			for($i =0; $i<3; $i++)
			{
				$output = curl_exec($this->client);
				if ($output !== false) break;
			}

			$output = @gzdecode($output);

			// get the cacheid and hmac for the attachment
			$output = php::substring($output, '/*-secure-', '*/');
			//echo $output;
			$output = json_decode($output);

			if (isset($output->tasks))
			{
				$composeCache = $output->tasks->{'imp:compose'}->cacheid;
				$composeHmac = $output->tasks->{'imp:compose'}->hmac;
			}
		}

		// create emails params
		$url = 'http://webmail.nauta.cu/services/ajax.php/imp/smartmobileSendMessage';
		$params['composeCache'] = $composeCache;
		$params['composeHmac'] = $composeHmac;
		$params['user'] = "{$this->user}@nauta.cu";
		$params['to[]'] = $to;
		$params['cc[]'] = "";
		$params['subject'] = $subject;
		$params['message'] = $body;
		$params['token'] = $this->mobileToken;

		// send the email
		$this->setHttpHeaders(["Content-Type" => "application/x-www-form-urlencoded"]);
		curl_setopt($this->client, CURLOPT_URL, $url);
		curl_setopt($this->client, CURLOPT_SAFE_UPLOAD, true);
		curl_setopt($this->client, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($this->client, CURLOPT_POST, 1);
		curl_setopt($this->client, CURLOPT_POSTFIELDS, http_build_query($params));
		curl_setopt($this->client, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($this->client);
		//echo gzdecode($output);
		// alert if there are errors
		if (curl_errno($this->client)) {
			$utils = new Utils();
			return $utils->createAlert("[NautaClient] Error sending email: " . curl_error($this->client) . " (to: $to, subject: $subject)");
		}

		return true;
	}

	/**
	 * Logout from webmail
	 */
	public function logout()
	{
		if ($this->client) {
			curl_setopt($this->client, CURLOPT_URL, $this->getLogoutUrl([
				'token' => $this->logoutToken
			]));

			for($i =0; $i<3; $i++)
			{
				$output = curl_exec($this->client);
				if ($output !== false) break;
			}

			curl_close($this->client);
		}
	}

	/**
	 * Set more http headers
	 *
	 * @param array $headers
	 */
	private function setHttpHeaders($headers = [])
	{
		// set default headers
		$default_headers = [
			"Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
			"Accept-Encoding" => "gzip, deflate",
			"Cache-Control" => "max-age=0",
			"Origin" => $this->getBaseUrl(),
			"User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:57.0) Gecko/20100101 Firefox/57.0",
			"Content-Type" => "application/x-www-form-urlencoded",
			"Connection" => "keep-alive",
			"Keep-Alive" => 86400, //secs,
			"Referer" => "http://webmail.nauta.cu/imp/minimal.php?mailbox=SU5CT1g&page=mailbox"
		];

		// add custom headers
		$default_headers = array_merge($default_headers, $headers);

		// convert headers array into string
		$headerStr = [];
		foreach ($default_headers as $key => $val) $headerStr[] = "$key:$val";

		// add headers to cURL
		curl_setopt($this->client, CURLOPT_HTTPHEADER, $headerStr);
	}

	/**
	 * Breaks an image captcha using human labor. Takes ~15sec to return
	 *
	 * @author salvipascual
	 * @param String $image
	 * @return String
	 */
	private function breakCaptcha($image)
	{
		// get path to root and the key from the configs
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];
		$key = $di->get('config')['anticaptcha']['key'];

		// include captcha libs
		require_once("$wwwroot/lib/anticaptcha-php/anticaptcha.php");
		require_once("$wwwroot/lib/anticaptcha-php/imagetotext.php");

		// set the file
		$api = new ImageToText();
		$api->setVerboseMode(true);
		$api->setKey($key);
		$api->setFile($image);

		// create the task
		if (!$api->createTask()) {
			$ret = new stdClass();
			$ret->code = "500";
			$ret->message = "API v2 send failed: " . $api->getErrorMessage();
			return $ret;
		}

		// wait for results
		$taskId = $api->getTaskId();
		if (!$api->waitForResult()) {
			$ret = new stdClass();
			$ret->code = "510";
			$ret->message = "Could not solve captcha: " . $api->getErrorMessage();
			return $ret;
		}

		// return the solution
		$ret = new stdClass();
		$ret->code = "200";
		$ret->message = $api->getTaskSolution();
		return $ret;
	}
}
