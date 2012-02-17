<?php 
include_once 'app/Mage.php';
require_once(Mage::getBaseDir('lib').'\Gharpay\Array2Xml.php');
require_once(Mage::getBaseDir('lib').'\Gharpay\Xml2Array.php');
require_once(Mage::getModuleDir('Model', 'Gharpay_Dbconns').'\Model\Gharpayorders.php');

class Gharpay_Cashondelivery_Model_Createorder extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'cashondelivery';
    protected $_canCapture = true;
    protected $_canUseCheckout = true;
    protected $_canFetchTransactionInfo     = true;
    protected $_isGateway                   = true;
    
    public function validate()
    {
       $paymentInfo = $this->getInfoInstance();
         if ($paymentInfo instanceof Mage_Sales_Model_Order_Payment) {
             $postCode = $paymentInfo->getOrder()->getBillingAddress()->getPostcode();
             Mage::log('Validate Pincode :'.$postCode.'  In GetOrder');
         } 
         else {
             $postCode = $paymentInfo->getQuote()->getBillingAddress()->getPostcode();
             Mage::Log('Validate Pincode:'.$postCode.'  In GetQuote');
         }
         if (!$this->canUseForPostCode($postCode)) {
             Mage::throwException($this->_getHelper()->__('Sorry ! Gharpay Service is not available in your area'));
             Mage::Log("Validate : In Exception");
         }
         return $this;
    }
    
    public function canUseForPostCode($postCode)
    {
        $uri = Mage::getStoreConfig('payment/cashondelivery/gharpay_uri',Mage::app()->getStore());
        $username = Mage::getStoreConfig('payment/cashondelivery/username',Mage::app()->getStore());
        $password = Mage::getStoreConfig('payment/cashondelivery/password',Mage::app()->getStore());
        Mage::Log($uri);Mage::Log($username);Mage::Log($password); 
        Mage::app(); //for autoloading:)
        $client = new Varien_Http_Client('http://'.$uri.'/rest/GharpayService/isPincodePresent?pincode='.$postCode);
        $client->setMethod(Varien_Http_Client::GET);
        $client->setHeaders('username',$username);
        $client->setHeaders('password',$password);
        $client->setEncType('application/xml');
        /** @var $response Zend_Http_Response */
        $response = $client->request();
        Mage::Log($response);
        $xml = $response->getRawBody(); #Here I need to handle GZip Compression
        Mage::Log('Response Body  Pincode:'. $xml);
        $parr=  XML2Array::createArray($xml);
        Mage::Log($parr['isPincodePresentPresentResponse']['result']);
        $res=$parr['isPincodePresentPresentResponse']['result'];
        Return $r = $res=='false'? FALSE : TRUE;
                
    }
    public function capture(Varien_Object $payment, $amount)
    {
        $uri = Mage::getStoreConfig('payment/cashondelivery/gharpay_uri',Mage::app()->getStore());
        $username = Mage::getStoreConfig('payment/cashondelivery/username',Mage::app()->getStore());
        $password = Mage::getStoreConfig('payment/cashondelivery/password',Mage::app()->getStore());
        $date=new DateTime();
        Mage::Log($uri);Mage::Log($username);Mage::Log($password);
            $paymentInfo = $this->getInfoInstance();
            Mage::Log('Got Order Info Instance');
            Mage::Log($amount);
            $order = $paymentInfo->getOrder();
            $info=$order->getBillingAddress();
           $customerDetails = array(
                "address"=>$info->getStreetFull().','.$info->getRegion().','.$info->getCity().','.$info->getCountry(),
                "contactNo"=>$info->getTelephone(),
                "email"=>$info->getEmail(),
                "firstName"=>$info->getFirstname(),
                "lastName"=>$info->getLastname(),                       
            );
            Mage::Log($info->getStreetFull());
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
            Mage::Log($productDetails);
            $orderDetails = array(
            "pincode"=>$order->getBillingAddress()->getPostcode(),
            "clientOrderID"=>$order->getIncrementId(),
            "deliveryDate"=>$date->format('d-m-Y'),
            "orderAmount"=>$order->getBaseGrandTotal(),
            "productDetails"=>$productDetails
            );
            Mage::Log($orderDetails);
            $arr= array(
                     "customerDetails"=>$customerDetails,
                     "orderDetails"=>$orderDetails                     
                 );
                 $xml = Array2XML::createXML('transaction', $arr);
                 $xml=$xml->saveXML();
                 Mage::Log('Log XML request :'.$xml);                        
                 Mage::app(); //for autoloading:)
                 $client = new Varien_Http_Client('http://'.$uri.'/rest/GharpayService/createOrder');
                 $client->setHeaders('username',$username);
                 $client->setHeaders('password',$password);
                 $client->setMethod(Varien_Http_Client::POST);
                 $client->setRawData($xml, 'application/xml');
                 /** @var $response Zend_Http_Response */
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
                        Mage::Log('Log response body Client ID: '.$clientId);
                        $gharpayId=$resArr['createOrderResponse']['orderID'];
                        Mage::Log('Log Gharpay ID: '.$gharpayId);
                        $gharpayorders= new Gharpay_Dbconns_Model_Gharpayorders();
                        $gharpayorders->setGharpayOrderId($gharpayId);
                        $gharpayorders->setClientOrderId($clientId);
                        
                        $gharpayorders->setCreatedAt($date->format('Y-m-d H:i:s'));
                        $gharpayorders->setUpdatedAt($date->format('Y-m-d H:i:s'));
                        $gharpayorders->save();
                        Mage::Log('saved successfully');
                    }
                 }
                 else
                 {
                     Mage::Log($response->getStatus());
                     Mage::throwException($this->_getHelper()->__('Oops! Some error occured in the Payment Gateway, Please try after some time or Contact Us'));                     
                 }
            return $this;
    }     
}
?>