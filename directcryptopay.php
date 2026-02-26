<?php
/**
 * Plugin Name: DirectCryptoPay
 * Plugin URI:  https://directcryptopay.com
 * Description: Accept crypto payments and donations directly on your WordPress site. Includes WooCommerce gateway. Web3, Non-custodial, No middlemen.
 * Version:     1.3.0
 * Author:      DirectCryptoPay
 * Author URI:  https://directcryptopay.com
 * License:     GPLv2 or later
 * Text Domain: directcryptopay
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('DCP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DCP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DCP_VERSION', '1.3.0');

/**
 * Add Settings Menu
 */
add_action('admin_menu', 'dcp_create_menu');

function dcp_create_menu() {
    add_menu_page(
        'DirectCryptoPay Settings',
        'DirectCryptoPay',
        'manage_options',
        'dcp-settings',
        'dcp_settings_page',
        'dashicons-money-alt',
        100
    );
    add_action('admin_init', 'dcp_register_settings');
}

function dcp_register_settings() {
    register_setting('dcp-settings-group', 'dcp_public_id');
}

function dcp_settings_page() {
    $public_id = get_option('dcp_public_id', '');
    $is_configured = !empty($public_id);
    ?>
    <div class="wrap">
        <h1>💎 DirectCryptoPay - Setup</h1>

        <?php if (!$is_configured): ?>
        <!-- Setup Wizard -->
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 8px; margin: 20px 0;">
            <h2 style="color: white; margin-top: 0;">🚀 Welcome! Setup in 4 Steps</h2>
            <p style="font-size: 16px; opacity: 0.95;">Follow this guide to accept crypto payments on your WordPress site</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin: 30px 0;">
            <!-- Step 1: Create Account -->
            <div style="background: white; border: 2px solid #e5e7eb; border-radius: 8px; padding: 25px;">
                <div style="background: #f59e0b; color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 20px; margin-bottom: 15px;">1</div>
                <h3 style="margin-top: 0; color: #1f2937;">Create Account</h3>
                <p style="color: #6b7280; margin-bottom: 20px;">Don't have an account? Sign up for free on DirectCryptoPay.</p>
                <a href="https://test-app.directcryptopay.com/auth/signin" target="_blank" style="display: inline-block; background: #f59e0b; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 15px;">
                    ✨ Sign Up / Login →
                </a>
            </div>

            <!-- Step 2: Create Integration -->
            <div style="background: white; border: 2px solid #e5e7eb; border-radius: 8px; padding: 25px;">
                <div style="background: #3b82f6; color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 20px; margin-bottom: 15px;">2</div>
                <h3 style="margin-top: 0; color: #1f2937;">Create Integration</h3>
                <p style="color: #6b7280; margin-bottom: 20px;">In your Dashboard, go to Integrations and create a new integration.</p>
                <a href="https://test-app.directcryptopay.com/dashboard/integrations" target="_blank" style="display: inline-block; background: #3b82f6; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 500;">
                    📊 Open Dashboard →
                </a>
            </div>

            <!-- Step 3: Copy Public ID -->
            <div style="background: white; border: 2px solid #e5e7eb; border-radius: 8px; padding: 25px;">
                <div style="background: #10b981; color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 20px; margin-bottom: 15px;">3</div>
                <h3 style="margin-top: 0; color: #1f2937;">Copy Public ID</h3>
                <p style="color: #6b7280; margin-bottom: 20px;">In the Integrations list, copy the <strong>Public ID</strong> (column "Public ID").</p>
                <div style="background: #f3f4f6; padding: 15px; border-radius: 6px; border-left: 4px solid #10b981;">
                    <code style="font-size: 13px; color: #374151;">Example: int_sub_01ka9z...</code>
                </div>
            </div>

            <!-- Step 4: Configure WordPress -->
            <div style="background: white; border: 2px solid #e5e7eb; border-radius: 8px; padding: 25px;">
                <div style="background: #8b5cf6; color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 20px; margin-bottom: 15px;">4</div>
                <h3 style="margin-top: 0; color: #1f2937;">Configure Plugin</h3>
                <p style="color: #6b7280; margin-bottom: 20px;">Paste your Public ID in the form below and save.</p>
                <div style="background: #f3f4f6; padding: 15px; border-radius: 6px;">
                    <p style="margin: 0; font-size: 14px; color: #6b7280;">👇 See form below</p>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Configuration Success -->
        <div style="background: #d1fae5; border: 2px solid #10b981; border-radius: 8px; padding: 20px; margin: 20px 0;">
            <h3 style="color: #065f46; margin-top: 0;">✅ Plugin Configured!</h3>
            <p style="color: #047857; margin-bottom: 0;">Your Public ID is saved. You can now use shortcodes on your pages.</p>
        </div>
        <?php endif; ?>

        <!-- Configuration Form -->
        <form method="post" action="options.php" style="background: white; padding: 25px; border: 1px solid #e5e7eb; border-radius: 8px; margin-top: 30px;">
            <?php settings_fields('dcp-settings-group'); ?>
            <?php do_settings_sections('dcp-settings-group'); ?>

            <h2 style="margin-top: 0;">⚙️ Configuration</h2>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row">
                        <label for="dcp_public_id">Public ID <span style="color: #dc2626;">*</span></label>
                    </th>
                    <td>
                        <input
                            type="text"
                            id="dcp_public_id"
                            name="dcp_public_id"
                            value="<?php echo esc_attr($public_id); ?>"
                            class="regular-text"
                            placeholder="int_sub_01ka9zhg4e3cb3..."
                            required
                            style="font-family: monospace; font-size: 14px;"
                        />
                        <p class="description">
                            <strong>How to get your Public ID:</strong><br>
                            1. Go to your <a href="https://test-app.directcryptopay.com/dashboard/integrations" target="_blank" style="color: #3b82f6; text-decoration: none; font-weight: 500;">DirectCryptoPay Dashboard → Integrations</a><br>
                            2. Find the integration you want to use<br>
                            3. Copy the <strong>Public ID</strong> from the "Public ID" column (starts with <code>int_</code>)<br>
                            4. Paste it in the field above<br>
                            <span style="color: #dc2626;">⚠️ Important:</span> Use the <strong>Public ID</strong>, not the integration name.
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button('💾 Save Configuration'); ?>
        </form>

        <!-- Usage Guide -->
        <div style="background: white; padding: 25px; border: 1px solid #e5e7eb; border-radius: 8px; margin-top: 30px;">
            <h2 style="margin-top: 0;">📖 How to Use</h2>

            <div style="background: #eff6ff; border-left: 4px solid #3b82f6; padding: 20px; margin: 20px 0;">
                <h3 style="color: #1e40af; margin-top: 0;">Method 1: Shortcode (Quick & Easy)</h3>
                <p style="color: #1e40af;">Add this code to any page, post, or widget:</p>
                <pre style="background: #1e293b; color: #e2e8f0; padding: 15px; border-radius: 6px; overflow-x: auto; margin: 15px 0;"><code>[dcp_pay amount="10" label="Pay with Crypto"]</code></pre>

                <h4 style="color: #1e40af; margin-top: 20px;">Available Parameters:</h4>
                <ul style="color: #1e40af; line-height: 1.8;">
                    <li><code style="background: #dbeafe; padding: 2px 8px; border-radius: 4px;">amount</code> - Amount in USD (ex: <code>amount="10"</code>)</li>
                    <li><code style="background: #dbeafe; padding: 2px 8px; border-radius: 4px;">label</code> - Button text (ex: <code>label="Donate Now"</code>)</li>
                    <li><code style="background: #dbeafe; padding: 2px 8px; border-radius: 4px;">currency</code> - Crypto token to accept (default: ETH, supported: ETH, USDC, USDT)</li>
                    <li><code style="background: #dbeafe; padding: 2px 8px; border-radius: 4px;">success_url</code> - <strong>NEW!</strong> Custom page after successful payment (ex: <code>success_url="https://example.com/thank-you"</code>)</li>
                    <li><code style="background: #dbeafe; padding: 2px 8px; border-radius: 4px;">error_url</code> - <strong>NEW!</strong> Custom page if payment fails (ex: <code>error_url="https://example.com/error"</code>)</li>
                </ul>

                <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 15px 0; border-radius: 4px;">
                    <strong style="color: #92400e;">💡 Tip:</strong>
                    <span style="color: #92400e;"> You can customize the token for each button:</span>
                    <ul style="color: #92400e; margin-top: 10px;">
                        <li><code>[dcp_pay amount="10" currency="ETH"]</code> - Accept Ethereum</li>
                        <li><code>[dcp_pay amount="25" currency="USDC"]</code> - Accept USD Coin</li>
                        <li><code>[dcp_pay amount="50" currency="USDT"]</code> - Accept Tether</li>
                    </ul>
                </div>

                <h4 style="color: #1e40af; margin-top: 20px;">Examples:</h4>
                <pre style="background: #1e293b; color: #e2e8f0; padding: 15px; border-radius: 6px; overflow-x: auto; margin: 10px 0;"><code>[dcp_pay amount="5" label="Donate" currency="ETH"]</code></pre>
                <pre style="background: #1e293b; color: #e2e8f0; padding: 15px; border-radius: 6px; overflow-x: auto; margin: 10px 0;"><code>[dcp_pay amount="99" label="Buy Premium" currency="USDC" success_url="https://yoursite.com/thank-you"]</code></pre>
            </div>

            <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 20px; margin: 20px 0;">
                <h3 style="color: #92400e; margin-top: 0;">🎯 NEW: Custom Success & Error Pages</h3>
                <p style="color: #92400e;">Redirect users to custom pages after payment completion or failure:</p>

                <pre style="background: #1e293b; color: #e2e8f0; padding: 15px; border-radius: 6px; overflow-x: auto; margin: 15px 0;"><code>[dcp_pay amount="10" success_url="https://yoursite.com/thank-you" error_url="https://yoursite.com/error"]</code></pre>

                <h4 style="color: #92400e; margin-top: 20px;">Payment Data Included:</h4>
                <p style="color: #92400e;">The success page receives these URL parameters:</p>
                <ul style="color: #92400e; line-height: 1.8;">
                    <li><code style="background: #fef3c7; padding: 2px 8px; border-radius: 4px;">tx_hash</code> - Blockchain transaction hash</li>
                    <li><code style="background: #fef3c7; padding: 2px 8px; border-radius: 4px;">payment_id</code> - Payment ID in system</li>
                    <li><code style="background: #fef3c7; padding: 2px 8px; border-radius: 4px;">amount</code> - Payment amount</li>
                    <li><code style="background: #fef3c7; padding: 2px 8px; border-radius: 4px;">currency</code> - Token used (ETH, USDC, etc.)</li>
                    <li><code style="background: #fef3c7; padding: 2px 8px; border-radius: 4px;">status</code> - "success" or "error"</li>
                </ul>

                <p style="color: #92400e; margin-top: 15px;"><strong>Example success URL:</strong></p>
                <pre style="background: #1e293b; color: #e2e8f0; padding: 15px; border-radius: 6px; overflow-x: auto; margin: 10px 0; font-size: 12px;"><code>https://yoursite.com/thank-you?tx_hash=0x7f72...&payment_id=01KG...&amount=10&currency=ETH&status=success</code></pre>

                <div style="background: #fff7ed; border: 1px solid #fb923c; padding: 15px; margin: 15px 0; border-radius: 4px;">
                    <strong style="color: #9a3412;">💡 Pro Tip:</strong>
                    <span style="color: #9a3412;"> On your success page, use JavaScript to read these parameters:</span>
                    <pre style="background: #1e293b; color: #e2e8f0; padding: 10px; border-radius: 4px; overflow-x: auto; margin: 10px 0; font-size: 12px;"><code>const params = new URLSearchParams(window.location.search);
const txHash = params.get('tx_hash');
const paymentId = params.get('payment_id');
// Display to user or send to your backend</code></pre>
                </div>
            </div>

            <div style="background: #f0fdf4; border-left: 4px solid #10b981; padding: 20px; margin: 20px 0;">
                <h3 style="color: #065f46; margin-top: 0;">Method 2: Gutenberg Block (Visual Editor)</h3>
                <p style="color: #065f46;">In the page/post editor:</p>
                <ol style="color: #065f46; line-height: 1.8;">
                    <li>Click the <strong>+</strong> button to add a block</li>
                    <li>Search for <strong>"DirectCryptoPay Payment Button"</strong></li>
                    <li>Configure amount, label, and currency in block settings</li>
                    <li>Publish your page</li>
                </ol>
            </div>

            <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 20px; margin: 20px 0;">
                <h3 style="color: #92400e; margin-top: 0;">🛒 Method 3: WooCommerce Gateway (E-commerce)</h3>
                <p style="color: #92400e;"><strong>Automatically accept crypto payments on your WooCommerce store!</strong></p>
                <ol style="color: #92400e; line-height: 1.8;">
                    <li>Make sure <strong>WooCommerce</strong> is installed and active</li>
                    <li>Go to <strong>WooCommerce → Settings → Payments</strong></li>
                    <li>Enable <strong>"DirectCryptoPay"</strong> payment gateway</li>
                    <li>Click <strong>"Manage"</strong> to configure the gateway:
                        <ul style="margin-top: 8px;">
                            <li><strong>Title:</strong> "Pay with Crypto" (or your preferred text)</li>
                            <li><strong>Description:</strong> Message shown at checkout</li>
                            <li><strong>Default Currency:</strong> ETH, USDC, or USDT</li>
                        </ul>
                    </li>
                    <li>Your Public ID from DirectCryptoPay Settings is used automatically</li>
                    <li>Test with a checkout - the widget launches automatically!</li>
                </ol>
                <div style="background: #fff7ed; border: 1px solid #fb923c; padding: 12px; margin-top: 15px; border-radius: 4px;">
                    <strong style="color: #9a3412;">💡 Payment Flow:</strong>
                    <p style="color: #9a3412; margin: 8px 0 0 0; font-size: 13px;">
                        Customer selects "Pay with Crypto" → Order created → Widget launches → Customer pays → Order marked as "Processing" → Success page
                    </p>
                </div>
            </div>
        </div>

        <!-- Help Section -->
        <div style="background: #fefce8; border: 2px solid #facc15; border-radius: 8px; padding: 20px; margin-top: 30px;">
            <h3 style="color: #854d0e; margin-top: 0;">❓ Need Help?</h3>
            <p style="color: #a16207; margin-bottom: 15px;">Check the complete documentation or contact support:</p>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="https://docs.directcryptopay.com" target="_blank" style="display: inline-block; background: white; color: #854d0e; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 500; border: 2px solid #facc15;">
                    📚 Documentation
                </a>
                <a href="https://test-app.directcryptopay.com/dashboard" target="_blank" style="display: inline-block; background: white; color: #854d0e; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 500; border: 2px solid #facc15;">
                    🎛️ Dashboard
                </a>
                <a href="mailto:support@directcryptopay.com" style="display: inline-block; background: white; color: #854d0e; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 500; border: 2px solid #facc15;">
                    ✉️ Support Email
                </a>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Enqueue Widget Script
 */
add_action('wp_enqueue_scripts', 'dcp_load_scripts');

function dcp_load_scripts() {
    // Load the DirectCryptoPay Widget SDK
    // Environment detection with auto-detection fallback:
    // 1. Check DCP_ENV constant (can be set in wp-config.php)
    // 2. Auto-detect based on site URL
    // 3. Default to production

    if (defined('DCP_ENV')) {
        $env = DCP_ENV;
    } else {
        // Auto-detect environment based on site URL
        $site_url = get_site_url();
        if (strpos($site_url, 'localhost') !== false || strpos($site_url, '127.0.0.1') !== false) {
            $env = 'local';
        } elseif (strpos($site_url, 'test.') !== false || strpos($site_url, 'staging.') !== false || strpos($site_url, 'dev.') !== false) {
            $env = 'test';
        } else {
            $env = 'production';
        }
    }

    switch ($env) {
        case 'local':
            $widget_url = 'http://localhost:4002/widget/dcp-widget.umd.js';
            break;
        case 'staging':
        case 'test':
            $widget_url = 'https://test-pay.directcryptopay.com/widget/dcp-widget.umd.js';
            break;
        case 'production':
        default:
            $widget_url = 'https://pay.directcryptopay.com/widget/dcp-widget.umd.js';
            break;
    }

    // NUCLEAR cache busting: timestamp in BOTH query string AND script handle
    $timestamp = time();
    $cache_buster = '?bust=' . $timestamp . '&nocache=' . rand();

    // Deregister any previously registered versions
    wp_deregister_script('dcp-widget');

    wp_enqueue_script(
        'dcp-widget-' . $timestamp,  // Unique handle name each time
        $widget_url . $cache_buster,
        [],
        null,  // Don't let WordPress add its own version parameter
        true
    );

    // Initialize widget globally after it loads
    wp_add_inline_script(
        'dcp-widget',
        "
        (function() {
            console.log('[DCP Plugin] Inline script executing...');

            function initDCP() {
                console.log('[DCP Plugin] initDCP called, typeof DCP:', typeof DCP);
                if (typeof DCP !== 'undefined') {
                    console.log('[DCP Plugin] Initializing DCP with test API URL');
                    DCP.init({
                        baseURL: 'https://test-api.directcryptopay.com'
                    });
                    console.log('[DCP Plugin] DCP initialized, isInitialized:', DCP.isInitialized());
                } else {
                    console.warn('[DCP Plugin] DCP not ready yet, retrying in 100ms...');
                    setTimeout(initDCP, 100);
                }
            }

            // Try immediately
            initDCP();
        })();
        ",
        'after'
    );
}

/**
 * Payment Button Shortcode
 */
add_shortcode('dcp_pay', 'dcp_pay_shortcode');

function dcp_pay_shortcode($atts) {
    $public_id = get_option('dcp_public_id', '');

    if (empty($public_id)) {
        return '<div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 15px 0; border-radius: 4px;">
            <strong style="color: #92400e;">⚠️ DirectCryptoPay:</strong>
            <span style="color: #92400e;"> Please configure your Public ID in </span>
            <a href="' . admin_url('admin.php?page=dcp-settings') . '" style="color: #3b82f6; text-decoration: none; font-weight: 600;">Settings</a>.
        </div>';
    }

    $a = shortcode_atts([
        'amount' => '10',
        'label' => 'Pay with Crypto',
        'currency' => 'ETH',  // Default to ETH (can be overridden: currency="USDC" or currency="USDT")
        'success_url' => '',  // Optional: URL to redirect on success
        'error_url' => '',    // Optional: URL to redirect on error
    ], $atts);

    // Generate unique ID for this button instance
    $button_id = 'dcp-btn-' . uniqid();

    $amount = floatval($a['amount']);
    $currency = strtoupper(sanitize_text_field($a['currency']));  // Normalize to uppercase
    $label = sanitize_text_field($a['label']);
    $success_url = esc_url($a['success_url']);
    $error_url = esc_url($a['error_url']);

    ob_start();
    ?>
    <button id="<?php echo esc_attr($button_id); ?>" class="dcp-payment-button" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 28px; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: all 0.3s;">
        <?php echo esc_html($label); ?>
    </button>
    <script>
    (function() {
        console.log('[DCP Shortcode] Script executing for button <?php echo esc_js($button_id); ?>');

        // CRITICAL: Initialize DCP with test API URL BEFORE any payment
        function ensureDCPInit() {
            if (typeof DCP !== 'undefined') {
                console.log('[DCP Shortcode] DCP found, initializing with test API...');
                DCP.init({
                    baseURL: 'https://test-api.directcryptopay.com'
                });
                console.log('[DCP Shortcode] DCP initialized, isInitialized:', DCP.isInitialized());
                return true;
            }
            console.warn('[DCP Shortcode] DCP not loaded yet');
            return false;
        }

        // Try to initialize immediately
        ensureDCPInit();

        const button = document.getElementById('<?php echo esc_js($button_id); ?>');

        // Add hover effect
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 6px 12px rgba(0,0,0,0.15)';
        });
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
        });

        // Payment handler
        button.addEventListener('click', async function() {
            console.log('[DCP Shortcode] Button clicked');

            if (typeof DCP === 'undefined') {
                alert('DirectCryptoPay widget not loaded. Please refresh the page.');
                return;
            }

            try {
                // CRITICAL: Re-initialize before payment to ensure test API URL
                console.log('[DCP Shortcode] Re-initializing DCP with test API before payment...');
                DCP.init({
                    baseURL: 'https://test-api.directcryptopay.com'
                });
                console.log('[DCP Shortcode] DCP re-initialized');

                // Execute payment with integration ID
                // Using Sepolia testnet (chainId: 11155111)
                // Currency can be customized in shortcode: [dcp_pay amount="10" currency="ETH"]
                console.log('[DCP Shortcode] Calling DCP.Payment()...');
                const result = await DCP.Payment({
                    integrationId: '<?php echo esc_js($public_id); ?>',
                    amount_usd: '<?php echo $amount; ?>',
                    currency: '<?php echo esc_js($currency); ?>',  // ETH, USDC, USDT, etc.
                    chainId: 11155111,  // Sepolia testnet
                    onStatus: (status) => {
                        console.log('[DCP Shortcode] Payment status:', status);

                        // Handle confirmed payment
                        if (status.type === 'confirmed') {
                            const successUrl = '<?php echo esc_js($success_url); ?>';

                            if (successUrl) {
                                // Redirect to custom success page with payment data
                                const params = new URLSearchParams({
                                    tx_hash: status.txHash || '',
                                    payment_id: status.paymentId || '',
                                    amount: '<?php echo $amount; ?>',
                                    currency: '<?php echo esc_js($currency); ?>',
                                    status: 'success'
                                });
                                window.location.href = successUrl + '?' + params.toString();
                            } else {
                                // Fallback to alert if no custom URL
                                alert('Payment completed successfully!\\n\\nTransaction: ' + (status.txHash || 'N/A'));
                            }
                        }
                    }
                });
                console.log('[DCP Shortcode] Payment result:', result);
            } catch (error) {
                console.error('Payment error:', error);

                // Skip error redirect if user rejected the transaction
                if (error.message && error.message.includes('User rejected')) {
                    console.log('[DCP Shortcode] User rejected transaction');
                    return;
                }

                // Redirect to error page or show alert
                const errorUrl = '<?php echo esc_js($error_url); ?>';

                if (errorUrl) {
                    // Redirect to custom error page with error data
                    const params = new URLSearchParams({
                        error: error.message || 'Unknown error',
                        amount: '<?php echo $amount; ?>',
                        currency: '<?php echo esc_js($currency); ?>',
                        status: 'error'
                    });
                    window.location.href = errorUrl + '?' + params.toString();
                } else {
                    // Fallback to alert if no custom URL
                    alert('Payment failed: ' + error.message);
                }
            }
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}

/**
 * Gutenberg Block Registration
 */
add_action('init', 'dcp_register_block');

function dcp_register_block() {
    if (!function_exists('register_block_type')) {
        return; // Gutenberg not available
    }

    wp_register_script(
        'dcp-block-editor',
        DCP_PLUGIN_URL . 'assets/block.js',
        ['wp-blocks', 'wp-element', 'wp-editor', 'wp-components'],
        DCP_VERSION
    );

    register_block_type('directcryptopay/payment-button', [
        'editor_script' => 'dcp-block-editor',
        'render_callback' => 'dcp_render_block',
        'attributes' => [
            'amount' => [
                'type' => 'string',
                'default' => '10',
            ],
            'label' => [
                'type' => 'string',
                'default' => 'Pay with Crypto',
            ],
            'currency' => [
                'type' => 'string',
                'default' => 'USD',
            ],
        ],
    ]);
}

function dcp_render_block($attributes) {
    $shortcode_atts = '';
    foreach ($attributes as $key => $value) {
        if (!empty($value)) {
            $shortcode_atts .= ' ' . $key . '="' . esc_attr($value) . '"';
        }
    }
    return do_shortcode('[dcp_pay' . $shortcode_atts . ']');
}

/**
 * ========================================
 * WooCommerce Integration
 * ========================================
 */

/**
 * Check if WooCommerce is active
 * More robust detection that works with multisite and MU plugins
 */
function dcp_is_woocommerce_active() {
    // Method 1: Check if WooCommerce class exists (most reliable)
    if (class_exists('WooCommerce')) {
        return true;
    }

    // Method 2: Check active plugins
    if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins', array())))) {
        return true;
    }

    // Method 3: Check network activated plugins (multisite)
    if (is_multisite()) {
        $plugins = get_site_option('active_sitewide_plugins');
        if (isset($plugins['woocommerce/woocommerce.php'])) {
            return true;
        }
    }

    return false;
}

