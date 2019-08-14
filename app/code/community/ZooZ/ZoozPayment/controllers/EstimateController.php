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
    protected $productId;

    protected function _initProduct() {
        $params = new Varien_Object();
        $productId = $this->productId;
        //return Mage::helper('catalog/product')->initProduct($productId, $this, $params);
        return Mage::getModel('catalog/product')->load($productId);
    }

    public function quoteAction() {
        echo $discount = Mage::helper('zoozpayment')->getdiscount();
        die();
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $payment = $quote->getPayment();

        $payment->setMethod('zoozpayment');
//       
        $quote->collectTotals();
        $service = Mage::getModel('sales/service_quote', $quote);
        $service->submitAll();
//        
        $cart = Mage::helper('checkout/cart')->getCart();
        $data = $cart->getQuote()->getData();

        //$quote = Mage::getSingleton('checkout/session')->getQuote();
        //  print_r($quote->getData());
//        $rewardsQuote = Mage::getSingleton('rewards/session')->getQuote();
//         print_r($rewardsQuote);
//        $cart = Mage::getSingleton('checkout/cart');
//         $rewardsQuote = Mage::getModel('rewards/sales_quote');
//            
//            $rewardsQuote->updateItemCatalogPoints( $cart->getQuote() );
//            
//			$cart->getQuote ()->collectTotals ();
//			$cart->getQuote ()->getShippingAddress ()->setCollectShippingRates ( true );
//			$cart->getQuote ()->getShippingAddress ()->collectShippingRates();
//			
//            $rewardsQuote->updateDisabledEarnings( $cart->getQuote() );
        //   $cart = Mage::getSingleton('checkout/cart');
        //  $rewardsQuote->updateItemCatalogPoints($cart->getQuote());
        //  $rewardsQuote->setPointsSpending('500');
        // $cart = Mage::getSingleton('checkout/cart');
        // $rewardsQuote = Mage::getModel('rewards/sales_quote');
//
//        $rewardsQuote->updateItemCatalogPoints($cart->getQuote());
//
//        $cart->getQuote()->collectTotals();
//        $cart->getQuote()->getShippingAddress()->setCollectShippingRates(true);
//        $cart->getQuote()->getShippingAddress()->collectShippingRates();
//
//        $rewardsQuote->updateDisabledEarnings($cart->getQuote());
        //     echo $rewardsQuote->getTotalPointsSpendingAsStringList()."kkkk";
        // print_r($rewardsQuote->updateShoppingCartPoints($cart));
        //magento.zooz.com/magentoe/index.php/zoozpayment/index/test/
    }

    public function indexAction() {
        Mage::log("Received Estimate controller index action");
        $data_json = $this->getRequest()->getPost('data');
        if ($data_json == '') {
            $data_json = file_get_contents('php://input');
        }
        Mage::log("Json received" . $data_json);


        if ($data_json == '') {
            Mage::log("Could not receive data.");
            return;
        }
        $variable_post = json_decode($data_json);
        $arr_addressinfo = get_object_vars($variable_post->estimate);
        $arr_cart = $variable_post->cart;
        $this->getResponse()->setHeader('Content-type', 'application/json');
        Mage::log("Loading layout");
        $this->loadLayout(false);
        $block = $this->getLayout()->getBlock('shipping.estimate.result');
        Mage::log("Block receieved");
        if ($block) {

            $estimate = $block->getEstimate();
            foreach ($arr_cart as $pro) {
                Mage::log("Itterating product ID: " . $pro->invoiceItemId);
                if ($pro->invoiceItemId == '-i') {
                    continue;
                }

                $this->productId = $pro->invoiceItemId;

                $product = $this->_initProduct($pro->invoiceItemId);
                if (isset($pro->options) && $pro->options != '') {

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
            $addressConvert = array("city" => isset($arr_addressinfo["city"]) ? $arr_addressinfo["city"] : "", "region_id" => isset($arr_addressinfo["stateName"]) ? $arr_addressinfo["stateName"] : "", "postcode" => isset($arr_addressinfo["zipCode"]) ? $arr_addressinfo["zipCode"] : "", "country_id" => isset($arr_addressinfo["countryCode"]) ? $arr_addressinfo["countryCode"] : "");
            Mage::log("Address converted");
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
