<?php

class ZooZ_ZoozPayment_StandardController extends Mage_Core_Controller_Front_Action {

    public function indexAction() {
        $this->getResponse()->setRedirect(Mage::getUrl('', array('_secure' => true)));
    }

    public function redirectAction() {

        $trimmedSessionToken = Mage::getModel('zoozpayment/standard')->ajaxprocessing();
        if ($trimmedSessionToken != '') {

            $this->getResponse()->setRedirect(Mage::getUrl('zoozpayment') . '?token=' . $trimmedSessionToken);
        }
    }

    public function successexpressAction() {

        $statusCode = Mage::getModel('zoozpayment/standard')->successprocessing();
        if ($statusCode == 0) {
            
             $session = Mage::getSingleton('checkout/session');
            $session->setQuoteId($session->getZoozQuoteId(true));
            if ($session->getLastRealOrderId()) {
                $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
                if ($order->getId()) {
                    //  $status = Mage::getStoreConfig('payment/zoozpayment/order_status');
                    $grandtotal = Mage::getSingleton('core/session')->getZoozGrandTotal();
                    $info = Mage::getSingleton('core/session')->getZoozInfo();
                    $payment = $order->getPayment();
                    $payment->setStatus(Mage_Payment_Model_Method_Abstract::STATUS_APPROVED)
                            ->setTransactionId($info->transactionID)
                            ->setStatusDescription('Payment was successful.')
                            ->setAdditionalData(serialize(''))
                            ->setIsTransactionClosed('')
                            ->authorize(true, $grandtotal)
                            ->save();
                    $order->setPayment($payment);
                    $order->save(); //Save details in order   
                    if (Mage::getStoreConfig('payment/zoozpayment/payment_action') == "authorize_capture") {
                        try {
                            if (!$order->canInvoice()) {
                                Mage::throwException(Mage::helper('core')->__('Cannot create an invoice.'));
                            }
                            $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
                            if (!$invoice->getTotalQty()) {
                                Mage::throwException(Mage::helper('core')->__('Cannot create an invoice without products.'));
                            }
                            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
                            $invoice->register();
                            $transactionSave = Mage::getModel('core/resource_transaction')
                                    ->addObject($invoice)
                                    ->addObject($invoice->getOrder());
                            $transactionSave->save();
                        } catch (Mage_Core_Exception $e) {
                            
                        }
                    }
                    Mage::getSingleton('core/session')->unsZoozGrandTotal();
                    Mage::getSingleton('core/session')->unsZoozInfo();
                    //	$order->setState($status, true)->save();
                }
            }
            $this->getResponse()->setRedirect(Mage::getUrl('checkout/onepage/success'));
        } else {
            $session = Mage::getSingleton('checkout/session');
            $session->setQuoteId($session->getZoozQuoteId(true));
            if ($session->getLastRealOrderId()) {
                $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
                if ($order->getId()) {
                    $order->cancel()->save();
                }
            }
            $params = Mage::app()->getRequest()->getParams();

            Mage::getSingleton('checkout/session')->addError($params['errorMessage']);
            $this->getResponse()->setRedirect(Mage::getUrl('checkout/cart'));
        }
    }

    public function successAction() {

        $statusCode = Mage::getModel('zoozpayment/standard')->successprocessing();

        if ($statusCode == 0) {
            $session = Mage::getSingleton('checkout/session');
            $session->setQuoteId($session->getZoozQuoteId(true));
            if ($session->getLastRealOrderId()) {
                $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
                if ($order->getId()) {
                    $order->setState(Mage_Sales_Model_Order::STATE_NEW, true)->save();
                }
            }

            $this->getResponse()->setRedirect(Mage::getUrl('checkout/onepage/success'));
        } else {
            $session = Mage::getSingleton('checkout/session');
            $session->setQuoteId($session->getZoozQuoteId(true));
            if ($session->getLastRealOrderId()) {
                $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
                if ($order->getId()) {
                    $order->cancel()->save();
                }
            }
            $params = Mage::app()->getRequest()->getParams();

            Mage::getSingleton('checkout/session')->addError($params['errorMessage']);
            $this->getResponse()->setRedirect(Mage::getUrl('checkout/cart'));
        }
    }

    function cancelAction() {
        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getZoozQuoteId(true));
        if ($session->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
            if ($order->getId()) {
                $order->cancel()->save();
            }
        }

        $params = Mage::app()->getRequest()->getParams();
        Mage::log($params);
        Mage::getSingleton('checkout/session')->addError($params['errorMessage']);
        $this->getResponse()->setRedirect(Mage::getUrl('checkout/cart'));
    }

    function ajaxstartAction() {

        $trimmedSessionToken = Mage::getModel('zoozpayment/standard')->ajaxprocessing(true);
        if ($trimmedSessionToken != '') {

            echo "var data = {'token' : '" . $trimmedSessionToken . "'}";

            return;
        }
    }

    function startAction() {

        $url = Mage::getModel('zoozpayment/standard')->processing(true);
        if ($url) {
            Mage::getModel('zoozpayment/order')->saveOrder();
            $this->getResponse()->setRedirect($url);
        } else {
            $this->getResponse()->setRedirect(Mage::getUrl('checkout/cart'));
        }
    }

}
