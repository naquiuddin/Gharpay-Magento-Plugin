<?php
include_once 'app/Mage.php';
require_once(Mage::getBaseDir('lib').DIRECTORY_SEPARATOR.'Gharpay'.DIRECTORY_SEPARATOR.'GharpayAPI.php');
require_once(Mage::getModuleDir('Model', 'Gharpay_Dbconns').DIRECTORY_SEPARATOR.'Model'.DIRECTORY_SEPARATOR.'Gharpayorders.php');
require_once(Mage::getModuleDir('Model', 'Gharpay_Dbconns').DIRECTORY_SEPARATOR.'Model'.DIRECTORY_SEPARATOR.'Gharpayproperty.php');
require_once(Mage::getModuleDir('Model', 'Gharpay_Dbconns').DIRECTORY_SEPARATOR.'Model'.DIRECTORY_SEPARATOR.'Gharpaypropvalue.php');

define('GHARPAY_STATUS','Gharpay Order Status');
class Gharpay_Gharpaypushnotification_Model_Pnotif extends Mage_Core_Model_Abstract
{
    
    public function viewOrderStatus($gharpayOrderId)
    {
    	Mage::Log("called viewOrderStatus of push notification");
        $uri = Mage::getStoreConfig('payment/gpcashpayment/gharpay_uri',Mage::app()->getStore());
		$username = Mage::getStoreConfig('payment/gpcashpayment/username',Mage::app()->getStore());
		$password = Mage::getStoreConfig('payment/gpcashpayment/password',Mage::app()->getStore());
		$gpAPI = new GharpayAPI();
		$gpAPI->setUsername($username);
		$gpAPI->setPassword($password);
		$gpAPI->setURL($uri);
        
        Mage::app(); //for autoloading
        try {
        $result = $gpAPI->viewOrderStatus($gharpayOrderId);
        }
        catch (Exception $e)
        {
        	Mage::throwException($this->_getHelper()->__($e->getMessage()));
        } 
        $this->addStatusToGharpayDb($result['gharpayOrderId'],$result['status']);
    }
    public function addStatusToGharpayDb($gharpayOrderId,$status)
    {
    	Mage::Log("called addStatusToGharpayDb method");
        $go =  new Gharpay_Dbconns_Model_Gharpayorders();
        $gp =  new Gharpay_Dbconns_Model_Gharpayproperty();
        $gpv =  new Gharpay_Dbconns_Model_Gharpaypropvalue();
        $go= $go->getCollection();
        $go=$go->addFieldToFilter('gharpay_order_id',$gharpayOrderId);
        $gpid = $go->getFirstItem()->getData('gharpay_id');
        $cid=$go->getFirstItem()->getData('client_order_id');
        $gp = $gp->getCollection();
        $gp->addFieldToFilter('property_name',GHARPAY_STATUS);
        $pid=$gp->getFirstItem()->getData('property_id');
        Mage::Log($pid);
        $gpv=$gpv->getCollection();
        $gpv->addFieldToFilter('gharpay_id',$gpid);
        $gpv->addFieldToFilter('property_id',$pid)->getSelect();
        Mage::Log($gpv->count());
        if($gpv->count())
        {
            $gpvn= new Gharpay_Dbconns_Model_Gharpaypropvalue();
            $gpvn->setPropertyId($pid);
            $gpvn->setGharpayId($gpid);
            $gpvn->setPropValue($status);
            $gpvn->save();
            $this->addStatusToOrderGrid($cid, $status);
        }
        else
        {
            Mage::app();
            Mage::throwException('Oops! Something went wrong.Please contact us');
        } 
   }
    
    public function addStatusToOrderGrid($increment_id,$status)
    {
    	Mage::Log("Called addStatusToOrderGrid method");
        $resource=Mage::getSingleton('core/resource');
        $og=$resource->getConnection('core_write');
        $table = $resource->getTableName('sales_flat_order_grid');
        $qry="update ".$table." set gharpay_status='".$status."' where increment_id=".$increment_id;
        Mage::Log("Order ID :".$increment_id." has been updated with Status : ".$status);
        Mage::Log($qry);
        $result=$og->query($qry);
    }
}