<?php
/*

// Mike Prince, 30 June 2012
// Set template for Gharpay payment info block

*/

class Gharpay_Gpcashpayment_Block_Info extends Mage_Payment_Block_Info
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('gpcashpayment/info.phtml');
    }

}
