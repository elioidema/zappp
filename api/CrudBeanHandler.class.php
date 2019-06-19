<?php

class CrudBeanHandler 
{
	public static $storeAsObject;

	static function init()
	{
	}

	public static function exportBean($bean)
	{
		$b = $bean->export();

		$table = $bean->getMeta('type');

		if (isset(self::$storeAsObject[$table]))
		{
			foreach (self::$storeAsObject[$table] as $key => $value) 
			{
				$b[$value] = json_decode($b[$value]);
			}
		}

		return $b;
	}

	public static function updateBean($bean, $posted)
	{
		$table = $bean->getMeta('type');

		foreach ($posted as $key => $value) 
		{
			if ($key !== 'id')
			{
				if (isset(self::$storeAsObject[$table]) && in_array($key, self::$storeAsObject[$table]))
				{
					$value = json_encode($value);
				}
				$bean->$key = $value;
			}
		}
	}
	
	public static function queryBeans($table, $condition, $variables)
	{
		$beans  = R::find( $table, $condition, $variables );
		return $beans;
	}


	public static function findBean($table, $id)
	{
		$bean = R::load($table, $id);
		return $bean;
	}

	public static function findAllBeans($table, $orderBy = '')
	{
		if ($orderBy == '') {
			$all = R::findAll($table);
		} else {
			$all = R::findAll($table, $orderBy);
		}
		return $all;		
	}

	public static function trashBean($bean)
	{
		R::trash($bean);
	}

	public static function storeBean($bean)
	{
		R::store($bean);
	}

	public static function dispenseBean($table)
	{
		return R::dispense($table);
	}
}


CrudBeanHandler::init();
