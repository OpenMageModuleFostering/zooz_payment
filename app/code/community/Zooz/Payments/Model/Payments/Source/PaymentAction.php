<?php
/**
 * ZooZ Payments Integration
 *
 * @category    Zooz
 * @package     Zooz_Payments
 */

/**
 * Zooz Payments Action Dropdown source
 */
class Zooz_Payments_Model_Payments_Source_PaymentAction
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => Zooz_Payments_Model_Payments::ACTION_AUTHORIZE,
                'label' => Mage::helper('payments')->__('Authorize Only')
            ),
            array(
                'value' => Zooz_Payments_Model_Payments::ACTION_AUTHORIZE_CAPTURE,
                'label' => Mage::helper('payments')->__('Authorize and Capture')
            ),
        );
    }
}
