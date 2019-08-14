<?php

        require_once(Mage::getBaseDir('lib') . '/zooz/nvps.php');
        require_once(Mage::getBaseDir('lib') . '/zooz/transaction.details.php');
        require_once(Mage::getBaseDir('lib') . '/zooz/zooz.exception.php');


	class ZooZExtendedServerAPI {
		
		private $ch;
		
		
		const SANDBOX_URL =	"https://sandbox.zooz.co";
		const PRODUCTION_URL = "https://app.zooz.com";



		const VERSION_NUMBER = "1.0.6";
		
		/**
		 * ZooZExtendedServerAPI Constructor.
		 * 
		 * @param string $zoozDeveloperId - Developer email as entered in ZooZ Developer portal.
		 * @param string $zoozServerAPIKey - ZooZ Server API Key assigned to you upon registration. Can be found on the "Account" section of the ZooZ Developer Portal.
		 * @param string $isSandbox - TRUE to use sandbox environment, FALSE to use live environment.
		 * @throws ZooZException
		 */
		function __construct($zoozDeveloperId, $zoozServerAPIKey, $isSandbox) {
			
			if (!function_exists('curl_init')){
				throw new ZooZException('Sorry cURL is not installed!');
			}
			
			if (empty($zoozDeveloperId)) {
				throw new ZooZException("Please enter your developer email as registered in ZooZ's developer portal");
			}
				
			if (empty($zoozServerAPIKey)) {
				throw new ZooZException("Please enter ZooZ Server API Key as diaplayed in ZooZ's developer portal under 'My Account'");
			}
				
			$this->ch = curl_init();
			
			$zoozServer;
				
			if ($isSandbox) {
				$zoozServer = self::SANDBOX_URL;
				curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
			} else {
				$zoozServer = self::PRODUCTION_URL;
			}
				
			$zoozServer .= "/mobile/ExtendedServerAPI";
				
			curl_setopt($this->ch, CURLOPT_HTTPHEADER, array(
					'ZooZDeveloperId: ' . $zoozDeveloperId,
					'ZooZServerAPIKey: ' . urlencode($zoozServerAPIKey),
			) );
			
			curl_setopt($this->ch, CURLOPT_URL, $zoozServer);
			curl_setopt($this->ch, CURLOPT_TIMEOUT, 30 );
			curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($this->ch, CURLOPT_POST, true);
				
		}
		
		/**
		 * Get transaction details with the transaction ID API. 
		 * This allows you to retrieve complete transaction details and transaction statuses before and/or after commit or refund. 
		 * This call does not change the transaction state.
		 * 
		 * @param string $transactionID - Transaction ID as returned by the SDK upon transaction completion.
		 * @throws ZooZException
		 * @return TransactionDetails - Transaction details for above transaction
		 */
		function getTransactionDetailsByTransactionID($transactionID) {
			
			if (empty($transactionID)) {
				throw new ZooZException("transactionID is empty");
			}
			
			$nvps = new NVPs();
			$nvps->add("cmd", "getTransactionDetails");
			$nvps->add("ver", self::VERSION_NUMBER);
			$nvps->add("transactionID", $transactionID);
			
			$responseObj = $this->postToZooZ($nvps->toString());
			
			
			return new TransactionDetails($responseObj);
			
			
		}
		
		/**
		 * Retrieve transaction full details and transaction status before or/and after commit or refund for all transactions associated with the payee's email. 
		 * 
		 * @param string $email - Payee's email
		 * @param DateTime $fromDate (optional) - minimum date to query
		 * @param DateTime $toDate (optional) - maximum date to query
		 * @throws ZooZException
		 * @return TransactionDetails[] - Array of transaction details for all transactions associated with the above email
		 */
		function getTransactionDetailsByPayeeEmail($email, $fromDate=NULL, $toDate=NULL) {
			
			if (empty($email)) {
				throw new ZooZException("email is empty");
			}
				
			$nvps = new NVPs();
			$nvps->add("cmd", "getTransactionDetailsByPayeeEmail");
			$nvps->add("ver", self::VERSION_NUMBER);
			$nvps->add("email", $email);
				
			if (!empty($fromDate)) {
				$nvps->add("fromDate", date_format($fromDate, 'Y-m-d'));
			}
			
			if (!empty($toDate)) {
				$nvps->add("toDate", date_format($toDate, 'Y-m-d'));
			}
			
			$responseObj = $this->postToZooZ($nvps->toString());
			
			$transactionDetailsArr = Array();
			if (!empty($responseObj) && !empty($responseObj[payments])) {
				foreach ($responseObj[payments] as $key => $value) {
					array_push($transactionDetailsArr, new TransactionDetails($value));
				}
			}
			return $transactionDetailsArr;			
		}
		
		/**
		 * Commit a transaction in pending status (If your app is set to allow deferred payments).
		 * 
		 * @param string $transactionID - Transaction ID as returned by the SDK upon transaction completion.
		 * @param double $amount - (Optional) amount for partial commit, must be lower that the original transaction amount.
		 * @param Invoice $invoice - (Optional) invoice to be updated for payment.
		 * @throws ZooZException
		 * @return boolean - TRUE if action completed successfully.
		 */
		function commitTransaction($transactionID, $amount=NULL, $invoice=NULL) {
			
			if (empty($transactionID)) {
				throw new ZooZException("transactionID is empty");
			}
			
			$nvps = new NVPs();
			$nvps->add("cmd", "commitTransaction");
			$nvps->add("ver", self::VERSION_NUMBER);
			$nvps->add("transactionID", $transactionID);
			
			
			
			if (!empty($amount)) {
				$nvps->add("amount", $amount);
			}
			
			if (!empty($invoice)) {
				$nvps->add("invoice", json_encode($invoice));
			}
			
			$responseObj = $this->postToZooZ($nvps->toString());
			if (!empty($responseObj) && !empty($responseObj[boolean]) && $responseObj[boolean] == true) {
				 return true;
			}
			return false;
		}
		
		/**
		 * Refund a transaction (Up to 60 days after execution)
		 * 
		 * @param string $transactionID - Transaction ID as returned by the SDK upon transaction completion.
		 * @param double $amount - (Optional) amount for partial refund, must be lower that the original transaction amount.
		 * @throws ZooZException
		 * @return boolean - TRUE if action completed successfully.
		 */
		function refundTransaction($transactionID, $amount=NULL) {
			
			if (empty($transactionID)) {
				throw new ZooZException("transactionID is empty");
			}
				
			$nvps = new NVPs();
			$nvps->add("cmd", "refundTransaction");
			$nvps->add("ver", self::VERSION_NUMBER);
			$nvps->add("transactionID", $transactionID);
				
			if ($amount != null) {
				$nvps->add("amount", $amount);
			}
				
			$responseObj = $this->postToZooZ($nvps->toString());
			if (!empty($responseObj) && !empty($responseObj[boolean]) && $responseObj[boolean] == true) {
				return true;
			}
			return false;
		}
		
		private function postToZooZ($data) {
				
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
			$chResult = curl_exec($this->ch);
			$chResult = trim($chResult, "\n");
			
			$errorMsg = "Error communicating with ZooZ server, please check your network connection";
			
			$errorCode = -1;
			
			if (empty($chResult)) {
				throw new ZooZException($errorMsg);
			}
			
			$decoded = json_decode(trim($chResult), TRUE); 
			
			if ($decoded[ResponseStatus] != 0) {
				if (!empty($decoded[ResponseObject]) && !empty($decoded[ResponseObject][errorMessage])) {
					$errorMsg = $decoded[ResponseObject][errorMessage];
					$errorCode = $decoded[ResponseStatus];
				}
				throw new ZooZException($errorMsg, $errorCode);
			}
			
			return $decoded[ResponseObject];
				
		}
		
		/**
		 * ZooZExtendedServerAPI destructor
		 */
		function __destruct() {
			curl_close($this->ch);
		}
		
	}
?>