<?php

/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2019
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Cloudrexx" is a registered trademark of Cloudrexx AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

/**
 * Interface to Saferpay JSON API
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.3
 */

/**
 * Exception while using interface to Saferpay JSON API
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.3
 */
class SaferpayJsonException extends \Exception {}

/**
 * Interface to Saferpay JSON API
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.3
 */
class SaferpayJson
{

    /**
     * Error messages
     * @var     array
     */
    protected static $arrError = array();

    /**
     * Warning messages
     * @var     array
     */
    protected static $arrWarning = array();

    /**
     * Contains the ID of the current transaction. Empty unless payment is confirmed.
     * @var string
     */
    protected static $transactionId = '';

    /**
     * Base API URL for live system
     * @var string
     */
    protected static $liveUrl = '';

    /**
     * Base API URL for test system
     * @var string
     */
    protected static $testUrl = 'https://test.saferpay.com/api/Payment/v1/';

    /**
     * API endpoint offsets for the different payment steps
     * @var array <paymentStep>=><EnpointOffset>
     */
    protected static $urls = array(
        'payInit' => 'PaymentPage/Initialize',
        'payConfirm' => 'PaymentPage/Assert',
        'payComplete' => 'Transaction/Capture',
    );

    /**
     * Perform a request to the Saferpay JSON API
     *
     * @param string $step One of the strings defined as a key in $urls
     * @param array $data Associative array with the request payload
     * @return stdClass|boolean Decoded JSON on success, false otherwise
     */
    protected static function doRequest($step, $data) {
        $test = (bool) \Cx\Core\Setting\Controller\Setting::getValue(
            'saferpay_json_use_test_account',
            'Shop'
        );
        $baseUrl = $test ? static::$testUrl : static::$liveUrl;
        if (!isset(static::$urls[$step])) {
            throw new \SaferpayJsonException('Invalid step: "' . $step . '"');
        }
        $customerId = \Cx\Core\Setting\Controller\Setting::getValue(
            'saferpay_json_id',
            'Shop'
        );
        $username = \Cx\Core\Setting\Controller\Setting::getValue(
            'saferpay_json_user',
            'Shop'
        );
        $password = \Cx\Core\Setting\Controller\Setting::getValue(
            'saferpay_json_pass',
            'Shop'
        );
        $data['RequestHeader'] = array(
            'SpecVersion' => '1.10',
            'CustomerId' => $customerId,
            'RequestId' => mt_rand(), // TODO
            'RetryIndicator' => 0,
        );
        $url = $baseUrl . static::$urls[$step];
        $jd = new \Cx\Core\Json\JsonData();
        return $jd->getJson(
            $url,
            $data,
            true,
            '', // No additional certificate needed
            array(
                'httpAuthMethod' => 'basic',
                'httpAuthUsername' => $username,
                'httpAuthPassword' => $password,
            ),
            array(),
            true
        );
    }

    /**
     * Returns the URI for initializing the payment with Saferpay
     * @param   array   $arrOrder   The attributes array
     * @return  string              The URI for the payment initialisation
     *                              on success, the empty string otherwise
     */
    public static function payInit($arrOrder) {
        $terminalId = \Cx\Core\Setting\Controller\Setting::getValue(
            'saferpay_json_terminal_id',
            'Shop'
        );
        $data = array(
            'TerminalId' => $terminalId,
            'Payment' => array(
                'Amount' => array(
                    'Value' => $arrOrder['AMOUNT'],
                    'CurrencyCode' => $arrOrder['CURRENCY'],
                ),
                'OrderId' => $arrOrder['ORDERID'],
                'Description' => 'Lorem ipsum', // TODO
            ),
            'ReturnUrls' => array(
                'Success' => $arrOrder['SUCCESSLINK'],
                'Fail' => $arrOrder['FAILLINK'],
            ),
        );
        $result = (array) static::doRequest('payInit', $data);
        if (!isset($result['RedirectUrl'])) {
            static::$arrError[] = 'Payment initialization request failed!';
            return '';
        }
        $_SESSION['shop']['saferpay_json_token'] = $result['Token'];
        return $result['RedirectUrl'];
    }

    /**
     * Confirms the payment transaction
     * @return  boolean     The transaction ID on success, NULL otherwise
     */
    public static function payConfirm() {
        $result = (array) static::doRequest(
            'payConfirm',
            array(
                'Token' => $_SESSION['shop']['saferpay_json_token'],
            )
        );
        if (
            !isset($result['Transaction'])
        ) {
            return false;
        }
        $result['Transaction'] = (array) $result['Transaction'];
        if (
            empty($result['Transaction']['Status']) ||
            empty($result['Transaction']['Id']) ||
            $result['Transaction']['Status'] != 'AUTHORIZED'
        ) {
            return false;
        }
        static::$transactionId = $result['Transaction']['Id'];
        return true;
    }

    /**
     * Completes the payment transaction
     * @param   array       $arrOrder   The attributes array
     * @return  boolean                 True on success, false otherwise
     */
    public static function payComplete() {
        $result = (array) static::doRequest(
            'payComplete',
            array(
                'TransactionReference' => array(
                    'TransactionId' => static::$transactionId,
                ),
            )
        );
        return !empty($result['Status']) && $result['Status'] == 'CAPTURED';
    }

    /**
     * Returns the order ID of the current transaction
     * @return  integer         The Order ID
     */
    public static function getOrderId() {
        // Advise Shop to take Order ID from Session
        return false;
    }

    /**
     * Returns accumulated warnings as a HTML string
     * @return  string          The warnings, if any, or the empty string
     */
    public static function getWarnings()
    {
        return join('<br />', static::$arrWarning);
    }


    /**
     * Returns accumulated warnings as a HTML string
     * @return  string          The warnings, if any, or the empty string
     */
    public static function getErrors()
    {
        return join('<br />', static::$arrError);
    }
}

