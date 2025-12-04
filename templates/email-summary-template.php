<?php
/**
 * Email Template for Status-Based Daily Summary Cron
 *
 * This file contains the HTML email template specifically designed for
 * the status-based daily summary cron job. It displays orders with
 * specific statuses (Processing, Partially Shipped, or Pending Payment Partially Shipped)
 * that have Walsworth mixed fulfillment notes.
 *
 * @param array $summary_data Array containing order summary data
 * @return string HTML email content
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function get_status_cron_email_template( $summary_data ) {
	$orders_html             = '';
	$total_unprocessed_items = 0;

	// Build Quick Overview Table rows
	$quick_overview_rows = '';
	foreach ( $summary_data['orders'] as $index => $order ) {
		$order_number             = $index + 1;
		$total_unprocessed_items += $order['walsworth_fulfillment']['total_not_processed_qty'];

		$order_edit_url = admin_url( 'post.php?post=' . $order['order_id'] . '&action=edit' );

		// Format status display with exact naming style
		if ( $order['order_status'] === 'processing' ) {
			$status_display = 'Processing';
		} elseif ( $order['order_status'] === 'partially-shipped' ) {
			$status_display = 'Partially Shipped';
		} elseif ( $order['order_status'] === 'pending-payment-partially-shipped' ) {
			$status_display = 'Pending payment partially shipped';
		} else {
			$status_display = ucwords( str_replace( '-', ' ', $order['order_status'] ) );
		}

		// Status badge colors matching user requirements
		$status_bg_color   = '#fff3cd';
		$status_text_color = '#856404';
		if ( $order['order_status'] === 'partially-shipped' ) {
			$status_bg_color   = 'rgb(217, 169, 68)';
			$status_text_color = '#1a0f05';
		} elseif ( $order['order_status'] === 'pending-payment-partially-shipped' ) {
			$status_bg_color   = 'rgb(234, 207, 134)';
			$status_text_color = '#1a0f05';
		} elseif ( $order['order_status'] === 'processing' ) {
			$status_bg_color   = 'rgb(198, 225, 198)';
			$status_text_color = '#155724';
		}

		// Format order date for display
		$order_date_formatted = date( 'M d, H:i', strtotime( $order['order_date'] ) );

		$quick_overview_rows .= '
        <tr style="background-color: #fff;">
            <td style="padding: 14px 16px; border-bottom: 1px solid #f1f3f5;">
                <a href="' . esc_url( $order_edit_url ) . '" style="color: #264584; font-weight: 700; text-decoration: none; font-size: 14px; font-family: \'Segoe UI\', sans-serif;">#' . esc_html( $order['order_number'] ) . '</a><br>
                <span style="font-size: 12px; color: #6c757d; font-family: \'Segoe UI\', sans-serif;">' . esc_html( $order_date_formatted ) . '</span>
            </td>
            <td style="padding: 14px 16px; border-bottom: 1px solid #f1f3f5;">
                <span style="color: #212529; font-size: 13px; font-weight: 600; font-family: \'Segoe UI\', sans-serif;">' . esc_html( $order['customer_name'] ) . '</span><br>
                <span style="font-size: 12px; color: #6c757d; font-family: \'Segoe UI\', sans-serif;">$' . esc_html( number_format( $order['order_total'], 2 ) ) . '</span>
            </td>
            <td style="padding: 14px 16px; text-align: center; border-bottom: 1px solid #f1f3f5;" align="center">
                <span style="display: inline-block; padding: 4px 8px; background-color: ' . esc_attr( $status_bg_color ) . '; color: ' . esc_attr( $status_text_color ) . '; border-radius: 4px; font-weight: 600; font-size: 10px; font-family: \'Segoe UI\', sans-serif;">' . esc_html( $status_display ) . '</span>
            </td>
            <td style="padding: 14px 16px; text-align: center; border-bottom: 1px solid #f1f3f5;" align="center">
                <span style="font-size: 20px; font-weight: 700; color: #dc3545; font-family: \'Segoe UI\', sans-serif;">' . esc_html( $order['walsworth_fulfillment']['total_not_processed_qty'] ) . '</span><br>
                <span style="font-size: 11px; color: #6c757d; font-family: \'Segoe UI\', sans-serif;">items</span>
            </td>
            <td style="padding: 14px 16px; text-align: center; border-bottom: 1px solid #f1f3f5;" align="center">
                <a href="' . esc_url( $order_edit_url ) . '" style="display: inline-block; padding: 6px 12px; background-color: #264584; color: #fff; text-decoration: none; border-radius: 4px; font-size: 11px; font-weight: 600; font-family: \'Segoe UI\', sans-serif;">View</a>
            </td>
        </tr>';
	}

	// Build detailed order breakdown
	foreach ( $summary_data['orders'] as $index => $order ) {
		$order_number   = $index + 1;
		$order_edit_url = admin_url( 'post.php?post=' . $order['order_id'] . '&action=edit' );

		// Format status display with exact naming style
		if ( $order['order_status'] === 'processing' ) {
			$status_display = 'Processing';
		} elseif ( $order['order_status'] === 'partially-shipped' ) {
			$status_display = 'Partially Shipped';
		} elseif ( $order['order_status'] === 'pending-payment-partially-shipped' ) {
			$status_display = 'Pending payment partially shipped';
		} else {
			$status_display = ucwords( str_replace( '-', ' ', $order['order_status'] ) );
		}

		// Status badge colors matching user requirements
		$status_bg_color   = '#fff3cd';
		$status_text_color = '#856404';
		if ( $order['order_status'] === 'partially-shipped' ) {
			$status_bg_color   = 'rgb(217, 169, 68)';
			$status_text_color = '#1a0f05';
		} elseif ( $order['order_status'] === 'pending-payment-partially-shipped' ) {
			$status_bg_color   = 'rgb(234, 207, 134)';
			$status_text_color = '#1a0f05';
		} elseif ( $order['order_status'] === 'processing' ) {
			$status_bg_color   = 'rgb(198, 225, 198)';
			$status_text_color = '#155724';
		}

		// Build unified items table (fulfilled and not fulfilled together)
		$items_table_rows = '';

		// Add fulfilled items
		if ( ! empty( $order['walsworth_fulfillment']['processed_items'] ) ) {
			foreach ( $order['walsworth_fulfillment']['processed_items'] as $item ) {
				$items_table_rows .= '
                <tr style="background-color: #fff;">
                    <td style="padding: 12px 14px; color: #212529; border-bottom: 1px solid #f1f3f5; font-size: 13px; font-family: \'Segoe UI\', sans-serif;" align="left">' . esc_html( $item['product'] ) . '</td>
                    <td style="padding: 12px 14px; color: #212529; border-bottom: 1px solid #f1f3f5; font-weight: 600; text-align: center; font-size: 14px; font-family: \'Segoe UI\', sans-serif;" align="center">' . esc_html( $item['quantity'] ) . '</td>
                    <td style="padding: 12px 14px; border-bottom: 1px solid #f1f3f5; text-align: center;" align="center">
                        <span style="display: inline-block; padding: 4px 10px; background-color: #d4edda; color: #155724; border-radius: 4px; font-weight: 600; font-size: 11px; font-family: \'Segoe UI\', sans-serif;">✅ Fulfilled</span>
                    </td>
                </tr>';
			}
		}

		// Add NOT fulfilled items.
		if ( ! empty( $order['walsworth_fulfillment']['not_processed_items'] ) ) {
			foreach ( $order['walsworth_fulfillment']['not_processed_items'] as $item ) {
				$items_table_rows .= '
                <tr style="background-color: #fff;">
                    <td style="padding: 12px 14px; color: #212529; border-bottom: 1px solid #f1f3f5; font-size: 13px; font-family: \'Segoe UI\', sans-serif;" align="left">' . esc_html( $item['product'] ) . '</td>
                    <td style="padding: 12px 14px; color: #212529; border-bottom: 1px solid #f1f3f5; font-weight: 600; text-align: center; font-size: 14px; font-family: \'Segoe UI\', sans-serif;" align="center">' . esc_html( $item['quantity'] ) . '</td>
                    <td style="padding: 12px 14px; border-bottom: 1px solid #f1f3f5; text-align: center;" align="center">
                        <span style="display: inline-block; padding: 4px 10px; background-color: #f8d7da; color: #721c24; border-radius: 4px; font-weight: 600; font-size: 11px; font-family: \'Segoe UI\', sans-serif;">❌ Not Fulfilled</span>
                    </td>
                </tr>';
			}
		}

		// Format order date for display
		$order_date_formatted = date( 'Y-m-d H:i:s', strtotime( $order['order_date'] ) );

		$orders_html .= '
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 30px; border: 2px solid #e8e8e8; border-radius: 8px; overflow: hidden;">
            <tr>
                <td style="background: linear-gradient(to right, #f8f9fa 0%, #e9ecef 100%); padding: 20px 24px; border-bottom: 1px solid #dee2e6;">
                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            <td style="vertical-align: middle;" valign="middle">
                                <h3 style="margin: 0 0 6px 0; color: #264584; font-family: \'Segoe UI\', sans-serif; font-size: 20px; font-weight: 700; line-height: 1.3;">
                                    🛒 <a href="' . esc_url( $order_edit_url ) . '" style="color: #264584; text-decoration: none;">Order #' . esc_html( $order['order_number'] ) . '</a>
                                </h3>
                                <p style="margin: 0; font-size: 13px; color: #6c757d; font-family: \'Segoe UI\', sans-serif;">
                                    <span style="display: inline-block; padding: 3px 10px; background-color: ' . esc_attr( $status_bg_color ) . '; color: ' . esc_attr( $status_text_color ) . '; border-radius: 4px; font-weight: 600; font-size: 11px; letter-spacing: 0.5px; font-family: \'Segoe UI\', sans-serif;">' . esc_html( $status_display ) . '</span>
                                </p>
                            </td>
                            <td style="text-align: right; vertical-align: middle;" align="right" valign="middle">
                                <a href="' . esc_url( $order_edit_url ) . '" style="display: inline-block; padding: 10px 20px; background-color: #264584; color: #fff; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: 600; font-family: \'Segoe UI\', sans-serif;">View Order →</a>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style="padding: 24px; background-color: #fff;">
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 20px;">
                        <tr>
                            <td width="50%" style="padding: 8px 0; font-size: 14px; color: #495057; vertical-align: top; font-family: \'Segoe UI\', sans-serif;" valign="top">
                                <strong style="color: #212529;">👤 Customer:</strong><br>
                                <span style="color: #6c757d;">' . esc_html( $order['customer_name'] ) . '</span>
                            </td>
                            <td width="50%" style="padding: 8px 0; font-size: 14px; color: #495057; vertical-align: top; font-family: \'Segoe UI\', sans-serif;" valign="top">
                                <strong style="color: #212529;">📧 Email:</strong><br>
                                <span style="color: #6c757d;">' . esc_html( $order['customer_email'] ) . '</span>
                            </td>
                        </tr>
                        <tr>
                            <td width="50%" style="padding: 8px 0; font-size: 14px; color: #495057; vertical-align: top; font-family: \'Segoe UI\', sans-serif;" valign="top">
                                <strong style="color: #212529;">📅 Order Date:</strong><br>
                                <span style="color: #6c757d;">' . esc_html( $order_date_formatted ) . '</span>
                            </td>
                            <td width="50%" style="padding: 8px 0; font-size: 14px; color: #495057; vertical-align: top; font-family: \'Segoe UI\', sans-serif;" valign="top">
                                <strong style="color: #212529;">💰 Order Total:</strong><br>
                                <span style="color: #6c757d; font-weight: 600;">$' . esc_html( number_format( $order['order_total'], 2 ) ) . '</span>
                            </td>
                        </tr>
                    </table>
                    
                    <h4 style="margin: 0 0 16px 0; color: #264584; font-family: \'Segoe UI\', sans-serif; font-size: 16px; font-weight: 700;">Order Items</h4>
                    <table cellspacing="0" cellpadding="0" border="0" style="width: 100%; border: 1px solid #dee2e6; border-radius: 6px; overflow: hidden;" width="100%">
                        <thead>
                            <tr style="background: linear-gradient(to right, #f8f9fa 0%, #e9ecef 100%);">
                                <th style="padding: 12px 14px; text-align: left; font-size: 12px; color: #495057; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #dee2e6; font-family: \'Segoe UI\', sans-serif;" align="left">Product</th>
                                <th style="padding: 12px 14px; text-align: center; font-size: 12px; color: #495057; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #dee2e6; width: 70px; font-family: \'Segoe UI\', sans-serif;" align="center">Qty</th>
                                <th style="padding: 12px 14px; text-align: center; font-size: 12px; color: #495057; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #dee2e6; width: 140px; font-family: \'Segoe UI\', sans-serif;" align="center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ' . $items_table_rows . '
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>';
	}

	// Format generated date
	$generated_date = date( 'Y-m-d \a\t g:i A', strtotime( $summary_data['date_generated'] ) );

	// HTML Email Template with inline styles for maximum email client compatibility
	$html = '<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>The Lionheart Foundation - Daily Order Summary</title>
    <style type="text/css">
        @media screen and (max-width: 600px) {
            #header_wrapper { padding: 27px 36px !important; font-size: 24px; }
            #body_content table > tbody > tr > td { padding: 10px !important; }
            #body_content_inner { font-size: 10px !important; }
            .stat-box { width: 100% !important; display: block !important; margin-bottom: 15px !important; }
        }
    </style>
</head>
<body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style="background-color: #f7f7f7; padding: 0; text-align: center; font-family: \'Segoe UI\', sans-serif;" bgcolor="#f7f7f7">
    <table width="100%" id="outer_wrapper" style="background-color: #f7f7f7;" bgcolor="#f7f7f7">
        <tr>
            <td></td>
            <td width="600">
                <div id="wrapper" dir="ltr" style="margin: 0 auto; padding: 70px 0; width: 100%; max-width: 600px; -webkit-text-size-adjust: none;" width="100%">
                    <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="inner_wrapper">
                        <tr>
                            <td align="center" valign="top">
                                <table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_container" style="background-color: #fff; border: 1px solid #dedede; box-shadow: 0 1px 4px rgba(0,0,0,.1); border-radius: 3px;" bgcolor="#fff">
                                    <tr>
                                        <td align="center" valign="top">
                                            <!-- Header -->
                                            <table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_header" style="background: linear-gradient(135deg, #264584 0%, #1a3461 100%); color: #fff; border-bottom: 0; font-weight: bold; line-height: 100%; vertical-align: middle; font-family: \'Segoe UI\', sans-serif; border-radius: 3px 3px 0 0;" bgcolor="#264584">
                                                <tr>
                                                    <td id="header_wrapper" style="padding: 36px 48px; display: block;">
                                                        <h1 style="font-family: \'Segoe UI\', sans-serif; font-size: 28px; font-weight: 600; line-height: 150%; margin: 0; color: #fff; background-color: inherit; text-align: left;" bgcolor="inherit">📋 Daily Mixed Order Summary</h1>
                                                        <p style="margin: 8px 0 0 0; font-size: 13px; color: #fff; opacity: 0.85; font-weight: normal; font-family: \'Segoe UI\', sans-serif;">Generated: ' . esc_html( $generated_date ) . '</p>
                                                    </td>
                                                </tr>
                                            </table>
                                            <!-- End Header -->
                                        </td>
                                    </tr>
                                    
                                    <!-- Alert Banner -->
                                    <tr>
                                        <td style="background: linear-gradient(to right, #fff3cd 0%, #ffeaa7 100%); border-bottom: 3px solid #f39c12; padding: 20px 48px;">
                                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td style="padding: 0;">
                                                        <p style="margin: 0; color: #856404; font-family: \'Segoe UI\', sans-serif; font-size: 14px; line-height: 150%;"><strong>⚠️ Action Required:</strong> Items below require alternative fulfillment.</p>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <td align="center" valign="top">
                                            <!-- Body -->
                                            <table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_body">
                                                <tr>
                                                    <td valign="top" id="body_content" style="background-color: #fff;" bgcolor="#fff">
                                                        <!-- Content -->
                                                        <table border="0" cellpadding="20" cellspacing="0" width="100%">
                                                            <tr>
                                                                <td valign="top" style="padding: 40px 48px 32px;">
                                                                    <div style="color: #636363; font-family: \'Segoe UI\', sans-serif; font-size: 14px; line-height: 150%; text-align: left;" align="left">
                                                                        
                                                                        <!-- Dashboard Stats -->
                                                                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 35px;">
                                                                            <tr>
                                                                                <td class="stat-box" width="48%" style="padding: 20px; background: linear-gradient(135deg, #264584 0%, #3d5a99 100%); border-radius: 8px; text-align: center; vertical-align: middle;" align="center" valign="middle">
                                                                                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                                                                        <tr>
                                                                                            <td style="padding: 0;">
                                                                                                <p style="margin: 0; font-size: 38px; font-weight: 700; color: #fff; line-height: 1.2; font-family: \'Segoe UI\', sans-serif;">' . esc_html( $summary_data['total_orders'] ) . '</p>
                                                                                                <p style="margin: 8px 0 0 0; font-size: 13px; color: rgba(255,255,255,0.9); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500; font-family: \'Segoe UI\', sans-serif;">Orders Requiring<br>Attention</p>
                                                                                            </td>
                                                                                        </tr>
                                                                                    </table>
                                                                                </td>
                                                                                <td width="4%"></td>
                                                                                <td class="stat-box" width="48%" style="padding: 20px; background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); border-radius: 8px; text-align: center; vertical-align: middle;" align="center" valign="middle">
                                                                                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                                                                        <tr>
                                                                                            <td style="padding: 0;">
                                                                                                <p style="margin: 0; font-size: 38px; font-weight: 700; color: #fff; line-height: 1.2; font-family: \'Segoe UI\', sans-serif;">' . esc_html( $total_unprocessed_items ) . '</p>
                                                                                                <p style="margin: 8px 0 0 0; font-size: 13px; color: rgba(255,255,255,0.9); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500; font-family: \'Segoe UI\', sans-serif;">Unprocessed<br>Items</p>
                                                                                            </td>
                                                                                        </tr>
                                                                                    </table>
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                        
                                                                        <!-- Quick Summary Table -->
                                                                        <h2 style="color: #264584; font-family: \'Segoe UI\', sans-serif; font-size: 18px; font-weight: 700; margin: 0 0 16px 0;">📊 Quick Overview</h2>
                                                                        
                                                                        <table cellspacing="0" cellpadding="0" border="0" style="width: 100%; margin-bottom: 35px; border: 2px solid #e8e8e8; border-radius: 8px; overflow: hidden;" width="100%">
                                                                            <thead>
                                                                                <tr style="background: linear-gradient(to right, #f8f9fa 0%, #e9ecef 100%);">
                                                                                    <th style="padding: 14px 16px; text-align: left; font-size: 12px; color: #495057; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #dee2e6; font-family: \'Segoe UI\', sans-serif;" align="left">Order</th>
                                                                                    <th style="padding: 14px 16px; text-align: left; font-size: 12px; color: #495057; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #dee2e6; font-family: \'Segoe UI\', sans-serif;" align="left">Customer</th>
                                                                                    <th style="padding: 14px 16px; text-align: center; font-size: 12px; color: #495057; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #dee2e6; width: 80px; font-family: \'Segoe UI\', sans-serif;" align="center">Status</th>
                                                                                    <th style="padding: 14px 16px; text-align: center; font-size: 12px; color: #495057; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #dee2e6; width: 100px; font-family: \'Segoe UI\', sans-serif;" align="center">Unprocessed</th>
                                                                                    <th style="padding: 14px 16px; text-align: center; font-size: 12px; color: #495057; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #dee2e6; width: 80px; font-family: \'Segoe UI\', sans-serif;" align="center">Action</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                ' . $quick_overview_rows . '
                                                                            </tbody>
                                                                        </table>
                                                                        
                                                                        <!-- Divider -->
                                                                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 30px;">
                                                                            <tr>
                                                                                <td style="border-bottom: 3px solid #264584; padding: 0;"></td>
                                                                            </tr>
                                                                        </table>
                                                                        
                                                                        <h2 style="color: #264584; font-family: \'Segoe UI\', sans-serif; font-size: 18px; font-weight: 700; margin: 0 0 20px 0;">📦 Detailed Order Breakdown</h2>
                                                                        
                                                                        ' . $orders_html . '
                                                                        
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                        <!-- End Content -->
                                                    </td>
                                                </tr>
                                            </table>
                                            <!-- End Body -->
                                        </td>
                                    </tr>
                                    
                                    <!-- Footer -->
                                    <tr>
                                        <td align="center" valign="top">
                                            <table border="0" cellpadding="10" cellspacing="0" width="100%" id="template_footer">
                                                <tr>
                                                    <td valign="top" style="padding: 24px 48px; background-color: #f8f9fa; border-top: 2px solid #e9ecef;">
                                                        <table border="0" cellpadding="10" cellspacing="0" width="100%">
                                                            <tr>
                                                                <td colspan="2" valign="middle" id="credit" style="text-align: center; color: #6c757d; font-family: \'Segoe UI\', sans-serif; font-size: 12px; line-height: 150%;" align="center">
                                                                    <p style="margin: 0 0 8px 0; font-family: \'Segoe UI\', sans-serif;">The Lionheart Foundation</p>
                                                                    <p style="margin: 0; font-size: 11px; color: #adb5bd; font-family: \'Segoe UI\', sans-serif;">This is an automated notification. Please do not reply to this email.</p>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <!-- End Footer -->
                                </table>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
            <td></td>
        </tr>
    </table>
</body>
</html>';

	return $html;
}
