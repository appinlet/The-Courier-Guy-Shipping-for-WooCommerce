<?php
/**
 * Plugin Name: The Courier Guy Shipping for WooCommerce
 * Description: The Courier Guy WP & Woocommerce Shipping functionality.
 * Author: The Courier Guy
 * Author URI: https://www.thecourierguy.co.za/
 * Contributor: App Inlet
 * Version: 4.2.0
 * Plugin Slug: wp-plugin-the-courier-guy
 * Text Domain: the-courier-guy
 */
if (!defined('ABSPATH')) {
    exit;
}
$dependencyPlugins = [
    'woocommerce/woocommerce.php' => [
        'notice' => 'Please install Woocommerce before attempting to install the The Courier Guy plugin.'
    ],
];
require_once('Includes/ls-framework-custom/Core/CustomPluginDependencies.php');
require_once('Includes/ls-framework-custom/Core/CustomPlugin.php');
require_once('Includes/ls-framework-custom/Core/CustomPostType.php');
require_once('Includes/ls-framework-custom/Core/CurlController.php');
require_once('Includes/dompdf/lib/html5lib/Parser.php');
require_once('Includes/dompdf/lib/php-font-lib/src/FontLib/Autoloader.php');
require_once('Includes/dompdf/lib/php-svg-lib/src/autoload.php');
require_once('Includes/dompdf/src/Autoloader.php');
require_once('Includes/php-barcode-generator-master/src/BarcodeGenerator.php');
require_once('Includes/php-barcode-generator-master/src/BarcodeGeneratorPNG.php');
$dependencies = new CustomPluginDependencies(__FILE__);
$dependenciesValid = $dependencies->checkDependencies($dependencyPlugins);
if ($dependenciesValid && !class_exists('TCG_Plugin') && class_exists('WC_Shipping_Method')) {
    require_once('Core/TCG_Plugin.php');
    global $TCG_Plugin;
    $TCG_Plugin = new TCG_Plugin(__FILE__);
    $GLOBALS['TCG_Plugin'] = $TCG_Plugin;
    register_activation_hook(__FILE__, [$TCG_Plugin, 'activatePlugin']);
    register_deactivation_hook(__FILE__, [$TCG_Plugin, 'deactivatePlugin']);
} else {
    deactivate_plugins(plugin_basename(__FILE__));
    unset($_GET['activate']);
}
