<?php
/**
 * ZooZ Payments Integration
 *
 * @category    Zooz
 * @package     Zooz_Payments
 */

/**
 * Zooz Payments Controller
 *
 * @category    Zooz
 * @package     Zooz_Payments
 */
class Zooz_Payments_Adminhtml_Zooz_PaymentController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Cancel active partail authorizations
     */
    public function cancelAction()
    {
        $result['success'] = false;
        try {
            $paymentMethod = Mage::helper('payments')->getMethodInstance(Zooz_Payments_Model_Payments::METHOD_CODE);
            if ($paymentMethod) {
                $paymentMethod->setStore(Mage::getSingleton('adminhtml/session_quote')->getQuote()->getStoreId());
                $paymentMethod->cancelPartialAuthorization(Mage::getSingleton('adminhtml/session_quote')->getQuote()->getPayment());
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

        Mage::getSingleton('adminhtml/session_quote')->getQuote()->getPayment()->save();
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
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

    /**
     * Check is allowed access to action
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/review_payment');
    }
}
