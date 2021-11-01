<?php
//to be included after config.inc.php
//php/common.php

$common_inc=true;

//<COOKIES>
$today=time();
$exp_day = $today+86400;
$exp_year = $today+31536000;
function my_setcookie($name, $value, $expires){return setcookie($GLOBALS['cookie_prefix'].$name,$value,$expires,"/");}
function my_delcookie($name){return setcookie ($GLOBALS['cookie_prefix'].$name, "undefined", $now - 3600,"/");}
//</COOKIES>

//<CONTEXT>
//user
$context['utype']=my_getcookie("utype"); if (!isset($context['utype'])) $context['utype']=666;
$context['user']=my_getcookie("user"); if (!isset($context['user'])) $context['user']="guest";
$context['userc']=my_getcookie("userc"); if (!isset($context['user'])) $context['userc']="";
$context['uuid']=my_getcookie("uuid"); if (!isset($context['uuid'])) $context['uuid']=0;
$context['instid']=my_getcookie("instid"); if (!isset($context['instid'])) $context['instid']=0;
$context['loggedin']=my_getcookie("loggedin"); if (!isset($context['loggedin'])) $context['loggedin']=0;

$context['scope']=my_getcookie("scope"); if (!isset($context['scope'])) $context['scope']="m";
$context['msmonth']=my_getcookie("msmonth"); if (!isset($context['msmonth'])) $context['msmonth']=0;
$context['shown_1']=my_getcookie("shown_1"); if (!isset($context['shown_1'])) $context['shown_1']=0;
//</CONTEXT>

//<FUNCTIONS>
function unicode_urldecode($url)
{
  preg_match_all('/%u([[:alnum:]]{4})/', $url, $a);
  foreach ($a[1] as $uniord)
  {
    $dec = hexdec($uniord);
    $utf = '';
    if ($dec < 128){$utf = chr($dec);}
    else if ($dec < 2048) {$utf = chr(192 + (($dec - ($dec % 64)) / 64));  $utf .= chr(128 + ($dec % 64));}
    else {$utf = chr(224 + (($dec - ($dec % 4096)) / 4096)); $utf .= chr(128 + ((($dec % 4096) - ($dec % 64)) / 64)); $utf .= chr(128 + ($dec % 64));} 
    $url = str_replace('%u'.$uniord, $utf, $url);
  }     
  return urldecode($url);
} 
//</FUNCTIONS>

//<MISC>
$dtnames=array('all','night','day','evening');
$stypes=array("","ordinary","chief","coordinator");
$shift_role=array("shifter","instrep","manager");
$alocations=array("Beijing","non-Beijing","outside","all");
$aoncall=array(array("institution_id" => 37, "subsystems" => 2),array("institution_id" => 14, "subsystems" => 14));//USTC, IHEP
//</MISC>

?>
