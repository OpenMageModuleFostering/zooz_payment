<?php

/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   EcomDev
 * @package    EcomDev_ProductPageShipping
 * @copyright  Copyright (c) 2010 Ecommerce Developer Blog (http://www.ecomdev.org)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
require_once 'app/code/core/Mage/Catalog/controllers/ProductController.php';

/**
 * Estimate shiping controller, passes the request to estimate model
 * Extended from product controller for supporting of full product initialization
 *
 */
class ZooZ_ZoozPayment_EstimateController extends Mage_Catalog_ProductController {

    /**
     * Estimate action
     *
     * Initializes the product and passes data to estimate model in block
     */
    public function addProduct() {
        return $this->_initProduct('');
    }

    protected function _initProduct($productId) {
        $params = new Varien_Object();
        return Mage::helper('catalog/product')->initProduct($productId, $this, $params);
    }

    public function indexAction() {
        //  $pro = $this->addProduct();

        //  $data_json = $this->getRequest()->getPost('data');
        //  $data_json = $this->getRequest()->getContent();
	$data_json = file_get_contents('php://input');
	Mage::log($data_json);
	$variable_post = json_decode($data_json);
        $arr_addressinfo = get_object_vars($variable_post->estimate);
        $arr_cart = $variable_post->cart;
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->loadLayout(false);
        $block = $this->getLayout()->getBlock('shipping.estimate.result');

        if ($block) {
	
            $estimate = $block->getEstimate();
            foreach ($arr_cart as $pro) {
                 $product = $this->_initProduct($pro->invoiceItemId);
                if ($pro->options) {
                   
                    $params = get_object_vars($pro->options);
                    $pro_cart = array('product' => $pro->invoiceItemId, 'qty' => $pro->qty, 'options' => $params);
                   
                } else {
                
                    $pro_cart = array('product' => $pro->invoiceItemId, 'qty' => $pro->qty);
                    
                }
			
                $product->setAddToCartInfo($pro_cart);
                    $estimate->setProduct($product);
                    Mage::unregister('current_category');
                    Mage::unregister('current_product');
                    Mage::unregister('product');
            }
            ///$addressInfo = $arr_addressinfo;
				
            $addressConvert = array("city"=> $arr_addressinfo["city"],"region_id"=>$arr_addressinfo["stateName"],"postcode"=>$arr_addressinfo["zipCode"],"country_id"=>$arr_addressinfo["countryCode"]);
         
            $estimate->setAddressInfo((array) $addressConvert);
            $block->getSession()->setFormValues($addressConvert);
            try {
                $estimate->estimate();
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('catalog/session')->addError($e->getMessage());
            } catch (Exception $e) {
                Mage::logException($e);
                Mage::getSingleton('catalog/session')->addError(
                        Mage::helper('zoozpayment')->__('There was an error during processing your shipping request')
                );
            }
        }
        $this->_initLayoutMessages('catalog/session');
        $this->renderLayout();
    }

}
