<?php
//autocomplete.php

require "config.inc.php";
$key=$proj;
require "errors.inc.php";

$val=array();
$err_no=ERR_NONE;
$result="ok";
$et=array();

$oper=$_POST['oper'];   if(!isset($oper)) $oper=$_GET['oper'];  if (!isset($oper)) {echo "<script language='JavaScript' type='text/javascript'>window.location='$sweet_home';</script>";	 return;}

require "connect.inc.php";

if (!$link || ($err_no > 0)) {$ret = array('err_no' => $err_no, 'result' => $result);  echo json_encode($ret);  return;} //mySQL error

if ($oper=="inst")
{  
  $inst=$_POST['term'];
  $val=array();  
  $query="SELECT * FROM Institution WHERE abbreviation_name LIKE '$inst%' LIMIT 10";
  $res=mysqli_query($link, $query);
  if (!$res) {$err_no=ERR_MYSQL_Q; $result=mysqli_error($link)." (err=".mysqli_errno($link).")";}	
  else
  {  
   $rows=mysqli_num_rows($res);
   if (!$rows) {$err_no=ERR_MYSQL_R; $result="empty result";}
   else {while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {$val[]=array('label' => $row['abbreviation_name'], 'institution_id' => $row['institution_id']);}}
   mysqli_free_result($res); 
  }
}
else if ($oper=="author")
{  
  $author=$_POST['term'];
  $institution_id=$_POST['inst'];
  if ($institution_id==0) $filter="";
  else $filter="AND institution_id=$institution_id";
  $val=array();  
  $query="SELECT * FROM Author WHERE name LIKE '%$author%' $filter ORDER BY author_id DESC LIMIT 10";
  $res=mysqli_query($link, $query);
  if (!$res) {$err_no=ERR_MYSQL_Q; $result=mysqli_error($link)." (err=".mysqli_errno($link).")";}	
  else
  {  
   $rows=mysqli_num_rows($res);
   if (!$rows) {$err_no=ERR_MYSQL_R; $result="empty result";}
   else {while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {$val[]=array('label' => $row['name'], 'author_id' => $row['author_id'], 'institution_id' => $row['institution_id']);}}
   mysqli_free_result($res); 
  }
}
if ($oper=="round")
{  
  $round=$_POST['term'];
  $val=array();  
  $query="SELECT * FROM ShiftRound WHERE shiftround_name LIKE '$round%' LIMIT 10";
  $res=mysqli_query($link, $query);
  if (!$res) {$err_no=ERR_MYSQL_Q; $result=mysqli_error($link)." (err=".mysqli_errno($link).")";}	
  else
  {  
   $rows=mysqli_num_rows($res);
   if (!$rows) {$err_no=ERR_MYSQL_R; $result="empty result";}
   else {while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {$val[]=array('label' => $row['shiftround_name'], 'shiftround_id' => $row['shiftround_id'], 'start_date' => $row['start_date'], 'end_date' => $row['end_date'], 'shiftround_status' => $row['shiftround_status'], 'pr' => $row['pr']);}}
   mysqli_free_result($res); 
  }
}
mysqli_close($link);
if (count($ret)==0) {$ret = array('err_no' => $err_no, 'result' => $result, 'val' => $val);  echo json_encode($ret);}
else {echo json_encode($ret);}
?>
