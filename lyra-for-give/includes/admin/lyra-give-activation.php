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

/**
 * Payment Gateway Activation Banner.
 *
 * Includes and initializes Give activation banner class.
 *
 */
function lyra_give_activation_banner()
{
    // Check if give plugin is activated or not.
    $is_give_active = defined('GIVE_PLUGIN_BASENAME') ? is_plugin_active(GIVE_PLUGIN_BASENAME) : false;

    // Check if Give is deactivate and show a banner.
    if (current_user_can('activate_plugins') && ! $is_give_active) {
        add_action('admin_notices', 'lyra_give_activation_notice');

        // Don't let this plugin activate.
        deactivate_plugins(LYRA_GIVE_BASENAME);

        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }

        return false;
    }

    // Check for activation banner inclusion.
    if (! class_exists('Give_Addon_Activation_Banner') && file_exists(GIVE_PLUGIN_DIR . 'includes/admin/class-addon-activation-banner.php')) {
        include GIVE_PLUGIN_DIR . 'includes/admin/class-addon-activation-banner.php';
    }

    // Initialize activation welcome banner.
    if (class_exists('Give_Addon_Activation_Banner')) {
        $args = array(
            'file'         => __FILE__,
            'name'         => sprintf(__('%s Payment Gateway', 'lyra-give'), 'Lyra Collect'),
            'version'      => LYRA_GIVE_VERSION,
            'settings_url' => admin_url('edit.php?post_type=give_forms&page=give-settings&tab=gateways&section=lyra'),
            'testing'      => false
        );

        new Give_Addon_Activation_Banner($args);
    }

    return false;
}

add_action('admin_init', 'lyra_give_activation_banner');

/**
 * Notice for Activation.
 */
function lyra_give_activation_notice()
{
    echo '<div class="error">
            <p>'
              . sprintf(__('<strong>Activation Error:</strong> You must have the <a href="https://givewp.com/" target="_blank">Give</a> plugin installed and activated for the %s add-on to activate.', 'lyra-give'), 'Lyra Collect') .
            '</p>
         </div>';
}

/**
 * Payment gateway row action links.
 *
 * @param array $actions An array of plugin action links
 *
 * @return array An array of updated action links
 */
function lyra_give_plugin_action_links($actions)
{
    $new_actions = array(
        'settings' => sprintf(
            '<a href="%1$s">%2$s</a>',
            admin_url('edit.php?post_type=give_forms&page=give-settings&tab=gateways&section=lyra'),
            __('Settings', 'lyra-give')
        )
    );

    return array_merge($new_actions, $actions);
}

add_filter('plugin_action_links_' . LYRA_GIVE_BASENAME, 'lyra_give_plugin_action_links');
