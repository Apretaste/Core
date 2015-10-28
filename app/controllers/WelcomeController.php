<?php

use Phalcon\Mvc\Controller;

class WelcomeController extends Controller
{
	public function indexAction()
	{
		$this->view->wwwhttp = $this->di->get('path')['http'];
		$this->view->wwwroot = $this->di->get('path')['root'];
		$this->view->stripePushibleKey = $this->di->get('config')['stripe']['pushible'];

		$this->view->pick("index/welcome");
	}

	public function payAction()
	{
		// See your keys here https://dashboard.stripe.com/account/apikeys
		$stripeSecretKey = $this->di->get('config')['stripe']['secret'];
		\Stripe\Stripe::setApiKey($stripeSecretKey); // stored on setup.php

		// Get the credit card details submitted by the form
		$token = $_POST['stripeToken'];
		$amount = $_POST['amount'];
		$email = $_POST['email'];

		// Create the charge on Stripe's servers - this will charge the user's card
		try {
			$charge = \Stripe\Charge::create(array(
				"amount" => $amount, // amount in cents, again
				"currency" => "usd",
				"source" => $token,
				"description" => "Example charge")
			);
		} catch(\Stripe\Error\Card $e) {
			// The card has been declined
			die("Sorry, your card was declined. Please go back and try again.");
		}

		// get the path to the www folder
		$wwwroot = $this->di->get('path')['root'];

		// get the key from the config
		$mailerLiteKey = $this->di->get('config')['mailerlite']['key'];

		// adding the new Donor to the list
		include "$wwwroot/lib/mailerlite-api-php-v1/ML_Subscribers.php";
		$ML_Subscribers = new ML_Subscribers($mailerLiteKey);
		$subscriber = array('email' => $email);
		$result = $ML_Subscribers->setId("2225307")->add($subscriber); // adding to Donors list

		// send email with the donor's info
		$today = date('l jS \of F Y h:i:s A');
		$message = "Date: $today\r\nDonor: $email\r\nAmount: $amount";
		$email = new Email();
		$email->sendEmail("salvi.pascual@gmail.com", "Apretaste: New donation", $message);

		// Send to the ThankYou page
		$dollarsAmount = $amount/100;
		return $this->response->redirect("welcome/thankyou&email=$email&amount=$dollarsAmount");
	}

	public function thankyouAction()
	{
		$amount = $_GET['amount'];
		$email = $_GET['email'];

		// open the view
		$this->view->amount = $amount;
		$this->view->email = $email;
		$this->view->wwwroot = $this->di->get('path')['root'];
		$this->view->wwwhttp = $this->di->get('path')['http'];
		$this->view->pick("index/thankyou");
	}
}