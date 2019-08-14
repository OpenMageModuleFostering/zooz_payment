<?php

class ZooZ_ZoozPayment_Helper_Data extends Mage_Core_Helper_Abstract {

    public function getTax() {
        $totals = Mage::getSingleton("checkout/session")->getQuote()->getTotals(); //Total object
        if (isset($totals['tax']) && $totals['tax']->getValue()) {
            return $totals['tax']->getValue();
        } else {
            return 0;
        }
    }
    
    public function getshipping() {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        return $shipping = $quote->getShippingAddress()->getShippingAmount();
    }

    public function getdiscount() {
        $quote = Mage::getSingleton("checkout/session")->getQuote(); //Total object
        Mage::log('Data.php -- Get discount - getSubtotalWithDiscount: ' . $quote->getSubtotalWithDiscount() . ' subtotal:' . $quote->getSubtotal());

	return (float) $quote->getSubtotal() - $quote->getSubtotalWithDiscount();
      
    }

}
	 