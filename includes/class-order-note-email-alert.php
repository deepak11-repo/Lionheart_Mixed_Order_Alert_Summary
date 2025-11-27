<?php
/**
 * Class: WooCommerce_OrderNote_Email_Alert
 *
 * Main plugin class that handles order note monitoring and instant email alerts.
 *
 * @package WooCommerce_Order_Alerts
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WooCommerce_OrderNote_Email_Alert {

	private $namespace       = 'wc-order-note-api/v1';
	private $processed_notes = array(); // Track processed notes to prevent duplicates

	// Email settings
	private $send_to_admins        = false; // Set to true to send to all admins
	private $additional_recipients = array( 'deepak.naidu@wisdmlabs.com' ); // Add custom email addresses here

	public function __construct() {

		/** Order Note Hooks */
		add_action( 'woocommerce_order_note_added', array( $this, 'send_order_note_alert' ), 10, 2 );
		add_action( 'wp_insert_comment', array( $this, 'check_if_order_note' ), 10, 2 );
		add_action( 'admin_notices', array( $this, 'show_alert_notices' ) );

		/** REST API Hook */
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes
	 */
	public function register_routes() {
		// Administrator Email Addresses Endpoint
		register_rest_route(
			$this->namespace,
			'/administrators',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_admin_emails' ),
				'permission_callback' => array( $this, 'check_rest_api_permission' ),
			)
		);
	}

	/**
	 * Check REST API permission
	 * Only allow authenticated users with appropriate capabilities
	 */
	public function check_rest_api_permission() {
		return current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
	}

	/**
	 * GET ADMIN EMAILS ENDPOINT
	 */
	public function get_admin_emails( $request ) {
		$admins = get_users( array( 'role' => 'administrator' ) );

		$emails = array();
		foreach ( $admins as $admin ) {
			$emails[] = $admin->user_email;
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'emails'  => $emails,
				'total'   => count( $emails ),
			)
		);
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

	/**
	 * Get all recipient email addresses
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
		$recipients = array_filter( array_unique( $recipients ) );

		return $recipients;
	}

	/**
	 * Fallback method for older WooCommerce versions
	 */
	public function check_if_order_note( $comment_id, $comment ) {
		if ( $comment->comment_type !== 'order_note' ) {
			return;
		}

		$order = wc_get_order( $comment->comment_post_ID );
		if ( $order ) {
			$this->process_order_note( $comment_id, $order );
		}
	}

	/**
	 * Main hook - woocommerce_order_note_added
	 *
	 * @param int      $order_note_id - The order note ID
	 * @param WC_Order $order - The order object
	 */
	public function send_order_note_alert( $order_note_id, $order ) {
		if ( ! $order ) {
			return;
		}

		$this->process_order_note( $order_note_id, $order );
	}

	/**
	 * Process order note and send email alert
	 */
	private function process_order_note( $note_id, $order ) {
		// Prevent duplicate processing - check if this note was already processed
		if ( isset( $this->processed_notes[ $note_id ] ) ) {
			return;
		}

		$note = get_comment( $note_id );

		if ( ! $note ) {
			return;
		}

		// Check if this is a private note
		$is_customer_note = get_comment_meta( $note_id, 'is_customer_note', true );
		$is_private_note  = empty( $is_customer_note );

		// Only process private notes
		if ( ! $is_private_note ) {
			return;
		}

		// Check if this is a Walsworth fulfillment note
		if ( ! $this->is_walsworth_note( $note->comment_content ) ) {
			return; // Skip non-Walsworth notes
		}

		// Mark this note as processed to prevent duplicates
		$this->processed_notes[ $note_id ] = true;

		// Parse Walsworth fulfillment data
		$walsworth_data = $this->parse_walsworth_data( $note->comment_content );

		// Prepare order data
		$order_data = array(
			'order_id'       => $order->get_id(),
			'order_number'   => $order->get_order_number(),
			'order_status'   => $order->get_status(),
			'order_date'     => $order->get_date_created()->date( 'Y-m-d H:i:s' ),
			'customer_name'  => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
			'customer_email' => $order->get_billing_email(),
		);

		// Prepare note data
		$note_data = array(
			'note_id'         => $note_id,
			'note_content'    => $note->comment_content,
			'note_date'       => $note->comment_date,
			'note_author'     => $note->comment_author,
			'is_private_note' => true,
		);

		// Combine all data
		$alert_data = array(
			'event'                 => 'walsworth_order_fulfillment',
			'timestamp'             => current_time( 'mysql' ),
			'order'                 => $order_data,
			'note'                  => $note_data,
			'walsworth_fulfillment' => $walsworth_data,
		);

		// Send email alert
		$this->send_email_alert( $alert_data );
	}

	/**
	 * Check if order note is a Walsworth fulfillment note
	 */
	private function is_walsworth_note( $note_content ) {
		// Check for the Walsworth pattern
		if ( strpos( $note_content, 'Walsworth processed:' ) !== false ) {
			return true;
		}

		return false;
	}

	/**
	 * Parse Walsworth fulfillment data from order note
	 */
	private function parse_walsworth_data( $note_content ) {
		$data = array(
			'timestamp'               => '',
			'fulfillment_status'      => 'fully_processed', // Default
			'processed_items'         => array(),
			'not_processed_items'     => array(),
			'total_processed_qty'     => 0,
			'total_not_processed_qty' => 0,
		);

		// Extract timestamp
		if ( preg_match( '/At (\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) GMT/', $note_content, $matches ) ) {
			$data['timestamp'] = $matches[1];
		}

		// Extract processed items
		if ( preg_match( '/Walsworth processed:(.*?)(?:Walsworth DID NOT process:|$)/s', $note_content, $matches ) ) {
			preg_match_all( '/Qty (\d+) of \[(.*?)\]/', $matches[1], $items );
			for ( $i = 0; $i < count( $items[0] ); $i++ ) {
				$qty                          = intval( $items[1][ $i ] );
				$data['processed_items'][]    = array(
					'quantity' => $qty,
					'product'  => trim( $items[2][ $i ] ),
				);
				$data['total_processed_qty'] += $qty;
			}
		}

		// Extract NOT processed items
		if ( preg_match( '/Walsworth DID NOT process:(.*?)$/s', $note_content, $matches ) ) {
			preg_match_all( '/Qty (\d+) of \[(.*?)\]/', $matches[1], $items );
			for ( $i = 0; $i < count( $items[0] ); $i++ ) {
				$qty                              = intval( $items[1][ $i ] );
				$data['not_processed_items'][]    = array(
					'quantity' => $qty,
					'product'  => trim( $items[2][ $i ] ),
				);
				$data['total_not_processed_qty'] += $qty;
			}

			// Update fulfillment status if there are unprocessed items
			if ( $data['total_not_processed_qty'] > 0 ) {
				$data['fulfillment_status'] = 'partially_processed';
			}
		}

		// If no processed items at all
		if ( $data['total_processed_qty'] === 0 && $data['total_not_processed_qty'] > 0 ) {
			$data['fulfillment_status'] = 'not_processed';
		}

		return $data;
	}

	/**
	 * Send email alert using wp_mail
	 */
	private function send_email_alert( $data ) {
		$recipients = $this->get_recipients();

		if ( empty( $recipients ) ) {
			$message = 'Email alert FAILED: No recipients configured';
			set_transient( 'wc_email_alert_notice_' . get_current_user_id(), $message, 10 );
			return;
		}

		// Prepare email subject
		$subject = '🚨 Mixed Order Alert - Order #' . $data['order']['order_number'];

		// Prepare email body (HTML)
		$message = get_order_note_email_template( $data );

		// Email headers
		$from_email = 'noreply@' . sanitize_text_field( wp_parse_url( home_url(), PHP_URL_HOST ) );
		$headers    = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: Lion Heart Order Management <' . sanitize_email( $from_email ) . '>',
		);

		// Send email
		$sent = wp_mail( $recipients, $subject, $message, $headers );

		if ( $sent ) {
			$notice = '✅ Mixed order alert email sent successfully for Order #' . $data['order']['order_id'] . ' to ' . count( $recipients ) . ' recipient(s)';
			set_transient( 'wc_email_alert_notice_' . get_current_user_id(), $notice, 10 );
		} else {
			$notice = '❌ Failed to send mixed order alert email for Order #' . $data['order']['order_id'];
			set_transient( 'wc_email_alert_notice_' . get_current_user_id(), $notice, 10 );
		}
	}


	/**
	 * Show admin notices
	 */
	public function show_alert_notices() {
		$user_id = get_current_user_id();

		// Email alert notice
		$email_notice = get_transient( 'wc_email_alert_notice_' . $user_id );
		if ( $email_notice ) {
			$class = strpos( $email_notice, '✅' ) !== false ? 'notice-success' : 'notice-error';
			echo '<div class="notice ' . $class . ' is-dismissible"><p><strong>' . esc_html( $email_notice ) . '</strong></p></div>';
			delete_transient( 'wc_email_alert_notice_' . $user_id );
		}
	}
}
