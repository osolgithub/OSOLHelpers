<?php
/**
*Class that could simplify mysql database operations.Used in attached \OSOLHelpers\OSOLPageNav class
*
*
https://www.w3schools.com/php/php_mysql_prepared_statements.asp
Sample

	$dbDetails['DB_USER'] = username;
	$dbDetails['DB_PASS'] = password;
	$dbDetails['DB_SERVER'] = server;
	$dbDetails['DB_NAME'] = database;
	$db = new \OSOL\PHP\helpers\MySQL($dbDetails);	
	$db->connectdb();
	//select query usage
	$sql = "SELECT * FROM updates  ";
	$latest_updates = $db->select_sql($sql);
	$row_latest_updates = $latest_updates[0];
	$totalRows_latest_updates = count($latest_updates);
	
	//other querie usage
	$SQL="delete fromcurrent_users where  DATE_ADD(last_visited,INTERVAL 1 year)<NOW()";
	$db->execute_sql($SQL);

*
* @package database and pagenav class
* @author Sreekanth Dayanand <osolgithub@outsource-online.net>
* @version 1.0 <2021/12/15>
* @copyright GNU General Public License (GPL)
**/
#namespace OSOLHelpers;
namespace OSOLUtils\Helpers;
class OSOLMySQL {
	var $user;
	var $pass;
	var $server;
	var $db;
	var $conn;
	var $query;
	var $table_prefix;
	var $log_queries;
	var $query_log_type;
	var $dbDetails;

	var $lastSQLRun = "";
	

	var $connected = false;
	var $showError =  false;
	private static $inst =  null;
    public static function getInstance()
    {
        /* 
        if(ClassPageConfig::$inst ==  null)
        {
            ClassPageConfig::$inst = new ClassPageConfig();
        }//if(ClassPageConfig::$inst ==  null)
        return ClassPageConfig::$inst;
        */
        //https://www.php.net/manual/en/reflectionclass.newinstancewithoutconstructor.php &
        //https://refactoring.guru/design-patterns/singleton/php/example
        $ref  = new \ReflectionClass( get_called_class() ) ;
        
        $reflectionProperty = new \ReflectionProperty(static::class, 'inst');
        $reflectionProperty->setAccessible(true);
        //echo $reflectionProperty->getValue();
        $inst =   $reflectionProperty->getValue();;//$reflectedClass->getStaticPropertyValue('inst');

        if (  $inst == null)
        {
           
            
			$arguments = func_get_args();
        	$numberOfArguments = func_num_args();
			//die("numberOfArguments  is " . $numberOfArguments );
            // The magic.
            //$ctor->setAccessible( true ) ;
            //$inst = new static();
            $inst = $numberOfArguments==0?new static():new static($arguments[0] /* $dbDetails */);
            //echo "INSTANTIATED ".print_r($inst,true) ."<br />";
            
            $reflectionProperty->setValue(null/* null for static var */, $inst);
            
        }

        return $inst ;
    }//public static function getInstance($dbDetails)
	
