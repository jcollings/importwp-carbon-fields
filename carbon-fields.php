<?php

/**
 * Plugin Name: Import WP - Carbon Fields Importer Addon
 * Plugin URI: https://www.importwp.com
 * Description: Allow Import WP to import Carbon Fields.
 * Author: James Collings <james@jclabs.co.uk>
 * Version: 0.0.1
 * Author URI: https://www.importwp.com
 * Network: True
 */

define('IWP_CARBON_FIELDS_MIN', '2.5.0');
define('IWP_CARBON_FIELDS_PRO_MIN', '2.5.0');

add_action('admin_init', 'iwp_carbon_fields_check');

function iwp_carbon_fields_requirements_met()
{
    return false === (is_admin() && current_user_can('activate_plugins') &&  (!defined('IWP_VERSION') || version_compare(IWP_VERSION, IWP_CARBON_FIELDS_MIN, '<') || !defined('IWP_PRO_VERSION') || version_compare(IWP_PRO_VERSION, IWP_CARBON_FIELDS_PRO_MIN, '<')));
}

function iwp_carbon_fields_check()
{
    if (!iwp_carbon_fields_requirements_met()) {

        add_action('admin_notices', 'iwp_carbon_fields_notice');

        deactivate_plugins(plugin_basename(__FILE__));

        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }
    }
}

function iwp_carbon_fields_setup()
{
    if (!iwp_carbon_fields_requirements_met()) {
        return;
    }

    $base_path = dirname(__FILE__);

    require_once $base_path . '/setup.php';

    // Install updater
    if (file_exists($base_path . '/updater.php') && !class_exists('IWP_Updater')) {
        require_once $base_path . '/updater.php';
    }

    if (class_exists('IWP_Updater')) {
        $updater = new IWP_Updater(__FILE__, 'importwp-carbon-fields');
        $updater->initialize();
    }
}
add_action('plugins_loaded', 'iwp_carbon_fields_setup', 9);

function iwp_carbon_fields_notice()
{
    echo '<div class="error">';
    echo '<p><strong>Import WP - Carbon Fields Importer Addon</strong> requires that you have <strong>Import WP v' . IWP_CARBON_FIELDS_MIN . '+ and Import WP PRO v' . IWP_CARBON_FIELDS_PRO_MIN . '+</strong>, and <strong>Carbon Fields</strong> installed.</p>';
    echo '</div>';
}
