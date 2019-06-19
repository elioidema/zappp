<?php

class PaymentHandlerMollieAPI implements PaymentHandler
{
    private $payment = NULL;
    private $apikey = NULL;
    private $mollieAPIUrl = "https://api.mollie.com/v2";

    function __construct() 
    {

        if (getenv('DEV_OR_DIST') == 'DEV')
        {
            $this->apikey = getenv('MOLLIE_COM_API_KEY_DEV');
        }
        else
        {
            $this->apikey = getenv('MOLLIE_COM_API_KEY_DIST');
        }
    }

    private function makeGetCall($url)
    {
        $opts = [
            'http' => [
              'method'=>"GET",
              'header'=> "Authorization: Bearer {$this->apikey}\r\n",
              'ignore_errors' => TRUE
            ],
            'ssl' => [
                'verify_peer' => FALSE,
                'verify_peer_name' => FALSE
            ]
        ];
        
        return $this->makeHTTPCall($url, $opts);
    }

    private function makePostCall($url, $postdata)
    {
        $json_data = json_encode($postdata);

        $opts = [
                'http' => [
                    'method' => 'POST',
                    'header' => 
                        "Content-type: application/json\r\n" .
                        "Authorization: Bearer {$this->apikey}\r\n" .
                        "Accept: application/json\r\n" .
                        "Connection: close\r\n" .
                        "Content-length: " . strlen($json_data) . "\r\n",
                'protocol_version' => 1.1,
                'content' => $json_data,
                'ignore_errors' => TRUE
            ],
            'ssl' => [
                'verify_peer' => FALSE,
                'verify_peer_name' => FALSE
            ]
        ];

        // $http_data = http_build_query($postdata);

        // $opts = [   'http' =>
        //             [
        //                 'method'  => 'POST',
        //                 'header'  => "Content-type: application/x-www-form-urlencoded\r\n" .
        //                              "Authorization: Bearer {$this->apikey}\r\n",
        //                 'content' => $http_data,
        //                 'ignore_errors' => TRUE
        //             ],
        //             'ssl' => 
        //             [
        //                 'verify_peer' => FALSE,
        //                 'verify_peer_name' => FALSE
        //             ],
        //         ];
        
        return $this->makeHTTPCall($url, $opts);
    }

    private function makeHTTPCall($url, $opts)
    {
        $context = stream_context_create($opts);
        $file = file_get_contents($this->mollieAPIUrl . $url, false, $context);

        // print_r($file);
        // exit();

        if ($file === false) {
            exit("Unable to connect to $url");
        }

        $returnData = json_decode($file, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            exit("Failed to parse json: " . json_last_error_msg());
        }

        return $returnData;
    }

    public function getProviderName()
    {
        return "MollieAPI";
    }
    
    public function setupPayment($description, $amount, $redirecturl)
    {
        $amount = number_format((float)$amount, 2, '.', '');

        $paymentconfig = [
            "amount" => [
                "currency" => "EUR",
                "value" => "$amount"
            ],
            "description" => $description,
            "redirectUrl" => $redirecturl
            /* "webhookUrl"  => "https://webshop.example.org/mollie-webhook/", */
            /* "method"      => \Mollie\Api\Types\PaymentMethod::IDEAL, */
            /* "issuer"      => $selectedIssuerId, // e.g. "ideal_INGBNL2A" */
            ]
        ;

        $this->payment = $this->makePostCall("/payments", $paymentconfig);
        // print_r($payment);
        // exit();
        return $this->payment['id'];
    }

    public function redirectToPayment()
    {
        // calling script must exit() after this.
        if ($this->payment != NULL)
        {
            $checkoutUrl = $this->payment['_links']['checkout']['href'];
            header("Location: " . $checkoutUrl, true, 303);
        }
        else
        {
            throw new Exception("No payment setup, calls setupPayment first");
        }
    }

    public function checkPayment($paymentid)
    {
        // print_r($paymentid);
        // exit();
        $payment = $this->makeGetCall("/payments/$paymentid");
        // print_r($payment);
        // exit();

        if ($payment['status'] == 'paid')
        {
            return TRUE;
        }
        return FALSE;
    }
}
