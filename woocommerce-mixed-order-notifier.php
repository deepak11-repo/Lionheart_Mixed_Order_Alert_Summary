<?php
/**
 * Plugin Name: WooCommerce Mixed Order Email Notifier
 * Plugin URI: https://github.com/wisdmlabs/woocommerce-mixed-order-notifier
 * Description: This plugin enhances your WooCommerce store by sending instant email alerts whenever an order involves split or mixed fulfillment—such as items shipping from different locations or at different times.
 * Version: 3.0
 * Author: Team WisdmLabs
 * Author URI: https://wisdmlabs.com
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p><strong>WooCommerce Mixed Order Email Notifier</strong> requires WooCommerce to be installed and active.</p></div>';
		}
	);
	return;
}

// Include core classes
require_once plugin_dir_path( __FILE__ ) . 'includes/class-order-note-email-alert.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-daily-summary-status-cron.php';

// Include email templates
require_once plugin_dir_path( __FILE__ ) . 'templates/email-alert-template.php';
require_once plugin_dir_path( __FILE__ ) . 'templates/email-summary-template.php';

// Initialize the main plugin class
new WooCommerce_OrderNote_Email_Alert();

// Register deactivation hook to clean up cron jobs
register_deactivation_hook(
	__FILE__,
	function () {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-daily-summary-status-cron.php';

		$status_cron_instance = new WC_Daily_Summary_Status_Cron();
		$status_cron_instance->unschedule_daily_cron();
	}
);
