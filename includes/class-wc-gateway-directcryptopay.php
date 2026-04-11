<?php
/**
 * WooCommerce DirectCryptoPay Payment Gateway
 *
 * @package DirectCryptoPay
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * DirectCryptoPay Gateway Class
 *
 * Handles WooCommerce payment processing through DirectCryptoPay widget.
 *
 * Payment flow:
 * 1. Customer selects "Pay with Crypto" at checkout
 * 2. WooCommerce creates order with status "pending"
 * 3. Customer is redirected to receipt page with DCP widget
 * 4. Widget opens, customer connects wallet and pays
 * 5. On confirmed status, JS calls AJAX to mark order as paid
 * 6. Customer is redirected to thank-you page
 *
 * Additionally, the DCP backend sends a webhook (payment.succeeded)
 * which serves as a server-side backup verification.
 */
class WC_Gateway_DirectCryptoPay extends WC_Payment_Gateway {

    /**
     * Integration ID from plugin settings
     * @var string
     */
    public $integration_id;

    /**
     * Webhook secret for HMAC verification
     * @var string
     */
    private $webhook_secret;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id                 = 'directcryptopay';
        $this->icon               = '';
        $this->has_fields         = false;
        $this->method_title       = 'DirectCryptoPay';
        $this->method_description = 'Accept cryptocurrency payments (ETH, USDC, USDT) directly to your wallet via DirectCryptoPay. Non-custodial — funds go straight to your wallet.';

        $this->supports = array('products');

        // Load settings
        $this->init_form_fields();
        $this->init_settings();

        // Define user-facing settings
        $this->title          = $this->get_option('title');
        $this->description    = $this->get_option('description');
        $this->enabled        = $this->get_option('enabled');
        $this->webhook_secret = $this->get_option('webhook_secret');

        // Get Integration ID from global plugin settings
        $this->integration_id = get_option('dcp_public_id', '');

