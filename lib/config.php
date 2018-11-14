<?php
Namespace DBConfig
/** DB CONFIG FILE **/

# IMPORTANT YOU ADJUST THESE SETTINGS TO YOUR ENVIRONMENT FOR LOCAL TESTING #

$DBMS_Host = 'localhost';
$DBMS_User = 'fancy_user';
$DBMS_Pass = 'no_password';
$DBMS_DB   = 'landed';
$ErrorFile = '/var/www/err.log';

# DATABASE CLASS #

class DB
{
	private $mysqli;
	protected $UserName;
	protected $Transaction = false;
	
	function __construct($DBName) 
	{
		global $DBMS_Host, $DBMS_User, $DBMS_Pass, $DBMS_DB;
		$this->mysqli = new \mysqli($DBMS_Host, $DBMS_User, $DBMS_Pass, $DBMS_DB);
		if($this->mysqli->connect_errno)
    {
      $Error = array(
        'error_date'=>date('Y-m-d H:i:s'),
        'error_unix_time'=>time(),
        'error_type'=>'Database Connection Error',
        'error_thrown'=>$this->mysqli->connect_error()
      );
			$void = $this->fWriteError($Error);
    }
	}
	
	function __destruct()
	{
		mysqli_close($this->mysqli);
	}
	
	function closeConnection()
	{
		return @mysqli_close($this->mysqli);
	}
	
	function executeSQL($Query)
	{
		global $_SERVER;
		if($Query == '') return false;
		$Result = $this->mysqli->query($Query);
		if(!$Result) 
		{
      $Error = array(
        'error_date'=>date('Y-m-d H:i:s'),
        'error_unix_time'=>time(),
        'error_type'=>'Database Query Error',
        'error_thrown'=>mysqli_error($this->mysqli),
        'query_attempt'=>$Query,
        'page_source'=>$_SERVER['PHP_SELF']
      );
      
			if($this->Transaction===true)
			{
				$this->TransactionRollBack("SQL Error : ".mysqli_error($this->mysqli));
			}
			return false;
		} 
		else
    {
			return $Result;
    }
	}
	
	function mysqliError()
	{
		return mysqli_error($this->mysqli);
	}
	
	function fWriteError($Arr)
	{
		global $ErrorFile;
		return $this->writeToFile($ErrorFile,json_encode($Arr));		
	}

	function writeToFile($File,$Data)
	{
		return file_put_contents($File, "\n" . $Data, FILE_APPEND);
	}
	
	function Escape($Text)
	{
		if($Text === '') return '';
		elseif(is_array($Text)) return '(Array)';
		else return $this->mysqli->real_escape_string($Text);
	}
	
	function LastInsertID()
	{
		return $this->mysqli->insert_id;
	}
	
	function getFieldsFromTableAsArray($Table,$DB = '')
	{
		$Fields = array();
		$Result = $this->executeSQL("SHOW FIELDS FROM $DB.`$Table` ;");
		if(mysqli_num_rows($Result)>0)
		{
			while($row=mysqli_fetch_assoc($Result))
				$Fields[] = $row['Field'];
		}
		return $Fields;
	}
	
	function AffectedRows()
	{
		return $this->mysqli->affected_rows;
	}
  
	function TransactionStart()
	{
		$this->mysqli->autocommit(FALSE);
		$this->mysqli->begin_transaction();
		$this->Transaction = true;
		return $this->Transaction;
	}
	
	function TransactionRollBack($Reason)
	{
		# ONLY ROLLS BACK IF TRANSACTION IS STILL OPEN AND PRESENT
		if($this->Transaction)
		{
			$this->mysqli->rollback();
			$this->Transaction = false;
			$this->mysqli->autocommit(TRUE);
			$void = $this->fWriteError("Database Transaction Error! ".$Reason, "Check Previous SQL Error thrown ","SQL Transaction");
			return $void;
		}
	}
	
	function TransactionCommit()
	{
		$Var = $this->mysqli->commit();
		$this->Transaction = false;
		$this->mysqli->autocommit(TRUE);
		return $Var;
	}
}
