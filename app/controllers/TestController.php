<?php

use Phalcon\Mvc\Controller;
  
class TestController extends Controller
{
    public function indexAction()
    {
        $email = new Email();
        $email->sendEmail("salvi.pascual@gmail.com", "Hello Salvi", "Test me");
        
        echo "Email sent";
    }
}
