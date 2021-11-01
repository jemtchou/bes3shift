<?php
//php/loadform.php - create form, depending on input parameters
//currently supproted forms: {author}
header("Content-type: text/html; charset=utf-8");

require "config.inc.php";
$key=$proj;
require "common.php";

$oper=$_POST['oper']; if (!isset($oper)) {echo "<script language='JavaScript' type='text/javascript'>window.location='$sweet_home';</script>";	 return;}

require "connect.inc.php";
if (!$link || ($err_no > 0)) {$ret = array('err_no' => $err_no, 'result' => $result);  echo json_encode($ret);  return;} //mySQL error

$val=0; 
$err_no=ERR_NONE; 
$result="ok"; 
$ret=array();
$zerodate="0000-00-00";

//common forms' params
$size=50; 
$dot="<span class=dot>*</span>";

if ($oper=="author")
{
  //edit create
  $uuid=$_POST['uuid'];
  $instid=$_POST['instid'];
  $shift_role=$_POST['shift_role'];
  //editor/creator
  $utype=$_POST['utype'];
  $cuuid=$_POST['cuuid'];
  $cinstid=$_POST['cinstid'];
  
  $bOwner = ($uuid==$cuuid) ? true: false;
  $bNew = ($uuid==0) ? true: false;
  $bManager = ($utype==MANAGER) ? true: false;
  

  if ($bNew==false)
  {
  //2017-01-16  //$query="SELECT * FROM Author WHERE author_id=$uuid LIMIT 1";
    $query="SELECT *,CONVERT(chinese_name_unicode USING binary) as ch_name FROM Author WHERE author_id=$uuid LIMIT 1";

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
    //get password
    $query="SELECT AES_DECRYPT(password,'$proj') AS pass FROM Author WHERE author_id=$uuid LIMIT 1";
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
    $row0 = mysqli_fetch_array($res, MYSQLI_ASSOC); 
    
         
    //get institution
    $institution_id=$row['institution_id'];
     //can't use JOIN due to predefined tables structure ((
    $query="SELECT * FROM Institution WHERE institution_id=$institution_id LIMIT 1";
    $res=mysqli_query($link, $query);
    $rows=mysqli_num_rows($res);
    if (!$rows) 
    {
      $err_no=ERR_MYSQL_R; $result="Server error: no institution_id=".$institution_id;
      mysqli_close($link);
      $ret = array('err_no' => $err_no, 'result' => $result, 'val' => $val);
      echo json_encode($ret);
      return;
    }
    $row1 = mysqli_fetch_array($res, MYSQLI_ASSOC);      
    $abbreviation_name=$row1['abbreviation_name'];
   
    $initials=$row['initials'];
//    $chinese_name=$row['chinese_name_unicode']; //24.05.2012 12:29:00
    $chinese_name=$row['ch_name']; 
    $name=$row['name'];
    $family_name=$row['family_name'];
    $institution_id=$row['institution_id'];
    $foot_note=$row['foot_note'];
    $email=$row['email'];
    $office=$row['office'];
    $telephone=$row['telephone'];
    $mobile_telephone=$row['mobile_telephone'];
    $position=$row['position'];
    $bes3_service=$row['bes3_service'];
    $bes3_interest=$row['bes3_interest'];
    $password=$row0['pass'];
    
    $chinese_speaking=$row['chinese_speaking'];
    $is_author=$row['is_author'];

    //reformat:
    $date=$row['join_bes3_time']; if ($date==$zerodate) $join_bes3_time=""; else {$d=strtotime($date); $join_bes3_time=date("m/d/Y",$d);}
    $date=$row['leave_bes3_time']; if ($date==$zerodate) $leave_bes3_time=""; else {$d=strtotime($date); $leave_bes3_time=date("m/d/Y",$d);}
    $date=$row['join_author_list_time']; if ($date==$zerodate) $join_author_list_time=""; else {$d=strtotime($date); $join_author_list_time=date("m/d/Y",$d);}
    $date=$row['leave_author_list_time']; if ($date==$zerodate) $leave_author_list_time=""; else {$d=strtotime($date); $leave_author_list_time=date("m/d/Y",$d);}
  }
  else
  {
    $initials="";
    $chinese_name="";
    $name="";
    $family_name="";
    $institution_id=0;
    $abbreviation_name="";
    $foot_note="";
    $email="";
    $office="";
    $telephone="";
    $mobile_telephone="";
    $position="";
    $bes3_service="";
    $bes3_interest="";
    $password="";
    
    $chinese_speaking=0;  //no
    $is_author="no";
    
    $join_bes3_time="";
    $leave_bes3_time="";
    $join_author_list_time="";
    $leave_author_list_time="";
    
}

  //if (!(($bOwner==true) || ($utype==MANAGER)))
 // if(false)
 // {
 //   $err_no=ERR_USER;
 //   $result="Access not granted";
 // }
 // else
  { 
    if (!(($bOwner==true) || ($utype==MANAGER)))
	{
          $ro3 = "readonly";
        }
      
    $val="<table class='ui-widget-content ui-corner-all'>";
    $val.="<tr><td colspan=3 class='smallb tips' align=right>Marked ($dot) input fields are necessary!</td></tr>";

      $val.="<tr><td>$dot</td><td>Full name</td><td id=attribtes uuid=$uuid shift_role=$shift_role cuuid=$cuuid utype=$utype><input id=profile-name type=text value='$name' placeholder='full name' size=$size $ro3></td></tr>";
    //$val.="<tr><td>$dot</td><td>Family name</td><td><input id=profile-family_name type=text value='$family_name' placeholder='last name' size=$size></td></tr>";
    //$val.="<tr><td>$dot</td><td>Initials</td><td><input id=profile-initials type=text value='$initials' placeholder='initials' size=$size></td></tr>";
    $val.="<tr><td> </td><td>Chinese name</td><td><input id=profile-chinese_name type=text value='".$chinese_name."' placeholder='chinese name if any' size=$size $ro3></td></tr>";
      $val.="<tr><td>$dot</td><td>Institution</td><td id='pinstsel' institution_id=$institution_id><input id='profile-institution' name='profile-institution' type=text value='$abbreviation_name' placeholder='institution brief name' size=$size  $ro3></td></tr>";
    $val.="<tr><td>$dot</td><td>E-mail</td><td><input id=profile-email type=text value='$email' placeholder='e-mail address' size=$size  $ro3></td></tr>";
    //$val.="<tr><td> </td><td>Office</td><td><input id=profile-office type=text value='$office' placeholder='office address' size=$size></td></tr>";
    $val.="<tr><td> </td><td>Phone</td><td><input id=profile-telephone type=text value='$telephone' placeholder='phone number' size=$size  $ro3></td></tr>";
    $val.="<tr><td> </td><td>Cell phone</td><td><input id=profile-mobile_telephone type=text value='$mobile_telephone' placeholder='cell phone number' size=$size  $ro3></td></tr>";
    $val.="<tr><td> </td><td>Position</td><td><input id=profile-position type=text value='$position' placeholder='position' size=$size  $ro3></td></tr>";
    //$val.="<tr><td> </td><td>Service</td><td><input id=profile-bes3_service type=text value='$bes3_service' placeholder='BES-III service' size=$size></td></tr>";    
    //$val.="<tr><td> </td><td>Interest</td><td><input id=profile-bes3_interest type=text value='$bes3_interest' placeholder='BES-III interest' size=$size></td></tr>";
    //$val.="<tr><td> </td><td>Footnote</td><td><input id=profile-foot_note type=text value='$foot_note' placeholder='some notes' size=$size></td></tr>";

    $cl=($bManager) ? "class='adate'" : "readonly";
    $val.="<tr><td> </td><td>Joined</td><td><input id=profile-join_bes3_time type=text $cl value='$join_bes3_time' placeholder='date when the user joined BES-III' size=$size></td></tr>";
    $val.="<tr><td> </td><td>Left</td><td><input id=profile-leave_bes3_time type=text $cl value='$leave_bes3_time' placeholder='date when the user left BES-III' size=$size></td></tr>";
    $val.="<tr><td> </td><td>Author since</td><td><input id=profile-join_author_list_time type=text $cl value='$join_author_list_time' placeholder='date when the user joined author list' size=$size></td></tr>";
    $val.="<tr><td> </td><td>Author up to</td><td><input id=profile-leave_author_list_time type=text $cl value='$leave_author_list_time' placeholder='date when the user left author list' size=$size></td></tr>";

    $ro=($bManager) ? "" : "readonly";
    $ro1="readonly";  //default: shifter, instrep - assigned via 'Institution' editor, manager - flag in db by admin.
/*
    if ($bManager)
    {
      $r0=($shift_role==SHIFTER) ? "checked='checked'" : "";
      $r1=($shift_role==INSTREP) ? "checked='checked'" : "";
      $r2=($shift_role==MANAGER) ? "checked='checked'" : "";
      $val.="<tr><td>$dot</td><td>Shift role</td><td><div id='profile-shift_role' class='ui-corner-all radiosel'><input type='radio' id='role0' name='roleradio' $r0 value=".SHIFTER."><label for='role0' title='BES-III shifting role'>shifter</label><input type='radio' id='role1' name='roleradio' $r1 value=".INSTREP."><label for='role1' title='BES-III shifting role'>instrep</label><input type='radio' id='role2' name='roleradio' $r2 value=".MANAGER."><label for='role2' title='BES-III shifting role'>manager</label></div></td></tr>";
    }
    else      
*/    
//    {
      $a_shift_role = array("shifter","instrep","manager");
      $val.="<tr><td> </td><td>Shift Role</td><td><input id=profile-shift_role type=text value='".$a_shift_role[$shift_role]."' placeholder='BES-III shifting role' size=$size $ro1></td></tr>";
//    }

    if($ro3=="readonly")
    {
      $r0=($chinese_speaking==1) ? "yes" : "no";
      $val.="<tr><td>$dot</td><td>Speak Chinese</td><td><input type=text value='".$r0."' placeholder=$r0 size=$size $ro3></td></tr>";
    }
    else
    {
    $r0=($chinese_speaking==1) ? "checked='checked'" : "";
    $r1=($chinese_speaking==0) ? "checked='checked'" : "";
    $val.="<tr><td>$dot</td><td>Speak Chinese</td><td><div id='profile-chinese_speaking' class='ui-corner-all radiosel'><input type='radio' id='lang0' name='langradio' $r0 value=1><label for='lang0' title='Speak Chinese :)'>yes</label><input type='radio' id='lang1' name='langradio' $r1 value=0><label for='lang1' title='Don't speak chinese :('>no</label></div></td></tr>";
    }

    if ($bManager)
    {
      $r0=($is_author=="yes") ? "checked='checked'" : "";
      $r1=($is_author=="no") ? "checked='checked'" : "";
      $val.="<tr><td>$dot</td><td>Author</td><td><div id='profile-is_author' class='ui-corner-all radiosel'><input type='radio' id='author0' name='authorradio' $r0 value='yes'><label for='author0' title='author :)'>yes</label><input type='radio' id='author1' name='authorradio' $r1 value='no'><label for='author1' title='not an author :('>no</label></div></td></tr>";
    }
    else      
    {
      $val.="<tr><td>$dot</td><td>Author</td><td><input id=profile-is_author type=text value='".$is_author."' placeholder='is author' size=$size $ro></td></tr>";
    }
    
//    $t1="leave blank to autogenerate & send out by e-mail";
//    $t2="... or enter a password and confirm it";
//    $val.="<tr><td> </td><td>Password</td><td><input id=profile-password type=password value='$password' placeholder='$t1' title='$t1' size=$size></td></tr>";
//    $val.="<tr><td> </td><td>Confirm</td><td><input id=profile-password2 type=password value='$password' placeholder='$t2' title='$t2' size=$size></td></tr>";

if (($bOwner==true) || ($utype==MANAGER))
{
    $val.="<tr><td> </td><td><td align=right><button id=profile_save title='save profile'>save</button>&nbsp;<button id=cancel>cancel</button></td></tr>";
}
else{
$val.="<tr><td> </td><td><td align=right><button id=cancel>cancel</button></td></tr>";
}
    $val.="</table>";    
  }
}

