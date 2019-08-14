<?php
	
	
	require_once(Mage::getBaseDir('lib') . '/zooz/invoice.item.php');

	class Invoice {
		
		public $number;
		public $items;
		public $additionalDetails;
		
		private function __construct($number, $additionalDetails, $items) {
			$this->number = $number;
			if (empty($items)) {
				$this->items = Array();
			} else {
				$this->invoiceItems = $items;
			}
			$this->additionalDetails = $additionalDetails;	
		}
		
		public static function createInvoiceFromJson($jsonObj) {
			$items = Array();
			if (!empty($jsonObj[items])) {
				foreach ($jsonObj[items] as $key => $value) {
					array_push($items, InvoiceItem::createInvoiceItemFromJson($value));
				}
			}
			return new Invoice($jsonObj[number], utf8_decode($jsonObj[additionalDetails]), $items);
		}
		
		public static function createInvoice($number = NULL, $additionalDetails = NULL, $items = NULL) {
			return new Invoice($number, $additionalDetails, $items);
		}
		
		public function addItem($name, $id, $quantity, $price, $taxAmount, $additionalDetails = NULL) {
			array_push($this->items, InvoiceItem::createInvoiceItem($name, $id, $quantity, $price, $taxAmount, $additionalDetails));
		}
		
	}
	
?>