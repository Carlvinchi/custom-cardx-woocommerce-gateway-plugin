jQuery(function($) {
    
    //store the checkout form in a variable
    const checkoutForm = $('form.woocommerce-checkout');

    const successCallback = function(data) {
        
        // add a token to our hidden input field
         checkoutForm.find('#cardx_token').val(data.pi_response_status)
        // deactivate the tokenRequest function event
         checkoutForm.off('checkout_place_order', paymentResponse)
        // submit the form now
         checkoutForm.submit()
    };

    const errorCallback = function(data) {
        alert("An error occurred while processing your payment. Please try again later.")
    };

    const paymentResponse = function() {
        // here we will check the payment response from CardX and fire successCallback() or errorCallback()

        var data = document.getElementById('response_target').value // the response from the payment gateway was stored in an input hidden field
			
        if (data != null) {
			var data_ob = JSON.parse(data)
            //data.response = "auth_complete"
           // data.pi_response_status = "success"

            if (data_ob.pi_response_status == 'success' && data_ob.response == 'auth_complete') {
                successCallback(data_ob);
            } else {
                errorCallback(data_ob);
            }
        }
        
        return false;
    };

    //add the event listener to the checkout form, we call the paymentResponse() function when the Place Order button is clicked
    // checkout_place_order is the event name that WooCommerce fires when the Place Order button is clicked
    checkoutForm.on('checkout_place_order', paymentResponse);
});