else if ($oper=="institution")
{
  //edit create
  $instid=$_POST['instid'];
  //editor/creator
  $utype=$_POST['utype'];
  $cuuid=$_POST['cuuid'];
  $cinstid=$_POST['cinstid'];
  
  $bOwner = (($instid==$cinstid) && ($utype==INSTREP)) ? true: false;
  $bNew = ($instid==0) ? true: false;
  $bManager=($utype==MANAGER)? true:false;

  if (!(($utype==MANAGER) || ($bOwner==true)))
  {
    $err_no=ERR_USER;
    $result="Access not granted";
  }
  else
  {
    $cl=($bManager) ? "class='adater'" : "readonly";
    $ro=($bManager) ? "" : "readonly";
    if ($bNew==false)
    {
      $query="SELECT * FROM Institution WHERE institution_id=$instid LIMIT 1";
      $res=mysqli_query($link, $query);
      $rows=mysqli_num_rows($res);
      if (!$rows) 
      {
        $err_no=ERR_MYSQL_R; $result="Server error: no institution_id=".$instid;
        mysqli_close($link);
        $ret = array('err_no' => $err_no, 'result' => $result, 'val' => $val);
        echo json_encode($ret);
        return;
      }
      $row = mysqli_fetch_array($res, MYSQLI_ASSOC); 
      $full_name=$row['full_name'];
      $abbreviation_name=$row['abbreviation_name'];
      $address1=$row['address1'];
      $continent=$row['continent'];
      $contact_person_id=$row['contact_person_id'];      
      $location=$row['location'];
      
      $date=$row['join_bes3_time']; if ($date==$zerodate) $join_bes3_time=""; else {$d=strtotime($date); $join_bes3_time=date("m/d/Y",$d);}
      $date=$row['leave_bes3_time']; if ($date==$zerodate) $leave_bes3_time=""; else {$d=strtotime($date); $leave_bes3_time=date("m/d/Y",$d);
   }
      
      $description=$row['description'];
      if ($contact_person_id > 0)
      {
        $query="SELECT name FROM Author WHERE author_id='$contact_person_id' LIMIT 1";
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
        $author = $row['name'];
      } else $author="";
    }
    else
    {
      $creator_id=$cuuid;
      //$create_time=NOW();
      $full_name="";
      $abbreviation_name="";
      $address1="";
      $continent="";
      $contact_person_id=0;
      $author="";
      $join_bes3_time="";
      $leave_bes3_time="";
      $description="debug";
      $location=INSTLOC_UNDEF; //undefined
    }    
    
    $val="<table class='ui-widget-content ui-corner-all'>";
    $val.="<tr><td colspan=3 class='smallb tips' align=right>Marked ($dot) input fields are necessary!</td></tr>";
    $val.="<tr><td>$dot</td><td>Full Name</td><td><input id=institution-full_name institution_id=$instid type=text value='$full_name' placeholder='full name' size=$size></td></tr>";
    $val.="<tr><td>$dot</td><td>Brief Name</td><td><input id=institution-abbreviation_name type=text value='$abbreviation_name' placeholder='name abbreviation' size=$size></td></tr>";    
    $val.="<tr><td>$dot</td><td>Address 1</td><td><input id=institution-address1 type=text value='$address1' placeholder='location addess' size=$size></td></tr>";
 //   $val.="<tr><td>$dot</td><td>Continent</td><td><input id=institution-continent type=text value='$continent' placeholder='location continent' size=$size></td></tr>";
    $val.="<tr><td> </td><td>Contact Person</td><td><input id=institution-contact_person author_id=$contact_person_id type=text value='$author' $ro placeholder='contact person name' size=$size></td></tr>";
 //   $val.="<tr><td>$dot</td><td>Joined</td><td><input id=institution-join_bes3_time $cl type=text value='$join_bes3_time' placeholder='date when the institution joined BES-III' size=$size></td></tr>";
 //   $val.="<tr><td> </td><td>Left</td><td><input id=institution-leave_bes3_time $cl type=text value='$leave_bes3_time' placeholder='date when the institution left BES-III' size=$size></td></tr>";
 //   $val.="<tr><td> </td><td>Description</td><td><input id=institution-description type=text value='$description' placeholder='some notes' size=$size></td></tr>";

    $r0=($location==INSTLOC_BEIJING) ? "checked='checked'" : "";
    $r1=($location==INSTLOC_NONBEIJING) ? "checked='checked'" : "";
    $r2=($location==INSTLOC_OUTSIDE) ? "checked='checked'" : "";
    $r3=($location==INSTLOC_UNDEF) ? "checked='checked'" : "";
    $val.="<tr><td>$dot</td><td>Location</td><td><div id='institution-location' class='ui-corner-all radiosel'><input type='radio' id='location0' name='locationradio' $r0 value=".INSTLOC_BEIJING."><label for='location0' title='Institution location'>Beijing</label><input type='radio' id='location1' name='locationradio' $r1 value=".INSTLOC_NONBEIJING."><label for='location1' title='Institution location'>non-Beijing</label><input type='radio' id='location2' name='locationradio' $r2 value=".INSTLOC_OUTSIDE."><label for='location2' title='Institution location'>Outside</label><input type='radio' id='location3' name='locationradio' $r3 value=".INSTLOC_UNDEF."><label for='location3' title='Institution location'>Undefined</label></div></td></tr>";

    $val.="<tr><td> </td><td><td align=right>";
    if (($utype==MANAGER) || ($bOwner==true))
      $val.="<button id=institution_save title='save institution data'>save</button>&nbsp;";
    $val.="<button id=cancel>cancel</button></td></tr>";
    $val.="<table>";    
  }
}

