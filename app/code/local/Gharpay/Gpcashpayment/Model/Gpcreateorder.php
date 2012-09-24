<?php
/*

// Mike Prince, 27 June 2012
// New isAvailable function restricts Gharpay to Indian billing addresses only.
// NB: Ideally countries should be defined in configuration rather than hard-coded, and should be further restricted on pincode.

// Mike Prince, 27 June 2012
// Define form block for checkout page.

// Mike Prince, 29 June 2012
// Change error message for Gharpay not available in area.

*/

include_once 'app/Mage.php';
require_once(Mage::getBaseDir('lib').DIRECTORY_SEPARATOR.'Gharpay'.DIRECTORY_SEPARATOR.'GharpayAPI.php');
require_once(Mage::getModuleDir('Model', 'Gharpay_Dbconns').DIRECTORY_SEPARATOR.'Model'.DIRECTORY_SEPARATOR.'Gharpayorders.php');
require_once(Mage::getModuleDir('Model', 'Gharpay_Dbconns').DIRECTORY_SEPARATOR.'Model'.DIRECTORY_SEPARATOR.'Gharpaypropvalue.php');
require_once(Mage::getModuleDir('Model', 'Gharpay_Gharpaypushnotification').DIRECTORY_SEPARATOR.'Model'.DIRECTORY_SEPARATOR.'Pnotif.php');

class Gharpay_Gpcashpayment_Model_Gpcreateorder extends Mage_Payment_Model_Method_Abstract
{
	protected $_code = 'gpcashpayment';
	protected $_canAuthorize = true;
	protected $_canUseCheckout = true;
	protected $_canFetchTransactionInfo     = true;
	protected $_isGateway                   = true;
	protected $_canUseInternal = true;
	protected $_canVoid    = true;
	protected $_canCancel = true;

    protected $_formBlockType = 'gpcashpayment/form';
    //protected $_infoBlockType = 'gpcashpayment/info';

	public function canCancel()
	{
		return $this->_canCancel;
	}

	public function validate()
	{	
		Mage::Log("Validate Function of Payment Method called");
		$title = Mage::getStoreConfig('payment/gpcashpayment/title',Mage::app()->getStore());
		$paymentInfo = $this->getInfoInstance();
		if ($paymentInfo instanceof Mage_Sales_Model_Order_Payment) {
			$postCode = $paymentInfo->getOrder()->getBillingAddress()->getPostcode();
		}
		else {
			$postCode = $paymentInfo->getQuote()->getBillingAddress()->getPostcode();
		}
		if (!$this->canUseForPostCode($postCode)) {
/*
			Mage::throwException($this->_getHelper()->__('Sorry ! '.$title.' Service is not available in your area'));
*/
			Mage::throwException($this->_getHelper()->__('Apologies, but we are unable to offer cash payment as an option for your pincode area. Please choose a different payment option.'));
		}
		return $this;
	}

	public function canUseForPostCode($postCode)
	{
		Mage::Log("canUseForPostCode of payment method called");
		$uri = Mage::getStoreConfig('payment/gpcashpayment/gharpay_uri',Mage::app()->getStore());
		$username = Mage::getStoreConfig('payment/gpcashpayment/username',Mage::app()->getStore());
		$password = Mage::getStoreConfig('payment/gpcashpayment/password',Mage::app()->getStore());
		$gpAPI = new GharpayAPI();
		$gpAPI->setUsername($username);
		$gpAPI->setPassword($password);
		$gpAPI->setURL($uri);
		Mage::app(); //for autoloading:)
		try {
		$response = $gpAPI->isPincodePresent($postCode);
		return $response;
		}
		catch (Exception $e){
			Mage::throwException($this->_getHelper()->__($e->getMessage()));
		}
	}
	
	public function isAvailable($quote=null)
    {
        if (!parent::isAvailable($quote)) {
            return false;
        }
        if (!is_null($quote)) {
            $country = $quote->getBillingAddress()->getCountry();
            if (!($country=="IN")) {
                return false;
            }
		}
       return true;
    }
	
	public function authorize(Varien_Object $payment,$amount)
	{
		Mage::Log("authorize function is called");
		$uri = Mage::getStoreConfig('payment/gpcashpayment/gharpay_uri',Mage::app()->getStore());
		$username = Mage::getStoreConfig('payment/gpcashpayment/username',Mage::app()->getStore());
		$password = Mage::getStoreConfig('payment/gpcashpayment/password',Mage::app()->getStore());
		$gpAPI = new GharpayAPI();
		$gpAPI->setUsername($username);
		$gpAPI->setPassword($password);
		$gpAPI->setURL($uri);
	
		$date=new DateTime();
		$paymentInfo = $this->getInfoInstance();
		$order = $paymentInfo->getOrder();
		$info=$order->getBillingAddress();
		
		$customerDetails = array(
				"address"=>$info->getStreetFull().','.$info->getRegion().','.$info->getCity().','.$info->getCountry(),
				"contactNo"=>$info->getTelephone(),
				"email"=>$info->getEmail(),
				"firstName"=>$info->getFirstname(),
				"lastName"=>$info->getLastname(),
		);
		
		$productDetails=array();
		$i=0;
		foreach ($order->getAllItems() as $item) {
			$productDetails[$i] = array(
					"productID"  => $item->getProductId(),
					"productQuantity"=> $item->getQtyOrdered(),
					"unitCost" => $item->getPrice(),
					"productDescription"=>$item->getName()
			);			
			$i++;
		}

		// Normalise pincode to avoid 7 digit codes with space being passed to GharPay.
		$orderDetails = array(
				"pincode"=>$gpAPI->normalisePincode($order->getBillingAddress()->getPostcode()),
				"clientOrderID"=>$order->getIncrementId(),
				"deliveryDate"=>$date->format('d-m-Y'),
				"orderAmount"=>$order->getBaseGrandTotal()
		);
		$result = null;
		try {
			$result = $gpAPI->createOrder($customerDetails, $orderDetails,$productDetails);
		}
		catch (Exception $e) {
			Mage::throwException($this->_getHelper()->__($e->getMessage()));
		}
		
		$clientId=$result['clientOrderId'];
		if($clientId==$order->getIncrementId())
		{
			$gharpayId=$result['gharpayOrderId'];
			$gharpayorders= new Gharpay_Dbconns_Model_Gharpayorders();
			$gharpayorders->setGharpayOrderId($gharpayId);
			$gharpayorders->setClientOrderId($clientId);
			$gharpayorders->setCreatedAt($date->format('Y-m-d h:i:s'));
			$gharpayorders->save();
			$goid = $gharpayorders->getId();
			$gharpaypropvalue= new Gharpay_Dbconns_Model_Gharpaypropvalue();
			$gharpaypropvalue->setGharpayId($goid);
			$gharpaypropvalue->setPropertyId(1);
			$gharpaypropvalue->setPropValue('Pending');
			$gharpaypropvalue->setCreatedAt($date->format('Y-m-d h:i:s'));
			$gharpaypropvalue->save();
			$gp = new Gharpay_Gharpaypushnotification_Model_Pnotif();
			$status = 'Pending';
			$coid=$order->getIncrementId();
			$gp->addStatusToOrderGrid($coid,$status);
			$payment->setTransactionId($gharpayId);
		}
		return $this;		
	}		
}