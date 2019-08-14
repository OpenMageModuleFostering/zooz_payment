<?php

/**
 * ZooZ Payments Integration
 *
 * @category    Zooz
 * @package     Zooz_Payments
 */

/**
 * Payment Api model
 *
 * @category   Zooz
 * @package    Zooz_Payments
 */
class Zooz_Payments_Model_Api
{
    /**
     * @var string Static sandbox mode
     */
    const ACTION_MODE_SANDBOX       = 'sandbox';

    /**
     * @var string Static production mode
     */
    const ACTION_MODE_PRODUCTION    = 'production';

    /**
     * @var string sandbox api url
     */
    const URI_SANDBOX               = 'https://sandbox.zooz.co/mobile/ZooZPaymentAPI';
    /**
     * @var string production api url
     */
    const URI_PRODUCTION            = 'https://app.zooz.com/mobile/ZooZPaymentAPI';

    /**
     * @var int response code success
     */
    const RESPONSE_CODE_SUCCESS = 0;
    /**
     * @var int response code failure
     */
    const RESPONSE_CODE_FAILURE = -1;

    /**
     * @var int Payment method status valid
     */
    const PAYMENT_METHOD_STATUS_VALID = 0;
    /**
     * @var int Payment method status expired
     */
    const PAYMENT_METHOD_STATUS_EXPIRED = 1;
    /**
     * @var int Payment method status invalid
     */
    const PAYMENT_METHOD_STATUS_NOTVALID = 2;

    /**
     * @var string config path
     */
    const XML_PATH_CONFIG_GROUP = 'payment/payments/';

    /**
     * Returns config based on provided field
     *
     * @param $field
     * @param null $storeId
     * @return mixed
     */
    private function getConfigData($field, $storeId = null)
    {
        $path = self::XML_PATH_CONFIG_GROUP . $field;
        return Mage::getStoreConfig($path, $storeId);
    }

    /**
     * Returns Request Model
     *
     * @return Zooz_Payments_Model_Payments_Request
     */
    protected function _getRequest()
    {
        return Mage::getModel('payments/payments_request');
    }

    /**
     * Return helper
     *
     * @return Zooz_Payments_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('payments');
    }

    /**
     * Gateway response wrapper
     *
     * @param string $text
     * @return string
     */
    protected function _wrapGatewayError($text)
    {
        return Mage::helper('payments')->__('Gateway error: %s', $text);
    }

    /**
     * Returns payment gateway uri depending on selected environment (sandbox/production)
     *
     * @return string
     */
    private function _getRequestUri()
    {
        if ($this->getConfigData('payment_mode') == self::ACTION_MODE_PRODUCTION) {
            return self::URI_PRODUCTION;
        }

        return self::URI_SANDBOX;
    }

    /**
     * Return Api Client
     *
     * @return Varien_Http_Client
     * @throws Zend_Http_Client_Exception
     */
    private function _getApiClient()
    {
        $client = new Varien_Http_Client();
        $client->setUri($this->_getRequestUri());
        $client->setConfig(array(
            'maxredirects'=>0,
            'timeout'=>30,

        ));
        $client->setHeaders(array(
            'programId' => $this->getConfigData('program_id'),
            'programKey' => Mage::helper('core')->decrypt($this->getConfigData('program_key')),
            'ZooZResponseType' => 'JSon'
        ));
        $client->setMethod(Zend_Http_Client::POST);

        return $client;
    }

