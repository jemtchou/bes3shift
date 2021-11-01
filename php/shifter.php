<?php
//shifter.php
header("Content-type: text/html; charset=utf-8");

require "config.inc.php";
require "common.php";

$oper=$_POST['oper'];
if (!isset($oper)) {echo "<script language='JavaScript' type='text/javascript'>window.location='$sweet_home';</script>";	 return;}

require "connect.inc.php";
if (!$link || ($err_no > 0)) {$ret = array('err_no' => $err_no, 'result' => $result);  echo json_encode($ret);  return;} //mySQL error

$val=array(); 
$err_no=ERR_NONE; 
$result="ok"; 
$ret=array(); //init default return vars

//for 'type'  != RUNCOORDINATOR
function CheckRules4Exchange($request)
{
	global $link;
	$err_no=ERR_NONE;
	$result="ok";
  $author_id1=$request['author_id1'];
  $author_id2=$request['author_id2'];

	//1 - get user1 * data 
	if ($err_no==ERR_NONE)
	{
    $query="SELECT * FROM Author WHERE author_id=$author_id1 LIMIT 1";
    $res=mysqli_query($link, $query);
    $rows=mysqli_num_rows($res);
    if (!$rows) {$err_no=ERR_MYSQL_R; $result="Server error: no author_id=$author_id1";} 
    else $user1 = mysqli_fetch_array($res, MYSQLI_ASSOC); 
  }  
	if ($err_no==ERR_NONE)
	{
	//2 - get user2 * data
    $query="SELECT * FROM Author WHERE author_id=$author_id2 LIMIT 1";
    $res=mysqli_query($link, $query);
    $rows=mysqli_num_rows($res);
    if (!$rows) {$err_no=ERR_MYSQL_R; $result="Server error: no author_id=$author_id2";} 
    else $user2 = mysqli_fetch_array($res, MYSQLI_ASSOC); 
  }
	//3 - define if lang check needed
	if ($err_no==ERR_NONE)
	{
	  $bCheckLang = ($user1['chinese_speaking']==$user2['chinese_speaking']) ? false : true;	
  }
  //4 - CHECK for noncontiquous shifts and lang - for user1 environment
  $days=$request['days1'];
  $dfrom=strtotime($request['date1']);
  $dupto=$dfrom+($days-1)*86400;
  $dtime=$request['dtime1'];
  if ($err_no==ERR_NONE)
  {
    $dfromminus=$dfrom-86400;
    $duptoplus=$dupto+86400;
    $fromminus=date("Y-m-d",$dfromminus);
    $uptoplus=date("Y-m-d",$duptoplus);

    //collect all records for the client for the book period -1 +1 days
    $query="SELECT * FROM Shifts WHERE (Shifts.date >= '$fromminus') AND (Shifts.date <= '$uptoplus') AND ((author_id=$author_id1) OR (author_id=$author_id2)) ORDER BY Shifts.date ASC";
    $res=mysqli_query($link, $query);
    $rows=mysqli_num_rows($res);
    if ($rows > 0)
    {
      $adtime=array_fill(0,($days+2)*3,0);  //including -1 day and +1day 
      while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) 
      {
        $ts = strtotime($row['date']);
        $dt = $row['dtime']-1;  //1-night, 2-day, 3-evening and NO COORDINATORS with 0!
        $d=($ts - $dfromminus)/86400;
        $ind=$d*3+$dt;
        $adtime[$ind]=1;
      }
      for($ind=2; $ind < ($days+1)*3; $ind++)//(00x)|(xxx)(xxx)..(xxx)|(x00)
      {
        if (($adtime[$ind]==1) && ($adtime[$ind+1]==1))
        {$err_no=ERR_USER_DOUBLE; $result="Taking two consecutive shifts is forbidden!"; break;}
      }
    } //end if found
    //check lang.rule violation for user2 within user1 block if he doesn't speak chinese:
    if (($err_no==ERR_NONE) && ($bCheckLang==true) && ($user2['chinese_speaking']==0))
    {
    	$from=date("Y-m-d",$dfrom);
      $upto=date("Y-m-d",$dupto);
      $query="SELECT chinese_speaking FROM Author LEFT JOIN Shifts ON (Author.author_id=Shifts.author_id) WHERE (Shifts.date >= '$from') AND (Shifts.date <= '$upto') AND (Shifts.dtime=$dtime) AND (chinese_speaking=0) ORDER BY Shifts.date ASC";
      $res=mysqli_query($link, $query);
      $rows=mysqli_num_rows($res);
      if ($rows > 0){$err_no=ERR_USER_DOUBLE; $result="At least one shifter must speak chinese.";}
    }
  }	
  //5 - CHECK for noncontiquous shifts and lang - for user2 environment
  $days=$request['days2'];
  $dfrom=strtotime($request['date2']);
  $dupto=$dfrom+($days-1)*86400;
  $dtime=$request['dtime2'];
  if ($err_no==ERR_NONE)
  {
    $dfromminus=$dfrom-86400;
    $duptoplus=$dupto+86400;
    $fromminus=date("Y-m-d",$dfromminus);
    $uptoplus=date("Y-m-d",$duptoplus);
    //collect all records for the client for the book period -1 +1 days
    $query="SELECT * FROM Shifts WHERE (Shifts.date >= '$fromminus') AND (Shifts.date <= '$uptoplus') AND ((author_id=$author_id1) OR (author_id=$author_id2)) ORDER BY Shifts.date ASC";
    $res=mysqli_query($link, $query);
    $rows=mysqli_num_rows($res);
    if ($rows > 0)
    {
      $adtime=array_fill(0,($days+2)*3,0);  //including -1 day and +1day 
      while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) 
      {
        $ts = strtotime($row['date']);
        $dt = $row['dtime']-1;  //1-night, 2-day, 3-evening and NO COORDINATORS with 0!
        $d=($ts - $dfromminus)/86400;
        $ind=$d*3+$dt;
        $adtime[$ind]=1;
      }
      for($ind=2; $ind < ($days+1)*3; $ind++)//(00x)|(xxx)(xxx)..(xxx)|(x00)
      {
        if (($adtime[$ind]==1) && ($adtime[$ind+1]==1))
        {$err_no=ERR_USER_DOUBLE; $result="Taking two consecutive shifts is forbidden!"; break;}        
      }
    } //end if found
    //check lang.rule violation for user1 within user2 block if he doesn't speak chinese:
    if (($err_no==ERR_NONE) && ($bCheckLang==true) && ($user1['chinese_speaking']==0))
    {
    	$from=date("Y-m-d",$dfrom);
      $upto=date("Y-m-d",$dupto);
      $query="SELECT chinese_speaking FROM Author LEFT JOIN Shifts ON (Author.author_id=Shifts.author_id) WHERE (Shifts.date >= '$from') AND (Shifts.date <= '$upto') AND (Shifts.dtime=$dtime) AND (chinese_speaking=0) ORDER BY Shifts.date ASC";
      $res=mysqli_query($link, $query);
      $rows=mysqli_num_rows($res);
      if ($rows > 0){$err_no=ERR_USER_DOUBLE; $result="At least one shifter must speak chinese.";}
    }
  }
	return array("err_no" => $err_no,"result" => $result);
}

