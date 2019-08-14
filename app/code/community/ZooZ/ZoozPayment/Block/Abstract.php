<?php


/**
 * Abstract block for estimate module
 *
 */
abstract class ZooZ_ZoozPayment_Block_Abstract extends Mage_Catalog_Block_Product_Abstract
{
  
    protected $_estimate = null;


  
    protected $_config = null;


    protected $_session = null;

   
    protected $_carriers = null;

    public function getEstimate()
    {
        if ($this->_estimate === null) {
            $this->_estimate = Mage::getSingleton('zoozpayment/estimate');
        }

        return $this->_estimate;
    }

    /**
     * Retrieve configuration model for module
     *
     * @return Lotus_Getshipping_Model_Config
     */
    public function getConfig()
    {
        if ($this->_config === null) {
            $this->_config = Mage::getSingleton('zoozpayment/config');
        }

        return $this->_config;
    }

    /**
     * Retrieve session model object
     *
     * @return Lotus_Getshipping_Model_Session
     */
    public function getSession()
    {
        
        if ($this->_session === null) {
            $this->_session = Mage::getSingleton('zoozpayment/session');
        }

        return $this->_session;
    }

    /**
     * Check is enabled functionality
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->getConfig()->isEnabled() && !$this->getProduct()->isVirtual();
    }
}
