<?php 

use Phalcon\Mvc\Controller;

class InvitarController extends Controller
{
	public function indexAction()
	{
		$this->view->wwwhttp = $this->di->get('path')['http'];
		$this->view->wwwroot = $this->di->get('path')['root'];
		
		if ($this->request->isPost()){
			// proccess invitation
			
			$this->view->invitation_success = true;
			
			return true;
		}
		
		// show the form
		$this->view->title = "Invite a friend to Apretaste!";
	}
}