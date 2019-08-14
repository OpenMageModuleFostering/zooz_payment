<?php
/**
 * ZooZ Payments Integration
 *
 * @category    Zooz
 * @package     Zooz_Payments
 */

$installer = $this;
$installer->startSetup();
$installer->run("
ALTER TABLE `{$installer->getTable('sales/quote_payment')}` 
ADD `zooz_credit_card_type` VARCHAR( 32 ) NOT NULL,
ADD `zooz_credit_card_exp_month` INT( 2 ) NOT NULL,
ADD `zooz_credit_card_exp_year` INT( 2 ) NOT NULL;
  
ALTER TABLE `{$installer->getTable('sales/order_payment')}` 
ADD `zooz_credit_card_type` VARCHAR( 32 ) NOT NULL,
ADD `zooz_credit_card_exp_month` INT( 2 ) NOT NULL,
ADD `zooz_credit_card_exp_year` INT( 2 ) NOT NULL;
");
$installer->endSetup();