<?php
/**
 * ZooZ Payments Integration
 *
 * @category    Zooz
 * @package     Zooz_Payments
 */

/**
 * Zooz payment information model
 * Provides business logic information about payment flow
 */
class Zooz_Payments_Model_Info
{
    /**
     * @var integer
     */
    const PAYMENTSTATUS_APPROVED                                = 1001;
    const PAYMENTSTATUS_AUTHORIZED_PENDING_COMPLETION           = 1002;
    const PAYMENTSTATUS_AUTHORIZED_PENDING_USER_COMPLETION      = 1003;
    const PAYMENTSTATUS_REFUNDED                                = 1004;
    const PAYMENTSTATUS_REFUND_DUE_CHARGEBACK                   = 1005;
    const PAYMENTSTATUS_CHARGED_BACK                            = 1006;
    const PAYMENTSTATUS_REFUND                                  = 1007;
    const PAYMENTSTATUS_PENDING_USER_PAYMENT_COMPLETION         = 1008;
    const PAYMENTSTATUS_VOIDED                                  = 1009;
    const PAYMENTSTATUS_REFUND_DECLINED                         = 1011;
    const PAYMENTSTATUS_SUSPICIOUS_TRANSACTION                  = 1013;
    const PAYMENTSTATUS_PAYMENT_EXPIRED                         = 1014;
    const PAYMENTSTATUS_PAYMENT_IS_WAITING_FOR_AUTHORIZATION    = 1015;
    const PAYMENTSTATUS_PAYMENT_IS_WAITING_FOR_APPROVAL         = 1016;
    const PAYMENTSTATUS_PAYMENT_APPROVED_PENDING_FOR_REFUND     = 1017;
    const PAYMENTSTATUS_REFUND_IS_WAITING_FOR_APPROVAL          = 1018;

    /**
     * @var string
     */
    const PAYMENT_REASON_CODE_REFUND  = 'refund';
      
}
