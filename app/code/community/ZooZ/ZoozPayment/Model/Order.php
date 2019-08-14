<?php

class ZooZ_ZoozPayment_Model_Order extends Mage_Core_Model_Abstract {

    public function saveOrder($info = null, $giftInfo = null) {
        try {

            $session = Mage::getSingleton("customer/session");
            $customer = $session->getCustomer();
            $customer = Mage::getModel('customer/customer')->load($customer->getId());

            $notLogin = false;
            $storeId = Mage::app()->getStore()->getId();

            if (Mage::getSingleton('customer/session')->IsLoggedIn()) {
                $customer = Mage::getModel('customer/customer')->load($customer->getId());
                $storeId = $customer->getStoreId();
                $notLogin = true;
            }
            //Mage::log("login status");
            //Mage::log($notLogin);
           // Mage::log("login status");
            $transaction = Mage::getModel('core/resource_transaction');
            $reservedOrderId = Mage::getSingleton('eav/config')->getEntityType('order')->fetchNewIncrementId($storeId);

            $currency_code = Mage::app()->getStore()->getCurrentCurrencyCode();
            $order = Mage::getModel('sales/order')
                    ->setIncrementId($reservedOrderId)
                    ->setStoreId($storeId)
                    ->setQuoteId(0)
                    ->setGlobal_currency_code($currency_code)
                    ->setBase_currency_code($currency_code)
                    ->setStore_currency_code($currency_code)
                    ->setOrder_currency_code($currency_code);
            // if return gift message
            // get value from $info 
            $return_gift = true;
	#Mage::log($info);
            if ($return_gift) {
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
            }
            // if return gift message
            if ($notLogin) {
                // set Customer data
                $order->setCustomer_email($customer->getEmail())
                        ->setCustomerFirstname($customer->getFirstname())
                        ->setCustomerLastname($customer->getLastname())
                        ->setCustomerGroupId($customer->getGroupId())
                        ->setCustomer_is_guest(0)
                        ->setCustomer($customer);
            } else {
                // set Customer data
                $order->setCustomer_email('non-customer@gmail.com')
                        ->setCustomerFirstname('No')
                        ->setCustomerLastname('Name')
                        ->setCustomerGroupId(0)
                        ->setCustomer_is_guest(1);
                //->setCustomer($customer);	
                if ($info != null) {
                    $user = $info->user;
                    $order->setCustomer_email($user->email)
                            ->setCustomerFirstname($user->firstName)
                            ->setCustomerLastname($user->lastName)
                            ->setCustomerGroupId(0);
                }
            }
            if ($info != null) {

                $shippingAddress = Mage::getModel('sales/order_address');
                $shipp_address = $info->shippingAddress;
                //   $country_id =  Mage::getModel('directory/country')->load($info->country)->getCountryId();
                $shippingAddress->setStoreId($storeId)
                        ->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_SHIPPING)
                        ->setCustomerId($customer->getId())
                        ->setFirstname($shipp_address->firstName)
                        ->setLastname($shipp_address->lastName)
                        ->setStreet($shipp_address->street)
                        ->setCity($shipp_address->city)
                        ->setCountry_id($shipp_address->country)
                        ->setRegion($shipp_address->state)
                        ->setPostcode($shipp_address->zipCode)
                        ->setTelephone($user->phoneNumber);
                if ($notLogin) {

                    $billing = $customer->getDefaultBillingAddress();
                    if ($billing) {
                        $billingAddress = Mage::getModel('sales/order_address');
                        $billingAddress->setStoreId($storeId)
                                ->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_BILLING)
                                ->setFirstname($billing->getFirstname())
                                ->setMiddlename($billing->getMiddlename())
                                ->setLastname($billing->getLastname())
                                ->setStreet($billing->getStreet())
                                ->setCity($billing->getCity())
                                ->setCountry_id($billing->getCountryId())
                                ->setRegion($billing->getRegion())
                                ->setPostcode($billing->getPostcode());

                        $order->setBillingAddress($billingAddress);
                        $order->setShippingAddress($shippingAddress);
                    } else {
                        $billingAddress = clone $shippingAddress;
                        $shippingAddress->setSameAsBilling(true);
                        $order->setBillingAddress($billingAddress);
                        $order->setShippingAddress($shippingAddress);
                    }
                } else {

                    $billingAddress = clone $shippingAddress;
                    $shippingAddress->setSameAsBilling(true);
                    $order->setBillingAddress($billingAddress);
                    $order->setShippingAddress($shippingAddress);
                }
            } else if ($notLogin) {
                // set Billing Address
                $billing = $customer->getDefaultBillingAddress();
                $billingAddress = Mage::getModel('sales/order_address');
                if ($billing) {
                    $billingAddress->setStoreId($storeId)
                            ->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_BILLING)
                            ->setCustomerId($customer->getId())
                            ->setCustomerAddressId($customer->getDefaultBilling())
                            ->setCustomer_address_id($billing->getEntityId())
                            ->setPrefix($billing->getPrefix())
                            ->setFirstname($billing->getFirstname())
                            ->setMiddlename($billing->getMiddlename())
                            ->setLastname($billing->getLastname())
                            ->setSuffix($billing->getSuffix())
                            ->setCompany($billing->getCompany())
                            ->setStreet($billing->getStreet())
                            ->setCity($billing->getCity())
                            ->setCountry_id($billing->getCountryId())
                            ->setRegion($billing->getRegion())
                            ->setRegion_id($billing->getRegionId())
                            ->setPostcode($billing->getPostcode())
                            ->setTelephone($billing->getTelephone())
                            ->setFax($billing->getFax());
                    $order->setBillingAddress($billingAddress);
                } else {
                    $order->setBillingAddress($this->cloneAddress());
                }


