<?php
/**
 * Copyright © Lyra Network.
 * This file is part of Lyra Collect plugin for GiveWP. See COPYING.md for license details.
 *
 * @author    Lyra Network <https://www.lyra.com>
 * @copyright Lyra Network
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL v3)
 */

/**
 * Plugin Name: Lyra Collect for GiveWP
 * Description: Accept donations with secured payment gateway.
 * Author: Lyra Network
 * Version: 1.0.1
 * Author URI: https://www.lyra.com
 * License: GPLv3 or later
 * Requires at least: 4.8
 * Tested up to: 6.0
 * GiveWP requires at least: 2.0
 * GiveWP tested up to: 2.21
 * Text Domain: lyra-give
 * Domain Path: /languages
 */

// No direct access is allowed.
if (! defined('ABSPATH')) {
    exit;
}

if (! defined('LYRA_GIVE_VERSION')) {
    define('LYRA_GIVE_VERSION', '1.0.0');
}

if (! defined('LYRA_GIVE_FILE')) {
    define('LYRA_GIVE_FILE', __FILE__);
}

if (! defined('LYRA_GIVE_BASENAME')) {
    define('LYRA_GIVE_BASENAME', plugin_basename(LYRA_GIVE_FILE));
}

if (! defined('LYRA_GIVE_DIR')) {
    define('LYRA_GIVE_DIR', plugin_dir_path(LYRA_GIVE_FILE));
}

if (! defined( 'LYRA_GIVE_URL')) {
    define('LYRA_GIVE_URL', plugin_dir_url(LYRA_GIVE_FILE));
}

include_once LYRA_GIVE_DIR . 'lib/lyra-give-sdk-autoload.php';

include(LYRA_GIVE_DIR . '/includes/admin/lyra-give-activation.php');
include(LYRA_GIVE_DIR . '/includes/admin/lyra-give-admin.php');
include(LYRA_GIVE_DIR . '/includes/lyra-give-payment-gateway.php');

/**
 * Load textdomain for translations.
 */
function lyra_give_textdomain()
{
    load_plugin_textdomain('lyra-give', false, basename(dirname(LYRA_GIVE_FILE)) . '/languages');
}

add_action('init', 'lyra_give_textdomain');

/**
 * Register Payment Gateway.
 *
 * @param  array $gateways
 *
 * @return array
 */
function lyra_give_register_gateway($gateways)
{
    $gateways['lyra'] = array(
        'admin_label'    => 'Lyra Collect',
        'checkout_label' => 'Lyra Collect'
    );

    return $gateways;
}

add_filter('give_payment_gateways', 'lyra_give_register_gateway');

function lyra_give_redirect_notice($form_id)
{
    printf(
        '
        <fieldset class="no-fields">
            <p style="text-align: center;"><b>%1$s</b></p>
        </fieldset>
        ',
        __('You will enter payment data after donation confirmation.', 'lyra-give')
    );

    return true;
}

add_action('give_lyra_cc_form', 'lyra_give_redirect_notice');

function lyra_give_receipt()
{
    require_once GIVE_PLUGIN_DIR . '/includes/class-notices.php';

    if (Give_Cache::get('give_cache_lyra_going_into_prod')) {
        Give_Notices::print_frontend_notice(sprintf(__('<u>GOING INTO PRODUCTION</u><br >You want to know how to put your shop into production mode, read chapters « Proceeding to test phase » and « Shifting the shop to production mode » in the documentation of the module.', 'lyra-give')), true, 'warning');
        Give_Cache::delete('give_cache_payzen_going_into_prod');
    }

    if (Give_Cache::get('give_cache_lyra_check_url_warn')) {
        $ipn_url_warn = sprintf(__('The automatic validation has not worked. Have you correctly set up the notification URL in your %s Back Office?', 'lyra-give'), 'Lyra');
        $ipn_url_warn .= '<br />';
        $ipn_url_warn .= __('For understanding the problem, please read the documentation of the module : <br />&nbsp;&nbsp;&nbsp;- Chapter &laquo; To read carefully before going further &raquo;<br />&nbsp;&nbsp;&nbsp;- Chapter &laquo; Notification URL settings &raquo;', 'lyra-give');

        Give_Notices::print_frontend_notice($ipn_url_warn, true, 'error');
        Give_Cache::delete('give_cache_lyra_check_url_warn');
    }

    echo '<br />';
}

add_action('give_new_receipt', 'lyra_give_receipt');
add_action('give_payment_receipt_before_table', 'lyra_give_receipt');