        // Hooks
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));

        // Webhook handler for server-side payment confirmation
        add_action('woocommerce_api_wc_gateway_directcryptopay', array($this, 'webhook_handler'));
    }

    /**
     * Initialize Gateway settings form fields
     */
    public function init_form_fields() {
        $webhook_url = add_query_arg('wc-api', 'wc_gateway_directcryptopay', home_url('/'));

        $this->form_fields = array(
            'enabled' => array(
                'title'   => 'Enable/Disable',
                'type'    => 'checkbox',
                'label'   => 'Enable DirectCryptoPay Payment Gateway',
                'default' => 'no'
            ),
            'title' => array(
                'title'       => 'Title',
                'type'        => 'text',
                'description' => 'Payment method title that customers see during checkout.',
                'default'     => 'Pay with Crypto',
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => 'Description',
                'type'        => 'textarea',
                'description' => 'Payment method description that customers see during checkout.',
                'default'     => 'Pay securely with ETH, USDC, or USDT using your crypto wallet. Non-custodial — funds go directly to the merchant.',
                'desc_tip'    => true,
            ),
            'currency' => array(
                'title'       => 'Default Currency',
                'type'        => 'select',
                'description' => 'Default cryptocurrency. The customer can still choose another token at payment time via the multi-chain selector.',
                'default'     => 'ETH',
                'desc_tip'    => true,
                'options'     => array(
                    'ETH'  => 'Ethereum (ETH)',
                    'USDC' => 'USD Coin (USDC)',
                    'USDT' => 'Tether (USDT)',
                )
            ),
            'webhook_secret' => array(
                'title'       => 'Webhook Secret',
                'type'        => 'password',
                'description' => 'Your webhook secret from the DCP Dashboard (starts with <code>whsec_</code>). Used to verify webhook signatures. Optional but recommended.',
                'default'     => '',
                'desc_tip'    => false,
            ),
            'integration_info' => array(
                'title'       => 'Integration ID',
                'type'        => 'title',
                'description' => 'Using Integration ID from DirectCryptoPay settings: <code>' . esc_html(get_option('dcp_public_id', 'Not configured')) . '</code><br><a href="' . esc_url(admin_url('admin.php?page=dcp-settings')) . '">Configure Integration ID</a>',
            ),
            'webhook_url_info' => array(
                'title'       => 'Webhook URL',
                'type'        => 'title',
                'description' => 'Set this URL in your DCP Dashboard → Integrations → Webhook URL:<br><code>' . esc_html($webhook_url) . '</code>',
            ),
        );
    }

    /**
     * Process the payment
     *
     * @param int $order_id Order ID
     * @return array
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);

        if (empty($this->integration_id)) {
            wc_add_notice('DirectCryptoPay is not configured. Please contact the store administrator.', 'error');
            return array(
                'result'   => 'failure',
                'messages' => 'Payment gateway not configured'
            );
        }

        // Mark as pending payment
        $order->update_status('pending', __('Awaiting cryptocurrency payment via DirectCryptoPay', 'directcryptopay'));

        // Reduce stock levels
        wc_reduce_stock_levels($order_id);

        // Remove cart
        WC()->cart->empty_cart();

        // Redirect to payment page (order-pay)
        return array(
            'result'   => 'success',
            'redirect' => $order->get_checkout_payment_url(true)
        );
    }

    /**
     * Receipt page — displays the DCP widget on the order-pay page
     *
     * @param int $order_id Order ID
     */
    public function receipt_page($order_id) {
        $order = wc_get_order($order_id);

        if (!$order) {
            echo '<p>' . esc_html__('Order not found.', 'directcryptopay') . '</p>';
            return;
        }

        $amount     = $order->get_total();
        $order_key  = $order->get_order_key();
        $return_url = $this->get_return_url($order);
        $cancel_url = $order->get_cancel_order_url();
        $nonce      = wp_create_nonce('dcp_mark_order_paid_' . $order_id);

        // Detect environment for API URL
        $wc_api_url = $this->get_api_url();

        $container_id = 'dcp-wc-payment-' . $order_id;

        ?>
        <style>
            #<?php echo esc_attr($container_id); ?> { display: none; }
            .dcp-overlay {
                position: fixed; top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(255,255,255,0.97); z-index: 99999;
                display: flex; align-items: center; justify-content: center;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            .dcp-card {
                text-align: center; max-width: 420px; width: 90%; padding: 48px 32px;
            }
            .dcp-amount {
                font-size: 36px; font-weight: 700; color: #1a1a2e; margin: 0 0 4px;
            }
            .dcp-label {
                font-size: 14px; color: #888; margin: 0 0 36px; letter-spacing: 0.5px;
            }

            /* Spinner */
            .dcp-spinner {
                width: 56px; height: 56px; margin: 0 auto 24px;
                border: 3px solid #e5e7eb; border-top-color: #6366f1;
                border-radius: 50%; animation: dcp-spin 0.8s linear infinite;
            }
            @keyframes dcp-spin { to { transform: rotate(360deg); } }

            /* Checkmark */
            .dcp-check {
                width: 64px; height: 64px; margin: 0 auto 24px;
                background: #10b981; border-radius: 50%;
                display: flex; align-items: center; justify-content: center;
                animation: dcp-pop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            }
            .dcp-check svg { width: 32px; height: 32px; }
            @keyframes dcp-pop { 0% { transform: scale(0); } 100% { transform: scale(1); } }

            /* Error icon */
            .dcp-error-icon {
                width: 64px; height: 64px; margin: 0 auto 24px;
                background: #ef4444; border-radius: 50%;
                display: flex; align-items: center; justify-content: center;
                animation: dcp-pop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            }
            .dcp-error-icon svg { width: 32px; height: 32px; }

            .dcp-status-text {
                font-size: 18px; font-weight: 600; color: #1a1a2e; margin: 0 0 8px;
            }
            .dcp-status-sub {
                font-size: 14px; color: #888; margin: 0 0 32px;
            }
            .dcp-cancel-link {
                font-size: 13px; color: #aaa; text-decoration: none;
                transition: color 0.2s;
            }
            .dcp-cancel-link:hover { color: #666; }

            /* Progress bar */
            .dcp-progress {
                width: 200px; height: 3px; background: #e5e7eb;
                border-radius: 2px; margin: 0 auto 32px; overflow: hidden;
            }
            .dcp-progress-bar {
                height: 100%; width: 30%; background: #6366f1;
                border-radius: 2px; animation: dcp-progress 1.5s ease-in-out infinite;
            }
            @keyframes dcp-progress {
                0% { width: 10%; margin-left: 0; }
                50% { width: 40%; margin-left: 30%; }
                100% { width: 10%; margin-left: 90%; }
            }
        </style>

        <div id="<?php echo esc_attr($container_id); ?>"></div>

        <div class="dcp-overlay" id="dcp-overlay">
            <div class="dcp-card">
                <p class="dcp-amount"><?php echo wc_price($amount); ?></p>
                <p class="dcp-label">Crypto Payment</p>

                <div id="dcp-icon-area">
                    <div class="dcp-spinner"></div>
                </div>

                <div id="dcp-progress-area">
                    <div class="dcp-progress"><div class="dcp-progress-bar"></div></div>
                </div>

                <p class="dcp-status-text" id="dcp-status-text">Initializing wallet...</p>
                <p class="dcp-status-sub" id="dcp-status-sub">The payment widget will open shortly</p>

                <div id="dcp-cancel-area">
                    <a href="<?php echo esc_url($cancel_url); ?>" class="dcp-cancel-link">Cancel and return to cart</a>
                </div>
            </div>
        </div>

        <script>
        (function() {
            var iconArea     = document.getElementById('dcp-icon-area');
            var progressArea = document.getElementById('dcp-progress-area');
            var statusText   = document.getElementById('dcp-status-text');
            var statusSub    = document.getElementById('dcp-status-sub');
            var cancelArea   = document.getElementById('dcp-cancel-area');

            function showSuccess(title, subtitle) {
                iconArea.innerHTML = '<div class="dcp-check"><svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg></div>';
                progressArea.style.display = 'none';
                statusText.textContent = title;
                statusText.style.color = '#065f46';
                statusSub.textContent = subtitle;
                cancelArea.style.display = 'none';
            }

            function showError(title, subtitle) {
                iconArea.innerHTML = '<div class="dcp-error-icon"><svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></div>';
                progressArea.style.display = 'none';
                statusText.textContent = title;
                statusText.style.color = '#991b1b';
                statusSub.textContent = subtitle;
                statusSub.style.color = '#b91c1c';
            }

            function showProcessing(title, subtitle) {
                statusText.textContent = title;
                statusSub.textContent = subtitle;
            }

            function initializePayment() {
                if (typeof DCP === 'undefined') {
                    setTimeout(initializePayment, 200);
                    return;
                }

                DCP.init({ baseURL: '<?php echo esc_js($wc_api_url); ?>' });
                showProcessing('Preparing payment...', 'Connecting to DirectCryptoPay');

                DCP.Payment({
                    integrationId: '<?php echo esc_js($this->integration_id); ?>',
                    amount_usd: '<?php echo esc_js($amount); ?>',
                    metadata: {
                        order_id: '<?php echo esc_js($order_id); ?>',
                        order_key: '<?php echo esc_js($order_key); ?>'
                    },
                    onStatus: function(status) {
                        switch(status.type) {
                            case 'awaiting_signature':
                                showProcessing('Confirm in your wallet', 'Please approve the transaction in your wallet');
                                break;
                            case 'submitted':
                                showProcessing('Transaction sent!', 'Verifying on blockchain...');
                                cancelArea.style.display = 'none';
                                pollBackendStatus(status.txHash, status.paymentId);
                                break;
                            case 'confirmed':
                                showSuccess('Payment confirmed!', 'Redirecting to your order...');
                                cancelArea.style.display = 'none';
                                markOrderPaid(status.txHash, status.paymentId);
                                setTimeout(function() {
                                    window.location.href = '<?php echo esc_js($return_url); ?>';
                                }, 2000);
                                break;
                            case 'failed':
                                showError('Payment failed', 'The transaction could not be completed. Please try again.');
                                break;
                            case 'rejected':
                                showError('Transaction declined', 'The transaction was rejected by your wallet.');
                                break;
                        }
                    }
                }).catch(function(error) {
                    if (error.message && error.message.includes('User rejected')) {
                        showError('Transaction cancelled', 'You cancelled the transaction. You can try again below.');
                        cancelArea.innerHTML = '<a href="javascript:location.reload()" style="display:inline-block;padding:12px 32px;background:#6366f1;color:#fff;border-radius:8px;text-decoration:none;font-weight:600;font-size:15px;margin-bottom:12px;">Try Again</a><br><a href="<?php echo esc_url($cancel_url); ?>" class="dcp-cancel-link">Cancel order</a>';
                        cancelArea.style.display = '';
                        return;
                    }
                    showError('Payment error', error.message || 'An unexpected error occurred.');
                });
            }

            function pollBackendStatus(txHash, paymentId) {
                var apiUrl = '<?php echo esc_js($wc_api_url); ?>';
                var attempts = 0;
                var maxAttempts = 60; // 60 x 2s = 2 minutes

                function poll() {
                    attempts++;
                    fetch(apiUrl + '/pay/payment-status/' + encodeURIComponent(txHash))
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            if (data.status === 'confirmed') {
                                showSuccess('Payment confirmed!', 'Redirecting to your order...');
                                markOrderPaid(txHash, paymentId);
                                setTimeout(function() {
                                    window.location.href = '<?php echo esc_js($return_url); ?>';
                                }, 2000);
                            } else if (data.status === 'failed') {
                                showError('Payment failed', 'The transaction failed on-chain. Please try again.');
                            } else if (attempts >= maxAttempts) {
                                // Timeout — payment is still processing, redirect anyway
                                showSuccess('Payment processing!', 'Your order is being confirmed. Redirecting...');
                                markOrderPaid(txHash, paymentId);
                                setTimeout(function() {
                                    window.location.href = '<?php echo esc_js($return_url); ?>';
                                }, 2000);
                            } else {
                                // Still pending — update subtitle with progress dots
                                var dots = '.'.repeat((attempts % 3) + 1);
                                showProcessing('Transaction sent!', 'Verifying on blockchain' + dots);
                                setTimeout(poll, 2000);
                            }
                        })
                        .catch(function() {
                            // Network error — retry silently
                            if (attempts < maxAttempts) {
                                setTimeout(poll, 2000);
                            } else {
                                showSuccess('Payment processing!', 'Your order is being confirmed. Redirecting...');
                                markOrderPaid(txHash, paymentId);
                                setTimeout(function() {
                                    window.location.href = '<?php echo esc_js($return_url); ?>';
                                }, 2000);
                            }
                        });
                }

                // Start first poll after a short delay (let backend index the tx)
                setTimeout(poll, 2000);
            }

            function markOrderPaid(txHash, paymentId) {
                var data = new FormData();
                data.append('action', 'dcp_mark_order_paid');
                data.append('order_id', '<?php echo esc_js($order_id); ?>');
                data.append('order_key', '<?php echo esc_js($order_key); ?>');
                data.append('tx_hash', txHash || '');
                data.append('intent_id', paymentId || '');
                data.append('_wpnonce', '<?php echo esc_js($nonce); ?>');

                fetch('<?php echo esc_js(admin_url('admin-ajax.php')); ?>', {
                    method: 'POST',
                    body: data
                }).catch(function() {});
            }

            initializePayment();
        })();
        </script>
        <?php
    }

    /**
     * Webhook handler — server-side payment verification from DCP backend
     *
     * Verifies HMAC signature and updates order status.
     * This is a backup to the client-side AJAX confirmation.
     */
    public function webhook_handler() {
        $raw_body = file_get_contents('php://input');

        // Verify HMAC signature if webhook secret is configured
        if (!empty($this->webhook_secret)) {
            $signature_header = isset($_SERVER['HTTP_X_DCP_SIGNATURE']) ? $_SERVER['HTTP_X_DCP_SIGNATURE'] : '';

            if (!$this->verify_webhook_signature($raw_body, $signature_header)) {
                status_header(401);
                wp_send_json(array('error' => 'Invalid signature'));
                return;
            }
        }

        $data = json_decode($raw_body, true);

        if (!$data || !isset($data['event'])) {
            status_header(400);
            wp_send_json(array('error' => 'Invalid payload'));
            return;
        }

        // Extract order_id from metadata (passed during payment creation)
        $order_id = isset($data['metadata']['order_id']) ? intval($data['metadata']['order_id']) : 0;
        $order_key = isset($data['metadata']['order_key']) ? sanitize_text_field($data['metadata']['order_key']) : '';

        if (!$order_id) {
            // Can't match to an order — acknowledge receipt anyway
            wp_send_json(array('success' => true, 'message' => 'No order_id in metadata'));
            return;
        }

        $order = wc_get_order($order_id);

        if (!$order || ($order_key && $order->get_order_key() !== $order_key)) {
            status_header(404);
            wp_send_json(array('error' => 'Order not found'));
            return;
        }

        switch ($data['event']) {
            case 'payment.succeeded':
                if (!$order->has_status(array('processing', 'completed'))) {
                    $tx_hash   = sanitize_text_field($data['tx_hash'] ?? '');
                    $intent_id = sanitize_text_field($data['intent_id'] ?? '');
                    $currency  = sanitize_text_field($data['currency'] ?? '');
                    $chain_id  = intval($data['chain_id'] ?? 0);

                    $order->payment_complete($tx_hash);
                    $order->add_order_note(sprintf(
                        __('DirectCryptoPay: payment confirmed on-chain.%sTX: %s%sIntent: %s%sCurrency: %s | Chain: %d', 'directcryptopay'),
                        PHP_EOL, $tx_hash, PHP_EOL, $intent_id, PHP_EOL, $currency, $chain_id
                    ));

                    $order->update_meta_data('_dcp_tx_hash', $tx_hash);
                    $order->update_meta_data('_dcp_intent_id', $intent_id);
                    $order->update_meta_data('_dcp_chain_id', $chain_id);
                    $order->update_meta_data('_dcp_currency', $currency);
                    $order->save();
                }
                break;

            case 'payment.failed':
                $reason = sanitize_text_field($data['reason'] ?? 'Unknown');
                $order->update_status('failed', sprintf(
                    __('DirectCryptoPay: payment failed. Reason: %s', 'directcryptopay'),
                    $reason
                ));
                break;

            case 'payment.expired':
                if ($order->has_status('pending')) {
                    $order->update_status('cancelled', __('DirectCryptoPay: payment intent expired', 'directcryptopay'));
                    // Restore stock
                    wc_increase_stock_levels($order_id);
                }
                break;

            case 'payment.processing':
                $order->add_order_note(sprintf(
                    __('DirectCryptoPay: transaction detected on-chain, awaiting confirmations. TX: %s', 'directcryptopay'),
                    sanitize_text_field($data['tx_hash'] ?? '')
                ));
                break;
        }

        wp_send_json(array('success' => true));
    }

    /**
     * Verify HMAC-SHA256 webhook signature
     *
     * @param string $raw_body Raw request body
     * @param string $signature_header X-DCP-Signature header value (t=<timestamp>,v1=<hmac>)
     * @return bool
     */
    private function verify_webhook_signature($raw_body, $signature_header) {
        if (empty($signature_header)) return false;

        $parts = array();
        foreach (explode(',', $signature_header) as $part) {
            $kv = explode('=', $part, 2);
            if (count($kv) === 2) {
                $parts[$kv[0]] = $kv[1];
            }
        }

        $timestamp = isset($parts['t']) ? $parts['t'] : '';
        $signature = isset($parts['v1']) ? $parts['v1'] : '';

        if (empty($timestamp) || empty($signature)) return false;

        // Check timestamp freshness (5 minutes)
        if (abs(time() - intval($timestamp)) > 300) return false;

        // Compute expected signature
        $payload  = $timestamp . '.' . $raw_body;
        $expected = hash_hmac('sha256', $payload, $this->webhook_secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Get API URL based on environment
     *
     * @return string
     */
    private function get_api_url() {
        if (defined('DCP_ENV')) {
            $env = DCP_ENV;
        } else {
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
                return 'http://localhost:4001';
            case 'staging':
            case 'test':
                return 'https://preview-api.directcryptopay.com';
            default:
                return 'https://api.directcryptopay.com';
        }
    }

    /**
     * Check if this gateway is available
     *
     * @return bool
     */
    public function is_available() {
        if ($this->enabled !== 'yes') return false;
        if (empty($this->integration_id)) return false;
        return true;
    }
}