    /**
     * Post request to gateway and return response
     *
     * @param Varien_Object $request
     * @return Zooz_Payments_Model_Payments_Result
     */
    protected function _postRequest(Varien_Object $request)
    {
        $helper = $this->_getHelper();
        $debugData = array('request' => $request);
        $result = Mage::getModel('payments/payments_result');

        $client = $this->_getApiClient();
        $client->setRawData(json_encode($request->getData()), 'application/json');

        try {
            $response = $client->request();
        } catch (Exception $ex) {
            $result
                ->setResponseCode(self::RESPONSE_CODE_FAILURE)
                ->setResponseReasonCode($ex->getCode())
                ->setResponseReasonText($ex->getMessage());
			
            $debugData['result'] = $result->getData();
//TODO implement debug
//            $this->_debug($debugData);
			Mage::log($ex->getMessage(),NULL,'debgs.log');
            Mage::throwException($this->_wrapGatewayError($ex->getMessage()));
        }

        $responseAsArray = json_decode($response->getBody(), true);

        try {
            if (!is_array($responseAsArray)) {
                throw new Zooz_Payments_Exception($helper->__('Not able to deserialize response'));
            }

            if ($responseAsArray['responseStatus'] != 0) {
                throw new Zooz_Payments_Exception(
                    $responseAsArray['responseObject']['errorDescription'],
                    $responseAsArray['responseObject']['responseErrorCode']
                );
            }
        } catch (Zooz_Payments_Exception $ex) {
            $message = Mage::helper('payments')->handleError($responseAsArray['responseObject']);
            $result
                ->setResponseCode(self::RESPONSE_CODE_FAILURE)
                ->setResponseReasonCode($ex->getFields())
	           ->setResponseReasonText($message);
	           
            $debugData['result'] = $result->getData();
//TODO implement debug
//            $this->_debug($debugData);

            return $result;
        }

        $result
            ->setResponseCode(self::RESPONSE_CODE_SUCCESS)
            ->addData($responseAsArray['responseObject']);


        $debugData['result'] = $result->getData();
//TODO implement debug
//        $this->_debug($debugData);
        return $result;
    }

    /**
     * To start the payment process, use the openPayment API call. This opens a request to the Zooz server,
     * using a secure channel.
     * The openPayment call returns a payment token for uniquely identifying the transaction later on.
     * The payment token is used to initiate payments, authorizations, refunds, voids, etc.; used for active operations.
     *
     * @param Mage_Payment_Model_Info $payment
     * @param $amount integer Order grand total
     * @param $customerLoginId integer customer login id saved in zooz api backend
     * @return Zooz_Payments_Model_Payments_Result
     */
    public function openPayment(Mage_Payment_Model_Info $payment, $amount, $customerLoginId)
    {

        $request = $this->_getRequest();
        /* @var $order Mage_Sales_Model_Order */
        
        if($payment->getOrder() !== null) {
            $order = $payment->getOrder();
            $shippingAmount = $order->getShippingAmount();

        } else {
            $order = Mage::getModel('checkout/session')->getQuote();
            $shippingAmount = $order->getShippingAddress()->getShippingAmount();

        }


        $requestData = array(
            'command' => 'openPayment',
            'paymentDetails' => array(
                'amount' => $amount,
                'shippingAmount' => $shippingAmount,
                'currencyCode' => $order->getBaseCurrencyCode(),
                'taxAmount' => $order->getBaseTaxAmount(),
                'invoice' => array(
                    'number' => $order->getIncrementId(),
                    'items' => array()
                ),
                'user' => array(
                    'firstName' => $order->getCustomerFirstname(),
                    'lastName' => $order->getCustomerLastname(),
                    'phone' => array(
                        'countryCode' => '',
                        'phoneNumber' => ''
                    ),
                    'email' => $order->getCustomerEmail()
                )
            ),
            'customerDetails' => array(
                'customerLoginID' => $customerLoginId
            )
        );
        
        if($payment->getOrder() !== null) {
            $items = $order->getAllItems();
        } else {
            $items = $order->getAllVisibleItems();
        }
        
        foreach($items as $key => $val) {
            $requestData['paymentDetails']['invoice']['items'][$key]['name'] = $val->getName();
            $requestData['paymentDetails']['invoice']['items'][$key]['id'] = $val->getProductId();
            $requestData['paymentDetails']['invoice']['items'][$key]['quantity'] = $val->getQtyOrdered();
            $requestData['paymentDetails']['invoice']['items'][$key]['price'] = $val->getPrice();
        }

        $billing = $order->getBillingAddress();
        
        if ($billing) {
            $phoneCode = Mage::helper('payments')->getPhoneCode($billing);

            $addressData = array(
                'countryCode' => $billing->getCountry(),
                'state' => $billing->getRegion(),
                'city' => $billing->getCity(),
                'address1' => $billing->getStreet1(),
                'address2' => $billing->getStreet2(),
                'zipCode' => $billing->getPostcode(),
                'firstName' => $billing->getFirstname(),
                'lastName' => $billing->getLastname(),
                'phone' => array(
                    'countryCode' => $phoneCode,
                    'phoneNumber' => Mage::helper('payments')->preparePhone($billing->getTelephone(), $phoneCode)
                )
            );

            $requestData['paymentDetails']['user']['phone']['countryCode'] = $phoneCode;
            $requestData['paymentDetails']['user']['phone']['phoneNumber'] = Mage::helper('payments')->preparePhone($billing->getTelephone(), $phoneCode);

            $requestData['paymentDetails']['user']['addresses'] = array();
            $requestData['paymentDetails']['user']['addresses']['billing'] = $addressData;
        }

        $shipping = $order->getShippingAddress();
        if ($shipping) {
            $phoneCode = Mage::helper('payments')->getPhoneCode($shipping);
            $addressData = array(
                'countryCode' => $shipping->getCountry(),
                'state' => $shipping->getRegion(),
                'city' => $shipping->getCity(),
                'address1' => $shipping->getStreet1(),
                'address2' => $shipping->getStreet2(),
                'zipCode' => $shipping->getPostcode(),
                'firstName' => $shipping->getFirstname(),
                'lastName' => $shipping->getLastname(),
                'phone' => array(
                    'countryCode' => $phoneCode,
                    'phoneNumber' => Mage::helper('payments')->preparePhone($shipping->getTelephone(), $phoneCode)
                )
            );

            $requestData['paymentDetails']['user']['addresses']['shipping'] = $addressData;
        }

        $request->addData($requestData);

        $response = $this->_postRequest($request);

        return $response;
    }

