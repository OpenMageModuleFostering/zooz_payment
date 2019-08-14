<?php

$mageFilename = 'app/Mage.php';
require_once $mageFilename;
Mage::setIsDeveloperMode(true);
ini_set('display_errors', 1);
ini_set('max_execution_time', 300);
umask(0);
Mage::app();


$uri = 'https://sandbox.zooz.co/mobile/ZooZPaymentAPI';

$headers = array(
    'programId' => 'PAPI_ZooZNP_TS2YCLIPYYRNBNMMOHUUMNOZFM_1',
    'programKey' => '0532741d-bc8e-4ae8-8aa3-cbc2b9e55b4e',
    'ZooZResponseType' => 'JSon'
);

$card = array(
    'expirationDate' => '01/2019',
    'cardHolderName' => 'John Doe',
    'cardNumber' => '4580458045804580', //5105105105105100
    'cvvNumber' => '123'
);

$baseClient = new Varien_Http_Client();
$baseClient->setUri($uri);
$baseClient->setConfig(array(
    'maxredirects'=>0,
    'timeout'=>30,

));
$baseClient->setHeaders($headers);
$baseClient->setMethod(Zend_Http_Client::POST);
var_dump($uri, $headers);


/*
 * open payment
 */
var_dump('open payment');
$openPayment = array(
    'command' => 'openPayment',
    'paymentDetails' => array(
        'amount' => 10,
        'currencyCode' => 'USD'
    ),
    'customerDetails' => array(
        'customerLoginID' => time()
    )
);

$dataJson = json_encode($openPayment);
var_dump($openPayment, $dataJson);

$client = clone $baseClient;
$client->setRawData($dataJson, 'application/json');
$response = $client->request();
$paymentResponse = json_decode($response->getBody(), true);
$paymentToken = $paymentResponse['responseObject']['paymentToken'];

var_dump(
    $response->getStatus(),
    $response->getBody(),
    $paymentResponse,
    $paymentToken
);



/*
 * add payment method
 */
var_dump('add payment method');
$addPayment = array(
    'command' => 'addPaymentMethod',
    'paymentToken' => $paymentToken,
    'email' => 'test@gmail.com',
    'paymentMethod' => array(
        'paymentMethodType' => 'CreditCard',
        'paymentMethodDetails' => $card
    ),
);

$dataJson = json_encode($addPayment);
var_dump($addPayment, $dataJson);

$client = clone $baseClient;
$client->setRawData($dataJson, 'application/json');
$response = $client->request();
$methodResponse = json_decode($response->getBody(), true);
$paymentMethodToken = $methodResponse['responseObject']['paymentMethodToken'];

var_dump(
    $response->getStatus(),
    $response->getBody(),
    $methodResponse,
    $paymentMethodToken
);



//authorize payment
var_dump('authorize payment');
$authorize = array(
    'command' => 'authorizePayment',
    'paymentToken' => $paymentToken,
    'paymentMethod' => array(
        'paymentMethodType' => 'CreditCard',
        'paymentMethodToken' => $paymentMethodToken,
        'paymentMethodDetails' => array(
            'cvvNumber' => $card['cvvNumber']
        )
    )
);

$dataJson = json_encode($authorize);
var_dump($authorize, $dataJson);

$client = clone $baseClient;
$client->setRawData($dataJson, 'application/json');
$response = $client->request();
$authorizeResponse = json_decode($response->getBody(), true);

var_dump(
    $response->getStatus(),
    $response->getBody(),
    $authorizeResponse
);



//capture
var_dump('capture');
$capture = array(
    'command' => 'commitPayment',
    'paymentToken' => $paymentToken,
);

$dataJson = json_encode($capture);
var_dump($capture, $dataJson);

$client = clone $baseClient;
$client->setRawData($dataJson, 'application/json');
$response = $client->request();
$captureResponse = json_decode($response->getBody(), true);

var_dump(
    $response->getStatus(),
    $response->getBody(),
    $captureResponse
);



//sell
/*
var_dump('sell');
$sell = array(
    'command' => 'sale',
    'paymentToken' => $paymentToken,
    'paymentMethod' => array(
        'paymentMethodType' => 'CreditCard',
        'paymentMethodToken' => $paymentMethodToken,
        'paymentMethodDetails' => array(
            'cvvNumber' => $card['cvvNumber']
        )
    )
);

$dataJson = json_encode($sell);
var_dump($sell, $dataJson);
$client = clone $baseClient;
$client->setRawData($dataJson, 'application/json');
$response = $client->request();
$sellResponse = json_decode($response->getBody(), true);

var_dump(
    $response->getStatus(),
    $response->getBody(),
    $sellResponse
);
*/