<?php
//confirm.php: handles the 'Author' table. Following is the list of functions currently supported:
//oper={login}
header("Content-type: text/html; charset=utf-8");

require "config.inc.php";
require "errors.inc.php";
require "common.php";

$request_id=$_GET['request_id'];
$reply=$_GET['reply'];
$uuid=$context['uuid'];  if (!isset($uuid)) $uuid=0;

if (!isset($request_id) || !isset($reply) || ($uuid==0)) {echo "<script language='JavaScript' type='text/javascript'>window.location='$sweet_home';</script>";	 return;}

require "smtp_mail.inc.php";

function ShowResult()
{
  global $sys_name, $err_no, $result, $sweet_home;
  $serr=($err_no > 0) ? "(Error code: $err_no)" : "";
  echo "<div align=center>$result $serr<br>";
  echo "<a href='$sweet_home'>$sys_name</a><br>";
  echo date("Y-m-d H:i:s")."</div>";
}

require "connect.inc.php";
if (!$link || ($err_no > 0)) {ShowResult(); return;} //mySQL error

$err_no=ERR_NONE; 
$result="Operation completed successfully!"; 
$ret=array();

//get the request data
$query="SELECT * FROM Requests WHERE request_id=$request_id LIMIT 1";
$res=mysqli_query($link, $query);
$rows=mysqli_num_rows($res);
if (!$rows) 
{
  $err_no=ERR_MYSQL_R; 
  $result="Server error: no request_id=".$request_id;
  ShowResult(); return;
} else $arequest = mysqli_fetch_array($res, MYSQLI_ASSOC); 

if ($arequest['author_id2']==0) $oper='reject';
else $oper='exchange';

//GET (initiator) USER DATA 
$query="SELECT * FROM Author WHERE author_id=".$arequest['author_id1']." LIMIT 1";
$res=mysqli_query($link, $query);
$rows=mysqli_num_rows($res);
if (!$rows) 
{
    $err_no=ERR_MYSQL_R; 
    $result="Server error: no author_id=".$arequest['author_id1'];
    ShowResult(); return;
} else $auser1 = mysqli_fetch_array($res, MYSQLI_ASSOC); 

$status=($reply=="yes") ? 1 : 2;


if ($oper=='reject')
{  
  //CHECK if MANAGER (check here if $uuid belongs to a manager (just in case): if found - ok, else - exit)
  $query="SELECT shift_role FROM Author WHERE author_id=$uuid AND shift_role=".MANAGER." LIMIT 1";
  $res=mysqli_query($link, $query);
  $rows=mysqli_num_rows($res);
  if (!$rows) 
  {
    $err_no=ERR_USER; 
    $result="Access for this operation is not granted for you";
    ShowResult(); return;
  } else $amanager = mysqli_fetch_array($res, MYSQLI_ASSOC);
  
  if ($status==1) //remove the shift from 'Shifts' table
  {
    $author_id=$arequest['author_id1'];
    $stype=$arequest['type1'];
    $dtime=$arequest['dtime1'];
    $from1=$arequest['date1'];
    $days=$arequest['days1'];
    
    $dfrom=strtotime($from1);
    $dupto=$dfrom+$days*86400;
    $from=date("Y-m-d",$dfrom);
    $upto=date("Y-m-d",$dupto);
  
    $query="DELETE FROM Shifts WHERE (Shifts.date >= '$from') AND (Shifts.date < '$upto') AND (author_id=$author_id) AND (dtime=$dtime) ORDER BY Shifts.author_id,Shifts.date ASC LIMIT $days";
    $res=mysqli_query($link, $query);  
    if ($res==FALSE)
    {
      $err_no=ERR_MYSQL_Q; 
      $result=mysqli_error($link)." (err=".mysqli_errno($link).")";
      ShowResult(); return;    
    }
  }
  //change request status:
  $query="UPDATE Requests SET status=$status WHERE request_id=$request_id LIMIT 1";
  $res=mysqli_query($link, $query);  
  if ($res==FALSE)
  {
    $err_no=ERR_MYSQL_Q; 
    $result=mysqli_error($link)." (err=".mysqli_errno($link).")";
    ShowResult(); return;    
  }
  //inform shifter on decision: 
  $decision=($reply=="yes") ? "ALLOWED" : "DENIED";
  $type=$arequest['type1'];
  $dtime=$arequest['dtime1'];
  $to1=$auser1['name']." <".$auser1['email'].">";            
  $subject1="BES-III shift block rejected by you";
  $body1="Dear ".$auser1['name'].",<br>the following shifts block of yours is $decision to release:<br>";
  $body1.="shifter:  you<br>type : ".$stypes[$type]."<br>time of day: ".$dtnames[$dtime]."<br>start date: ".$arequest['date1']."<br>block (days): ".$arequest['days1']."<br><br>";  
  if ($reply=="yes")
    $body1.="Notice: the block is now open for booking by other shifters.<br>";  
  else  
    $body1.="Notice: the block is still yours!!!<br>Use live contact to managers if you'd like to dispute the decision.<br>";  
  $ret = informUser($to1, $subject1, $body1);
  $err_no=$ret['err_no'];
  $result=$ret['result'];
  //$result="DEBUG: to=$to1, subj=$subject1, body=$body1";
}

