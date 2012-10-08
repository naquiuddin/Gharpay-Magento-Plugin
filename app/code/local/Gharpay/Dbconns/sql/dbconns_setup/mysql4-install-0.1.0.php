<?php
$installer = $this;
$installer->startSetup();
$installer->run("
CREATE TABLE IF NOT EXISTS `{$this->getTable(gharpay_orders)}` (
   gharpay_id INT UNSIGNED AUTO_INCREMENT NOT NULL,
   created_at DATETIME NOT NULL,
   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
   client_order_id INT NOT NULL,
   gharpay_order_id VARCHAR(60) NOT NULL,
  PRIMARY KEY (gharpay_id)
);

CREATE TABLE IF NOT EXISTS `{$this->getTable(gharpay_property)}` (
   property_id INT AUTO_INCREMENT NOT NULL,
   property_name VARCHAR(80) NOT NULL,
   PRIMARY KEY (property_id)
);

CREATE TABLE IF NOT EXISTS `{$this->getTable(gharpay_prop_value)}` (
   prop_value_id INT AUTO_INCREMENT NOT NULL,
   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   property_id INT NOT NULL,
   gharpay_id INT NOT NULL,
   prop_value VARCHAR(80) NOT NULL,
  CONSTRAINT fkgharpayid FOREIGN KEY (gharpay_id) REFERENCES `{$this->getTable(gharpay_orders)}` (gharpay_id) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fkpropertyid FOREIGN KEY (property_id) REFERENCES `{$this->getTable(gharpay_property)}` (property_id) ON UPDATE RESTRICT ON DELETE RESTRICT,
  PRIMARY KEY (prop_value_id)
);
insert into `{$this->getTable(gharpay_property)}` (property_name) values ('Gharpay Order Status');
");



//demo 
Mage::getModel('core/url_rewrite')->setId(null);
//demo 
$installer->endSetup();
	 