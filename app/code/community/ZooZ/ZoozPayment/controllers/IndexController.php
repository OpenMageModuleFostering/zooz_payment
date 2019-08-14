<?php

class ZooZ_ZoozPayment_IndexController extends Mage_Core_Controller_Front_Action {

    //public function IndexAction() {
		//$this->getResponse()->setRedirect(Mage::getUrl('', array('_secure' => true))); 
    //}
    public function testAction() {
        // $tempAddress = Mage::getModel('sales/quote_address')->load(596 );
//        $quote = Mage::getSingleton('checkout/session')->getQuote();
//        echo $giftcardAmount = $quote->getGiftCardsAmountUsed();
//        die();
          $quote = Mage::getSingleton('checkout/session')->getQuote();
       print_r($quote->getData());
       die();
             $coupon_code = $quote->getCouponCode();
            $oCoupon = Mage::getModel('salesrule/coupon')->load($coupon_code, 'code');
           $oRule = Mage::getModel('salesrule/rule')->load($oCoupon->getRuleId());
$oRule->getName();
          die();
        $date = new DateTime('now');
        echo $date->format('Y-m-d H:i:s');
        die();
        $card_history = Mage::getModel('enterprise_giftcardaccount/history');
        $card_history->setGiftcardaccountId();
        $card_history->setUpdatedAt();
        $card_history->setAction(1);
        $card_history->setBalanceAmount();
        $card_history->setAdditionalInfo();
        $card_history->setBalanceDelta();

        print_r($card_history->getData());

        die();
        $_card = Mage::getModel('enterprise_giftcardaccount/giftcardaccount')->load(1);
        $_card->setBalance(80);
        $_card->save();
        print_r($_card->getData());
//
        die();
        $giftcart_s = 'a:1:{i:0;a:4:{s:1:"i";s:1:"2";s:1:"c";s:12:"02DQ9T1K0Y0V";s:1:"a";d:17;s:2:"ba";s:7:"17.0000";}}';
        $giftcart = unserialize($giftcart_s);
        $temp_1 = $giftcart[0];
        $temp_1['authorized'] = $temp_1['a'];
        $temp_2[] = $temp_1;
        $giftcart_auth = serialize($temp_2);

        die();
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $shippingAddress = $quote->getShippingAddress();
//           $shippingAddress->setShippingMethod(freeshipping_freeshipping);
//              $shippingAddress->save();
        print_r($shippingAddress->getData());
        echo "Dev";
//        $quote = Mage::getSingleton('checkout/session')->getQuote();
//
//       $cart = unserialize($quote->getGiftCards());
//     
//        echo $quote->getGiftCardsAmount();
//        echo $quote->getBaseGiftCardsAmount();
//        echo $quote->getGiftCardsAmountUsed();
//        echo $quote->getBaseGiftCardsAmountUsed();
//        print_r($quote->getData());
//        a:1:{i:0;a:5:{s:1:"i";s:1:"2";s:1:"c";s:12:"02DQ9T1K0Y0V";s:1:"a";d:252;s:2:"ba";d:252;s:10:"authorized";d:252;}}
//        echo $giftcardAmount = $quote->getGiftCardsAmountUsed();
        //   die();
    }
    
    public function IndexAction() {      
	  $this->loadLayout();   
	  $this->getLayout()->getBlock("head")->setTitle($this->__("Zoozpayment checkout"));
	  
	  $breadcrumbs = $this->getLayout()->getBlock("breadcrumbs");
      $breadcrumbs->addCrumb("home", array(
                "label" => $this->__("Home Page"),
                "title" => $this->__("Home Page"),
                "link"  => Mage::getBaseUrl()
		   ));

      $breadcrumbs->addCrumb("zoozpayment", array(
                "label" => $this->__("Zoozpayment checkout"),
                "title" => $this->__("Zoozpayment checkout")
		   ));

      $this->renderLayout(); 
    }
	  
}    
