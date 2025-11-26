<?php
/**
 * Email Template for WooCommerce Order Note Alerts
 * 
 * This file contains the HTML email template for mixed order fulfillment alerts.
 * 
 * @param array $data Array containing order, note, and fulfillment data
 * @return string HTML email content
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

function get_order_note_email_template($data) {
    $order = $data['order'];
    $note = $data['note'];
    $fulfillment = $data['walsworth_fulfillment'];
    
    // Build fulfilled items HTML
    $processed_items_html = '';
    if (!empty($fulfillment['processed_items'])) {
        foreach ($fulfillment['processed_items'] as $item) {
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
    if (!empty($fulfillment['not_processed_items'])) {
        foreach ($fulfillment['not_processed_items'] as $item) {
            $not_processed_items_html .= '<tr style="border-bottom: 1px solid #e0e0e0;">';
            $not_processed_items_html .= '<td style="padding: 8px 12px; font-weight: 600; color: #d32f2f; font-family: \'Segoe UI\', sans-serif;">Qty ' . esc_html($item['quantity']) . '</td>';
            $not_processed_items_html .= '<td style="padding: 8px 12px; color: #424242; font-family: \'Segoe UI\', sans-serif;">' . esc_html($item['product']) . '</td>';
            $not_processed_items_html .= '</tr>';
        }
    } else {
        $not_processed_items_html = '<tr><td colspan="2" style="padding: 12px; color: #757575; font-style: italic; text-align: center; font-family: \'Segoe UI\', sans-serif;">No items not fulfilled</td></tr>';
    }
    
    // Format status display with custom colors (matching summary template)
    $status_display = ucwords(str_replace('-', ' ', $order['order_status']));
    $status_color = '#424242'; // Default color
    if ($order['order_status'] === 'partially-shipped') {
        $status_color = 'rgb(217, 169, 68)'; // #d9a944
    } elseif ($order['order_status'] === 'pending-payment-partially-shipped') {
        $status_color = 'rgb(234, 207, 134)'; // #eacf86
    } elseif ($order['order_status'] === 'processing') {
        $status_color = 'rgb(198, 225, 198)'; // #c6e1c6
    }
    
    $order_edit_url = admin_url('post.php?post=' . $order['order_id'] . '&action=edit');
    
    // HTML Email Template with inline styles for maximum email client compatibility
    $html = '<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Mixed Order Fulfillment Alert</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f5f5f5; font-family: \'Segoe UI\', sans-serif; font-size: 14px; line-height: 1.6; color: #424242;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f5f5f5; padding: 20px 0;">
        <tr>
            <td align="center" style="padding: 0;">
                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width: 800px; background-color: #ffffff; border-radius: 4px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 30px; background-color: #d32f2f; text-align: center;">
                            <h1 style="margin: 0 0 10px 0; font-size: 24px; font-weight: 600; color: #ffffff; line-height: 1.3; font-family: \'Segoe UI\', sans-serif;">⚠️ Mixed Order Fulfillment Alert</h1>
                            <p style="margin: 0; font-size: 14px; color: #ffffff; opacity: 0.95; font-family: \'Segoe UI\', sans-serif;">Walsworth has partially fulfilled this order</p>
                        </td>
                    </tr>
                    
                    <!-- Order Information -->
                    <tr>
                        <td style="padding: 30px;">
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
                                                <td style="padding: 6px 0; font-size: 14px; color: #666666; font-family: \'Segoe UI\', sans-serif;"><strong style="color: #424242;">Processing Time:</strong> ' . esc_html($data['timestamp']) . '</td>
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
                                                        <p style="margin: 12px 0 0 0; font-size: 13px; font-weight: 600; color: #2e7d32; text-align: right; font-family: \'Segoe UI\', sans-serif;">Total: ' . esc_html($fulfillment['total_processed_qty']) . ' items</p>
                                                    </div>
                                                </td>
                                                <td width="48%" valign="top" style="padding-left: 2%;">
                                                    <div style="background-color: #ffebee; border-left: 4px solid #d32f2f; padding: 15px; border-radius: 4px;">
                                                        <h3 style="margin: 0 0 12px 0; font-size: 15px; font-weight: 600; color: #c62828; font-family: \'Segoe UI\', sans-serif;">NOT Fulfilled by Walsworth</h3>
                                                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 3px;">
                                                            ' . $not_processed_items_html . '
                                                        </table>
                                                        <p style="margin: 12px 0 0 0; font-size: 13px; font-weight: 600; color: #c62828; text-align: right; font-family: \'Segoe UI\', sans-serif;">Total: ' . esc_html($fulfillment['total_not_processed_qty']) . ' items</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Action Required Notice -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 30px; background-color: #ffebee; border-left: 4px solid #d32f2f; border-radius: 4px; overflow: hidden;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <h3 style="margin: 0 0 10px 0; font-size: 16px; font-weight: 600; color: #c62828; font-family: \'Segoe UI\', sans-serif;">⚡ Action Required</h3>
                                        <p style="margin: 0; font-size: 14px; color: #424242; line-height: 1.6; font-family: \'Segoe UI\', sans-serif;"><strong>' . esc_html($fulfillment['total_not_processed_qty']) . '</strong> items from this order need alternative fulfillment. Please review and arrange fulfillment for the unfulfilled items.</p>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Order Note -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border: 1px solid #e0e0e0; border-radius: 4px; overflow: hidden;">
                                <tr>
                                    <td style="padding: 20px; background-color: #f5f5f5; border-bottom: 2px solid #1976d2;">
                                        <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #1976d2; font-family: \'Segoe UI\', sans-serif;">📝 Order Note</h3>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 20px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 15px;">
                                            <tr>
                                                <td style="padding: 6px 0; font-size: 14px; color: #666666; font-family: \'Segoe UI\', sans-serif;"><strong style="color: #424242;">Added by:</strong> ' . esc_html($note['note_author']) . '</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 6px 0; font-size: 14px; color: #666666; font-family: \'Segoe UI\', sans-serif;"><strong style="color: #424242;">Date:</strong> ' . esc_html($note['note_date']) . '</td>
                                            </tr>
                                        </table>
                                        <div style="background-color: #f5f5f5; padding: 15px; border-radius: 4px; border-left: 4px solid #1976d2;">
                                            <p style="margin: 0; white-space: pre-line; font-family: \'Segoe UI\', monospace; font-size: 13px; color: #424242; line-height: 1.6;">' . esc_html($note['note_content']) . '</p>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px 30px; background-color: #f9f9f9; border-top: 1px solid #e0e0e0; text-align: center;">
                            <p style="margin: 0 0 8px 0; font-size: 13px; font-weight: 600; color: #424242; font-family: \'Segoe UI\', sans-serif;">The Lionheart Foundation</p>
                            <p style="margin: 0 0 8px 0; font-size: 12px; color: #757575; font-family: \'Segoe UI\', sans-serif;">This is an automated notification</p>
                            <p style="margin: 0; font-size: 12px; color: #757575; font-family: \'Segoe UI\', sans-serif;">Event: ' . esc_html($data['event']) . '</p>
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
