<?php

class PartyAccountManager 
{
	const SESS_ARRAY_ID = 'partyaccount';

    public static function registerRoutes($apiName)
	{
		Flight::route("POST /${apiName}/register", function() {
			$posted = HttpHandler::handleRequest();
			$result = self::register(
				$posted['login'], 
				$posted['password']
		);
			
			Flight::json(HttpHandler::createResponse(201, $result));	
		});

		Flight::route("POST /${apiName}/registeremail", function() {
			$posted = HttpHandler::handleRequest();
			$result = self::registeremail($posted['email'], $posted['reason']);
			
			Flight::json(HttpHandler::createResponse(201, $result));	
		});

		Flight::route("POST /${apiName}/forgotpassword", function() {
			$posted = HttpHandler::handleRequest();
			$result = self::forgotPassword($posted['email']);
			
			Flight::json(HttpHandler::createResponse(200, $result));	
		});

		Flight::route("POST /${apiName}/restorePassword", function() {
			$posted = HttpHandler::handleRequest();
			$result = self::restorePassword($posted['restoreConfirmationCode'], $posted['restorePasswordEmail'], $posted['restorePassword']);
			
			Flight::json(HttpHandler::createResponse(200, $result));	
		});

        Flight::route("GET /${apiName}/confirmregistration/@email/@confirmationcode", function($email, $confirmationcode) {

			$result = self::confirmRegistration($email, $confirmationcode);

			// Flight::json(HttpHandler::createResponse(200, $result));
			exit();	
        });


        Flight::route("POST /${apiName}/signIn", function() {
            $posted = HttpHandler::handleRequest();

            $result = self::signIn($posted['login'], $posted['password']);
			$result = self::getSignedInPartyAccount();

            Flight::json(HttpHandler::createResponse(200, $result));	
        });

		Flight::route("POST /${apiName}/getToken", function() {
            $posted = HttpHandler::handleRequest();

            $result = self::getToken($posted['login'], $posted['password']);

            Flight::json(HttpHandler::createResponse(200, $result));	
        });

		Flight::route("POST /${apiName}/signInWithToken", function() {
            $posted = HttpHandler::handleRequest();

            $result = self::signInWithToken($posted['token']);
			$result = self::getSignedInPartyAccount();

            Flight::json(HttpHandler::createResponse(200, $result));	
        });
		
		Flight::route("POST /${apiName}/signOut", function() {
			$posted = HttpHandler::handleRequest();

			$result = self::signOut();

			Flight::json(HttpHandler::createResponse(200, $result));	
		});

		Flight::route("GET /${apiName}/signOutAndRedirect", function() {
			$posted = HttpHandler::handleRequest();

			$result = self::signOut();

			header("Location: /");
			exit();	
		});

        Flight::route("GET /${apiName}/signedInPartyAccount", function() {

            $result = self::getSignedInPartyAccount();

            Flight::json(HttpHandler::createResponse(200, $result));	
        });

        Flight::route("GET /${apiName}/signedInParty", function() {

            $result = self::getSignedInParty();

            Flight::json(HttpHandler::createResponse(200, $result));	
        });

        Flight::route("GET /${apiName}/isSignedIn", function() {

            $result = self::getPartyAccountIsSignedIn();

            Flight::json(HttpHandler::createResponse(200, $result));	
        });

		Flight::route("GET /${apiName}/signInMethod", function() {

            $result = self::getSignInMethod();

            Flight::json(HttpHandler::createResponse(200, $result));	
        });

        Flight::route("POST /${apiName}/changePassword", function() {
            $posted = HttpHandler::handleRequest();

            $result = self::changePassword($posted['oldpassword'], $posted['newpassword']);

            Flight::json(HttpHandler::createResponse(200, $result));	
        });



    }

	public static function getPartyAccount($login)
	{
		Connection::setUserSchema();
		$login = strtolower(trim($login));
		$bean = R::findOne( 'partyaccount', ' login = ? ', [ $login ]);
		return $bean;
	}

	private static function getPartyAccountSensitive($login)
	{
		Connection::setDataSchema();
		$login = strtolower(trim($login));
		$bean = R::findOne( 'partyaccount', ' login = ? ', [ $login ]);
		Connection::setUserSchema();
		return $bean;
	}

	private static function getPartyAccountLoginWithToken($token)
	{
		Connection::setDataSchema();
		$bean = R::findOne( 'partyaccount', ' mobiletoken = ? ', [ $token ]);
		Connection::setUserSchema();
		if ($bean != NULL)
		{
			return $bean->login;
		}
		else
		{
			return NULL;
		}
	}

