<?php

/**
 * ZooZ Payments Integration
 *
 * @category    Zooz
 * @package     Zooz_Payments
 */

class Zooz_Payments_Helper_Data extends Mage_Core_Helper_Abstract
{
    const CUSTOMER_IFRAME = 'https://paymentpages.zooz.com/Magento/iframe_customer_dashboard.html';

    private $_subtypeToTypeCodeMap = array(
        'visa' => 'VI',
        'mastercard' => 'MC',
        'americanexpress' => 'AE',
        'discover' => 'DI',
        'diners' => 'DICL',
        'jcb' => 'JCB',
    );

    /**
     * Converts a lot of messages to message
     *
     * @param  array $messages
     * @return string
     */
    public function convertMessagesToMessage($messages)
    {
        return implode(' | ', $messages);
    }

    /**
     * Return message for gateway transaction request
     *
     * @param  Mage_Payment_Model_Info $payment
     * @param  string $requestType
     * @param  string $lastTransactionId
     * @param  Varien_Object $card
     * @param float $amount
     * @param string $exception
     * @return bool|string
     */
    public function getTransactionMessage($payment, $requestType, $lastTransactionId, $card, $amount = false, $exception = false
    )
    {
        return $this->getExtendedTransactionMessage(
            $payment, $requestType, $lastTransactionId, $card, $amount, $exception
        );
    }

    /**
     * Return message for gateway transaction request
     *
     * @param  Mage_Payment_Model_Info $payment
     * @param  string $requestType
     * @param  string $lastTransactionId
     * @param  Varien_Object $card
     * @param float $amount
     * @param string $exception
     * @param string $additionalMessage Custom message, which will be added to the end of generated message
     * @return bool|string
     */
    public function getExtendedTransactionMessage($payment, $requestType, $lastTransactionId, $card, $amount = false, $exception = false, $additionalMessage = false
    )
    {
        $operation = $this->_getOperation($requestType);

        if (!$operation) {
            return false;
        }

        if ($amount) {
            $amount = $this->__('amount %s', $this->_formatPrice($payment, $amount));
        }

        if ($exception) {
            $result = $this->__('failed');
        } else {
            $result = $this->__('successful');
        }

        $card = $this->__('Credit Card: xxxx-%s', $card->getCcLast4());

        $pattern = '%s %s %s - %s.';
        $texts = array($card, $amount, $operation, $result);

        if (!is_null($lastTransactionId)) {
            $pattern .= ' %s.';
            $texts[] = $this->__('Zooz Transaction ID %s', $lastTransactionId);
        }

        if ($additionalMessage) {
            $pattern .= ' %s.';
            $texts[] = $additionalMessage;
        }
        $pattern .= ' %s';
        $texts[] = $exception;

        return call_user_func_array(array($this, '__'), array_merge(array($pattern), $texts));
    }

    /**
     * Return operation name for request type
     *
     * @param  string $requestType
     * @return bool|string
     */
    protected function _getOperation($requestType)
    {
        switch ($requestType) {
            case Zooz_Payments_Model_Payments::REQUEST_TYPE_AUTH_ONLY:
                return $this->__('authorize');
            case Zooz_Payments_Model_Payments::REQUEST_TYPE_AUTH_CAPTURE:
                return $this->__('authorize and capture');
            default:
                return false;
        }
    }