if ($oper=="populate") //for 'PopulateMonth' and 'PopulateRound'
{
  $view=$_POST['view'];
  if ($view=="month")
  {
    $msfrom=$_POST['msfrom']; //'YYYY-MM-DD'
    $msupto=$_POST['msupto'];
    $dfrom=$msfrom/1000;
    $dupto=$msupto/1000;  //including!
    $from=date("Y-m-d",$dfrom);
    $upto=date("Y-m-d",$dupto);
    $res=mysqli_query($link, "set names utf8");
    $query="SELECT Author.author_id,Author.institution_id,Shifts.date,Shifts.dtime,Shifts.type,Shifts.score,Shifts.remote,Author.initials,CONVERT(CONVERT(Author.name USING binary) USING utf8) AS name,CONVERT(CONVERT(Author.family_name USING binary) USING utf8) AS family_name,Author.email,Author.join_bes3_time,Author.leave_bes3_time,Author.leave_bes3,Author.chinese_speaking,Author.canbechief,Author.chinese_name_unicode FROM Shifts LEFT JOIN Author ON (Shifts.author_id=Author.author_id) WHERE (Shifts.date >= '$from') AND (Shifts.date <= '$upto') ORDER BY Shifts.author_id,Shifts.date ASC";
  }
  else // if ($view=="round")
  {
    $from=$_POST['from']; //'YYYY-MM-DD'
    $upto=$_POST['upto'];
    $query="SELECT * FROM Shifts WHERE (Shifts.date >= '$from') AND (Shifts.date <= '$upto') ORDER BY Shifts.author_id,Shifts.date ASC";
  }  
  $res=mysqli_query($link, $query);
  if ($res==FALSE){$err_no=ERR_MYSQL_Q; $result=mysqli_error($link)." (err=".mysqli_errno($link).")";}
  else
  {
    $rows=mysqli_num_rows($res);
    if ($rows > 0){while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {$val[] = $row;}}
    //GET HOLIDAYS list for this span if any    
    $result=array();
    $query="SELECT * FROM ShiftHolidays WHERE ShiftHolidays.date >= '$from' AND ShiftHolidays.date <= '$upto' ORDER BY date ASC";
    $res=mysqli_query($link, $query);
    $rows=mysqli_num_rows($res);
    if ($rows > 0){while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {$result[] = $row;}}    
  }
}//end POPULATE

