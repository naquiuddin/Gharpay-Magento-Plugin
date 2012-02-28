<?php
/**
 * Setup scripts, add new column and fulfills
 * its values to existing rows
 *
 */
/* @var $this Mage_Sales_Model_Mysql4_Setup */
$this->startSetup();
// Add payment_method column to grid table
$this->getConnection()->addColumn(
    $this->getTable('sales/order_grid'),//table name
    'gharpay_status',//column name
    "varchar(80) not null default ''"//definition
);
// Add key to table for this field,
// it will improve the speed of searching & sorting by the field
$this->getConnection()->addKey(
    $this->getTable('sales/order_grid'),//table name
    'IDX_GHARPAY_STATUS',//index name
    'gharpay_status'//fields
);
// Now you need to fullfill existing rows with data from address table
//$this->run("update sales_flat_order_grid sfog 
//    join gharpay_orders go on sfog.increment_id=go.client_order_id 
//    join gharpay_prop_value gpv on go.gharpay_id=gpv.gharpay_id 
//    join gharpay_property gp on gp.property_id=gpv.property_id
//set sfog.gharpay_status=gpv.prop_value 
//where sfog.increment_id=go.client_order_id and gp.property_id=1;");
//$select = $this->getConnection()->select();
//$select->join(
//    array('order_payment'=>$this->getTable('sales/order_payment')),//alias=>table_name
//    $this->getConnection()->quoteInto(
//        'order_payment.parent_id = order_grid.entity_id',
//    	Mage_Sales_Model_Quote_Address::TYPE_BILLING
//    ),//join clause
//    array('payment_method' => 'method')//fields to retrieve
//);
//$this->getConnection()->query(
//    $select->crossUpdateFromSelect(
//        array('order_grid' => $this->getTable('sales/order_grid'))
//    )
//);
$this->endSetup();