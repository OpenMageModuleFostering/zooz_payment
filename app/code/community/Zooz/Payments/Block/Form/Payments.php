<?php
/**
 * ZooZ Payments Integration
 *
 * @category    Zooz
 * @package     Zooz_Payments
 */

class Zooz_Payments_Block_Form_Payments extends Mage_Payment_Block_Form {

    protected function _construct() {
        parent::_construct();
        $this->setTemplate('zoozpayments/form/payments.phtml');
    }
    
    /**
     * Retrieve payment configuration object
     *
     * @return Zooz_Payments_Model_Config
     */
    protected function _getConfig() {
        return Mage::getSingleton('payments/config');
    }
    
    /**
     * Retrieve payment model object
     *
     * @return Zooz_Payments_Model_Payments
     */
    protected function _getPayment() {
        return Mage::getSingleton('payments/payments');
    }

    /**
     * Retrieve availables credit card types
     *
     * @return array
     */
    public function getCcAvailableTypes() {

        $method = $this->getMethod();

        return $this->_getConfig()->getCcAvailableTypes($method);
    }





    /**
     * Checks whether customer is allowed to save credit card data
     *
     * @return bool
     */
    public function isSavingCardDataAllowed()
    {
        return Mage::getModel('payments/config')->isSavingCardDataAllowed();
    }

    /**
     * @return array
     */
    public function getSavedCards()
    {
        $customerId = Mage::getSingleton('customer/session')->getCustomerId();

        //if saving cards is not customer user shoulnd't be allowed to use saved credit card
        if (!$this->isSavingCardDataAllowed() || !$customerId) {
            return array();
        }

        $api = Mage::getModel('payments/api');
        $cardsData = $api->getPaymentMethods($customerId);
        return $cardsData;
    }

    /**
     * Retrieve credit card expire months
     *
     * @return array
     */
    public function getCcMonths()
    {
        $months = $this->getData('cc_months');
        $result = $this->_getConfig()->getCcMonths($months);
        $this->setData('cc_months', $result);
        return $result;
    }

    /**
     * Retrieve credit card expire years
     *
     * @return array
     */
    public function getCcYears()
    {
        $years = $this->getData('cc_years');
        $result = $this->_getConfig()->getCcYears($years);
        $this->setData('cc_years', $result);
        return $result;
    }

    /**
     * Retrive payment mode type
     * 
     * @return string
     */
    public function getPaymentModeType() 
    {
        if ($this->getMethod()) {
            $configData = $this->getMethod()->getConfigData('payment_mode');
            if(is_null($configData)){
                return Zooz_Payments_Model_Payments::ACTION_MODE_SANDBOX;
            }
            return $configData;
        }
        return Zooz_Payments_Model_Payments::ACTION_MODE_SANDBOX;
    }

    /**
     * Retrieves payment token from Zooz
     *
     * @param $payment array
     * @param $amount float Grand Total
     * @return null
     */
    public function getPaymentToken($payment, $amount)
    {    
        $paymentModel = $this->_getPayment();
        $result = $paymentModel->getPaymentToken($payment, $amount);
        return $result;
    }

    /**
     * Retrieves config if iframe is set for the payment
     *
     * @return bool
     */
    public function isPciIframeEnabled()
    {
        return $this->_getConfig()->isPciIframeEnabled();
    }

    /**
     * Retrieves iframe url
     *
     * @return string
     */
    public function getIframeUrl()
    {
        return $this->_getConfig()->getPciIframeUrl();
    }

    /**
     * Retrieves program unique ID
     *
     * @return string
     */
    public function getUniqueId()
    {
        return $this->_getConfig()->getProgramId();
    }

    /**
     * Retrieves config is sandbox is set
     *
     * @return bool
     */
    public function isSandbox()
    {
        return $this->_getConfig()->isSandbox();
    }
}
