<?php

use Phalcon\Mvc\Controller;
  
class AdminController extends Controller
{
    public function indexAction()
    {
		//Include simple.phtml Layout
		$this->view->setLayout('simple');  
    }
    
    public function raffleAction() 
    {
       $this->view->setLayout('simple'); 
    }
	
	public function adsAction() 
    {
       $this->view->setLayout('simple'); 
    }
}
