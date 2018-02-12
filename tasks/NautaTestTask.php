<?php

class NautaTestTask extends \Phalcon\Cli\Task
{
	public function mainAction()
	{

		$di      = \Phalcon\DI\FactoryDefault::getDefault();

		echo "NautaClient CLI test\n";

		$user = $this->input('User');
		$pass = $this->input('Password');

		$proxy_host = $this->input('Proxy host');
		$proxy_port = $this->input('Proxy port');

		$client = new NautaClient($user, $pass);

		if( ! empty($proxy_host)) $client->setProxy("$proxy_host:$proxy_port");

		if ($client->checkLogin()) echo "Login keep alive! \n";

		$doLogin = $this->input("Login (Y/N)");

		if(strtolower($doLogin) == "y")
		{
			echo "Login...\n";

			$offline = strtolower($this->input("Offline (Y/N)"));

			do
			{
				$login = $client->login($offline == 'y');

				if($login == false)
				{
					$continue = $this->input("Login fail. Continue(Y/N)");
					if(strtolower($continue) == "y") continue;
				}
			} while($login == false);
		}
		else $login = true;

		if($login)
		{
			$to      = $this->input("To");
			$subject = $this->input("Subject");
			$body    = $this->input("Body");
			$attach  = $this->input("Path to attachment");

			$attachment = [];

			if($attach != '') $attachment = [
				"content" => file_get_contents($attach),
				"filename" => pathinfo($attach, PATHINFO_FILENAME)
			];

			echo "Sending email...\n";

			echo $client->send($to, $subject, $body, $attach);
			echo "\n";
		}

		$logout = $this->input("Logout (Y/n):");
		if(strtolower($logout) == "y") $client->logout();
	}

	private function input($message = "")
	{
		$cli = fopen("php://stdin", "r");
		echo "$message: ";
		$text = trim(str_replace(["\r", "\n"], "", fgets($cli)));
		echo "\n";
		fclose($cli);

		return $text;
	}
}