else if ($oper=="book") //for 'ShiftBook'
{
  $institution_id=$_POST['institution_id'];
  $author_id=$_POST['author_id'];
  $stype=$_POST['stype'];
  $dtime=$_POST['dtime'];
  $msfrom=$_POST['msfrom'];
  $days=$_POST['days'];
  $startsindays=$_POST['startsindays'];
  $bManAss=$_POST['bManAss'];
  $from=$_POST['from'];
  $upto=$_POST['upto'];
  $remote=$_POST['remote'];
  $bOwner=($author_id==$context['uuid'])?true:false;

  $dfrom=strtotime($from);
  $dupto=strtotime($upto);

  $aPoints=array(POINTS_COORDINATOR, POINTS_NIGHT, POINTS_DAY, POINTS_EVENING);
  $aScore=array_fill(0,$days,$aPoints[$dtime]); //DTIME_ALL,DTIME_NIGHT,DTIME_DAY,DTIME_EVENING

  //GET USER DATA for chinese checkup and for 'informUser' and for 'just in case'
  $query="SELECT * FROM Author WHERE author_id=$author_id LIMIT 1";
  $res=mysqli_query($link, $query);
  $rows=mysqli_num_rows($res);
  if (!$rows) 
  {
    $err_no=ERR_MYSQL_R; 
    $result="Server error: no author_id=".$uuid;
  } else $user = mysqli_fetch_array($res, MYSQLI_ASSOC); 

//BEGIN CHECKS
  
  //CHECK if available (for the case of nearly simultaneous booking)
  if ($err_no==ERR_NONE)
  {
    $query="SELECT * FROM Shifts  WHERE (Shifts.date >= '$from') AND (Shifts.date <= '$upto') AND (Shifts.type=$stype) AND (Shifts.dtime=$dtime) ORDER BY Shifts.date ASC";
    $res=mysqli_query($link, $query);
    $rows=mysqli_num_rows($res);
    if ($rows > 0)
    {
      $err_no=ERR_USER_DOUBLE; 
      $result="Sorry, your are late. This block was booked a few seconds ago.";
    }
  }

  //CHECK the same dtime but with another role (double user per dtime)
  if (($err_no==ERR_NONE) && ($stype==ORDINARY || $stype==CHIEF))
  {
    $query="SELECT * FROM Shifts  WHERE (Shifts.date >= '$from') AND (Shifts.date <= '$upto') AND (author_id=$author_id) AND (Shifts.dtime=$dtime) ORDER BY Shifts.date ASC";
    $res=mysqli_query($link, $query);
    $rows=mysqli_num_rows($res);
    if ($rows > 0){$err_no=ERR_USER_DOUBLE; $result="A chief shifter and an ordinary shifter must be different persons.";}
  }
  //CHECK the same period for coordinator
  if (($err_no==ERR_NONE) && ($stype==RUNCOORDINATOR))
  {
    $query="SELECT * FROM Shifts  WHERE (Shifts.date >= '$from') AND (Shifts.date <= '$upto') AND (author_id=$author_id) ORDER BY Shifts.date ASC";
    $res=mysqli_query($link, $query);
    $rows=mysqli_num_rows($res);
    if ($rows > 0){$err_no=ERR_USER_DOUBLE; $result="You are already taking another shift during this period.";}
  }
  
  //CHECK for noncontiquous shifts
  if (($err_no==ERR_NONE) && ($stype==ORDINARY || $stype==CHIEF))
  {
    $dfromminus=$dfrom-86400;
    $duptoplus=$dupto+86400;
    $fromminus=date("Y-m-d",$dfromminus);
    $uptoplus=date("Y-m-d",$duptoplus);
    //collect all records for the client for the book period -1 +1 days
    $query="SELECT * FROM Shifts WHERE (Shifts.date >= '$fromminus') AND (Shifts.date < '$uptoplus') AND (author_id=$author_id) ORDER BY Shifts.date ASC";
    $res=mysqli_query($link, $query);
    $rows=mysqli_num_rows($res);
    if ($rows > 0)
    {
      $adtime=array_fill(0,$days+2,0);  //including -1 day and +1day
      while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) 
      {
        $ts = strtotime($row['date']);
        $d=($ts - $dfromminus)/86400;
        $adtime[$d] = $row['dtime'];
      }
      for($n=1; $n<=$days; $n++)
      {
        if (($adtime[$n-1]==3 && $dtime==1) || ($adtime[$n+1]==1 && $dtime==3) || ($adtime[$n]==1 && $dtime==2) || ($adtime[$n]==2 && $dtime==1) || ($adtime[$n]==2 && $dtime==3) || ($adtime[$n]==3 && $dtime==2))
        {$err_no=ERR_USER_DOUBLE; $result="Taking two consecutive shifts is forbidden!"; break;}
      }
    } //end if found
  }

  //CHECK for at least one chinese speaking person the same dtime of all intersecting shifts but with another role   
  ////2 chinese speaking persons is ok, so, if a person is chinese speaking - we don't check language
