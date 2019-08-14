<?php
/**
 * ZooZ Payments Integration
 *
 * @category    Zooz
 * @package     Zooz_Payments
 */

/**
 * Zooz Instant Payment Notification processor model
 */
class Zooz_Payments_Model_Ipn
{
    /**
     * Default log filename
     *
     * @var string
     */
    const DEFAULT_LOG_FILE = 'zooz_payments_ipn.log';

    /**
     * Store order instance
     *
     * @var Mage_Sales_Model_Order
     */
    protected $_order = null;

    /**
     *
     * @var Zooz_Payments_Model_Config
     */
    protected $_config = null;

    /**
     * ZooZ info instance
     *
     * @var Zooz_Payments_Model_Info
     */
    protected $_info = null;

    /**
     * IPN request data
     * @var array
     */
    protected $_request = array();

    /**
     * Collected debug information
     *
     * @var array
     */
    protected $_debugData = array();

    /**
     * IP addresses that are valid source of IPN requests
     *
     * @var array
     */
    private $_validRemoteAddresses = array(
        '54.200.242.136',
        '54.200.93.153',
        '54.201.49.185',
        '54.200.38.78',
        '54.69.146.25',
        '54.68.2.155',
        '54.68.182.134',
        '54.148.9.225',
        '54.69.192.240',
    );

    /**
     * IPN request data getter
     *
     * @param string $key
     * @return array|string
     */
    public function getRequestData($key = null)
    {
        if (null === $key) {
            return $this->_request;
        }
        return isset($this->_request[$key]) ? $this->_request[$key] : null;
    }

    /**
     * Get ipn data, send verification to Zooz, run corresponding handler
     *
     * @param array $request
     * @throws Exception
     */
    public function processIpnRequest(array $request)
    {
        $this->_request   = $request;
        $this->_debugData = array('ipn' => $request);
        ksort($this->_debugData['ipn']);

        try {
            $this->_validateRequest($request);
            $this->_processOrder();
        } catch (Exception $e) {
            $this->_debugData['exception'] = $e->getMessage();
            $this->_debug();
            throw $e;
        }
        $this->_debug();
    }

    /**
     * Load and validate order, instantiate proper configuration
     *
     *
     * @return Mage_Sales_Model_Order
     * @throws Exception
     */
    protected function _getOrder()
    {
        if (empty($this->_order)) {
            // get proper order
            $id = $this->_request['invoice']['number'];
            $this->_order = Mage::getModel('sales/order')->loadByIncrementId($id);
            if (!$this->_order->getId()) {
                $this->_debugData['exception'] = sprintf('Wrong order ID: "%s".', $id);
                $this->_debug();
                Mage::app()->getResponse()
                    ->setHeader('HTTP/1.1','503 Service Unavailable')
                    ->sendResponse();
                exit;
            }
            // re-initialize config with the method code and store id
            $methodCode = $this->_order->getPayment()->getMethod();
            $this->_config = Mage::getModel('payments/config', array($methodCode, $this->_order->getStoreId()));
//            if (!$this->_config->isMethodActive($methodCode)) {
//                throw new Exception(sprintf('Method "%s" is not available.', $methodCode));
//            }

        }
        return $this->_order;
    }

    /**
     * IPN workflow implementation
     * Everything should be added to order comments. In positive processing cases customer will get email notifications.
     * Admin will be notified on errors.
     */
    protected function _processOrder()
    {
        $this->_order = null;
        $this->_getOrder();

        $this->_info = Mage::getSingleton('payments/info');
        try {
            $this->_registerTransaction();
        } catch (Mage_Core_Exception $e) {
            $comment = $this->_createIpnComment(Mage::helper('payments')->__('Note: %s', $e->getMessage()), true);
            $comment->save();
            throw $e;
        }
    }

    /**
     * Process regular IPN notifications
     */
    protected function _registerTransaction()
    {
        try {
            // Handle payment_status
            $paymentStatus = $this->_request['paymentStatusCode'];
            switch ($paymentStatus) {
                // paid
                case Zooz_Payments_Model_Info::PAYMENTSTATUS_APPROVED:
                    $this->_registerPaymentCapture(true);
                    break;
                case Zooz_Payments_Model_Info::PAYMENTSTATUS_REFUNDED:
                    $this->_registerPaymentRefund(true);
                    break;
                // authorization void
                case Zooz_Payments_Model_Info::PAYMENTSTATUS_VOIDED:
                    $this->_registerPaymentVoid();
                    break;
                default:
                    throw new Exception("Cannot handle payment status '{$paymentStatus}'.");
            }
        } catch (Mage_Core_Exception $e) {
            $comment = $this->_createIpnComment(Mage::helper('payments')->__('Note: %s', $e->getMessage()), true);
            $comment->save();
            throw $e;
        }
    }

