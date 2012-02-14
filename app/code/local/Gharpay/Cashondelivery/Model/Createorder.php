<?php 
include_once 'app/Mage.php';
require_once(Mage::getBaseDir('lib').'\Gharpay\Array2Xml.php');
require_once(Mage::getBaseDir('lib').'\Gharpay\Xml2Array.php');
//class ZendHttp extends Zend_Http_Response
//{
//
//    public static function decodeChunkedBody($body)
//    {
//    $decBody = '';
//    if (preg_match("/^([\da-fA-F]+)[^\r\n]*\r\n/sm", trim($body))) {
//        while (preg_match("/^([\da-fA-F]+)[^\r\n]*\r\n/sm", trim($body), $m)) {
//            $length = hexdec(trim($m[1]));
//            $cut = strlen($m[0]);
//
//            $decBody .= substr($body, $cut, $length);
//            $body = substr($body, $cut + $length + 2);
//        }
//    } else {
//        return $body;
//    }
//
//    return $decBody;
//    }   
//}
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
        
        Mage::app(); //for autoloading:)
        $client = new Varien_Http_Client('http://services.gharpay.in/rest/GharpayService/isPincodePresent?pincode='.$postCode);
        $client->setMethod(Varien_Http_Client::GET);
        $client->setHeaders('username','redbus');
        $client->setHeaders('password','redbus');
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
            $paymentInfo = $this->getInfoInstance();
            Mage::Log('Got Order Info Instance');
            Mage::Log($amount);
            $order = $paymentInfo->getOrder();
            $info=$order->getBillingAddress();
            Mage::Log($this->getCofigData('payment_action')); 
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
            "deliveryDate"=>date('d-m-Y'),
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
                 $client = new Varien_Http_Client('http://services.gharpay.in/rest/GharpayService/createOrder');
                 $client->setHeaders('username','redbus');
                 $client->setHeaders('password','redbus');
                 $client->setMethod(Varien_Http_Client::POST);
                 $client->setRawData($xml, 'application/xml');
                 /** @var $response Zend_Http_Response */
                 $response = $client->request();
                 Mage::Log($response);
        return $this;
    }     
}
?>