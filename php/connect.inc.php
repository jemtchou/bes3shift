<?php
require "errors.inc.php";
$err_no=ERR_NONE;
$result="ok";
$link=@mysqli_connect ("localhost", "alexey", "zhemchugov");
if (!$link) {$err_no=ERR_MYSQL_CON; $result=mysqli_error();}
else {if (!mysqli_select_db($link,"bes3member")) {$result=mysqli_error($link)." (err=".mysqli_errno($link).")"; mysqli_close($link); $err_no=ERR_MYSQL_SEL;}}
?>
