<?php
class ZooZ_ZoozPayment_Model_Source_Status
{
	public function toOptionArray()
	{
		return array(
				array(
						'value' => Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
						'label' => Mage::helper('core')->__('Pending')
				),
				array(
						'value' => Mage_Sales_Model_Order::STATE_PROCESSING,
						'label' => Mage::helper('core')->__('Processing') 
				)
				
		);
	}
}