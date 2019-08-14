<?php
class ZooZ_ZoozPayment_StandardController extends Mage_Core_Controller_Front_Action {
    public function indexAction() {    
    	$this->getResponse()->setRedirect(Mage::getUrl('', array('_secure' => true))); 
    }

    public function redirectAction() {    
		
		$trimmedSessionToken = Mage::getModel('zoozpayment/standard')->ajaxprocessing();
		if($trimmedSessionToken != '') {
                    
			$this->getResponse()->setRedirect(Mage::getUrl('zoozpayment').'?token='.$trimmedSessionToken);											
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
		if($trimmedSessionToken != '') {
			
			echo "var data = {'token' : '" . $trimmedSessionToken . "'}";
			
			return;													
		}	    
    }
    
    function startAction() {
    	 		   		
	    	$url = Mage::getModel('zoozpayment/standard')->processing(true);
	    	if($url) {
	    		Mage::getModel('zoozpayment/order')->saveOrder(); 
			    $this->getResponse()->setRedirect($url);
			} else {
				$this->getResponse()->setRedirect(Mage::getUrl('checkout/cart'));
			}    	
	    
    }
}    