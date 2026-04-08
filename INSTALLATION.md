# DirectCryptoPay WordPress Plugin — Installation Guide

## Requirements

- WordPress 5.8+
- PHP 7.4+
- WooCommerce 7.0+ (for WooCommerce gateway only)
- A DirectCryptoPay account ([create one here](https://directcryptopay.com/auth/signin))

## Step 1: Download

Download the plugin ZIP from:
- [GitHub Releases](https://github.com/directcryptopay/directcryptopay-wordpress/releases)
- Or your [DirectCryptoPay Dashboard](https://directcryptopay.com/dashboard)

## Step 2: Install

### Option A: WordPress Admin (Recommended)

1. Go to **Plugins > Add New > Upload Plugin**
2. Select the `directcryptopay-wordpress.zip` file
3. Click **Install Now**
4. Click **Activate**

### Option B: Manual / FTP

1. Extract `directcryptopay-wordpress.zip`
2. Upload the `wordpress-plugin` folder to `/wp-content/plugins/`
3. Rename it to `directcryptopay`
4. Go to **Plugins** and activate **DirectCryptoPay**

## Step 3: Configure

1. In the WordPress sidebar, click **DirectCryptoPay**
2. Follow the 4-step wizard:
   - **Step 1:** Create an account at [directcryptopay.com](https://directcryptopay.com/auth/signin)
   - **Step 2:** Go to Dashboard > Integrations and create a new integration
   - **Step 3:** Copy your **Integration ID** (starts with `int_`)
   - **Step 4:** Paste it in the plugin settings and save

## Step 4: Choose Your Integration Method

### Shortcode (Any WordPress site)

Add to any page, post, or widget:

```
[dcp_pay amount="10" label="Pay with Crypto"]
```

Parameters:
- `amount` — Amount in USD (required)
- `label` — Button text (default: "Pay with Crypto")
- `currency` — Token: ETH, USDC, or USDT (default: ETH)
- `success_url` — Custom redirect after payment
- `error_url` — Custom redirect on error

### Gutenberg Block (Visual Editor)

1. Click **+** to add a block
2. Search for **DirectCryptoPay Payment Button**
3. Configure and publish

### WooCommerce Gateway (E-commerce)

1. Go to **WooCommerce > Settings > Payments**
2. Enable **DirectCryptoPay** and click **Manage**
3. Configure:
   - **Title:** "Pay with Crypto" (shown to customers)
   - **Description:** Message at checkout
   - **Default Currency:** ETH, USDC, or USDT
   - **Webhook Secret:** (optional) From your DCP Dashboard integration
4. Copy the **Webhook URL** displayed in settings to your DCP Dashboard

**Checkout flow:** Customer selects "Pay with Crypto" > Widget opens > Customer connects wallet and pays > Order auto-updated to "Processing"

## Step 5: Test

1. Set your integration to **Testnet** in the DCP Dashboard
2. Get test tokens:
   - Sepolia ETH: [sepoliafaucet.com](https://sepoliafaucet.com)
   - Sepolia USDC: [faucet.circle.com](https://faucet.circle.com)
3. Place a test order and pay with test tokens
4. Verify the order updates in WooCommerce

## Step 6: Go Live

1. Create a **mainnet** integration in your DCP Dashboard
2. Update the Integration ID in plugin settings
3. Place a small real payment to verify
4. You're live!

## Troubleshooting

**Widget not appearing:** Clear WordPress cache plugins, try incognito mode, check browser console.

**Order not updating:** Verify your site is publicly accessible (webhooks can't reach localhost). Check WooCommerce order notes.

**Plugin conflicts:** Temporarily disable other plugins to isolate. Switch to a default theme to rule out theme issues.

## Support

- Docs: [docs.directcryptopay.com](https://docs.directcryptopay.com)
- Dashboard: [directcryptopay.com/dashboard](https://directcryptopay.com/dashboard)
- Email: support@directcryptopay.com