/*  if (($err_no==ERR_NONE) && ($stype==ORDINARY || $stype==CHIEF) && ($user['chinese_speaking']==0))
  {    
    $query="SELECT chinese_speaking FROM Author LEFT JOIN Shifts ON (Author.author_id=Shifts.author_id) WHERE (Shifts.date >= '$from') AND (Shifts.date <= '$upto') AND (Shifts.dtime=$dtime) AND (chinese_speaking=0) ORDER BY Shifts.date ASC";
    $res=mysqli_query($link, $query);
    $rows=mysqli_num_rows($res);
    if ($rows > 0){$err_no=ERR_USER_DOUBLE; $result="At least one shifter must speak chinese";}
  }  //if non-chinese-speaking  
*/ 
  // check that authors follow the sequence of shift booking (outside,non-Beijing, Beijing)
  if ($err_no==ERR_NONE)
  {
    $query="SELECT * FROM Author,Institution,ShiftRound WHERE Author.author_id=$author_id AND Author.institution_id=Institution.institution_id AND Institution.location>=ShiftRound.pr AND ShiftRound.shiftround_status=1";
    $res=mysqli_query($link, $query);
    $rows=mysqli_num_rows($res);
    if ($rows == 0){$err_no=ERR_NOT_OPEN_YET; $result="Shift booking is not open for your institution yet";}
  }  //wrong time 
 
  if ($err_no!=ERR_NONE)
  {
    mysqli_close($link);
    $ret = array('err_no' => $err_no, 'result' => $result, 'val' => $val);
    echo json_encode($ret);
    return;
  }
  //TO DO (?): check 1/3 night shifts - take portion from rostat.php
//END CHECKS
  
  //get additional holidays points if any
  $query="SELECT * FROM ShiftHolidays WHERE ShiftHolidays.date >= '$from' AND ShiftHolidays.date < '$upto' ORDER BY date ASC";
  $res=mysqli_query($link, $query);
  $rows=mysqli_num_rows($res);
  if ($rows > 0)
  {
    while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {$hd[] = $row;}
    foreach ($hd as $value)
    {
      $ts = strtotime($value['date']);
      $d=($ts - $dfrom)/86400;
      $aScore[$d]+= POINTS_HOLIDAY;
    }    
  }
  $val['points']=array_sum($aScore);
  if($stype==CHIEF)  // rules of 01 Feb 2021
    {
$val['points']=array_sum($aScore)*1.2;
    }
    if($stype==ORDINARY)
    {
$val['points']=array_sum($aScore)*0.8;
    }
 
  for($d=0; $d < $days; $d++)
  {
    $from=$dfrom+$d*86400;
    $score=$aScore[$d];
    if($stype==CHIEF)  // rules of 01 Feb 2021
    {
       $score=$score*1.2;
    } 
    if($stype==ORDINARY)
    {
       $score=$score*0.8;
    }
    $date=date("Y-m-d",$from);
    $booking_time=date("Y-m-d",time());
    $query="INSERT INTO Shifts (author_id,institution_id,Shifts.date,dtime,type,score,booking_time,remote) VALUES ($author_id,$institution_id,'$date',$dtime,$stype,$score,'$booking_time',$remote)";
    $res=mysqli_query($link, $query);
    if ($res==FALSE)break;
  }
  if ($res==FALSE){$err_no=ERR_MYSQL_Q; $result=mysqli_error($link)." (err=".mysqli_errno($link).")";}

  else
  {
    $email=$user['email'];    
    //make up informUser data
    $val['body']="Dear ".$user['name'].",<br>the following shifts block is booked for you:<br>";
    $val['body'].="type : {$stypes[$stype]}<br>time of day: {$dtnames[$dtime]}<br>start date: ".date("d-M-Y",$dfrom)."<br>end date: ".date("d-M-Y",$dfrom+$days*86400-1)."<br>block (days): $days<br>starts in (days): $startsindays<br>score (points): {$val[points]}";
    if ($bOwner==false) $val['subject']="BES-III shift manager booked shifts for you";
    else $val['subject']="BES-III shifts booked by you";
    $val['to']=$user['name']." <".$user['email'].">";            
  }
}

