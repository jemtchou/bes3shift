<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<meta name="keywords" content="BES-III Shift Schedule"/>
<meta name="robots" content="all"/>
<meta http-equiv="expires" content="0"/>
<meta http-equiv="pragma" content="no-cache"/>
<meta http-equiv="Cache-Control" content="no-cache">
<meta http-equiv="cache-control" content="no-cache"/>
<link rel="icon" href="./favicon.ico" type="image/x-icon">
<link rel="shortcut icon" href="./favicon.ico" type="image/x-icon">
<link href="css/common.css" rel="stylesheet" type="text/css" />
<title>BES-III Shift Schedule</title>

<?php
require "php/config.inc.php";
vars2js();
?>

<script src="js/jquery/jquery.js" type="text/javascript" charset="utf-8"></script>
<script src="js/jquery/jquery-ui.js" type="text/javascript"></script>
<script src="js/jquery/cookie.js" type="text/javascript"></script>

<?php	echo "<link href='css/jquery/$uitheme/jquery-ui.css' rel='stylesheet' type='text/css'>"; ?>

<script src='js/common.js' type="text/javascript"></script>
<script src='js/jqmain.js' type="text/javascript"></script>
  
</head>

<body class="ui-widget">

<div id='dialog-message' class= hidden_dlg  title='dummy'><p><span class='ui-icon ui-icon-circle-check' style='float:left; margin:0 7px 50px 0;'></span>hello</p></div>
<div id="exit" class='hidden_dlg' title="Logout"><p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 10px 0;"></span>Are you really going to exit?</p></div>

<table id="shifted" class='hidden_dlg' title="Shift Editor" width=100%>  
  <tr><td>shifter:</td><td id=ed-name>none</td></tr>
  <tr><td>type:</td><td id=ed-stype_name>ordinary</td></tr>
  <tr><td>time of day:</td><td id=ed-dtime_name>night</td></tr>
  <tr><td>start date:</td><td id=ed-from_name>date</td></tr>
  <tr><td>end date:</td><td id=ed-upto_name>date</td></tr>
  <tr><td>block (days):</td><td id=ed-days_name>0</td></tr>
<!--   <tr id=ed-score><td>score (points):</td><td id=ed-score_name align=center>0</td></tr> -->
  <tr id=ed-startsin><td>starts in (days):</td><td id=ed-startsin_name>0</td></tr>
  <tr id=ed-remote><td><input type="checkbox" id="checkbox"></td><td>remote</td></tr>
</table>

<?php
require "php/shibauth.php";
require "php/common.php";
require "php/main.php";
_RenderMain();
?>

<script language="JavaScript" type="text/javascript">
if (my_cookieson()==false) alert('Cookies must be enabled in your browser');
</script>

</body>
</html>
