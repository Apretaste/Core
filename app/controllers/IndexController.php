<?php

use Phalcon\Mvc\Controller;
  
class IndexController extends Controller
{
	public function indexAction()
	{
		//$this->dispatcher->forward(array("controller" => "index", "action" => "welcome"));
		//return $this->response->redirect("index/welcome");
	}
	
	/*public function welcomeAction()
	{
		
	}
	
	public function bienvenidoAction()
	{
		echo "Espanol";
	}*/
}
