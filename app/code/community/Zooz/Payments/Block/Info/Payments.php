<?php
/**
 * ZooZ Payments Integration
 *
 * @category    Zooz
 * @package     Zooz_Payments
 */

class Zooz_Payments_Block_Info_Payments extends Mage_Payment_Block_Info_Cc {
    
    /**
     * Checkout progress information block flag
     *
     * @var bool
     */
    protected $_isCheckoutProgressBlockFlag = true;
    /**
     * Set block template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('zoozpayments/info/payments.phtml');
    }

    /**
     * Render as PDF
     *
     * @return string
     */
    public function toPdf()
    {
        $this->setTemplate('zoozpayments/info/pdf.phtml');
        return $this->toHtml();
    }

    /**
     * Retrieve card info object
     *
     * @return mixed
     */
    public function getInfo()
    {
        if ($this->hasCardInfoObject()) {
            return $this->getCardInfoObject();
        }
        return parent::getInfo();
    }

    /**
     * Set checkout progress information block flag
     * to avoid showing credit card information from payment quote
     * in Previously used card information block
     *
     * @param bool $flag
     * @return Mage_Paygate_Block_Authorizenet_Info_Cc
     */
    public function setCheckoutProgressBlock($flag)
    {
        $this->_isCheckoutProgressBlockFlag = $flag;
        return $this;
    }

    /**
     * Retrieve credit cards info
     *
     * @return array
     */
    public function getCards()
    {
        $cardsData = $this->getMethod()->getCardsStorage()->getCards();
        $cards = array();

        if (is_array($cardsData)) {
            foreach ($cardsData as $cardInfo) {
                $data = array();
                if ($cardInfo->getProcessedAmount()) {
                    $amount = Mage::helper('core')->currency($cardInfo->getProcessedAmount(), true, false);
                    $data[Mage::helper('payments')->__('Processed Amount')] = $amount;
                }
                if ($cardInfo->getBalanceOnCard() && is_numeric($cardInfo->getBalanceOnCard())) {
                    $balance = Mage::helper('core')->currency($cardInfo->getBalanceOnCard(), true, false);
                    $data[Mage::helper('payments')->__('Remaining Balance')] = $balance;
                }
                $this->setCardInfoObject($cardInfo);
                $cards[] = array_merge($this->getSpecificInformation(), $data);
                $this->unsCardInfoObject();
                $this->_paymentSpecificInformation = null;
            }
        }
        if ($this->getInfo()->getCcType() && $this->_isCheckoutProgressBlockFlag) {
            $cards[] = $this->getSpecificInformation();
        }
        return $cards;
    }
}
