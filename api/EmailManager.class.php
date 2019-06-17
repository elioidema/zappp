<?php

use google\appengine\api\mail\Message;

class EmailManager
{
    public static function sendEmail($to, $subject, $messageHTML, $securityid)
	{
		// protocol
		if ($_SERVER['HTTPS'] == 'on') 
		{
			$protocol = 'https://';
			$notProtocol = 'http://';
		} 
		else 
		{
			$protocol = 'http://';
			$notProtocol = 'https://';
		}

		$messageHTML = str_replace($notProtocol, $protocol, $messageHTML);

		$from = SettingsManager::getSetting('email.fromaddress');
		
		$email = R::dispense('email');

		$email->securityid = $securityid;
		$email->emailfrom = $from;
		$email->emailto = $to;
		$email->subject = $subject;
		$email->messagehtml = $messageHTML;
		R::store($email);

		if (Connection::getEnvironment() == 'Production')
		{
			$message = new Message();
			$message->setSender($from);
			$message->addTo($to);
			$message->setSubject($subject);
			$message->setHtmlBody($messageHTML);
			$message->send();
		}
		
	}

	public static function adminSendEmail($to, $subject, $messageHTML, $securityid)
	{
		SecurityManager::adminSetAdminVariable();

		self::sendEmail($to, $subject, $messageHTML, $securityid);

		SecurityManager::adminUnSetAdminVariable();
	}

	public static function sendContentitemEmail($to, $subjectitem, $messageitem, $securityid)
	{
		$subject = ContentItemManager::getAllCurrentLanguageContentItem($subjectitem);		
		$message = ContentItemManager::getAllCurrentLanguageContentItem($messageitem);
		
		self::sendEmail($to, $subject, $message, $securityid);
	}
}