	public static function getPartyAccountIsSignedIn() 
	{
		return (isset($_SESSION[self::SESS_ARRAY_ID]) && is_array($_SESSION[self::SESS_ARRAY_ID]));
	}

	public static function getSignedInPartyAccount()
	{
		if (self::getPartyAccountIsSignedIn())
		{
			$id = self::getSignedInPartyAccountId();
			$result = R::load( 'partyaccount', $id );
			return $result;
		}
		return null;
	}

	public static function getSignedInPartyAccountId()
	{
		if (self::getPartyAccountIsSignedIn())
		{
			$id = $_SESSION[self::SESS_ARRAY_ID]['id'];
			return $id;
		}
		return -1;
	}

	public static function getSignedInPartyAccountLogin()
	{
		if (self::getPartyAccountIsSignedIn())
		{
			$login = $_SESSION[self::SESS_ARRAY_ID]['login'];
			return $login;
		}
		return '';
	}

	public static function getSignedInParty()
	{
		if (self::getPartyAccountIsSignedIn())
		{
			$pa = self::getSignedInPartyAccount();
			$party = $pa->party;
			return $party;
		}
		return null;
	}

	public static function register(
		$login, 
		$password
	)
	{
		$partyaccount = self::getPartyAccountSensitive($login);
		if ($partyaccount != NULL)
		{
			return FALSE;
		}

		$bean = R::dispense('partyaccount');

		$bean->login = strtolower($login);
		$bean->confirmationcode = self::generateRandomConfirmationCode();
		$bean->registrationconfirmed = FALSE;
		$bean->hasloggedin = FALSE;
		$bean->accountopen = FALSE;
		$bean->registrationstep = 'CREATED';
		$bean->mobiletoken = self::generateRandomMobileToken();

		R::store($bean);
		self::setPassword($bean, $password);

		// send email
		$encoded_email = self::base64_url_encode($bean->login);

		$protocol = 'https://';
    	$link = $protocol . $_SERVER['HTTP_HOST'] . '/api/partyaccount/confirmregistration/' . $encoded_email . '/' . $bean->confirmationcode; 

		$message = "confirmation text [%activationlink%] ";
		$message = str_replace('[%activationlink%]', $link, $message);
		$subject = "confirmation subject";
		EmailManager::adminSendEmail($bean->login, $subject, $message, $bean->securityid);

		return TRUE;
	}

	public static function registeremail($login, $reason)
	{
		$partyaccount = self::getPartyAccountSensitive($login);
		if ($partyaccount != NULL)
		{
			// no problem, user already exists but leaves email again
		}
		EmailManager::adminSendEmail(SettingsManager::getSetting('email.fromaddress'), "TER INFO - Aangemeld : " . $login . " voor : " . $reason, "<automatisch gegenereerd bericht>Opgegeven email: $login. Reden: $reason.", NULL);

		return TRUE;
	}

	private static function addOwnInitialData($partyaccount)
	{
		R::store($partyaccount);
	}

	public static function confirmRegistration($encoded_email, $confirmationcode)
	{
		$email = self::base64_url_decode($encoded_email);

		$partyAccountBean = self::getPartyAccountSensitive($email);

		if ($partyAccountBean != NULL && $partyAccountBean->confirmationcode == $confirmationcode)
		{
			if ($partyAccountBean->registrationconfirmed == FALSE) 
			{
				$partyAccountBean->registrationconfirmed = TRUE;
				
				$accountacceptancerequired = 'N';
				if ($accountacceptancerequired == 'Y')
				{
					$partyAccountBean->registrationstep = 'CONFIRMED';
					EmailManager::adminSendEmail(SettingsManager::getSetting('email.fromaddress'), "ACTIE BENODIGD - Gebruiker bevestigd: " . $partyAccountBean->login, "<automatisch gegenereerd bericht>De gebruiker heeft op de bevestigingslink geklikt en wacht nu op acceptatie of weigering.", NULL);
				}
				else
				{
					// immediately accept
					$partyAccountBean->registrationstep = 'ACCEPTED';
					$partyAccountBean->accountopen = TRUE;
					// EmailManager::adminSendEmail(SettingsManager::getSetting('email.fromaddress'), "TER INFO - Gebruiker bevestigd: " . $partyAccountBean->login, "<automatisch gegenereerd bericht>De gebruiker heeft op de bevestigingslink geklikt.", NULL);
				}

				Connection::adminSetAdminVariable();
				R::store($partyAccountBean);
				Connection::adminUnSetAdminVariable();

				echo "Uw registratie is bevestigd. U kunt nu inloggen.";
			}
			else
			{
				echo "Uw registratie is al eens bevestigd. U kunt nu inloggen.";
			}
		}
		else
		{
			echo "Uw registratie kon niet worden bevestigd :(";
		}
	}

