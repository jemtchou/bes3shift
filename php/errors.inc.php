<?php
//errors.inc.php

define("ERR_NONE",0);  //no error
define("ERR_SERVER",1);  //some server or undefined error (e.g. an attampt to acces some pages using improper links
define("ERR_DEBUG",2);

//MySQL
define("ERR_MYSQL",100);             //basic
define("ERR_MYSQL_CON",101);  //can't connect to db
define("ERR_MYSQL_SEL",102);  //can't select db
define("ERR_MYSQL_UTF",103);  //can't set utf8
define("ERR_MYSQL_Q",104);  //query failure - select result
define("ERR_MYSQL_R",105);  //query failure - no rows
define("ERR_MYSQL_W",106);  //query failure - no update

//Porter
define("ERR_PORTER",200);                   //basic
define("ERR_PORTER_REGISTER",201);    //registraition failed
define("ERR_PORTER_REGISTER2",202);    //already registered (the same e-mail entered)
define("ERR_PORTER_LOGIN",203);    //login failed
define("ERR_PORTER_REMIND",204);    //login reminder
define("ERR_PORTER_INFORM",205);    //message for admin (not actual record spotted by a user)

//Clients
define("ERR_CLI",300);        //basic
define("ERR_CLI_ADD",301);    //new client addition failed
define("ERR_CLI_ST",302);    //statistic correction operation failed - cats, subcats
define("ERR_CLI_ST2",303);    //statistic correction operation failed - clientscats
define("ERR_CLI_DBL",304);    //doubling records - used as a numer in jqforms - do not change!!!

//User input
define("ERR_USER",400);        //basic
define("ERR_USER_DONE",401);    //this was done before by the user (for single action - like rating, comment)
define("ERR_USER_BADWORDS",402);    //user input contains bad words
define("ERR_USER_DOUBLE",403);  //nearly simultaneous attempt to book shifts block
define("ERR_NOT_OPEN_YET",404); //institution location (outside,non-bj, bj) is not open for booking yet

//Mail errors
define("ERR_MAIL",666);

?>
