<?php
	class NVPs {
		
		public $nameValuePairs;
		
		function __construct() {
			$this->nameValuePairs = Array();
		}
		
		function add($name, $value) {
			if (!empty($name) && ($value == 0 || !empty($value))) {
				$this->nameValuePairs[$name] = $value;
			}
		}
		
		function addAll(NVPs $nvps) {
			$this->nameValuePairs = array_merge($this->nameValuePairs, $nvps->nameValuePairs);
		}
		
		function toString() {
			$str = "";
			foreach ($this->nameValuePairs as $name => $value) {
				$str .= '&' . $name . '=' . urlencode($value);
			}
			return $str;
		}
	}
	
	
?>