    /**
     * Get phone number country code based on country code
     *
     * @param string $countryCode
     * @return string phone code
     */
    public function getPhoneCode($address)
    {
        $countryCodes = array(
            array(
                'country_code' => 'AF',
                'phone_code' => '93',
            ),
            array(
                'country_code' => 'AX',
                'phone_code' => '35818',
            ),
            array(
                'country_code' => 'NL',
                'phone_code' => '31',
            ),
            array(
                'country_code' => 'AN',
                'phone_code' => '599',
            ),
            array(
                'country_code' => 'AL',
                'phone_code' => '355',
            ),
            array(
                'country_code' => 'DZ',
                'phone_code' => '213',
            ),
            array(
                'country_code' => 'AS',
                'phone_code' => '685',
            ),
            array(
                'country_code' => 'AD',
                'phone_code' => '376',
            ),
            array(
                'country_code' => 'AO',
                'phone_code' => '244',
            ),
            array(
                'country_code' => 'AI',
                'phone_code' => '1264',
            ),
            array(
                'country_code' => 'AQ',
                'phone_code' => '672',
            ),
            array(
                'country_code' => 'AG',
                'phone_code' => '1268',
            ),
            array(
                'country_code' => 'AE',
                'phone_code' => '971',
            ),
            array(
                'country_code' => 'AR',
                'phone_code' => '54',
            ),
            array(
                'country_code' => 'AM',
                'phone_code' => '374',
            ),
            array(
                'country_code' => 'AW',
                'phone_code' => '297',
            ),
            array(
                'country_code' => 'AU',
                'phone_code' => '61',
            ),
            array(
                'country_code' => 'AZ',
                'phone_code' => '994',
            ),
            array(
                'country_code' => 'BS',
                'phone_code' => '1242',
            ),
            array(
                'country_code' => 'BH',
                'phone_code' => '973',
            ),
            array(
                'country_code' => 'BD',
                'phone_code' => '880',
            ),
            array(
                'country_code' => 'BB',
                'phone_code' => '1242',
            ),
            array(
                'country_code' => 'BE',
                'phone_code' => '32',
            ),
            array(
                'country_code' => 'BZ',
                'phone_code' => '501',
            ),
            array(
                'country_code' => 'BJ',
                'phone_code' => '229',
            ),
            array(
                'country_code' => 'BM',
                'phone_code' => '1441',
            ),
            array(
                'country_code' => 'BT',
                'phone_code' => '975',
            ),
            array(
                'country_code' => 'BO',
                'phone_code' => '591',
            ),
            array(
                'country_code' => 'BA',
                'phone_code' => '387',
            ),
            array(
                'country_code' => 'BW',
                'phone_code' => '267',
            ),
            array(
                'country_code' => 'BV',
                'phone_code' => '47',
            ),
            array(
                'country_code' => 'BR',
                'phone_code' => '55',
            ),
            array(
                'country_code' => 'GB',
                'phone_code' => '44',
            ),
            array(
                'country_code' => 'IO',
                'phone_code' => '246',
            ),
            array(
                'country_code' => 'VG',
                'phone_code' => '1284',
            ),
            array(
                'country_code' => 'BN',
                'phone_code' => '673',
            ),
            array(
                'country_code' => 'BG',
                'phone_code' => '359',
            ),
            array(
                'country_code' => 'BF',
                'phone_code' => '226',
            ),
            array(
                'country_code' => 'BI',
                'phone_code' => '257',
            ),
            array(
                'country_code' => 'KY',
                'phone_code' => '1345',
            ),
            array(
                'country_code' => 'CL',
                'phone_code' => '56',
            ),
            array(
                'country_code' => 'CK',
                'phone_code' => '682',
            ),
            array(
                'country_code' => 'CR',
                'phone_code' => '506',
            ),
            array(
                'country_code' => 'DJ',
                'phone_code' => '253',
            ),
            array(
                'country_code' => 'DM',
                'phone_code' => '1767',
            ),
            array(
                'country_code' => 'DO',
                'phone_code' => '1809',
            ),
            array(
                'country_code' => 'EC',
                'phone_code' => '593',
            ),
            array(
                'country_code' => 'EG',
                'phone_code' => '20',
            ),
            array(
                'country_code' => 'SV',
                'phone_code' => '503',
            ),
            array(
                'country_code' => 'ER',
                'phone_code' => '291',
            ),
            array(
                'country_code' => 'ES',
                'phone_code' => '34',
            ),
            array(
                'country_code' => 'ZA',
                'phone_code' => '27',
            ),
            array(
                'country_code' => 'GS',
                'phone_code' => '500',
            ),
            array(
                'country_code' => 'KR',
                'phone_code' => '82',
            ),
            array(
                'country_code' => 'ET',
                'phone_code' => '251',
            ),
            array(
                'country_code' => 'FK',
                'phone_code' => '500',
            ),
            array(
                'country_code' => 'FJ',
                'phone_code' => '679',
            ),
            array(
                'country_code' => 'PH',
                'phone_code' => '63',
            ),
            array(
                'country_code' => 'FO',
                'phone_code' => '298',
            ),
            array(
                'country_code' => 'GA',
                'phone_code' => '241',
            ),
            array(
                'country_code' => 'GM',
                'phone_code' => '220',
            ),
            array(
                'country_code' => 'GE',
                'phone_code' => '995',
            ),
            array(
                'country_code' => 'GH',
                'phone_code' => '233',
            ),
            array(
                'country_code' => 'GI',
                'phone_code' => '350',
            ),
            array(
                'country_code' => 'GD',
                'phone_code' => '1473',
            ),
            array(
                'country_code' => 'GL',
                'phone_code' => '299',
            ),
            array(
                'country_code' => 'GP',
                'phone_code' => '590',
            ),
            array(
                'country_code' => 'GU',
                'phone_code' => '1671',
            ),
            array(
                'country_code' => 'GT',
                'phone_code' => '502',
            ),
            array(
                'country_code' => 'GG',
                'phone_code' => '44',
            ),
            array(
                'country_code' => 'GN',
                'phone_code' => '224',
            ),
            array(
                'country_code' => 'GW',
                'phone_code' => '245',
            ),
            array(
                'country_code' => 'GY',
                'phone_code' => '592',
            ),
            array(
                'country_code' => 'HT',
                'phone_code' => '509',
            ),
            array(
                'country_code' => 'HM',
                'phone_code' => '61',
            ),
            array(
                'country_code' => 'HN',
                'phone_code' => '504',
            ),
            array(
                'country_code' => 'HK',
                'phone_code' => '852',
            ),
            array(
                'country_code' => 'SJ',
                'phone_code' => '47',
            ),
            array(
                'country_code' => 'ID',
                'phone_code' => '62',
            ),
            array(
                'country_code' => 'IN',
                'phone_code' => '91',
            ),
            array(
                'country_code' => 'IQ',
                'phone_code' => '964',
            ),
            array(
                'country_code' => 'IR',
                'phone_code' => '98',
            ),
            array(
                'country_code' => 'IE',
                'phone_code' => '353',
            ),
            array(
                'country_code' => 'IS',
                'phone_code' => '354',
            ),
            array(
                'country_code' => 'IL',
                'phone_code' => '972',
            ),
            array(
                'country_code' => 'IT',
                'phone_code' => '39',
            ),
            array(
                'country_code' => 'TL',
                'phone_code' => '670',
            ),
            array(
                'country_code' => 'AT',
                'phone_code' => '43',
            ),
            array(
                'country_code' => 'JM',
                'phone_code' => '1876',
            ),
            array(
                'country_code' => 'JP',
                'phone_code' => '81',
            ),
            array(
                'country_code' => 'YE',
                'phone_code' => '967',
            ),
            array(
                'country_code' => 'JE',
                'phone_code' => '44',
            ),
            array(
                'country_code' => 'JO',
                'phone_code' => '962',
            ),
            array(
                'country_code' => 'CX',
                'phone_code' => '61',
            ),
            array(
                'country_code' => 'KH',
                'phone_code' => '855',
            ),
            array(
                'country_code' => 'CM',
                'phone_code' => '237',
            ),
            array(
                'country_code' => 'CA',
                'phone_code' => '1',
            ),
            array(
                'country_code' => 'CV',
                'phone_code' => '238',
            ),
            array(
                'country_code' => 'KZ',
                'phone_code' => '7',
            ),
            array(
                'country_code' => 'KE',
                'phone_code' => '254',
            ),
            array(
                'country_code' => 'CF',
                'phone_code' => '236',
            ),
            array(
                'country_code' => 'CN',
                'phone_code' => '86',
            ),
            array(
                'country_code' => 'KG',
                'phone_code' => '996',
            ),
            array(
                'country_code' => 'KI',
                'phone_code' => '686',
            ),
            array(
                'country_code' => 'CO',
                'phone_code' => '57',
            ),
            array(
                'country_code' => 'KM',
                'phone_code' => '269',
            ),
            array(
                'country_code' => 'CG',
                'phone_code' => '242',
            ),
            array(
                'country_code' => 'CD',
                'phone_code' => '243',
            ),
            array(
                'country_code' => 'CC',
                'phone_code' => '61',
            ),
            array(
                'country_code' => 'GR',
                'phone_code' => '30',
            ),
            array(
                'country_code' => 'HR',
                'phone_code' => '385',
            ),
            array(
                'country_code' => 'CU',
                'phone_code' => '53',
            ),
            array(
                'country_code' => 'KW',
                'phone_code' => '965',
            ),
            array(
                'country_code' => 'CY',
                'phone_code' => '357',
            ),
            array(
                'country_code' => 'LA',
                'phone_code' => '856',
            ),
            array(
                'country_code' => 'LV',
                'phone_code' => '371',
            ),
            array(
                'country_code' => 'LS',
                'phone_code' => '266',
            ),
            array(
                'country_code' => 'LB',
                'phone_code' => '961',
            ),
            array(
                'country_code' => 'LR',
                'phone_code' => '231',
            ),
            array(
                'country_code' => 'LY',
                'phone_code' => '218',
            ),
            array(
                'country_code' => 'LI',
                'phone_code' => '423',
            ),
            array(
                'country_code' => 'LT',
                'phone_code' => '370',
            ),
            array(
                'country_code' => 'LU',
                'phone_code' => '352',
            ),
            array(
                'country_code' => 'EH',
                'phone_code' => '21228',
            ),
            array(
                'country_code' => 'MO',
                'phone_code' => '853',
            ),
            array(
                'country_code' => 'MG',
                'phone_code' => '261',
            ),
            array(
                'country_code' => 'MK',
                'phone_code' => '389',
            ),
            array(
                'country_code' => 'MW',
                'phone_code' => '265',
            ),
            array(
                'country_code' => 'MV',
                'phone_code' => '960',
            ),
            array(
                'country_code' => 'MY',
                'phone_code' => '60',
            ),
            array(
                'country_code' => 'ML',
                'phone_code' => '223',
            ),
            array(
                'country_code' => 'MT',
                'phone_code' => '356',
            ),
            array(
                'country_code' => 'IM',
                'phone_code' => '44',
            ),
            array(
                'country_code' => 'MA',
                'phone_code' => '212',
            ),
            array(
                'country_code' => 'MH',
                'phone_code' => '692',
            ),
            array(
                'country_code' => 'MQ',
                'phone_code' => '596',
            ),
            array(
                'country_code' => 'MR',
                'phone_code' => '222',
            ),
            array(
                'country_code' => 'MU',
                'phone_code' => '230',
            ),
            array(
                'country_code' => 'YT',
                'phone_code' => '262',
            ),
            array(
                'country_code' => 'MX',
                'phone_code' => '52',
            ),
            array(
                'country_code' => 'FM',
                'phone_code' => '691',
            ),
            array(
                'country_code' => 'MD',
                'phone_code' => '373',
            ),
            array(
                'country_code' => 'MC',
                'phone_code' => '377',
            ),
            array(
                'country_code' => 'MN',
                'phone_code' => '976',
            ),
            array(
                'country_code' => 'ME',
                'phone_code' => '382',
            ),
            array(
                'country_code' => 'MS',
                'phone_code' => '1664',
            ),
            array(
                'country_code' => 'MZ',
                'phone_code' => '258',
            ),
            array(
                'country_code' => 'MM',
                'phone_code' => '95',
            ),
            array(
                'country_code' => 'NA',
                'phone_code' => '264',
            ),
            array(
                'country_code' => 'NR',
                'phone_code' => '674',
            ),
            array(
                'country_code' => 'NP',
                'phone_code' => '977',
            ),
            array(
                'country_code' => 'NI',
                'phone_code' => '505',
            ),
            array(
                'country_code' => 'NE',
                'phone_code' => '227',
            ),
            array(
                'country_code' => 'NG',
                'phone_code' => '234',
            ),
            array(
                'country_code' => 'NU',
                'phone_code' => '683',
            ),
            array(
                'country_code' => 'NF',
                'phone_code' => '672',
            ),
            array(
                'country_code' => 'NO',
                'phone_code' => '47',
            ),
            array(
                'country_code' => 'CI',
                'phone_code' => '255',
            ),
            array(
                'country_code' => 'OM',
                'phone_code' => '968',
            ),
            array(
                'country_code' => 'PK',
                'phone_code' => '92',
            ),
            array(
                'country_code' => 'PW',
                'phone_code' => '680',
            ),
            array(
                'country_code' => 'PS',
                'phone_code' => '970',
            ),
            array(
                'country_code' => 'PA',
                'phone_code' => '507',
            ),
            array(
                'country_code' => 'PG',
                'phone_code' => '675',
            ),
            array(
                'country_code' => 'PY',
                'phone_code' => '595',
            ),
            array(
                'country_code' => 'PE',
                'phone_code' => '51',
            ),
            array(
                'country_code' => 'PN',
                'phone_code' => '870',
            ),
            array(
                'country_code' => 'KP',
                'phone_code' => '850',
            ),
            array(
                'country_code' => 'MP',
                'phone_code' => '1670',
            ),
            array(
                'country_code' => 'PT',
                'phone_code' => '351',
            ),
            array(
                'country_code' => 'PR',
                'phone_code' => '1',
            ),
            array(
                'country_code' => 'PL',
                'phone_code' => '48',
            ),
            array(
                'country_code' => 'GQ',
                'phone_code' => '240',
            ),
            array(
                'country_code' => 'QA',
                'phone_code' => '974',
            ),
            array(
                'country_code' => 'FR',
                'phone_code' => '33',
            ),
            array(
                'country_code' => 'GF',
                'phone_code' => '594',
            ),
            array(
                'country_code' => 'PF',
                'phone_code' => '689',
            ),
            array(
                'country_code' => 'TF',
                'phone_code' => '33',
            ),
            array(
                'country_code' => 'RO',
                'phone_code' => '40',
            ),
            array(
                'country_code' => 'RW',
                'phone_code' => '250',
            ),
            array(
                'country_code' => 'SE',
                'phone_code' => '46',
            ),
            array(
                'country_code' => 'RE',
                'phone_code' => '262',
            ),
            array(
                'country_code' => 'SH',
                'phone_code' => '290',
            ),
            array(
                'country_code' => 'KN',
                'phone_code' => '1869',
            ),
            array(
                'country_code' => 'LC',
                'phone_code' => '1758',
            ),
            array(
                'country_code' => 'VC',
                'phone_code' => '1784',
            ),
            array(
                'country_code' => 'BL',
                'phone_code' => '590',
            ),
            array(
                'country_code' => 'MF',
                'phone_code' => '1599',
            ),
            array(
                'country_code' => 'PM',
                'phone_code' => '508',
            ),
            array(
                'country_code' => 'DE',
                'phone_code' => '49',
            ),
            array(
                'country_code' => 'SB',
                'phone_code' => '677',
            ),
            array(
                'country_code' => 'ZM',
                'phone_code' => '260',
            ),
            array(
                'country_code' => 'WS',
                'phone_code' => '685',
            ),
            array(
                'country_code' => 'SM',
                'phone_code' => '378',
            ),
            array(
                'country_code' => 'SA',
                'phone_code' => '966',
            ),
            array(
                'country_code' => 'SN',
                'phone_code' => '221',
            ),
            array(
                'country_code' => 'RS',
                'phone_code' => '381',
            ),
            array(
                'country_code' => 'SC',
                'phone_code' => '248',
            ),
            array(
                'country_code' => 'SL',
                'phone_code' => '232',
            ),
            array(
                'country_code' => 'SG',
                'phone_code' => '65',
            ),
            array(
                'country_code' => 'SK',
                'phone_code' => '421',
            ),
            array(
                'country_code' => 'SI',
                'phone_code' => '386',
            ),
            array(
                'country_code' => 'SO',
                'phone_code' => '252',
            ),
            array(
                'country_code' => 'LK',
                'phone_code' => '94',
            ),
            array(
                'country_code' => 'SD',
                'phone_code' => '249',
            ),
            array(
                'country_code' => 'FI',
                'phone_code' => '358',
            ),
            array(
                'country_code' => 'SR',
                'phone_code' => '594',
            ),
            array(
                'country_code' => 'CH',
                'phone_code' => '41',
            ),
            array(
                'country_code' => 'SZ',
                'phone_code' => '268',
            ),
            array(
                'country_code' => 'SY',
                'phone_code' => '963',
            ),
            array(
                'country_code' => 'ST',
                'phone_code' => '239',
            ),
            array(
                'country_code' => 'TJ',
                'phone_code' => '992',
            ),
            array(
                'country_code' => 'TW',
                'phone_code' => '886',
            ),
            array(
                'country_code' => 'TZ',
                'phone_code' => '255',
            ),
            array(
                'country_code' => 'DK',
                'phone_code' => '45',
            ),
            array(
                'country_code' => 'TH',
                'phone_code' => '66',
            ),
            array(
                'country_code' => 'TG',
                'phone_code' => '228',
            ),
            array(
                'country_code' => 'TK',
                'phone_code' => '690',
            ),
            array(
                'country_code' => 'TO',
                'phone_code' => '676',
            ),
            array(
                'country_code' => 'TT',
                'phone_code' => '1868',
            ),
            array(
                'country_code' => 'TN',
                'phone_code' => '216',
            ),
            array(
                'country_code' => 'TR',
                'phone_code' => '90',
            ),
            array(
                'country_code' => 'TM',
                'phone_code' => '993',
            ),
            array(
                'country_code' => 'TC',
                'phone_code' => '1649',
            ),
            array(
                'country_code' => 'TV',
                'phone_code' => '688',
            ),
            array(
                'country_code' => 'TD',
                'phone_code' => '235',
            ),
            array(
                'country_code' => 'CZ',
                'phone_code' => '420',
            ),
            array(
                'country_code' => 'UG',
                'phone_code' => '256',
            ),
            array(
                'country_code' => 'UA',
                'phone_code' => '380',
            ),
            array(
                'country_code' => 'HU',
                'phone_code' => '36',
            ),
            array(
                'country_code' => 'UY',
                'phone_code' => '598',
            ),
            array(
                'country_code' => 'NC',
                'phone_code' => '687',
            ),
            array(
                'country_code' => 'NZ',
                'phone_code' => '64',
            ),
            array(
                'country_code' => 'UZ',
                'phone_code' => '998',
            ),
            array(
                'country_code' => 'BY',
                'phone_code' => '375',
            ),
            array(
                'country_code' => 'VU',
                'phone_code' => '678',
            ),
            array(
                'country_code' => 'VA',
                'phone_code' => '39',
            ),
            array(
                'country_code' => 'VE',
                'phone_code' => '58',
            ),
            array(
                'country_code' => 'RU',
                'phone_code' => '7',
            ),
            array(
                'country_code' => 'VN',
                'phone_code' => '84',
            ),
            array(
                'country_code' => 'EE',
                'phone_code' => '372',
            ),
            array(
                'country_code' => 'WF',
                'phone_code' => '681',
            ),
            array(
                'country_code' => 'US',
                'phone_code' => '1',
            ),
            array(
                'country_code' => 'VI',
                'phone_code' => '1340',
            ),
            array(
                'country_code' => 'UM',
                'phone_code' => '1',
            ),
            array(
                'country_code' => 'ZW',
                'phone_code' => '263',
            )
        );


        usort($countryCodes, array($this, 'sortShuffle'));

        $phoneNumber = str_replace("+", "", $address->getTelephone());

        foreach( $countryCodes as $key => $value )
        {
            if ( substr( $phoneNumber, 0, strlen( $value['phone_code'] ) ) == $value['phone_code'] )
            {
                return $value['country_code'];
            }
        }

        foreach( $countryCodes as $key => $value )
        {
            if ( substr( $phoneNumber, 0, strlen( $address->getCountryId() ) ) == $address->getCountryId() )
            {
                return $value['phone_code'];
            }
        }

        return $address->getCountryId();
    }

