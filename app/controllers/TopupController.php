<?php

use Phalcon\Mvc\Controller;
use Gregwar\Captcha\CaptchaBuilder;
use Stripe\Stripe;
use Stripe\Charge;

class TopupController extends Controller
{
	/**
	 * Show topup page
	 *
	 * @author salvipascual
	 */
	public function indexAction()
	{
		$this->wwwhttp = $this->di->get('path')['http'];
	}

	/**
	 * Pays when submitted and show thank you message
	 *
	 * @author salvipascual
	 * */
	public function thanksAction()
	{
		// get the values from the post
		$email = $this->request->get('email');
		$credits = $this->request->get('credits');
		$token = $this->request->get('token');
		$captcha = $this->request->get('captcha');

		// validate user inputs
		$failEmail = empty($email) || !Utils::personExist($email);
		$failCredits = !is_numeric($credits) || $credits < 5 || $credits > 50;
		$failToken = empty($token);
		$failCaptcha = strtoupper($captcha) != strtoupper($this->session->get('phrase'));

		// do not let pass with wrong values
		if($failEmail || $failCredits || $failToken || $failCaptcha) {
			echo "Lo sentimos pero ha ocurrido un error procesando sus datos. Por favor valla atras y comience nuevamente.";
			return false;
		}

		// calculate amount to charge
		$amount = $credits * 1.07 * 100;
		$amountUSD = number_format(($amount/100), 2);
/*
		// confirm the payment
		Stripe::setApiKey("sk_test_4eC39HqLyjWDarjtT1zdp7dc");
		$charge = Charge::create([
			'amount' => $amount,
			'currency' => 'usd',
			'description' => "$credits creditos de Apretaste",
			'source' => $token,
		]);
*/
		// send confirmation email
		$sender = new Email();
		$sender->to = $email;
		$sender->subject = "Recibo de su compra";
		$sender->sendFromTemplate("purchaseThankYou.tpl", ["amount"=>$amountUSD,"credits"=>$credits,"date"=>date("F j, Y, g:i a")]);

		// save a record of the purchase
		Connection::query("INSERT INTO sales(email,credits,amount) VALUES ('$email','$credits','$amount')");

		// redirect to the thank you page
		$this->view->amount = $amountUSD;
		$this->view->credits = $credits;
	}

	/**
	 * Publish CAPTCHA image
	 *
	 * @author salvipascual
	 */
	public function captchaAction()
	{
		$builder = new CaptchaBuilder();
		$builder->build();

		$this->session->set('phrase', $builder->getPhrase());

		header('Content-type: image/jpeg');
		$builder->output();
		$this->view->disable();
	}

	/**
	 * Ajax call to check if the email is an Apretaste user
	 *
	 * @author salvipascual
	 * @async
	 */
	public function checkEmailAction()
	{
		$email = $this->request->get('text');
		$personId = Utils::personExist($email);
		echo $personId ? "true" : "false";
		$this->view->disable();
	}

	/**
	 * Ajax call to check if the captcha is ok
	 *
	 * @author salvipascual
	 * @async
	 */
	public function checkCaptchaAction()
	{
		$captcha = $this->request->get('text');
		echo (strtoupper($captcha) == strtoupper($this->session->get('phrase'))) ? "true" : "false";
		$this->view->disable();
	}
}
