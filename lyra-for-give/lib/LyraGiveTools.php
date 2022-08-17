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

use Lyranetwork\Lyra\Sdk\Form\Api as LyraApi;
use Lyranetwork\Lyra\Sdk\Form\Response as LyraResponse;

class LyraGiveTools
{
    private static $GATEWAY_CODE = 'Lyra';
    private static $GATEWAY_NAME = 'Lyra Collect';
    private static $BACKOFFICE_NAME = 'Lyra Expert';
    private static $GATEWAY_URL = 'https://secure.lyra.com/vads-payment/';
    private static $SITE_ID = '12345678';
    private static $KEY_TEST = '1111111111111111';
    private static $KEY_PROD = '2222222222222222';
    private static $CTX_MODE = 'TEST';
    private static $SIGN_ALGO = 'SHA-256';
    private static $LANGUAGE = 'en';

    private static $CMS_IDENTIFIER = 'GiveWP_2.x';
    private static $SUPPORT_EMAIL = 'support-ecommerce@lyra-collect.com';
    private static $PLUGIN_VERSION = '1.0.1';
    private static $GATEWAY_VERSION = 'V2';

    public static $plugin_features = array(
        'qualif' => false,
        'prodfaq' => false,
        'shatwo' => true
    );

    public static function getDefault($name)
    {
        if (! is_string($name)) {
            return '';
        }

        if (! isset(self::$$name)) {
            return '';
        }

        return self::$$name;
    }

    /**
     * Provide dropdown lists for configuration.
     */
    public static function getDropdownList($name)
    {
        if ($name === 'lyra_cmodes') {
            return array(
                'TEST'       => __('TEST', 'lyra-give'),
                'PRODUCTION' => __('PRODUCTION', 'lyra-give')
            );
        } elseif ($name === 'lyra_vmodes') {
            return array(
                ''  => sprintf(__('%s Back Office configuration', 'lyra-give'), 'Lyra Expert'),
                '0' => __('Automatic', 'lyra-give'),
                '1' => __('Manual', 'lyra-give')
            );
        } elseif ($name === 'lyra_rmodes') {
            return array(
                'GET'  => 'GET',
                'POST' => 'POST'
            );
        } elseif ($name === 'lyra_redirects') {
            return array(
                '0' => __('Disable', 'lyra-give'),
                '1' => __('Enable', 'lyra-give')
            );
        } elseif ($name === 'lyra_languages') {
            $result = array();
            foreach (LyraApi::getSupportedLanguages() as $key => $language) {
                $result[$key] = __($language, 'lyra-give');
            }

            return $result;
        } elseif ($name === 'lyra_cards') {
            $result = array();
            foreach (LyraApi::getSupportedCardTypes() as $key => $card) {
                $result[$key] = $card;
            }

            return $result;
        } elseif ($name === 'lyra_salgos') {
            return array(
                'SHA-1'   => 'SHA-1',
                'SHA-256' => 'HMAC-SHA-256'
            );
        }
    }

    /**
     * Get documentation links.
     */
    public static function getDocsLinks()
    {
        $docs = '';
        $minor = substr(self::$PLUGIN_VERSION, 0, strrpos(self::$PLUGIN_VERSION, '.'));
        $doc_pattern = self::$GATEWAY_CODE . '_' . self::getDefault('CMS_IDENTIFIER') . '_v' . $minor . '*.pdf';
        $filenames = glob(LYRA_GIVE_DIR . 'installation_doc/' . $doc_pattern);

        if (! empty($filenames)) {
            $languages = array(
                'fr' => 'Français',
                'en' => 'English',
                'es' => 'Español',
                'de' => 'Deutsch'
                // Complete when other languages are managed.
            );
            foreach ($filenames as $filename) {
                $base_filename = basename($filename, '.pdf');
                $lang = substr($base_filename, -2); // Extract language code.

                $docs .= '<b><a href="' . LYRA_GIVE_URL . 'installation_doc/' . $base_filename . '.pdf" style="color: red; text-decoration: none; margin-left: 10px;">' . $languages[$lang] . '</a></b>';
            }
        }

        return $docs;
    }

    public static function getNewDonationStatus(LyraResponse $lyraResponse)
    {
        $trans_status = $lyraResponse->get('trans_status');
        $status = '';

        $successStatuses = array_merge(
            LyraApi::getSuccessStatuses(),
            LyraApi::getPendingStatuses()
        );

        switch (true) {
            case in_array($trans_status, $successStatuses):
                $status = 'publish';
                break;

            case in_array($trans_status, array('CANCELLED', 'NOT_CREATED')):
            case in_array($trans_status, LyraApi::getCancelledStatuses()):
                $status = 'cancelled';
                break;

            case 'EXPIRED':
            case 'REFUSED':
                $status = 'failed';
                break;

            default:
                $status = 'failed';
                break;
        }

        return $status;
    }

    public static function getContrib()
    {
        // Effective used version.
        include ABSPATH . WPINC . '/version.php';

        $version = $wp_version . '_' . GIVE_VERSION;

        return self::getDefault('CMS_IDENTIFIER') . '_' . self::getDefault('PLUGIN_VERSION') . '/' . $version . '/' . LyraApi::shortPhpVersion();
    }
}
