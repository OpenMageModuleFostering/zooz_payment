<?php
	class InvoiceItem {
		
		public $name;
		public $id;
		public $quantity;
		public $price;
		public $taxAmount;
		public $additionalDetails; 
		
		private function __construct($name, $id, $quantity, $price, $taxAmount, $additionalDetails) {
			$this->name = $name;
			$this->id = $id;
			$this->quantity = $quantity;
			$this->price = $price;
			$this->taxAmount = $taxAmount;
			$this->additionalDetails = $additionalDetails;
		}
		
		public static function createInvoiceItemFromJson($jsonObj) {
			return new InvoiceItem(utf8_decode($jsonObj[name]), $jsonObj[id], $jsonObj[quantity], $jsonObj[price], $jsonObj[taxAmount], utf8_decode($jsonObj[additionalDetails]));
		}
		
		public static function createInvoiceItem($name, $id, $quantity, $price, $taxAmount, $additionalDetails) {
			return new InvoiceItem($name, $id, $quantity, $price, $taxAmount, $additionalDetails);
		}

	}
?>