else if ($oper=="reject") //for 'Shiftreject'
{
  $author_id=$_POST['author_id'];
  $stype=$_POST['stype'];
  $dtime=$_POST['dtime'];
  $msfrom=$_POST['msfrom'];
  $days=$_POST['days'];
  $startsindays=$_POST['startsindays'];
  $bManAss=$_POST['bManAss'];
  $from=$_POST['from'];
  $upto=$_POST['upto'];
  $bOwner=($author_id==$context['uuid'])? true : false;
  
  $dfrom=strtotime($from);
  $dupto=strtotime($upto);

  // check booking time
  $query="SELECT booking_time FROM Shifts WHERE (Shifts.date >= '$from') AND (Shifts.date <= '$upto') AND (author_id=$author_id) AND (dtime=$dtime) ORDER BY Shifts.date ASC LIMIT $days";
  $res=mysqli_query($link, $query);  
  $row = mysqli_fetch_array($res, MYSQLI_ASSOC);
  $booking_time=$row['booking_time'];
  $btime=strtotime($booking_time);
  $ctime=date('Y-m-d',time());
  if($btime<$ctime-86400) // more than one day after booking
  {
    $err_no=ERR_MYSQL_Q; $result="Too late to cancel shift";
  }
  else
{
  //
  $query="DELETE FROM Shifts WHERE (Shifts.date >= '$from') AND (Shifts.date <= '$upto') AND (author_id=$author_id) AND (dtime=$dtime) ORDER BY Shifts.date ASC LIMIT $days";
  $res=mysqli_query($link, $query);  
  if ($res==FALSE){$err_no=ERR_MYSQL_Q; $result=mysqli_error($link)." (err=".mysqli_errno($link).")";}

  else
  {
    //get user data for 'informUser'
    $query="SELECT * FROM Author WHERE author_id=$author_id LIMIT 1";
    $res=mysqli_query($link, $query);
    $rows=mysqli_num_rows($res);
    if (!$rows) 
    {
      $err_no=ERR_MYSQL_R; $result="Server error: no author_id=".$uuid;
      mysqli_close($link);
      $ret = array('err_no' => $err_no, 'result' => $result, 'val' => $val);
      echo json_encode($ret);
      return;
    }
    $row = mysqli_fetch_array($res, MYSQLI_ASSOC); 
    $email=$row['email'];    
    //make up informUser data
    $val['body']="Dear ".$row['name'].",<br>the following shifts block of yours is cancelled:<br>";
    $val['body'].="type : {$stypes[$stype]}<br>time of day: {$dtnames[$dtime]}<br>start date: ".date("d-M-Y",$dfrom)."<br>finish date: ".date("d-M-Y",$dfrom+$days*86400-1)."<br>block (days): $days";
    if ($bOwner==false) $val['subject']="BES-III shift manager cancelled your shifts block";
    else $val['subject']="BES-III shifts cancelled by you";
    $val['to']=$row['name']." <".$row['email'].">";    
  }
 }
}

