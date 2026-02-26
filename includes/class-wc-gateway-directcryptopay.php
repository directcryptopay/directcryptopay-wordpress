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
 * Handles WooCommerce payment processing through DirectCryptoPay widget
 */
class WC_Gateway_DirectCryptoPay extends WC_Payment_Gateway {

    /**
     * Integration ID from plugin settings
     * @var string
     */
    public $integration_id;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id                 = 'directcryptopay';
        $this->icon               = ''; // URL to icon (optional)
        $this->has_fields         = false;
        $this->method_title       = 'DirectCryptoPay';
        $this->method_description = 'Accept cryptocurrency payments (ETH, USDC, USDT) directly to your wallet via DirectCryptoPay.';

        // Declare support for WooCommerce features (required for blocks)
        $this->supports = array(
            'products'
        );

        // Load settings
        $this->init_form_fields();
        $this->init_settings();

        // Define user-facing settings
        $this->title        = $this->get_option('title');
        $this->description  = $this->get_option('description');
        $this->enabled      = $this->get_option('enabled');

        // Get Integration ID from global plugin settings
        $this->integration_id = get_option('dcp_public_id', '');

        // Hooks
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));

        // Webhook handler (optional - for backend verification)
        add_action('woocommerce_api_wc_gateway_directcryptopay', array($this, 'webhook_handler'));
    }

    /**
     * Initialize Gateway settings form fields
     */
    public function init_form_fields() {
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
                'description' => 'Payment method title that users see during checkout.',
                'default'     => 'Pay with Crypto',
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => 'Description',
                'type'        => 'textarea',
                'description' => 'Payment method description that users see during checkout.',
                'default'     => 'Pay securely with Ethereum, USDC, or USDT using your crypto wallet.',
                'desc_tip'    => true,
            ),
            'currency' => array(
                'title'       => 'Default Currency',
                'type'        => 'select',
                'description' => 'Select the default cryptocurrency to accept.',
                'default'     => 'ETH',
                'desc_tip'    => true,
                'options'     => array(
                    'ETH'  => 'Ethereum (ETH)',
                    'USDC' => 'USD Coin (USDC)',
                    'USDT' => 'Tether (USDT)',
                )
            ),
            'integration_info' => array(
                'title'       => 'Integration ID',
                'type'        => 'title',
                'description' => 'Using Integration ID from DirectCryptoPay settings: <code>' . get_option('dcp_public_id', 'Not configured') . '</code><br><a href="' . admin_url('admin.php?page=dcp-settings') . '">Configure Integration ID</a>',
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

        // Validate Integration ID
        if (empty($this->integration_id)) {
            wc_add_notice('DirectCryptoPay is not configured. Please contact the store administrator.', 'error');
            return array(
                'result'   => 'failure',
                'messages' => 'Payment gateway not configured'
            );
        }

        // Mark as pending payment
        $order->update_status('pending', __('Awaiting cryptocurrency payment', 'directcryptopay'));

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
     * Receipt page
     *
     * Display the DirectCryptoPay widget on the order-pay page
     *
     * @param int $order_id Order ID
     */
    public function receipt_page($order_id) {
        $order = wc_get_order($order_id);

        if (!$order) {
            echo '<p>' . __('Order not found.', 'directcryptopay') . '</p>';
            return;
        }

        $amount = $order->get_total();
        $currency = $this->get_option('currency', 'ETH');
        $order_key = $order->get_order_key();
        $return_url = $this->get_return_url($order);
        $cancel_url = $order->get_cancel_order_url();

        // Generate unique container ID
        $container_id = 'dcp-wc-payment-' . $order_id;

        ?>
        <div id="<?php echo esc_attr($container_id); ?>" style="text-align: center; padding: 40px 20px;">
            <h2 style="color: #333; margin-bottom: 20px;">Complete Your Payment</h2>
            <p style="color: #666; margin-bottom: 30px;">
                Order Total: <strong><?php echo wc_price($amount); ?></strong><br>
                Payment will be processed via cryptocurrency
            </p>

            <div id="dcp-payment-status" style="margin: 20px 0;">
                <div style="display: inline-block; padding: 15px 30px; background: #f0f0f0; border-radius: 8px;">
                    <span style="font-size: 16px; color: #666;">Initializing payment widget...</span>
                </div>
            </div>

            <p style="margin-top: 20px;">
                <a href="<?php echo esc_url($cancel_url); ?>" style="color: #999; text-decoration: none;">
                    Cancel and return to cart
                </a>
            </p>
        </div>

        <script>
        (function() {
            console.log('[DCP WooCommerce] Initializing payment for order <?php echo $order_id; ?>');

            // Update status message
            function updateStatus(message, type = 'info') {
                const statusDiv = document.getElementById('dcp-payment-status');
                if (!statusDiv) return;

                let bgColor = '#f0f0f0';
                let textColor = '#666';

                if (type === 'success') {
                    bgColor = '#d1fae5';
                    textColor = '#065f46';
                } else if (type === 'error') {
                    bgColor = '#fee2e2';
                    textColor = '#991b1b';
                } else if (type === 'processing') {
                    bgColor = '#dbeafe';
                    textColor = '#1e40af';
                }

                statusDiv.innerHTML = `
                    <div style="display: inline-block; padding: 15px 30px; background: ${bgColor}; border-radius: 8px;">
                        <span style="font-size: 16px; color: ${textColor};">${message}</span>
                    </div>
                `;
            }

            // Initialize DCP and start payment
            function initializePayment() {
                if (typeof DCP === 'undefined') {
                    console.log('[DCP WooCommerce] DCP not loaded yet, retrying...');
                    setTimeout(initializePayment, 200);
                    return;
                }

                console.log('[DCP WooCommerce] DCP loaded, initializing...');

                // Initialize with test API URL
                DCP.init({
                    baseURL: 'https://test-api.directcryptopay.com'
                });

                console.log('[DCP WooCommerce] DCP initialized, starting payment...');
                updateStatus('Preparing payment...', 'processing');

                // Start payment
                DCP.Payment({
                    integrationId: '<?php echo esc_js($this->integration_id); ?>',
                    amount_usd: '<?php echo $amount; ?>',
                    currency: '<?php echo esc_js($currency); ?>',
                    chainId: 11155111,  // Sepolia testnet
                    onStatus: function(status) {
                        console.log('[DCP WooCommerce] Payment status:', status);

                        switch(status.type) {
                            case 'awaiting_wallet_connection':
                                updateStatus('Please connect your wallet...', 'processing');
                                break;
                            case 'wallet_connected':
                                updateStatus('Wallet connected! Preparing transaction...', 'processing');
                                break;
                            case 'estimating_gas':
                                updateStatus('Estimating gas fees...', 'processing');
                                break;
                            case 'awaiting_signature':
                                updateStatus('Please confirm the transaction in your wallet...', 'processing');
                                break;
                            case 'transaction_submitted':
                                updateStatus('Transaction submitted! Verifying payment...', 'processing');
                                break;
                            case 'verifying':
                                updateStatus('Verifying payment on blockchain...', 'processing');
                                break;
                            case 'confirmed':
                                updateStatus('Payment confirmed! Redirecting...', 'success');

                                // Mark order as processing/completed via AJAX
                                markOrderPaid(status.txHash, status.paymentId);

                                // Redirect to success page
                                setTimeout(function() {
                                    window.location.href = '<?php echo esc_js($return_url); ?>';
                                }, 2000);
                                break;
                            case 'failed':
                                updateStatus('Payment failed. Please try again.', 'error');
                                break;
                            case 'rejected':
                                updateStatus('Payment rejected. You can try again or cancel.', 'error');
                                break;
                        }
                    },
                    onError: function(error) {
                        console.error('[DCP WooCommerce] Payment error:', error);
                        updateStatus('Payment error: ' + error.message, 'error');
                    }
                }).catch(function(error) {
                    console.error('[DCP WooCommerce] Payment failed:', error);
                    if (error.message && !error.message.includes('User rejected')) {
                        updateStatus('Payment failed: ' + error.message, 'error');
                    }
                });
            }

            // Mark order as paid via AJAX
            function markOrderPaid(txHash, paymentId) {
                const data = new FormData();
                data.append('action', 'dcp_mark_order_paid');
                data.append('order_id', '<?php echo $order_id; ?>');
                data.append('order_key', '<?php echo esc_js($order_key); ?>');
                data.append('tx_hash', txHash);
                data.append('payment_id', paymentId);

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    body: data
                })
                .then(response => response.json())
                .then(result => {
                    console.log('[DCP WooCommerce] Order marked as paid:', result);
                })
                .catch(error => {
                    console.error('[DCP WooCommerce] Failed to mark order as paid:', error);
                });
            }

            // Start initialization
            initializePayment();
        })();
        </script>
        <?php
    }

    /**
     * Webhook handler (optional)
     *
     * Receive backend notifications from DirectCryptoPay
     */
    public function webhook_handler() {
        // Get raw POST data
        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);

        // Log webhook
        error_log('[DCP WooCommerce] Webhook received: ' . print_r($data, true));

        // Verify webhook signature (if implemented)
        // TODO: Implement HMAC verification

        // Process webhook
        if (isset($data['order_id']) && isset($data['status'])) {
            $order = wc_get_order($data['order_id']);

            if ($order) {
                if ($data['status'] === 'confirmed') {
                    $order->payment_complete($data['tx_hash']);
                    $order->add_order_note(
                        sprintf(
                            __('DirectCryptoPay payment confirmed. TX Hash: %s', 'directcryptopay'),
                            $data['tx_hash']
                        )
                    );
                }
            }
        }

        wp_send_json(array('success' => true));
    }

    /**
     * Check if this gateway is available
     *
     * @return bool
     */
    public function is_available() {
        if ($this->enabled !== 'yes') {
            return false;
        }

        // Check if Integration ID is configured
        if (empty($this->integration_id)) {
            return false;
        }

        return true;
    }
}
