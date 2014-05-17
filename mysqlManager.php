<?php 
class dataObject
{
	
}
class mysqlManager
{
	
	private 	$host = '127.0.0.1'; #host of the database
	private 	$database_name = ''; #the name of the database\
	private 	$user = ''; #user of the database
	private 	$password = ''; #password of the database
	#private 	$rowcount; #affected rows
	private	 	$data = Array(
					"data" 		=> NULL, 
					"rowcount" 	=> NULL,
					"lastid"	=> NULL
				); #result of the rows
	private 	$error; #error code
	private 	$errormessage; #mensaje de error
	
	/*
	 The construct is used to connect to differente databases.
	 */
	function __construct($host=NULL,$database_name=NULL,$user=NULL,$password=NULL)
	{
		if(!empty($host))
		$this->$host = $host;
		if(!empty($database_name))
		$this->$database_name = $host;
		if(!empty($user))
		$this->$user = $host;
		if(!empty($password))
		$this->$password = $host;
	}
	private function setError($iderror)
	{
		$this->error = $iderror;
	}
	
	private function setData($result=NULL, $rowcount=NULL, $lastid=NULL)
	{
		$this->data["data"] = $result;
		$this->data["rowcount"] = $rowcount;
		$this->data["lastid"] = $lastid;
	}
	/*Get the data, the rowcount and lastid*/
	public function getData()
	{
		return (object)$this->data;
	}
	
	/*Error handler from this class (need more work)*/
	public function getErrorMessage()
	{
		switch ($this->error) {
		    case 1:
		        return "The Query statement is empty";
		        break;
			case 2:
		        return "Table can't be null";
		        break;
			default:
		        return "no error";
		        break;
		}
	}
	
	/*The connection to the database*/
	private function conectionDatabase()
	{
		$host = $this->host;
		$database_name = $this->database_name;
		$user = $this->user;
		$password = $this->password;
		$cn = new \PDO( "mysql:host=$host;dbname=$database_name", 
                        "$user", 
                        "$password", 
                        array(
                            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, 
                            \PDO::ATTR_PERSISTENT => false, 
                            \PDO::MYSQL_ATTR_INIT_COMMAND => 'set names utf8'
                        )
                    );
		return $cn; 
	}
	
	#method for simple select query statements
	public function queryRecord($query=NULL)
	{
		if(!empty($query))
		{
			$con = $this->conectionDatabase();
			$query = $con->prepare($query);
			$query->execute();
			$data = $query->fetchAll(PDO::FETCH_OBJ);
			$rowcount = $query->rowCount();
			$this->setData($data, $rowcount, NULL);
			return $this->getData();
		}
		else 
		{
			$this->setError(1);
		}
	}
	
	# mothod for select query statement
	# $table is the table name
	# $records is an array with all the columns you want to select, if is empty it will select all columns ,ex: array('id','name','address')
	/*
	 * $where is an associative array which have the $column => $value
	 * ex: 
	 * 	array(
	 *   'name' => '%Jose%',
	 *   'date' => '2014/01/01'
	 *  )
	 * 
	 *  $order is an associative array which have the column => 'asc/desc'
	 *  ex:
	 *   array(
	 *    'name' => 'asc',
	 *    'address' => 'desc'
	 *   )
	 *   
	 *   $limit is an array which have the limit of the SQL statement
	 *   ex:
	 *   array(10,15) = LIMIT 10,15
	 */
	public function selectRecord($table, $records=null, $where=NULL, $order=NULL, $limit=NULL)
	{
		#If table is empty, it will return an error message
		if(!empty($table))
		{
			$whe = '';
			$lim = '';
			$ord = '';
			#if $records are empty, will select all columns
			if(empty($records))
			{
				$cols = '*';
			}
			else
			{
				$cols = implode(',',$records);
				
			}
			#if there is a $where
			if(!empty($where))
			{
				#Where statement construction
				foreach($where as $column => $value)
				{
					#if the value is numeric
					if(is_numeric($value))
					{ 
				 		$whe = "$whe AND $column = :$column ";
					}
					#if the value is a decimal
					else if(is_float($value))
					{
						$whe = "$whe AND $column = :$column ";
					}
					#if the value is a string
					else if(is_string($value))
					{
						$whe = "$whe AND $column like :$column ";
					}
					#if the value is empty
					else if(empty($value))
					{
						$whe = "$whe AND $column is null ";
					}
				}
			}
			#if there is an $order
			if(!empty($order))
			{
				$ord = "ORDER BY ";
				foreach($order as $column => $or)
				{
					#for each value in the array it will construct an order statement
					$ord = $ord . " $column $or,";
				}
				#remove the last comma
				$ord = substr($ord, 0, -1);
			}
			#if there is a limit
			if(!empty($limit))
			{
				#construction of the limit statement
				$lim = 'LIMIT ' . $limit[0] . $limit[1];
			}
			
			
			$con = $this->conectionDatabase();
			$query = $con->prepare("SELECT $cols FROM $table WHERE 1 $whe $ord $lim");
			if(!empty($where))
			{
				foreach($where as $column => $value)
				{
					#Bind values depending if is numeric, float or string
					if(is_numeric($value))
					{
						$query->bindValue(":$column",$value, PDO::PARAM_INT);
					}
					if(is_float($value))
					{
						$query->bindValue(":$column",$value, PDO::PARAM_STR);
					}
					if(is_string($value))
					{
						$query->bindValue(":$column",$value, PDO::PARAM_STR);
					}
				}	
			}
			#execute query
			$query->execute();
			$rowcount = $query->rowCount();
			$data = $query->fetchAll(PDO::FETCH_OBJ);
			#set $data, $rowcount values
			$this->setData($data, $rowcount, NULL);
			return $this->getData();
		}
		else
		{
			$this->setError(2);
		}
	}
	
