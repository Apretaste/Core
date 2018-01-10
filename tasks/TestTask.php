<?php

/**
 * Survey Reminder Task
 * @author kuma
 */
class TestTask extends \Phalcon\Cli\Task
{
	public function mainAction()
	{
		$utils = new Utils();
		$utils->optimizeImage("temp/imagen.png",300, "", 79, 'webp', 'temp/imagen.webp');
	}
}