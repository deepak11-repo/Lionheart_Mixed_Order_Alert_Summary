# WooCommerce Mixed Order Email Notifier

A WordPress plugin that sends instant email alerts when WooCommerce orders involve mixed fulfillment scenarios (items shipping from different locations or at different times).

## Features

- **Instant Email Alerts**: Immediate notifications when mixed fulfillment orders are detected
- **Daily Summary Reports**: Automated daily email summaries (runs at 7:45 PM UTC)
- **Walsworth Integration**: Monitors Walsworth fulfillment notes
- **REST API**: Endpoint for retrieving administrator emails

## Requirements

- WordPress 5.0+
- WooCommerce 3.0+
- PHP 7.2+

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin via WordPress Admin → Plugins
3. Ensure WooCommerce is installed and active

## Configuration

### Email Recipients

Configure email recipients by editing the following files:

- **Instant Alerts**: `includes/class-order-note-email-alert.php` (line 37)
- **Daily Summary**: `includes/class-daily-summary-status-cron.php` (line 32)

Set `$send_to_admins = true` to include all WordPress administrators.

### Daily Summary

The cron job automatically checks orders with these statuses:
- Processing
- Partially Shipped
- Pending Payment Partially Shipped

**Manual Testing**: Add `?trigger_status_summary=1` to any admin page URL (requires admin permissions)

## Usage

The plugin operates automatically once activated:

1. Monitors order notes for Walsworth fulfillment patterns
2. Sends instant email alerts when mixed fulfillment is detected
3. Generates daily summary reports at 7:45 PM UTC

## REST API

**Endpoint**: `/wp-json/wc-order-note-api/v1/administrators`  
**Method**: GET  
**Auth**: Requires `manage_woocommerce` or `manage_options` capability

```json
{
  "success": true,
  "emails": ["admin@example.com"],
  "total": 1
}
```

## File Structure

```
woocommerce-order-alerts/
├── woocommerce-mixed-order-notifier.php  # Main plugin file
├── includes/
│   ├── class-order-note-email-alert.php  # Main alert handler
│   └── class-daily-summary-status-cron.php # Daily summary cron
├── templates/
│   ├── email-alert-template.php          # Instant alert template
│   └── email-summary-template.php        # Daily summary template
├── README.md
└── .gitignore
```

## Troubleshooting

**Emails Not Sending**
- Verify WordPress email configuration
- Check recipient settings in class files
- Review spam folder and error logs

**Daily Summary Not Running**
- Check WordPress cron events (use "WP Crontrol" plugin)
- Verify cron hook: `wc_daily_mixed_order_summary_status`
- Test manually with trigger URL

**No Orders in Summary**
- Ensure orders have target statuses (Processing, Partially Shipped, or Pending Payment Partially Shipped)
- Verify orders contain Walsworth mixed fulfillment notes
- Check note format includes both "Walsworth processed:" and "Walsworth DID NOT process:" patterns

## Development

**Main Classes**
- `WooCommerce_OrderNote_Email_Alert` - Order note monitoring and instant alerts
- `WC_Daily_Summary_Status_Cron` - Daily summary generation and scheduling

**Key Hooks**
- `woocommerce_order_note_added` - Order note detection
- `rest_api_init` - REST API registration
- `cron_schedules` - Custom cron schedule
- `init` - Cron job scheduling

## Version

**Current Version**: 3.0

**Changelog**
- Status-based daily summary implementation
- Enhanced email templates
- REST API support
- Reorganized file structure (includes/ and templates/ directories)

---

Developed by **Team WisdmLabs** | [Plugin Repository](https://github.com/wisdmlabs/woocommerce-mixed-order-notifier)
