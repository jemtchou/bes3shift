<?php
//porter.php
header("Content-type: text/html; charset=utf-8");

require "config.inc.php";
$key=$proj;
require "common.php";

function genPassword($length=7, $strength=7) 
{
	$vowels='aeuy';	$consonants='bdghjmnpqrstvz'; $password="";
	if ($strength & 1) {$consonants .= 'BDGHJLMNPQRSTVWXZ';}
	if ($strength & 2) {$vowels .= 'AEUY';}
	if ($strength & 4) {$consonants .= '23456789';}
	if ($strength & 8) {$consonants .= '@#$%';}	
	$alt = time() % 2;
	for ($i = 0; $i < $length; $i++) {if ($alt == 1) {$password .= $consonants[(rand() % strlen($consonants))]; $alt = 0;} else {$password .= $vowels[(rand() % strlen($vowels))]; $alt = 1;}}
	return $password;
}

$oper=$_POST['oper'];
if (!isset($oper)) {echo "<script language='JavaScript' type='text/javascript'>window.location='$sweet_home';</script>";	 return;}

require "connect.inc.php";
if (!$link || ($err_no > 0)) {$ret = array('err_no' => $err_no, 'result' => $result);  echo json_encode($ret);  return;} //mySQL error
if (!mysqli_query("SET NAMES utf8", $link)) {$result=mysqli_error($link)." (err=".mysqli_errno($link).")"; $err_no=ERR_MYSQL_UTF;}	  

$val=array(); 
$err_no=ERR_NONE; 
$result="ok"; 
$ret=array();
$zerodate="0000-00-00";

if ($oper=="login")
{
  $email=$_POST['email'];
  $password=$_POST['password'];
  if ($password=="forgot")
  {
    $query="SELECT name, email, AES_DECRYPT(password,'$key') AS pass FROM Author WHERE email='$email' order by leave_bes3_time LIMIT 1";
    $res=mysqli_query($link, $query);
    $rows=mysqli_num_rows($res);
    if (!$rows) {$err_no=ERR_MYSQL_R; $result="No registered user with this e-mail found.";}
    else
    {
      $row = mysqli_fetch_array($res, MYSQLI_ASSOC);
     //make up informUser data
      $val['body']="Dear ".$row['name'].",<br>your password is: ".$row['pass']."<br>";
      $val['subject']="BES-III shifts password reminder";
      $val['to']=$row['name']." <".$row['email'].">";                  
      $result="Password is sent out. Please, check your e-mail";
      $err_no=ERR_PORTER_REMIND;    
    }
  }
  else
  {
echo '<script>parent.window.location.reload(true);</script>';
  //  $query="SELECT * FROM Author WHERE email='$email' AND AES_DECRYPT(password,'$key')='$password' LIMIT 1";
  //  $res=mysqli_query($link, $query);
  //  $rows=mysqli_num_rows($res);
  //  if (!$rows) {$err_no=ERR_MYSQL_R; $result="Wrong e-mail or/and password";}
  //  else $val = mysqli_fetch_array($res, MYSQLI_ASSOC);
  }
}//end LOGIN

