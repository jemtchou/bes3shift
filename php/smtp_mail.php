<?php

require "config.inc.php";
require "errors.inc.php";

$val=0; 
$err_no=ERR_NONE; 
$result="ok"; 
$ret=array();

require_once "Mail.php";
function informUser($to, $subject, $body, $cc="")
{
  global $sys_name, $sweet_home, $err_no, $result;
  $manager_email="bes3shift@ihep.ac.cn";
  $err_no=0; $result="ok";
  $from = $sys_name." <".$manager_email.">";
  $recipients = $to;  
  $headers = array('From'=> $from,'To' => $to,'Subject' => $subject,'Cc' => "bes3shift@ihep.ac.cn",'Date' => date("r"),'Content-Type' => "text/html; charset=\"UTF-8\"",'Content-Transfer-Encoding' => "8bit");
  if (strlen($cc) > 0) $recipients .= ",".$cc;
  else unset($headers['Cc']);
  $body .="<br><br><a href='$sweet_home'>$sys_name</a>";

  $host = "mail.ihep.ac.cn";
  $port = "25";
  $username = "bes3shift";
  $password = "bes3shift_2015";  
  $smtp = Mail::factory('smtp', array ('host' => $host,'port' => $port,'auth' => true,'username' => $username,'password' => $password));
  $mail = $smtp->send($recipients, $headers, $body);  
  if (PEAR::isError($mail)) {$err_no=666; $result=$mail->getMessage();};
}

$to=$_POST['to'];
$subject=$_POST['subject'];
$body=$_POST['body'];
$cc=$_POST['cc']; 
if (!isset($cc)) $cc="";
if (!isset($to) || !isset($subject) ||!isset($body)) {$err_no=ERR_MAIL; $result="Mail recipient, subject or body is missing";}
else informUser($to, $subject, $body,$cc);

if (count($ret)==0) $ret = array('err_no' => $err_no, 'result' => $result, 'val' => $val);
echo json_encode($ret);
?>
