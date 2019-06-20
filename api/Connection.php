<?php

class Connection {

	public static function connectAsUser()
	{
		self::connectAs('User');
	}

	public static function connectAsSystem()
	{
		self::connectAs('System');
	}

	public static function disconnect()
	{
		R::close();
	}

	private static function connectAs($connectAs)
	{
		$environment = self::getEnvironment();
		
		$isConnected = FALSE; //R::testConnection();

		if ($isConnected)
		{
			throw new Exception('Already connected.');
		}
		else
		{
			
			// set up redbean connection
			if ($environment == 'Development' && $connectAs == 'User')
			{
				$dsn = getenv('MYSQL_DSN_DEV_USER');
				$user = getenv('MYSQL_USER_DEV_USER');
				$pwd = getenv('MYSQL_PASSWORD_DEV_USER');
			}
			else if ($environment == 'Production' && $connectAs == 'User')
			{
				$dsn = getenv('MYSQL_DSN_PROD_USER');
				$user = getenv('MYSQL_USER_PROD_USER');
				$pwd = getenv('MYSQL_PASSWORD_PROD_USER');
			}
			else if ($environment == 'Development' && $connectAs == 'System')
			{
				$dsn = getenv('MYSQL_DSN_DEV_SYSTEM');
				$user = getenv('MYSQL_USER_DEV_SYSTEM');
				$pwd = getenv('MYSQL_PASSWORD_DEV_SYSTEM');
			}
			else if ($environment == 'Production' && $connectAs == 'System')
			{
				$dsn = getenv('MYSQL_DSN_PROD_SYSTEM');
				$user = getenv('MYSQL_USER_PROD_SYSTEM');
				$pwd = getenv('MYSQL_PASSWORD_PROD_SYSTEM');
			}

			R::setup($dsn, $user, $pwd);	
			// R::fancyDebug(TRUE);
		}
	}

	public static function setVariable($variable, $value)
	{
		R::exec("SET @:variable := :value", [':variable' => $variable, ':value' => $value]);	
	}

	public static function unSetVariable($variable)
	{
		R::exec("SET @:variable := NULL", [':variable' => $variable]);	
	}

	public static function setUserSchema()
	{
		R::exec("USE zappp;");	
	}

	public static function setDataSchema()
	{
		R::exec("USE zappp;");	
	}

	public static function setSystemSchema()
	{
		R::exec("USE drome_system;");
	}

	public static function setReportSchema()
	{
		R::exec("USE drome_report;");
	}


	public static function getEnvironment()
	{
		if (strpos(getenv('SERVER_SOFTWARE'), 'Development') === 0)
		{
			return 'Development';
		}
		else
		{
			return 'Production';	
		}
	}

}

