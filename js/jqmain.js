//jqmain.js

$(function(){

rexEmail=new RegExp("^[-a-z0-9!#$%&'*+/=?^_`{|}~]+(?:\\.[-a-z0-9!#$%&'*+/=?^_`{|}~]+)*@(?:[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])?\\.)*(?:aero|arpa|asia|biz|cat|com|coop|edu|gov|info|int|jobs|mil|mobi|museum|name|net|org|pro|tel|travel|[a-z][a-z])$","i");
warn_email="E-mail address syntax wrong";
warn_symb="Illegal symbols";

var email = $("#email");
var password = $("#password");
var allFields = $([]).add(email).add(password);
var tips = $(".validateTips");

$("button").button();
$("button").css("font-size","0.7em");
$(".radiosel").buttonset();

//$("#datenav").datepicker({changeMonth:true, changeYear:true, minDate: date_from, maxDate: "+1Y", showOtherMonths:true, showWeek:true, firstDay:0, onChangeMonthYear:ShowMonth, onSelect:ShowDay});
$("#datenav").datepicker({changeMonth:true, changeYear:true, minDate: date_from, maxDate: "+1Y", showOtherMonths:true, showWeek:true, firstDay:0, onChangeMonthYear:ShowMonth});
$("#datenav").datepicker("widget").css("font-size","0.7em");

//clock (id=dtnow)
function UpdateShowData() {var dt = new Date(); $('#dtnow').html(dt.toLocaleString());}//clock
function updateTips(t) {tips.text(t).addClass('ui-state-highlight'); setTimeout(function() {tips.removeClass('ui-state-highlight', 1500);}, 500);}
function checkLength(o,min,max) {if ( o.val().length > max || o.val().length < min ) {o.addClass('ui-state-error'); updateTips(warn_len+" "+min+" ..."+max+"."); return false;} return true;}
function checkRegexp(o,regexp,msg) {if (!(regexp.test(o.val()))) {o.addClass('ui-state-error'); updateTips(msg); return false;} return true;}

$("#dialog-message").dialog({modal: true,autoOpen: false,buttons: {Ok: function() {$(this).dialog('close');}}});

function displayMessage(t,msg)
{
  $('#dialog-message').dialog("widget").css("font-size","0.7em");
  $("#dialog-message").dialog({title: t});
  $('#dialog-message').text(msg);
  $('#dialog-message').dialog('open');
}
$("#exit").dialog({
   autoOpen: false, resizable: false, height:170, modal: true,
	 buttons: {
		'ok': function() 
			{
        my_setcookie("loggedin",0,exp_year,"/");
        my_delcookie("utype");
        my_delcookie("user");
        my_delcookie("uuid");
        $(this).dialog('close');
	      window.location = sweet_home;
			}, 'Close': function() {$(this).dialog('close');}
	}
});

$("#logout").click(function(){
  $('#exit').dialog("widget").css("font-size","0.7em");  
  $('#exit').dialog('open');  
});

function informUser(to,subject,body)
{
	$.post("php/smtp_mail.php", {to:to, subject:subject, body:body}, function(data) 
	{		
    if (data.err_no) {tit = "Error ("+data.err_no+")"; displayMessage(tit,data.result);}
    else {displayMessage("Success","Message sent");}
  },"json");  
}

$("#login").click(function(){
  var e=$("#email").val();
  var p=$("#password").val();

	$.post("php/porter.php", {email:e, password:p, oper: "login"}, function(data) 
	{		
    if (data.err_no) 
    {
      if (data.err_no==204) //reminder
      {
        tit = "Done. ("+data.err_no+")"; 
        informUser(data.val.to, data.val.subject, data.val.body);
        displayMessage(tit,data.result);
      }
      else
      {
        tit = "Error ("+data.err_no+")"; 
        displayMessage(tit,data.result);
      }
    }
    else
    {      
      displayMessage("Success","You are logged in.");
      my_setcookie("utype",data.val.shift_role,exp_year,"/");
      my_setcookie("user",data.val.name,exp_year,"/");
      my_setcookie("userc",data.val.chinese_name_unicode,exp_year,"/");
      my_setcookie("uuid",data.val.author_id,exp_year,"/");
      my_setcookie("instid",data.val.institution_id,exp_year,"/");
      my_setcookie("loggedin",1,exp_year,"/");
	    window.location.reload(true);            
    }
  },"json");    
});  

$("#scope").click(function()
{
  var scope= $(this).find("input[id*='scope']:checked").val();
  if (context.scope==scope) return;
  context.scope=scope;
  my_setcookie("scope",context.scope,exp_month,"/");
  if (scope=="m") {$("#datenav").removeClass("transpar"); $("#datenav").addClass("opaque"); ShowMonth();}
  else if (scope=="r") {$("#datenav").removeClass("opaque"); $("#datenav").addClass("transpar"); ShowRoundLast();}
});

//--------------------------------------- shift edit

function  ShiftBook(institution_id,author_id,stype,dtime,msfrom,days,startsindays,bManAss,sfrom,supto,remote)
{
	$.post("php/shifter.php", {oper: "book",institution_id:institution_id,author_id:author_id,stype:stype,dtime:dtime,msfrom:msfrom,days:days,startsindays:startsindays,bManAss:bManAss,from:sfrom,upto:supto, remote:remote}, function(data) 
	{		
    if (data.err_no) 
    {
      if (data.err_no==403) tit="Notice";
      else tit = "Error ("+data.err_no+")"; 
      displayMessage(tit,data.result);
    }
    else 
    {
      informUser(data.val.to, data.val.subject, data.val.body);
      if(remote == 0){
      displayMessage("Success","Shifts block is booked (score: "+data.val.points+" points). User notification sent."); }
      else {
displayMessage("Success","Shifts block (REMOTE) is booked (score: "+data.val.points+" points). User notification sent."); 
      }
      window.location=sweet_home; //redraw calendar
    }  
  },"json");       
}

function ShiftReject(author_id,stype,dtime,msfrom,days,startsindays,bManAss,sfrom,supto)
{
	var oper=((startsindays < LIMIT_DAYS_REJECT) && (bManAss==false))? "confreject" : "reject";
	//but manager is allowed to reject future shifts with no 'before'-time limitations:
	if ((author_id==context.uuid) && (context.utype==MANAGER)) oper="reject";

  if (oper=="confreject")
  {
	  $.post("php/loadform.php", {oper:oper,author_id:author_id,stype:stype,dtime:dtime,msfrom:msfrom,days:days,startsindays:startsindays, user:context.user,from:sfrom,upto:supto}, function(data) 
	  {		
      if (data.err_no){tit = "Error ("+data.err_no+")"; displayMessage(tit,data.result);}
      else
      {
        $("#conform").html(data.val);
        $.getScript("js/jqform.js");
      }
    },"json");      
    return;
  }
	
	$.post("php/shifter.php", {oper:oper,author_id:author_id,stype:stype,dtime:dtime,msfrom:msfrom,days:days,startsindays:startsindays,bManAss:bManAss,from:sfrom,upto:supto}, function(data) 
	{		
    if (data.err_no) 
    {
      tit = "Error ("+data.err_no+")"; 
      displayMessage(tit,data.result);
    }
    else 
    {
      informUser(data.val.to, data.val.subject, data.val.body);
      displayMessage("Success","Shifts block is cancelled and open for another shifter. User notification sent."); 
      window.location=sweet_home; //redraw calendar
    }  
  },"json");       
}

function ShiftExchange(author_id,stype,dtime,msfrom,days,startsindays,bManAss,name,sfrom,supto)
{
  var bOwner=(author_id==context.uuid)?true:false;
  var html=$("#conform").html();
  var html2=$("#conform2").html();
  if ((html.length==0) &&  (bOwner==false))
  {
    displayMessage("Notice","Please, select your own block for exchange first");
    return;
  }
  var formselector = (bOwner==true) ? "#conform" : "#conform2";
  var oper="confexchange";
  var uname=(bOwner==true) ? context.user : name;
  
	$.post("php/loadform.php", {oper:oper,author_id:author_id,stype:stype,dtime:dtime,msfrom:msfrom,days:days,startsindays:startsindays,user:uname,from:sfrom,upto:supto}, function(data) 
	{		
    if (data.err_no){tit = "Error ("+data.err_no+")"; displayMessage(tit,data.result);}
    else
    {
        $(formselector).html(data.val);
        $.getScript("js/jqform.js");
    }
  },"json");      
}

$("#shifted").dialog({autoOpen: false, resizable: false, height:170, width: 300, modal: true});


//--------------------------------------- MONTH view
//RULES:
//chief: sun-wed (4), thur-sat(3)
//ordinary: mon-thur (4), fri-sun (3)
//coordinator: sun-sat (7) - new: mon-sun
function GetBlockSpan(wd, stype) //returns {-days,+days}
{
  var ablock = new Array(0,0);
  var fr=0, up=6; //for coordinator
  switch(stype)
  {
    case CHIEF: if (wd <=3) up=3; else fr=4; break;
    case ORDINARY: if (wd>=1 && wd <=4) {fr=1; up=4;} else {wd=(wd==0)?7:wd; fr=5; up=7;} break;
    case RUNCOORDINATOR: ablock[0]=-1;  ablock[1]=7; return ablock; 
  }
  ablock[0]=wd-fr;   ablock[1]=up-wd;
  return ablock;
}

//populate cells with 
//a) 'holiday=POINTS_HOLIDAY' - for colorizing
//b) shifters (author_id, chinese_speaking) - for colorizing (?)
function PopulateMonth(msfrom,msupto) //35 cells
{
	$.post("php/shifter.php", {oper:"populate",view:"month",msfrom:msfrom,msupto:msupto}, function(data) 
	{		
    if (data.err_no) 
    {
      tit = "Error ("+data.err_no+")"; 
      displayMessage(tit,data.result);
    }
    else 
    {
      var dtclass=new Array('','.mcell_tnight','.mcell_tday','.mcell_teve');
      var stypeclass=new Array('','.ordinary','.chief','.coordinator');
      var c, cobj, cobjc,  shifter, ad, d,day, dayms, holiday, dtime, dt, stype,w,wk,ch,initials,firstname,words;
      var n, coordui=0, coordn=0; //to fix situation, when coordinator's week is split by 'iso8601Week'
      msfrom = parseInt(msfrom); //for IE
      for (n=0; n < data.val.length; n++)
      {
        d=data.val[n].date;
        ad=d.split("-");  //for IE
        day=new Date(ad[0],ad[1]-1,ad[2]);  //y,m,d - for IE
        dayms=day.getTime();
        c=Math.round((dayms-msfrom)/86400000);
        dt=data.val[n].dtime;
        stype=data.val[n].type;
        remote=data.val[n].remote;
        if (data.val[n].chinese_name_unicode.length > 0)
        {
          shifter = data.val[n].chinese_name_unicode;
          ch="+";
        }
        else
        {
//now nospecial 'chinese_speaking' mark  29.05.2012 12:13:07
//ch=(data.val[n].chinese_speaking==1) ? "<sup>~</sup>" : " ";
//shifter = data.val[n].family_name+ch+data.val[n].initials+".";
          ch=" ";
          initials=data.val[n].initials.replace(/[~]/g, "")
          words=data.val[n].family_name.split(" ");
          if (words.length > 1) {firstname=words[0]; /* ch="+"; */}
          else firstname=data.val[n].family_name;
          shifter = firstname+ch+initials;
        }
        
        cobj=$("td[cell_no="+c+"]");
        if (dt >0)
        {          
          dtobj=cobj.find(dtclass[dt]).find(stypeclass[stype]);
          dtobj.html(shifter);
          if(remote==1)
          {dtobj.css("background-color","#99ffff");}
          else{
	  dtobj.css("background-color","#ffff99");
          }
          dtobj.attr("author_id",data.val[n].author_id);
          dtobj.attr("institution_id",data.val[n].institution_id);
          dtobj.attr("chinese_speaking",data.val[n].chinese_speaking);
          //if (ch=="+") 
            dtobj.attr("title",data.val[n].name+"\nemail: "+data.val[n].email);
        }
        else//coordinator
        {
        	if (data.val[n].author_id!=coordui) {coordn=n; coordui=data.val[n].author_id;}//other coord beg
          if ((n-coordn)==0) //only 1st out of 7 - sea the ORDER BY clause - critical! here
          {
          	coordui=data.val[n].author_id;
          	coordn=n;
            d=data.val[n].date;
            ad=d.split("-");  //for IE
            //day=new Date(ad[0],ad[1]-1,ad[2]);  //was; y,m,d - for IE
            //ad[2]-1 - to get sunday and correct view-week - not 100% sure 
            day=new Date(ad[0],ad[1]-1,ad[2]);  
            wk=$.datepicker.iso8601Week(day);
            cobj=$("td").find(".mcell_week").each(function(index) 
            {
              w=parseInt($(this).text());
              if (w==wk) 
              { 
                cobjc=$(this).parent().find(".mcell_tweek");
                cobjc.html(shifter); 
                cobjc.attr("author_id",data.val[n].author_id);
                cobjc.attr("institution_id",data.val[n].institution_id);
                if (ch=="+") cobjc.attr("title",data.val[n].name);
              }
            });
          } else if ((n-coordn) >= 6) {coordn=0; coordui=0;}	//last ind of this coord block (7 days)
        }
      }
      //mark holidays
      for (m=0; m < data.result.length; m++)
      {
        d=data.result[m].date;
        ad=d.split("-");  //for IE
        day=new Date(ad[0],ad[1]-1,ad[2]);  //y,m,d - for IE
        dayms=day.getTime();
        c=Math.round((dayms-msfrom)/86400000);
        holiday=data.result[m].holiday;
        cobj=$("td[cell_no="+c+"]").find(".mcell_day");
        cobj.css("color","#FEA0A0");
        cobj.attr("title",holiday);
      }
    }  
  },"json");         
}

function ShowMonth(year, month, inst)
{
  var y,m;
  var now;
  var msday=86400000; //=24*3600*1000 ms
  if (inst) //month or year selected
  {
    y=year; m=month-1;
    now = new Date(y,m,1); //
    context.msmonth=now.getTime(); //
    my_setcookie("msmonth",context.msmonth,exp_month,"/"); //
  }  
  else //initial = current
  {
    if (context.msmonth==0) //set current
    {
      now = $("#datenav").datepicker("getDate"); y=now.getFullYear(); m=now.getMonth();
      context.msmonth=now.getTime(); //
      my_setcookie("msmonth",context.msmonth,exp_month,"/"); //
    }
    else //set selected //
    {
      now = new Date(parseInt(context.msmonth));
      $("#datenav").datepicker("setDate",now); y=now.getFullYear(); m=now.getMonth();
    }
  }
  var monthNames  = $("#datenav" ).datepicker("option","monthNames"); 
//TO DO: roundsel - shiftround_status, pr    
  var header="Period: "+monthNames[m]+"-"+y;
  var shiftround_name = $("#round").val();
  var pr=parseInt($("#roundsel").attr("pr"));
  if (pr && shiftround_name.length > 0)
  {
    var status=parseInt($("#roundsel").attr("shiftround_status"));
    header +=", round: "+shiftround_name+" - "+roundstatus[status];
    if (status==ROUND_OPEN)
      header +=" for "+alocations[pr]+" institutions.";
  } 
  $("#topcase").html(header);

  //FROM
  var dtfirst=new Date(y,m,1); //month beginning date  
  var dwfirst=dtfirst.getDay(); //0=Sunday, 1=Monday, etc.
  var msfirst=dtfirst.getTime();  
  var msfrom=msfirst-dwfirst*msday;
  var dtfrom=new Date(msfrom);  //cal table 1st date
  var wfrom=$.datepicker.iso8601Week(dtfrom); //cal table 1st week
  //UPTO
  var d = new Date(y,m+1,1);
  var dtlast = new Date(d-1); //last month day
  var mslast=dtlast.getTime();  
  var dwlast=dtlast.getDay();
  var ms1=mslast +(6-dwlast)*msday;
  var dtupto=new Date(ms1);
  
  //we have - month: dt-dtlast, dwfirst,dwlast; calendar: dtfrom - dtupto; 
  var dayNames = $("#datenav" ).datepicker("option","dayNames");  
  //header
  //legend:
  var html ="<table class='smallb'>";
  html+="<tr><td>Shifts daytime colors legend: ";
  html+="<div class='mcell_tnight nonl'>Night: 00:00-08:00</div>,&nbsp;";
  html+="<div class='mcell_tday nonl'>Day: 08:00-16:00</div>,&nbsp;";
  html+="<div class='mcell_teve nonl'>Evening: 16:00-24:00</div>,&nbsp;";
  html+="<div class='mcell_tweek nonl'>Whole day: 00:00-24:00</div>";
  html+="</td></tr>";
  html +="</table>";
 
  html += "<table cellspacing=0 class='ui-widget-content ui-corner-all'><tr class='ui-widget-header'><td class='mcell_header ui-corner-top'>Week</td>";
  for (d=0; d < 7; d++) html +="<td class='mcell_header ui-corner-top'>"+dayNames[d]+"</td>";
  html +="</tr>";
  //cells
  var cell, cell_no=0, attr, dcell, msd, other_month, shift, day_status, day_of_month, moncell, mname;
  ms=dtfrom.getTime();
  for (w=0; w < 5; w++)
  {
    msd = ms + (w*7 + 0)*msday; //sunday
    var msd1 = ms + (w*7 + 1)*msday; //monday
    var cday=new Date(msd);
    var cday1=new Date(msd1);
    var cwk=$.datepicker.iso8601Week(cday1); 
    html +="<tr>";
    //DEFINE WEEK CELL:
    attr="class='mcell ui-state-highlight ui-widget-content ui-corner-all'";
    cell ="<div class='mcell_week ui-widget-content ui-corner-top'>"+(cwk)+"</div>";
    shift="<a href='#'><div class='shift coordinator mcell_tweek' cell_no="+cell_no+" mstime="+msd+" stype="+RUNCOORDINATOR+" author_id=0 institution_id=0 title='edit'>coordinator</div></a>";      
//    cell+="<div class='mcell_dweek mcell_tweek'>"+shift+"</div>"; //night 0-8      
    cell+="<div class='mcell_dweek'>"+shift+"</div>"; //night 0-8      
    html += "<td "+attr+">"+cell+"</td>";
    
  //  ms=dtfrom.getTime();
    for (d=0; d < 7; d++)
    {
      msd = ms + (w*7 + d)*msday;
      var day = new Date(msd);
      moncell=day.getMonth();
      mname=monthNames[moncell];
      dcell=day.getDate();
      
      //DEFINE DAY CELL:
      cell=""; 
      other_month =((day < dtfirst) || (day > dtlast)); //1st week - check 'before', last week - check 'after'
      if (!other_month) attr="class='mcell ui-state-default ui-widget-content ui-corner-all' cell_no="+cell_no;
      else attr="class='mcell ui-widget-content ui-corner-all' dtime="+DTIME_NIGHT+" cell_no="+cell_no;
      cell_no++; //used to define blocks of 3..4 day shifts
      
      //month day
      cell ="<div class='mcell_day ui-state-focus ui-widget-content ui-corner-top'>"+mname+" "+dcell+"</div>";

      //time of day
      shift="<a href='#'><div class='shift chief mcell_tnight' mstime="+msd+" stype="+CHIEF+" author_id=0 institution_id=0 title='edit'>chief</div><div class='shift ordinary mcell_tnight' mstime="+msd+" stype="+ORDINARY+" author_id=0 institution_id=0 title='edit'>ordinary</div></a>";      
      cell+="<div class='mcell_dtime mcell_tnight' dtime="+DTIME_NIGHT+">"+shift+"</div>"; //night 0-8      
      shift="<a href='#'><div class='shift chief mcell_tday' mstime="+msd+" stype="+CHIEF+" author_id=0 institution_id=0 title='edit'>chief</div><div class='shift ordinary mcell_tday' mstime="+msd+" stype="+ORDINARY+" author_id=0 institution_id=0 title='edit'>ordinary</div></a>";      
      cell+="<div class='mcell_dtime mcell_tday' dtime="+DTIME_DAY+">"+shift+"</div>"; //day 8-16      
      shift="<a href='#'><div class='shift chief mcell_teve' mstime="+msd+" stype="+CHIEF+" author_id=0 institution_id=0 title='edit'>chief</div><div class='shift ordinary mcell_teve' mstime="+msd+" stype="+ORDINARY+" author_id=0 institution_id=0 title='edit'>ordinary</div></a>";      
      cell+="<div class='mcell_dtime mcell_teve' dtime="+DTIME_EVENING+">"+shift+"</div>"; //evening 16-24

      html += "<td "+attr+">"+cell+"</td>";
    }    
    html += "</tr>";
  }
  html +="</table>";

  $("#showcase").html(html);

  PopulateMonth(msfrom, ms1); //35 cells
  
  $(".shift").on("click", function()
  {
    var now = new Date(); now=noHMS(now);
    var roundfrom=my_getcookie("start_round");
    var roundupto=my_getcookie("end_round");
    var drfrom = new Date(roundfrom); drfrom=noHMS(drfrom); 
    var drupto = new Date(roundupto); drupto=noHMS(drupto);

    var shifter = $(this).text();
    var st=$(this).attr("stype"); //CHIEF, ORDINARY, RUNCOORDINATOR
    var stype = parseInt(st);
    if (stype==RUNCOORDINATOR)
    {
      var cno=$(this).attr("cell_no");
      var dt=DTIME_ALL;
    }
    else
    {
      var p = $(this).parent().parent();
      var dtime=p.attr("dtime");  //DTIME_NIGHT, DTIME_DAY, DTIME_EVENING
      var dt=parseInt(dtime);
      var cno=p.parent().attr("cell_no"); //zero-based
    }
    var mstime=$(this).attr("mstime"); 
    var msd=parseInt(mstime);
    var day=new Date(msd);
    var wd=day.getDay(); // day of week for the link (0-sunday)
    var author_id=$(this).attr("author_id");
    var institution_id=$(this).attr("institution_id");        
    var tt=$(this).attr("title");
    var name = ((author_id==0) || (author_id==context.uuid)) ? "you" : shifter;
    
    var ablock=GetBlockSpan(wd,stype);  //returns {-days,+days}
    var startblock=new Date(msd-86400000*ablock[0]);
    if (startblock<drfrom && stype!=RUNCOORDINATOR) ablock[0]=Math.round((msd-drfrom.getTime())/86400000);
    var endblock=new Date(msd+86400000*ablock[1]);
    if (endblock>drupto && stype!=RUNCOORDINATOR) ablock[1]=Math.round((drupto.getTime()-msd)/86400000);
    if ((ablock[0]<0 || ablock[1]<0)&&stype!=RUNCOORDINATOR) {displayMessage("Notice","Dates outside round are not editable");return;}

    var from=new Date(msd-ablock[0]*msday);
    var upto=new Date(msd+ablock[1]*msday);
    
    var days=1+ablock[1]+ablock[0];
    var stypes=new Array("","ordinary","chief","coordinator");
    var dtnames=new Array('all','night','day','evening');
    var sfrom=from.getFullYear()+"-"+(from.getMonth()+1)+"-"+from.getDate();
    var supto=upto.getFullYear()+"-"+(upto.getMonth()+1)+"-"+upto.getDate();

    if (now >= from || days<=0){displayMessage("Notice","Shifts in the past are not editable");return;}    

    var startsindays = Math.round((from.getTime() - now.getTime())/msday);

    $('td[id="ed-name"]').html(name);
    $('td[id="ed-stype_name"]').html(stypes[stype]);    
    $('td[id="ed-days_name"]').html(days);    
    $('td[id="ed-from_name"]').html(sfrom);    
    $('td[id="ed-upto_name"]').html(supto);    
    $('td[id="ed-dtime_name"]').html(dtnames[dt]);    
    $('td[id="ed-startsin_name"]').html(startsindays);    

    $('#shifted').dialog("widget").css("font-size","0.7em");      

    var bManAss=false, u_id=0, u_inst=0, u_name="";
    if (context.utype==MANAGER)
    {
       u_id = $("#authorsel").attr("author_id");
       u_inst=$("#authorsel").attr("institution_id");
       u_name = $("#author").val();  
       if (u_name.length > 0) 
       {
          bManAss=true;
          $('td[id="ed-name"]').html(u_name);      
       }              
    }
        
    var msdfrom=from.getTime();
    var buttons = [{text: "Close", click: function() {$(this).dialog('close');}}];
    if (author_id==0) //new own (or manager assigned) block - add 'book'
    {
        author_id=(bManAss==true)? u_id : context.uuid;
        institution_id=(bManAss==true)? u_inst : context.instid;
        var remote = 0;
        if ($("#checkbox").attr("checked") == 'checked'){remote =1;}
        //buttons[1]={text: "Book", click: function() {if ($("#checkbox").attr("checked") == 'checked')){remote =1;}; ShiftBook(institution_id,author_id,stype,dt,msdfrom,days,startsindays,bManAss,sfrom,supto,remote); $(this).dialog("close");}};
        buttons[1]={text: "Book", click: function() {if ($("#checkbox").attr("checked") == 'checked'){remote =1;}; ShiftBook(institution_id,author_id,stype,dt,msdfrom,days,startsindays,bManAss,sfrom,supto,remote); $(this).dialog("close");}};
    }
    else //add 'exchange', 'reject'
    {
      var bOwner=(author_id==context.uuid) ? true : false;
      var btn=1;
      if (bManAss==false) //people make exchange for themselves (not a manager does it)
      {
        buttons[btn]={text: "Exchange", click: function() {ShiftExchange(author_id,stype,dt,msdfrom,days,startsindays,bManAss,name,sfrom,supto); $(this).dialog("close");}};      
        btn++;
      }      
      if ((bOwner==true) || (bManAss==true))  //owner (or manager) - add 'reject'
      {
        author_id = (bManAss==true)? u_id : context.uuid;
        buttons[btn]={text: "Cancel shift", click: function() {ShiftReject(author_id,stype,dt,msdfrom,days,startsindays,bManAss,sfrom,supto); $(this).dialog("close");}};      
      }
    }
	  
    $("#shifted").dialog("option", "buttons", buttons);        
    $('#shifted').dialog('open');  
    
  });
      
  $(".shift").hover(
    function() 
    { 
      var roundfrom=my_getcookie("start_round");
      var roundupto=my_getcookie("end_round");
      var drfrom = new Date(roundfrom); drfrom=noHMS(drfrom);
      var drupto = new Date(roundupto); drupto=noHMS(drupto);

      var st=$(this).attr("stype"); //CHIEF, ORDINARY, RUNCOORDINATOR
      var stype = parseInt(st);
      if (stype==RUNCOORDINATOR)
      {
        var cno=$(this).attr("cell_no");
        var dtime=$(this).attr("dtime");
        $(this).css("font-weight","bolder");
      }
      else
      {
        var p = $(this).parent().parent();     
        var dtime=p.attr("dtime");  //DTIME_NIGHT, DTIME_DAY, DTIME_EVENING
        var cno=p.parent().attr("cell_no"); //zero-based under the link
      }  
      var dt=parseInt(dtime);
      var cell_no=parseInt(cno);
      var mstime=$(this).attr("mstime"); 
      var msd=parseInt(mstime);
      var day=new Date(msd);
      var wd=day.getDay(); // day of week for the link (0-sunday)
      var ablock=GetBlockSpan(wd,stype);  //returns {-days,+days}
      var startblock=new Date(msd-86400000*ablock[0]);
      if (startblock<drfrom && stype!=RUNCOORDINATOR) ablock[0]=Math.round((msd-drfrom.getTime())/86400000);
      var endblock=new Date(msd+86400000*ablock[1]);
      if (endblock>drupto && stype!=RUNCOORDINATOR) ablock[1]=Math.round((drupto.getTime()-msd)/86400000);
      var cell_fr=cell_no-ablock[0]; if (cell_fr < 0) cell_fr=0;
      var cell_up=cell_no+ablock[1]; if (cell_up > 34) cell_up=34;  //7x5-1
      if((ablock[0]<0 || ablock[1]<0) && stype!=RUNCOORDINATOR) {cell_fr=34; cell_up=0;} 
      var cobj, dtobj;
      var dtclass=new Array('','.mcell_tnight','.mcell_tday','.mcell_teve');
      var stypeclass=new Array('','.ordinary','.chief','.coordinator');
      for (var c=cell_fr; c <= cell_up; c++)
      {
        cobj=$("td[cell_no="+c+"]");
        cobj.addClass('ui-state-highlightred');
        if (dtime>0)
        {          
          dtobj=cobj.find(dtclass[dt]).find(stypeclass[stype]);
          dtobj.css("text-decoration","underline");
        }        
      }    
    }, 
    function() 
    {
      var st=$(this).attr("stype"); //CHIEF, ORDINARY, RUNCOORDINATOR
      var stype = parseInt(st);
      if (stype==RUNCOORDINATOR)
      {
        var cno=$(this).attr("cell_no");
        var dtime=$(this).attr("dtime");
        $(this).css("font-weight","normal");
      }
      else
      {
        var p = $(this).parent().parent();
        var dtime=p.attr("dtime");  //DTIME_NIGHT, DTIME_DAY, DTIME_EVENING
        var cno=p.parent().attr("cell_no"); //zero-based under the link
      }
      var dt=parseInt(dtime);
      var cell_no=parseInt(cno);      //
      var mstime=$(this).attr("mstime"); 
      var msd=parseInt(mstime);
      var day=new Date(msd);
      var wd=day.getDay(); // day of week for the link (0-sunday)
      var ablock=GetBlockSpan(wd,stype);  //returns {-days,+days}
      var cell_fr=cell_no-ablock[0]; if (cell_fr < 0) cell_fr=0;
      var cell_up=cell_no+ablock[1]; if (cell_fr > 34) cell_fr=34;  //7x5-1
      var cobj, dtobj;      
      var dtclass=new Array('','.mcell_tnight','.mcell_tday','.mcell_teve');
      var stypeclass=new Array('','.ordinary','.chief','.coordinator');      
      for (var c=cell_fr; c <= cell_up; c++)
      {
        cobj=$("td[cell_no="+c+"]");
        cobj.removeClass('ui-state-highlightred');
        if (dtime>0)
        {          
          dtobj=cobj.find(dtclass[dt]).find(stypeclass[stype]);
          dtobj.css("text-decoration","none");          
        }        
      }    
    }
  );

}

//--------------------------------------- ROUND view
function PopulateRound(from,upto)
{
	$.post("php/shifter.php", {oper:"populate",view:"round",from:from,upto:upto}, function(data) 
	{		
    if (data.err_no) {tit = "Error ("+data.err_no+")"; displayMessage(tit,data.result);}
    else 
    {
      var dtclass=new Array('','.mcell_tnight','.mcell_tday','.mcell_teve');
      var stypeclass=new Array('','.ordinary','.chief','.coordinator');
      var c, cobj, cobjc,  shifter, ad, d,day, dayms, holiday, dtime, dt, stype,w,wk,ch;
      var n, coordui=0, coordn=0; //to fix situation, when coordinator's week is split by 'iso8601Week'
      var msd, occupied;
      //var acells=$(".rmcell");  //all td
      for (n=0; n < data.val.length; n++)
      {
        d=data.val[n].date;
        ad=d.split("-");  //for IE
        day=new Date(ad[0],ad[1]-1,ad[2]);  //y,m,d - for IE
        dayms=day.getTime();
        dt=data.val[n].dtime;
        stype=data.val[n].type;
 
        //ch=(data.val[n].chinese_speaking==1) ? "<sup>~</sup>" : " ";
        //shifter = data.val[n].family_name+ch+data.val[n].initials+".";
        if (dt >0) //1-night,2,day,3-evening
        { 
          cobj=$("td[msd="+dayms+"]");
          if (cobj)
          {
//msd=cobj.attr("msd");
            occupied = parseInt(cobj.attr("occupied")); //29.05.2012 17:04:13
            occupied++;
            cobj.attr("occupied",occupied);
/*
            cobjc = cobj.find(".rmcell_day");
            if (occupied <=3)
              cobjc.addClass("ui-widget-header");                  
            else if (occupied < 6)
              cobjc.addClass("ui-state-highlight");                  
            else  
            {
              cobjc.css("color","white");
              cobjc.addClass("ui-state-focus");                  
            }
*/            
          }
        }
        else//0-all day (runcoordinator)
        {          
        	if (data.val[n].author_id!=coordui) {coordn=n; coordui=data.val[n].author_id;}//other coord beg
          if ((n-coordn)==0) //only 1st out of 7 - sea the ORDER BY clause - critical! here
          {
          	coordui=data.val[n].author_id;
          	coordn=n;
            d=data.val[n].date;
            ad=d.split("-");  //for IE
            day=new Date(ad[0],ad[1]-1,ad[2]-1);
            wk=$.datepicker.iso8601Week(day);
            cobj=$("td").find(".rmcell").find(".rmcell_week").each(function(index) 
            {
              w=parseInt($(this).text());
              if (w==wk) 
              { 
                cobjc=$(this);
                //debug: just mark a cell, where we've got smth.
                cobjc.css("color","white");
                cobjc.addClass("ui-state-focus");                                  
              }
            });
          } else if ((n-coordn) >= 6) {coordn=0; coordui=0;}	//last ind of this coord block (7 days)
        } //end coord
      }
      
      //colorize the day cells
      var acells=$(".rmcell");
      acells.each(function(index) 
      {
        occupied = parseInt($(this).attr("occupied")); //29.05.2012 17:04:13
        cobjc = $(this).find(".rmcell_day");
        if (occupied==0)
	  cobjc.addClass("ui-state-redfill");
        else if (occupied <=3)
          cobjc.addClass("ui-state-orangefill");                  
        else if (occupied < 6)
          cobjc.addClass("ui-state-yellowfill");                  
        else if (occupied >= 6) 
        {
         // cobjc.css("color","white");
          cobjc.addClass("ui-state-greenfill");                  
        }             
      });
      
      
      //mark holidays
      
      for (m=0; m < data.result.length; m++)
      {
        d=data.result[m].date;
        ad=d.split("-");  //for IE
        day=new Date(ad[0],ad[1]-1,ad[2]);  //y,m,d - for IE
        dayms=day.getTime();        
        holiday=data.result[m].holiday;
        
        cobj=$("td[msd="+dayms+"]");
        cobjc = cobj.find(".rmcell_day");
        cobjc.css("color","#FEA0A0");
        cobjc.attr("title",holiday);
      }
             
    }  
  },"json");               
}

function genCal(year,month,cell_header)
{
  var y,m,wk;
  var msday=86400000; //=24*3600*1000 ms
  y=year; m=month-1;  //month or year selected
  //FROM
  var dtfirst=new Date(y,m,1); //month beginning date  
  var dwfirst=dtfirst.getDay(); //0=Sunday, 1=Monday, etc.
  var msfirst=dtfirst.getTime();  
  var msfrom=msfirst-dwfirst*msday;
  var dtfrom=new Date(msfrom);  //cal table 1st date
  var wfrom=$.datepicker.iso8601Week(dtfrom); //cal table 1st week
  //UPTO
  var d = new Date(y,m+1,1);
  var dtlast = new Date(d-1); //last month day
  var mslast=dtlast.getTime();  
  var dwlast=dtlast.getDay();
  var ms1=mslast +(6-dwlast)*msday;
  var dtupto=new Date(ms1);
  var roundfrom=my_getcookie("start_round");
  var roundupto=my_getcookie("end_round");
  var drfrom = new Date(roundfrom); drfrom=noHMS(drfrom);
  var drupto = new Date(roundupto); drupto=noHMS(drupto);
  var drfirst=drfrom.getDay();
  var drlast=drupto.getDay();
  //we have - month: dt-dtlast, dwfirst,dwlast; calendar: dtfrom - dtupto; 
  var dayNames = $("#datenav" ).datepicker("option","dayNamesShort");  
  //header
  var html = "<table cellspacing=0 class='ui-widget-content ui-corner-all'>";
  html +="<tr><td colspan=8 class='rcell_month_name rcell_header ui-state-focus ui-widget-content ui-corner-top'>"+cell_header+"</td></tr>";
  html += "<tr class='ui-widget-header'><td class='rmcell_header'>Wk</td>";  
  for (d=0; d < 7; d++) html +="<td class='rmcell_header'>"+dayNames[d]+"</td>";
  html +="</tr>";
  
  //cells
  var cell, attr, dcell, msd, other_month, beyond_round, shift, day_status, day_of_month;
  for (w=0; w < 5; w++)
  {
    html +="<tr>";
   // wk = (w+wfrom); if (wk > 52) wk -= 52;
    var dtwk=new Date(msfrom+(w*7+1)*msday);
    wk=$.datepicker.iso8601Week(dtwk); 
    //DEFINE WEEK CELL:
    attr="class='rmcell ui-state-highlight ui-widget-content ui-corner-all'";
    cell ="<div class='rmcell_week ui-widget-content ui-corner-top'>"+wk+"</div>";
    html += "<td "+attr+">"+cell+"</td>";
    
    ms=dtfrom.getTime();
    for (d=0; d < 7; d++)
    {
      msd = ms + (w*7 + d)*msday;
      var day = new Date(msd);
      //dcell=day.getDate();
      
      //DEFINE DAY CELL:
      cell=""; 
      beyond_round = ((day<drfrom)||(day>drupto));
      other_month =((day < dtfirst) || (day > dtlast)); //1st week - check 'before', last week - check 'after'
      if (!other_month && !beyond_round)
      {
        attr="class='rmcell ui-state-default ui-widget-content ui-corner-all' msd="+msd+" occupied=0";
        dcell=day.getDate();
      }
      else  
      {
        attr="class='rmcell ui-widget-content ui-corner-all'";
        dcell="";
      }
     //month day:
      cell ="<div class='rmcell_day ui-widget-content ui-corner-all'>"+dcell+"</div>";

      html += "<td "+attr+">"+cell+"</td>";
    }    
    html += "</tr>";
  }
  html +="</table>";
  return html;
}

function ShowRound()
{
  var cols=3;
  var shiftround_id = $("#roundsel").attr("shiftround_id");
  var shiftround_name = $("#round").val();
  var from=$("#roundsel").attr("start_date");
  var upto=$("#roundsel").attr("end_date");
  my_setcookie("start_round",from,exp_day,"/");
  my_setcookie("end_round",upto,exp_day,"/");
  my_setcookie("round_name",shiftround_name);
  
  var dtfrom = new Date(from); dtfrom.setHours(0,0,0,0); var mfrom=dtfrom.getMonth()+1; var yfrom=dtfrom.getFullYear();
  var dtupto = new Date(upto); dtupto.setHours(23,59,59,99); var mupto=dtupto.getMonth()+1; var yupto=dtupto.getFullYear();
  var months = (yfrom==yupto) ? (mupto-mfrom+1) : ((yupto-yfrom-1)*12 + (12-mfrom+1) + mupto);
  var col_first=(mfrom-1) % cols;
  var col_last=(mupto-1) % cols;
  var dtfirst = new Date(yfrom,mfrom-1-col_first,1); var mfirst=dtfirst.getMonth()+1; var yfirst=dtfirst.getFullYear();
  var dtlast = new Date(yupto,mupto-1+col_last,28); var mlast=dtlast.getMonth()+1; var ylast=dtlast.getFullYear();  //month day doesn't matter here
  var allmonths = (yfirst==ylast) ? (mlast-mfirst+1) : ((ylast-yfirst-1)*12 + (12-mfirst+1) + mlast);
  var rows=allmonths/cols;
    
  var row, col, m, y, other_round, rcell;
  var attr="", cell="", cal="";

  var monthNames  = $("#datenav" ).datepicker("option","monthNames"); 
  //legend:
  var html ="<table>";
//  html+="<tr><td>&nbsp;</td></tr>";
  html+="<tr><td>Covered shifts: ";
  html+="<div class='rmcell_day ui-widget-content ui-corner-all ui-state-redfill nonl'>&nbsp;&nbsp;0%&nbsp;</div>&nbsp;";
  html+="<div class='rmcell_day ui-widget-content ui-corner-all ui-state-orangefill nonl'>&nbsp;<=50%&nbsp;</div>&nbsp;";
  html+="<div class='rmcell_day ui-widget-content ui-corner-all ui-state-yellowfill nonl'>&nbsp;>50%&nbsp;</div>&nbsp;";
  html+="<div class='rmcell_day ui-widget-content ui-corner-all ui-state-greenfill nonl'>&nbsp;100%&nbsp;</div>";
  html+="</td></tr>";
  html +="</table>";

  html += "<table cellspacing=0 class='ui-widget-content ui-corner-all'>";
  for (row=0; row < rows; row++)
  {
    html +="<tr>";
    for (col=0; col < cols; col++)
    {
      var dt=new Date(yfirst,mfirst-1+ row*cols + col,1); 
      m=dt.getMonth(); 
      y=dt.getFullYear();
      rcell=monthNames[m]+"-"+y;
      var dtfromNew = new Date(dtfrom.getFullYear(),dtfrom.getMonth(),1);
      var dtuptoNew = new Date(dtupto.getFullYear(),dtupto.getMonth(),28);
      other_round =((dt < dtfromNew) || (dt > dtuptoNew));
      if (!other_round)
      {
        attr="class='rcell ui-state-default ui-widget-content ui-corner-all'";
        cal=genCal(y,m+1,rcell);
      }
      else  
      {
        attr="class='rcell ui-widget-content ui-corner-all'";
        cal="";
      }
//      cell="<div class='rcell_month_cal'>"+cal+"</div>";
      cell="<a href='#' class='rcell_month_cal' m="+m+" y="+y+">"+cal+"</a>";
      html += "<td "+attr+">"+cell+"</td>";
    }
    html +="</tr>";
  }  
  html +="</table>";
    
  $("#showcase").html(html);
  var header="Round: ";
  if (shiftround_name.length > 0)
    header +=shiftround_name;
  var pr=parseInt($("#roundsel").attr("pr"));
  if (pr)
  {
    var status=parseInt($("#roundsel").attr("shiftround_status"));
    header +=" - "+roundstatus[status];
    if (status==ROUND_OPEN)
      header +=" for "+alocations[pr]+" institutions";
  } 
  $("#topcase").html(header);
  
  PopulateRound(from,upto);
  $(".rcell_month_cal").click(function()
  {
    m=$(this).attr("m");
    y=$(this).attr("y");
    var msel = new Date(y,m,1); //
    context.scope="m";
    my_setcookie("scope",context.scope,exp_month,"/");
    context.msmonth=msel.getTime(); //
    my_setcookie("msmonth",context.msmonth,exp_month,"/"); //
	  window.location = sweet_home;
  });
}

function ShowRoundLast()
{
  var shiftround_id = $("#roundsel").attr("shiftround_id");
  var shiftround_name = $("#round").val();
  if ((shiftround_id > 0) && (shiftround_name.length>0))
   ShowRound();
  else
  {
	  $.post("php/round.php", {oper: "last"}, function(data) 
	  {		
      if (data.err_no) {tit = "Error ("+data.err_no+")"; displayMessage(tit,data.result);}
      else 
      {
        if (context.shown_1==0)
        {
          displayMessage("Notice","No shift round selected, assuming the latest one"); 
          context.shown_1=1;
          my_setcookie("shown_1",context.shown_1,exp_day,"/");                    
        }
        
        $("#roundsel").attr("shiftround_id",data.val.shiftround_id);
        $("#round").val(data.val.shiftround_name);
        $("#roundsel").attr("start_date",data.val.start_date);
        $("#roundsel").attr("end_date",data.val.end_date);
	my_setcookie("start_round",data.val.start_date,exp_day,"/");
	my_setcookie("end_round",data.val.end_date,exp_day,"/");
	my_setcookie("round_name",data.val.shiftround_name);
        //limit the calendar
        var from = new Date(data.val.start_date);
        var upto = new Date(data.val.end_date);
        $("#datenav").datepicker("option","minDate", from);
        $("#datenav").datepicker("option","maxDate", upto);
        var now = new Date(); now=noHMS(now);
        if (now < from || now > upto) $("#datenav").datepicker("option","defaultDate",from); //if past or future period
        ShowRound();
      }  
    },"json");       
  }
}

//Round select, edit
$( "#round" ).autocomplete({
  minLength: 1,
  source: function(request, response) 
  {
		$.post("php/autocomplete.php", {term:request.term, oper:"round"}, function(data) 
		{		
      if (data.err_no) {response("");} //tit = "Error ("+data.err_no+")"; displayMessage(tit,data.result);
      else {response(data.val);}
    },"json");
  },
  select: function(event, ui) 
  {
    $("#round").val(ui.item.label);
    $("#roundsel").attr("shiftround_id",ui.item.shiftround_id);
    $("#roundsel").attr("start_date",ui.item.start_date);
    $("#roundsel").attr("end_date",ui.item.end_date); 
    $("#roundsel").attr("shiftround_status",ui.item.shiftround_status);
    $("#roundsel").attr("pr",ui.item.pr);
    my_setcookie("start_round",ui.item.start_date,exp_day,"/");
    my_setcookie("end_round",ui.item.end_date,exp_day,"/");

    //limit the calendar
    var from = new Date(ui.item.start_date);
    var upto = new Date(ui.item.end_date);
    $("#datenav").datepicker("option","minDate", from);
    $("#datenav").datepicker("option","maxDate", upto);
    var now = new Date(); now=noHMS(now);
    if (now < from || now > upto) $("#datenav").datepicker("option","defaultDate",from); //if past or future period
  }
});

$('#round').keypress(function(event) {
  if (event.which == '13') 
  {
     event.preventDefault();
     //$('#some_button').click();
   }
});

//Institution select, edit
$( "#inst" ).autocomplete({
  minLength: 1,
  source: function(request, response) 
  {
		$.post("php/autocomplete.php", {term:request.term, oper:"inst"}, function(data) 
		{		
      if (data.err_no) {response("");} //tit = "Error ("+data.err_no+")"; displayMessage(tit,data.result);
      else {response(data.val);}
    },"json");
  },
  select: function(event, ui) 
  {
    $("#inst").val(ui.item.label);
    $("#instsel").attr("institution_id",ui.item.institution_id);
    $("#authorsel").attr("author_id",0);
    $("#author").val("");  
  }
});

$('#inst').keypress(function(event) {
  if (event.which == '13') 
  {
     event.preventDefault();
     //$('#some_button').click();
   }
});


//Author select, edit
$( "#author" ).autocomplete({
  minLength: 2,
  source: function(request, response) 
  {
    var institution_id = $("#instsel").attr("institution_id");  //id==0 - any
    var abbreviation_name = $("#inst").val(); trimstring(abbreviation_name);
    if (abbreviation_name.length==0) {institution_id=0; $("#instsel").attr("institution_id",0);}
    
		$.post("php/autocomplete.php", {term:request.term, inst:institution_id, oper:"author"}, function(data) 
		{		
      if (data.err_no) {response("");} //tit = "Error ("+data.err_no+")"; displayMessage(tit,data.result);
      else {response(data.val);}
    },"json");
  },
  select: function(event, ui) 
  {
    $("#author").val(ui.item.label);
    $("#authorsel").attr("author_id",ui.item.author_id);
    $("#authorsel").attr("institution_id",ui.item.institution_id);
    $("#authorsel").attr("shift_role",ui.item.shift_role);
  }
});

$('#author').keypress(function(event) {
  if (event.which == '13') 
  {
     event.preventDefault();
     //$('#some_button').click();
   }
});

//'display roster' stuff

function getTableHeader(table_name)
{
  //roster
  var inst = new Array("Name","Full name","Address","Contact person");
  var auth = new Array("Name","Position","Author","Speaks Chinese");
  //stats
  var auth_s = new Array("Shift","Points","Type","Time");//{date,dtime,type,score}
  var inst_s = new Array("Name","Shifts","Points");//{name,shiftssum,pointssum}
 //var all_s = new Array("Name","Shifters*","Shifts","Nights %","Points required","Points gained","Still needed","Compliance");
  var all_s = new Array("Name","Current authors","Shifts taken","Night shifts [%]","Points required","Scored points","Points needed","Compliance [%]");
  ////{name,manpower,shiftssum,nightpc,pointsreq,pointssum, pointsleft,compliance}

  if (table_name=="roster_inst") return inst;
  else if (table_name=="roster_auth") return auth;
  else if (table_name=="stats_auth") return auth_s;
  else if (table_name=="stats_inst") return inst_s;
  else if (table_name=="stats_all") return all_s;
  return null;  
}

function getTableFields(table_name)
{
  //roster
  var inst = new Array("abbreviation_name","full_name","address1","name");
  var auth = new Array("name","position","is_author","chinese_speaking");
  //stats
  var auth_s = new Array("date","score","type","dtime");
  var inst_s = new Array("name","shiftssum","pointssum");
//  var all_s = new Array("name","manpower","shiftssum","nightpc","pointsreq","pointssum","pointsleft","compliance");
  var all_s = new Array("name","author","shiftssum","nightpc","pointsreq","pointssum","pointsleft","compliance");
 
  if (table_name=="roster_inst") return inst;
  else if (table_name=="roster_auth") return auth;
  else if (table_name=="stats_auth") return auth_s;
  else if (table_name=="stats_inst") return inst_s;
  else if (table_name=="stats_all") return all_s;
  return null;  
}
function TableCols(table_name)
{
  var h = getTableHeader(table_name);
  return h.length;
}
function HeaderTR(table_name, cols)
{
  var html="";
  var h, col;
  var h = getTableHeader(table_name);
  html+="<tr class='ui-widget-header'>";
  for (col=0; col < cols; col++)
  {
    html+="<td class='trow'>"+h[col]+"</td>";
  }
  html +="</tr>";
  return html;
}
function RowTR(table_name,val,cols)
{
  var v, value, col, keys=getTableFields(table_name);
  var red="listrow";
  if(table_name=="stats_all")
    {  
      for (col=0; col < cols; col++)
      {
        v = keys[col];
        if(v=="pointsleft")
        {
          if(val[v]>0) red="listrowred";
          else red="listrow";
        }
      }
    }

  var html="<tr class='"+red+"'>";
  for (col=0; col < cols; col++)
  {
    v = keys[col];
    //exceptions:
    if (v=="shift_role")
    {
       if (val[v] < 0 || val[v] > 2) value=shift_role[0];
       else value = shift_role[val[v]];
    }
    else if (v=="chinese_speaking")
    {
       if (val[v] <= 0 || val[v] > 2) value="no";
       else value = "yes";
    }
    else if (v=="dtime")
    {
       if (val[v] < 0 || val[v] > 3) value=dtnames[0];
       else value = dtnames[val[v]];
    }
    else if (v=="type")
    {
       if (val[v] < 1 || val[v] > 3) value=stypes[0];
       else value = stypes[val[v]];
    }
    else if (val[v]) 
    	 value = val[v];
    else
    	 value=0;	 
    html+="<td class='trow'>"+value+"</td>";
  }
  html+="</tr>";
  return html;
}

$("#roster").click(function(){
  var shiftround_id = $("#roundsel").attr("shiftround_id");  
  var institution_id = $("#instsel").attr("institution_id");
  var author_id = $("#authorsel").attr("author_id");

  var shiftround_name = $("#round").val();
  var abbreviation_name = $("#inst").val();
  var name = $("#author").val();
  
  if (abbreviation_name.length==0) institution_id=0;
  if (name.length==0) author_id=0;
  
  var oper="roster";
  var orderby="";
  var orderdir="";
  var topcase="";
  if (abbreviation_name.length > 0) //authors
  {
     orderby = "family_name";
     orderdir = "ASC";
     topcase = "Institution: "+abbreviation_name+": all members";
     if(shiftround_id>0) topcase += " for the shift period "+shiftround_name;
  }
  else //institutions
  {
     orderby = "abbreviation_name"; //TO DO: get it from column header
     orderdir = "ASC";
     topcase = "Roster: all institutes";
  }
  
	$.post("php/rostat.php", {inst:institution_id, orderby: orderby, orderdir: orderdir, shiftround_id: shiftround_id, oper: oper}, function(data) 
	{		
    if (data.err_no) 
    {
      var tit = "Error ("+data.err_no+")"; 
      displayMessage(tit,data.result);
    }
    else
    {      
      var table_name =(institution_id > 0) ? "roster_auth" : "roster_inst";
      var cols=TableCols(table_name); //depends on user type

      var html = "<table cellspacing=0 class='ui-widget-content ui-corner-all' name='"+table_name+"'>";
      //header
      html+= HeaderTR(table_name, cols);
      //rows
      var row, rows=data.val.length;
      for(row=0; row < rows; row++)
        html+=RowTR(table_name,data.val[row],cols);
      //totals            
      html+="</table>";      
      $("#showcase").html(html);      
      topcase += " ("+rows+")";
      $("#topcase").html(topcase);
      $(".listrow").hover(function () {$(this).addClass("ui-state-highlight"); },function () {$(this).removeClass("ui-state-highlight");});      
    }
  },"json");      
});

function RowTRTotals(table_name,data,rows,cols)
{
	var totals= new Array(cols);
	var pc,maxrows=1;
	var legend="";
	if (table_name=="stats_auth")
  {
  	var shifts=rows;	//shifts number
  	var score=0.0;    //score sum
  	var adtime = new Array(0,0,0,0);	//0-DTIME_ALL,1-DTIME_NIGHT,2-DTIME_DAY,3-DTIME_EVENING
  	var atype = new Array(0,0,0,0);	//1-ORDINARY,2-CHIEF,3-RUNCOORDINATOR
  	var dtime, stype;
  	for(var row=0; row < rows; row++)
  	{
  		score+=parseFloat(data[row]['score']);
  		dtime=parseInt(data[row]['dtime']); adtime[dtime]++;
  		stype=parseInt(data[row]['type']);  atype[stype]++;
  	}
  	//types %  	
  	pc="";
  	if (atype[ORDINARY] > 0)       pc +=(100.0*atype[ORDINARY]/shifts).toPrecision(4)+"-"+stypes[ORDINARY]+" %<br>";
  	if (atype[CHIEF] > 0)          pc +=(100.0*atype[CHIEF]/shifts).toPrecision(4)+"-"+stypes[CHIEF]+" %<br>";
  	if (atype[RUNCOORDINATOR] > 0) pc +=(100.0*atype[RUNCOORDINATOR]/shifts).toPrecision(4)+"-"+stypes[RUNCOORDINATOR]+" %<br>";
    totals[2]=pc;
    //time of day %
  	pc="";
  	if (adtime[DTIME_ALL] > 0)     pc += (100.0*adtime[DTIME_ALL]/shifts).toPrecision(4)+"-"+dtnames[DTIME_ALL]+" %<br>";
  	if (adtime[DTIME_NIGHT] > 0)   pc +=(100.0*adtime[DTIME_NIGHT]/shifts).toPrecision(4)+"-"+dtnames[DTIME_NIGHT]+" %<br>";
  	if (adtime[DTIME_DAY] > 0)     pc +=(100.0*adtime[DTIME_DAY]/shifts).toPrecision(4)+"-"+dtnames[DTIME_DAY]+" %<br>";
  	if (adtime[DTIME_EVENING] > 0) pc +=(100.0*adtime[DTIME_EVENING]/shifts).toPrecision(4)+"-"+dtnames[DTIME_EVENING]+" %<br>";
    totals[3]=pc;
    totals[0]=shifts;
    totals[1]=score;
  }  
  else if (table_name=="stats_inst") //{name,shiftssum,pointssum}
  {
  	var authors=rows;	//authors number
  	var score=0.0;    //score sum
  	var shifts=0;	   //shifts sum
  	for(var row=0; row < rows; row++)
  	{
  		if (data[row]['pointssum'])
  		  score+=parseFloat(data[row]['pointssum']);
  		shifts+=parseInt(data[row]['shiftssum']);
  	}
  	totals[0]=authors;
  	totals[1]=shifts;
  	totals[2]=score;  	
  }
  else if ( table_name=="stats_all")
  {
  	totals[0]=rows;	//inst number
        totals[1]="";   // authors
  	totals[2]=0.0;  //shiftssum
  	totals[3]="";  //nights %
  	totals[4]=0.0;  //points req.
  	totals[5]=0.0;  //points gained
  	totals[6]=0.0;  //points left
  	totals[7]=0.0;  //compliance %
        totals[8]=0.0;  //manpower

  	for(var row=0; row < rows; row++)
  	{
  		totals[8] +=data[row]['manpower'];
  		totals[2] +=parseInt(data[row]['shiftssum']);
  		totals[4] +=data[row]['pointsreq'];
  		totals[5] +=parseFloat(data[row]['pointssum']);
  		totals[6] +=data[row]['pointsleft'];
  	}  	
  	//toPrecision(4)
  	totals[8] = Math.round(totals[8]*100)/100;
  	totals[2] = Math.round(totals[2]*100)/100;
  	totals[4] = Math.round(totals[4]*100)/100;
  	totals[5] = Math.round(totals[5]*100)/100;
  	totals[6] = Math.round(totals[6]*100)/100;
        totals[7] = Math.round(totals[5]/totals[4]*100)+"%";

  //	legend="<table class='smallb'><tr ><td colspan="+cols+"><sup>*</sup> Manpower may be fractional in cases when authors' join-leave authors' list date(s) occur within a round period start-end date(s)</td></tr></table>";  	
  }
  var html="<tr class='listrow ui-widget-header'>";
  for (col=0; col < cols; col++)
  {
    html+="<td class='trow'>"+totals[col]+"</td>";
  }
  html+="</tr>";
 // html+=legend;
  return html;
}

$("#stats").click(function(){
  var request = {};
  request.shiftround_id = $("#roundsel").attr("shiftround_id");  
  request.institution_id = $("#instsel").attr("institution_id");
  request.author_id = $("#authorsel").attr("author_id");
  //check for idd
  var shiftround_name = $("#round").val();  trimstring(shiftround_name);   if (shiftround_name.length==0) request.shiftround_id=0;
  var abbreviation_name = $("#inst").val(); trimstring(abbreviation_name); if (abbreviation_name.length==0) request.institution_id=0;
  var name = $("#author").val();            trimstring(name);              if (name.length==0) request.author_id=0;
  
  //TO DO: add columns sorting
  var orderby="";
  var orderdir="";
  if (request.author_id > 0) //show authors' stats
  {
  }
  else if (request.institution_id > 0) //show authors' stats
  {
     orderby = "family_name";
     orderdir = "ASC";
  }
  else //all institutions
  {
     orderby = "abbreviation_name"; //TO DO: get it from column header
     orderdir = "ASC";
  }

  var topcase="Statistics for";
  if (request.author_id >0)topcase+=" "+name;
  else if (request.institution_id > 0) topcase+=" "+abbreviation_name;
  if (request.shiftround_id > 0) topcase+=" - round "+shiftround_name;
  else topcase+=" - all rounds";

	$.post("php/rostat.php", {oper: "stats", orderby:orderby, orderdir:orderdir, request: request}, function(data) 
	{		
    if (data.err_no) 
    {
      var tit = "Error ("+data.err_no+")"; 
      displayMessage(tit,data.result);
    }
    else
    {      
      var table_name="stats_all";
      if (request.author_id >0) table_name="stats_auth";
      else if (request.institution_id > 0) table_name="stats_inst";
      else 
      {
        table_name="stats_all";
        var msg="Required: shifts="+data.result.shifts+", points="+data.result.points+"; points gained="+data.result.pointsgained+", overall manpower="+data.result.manpower;
        displayMessage("Totals check",msg);
      }
      var cols=TableCols(table_name);
      var html = "<table cellspacing=0 class='ui-widget-content ui-corner-all' name='"+table_name+"'>";
      //header
      html+= HeaderTR(table_name, cols);
      //rows
      var row, rows=data.val.length;
      for(row=0; row < rows; row++)
        html+=RowTR(table_name,data.val[row],cols);
      html+=RowTRTotals(table_name,data.val,rows,cols);
      html+="</table>";      
      $("#showcase").html(html);      
      $("#topcase").html(topcase);
      $(".listrow").hover(function () {$(this).addClass("ui-state-highlight"); },function () {$(this).removeClass("ui-state-highlight");});      
    }
  },"json");      
    
});

//some of tables supprot for a MANAGER --------------------------

$("#holidays_ed").click(function(){
  var shiftround_id = $("#roundsel").attr("shiftround_id");
  var shiftround_name = $("#round").val();
  if (shiftround_name.length==0) 
  {
    displayMessage("Notice","Select round first");
    return;
  }
  var from=$("#roundsel").attr("start_date");
  var upto=$("#roundsel").attr("end_date");
  var rstatus=$("#roundsel").attr("shiftround_status");
  
	$.post("php/loadform.php", {utype:context.utype, from:from, upto:upto, rstatus:rstatus, oper: "holidays"}, function(data) 
	{		
    if (data.err_no){tit = "Error ("+data.err_no+")"; displayMessage(tit,data.result);}
    else
    {
      $("#showcase").html(data.val);
      $("#topcase").html("Holidays for Shift Round Period: "+shiftround_name+" ("+from+" - "+upto+")");
      $.getScript("js/jqform.js");
    }
  },"json");      
});

$("#round_ed").click(function(){
  var shiftround_id = $("#roundsel").attr("shiftround_id");
  var shiftround_name = $("#round").val();
  if (shiftround_name.length==0) {shiftround_id=0; shiftround_name="new";} 
	$.post("php/loadform.php", {utype:context.utype, cuuid:context.uuid, roundid:shiftround_id, oper: "round"}, function(data) 
	{		
    if (data.err_no){tit = "Error ("+data.err_no+")"; displayMessage(tit,data.result);}
    else
    {
      $("#showcase").html(data.val);
      $("#topcase").html("Shift Round Period: "+shiftround_name);
      $.getScript("js/jqform.js");
    }
  },"json");      
});

$("#inst_ed").click(function(){
  var institution_id = $("#instsel").attr("institution_id");
  var abbreviation_name = $("#inst").val();
  if (abbreviation_name.length==0) {institution_id=0; abbreviation_name="new";}
	$.post("php/loadform.php", {utype:context.utype, cuuid:context.uuid, cinstid:context.instid, instid:institution_id, oper: "institution"}, function(data) 
	{		
    if (data.err_no){tit = "Error ("+data.err_no+")"; displayMessage(tit,data.result);}
    else
    {
      $("#showcase").html(data.val);
      $("#topcase").html("Institution: "+abbreviation_name);
      $.getScript("js/jqform.js");
    }
  },"json");      
});

$("#author_ed").click(function(){
  var author_id = $("#authorsel").attr("author_id");
  var shift_role = $("#authorsel").attr("shift_role");
  var name = $("#author").val();  
  if (name.length==0) {author_id=0; shift_role=0;} //creating new author/user  
  if (shift_role==MANAGER)
  {
    displayMessage("Notice","You can't edit another manager's profile.");
    return;
  }  
  var institution_id = $("#instsel").attr("institution_id");
  var abbreviation_name = $("#inst").val();
  if (abbreviation_name.length==0) institution_id=0;
  
	$.post("php/loadform.php", {utype:context.utype, cuuid:context.uuid, cinst:context.instid, uuid:author_id, inst:institution_id, shift_role:shift_role, oper: "author"}, function(data) 
	{		
    if (data.err_no){tit = "Error ("+data.err_no+")"; displayMessage(tit,data.result);}
    else
    {
      $("#showcase").html(data.val);
      $("#topcase").html("Profile: "+name);
      $.getScript("js/jqform.js");
    }
  },"json");      
});

$("#profile").click(function(){
	$.post("php/loadform.php", {utype:context.utype, cuuid:context.uuid, cinst:context.instid, uuid:context.uuid, inst:context.instid, shift_role: context.utype, oper: "author"}, function(data) 
	{		
    if (data.err_no){tit = "Error ("+data.err_no+")"; displayMessage(tit,data.result);}
    else
    {
      $("#showcase").html(data.val);
      $("#topcase").html("Profile: "+context.user);
      $.getScript("js/jqform.js");
    }
  },"json");    
});
  
//start texts loading 
$("#rules").click(function(){$("#showcase").load('texts/rules.htm'); $("#topcase").html("BES-III Shift Rules");});

$("#instructions").click(function()
{
  var atype = new Array("shifter","instrep","manager");
  if (context.utype==SHIFTER) $("#showcase").load('texts/instruction4shifter.htm');
  else if (context.utype==INSTREP) $("#showcase").load('texts/instruction4instrep.htm');
  else if (context.utype==MANAGER) $("#showcase").load('texts/instruction4manager.htm');
  
  var stype=" ("+atype[context.utype]+" version)";
  $("#topcase").html("Help on BES-III Shift Software"+stype);    
    
});
//end texts loading

UpdateShowData();
show_timer = setInterval(UpdateShowData, 1000);

if (context.loggedin==1)
{
  if (context.scope=="m") {$("#datenav").removeClass("transpar"); $("#datenav").addClass("opaque"); ShowMonth();}
  else if (context.scope=="r") {$("#datenav").removeClass("opaque"); $("#datenav").addClass("transpar"); ShowRoundLast();}
}


});

//eof jqmain.js
