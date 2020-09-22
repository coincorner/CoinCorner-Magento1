<?php

final class order_status {
    const pending              = -99;
    const refunded             =  -5;
    const pending_refund       =  -4;
    const expired              =  -2;
    const cancelled            =  -1;
    const awaiting_payment     =   0;
    const pending_confirmation =   1;
    const complete             =   2;
}

class Mage_CoinCorner_PaymentController extends Mage_Core_Controller_Front_Action {
	// The redirect action is triggered when someone places an order
	public function redirectAction() {
		$this->loadLayout();
        $block = $this->getLayout()->createBlock('Mage_Core_Block_Template','coincorner',array('template' => 'coincorner/redirect.phtml'));
		$this->getLayout()->getBlock('content')->append($block);
        $this->renderLayout();
	}
	
	// The response action is triggered when your gateway sends back a response after processing the customer's payment
	public function responseAction() {
		Mage::log('hit the response action');
		
		$orderId = Mage::app()->getRequest()->getParam('OrderId');
		$method = $_SERVER['REQUEST_METHOD'];
		$config = Mage::getStoreConfig('payment/coincorner');

		if ($method == 'GET' && is_null($orderId)) {
			Mage_Core_Controller_Varien_Action::_redirect('');
			return;
		}
		
		if ($method == 'GET') {
			$order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
			$order->sendNewOrderEmail();
			$order->setEmailSent(true);
			
			$order->setState($config['invoice_paid'], true)->save();
		
			Mage::getSingleton('checkout/session')->unsQuoteId();
			
			Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/success', array('_secure'=>true));
			return;
		}
		
		// Already checked for get request
		if ($method != 'POST') {
			return;
		}
		
		$input = json_decode(file_get_contents('php://input'), true);

		$nonce = (int)microtime(true);

		$default_params = array(
			'OrderId' => $input['OrderId'],
			'Nonce' => $nonce,
			'APIKey' => $config['api_public'],
			'Signature' => strtolower(hash_hmac('sha256', $nonce . $config['api_id'] . $config['api_public'] , $config['api_secret'])),
		);

		$curl = curl_init();

		$curl_options = array(
			CURLOPT_RETURNTRANSFER => 1, 
			CURLOPT_URL => 'https://checkout.coincorner.com/api/GetOrder',
		);

		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array());

		$headers[] = 'Content-Type: application/x-www-form-urlencoded';

		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($default_params));

		curl_setopt_array($curl, $curl_options);
		$resp = json_decode(curl_exec($curl), true);
		curl_close($curl);

		switch ($resp['OrderStatus']) {
			case order_status::cancelled:
				$status = $config['invoice_canceled'];
				break;
		
			case order_status::complete:
				$status = $config['invoice_paid'];
				break;
		
			case order_status::refunded: 
				$status = $config['invoice_refunded'];
				break;
				
			case order_status::expired:
				$status = $config['invoice_expired'];
				break;
				
			default:
				$status = $config['invoice_invalid'];
				break;
		}

		try {
			$order = Mage::getModel('sales/order')->loadByIncrementId($input['OrderId']);
			$order->setState($status, true)->save();
		}
		catch (Exception $e) {
			echo("Error setting order state $e");
		}
	}
	
	// The cancel action is triggered when an order is to be cancelled
	public function cancelAction() {
		$this->_redirect('/');
	}
}