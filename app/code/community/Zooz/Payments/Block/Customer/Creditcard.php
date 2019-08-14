<?php
/**
 * ZooZ Payments Integration
 *
 * @category    Zooz
 * @package     Zooz_Payments
 */

class Zooz_Payments_Block_Customer_Creditcard extends Mage_Core_Block_Template {

    protected $_api = false;

    protected $_customer = false;

    protected function _construct() {
        parent::_construct();
        $this->_api = Mage::getSingleton('payments/api');
        $this->_customer = Mage::getSingleton('customer/session')->getCustomer();
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
     * Retrieve payment configuration object
     *
     * @return Zooz_Payments_Model_Config
     */
    protected function _getConfig() {
        return Mage::getSingleton('payments/config');
    }

    /**
     * Get saved credit cards in Zooz
     *
     * @return bool
     */
    public function getPaymentMethods()
    {
        $customerLoginId = false;
        
        if($this->_customer) {
            $customerLoginId = $this->_customer->getId();
            if($customerLoginId > 0) {
                return $this->_api->getPaymentMethods($customerLoginId, true);
            } 
        }
        
        return false;
    }

    /**
     * Retrieves collection of the countries saved in magento
     *
     * @return mixed
     */
    public function getCountryCollection()
    {
        if (!$this->_countryCollection) {
            $this->_countryCollection = Mage::getSingleton('directory/country')->getResourceCollection()
                ->loadByStore();
        }
        return $this->_countryCollection;
    }

    /**
     * Retrieves html dropdown with countries
     *
     * @return mixed
     */
    public function getCountryHtmlSelect()
    {
        $countryId = null;
        
        $customerAddressId = $this->_customer->getDefaultBilling();
        if ($customerAddressId) {
            $address = Mage::getModel('customer/address')->load($customerAddressId);
            $countryId = $address['country_id'];
        }

        if (is_null($countryId)) {
            $countryId = Mage::helper('core')->getDefaultCountry();
        }
        $select = $this->getLayout()->createBlock('core/html_select')
            ->setName('country_id')
            ->setId('country_id')
            ->setTitle(Mage::helper('payments')->__('Country'))
            ->setClass('validate-select')
            ->setValue($countryId)
            ->setOptions($this->getCountryOptions());

        return $select->getHtml();
    }

    /**
     * Retrieves country options
     *
     * @return bool|mixed
     */
    public function getCountryOptions()
    {
        $options    = false;
        $useCache   = Mage::app()->useCache('config');
        if ($useCache) {
            $cacheId    = 'DIRECTORY_COUNTRY_SELECT_STORE_' . Mage::app()->getStore()->getCode();
            $cacheTags  = array('config');
            if ($optionsCache = Mage::app()->loadCache($cacheId)) {
                $options = unserialize($optionsCache);
            }
        }

        if ($options == false) {
            $options = $this->getCountryCollection()->toOptionArray();
            if ($useCache) {
                Mage::app()->saveCache(serialize($options), $cacheId, $cacheTags);
            }
        }
        return $options;
    }
    
    /**
     * Retrieve availables credit card types
     *
     * @return array
     */
    public function getCcTypes() {
        $method = $this->_getPayment();
        return $this->_getConfig()->getCcAvailableTypes($method);
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
     * Retrieves credit card details
     *
     * @return bool
     * @throws Exception
     */
    public function getCreditCardDetails()
    {
        $paymentData = false;
        
        $token = $this->getRequest()->getParam('t');
        $paymentMethods = $this->_api->getPaymentMethods($this->_customer->getId(), true);
        foreach($paymentMethods as $key => $pm) {
            if($pm->getData('paymentMethodToken') == $token) {
                $paymentData = $pm->getData();
            }
        }
        
        return $paymentData;
    }


    /**
     * Retrieve availables credit card types
     *
     * @return array
     */
    public function getCcAvailableTypesByConfig() {

        return Mage::getStoreConfig('payment/payments/cctypes');

    }
}