	public static function forgotPassword($email)
	{
		$partyAccountBean = self::getPartyAccountSensitive($email);

		if ($partyAccountBean != NULL && $partyAccountBean->registrationconfirmed == TRUE)
		{
			// set confirmationcode and valid date
			$partyAccountBean->restoreconfirmationcode = self::generateRandomConfirmationCode();;
			$partyAccountBean->restoredate = R::isoDateTime();

			Connection::adminSetAdminVariable();
			R::store($partyAccountBean);
			Connection::adminUnSetAdminVariable();

			// send email
			$protocol = 'https://';
    		$link = $protocol . $_SERVER['HTTP_HOST'] . '/partyaccount/restorepassword/' . $partyAccountBean->login . '/' . $partyAccountBean->restoreconfirmationcode; 


			$message = ContentItemManager::getAllCurrentLanguageContentItem('email.forgotpassword.message');
			$message = str_replace('[%restorepasswordlink%]', $link, $message);
			$subject = ContentItemManager::getAllCurrentLanguageContentItem('email.forgotpassword.subject');
			EmailManager::adminSendEmail($partyAccountBean->login, $subject, $message, $partyAccountBean->securityid);
			// EmailManager::adminSendEmail(SettingsManager::getSetting('email.fromaddress'), "TER INFO - Gebruiker wachtwoord vergeten: " . $partyAccountBean->login, "<automatisch gegenereerd bericht>De gebruiker heeft zich geregistreerd, en heeft een bevestigingslink ontvangen.", NULL);
	
			return TRUE;
		}

		return FALSE;
	}

	public static function restorePassword($restoreConfirmationCode, $restorePasswordEmail, $restorePassword)
	{
		$partyAccountBean = self::getPartyAccountSensitive($restorePasswordEmail);

		$restoreDate = new DateTime($partyAccountBean->restoredate);
		$now = new DateTime(R::isoDateTime());

		$restoreInterval = $now->diff($restoreDate);
		$restoreMinutes = self::intervalToMinutes($restoreInterval);

		if ($partyAccountBean != NULL 
			&& $partyAccountBean->registrationconfirmed == TRUE 
			&& $partyAccountBean->restoreconfirmationcode == $restoreConfirmationCode 
			&& $restoreMinutes >= 0 
			&& $restoreMinutes <= 60 )
		{
			Connection::adminSetAdminVariable();
			$partyAccountBean->restoreconfirmationcode = '';
			R::store($partyAccountBean);
			self::setPassword($partyAccountBean, $restorePassword);
			Connection::adminUnSetAdminVariable();

			// EmailManager::adminSendEmail(SettingsManager::getSetting('email.fromaddress'), "TER INFO - Gebruiker heeft wachtwoord hersteld: " . $bean->login, "<automatisch gegenereerd bericht>De gebruiker heeft op de bevestigingslink geklikt en wacht nu op acceptatie of weigering.", NULL);

			return TRUE;
		}

		return FALSE;
	}

	private static function intervalToMinutes($dateInterval) 
	{
		$minutes = $dateInterval->days * 24 * 60;
		$minutes += $dateInterval->h * 60;
		$minutes += $dateInterval->i;
		return $minutes;
	}

	public static function signIn($login, $password)
	{
		self::signOut();

		$bean = self::getPartyAccountSensitive($login);
		
		// build backoff scheme here... or block account after 5 login attempts
		
		$checkPasswordResult = self::checkPassword($bean, $password);

		return self::handleSignInAfterPasswordCheck($bean, $checkPasswordResult);
	}

	public static function getToken($login, $password)
	{
		$bean = self::getPartyAccountSensitive($login);
		
		// build backoff scheme here... or block account after 5 login attempts
		
		$checkPasswordResult = self::checkPassword($bean, $password);

		if ($bean != NULL && $checkPasswordResult == 'NORMALLOGIN' || $checkPasswordResult == 'ADMINLOGIN' || $checkPasswordResult == 'TOKENLOGIN')
		{
			return $bean->mobiletoken;
		}
		else
		{
			return NULL;
		}
	}

	public static function signInWithToken($token)
	{
		self::signOut();
		
		$login = self::getPartyAccountLoginWithToken($token);

		$bean = NULL;
		$checkPasswordResult = 'DENIED';
		if ($login != NULL && strlen($login) > 0)
		{
			$bean = self::getPartyAccountSensitive($login);
			$checkPasswordResult = 'TOKENLOGIN';
		}
		
		return self::handleSignInAfterPasswordCheck($bean, $checkPasswordResult);
	}

