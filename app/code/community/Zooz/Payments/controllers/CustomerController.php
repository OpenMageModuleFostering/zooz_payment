<?php
/**
 * ZooZ Payments Integration
 *
 * @category    Zooz
 * @package     Zooz_Payments
 */

/**
 * Customer controller
 */
class Zooz_Payments_CustomerController extends Mage_Core_Controller_Front_Action
{
    const RESPONSE_CODE_SUCCESS = 0;
    const RESPONSE_CODE_FAILURE = -1;
    
    public function indexAction()
    {
        if (!$this->_getSession()->isLoggedIn()) {
            $this->_redirect('customer/account/login');
            return;
        }

        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Get list of all card in Zooz
     * If post data is set runs Api call and add credit card in Zooz
     *
     */
    public function creditcardsAction() {
        if (!$this->_getSession()->isLoggedIn()) {
            $this->_redirect('customer/account/login');
            return;
        }

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getParams();
            if (isset($data)) {
                $result = $this->_save($data);
                switch ($result->getResponseCode()) {
                    case self::RESPONSE_CODE_SUCCESS:
                        Mage::getSingleton('core/session')->addSuccess('Credit card has been added.');
                        break;
                    case self::RESPONSE_CODE_FAILURE:
                        Mage::getSingleton('core/session')->addError('Credit card has not been saved. Please try again.');
                        break;
                }

                $this->_redirect('payments/customer/creditcards');
            }
        }

        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->renderLayout();
    }

    /**
     * Delete custome credit card in Zooz
     *
     */
    public function deletecardAction()
    {
        $isValidToken = false;
        $token = $this->getRequest()->getParam('t');
        $customer = $this->_getCustomer();
        $api = $this->_getApi();
        $paymentMethods = $api->getPaymentMethods($customer->getId(), true);
        
        foreach($paymentMethods as $key => $pm) {
            if($pm->getData('paymentMethodToken') == $token) {
                $isValidToken = true;
            }
        }
        
        if($isValidToken) {
            $this->_delete($customer->getId(), $token);
            Mage::getSingleton('core/session')->addSuccess('Credit card has been deleted.');
            $this->_redirect('payments/customer/creditcards');
        } else {
            Mage::getSingleton('core/session')->addError('Credit card token is not valid. Please try again.');
            $this->_redirect('payments/customer/creditcards');
        }
    }

    /**
     * Edit cc card ub Zooz
     *
     */
    public function editcardAction()
    {
        $isValidToken = false;
        $token = $this->getRequest()->getParam('t');
        $customer = $this->_getCustomer();
        $api = $this->_getApi();
        $paymentMethods = $api->getPaymentMethods($customer->getId(), true);
        
        foreach($paymentMethods as $key => $pm) {
            if($pm->getData('paymentMethodToken') == $token) {
                $isValidToken = true;
            }
        }
        
        if($isValidToken) {
            
        } else {
            Mage::getSingleton('core/session')->addError('Credit card token is not valid. Please try again.');
            $this->_redirect('payments/customer/creditcards');
        }
        
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->renderLayout();
    }

    /**
     * Updates customer billing address in Zooz
     *
     */
    public function updateCustomerAddressAction() {
        if(!Mage::app()->getRequest()->isAjax()){
            $this->norouteAction();
            exit;
        }

        $postData = $this->getRequest()->getPost();
        parse_str($postData['form'], $billing);

        $customer = $this->_getCustomer();

        $address = new Varien_Object();
        $address->addData(
            array(
                'state' => $billing['state'],
                'city' => $billing['city'],
                'street' => $billing['street'],
                'zip_code' => $billing['zipcode'],
                'firstname' => $billing['firstname'],
                'lastname' => $billing['lastname'],
                'telephone' => $billing['telephone'],
                'phone_number' => $billing['telephone'],
                'country_code' => $billing['country_id'],
            )
        );

        $response = Mage::getModel("payments/api")->updateBilling($customer->getId(), $address);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array('token' => $response)));
    }

    /**
     * Prepare and save cc data in Zooz
     *
     * @param $data array Post data used for saving cc card
     * @return Varien_Object
     */
    protected function _save($data)
    {
        if(!isset($data['payment'])) return new Varien_Object(array("response_code" => 0));

        $cardData = new Varien_Object();
        $billingAddress = new Varien_Object();
        
        $customer = $this->_getCustomer();
        $customerData = Mage::getModel('customer/customer')->load($customer->getId())->getData();
        $holderEmail = $customerData['email'];

        $billingAddress->setFirstname($data['firstname'])
                       ->setLastname($data['lastname'])
                       ->setStreet($data['street'])
                       ->setCity($data['city'])
                       ->setState($data['state'])
                       ->setZipCode($data['zipcode'])
                       ->setCountryCode($data['country_id'])
                       ->setPhoneNumber($data['telephone']);
        
        $cardData->setHolderEmail($holderEmail)
                 ->setCcExpMonth($data['payment']['cc_exp_month'])
                 ->setCcExpYear($data['payment']['cc_exp_year'])
                 ->setHolderFirstname($data['firstname'])
                 ->setHolderLastname($data['lastname'])
                 ->setCcNumber($data['payment']['cc_number'])
                 ->setCcCvv($data['payment']['cc_cid'])
                 ->setCcSaveData(true)
                 ->setBillingAddress($billingAddress);                
                 
        $response = $this->_getApi()->addPaymentMethod($customer->getId(), $cardData);
        
        return $response;
    }

    /**
     * Calls API and removed cc on Zooz side form saved cc list
     *
     * @param $cutomerLoginId
     * @param $paymentMethodToken
     * @return mixed
     */
    protected function _delete($cutomerLoginId, $paymentMethodToken)
    {
        $response = $this->_getApi()->removePaymentMethod($cutomerLoginId, $paymentMethodToken);
        return $response;
    }

    /**
     * Retrieve Zooz API model
     *
     * @return Zooz_Payments_Model_Api
     */
    protected function _getApi()
    {
        return Mage::getSingleton('payments/api');
    }
    
    /**
     * Retrieve customer session model object
     *
     * @return Mage_Customer_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('customer/session');
    }

    /**
     * Retrieve customers session
     *
     * @return Mage_Customer_Model_Session
     */
    protected function _getCustomer()
    {
        return Mage::getSingleton('customer/session');
    }
    
    
}