    /**
     * Check's if Zooz should save cards in API side
     *
     * @return bool
     */
    protected function _isSavingCardDataAllowed()
    {
        return Mage::getModel('payments/config')->isSavingCardDataAllowed();
    }

    /**
     * Adds credit card to payment
     *
     * @param Varien_Object|array $cardData
     * @param integer $customerLoginId
     * @param string $paymentToken
     * @return Zooz_Payments_Model_Payments_Result
     */
    public function addPaymentMethod($customerLoginId, $cardData, $paymentToken = null)
    {
        if ($paymentToken === null) {
            $bilingAddress = is_object($cardData->getBillingAddress()) ? $cardData->getBillingAddress() : null;
            $paymentToken = $this->_getToken($customerLoginId, $bilingAddress);
        }

        if (!($cardData instanceof Varien_Object)) {
            $cardData = new Varien_Object($cardData);
        }

        $request = $this->_getRequest();
        $requestData = array(
            'command' => 'addPaymentMethod',
            'paymentToken' => $paymentToken,
            'email' => $cardData->getHolderEmail(),
            'paymentMethod' => array(
                'paymentMethodType' => 'CreditCard',
                'paymentMethodDetails' => array(
                    'expirationDate' => $cardData->getCcExpMonth() . '/' . $cardData->getCcExpYear(),
                    'cardHolderName' => $cardData->getHolderFirstname() . ' ' . $cardData->getHolderLastname(),
                    'cardNumber' => $cardData->getCcNumber(),
                    'cvvNumber' => $cardData->getCcCvv()
                ),
                'configuration' => array(
                    'rememberPaymentMethod' => false
                )
            ),
        );

        if ($this->_isSavingCardDataAllowed() && $cardData->getCcSaveData()) {
            $requestData['paymentMethod']['configuration']['rememberPaymentMethod'] = true;
        }

        $request->addData($requestData);
        $response = $this->_postRequest($request);

        return $response;
    }
    
    /**
     * Remove customer saved credit card 
     * 
     * @param string $customerLoginId
     * @param string $paymentMethodToken
     * @return Zooz_Payments_Model_Payments_Result
     */
    public function removePaymentMethod($customerLoginId, $paymentMethodToken)
    {
        $request = $this->_getRequest();
        $requestData = array(
            'command' => 'removePaymentMethod',
            'customerLoginID' => $customerLoginId,
            'paymentMethodToken' => $paymentMethodToken
        );
        
        $request->addData($requestData);
        $response = $this->_postRequest($request);
        
        return $response;
    }

    /**
     * This call creates a secure session with Zooz server and allows you to add a credit card or remove it or
     * do other operations, which are not payment related.
     *
     * @param bool $customerLoginId
     * @return null|string
     */
    public function getToken($customerLoginId = false) {

        if(!$customerLoginId) {
            $customerLoginId = Mage::getModel('customer/session')->getCustomer()->getId();
        }

        return $this->_getToken($customerLoginId);
    }

