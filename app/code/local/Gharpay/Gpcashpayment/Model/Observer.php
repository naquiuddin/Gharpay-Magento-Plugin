<?php
require_once(Mage::getBaseDir('lib').DIRECTORY_SEPARATOR.'Gharpay'.DIRECTORY_SEPARATOR.'GharpayAPI.php');
require_once(Mage::getModuleDir('Model', 'Gharpay_Dbconns').DIRECTORY_SEPARATOR.'Model'.DIRECTORY_SEPARATOR.'Gharpayorders.php');
require_once(Mage::getModuleDir('Model', 'Gharpay_Gharpaypushnotification').DIRECTORY_SEPARATOR.'Model'.DIRECTORY_SEPARATOR.'Pnotif.php');
class Gharpay_Gpcashpayment_Model_Observer
{
	public function gharpayCancelOrder(Varien_Event_Observer $Observer)
	{
		Mage::Log("Inside gharpay cancel observer");
		$order = $Observer->getOrder();
		$coid=$Observer->getOrder()->getIncrementId();
		Mage::Log($coid);
		$go=new Gharpay_Dbconns_Model_Gharpayorders();
		$go= $go->getCollection();
		$go->addFieldToFilter('client_order_id',$coid)->getSelect();
		if($go->count())
		{
			Mage::Log("inside if loop gharpay cancel observer");
			$transId = $go->getFirstItem()->getData('gharpay_order_id');
			Mage::Log($transId);
			Mage::app();
			$uri = Mage::getStoreConfig('payment/gpcashpayment/gharpay_uri',Mage::app()->getStore());
			$username = Mage::getStoreConfig('payment/gpcashpayment/username',Mage::app()->getStore());
			$password = Mage::getStoreConfig('payment/gpcashpayment/password',Mage::app()->getStore());
			$gpAPI = new GharpayAPI();
			$gpAPI->setUsername($username);
			$gpAPI->setPassword($password);
			$gpAPI->setURL($uri);
			$result=array();
			try {
				$result = $gpAPI->cancelOrder($transId);
				if($result['result']=='true')
				{
					$gp = new Gharpay_Gharpaypushnotification_Model_Pnotif();
					$status='Cancelled by Client';
					$gp->addStatusToGharpayDb($transId,$status);
					$gp->addStatusToOrderGrid($coid,$status);
				}
			}
			catch (Exception $e){
				Mage::Log("inside catch block of cancel Observer");
				Mage::throwException(Mage::helper('adminhtml')->__($e->getCode().":  ".$e->getMessage()));
			}
		}
	}
}