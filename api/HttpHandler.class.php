<?php

class HttpHandler
{
	
	public static $responses = [
	200 => [
	'success' => [
	'code' => 200,
	'status' => 'OK',
	],
	],
	201 => [
	'success' => [
	'code' => 201,
	'status' => 'Created',
	],
	],
	204 => [
	'error' => [
	'code' => 204,
	'status' => 'No Content',
	],
	],
	400 => [
	'error' => [
	'code' => 400,
	'status' => 'Bad Request',
	],
	],
	403 => [
	'error' => [
	'code' => 403,
	'status' => 'Forbidden',
	],
	],
	404 => [
	'error' => [
	'code' => 404,
	'status' => 'Not Found',
	],
	],
	409 => [
	'error' => [
	'code' => 409,
	'status' => 'Conflict',
	],
	],
	503 => [
	'error' => [
	'code' => 503,
	'status' => 'Service Unavailable',
	],
	],
	];
	
	public static function handleRequest() {
		
		$data = file_get_contents('php://input');
		
		if (preg_match('~^\x78[\x01\x5E\x9C\xDA]~', $data) > 0)
		{
			$data = gzuncompress($data);
		}
		
		if ((array_key_exists('CONTENT_TYPE', $_SERVER) === true) && (empty($data) !== true))
		{
			if (strncasecmp($_SERVER['CONTENT_TYPE'], 'application/json', 16) === 0)
			{
				$posted = json_decode($data, true);
			}
			else if ((strncasecmp($_SERVER['CONTENT_TYPE'], 'application/x-www-form-urlencoded', 33) === 0) && (strncasecmp($_SERVER['REQUEST_METHOD'], 'PUT', 3) === 0))
			{
				parse_str($data, $posted);
			}
		}
		
		if ((isset($posted) !== true) || (is_array($posted) !== true))
		{
			$posted = [];
		}
		unset($data);
		
		return $posted;
	}
	
	
	public static function createResponse($code, $data)
	{
		$result = HttpHandler::$responses[$code];
		$result['data'] = $data;
		
		return $result;
	}
}
