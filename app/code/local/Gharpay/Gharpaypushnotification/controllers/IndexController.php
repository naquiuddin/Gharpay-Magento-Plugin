<?php
class Gharpay_Gharpaypushnotification_IndexController extends Mage_Core_Controller_Front_Action{
    
	public function IndexAction() 
    {    
        if($this->getRequest()->getParams())
        {
	        $params=$this->getRequest()->getParams();
	        $gharpayOrderId=$params['order_id'];	      
	        $model = Mage::getModel('gharpaypushnotification/pnotif');
	        $model->viewOrderStatus($gharpayOrderId);
        }
    }
}
