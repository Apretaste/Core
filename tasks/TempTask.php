<?php

// USEFUL TO SEND MASS EMAILS TO USERS WHEN NEEDED

class TempTask extends \Phalcon\Cli\Task
{
	public function mainAction()
	{
		$utils = new Utils();
		$render = new Render();
		$wwwroot = $this->di->get('path')['root'];

		$people = array();

		echo "\nSTART\n";
		foreach ($people as $to)
		{
			echo "$to\n";

			// run the request and get the service and responses
			$ret = $utils->runRequest($to, "app", "", array());
			$service = $ret->service;
			$response = $ret->responses[0];

			// create a new render
			$html = $render->renderHTML($service, $response);

			$email = new Email();
			$email->to = $service->request->email;
			$email->subject = $response->subject;
			$email->body = $html;
			$output = $email->sendEmailViaMailgun();

			// saving the log
			$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/temp.log");
			$logger->log($to);
			$logger->close();
		}
		echo "\nEND\n";
	}
}