    public function __construct()
    {
		
        $arguments = func_get_args();
        $numberOfArguments = func_num_args();
		//die("numberOfArguments  is " . $numberOfArguments );
		//die("calling __construct in  \MYSQL");
        if (method_exists($this, $function = '__construct'.$numberOfArguments)) {
            call_user_func_array(array($this, $function), $arguments);
        }
    }
	/**
	 *  @brief Constructor without argument
	 *  
	 *  @return void
	 *  
	 *  @details This construstor is used when this class is used as a library, where $dbDetails can be obtained from  a config class of that system
	 */
	//--------------------------------------------------------
	private function __construct0()//MySQL(
	//--------------------------------------------------------
	{
		$siteConfig =  \OsolMVC\Core\Config\ClassSiteConfig::getInstance();
        $dbDetails = $siteConfig->getDBSettings();
		//die("dbDetails is <pre>".print_r($dbDetails,true)."</pre>");
        
        //die("calling __construct0 in  \MYSQL");

        $this->dbDetails = $dbDetails;
        $this->user = $dbDetails['DB_USER'];
		$this->pass = $dbDetails['DB_PASS'];
		$this->server = $dbDetails['DB_SERVER'];
		$this->db = $dbDetails['DB_NAME'];
		$this->table_prefix = $dbDetails['table_prefix'];

		
		$this->log_queries = $dbDetails['log_queries'];
		$this->query_log_type = $dbDetails['query_log_type'];
        $this->connectdb();

	}//private function __construct()
	//--------------------------------------------------------
	private function __construct1($dbDetails)//MySQL(
	//--------------------------------------------------------
	{
		//die("calling __construct1 in  \MYSQL");
		
		$this->user = $dbDetails['DB_USER'];
		$this->pass = $dbDetails['DB_PASS'];
		$this->server = $dbDetails['DB_SERVER'];
		$this->db = $dbDetails['DB_NAME'];
		$this->table_prefix = $dbDetails['table_prefix'];

		
		$this->log_queries = $dbDetails['log_queries'];
		$this->query_log_type = $dbDetails['query_log_type'];
		$this->connectdb();

		

	}
	
