<?php

require_once 'lib/Linfo/standalone_autoload.php';

class LinfoTask extends \Phalcon\Cli\Task
{
	public function mainAction()
	{
		$linfo = new \Linfo\Linfo;
		$parser = $linfo->getParser();
		$hd = $parser->getMounts();

		$alert = false;
		foreach($hd as $mount)
		{
			if ($mount['free_percent'] < 10 && $mount['mount'] == "/")
			{
				echo "FREE SPACE OF ".$mount['mount'].' = '.$mount['free_percent']."\n";
				$alert = true;
				break;
			}
		}

		if ($alert)
		{
			// send file as email attachment
			$email = new Email();
			$email->to = 'team@apretaste.com';
			$email->subject = "Alert: FREE SPACE WARNING";

			$body = date("Y-m-d h:i:s")."\n";

			foreach($hd as $mount)
			{
				$body .= "FREE SPACE OF ".$mount['label'].' = '.$mount['free_percent']."\n";
			}

			$email->body = $body;
			$email->send();

			$utils = new Utils();
			$utils->createAlert($body);
		}

		//var_dump($hd[0]['partitions']);
		//var_dump($hd); // and a whole lot more
	}
}