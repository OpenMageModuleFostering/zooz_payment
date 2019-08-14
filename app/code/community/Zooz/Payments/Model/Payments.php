<?php
/**
 * ZooZ Payments Integration
 *
 * @category    Zooz
 * @package     Zooz_Payments
 */

class Zooz_Payments_Model_Payments extends Mage_Payment_Model_Method_Cc {
    
    const ACTION_MODE_SANDBOX       = 'sandbox';
    const ACTION_MODE_PRODUCTION    = 'production';
    
    const REQUEST_METHOD_CC         = 'CC';
    const REQUEST_METHOD_ECHECK     = 'ECHECK';

    const REQUEST_TYPE_AUTH_CAPTURE = 'AUTH_CAPTURE';
    const REQUEST_TYPE_AUTH_ONLY    = 'AUTH_ONLY';
    const REQUEST_TYPE_PRIOR_AUTH_CAPTURE = 'PRIOR_AUTH_CAPTURE';
    const REQUEST_TYPE_CREDIT       = 'CREDIT';
    const REQUEST_TYPE_VOID         = 'VOID';
    
    const RESPONSE_CODE_ERROR       = -1;
    const RESPONSE_CODE_SUCCESS     = 0;

    const URI_SANDBOX               = 'https://sandbox.zooz.co/mobile/ZooZPaymentAPI';
    const URI_PRODUCTION            = 'https://app.zooz.com/mobile/ZooZPaymentAPI';
    
    const REFUND_REASON_GENERAL             = 'GENERAL_REFUND';
    const REFUND_REASON_SERVICE_NOT_RENDER  = 'SERVICE_NOT_RENDER';
    const REFUND_REASON_GOODS_NOT_PROVIDED  = 'GOODS_NOT_PROVIDED';
    const REFUND_REASON_GOODS_RETURNED      = 'GOODS_RETURNED';
    
    const METHOD_CODE = 'payments';
    
    private static $sandbox   = true;
    private static $uniqueID  = '';
    private static $appKey    = '';
    
    protected $_code  = self::METHOD_CODE;
    
    /**
     * Form block type
     */
    protected $_formBlockType = 'payments/form_payments';

    /**
     * Info block type
     */
    protected $_infoBlockType = 'payments/info_payments';
    
    /**
     * Availability options
     */
    protected $_isGateway               = true;
    protected $_canAuthorize            = true;
    protected $_canCapture              = true;
    protected $_canCapturePartial       = false;
    protected $_canRefund               = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid                 = true;
    protected $_canUseInternal          = true;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = true;
    protected $_canSaveCc = false;
    protected $_canFetchTransactionInfo = true;

    private $_paymentToken = null;
    private $_paymentMethodToken = null;
    
    public function _construct() 
    {
        $this->_init('debug/zooz');
    }
    
    /**
     * Send authorize request to gateway
     *
     * @param  Varien_Object $payment
     * @param  float $amount
     * @return Zooz_Payments_Model_Payments
     */
    public function authorize(Varien_Object $payment, $amount)
    {

        if ($amount <= 0) {
            Mage::throwException(Mage::helper('payments')->__('Invalid amount for authorization.'));
        }

        $this->_initCardsStorage($payment);

        if($payment->getIsIframe()) {
            $this->_paymentToken = $payment->getCcPaymentToken();
            $this->_paymentMethodToken = $payment->getCcSavedCard();

            try {
                $this->_update($payment, $amount);
            }catch(Exception $e){}
        } else {
            $this->_openPayment($payment, $amount);
            if ($this->_isSavingCardDataAllowed() && $payment->getCcSavedCard()) {
                $paymentMethod = $this->_getSavedCard($payment->getCcSavedCard(), $this->_getCustomerLoginId($payment));
                if ($paymentMethod !== null) {
                    $this->_paymentMethodToken = $payment->getCcSavedCard();
                } else {
                    Mage::throwException('Invalid credit card selected');
                }
            } else {
                $this->_addPaymentMethod($payment);
            }
        }
        
        $this->_place($payment, $amount, self::REQUEST_TYPE_AUTH_ONLY);
        $payment->setSkipTransactionCreation(true);

        return $this;
    }