                $shipping = $customer->getDefaultShippingAddress();
                $shippingAddress = Mage::getModel('sales/order_address');
                if ($shipping) {
                    $shippingAddress->setStoreId($storeId)
                            ->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_SHIPPING)
                            ->setCustomerId($customer->getId())
                            ->setCustomerAddressId($customer->getDefaultShipping())
                            ->setCustomer_address_id($shipping->getEntityId())
                            ->setPrefix($shipping->getPrefix())
                            ->setFirstname($shipping->getFirstname())
                            ->setMiddlename($shipping->getMiddlename())
                            ->setLastname($shipping->getLastname())
                            ->setSuffix($shipping->getSuffix())
                            ->setCompany($shipping->getCompany())
                            ->setStreet($shipping->getStreet())
                            ->setCity($shipping->getCity())
                            ->setCountry_id($shipping->getCountryId())
                            ->setRegion($shipping->getRegion())
                            ->setRegion_id($shipping->getRegionId())
                            ->setPostcode($shipping->getPostcode())
                            ->setTelephone($shipping->getTelephone())
                            ->setFax($shipping->getFax());
                    $order->setShippingAddress($shippingAddress);
                } else {
                    if ($billing) {
                        $shippingAddress = clone $billingAddress;
                        $shippingAddress->setSameAsBilling(false);

                        $order->setShippingAddress($shippingAddress)
                                ->setShipping_method('freeshipping_freeshipping')
                                ->setShippingDescription($this->getCarrierName('freeshipping'));
                    } else {
                        $billingAddress = $this->cloneAddress();
                        $shippingAddress = clone $billingAddress;
                        $shippingAddress->setSameAsBilling(false);

                        $order->setShippingAddress($shippingAddress);
                    }
                }
            } else {

                $billingAddress = $this->cloneAddress();
                $order->setBillingAddress($billingAddress);

                $shippingAddress = clone $billingAddress;
                $shippingAddress->setSameAsBilling(true);

                $order->setShippingAddress($shippingAddress);
            }

            $orderPayment = Mage::getModel('sales/order_payment')
                    ->setStoreId($storeId)
                    ->setCustomerPaymentId(0)
                    ->setMethod('zoozpayment')
                    ->setPo_number(' - ');
            $order->setPayment($orderPayment);

            $convertQuoteObj = Mage::getSingleton('sales/convert_quote');

            $cart = Mage::helper('checkout/cart')->getCart();
            $items = $cart->getQuote()->getAllVisibleItems();
            $subTotal = 0;
            $baseSubTotal = 0;

            foreach ($items as $item) {
                $itemdata = $item->getData();

                $orderItem = $convertQuoteObj->itemToOrderItem($item);

                $options = array();
                if ($productOptions = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct())) {
                    $options = $productOptions;
                }
                if ($addOptions = $item->getOptionByCode('additional_options')) {
                    $options['additional_options'] = unserialize($addOptions->getValue());
                }
                if ($options) {
                    $orderItem->setProductOptions($options);
                }
                if ($item->getParentItem()) {
                    $orderItem->setParentItem($orderObj->getItemByQuoteItemId($item->getParentItem()->getId()));
                }

                $subTotal += $itemdata['row_total'];
                $baseSubTotal += $itemdata['base_row_total'];
                $order->addItem($orderItem);
            }



	    	$shipping_price = $info->shippingAmount;
            $shipping_name = $info->shippingMethod;
            $shipping_carrier_name = $info->shippingCarrierName;
            $shipping_carrier_code = $info->shippingCarrierCode;
            $order->setSubtotal($subTotal)
                    ->setShippingMethod($shipping_carrier_code)
                    ->setShippingDescription($shipping_name . " (" . $shipping_carrier_name .")")
                    ->setBaseShippingAmount($shipping_price)
                    ->setShippingAmount($shipping_price)
                    ->setBaseSubtotal($baseSubTotal + $shipping_price)
                    ->setBaseTotalDue($subTotal + $shipping_price)
                    ->setGrandTotal($subTotal + $shipping_price)
                    ->setBaseGrandTotal($baseSubTotal);

            $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true);
            $order->setGiftMessageId($gift_id);
            $transaction->addObject($order);
            $transaction->addCommitCallback(array($order, 'place'));
            $transaction->addCommitCallback(array($order, 'save'));
            $transaction->save();

            //print_r($quote->getId()); die;

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
