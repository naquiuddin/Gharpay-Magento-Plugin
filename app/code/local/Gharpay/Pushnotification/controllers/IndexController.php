<?php
class Gharpay_Pushnotification_IndexController extends Mage_Core_Controller_Front_Action{
    public function IndexAction() {
        
        $params=$this->getRequest()->getParams();
        $gharpayOrderId=$params['order_id'];
        $time=$params['time'];
        
        $model = Mage::getModel('pushnotification/pnotif');
        $model->viewOrderStatus($gharpayOrderId);
//        $gporders =new Gharpay_Dbconns_Model_Gharpayorders();
//        $gporders = $gporders->getCollection();
//        $gporders->addFieldToFilter('gharpay_order_id',$gharpayOrderId);        
//        $uri = Mage::getStoreConfig('payment/cashondelivery/gharpay_uri',Mage::app()->getStore());
//        $username = Mage::getStoreConfig('payment/cashondelivery/username',Mage::app()->getStore());
//        $password = Mage::getStoreConfig('payment/cashondelivery/password',Mage::app()->getStore());
//        echo $uri.$username.$password;
//        echo $time;
//        
        
//	  $this->loadLayout();   
//	  $this->getLayout()->getBlock("head")->setTitle($this->__("Gharpaynotification"));
//	        $breadcrumbs = $this->getLayout()->getBlock("breadcrumbs");
//      $breadcrumbs->addCrumb("home", array(
//                "label" => $this->__("Home Page"),
//                "title" => $this->__("Home Page"),
//                "link"  => Mage::getBaseUrl()
//		   ));
//
//      $breadcrumbs->addCrumb("gharpaynotification", array(
//                "label" => $this->__("Gharpaynotification"),
//                "title" => $this->__("Gharpaynotification")
//		   ));
//
//      $this->renderLayout(); 
	  
    }
}