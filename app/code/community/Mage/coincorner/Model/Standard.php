<?php
class Mage_CoinCorner_Model_Standard extends Mage_Payment_Model_Method_Abstract {
	protected $_code = 'coincorner';
	
	protected $_isInitializeNeeded      = true;
	protected $_canUseInternal          = true;
	protected $_canUseForMultishipping  = false;
	
	public function getOrderPlaceRedirectUrl() {
		return Mage::getUrl('coincorner/payment/redirect', array('_secure' => true));
	}
}
?>