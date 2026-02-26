# DirectCryptoPay - WordPress & WooCommerce Plugin

Accept cryptocurrency payments directly to your wallet on any WordPress site. Non-custodial, multi-chain, zero platform fees on testnet.

## Features

- **Non-custodial** - Payments go directly to your wallet. No intermediary holds your funds.
- **Multi-chain** - Supports Ethereum, Polygon, BSC, Arbitrum, Optimism, and Base.
- **Multiple tokens** - Accept ETH, USDC, USDT, BNB, MATIC, and more.
- **WooCommerce integration** - Full payment gateway for e-commerce stores.
- **Gutenberg block** - Drag-and-drop payment button in the WordPress editor.
- **Shortcode** - Add payment buttons anywhere with `[dcp_pay]`.
- **Setup wizard** - 4-step guided configuration in the WordPress admin.
- **Testnet support** - Test payments on Sepolia, Amoy, BSC Testnet before going live.

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- A DirectCryptoPay account ([sign up](https://app.directcryptopay.com))

## Installation

### From ZIP file

1. Download the latest release from [Releases](https://github.com/directcryptopay/directcryptopay-wordpress/releases)
2. In WordPress admin, go to **Plugins > Add New > Upload Plugin**
3. Upload the ZIP file and click **Install Now**
4. Click **Activate Plugin**

### Manual installation

1. Clone this repository:
   ```bash
   git clone https://github.com/directcryptopay/directcryptopay-wordpress.git
   ```
2. Copy the folder to `wp-content/plugins/directcryptopay/`
3. Activate the plugin in **Plugins > Installed Plugins**

## Configuration

After activation, navigate to **Settings > DirectCryptoPay** to access the setup wizard:

1. **Connect** - Enter your Integration ID from the DirectCryptoPay dashboard
2. **Environment** - Select Testnet (for testing) or Mainnet (for production)
3. **Display** - Configure button style and placement
4. **Test** - Verify the integration works

### Getting your Integration ID

1. Log in to your [DirectCryptoPay Dashboard](https://app.directcryptopay.com/dashboard)
2. Go to **Integrations** and create a new integration
3. Copy the Integration ID

## Usage

### Shortcode

Add a payment button anywhere in your posts or pages:

```
[dcp_pay amount="10" label="Pay with Crypto" currency="USD"]
```

**Parameters:**

| Parameter | Default | Description |
|-----------|---------|-------------|
| `amount` | `10` | Payment amount |
| `label` | `Pay with Crypto` | Button text |
| `currency` | `USD` | Currency code (USD, ETH, USDC, USDT) |
| `success_url` | - | Redirect URL after successful payment |
| `error_url` | - | Redirect URL on payment failure |

### Gutenberg Block

1. In the WordPress editor, click **+** to add a new block
2. Search for **DirectCryptoPay**
3. Configure the amount and button text in the block settings

### WooCommerce

When WooCommerce is active, DirectCryptoPay automatically registers as a payment gateway:

1. Go to **WooCommerce > Settings > Payments**
2. Enable **DirectCryptoPay**
3. Configure the payment method title and default currency
4. Customers can now pay with crypto at checkout

## File Structure

```
directcryptopay/
├── directcryptopay.php                          # Main plugin file
├── includes/
│   └── class-wc-gateway-directcryptopay.php     # WooCommerce gateway
├── assets/
│   └── block.js                                 # Gutenberg block
├── INSTALLATION.md                              # Detailed setup guide
└── README.md                                    # This file
```

## Supported Chains & Tokens

| Chain | Mainnet | Testnet | Tokens |
|-------|---------|---------|--------|
| Ethereum | Chain ID 1 | Sepolia (11155111) | ETH, USDC, USDT |
| Polygon | Chain ID 137 | Amoy (80002) | MATIC, USDC, USDT |
| BSC | Chain ID 56 | BSC Testnet (97) | BNB, USDC, USDT |
| Base | Chain ID 8453 | Base Sepolia (84532) | ETH, USDC |
| Arbitrum | Chain ID 42161 | Arb Sepolia (421614) | ETH, USDC |
| Optimism | Chain ID 10 | OP Sepolia (11155420) | ETH, USDC |

## Documentation

Full documentation is available at [docs.directcryptopay.com](https://docs.directcryptopay.com).

- [Getting Started](https://docs.directcryptopay.com/getting-started/create-account.html)
- [WordPress Plugin Guide](https://docs.directcryptopay.com/plugins/wordpress.html)
- [API Reference](https://docs.directcryptopay.com/api/reference.html)
- [Webhooks](https://docs.directcryptopay.com/backend/webhooks.html)

## License

GPLv2 or later. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).

## Support

- [Documentation](https://docs.directcryptopay.com)
- [GitHub Issues](https://github.com/directcryptopay/directcryptopay-wordpress/issues)
- Email: contact@directcryptopay.com
