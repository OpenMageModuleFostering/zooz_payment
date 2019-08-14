<?php

require_once(Mage::getBaseDir('lib') . '/zooz/zooz.extended.server.api.php');

class ZooZ_ZoozPayment_Model_Standard extends Mage_Payment_Model_Method_Abstract {

    protected $_code = 'zoozpayment';
    protected $_isInitializeNeeded = true;
    protected $_canUseInternal = false;
    protected $_canUseForMultishipping = false;

    /**
     * Return Order place redirect url
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl() {
        //when you click on place order you will be redirected on this url, if you don't want this action remove this method
        return Mage::getUrl('zoozpayment/standard/redirect', array('_secure' => true));
    }

    public function getAppUniqueId() {
        return $config = Mage::getStoreConfig('payment/zoozpayment/app_unique_id');
    }

    public function getAppKey() {
        return $config = Mage::getStoreConfig('payment/zoozpayment/app_key');
    }

    public function getIsSandBox() {
        return $config = Mage::getStoreConfig('payment/zoozpayment/sandbox_flag');
    }

    /**
     * Instantiate state and set it to state object
     * @param string $paymentAction
     * @param Varien_Object
     */
    public function initialize($paymentAction, $stateObject) {
        $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        $stateObject->setState($state);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);
    }

    public function processing($start = false) {
        if ($start == true) {

            $postFields = $this->getPostFieldFromCart();
        } else {
            $postFields = $this->getPostField();
        }
        if ($postFields != '') {
            // Flag to indicate whether sandbox environment should be used
            $isSandbox = false;
            if ($this->getIsSandBox()) {
                $isSandbox = true;
            }

            $url;
            $zoozServer;

            if ($isSandbox == true) {

                $zoozServer = 'https://sandbox.zooz.co';
                $url = $zoozServer . "/mobile/SecuredWebServlet";
            } else {

                $zoozServer = "https://app.zooz.com";
                $url = $zoozServer . "/mobile/SecuredWebServlet";
            }

            if (!function_exists('curl_init')) {
                Mage::getSingleton('checkout/session')->addError('Sorry cURL is not installed!');
                return Mage::getUrl('checkout/cart');
            }

            // OK cool - then let's create a new cURL resource handle
            $ch = curl_init();

            // Now set some options
            // Set URL
            curl_setopt($ch, CURLOPT_URL, $url);

            //Header fields: ZooZUniqueID, ZooZAppKey, ZooZResponseType
            $header = array('ZooZUniqueID: ' . $this->getAppUniqueId(), 'ZooZAppKey: ' . $this->getAppKey(), 'ZooZResponseType: NVP');
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

            // If it is a post request
            curl_setopt($ch, CURLOPT_POST, 1);

            // Timeout in seconds
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            // If you are experiencing issues recieving the token on the sandbox environment, please set this option
            if ($isSandbox) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            }

            //Mandatory POST fields: cmd, amount, currencyCode
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

            ob_start();

            curl_exec($ch);

            $result = ob_get_contents();

            ob_end_clean();

            curl_close($ch);

            parse_str($result);


            if ($statusCode == 0) {

                // Get token from ZooZ server
                $trimmedSessionToken = rtrim($sessionToken, "\n");

                // Send token back to page
                if ($start == true) {
                    $success = Mage::getUrl('zoozpayment/standard/successexpress');
                } else {
                    $success = Mage::getUrl('zoozpayment/standard/success');
                }

                $cancel = Mage::getUrl('zoozpayment/standard/cancel');

                $redirect = $zoozServer . "/mobile/mobileweb/zooz-checkout.jsp?";
                $redirect .= "token=" . $trimmedSessionToken;
                $redirect .= "&uniqueID=" . $this->getAppUniqueId();
                $redirect .= "&largeViewport=true";
                $redirect .= "&returnUrl=" . $success;
                $redirect .= "&cancelUrl=" . $cancel;
                $redirect .= "&isShippingRequired=false";

                // process order
                $session = Mage::getSingleton('checkout/session');
                $session->setZoozQuoteId($session->getQuoteId());

                //Mage::log($redirect);
                return $redirect;

                $session->unsQuoteId();
                $session->unsRedirectUrl();
            } else if (isset($errorMessage)) {
                Mage::getSingleton('checkout/session')->addError("Error to open transaction to ZooZ server. " . $errorMessage);
                return Mage::getUrl('checkout/cart');
            }
            //Close the cURL resource, and free system resources
        } else {
            return Mage::getUrl('checkout/cart');
        }
    }

    public function ajaxprocessing($start = false) {
        if ($start == true) {

            $postFields = $this->getPostFieldFromCart();
        } else {
            $postFields = $this->getPostField();
        }


        if ($postFields != '') {
            if (Mage::getStoreConfig('sales/gift_options/allow_order', null)) {
                $postFields.="&featureProvider=102";
                $postFields.="&providerSupportedFeatures=[100]";
            } else {
                $postFields.="&providerSupportedFeatures=";
            }

           // Mage::log($postFields);    



            // Flag to indicate whether sandbox environment should be used
            $isSandbox = false;
            if ($this->getIsSandBox()) {
                $isSandbox = true;
            }

            $url;
            $zoozServer;

            if ($isSandbox == true) {

                $zoozServer = 'https://sandbox.zooz.co';
                $url = $zoozServer . "/mobile/SecuredWebServlet";
            } else {

                $zoozServer = "https://app.zooz.com";
                $url = $zoozServer . "/mobile/SecuredWebServlet";
            }

            // is cURL installed yet?

            if (!function_exists('curl_init')) {
                Mage::getSingleton('checkout/session')->addError('Sorry cURL is not installed!');
                return Mage::getUrl('checkout/cart');
            }

            // OK cool - then let's create a new cURL resource handle
            $ch = curl_init();

            // Now set some options
            // Set URL
            curl_setopt($ch, CURLOPT_URL, $url);

            //Header fields: ZooZUniqueID, ZooZAppKey, ZooZResponseType
            $header = array('ZooZUniqueID: ' . $this->getAppUniqueId(), 'ZooZAppKey: ' . $this->getAppKey(), 'ZooZResponseType: NVP');
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

            // If it is a post request
            curl_setopt($ch, CURLOPT_POST, 1);

            // Timeout in seconds
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            // If you are experiencing issues recieving the token on the sandbox environment, please set this option
            if ($isSandbox) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            }

            //Mandatory POST fields: cmd, amount, currencyCode
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

            ob_start();

            curl_exec($ch);

            $result = ob_get_contents();

            ob_end_clean();

            curl_close($ch);

            parse_str($result);



            if ($statusCode == 0) {
                // Get token from ZooZ server
                $trimmedSessionToken = rtrim($sessionToken, "\n");

                $session = Mage::getSingleton('checkout/session');
                $session->setZoozQuoteId($session->getQuoteId());
                if ($start == true)
                    $typecheckout = 1;
                else
                    $typecheckout = 0;
                Mage::getSingleton('core/session')->setZooztype($typecheckout);
                return $trimmedSessionToken;
                //return $trimmedSessionToken;
                // Send token back to page
            } else if (isset($errorMessage)) {
                Mage::getSingleton('checkout/session')->addError("Error to open transaction to ZooZ server. " . $errorMessage);
                return Mage::getUrl('checkout/cart');
            }
            // Close the cURL resource, and free system resources
        } else {

            return Mage::getUrl('checkout/cart');
        }
    }

    public function getPostFieldFromCart() {

        //Mage::log("getPostFieldFromCart");
        $postFields = "";
        $cart = Mage::helper('checkout/cart')->getCart();
        $itemsCount = $cart->getItemsCount();

        if ($itemsCount > 0) {
            $data = $cart->getQuote()->getData();

            //Mage::log($data); 
            // cmd            	
            $postFields .= "cmd=openTrx";
            $postFields .= "&amount=" . $data['grand_total'];
            $postFields .= "&currencyCode=" . $data['quote_currency_code'];
            //$postFields .= "&taxAmount=".$data['tax_amount'];
            //shipping
            $carrier = Mage::helper('zoozpayment/carrier');
            $carrier->setCarrier('freeshipping');
            $carrier_temp = '';
            $carrier_temp_0 = '';
            if ($carrier->check()) {
                $carrier_temp_0 = "{name:'" . $carrier->getAllowedMethods() . "', price: " . (float) $carrier->getAllowedPrice() . "}";
                $carrier_temp.=$carrier_temp_0;
            }
            $carrier->setCarrier('flatrate');
            $carrier_temp_1 = '';
            if ($carrier->check()) {
                $cart = Mage::helper('checkout/cart')->getCart();
                $items = $cart->getQuote()->getAllVisibleItems();
                $total = 0;
                $price_total = 0;
                foreach ($items as $item) {
                    $total+=$item->getQty();
                    //    $price_total+= $item->getPrice()*$item->getQty();
                }
                if ($carrier->getAllowedType() == "I") {
                    $price = $total * (float) $carrier->getAllowedPrice();
                } elseif ($carrier->getAllowedType() == "O") {
                    $price = (float) $carrier->getAllowedPrice();
                } else {
                    $price = 0;
                }
                if ($carrier->getAllowedHandlingType() == 'F') {
                    $price_handing = (float) $carrier->getAllowedHandlingFee();
                    $price_handing = number_format($price_handing, 2);
                }
                if ($carrier->getAllowedHandlingType() == 'P') {

                    $price_handing = ((float) $carrier->getAllowedHandlingFee() * $price) / 100;
                    $price_handing = number_format($price_handing, 2);
                }
                $price = $price + $price_handing;
                $carrier_temp_1 = "{name:'" . $carrier->getAllowedMethods() . "', price: " . $price . "}";
                if ($carrier_temp != '')
                    $carrier_temp.="," . $carrier_temp_1;
                else
                    $carrier_temp = $carrier_temp_1;
            }
            if ($carrier_temp != '') {
                $postFields.="&isShippingRequired=true";
                $postFields.= "&shippingMethods=[" . $carrier_temp . "]";
            } else {
                $postFields.="&isShippingRequired=false";
            }
            //shipping
            //
        	// customer           
            if (Mage::getSingleton('customer/session')->isLoggedIn()) {

                /* Get the customer data */
                $customer = Mage::getSingleton('customer/session')->getCustomer();
                /* Get the customer's first name */
                $firstname = $customer->getFirstname();
                /* Get the customer's last name */
                $lastname = $customer->getLastname();
                /* Get the customer's email address */
                $email = $customer->getEmail();
                $_billingData = $customer->getDefaultBillingAddress();
                $_shippingData = $customer->getDefaultShippingAddress();

                $postFields .= "&user.idNumber=" . $customer->getId();
                $postFields .= "&user.email=" . $email;
                $postFields .= "&user.firstName=" . $firstname;
                $postFields .= "&user.lastName=" . $lastname;
                if (is_object($_billingData)) {
                    $postFields .= "&user.phone.phoneNumber=" . $_billingData->getTelephone();
                }


                //$postFields .= "&user.additionalDetails=Payment for ".$data['entity_id'];
                //Billing and Shipping
                if (is_object($_billingData)) {
                    $postFields .= "&billingAddress.firstName=" . $_billingData['firstname'];
                    $postFields .= "&billingAddress.lastName=" . $_billingData['lastname'];
                    //$postFields .= "&billingAddress.phone.countryCode=USA";
                    //$postFields .= "&billingAddress.phone.number=1222323".$_billingData['telephone'];
                    $postFields .= "&billingAddress.street=" . $_billingData['street'];
                    $postFields .= "&billingAddress.city=" . $_billingData['city'];
                    //$postFields .= "&billingAddress.state=22";
                    $postFields .= "&billingAddress.state=".$_billingData['region'];
                    $postFields .= "&billingAddress.country=" . $_billingData['country_id'];
                    $postFields .= "&billingAddress.zipCode=" . $_billingData['postcode'];
                }

                // shipping address
                if (is_object($_shippingData)) {
                    $postFields .= "&shippingAddress.firstName=" . $_shippingData['firstname'];
                    $postFields .= "&shippingAddress.lastName=" . $_shippingData['lastname'];
                    //$postFields .= "&shippingAddress.phone.countryCode=2323";
                    //$postFields .= "&shippingAddress.phone.number=232323".$_shippingData['telephone'];
                    $postFields .= "&shippingAddress.street=" . $_shippingData['street'];
                    $postFields .= "&shippingAddress.city=" . $_shippingData['city'];
                    //$postFields .= "&shippingAddress.state=22";
                    $postFields .= "&shippingAddress.state=".$_shippingData['region'];
                    $postFields .= "&shippingAddress.country=" . $_shippingData['country_id'];
                    $postFields .= "&shippingAddress.zipCode=" . $_shippingData['postcode'];
                }
            } else {
                
            }
            // invoice
            $postFields .= "&invoice.number=" . $data['entity_id'];
            $postFields .= "&invoice.additionalDetails=" . $data['entity_id'] . ': $' . $data['base_grand_total'];

            // items
            $cartItems = $cart->getQuote()->getAllVisibleItems();
            $i = 1;
            $tax = 0;
            foreach ($cartItems as $item) {
                $itemdata = $item->getData();

                $postFields .= "&invoice.items(" . $i . ").id=" . $itemdata['product_id'];
                $postFields .= "&invoice.items(" . $i . ").name=" . $itemdata['name'];
                $postFields .= "&invoice.items(" . $i . ").quantity=" . $itemdata['qty'];
                $postFields .= "&invoice.items(" . $i . ").price=" . $itemdata['price'];
                $tax += ($item->getData('tax_percent') * $itemdata['price'] * $itemdata['qty']) / 100;
                $i++;
            }
        }

        $postFields .= "&taxAmount=" . $tax;

        //Mage::log($postFields);
        return $postFields;
    }

    public function getPostField() {
        //Mage::log("getPostField");
        $postFields = "";
        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getZoozQuoteId(true));
        if ($session->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
            if ($order->getId()) {
                $_orderData = $order->getData();
                $_shippingData = $order->getShippingAddress()->getData();
                $_billingData = $order->getBillingAddress()->getData();

                // cmd            	
                $postFields .= "cmd=openTrx";
                $postFields .= "&amount=" . $_orderData['grand_total'];
                $postFields .= "&currencyCode=" . $_orderData['order_currency_code'];
                $postFields .= "&taxAmount=" . $_orderData['tax_amount'];
                $postFields.="&isShippingRequired=false";
                // customer
                $postFields .= "&user.idNumber=" . $_orderData['customer_id'];
                $postFields .= "&user.email=" . $_orderData['customer_email'];
                $postFields .= "&user.firstName=" . $_orderData['customer_firstname'];
                $postFields .= "&user.lastName=" . $_orderData['customer_lastname'];
                $postFields .= "&user.phone.phoneNumber=" . $_shippingData['telephone'];
                $postFields .= "&user.additionalDetails=Payment for Invoice #" . $_orderData['increment_id'];

                // invoice
                $postFields .= "&invoice.number=" . $_orderData['increment_id'];
                $postFields .= "&invoice.additionalDetails=" . $_orderData['shipping_description'] . ': $' . $_orderData['shipping_amount'];

                // items
                $items = $order->getAllItems();
                $i = 1;
                foreach ($items as $itemId => $item) {
                    $postFields .= "&invoice.items(" . $i . ").id=" . $item->getProductId();
                    $postFields .= "&invoice.items(" . $i . ").name=" . $item->getName();
                    $postFields .= "&invoice.items(" . $i . ").quantity=" . $item->getQtyToInvoice();
                    $postFields .= "&invoice.items(" . $i . ").price=" . $item->getPrice();
                    $i++;
                }

                // billing address
                $postFields .= "&billingAddress.firstName=" . $_billingData['firstname'];
                $postFields .= "&billingAddress.lastName=" . $_billingData['lastname'];
                $postFields .= "&billingAddress.phone.countryCode=USA";
                $postFields .= "&billingAddress.phone.number=1222323" . $_billingData['telephone'];
                $postFields .= "&billingAddress.street=" . $_billingData['street'];
                $postFields .= "&billingAddress.city=" . $_billingData['city'];
                //$postFields .= "&billingAddress.state=22";
                $postFields .= "&billingAddress.state=".$_billingData['region'];
                $postFields .= "&billingAddress.country=" . $_billingData['country_id'];
                $postFields .= "&billingAddress.zipCode=" . $_billingData['postcode'];

                // shipping address
                $postFields .= "&shippingAddress.firstName=" . $_shippingData['firstname'];
                $postFields .= "&shippingAddress.lastName=" . $_shippingData['lastname'];
                $postFields .= "&shippingAddress.phone.countryCode=2323";
                $postFields .= "&shippingAddress.phone.number=232323" . $_shippingData['telephone'];
                $postFields .= "&shippingAddress.street=" . $_shippingData['street'];
                $postFields .= "&shippingAddress.city=" . $_shippingData['city'];
                //$postFields .= "&shippingAddress.state=22";
                $postFields .= "&shippingAddress.state=".$_shippingData['region'];
                $postFields .= "&shippingAddress.country=" . $_shippingData['country_id'];
                $postFields .= "&shippingAddress.zipCode=" . $_shippingData['postcode'];
            }
        }


        return $postFields;
    }

    public function successprocessing() {
        // Flag to indicate whether sandbox environment should be used
        $isSandbox = false;

        if ($this->getIsSandBox()) {
            $isSandbox = true;
        }

        $url;

        if ($isSandbox == true) {

            $zoozServer = 'https://sandbox.zooz.co';
            $url = $zoozServer . "/mobile/SecuredWebServlet";
        } else {

            $zoozServer = "https://app.zooz.com";
            $url = $zoozServer . "/mobile/SecuredWebServlet";
        }
        // is cURL installed yet?

        if (!function_exists('curl_init')) {
            Mage::getSingleton('checkout/session')->addError('Sorry cURL is not installed!');
            return Mage::getUrl('checkout/cart');
        }

        // OK cool - then let's create a new cURL resource handle
        $ch = curl_init();

        // Now set some options
        // Set URL
        curl_setopt($ch, CURLOPT_URL, $url);

        //Header fields: ZooZUniqueID, ZooZAppKey, ZooZResponseType
        $header = array('ZooZUniqueID: ' . $this->getAppUniqueId(), 'ZooZAppKey: ' . $this->getAppKey(), 'ZooZResponseType: NVP');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        // If it is a post request
        curl_setopt($ch, CURLOPT_POST, 1);

        // Timeout in seconds
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        // If you are experiencing issues recieving the token on the sandbox environment, please set this option
        if ($isSandbox) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        }

        $params = Mage::app()->getRequest()->getParams();

        $transactionID = $params['transactionID'];

        $sessionToken = $params['sessionToken'];

        curl_setopt($ch, CURLOPT_POSTFIELDS, "cmd=verifyTrx&transactionID=" . $transactionID);

        ob_start();

        curl_exec($ch);

        $result = ob_get_contents();

        ob_end_clean();

        parse_str($result);

        curl_close($ch);

        // create order with billding return
        $zooz_useremail = Mage::getStoreConfig('payment/zoozpayment/zooz_useremail');
        $zooz_serverkey = Mage::getStoreConfig('payment/zoozpayment/zooz_serverkey');

        if ($isSandbox) {
            $zooz = new ZooZExtendedServerAPI($zooz_useremail, $zooz_serverkey, true);
        } else {
            $zooz = new ZooZExtendedServerAPI($zooz_useremail, $zooz_serverkey, false);
        }
        //
        //$info = $zooz->getTransactionDetailsByTransactionID('4QQ6RIRDNNKI7GN37JLHI7BSAI');

        $info = $zooz->getTransactionDetailsByTransactionID($transactionID);

        //Get Gift information from Params
        $giftInfo =  $params['gift'];     
        //print_r($giftInfo);
        //die();
                

         //Mage::log($info);
        //
        Mage::getModel('zoozpayment/order')->saveOrder($info,$giftInfo);
        // create order with billding return
        //Mage::log($result);
        return $statusCode;
    }

}
