<?php
	
	class Address {
		
		public $addressType;
 		public $firstName;
 		public $lastName;
// 		public $phoneCountryCode;
// 		public $phoneNumber;
		public $street;
		public $city;
		public $state;
		public $country;
		public $countryCode;
		public $zipCode;
		
		function __construct($addressType, $firstName, $lastName, $street, $city, $state, $country, $zipCode, $countryCode) {
			$this->addressType = $addressType;
			$this->firstName = $firstName;
			$this->lastName = $lastName;
			$this->street = $street;
			$this->city = $city;
			$this->state = $state;
			$this->country = $country;
			$this->countryCode = $countryCode;
			$this->zipCode = $zipCode;
		}
		
		public static function createAddressFromJson($addressType, $jsonObj) {
			return new Address($addressType, utf8_decode($jsonObj[firstName]), utf8_decode($jsonObj[lastName]), utf8_decode($jsonObj[street]), utf8_decode($jsonObj[city]), utf8_decode($jsonObj[state]), utf8_decode($jsonObj[country]), $jsonObj[zipCode],  utf8_decode($jsonObj[countryCode]));
		}
		
		public static function createAddress($addressType, $firstName, $lastName, $street, $city, $state, $country, $zipCode, $countryCode) {
			return new Address($addressType, $firstName, $lastName, $street, $city, $state, $country, $zipCode, $countryCode);
		}
		
	}
	
	
	class AddressType {

		const billingAddress = "billingAddress";

		const shippingAddress = "shippingAddress";

	}

?>