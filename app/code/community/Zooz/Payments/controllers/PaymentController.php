<?php
/**
 * ZooZ Payments Integration
 *
 * @category    Zooz
 * @package     Zooz_Payments
 */

class Zooz_Payments_PaymentController extends Mage_Core_Controller_Front_Action {
    
    public function indexAction() {
        $params = $this->getRequest()->getParams();
    }

    /**
     * Cancel active partail authorizations
     */
    public function cancelAction()
    {
        $result['success'] = false;
        try {
            $paymentMethod = Mage::helper('payments')->getMethodInstance(Zooz_Payments_Model_Payments::METHOD_CODE);
            if ($paymentMethod) {
                $paymentMethod->cancelPartialAuthorization(Mage::getSingleton('checkout/session')->getQuote()->getPayment());
            }
            $result['success']  = true;
            $result['update_html'] = $this->_getPaymentMethodsHtml();
        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
            $result['error_message'] = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $result['error_message'] = $this->__('There was an error canceling transactions. Please contact us or try again later.');
        }

        Mage::getSingleton('checkout/session')->getQuote()->getPayment()->save();
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
    
    public function setpaymentmethodAction()
    {
        if (!$this->getRequest()->isPost()) {
            return;
        }
        
        var_dump($this->getRequest()->getParams());
        die;
        
        try {
            $data = json_decode($this->getRequest()->getParams());
            print_r($data);
            die;
        } catch (Zooz_Payments_UnavailableException $e) {
            Mage::logException($e);
            $this->getResponse()->setHeader('HTTP/1.1','503 Service Unavailable')->sendResponse();
            exit;
        } catch (Exception $e) {
            Mage::logException($e);
            $this->getResponse()->setHttpResponseCode(500);
        }
    }
    
    /**
     * Get payment method step html
     *
     * @return string
     */
    protected function _getPaymentMethodsHtml()
    {
        $layout = $this->getLayout();
        $update = $layout->getUpdate();
        $update->load('checkout_onepage_paymentmethod');
        $layout->generateXml();
        $layout->generateBlocks();
        $output = $layout->getOutput();
        return $output;
    }
}