else if ($oper=="confreject")
{
  $request=$_POST['request'];
  $query="INSERT INTO Requests (author_id1,date1,dtime1,type1,days1,author_id2) VALUES ({$request[author_id1]},'{$request[date1]}',{$request[dtime1]},{$request[type1]},{$request[days1]},{$request[author_id2]})";
  $res=mysqli_query($link, $query);
  if ($res==FALSE){$err_no=ERR_MYSQL_Q; $result=mysqli_error($link)." (err=".mysqli_errno($link).")";}
  else
  {
    $dtime=$request['dtime1'];
    $type=$request['type1'];
    $request_id=mysqli_insert_id($link);
    $link="$sweet_home/php/confirm.php?request_id=$request_id&reply";
    $val['subject']="Request to cancel shift block";
    $val['body']="Dear Shift Manager,<br>your are asked to decide on the following request to cancel the shift block<br><br>";
    $val['body'].="shifter:  ".$request['user1']."<br>type : ".$stypes[$type]."<br>time of day: ".$dtnames[$dtime]."<br>start date: ".$request['date1']."<br>block (days): ".$request['days1']."<br><br>";
    $val['body'].="Click on <a href='$link=yes'>this link</a> to ALLOW the request to cancel shifts<br>OR<br> ";
    $val['body'].="Click on <a href='$link=no'>this link</a> to DENY the request to cancel shifts<br><br>";
    $val['body'].="The shifter will be informed by e-mail on your decision.<br>";    
    $val['body'].="Notice: if you preserve this mail - you'll have a chance to change your mind<br>within those days left until the block beginning.<br>";    
    $val['to']="BES-III shift manager <$duty_manager>";    
  }
}

else if ($oper="confexchange")
{
  $request=$_POST['request'];
 
  if ($request['type1']!=RUNCOORDINATOR)
  {
    $acheck = CheckRules4Exchange($request);
  	$err_no=$acheck['err_no'];
  	$result=$acheck['result'];
  }
  
  if ($err_no==ERR_NONE)
  {
    //GET USER DATA for chinese checkup and for 'informUser' and for 'just in case'
    $query="SELECT * FROM Author WHERE author_id=".$request['author_id2']." LIMIT 1";
    $res=mysqli_query($link, $query);
    $rows=mysqli_num_rows($res);
    if (!$rows) {$err_no=ERR_MYSQL_R; $result="Server error: no author_id=".$request['author_id2'];} 
    else $user2 = mysqli_fetch_array($res, MYSQLI_ASSOC); 
  }
  //store the request
  if ($err_no==ERR_NONE)
  {
    $query="INSERT INTO Requests (author_id1,date1,dtime1,type1,days1,author_id2,date2,dtime2,type2,days2) VALUES ({$request[author_id1]},'{$request[date1]}',{$request[dtime1]},{$request[type1]},{$request[days1]},{$request[author_id2]},'{$request[date2]}',{$request[dtime2]},{$request[type2]},{$request[days2]})";
    $res=mysqli_query($link, $query);
    if ($res==FALSE){$err_no=ERR_MYSQL_Q; $result=mysqli_error($link)." (err=".mysqli_errno($link).")";}
    else
    {
      //make up a letter
      $dtime1=$request['dtime1'];
      $type1=$request['type1'];
      $dtime2=$request['dtime2'];
      $type2=$request['type2'];
      $request_id=mysqli_insert_id($link);
      $link="$sweet_home/php/confirm.php?request_id=$request_id&reply";      
      $val['subject']="Request on shifts block exchange";
      $val['body']="Dear ".$request['user2'].",<br>your decision is required on the following shifts block exchange proposal:<br><br>";
      $val['body'].="shifter:  ".$request['user1']."<br>type : ".$stypes[$type1]."<br>time of day: ".$dtnames[$dtime1]."<br>start date: ".$request['date1']."<br>block (days): ".$request['days1']."<br><br>";
      $val['body'].="The shifter proposes to exchange it for your block:<br>";
      $val['body'].="shifter: you<br>type : ".$stypes[$type2]."<br>time of day: ".$dtnames[$dtime2]."<br>start date: ".$request['date2']."<br>block (days): ".$request['days2']."<br><br>";
      $val['body'].="Click on <a href='$link=yes'>this link</a> to AGREE the exchange proposed<br>OR<br> ";
      $val['body'].="Click on <a href='$link=no'>this link</a> to DECLINE the block exchange<br><br>";
      $val['body'].="The shifter will be informed by e-mail on your decision.<br>";    
      $val['body'].="Notice: if you preserve this mail - you'll have a chance to change your mind<br>within those days left until the nearest block beginning date.<br>";    
      
      $val['to']=$user2['name']." <".$user2['email'].">";          
    }
  }
}

mysqli_close($link);
if (count($ret)==0) $ret = array('err_no' => $err_no, 'result' => $result, 'val' => $val);
echo json_encode($ret);

?>
