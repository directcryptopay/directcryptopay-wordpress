# DirectCryptoPay WordPress Plugin

Accept crypto payments directly on your WordPress site. Web3, non-custodial, no middlemen.

## Features

- Accept cryptocurrency payments (ETH, USDC, USDT and more)
- Non-custodial — payments go directly to your wallet
- Multi-chain support (Ethereum, Polygon, BNB Chain, Base, Arbitrum, Optimism)
- 3 integration methods: Shortcode, Gutenberg Block, WooCommerce Gateway
- Smart chain/token selector with real-time wallet balances
- HMAC-SHA256 webhook verification
- Testnet and mainnet environments
- Mobile-friendly payment widget

## Requirements

- WordPress 5.8+
- PHP 7.4+
- WooCommerce 7.0+ (for WooCommerce gateway only)

## Installation

1. Download `directcryptopay-wordpress.zip` from the [Releases](https://github.com/directcryptopay/directcryptopay-wordpress/releases)
2. In WordPress admin, go to **Plugins > Add New > Upload Plugin**
3. Upload the ZIP file and click **Install Now**
4. Click **Activate**
5. Go to **DirectCryptoPay** in the sidebar and enter your Integration ID

## Configuration

1. Log in to your [DirectCryptoPay Dashboard](https://directcryptopay.com/dashboard)
2. Go to **Integrations** and copy your Integration ID
3. In WordPress, go to **DirectCryptoPay** settings
4. Paste your Integration ID and save

## Usage

### Method 1: Shortcode

```
[dcp_pay amount="10" label="Pay with Crypto"]
```

**Parameters:**

| Parameter | Required | Default | Description |
|-----------|----------|---------|-------------|
| `amount` | Yes | `10` | Amount in USD |
| `label` | No | `Pay with Crypto` | Button text |
| `currency` | No | `ETH` | Token symbol (ETH, USDC, USDT) |
| `success_url` | No | — | Redirect URL after successful payment |
| `error_url` | No | — | Redirect URL if payment fails |

**Examples:**

```
[dcp_pay amount="5" label="Donate" currency="ETH"]
[dcp_pay amount="25" label="Buy Premium" currency="USDC" success_url="https://yoursite.com/thank-you"]
[dcp_pay amount="99" label="Subscribe" success_url="https://yoursite.com/welcome" error_url="https://yoursite.com/error"]
```

The success page receives URL parameters: `tx_hash`, `intent_id`, `amount`, `currency`, `status`.

### Method 2: Gutenberg Block

1. In the page/post editor, click **+** to add a block
2. Search for **DirectCryptoPay Payment Button**
3. Configure amount, label, and currency in block settings
4. Publish your page

### Method 3: WooCommerce Gateway

1. Ensure WooCommerce is installed and active
2. Go to **WooCommerce > Settings > Payments**
3. Enable **DirectCryptoPay** and click **Manage**
4. Configure title, description, and default currency
5. (Optional) Enter your Webhook Secret for server-side verification
6. Copy the displayed Webhook URL to your DCP Dashboard integration settings

**Checkout flow:**
Customer selects "Pay with Crypto" > Order created > Widget opens > Customer pays > Order auto-updated to "Processing" > Redirect to thank-you page

**Webhook verification (optional but recommended):**
The plugin supports HMAC-SHA256 webhook signature verification. When configured, the DCP backend sends a `payment.succeeded` webhook as server-side backup — the order is updated even if the customer closes their browser during payment.

## Environment Detection

The plugin auto-detects the environment based on your WordPress site URL:

| Site URL contains | Environment | API URL |
|-------------------|-------------|---------|
| `localhost` / `127.0.0.1` | Local | `http://localhost:4001` |
| `test.` / `staging.` / `dev.` | Preview | `https://preview-api.directcryptopay.com` |
| Everything else | Production | `https://api.directcryptopay.com` |

Override manually by adding to `wp-config.php`:
```php
define('DCP_ENV', 'production'); // or 'test' or 'local'
```

## Changelog

### 1.5.0
- WooCommerce: real-time payment status polling (polls backend every 2s for on-chain confirmation)
- WooCommerce: full-screen payment overlay with animated progress states (spinner → checkmark)
- WooCommerce: proper handling of `submitted` status for integration payments
- Shortcode: handle both `submitted` and `confirmed` status as success
- Improved error recovery (Try Again button on wallet rejection)

### 1.4.0
- Fixed script enqueue bug (inline init script was not attaching)
- Added HMAC-SHA256 webhook signature verification
- Added WordPress nonce to AJAX order confirmation
- WooCommerce webhook handler: supports all 6 DCP events (succeeded, failed, expired, processing, pending, late_confirmation)
- WooCommerce: order metadata includes `intent_id`, `chain_id`, `currency`
- WooCommerce: passes `order_id` and `order_key` in payment metadata for webhook matching
- Cleaned up debug console.log statements

### 1.3.0
- Added custom success/error page redirects (`success_url`, `error_url`)
- Added multi-currency shortcode parameter
- WooCommerce gateway: multi-chain smart selector

### 1.2.0
- Added WooCommerce payment gateway
- Added Gutenberg block

### 1.0.0
- Initial release with shortcode support

## Support

- Documentation: https://docs.directcryptopay.com
- Dashboard: https://directcryptopay.com/dashboard
- Support: support@directcryptopay.com

## License

GPLv2 or later
