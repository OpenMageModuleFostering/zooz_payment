<?php

	class UserDetails {
		
		public $firstName;
		public $lastName;
		public $phoneCountryCode;
		public $phoneNumber;
		public $email;
		public $additionalDetails;
		
		private function __construct($firstName, $lastName, $phoneCountryCode, $phoneNumber, $email, $additionalDetails) {
			$this->firstName = $firstName;
			$this->lastName = $lastName;
			$this->phoneCountryCode = $phoneCountryCode;
			$this->phoneNumber = $phoneNumber;
			$this->email = $email;
			$this->additionalDetails = $additionalDetails;
		}
		
		public static function createUserDetailsFromJson($jsonObj) {
			$phoneCountryCode = NULL;
			$phoneNumber = NULL;
			
			if (!empty($jsonObj[phone])) {
				$phoneCountryCode = $jsonObj[phone][countryCode];
				$phoneNumber = $jsonObj[phone][phoneNumber];
			}
			return new UserDetails(utf8_decode($jsonObj[firstName]), utf8_decode($jsonObj[lastName]), $phoneCountryCode, $phoneNumber, $jsonObj[email], utf8_decode($jsonObj[additionalDetails]));
		}
		
		public static function createUserDetails($firstName, $lastName, $phoneCountryCode, $phoneNumber, $email, $additionalDetails) {
			return new UserDetails($firstName, $lastName, $phoneCountryCode, $phoneNumber, $email, $additionalDetails);
		}
		
	}
?>