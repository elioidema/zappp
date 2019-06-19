<?php

class TransactionManager
{
    public static function registerRoutes($apiName)
	{
        Flight::route("GET /${apiName}/buy/@amount", function($amount) use ($apiName) {
            
            $partyAccountId = PartyAccountManager::getSignedInPartyAccountId();

            if ($partyAccountId > 0)
            {
                $t = self::addTransaction($amount);
                $tid = $t->id;
                $t->from = -1;
                $t->to = $partyAccountId;
                $paymentProviderName = 'MollieAPI';
                $t->paymentprovider = $paymentProviderName;
                CrudBeanHandler::storeBean($t);


                $className = "PaymentHandler$paymentProviderName";
                $paymenthandler = new $className();

                if ($_SERVER['HTTPS'] == 'on') 
                {
                    $protocol = 'https://';
                } 
                else 
                {
                    $protocol = 'http://';
                }
                $redirecturl = $protocol . $_SERVER['HTTP_HOST'] . "/api/${apiName}/buy/paymentconfirmation/${tid}";

                $paymentid = $paymenthandler->setupPayment("Zapp geld storten", $amount, $redirecturl);
                $t->paymentid = $paymentid;
                
                CrudBeanHandler::storeBean($t);

                $paymenthandler->redirectToPayment();

            }
            else
            {
                // send along a message??
                header("Location: /transactions");
            }

            exit();
        }
        );
        
        Flight::route("GET /${apiName}/buy/paymentconfirmation/@tid", function($tid) {
            // Flight::json(HttpHandler::createResponse(201, $array));
            $transaction = self::getTransaction($tid);

            if ($transaction->id > 0)
            {
                
                $paymenthandler = NULL;
                $paymentProviderName = $transaction->paymentprovider;
                $className = "PaymentHandler$paymentProviderName";
                $paymenthandler = new $className();
    

                // check with Mollie if the payment indeed happened
                $paymentid = $transaction->paymentid;
                
                $paymentok = $paymenthandler->checkPayment($paymentid);

                if ($transaction->status == 'NOTPAID' && $paymentok)
                {
                    self::setTransactionAgreementStatusPaid($transaction);
                    CrudBeanHandler::storeBean($transaction);
                    
                    header("Location: /transactions/confirmPayment/$transaction->amount");
                }
                else if ($transaction->status == 'NOTPAID' && !$paymentok)
                {
                    $transaction->status = 'PAYMENTFAILED';
                    CrudBeanHandler::storeBean($transaction);

                    // give feedback?

                    header("Location: /transactions");
                }
                else if ($transaction->status == 'PAID')
                {
                    header("Location: /transactions");
                }
            }
            else
            {
                echo "Overeenkomst niet gevonden.";
            }

            exit();
        }
        );

        Flight::route("GET /${apiName}", function() {
            $array = array();
            $all = self::getTransactions();
            foreach ($all as $bean) 
            {
                $array[] = CrudBeanHandler::exportBean($bean);
            }
            Flight::json(HttpHandler::createResponse(200, $array));
        }
        );
    
        Flight::route("GET /${apiName}/@id", function($id) {
            $result = self::getTransaction($id);
            $array = CrudBeanHandler::exportBean($result);
            Flight::json(HttpHandler::createResponse(200, $array));
        }
        );

        Flight::route("POST /${apiName}", function() {
            $posted = HttpHandler::handleRequest();
            $bean = self::addNewTransaction($posted);
            $array = CrudBeanHandler::exportBean($bean);
            Flight::json(HttpHandler::createResponse(201, $array));
        });

        Flight::route("POST /${apiName}/@id", function($id) {
            $posted = HttpHandler::handleRequest();
            $bean = self::updateTransaction($id, $posted);
            $array = CrudBeanHandler::exportBean($bean);
            Flight::json(HttpHandler::createResponse(200, $array));
        }
        );

    }

    public static function addNewTransaction($posted) 
    {
        $bean = CrudBeanHandler::dispenseBean('transaction');
        CrudBeanHandler::updateBean($bean, $posted);
        CrudBeanHandler::storeBean($bean);
        return $bean;
    }

    public static function updateTransaction($id, $posted) 
    {
        $bean = CrudBeanHandler::findBean('transaction', $id);
        CrudBeanHandler::updateBean($bean, $posted);
        CrudBeanHandler::storeBean($bean);
        return $bean;
    }
    
    public static function addTransaction($amount)
    {
        $partyAccountId = PartyAccountManager::getSignedInPartyAccountId();

        if ($partyAccountId > 0)
        {
            $transaction = CrudBeanHandler::dispenseBean('transactionagreement');
            // can be removed
            $transaction->securityid = $partyAccountId;
            $transaction->amount = $amount;
            $transaction->status = 'NOTPAID';
            $transaction->notpaiddate = R::isoDateTime();

            CrudBeanHandler::storeBean($transaction);

            return $transaction;
        }

        return NULL;
    }

    public static function setTransactionAgreementStatusPaid($transaction)
    {
        $transaction->status = 'PAID';
        $transaction->paiddate = R::isoDateTime();
        CrudBeanHandler::storeBean($transaction);
    }

    
    public static function getTransactions() {
        $all = CrudBeanHandler::findAllBeans('transaction', ' ORDER BY id asc ');
        return $all;
    }

    public static function getTransaction($id) 
    {
        $transaction = CrudBeanHandler::findBean('transaction', $id);
        return $transaction;
    }

    public static function getPaidFortransactionAgreements() 
    {
        $active = CrudBeanHandler::queryBeans('transactionagreement',  ' status = :status AND realnonzeromoneypaid = :realnonzeromoneypaid ', [ ':status' => 'PAID', ':realnonzeromoneypaid' => 'YES' ]);
        return $active; 
    }
}