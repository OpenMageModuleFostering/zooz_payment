<?php
/**
 * ZooZ Payments Integration
 *
 * @category    Zooz
 * @package     Zooz_Payments
 */

/**
 * ZooZ Payments Mode Dropdown source
 */
class Zooz_Payments_Model_Payments_Source_PaymentMode
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => Zooz_Payments_Model_Payments::ACTION_MODE_SANDBOX,
                'label' => Mage::helper('payments')->__('Sandbox')
            ),
            array(
                'value' => Zooz_Payments_Model_Payments::ACTION_MODE_PRODUCTION,
                'label' => Mage::helper('payments')->__('Production')
            ),
        );
    }
}