else if ($oper=="profile_save")
{
  $profile=$_POST['profile'];  

  $bOwner = ($profile['uuid']==$profile['creator_id']) ? true : false;
  $bNew = ($profile['uuid']==0) ? true : false;
  $bNew=false; //AZ 12.10.2012
  $password = $profile['password'];
  $login = $profile['email'];
  if (strlen($password)==0) $password = genPassword();

  if ($profile['join_bes3_time']==0) $profile['join_bes3_time']=$zerodate;
  else $profile['join_bes3_time'] = date("Y-m-d",$profile['join_bes3_time']);
  if ($profile['leave_bes3_time']==0) $profile['leave_bes3_time']=$zerodate;
  else $profile['leave_bes3_time'] = date("Y-m-d",$profile['leave_bes3_time']);
  if ($profile['join_author_list_time']==0) $profile['join_author_list_time']=$zerodate;
  else $profile['join_author_list_time'] = date("Y-m-d",$profile['join_author_list_time']);
  if ($profile['leave_author_list_time']==0) $profile['leave_author_list_time']=$zerodate;
  else $profile['leave_author_list_time'] = date("Y-m-d",$profile['leave_author_list_time']);
  
  if ($bNew==true)
  {    
    $query="INSERT INTO Author (initials,chinese_name_unicode,name,family_name,institution_id,foot_note,email,office,telephone,mobile_telephone,is_author,join_bes3_time,leave_bes3_time,join_author_list_time,leave_author_list_time,position,bes3_service,bes3_interest,creator_id,create_time,shift_role,chinese_speaking,password) VALUES ('{$profile[initials]}','{$profile[chinese_name]}','{$profile[name]}','{$profile[family_name]}',{$profile[institution_id]},'{$profile[foot_note]}','{$profile[email]}','{$profile[office]}','{$profile[telephone]}','{$profile[mobile_telephone]}','{$profile[is_author]}','{$profile[join_bes3_time]}','{$profile[leave_bes3_time]}','{$profile[join_author_list_time]}','{$profile[leave_author_list_time]}','{$profile[position]}','{$profile[bes3_service]}','{$profile[bes3_interest]}',{$profile[creator_id]},NOW(),{$profile[shift_role]},{$profile[chinese_speaking]},AES_ENCRYPT('$password','$key'))";
    $res=mysqli_query($link, $query);
    if ($res==FALSE){$err_no=ERR_MYSQL_Q; $result=mysqli_error($link)." (err=".mysqli_errno($link).")";}
    else $val=mysqli_insert_id($link);
  }
  else  
  {
    if ($context['utype']==MANAGER)
//      $query="UPDATE Author SET initials='{$profile[initials]}',chinese_name_unicode='{$profile[chinese_name]}',name='{$profile[name]}',family_name='{$profile[family_name]}',institution_id={$profile[institution_id]},foot_note='{$profile[foot_note]}',email='{$profile[email]}',office='{$profile[office]}',telephone='{$profile[telephone]}',mobile_telephone='{$profile[mobile_telephone]}',is_author='{$profile[is_author]}',join_bes3_time='{$profile[join_bes3_time]}',leave_bes3_time='{$profile[leave_bes3_time]}',join_author_list_time='{$profile[join_author_list_time]}',leave_author_list_time='{$profile[leave_author_list_time]}',position='{$profile[position]}',bes3_service='{$profile[bes3_service]}',bes3_interest='{$profile[bes3_interest]}',chinese_speaking={$profile[chinese_speaking]},password=AES_ENCRYPT('$password','$key') WHERE author_id={$profile[uuid]} LIMIT 1";
$query="UPDATE Author SET chinese_speaking={$profile[chinese_speaking]},password=AES_ENCRYPT('$password','$key') WHERE author_id={$profile[uuid]} LIMIT 1";
    else 
$query="UPDATE Author SET chinese_speaking={$profile[chinese_speaking]},password=AES_ENCRYPT('$password','$key') WHERE author_id={$profile[uuid]} LIMIT 1"; 
//      $query="UPDATE Author SET initials='{$profile[initials]}',chinese_name_unicode='{$profile[chinese_name]}',name='{$profile[name]}',family_name='{$profile[family_name]}',institution_id={$profile[institution_id]},foot_note='{$profile[foot_note]}',email='{$profile[email]}',office='{$profile[office]}',telephone='{$profile[telephone]}',mobile_telephone='{$profile[mobile_telephone]}',position='{$profile[position]}',bes3_service='{$profile[bes3_service]}',bes3_interest='{$profile[bes3_interest]}',chinese_speaking={$profile[chinese_speaking]},password=AES_ENCRYPT('$password','$key') WHERE author_id={$profile[uuid]} LIMIT 1";
    $res=mysqli_query($link, $query);
    if ($res==FALSE){$err_no=ERR_MYSQL_Q; $result=mysqli_error($link)." (err=".mysqli_errno($link).")";}
    else if (mysqli_affected_rows($link)==0)	{$err_no=ERR_MYSQL_W; $result="Nothing to do";}
  }

  if ($err_no==ERR_NONE)
  { 
    if ($bNew==true)
    {
      $subject="Your BES-III registration";
      $body="Dear ".$profile['name'].",<br> you were successfully registered as BES-III Shifting system user.<br> Please, check up your profile to verify the personal data entered by a manager.\n Be sure to read the rules and instruction prior to using the system.<br><br> Your login: ".$login."<br>Password: ".$password."<br>";
    }
    else
    {
      $subject="Your BES-III profile changed";
      $body="Dear ".$profile['name'].",<br> be aware of your profile recent change by a BES-III Shifting manager.<br> Please, check up the changes using your<br><br> login: ".$login."<br>password: ".$password."<br>";
    }
    $to=$profile['name']." <".$profile['email'].">";        
    $val=array('to'=> $to, 'subject' => $subject, 'body' => $body);
  }
  
}

mysqli_close($link);
if (count($ret)==0) $ret = array('err_no' => $err_no, 'result' => $result, 'val' => $val);
echo json_encode($ret);
?>
