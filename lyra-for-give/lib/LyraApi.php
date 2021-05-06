<?php
/**
 * Copyright © Lyra Network.
 * This file is part of Lyra Collect plugin for GiveWP. See COPYING.md for license details.
 *
 * @author    Lyra Network <https://www.lyra.com>
 * @copyright Lyra Network
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL v3)
 */

// No direct access is allowed.
if (! defined('ABSPATH')) {
    exit;
}

require_once 'LyraCurrency.php';
require_once 'LyraField.php';

if (! class_exists('LyraApi', false)) {

    /**
     * Utility class for managing parameters checking, inetrnationalization, signature building and more.
     */
    class LyraApi
    {

        const ALGO_SHA1 = 'SHA-1';
        const ALGO_SHA256 = 'SHA-256';

        public static $SUPPORTED_ALGOS = array(self::ALGO_SHA1, self::ALGO_SHA256);

        /**
         * The list of encodings supported by the API.
         *
         * @var array[string]
         */
        public static $SUPPORTED_ENCODINGS = array(
            'UTF-8',
            'ASCII',
            'Windows-1252',
            'ISO-8859-15',
            'ISO-8859-1',
            'ISO-8859-6',
            'CP1256'
        );

        /**
         * Generate a trans_id.
         * To be independent from shared/persistent counters, we use the number of 1/10 seconds since midnight
         * which has the appropriatee format (000000-899999) and has great chances to be unique.
         *
         * @param int $timestamp
         * @return string the generated trans_id
         */
        public static function generateTransId($timestamp = null)
        {
            if (! $timestamp) {
                $timestamp = time();
            }

            $parts = explode(' ', microtime());
            $id = ($timestamp + $parts[0] - strtotime('today 00:00')) * 10;
            $id = sprintf('%06d', $id);

            return $id;
        }

        /**
         * Returns an array of languages accepted by the payment gateway.
         *
         * @return array[string][string]
         */
        public static function getSupportedLanguages()
        {
            return array(
                'de' => 'German',
                'en' => 'English',
                'zh' => 'Chinese',
                'es' => 'Spanish',
                'fr' => 'French',
                'it' => 'Italian',
                'ja' => 'Japanese',
                'nl' => 'Dutch',
                'pl' => 'Polish',
                'pt' => 'Portuguese',
                'ru' => 'Russian',
                'sv' => 'Swedish',
                'tr' => 'Turkish'
            );
        }

        /**
         * Returns true if the entered language (ISO code) is supported.
         *
         * @param string $lang
         * @return boolean
         */
        public static function isSupportedLanguage($lang)
        {
            foreach (array_keys(self::getSupportedLanguages()) as $code) {
                if ($code == strtolower($lang)) {
                    return true;
                }
            }

            return false;
        }

        /**
         * Return the list of currencies recognized by the payment gateway.
         *
         * @return array[int][LyraCurrency]
         */
        public static function getSupportedCurrencies()
        {
            $currencies = array(
                array('EUR', '978', 2), array('GBP', '826', 2), array('CAD', '124', 2), array('JPY', '392', 0),
                array('DKK', '208', 2), array('PLN', '985', 2), array('USD', '840', 2), array('CHF', '756', 2),
                array('NOK', '578', 2)
            );

            $lyra_currencies = array();

            foreach ($currencies as $currency) {
                $lyra_currencies[] = new LyraCurrency($currency[0], $currency[1], $currency[2]);
            }

            return $lyra_currencies;
        }

        /**
         * Return a currency from its 3-letters ISO code.
         *
         * @param string $alpha3
         * @return LyraCurrency
         */
        public static function findCurrencyByAlphaCode($alpha3)
        {
            $list = self::getSupportedCurrencies();
            foreach ($list as $currency) {
                /**
                 * @var LyraCurrency $currency
                 */
                if ($currency->getAlpha3() == $alpha3) {
                    return $currency;
                }
            }

            return null;
        }

        /**
         * Returns a currency form its numeric ISO code.
         *
         * @param int $numeric
         * @return LyraCurrency
         */
        public static function findCurrencyByNumCode($numeric)
        {
            $list = self::getSupportedCurrencies();
            foreach ($list as $currency) {
                /**
                 * @var LyraCurrency $currency
                 */
                if ($currency->getNum() == $numeric) {
                    return $currency;
                }
            }

            return null;
        }

        /**
         * Return a currency from its 3-letters or numeric ISO code.
         *
         * @param string $code
         * @return LyraCurrency
         */
        public static function findCurrency($code)
        {
            $list = self::getSupportedCurrencies();
            foreach ($list as $currency) {
                /**
                 * @var LyraCurrency $currency
                 */
                if ($currency->getNum() == $code || $currency->getAlpha3() == $code) {
                    return $currency;
                }
            }

            return null;
        }

        /**
         * Returns currency numeric ISO code from its 3-letters code.
         *
         * @param string $alpha3
         * @return int
         */
        public static function getCurrencyNumCode($alpha3)
        {
            $currency = self::findCurrencyByAlphaCode($alpha3);
            return ($currency instanceof LyraCurrency) ? $currency->getNum() : null;
        }

        /**
         * Returns an array of card types accepted by the payment gateway.
         *
         * @return array[string][string]
         */
        public static function getSupportedCardTypes()
        {
            return array(
                'CB' => 'CB', 'E-CARTEBLEUE' => 'e-Carte Bleue', 'MAESTRO' => 'Maestro', 'MASTERCARD' => 'Mastercard',
                'VISA' => 'Visa', 'VISA_ELECTRON' => 'Visa Electron', 'VPAY' => 'V PAY', 'AMEX' => 'American Express', 'ALIPAY' => 'Alipay',
                'APETIZ' => 'Apetiz', 'AURORE-MULTI' => 'Cpay Aurore', 'BANCONTACT' => 'Bancontact Mistercash', 'CDGP' => 'Carte Privilège',
                'CHQ_DEJ' => 'Chèque Déjeuner', 'CONECS' => 'Conecs', 'DINERS' => 'Diners', 'DISCOVER' => 'Discover',
                'EDENRED' => 'Ticket Restaurant', 'EDENRED_EC' => 'Ticket EcoCheque', 'EDENRED_SC' => 'Ticket Sport & Culture',
                'EDENRED_TC' => 'Ticket Compliments', 'EDENRED_TR' => 'Ticket Restaurant', 'E_CV' => 'e-Chèque-Vacances',
                'FULLCB3X' => 'Paiement en 3 fois CB', 'FULLCB4X' => 'Paiement en 4 fois CB', 'GIROPAY' => 'Giropay',
                'GOOGLEPAY' => 'Google Pay', 'IDEAL' => 'iDEAL', 'ILLICADO' => 'Carte Illicado', 'ILLICADO_SB' => 'Carte Illicado (sandbox)',
                'JCB' => 'JCB', 'KLARNA' => 'Klarna', 'MULTIBANCO' => 'Multibanco', 'MYBANK' => 'MyBank',
                'ONEY_3X_4X' => 'Paiement en 3 ou 4 fois Oney', 'ONEY_ENSEIGNE' => 'Cartes enseignes Oney', 'PAYLIB' => 'Paylib',
                'PAYPAL' => 'PayPal', 'PAYPAL_SB' => 'PayPal Sandbox', 'POSTFINANCE' => 'PostFinance Card',
                'POSTFINANCE_EFIN' => 'PostFinance E-Finance', 'PRZELEWY24' => 'Przelewy24', 'SDD' => 'SEPA direct debit',
                'SODEXO' => 'Pass Restaurant', 'SOFORT_BANKING' => 'Sofort', 'UNION_PAY' => 'UnionPay', 'WECHAT' => 'WeChat Pay'
            );
        }

        /**
         * Return the statuses list of finalized successful payments (authorized or captured).
         * @return array
         */
        public static function getSuccessStatuses()
        {
            return array(
                'AUTHORISED',
                'AUTHORISED_TO_VALIDATE', // TODO is this a pending status?
                'CAPTURED',
                'ACCEPTED'
            );
        }

        /**
         * Return the statuses list of payments that are waiting confirmation (successful but
         * the amount has not been transfered and is not yet guaranteed).
         * @return array
         */
        public static function getPendingStatuses()
        {
            return array(
                'INITIAL',
                'WAITING_AUTHORISATION',
                'WAITING_AUTHORISATION_TO_VALIDATE',
                'UNDER_VERIFICATION',
                'PRE_AUTHORISED',
                'WAITING_FOR_PAYMENT'
            );
        }

        /**
         * Return the statuses list of payments interrupted by the buyer.
         * @return array
         */
        public static function getCancelledStatuses()
        {
            return array('ABANDONED');
        }

        /**
         * Return the statuses list of payments waiting manual validation from the gateway Back Office.
         * @return array
         */
        public static function getToValidateStatuses()
        {
            return array('WAITING_AUTHORISATION_TO_VALIDATE', 'AUTHORISED_TO_VALIDATE');
        }

        /**
         * Compute the signature. Parameters must be in UTF-8.
         *
         * @param array[string][string] $parameters payment gateway request/response parameters
         * @param string $key shop certificate
         * @param string $algo signature algorithm
         * @param boolean $hashed set to false to get the unhashed signature
         * @return string
         */
        public static function sign($parameters, $key, $algo, $hashed = true)
        {
            ksort($parameters);

            $sign = '';
            foreach ($parameters as $name => $value) {
                if (substr($name, 0, 5) == 'vads_') {
                    $sign .= $value . '+';
                }
            }

            $sign .= $key;

            if (! $hashed) {
                return $sign;
            }

            switch ($algo) {
                case self::ALGO_SHA1:
                    return sha1($sign);
                case self::ALGO_SHA256:
                    return base64_encode(hash_hmac('sha256', $sign, $key, true));
                default:
                    throw new \InvalidArgumentException("Unsupported algorithm passed : {$algo}.");
            }
        }
    }
}
