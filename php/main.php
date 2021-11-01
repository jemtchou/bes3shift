<?php
//php/main.php

function  _Showcase()
{
  global $context, $sweet_home;

//SHOWCASE
  echo "<tr>";

//LEFT
  echo "<td valign=top height=100%>";
  echo "<table width=100% height=100% class='ui-widget-content'>";

  //title
  echo "<tr><td nowrap align=center class='ui-state-default ui-corner-tl'>";
  echo "<a href='$sweet_home' title='BES-III Shift Schedule home'>";
  echo "<div class=eeicon><span class='ui-icon ui-icon-home'></span></div>&nbsp;&nbsp;&nbsp;BES-III Shifts";
  echo "</a></td></tr>";

  //date navigator
  echo "<tr><td><div id=datenav class='smallb'></div></td></tr>";

  //scope-switcher
  echo "<tr><td align=center>";
    $r0=($context['scope']=="m") ? "checked='checked'" : "";  //scope=month
    $r1=($context['scope']=="r") ? "checked='checked'" : "";  //scope=round
    echo "<div id='scope' class='ui-corner-all radiosel'>";
      echo "<input type='radio' id='scope0' name='scoperadio' $r0 value='m'><label for='scope0' title='browse the calendar by month'>month</label>";
      echo "<input type='radio' id='scope1' name='scoperadio' $r1 value='r'><label for='scope1' title='browse the calendar by rounds'>round</label>";
    echo "</div>";
  echo "</td></tr>";
  
  //round selector, for manager, instrep - it's a link to edit (existing) or add new
  if ($context['utype']==MANAGER)
  {
    $sel = "<a href='#' id='holidays_ed' title='round holidays edit/create new' style='text-decoration: underline;background: ffff99;'>Holidays.</a>&nbsp;&nbsp;&nbsp;&nbsp;";
    $sel .= "<a href='#' id='round_ed' title='edit/create new' style='text-decoration: underline;background: ffff99;'>Round</a>";
  }
  else $sel = "Round";    
  echo "<tr><td align=center><div id=roundsel class='smallb' shiftround_id=0 start_date=0 end_date=0>$sel:<br><input type='text' name='round' id='round' value='' placeholder='all' class='acinp ui-widget-content ui-corner-all' title='begin typing in a round period name' size=30></div></td></tr>";
  //institution selector, for manager, instrep - it's a link to edit (existing) or add new
  $sel = (($context['utype']==MANAGER)|| ($context['utype']==INSTREP))? "<a href='#' id='inst_ed' title='edit/create new'  style='text-decoration: underline;background: ffff99;'>Institution</a>" : "Institution";
  echo "<tr><td align=center><div id=instsel class='smallb' institution_id=0>$sel:<br><input type='text' name='inst' id='inst' value='' placeholder='all' class='acinp ui-widget-content ui-corner-all' title='begin typing in an institution abbreviation name' size=30></div></td></tr>";
  //author selector, for manager - it's a link to edit (existing) or add new
//  $sel = ($context['utype']==MANAGER) ? "<a href='#' id='author_ed' title='edit/create new' style='text-decoration: underline;background: ffff99;'>Author</a>" : "Author";
   $sel = "<a href='#' id='author_ed' title='edit/create new' style='text-decoration: underline;background: ffff99;'>Author</a>";
  echo "<tr><td align=center><div id=authorsel class='smallb' author_id=0 shift_role=0>$sel:<br><input type='text' name='author' id='author' value='' placeholder='all' class='acinp ui-widget-content ui-corner-all' title='begin typing in an author name' size=30></div></td></tr>";
  //control buttons;
  echo "<tr><td align=center><div id='control' class='ui-corner-all nonl'>";
      echo "<button id='roster' title='display roster for selected institution, author'>Roster</button>";
      echo "<button id='stats' title='display statistics for for selected round, institution, author'>Stats</button>";    
  echo "</div></td></tr>";
  
  //confirmation dependent operations: exchange, (late) reject:
  echo "<tr><td align=center><div id='conform' class='ui-corner-all nonl'></div><td></tr>";
  echo "<tr><td align=center><div id='conform2' class='ui-corner-all nonl'></div><td></tr>";

  //push up
  echo "<tr><td height=100% valign=top></td></tr>";

  echo "</table>";
  echo "</td>"; //end LEFT

//RIGHT
  echo "<td valign=top width=100% height=100%>";
    echo "<table width=100% height=100% class='ui-widget-content'>";

        //top
//        echo "<tr><td height=1% class='ui-widget-header ui-corner-tr'>";
        echo "<tr><td align=center height=1% class='ui-state-highlight ui-corner-tr'>";
        echo "<div id=topcase>Loading data ...</div>";
        echo "</td></tr>";

        //calendar, statistics, lists...
        echo "<tr><td height=99% valign=middle align=center>";
        echo "<div id=showcase>Please wait <img src='images/loader.gif'></div>";  //SHOWCASE - calendar, stats, lists
        echo "</td></tr>";

    echo "</table>";
  echo "</td>"; //end RIGHT
   
  echo "</tr>"; //end SHOWCASE
}
 