    /**
     * Send capture request to gateway
     *
     * @param Varien_Object $payment
     * @param float $amount
     * @return Zooz_Payments_Model_Payments
     */
    public function capture(Varien_Object $payment, $amount)
    {

        if ($amount <= 0) {
            Mage::throwException(Mage::helper('payments')->__('Invalid amount for capture.'));
        }
        
        $this->_initCardsStorage($payment);
        if ($this->_isPreauthorizeCapture($payment)) {
            $this->_preauthorizeCapture($payment, $amount);
        } else {
                if($payment->getIsIframe()) {
                    $this->_paymentToken = $payment->getCcPaymentToken();
                    $this->_paymentMethodToken = $payment->getCcSavedCard();
                    try {
                        $this->_update($payment, $amount);
                    }catch(Exception $e){}
            } else {
                $this->_openPayment($payment, $amount);
                if ($this->_isSavingCardDataAllowed() && $payment->getCcSavedCard()) {
                     $paymentMethod = $this->_getSavedCard($payment->getCcSavedCard(), $this->_getCustomerLoginId($payment));
                    if ($paymentMethod !== null) {
                        $this->_paymentMethodToken = $payment->getCcSavedCard();
                    } else {
                        Mage::throwException('Invalid credit card selected');
                    }
                } else {
                    $this->_addPaymentMethod($payment);
                }
            }
            $this->_place($payment, $amount, self::REQUEST_TYPE_AUTH_CAPTURE);
        }

        $payment->setSkipTransactionCreation(true);
        return $this;
    }
    
    /**
     * Void the payment
     * 
     * @param Varien_Object $payment
     * @param type $amount
     * @return Zooz_Payments_Model_Payments
     */
    public function void(Varien_Object $payment)
    {
        $cardsStorage = $this->getCardsStorage($payment);

        $messages = array();
        $isSuccessful = false;
        $isFiled = false;
        foreach($cardsStorage->getCards() as $card) {
            try {
                $newTransaction = $this->_voidCardTransaction($payment, $card);
                $messages[] = $newTransaction->getMessage();
                $isSuccessful = true;
            } catch (Exception $e) {
                $messages[] = $e->getMessage();
                $isFiled = true;
                continue;
            }
            $cardsStorage->updateCard($card);
        }

        if ($isFiled) {
            $this->_processFailureMultitransactionAction($payment, $messages, $isSuccessful);
        }

        $payment->setSkipTransactionCreation(true);
        return $this;
    }
    
    /**
     * Cancel the payment through gateway
     *
     * @param  Varien_Object $payment
     * @return Zooz_Payments_Model_Payments
     */
    public function cancel(Varien_Object $payment)
    {
        return $this->void($payment);
    }
    
