<?php
class ZooZ_ZoozPayment_Helper_Sanbox
{
 /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 1, 'label'=>Mage::helper('adminhtml')->__('Sandbox')),
            array('value' => 0, 'label'=>Mage::helper('adminhtml')->__('Production')),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            0 => Mage::helper('adminhtml')->__('Production'),
            1 => Mage::helper('adminhtml')->__('Sandbox'),
        );
    }

}
?>
