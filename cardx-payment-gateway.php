<?php
/*
Plugin Name: WooCommerce CardX Payment Gateway
Description: CardX Payment Gateway for WooCommerce, allows you to accept credit cards via CardX.
Version: 1.5.1
Author: Carlvinchi
Author URI: https://www.uvitechgh.com/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: woocommerce-cardx-payment-gateway
*/

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/* This action hook registers the gateway with WooCommerce. */
add_filter('woocommerce_payment_gateways', 'cardx_add_gateway_class');
function cardx_add_gateway_class($gateways)
{
    $gateways[] = 'WC_CardX_Payment_Gateway'; // Class name is here.
    return $gateways;
}

/* Load the cardx gateway class after plugins are loaded. */
add_action('plugins_loaded', 'cardx_init_gateway_class');
function cardx_init_gateway_class()
{
    if (class_exists('WC_Payment_Gateway')) {

        class WC_CardX_Payment_Gateway extends WC_Payment_Gateway
        {
            /*
             * Initialize the gateway class
             *
             * @access public
             * @return void
             */
            public function __construct()
            {
                /*
                 * Plugin Details
                 *
                 * @access public
                 * @var string
                 */
                $this->id = 'cardx'; // Payment Gateway ID

                 $this->icon = '';

                $this->has_fields = true; // Whether or not your gateway requires any additional fields to be saved.

                $this->method_title = 'CardX Gateway'; // Title of the payment gateway displayed on the checkout page.

                $this->method_description = 'Accept credit/debit cards via CardX'; // Description of the payment gateway displayed on the checkout page.


                /*
                 *
                 * Supported Features: products, subscriptions, refunds, and pre-orders.
                 *
                 */
                $this->supports = array(
                    'products', // Whether or not your gateway supports products.
                );

                /* Methods with all the option feilds */
                $this->init_form_fields();


                // Load the settings.
                $this->init_settings();
                $this->title = $this->get_option('title');
                $this->description = $this->get_option('description');
                $this->enabled = $this->get_option('enabled');
                $this->testmode = 'yes' === $this->get_option('testmode');
                $this->api_js_script_url = $this->testmode ? $this->get_option('test_api_url') : $this->get_option('live_api_url');
                $this->account_name = $this->testmode ? $this->get_option('test_account_name') : $this->get_option('live_account_name');


                //This action hook saves the settings.
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));


                // We need a custom javascript file to handle the payment form.
                add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
            }

            // This function is called when the user clicks the "Save Changes" button on the gateway settings page.
            public function init_form_fields()
            {

                $this->form_fields = array(
                    'enabled' => array(
                        'title' => 'Enable/Disable',
                        'label' => 'Enable CardX Gateway',
                        'type' => 'checkbox',
                        'description' => '',
                        'default' => 'no'
                    ),
                    'title' => array(
                        'title' => 'Title',
                        'type' => 'text',
                        'description' => 'This controls the title which the user sees during checkout.',
                        'default' => 'Credit/Debit Card',
                        'desc_tip' => true,
                    ),
                    'description' => array(
                        'title' => 'Description',
                        'type' => 'textarea',
                        'description' => 'This controls the description which the user sees during checkout.',
                        'default' => 'Pay with your credit/debit card via our super-cool payment gateway.',
                    ),
                    'testmode' => array(
                        'title' => 'Test mode',
                        'label' => 'Enable Test Mode',
                        'type' => 'checkbox',
                        'description' => 'Place the payment gateway in test mode using test API url.',
                        'default' => 'yes',
                        'desc_tip' => true,
                    ),
                    'test_api_url' => array(
                        'title' => 'Test API URL',
                        'type' => 'text',
                    ),
                    'live_api_url' => array(
                        'title' => 'Live API URL',
                        'type' => 'text'
                    ),
                    'test_account_name' => array(
                        'title' => 'Test Account Name',
                        'type' => 'text',
                    ),
                    'live_account_name' => array(
                        'title' => 'Live Account Name',
                        'type' => 'text'
                    )
                );
            }

            public function payment_fields()
            {   
                if (is_checkout() && !empty(WC()->session)) {
					// Get order total and other necessary details
        			
					$total_to_pay = WC()->cart->get_cart_contents_total();
					
					
                    // Include the single line of JavaScript code
                    echo '<div id="pay_with_cardx">
                    
                    </div>';

					echo '
                        <input type="hidden" id="response_target" name="cardx_response" />
                        <input type="hidden" id="cardx_token" name="cardx_token" value="" />
                    
                    ';
					
					
                    echo'

                    <script
                    id="cardx_lightbox_script"
                    src="'.$this->api_js_script_url.'"
                    data-button="#pay_with_cardx"
                    data-reversedColors="false"
                    data-account="'.$this->account_name.'"
                    data-target="#response_target"
                    data-mode="payment"
                    data-amount="'.$total_to_pay.'"
                    data-name=""
                    data-billingInclude="true"
                    data-billingRequired="true"
                    data-billingAddress1=""
                    data-billingCity=""
                    data-billingState=""
                    data-billingZip=""
                    data-billingEmail=""
                    data-companyNameEditable="false"
                    data-companyNameLabel=""
                    data-companyName=""
                    data-invoiceIdentifierEditable="false"
                    data-invoiceIdentifierLabel=""
                    data-invoiceIdentifier=""
                    data-accountIdentifierEditable="false"
                    data-accountIdentifierLabel=""
                    data-accountIdentifier=""
                    data-displayConfirmation="true"
                    >
                    </script>
                    ';
                }
				
        
            }

            public function payment_scripts()
            {
					
        			
                // Only load the javascript file on the checkout page.
                if(is_checkout()) {
                    // and this is our custom JS in your plugin directory that works with our payment gateway
                    wp_enqueue_script( 'woocommerce_cardx', plugin_dir_url(__FILE__) . 'assets/js/cardx.js', array( 'jquery'), '1.0.0', true  );
                    
    
                }
            }

            public function process_payment($order_id)
            {
                // Implement payment processing logic here
                global $woocommerce;
				$order = new WC_order($order_id);

                 // Retrieve the value of the cardx_token field from the $_POST array
                $cardx_token = isset($_POST['cardx_token']) ? sanitize_text_field($_POST['cardx_token']) : '';
                
                if($cardx_token == "success") {

                    // we received the payment
                    $order->payment_complete();
                    
                    // some notes to customer (replace true with false to make it private)
                    $order->add_order_note( 'Hey, your order is paid! Thank you!', true );
                    // Empty cart
                    $woocommerce->cart->empty_cart();
                    
                    // Redirect to the thank you page
                    return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url( $order ),
                    );
				
                }
                else {
                    //transiction fail
                    wc_add_notice( 'Payment failed, order could not be completed.', 'error' );
                    $order->add_order_note( 'Error: '. 'Payment failed, order could not be completed.', true );
                  }
				
				
            }
        }
    }
}
?>