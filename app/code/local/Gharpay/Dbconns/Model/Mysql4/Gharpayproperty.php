<?php
    class Gharpay_Dbconns_Model_Mysql4_Gharpayproperty extends Mage_Core_Model_Mysql4_Abstract
    {
        protected function _construct()
        {
            $this->_init("dbconns/gharpayproperty", "property_id");
        }
    }
	 