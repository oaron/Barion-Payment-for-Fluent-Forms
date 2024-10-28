<?php
namespace FluentBarion\BarionPaymentMethodIntegration;

/*
  Plugin Name: Integration for Barion payment gateway and Fluent Forms
  Description: Barion payment gateway integration for Fluent Forms
  Version: 1.0.1
  Author: oaron
  Author URI:  https://bitron.hu
  Text Domain: integration-barion-payment-gateway-fluent-forms
  Domain Path: /languages/
  License: GPLv2 or later
  License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class BarionPaymentMethodIntegrationForFluentForms {

	private static $instance = null;

	public static function get_instance() {
	   
		if ( ! self::$instance )
			self::$instance = new self;
		return self::$instance;
        
	}
    
    public function __construct() {
		add_action( 'fluentform/loaded', array( $this, 'init' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_pixelcode' ) );
		add_action( 'wp_footer', array( $this, 'add_noscript_pixel' ) );
	}

	public function init(){

        $this->load_plugin_textdomain();
    
        if ( 
            class_exists( 'FluentForm\App\Helpers\Helper' ) &&
            class_exists( 'FluentForm\Framework\Helpers\ArrayHelper' ) &&
            class_exists( 'FluentFormPro\Payments\PaymentHelper' ) &&
            class_exists( 'FluentFormPro\Payments\PaymentMethods\BasePaymentMethod' ) &&
            class_exists( 'FluentFormPro\Payments\PaymentMethods\BaseProcessor' ) 
        ) {

            require_once plugin_dir_path( __FILE__ ) . 'includes/BarionLoader.php';
            \FluentBarion\BarionWrapper\BarionLoader::load();
            new \FluentBarion\BarionHandler\BarionHandler();
        }

	}

	public function enqueue_pixelcode() {
		// Ellenőrizzük, hogy a BarionSettings osztály elérhető-e
		if ( ! class_exists( 'BarionSettings' ) ) {
			return; // Ha nincs, akkor ne folytassuk
		}

		// Register Barion Pixel script
		wp_register_script( 'barion-pixel', 'https://pixel.barion.com/bp.js', array(), null, true );

		// Enqueue the script
		wp_enqueue_script( 'barion-pixel' );

		// Add inline script for initializing Barion Pixel
		$pixel_key = esc_js( BarionSettings::getPixelKey() );
		$inline_script = "
			var bp_code = '{$pixel_key}';
			// Create BP element on the window
			window.bp = window.bp || function () {
				(window.bp.q = window.bp.q || []).push(arguments);
			};
			window.bp.l = 1 * new Date();
			
			// Send init event
			bp('init', 'addBarionPixelId', bp_code);
		";

		wp_add_inline_script( 'barion-pixel', $inline_script );
	}

	public function add_noscript_pixel() {
		// Ellenőrizzük, hogy a BarionSettings osztály elérhető-e
		if ( ! class_exists( 'BarionSettings' ) ) {
			return; // Ha nincs, akkor ne folytassuk
		}

		$pixel_key = esc_attr( BarionSettings::getPixelKey() );
		echo '<noscript>
			<img height="1" width="1" style="display:none" alt="Barion Pixel" src="https://pixel.barion.com/a.gif?ba_pixel_id=' . esc_attr( $pixel_key ) . '&ev=contentView&noscript=1">
		</noscript>';
	}

    public function load_plugin_textdomain() {
		$plugin_dir = basename( dirname( __FILE__ ) );
		load_plugin_textdomain( 'integration-barion-payment-gateway-fluent-forms', false, plugin_basename( dirname( __FILE__ ) ) . '/languages/' );
	}

}

function ffbp_is_fluentform_and_fluentformpro_plugin_activated() {
	$active_plugins = (array) get_option( 'active_plugins', array() );

	if ( is_multisite() ) {
		$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
	}

	return ( in_array( 'fluentformpro/fluentformpro.php', $active_plugins ) || array_key_exists( 'fluentformpro/fluentformpro.php', $active_plugins ) ) &&  
		   ( in_array( 'fluentform/fluentform.php', $active_plugins ) || array_key_exists( 'fluentform/fluentform.php', $active_plugins ) );
}

if ( ffbp_is_fluentform_and_fluentformpro_plugin_activated() ) {
	BarionPaymentMethodIntegrationForFluentForms::get_instance();
} else {
	add_action( 'admin_notices', function (){
		if ( current_user_can( 'activate_plugins' ) ) {
			echo '<div class="notice notice-error"><p>';
			echo esc_html__( 'Please install Fluent Forms and Fluent Forms Pro to use Barion Payment Gateway Integration!', 'integration-barion-payment-gateway-fluent-forms' );
			echo '</p></div>';
		}
	});
}
