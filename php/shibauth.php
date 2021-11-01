<?php
require "connect.inc.php";

$email=$_SERVER['mail'];
$name=$_SERVER['trueName'];
$coopers=$_SERVER['coopers']; 

if(! preg_match('/(^|;)2(-|$)/',$coopers))
{
 echo ("<SCRIPT LANGUAGE='JavaScript'>
    window.alert('Only BES-III members are allowed.')
    window.location.href='http://docbes3.ihep.ac.cn/Shibboleth.sso/Logout';
    </SCRIPT>");
}

mysqli_query($link, "SET NAMES utf8");
$query="SELECT * FROM Author WHERE email='$email' ORDER BY author_id DESC  LIMIT 1";
$res=mysqli_query($link, $query);
$rows=mysqli_num_rows($res);

if ( ! $rows )
{
    $query="SELECT * FROM Author WHERE CONVERT(CONVERT(name USING binary) USING utf8)='$name' ORDER BY author_id DESC  LIMIT 1";
    $res=mysqli_query($link, $query);
    $rows=mysqli_num_rows($res);
}

if($rows)
{
  $row = mysqli_fetch_array($res, MYSQLI_ASSOC);

  setcookie("bes3shift_user",$row['name']);
#  setcookie("bes3shift_userc",urlencode(utf8_encode($row['chinese_name_unicode'])));
  setcookie("bes3shift_userc",$row['chinese_name_unicode']);
  setcookie("bes3shift_uuid",$row['author_id']);
  setcookie("bes3shift_instid",$row['institution_id']);
  setcookie("bes3shift_loggedin",1);
  setcookie("bes3shift_utype",$row['shift_role']);
}
else
{
  error_log('Login problem |'.$_SERVER['trueName'].'| |'.$_SERVER['mail'].'|');
  setcookie("bes3shift_loggedin",2);
}
?>
