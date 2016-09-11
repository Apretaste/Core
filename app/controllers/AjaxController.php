<?php 


use Phalcon\Mvc\Controller;

class AjaxController extends Controller{
	
	public function getMarketProductAction()
	{
		$utils = new Utils();
		$code = $this->request->getPost('code');
		$code = $utils->clearStr($code, "1234567890");
		$connection = new Connection();
		$product = $connection->deepQuery("SELECT * FROM _tienda_products WHERE code = '$code';");
		$wwwroot = $this->di->get('path')['root'];
		
		if (is_array($product))
		{
			$product = $product[0];
			$product->image = false;
			
			if (file_exists("$wwwroot/public/products/$code"))
				$product->image = true;
			
			$product->price_friendly = '$'.number_format($product->price,2);
			$product->shipping_price_friendly = '$'.number_format($product->shipping_price,2);
			$product->credits_friendly = '$'.number_format($product->credits,2);
				
			echo "{product:".json_encode($product)."}";
		}
		else 
			echo "{product: false}";
		
		$this->view->disable();
	}
	
}