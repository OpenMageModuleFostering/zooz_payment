<?php
require_once 'Mage/Checkout/controllers/CartController.php';
class ZooZ_ZoozPayment_CartController extends Mage_Checkout_CartController
{
	public function addAction()
	{
         
          
		$cart   = $this->_getCart();
		$params = $this->getRequest()->getParams();
			
		$response = array();
		try {
			if (isset($params['qty'])) {
				$filter = new Zend_Filter_LocalizedToNormalized(
				array('locale' => Mage::app()->getLocale()->getLocaleCode())
				);
				$params['qty'] = $filter->filter($params['qty']);
			}

			$product = $this->_initProduct();
			$related = $this->getRequest()->getParam('related_product');

			/**
			 * Check product availability
			 */
			if (!$product) {
				$response['status'] = 'ERROR';
				$response['message'] = $this->__('Unable to find Product ID');
			}

			$cart->addProduct($product, $params);
			if (!empty($related)) {
				$cart->addProductsByIds(explode(',', $related));
			}

			$cart->save();
                        
			$this->_getSession()->setCartWasUpdated(true);
                        
         


			/**
			 * @todo remove wishlist observer processAddToCart
			 */
			Mage::dispatchEvent('checkout_cart_add_product_complete',
			array('product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse())
			);

			if (!$cart->getQuote()->getHasError()){
				//New Code Here
				Mage::register('referrer_url', $this->_getRefererUrl());				
				$trimmedSessionToken = Mage::getModel('zoozpayment/standard')->ajaxprocessing(true);
				  
                                if($trimmedSessionToken != '') {
					//Mage::getModel('zoozpayment/order')->saveOrder();  
					echo "var data = {'token' : '" . $trimmedSessionToken . "'}";
					return;													
				}
			}
		} catch (Mage_Core_Exception $e) {
			$msg = "";
			if ($this->_getSession()->getUseNotice(true)) {
				$msg = $e->getMessage();
			} else {
				$messages = array_unique(explode("\n", $e->getMessage()));
				foreach ($messages as $message) {
					$msg .= $message.'<br/>';
				}
			}

			$response['status'] = 'ERROR';
			$response['message'] = $msg;
			$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
			return;			
		} catch (Exception $e) {
			$response['status'] = 'ERROR';
			$response['message'] = $this->__('Cannot add the item to shopping cart.');			
			Mage::logException($e);
			$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));			
			return;
		}
	}
}