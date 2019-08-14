<?php
/**
 * ZooZ Payments Integration
 *
 * @category    Zooz
 * @package     Zooz_Payments
 */

/**
 * Observer class which will prepare order for sending to Zooz API
 *
 * Used for retrieving configuration data by payment models
 *
 * @category   Zooz
 * @package    Zooz_Payments
 */
class Zooz_Payments_Model_Observer
{


	/**
	 * Observer catchs the sales_order_save_before and prepare data required for zooz
	 *
	 * @param Varien_Event_Observer $observer
	 * @return void
     */
	public function handleSalesOrderSaveBefore(Varien_Event_Observer $observer)
	{

		$order = $observer->getEvent()->getOrder();
		$payment = $order->getPayment();
		$post = Mage::app()->getRequest()->getPost();

		if($post['payment']['method'] == 'payments' && isset($post['payment']['is_iframe']) &&$post['payment']['is_iframe'] == 1) {
			$payment_detail = $this->_getApi()->getPaymentDetails($post['payment']['cc_payment_token']);
			$additional = $payment->getAdditionalInformation();

			$type = strtolower($payment_detail["paymentMethod"]["subtype"]);

			if($type == 'visa') {
				$type = "VI";
			}
			if($type == 'mastercard') {
				$type = "MC";
			}
			if($type == 'americanexpress') {
				$type = "AE";
			}
			if($type == 'discover') {
				$type = "DI";
			}
			foreach($additional['zooz_cards'] as $key => $value) {
				foreach($value as $k => $v) {
					if($k=='cc_last4') {
						$additional['zooz_cards'][array_keys($additional['zooz_cards'])[0]][$k] = $payment_detail["paymentMethod"]["lastFourDigits"];
					}
					if($k=='cc_type') {
						$additional['zooz_cards'][array_keys($additional['zooz_cards'])[0]][$k] = $type;
					}
				}
			}


			$payment->setAdditionalInformation($additional);

			$payment->save();
		}


	}

	/**
	 * Retrevie the Api model
	 *
	 * @return Zooz_Payments_Model_Api
     */
	protected function _getApi()
	{
		return Mage::getSingleton('payments/api');
	}

}