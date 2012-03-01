<?php
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
//demo 
Mage::getModel('core/url_rewrite')->setId(null);
//demo 

$this->endSetup();
	 