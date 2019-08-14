<?php
/**
 * ZooZ Payments Integration
 *
 * @category    Zooz
 * @package     Zooz_Payments
 */

/**
 * Payment configuration model
 *
 * Used for retrieving configuration data by payment models
 *
 * @category   Zooz
 * @package    Zooz_Payments
 */
class Zooz_Payments_Model_Config extends Mage_Core_Block_Template
{

    const XML_PATH_SAVE_CREDIT_CARD_ALLOWED = 'payment/payments/save_cart_data_allowed';
    const XML_PATH_PCI_IFRAME_ENABLED = 'payment/payments/iframe_for_pci';
    
    const PCI_IFRAME_URL = 'https://paymentpages.zooz.com/Magento/iframe.html';

    const XML_PATH_PROGRAM_ID = 'payment/payments/program_id';
    const XML_PATH_IS_SANDBOX = 'payment/payments/payment_mode';
    
    protected static $_methods;
    
    /**
     * Retrieve active system payments
     *
     * @param   mixed $store
     * @return  array
     */
    public function getActiveMethods($store=null)
    {
        $methods = array();
        $config = Mage::getStoreConfig('payment', $store);
        foreach ($config as $code => $methodConfig) {
            if (Mage::getStoreConfigFlag('payment/'.$code.'/active', $store)) {
                if (array_key_exists('model', $methodConfig)) {
                    $methodModel = Mage::getModel($methodConfig['model']);
                    if ($methodModel && $methodModel->getConfigData('active', $store)) {
                        $methods[$code] = $this->_getMethod($code, $methodConfig);
                    }
                }
            }
        }
        return $methods;
    }

    /**
     * Retrieve all system payments
     *
     * @param mixed $store
     * @return array
     */
    public function getAllMethods($store=null)
    {
        $methods = array();
        $config = Mage::getStoreConfig('payments', $store);
        foreach ($config as $code => $methodConfig) {
            $data = $this->_getMethod($code, $methodConfig);
            if (false !== $data) {
                $methods[$code] = $data;
            }
        }
        return $methods;
    }
    
    protected function _getMethod($code, $config, $store=null)
    {
        if (isset(self::$_methods[$code])) {
            return self::$_methods[$code];
        }
        if (empty($config['model'])) {
            return false;
        }
        $modelName = $config['model'];

        $className = Mage::getConfig()->getModelClassName($modelName);
        if (!mageFindClassFile($className)) {
            return false;
        }

        $method = Mage::getModel($modelName);
        $method->setId($code)->setStore($store);
        self::$_methods[$code] = $method;
        return self::$_methods[$code];
    }
    
    /**
     * Retrieve Program ID
     * The Program ID, as registered in the Zooz Developer Portal.
     * 
     * @return string
     */
    public function getProgramId($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_PROGRAM_ID, $storeId);
    }
    
    /**
     * Retrieve Program Key
     * The Program Key, as generated upon app / site registration.
     * 
     * @return string
     */
    public function getProgramKey()
    {
        return Mage::helper('core')->decrypt($this->getConfigData('program_key'));
    }

    /**
     * Retrieve availables credit card types
     *
     * @return array
     */
    public function getCcAvailableTypes($method = false) {
        $types = $this->getCcTypes();
        if ($method) {
            $availableTypes = $method->getConfigData('cctypes');
            if ($availableTypes) {
                $availableTypes = explode(',', $availableTypes);
                foreach ($types as $code=>$name) {
                    if (!in_array($code, $availableTypes)) {
                        unset($types[$code]);
                    }
                }
            }
        }
        return $types;
    }
    
    /**
     * Retrieve array of credit card types
     *
     * @return array
     */
    public function getCcTypes()
    {

        $_types = Mage::getConfig()->getNode('global/payments/cc/types')->asArray();



        uasort($_types, array('Zooz_Payments_Model_Config', 'compareCcTypes'));

        $types = array();
        foreach ($_types as $data) {
            if (isset($data['code']) && isset($data['name'])) {
                $types[$data['code']] = $data['name'];
            }
        }
        return $types;
    }

    /**
     * Retrieve list of months translation
     *
     * @return array
     */
    public function getMonths()
    {
        $data = Mage::app()->getLocale()->getTranslationList('month');
        foreach ($data as $key => $value) {
            $monthNum = ($key < 10) ? '0'.$key : $key;
            $data[$key] = $monthNum . ' - ' . $value;
        }
        return $data;
    }
    
    /**
     * Retrieve credit card expire months
     *
     * @return array
     */
    public function getCcMonths($months)
    {
        if (is_null($months)) {
            $months[0] =  $this->__('Month');
            $months = array_merge($months, $this->getMonths());
        }
        return $months;
    }

    /**
     * Retrieve array of available years
     *
     * @return array
     */
    public function getYears()
    {
        $years = array();
        $first = date("Y");

        for ($index=0; $index <= 10; $index++) {
            $year = $first + $index;
            $years[$year] = $year;
        }
        return $years;
    }
    
    /**
     * Retrieve credit card expire years
     *
     * @return array
     */
    public function getCcYears($years)
    {
        if (is_null($years)) {
            $years = $this->getYears();
            $years = array(0=>$this->__('Year'))+$years;
        }
        return $years;
    }
    
    /**
     * Check whether sandbox mode is enabled
     * 
     * @return bool
     */
    public function isSandbox($storeId = null)
    {
        $result = Mage::getStoreConfig(self::XML_PATH_IS_SANDBOX, $storeId) == Zooz_Payments_Model_Payments::ACTION_MODE_SANDBOX ? true : false;
        return $result;
    }

    /**
     * Check whether method active in configuration and supported for merchant country or not
     *
     * @param string $method Method code
     * @return bool
     */
    public function isMethodActive($method)
    {
        if ($this->isMethodSupportedForCountry($method)
            && Mage::getStoreConfigFlag("payment/{$method}/active", $this->_storeId)
        ) {
            return true;
        }
        return false;
    }
    
    /**
     * Check whether method supported for specified country or not
     * Use $_methodCode and merchant country by default
     *
     * @return bool
     */
    public function isMethodSupportedForCountry($method = null, $countryCode = null)
    {
        if ($method === null) {
            $method = $this->getMethodCode();
        }
        if ($countryCode === null) {
            $countryCode = $this->getMerchantCountry();
        }
        $countryMethods = $this->getCountryMethods($countryCode);
        if (in_array($method, $countryMethods)) {
            return true;
        }
        return false;
    }

    /**
     * @param int $storeId
     * @return bool
     */
    public function isSavingCardDataAllowed($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_SAVE_CREDIT_CARD_ALLOWED, $storeId)
            && Mage::getSingleton('customer/session')->isLoggedIn();
    }

    /**
     * @param int $storeId
     * @return bool
     */
    public function isPciIframeEnabled($storeId = null)
    {

        return Mage::getStoreConfigFlag(self::XML_PATH_PCI_IFRAME_ENABLED, $storeId);
    }

    /**
     * @param int $storeId
     * @return string
     */
    public function getPciIframeUrl($storeId = null)
    {
        return self::PCI_IFRAME_URL;
    }

    /**
     * Statis Method for compare sort order of CC Types
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    static function compareCcTypes($a, $b)
    {
        if (!isset($a['order'])) {
            $a['order'] = 0;
        }

        if (!isset($b['order'])) {
            $b['order'] = 0;
        }

        if ($a['order'] == $b['order']) {
            return 0;
        } else if ($a['order'] > $b['order']) {
            return 1;
        } else {
            return -1;
        }

    }
}