    public function preparePhone($phoneNr, $region) {
        $phoneNr = str_replace("+", "", $phoneNr);
        return substr( $phoneNr, strlen( $region ));
    }


    public function handleError($error)
    {
        $type = array("393729", "393730", "393731", "393732", "393733", "393734", "393735", "393736", "393737", "393744", "393745", "393746", "393747", "393749", "393750", "393751", "393752", "393760", "393764", "393769", "393776", "393986", "393987", "393988");

        if (in_array($error['responseErrorCode'], $type)) {
            switch ($error['responseErrorCode']) {
                case "393730":
                    $message = $this->__("Sorry, cannot process transaction. Your card's CVV number is incorrect.");
                    break;
                case "393731":
                    $message = $this->__("Sorry, cannot complete transaction. You typed an invalid card number.");
                    break;
                case "393732":
                    $message = $this->__("It seems your card has expired. Please verify your details or use other payment source.");
                    break;
                default:
                    $message = $this->__("The transaction cannot be completed at this time - your card was declined. Please try a different card.");
            }
            return $message;
        } else {
            $adminEmail = Mage::getStoreConfig('trans_email/ident_general/email');
            $adminName = Mage::getStoreConfig('trans_email/ident_general/name');

            $mail = Mage::getModel('core/email');
            $mail->setToName($adminName);
            $mail->setToEmail($adminEmail);
            $mail->setFromEmail($adminEmail);
            $mail->setFromName($adminName);
            $mail->setBody(nl2br($error['errorMessage']));
            $mail->setSubject("Zooz payment : error #" . $error['responseErrorCode']);
            $mail->setType('html');

            try {
                $mail->send();
            }
            catch (Exception $e) {
                Mage::log($e->getMessage());
            }
            return $error['errorMessage'];
        }
    }
    public function getPaymentGatewayUrl()
    {
        return Mage::getUrl('zoozpayments/payment/gateway', array('_secure' => false));
    }

    /**
     * Format price with currency sign
     * @param  Mage_Payment_Model_Info $payment
     * @param float $amount
     * @return string
     */
    protected function _formatPrice($payment, $amount)
    {
        return $payment->getOrder()->getBaseCurrency()->formatTxt($amount);
    }

    /**
     * Translates payment method subtype returned by getPaymentMethods to credit card type code used in magento
     *
     * @param string $subtype
     * @return string
     */
    public function translateCardSubtypeToTypeCode($subtype)
    {
        $subtype = strtolower($subtype);
        return isset($this->_subtypeToTypeCodeMap[$subtype]) ? $this->_subtypeToTypeCodeMap[$subtype] : '';

    }

    function sortShuffle($a, $b)
    {
        return ($a['phone_code'] > $b['phone_code']) ? -1 : 1;
    }

    public function canSaveCc() {
        return Mage::getSingleton('payments/config')->isPciIframeEnabled();
    }

    public function getCustomerIframeUrl() {
        return self::CUSTOMER_IFRAME;
    }
}