else if ($oper=='exchange')
{
  //GET (addressee) USER DATA 
  $query="SELECT * FROM Author WHERE author_id=".$arequest['author_id2']." LIMIT 1";
  $res=mysqli_query($link, $query);
  $rows=mysqli_num_rows($res);
  if (!$rows) 
  {
    $err_no=ERR_MYSQL_R; 
    $result="Server error: no author_id=".$arequest['author_id2'];
    ShowResult(); return;
  } else $auser2 = mysqli_fetch_array($res, MYSQLI_ASSOC); 

  if ($status==1) //swap user's author_id in 'Shifts' table
  {
    $uuid1=$arequest['author_id1'];   $uuid2=$arequest['author_id2'];

  //update user1:    
    $from1=$arequest['date1'];
    $dfrom=strtotime($from1);
    $dupto=$dfrom+$arequest['days1']*86400;
    $from=date("Y-m-d",$dfrom);
    $upto=date("Y-m-d",$dupto);
    $query="UPDATE Shifts SET author_id={$arequest[author_id2]} WHERE author_id={$arequest[author_id1]}  AND (Shifts.date >= '$from') AND (Shifts.date < '$upto') AND (dtime={$arequest[dtime1]}) ORDER BY Shifts.author_id,Shifts.date ASC LIMIT {$arequest[days1]}";
    $res=mysqli_query($link, $query);  
    if ($res==FALSE){$err_no=ERR_MYSQL_Q; $result=mysqli_error($link)." (err=".mysqli_errno($link).")";}
    else if (mysqli_affected_rows($link)==0)	{$err_no=ERR_MYSQL_W; $result="Nothing to do";}    
    if ($err_no!=ERR_NONE) {ShowResult(); return;}

  //update user2:    
    $from1=$arequest['date2'];
    $dfrom=strtotime($from1);
    $dupto=$dfrom+$arequest['days2']*86400;
    $from=date("Y-m-d",$dfrom);
    $upto=date("Y-m-d",$dupto);
    $query="UPDATE Shifts SET author_id={$arequest[author_id1]} WHERE author_id={$arequest[author_id2]}  AND (Shifts.date >= '$from') AND (Shifts.date < '$upto') AND (dtime={$arequest[dtime2]}) ORDER BY Shifts.author_id,Shifts.date ASC LIMIT {$arequest[days2]}";
    $res=mysqli_query($link, $query);  
    if ($res==FALSE){$err_no=ERR_MYSQL_Q; $result=mysqli_error($link)." (err=".mysqli_errno($link).")";}
    else if (mysqli_affected_rows($link)==0)	{$err_no=ERR_MYSQL_W; $result="Nothing to do";}    
    if ($err_no!=ERR_NONE) {ShowResult(); return;}
  }  

  //change request status:
  $query="UPDATE Requests SET status=$status WHERE request_id=$request_id LIMIT 1";
  $res=mysqli_query($link, $query);  
  if ($res==FALSE)
  {
    $err_no=ERR_MYSQL_Q; 
    $result=mysqli_error($link)." (err=".mysqli_errno($link).")";
    ShowResult(); return;    
  }
  
  //inform shifter on decision: 
  $decision=($reply=="yes") ? "AGREED" : "REFUSED";
  $type1=$arequest['type1'];
  $dtime1=$arequest['dtime1'];
  $type2=$arequest['type2'];
  $dtime2=$arequest['dtime2'];
  
  $to1=$auser1['name']." <".$auser1['email'].">";            
  $subject1="BES-III shift blocks exchange invoked by you";
  $body1="Dear ".$auser1['name'].",<br>the following shifts block of yours was $decision to exchange by another shifter:<br>";
  $was =  ($reply=="yes") ? "was - you, now - {$auser2[name]}" : "you";
  $body1.="shifter:  $was <br>type : ".$stypes[$type1]."<br>time of day: ".$dtnames[$dtime1]."<br>start date: ".$arequest['date1']."<br>block (days): ".$arequest['days1']."<br><br>";  
  if ($reply=="yes")
  {
    $body1.="Reminder: your new shifts block is now as follows:<br>";  
    $now = "was - {$auser2[name]}, now - you";
    $body1.="shifter:  $now<br>type : ".$stypes[$type2]."<br>time of day: ".$dtnames[$dtime2]."<br>start date: ".$arequest['date2']."<br>block (days): ".$arequest['days2']."<br><br>";  
  }
  else
  {  
    $body1.="Notice: the block is still yours!!!<br>Use live contact to the user if you'd like to dispute the decision.<br>";  
  }
  $ret = informUser($to1, $subject1, $body1);
  $err_no=$ret['err_no'];
  $result=$ret['result'];
}


mysqli_close($link);
ShowResult();
?>