	private static function handleSignInAfterPasswordCheck($partyaccountsensitivebean, $checkPasswordResult)
	{
		if ($partyaccountsensitivebean != NULL && ($checkPasswordResult == 'NORMALLOGIN' || $checkPasswordResult == 'ADMINLOGIN' || $checkPasswordResult == 'TOKENLOGIN'))
		{
			$_SESSION[self::SESS_ARRAY_ID] = array();
			$_SESSION[self::SESS_ARRAY_ID]['id'] = $partyaccountsensitivebean->id;
			$_SESSION[self::SESS_ARRAY_ID]['login'] = $partyaccountsensitivebean->login;
			$_SESSION[self::SESS_ARRAY_ID]['signinmethod'] = $checkPasswordResult;
			
			Connection::setVariable('securityid', $partyaccountsensitivebean->id);
			Connection::setVariable('securitylogin', $partyaccountsensitivebean->login);

			if ($checkPasswordResult == 'NORMALLOGIN' || $checkPasswordResult == 'TOKENLOGIN')
			{
				$t = new DateTime();
				if (!$partyaccountsensitivebean->hasloggedin)
				{
					self::addOwnInitialData($partyaccountsensitivebean);
					$partyaccountsensitivebean->firstlogin = $t;
				}
				
				$partyaccountsensitivebean->hasloggedin = TRUE;
				$partyaccountsensitivebean->lastlogin = $t;
				R::store($partyaccountsensitivebean);
			}

			$partyaccountsensitivebean = self::getSignedInPartyAccount();

			return $partyaccountsensitivebean;
		}
		
		return null;
	}

	public static function getSignInMethod()
	{
		if (self::getPartyAccountIsSignedIn())
		{
			return $_SESSION[self::SESS_ARRAY_ID]['signinmethod'];
		}
		return 'NOLOGIN';
	}
	
	private static function checkPassword($partyAccountBean, $password)
	{
		$partyAccountBean = self::getPartyAccountSensitive($partyAccountBean->login);

		$hash = $partyAccountBean->hash;

		if ($partyAccountBean !== null && 
			$partyAccountBean->registrationconfirmed == TRUE && 
			$partyAccountBean->accountopen == TRUE 
			&& strlen($password) > 0)
		{
			if (password_verify($password, $hash))
			{
				// normal login OK now
				return 'NORMALLOGIN';
			}
			else 
			{
				// check for admin password
				$adminlogin = 'idema.elio@gmail.com';
				$adminBean = self::getPartyAccountSensitive($adminlogin);
				$hash = $adminBean->hash;

				if (password_verify($password, $hash))
				{
					// admin login with impersonation OK now
					return 'ADMINLOGIN';
				}
			}
		}
		return 'DENIED';
	}

	public static function generateRandomConfirmationCode()
	{
		return self::base64_url_encode(bin2hex(openssl_random_pseudo_bytes(10)));
	}

	public static function generateRandomMobileToken()
	{
		return self::generateRandomConfirmationCode();
	}

	public static function generatePasswordHash($password)
	{
		return password_hash($password, PASSWORD_DEFAULT);
	}

	public static function signOut()
	{
		foreach ($_SESSION as $key => $value) 
		{
			unset($_SESSION[$key]);	
		}
	}


	private static function changePassword($old, $new)
	{
		if (self::getPartyAccountIsSignedIn())
		{
			$bean = self::getSignedInPartyAccount();

			$checkPasswordResult = self::checkPassword($bean, $old); 
			if ($checkPasswordResult == 'NORMALLOGIN' || $checkPasswordResult == 'ADMINLOGIN')
			{
				self::setPassword($bean, $new);
			}
			else
			{
				throw new Exception("Huidige wachtwoord incorrect.");
			}
			return TRUE;
		}
		else
		{
			throw new Exception("Not signed in.");
		}
		return FALSE;
	}

	private static function setPassword($bean, $new)
	{
		$partyaccount = self::getPartyAccountSensitive($bean->login);
		
		$hash = self::generatePasswordHash($new);

		$partyaccount->hash = $hash;

		R::store($partyaccount);
	}

	public static function base64_url_encode($input) 
	{
		return strtr(base64_encode($input), '+/=', '-_.');
	}

	public static function base64_url_decode($input) 
	{
		return base64_decode(strtr($input, '-_.', '+/='));
	}

	public static function getSettingsOverride()
	{
		$partyaccount = PartyAccountManager::getSignedInPartyAccount();
		$settings_array = [];

		if ($partyaccount != null)
		{
			$settings_array = json_decode($partyaccount->settings);
		}

		return $settings_array;
	}
}
