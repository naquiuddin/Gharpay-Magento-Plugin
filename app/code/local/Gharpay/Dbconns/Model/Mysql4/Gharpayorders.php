<?php
    class Gharpay_Dbconns_Model_Mysql4_Gharpayorders extends Mage_Core_Model_Mysql4_Abstract
    {
        protected function _construct()
        {
            $this->_init("dbconns/gharpayorders", "gharpay_id");
        }
    }
	 