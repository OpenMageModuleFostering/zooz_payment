<?php
/**
 * ZooZ Payments Integration
 *
 * @category    Zooz
 * @package     Zooz_Payments
 */
 
class Zooz_Payments_Exception extends Mage_Payment_Exception
{
    protected $_code = null;

    public function __construct($message = null, $code = 0)
    {
        $this->_code = $code;
        parent::__construct($message, 0);
        self::logException($message);
    }

    public function getFields()
    {
        return $this->_code;
    }
    
    /**
     * Log an ZooZ_Payments_Exception
     * @param string $e
     */
    public static function logException($e)
    {
        Mage::log("\n" . $e, Zend_Log::ERR, 'zooz_exception.log');
    }
}
