<?php

interface PaymentHandler
{
    public function getProviderName();
    
    public function setupPayment($description, $amount, $redirecturl);
    
    // calling script must exit() after this.
    public function redirectToPayment();
    
    public function checkPayment($paymentid);
}
