<?php
//round.php: handles the 'ShiftRound' table. Following is the list of functions currently supported:
//oper={last}
header("Content-type: text/html; charset=utf-8");

require "config.inc.php";
$key=$proj;
require "common.php";

$oper=$_POST['oper'];
if (!isset($oper)) {echo "<script language='JavaScript' type='text/javascript'>window.location='$sweet_home';</script>";	 return;}

require "connect.inc.php";
if (!$link || ($err_no > 0)) {$ret = array('err_no' => $err_no, 'result' => $result);  echo json_encode($ret);  return;} //mySQL error

$val=0; 
$err_no=ERR_NONE; 
$result="ok"; 
$ret=array();
$zerodate="0000-00-00";

if ($oper=="last")  //last => actual
{
  $query="SELECT * FROM ShiftRound WHERE shiftround_status=1 ORDER BY shiftround_id DESC LIMIT 1";
  $res=mysqli_query($link, $query);
  $rows=mysqli_num_rows($res);
  if (!$rows) {$err_no=ERR_MYSQL_R; $result="None shift rounds specified/opened! Any manager must specify/open at least one shift round.";}
  else $val = mysqli_fetch_array($res, MYSQLI_ASSOC);
}//end LAST

else if ($oper=="round_save")
{
  $shiftround=$_POST['shiftround'];  
  $bNew=($shiftround['shiftround_id']==0) ? true : false;
  $bDatesEditable = ($shiftround['shiftround_status']==ROUND_NEW) ? true : false;

  if ($shiftround['start_date'] > 0 && $shiftround['end_date'] > 0 && ($shiftround['start_date'] < $shiftround['end_date']))
  {
    $shiftround['start_date'] = date("Y-m-d",$shiftround['start_date']);
    $shiftround['end_date'] = date("Y-m-d",$shiftround['end_date']);      
  }
  else $bDatesEditable=false;

  //check overlapping with existing records
  if ($bNew==true || $bDatesEditable==true)
  {
    $from=$shiftround['start_date']; 
    $upto=$shiftround['end_date'];
    $query="SELECT * FROM ShiftRound WHERE ((start_date <='$from' AND end_date >='$from') OR (start_date <='$upto' AND end_date >='$upto') OR (start_date >='$from' AND end_date <='$upto')) AND (shiftround_id!={$shiftround[shiftround_id]}) LIMIT 1";
    $res=mysqli_query($link, $query);
    $rows=mysqli_num_rows($res);
    if ($rows > 0)
    {
      $row=mysqli_fetch_array($res, MYSQLI_ASSOC);
      $err_no=ERR_USER_DOUBLE; $result="Overlaps with the shift round period: ".$row['shiftround_name'];
      mysqli_close($link);
      $ret = array('err_no' => $err_no, 'result' => $result, 'val' => $val);
      echo json_encode($ret);
     return;
    }    
  }

  if ($bNew==true)
  {    
    $query="INSERT INTO ShiftRound (start_date,end_date,shiftround_name) VALUES ('{$shiftround[start_date]}','{$shiftround[end_date]}','{$shiftround[shiftround_name]}')";
    $res=mysqli_query($link, $query);
    if ($res==FALSE){$err_no=ERR_MYSQL_Q; $result=mysqli_error($link)." (err=".mysqli_errno($link).")";}
    else $val=mysqli_insert_id($link);
  }
  else if ($bDatesEditable==true)
  {
    $query="UPDATE ShiftRound SET start_date='{$shiftround[start_date]}',end_date='{$shiftround[end_date]}',shiftround_name='{$shiftround[shiftround_name]}' WHERE shiftround_id={$shiftround[shiftround_id]} LIMIT 1";
    $res=mysqli_query($link, $query);
    if ($res==FALSE){$err_no=ERR_MYSQL_Q; $result=mysqli_error($link)." (err=".mysqli_errno($link).")";}
    else if (mysqli_affected_rows($link)==0)	{$err_no=ERR_MYSQL_W; $result="Nothing to do";}
  }  
}

