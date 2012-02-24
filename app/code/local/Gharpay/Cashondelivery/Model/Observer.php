<?php
require_once(Mage::getModuleDir('Model', 'Gharpay_Dbconns').DIRECTORY_SEPARATOR.'Model'.DIRECTORY_SEPARATOR.'Gharpayorders.php');
require_once(Mage::getModuleDir('Model', 'Gharpay_Pushnotification').DIRECTORY_SEPARATOR.'Model'.DIRECTORY_SEPARATOR.'Pnotif.php');
class Gharpay_Cashondelivery_Model_Observer
{
    public function gharpayCancelOrder(Varien_Event_Observer $Observer)
    {
        $coid=$Observer->getOrder()->getIncrementId();
        Mage::Log($coid);
        $go=new Gharpay_Dbconns_Model_Gharpayorders();
        $go= $go->getCollection();
        $go->addFieldToFilter('client_order_id',$coid)->getSelect();        
        if($go->count())
        {
            $transId = $go->getFirstItem()->getData('gharpay_order_id');
            Mage::Log($transId);
            #if(!empty($transId)&&isset($transId)&&$tBits[0]=='GW')
            #{                                   
                Mage::app();
                Mage::Log('this is inside If block');
                $uri = Mage::getStoreConfig('payment/cashondelivery/gharpay_uri',Mage::app()->getStore());
                $username = Mage::getStoreConfig('payment/cashondelivery/username',Mage::app()->getStore());
                $password = Mage::getStoreConfig('payment/cashondelivery/password',Mage::app()->getStore());
                $arr['orderID']=$transId;
                $xml=Array2XML::createXML('cancelOrder',$arr);
                $xml=$xml->saveXML();
                Mage::Log('this is inside XML :'.$xml);
                $client = new Varien_Http_Client('http://'.$uri.'/rest/GharpayService/cancelOrder');
                $client->setHeaders('username',$username);
                $client->setHeaders('password',$password);
                $client->setMethod(Varien_Http_Client::POST);
                $client->setRawData($xml, 'application/xml');
                $response = $client->request();
                Mage::Log('Log Response :'.$response);
                $resXML=$response->getRawBody();
                Mage::Log('This is response Body :' .$resXML);
                $arr =  XML2Array::createArray($resXML);
                Mage::Log($arr['cancelOrderResponse']['result']);
                $result=$arr['cancelOrderResponse']['result'];
                if($result=='true')
                {
                    $gp = new Gharpay_Pushnotification_Model_Pnotif();                    
                    $status='Cancelled by Client';
                    $gp->addStatusToGharpayDb($transId,$status);
                    $gp->addStatusToOrderGrid($coid,$status);
                }
            #}
        }
    }
}