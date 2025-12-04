<?php
/**
 * Daily Summary Cron Job Handler - Status-Based
 * * Handles the daily cron job that checks for orders with specific statuses
 * (Processing, Partially Shipped, or Pending Payment Partially Shipped) that have
 * Walsworth mixed fulfillment notes. No date restrictions.
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}

class WC_Daily_Summary_Status_Cron {

	private $cron_hook = 'wc_daily_mixed_order_summary_status';

	// Target order statuses - defined as constant for reuse (NOTE: these are UNPREFIXED slugs)
	private static $target_statuses = array(
		'processing',
		'partially-shipped',
		'pending-payment-partially-shipped',
	);

	// Email settings - separate from other cron jobs
	private $send_to_admins        = false; // Set to true to send to all admins
	private $additional_recipients = array( 'deepak.naidu@wisdmlabs.com' ); // Add custom email addresses here

	public function __construct() {
		add_filter( 'cron_schedules', array( $this, 'add_daily_cron_schedule' ) );
		add_action( $this->cron_hook, array( $this, 'execute_daily_summary' ) );
		add_action( 'init', array( $this, 'schedule_daily_cron' ) );

		// Allow manual triggering for testing (only for admins)
		if ( isset( $_GET['trigger_status_summary'] ) ) {
			add_action( 'admin_init', array( $this, 'check_and_trigger' ) );
		}
	}


	/**
	 * Adds a daily cron schedule to the given array of schedules.
	 *
	 * If the 'daily_at_7am' schedule is not already present in the given array,
	 * it is added with an interval of 86400 seconds (1 day) and a display name of
	 * 'Daily at 7:00 AM UTC'.
	 *
	 * @param array $schedules Array of cron schedules.
	 * @return array Modified array of cron schedules.
	 */
	public function add_daily_cron_schedule( $schedules ) {
		// Reuse the same schedule as the other cron.
		if ( ! isset( $schedules['daily_at_7am'] ) ) {
			$schedules['daily_at_7am'] = array(
				'interval' => 86400,
				'display'  => __( 'Daily at 7:00 AM UTC' ),
			);
		}
		return $schedules;
	}

	/**
	 * Schedules the daily cron job to run at 7:00 AM UTC.
	 * This method is automatically called during the WordPress init action.
	 * It checks if the cron job is already scheduled and if not, it schedules it.
	 */
	public function schedule_daily_cron() {
		$next_scheduled = wp_next_scheduled( $this->cron_hook );

		if ( ! $next_scheduled ) {
			// Calculate next 7:00 AM UTC - 2:00 AM EST.
			$dt        = new DateTime( 'tomorrow 07:00:00', new DateTimeZone( 'UTC' ) );
			$timestamp = $dt->getTimestamp();
			wp_schedule_event( $timestamp, 'daily_at_7am', $this->cron_hook );
		}
	}

	public function unschedule_daily_cron() {
		$timestamp = wp_next_scheduled( $this->cron_hook );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, $this->cron_hook );
		}
	}

	/**
	 * Check permissions and trigger manual summary
	 */
	public function check_and_trigger() {
		if ( current_user_can( 'manage_options' ) ) {
			$this->manual_trigger();
		}
	}

	/**
	 * Manually trigger the daily summary (for testing)
	 * Usage: Add ?trigger_status_summary=1 to any admin page URL
	 * Example: /wp-admin/?trigger_status_summary=1
	 */
	public function manual_trigger() {
		$this->execute_daily_summary();

		// Show success message
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-success is-dismissible"><p><strong>Status-based daily summary has been triggered manually!</strong> Check your email inbox.</p></div>';
			}
		);
	}

	public function execute_daily_summary() {
		$orders = $this->get_mixed_fulfillment_orders_by_status();

		if ( empty( $orders ) ) {
			return;
		}

		$summary_data = array(
			'date_generated'   => current_time( 'mysql' ),
			'date_range_start' => 'All time', // No date restriction
			'date_range_end'   => current_time( 'mysql' ),
			'total_orders'     => count( $orders ),
			'orders'           => $orders,
		);

		// Send email using separate recipient settings
		$this->send_summary_email( $summary_data );
	}

	/**
	 * Get orders with mixed fulfillment based on status filtering
	 * * Logic:
	 * 1. Find orders with target statuses
	 * 2. Check if order has Walsworth mixed fulfillment note
	 * 3. No date restrictions
	 */
	private function get_mixed_fulfillment_orders_by_status() {
		// Get all orders with target statuses (no date restriction)
		$orders_query = new WC_Order_Query(
			array(
				'post_type' => 'shop_order',
				'status'    => self::$target_statuses,
				'limit'     => -1,
				'orderby'   => 'date',
				'order'     => 'DESC',
			)
		);

		$all_orders = $orders_query->get_orders();

		if ( empty( $all_orders ) ) {
			return array();
		}

		$qualifying_orders = array();

		foreach ( $all_orders as $order ) {
			$order_id = $order->get_id();

			// Get all order notes for this order (include internal and system notes)
			$order_notes = wc_get_order_notes(
				array(
					'order_id' => $order_id,
					'type'     => 'any',
					'orderby'  => 'date_created',
					'order'    => 'DESC',
					'per_page' => -1,
				)
			);

			if ( empty( $order_notes ) ) {
				continue;
			}

			// Check if any note contains Walsworth mixed fulfillment pattern
			$mixed_fulfillment_note = null;

			foreach ( $order_notes as $note ) {
				// Handle different note structures
				$note_content = $this->get_note_content( $note );

				if ( empty( $note_content ) ) {
					continue;
				}

				// Check for both "Walsworth processed" and "Walsworth DID NOT process"
				$is_mixed = $this->is_mixed_fulfillment_note( $note_content );

				if ( $is_mixed ) {
					$mixed_fulfillment_note = $note;
					// Store note content to avoid re-accessing later
					$mixed_fulfillment_note->cached_content = $note_content;
					break; // Found one, no need to check further
				}
			}

			// If order has mixed fulfillment note, include it
			if ( $mixed_fulfillment_note ) {
				$qualifying_orders[] = $this->prepare_order_data( $order, $mixed_fulfillment_note );
			}
		}

		return $qualifying_orders;
	}

	/**
	 * Get note content from different note object structures
	 */
	private function get_note_content( $note ) {
		if ( isset( $note->comment_content ) ) {
			return $note->comment_content;
		} elseif ( isset( $note->content ) ) {
			return $note->content;
		} elseif ( is_object( $note ) && method_exists( $note, 'get_content' ) ) {
			return $note->get_content();
		}
		return '';
	}

	/**
	 * Check if order note contains Walsworth mixed fulfillment pattern
	 */
	private function is_mixed_fulfillment_note( $note_content ) {
		if ( empty( $note_content ) ) {
			return false;
		}

		// Normalize content for reliable pattern matching
		$normalized_content = preg_replace( '/\s+/', ' ', $note_content );

		// Check for both necessary strings in normalized content
		$has_processed     = strpos( $normalized_content, 'Walsworth processed:' ) !== false;
		$has_not_processed = strpos( $normalized_content, 'Walsworth DID NOT process:' ) !== false;

		return $has_processed && $has_not_processed;
	}

	/**
	 * Prepare order data for email template
	 */
	private function prepare_order_data( $order, $note ) {
		// Use cached content if available (from optimization), otherwise get from note
		$note_content   = isset( $note->cached_content ) ? $note->cached_content : $this->get_note_content( $note );
		$walsworth_data = $this->parse_walsworth_data( $note_content );

		// Get note date - handle different possible structures
		$note_date = isset( $note->comment_date ) ? $note->comment_date : ( isset( $note->date_created ) ? ( is_object( $note->date_created ) ? $note->date_created->date( 'Y-m-d H:i:s' ) : $note->date_created ) : '' );

		return array(
			'order_id'              => $order->get_id(),
			'order_number'          => $order->get_order_number(),
			'order_status'          => $order->get_status(), // Correct function to retrieve status
			'order_date'            => $order->get_date_created()->date( 'Y-m-d H:i:s' ),
			'customer_name'         => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
			'customer_email'        => $order->get_billing_email(),
			'order_total'           => $order->get_total(),
			'note_date'             => $note_date,
			'note_author'           => isset( $note->comment_author ) ? $note->comment_author : 'System',
			'walsworth_fulfillment' => $walsworth_data,
		);
	}

	/**
	 * Parse Walsworth fulfillment data from order note
	 * (Reused from original cron)
	 */
	private function parse_walsworth_data( $note_content ) {
		$data = array(
			'timestamp'               => '',
			'fulfillment_status'      => 'fully_processed',
			'processed_items'         => array(),
			'not_processed_items'     => array(),
			'total_processed_qty'     => 0,
			'total_not_processed_qty' => 0,
		);

		if ( preg_match( '/At (\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) GMT/', $note_content, $matches ) ) {
			$data['timestamp'] = $matches[1];
		}

		if ( preg_match( '/Walsworth processed:(.*?)(?:Walsworth DID NOT process:|$)/s', $note_content, $matches ) ) {
			preg_match_all( '/Qty (\d+) of \[(.*?)\]/', $matches[1], $items );
			$item_count = count( $items[0] ); // Cache count to avoid repeated calls
			for ( $i = 0; $i < $item_count; $i++ ) {
				$qty                          = intval( $items[1][ $i ] );
				$data['processed_items'][]    = array(
					'quantity' => $qty,
					'product'  => trim( $items[2][ $i ] ),
				);
				$data['total_processed_qty'] += $qty;
			}
		}

		if ( preg_match( '/Walsworth DID NOT process:(.*?)$/s', $note_content, $matches ) ) {
			preg_match_all( '/Qty (\d+) of \[(.*?)\]/', $matches[1], $items );
			$item_count = count( $items[0] ); // Cache count to avoid repeated calls
			for ( $i = 0; $i < $item_count; $i++ ) {
				$qty                              = intval( $items[1][ $i ] );
				$data['not_processed_items'][]    = array(
					'quantity' => $qty,
					'product'  => trim( $items[2][ $i ] ),
				);
				$data['total_not_processed_qty'] += $qty;
			}

			if ( $data['total_not_processed_qty'] > 0 ) {
				$data['fulfillment_status'] = 'partially_processed';
			}
		}

		if ( $data['total_processed_qty'] === 0 && $data['total_not_processed_qty'] > 0 ) {
			$data['fulfillment_status'] = 'not_processed';
		}

		return $data;
	}

	/**
	 * Send summary email with separate recipient settings
	 */
	private function send_summary_email( $summary_data ) {
		$recipients = $this->get_recipients();

		if ( empty( $recipients ) ) {
			return false;
		}

		// Load the status cron email template
		require_once plugin_dir_path( __DIR__ ) . 'templates/email-summary-template.php';
		$message = get_status_cron_email_template( $summary_data );

		$subject = 'Pending Order(s) Summary || ' . $summary_data['total_orders'] . ' Orders Require Attention';

		$from_email = 'noreply@' . sanitize_text_field( wp_parse_url( home_url(), PHP_URL_HOST ) );

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: The Lionheart Foundation <' . sanitize_email( $from_email ) . '>',
		);

		return wp_mail( $recipients, $subject, $message, $headers );
	}

	/**
	 * Get all recipient email addresses for status-based cron
	 */
	private function get_recipients() {
		$recipients = array();

		// Add admin emails if enabled
		if ( $this->send_to_admins ) {
			$recipients = array_merge( $recipients, $this->get_admin_email_addresses() );
		}

		// Add additional custom recipients
		if ( ! empty( $this->additional_recipients ) ) {
			$recipients = array_merge( $recipients, $this->additional_recipients );
		}

		// Remove duplicates and empty values
		return array_filter( array_unique( $recipients ) );
	}

	/**
	 * Get admin email addresses as array
	 */
	private function get_admin_email_addresses() {
		$admins = get_users( array( 'role' => 'administrator' ) );

		$emails = array();

		foreach ( $admins as $admin ) {
			if ( ! empty( $admin->user_email ) ) {
				$emails[] = $admin->user_email;
			}
		}

		return $emails;
	}
}

// Only instantiate if WooCommerce is active
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	new WC_Daily_Summary_Status_Cron();
}
