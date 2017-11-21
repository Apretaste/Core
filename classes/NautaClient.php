<?php

/**
 * NautaClient
 *
 * Client for horde webmail
 *
 * @author kumahacker
 * @version 1.0
 */

class NautaClient
{
	private $user = null;
	private $pass = null;

	private $baseUrl = "https://webmail.nauta.cu/";

	private $client = null;
	private $cookieFile = "";

	private $logoutToken = "";
	private $composeToken = "";

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

		// get root folder
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];

		// save tmp files
		$utils = new Utils();
		$temp = $utils->getTempDir();
		@mkdir ("{$temp}nautaclient");
		$cookieFile = "{$temp}nautaclient/{$this->user}.cookie";

		// init curl
		$this->client = curl_init();
		curl_setopt($this->client, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($this->client, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->client, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->client, CURLOPT_COOKIEJAR, $cookieFile);
		curl_setopt($this->client, CURLOPT_COOKIEFILE, $cookieFile);

		$this->setHttpHeaders();
		$this->cookieFile = $cookieFile;
	}

	/**
	 * Set more http headers
	 *
	 * @param array $headers
	 */
	public function setHttpHeaders($headers = [])
	{
		$default_headers = [
			"Cache-Control" => "max-age=0",
			"Origin" => "{$this->baseUrl}",
			"User-Agent" => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1985.125 Safari/537.36",
			"Content-Type" => "application/x-www-form-urlencoded"
		];

		$default_headers = array_merge($default_headers, $headers);

		$hhs = [];
		foreach ($default_headers as $key => $val)
			$hhs[] = "$key: $val";

		curl_setopt($this->client, CURLOPT_HTTPHEADER, $hhs);
	}

	/**
	 * Set proxy
	 *
	 * @param string $host
	 * @param int $type
	 */
	public function setProxy($host = "localhost:8082", $type = CURLPROXY_SOCKS5)
	{
		curl_setopt($this->client, CURLOPT_PROXY, $host);
		curl_setopt($this->client, CURLOPT_PROXYTYPE, $type);
	}

	/**
	 * Login webmail
	 *
	 * @param string $user
	 * @param string $pass
	 * @return bool
	 */
	public function login($user=null, $pass=null)
	{
		if (is_null($user)) $user = $this->user;
		if (is_null($pass)) $pass = $this->pass;

		// download the login page
		curl_setopt($this->client, CURLOPT_URL, "{$this->baseUrl}login.php");
		$loginPage = curl_exec($this->client);

		// get the path to the captcha image
		$doc = new DOMDocument();
		$doc->loadHTML($loginPage);
		$imageSrc = $doc->getElementById('captcha')->attributes->getNamedItem('src')->nodeValue;

		// save the captcha image in the temp folder
		$utils = new Utils();
		$captchaImage = $utils->getTempDir() . "capcha/" . $utils->generateRandomHash() . ".jpg";
		file_put_contents($captchaImage, file_get_contents("{$this->baseUrl}{$imageSrc}"));

		// break the captcha
		$captcha = $this->breakCaptcha($captchaImage);
		if($captcha->code == "200") {
			$captchaText = $captcha->message;
			rename($captchaImage, $utils->getTempDir()."capcha/$captchaText.jpg");
		} else {
			$text = "Captcha error " . $captcha->code . " with message " . $captcha->message;
			$utils->createAlert($text, "ERROR");
			return false;
		}

		// send datails to login
		curl_setopt($this->client, CURLOPT_URL, "{$this->baseUrl}login.php");
		curl_setopt($this->client, CURLOPT_POSTFIELDS, "app=&login_post=1&url=&anchor_string=&ie_version=&horde_user=".urlencode($user)."&horde_pass=".urlencode($pass)."&captcha_code=".urlencode($captchaText)."&horde_select_view=mobile&new_lang=en_US");
		$response = curl_exec($this->client);
		if ($response === false) return false;

		$this->logoutToken = "";
		$this->composeToken = "";

		// parse logout token
		$p = strpos($response, "horde_logout_token");
		if ($p !== false) {
			$t = substr($response, $p);
			$t = explode("&", $t);
			$t = $t[0];
			$t = explode("=", $t);
			$t = $t[1];
			$this->logoutToken = $t;
		}

		// parse compose token
		$tk1 = 'compose-mimp.php?u=';
		$tk2 = '">New Message<';
		$p1 = strpos($response, $tk1);
		$p2 = strpos($response, $tk2, $p1);
		if ($p1 !== false && $p2 !== false) $this->composeToken = substr($response, $p1 + strlen($tk1), $p2 - ($p1 + strlen($tk1)));
		return true;
	}

	/**
	 * Send email
	 *
	 * @param $to
	 * @param $cc
	 * @param $bcc
	 * @param $subject
	 * @param $body
	 * @param string $priority
	 * @param bool $attachment
	 * @return mixed
	 */
	public function sendEmail($to, $subject, $body, $attachment = false, $cc = "", $bcc = "", $priority = "normal")
	{
		// get send form
		curl_setopt($this->client, CURLOPT_URL, "{$this->baseUrl}imp/compose.php?u={$this->composeToken}");
		$html = curl_exec($this->client);

		if (curl_errno($this->client) !== 0)
		{
			$utils = new Utils();
			$utils->createAlert("[NautaClient] Error when load the login form: ".curl_error($this->client)." (to: $to, subject: $subject) ","ERROR");
			return false;
		}

		// clear html code
		while (strpos($html,'  ')!==false) $html = str_replace('  ',' ',$html);
		while (strpos($html,' =')!==false) $html = str_replace(' =','=',$html);
		while (strpos($html,'= ')!==false) $html = str_replace('= ','=',$html);

		// parse action attr
		$s = "action=\"";
		$p = strpos($html, $s);
		if ($p === false) return false;
		$p += strlen($s);
		$p1 = strpos($html, '"', $p + 1);
		$action = substr($html, $p, $p1 - $p);

		// get hidden fields
		$fields = [
			"actionID",
			"attachmentAction",
			"compose_formToken",
			"compose_requestToken",
			"composeCache",
			"mailbox",
			"oldrtemode",
			"rtemode",
			"user",
			"MAX_FILE_SIZE",
			"page",
			"start",
			"popup"];

		$values = [];
		foreach($fields as $field)
		{
			//echo "searching hidden field: $field\n";
			$s = '<input type="hidden" name="'. $field;
			$p = strpos($html, $s);
			if ($p!==false)
			{
				$s = 'value="';
				$p =strpos($html, $s, $p);
				if ($p!==false)
				{
					$p += strlen($s);
					$p1 = strpos($html, '"', $p + 1);
					$value = substr($html, $p, $p1 - $p);
					if ($value== '" id=') $value = "";
					$values[$field] = $value;
				}
			}
		}

		$data = $values;

		// prepare data
		$url = $this->baseUrl.substr($action, 1);

		$data['to'] = $to;
		$data['cc'] = $cc;
		$data['bcc'] = $bcc;
		$data['subject'] = $subject;
		$data['priority'] = $priority;
		$data['message'] = $body;
		$data['btn_send_message'] = 'Enviar mensaje';

		if ($attachment !== false)
		{
			if ( ! isset($attachment['contentType'])) $attachment['contentType'] = 'application/octet-stream';
			if ( ! isset($attachment['fileName'])) $attachment['fileName'] = 'attachment-'.uniqid();
			if ( ! isset($attachment['content'])) $attachment['content'] = '';

			$data['upload_1'] = [
				'value' => $attachment['content'],
				'filename' => $attachment['fileName'],
				'contentType' => $attachment['contentType']
			];

			$data['link_attachments'] = 0;
		}

		// build multipart
		$boundary = '---------------------------'.uniqid();
		$body = $this->buildMultipart($data, $boundary);

		$this->setHttpHeaders([
			"Content-Type" => "multipart/form-data; boundary=$boundary",
			"Content-Length" => "". (strlen($body) - 1)
		]);

		curl_setopt($this->client, CURLOPT_URL, $url);
		curl_setopt($this->client, CURLOPT_POST, true);
		curl_setopt($this->client, CURLOPT_POSTFIELDS, $body);

		// send
		$response = curl_exec($this->client);

		if (curl_errno($this->client) !== 0)
		{
			$utils = new Utils();
			$utils->createAlert("[NautaClient] Error when post multipart form: ".curl_error($this->client)." (to: $to, subject: $subject)", "ERROR");
			return false;
		}

		return $response;
	}

	/**
	 * Logout from webmail
	 *
	 * @return bool|mixed
	 */
	public function logout()
	{
		if (!is_null($this->client))
		{
			curl_setopt($this->client, CURLOPT_URL, "{$this->baseUrl}imp/login.php?horde_logout_token={$this->logoutToken}");
			$response = curl_exec($this->client);
			curl_close($this->client);
			return $response;
		}

		return false;
	}

	/**
	 * Build multipart form data
	 *
	 * @param $fields
	 * @param $boundary
	 * @return string
	 */
	function buildMultipart($fields, $boundary)
	{
		$retval = '';
		foreach($fields as $key => $value){
			$filename = false;
			$contentType = false;

			if (is_array($value))
			{
				$filename = $value['filename'];
				$contentType = $value['contentType'];
				$value = $value['value'];
			}

			$retval .= "--$boundary\nContent-Disposition: form-data; name=\"$key\"".($filename!==false?"; filename=\"{$filename}\"":"").($contentType!==false?"\nContent-type: {$contentType}":"")."\n\n$value\n";
		}
		$retval .= "--$boundary--";
		return $retval;
	}

	/**
	 * Breaks an image captcha using human labor. Takes ~15sec to return
	 *
	 * @author salvipascual
	 * @param String $image
	 * @return String
	 */
	function breakCaptcha($image)
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
		if ( ! $api->createTask()) {
			$ret = new stdClass();
			$ret->code = "500";
			$ret->message = "API v2 send failed: ".$api->getErrorMessage();
			return $ret;
		}

		// wait for results
		$taskId = $api->getTaskId();
		if ( ! $api->waitForResult()) {
			$ret = new stdClass();
			$ret->code = "510";
			$ret->message = "Could not solve captcha: ".$api->getErrorMessage();
			return $ret;
		}

		// return the solution
		$ret = new stdClass();
		$ret->code = "200";
		$ret->message = $api->getTaskSolution();
		return $ret;
	}
}