	//--------------------------------------------------------
	function connectdb()
	//--------------------------------------------------------
	{
		if($this->connected) return true;
        // Create connection
        $this->conn = new \mysqli($this->server, $this->user, $this->pass, $this->db);// or die("Failed to connect to database".$this->db);
		
		$this->conn->set_charset("utf8");//https://stackoverflow.com/questions/46305169/php-json-encode-malformed-utf-8-characters-possibly-incorrectly-encoded#comment112161850_46305914
		
		// Check connection
        if ($this->conn->connect_error) {
			echo "{$this->server}, {$this->user}, {$this->pass}, {$this->db} <br />";
			die("Connection failed: " . $this->conn->connect_error);
			$this->connected = true;
		}
		else{
			//die("Connection success");
		}
	}
	function getVersion()
	{
		return $this->conn->server_info;;
	}
	
	
	//--------------------------------------------------------
	function disconnect()
	//--------------------------------------------------------
	{
		/* echo "Disconnect called<br />";
		throw new Exception(); */
		if($this->connected)$this->conn->close();
		$this->connected = false;
	}
    //--------------------------------------------------------
    //https://www.javatpoint.com/php-variable-length-argument-function
	//function setquery($sql,...$bindArgs) 
	function debug_trace()
	{
		$debug_backtrace = debug_backtrace();
		array_walk($debug_backtrace, function($debug_backtraceValue,$key){
												//$fields2Show = array('file','line','function');
												//$echoLine = "";
												//foreach($fields2Show as $k => v)
												{
													$echoLine = $debug_backtraceValue['file'] ." : " . $debug_backtraceValue['line'] ." : ".  $debug_backtraceValue['function'] ."<br />"; 
												}//foreach($fields2Show as $k => v)
												echo $echoLine."<br />";
											});
	    return "";

	}//function debug_trace()
	function executePS($sql,$types="",...$bindArgsRecieved) 
	//--------------------------------------------------------
	{ 	
		//echo $sql."<br />";
		global $site_options;
        $this->query=$sql;		
		$stmt = $this->conn->prepare($sql) ;//or die(sprintf("Error: %s.\n", $this->conn->error)."<br /> ".$sql."<br /> types :{$types }<br /> bindArgs <pre>".print_r($bindArgsRecieved,true)."</pre>".$this->debug_trace());
		//$this->logQuery("bindArgsRecieved is <pre>".print_r($bindArgsRecieved, true)."<pre>");
		//$bindArgs = $this->getRestructuredBindSQLIncludingArrays($bindArgsRecieved);		
		$bindArgs = $this->convertMultiLevelArray2Single($bindArgsRecieved);		
		//$this->logQuery("bindArgs is <pre>".print_r($bindArgs, true)."<pre>");
		$this->lastSQLRun = $this->getReplacedSQL($sql,$bindArgs);
		if($this->log_queries)
		{
			$query2Log = $this->lastSQLRun;
			$this->logQuery($query2Log);
		}//if($this->log_queries)

        if(count($bindArgs)>0)//https://stackoverflow.com/questions/17226762/mysqli-bind-param-for-array-of-strings
        {
            //$types = str_repeat('s', count($bindArgs)); //types
            $stmt->bind_param($types, ...$bindArgs); // bind array at once
            
		}//if(count($bindArgs)>0)
		//Error: 1062 : Duplicate entry '...' for key 'link_is_unique'.
		
		if($stmt->execute())// or die(sprintf("Error: %s.\n", $stmt->errno ." : ". $stmt->error)."<br /> ".$sql."<br /> types :{$types }<br /> bindArgs <pre>".print_r($bindArgs,true)."</pre>");
		{
			//if($site_options["debug_mode"]) echo $sql."<br />";
			
			//$resultGot = array("status"=> "success","insert_id" => $stmt->lastInsertId(),"affectedRows" => $stmt->affectedRows());
			$resultGot = array("status"=> "success","insert_id" => $stmt->insert_id,"affectedRows" => $stmt->affected_rows);
			$stmt->close();
			
		}
		else //if($stmt->execute())
		{
			$resultGot = array("status"=> "failed","error_no" => $stmt->errno , "error_desc" => $stmt->error);
			$error2Show =  $this->lastSQLRun."<hr />"."<pre>".print_r($resultGot,true)."</pre>";
			if($this->showError)
			{
				//echo "sql is ".$sql."<br />";
				echo  $error2Show;
				$this->logQuery($error2Show);
			}
			/* else
			{
				throw new \Exception($error2Show );
			}//if($this->showError) */
		}//if($stmt->execute())
		return $resultGot;
	}//function executePS($sql,$types="",...$bindArgs) 
	/**
    *returns the resultset as an array
    *@access public
    *@param integer height
    *@return boolean
    **/
	//----------------------------------------------------------------------------
	function selectPS($sql,$types="",...$bindArgsRecieved)
	//----------------------------------------------------------------------------
	{
		global $site_options;	
		//echo $sql."<br />";
        $this->query=$sql;
		$stmt = $this->conn->prepare($sql);
		$bindArgs = $this->convertMultiLevelArray2Single($bindArgsRecieved);
		$this->lastSQLRun = $this->getReplacedSQL($sql,$bindArgs);

		if($this->log_queries)
		{
			$query2Log = $this->lastSQLRun;
			$this->logQuery($query2Log);
		}//if($this->log_queries)
		
        if(count($bindArgs)>0)//https://stackoverflow.com/questions/17226762/mysqli-bind-param-for-array-of-strings
        {
            $stmt->bind_param($types, ...$bindArgs); // bind array at once
		}//if(count($bindArgs)>0)
		
		if(!$stmt->execute())
		{
			$resultGot = array("status"=> "failed","error_no" => $stmt->errno , "error_desc" => $stmt->error);
			 //or die(sprintf("Error: %s.\n", $stmt->errno ." : ". $stmt->error)."<br /> ".$sql."<br /> types :{$types }<br /> bindArgs <pre>".print_r($bindArgs,true)."</pre>");
			 if($this->showError)
			{
				//echo "sql is ".$sql."<br />";
				echo $this->getReplacedSQL($sql,$bindArgs)."<hr />"."<pre>".print_r($resultGot,true)."</pre>";
				$this->logQuery( $this->getReplacedSQL($sql,$bindArgs)."<hr />"."<pre>".print_r($resultGot,true)."</pre>");
			}//if($this->showError)
		}
		
		

		
		$ret = array();
		// Call to undefined method mysqli_stmt::get_result() 
		/* $result = $stmt->get_result();
		
		//echo "<pre>".print_r($result,true)."</pre>";
		if($result->num_rows >0){
			while( $row = $result->fetch_assoc())
			{
				$ret[] = $row;
			}
		} */
		$stmt->store_result();
		
		while($assoc_array = $this->fetchAssocStatement($stmt))
		{
			$ret[] = $assoc_array;
		}//while($assoc_array = $this->fetchAssocStatement($stmt))
		
		$stmt->close();
		//die("<pre>".print_r($ret,true)."</pre>");
		return $ret;
	}//function selectPS($sql,...$bindArgs)
	function convertMultiLevelArray2Single($multiLevelArray)
	{
		$singleLevelArray = array();
		foreach($multiLevelArray as $multiLevelArrayElement)
		{
			if(is_array($multiLevelArrayElement))
			{
				$singleLeveledChild = $this->convertMultiLevelArray2Single($multiLevelArrayElement);
				$singleLevelArray = array_merge($singleLevelArray,$singleLeveledChild);
			}
			else
			{
				$singleLevelArray[] = $multiLevelArrayElement;
			}//if(is_array($multiLevelArrayElement))
		}//foreach($multiLevelArray as $multiLevelArrayElement)
		return $singleLevelArray;
	}//function convertMultiLevelArray2Single($multiLevelArray)
	function getRestructuredBindSQLIncludingArrays($bindparams)
	{
		$newBindParams = array();
		if(count($bindparams)>0)//https://stackoverflow.com/questions/17226762/mysqli-bind-param-for-array-of-strings
		{
			//$types = str_repeat('s', count($bindArgs)); //types
			foreach($bindparams as $bindParam)
			{
				if(is_array($bindParam))// needed when using  select * from ..  whee ... in ()
				{
					$newBindParams =  array_merge($newBindParams,$bindParam);
				}
				else
				{
					$newBindParams[] = $bindParam;
				}
			}//foreach($bindparams as $bindParam)
			
			
		}//if(count($bindArgs)>0)
		return $newBindParams;
	}//function bindSQLIncludingArrays($bindparams)
	