    /**
     * Check refund availability
     *
     * @return bool
     */
    public function canRefund()
    {
        
        foreach($this->getCardsStorage()->getCards() as $card) {
            $lastTransaction = $this->getInfoInstance()->getTransaction($card->getLastTransId());
            if ($lastTransaction
                && $lastTransaction->getTxnType() == Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE
                && !$lastTransaction->getIsClosed()
            ) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Refund the amount with transaction id
     *
     * @param Varien_Object $payment
     * @param float $amount
     * @return Mage_Paygate_Model_Authorizenet
     * @throws Mage_Core_Exception
     */
    public function refund(Varien_Object $payment, $requestedAmount)
    {
        $cardsStorage = $this->getCardsStorage($payment);

        if ($this->_formatAmount(
                $cardsStorage->getCapturedAmount() - $cardsStorage->getRefundedAmount()
            ) < $requestedAmount
        ) {
            Mage::throwException(Mage::helper('payments')->__('Invalid amount for refund.'));
        }
        
        $messages = array();
        $isSuccessful = false;
        $isFiled = false;
        foreach($cardsStorage->getCards() as $card) {
            if ($requestedAmount > 0) {
                $cardAmountForRefund = $this->_formatAmount($card->getCapturedAmount() - $card->getRefundedAmount());
                if ($cardAmountForRefund <= 0) {
                    continue;
                }
                if ($cardAmountForRefund > $requestedAmount) {
                    $cardAmountForRefund = $requestedAmount;
                }
                try {
                    $newTransaction = $this->_refundCardTransaction($payment, $cardAmountForRefund, $card);
                    $messages[] = $newTransaction->getMessage();
                    $isSuccessful = true;
                } catch (Exception $e) {
                    $messages[] = $e->getMessage();
                    $isFiled = true;
                    continue;
                }
                $card->setRefundedAmount($this->_formatAmount($card->getRefundedAmount() + $cardAmountForRefund));
                $cardsStorage->updateCard($card);
                $requestedAmount = $this->_formatAmount($requestedAmount - $cardAmountForRefund);
            } else {
                $payment->setSkipTransactionCreation(true);
                return $this;
            }
        }

        if ($isFiled) {
            $this->_processFailureMultitransactionAction($payment, $messages, $isSuccessful);
        }

        $payment->setSkipTransactionCreation(true);
        return $this;
    }

    /**
     * Retrieves payment token
     *
     * @param $payment
     * @param $amount
     * @return null
     */
    public function getPaymentToken($payment, $amount)
    {
        $result = $this->_openPayment($payment, $amount);
        return $this->_paymentToken;
    }


    /**
     * Opens payments on api side
     *
     * @param $payment
     * @param $amount
     * @return $this
     */
    protected function _openPayment($payment, $amount)
    {

        $api = Mage::getModel('payments/api');
        $result = $api->openPayment($payment, $amount, $this->_getCustomerLoginId($payment));
        $this->_paymentToken = $result->getData('paymentToken');

        return $this;
    }

    /**
     * Adds credit card to payment
     *
     * @param Mage_Payment_Model_Info $payment
     * @return $this
     */
    private function _addPaymentMethod($payment)
    {
        $checkout = $this->_getSession()->getQuote();
        $billAddress = $checkout->getBillingAddress();
        $info = $this->getInfoInstance();
        $cardData = new Varien_Object();
        $cardData
            ->setHolderEmail($payment->getOrder()->getCustomerEmail())
            ->setHolderFirstname($billAddress->getFirstname())
            ->setHolderLastname($billAddress->getLastname())
            ->setCcExpMonth($info->getCcExpMonth())
            ->setCcExpYear($info->getCcExpYear())
            ->setCcNumber($info->getCcNumber())
            ->setCcCvv($info->getCcCid())
            ->setCcSaveData($info->getCcSaveData());

        $api = Mage::getModel('payments/api');
        $response = $api->addPaymentMethod($this->_getCustomerLoginId($payment), $cardData, $this->_paymentToken);

        $this->_paymentMethodToken = $response->getData('paymentMethodToken');
        
        return $this;
    }

    /**
     * Send request with new payment to gateway
     *
     * @param Mage_Payment_Model_Info $payment
     * @param float $amount
     * @param string $requestType
     * @return Zooz_Payments_Model_Payments
     * @throws Mage_Core_Exception
     */
    protected function _place($payment, $amount, $requestType)
    {
        $payment->setAmount($amount);

        $info = $this->getInfoInstance();
        $request = $this->_getRequest();
        $request->addData(array(
            'paymentToken' => $this->_paymentToken,
            'ipAddress' => Mage::app()->getRequest()->getServer('REMOTE_ADDR'),
            'paymentMethod' => array(
                'paymentMethodType' => 'CreditCard',
                'paymentMethodToken' => $this->_paymentMethodToken,
                'paymentMethodDetails' => array(
                    'cvvNumber' => $info->getCcCid()
                )
            )
        ));

        switch ($requestType) {
            case self::REQUEST_TYPE_AUTH_ONLY:
                $request->setCommand('authorizePayment');
                $newTransactionType = Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH;
                $defaultExceptionMessage = Mage::helper('payments')->__('Payment authorization error.');
                break;
            case self::REQUEST_TYPE_AUTH_CAPTURE:
                $request->setCommand('sale');
                $newTransactionType = Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE;
                $defaultExceptionMessage = Mage::helper('payments')->__('Payment capturing error.');
                break;
        }

        $result = $this->_postRequest($request);

        switch ($result->getResponseCode()) {
            case self::RESPONSE_CODE_SUCCESS:
                $this->getCardsStorage($payment)->flushCards();
                $card = $this->_registerCard($result, $payment);
                $this->_addTransaction(
                    $payment,
                    $card->getLastTransId(),
                    $newTransactionType,
                    array('is_transaction_closed' => 0),
                    array($this->_realTransactionIdKey => $card->getLastTransId()),
                    Mage::helper('payments')->getTransactionMessage(
                        $payment, $requestType, $card->getLastTransId(), $card, $amount
                    )
                );
                if ($requestType == self::REQUEST_TYPE_AUTH_CAPTURE) {
                    $card->setCapturedAmount($card->getProcessedAmount());
                    $this->getCardsStorage($payment)->updateCard($card);
                }
                return $this;
            case self::RESPONSE_CODE_ERROR:
                Mage::throwException($this->_wrapGatewayError($result->getResponseReasonText()));
            default:
                Mage::throwException($defaultExceptionMessage);
        }
        return $this;
    }

    /**
     * Update transaction with order id
     *
     * @param Varien_Object $payment
     * @param float $amount
     * @param string $requestType
     * @return Zooz_Payments_Model_Payments
     * @throws Mage_Core_Exception
     */
    protected function _update($payment, $amount)
    {
        $request = $this->_getRequest();

        $requestData = array(
            'command' => 'updatePaymentAndInvoice',
            'paymentToken' => $this->_paymentToken,
            'amount' => $amount,
            'currencyCode' => $payment->getOrder()->getBaseCurrencyCode(),
            'invoice' => array(
                'number' => $payment->getOrder()->getIncrementId(),
            ),
        );
        $request->addData($requestData);
        $defaultExceptionMessage = Mage::helper('payments')->__('Payment authorization error.');

        $result = $this->_postRequest($request);

        switch ($result->getResponseCode()) {
            case self::RESPONSE_CODE_SUCCESS:
                return true;
            case self::RESPONSE_CODE_ERROR:
                Mage::throwException($this->_wrapGatewayError($result->getResponseReasonText()));
            default:
                Mage::throwException($defaultExceptionMessage);
        }
        return $this;
    }

    /**
     * Return true if there are authorized transactions
     *
     * @param Mage_Payment_Model_Info $payment
     * @return bool
     */
    protected function _isPreauthorizeCapture($payment)
    {
        if ($this->getCardsStorage()->getCardsCount() <= 0) {
            return false;
        }
        foreach($this->getCardsStorage()->getCards() as $card) {
            $lastTransaction = $payment->getTransaction($card->getLastTransId());
            if (!$lastTransaction
                || $lastTransaction->getTxnType() != Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH
            ) {
                return false;
            }
        }
        return true;
    }

    /**
     * Send capture request to gateway for capture authorized transactions
     *
     * @param Mage_Payment_Model_Info $payment
     * @param float $amount
     * @return Zooz_Payments_Model_Payments
     */
    protected function _preauthorizeCapture($payment, $requestedAmount)
    {
        $cardsStorage = $this->getCardsStorage($payment);

        if ($this->_formatAmount(
                $cardsStorage->getProcessedAmount() - $cardsStorage->getCapturedAmount()
            ) < $requestedAmount
        ) {
            Mage::throwException(Mage::helper('payments')->__('Invalid amount for capture.'));
        }

        $messages = array();
        $isSuccessful = false;
        $isFiled = false;
        foreach($cardsStorage->getCards() as $card) {
            if ($requestedAmount > 0) {
                $cardAmountForCapture = $card->getProcessedAmount();
                if ($cardAmountForCapture > $requestedAmount) {
                    $cardAmountForCapture = $requestedAmount;
                }
                try {
                    $newTransaction = $this->_preauthorizeCaptureCardTransaction(
                        $payment, $cardAmountForCapture , $card
                    );
                    $messages[] = $newTransaction->getMessage();
                    $isSuccessful = true;
                } catch (Exception $e) {
                    $messages[] = $e->getMessage();
                    $isFiled = true;
                    continue;
                }
                $card->setCapturedAmount($cardAmountForCapture);
                $cardsStorage->updateCard($card);
                $requestedAmount = $this->_formatAmount($requestedAmount - $cardAmountForCapture);
            } else {
                /**
                 * This functional is commented because partial capture is disable. See self::_canCapturePartial.
                 */
                //$this->_voidCardTransaction($payment, $card);
            }
        }

        if ($isFiled) {
            $this->_processFailureMultitransactionAction($payment, $messages, $isSuccessful);
        }
        return $this;
    }

    /**
     * Send capture request to gateway for capture authorized transactions of card
     *
     * @param Mage_Payment_Model_Info $payment
     * @param float $amount
     * @param Varien_Object $card
     * @return Mage_Sales_Model_Order_Payment_Transaction
     */
    protected function _preauthorizeCaptureCardTransaction($payment, $amount, $card)
    {
        $payment->setAmount($amount);
        $authTransactionId = $card->getLastTransId();

        $request = $this->_getRequest();
        $request->addData(array(
            'command' => 'commitPayment',
            'paymentToken' => $authTransactionId,

        ));

        $result = $this->_postRequest($request);

        switch ($result->getResponseCode()) {
            case self::RESPONSE_CODE_SUCCESS:
                $captureTransactionId = $authTransactionId . '-capture';
                $card->setLastTransId($captureTransactionId);
                return $this->_addTransaction(
                    $payment,
                    $captureTransactionId,
                    Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE,
                    array(
                        'is_transaction_closed' => 0,
                        'parent_transaction_id' => $authTransactionId
                    ),
                    array($this->_realTransactionIdKey => $authTransactionId),
                    Mage::helper('payments')->getTransactionMessage(
                        $payment, self::REQUEST_TYPE_PRIOR_AUTH_CAPTURE, $authTransactionId, $card, $amount
                    )
                );
                break;
            case self::RESPONSE_CODE_ERROR:
                $exceptionMessage = $this->_wrapGatewayError($result->getResponseReasonText());
                break;
            default:
                $exceptionMessage = Mage::helper('payments')->__('Payment capturing error.');
                break;
        }

        $exceptionMessage = Mage::helper('payments')->getTransactionMessage(
            $payment, self::REQUEST_TYPE_PRIOR_AUTH_CAPTURE, $authTransactionId, $card, $amount, $exceptionMessage
        );
        Mage::throwException($exceptionMessage);
    }
    
    /**
     * Void the card transaction through gateway
     *
     * @param Mage_Payment_Model_Info $payment
     * @param Varien_Object $card
     * @return Mage_Sales_Model_Order_Payment_Transaction
     */
    protected function _voidCardTransaction($payment, $card)
    {
        $authTransactionId = $card->getLastTransId();
       
        $request = $this->_getRequest();
        $request->addData(array(
            'command' => 'voidPayment',
            'paymentToken' => $authTransactionId
            )
        );
        
        $result = $this->_postRequest($request);
        
        switch ($result->getResponseCode()) {
            case self::RESPONSE_CODE_SUCCESS:
                $voidTransactionId = $authTransactionId . '-void';
                $card->setLastTransId($voidTransactionId);
                return $this->_addTransaction(
                    $payment, $voidTransactionId, Mage_Sales_Model_Order_Payment_Transaction::TYPE_VOID,
                    array(
                        'is_transaction_closed' => 1,
                        'should_close_parent_transaction' => 1,
                        'parent_transaction_id' => $authTransactionId
                    ),
                    array($this->_realTransactionIdKey => $authTransactionId),
                    Mage::helper('payments')->getTransactionMessage(
                        $payment, self::REQUEST_TYPE_VOID, $authTransactionId, $card
                    )
                );
                break;
            case self::RESPONSE_CODE_ERROR:
                Mage::throwException($this->_wrapGatewayError($result->getResponseReasonText()));
                break;
            default:
                Mage::throwException($defaultExceptionMessage);
                break;
        }
        
        $exceptionMessage = Mage::helper('payments')->getTransactionMessage(
            $payment, self::REQUEST_TYPE_PRIOR_AUTH_CAPTURE, $authTransactionId, $card, $amount, $exceptionMessage
        );
        Mage::throwException($exceptionMessage);
    }
    
    /**
     * Refund the card transaction through gateway
     *
     * @param Mage_Payment_Model_Info $payment
     * @param Varien_Object $card
     * @return Mage_Sales_Model_Order_Payment_Transaction
     */
    protected function _refundCardTransaction($payment, $amount, $card)
    {
        $authTransactionId = $card->getLastTransId();
        
        $request = $this->_getRequest();
        $request->addData(array(
            'command' => 'refundPayment',
            'paymentToken' => $authTransactionId,
            'amount' => $amount,
            'refundReason' => self::REFUND_REASON_GENERAL,
            'uniqueTransactionID' => 'refundTxId-' . $authTransactionId . '-' . time()
            )
        );
        
        $result = $this->_postRequest($request);
        
        switch ($result->getResponseCode()) {
            case self::RESPONSE_CODE_SUCCESS:
                $captureTransactionId = $authTransactionId . '-capture';
                $refundTransactionId = $authTransactionId . '-refund';
                $shouldCloseCaptureTransaction = 0;
                /**
                 * If it is last amount for refund, transaction with type "capture" will be closed
                 * and card will has last transaction with type "refund"
                 */
                if ($this->_formatAmount($card->getCapturedAmount() - $card->getRefundedAmount()) == $amount) {
                    $card->setLastTransId($refundTransactionId);
                    $shouldCloseCaptureTransaction = 1;
                }
                return $this->_addTransaction(
                    $payment,
                    $refundTransactionId,
                    Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND,
                    array(
                        'is_transaction_closed' => 1,
                        'should_close_parent_transaction' => $shouldCloseCaptureTransaction,
                        'parent_transaction_id' => $captureTransactionId
                    ),
                    array($this->_realTransactionIdKey => $authTransactionId),
                    Mage::helper('payments')->getTransactionMessage(
                        $payment, self::REQUEST_TYPE_CREDIT, $authTransactionId, $card, $amount
                    )
                );
            case self::RESPONSE_CODE_ERROR:
                Mage::throwException($this->_wrapGatewayError($result->getResponseReasonText()));
                break;
            default:
                Mage::throwException($defaultExceptionMessage);
                break;
        }
        
        $exceptionMessage = Mage::helper('payments')->getTransactionMessage(
            $payment, self::REQUEST_TYPE_PRIOR_AUTH_CAPTURE, $authTransactionId, $card, $amount, $exceptionMessage
        );
        Mage::throwException($exceptionMessage);
    }

    /**
     * Process exceptions for gateway action with a lot of transactions
     *
     * @param  Mage_Payment_Model_Info $payment
     * @param  string $messages
     * @param  bool $isSuccessfulTransactions
     */
    protected function _processFailureMultitransactionAction($payment, $messages, $isSuccessfulTransactions)
    {
        if ($isSuccessfulTransactions) {
            $messages[] = Mage::helper('payments')->__('Gateway actions are locked because the gateway cannot complete one or more of the transactions. Please log in to your Authorize.Net account to manually resolve the issue(s).');
            /**
             * If there is successful transactions we can not to cancel order but
             * have to save information about processed transactions in order`s comments and disable
             * opportunity to voiding\capturing\refunding in future. Current order and payment will not be saved because we have to
             * load new order object and set information into this object.
             */
            $currentOrderId = $payment->getOrder()->getId();
            $copyOrder = Mage::getModel('sales/order')->load($currentOrderId);
            $copyOrder->getPayment()->setAdditionalInformation($this->_isGatewayActionsLockedKey, 1);
            foreach($messages as $message) {
                $copyOrder->addStatusHistoryComment($message);
            }
            $copyOrder->save();
        }
        Mage::throwException(Mage::helper('payments')->convertMessagesToMessage($messages));
    }

    /**
     * @return $this|Mage_Payment_Model_Abstract
     * @throws Mage_Core_Exception
     */
    public function validate()
    {
        $helper = $this->_getHelper();
        /* @var $info Mage_Payment_Model_Info */
        $info = $this->getInfoInstance();
        
        if(!$info->getIsIframe()) {
            if (!$this->_isSavingCardDataAllowed() || !$info->getCcSavedCard()) {
                return parent::validate();
            }
        }


        $isOrder = $info instanceof Mage_Sales_Model_Order_Payment;
        /**
         * this validation is normally (in standard workflow) done in Mage_Payment_Model_Method_Abstract::validate
         * called from Mage_Payment_Model_Method_Cc::validate. If saved card is used this method won't be called.
         * That's why this has to be validated here
         */
        if ($isOrder) {
            $billingCountry = $info->getOrder()->getBillingAddress()->getCountryId();
        } else {
            $billingCountry = $info->getQuote()->getBillingAddress()->getCountryId();
        }
        if (!$this->canUseForCountry($billingCountry)) {
            Mage::throwException(Mage::helper('payment')->__('Selected payment type is not allowed for billing country.'));
        }
        //end additinal validation
        
        if(!$info->getIsIframe()) {
            $customerLoginId = $isOrder ? $info->getOrder()->getCustomerId() : $info->getQuote()->getCustomerId();
            $savedCard = $this->_getSavedCard($info->getCcSavedCard(), $customerLoginId);
            if ($savedCard === null) {
                Mage::throwException($helper->__('Invalid credit card'));
            }
            $ccType = $helper->translateCardSubtypeToTypeCode($savedCard->getSubtype());
        }
        
        
        
        return $this;
    }

    /**
     * @return array
     */
    public function getVerificationRegEx()
    {
        return array_merge(parent::getVerificationRegEx(), array(
            'DC' => '/^[0-9]{3}$/' // Diners Club CCV
        ));
    }

    /**
     * @param $type
     * @return bool
     */
    public function OtherCcType($type)
    {
        return in_array($type, array('OT', 'DC'));
    }

    /**
     * @param $paymentMethodToken
     * @param $customerLoginId
     * @return null|Varien_Object
     */
    private function _getSavedCard($paymentMethodToken, $customerLoginId)
    {
        $api = Mage::getModel('payments/api');
        return $api->getPaymentMethod($paymentMethodToken, $customerLoginId);
    }

    /**
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
     * Post request to gateway and return responce
     * @param Zooz_Payments_Model_Payments_Request $request)
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
                ->setResponseCode(-1)
                ->setResponseReasonCode($ex->getCode())
                ->setResponseReasonText($ex->getMessage());

            $debugData['result'] = $result->getData();
            Mage::log($ex->getMessage(),NULL,'debgs.log');
            $this->_debug($debugData);
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
                ->setResponseCode(-1)
                ->setResponseReasonCode($ex->getFields())
                ->setResponseReasonText($message);
            
            $debugData['result'] = $result->getData();
            $this->_debug($debugData);
			 Mage::log($ex->getMessage(),NULL,'debgs.log');
            return $result;
        }

        $result
            ->setResponseCode(0)
            ->addData($responseAsArray['responseObject']);

		 Mage::log('pp1',NULL,'debgs.log');
        $debugData['result'] = $result->getData();
        $this->_debug($debugData);
        
        return $result;
    }

    /**
     * Retrieves customer login id for zooz api calls
     *
     * @param Mage_Payment_Model_Info $payment
     * @return int
     */
    private function _getCustomerLoginId(Mage_Payment_Model_Info $payment)
    {
        $quoteId = $this->_getSession()->getQuote()->getId();
        if($payment->getOrder() !== null) {
        	if(Mage::getSingleton('customer/session')->isLoggedIn()) return Mage::getSingleton('customer/session')->getCustomer()->getId();
            return $payment->getOrder()->getCustomerId() ? $payment->getOrder()->getCustomerId() : $quoteId;
        } else {
        	if(Mage::getSingleton('customer/session')->isLoggedIn()) return Mage::getSingleton('customer/session')->getCustomer()->getId();
            return $quoteId;
        }
    }

    /**
     * @param mixed $data
     * @return $this
     */
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        parent::assignData($data);
        
        $info = $this->getInfoInstance();
        $info->setCcSavedCard($data->getCcSavedCard());
        $info->setCcSavedCid($data->getCcSavedCid());
        $info->setCcSaveData($data->getCcSaveData());
        
        $info->setIsIframe($data->getIsIframe());
        $info->setCcPaymentToken($data->getCcPaymentToken());
        
        return $this;
    }

    /**
     * @return bool
     */
    protected function _isSavingCardDataAllowed()
    {
        return Mage::getModel('payments/config')->isSavingCardDataAllowed();
    }

    /**
     * @return Mage_Core_Helper_Abstract
     */
    protected function _getHelper()
    {
        return Mage::helper('payments');
    }

    /**
     * @return false|Mage_Core_Model_Abstract
     */
    protected function _getRequest()
    {
        return Mage::getModel('payments/payments_request');
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
     * Retrieve session object
     *
     * @return Mage_Core_Model_Session_Abstract
     */
    protected function _getSession()
    {
        if (Mage::app()->getStore()->isAdmin()) {
            return Mage::getSingleton('adminhtml/session_quote');
        } else {
            return Mage::getSingleton('checkout/session');
        }
    }
    
    /**
     * Retrieve logged customer info from session
     *
     * @return Mage_Core_Model_Abstract
     */
    protected function _getLoggedCustomer()
    {
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            return Mage::getSingleton('customer/session')->getCustomer();
        } 
        
        return false;
    }
    
    /**
     * Init cards storage model
     *
     * @param Mage_Payment_Model_Info $payment
     */
    protected function _initCardsStorage($payment)
    {
        $this->_cardsStorage = Mage::getModel('payments/payments_cards')->setPayment($payment);
    }
    
    /**
     * Return cards storage model
     *
     * @param Mage_Payment_Model_Info $payment
     * @return Zooz_Payments_Model_Payments_Cards
     */
    public function getCardsStorage($payment = null)
    {
        if (is_null($payment)) {
            $payment = $this->getInfoInstance();
        }
        if (is_null($this->_cardsStorage)) {
            $this->_initCardsStorage($payment);
        }
        return $this->_cardsStorage;
    }
    
    /**
     * It sets card`s data into additional information of payment model
     *
     * @param Zooz_Payments_Model_Payments_Result $response
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return Varien_Object
     */
    protected function _registerCard(Varien_Object $response, Mage_Sales_Model_Order_Payment $payment)
    {
        $cardsStorage = $this->getCardsStorage($payment);
        $card = $cardsStorage->registerCard();
        $card
            ->setRequestedAmount($response->getRequestedAmount())
            ->setBalanceOnCard($response->getBalanceOnCard())
            ->setLastTransId($this->_paymentToken)
            ->setProcessedAmount($payment->getAmount())
            ->setCcType($payment->getCcType())
            ->setCcOwner($payment->getCcOwner())
            ->setCcLast4($payment->getCcLast4())
            ->setCcExpMonth($payment->getCcExpMonth())
            ->setCcExpYear($payment->getCcExpYear())
            ->setCcSsIssue($payment->getCcSsIssue())
            ->setCcSsStartMonth($payment->getCcSsStartMonth())
            ->setCcSsStartYear($payment->getCcSsStartYear());

        $cardsStorage->updateCard($card);
        $this->_clearAssignedData($payment);
        return $card;
    }

    /**
     * Reset assigned data in payment info model
     *
     * @param Mage_Payment_Model_Info
     * @return Zooz_Payments_Model_Payments
     */
    private function _clearAssignedData($payment)
    {
        $payment->setCcType(null)
            ->setCcOwner(null)
            ->setCcLast4(null)
            ->setCcNumber(null)
            ->setCcCid(null)
            ->setCcExpMonth(null)
            ->setCcExpYear(null)
            ->setCcSsIssue(null)
            ->setCcSsStartMonth(null)
            ->setCcSsStartYear(null)
            ;
        return $this;
    }
    
    /**
     * Add payment transaction
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param string $transactionId
     * @param string $transactionType
     * @param array $transactionDetails
     * @param array $transactionAdditionalInfo
     * @return null|Mage_Sales_Model_Order_Payment_Transaction
     */
    protected function _addTransaction(Mage_Sales_Model_Order_Payment $payment, $transactionId, $transactionType,
        array $transactionDetails = array(), array $transactionAdditionalInfo = array(), $message = false
    ) {
        $payment->setTransactionId($transactionId);
        $payment->resetTransactionAdditionalInfo();
        foreach ($transactionDetails as $key => $value) {
            $payment->setData($key, $value);
        }
        foreach ($transactionAdditionalInfo as $key => $value) {
            $payment->setTransactionAdditionalInfo($key, $value);
        }
        $transaction = $payment->addTransaction($transactionType, null, false , $message);
        foreach ($transactionDetails as $key => $value) {
            $payment->unsetData($key);
        }
        $payment->unsLastTransId();

        /**
         * It for self using
         */
        $transaction->setMessage($message);

        return $transaction;
    }
    
    /**
     * Mock capture transaction id in invoice
     *
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return Mage_Payment_Model_Method_Abstract
     */
    public function processInvoice($invoice, $payment)
    {
        $invoice->setTransactionId(1);
        return $this;
    }

    /**
     * Set transaction ID into creditmemo for informational purposes
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return Mage_Payment_Model_Method_Abstract
     */
    public function processCreditmemo($creditmemo, $payment)
    {
        $creditmemo->setTransactionId(1);
        return $this;
    }

    /**
     * Round up and cast specified amount to float or string
     *
     * @param string|float $amount
     * @param bool $asFloat
     * @return string|float
     */
    protected function _formatAmount($amount, $asFloat = false)
    {
        $amount = sprintf('%.2F', $amount); // "f" depends on locale, "F" doesn't
        return $asFloat ? (float)$amount : $amount;
    }
}