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
	private $baseUrl = 'https://webmail.nauta.cu/';

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

		// save cookie file
		$utils             = new Utils();
		$this->sessionFile = $utils->getTempDir() . "nautaclient/{$this->user}.session";
		$this->cookieFile  = $utils->getTempDir() . "nautaclient/{$this->user}.cookie";

		$this->loadSession();

		// init curl
		$this->client = curl_init();
		curl_setopt($this->client, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($this->client, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->client, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->client, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($this->client, CURLOPT_COOKIEJAR, $this->cookieFile);
		curl_setopt($this->client, CURLOPT_COOKIEFILE, $this->cookieFile);

		// add default headers
		$this->setHttpHeaders();
	}

	/**
	 * Set proxy
	 *
	 * @param string $host
	 * @param int    $type
	 */
	public function setProxy($host = "localhost:8082", $type = CURLPROXY_SOCKS5)
	{
		curl_setopt($this->client, CURLOPT_PROXY, $host);
		curl_setopt($this->client, CURLOPT_PROXYTYPE, $type);
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
		if ($this->checkLogin())
			return true;

		// save the captcha image in the temp folder
		$utils        = new Utils();
		$captchaImage = $utils->getTempDir() . "capcha/" . $utils->generateRandomHash() . ".jpg";
		curl_setopt($this->client, CURLOPT_URL, "{$this->baseUrl}/securimage/securimage_show.php");
		file_put_contents($captchaImage, curl_exec($this->client));

		if($cliOfflineTest)
		{
			echo "[INFO] Captcha image store in: $captchaImage \n";
			echo "Please enter captcha test:";
			$cli         = fopen("php://stdin", "r");
			$captchaText = fgets($cli);
		}
		else
		{
			// break the captcha
			$captcha = $this->breakCaptcha($captchaImage);
			if($captcha->code == "200")
			{
				$captchaText = $captcha->message;
				rename($captchaImage, $utils->getTempDir() . "capcha/$captchaText.jpg");
			}
			else
			{
				$text = "Captcha error " . $captcha->code . " with message " . $captcha->message;
				$utils->createAlert($text, "ERROR");

				return false;
			}
		}

		// send details to login
		curl_setopt($this->client, CURLOPT_URL, "{$this->baseUrl}login.php");
		curl_setopt($this->client, CURLOPT_POSTFIELDS, "app=&login_post=1&url=&anchor_string=&ie_version=&horde_user=" . urlencode($this->user) . "&horde_pass=" . urlencode($this->pass) . "&captcha_code=" . urlencode($captchaText) . "&horde_select_view=mobile&new_lang=en_US");
		$response = curl_exec($this->client);

		if($response === false) return false;

		if (stripos($response, 'digo de verificaci') !== false &&
		    stripos($response, 'n incorrecto') !== false)
			return false;

		// get tokens
		$this->logoutToken  = php::substring($response, 'horde_logout_token=', '&');
		$this->composeToken = php::substring($response, 'u=', '">New');

		$this->saveSession();
		return true;
	}

	/**
	 * Check keep alive
	 *
	 * @return bool
	 */
	public function checkLogin()
	{
		$this->loadSession();

		curl_setopt($this->client, CURLOPT_URL, "{$this->baseUrl}imp/minimal.php?page=compose&u={$this->composeToken}");
		$html = curl_exec($this->client);

		if (stripos($html, 'Message Composition') === false)
		{
			return false;
		}

		return true;
	}

	/**
	 * Save session
	 */
	public function saveSession()
	{
		$sessionData = [
			'logoutToken' => $this->logoutToken,
			'composeToken' => $this->composeToken
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
		if( ! file_exists($this->sessionFile)) $this->saveSession();

		$sessionData = unserialize(file_get_contents($this->sessionFile));

		$this->logoutToken  = $sessionData['logoutToken'];
		$this->composeToken = $sessionData['composeToken'];

		return $sessionData;
	}

	/**
	 * Send an email
	 *
	 * @param String $to
	 * @param String $subject
	 * @param String $body
	 * @param mixed  $attachment
	 *
	 * @return mixed
	 */
	public function send($to, $subject, $body, $attachment = false)
	{
		// get the HTML of the compose window
		curl_setopt($this->client, CURLOPT_URL, "{$this->baseUrl}imp/minimal.php?page=compose&u={$this->composeToken}");
		$html = curl_exec($this->client);

		// get the value of hidden fields from the HTML
		$utils        = new Utils();
		$action       = php::substring($html, 'u=', '"');
		$composeCache = php::substring($html, 'composeCache" value="', '"');
		$composeHmac  = php::substring($html, 'composeHmac" value="', '"');
		$user         = php::substring($html, 'user" value="', '"');

		// create the body of the image
		$data['composeCache'] = $composeCache;
		$data['composeHmac']  = $composeHmac;
		$data['user']         = $user;
		$data['to']           = $to;
		$data['cc']           = "";
		$data['bcc']          = "";
		$data['subject']      = $subject;
		$data['priority']     = "normal";
		$data['message']      = $body;
		if($attachment) $data['upload_1'] = new CURLFile($attachment);
		$data['a'] = 'Send';

		// set headers
		$this->setHttpHeaders(["Content-Type" => "multipart/form-data"]);

		// send email
		curl_setopt($this->client, CURLOPT_URL, "{$this->baseUrl}imp/minimal.php?page=compose&u=$action");
		curl_setopt($this->client, CURLOPT_SAFE_UPLOAD, true);
		curl_setopt($this->client, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($this->client, CURLOPT_POSTFIELDS, $data);
		$response = curl_exec($this->client);

		// alert if there are errors
		if(curl_errno($this->client))
		{
			$utils = new Utils();

			return $utils->createAlert("[NautaClient] Error sending email: " . curl_error($this->client) . " (to: $to, subject: $subject)", "ERROR");
		}

		return $response;
	}

	/**
	 * Logout from webmail
	 */
	public function logout()
	{
		if($this->client)
		{
			curl_setopt($this->client, CURLOPT_URL, "{$this->baseUrl}login.php?horde_logout_token={$this->logoutToken}&logout_reason=4");
			curl_exec($this->client);
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
			"Cache-Control" => "max-age=0",
			"Origin" => "{$this->baseUrl}",
			"User-Agent" => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1985.125 Safari/537.36",
			"Content-Type" => "application/x-www-form-urlencoded",
			"Connection" => "Keep-Alive",
			"Keep-Alive" => 86400 //secs
		];

		// add custom headers
		$default_headers = array_merge($default_headers, $headers);

		// convert headers array into string
		$headerStr = [];
		foreach($default_headers as $key => $val) $headerStr[] = "$key:$val";

		// add headers to cURL
		curl_setopt($this->client, CURLOPT_HTTPHEADER, $headerStr);
	}

	/**
	 * Breaks an image captcha using human labor. Takes ~15sec to return
	 *
	 * @author salvipascual
	 *
	 * @param String $image
	 *
	 * @return String
	 */
	private function breakCaptcha($image)
	{
		// get path to root and the key from the configs
		$di      = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];
		$key     = $di->get('config')['anticaptcha']['key'];

		// include captcha libs
		require_once("$wwwroot/lib/anticaptcha-php/anticaptcha.php");
		require_once("$wwwroot/lib/anticaptcha-php/imagetotext.php");

		// set the file
		$api = new ImageToText();
		$api->setVerboseMode(true);
		$api->setKey($key);
		$api->setFile($image);

		// create the task
		if( ! $api->createTask())
		{
			$ret          = new stdClass();
			$ret->code    = "500";
			$ret->message = "API v2 send failed: " . $api->getErrorMessage();

			return $ret;
		}

		// wait for results
		$taskId = $api->getTaskId();
		if( ! $api->waitForResult())
		{
			$ret          = new stdClass();
			$ret->code    = "510";
			$ret->message = "Could not solve captcha: " . $api->getErrorMessage();

			return $ret;
		}

		// return the solution
		$ret          = new stdClass();
		$ret->code    = "200";
		$ret->message = $api->getTaskSolution();

		return $ret;
	}
}
