<?php

class SocksTask extends \Phalcon\Cli\Task
{
	static $hosts = [];

	public function mainAction()
	{
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$www_root = $di->get('path')['root'];
		$configFile = "$www_root/configs/socks.json";

		$newproxies = [];

		if (file_exists($configFile)) {

			echo "Verifying current config....\n";

			$proxies = file_get_contents("$www_root/configs/socks.json");
			$proxies = json_decode($proxies);

			foreach ($proxies as $proxy) {
				if (is_object($proxy)) $proxy = get_object_vars($proxy);

				echo "Verifying current config host: {$proxy['host']}:{$proxy['port']} ...";

				$kk = new Krawler("http://example.com");
				$result = $kk->getRemoteContent("http://example.com", $info, [
					"host" => "{$proxy['host']}:{$proxy['port']}",
					"type" => CURLPROXY_SOCKS5
				]);

				if ($result !== false)
					$newproxies[] = $proxy;
			}
		}

		SocksTask::$hosts[] = $newproxies;

		echo "Searching for new proxies....\n";

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

		file_put_contents($configFile, json_encode(SocksTask::$hosts, JSON_PRETTY_PRINT));
	}
}