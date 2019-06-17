<?php

class RedbeanSessionHandler implements \SessionHandlerInterface
{

	/* requires a table like this:
	  
	  CREATE TABLE `sessions` (
	  `id` varchar(100) NOT NULL,
	  `data` mediumtext NOT NULL,
	  `timestamp` int(255) NOT NULL,
	  PRIMARY KEY (`id`)
	  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

	*/

	  protected $dbTable;

	  public function __construct($dbTable) 
	  {
	  	$this->dbTable = $dbTable;
	  }

	  public function setDbTable($dbTable){
	  	$this->dbTable = $dbTable;
	  }

	  public function open($savePath, $sessionName) {
	  	$limit = time() - (3600 * 24);

	  	$result = R::exec("DELETE FROM {$this->dbTable} WHERE timestamp < ?", array($limit));

	  	return $result; 
	  }

	  public function close() {
	  	return TRUE;
	  }

	  public function read($id) 
	  {
	  	$result = R::getRow("SELECT data FROM {$this->dbTable} WHERE id = ?", array($id));

	  	if ($result !== FALSE)
	  	{
	  		return $result['data'];
	  	}
	  	return FALSE;
	  }

	  public function write($id, $data) {
	  	$result = R::exec("REPLACE INTO {$this->dbTable} VALUES(?, ?, ?)", array($id, $data, time()));
	  	return $result;
	  }

	  public function destroy($id) {
	  	$result = R::exec("DELETE FROM {$this->dbTable} WHERE `id` = ?", array($id));
	  	return $result;
	  }

	  public function gc($max) {
	  	$result = R::exec("DELETE FROM {$this->dbTable} WHERE `timestamp` < ?", array(time() - intval($max)));
	  	return $result;
	  }
	}