else if ($oper=="holiday_save")
{
  $holidays=$_POST['holidays'];
  foreach($holidays as $value)
  {
    $date=date("Y-m-d",$value['date']);
    $holiday=$value['holiday'];
    if (strlen($holiday) > 0) //add this date as holiday
      $query="INSERT INTO ShiftHolidays (date,holiday) VALUES ('$date','$holiday') ON DUPLICATE KEY UPDATE holiday='$holiday'";     
    else //erase this date as holiday
      $query="DELETE FROM ShiftHolidays WHERE date='$date' LIMIT 1";
    $res=mysqli_query($link, $query);
    if ($res==FALSE){$err_no=ERR_MYSQL_Q; $result=mysqli_error($link)." (err=".mysqli_errno($link).")"; break;}
  }    
}

else if ($oper=="institution_save")
{
  $institution=$_POST['institution'];  
  $instid=$institution['institution_id'];
  $bNew=($instid==0) ? true : false;
  $new_contact_person_id=$institution['contact_person_id'];
  $prev_contact_person_id=0;
  $bOwner=false;
  if ($institution['join_bes3_time']==0) $institution['join_bes3_time']=$zerodate;
  else $institution['join_bes3_time'] = date("Y-m-d",$institution['join_bes3_time']);
  if ($institution['leave_bes3_time']==0) $institution['leave_bes3_time']=$zerodate;
  else $institution['leave_bes3_time'] = date("Y-m-d",$institution['leave_bes3_time']);  

  if ($bNew==false)
  {
    $query="SELECT contact_person_id FROM Institution WHERE institution_id=$instid LIMIT 1";
    $res=mysqli_query($link, $query);
    $rows=mysqli_num_rows($res);
    if (!$rows) 
    {
      $err_no=ERR_MYSQL_R; 
      $result="Server error: no institution_id=".$instid;
    } 
    else 
    {
    	$row = mysqli_fetch_array($res, MYSQLI_ASSOC); 
    	$prev_contact_person_id = $row['contact_person_id'];
      //GET prev USER DATA for 'informUser' and for 'just in case'
    	if ($prev_contact_person_id > 0)	//get previous instrep info
    	{
        $query="SELECT * FROM Author WHERE author_id=$prev_contact_person_id LIMIT 1";
        $res=mysqli_query($link, $query);
        $rows=mysqli_num_rows($res);
        if (!$rows) 
        {
          $err_no=ERR_MYSQL_R; 
          $result="Server error: no author_id=".$prev_contact_person_id;
        } else $prev_user = mysqli_fetch_array($res, MYSQLI_ASSOC);
    	}
      //GET new USER DATA for 'informUser' and for 'just in case'
    	if ($new_contact_person_id > 0)	//get new instrep info
    	{
        $query="SELECT * FROM Author WHERE author_id=$new_contact_person_id LIMIT 1";
        $res=mysqli_query($link, $query);
        $rows=mysqli_num_rows($res);
        if (!$rows) 
        {
          $err_no=ERR_MYSQL_R; 
          $result="Server error: no author_id=".$new_contact_person_id;
        } else $new_user = mysqli_fetch_array($res, MYSQLI_ASSOC);
    	}
    	
    }
    $bOwner=($prev_contact_person_id==$context['uuid']) ? true: false;
  }
  
  //save data  
  if ($err_no==ERR_NONE && $bNew==true)
  {    
    $query="INSERT INTO Institution (full_name,abbreviation_name,address1,continent,contact_person_id,join_bes3_time,leave_bes3_time,description,creator_id,create_time,location) VALUES ('{$institution[full_name]}','{$institution[abbreviation_name]}','{$institution[address1]}','{$institution[continent]}',{$institution[contact_person_id]},'{$institution[join_bes3_time]}','{$institution[leave_bes3_time]}','{$institution[description]}',{$institution[creator_id]},NOW(),{$institution[location]})";
    $res=mysqli_query($link, $query);
    if ($res==FALSE){$err_no=ERR_MYSQL_Q; $result=mysqli_error($link)." (err=".mysqli_errno($link).")";}
    else $last=mysqli_insert_id($link);
  }
  else if ($err_no==ERR_NONE)	//not new
  {
    $query="UPDATE Institution SET full_name='{$institution[full_name]}',abbreviation_name='{$institution[abbreviation_name]}',address1='{$institution[address1]}',continent='{$institution[continent]}',contact_person_id={$institution[contact_person_id]},join_bes3_time='{$institution[join_bes3_time]}',leave_bes3_time='{$institution[leave_bes3_time]}',description='{$institution[description]}',location={$institution[location]} WHERE institution_id={$institution[institution_id]} LIMIT 1";
    $res=mysqli_query($link, $query);
    if ($res==FALSE){$err_no=ERR_MYSQL_Q; $result=mysqli_error($link)." (err=".mysqli_errno($link).")  QUERY=".$query;}
    else if (mysqli_affected_rows($link)==0)	{$err_no=ERR_MYSQL_W; $result="Nothing to do";}
  }  

  if ($err_no==ERR_NONE)
  {
    $val=array();
    //notify instreps if needed on changes: 0-none, 1-prev, 2-new, 3-both
    $b1="no longer"; $b2="assigned as"; $val['sendto']=0;
    if ($new_contact_person_id==$prev_contact_person_id)
    {    	
      $val['sendto']=(($bOwner==true) || ($new_contact_person_id==0))? 0: 1; 
      $subj = "data changed";    	 //notification on change -don't send for self
    }
    else
    {
    	$subj = "representative change";
    	$val['sendto']=0;
      if ($prev_contact_person_id!=0) $val['sendto'] |=1;
      if ($new_contact_person_id!=0) $val['sendto'] |=2;
    }
    if ($val['sendto'] > 0)
    {
      $val['subject']="BES-III Institution (".$institution['abbreviation_name'].") ".$subj;
    	if (($val['sendto'] & 1)==1)
    	{
        //change author's shift role:
        $query="UPDATE Author SET shift_role=".SHIFTER." WHERE author_id={$prev_user[author_id]} LIMIT 1";
        $res=mysqli_query($link, $query);
        if ($res==FALSE){$err_no=ERR_MYSQL_Q; $result=mysqli_error($link)." (err=".mysqli_errno($link).")";}
        //else if (mysql_affected_rows($link)==0)	{$err_no=ERR_MYSQL_W; $result="Nothing to do";}        
        
        $email=$prev_user['email'];
        $val['body']="Dear ".$prev_user['name'].",<br>this message is just to inform you, that you are<br>$b1 a contact person (representative) of your institution in the BES-III Collaboration.<br>If some error occurred - please apply to BES-III manager for assistance and explanations.";
        $val['to']=$prev_user['name']." <".$prev_user['email'].">";            
    	}
    	if ( ($err_no==ERR_NONE) &&(($val['sendto'] & 2)==2))
    	{
        //change author's shift role        
        $query="UPDATE Author SET shift_role=".INSTREP." WHERE author_id={$new_user[author_id]} LIMIT 1";
        $res=mysqli_query($link, $query);
        if ($res==FALSE){$err_no=ERR_MYSQL_Q; $result=mysqli_error($link)." (err=".mysqli_errno($link).")";}

        $body="Dear ".$new_user['name'].",<br>this message is just to inform you, that you are<br>$b2 a contact person (representative) of your institution in the BES-III Collaboration.<br>If any error occurred - please apply to BES-III shift manager for assistance and explanations.";
        $email=$new_user['email']; 
        $to=$new_user['name']." <".$new_user['email'].">";
        if ($val['sendto']==2)   //to new only
        {
          $val['body']=$body; 
          $val['to']=$to;
        }
        else   //to both
        {
          $val['body2']=$body; 
          $val['to2']=$to;
        }
    	}
    }
  }//notifications if no errors  
}

