<?php
/**
 * ZooZ Payments Integration
 *
 * @category    Zooz
 * @package     Zooz_Payments
 */

/**
 * Unified IPN controller for ZooZ Payments
 */
class Zooz_Payments_IpnController extends Mage_Core_Controller_Front_Action
{
    /**
     * Instantiate IPN model and pass IPN request to it
     */
    public function indexAction()
    {
        if (!$this->getRequest()->isPost()) {
            return;
        }

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!is_array($data) || empty($data)) {
                throw new Exception('IPN request contains no data');
            }

            Mage::getModel('payments/ipn')->processIpnRequest($data);
        } catch (Zooz_Payments_UnavailableException $e) {
            Mage::logException($e);
            $this->getResponse()->setHeader('HTTP/1.1','503 Service Unavailable')->sendResponse();
            exit;
        } catch (Exception $e) {
            Mage::logException($e);
            $this->getResponse()->setHttpResponseCode(500);
        }
    }
}
