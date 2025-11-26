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
if (!defined('ABSPATH')) {
    exit;
}

function get_status_cron_email_template($summary_data) {
    $orders_html = '';
    $total_unprocessed_items = 0;
    
    foreach ($summary_data['orders'] as $index => $order) {
        $order_number = $index + 1;
        $total_unprocessed_items += $order['walsworth_fulfillment']['total_not_processed_qty'];
        
        // Build processed items HTML
        $processed_items_html = '';
        if (!empty($order['walsworth_fulfillment']['processed_items'])) {
            foreach ($order['walsworth_fulfillment']['processed_items'] as $item) {
                $processed_items_html .= '<tr style="border-bottom: 1px solid #e0e0e0;">';
                $processed_items_html .= '<td style="padding: 8px 12px; font-weight: 600; color: #1976d2; font-family: \'Segoe UI\', sans-serif;">Qty ' . esc_html($item['quantity']) . '</td>';
                $processed_items_html .= '<td style="padding: 8px 12px; color: #424242; font-family: \'Segoe UI\', sans-serif;">' . esc_html($item['product']) . '</td>';
                $processed_items_html .= '</tr>';
            }
        } else {
            $processed_items_html = '<tr><td colspan="2" style="padding: 12px; color: #757575; font-style: italic; text-align: center; font-family: \'Segoe UI\', sans-serif;">No items fulfilled</td></tr>';
        }
        
        // Build NOT fulfilled items HTML
        $not_processed_items_html = '';
        if (!empty($order['walsworth_fulfillment']['not_processed_items'])) {
            foreach ($order['walsworth_fulfillment']['not_processed_items'] as $item) {
                $not_processed_items_html .= '<tr style="border-bottom: 1px solid #e0e0e0;">';
                $not_processed_items_html .= '<td style="padding: 8px 12px; font-weight: 600; color: #d32f2f; font-family: \'Segoe UI\', sans-serif;">Qty ' . esc_html($item['quantity']) . '</td>';
                $not_processed_items_html .= '<td style="padding: 8px 12px; color: #424242; font-family: \'Segoe UI\', sans-serif;">' . esc_html($item['product']) . '</td>';
                $not_processed_items_html .= '</tr>';
            }
        } else {
            $not_processed_items_html = '<tr><td colspan="2" style="padding: 12px; color: #757575; font-style: italic; text-align: center; font-family: \'Segoe UI\', sans-serif;">No items not fulfilled</td></tr>';
        }
        
        $order_edit_url = admin_url('post.php?post=' . $order['order_id'] . '&action=edit');
        
        // Format status display with custom colors
        $status_display = ucwords(str_replace('-', ' ', $order['order_status']));
        $status_color = '#424242'; // Default color
        if ($order['order_status'] === 'partially-shipped') {
            $status_color = 'rgb(217, 169, 68)'; // #d9a944
        } elseif ($order['order_status'] === 'pending-payment-partially-shipped') {
            $status_color = 'rgb(234, 207, 134)'; // #eacf86
        } elseif ($order['order_status'] === 'processing') {
            $status_color = 'rgb(198, 225, 198)'; // #c6e1c6
        }
        
        $orders_html .= '
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 30px; background-color: #ffffff; border: 1px solid #e0e0e0; border-radius: 4px; overflow: hidden;">
            <tr>
                <td style="padding: 20px; background-color: #f5f5f5; border-bottom: 2px solid #d32f2f;">
                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            <td style="padding: 0;">
                                <h2 style="margin: 0; font-size: 18px; font-weight: 600; color: #d32f2f; line-height: 1.4; font-family: \'Segoe UI\', sans-serif;">Order #' . esc_html($order['order_number']) . '</h2>
                                <p style="margin: 8px 0 0 0; font-size: 13px; color: #666666; font-family: \'Segoe UI\', sans-serif;">Status: <strong style="color: ' . esc_attr($status_color) . ';">' . esc_html($status_display) . '</strong></p>
                            </td>
                            <td align="right" style="padding: 0; vertical-align: top;">
                                <a href="' . esc_url($order_edit_url) . '" style="display: inline-block; padding: 8px 16px; background-color: #1976d2; color: #ffffff; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: 500; font-family: \'Segoe UI\', sans-serif;">View Order</a>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style="padding: 20px;">
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 20px;">
                        <tr>
                            <td style="padding: 6px 0; font-size: 14px; color: #666666; font-family: \'Segoe UI\', sans-serif;"><strong style="color: #424242;">Customer:</strong> ' . esc_html($order['customer_name']) . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 6px 0; font-size: 14px; color: #666666; font-family: \'Segoe UI\', sans-serif;"><strong style="color: #424242;">Email:</strong> ' . esc_html($order['customer_email']) . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 6px 0; font-size: 14px; color: #666666; font-family: \'Segoe UI\', sans-serif;"><strong style="color: #424242;">Order Date:</strong> ' . esc_html($order['order_date']) . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 6px 0; font-size: 14px; color: #666666; font-family: \'Segoe UI\', sans-serif;"><strong style="color: #424242;">Order Total:</strong> $' . esc_html(number_format($order['order_total'], 2)) . '</td>
                        </tr>
                    </table>
                    
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 20px;">
                        <tr>
                            <td width="48%" valign="top" style="padding-right: 2%;">
                                <div style="background-color: #e8f5e9; border-left: 4px solid #4caf50; padding: 15px; border-radius: 4px;">
                                    <h3 style="margin: 0 0 12px 0; font-size: 15px; font-weight: 600; color: #2e7d32; font-family: \'Segoe UI\', sans-serif;">Fulfilled by Walsworth</h3>
                                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 3px;">
                                        ' . $processed_items_html . '
                                    </table>
                                    <p style="margin: 12px 0 0 0; font-size: 13px; font-weight: 600; color: #2e7d32; text-align: right; font-family: \'Segoe UI\', sans-serif;">Total: ' . esc_html($order['walsworth_fulfillment']['total_processed_qty']) . ' items</p>
                                </div>
                            </td>
                            <td width="48%" valign="top" style="padding-left: 2%;">
                                <div style="background-color: #ffebee; border-left: 4px solid #d32f2f; padding: 15px; border-radius: 4px;">
                                    <h3 style="margin: 0 0 12px 0; font-size: 15px; font-weight: 600; color: #c62828; font-family: \'Segoe UI\', sans-serif;">NOT Fulfilled by Walsworth</h3>
                                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 3px;">
                                        ' . $not_processed_items_html . '
                                    </table>
                                    <p style="margin: 12px 0 0 0; font-size: 13px; font-weight: 600; color: #c62828; text-align: right; font-family: \'Segoe UI\', sans-serif;">Total: ' . esc_html($order['walsworth_fulfillment']['total_not_processed_qty']) . ' items</p>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>';
    }
    
    // HTML Email Template with inline styles for maximum email client compatibility
    $html = '<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Daily Mixed Order Summary (Status-Based)</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f5f5f5; font-family: \'Segoe UI\', sans-serif; font-size: 14px; line-height: 1.6; color: #424242;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f5f5f5; padding: 20px 0;">
        <tr>
            <td align="center" style="padding: 0;">
                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width: 800px; background-color: #ffffff; border-radius: 4px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 30px; background-color: #d32f2f; text-align: center;">
                            <h1 style="margin: 0 0 10px 0; font-size: 24px; font-weight: 600; color: #ffffff; line-height: 1.3; font-family: \'Segoe UI\', sans-serif;">Daily Mixed Order Summary</h1>
                            <p style="margin: 0; font-size: 14px; color: #ffffff; opacity: 0.95; font-family: \'Segoe UI\', sans-serif;">Status-Based Report | Generated: ' . esc_html($summary_data['date_generated']) . '</p>
                        </td>
                    </tr>
                    
                    <!-- Summary Statistics -->
                    <tr>
                        <td style="padding: 25px 30px; background-color: #fff3cd; border-bottom: 1px solid #e0e0e0;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td width="50%" align="center" style="padding: 10px; border-right: 1px solid #e0e0e0;">
                                        <div style="font-size: 36px; font-weight: 700; color: #d32f2f; line-height: 1.2; font-family: \'Segoe UI\', sans-serif;">' . esc_html($summary_data['total_orders']) . '</div>
                                        <div style="font-size: 13px; color: #666666; margin-top: 5px; text-transform: uppercase; letter-spacing: 0.5px; font-family: \'Segoe UI\', sans-serif;">Orders Requiring Attention</div>
                                    </td>
                                    <td width="50%" align="center" style="padding: 10px;">
                                        <div style="font-size: 36px; font-weight: 700; color: #d32f2f; line-height: 1.2; font-family: \'Segoe UI\', sans-serif;">' . esc_html($total_unprocessed_items) . '</div>
                                        <div style="font-size: 13px; color: #666666; margin-top: 5px; text-transform: uppercase; letter-spacing: 0.5px; font-family: \'Segoe UI\', sans-serif;">Unprocessed Items</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Action Required Notice -->
                    <tr>
                        <td style="padding: 20px 30px; background-color: #ffebee; border-bottom: 1px solid #e0e0e0;">
                            <h3 style="margin: 0 0 10px 0; font-size: 16px; font-weight: 600; color: #c62828; font-family: \'Segoe UI\', sans-serif;">Action Required</h3>
                            <p style="margin: 0; font-size: 14px; color: #424242; line-height: 1.6; font-family: \'Segoe UI\', sans-serif;">The following orders have status <strong>"Processing"</strong>, <strong>"Partially Shipped"</strong>, or <strong>"Pending Payment Partially Shipped"</strong> with Walsworth mixed fulfillment notes. Items that were NOT fulfilled by Walsworth require alternative fulfillment.</p>
                        </td>
                    </tr>
                    
                    <!-- Orders List -->
                    <tr>
                        <td style="padding: 30px;">
                            ' . $orders_html . '
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px 30px; background-color: #f9f9f9; border-top: 1px solid #e0e0e0; text-align: center;">
                            <p style="margin: 0 0 8px 0; font-size: 13px; font-weight: 600; color: #424242; font-family: \'Segoe UI\', sans-serif;">The Lionheart Foundation</p>
                            <p style="margin: 0 0 8px 0; font-size: 12px; color: #757575; font-family: \'Segoe UI\', sans-serif;">Status-Based Daily Summary Report</p>
                            <p style="margin: 0; font-size: 12px; color: #757575; font-family: \'Segoe UI\', sans-serif;">This is an automated report. Please review and take necessary action on the listed orders.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';

    return $html;
}