else if ($oper=="round")
{
  //edit create
  $roundid=$_POST['roundid'];
  //editor/creator
  $utype=$_POST['utype'];
  $cuuid=$_POST['cuuid'];
  
  $bNew = ($roundid==0) ? true: false;
  $shiftround_status=($bNew==true) ? 0: 1;
  
  if ($utype!=MANAGER)
  {
    $err_no=ERR_USER;
    $result="Access not granted";
  }
  else
  {
    $cl="class='adater'";  $ro="";
    if ($bNew==false)
    {
      $query="SELECT * FROM ShiftRound WHERE shiftround_id=$roundid LIMIT 1";
      $res=mysqli_query($link, $query);
      $rows=mysqli_num_rows($res);
      if (!$rows) 
      {
        $err_no=ERR_MYSQL_R; $result="Server error: no shiftround_id=".$roundid;
        mysqli_close($link);
        $ret = array('err_no' => $err_no, 'result' => $result, 'val' => $val);
        echo json_encode($ret);
        return;
      }
      $row = mysqli_fetch_array($res, MYSQLI_ASSOC); 
      $shiftround_name=$row['shiftround_name'];
      $shiftround_status=$row['shiftround_status'];
      $date=$row['start_date']; $d=strtotime($date); 
      $start_date=date("m/d/Y",$d);
      $date=$row['end_date']; $d=strtotime($date); 
      $end_date=date("m/d/Y",$d);
      if ($shiftround_status > ROUND_NEW) {$cl="readonly"; $ro="readonly";}//editing is enbled for new rounds only           
    }
    else {$shiftround_name=""; $start_date=""; $end_date="";}
    
    if ($bNew==true || $row['shiftround_status']==ROUND_NEW) $t0 = "Marked ($dot) input fields are necessary!";
    else  {$t0 = "This shift period is not editable!"; $dot = "&nbsp;";}
    
    $val="<table class='ui-widget-content ui-corner-all'>";
    $val.="<tr><td colspan=3 class='smallb tips' align=right>$t0</td></tr>";

    $val.="<tr><td>$dot</td><td>Name</td><td><input id=round-shiftround_name shiftround_id=$roundid shiftround_status=$shiftround_status type=text $ro value='$shiftround_name' placeholder='shift round name' size=$size></td></tr>";
    $val.="<tr><td>$dot</td><td>Start</td><td><input id=round-start_date type=text $cl value='$start_date' placeholder='round period beginning date' size=$size></td></tr>";
    $val.="<tr><td>$dot</td><td>End</td><td><input id=round-end_date type=text $cl value='$end_date' placeholder='round period finish date' size=$size></td></tr>";

    $val.="<tr><td> </td><td><td align=right>";
    if ($row['shiftround_status']==ROUND_OPEN)
    {
      $val.="<button id=round_close title='close round for users and stop sending notifications'>close</button>&nbsp;";
    }
    if ($bNew==true || $row['shiftround_status']==ROUND_NEW)
    {
      if ($row['shiftround_status']==ROUND_NEW)
      {
        $val.="<button id=round_open title='open round for users and start sending notifications'>open</button>&nbsp;";
      }
      $val.="<button id=round_save title='save round data'>save</button>&nbsp;";
    }
    $val.="<button id=cancel>cancel</button></td></tr>";
    $val.="<table>";      
  }
}