	//https://stackoverflow.com/questions/8321096/call-to-undefined-method-mysqli-stmtget-result
		function fetchAssocStatement($stmt)
        {
			//die("num rows is  " . $stmt->num_rows);
            if($stmt->num_rows>0)
            {
                $result = array();
                $md = $stmt->result_metadata();
                $params = array();
                while($field = $md->fetch_field()) {
                    $params[] = &$result[$field->name];
                }
                call_user_func_array(array($stmt, 'bind_result'), $params);
				if($stmt->fetch())
					//die("<pre>".print_r($result,true)."</pre>");
                    return $result;
            }
        
            return null;
        }
	
	function select_sql($sql)
	{
		
		
		
	    $resutl2Return = array();
		if ($result =$this->conn->query($sql)) {
			while($row = $result->fetch_assoc()){
				$resutl2Return[] = $row;
			}
		}
		$result->close();
		
		return $resutl2Return;
	}	
	
	
	//For insert, update, delete
	//--------------------------------------------------------
	function sqlnodie()
	//--------------------------------------------------------
	{
		mysql_query($this->query);
	}

	function lastInsertId()
	{
		return $this->conn->insert_id;
	}
	function affectedRows()
	{
		return $this->conn->affected_rows;
	}

	function getTablePrefix()
	{
		return $this->table_prefix;
	}//function getTablePrefix()

	public function getReplacedSQLAdv($stmt,$types = "", ...$bindArgsRecieved)
	{
		$bindArgs = $this->getRestructuredBindSQLIncludingArrays($bindArgsRecieved);
		return $this->getReplacedSQL($stmt,$bindArgs)."\r\n";//<br />
	}//public function getReplacedSQLAdv($stmt,$types = "", ...$bindparams)
	public function getReplacedSQL($stmt,$bindparams)
	{
		//echo"<pre>".print_r(array($stmt,$bindparams), true)."</pre>";

		$replacedSQL =  preg_replace("@\\\@u","\\",trim(vsprintf(preg_replace("/\?/","'%s'",$stmt),
										array_map(
													array($this->conn,"real_escape_string"),
															array_map("stripslashes",$bindparams)
												)
                                             )));
		
	    if(substr($replacedSQL,-1) != ";")$replacedSQL .= ";";
		return $replacedSQL;
	}//public getReplacedSQL($stmt,$bindparams)
	
