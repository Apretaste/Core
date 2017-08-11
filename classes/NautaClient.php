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

	private $baseUrl = "http://webmail.nauta.cu/";

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
		$this->user = $user;
		$this->pass = $pass;

		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];

		$this->client = curl_init();

		curl_setopt($this->client, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($this->client, CURLOPT_RETURNTRANSFER, 1);

		@mkdir ("$wwwroot/temp/nautaclient");

		$cookieFile = $wwwroot."/temp/nautaclient/{$this->user}.cookie";
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
	public function login($user = null, $pass = null)
	{
		if (is_null($user))
			$user = $this->user;

		if (is_null($pass))
			$pass = $this->pass;

		curl_setopt($this->client, CURLOPT_URL, "{$this->baseUrl}horde/login.php");
        curl_setopt($this->client, CURLOPT_POSTFIELDS, "app=&login_post=1&url=&anchor_string=&ie_version=&horde_user=".urlencode($user)."&horde_pass=".urlencode($pass)."&horde_select_view=mobile&new_lang=en_US");

		$response = curl_exec($this->client);

		if ($response === false)
			return false;

		$this->logoutToken = "";
		$this->composeToken = "";

		// parse logout token
		$p = strpos($response, "horde_logout_token");
		if ($p !== false)
		{
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
		if ($p1 !== false && $p2 !== false)
			$this->composeToken = substr($response, $p1 + strlen($tk1), $p2 - ($p1 + strlen($tk1)));

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
		curl_setopt($this->client, CURLOPT_URL, "{$this->baseUrl}horde/imp/compose.php?u={$this->composeToken}");
		$html = curl_exec($this->client);

		// parse action attr
		$s  = "action=\"";
		$p = strpos($html, $s);
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
			curl_setopt($this->client, CURLOPT_URL, "{$this->baseUrl}horde/imp/login.php?horde_logout_token={$this->logoutToken}");
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
	function buildMultipart($fields, $boundary){

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


}
