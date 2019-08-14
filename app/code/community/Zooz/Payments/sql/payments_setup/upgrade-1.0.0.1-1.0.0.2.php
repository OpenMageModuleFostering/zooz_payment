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
DROP TABLE IF EXISTS `{$this->getTable('zooz')}`;
CREATE TABLE `{$this->getTable('zooz')}` (
`debug_id` int(11) NOT NULL AUTO_INCREMENT,
`adyen_response` 	text NULL,
PRIMARY KEY (`debug_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$installer->endSetup();
