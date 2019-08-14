<?php
class ZooZ_ZoozPayment_IndexController extends Mage_Core_Controller_Front_Action{
    //public function IndexAction() {
		//$this->getResponse()->setRedirect(Mage::getUrl('', array('_secure' => true))); 
    //}
    
    public function IndexAction() {      
	  $this->loadLayout();   
	  $this->getLayout()->getBlock("head")->setTitle($this->__("Zoozpayment checkout"));
	  
	  $breadcrumbs = $this->getLayout()->getBlock("breadcrumbs");
      $breadcrumbs->addCrumb("home", array(
                "label" => $this->__("Home Page"),
                "title" => $this->__("Home Page"),
                "link"  => Mage::getBaseUrl()
		   ));

      $breadcrumbs->addCrumb("zoozpayment", array(
                "label" => $this->__("Zoozpayment checkout"),
                "title" => $this->__("Zoozpayment checkout")
		   ));

      $this->renderLayout(); 
	  
    }    
}