else if ($oper=="round_open")
{
  $shiftround=$_POST['shiftround'];  
//GET round info
  $query="SELECT * FROM ShiftRound WHERE shiftround_id=$shiftround LIMIT 1";
  $res=mysqli_query($link, $query);
  $rows=mysqli_num_rows($res);
  if (!$rows) {$err_no=ERR_MYSQL_R; $result="Possible server error.";}
  else $round = mysqli_fetch_array($res, MYSQLI_ASSOC);

//BEGIN CHECKING UP------
  if ($err_no==ERR_NONE)
  {
  //CHECK holidays list
    $from=$round['start_date'];
    $upto=$round['end_date'];
    $query="SELECT COUNT(*) as total FROM ShiftHolidays WHERE date>='$from' AND date <='$upto'";  
    $res=mysqli_query($link, $query);
    if ($res==FALSE){$err_no=ERR_MYSQL_Q; $result=mysqli_error($link)." (err=".mysqli_errno($link).")"; $cnt_err++;}
    else
    {
      if (mysqli_num_rows($res) == 0)
      {
        $err_no=ERR_USER;
        $result="Cannot open the round: its holidays list is missing.";
      }
    }
  }
  if ($err_no==ERR_NONE)
  {
   //CHECK institutions
   $query="SELECT COUNT(*)  AS total FROM Institution WHERE contact_person_id=0 OR LENGTH(continent)=0 OR location=".INSTLOC_UNDEF;
   $res=mysqli_query($link, $query);
   if ($res==FALSE){$err_no=ERR_MYSQL_Q; $result=mysqli_error($link)." (err=".mysqli_errno($link).")"; $cnt_err++;}
   else
   {
      if (mysqli_num_rows($res) > 0)
      {
        $row = mysqli_fetch_array($res, MYSQLI_ASSOC);
        if($row['total']>0) {
           $err_no=ERR_USER;
           $result="Cannot open the round: ".$row['total']." institution(s) have one of the following necessary data missing: contact person, continent, location. Check your daily system report for details.";
        }
      }
    }
  }
//END CHECKING UP and  -------
  
  //add static oncall points for this round
  if ($err_no==ERR_NONE && isset($aoncall) && count($aoncall) > 0)
  {
    $round_subsys_points = round((strtotime($upto) - strtotime($from))/(7*86400))*POINTS_ONCALLEXPERT;
    foreach ($aoncall as $value)
    {
      $points=$value['subsystems']*$round_subsys_points;
      $inst=$value['institution_id'];
      $query="INSERT INTO OncallShifts(shiftround_id,institution_id,oncall_points) VALUES ('".$shiftround."','".$inst."','".$points."')";
      $res=mysqli_query($link, $query);
      if ($res==FALSE){$err_no=ERR_MYSQL_Q; $result=mysqli_error($link)." (err=".mysqli_errno($link).")";}
    }
  }
  //launch round PR company
  if ($err_no==ERR_NONE)
  {
    $query="UPDATE ShiftRound SET open_date=NOW(),shiftround_status=".ROUND_OPEN.",pr=".INSTLOC_OUTSIDE." WHERE shiftround_id=$shiftround LIMIT 1";
    $res=mysqli_query($link, $query);
    if ($res==FALSE){$err_no=ERR_MYSQL_Q; $result=mysqli_error($link)." (err=".mysqli_errno($link).")";}
    else if (mysqli_affected_rows($link)==0)	{$err_no=ERR_MYSQL_W; $result="Nothing to do";}
    else $result="The new round notification company would start automatically within 1 day.";
    //the rest of the work - for rundaily.php module
  }  
}

else if ($oper=="round_close")
{
  $shiftround=$_POST['shiftround'];  
  $query="UPDATE ShiftRound SET close_date=NOW(),shiftround_status=".ROUND_CLOSED." WHERE shiftround_id=$shiftround LIMIT 1";
  $res=mysqli_query($query, $link);
  if ($res==FALSE){$err_no=ERR_MYSQL_Q; $result=mysqli_error($link)." (err=".mysqli_errno($link).")";}
  else if (mysqli_affected_rows($link)==0)	{$err_no=ERR_MYSQL_W; $result="Nothing to do";}  
}

mysqli_close($link);
if (count($ret)==0) $ret = array('err_no' => $err_no, 'result' => $result, 'val' => $val);
echo json_encode($ret);
?>
