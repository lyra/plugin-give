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

if (! class_exists('Lyra_Give_Gateway_Processor')) {
    require_once LYRA_GIVE_DIR . '/lib/LyraTools.php';
    require_once LYRA_GIVE_DIR . '/lib/LyraLogger.php';

    /**
     * Handles payment gateway.
     *
     * Adds frontend fields and handles payment processing.
     */
    class Lyra_Give_Gateway_Processor
    {
        public $logger;

        public function __construct()
        {
            $this->logger = LyraLogger::getLogger(__CLASS__);

            add_action('give_gateway_lyra', array($this, 'lyra_give_process_payment')); // This action will run the function attached to it when it's time to process the donation submission.
            add_action('give_handle_lyra_response', array($this, 'lyra_give_payment_listener'));
        }

        private function lyra_give_create_payment($payment_data)
        {
            if (is_array($payment_data) && ! empty($payment_data) && $payment_data != null) {
                $form_id  = isset($payment_data['post_data']) ? intval($payment_data['post_data']['give-form-id']) : '';
                $price_id = isset($payment_data['post_data']['give-price-id']) ? $payment_data['post_data']['give-price-id'] : '';

                $insert_payment_data = array(
                    'price'           => isset($payment_data['price']) ? $payment_data['price'] : '',
                    'give_form_title' => isset($payment_data['post_data']['give-form-title']) ? $payment_data['post_data']['give-form-title'] : '',
                    'give_form_id'    => $form_id,
                    'give_price_id'   => $price_id,
                    'date'            => isset($payment_data['date']) ? $payment_data['date'] : '',
                    'user_email'      => isset($payment_data['user_email']) ? $payment_data['user_email'] : '',
                    'purchase_key'    => isset($payment_data['purchase_key']) ? $payment_data['purchase_key'] :  '',
                    'currency'        => give_get_currency($form_id, $payment_data),
                    'user_info'       => isset($payment_data['user_info']) ? $payment_data['user_info'] : '',
                    'status'          => 'pending',
                    'gateway'         => 'lyra'
                );

                /**
                 * Filter the payment params.
                 *
                 * @param array $insert_payment_data
                 */
                $insert_payment_data = apply_filters('give_create_payment', $insert_payment_data);

                return give_insert_payment($insert_payment_data);
            }

            return null;
        }

        public function lyra_give_process_payment($payment_data)
        {
            $payment_id = $this->lyra_give_create_payment($payment_data);

            $this->logger->info('Creating payment with ID#' . $payment_id . '.');

            // Check payment.
            if (empty($payment_id)) {
                // Save payment creation error to database.
                give_record_gateway_error(
                    'Lyra Collect payment error',
                     sprintf(
                         'Payment creation failed before sending donor to Lyra Collect payment gateway. Payment data: %s',
                         json_encode($payment_data)
                    )
                );

                $this->logger->error('Payment creation failed before sending donor to Lyra Collect payment gateway. Payment data:' . print_r($payment_data, true));

                give_send_back_to_checkout();
            }

            // Get give gateways settings.
            $give_settings = give_get_settings();

            // Use our custom class to generate the HTML.
            require_once LYRA_GIVE_DIR . '/lib/LyraRequest.php';
            $lyra_request = new LyraRequest();

            $this->logger->info('Generating payment form for donation #' . $payment_id . '.');

            // Admin configuration parameters.
            $config_params = array(
                'site_id', 'key_test', 'key_prod', 'ctx_mode', 'sign_algo', 'url_check', 'platform_url',
                'language', 'available_languages', 'capture_delay', 'validation_mode', 'payment_cards',
                '3ds_min_amount', 'redirect_enabled', 'redirect_success_timeout', 'redirect_success_message',
                'redirect_error_timeout', 'redirect_error_message', 'return_mode'
            );

            foreach ($config_params as $name) {
                $value = key_exists('lyra_' . $name, $give_settings) ? $give_settings['lyra_' . $name] : '';

                if (is_array($value)) {
                    $value = implode(';', $value);
                }

                $lyra_request->set($name, $value);
            }

            // Get the shop language code.
            $lang = substr(get_locale(), 0, 2);
            $lyra_language = LyraApi::isSupportedLanguage($lang) ? $lang : $give_settings['lyra_language'];

            // Get the currency to use.
            $form_id = isset($payment_data['post_data']['give-form-id']) ? $payment_data['post_data']['give-form-id'] : '';
            $currency_code = give_get_currency($form_id);
            $lyra_currency = LyraApi::findCurrencyByAlphaCode($currency_code);

            // Calculate total amount in cents.
            $amount = give_donation_amount($payment_id);
            $total = $lyra_currency->convertAmountToInteger($amount);

            // Activate 3DS?
            $threeds_mpi = null;
            if ($give_settings['lyra_3ds_min_amount'] && $amount < $give_settings['lyra_3ds_min_amount']) {
                $threeds_mpi = '2';
            }

            // Effective used version.
            include ABSPATH . WPINC . '/version.php';

            $version = $wp_version . '_' . GIVE_VERSION;

            $plugin_params = LyraTools::getDefault('CMS_IDENTIFIER') . '_' . LyraTools::getDefault('PLUGIN_VERSION') . '/' . $version . '/' . PHP_VERSION;

            $callback = home_url('?give-action=handle_lyra_response');

            // Other parameters.
            $data = array(
                // Donation info.
                'amount'   => $total,
                'order_id' => $payment_id,
                'contrib'  => $plugin_params,

                // Misc data.
                'currency'    => $lyra_currency->getNum(),
                'language'    => $lyra_language,
                'threeds_mpi' => $threeds_mpi,
                'url_return'  => $callback,

                // Donator info.
                'cust_first_name' => isset($payment_data['user_info']['first_name']) ? $payment_data['user_info']['first_name'] : '',
                'cust_last_name'  => isset($payment_data['user_info']['last_name']) ? $payment_data['user_info']['last_name'] : '',
                'cust_email'      => isset($payment_data['user_info']['email']) ? $payment_data['user_info']['email'] : ''
            );

            $lyra_request->setFromArray($data);

            $this->logger->info('Data to be sent to payment gateway: ' . print_r($lyra_request->getRequestFieldsArray(true /* To hide sensitive data. */), true));

            // Redirect to payment gateway.
            wp_redirect($lyra_request->getRequestUrl());
            give_die();
        }

        /**
         * Payment listener.
         *
         * Waits for responses from payment gateway.
         */
        public function lyra_give_payment_listener()
        {
            require_once LYRA_GIVE_DIR . '/lib/LyraResponse.php';

            $from_server = isset($_POST['vads_hash']) && ! empty($_POST['vads_hash']);

            $params = (array) stripslashes_deep($_REQUEST);

            $lyraResponse = new LyraResponse (
                $params,
                give_get_option('lyra_ctx_mode'),
                give_get_option('lyra_key_test'),
                give_get_option('lyra_key_prod'),
                give_get_option('lyra_sign_algo')
            );

            if (! $lyraResponse->isAuthentified()) {
                $this->logger->error('Authentication failed: received invalid response with parameters: ' . print_r($params, true));
                $this->logger->error('Signature algorithm selected in module settings must be the same as one selected in gateway Back Office.');

                // Save authentificaion error to database.
                give_record_gateway_error(
                    'Lyra Collect authentification error',
                    sprintf(
                        'Authentication failed: received invalid response with parameters: %s',
                        json_encode($params)
                    )
                );

                if ($from_server) {
                    $this->logger->info('IPN URL PROCESS END.');

                    give_die($lyraResponse->getOutputForGateway('auth_fail'));
                } else {
                    $this->logger->info('RETURN URL PROCESS END.');

                    wp_redirect(home_url('/'));
                    give_die();
                }
            }

            // Retrieve give donation info from database.
            $donationId = $lyraResponse->get('order_id');
            $give_donation = get_post($donationId);
            if (empty($give_donation)) {
                $this->logger->error("Donation with ID #$donationId was not found.");

                // Save donation not found error to database.
                give_record_gateway_error(
                    'Lyra Collect donation not found error',
                    sprintf(
                        'Donation with ID #%s was not found.',
                        $donationId
                    )
                );

                if ($from_server) {
                    $this->logger->info('IPN URL PROCESS END.');

                    give_die($lyraResponse->getOutputForGateway('order_not_found'));
                } else {
                    $this->logger->info('RETURN URL PROCESS END.');

                    wp_redirect(home_url('/'));
                    give_die();
                }
            }

            // Add prodfaq domain feature to show going to production messages.
            Give()->session->set('lyra_going_into_prod', (! $from_server && (give_get_option('lyra_ctx_mode') === 'TEST') && LyraTools::$plugin_features['prodfaq']));

            // Process according to donation status and payment result.
            if (! empty($give_donation)) {
                if ($give_donation->post_status === 'pending') {
                    //Donation not processed yet.
                    $this->logger->info("First payment notification. Let's change donation status.");
                    $new_donation_status = LyraTools::getNewDonationStatus($lyraResponse);
                    give_update_payment_status($donationId, $new_donation_status);

                    if ($lyraResponse->isAcceptedPayment()) { // Update post_status = publish.
                        $this->logger->info("Payment for donation #$donationId accepted. New donation status is $new_donation_status.");

                        if ($from_server) {
                            $this->logger->info('IPN URL PROCESS END.');
                            give_die($lyraResponse->getOutputForGateway('payment_ok'));
                        } else {
                            if (give_get_option('lyra_ctx_mode') === 'TEST') {
                                Give()->session->set('lyra_check_url_warn', 'true');
                            }

                            $this->logger->info('RETURN URL PROCESS END.');
                            give_send_to_success_page();
                        }
                    } elseif ($lyraResponse->isCancelledPayment()) { // Update post_status = cancelled.
                        $this->logger->info('Payment cancelled. ' . $lyraResponse->getLogMessage() . ' Please, try to re-donate.');

                        if ($from_server) {
                            $this->logger->info('SERVER URL PROCESS END');
                            give_die($lyraResponse->getOutputForGateway('payment_ko'));
                        } else {
                            $this->logger->info('RETURN URL PROCESS END');
                            $this->send_back_to_checkout($donationId);
                        }
                    } else { // Update post_status = failed.
                        $this->logger->info("Donation #$donationId is failed. Payment result: " . $lyraResponse->getTransStatus() . ", Donation status: $new_donation_status.");

                        if ($from_server) {
                            $this->logger->info('SERVER URL PROCESS END');
                            give_die($lyraResponse->getOutputForGateway('payment_ko'));
                        } else {
                            $this->logger->info('RETURN URL PROCESS END');
                            $this->send_back_to_checkout($donationId);
                        }
                    }
                } else {
                    // Donation already processed.
                    $this->logger->info("Donation #$donationId is already saved.");

                    $expected_donation_status = LyraTools::getNewDonationStatus($lyraResponse);
                    if ($expected_donation_status !== $give_donation->post_status) {
                        $this->logger->error("Error! Invalid payment result received for already saved donation #$donationId. Payment result: " . $lyraResponse->getTransStatus() . ", Donation status: $give_donation->post_status.");

                        // Save donation bad status error to database.
                        give_record_gateway_error(
                            'Lyra Collect donation status error',
                            sprintf(
                                'Invalid payment result received for already saved donation #%s. Payment result: %s, donation status: %s.',
                                $donationId,
                                $lyraResponse->getTransStatus(),
                                $give_donation->post_status
                            )
                        );

                        if ($from_server) {
                            $this->logger->info('IPN URL PROCESS END.');
                            give_die($lyraResponse->getOutputForGateway('payment_ko_on_order_ok'));
                        } else {
                            $this->logger->info('RETURN URL PROCESS END.');

                            wp_redirect(home_url('/'));
                            give_die();
                        }
                    } elseif ($lyraResponse->isAcceptedPayment()) {
                        $this->logger->info("Payment successful confirmed for donation #$donationId.");

                        if ($from_server) {
                            $this->logger->info('IPN URL PROCESS END.');
                            give_die($lyraResponse->getOutputForGateway('payment_ok_already_done'));
                        } else {
                            $this->logger->info('RETURN URL PROCESS END.');
                            give_send_to_success_page();
                        }
                    } else {
                        $this->logger->error("Payment failed or cancelled confirmed for donation #$donationId.");

                        if ($from_server) {
                            $this->logger->info('IPN URL PROCESS END.');
                            give_die($lyraResponse->getOutputForGateway('payment_ko_already_done'));
                        } else {
                            $this->logger->info('RETURN URL PROCESS END.');
                            $this->send_back_to_checkout($donationId);
                        }
                    }
                }
            }
        }

        private function send_back_to_checkout($donationId)
        {
            $this->logger->info('send back to checkout: ' . $donationId);
            $_POST['give-current-url'] = get_post_meta($donationId, '_give_current_url', true);
            $_POST['give-form-id'] = get_post_meta($donationId, '_give_payment_form_id', true);
            $_POST['give-price-id'] = get_post_meta($donationId, '_give_payment_price_id', true);

            give_send_back_to_checkout();
        }
    }

    return new Lyra_Give_Gateway_Processor();
}
