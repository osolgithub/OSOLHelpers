<?php
/**
*Class that could creating page nav while using pages that lists rows from a database table.
*Needs attached db_class.php,make sure you include that file in scripts that use this class
*
Sample
$lps_page_nav = array();
$lps_page_nav_index=count($lps_page_nav);//if $lps_page_navis not already set $lps_page_nav_index will be set to 0
$lps_page_nav[]=new \upkar\php\helpers\PageNav("pn_".$lps_page_nav_index);//the query variables will be starting with the string pn_0(for pagenavs for first lisitng in a page)
$lps_page_nav[$lps_page_nav_index]->options[rows_per_page]=10;//fetch 10 records for the given query for each page and create pagenave accordingly

$sql = "SELECT * FROM table";

/* 
Old style
//$templates = $lps_page_nav[$lps_page_nav_index]->fetch_records($sql);//returns the records as an array

New style with prepared statements
if only sql is there simply use [$sql]

$templates = $lps_page_nav[$lps_page_nav_index]->fetch_records([$sql,$paramTypes,$paramenterValues]);//returns the records as an array

 echo $lps_page_nav[$lps_page_nav_index]->page_nav."<br />"//displays like 1,2,3,4
 echo $lps_page_nav[$lps_page_nav_index]->display_rec_nums."<br />";//displays like 1 to 10 of 25
 
 foreach($templates as $row_templates) 
  write code to display each record items here
}
 
* @package database and pagenav class
* @author Sreekanth Dayanand <codes@oursource-online.net>
* @version 1.0 <2009/09/18>
* @copyright GNU General Public License (GPL)
**/
/*
regexp for functions
^(?!(.*)\}\/)(.*)function([^->=,'",\r,\n]+)\s+([^\r\n]+)
*/
#namespace OSOLHelpers;
namespace OSOLUtils\Helpers;
class OSOLPageNav
{
 var $lastReferedInstName="pn";// formerly varname
 var $rs=array();
 var $options=array("pagelinksperpage"=>20,"rows_per_page"=>5,"class"=>"");
 var $row_count=0;
 var $page_nav="";
 var $pageLinksArray= [];
 var $currentPage = "";
 var $fpnlLinks="";
 var $display_rec_nums="";
 var $script_uri = "";
 var $database = null;
 private $instancesOptions =  array();
 private static $inst =  null;
 public static function getInstance($dbDetails)//$varname)
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
        
         

         // The magic.
         //$ctor->setAccessible( true ) ;
         $inst = new static($dbDetails);//$varname);
         //echo "INSTANTIATED ".print_r($inst,true) ."<br />";
         
