<?php
/**
 * Event observer model
 */
class Gharpay_Gharpaypushnotification_Model_Observer
{
    /**
     * Adds virtual grid column to order grid records generation
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function addColumnToResource(Varien_Event_Observer $observer)
    {
        $resource = $observer->getEvent()->getResource();
//        $resource->addVirtualGridColumn(
//            'gharpay_status',
//            'gharpay/orders',
//            array('increment_id' => 'client_order_id'),
//            'gharpay_status'
//        );
    }
}