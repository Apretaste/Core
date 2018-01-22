<?php

class SocksTask extends \Phalcon\Cli\Task
{
	static $hosts = [];

	public function mainAction()
	{
		$k = new Krawler("https://www.socks-proxy.net/");
		$k->filter("#proxylisttable")->filter("tr")->each(function ($item, $i) {
			$html = $item->html();
			if (stripos($html, 'Socks5') !== false) {
				$p = strpos($html, '</td>');
				if ($p !== false) {
					$ip = substr($html, 4, $p - 4);
					$p1 = stripos($html, '</td>', $p + 9);
					$port = substr($html, $p + 10, $p1 - $p - 10);

					echo "Verifying $ip:$port ...";

					$kk = new Krawler("http://example.com");
					$result = $kk->getRemoteContent("http://example.com", $info, [
						"host" => "$ip:$port",
						"type" => CURLPROXY_SOCKS5
					]);

					if ($result !== false) {
						SocksTask::$hosts[] = ["host" => $ip, "port" => intval($port)];
						echo "OK\n";
					} else {
						echo "FAIL\n";
					}
				}
			}
		});


		// get a path to the root folder
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];
		file_put_contents("$wwwroot/configs/socks.json", json_encode(SocksTask::$hosts, JSON_PRETTY_PRINT));
	}
}