         $reflectionProperty->setValue(null/* null for static var */, $inst);
         
     }

     return $inst ;
 }
 private function __construct($dbDetails)//,$options=array())
 {
   $this->database = ($dbDetails == null)?\OSOLUtils\Helpers\OSOLMySQL::getInstance():\OSOLHelpers\OSOLMySQL::getInstance($dbDetails);
   $this->activateInst($this->lastReferedInstName);
   
 }//private function __construct()
 function activateInst($refName)
 {
  $this->lastReferedInstName=$refName;
  if(!isset($this->instancesOptions[$refName]))
   {
      $this->instancesOptions[$refName] = $this->options;
   }//if(!isset($this->instances[$refName]))
  return $this;
 }//function activateInst($refName)
 function setOptions($options)
 {
   foreach($options as $keyName => $keyVal)
   {
      $this->instancesOptions[$this->lastReferedInstName][$keyName] = $keyVal;
   }//foreach($options as $keyName => $keyVal)
  return $this;
 }//function setOptions($refName,$options)
 function getOptions($refName)
 {
    return $this->instancesOptions[$refName];
 }//function getOptions($refName)
 function fetch_records(...$bindparams)
 {
   $sql = $bindparams[0];
   if(isset($bindparams[1]))$types = $bindparams[1]; //...$bindparams
  //global $db;//$db is an instace of MYSQL class in the attacjhed db_class.php
  
  $dd_total_rows_var_name=$this->lastReferedInstName."_tot";
  if(isset($_GET[$dd_total_rows_var_name]) && $_GET[$dd_total_rows_var_name] != "")
  {
    $this->row_count = $_GET[$dd_total_rows_var_name];
  }
  else
  {
    
    //$count_sql=preg_replace("/select (.+) from (.+)/i","select count(*) as tot from $2",$sql);

    // above regexp replace when there are multiple selects as in 
    //select * from `table1` where  field1InTable1 = ?   and  _id in (select `_id` from `tabl2` where `field1InTable2` = '2')  order by _id desc
    $splitSQLArray = preg_split("/from/i",$sql);
    $sqlBeforeFrom = $splitSQLArray[0];
    $splitSQLArray[0] = $replacesSQLBeforeFrom = preg_replace("/select (.+) /i","select count(*) as tot",$sqlBeforeFrom );
    $count_sql= join(" from",$splitSQLArray);



    if(count($bindparams) > 1)
    {
      $bindParams4Count = array_values($bindparams);
      $bindParams4Count[0] = $count_sql;
      //die(__FILE__ . " : " . __LINE__ . "<br />"  . "<pre>".print_r($bindParams4Count,true)."</pre>");
      /*$queriesRun = call_user_func_array(array($this->database, 'getReplacedSQLAdv'), $bindParams4Count);
                                 
      die(__FILE__ . " : " . __LINE__ . "<br />" .$queriesRun);*/
      //die(__FILE__ . " : " . __LINE__ . "<br />" . $queriesRun . "<pre>".print_r($bindParams4Count,true)."</pre>");
      //echo __FILE__. ":" . __LINE__ ."<pre>".$sql."</pre>";
      //echo __FILE__. ":" . __LINE__ ."<pre>"."/select (.+) from (.+)/i"."</pre>";
      //echo __FILE__. ":" . __LINE__ ."<pre>"."select count(*) as tot from $2"."</pre>";
      //echo __FILE__. ":" . __LINE__ ."<pre>".print_r($bindParams4Count,true)."</pre>";
      $count_rsSQL = call_user_func_array(array($this->database, 'getReplacedSQLAdv'), $bindParams4Count);
      
      
      $logMessage = "select_id_sql is \r\n " . $count_rsSQL;
      \OsolMVC\Core\Helper\LogHelper::getInstance()->doLog($logMessage);


      $count_rs = call_user_func_array(array($this->database, 'selectPS'), $bindParams4Count);
      //die(__FILE__ . " : " . __LINE__ . "<br />" . $queriesRun . "<pre>".print_r($count_rs,true)."</pre>");
    }
    else //if(count($bindparams) > 0)
    {
      $count_rs = $this->database->select_sql($count_sql);
    }//if(count($bindparams) > 0)
    $this->row_count = $count_rs[0]['tot'];
  }//if(isset($_GET[$dd_total_rows_var_name]) && $_GET[$dd_total_rows_var_name] != "")
  
  //$dd_total_rows_var_name=$this->lastReferedInstName."_tot";
  $dd_per_page_var_name=$this->lastReferedInstName."_per_page";
  $dd_page_num_var_name=$this->lastReferedInstName."_page_num";
  $this->currentPage = isset($_GET[$dd_page_num_var_name])?$_GET[$dd_page_num_var_name]:0;
  $pageNavOptions =  $this->getOptions($this->lastReferedInstName);
  if((!isset($_GET[$dd_per_page_var_name]))  || (isset($_GET[$dd_per_page_var_name]) && $_GET[$dd_per_page_var_name] !="all"))
  {
    $this->page_nav=$this->create_pagenav($this->row_count,$dd_total_rows_var_name,$dd_per_page_var_name,$dd_page_num_var_name,$pageNavOptions["class"]);
    $this->display_rec_nums=$this->get_display_row_limit($dd_per_page_var_name,$dd_page_num_var_name).$this->row_count; 		  
    $sql.=" ".$this->get_row_limit($dd_per_page_var_name,$dd_page_num_var_name); 
  }
  else
  {
    
    $this->page_nav= "";
    $this->display_rec_nums= "Total {$this->row_count} items"; 		  
    //$sql.=" ".$this->get_row_limit($dd_per_page_var_name,$dd_page_num_var_name);

  }//(isset($_GET[$dd_per_page_var_name] && $_GET[$dd_per_page_var_name] !="all"))
  //echo $sql."<br />";
  //$this->rs=$this->database->select_sql($sql);  
  //echo $sql." line # ".__LINE__."<br />";
  if(count($bindparams) > 1)
  {
    $bindparams[0] = $sql;
    /* $queriesRun = call_user_func_array(array($this->database, 'getReplacedSQLAdv'), $bindparams);                                 
    die(__FILE__ . " : " . __LINE__ . "<br />" .$queriesRun); */
    //$bindparams = $this->database->convertMultiLevelArray2Single( $bindparams);
    //echo "<pre>".print_r($bindparams,true)."</pre>";
    $this->rs = call_user_func_array(array($this->database, 'selectPS'), $bindparams);
  }
  else //if(count($bindparams) > 0)
  {
    $this->rs = $this->database->select_sql($sql);
  }//if(count($bindparams) > 0)
  return $this->rs;
		 
 }//function fetchrecords($sql)
 /* function get_row_limit($dd_per_page_var_name,$dd_page_num_var_name)
 {
     $pageNum = isset($_GET[$dd_page_num_var_name])?$_GET[$dd_page_num_var_name]:0;
     $perPage = isset($_GET[$dd_per_page_var_name])?$_GET[$dd_per_page_var_name]:$pageNavOptions['rows_per_page'];
     $rangeStart = $pageNum * $perPage;
     if($rangeStart == 0)$rangeStart = 1;
    return " limit ".$rangeStart.",".$perPage;
 }//function get_row_limit($dd_per_page_var_name,$dd_page_num_var_name) */
 function first_recno_for_pageno($numrows_var,$rows_per_page_var,$nav_var)
 {
  return ($_GET[$nav_var]*$_GET[$rows_per_page_var]);
 }

 function fpnlLinks($tot_rows,$numrows_var,$rows_per_page_var,$nav_var /*$currentPageNo*/,$class="")//($numrows,$rows_per_page)
 {
 }//function fpnllinks($currentPageNo,$tot_rows,$numrows_var,$rows_per_page_var,$nav_var,$class="")
 function create_pagenav($tot_rows,$numrows_var,$rows_per_page_var,$nav_var,$class="")
 {
      //die(__FILE__ . " : " . __LINE__ ."  in create_pagenav") ;
      $pageNavOptions =  $this->getOptions($this->lastReferedInstName);
         //set defaults if not declared aleady
		  if(!( isset($_GET[$rows_per_page_var]) && $_GET[$rows_per_page_var]>0)) $_GET[$rows_per_page_var]=$pageNavOptions['rows_per_page'];
		  if(!isset($_GET[$nav_var])) $_GET[$nav_var]=0;
		  if(!isset($_GET[$numrows_var])) $_GET[$numrows_var]=$tot_rows;
		 //set defaults if not declared aleady  ends here
     /* echo(
            __FILE__ . " : " . __LINE__ ."  in create_pagenav ".$_GET[$rows_per_page_var]."\r\n".
            __FILE__ . " : " . __LINE__ ."  in create_pagenav ".$_GET[$nav_var]."\r\n"
        ) ; */

      $maxpagelinks=$pageNavOptions['pagelinksperpage'];
      $tot_pages=ceil($_GET[$numrows_var]/$_GET[$rows_per_page_var])-1;
	  $currentPage = $_GET[$nav_var];//$maxpagelinks,$tot_pages
	  /* echo "$nav_var is {$_GET[$nav_var]}<br />";
	  echo "maxpagelinks is $maxpagelinks<br />";
	  echo "tot_pages is $tot_pages<br />";
	  echo " first range max is ". ($maxpagelinks - ceil($maxpagelinks/2))."<br />"; */
	  
      //$firstpage=$_GET[$nav_var]>$maxpagelinks?$_GET[$nav_var]-ceil($maxpagelinks/2):0;
      $firstpage=($_GET[$nav_var]+ceil($maxpagelinks/2))>$maxpagelinks?$_GET[$nav_var]-ceil($maxpagelinks/2):0;
      $lastpage=($tot_pages>=($_GET[$nav_var]+$maxpagelinks))?(($_GET[$nav_var]+ceil($maxpagelinks/2))):$tot_pages;
	  
	  if($tot_pages > $maxpagelinks)
	  {
		  $min = 0 ;
		  $max = $maxpagelinks;
		  $value = $currentPage;
		  /*
		  show 0 to $maxpagelinks if $currentPage is between 0 to $currentPage - ceil($maxpagelinks/2)
		  show ($tot_pages - $maxpagelinks) to $tot_pages if $currentPage is between  $tot_pages - $maxpagelinks to $tot_pages
		  otherwise show pages between  $currentPage - floor($maxpagelinks/2) and $currentPage + floor($maxpagelinks/2)
		  */
		  //($min <= $value) && ($value <= $max)
		  
		  switch(true)
		  {
			  case ((0 <= $currentPage) && ($currentPage <= ($maxpagelinks - ceil($maxpagelinks/2)))):
				  //echo "FIRST<br />";
				  $firstpage = 0;
				  $lastpage = $maxpagelinks - 1;
				break;
			  //case ((($tot_pages - $maxpagelinks) <= $currentPage) && ($currentPage <= ($tot_pages))):
			  case ($tot_pages <= ($currentPage + ceil($maxpagelinks/2))):
				  //echo "SECOND<br />";
				  $firstpage = $tot_pages - $maxpagelinks + 1;
				  $lastpage = $tot_pages;
				break;
			  default:
				  //echo "DEFAULT<br />";
				  $firstpage = $currentPage - floor($maxpagelinks/2);
				  $lastpage = $currentPage + floor($maxpagelinks/2);
				break;
		  }
		  
	  }
	  else
	  {
		  $firstpage = 1;
		  $lastpage = $tot_pages;
	  }//if($tot_pages > $maxpagelinks)
      
     /* die(
      __FILE__ . " : " . __LINE__ ."  in create_pagenav ".$tot_pages."\r\n".
      __FILE__ . " : " . __LINE__ ."  in create_pagenav ".$firstpage."\r\n".
      __FILE__ . " : " . __LINE__ ."  in create_pagenav ".$lastpage."\r\n"
      ) ; */
      
      $page_nav_num = "";
      $skippedQString = $this->sp_skip_param(array($numrows_var,$nav_var,$rows_per_page_var));
      //die(__FILE__ . " : " . __LINE__. " <br />".$skippedQString);
      
      $script_uri = $this->getScriptURI();//(isset($pageNavOptions['script_uri']) && $pageNavOptions['script_uri'] != "")?$pageNavOptions['script_uri']:$_SERVER['SCRIPT_URI'];
	  //$this->pageNavLink($script_uri, $skippedQString, $rows_per_page_var, $numrows_var, $nav_var, "", $class);
      for($i=($firstpage);$i<=$lastpage;$i++)
      {
            /* $page_nav_num .= " <a class=\"$class\"  href=\"{$script_uri}?".//{$_SERVER['SCRIPT_URI']}
                            $skippedQString.
                            (($skippedQString!="")?"&":"").
                            $rows_per_page_var."=".$_GET[$rows_per_page_var].
                            "&".$numrows_var."=".$_GET[$numrows_var].
                            "&".$nav_var."=".$i.
                            "\">".($i+1)."</a>"; */
            $page_nav_num .= $this->pageNavLink($script_uri, $skippedQString, $rows_per_page_var, $numrows_var, $nav_var, $i, $class);
      
      }//for($i=0;$i<=$numrows;$i++)
      /* $firstPageLink = $this->pageNavLink($script_uri, $skippedQString, $rows_per_page_var, $numrows_var, $nav_var, 0, $class);
      $finalPageLink = $this->pageNavLink($script_uri, $skippedQString, $rows_per_page_var, $numrows_var, $nav_var, $tot_pages, $class);
      if($firstpage!=0) $page_nav_num= $firstPageLink . "...$page_nav_num";
      if($lastpage!=$tot_pages) $page_nav_num="$page_nav_num..." . $finalPageLink; */
      return $page_nav_num;
 
 }//function pagenav($tot_rows,$numrows_var,$rows_per_page_var,$nav_var,$class="")
 function pageNavLinkURL($script_uri, $skippedQString, $rows_per_page_var, $numrows_var, $nav_var, $i, $class="")
 {
	 
	 $url =  $script_uri. "?".//{$_SERVER['SCRIPT_URI']}
                            $skippedQString.
                            (($skippedQString!="")?"&":"").
                            $rows_per_page_var."=".$_GET[$rows_per_page_var].
                            "&".$numrows_var."=".$_GET[$numrows_var].
                            "&".$nav_var."=".$i;
	return $url;
 }
 function pageNavLink($script_uri, $skippedQString, $rows_per_page_var, $numrows_var, $nav_var, $i, $class="")
 {
	 $pageNavOptions =  $this->getOptions($this->lastReferedInstName);
	 $currentPageClass = "";
	 $url = $this->pageNavLinkURL($script_uri, $skippedQString, $rows_per_page_var, $numrows_var, $nav_var, $i, $class);
	 $this->pageLinksArray[] = $url;
	 if(isset($pageNavOptions['currentPageClass']) && $i == $this->currentPage)$currentPageClass = $pageNavOptions['currentPageClass'];
	 $linkText = $i==""?"":($i+1);
	 return " <a class=\"$class {$currentPageClass}\"  href=\"".$url."\">".$linkText."</a>";
 }
 private function getScriptURI()
 {
  $pageNavOptions =  $this->getOptions($this->lastReferedInstName);
  $script_uri = (isset($pageNavOptions['script_uri']) /* && $pageNavOptions['script_uri'] != "" */)?$pageNavOptions['script_uri']:$_SERVER['SCRIPT_URI'];
  return $script_uri;
 }
 private function sp_skip_param($params2Skip)
 {
    $skippedQueryString = "";
    $splittedQStringArray = preg_split("/&/",$_SERVER['QUERY_STRING']);
    foreach($splittedQStringArray as $splitVals)
    {
      $split2KeyVarArray = preg_split("/=/",$splitVals);
      if(!in_array($split2KeyVarArray[0],$params2Skip))
      {
        $skippedQueryString .= $splitVals."&";
      }//if(!in_array($split2KeyVarArray[0],$params2Skip))
    }//foreach($splittedQStringArray as $splitVals)
    $skippedQueryString = $skippedQueryString !=""?substr($skippedQueryString,0,-1):"";
    return $skippedQueryString;
    
 }//private function sp_skip_param($params2Skip)
 function get_display_row_limit($rows_per_page_var,$nav_var)
 {
        $pageNavOptions =  $this->getOptions($this->lastReferedInstName);
        $maxRows = (!($_GET[$rows_per_page_var]>0))?$pageNavOptions[rows_per_page]:$_GET[$rows_per_page_var];
        $pageNum = (!isset($_GET[$nav_var]))?0:$_GET[$nav_var];
        $startRow = ($pageNum * $maxRows)+1;
		$maxRows=$startRow + $maxRows-1;
		$maxRows=$maxRows<$this->row_count?$maxRows:$this->row_count;
		$limit="$startRow  to $maxRows of ";	
		return $limit;
 }//function get_row_limit($rows_per_page_var,$nav_var)
 function get_row_limit($rows_per_page_var,$nav_var)
 {
        $pageNavOptions =  $this->getOptions($this->lastReferedInstName);
        $maxRows = (!($_GET[$rows_per_page_var]>0))?$pageNavOptions[rows_per_page]:$_GET[$rows_per_page_var];
        $pageNum = (!isset($_GET[$nav_var]))?0:$_GET[$nav_var];
        $startRow = $pageNum * $maxRows;
		$limit="limit $startRow ,$maxRows";	
		return $limit;
 }//function get_row_limit($rows_per_page_var,$nav_var)
 function getDropDownHTML()
 {
    $script_uri = $this->getScriptURI();
    $pageNavOptions =  $this->getOptions($this->lastReferedInstName);
    $rows_per_page_var = $this->lastReferedInstName."_per_page";
    $nav_var = $this->lastReferedInstName."_page_num";
    $numrows_var = $this->lastReferedInstName."_tot";
    if(!isset($_GET[$numrows_var]))$_GET[$numrows_var] = $this->row_count;
    $skippedQString = $this->sp_skip_param(array($numrows_var,$nav_var,$rows_per_page_var));
    $qString = $skippedQString . 
              (($skippedQString!="")?"&":"").
              $numrows_var."=".$_GET[$numrows_var].
                        "&".$nav_var."=0".
                        "&".$rows_per_page_var."=";//.$_GET[$rows_per_page_var].;
    $newURI = $script_uri."?".$qString;
    $dropdownFunctionName = $this->lastReferedInstName."_onRangeChanged()";
    $selectRangeId = $this->lastReferedInstName . "_dropdown";
    $selectedRangeVarName = $this->lastReferedInstName."_selectedRange";
    $dropdownHTML = "<select style=\"display:inline\" id=\"{$selectRangeId}\" onchange=\"{$dropdownFunctionName}\">\r\n";
    $dropdownHTML .= "<option value=\"\">Select</a>\r\n";
    $dropdownHTML .= "<option value=\"all\">All</a>\r\n";
    $dropdownHTML .= "<option value=\"10\">10</a>\r\n";
    $dropdownHTML .= "<option value=\"25\">25</a>\r\n";
    $dropdownHTML .= "<option value=\"50\">50</a>\r\n";
    $dropdownHTML .= "</select>\r\n";

    $dropdownScript = "";//"\r\n<script>\r\n";
    $dropdownScript .= "var newPageNavURI = '{$newURI}';\r\n";
    $dropdownScript .= "function " .$dropdownFunctionName."{\r\n" ;
    $dropdownScript .=    "var {$selectedRangeVarName} = document.getElementById('{$selectRangeId}').value;\r\n";
    $dropdownScript .=    " window.location.assign(newPageNavURI +{$selectedRangeVarName}) \r\n";
    $dropdownScript .= "}\r\n";
    //$dropdownScript .= "</script>\r\n";
    return  array("js" => $dropdownScript, "html" => $dropdownHTML) ;
 }//function getDropDownHTML()
}//class pagenav
?>