    /**
     * Process completed payment (either full or partial)
     *
     * @param bool $skipFraudDetection
     */
    protected function _registerPaymentCapture($skipFraudDetection = false)
    {
        $parentTransactionId = $this->getRequestData('paymentToken');
//        $this->_importPaymentInformation();
        $payment = $this->_order->getPayment();
        $payment->setTransactionId($parentTransactionId . '-capture')
            ->setCurrencyCode($this->getRequestData('currencyCode'))
            ->setPreparedMessage($this->_createIpnComment(''))
            ->setParentTransactionId($parentTransactionId)
            ->setShouldCloseParentTransaction(Zooz_Payments_Model_Info::PAYMENTSTATUS_APPROVED === $this->getRequestData('paymentStatusCode'))
            ->setIsTransactionClosed(0)
            ->registerCaptureNotification(
                $this->getRequestData('amount'),
                $skipFraudDetection && $parentTransactionId
            );
        $this->_order->save();

        // notify customer
        $invoice = $payment->getCreatedInvoice();
        if ($invoice && !$this->_order->getEmailSent()) {
            $this->_order->queueNewOrderEmail()->addStatusHistoryComment(
                Mage::helper('payments')->__('Notified customer about invoice #%s.', $invoice->getIncrementId())
            )
            ->setIsCustomerNotified(true)
            ->save();
        }
    }

    /**
     * Process a refund or a chargeback
     * 
     * @param bool $skipFraudDetection
     */
    protected function _registerPaymentRefund($skipFraudDetection = false)
    {
        $parentTransactionId = $this->getRequestData('paymentToken');
        $payment = $this->_order->getPayment();

        $amount = $this->_order->getBaseCurrency()->formatTxt($payment->getBaseAmountRefundedOnline());
        
        $comment = Mage::helper('payments')->__('Refunded amount of %s. Transaction ID: "%s"', $this->getRequestData('amount'), $parentTransactionId);
        
        $payment->setPreparedMessage($comment)
            ->setTransactionId($parentTransactionId . '-refund')
            ->setParentTransactionId($parentTransactionId)
            ->setIsTransactionClosed(1)
            ->registerRefundNotification(
                $this->getRequestData('amount'),
                $skipFraudDetection && $parentTransactionId
            );
        
        $this->_order->addStatusHistoryComment($comment, false);
        $this->_order->save();

        // TODO: there is no way to close a capture right now
        $creditmemo = $payment->getCreatedCreditmemo();
        if ($creditmemo) {
            $creditmemo->sendEmail();
            $this->_order->addStatusHistoryComment(
                Mage::helper('payments')->__('Notified customer about creditmemo #%s.', $creditmemo->getIncrementId())
            )
            ->setIsCustomerNotified(true)
            ->save();
        }
    }

    /**
     * Process voided authorization
     */
    protected function _registerPaymentVoid()
    {
        // $this->_importPaymentInformation();

        $parentTransactionId = $this->getRequestData('paymentToken');
        $payment = $this->_order->getPayment();

        $payment->setPreparedMessage($this->_createIpnComment(''))
            ->setParentTransactionId($parentTransactionId)
            ->registerVoidNotification();

        $this->_order->save();
    }

    protected function _validateRequest(array $request)
    {
        $remoteAddr = Mage::helper('core/http')->getRemoteAddr();
        if (!in_array($remoteAddr, $this->_validRemoteAddresses)) {
            throw new Exception("$remoteAddr is not a valid remote address for Zooz IPN request");
        }

        if (!$this->_validateIpnSignature($request)) {
            throw new Exception("Request signature is invalid");
        }
    }

    private function _validateIpnSignature(array $request)
    {
        return true; //TODO complete signature validation
        $text = sprintf('%.2F', $request['amount']);
        $text .= $request['paymentId'];
        $text .= $request['paymentMethod']['paymentMethodLastUsedTimestamp'];
        $text .= $request['paymentMethod']['paymentMethodToken'];
        $text .= $request['processorReferenceId'];
        $text .= 'merchantServerApiKey';

        return false;
    }

    /**
     * Generate an "IPN" comment with additional explanation.
     * Returns the generated comment or order status history object
     *
     * @param string $comment
     * @param bool $addToHistory
     * @return string|Mage_Sales_Model_Order_Status_History
     */
    protected function _createIpnComment($comment = '', $addToHistory = false)
    {
        $paymentStatus = $this->getRequestData('paymentStatus');
        $message = Mage::helper('payments')->__('IPN "%s".', $paymentStatus);
        if ($comment) {
            $message .= ' ' . $comment;
        }
        if ($addToHistory) {
            $message = $this->_order->addStatusHistoryComment($message);
            $message->setIsCustomerNotified(null);
        }
        return $message;
    }

    /**
     * Log debug data to file
     *
     * @param mixed $debugData
     */
    protected function _debug()
    {
        if ($this->_config && $this->_config->debug) {
            $file = $this->_config->getMethodCode() ? "payment_{$this->_config->getMethodCode()}.log"
                : self::DEFAULT_LOG_FILE;
            Mage::getModel('core/log_adapter', $file)->log($this->_debugData);
        }
    }
}