else if ($oper=="holidays")
{
  //edit create
  $from=$_POST['from'];
  $upto=$_POST['upto'];
  $shiftround_status=$_POST['rstatus'];
  //editor/creator
  $utype=$_POST['utype'];
  
  if ($utype!=MANAGER)
  {
    $err_no=ERR_USER;
    $result="Access not granted";
  }
  else
  {
    $cl=($shiftround_status > 0) ? "readonly" : "class='adater'";
    $ro=($shiftround_status > 0) ? "readonly" : "";
    $aholi=array();
    $query="SELECT * FROM ShiftHolidays WHERE (ShiftHolidays.date >= '$from') AND (ShiftHolidays.date < '$upto') ORDER BY ShiftHolidays.date ASC";
    $res=mysqli_query($link, $query);
    $rows=mysqli_num_rows($res);
    if ($rows > 0) 
    {
      while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) $aholi[]=$row;
    }
    $alen=count($aholi);

    if ($shiftround_status==0)
    {
      $t0 = "Clear a holiday name to remove it from the list. Sequence doesn't matter.";
      $tlen=HOLIDAYS_PER_ROUND;
    }
    else  
    {
      $t0 = "This shift period is being used and the holidays table is not editable!";
      $tlen=$alen;
    }
    
    $val="<table class='ui-widget-content ui-corner-all' id=holiday_list from='$from' upto='$upto'>";
    $val.="<tr><td colspan=2 class='smallb tips' align=right>$t0</td></tr>";
    $val.="<tr class='ui-widget-header'><td>Date</td><td>Holiday Name</td></tr>";
    for ($n=0; $n < $tlen; $n++)
    {
      if ($n < $alen) 
      {
        $date=$aholi[$n]['date']; $d=strtotime($date); $sdate=date("m/d/Y",$d);
        $sholiday=$aholi[$n]['holiday'];
      }
      else {$sdate=""; $sholiday="";}
      $val.="<tr id=holiday-$n><td><input type=text $cl value='$sdate'></td><td><input class=holiname type=text $ro value='$sholiday' placeholder='leave empty to exclude from the list' size=$size></td></tr>";
    }
    $val.="<tr><td colspan=2 align=right>";
    if ($shiftround_status==0)
      $val.="<button id=holiday_save title='save holidays list'>save</button>&nbsp;";
    $val.="<button id=cancel>cancel</button></td></tr>";
    $val.="<table>";    
  }
}
else if ($oper=="confreject")
{
  $author_id=$_POST['author_id'];
  $stype=$_POST['stype'];
  $dtime=$_POST['dtime'];
  $msfrom=$_POST['msfrom'];
  $days=$_POST['days'];
  $startsindays=$_POST['startsindays'];
  $user=$_POST['user'];
  $bOwner=($author_id==$context['uuid'])? true : false;
  $from=$_POST['from'];
  $upto=$_POST['upto'];
  $dfrom=strtotime($from);
  $dupto=strtotime($upto);
  
  $attributes="author_id1=$author_id date1='$from' dtime1=$dtime type1=$stype days1=$days user1='$user'";
  //$stypes=array("","ordinary","chief","coordinator");
  //$dtnames=array('all','night','day','evening');
  $val="<br><br><table class='ui-widget-content ui-corner-all smally'>";
  $val.="<tr><td colspan=2 align=center class='ui-widget-header' id=shift1 $attributes>Shifts block releasing</td></tr>";
  $val.="<tr><td>shifter:</td><td>you</td></tr>";
  $val.="<tr><td>type:</td><td>".$stypes[$stype]."</td></tr>";
  $val.="<tr><td>time of day:</td><td>".$dtnames[$dtime]."</td></tr>";
  $val.="<tr><td>start date:</td><td>$from</td></tr>";
  $val.="<tr><td>finish date:</td><td>$upto</td></tr>";
  $val.="<tr><td>block (days):</td><td>$days</td></tr>";
  $val.="<tr><td>starts in (days):</td><td>$startsindays</td></tr>";
  $val.="<tr>";
  $val.="<td align=left><button id=askfor_reject class='fbutt' title='Ask a manager to confirm your reject'>ask for</button></td>";
  $val.="<td align=right><button id=cancel_reject class='fbutt' title='Forget'>cancel</button></td>";
  $val.="</tr>";  
  $val.="</table>";    
}