function  _Header()
{
  global $context;
  $user=(strlen($context['userc'])==0) ? $context['user'] : (unicode_urldecode($context['userc']));
//  $user=$context['user'];
  $dot = "<span class='dot'>&#9679;</span>";
  echo "<tr><td colspan=2>";
  echo "<table width=100% class='ui-state-default ui-corner-bottom'><tr>";

  echo "<td align=left id=dtnow>***</td>";
//  echo "<td align=left id=dtnow>time for tea ...</td>";
  echo "<td align=center id=rules><a href='#' title='Read BES-III shift policy'>$dot Rules</a></td>";
  echo "<td align=center id=instructions><a href='#' title='Shift management howto'>$dot Instructions</a></td>";
  echo "<td align=center id=profile><a href='#' title='Click to edit personal profile data'>$dot Profile [ $user ]</a></td>";
  echo "<td align=center id=logout><a href='http://docbes3.ihep.ac.cn/Shibboleth.sso/Logout' title='Click to exit'>$dot Logout</a></td>";
  echo "</tr></table>";
  echo "</td></tr>";
}

//TO DO: login, password, forgot, register, info, contacts";
function  _Enter()
{  
	echo "<tr><td align=center class='ui-widget-content ui-corner-all'>";
	
  echo "<div id='start' valign=middle class='ui-widget-content ui-corner-all'>";
  echo "<table cellspacing=0 class='ui-widget-content ui-corner-all'>";
  echo "<tr><td colspan=3 align=center class='ui-widget-header ui-corner-top'>BES-III Shift Schedule</td></tr>";
  echo "<tr><td>&nbsp</td></tr>";
  echo "<tr><td colspan=3 align=center><button id=login class=smallb>Login</button></td></tr>";
  echo "<tr><td>&nbsp</td></tr>";
//  echo "<tr>";
//    echo "<td class=smallb>&nbsp;&nbsp;e-mail: <input type='text' name='email' id='email' value='' class='ui-widget-content ui-corner-all' placeholder='your e-mail here'></td>";
//    echo "<td class=smallb>&nbsp;&nbsp;password: <input type='password' name='password' id='password' value='' class='ui-widget-content ui-corner-all' placeholder='*****'></td>";
//    echo "<td><button id=login class=smallb>login</button></td>";
//  echo "</tr>";  
//  echo "<tr><td align=center colspan=3 class='tiny validateTips'>check keyboard register while entering your password</td></tr>";
//  echo "<tr><td align=center colspan=3 class=tiny>Forgot your password? Type in the word 'forgot' instead (no quotes).<br> The password would be sent out to the e-mail specified.</td></tr>";
  echo "<tr class='ui-widget-header ui-corner-bottom'>";
  echo "<td  colspan=2 class=tiny align=left><a href='http://bes3.ihep.ac.cn' tagret=_new title='BES-III Home'>BES-III Experiment</a></td>";
  echo "<td class=tiny align=right><a href='mailto:bes3shift@ihep.ac.cn'>support</a></td>";
  echo "</tr>";
  echo "</table>";
  echo "</div>";

	echo "</td></tr>";
}

function _RenderMain()
{
  global $context;
  echo "<table width=100% height=100% class='ui-widget-content ui-corner-all'>";
  if ($context['loggedin']==1) {_Header(); _ShowCase();}
  else if ($context['loggedin']==2) {print "User not found in BES-III database.";}
  else {header("Location: http://docbes3.ihep.ac.cn/~alexey/bes3shift/");
  die();}
  
  echo "</table>";
}
?>