	/*Method for insert record*/
	# mothod for select query statement
	# $table is the table name
	# $records is an array with all the columns you want to select, if is empty it will select all columns ,ex: array('id','name','address')
	public function insertRecord($table, $records)
	{
		if(!empty($table))
		{
			if(!empty($records))
			{
				$rec = '';
				$vals = '';
				foreach($records as $column => $value)
				{
					$rec = $rec . "$column,";
				}
				$rec = substr($rec, 0, -1);
				
				foreach($records as $column => $value)
				{
					$vals = $vals . ":$column,";
				}
				$vals = substr($vals, 0, -1);
				
				$con = $this->conectionDatabase();
				$query = $con->prepare("INSERT INTO $table(".$rec.") values (".$vals.")");
				foreach($records as $column => $value)
				{
					if(is_numeric($value))
					{
						$query->bindValue(":$column",$value, PDO::PARAM_INT);
					}
					if(is_float($value))
					{
						$query->bindValue(":$column",$value, PDO::PARAM_STR);
					}
					if(is_string($value))
					{
						$query->bindValue(":$column",$value, PDO::PARAM_STR);
					}
					if(empty($value))
					{
						$query->bindValue(":$column",NULL, PDO::PARAM_INT);
					}
					
				}
				$query->execute();
				$rowcount = $query->rowCount();
				$lastid = $con->lastInsertId();
				$this->setData(NULL, $rowcount, $lastid);
				return $this->getData();
			}
		}
	}
	
	/*Method for update record*/
	# mothod for select query statement
	# $table is the table name
	# $records is an array with all the columns you want to select, if is empty it will select all columns ,ex: array('id','name','address')
	/*
	 * $where is an associative array which have the $column => $value
	 * ex: 
	 * 	array(
	 *   'name' => '%Jose%',
	 *   'date' => '2014/01/01'
	 *  )
	 */
	public function updateRecord($table, $records, $where=NULL)
	{
		if(!empty($table))
		{
			if(!empty($records))
			{
				$whe = '';
				$rec = 'SET ';
				foreach($records as $column => $value)
				{
					$rec = $rec . "$column = :$column,";
				}
				$rec = substr($rec, 0, -1);
			}
			foreach($where as $column => $value)
			{
				if(is_numeric($value))
				{ 
			 		$whe = "$whe AND $column = :$column ";
				}
				else if(is_float($value))
				{
					$whe = "$whe AND $column = :$column ";
				}
				else if(is_string($value))
				{
					$whe = "$whe AND $column like :$column ";
				}
				else if(empty($value))
				{
					$whe = "$whe AND $column is null ";
				}
			}
			$con = $this->conectionDatabase();
			$query = $con->prepare("UPDATE $table $rec WHERE 1 $whe");
			foreach($records as $column => $value)
			{
				if(is_numeric($value))
				{
					$query->bindValue(":$column",$value, PDO::PARAM_INT);
				}
				if(is_float($value))
				{
					$query->bindValue(":$column",$value, PDO::PARAM_STR);
				}
				if(is_string($value))
				{
					$query->bindValue(":$column",$value, PDO::PARAM_STR);
				}
				if(empty($value))
				{
					$query->bindValue(":$column",NULL, PDO::PARAM_INT);
				}
			}
			foreach($where as $column => $value)
			{
				if(is_numeric($value))
				{
					$query->bindValue(":$column",$value, PDO::PARAM_INT);
				}
				if(is_float($value))
				{
					$query->bindValue(":$column",$value, PDO::PARAM_STR);
				}
				if(is_string($value))
				{
					$query->bindValue(":$column",$value, PDO::PARAM_STR);
				}
			}
			$query->execute();
			$rowcount = $query->rowCount();
			$this->setData(NULL, $rowcount, NULL);
			return $this->getData();
			
		}
	}
	
	/*method for delete record*/
	# mothod for select query statement
	# $table is the table 
	/*
	 * $where is an associative array which have the $column => $value
	 * ex: 
	 * 	array(
	 *   'name' => '%Jose%',
	 *   'date' => '2014/01/01'
	 *  )
	 * 
	 */
	public function deleteRecord($table, $where=NULL)
	{
		if(!empty($table))
		{
			$whe = '';
			foreach($where as $column => $value)
			{
				if(is_numeric($value))
				{ 
			 		$whe = "$whe AND $column = :$column ";
				}
				else if(is_float($value))
				{
					$whe = "$whe AND $column = :$column ";
				}
				else if(is_string($value))
				{
					$whe = "$whe AND $column like :$column ";
				}
				else if(empty($value))
				{
					$whe = "$whe AND $column is null ";
				}
			}
			$con = $this->conectionDatabase();
			$query = $con->prepare("DELETE FROM $table WHERE 1 $whe");
			foreach($where as $column => $value)
			{
				if(is_numeric($value))
				{
					$query->bindValue(":$column",$value, PDO::PARAM_INT);
				}
				if(is_float($value))
				{
					$query->bindValue(":$column",$value, PDO::PARAM_STR);
				}
				if(is_string($value))
				{
					$query->bindValue(":$column",$value, PDO::PARAM_STR);
				}
			}
			$query->execute();
			$rowcount = $query->rowCount();
			$this->setData(NULL, $rowcount, NULL);
			return $this->getData();
		}
	}

	
}
?>