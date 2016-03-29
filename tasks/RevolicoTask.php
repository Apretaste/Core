<?php

use GuzzleHttp\Stream\Utils;
use Goutte\Client;

class RevolicoTask extends \Phalcon\Cli\Task
{
    public function mainAction()
    {
        echo "\nThis is Revolico task and the default action \n";
    }
}
