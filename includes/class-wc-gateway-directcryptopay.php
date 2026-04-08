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
        <div id="<?php echo esc_attr($container_id); ?>" style="text-align: center; padding: 40px 20px;">
            <h2 style="color: #333; margin-bottom: 20px;"><?php esc_html_e('Complete Your Payment', 'directcryptopay'); ?></h2>
            <p style="color: #666; margin-bottom: 30px;">
                <?php esc_html_e('Order Total:', 'directcryptopay'); ?> <strong><?php echo wc_price($amount); ?></strong><br>
                <?php esc_html_e('Payment will be processed via cryptocurrency', 'directcryptopay'); ?>
            </p>

            <div id="dcp-payment-status" style="margin: 20px 0;">
                <div style="display: inline-block; padding: 15px 30px; background: #f0f0f0; border-radius: 8px;">
                    <span style="font-size: 16px; color: #666;"><?php esc_html_e('Initializing payment widget...', 'directcryptopay'); ?></span>
                </div>
            </div>

            <p style="margin-top: 20px;">
                <a href="<?php echo esc_url($cancel_url); ?>" style="color: #999; text-decoration: none;">
                    <?php esc_html_e('Cancel and return to cart', 'directcryptopay'); ?>
                </a>
            </p>
        </div>

        <script>
        (function() {
            function updateStatus(message, type) {
                var statusDiv = document.getElementById('dcp-payment-status');
                if (!statusDiv) return;
                var colors = {
                    info:       { bg: '#f0f0f0', text: '#666' },
                    success:    { bg: '#d1fae5', text: '#065f46' },
                    error:      { bg: '#fee2e2', text: '#991b1b' },
                    processing: { bg: '#dbeafe', text: '#1e40af' }
                };
                var c = colors[type] || colors.info;
                statusDiv.innerHTML = '<div style="display:inline-block;padding:15px 30px;background:' + c.bg + ';border-radius:8px;"><span style="font-size:16px;color:' + c.text + ';">' + message + '</span></div>';
            }

            function initializePayment() {
                if (typeof DCP === 'undefined') {
                    setTimeout(initializePayment, 200);
                    return;
                }

                DCP.init({ baseURL: '<?php echo esc_js($wc_api_url); ?>' });
                updateStatus('Preparing payment...', 'processing');

                DCP.Payment({
                    integrationId: '<?php echo esc_js($this->integration_id); ?>',
                    amount_usd: '<?php echo esc_js($amount); ?>',
                    metadata: {
                        order_id: '<?php echo esc_js($order_id); ?>',
                        order_key: '<?php echo esc_js($order_key); ?>'
                    },
                    onStatus: function(status) {
                        switch(status.type) {
                            case 'submitted':
                                updateStatus('Transaction submitted! Verifying on blockchain...', 'processing');
                                break;
                            case 'confirmed':
                                updateStatus('Payment confirmed! Redirecting...', 'success');
                                markOrderPaid(status.txHash, status.paymentId);
                                setTimeout(function() {
                                    window.location.href = '<?php echo esc_js($return_url); ?>';
                                }, 2000);
                                break;
                            case 'failed':
                                updateStatus('Payment failed. Please try again.', 'error');
                                break;
                            case 'rejected':
                                updateStatus('Transaction rejected. You can try again or cancel.', 'error');
                                break;
                        }
                    }
                }).catch(function(error) {
                    if (error.message && !error.message.includes('User rejected')) {
                        updateStatus('Payment error: ' + error.message, 'error');
                    }
                });
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
