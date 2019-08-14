<?php

/**
 * ZooZ Payments Integration
 *
 * @category    Zooz
 * @package     Zooz_Payments
 */
class Zooz_Payments_Model_Mysql4_Debug extends Mage_Core_Model_Mysql4_Abstract {

    protected function _construct() {
        $this->_init('zooz/api_debug', 'debug_id');
    }

}