    /**
     * Internal function to get token
     * See getToken description
     *
     * @param string $customerLoginId
     * @return string|null
     */
    private function _getToken($customerLoginId, $billingAddress = null)
    {
        $request = $this->_getRequest();
        $requestData = array(
            'command' => 'getToken',
            'tokenType' => 'customerToken',
            'registerDetails' => array(
                'currencyCode' => Mage::app()->getStore()->getCurrentCurrencyCode()
            ),
            'customerDetails' => array(
                'customerLoginID' => $customerLoginId
            )
        );
        
        if($billingAddress !== null) {
            $requestData['registerDetails']['billingAddress']['countryCode'] = $billingAddress->getCountryCode();
            $requestData['registerDetails']['billingAddress']['state'] = $billingAddress->getState();
            $requestData['registerDetails']['billingAddress']['city'] = $billingAddress->getCity();
            $requestData['registerDetails']['billingAddress']['address1'] = $billingAddress->getStreet();
            $requestData['registerDetails']['billingAddress']['zipCode'] = $billingAddress->getZipCode();
            $requestData['registerDetails']['billingAddress']['firstName'] = $billingAddress->getFirstname();
            $requestData['registerDetails']['billingAddress']['lastName'] = $billingAddress->getLastname();
            $requestData['registerDetails']['billingAddress']['phone']['countryCode'] = Mage::helper('payments')->getPhoneCode($billingAddress);
            $requestData['registerDetails']['billingAddress']['phone']['phoneNumber'] = $billingAddress->getPhoneNumber();
        }
        $request->addData($requestData);
        
        $response = $this->_postRequest($request);
        if ($response->getResponseCode() !== self::RESPONSE_CODE_SUCCESS) {
            return null;
        }

        return $response->getData('customerToken');
    }

    /**
     * Retrieves list of payment methods saved in Zooz
     *
     * @param $customerLoginId
     * @param bool $includeNotValid determines whether not valid and expired methods should be retrieved
     * @return Varien_Object[]
     */
    public function getPaymentMethods($customerLoginId, $includeNotValid = false)
    {
        $request = $this->_getRequest();
        $request->addData(array(
            'command' => 'getPaymentMethods',
            'customerLoginID' => $customerLoginId
        ));

        $methods = array();
        $response = $this->_postRequest($request);
        if ($response->getResponseCode() !== self::RESPONSE_CODE_SUCCESS
                || !$response->hasData('paymentMethods')
                || !is_array($response->getData('paymentMethods'))) {
            return $methods;
        }

        foreach ($response->getData('paymentMethods') as $methodData) {
            if (!$includeNotValid && $methodData['paymentMethodStatus'] != self::PAYMENT_METHOD_STATUS_VALID) {
                continue;
            }
            $method = new Varien_Object();
            $method->addData($methodData);
            $methods[] = $method;
        }

        return $methods;
    }

    /**
     * Retrieve complete payment details and payment statuses before and/or after commit or refund calls
     *
     * @param $customerLoginId
     * @return Zooz_Payments_Model_Payments_Result
     */
    public function getPaymentDetails($customerLoginId)
    {
        $request = $this->_getRequest();
        $request->addData(array(
            'command' => 'getPaymentDetails',
            'paymentToken' => $customerLoginId
        ));


        $response = $this->_postRequest($request);

        if ($response->getResponseCode() !== self::RESPONSE_CODE_SUCCESS
                || !$response->hasData('paymentMethods')
                || !is_array($response->getData('paymentMethods'))) {
            return $response;
        }

        return $response;
    }

    /**
     * Retrieves single saved payment method
     *
     * @param string $paymentMethodToken
     * @param string $customerLoginId
     * @return null|Varien_Object
     */
    public function getPaymentMethod($paymentMethodToken, $customerLoginId)
    {
        $paymentMethods = $this->getPaymentMethods($customerLoginId);
        foreach ($paymentMethods as $paymentMethod) {
            if ($paymentMethod->getData('paymentMethodToken') == $paymentMethodToken) {
                return $paymentMethod;
            }
        }

        return null;
    }

    /**
     * Update's billing in Zooz
     *
     * @param $customer_id
     * @param $address
     * @return null|string
     */
    public function updateBilling($customer_id, $address) {
        return $this->_getToken($customer_id, $address);
    }
}