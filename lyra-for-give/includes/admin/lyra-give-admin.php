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

/**
 * Proceed only, if class Lyra_Give_Admin_Settings not exists.
 */
if (! class_exists('Lyra_Give_Admin_Settings')) {

    /**
     * Class Lyra_Give_Admin_Settings.
     */
    class Lyra_Give_Admin_Settings
    {
        /**
         * Lyra_Give_Admin_Settings constructor.
         */
        public function __construct()
        {
            add_action('give_admin_field_lyra_title', array($this, 'render_lyra_title'), 10, 2);
            add_action('give_admin_field_lyra_label', array($this, 'render_lyra_label'), 10, 2);
            add_filter('give_get_sections_gateways', array($this, 'register_sections'));
            add_action('give_get_settings_gateways', array($this, 'register_settings'));
        }

        /**
         * Render customized label.
         *
         * @param $field
         * @param $settings
         */
        public function render_lyra_label($field, $settings)
        {
            ?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr($field['id']); ?>"><?php echo $field['title']; ?></label>
                </th>
                <td class="give-forminp give-forminp-<?php echo sanitize_title($field['type']) ?>">
                    <span style="<?php echo isset($field['style']) ? $field['style'] :''; ?> font-weight: 700;"><?php echo $field['default']; ?></span>
                </td>
            </tr>
            <?php
        }

        /**
         * Render customized title.
         *
         * @param $field
         * @param $settings
         */
        public function render_lyra_title($field, $settings)
        {
            $current_tab = give_get_current_setting_tab();

            if ($field['table_html']) {
                echo $field['id'] === "lyra_module_information" ? '<table class="form-table">' . "\n\n" : '';
            }
            ?>
            <tr valign="top">
                <th scope="row" style="padding: 0px">
                    <div class="give-setting-tab-header give-setting-tab-header-<?php echo $current_tab; ?>">
                        <h2><?php echo $field['title']; ?></h2>
                        <hr>
                    </div>
                </th>
            </tr>
            <?php
        }

        /**
         * Register Admin Settings.
         *
         * @param array $settings List
         *
         * @return array
         */
        function register_settings($settings)
        {
            require_once LYRA_GIVE_DIR . '/lib/LyraGiveTools.php';

            switch (give_get_current_setting_section()) {
                case 'lyra':
                    $settings = array(
                        // Admin interface: module informations.
                        array(
                            'id'    => 'lyra_module_information',
                            'type'  => 'lyra_title',
                            'title' => __('MODULE INFORMATION', 'lyra-give')
                        ),
                        array(
                            'id'      => 'lyra_developer_by',
                            'type'    => 'lyra_label',
                            'name'    => __('Developed by', 'lyra-give'),
                            'default' => '<a href="https://www.lyra.com" style="text-decoration: none; color: #0073aa;">Lyra Network</a>'
                        ),
                        array(
                            'id'      => 'lyra_contact_email',
                            'type'    => 'lyra_label',
                            'name'    => __('Contact us', 'lyra-give'),
                            'default' => '<b>' . LyraApi::formatSupportEmails(LyraGiveTools::getDefault('SUPPORT_EMAIL')) . '</b>'
                        ),
                        array(
                            'id'      => 'lyra_module_version',
                            'type'    => 'lyra_label',
                            'name'    => __('Module version', 'lyra-give'),
                            'default' => LyraGiveTools::getDefault('PLUGIN_VERSION')
                        ),
                        array(
                            'id'      => 'lyra_gateway_version',
                            'type'    => 'lyra_label',
                            'name'    => __('Gateway version', 'lyra-give'),
                            'default' => LyraGiveTools::getDefault('GATEWAY_VERSION')
                        ),
                        array(
                            'id'      => 'lyra_doc_link',
                            'type'    => 'lyra_label',
                            'name'    => LyraGiveTools::getDocsLinks() !== "" ? __('Click to view the module configuration documentation', 'lyra-give') : "",
                            'default' => LyraGiveTools::getDocsLinks()
                        ),

                        // Admin interface: platform access settings.
                        array(
                            'id'    => 'lyra_payment_gateway_access',
                            'type'  => 'lyra_title',
                            'title' => __('PAYMENT GATEWAY ACCESS', 'lyra-give')
                        ),
                        array(
                            'id'      => 'lyra_site_id',
                            'type'    => 'text',
                            'name'    => __('Shop ID', 'lyra-give'),
                            'desc'    => sprintf(__('The identifier provided by %s.', 'lyra-give'), 'Lyra Collect'),
                            'default' => LyraGiveTools::getDefault('SITE_ID')
                        ),

                        ! LyraGiveTools::$plugin_features['qualif'] ?
                        array(
                            'id'      => 'lyra_key_test',
                            'type'    => 'text',
                            'name'    => __('Key in test mode', 'lyra-give'),
                            'desc'    => sprintf(__('Key provided by %s for test mode (available in %s Back Office).', 'lyra-give'), 'Lyra Collect', 'Lyra Expert'),
                            'default' => LyraGiveTools::getDefault('KEY_TEST')
                        ) : array(),
                        array(
                            'id'      => 'lyra_key_prod',
                            'type'    => 'text',
                            'name'    => __('Key in production mode', 'lyra-give'),
                            'desc'    => sprintf(__('Key provided by %s (available in %s Back Office after enabling production mode).', 'lyra-give'), 'Lyra Collect', 'Lyra Expert'),
                            'default' => LyraGiveTools::getDefault('KEY_PROD')
                        ),
                        array(
                            'id'         => LyraGiveTools::$plugin_features['qualif'] ? 'lyra_context_mode' : 'lyra_ctx_mode',
                            'type'       => 'select',
                            'options'    => LyraGiveTools::getDropdownList('lyra_cmodes'),
                            'name'       => __('Mode', 'lyra-give'),
                            'desc'       => __('The context mode of this module.', 'lyra-give'),
                            'default'    => LyraGiveTools::getDefault('CTX_MODE'),
                            'attributes' => LyraGiveTools::$plugin_features['qualif'] ? array('disabled' => 'true'): array()
                        ),
                        LyraGiveTools::$plugin_features['qualif'] ? array(
                            'id'      => 'lyra_ctx_mode',
                            'type'    => 'hidden',
                            'default' => LyraGiveTools::getDefault('CTX_MODE')
                        ) : array(),
                        array(
                            'id'      => 'lyra_sign_algo',
                            'type'    => 'select',
                            'options' => LyraGiveTools::getDropdownList('lyra_salgos'),
                            'name'    => __('Signature algorithm', 'lyra-give'),
                            'desc'    => LyraGiveTools::$plugin_features['shatwo'] ? preg_replace('#<br /><b>[^<>]+</b>#', '', sprintf(__('Algorithm used to compute the payment form signature. Selected algorithm must be the same as one configured in the %s Back Office.<br /><b>The HMAC-SHA-256 algorithm should not be activated if it is not yet available in the %s Back Office, the feature will be available soon.</b>', 'lyra-give'), 'Lyra Expert', 'Lyra Expert') ) : sprintf(__('Algorithm used to compute the payment form signature. Selected algorithm must be the same as one configured in the %s Back Office.<br /><b>The HMAC-SHA-256 algorithm should not be activated if it is not yet available in the %s Back Office, the feature will be available soon.</b>', 'lyra-give'), 'Lyra Expert', 'Lyra Expert'),
                            'default' => LyraGiveTools::getDefault('SIGN_ALGO')
                        ),
                        array(
                            'id'         => 'lyra_check_url',
                            'type'       => 'text',
                            'name'       => __('Instant Payment Notification URL', 'lyra-give'),
                            'desc'       => sprintf(__('<b style="color: red;">URL to copy into your %s Back Office > Settings > Notification rules.<br />In multistore mode, notification URL is the same for all the stores.</b>', 'lyra-give'), 'Lyra Expert'),
                            'default'    => home_url('?give-action=handle_lyra_response'),
                            'attributes' => array('disabled' => 'true')
                        ),
                        array(
                            'id'      => 'lyra_platform_url',
                            'type'    => 'text',
                            'name'    => __('Payment page URL', 'lyra-give'),
                            'desc'    => __('Link to the payment page.', 'lyra-give'),
                            'default' => LyraGiveTools::getDefault('GATEWAY_URL')
                        ),

                        // Admin interface: payment page settings.
                        array(
                            'id'   => 'lyra_payment_page',
                            'type' => 'lyra_title',
                            'name' => __('PAYMENT PAGE', 'lyra-give')
                        ),
                        array(
                            'id'      => 'lyra_default_language',
                            'type'    => 'select',
                            'options' => LyraGiveTools::getDropdownList('lyra_languages'),
                            'name'    => __('Default language', 'lyra-give'),
                            'desc'    => __('Default language on the payment page.', 'lyra-give'),
                            'default' => LyraGiveTools::getDefault('LANGUAGE')
                        ),
                        array(
                            'id'      => 'lyra_available_languages',
                            'type'    => 'multiselect',
                            'options' => LyraGiveTools::getDropdownList('lyra_languages'),
                            'name'    => __('Available languages', 'lyra-give'),
                            'desc'    => __('Languages available on the payment page. If you do not select any, all the supported languages will be available.', 'lyra-give'),
                            'default' => array()
                        ),
                        array(
                            'id'   => 'lyra_capture_delay',
                            'type' => 'text',
                            'name' => __('Capture delay', 'lyra-give'),
                            'desc' => sprintf(__('The number of days before the bank capture (adjustable in your %s Back Office).', 'lyra-give'), 'Lyra Expert')
                        ),
                        array(
                            'id'      => 'lyra_validation_mode',
                            'type'    => 'select',
                            'options' => LyraGiveTools::getDropdownList('lyra_vmodes'),
                            'name'    => __('Validation mode', 'lyra-give'),
                            'desc'    => sprintf(__('If manual is selected, you will have to confirm payments manually in your %s Back Office.', 'lyra-give'),'Lyra Expert'),
                            'default' => ''
                        ),
                        array(
                            'id'      => 'lyra_payment_cards',
                            'type'    => 'multiselect',
                            'options' => LyraGiveTools::getDropdownList('lyra_cards'),
                            'name'    => __('Card types', 'lyra-give'),
                            'desc'    => __('The card type(s) that can be used for the payment. Select none to use gateway configuration.', 'lyra-give')
                        ),

                        // Admin interface: 3-DS settings.
                        array(
                            'id'    => 'lyra_selective_3ds',
                            'type'  => 'lyra_title',
                            'title' => __('CUSTOM 3DS', 'lyra-give')
                        ),
                        array(
                            'id'   => 'lyra_3ds_min_amount',
                            'type' => 'text',
                            'name' => __('Manage 3DS', 'lyra-give'),
                            'desc' => __('Amount below which customer could be exempt from strong authentication. Needs subscription to « Selective 3DS1 » or « Frictionless 3DS2 » options. For more information, refer to the module documentation.', 'lyra-give')
                        ),

                        // Admin interface: return to store settings.
                        array(
                            'id'    => 'lyra_return_to_shop',
                            'type'  => 'lyra_title',
                            'title' => __('RETURN TO SHOP', 'lyra-give')
                        ),
                        array(
                            'id'          => 'lyra_redirect_enabled',
                            'type'        => 'radio_inline',
                            'name'        => __('Automatic redirection', 'lyra-give'),
                            'desc'        => __('If enabled, the buyer is automatically redirected to your site at the end of the payment.', 'lyra-give'),
                            'row_classes' => 'give-subfield give-hidden',
                            'default'     => '0',
                            'options'     => LyraGiveTools::getDropdownList('lyra_redirects')
                        ),
                        array(
                            'id'      => 'lyra_redirect_success_timeout',
                            'type'    => 'text',
                            'name'    => __('Redirection timeout on success', 'lyra-give'),
                            'desc'    => __('Time in seconds (0-300) before the buyer is automatically redirected to your website after a successful payment.', 'lyra-give'),
                            'default' => '5'
                        ),
                        array(
                            'id'      => 'lyra_redirect_success_message',
                            'type'    => 'text',
                            'name'    => __('Redirection message on success', 'lyra-give'),
                            'desc'    => __('Message displayed on the payment page prior to redirection after a successful payment.', 'lyra-give'),
                            'default' => __('Redirection to shop in a few seconds...', 'lyra-give')
                        ),
                        array(
                            'id'      => 'lyra_redirect_error_timeout',
                            'type'    => 'text',
                            'name'    => __('Redirection timeout on failure', 'lyra-give'),
                            'desc'    => __('Time in seconds (0-300) before the buyer is automatically redirected to your website after a declined payment.', 'lyra-give'),
                            'default' => '5'
                        ),
                        array(
                            'id'      => 'lyra_redirect_error_message',
                            'type'    => 'text',
                            'name'    => __('Redirection message on failure', 'lyra-give'),
                            'desc'    => __('Message displayed on the payment page prior to redirection after a declined payment.', 'lyra-give'),
                            'default' => __('Redirection to shop in a few seconds...', 'lyra-give')
                        ),
                        array(
                            'id'      => 'lyra_return_mode',
                            'type'    => 'select',
                            'name'    => __('Return mode', 'lyra-give'),
                            'desc'    => __('Method that will be used for transmitting the payment result from the payment page to your shop.', 'lyra-give'),
                            'options' => LyraGiveTools::getDropdownList('lyra_rmodes'),
                            'default' => 'GET'
                        ),
                        array(
                            'id'   => 'give_title_lyra',
                            'type' => 'sectionend'
                        ),
                    );

                    break;
            }

            return $settings;
        }

        /**
         * Register Section for Payment Gateway Settings.
         *
         * @param array $sections List of sections
         *
         * @return mixed
         */
        public function register_sections($sections)
        {
            $sections['lyra'] = 'Lyra Collect';

            return $sections;
        }
    }
}

new Lyra_Give_Admin_Settings();
