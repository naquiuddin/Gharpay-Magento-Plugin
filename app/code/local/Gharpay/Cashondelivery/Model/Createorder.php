<?php 
include_once 'app/Mage.php';
require_once(Mage::getBaseDir('lib').DIRECTORY_SEPARATOR.'Gharpay'.DIRECTORY_SEPARATOR.'Array2Xml.php');
require_once(Mage::getBaseDir('lib').DIRECTORY_SEPARATOR.'Gharpay'.DIRECTORY_SEPARATOR.'Xml2Array.php');
require_once(Mage::getModuleDir('Model', 'Gharpay_Dbconns').DIRECTORY_SEPARATOR.'Model'.DIRECTORY_SEPARATOR.'Gharpayorders.php');
require_once(Mage::getModuleDir('Model', 'Gharpay_Dbconns').DIRECTORY_SEPARATOR.'Model'.DIRECTORY_SEPARATOR.'Gharpaypropvalue.php');
require_once(Mage::getModuleDir('Model', 'Gharpay_Gharpaypushnotification').DIRECTORY_SEPARATOR.'Model'.DIRECTORY_SEPARATOR.'Pnotif.php');

class Gharpay_Cashondelivery_Model_Createorder extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'cashondelivery';
    protected $_canAuthorize = true;
    protected $_canUseCheckout = true;
    protected $_canFetchTransactionInfo     = true;
    protected $_isGateway                   = true;
    protected $_canUseInternal = true;
    protected $_canVoid    = true;
    protected $_canCancel = true;
    
    public function canCancel()
    {
        return $this->_canCancel;
    }
    
    public function validate()
    {
        $title = Mage::getStoreConfig('payment/cashondelivery/title',Mage::app()->getStore());
        $paymentInfo = $this->getInfoInstance();
         if ($paymentInfo instanceof Mage_Sales_Model_Order_Payment) {
             $postCode = $paymentInfo->getOrder()->getBillingAddress()->getPostcode();
         } 
         else {
             $postCode = $paymentInfo->getQuote()->getBillingAddress()->getPostcode();
         }
         if (!$this->canUseForPostCode($postCode)) {
             Mage::throwException($this->_getHelper()->__('Sorry ! '.$title.' Service is not available in your area'));
         }
         return $this;
    }
    
    public function canUseForPostCode($postCode)
    {
        $uri = Mage::getStoreConfig('payment/cashondelivery/gharpay_uri',Mage::app()->getStore());
        $username = Mage::getStoreConfig('payment/cashondelivery/username',Mage::app()->getStore());
        $password = Mage::getStoreConfig('payment/cashondelivery/password',Mage::app()->getStore());
        Mage::app(); //for autoloading:)
        $client = new Varien_Http_Client('http://'.$uri.'/rest/GharpayService/isPincodePresent?pincode='.$postCode);
        $client->setMethod(Varien_Http_Client::GET);
        $client->setHeaders('username',$username);
        $client->setHeaders('password',$password);
        $client->setEncType('application/xml');
        $response = $client->request();
        $xml = $response->getRawBody();
        Mage::Log($response);
        $parr=  XML2Array::createArray($xml);
        $res='';
        if(isset($parr['isPincodePresentPresentResponse']['result'])) {
               $res = $parr['isPincodePresentPresentResponse']['result'];
        }
        else
        {
            Mage::throwException($this->_getHelper()->__('something is wrong. Please Contact us'));
        }

        Return $r = $res=='false'? FALSE : TRUE;        
    }
    public function authorize(Varien_Object $payment, $amount)
    {
        $uri = Mage::getStoreConfig('payment/cashondelivery/gharpay_uri',Mage::app()->getStore());
        $username = Mage::getStoreConfig('payment/cashondelivery/username',Mage::app()->getStore());
        $password = Mage::getStoreConfig('payment/cashondelivery/password',Mage::app()->getStore());
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
            $productDetails;
            $i=0;
            foreach ($order->getAllItems() as $item) {
                $productDetails[$i] = array(
                    "productID"  => $item->getProductId(),
                    "productQuantity"=> $item->getQtyOrdered(),
                    "unitCost" => $item->getPrice()
                 ); 
                $i++;
            }
            #,
            $orderDetails = array(
            "pincode"=>$order->getBillingAddress()->getPostcode(),
            "clientOrderID"=>$order->getIncrementId(),
            "deliveryDate"=>$date->format('d-m-Y'),
            "orderAmount"=>$order->getBaseGrandTotal(),
            "productDetails"=>$productDetails
            );
            $arr= array(
                     "customerDetails"=>$customerDetails,
                     "orderDetails"=>$orderDetails                     
            );
            $xml = Array2XML::createXML('transaction', $arr);
            $xml=$xml->saveXML();                        
            Mage::app(); //for autoloading:)
            $client = new Varien_Http_Client('http://'.$uri.'/rest/GharpayService/createOrder');
            $client->setHeaders('username',$username);
            $client->setHeaders('password',$password);
            $client->setMethod(Varien_Http_Client::POST);
            $client->setRawData($xml, 'application/xml');
            $response = $client->request();
            Mage::Log('Log Response :'.$response);
            $resXML=$response->getRawBody();
            Mage::Log('Log Response Body XML :'.$resXML);
            $resArr=XML2Array::createArray($resXML);
            if(($response->getStatus()==200)&&!isset($resArr['createOrderResponse']['errorCode']))
            {
                     Mage::Log($response->getStatus());
                     Mage::Log($response->getStatus());
                     Mage::Log($response->isError());
                     Mage::Log($response->isSuccessful());
                    $clientId=$resArr['createOrderResponse']['clientOrderID'];
                    if($clientId==$order->getIncrementId())
                    {
                        $gharpayId=$resArr['createOrderResponse']['orderID'];   
                        $gharpayorders= new Gharpay_Dbconns_Model_Gharpayorders();
                        $gharpayorders->setGharpayOrderId($gharpayId);
                        $gharpayorders->setClientOrderId($clientId);
                        $gharpayorders->setCreatedAt($date->format('Y-m-d h:i:s'));
                        $gharpayorders->save();
                        $goid = $gharpayorders->getId();
                        Mage::Log($goid);
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
            }
            else
            {
                     Mage::throwException($this->_getHelper()->__('Oops! Some error occured in the Payment Gateway, Please try after some time or Contact Us'));                     
            }
            return $this;
    }
    
    public function void(Varien_Object $payment)
    {
        Mage::app();
        Mage::Log('this is inside Void up');
        if (!$this->canVoid($payment)) {
            Mage::throwException($this->_getHelper()->__('Void action is not available.'));
        }
        $username = Mage::getStoreConfig('payment/cashondelivery/username',Mage::app()->getStore());
        $password = Mage::getStoreConfig('payment/cashondelivery/password',Mage::app()->getStore());
        $arr['orderID']=$payment->getTransactionId();
        $xml=Array2XML::createXML('cancelOrder',$arr);
        Mage::Log('this is inside void :'.$xml);
            $client = new Varien_Http_Client('http://'.$uri.'/rest/GharpayService/cancelOrder');
            $client->setHeaders('username',$username);
            $client->setHeaders('password',$password);
            $client->setMethod(Varien_Http_Client::POST);
            $client->setRawData($xml, 'application/xml');
            $response = $client->request();
            Mage::Log('Log Response :'.$response);
            $resXML=$response->getRawBody();
            Mage::Log('This is response :' .$resXML);
        return $this;
        
    }
    
    public function cancel(Varien_Object $payment)
    {   Mage::Log('Inside Cancel');
        return $this;
        Mage::Log('Going out of cancel');
    }
}