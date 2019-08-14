<?php

/**
 * ZooZ Payments Integration
 *
 * @category    Zooz
 * @package     Zooz_Payments
 */
class Zooz_Payments_Model_Resource_Payments extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('debug/zooz', 'debug_id');
    }
}