else if ($oper=="confexchange")
{
  $author_id=$_POST['author_id'];
  $stype=$_POST['stype'];
  $dtime=$_POST['dtime'];
  $msfrom=$_POST['msfrom'];
  $days=$_POST['days'];
  $startsindays=$_POST['startsindays'];
  $user=$_POST['user'];
  $bOwner=($author_id==$context['uuid'])? true : false;
  $from=$_POST['from'];
  $upto=$_POST['upto'];
  $dfrom=strtotime($from);
  $dupto=strtotime($upto);
  
  if ($bOwner==true)
  {
    $attributes="author_id1=$author_id date1='$from' dtime1=$dtime type1=$stype days1=$days user1='$user'";
    $id="shift1";
    $shift="1";
    $uname="you";
  }
  else  
  {
    $attributes="author_id2=$author_id date2='$from' dtime2=$dtime type2=$stype days2=$days user2='$user'";
    $id="shift2";
    $shift="2";
    $uname=$user;
  }
  
  //if ($bOwner==true) $val="<br>"; else $val="";
  $val="<table class='ui-widget-content ui-corner-all smally'>";
  $val.="<tr><td colspan=2 align=center class='ui-widget-header' id=$id $attributes>Shifts block exchange ($shift)</td></tr>";
  $val.="<tr><td>shifter:</td><td>".$uname."</td></tr>";
  $val.="<tr><td>type:</td><td>".$stypes[$stype]."</td></tr>";
  $val.="<tr><td>time of day:</td><td>".$dtnames[$dtime]."</td></tr>";
  $val.="<tr><td>start date:</td><td>$from</td></tr>";
  $val.="<tr><td>finish date:</td><td>$upto</td></tr>";
  $val.="<tr><td>block (days):</td><td>$days</td></tr>";
  $val.="<tr><td>starts in (days):</td><td>$startsindays</td></tr>";
  if ($bOwner==false)  //when another person's shifts block is selected
  {
    $val.="<tr>";
    $val.="<td align=left><button id=askfor_exchange class='fbutt' title='Ask the shifter for exchange'>ask for</button></td>";
    $val.="<td align=right><button id=cancel_exchange class='fbutt' title='Forget'>cancel</button></td>";
    $val.="</tr>";  
  }
  $val.="</table>"; 
}

mysqli_close($link);
if (count($ret)==0) $ret = array('err_no' => $err_no, 'result' => $result, 'val' => $val);
echo json_encode($ret);

?>