/**
 * Load WooCommerce Gateway if WooCommerce is active
 */
add_action('plugins_loaded', 'dcp_init_woocommerce_gateway', 11);

function dcp_init_woocommerce_gateway() {
    if (!dcp_is_woocommerce_active()) {
        return;
    }

    // Ensure WooCommerce is fully loaded
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    // Include the Gateway class
    $gateway_file = DCP_PLUGIN_DIR . 'includes/class-wc-gateway-directcryptopay.php';
    if (file_exists($gateway_file)) {
        require_once $gateway_file;

        // Register the Gateway
        add_filter('woocommerce_payment_gateways', 'dcp_add_gateway_class');
    } else {
        error_log('DirectCryptoPay: WooCommerce gateway file not found at ' . $gateway_file);
    }
}

/**
 * Add DirectCryptoPay Gateway to WooCommerce
 */
function dcp_add_gateway_class($gateways) {
    $gateways[] = 'WC_Gateway_DirectCryptoPay';
    return $gateways;
}

/**
 * AJAX Handler: Mark order as paid
 *
 * Called from JavaScript after payment confirmation
 */
add_action('wp_ajax_dcp_mark_order_paid', 'dcp_mark_order_paid');
add_action('wp_ajax_nopriv_dcp_mark_order_paid', 'dcp_mark_order_paid');

