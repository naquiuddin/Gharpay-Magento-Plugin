<?php
class Gharpay_Gharpaypushnotification_Block_Adminhtml_Sales_Order_Renderer_GharpayStatus extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
	public function render(Varien_Object $row)
	{
		$ret=$row->getData('gharpay_status');
                return $ret;
//		$paymentTitle = Mage::getStoreConfig('payment/' . $paymentMethodCode . '/title');		
//		return $paymentTitle;
	}
}