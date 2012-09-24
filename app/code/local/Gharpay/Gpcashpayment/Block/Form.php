<?php
/*

// Mike Prince, 27 June 2012
// Set template for Gharpay checkout payment block

*/

class Gharpay_Gpcashpayment_Block_Form extends Mage_Payment_Block_Form
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('gpcashpayment/form.phtml');
    }

}
