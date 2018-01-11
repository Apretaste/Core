<?php

use Phalcon\Mvc\Controller;

class MarketController extends Controller
{
	// do not let anonymous users pass
	public function initialize()
	{
		$security = new Security();
		$security->enforceLogin();
		$this->view->setLayout("manage");
	}

	/**
	 * Market
	 * @author kuma
	 */
	public function indexAction()
	{
		// redirect to the main page
		$this->response->redirect("market/market");
	}

	/**
	 * Market
	 * @author kuma
	 */
	public function marketAction()
	{
		// get products
		$connection = new Connection();
		$products = $connection->query("SELECT * FROM _tienda_products ORDER BY name");

		// add buttons
		$this->view->buttons = [["caption"=>"New product", "href"=>"#", "modal"=>"newProductForm", "icon"=>"plus"]];

		// send info to the view
		$this->view->products = $products;
		$this->view->title = "Products in the market";
	}

	/**
	 * New product
	 * @author kuma
	 */
	public function marketNewProductAction()
	{
		if($this->request->isPost())
		{
			// generate code
			$code = substr(date("Ymdhi"), 2);

			// get data from post
			$name = $this->request->getPost('edtName');
			$description = $this->request->getPost('edtDesc');
			$category = $this->request->getPost('edtCategory');
			$price = $this->request->getPost('edtPrice') * 1;
			$shipping_price = $this->request->getPost('edtShippingPrice') * 1;
			$credits = $this->request->getPost('edtCredits') * 1;
			$agency = $this->request->getPost('edtAgency');
			$owner = $this->request->getPost('edtOwner');

			// add product
			$connection = new Connection();
			$connection->query("
				INSERT INTO _tienda_products (code, name, description, category, price, shipping_price, credits, agency, owner)
				VALUES ('$code', '$name', '$description','$category','$price','$shipping_price','$credits','$agency','$owner')");

			// add inventory
			$connection->query("INSERT INTO inventory (code, price, name, seller, service, active) VALUES ('$code','$credits','$name','$owner','MERCADO',0)");

			// redirect to edit product page
			$this->view->code = $code;
			return $this->dispatcher->forward(array("controller"=> "market", "action" => "marketDetail"));
		}
	}

	/**
	 * Update product
	 * @author kuma
	 */
	public function marketUpdateAction()
	{
		// getting ad's id
		$url = $_GET['_url'];
		$code =  explode("/",$url);
		$code = $code[count($code)-1];

		if ($this->request->isPost())
		{
			$name = $this->request->getPost('edtName');
			$description = $this->request->getPost('edtDesc');
			$category = $this->request->getPost('edtCategory');
			$price = $this->request->getPost('edtPrice') * 1;
			$shipping_price = $this->request->getPost('edtShippingPrice') * 1;
			$credits = $this->request->getPost('edtCredits') * 1;
			$agency = $this->request->getPost('edtAgency');
			$owner = $this->request->getPost('edtOwner');

			$connection = new Connection();
			$connection->query("
				UPDATE _tienda_products SET name = '$name',
					description = '$description',
					category = '$category',
					price = '$price',
					shipping_price = '$shipping_price',
					credits = '$credits',
					agency = '$agency',
					owner = '$owner'
				WHERE code = '$code'");

			// update inventory
			$connection->query("
				UPDATE inventory
				SET name = '$name',
					price = '$credits',
					seller = '$owner'
				WHERE code = '$code'");

			$this->view->message = 'The product was updated';
			$this->view->message_type = "success";
			$this->view->code = $code;
			return $this->dispatcher->forward(array("controller"=> "market", "action" => "marketDetail"));
		}
	}

	/**
	 * Edit product
	 * @author kuma
	 */
	public function marketDetailAction()
	{
		// getting ad's id
		$url = $_GET['_url'];
		$code =  explode("/",$url);
		$code = $code[count($code)-1];

		// get product details
		$connection = new Connection();
		$product = $connection->query("SELECT * FROM _tienda_products WHERE code='$code'");
		if(empty($product)) return false;

		// send items to the view
		$this->view->product = $product[0];
		$this->view->title = "{$product[0]->code}: {$product[0]->name}";
	}

	/**
	 * Set product's picture
	 * @author kuma
	 */
	public function marketPictureAction()
	{
		// getting ad's id
		$url = $_GET['_url'];
		$code =  explode("/",$url);
		$code = $code[count($code)-1];

		$wwwroot = $this->di->get('path')['root'];
		$fname = "$wwwroot/public/products/$code.jpg";
		copy($_FILES['file_data']['tmp_name'], $fname);

		echo '{}';
		$this->view->disable();
	}

	/**
	 * Delete product's picture
	 * @author kuma
	 */
	public function marketPictureDeleteAction()
	{
		$code = $this->request->getPost('code');
		$wwwroot = $this->di->get('path')['root'];

		$fn = "$wwwroot/public/products/$code";
		if (file_exists($fn)) unlink($fn);

		echo '{result: true}';
		$this->view->disable();
	}

	/**
	 * Delete product
	 * @author kuma
	 */
	public function marketDeleteAction()
	{
		// getting ad's id
		$url = $_GET['_url'];
		$code =  explode("/",$url);
		$code = $code[count($code)-1];

		$connection = new Connection();
		$wwwroot = $this->di->get('path')['root'];

		// delete record from database
		$connection->query("DELETE FROM _tienda_products WHERE code = '$code'");

		// delete record from inventory
		$connection->query("DELETE FROM inventory WHERE code = '$code'");

		// delete related picture
		$fn = "$wwwroot/public/products/$code";
		if (file_exists($fn)) unlink($fn);

		$this->view->message = "The product $code was deleted";
		$this->view->message_type = "success";
		return $this->dispatcher->forward(array("controller"=> "market", "action" => "market"));
	}

	/**
	 * Toggle product's activation
	 * @author kuma
	 */
	public function marketToggleActivationAction()
	{
		$connection = new Connection();
		$code = $this->request->getPost('code');
		$product = $connection->query("SELECT active FROM _tienda_products WHERE code = '$code'");

		if (is_array($product))
		{
			$product = $product[0];
			$active = $product->active;
			$toggle = '1';
			if ($active == '1')
				$toggle = '0';

			$connection->query("UPDATE _tienda_products SET active = '$toggle' WHERE code = '$code'");
			$connection->query("UPDATE inventory SET active = '$toggle' WHERE code = '$code'");

			echo $toggle;
			$this->view->disable();
		}
	}

	/**
	 * Retrieve transfer into market's orders
	 * @author kuma
	 */
	private function updateMarketOrders()
	{
		$connection = new Connection();
		$connection->query("
			INSERT INTO _tienda_orders (id, product, email, inserted_date)
			SELECT id, inventory_code, sender, transfer_time
			FROM transfer INNER JOIN inventory on transfer.inventory_code = inventory.code
			WHERE inventory.service = 'MERCADO' AND transfer.transfered = '1'
			AND NOT EXISTS (SELECT * FROM _tienda_orders WHERE _tienda_orders.id = transfer.id)");
	}

	/**
	 * Manage market's orders
	 * @author kuma
	 */
	public function marketOrdersAction()
	{
		$this->updateMarketOrders();

		// get opened orders
		$connection = new Connection();
		$orders = $connection->query("SELECT *, (SELECT name FROM _tienda_products WHERE code = _tienda_orders.product) as product_name FROM _tienda_orders WHERE received=0");

		foreach ($orders as $k => $v)
		{
			$orders[$k]->ready = false;
			if (trim($v->ci) !== '' && trim($v->name) !== '' && trim($v->address) !== '' && trim($v->province) !== '' ) $orders[$k]->ready = true;
		}

		$this->view->orders = $orders;
		$this->view->title = "Orders in the market";
	}

	/**
	 * Edit product's destination data
	 *  @author kuma
	 */
	public function marketDestinationAction()
	{
		// getting ad's id
		$url = $_GET['_url'];
		$id =  explode("/",$url);
		$id = $id[count($id)-1];

		$connection = new Connection();

		if ($this->request->isPost())
		{
			$ci = $this->request->getPost('edtCI');
			$name = $this->request->getPost('edtName');
			$address = $this->request->getPost('edtAddress');
			$province = $this->request->getPost('edtProvince');
			$phone = $this->request->getPost('edtPhone');

			$connection->query("UPDATE _tienda_orders SET ci = '$ci', name = '$name', address = '$address', province = '$province', phone = '$phone' WHERE id='$id'");
		}

		$order = $connection->query("SELECT * FROM _tienda_orders WHERE id = '$id'");

		if (is_array($order))
		{
			$order = $order[0];
			$order->ready = false;
			if (trim($order->ci) !== '' && trim($order->name) !== '' && trim($order->address) !== '' && trim($order->province) !== '' )
			$order->ready = true;

			$product = $connection->query("SELECT * FROM _tienda_products WHERE code = '$order->product'");

			if (is_array($product))
			{
				$product = $product[0];
				$wwwroot = $this->di->get('path')['root'];
				$product->image = file_exists("$wwwroot/public/products/{$product->code}.jpg");

				$provinces = array('PINAR_DEL_RIO','LA_HABANA','ARTEMISA','MAYABEQUE','MATANZAS','VILLA_CLARA','CIENFUEGOS','SANCTI_SPIRITUS','CIEGO_DE_AVILA','CAMAGUEY','LAS_TUNAS','HOLGUIN','GRANMA','SANTIAGO_DE_CUBA','GUANTANAMO','ISLA_DE_LA_JUVENTUD');

				$this->view->provinces = $provinces;
				$this->view->product = $product;
				$this->view->order = $order;
				$this->view->title = "Product's destination";
			}
		}
	}

	/**
	 * Edit product's destination data
	 *  @author kuma
	 */
	public function marketOrderReceivedAction()
	{
		// getting ad's id
		$url = $_GET['_url'];
		$id =  explode("/",$url);
		$id = $id[count($id)-1];
		$id = $id * 1;

		$wwwroot = $this->di->get('path')['root'];

		$connection = new Connection();
		$connection->query("UPDATE _tienda_orders SET received = 1 WHERE id = $id");

		$this->view->message = "Order <a href=\"/market/marketDestination/$id\">{$id}</a> was set as sent";
		$this->view->message_type = "success";
		return $this->dispatcher->forward(array("controller"=> "market", "action" => "marketOrders"));
	}

	/**
	 * Market statistics
	 * @author kuma
	 */
	public function marketStatsAction()
	{
		$this->updateMarketOrders();

		$this->view->maxCredit = 0;
		$this->view->avgCredit = 0;
		$this->view->sumCredit = 0;
		$this->view->minCredit = 0;
		$this->view->monthlySells = "";
		$this->view->sellsByProduct = "";
		$this->view->title = "Market stats";
	}
}
