<?php
include_once 'app/Mage.php';
require_once(Mage::getBaseDir('lib').DIRECTORY_SEPARATOR.'Gharpay'.DIRECTORY_SEPARATOR.'Array2Xml.php');
require_once(Mage::getBaseDir('lib').DIRECTORY_SEPARATOR.'Gharpay'.DIRECTORY_SEPARATOR.'Xml2Array.php');
require_once(Mage::getModuleDir('Model', 'Gharpay_Dbconns').DIRECTORY_SEPARATOR.'Model'.DIRECTORY_SEPARATOR.'Gharpayorders.php');
require_once(Mage::getModuleDir('Model', 'Gharpay_Dbconns').DIRECTORY_SEPARATOR.'Model'.DIRECTORY_SEPARATOR.'Gharpayproperty.php');
require_once(Mage::getModuleDir('Model', 'Gharpay_Dbconns').DIRECTORY_SEPARATOR.'Model'.DIRECTORY_SEPARATOR.'Gharpaypropvalue.php');

define('GHARPAY_STATUS','Gharpay Order Status');
class Gharpay_Gharpaypushnotification_Model_Pnotif extends Mage_Core_Model_Abstract
{
    
    public function viewOrderStatus($gharpayOrderId)
    {
        $uri = Mage::getStoreConfig('payment/cashondelivery/gharpay_uri',Mage::app()->getStore());
        $username = Mage::getStoreConfig('payment/cashondelivery/username',Mage::app()->getStore());
        $password = Mage::getStoreConfig('payment/cashondelivery/password',Mage::app()->getStore());
        Mage::app(); //for autoloading:)
        $client = new Varien_Http_Client('http://'.$uri.'/rest/GharpayService/viewOrderStatus?orderID='.$gharpayOrderId);
        $client->setMethod(Varien_Http_Client::GET);
        $client->setHeaders('username',$username);
        $client->setHeaders('password',$password);
        $client->setEncType('application/xml');
        $response = $client->request();
        $xml = $response->getRawBody();
        Mage::Log($xml);
        $arr=  XML2Array::createArray($xml);
        $orderStatus=$arr['viewOrderStatusResponse']['orderStatus'];
        $gorderId=$arr['viewOrderStatusResponse']['orderID'];
        Mage::Log($orderStatus.$gorderId);
        $this->addStatusToGharpayDb($gorderId,$orderStatus);
        Mage::Log('Called addStatusToGharpayDb() just now');
        //return $status;
    }
    public function addStatusToGharpayDb($gharpayOrderId,$status)
    {
        $go =  new Gharpay_Dbconns_Model_Gharpayorders();
        $gp =  new Gharpay_Dbconns_Model_Gharpayproperty();
        $gpv =  new Gharpay_Dbconns_Model_Gharpaypropvalue();
        
        $go= $go->getCollection();
        $go=$go->addFieldToFilter('gharpay_order_id',$gharpayOrderId);
        $gpid = $go->getFirstItem()->getData('gharpay_id');
        $cid=$go->getFirstItem()->getData('client_order_id');
        Mage::Log($gpid);

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
            Mage::log($gpv->count());
            Mage::throwException('Oops! Something went wrong.Please contact us');
        } 
   }
    
    public function addStatusToOrderGrid($increment_id,$status)
    {
        $og=Mage::getSingleton('core/resource')->getConnection('core_write');
        $qry="update sales_flat_order_grid set gharpay_status='".$status."' where increment_id=".$increment_id;
        Mage::Log($increment_id.'  '.$status);
        Mage::Log($qry);
        $result=$og->query($qry);
//        $sfog = Mage::getModel('sales/order')->loadByIncrementId($increment_id);
//        Mage::Log($sfog->getData('gharpay_status'));
//        $sfog->setGharpayStatus($status);
//        $sfog->save();
//              
    }
}