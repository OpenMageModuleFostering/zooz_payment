<?php

class ZooZ_ZoozPayment_Model_Order extends Mage_Core_Model_Abstract {

    public function saveOrder($info = null, $giftInfo = null) {
        try {

            $shipping_price = $info->shippingAmount;
            $shipping_name = $info->shippingMethod;
            $shipping_carrier_name = $info->shippingCarrierName;

            $shipping_carrier_code = $info->shippingCarrierCode;
            Mage::log($shipping_name);
            Mage::log($shipping_carrier_name);
            Mage::log($shipping_carrier_code);
            Mage::log($shipping_price);
            $session = Mage::getSingleton("customer/session");
            $customer_session = $session->getCustomer();
            $customer = Mage::getModel('customer/customer')->load($customer_session->getId());
            $notLogin = false;
            $storeId = Mage::app()->getStore()->getId();
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            $quote->save();

            Mage::log("quote sub:" . $quote->getSubtotalWithDiscount());
            if (Mage::getSingleton('customer/session')->IsLoggedIn()) {
                $customer = Mage::getModel('customer/customer')->load($customer->getId());
                $storeId = $customer->getStoreId();
                $notLogin = true;
            }
            if ($info != null) {
                /* Have Return Info Shipping Address */
                Mage::log("Have Return Info Shipping Address");
                $user = $info->user;

                $shippingAddress = $quote->getShippingAddress();
                $shipp_address = $info->shippingAddress;
                Mage::log($shipp_address);
                $phone_number = $user->phoneNumber;
                if ($phone_number == '') {
                    $phone_number = "0987654321";
                }
                $shippingAddress->setStoreId($storeId)
                        ->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_SHIPPING)
                        ->setCustomerId($customer->getId())
                        ->setFirstname($shipp_address->firstName)
                        ->setLastname($shipp_address->lastName)
                        ->setStreet($shipp_address->street)
                        ->setCity($shipp_address->city)
                        ->setCountryId($shipp_address->countryCode)
                        ->setRegion($shipp_address->state)
                        ->setPostcode($shipp_address->zipCode)
                        ->setTelephone($phone_number);
                $shippingAddress->setShippingMethod($shipping_name);
                $shippingAddress->setShippingDescription($shipping_name . " (" . $shipping_carrier_name . ")");
                $shippingAddress->setCollectShippingRates(true)->collectShippingRates();

                if ($notLogin) {
                    /* Is Customer Login */
                    Mage::log("Is Customer Login");
                    $billing = $customer->getDefaultBillingAddress();
                    if ($billing) {
		        $billingAddress = $quote->getBillingAddress();
                        $billingAddress
                                ->setFirstname($billing->getFirstname())
                                ->setLastname($billing->getLastname())
                                ->setStreet($billing->getStreet())
                                ->setCity($billing->getCity())
                                ->setCountryId($billing->getCountryId())
                                ->setPostcode($billing->getPostcode());
                        $billingAddress->setRegionId($billing->getRegionId());// != "" ? $billing->getRegion() : $shipp_address->state);
                        $billingAddress->setTelephone($billing->getTelephone() != "" ? $billing->getTelephone() : $phone_number);

                        $quote->setBillingAddress($billingAddress);
                        $quote->setShippingAddress($shippingAddress); 
                    } else {
                        $billingAddress = $quote->getBillingAddress();
                        $billingAddress
                                ->setFirstname($shipp_address->firstName)
                                ->setLastname($shipp_address->lastName)
                                ->setStreet($shipp_address->street)
                                ->setCity($shipp_address->city)
                                ->setCountryId($shipp_address->countryCode)
                                ->setRegion($shipp_address->state)
                                ->setPostcode($shipp_address->zipCode)
                                ->setTelephone($phone_number);

                        //      $shippingAddress->setSameAsBilling(true);
                        $quote->setShippingAddress($shippingAddress);
                        $quote->setBillingAddress($billingAddress);
                    }
                } else {
                    /* Customer Is Not Login */
                    Mage::log(" Customer Is Not Login");
                    $billingAddress = $quote->getBillingAddress();
                    $billingAddress->setFirstname($shipp_address->firstName)
                            ->setLastname($shipp_address->lastName)
                            ->setStreet($shipp_address->street)
                            ->setCity($shipp_address->city)
                            ->setCountryId($shipp_address->countryCode)
                            ->setRegion($shipp_address->state)
                            ->setPostcode($shipp_address->zipCode)
                            ->setTelephone($phone_number);
                    $shippingAddress->setSameAsBilling(true);
                    $quote->setCustomerIsGuest(1)
                            ->setCustomerEmail($user->email)
                            ->setCustomerFirstname($shipp_address->firstName)
                            ->setCustomerLastname($shipp_address->lastName)
                            ->setCustomerGroupId(0)
                            ->setCustomerIsGuest(1);
                    $quote->setBillingAddress($billingAddress);
                    $quote->setShippingAddress($shippingAddress);
                }
            }
            $quote->save();
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            $session = Mage::getSingleton('checkout/session');
                $giftcartcheck = false;

            $payment = $quote->getPayment();
            $payment->setMethod('zoozpayment');
            Mage::log("quote sub1:" . $quote->getSubtotalWithDiscount());
            $quote->collectTotals();
            Mage::log("quote sub2:" . $quote->getSubtotalWithDiscount());
            $service = Mage::getModel('sales/service_quote', $quote);
            $service->submitAll();
            $order = $service->getOrder();
            if (!$order) {
                return;
            }


            if ($giftInfo != null && $giftInfo != "") {
                $gift_customer = 0;
                if ($customer->getId()) {
                    $gift_customer = $customer->getId();
                }
                //$Mage::log($info);
                //$Mage::log($info['gift']['message']);

                $model = Mage::getModel('giftmessage/message');
                $model->setCustomerId(0);
                $model->setSender($giftInfo['from']);
                $model->setRecipient($giftInfo['to']);
                $model->setMessage($giftInfo['message']);
                $model->save();
                $gift_id = $model->getId();
                $order->setGiftMessageId($gift_id);
            }
            $session = Mage::getSingleton('checkout/session');
            $session->setLastRealOrderId($order->getIncrementId());
            $session->setLastSuccessQuoteId($session->getQuote()->getId());
            $session->setLastQuoteId($session->getQuote()->getId());
            $session->setLastOrderId($order->getId());



            $cart2 = Mage::getSingleton('checkout/cart');
            $cart2->init();
            $cart2->truncate();
            $cart2->save();
            $cart2->getItems()->clear()->save();
            //
            $order->save();
            Mage::log('zooz payment success');
        } catch (Exception $ex) {
            Mage::log($ex->getMessage());
        }
    }

    public function cloneAddress() {
        // set Billing Address
        $addressData = array(
            'firstname' => 'Test',
            'lastname' => 'Test',
            'street' => 'Sample Street 10',
            'city' => 'Somewhere',
            'postcode' => '123456',
            'telephone' => '123456',
            'country_id' => 'US',
            'region_id' => 12, // id from directory_country_region table
        );

        $tempAddress = Mage::getModel('sales/order_address')
                ->setData($addressData)
                ->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_BILLING);

        return $tempAddress;
    }

}
