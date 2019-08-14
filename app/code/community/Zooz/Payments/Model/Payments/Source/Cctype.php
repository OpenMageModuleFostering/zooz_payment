<?php
/**
 * ZooZ Payments Integration
 *
 * @category    Zooz
 * @package     Zooz_Payments
 */

/**
 * ZooZ Payments CC Types Source Model
 *
 * @category    Zooz
 * @package     Zooz_Payment
  */
class Zooz_Payments_Model_Payments_Source_Cctype extends Mage_Payment_Model_Source_Cctype
{
    public function getAllowedTypes()
    {
        return array('VI', 'MC', 'AE', 'DI','DC','JCB', 'OT');
    }

    public function toOptionArray()
    {
        /**
         * making filter by allowed cards
         */
        $allowed = $this->getAllowedTypes();
        $options = array();

        foreach (Mage::getSingleton('payments/config')->getCcTypes() as $code => $name) {

            if (in_array($code, $allowed) || !count($allowed)) {
                $options[] = array(
                    'value' => $code,
                    'label' => $name
                );
            }
        }

        return $options;
    }
}
