<?php
//config.inc.php: global parameters and defaults 

/**********************************************************/
/* you may change only these 3 lines of code portion here */
/**********************************************************/
//Site URL:
$sweet_home = "http://docbes3.ihep.ac.cn/~alexey/bes3shift/";
//Managers' email, must have real person reading it daily:
$duty_manager="chenml@ihep.ac.cn";
//System heartbeat email - make it = "" after the debugging:
$cc_email="bes3shift@ihep.ac.cn"; 
/*************************************************************/
/* The rest configuration is in the following scripts by now:*/
/* smtp_mail.inc.php, smtp_mail.php, connect.inc.php,        */
/* rundaily.php - '$prdebug', common.php - '$oncall'         */
/* Change them very carefully & only if you know what to do  */
/*************************************************************/

$proj="bes3shift";	
$proj_started="20-February-2012";
$proj_modified="20-September-2012";
$proj_completed="xx-xxxx-2012";
$proj_version="1.1";
$jqcore="1.7.2";
$jqui="1.8.18";
$sys_name="BES-III Shift System";

date_default_timezone_set("Asia/Shanghai");
error_reporting(E_ERROR | E_WARNING | E_PARSE);

//CONSTANTS
define("SHIFTER",0);  //Author.shift_role => utype
define("INSTREP",1);
define("MANAGER",2);

define("ORDINARY",1);  //Shifts.type => stype
define("CHIEF",2);
define("RUNCOORDINATOR",3);

define("DTIME_ALL",0);  //Shifts.time of day => dtime
define("DTIME_NIGHT",1);
define("DTIME_DAY",2);
define("DTIME_EVENING",3);

define("POINTS_NIGHT",1.5); //Points per shift 0 - 8
define("POINTS_DAY",1.0);  //8 - 16
define("POINTS_EVENING",1.0); //16 - 24
define("POINTS_HOLIDAY",0.5);
define("POINTS_COORDINATOR",1.0); //Points per day 0 - 24
define("POINTS_ONCALLEXPERT",1.0); //Points per week per subsystem

define("SHIFTS_PER_DAY",7);

define("HOLIDAYS_PER_ROUND",11);

define("ROUND_NEW",0);  //Shift round status
define("ROUND_OPEN",1); //actual
define("ROUND_CLOSED",2);//archive

define("INSTLOC_UNDEF",3);  //Institution location
define("INSTLOC_OUTSIDE",2);
define("INSTLOC_NONBEIJING",1);
define("INSTLOC_BEIJING",0);

define("PR_DAYS_OUTSIDE",1);  //send notifications in xx days after 'open_date'
define("PR_DAYS_NONBEIJING",2);
define("PR_DAYS_BEIJING",14);

//time before (in days)
define("LIMIT_DAYS_BOOK",1);  //? TO DO
define("LIMIT_DAYS_REJECT",30); //<30 - manager confirmation needed
define("LIMIT_DAYS_REMIND_1",7); //remind on shift
define("LIMIT_DAYS_REMIND_2",3); //remind on shift

//CONTEXT
$cookie_prefix="{$proj}_";	//set it in common.js as well !!!
$uitheme="pepper-grinder";

function my_getcookie($name){return $_COOKIE[$GLOBALS['cookie_prefix'].$name];} 

function vars2js()
{
  echo "<script language='JavaScript' type='text/javascript'>";
  echo "var sweet_home='" . $GLOBALS['sweet_home'] . "';";

  echo "var SHIFTER=".SHIFTER.";";
  echo "var INSTREP=".INSTREP.";";
  echo "var MANAGER=".MANAGER.";";
  
  echo "var ORDINARY=".ORDINARY.";";
  echo "var CHIEF=".CHIEF.";";
  echo "var RUNCOORDINATOR=".RUNCOORDINATOR.";";

  echo "var ROUND_NEW=".ROUND_NEW.";";
  echo "var ROUND_OPEN=".ROUND_OPEN.";";
  echo "var ROUND_CLOSED=".ROUND_CLOSED.";";

  echo "var DTIME_ALL=".DTIME_ALL.";";
  echo "var DTIME_NIGHT=".DTIME_NIGHT.";";
  echo "var DTIME_DAY=".DTIME_DAY.";";
  echo "var DTIME_EVENING=".DTIME_EVENING.";";

  echo "var POINTS_NIGHT=".POINTS_NIGHT.";";
  echo "var POINTS_DAY=".POINTS_DAY.";";
  echo "var POINTS_EVENING=".POINTS_EVENING.";";
  echo "var POINTS_HOLIDAY=".POINTS_HOLIDAY.";";
  echo "var POINTS_COORDINATOR=".POINTS_COORDINATOR.";";
  echo "var POINTS_ONCALLEXPERT=".POINTS_ONCALLEXPERT.";";

  echo "var LIMIT_DAYS_BOOK=".LIMIT_DAYS_BOOK.";";
  echo "var LIMIT_DAYS_REJECT=".LIMIT_DAYS_REJECT.";";
  echo "var LIMIT_DAYS_REMIND_1=".LIMIT_DAYS_REMIND_1.";";
  echo "var LIMIT_DAYS_REMIND_2=".LIMIT_DAYS_REMIND_2.";";

  echo "var INSTLOC_UNDEF=".INSTLOC_UNDEF.";";
  echo "var INSTLOC_OUTSIDE=".INSTLOC_OUTSIDE.";";
  echo "var INSTLOC_NONBEIJING=".INSTLOC_NONBEIJING.";";
  echo "var INSTLOC_BEIJING=".INSTLOC_BEIJING.";";

  echo "var SHIFTS_PER_DAY=".SHIFTS_PER_DAY.";";

  echo "</script>";  
}
?>
