<?php
require_once('lib/flight/Flight.php');
require_once('lib/redbean/rb-mysql.php');
require_once('Connection.php');
require_once('RedbeanSessionHandler.class.php');
require_once('HttpHandler.class.php');
require_once('EmailManager.class.php');
require_once('CrudBeanHandler.class.php');
require_once('PaymentHandler.class.php');
require_once('PaymentHandlerMollieAPI.class.php');
require_once('TransactionManager.class.php');

require_once('CrudManager.class.php');
require_once('PartyAccountManager.class.php');

// header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
// header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Credentials");

// Flight::route("OPTIONS /*", function() {
//     exit();	
// });

Connection::connectAsUser();
Connection::setUserSchema();

$handler = new RedbeanSessionHandler('sessions');
session_set_save_handler($handler, true);
session_start();

$partyAccountId = PartyAccountManager::getSignedInPartyAccountId();
$partyAccountLogin = PartyAccountManager::getSignedInPartyAccountLogin();

if ($partyAccountId == -1) {
    $partyAccountId = NULL;
    $partyAccountLogin = NULL;
}

Connection::setVariable('securityid', $partyAccountId);
Connection::setVariable('securitylogin', $partyAccountLogin);

CrudManager::registerRoutes('crud');
PartyAccountManager::registerRoutes('partyaccount');
TransactionManager::registerRoutes('transaction');

// Flight::route('/', function(){
//     echo 'hello world!';
// });


Flight::map('error', function(Exception $e) {
    $message = "<h1>" . $e->getMessage() . "</h1>";
    $message .= "\n\n<pre>" . $e->getTraceAsString() . "</pre>";
    Flight::halt(500, $message);
});

Flight::start();