function dcp_mark_order_paid() {
    // Verify required parameters
    if (!isset($_POST['order_id']) || !isset($_POST['order_key']) || !isset($_POST['tx_hash'])) {
        wp_send_json_error(array('message' => 'Missing required parameters'));
        return;
    }

    $order_id = intval($_POST['order_id']);
    $order_key = sanitize_text_field($_POST['order_key']);
    $tx_hash = sanitize_text_field($_POST['tx_hash']);
    $payment_id = isset($_POST['payment_id']) ? sanitize_text_field($_POST['payment_id']) : '';

    // Get order
    $order = wc_get_order($order_id);

    if (!$order) {
        wp_send_json_error(array('message' => 'Order not found'));
        return;
    }

    // Verify order key for security
    if ($order->get_order_key() !== $order_key) {
        wp_send_json_error(array('message' => 'Invalid order key'));
        return;
    }

    // Check if order is already completed
    if ($order->has_status(array('processing', 'completed'))) {
        wp_send_json_success(array('message' => 'Order already completed'));
        return;
    }

    // Mark payment complete
    $order->payment_complete($tx_hash);

    // Add order note
    $note = sprintf(
        __('DirectCryptoPay payment confirmed.%s%s', 'directcryptopay'),
        PHP_EOL . 'TX Hash: ' . $tx_hash,
        $payment_id ? PHP_EOL . 'Payment ID: ' . $payment_id : ''
    );
    $order->add_order_note($note);

    // Store transaction metadata
    $order->update_meta_data('_dcp_tx_hash', $tx_hash);
    if ($payment_id) {
        $order->update_meta_data('_dcp_payment_id', $payment_id);
    }
    $order->save();

    wp_send_json_success(array(
        'message' => 'Order marked as paid',
        'order_id' => $order_id,
        'status' => $order->get_status()
    ));
}
