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
    
    // Build processed items HTML
    $processed_items_html = '';
    foreach ($fulfillment['processed_items'] as $item) {
        $processed_items_html .= '<div class="item">';
        $processed_items_html .= '<span class="qty">Qty ' . esc_html($item['quantity']) . '</span> × ';
        $processed_items_html .= '<span class="product">' . esc_html($item['product']) . '</span>';
        $processed_items_html .= '</div>';
    }
    
    // Build NOT processed items HTML
    $not_processed_items_html = '';
    foreach ($fulfillment['not_processed_items'] as $item) {
        $not_processed_items_html .= '<div class="item">';
        $not_processed_items_html .= '<span class="qty">Qty ' . esc_html($item['quantity']) . '</span> × ';
        $not_processed_items_html .= '<span class="product">' . esc_html($item['product']) . '</span>';
        $not_processed_items_html .= '</div>';
    }
    
    // HTML Email Template
    $html = '<!DOCTYPE html>
<html>
<head>
  <style>
    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
    .header { background-color: #d32f2f; color: white; padding: 20px; border-radius: 5px 5px 0 0; }
    .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
    .section { margin-bottom: 20px; }
    .section h3 { color: #d32f2f; margin-bottom: 10px; border-bottom: 2px solid #d32f2f; padding-bottom: 5px; }
    .info-box { background-color: white; padding: 15px; border-left: 4px solid #2196F3; margin-bottom: 15px; }
    .processed { background-color: #e8f5e9; padding: 15px; border-left: 4px solid #4caf50; margin-bottom: 15px; }
    .not-processed { background-color: #ffebee; padding: 15px; border-left: 4px solid #f44336; margin-bottom: 15px; }
    .item { padding: 8px 0; border-bottom: 1px solid #eee; }
    .item:last-child { border-bottom: none; }
    .qty { font-weight: bold; color: #2196F3; }
    .product { color: #555; }
    .summary { background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin-top: 20px; }
    .footer { text-align: center; padding: 20px; color: #777; font-size: 12px; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h2 style="margin: 0;">⚠️ Mixed Order Fulfillment Alert</h2>
      <p style="margin: 10px 0 0 0;">Walsworth has partially processed this order</p>
    </div>
    
    <div class="content">
      <div class="section">
        <h3>📦 Order Information</h3>
        <div class="info-box">
          <p><strong>Order Number:</strong> ' . esc_html($order['order_number']) . '</p>
          <p><strong>Order Status:</strong> ' . esc_html($order['order_status']) . '</p>
          <p><strong>Order Date:</strong> ' . esc_html($order['order_date']) . '</p>
          <p><strong>Customer:</strong> ' . esc_html($order['customer_name']) . '</p>
          <p><strong>Email:</strong> ' . esc_html($order['customer_email']) . '</p>
          <p><strong>Processing Time:</strong> ' . esc_html($data['timestamp']) . '</p>
        </div>
      </div>

      <div class="section">
        <h3>✅ Items Processed by Walsworth</h3>
        <div class="processed">
          ' . $processed_items_html . '
          <p style="margin-top: 15px; font-weight: bold; color: #4caf50;">Total Processed: ' . esc_html($fulfillment['total_processed_qty']) . ' items</p>
        </div>
      </div>

      <div class="section">
        <h3>❌ Items NOT Processed by Walsworth</h3>
        <div class="not-processed">
          ' . $not_processed_items_html . '
          <p style="margin-top: 15px; font-weight: bold; color: #f44336;">Total NOT Processed: ' . esc_html($fulfillment['total_not_processed_qty']) . ' items</p>
        </div>
      </div>

      <div class="summary">
        <h3 style="margin-top: 0; color: #ff9800;">⚡ Action Required</h3>
        <p><strong>' . esc_html($fulfillment['total_not_processed_qty']) . '</strong> items from this order need alternative fulfillment.</p>
        <p style="margin-bottom: 0;">Please review and arrange fulfillment for the unprocessed items.</p>
      </div>

      <div class="section">
        <h3>📝 Order Note</h3>
        <div class="info-box">
          <p><strong>Added by:</strong> ' . esc_html($note['note_author']) . '</p>
          <p><strong>Date:</strong> ' . esc_html($note['note_date']) . '</p>
          <p style="white-space: pre-line; font-family: monospace; background-color: #f5f5f5; padding: 10px; border-radius: 3px;">' . esc_html($note['note_content']) . '</p>
        </div>
      </div>
    </div>
    
    <div class="footer">
      <p>This is an automated notification from Lion Heart Order Management System</p>
      <p>Event: ' . esc_html($data['event']) . '</p>
    </div>
  </div>
</body>
</html>';

    return $html;
}