    public function mySQLAndPHPTimeDiff()
    {
        
        
        /*  
        there are 3 time zones to be considered 
        'system_time_zone' => 'UTC', //timezone in php.ini
        "mysql_time_zone" => "Asia/Kolkata"
        'site_time_zone' => 'Asia/Kolkata' // time zone based on wchic site is intended to work
        */
        $siteConfig =  \OsolMVC\Core\Config\ClassSiteConfig::getInstance();
        $dbSettings = $siteConfig->getDBSettings();
        $siteSettings = $siteConfig->getSiteSettings();

        $systemTimeZone = $siteSettings['system_time_zone'];//locally UTC
        $mysqlTimeZone = $siteSettings['mysql_time_zone'];// locally IST , ie "Asia/Kolkata"
        //$siteTimeZone = $siteSettings['site_time_zone'];
        $secondsDiffOfTimeZone = $this->getTimeZone2MinusTimeZone1($systemTimeZone,$mysqlTimeZone);
		return $secondsDiffOfTimeZone;
    }//private function mySQLAndSiteTimeDiff()
    /*
    Method:getTimeZone2MinusTimeZone1
    Returns Diff in seconds
    */
    public function getTimeZone2MinusTimeZone1($timeZone1,$timeZone2)
    {
        
        //Sample code
        $local_tz = new \DateTimeZone($timeZone1);//'America/Los_Angeles');
        $local = new \DateTime('now', $local_tz);


        $user_tz = new \DateTimeZone($timeZone2);//'America/New_York');
        $user = new \DateTime('now', $user_tz);

        $local_offset = $local->getOffset();// / 3600;
        $user_offset = $user->getOffset();// / 3600;
        /*          
        // Output the date with microseconds.
            echo "time as per php.ini time zone is ".$local->format('Y-m-d\TH:i:s.u')."<br />"; //
            echo "time as per Asia/Kolkata zone is ".$user->format('Y-m-d\TH:i:s.u')."<br />"; //
            $dateTimeFromTime = date('Y-m-d\TH:i:s',time());
            echo "date time ".$dateTimeFromTime."<br />"; //$user->format('Y-m-d\TH:i:s.u')
            $timeFromDateTime =  strtotime($dateTimeFromTime);
            $dateTimeAgainFromTime = date('Y-m-d\TH:i:s',$timeFromDateTime );
            echo "dateTimeAgainFromTime is ".$dateTimeAgainFromTime."<br />";

            echo "date_default_timezone_get is " . date_default_timezone_get(). "<br />";
        */
        /*
            //above comment code will output like        
            time as per php.ini time zone is 2021-01-12T13:03:08.643437
            time as per Asia/Kolkata zone is 2021-01-12T18:33:08.643475
            date time 2021-01-12T13:03:08
            dateTimeAgainFromTime is 2021-01-12T13:03:08
            date_default_timezone_get is UTC
        */
        $diff = $user_offset - $local_offset;
        
        return $diff;
       
	}//public function get2TimeZoneDiff($timeZone1,$timeZone2)
	private function logQuery($query)
	{
		switch($this->query_log_type)	
		{
			case 'echo':
				echo $query."<br />\r\n";
				break;
			case 'file':
			    if(class_exists('\OsolMVC\Core\Helper\LogHelper'))
				{					
					\OsolMVC\Core\Helper\LogHelper::getInstance()->doLog($query, false);  //   php/logs/allLogs.txt
				}
				break;
		}//switch($this->query_log_type)	
	}//private function logQuery($query)
	
	
	
	
}//class OSOLMySQL {
?>