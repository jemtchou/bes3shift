//jqform.js

$(function(){

rexEmail=new RegExp("^[-a-z0-9!#$%&'*+/=?^_`{|}~]+(?:\\.[-a-z0-9!#$%&'*+/=?^_`{|}~]+)*@(?:[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])?\\.)*(?:aero|arpa|asia|biz|cat|com|coop|edu|gov|info|int|jobs|mil|mobi|museum|name|net|org|pro|tel|travel|[a-z][a-z])$","i");
warn_email="E-mail address syntax wrong";

$("button").button();
$("button").css("font-size","0.7em");
$(".radiosel").buttonset();

$(".fbutt").css("font-size","1.0em");

$(".adate").datepicker({changeMonth:true, changeYear:true, minDate: date_from, maxDate: "+0d", showOtherMonths:true, showWeek:true, firstDay:0});
$(".adater").datepicker({changeMonth:true, changeYear:true, minDate: date_from, maxDate: "+1Y", showOtherMonths:true, showWeek:true, firstDay:0});

$("#dialog-message").dialog({modal: true,autoOpen: false,buttons: {Ok: function() {$(this).dialog('close');}}});
function displayMessage(t,msg)
{
  $('#dialog-message').dialog("widget").css("font-size","0.7em");
  $("#dialog-message").dialog({title: t});
  $('#dialog-message').text(msg);
  $('#dialog-message').dialog('open');
}
function updateTips(t){var sv=$(".tips").text();$(".tips").text(t).addClass('ui-state-highlight'); setTimeout(function() {$(".tips").removeClass('ui-state-highlight', 3000); $(".tips").text(sv);}, 3000);}
function checkLength(o,min,max){if ( o.val().length > max || o.val().length < min ) {o.addClass('ui-state-error'); updateTips("The entry field length: "+min+" ..."+max+"."); setTimeout(function() {o.removeClass('ui-state-error', 1500);}, 500);return false;} return true;}
function checkRegexp(o,regexp,msg) {if (!(regexp.test(o.val()))) {o.addClass('ui-state-error'); updateTips(msg); setTimeout(function() {o.removeClass('ui-state-error', 1500);}, 500); return false;} return true;}

//Institution select, edit
$( "#profile-institution" ).autocomplete({
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
    $("#profile-institution").val(ui.item.label);
    $("#pinstsel").attr("institution_id",ui.item.institution_id);
  }
});
$('#profile-institution').keypress(function(event) {if (event.which == '13') {event.preventDefault();/*$('#some_button').click(); */}});

//Author select, edit - for Institution profile
$( "#institution-contact_person" ).autocomplete({
  minLength: 2,
  source: function(request, response) 
  {
    var institution_id = $("#instsel").attr("institution_id");  //id==0 - any
    
		$.post("php/autocomplete.php", {term:request.term, inst:institution_id, oper:"author"}, function(data) 
		{		
      if (data.err_no) {response("");} //tit = "Error ("+data.err_no+")"; displayMessage(tit,data.result);
      else {response(data.val);}
    },"json");
  },
  select: function(event, ui) 
  {
    $("#institution-contact_person").val(ui.item.label);
    $("#institution-contact_person").attr("author_id",ui.item.author_id);
  }
});
$('#institution-contact_person').keypress(function(event) {if (event.which == '13') {event.preventDefault();}});


function informUser(to,subject,body)
{
	$.post("php/smtp_mail.php", {to:to, subject:subject, body:body}, function(data) 
	{		
    if (data.err_no) {tit = "Error ("+data.err_no+")"; displayMessage(tit,data.result);}
    else {displayMessage("Success","User notification sent");}
  },"json");  
}

$("#profile_save").click(function()
{
  var now = new Date(); now=noHMS(now);
  var profile = {};
  profile.uuid=$("#attribtes").attr("uuid");
  profile.utype=$("#attribtes").attr("utype");
  profile.creator_id=$("#attribtes").attr("cuuid");
  profile.create_time=Math.round((now.getTime())/1000);
  profile.name=$("#profile-name").val();
//  profile.family_name=$("#profile-family_name").val();
//  profile.initials=$("#profile-initials").val();
  profile.institution_id=$("#pinstsel").attr("institution_id");
  profile.institution=$("#profile-institution").val();
  trimstring(profile.institution);
  if (profile.institution.length==0) {$("#pinstsel").attr("institution_id",0); profile.institution_id=0;}
  profile.chinese_name=$("#profile-chinese_name").val();
  profile.email=$("#profile-email").val();
//  profile.office=$("#profile-office").val();
//  profile.telephone=$("#profile-telephone").val();
//  profile.mobile_telephone=$("#profile-mobile_telephone").val();
  profile.position=$("#profile-position").val();
//  profile.bes3_service=$("#profile-bes3_service").val();
//  profile.bes3_interest=$("#profile-bes3_interest").val();
//  profile.foot_note=$("#profile-foot_note").val();  
  profile.chinese_speaking=$("#profile-chinese_speaking").find("input[id*='lang']:checked").val(); // 0/1
//  profile.password=$("#profile-password").val();  
//  profile.password2=$("#profile-password2").val();
//  trimstring(profile.password);  
//  trimstring(profile.password2);  

  if (context.utype==MANAGER)
  { 
 /*  var d1 = $("#profile-join_bes3_time").datepicker("getDate");
   profile.join_bes3_time =(d1)? (d1.getTime()/1000) : 0;
   var d2 = $("#profile-leave_bes3_time").datepicker("getDate");
   profile.leave_bes3_time =(d2)? (d2.getTime()/1000) : 0;
   var d3 = $("#profile-join_author_list_time").datepicker("getDate");
   profile.join_author_list_time =(d3)? (d3.getTime()/1000) : 0;
   var d4 = $("#profile-leave_author_list_time").datepicker("getDate");
   profile.leave_author_list_time =(d4)? (d4.getTime()/1000) : 0;
   
   profile.is_author=$("#profile-is_author").find("input[id*='author']:checked").val();
*/   //profile.shift_role=$("#profile-shift_role").find("input[id*='role']:checked").val();
   profile.shift_role=0; //default: shifter, instrep - assigned via 'Institution' editor, manager - flag in db by admin.
  }
  
  //check up the necessary fields:
  if (checkLength($("#profile-name"),5,50) == false) return;
//  if (checkLength($("#profile-family_name"),2,25) == false) return;
//  if (checkLength($("#profile-initials"),1,5) == false) return;
  if (checkRegexp($("#profile-email"),rexEmail,warn_email) == false) return;
  if (checkLength($("#profile-institution"),2,16) == false) {updateTips("See the roster for institution abbreviations."); return;}
  if (profile.password!=profile.password2) {updateTips("Password not confirmed (not identical)."); return;}
  
	$.post("php/porter.php", {oper:"profile_save", profile: profile}, function(data) 
	{		
    if (data.err_no) {tit = "Error ("+data.err_no+")"; displayMessage(tit,data.result);}
    else 
    {
       var sNotice="";
      if ((parseInt(profile.uuid))!=context.uuid)
      {
          informUser(data.val.to, data.val.subject, data.val.body);
          sNotice=". User notification sent.";
      }
      displayMessage("Success","Data saved"+sNotice);
      window.location=sweet_home;
    }
  },"json");  
});

//instrep + manager:

$("#institution_save").click(function()
{
  var institution = {};
  institution.full_name=$("#institution-full_name").val();
  institution.institution_id=$("#institution-full_name").attr("institution_id");
  institution.abbreviation_name=$("#institution-abbreviation_name").val();
  institution.address1=$("#institution-address1").val();
//  institution.continent=$("#institution-continent").val();
//  institution.description=$("#institution-description").val();
  institution.location=$("#institution-location").find("input[id*='location']:checked").val();

  institution.contact_person=$("#institution-contact_person").val();
  trimstring(institution.contact_person);
  if (institution.contact_person.length==0) institution.contact_person_id=0;
  else institution.contact_person_id=$("#institution-contact_person").attr("author_id");

  //for new record:
  institution.creator_id=context.uuid;
  //institution.create_time=Math.round((now.getTime())/1000);

  if (context.utype==MANAGER)
  { 
  /* var d1 = $("#institution-join_bes3_time").datepicker("getDate");
   institution.join_bes3_time =(d1)? (d1.getTime()/1000) : 0;
   var d2 = $("#institution-leave_bes3_time").datepicker("getDate");
   institution.leave_bes3_time =(d2)? (d2.getTime()/1000) : 0;   
  */
  }
  else
  {
    //TO DO: checkup as an instrep!!
   // institution.join_bes3_time=$("#institution-join_bes3_time").val();
   // institution.leave_bes3_time=$("#institution-leave_bes3_time").val();
  }
  
  //check up the necessary fields:
  if (checkLength($("#institution-full_name"),5,250) == false) return;
  if (checkLength($("#institution-abbreviation_name"),2,16) == false) return;
  if (checkLength($("#institution-address1"),10,250) == false) return;
//  if (checkLength($("#institution-continent"),4,25) == false) return;
  if (institution.join_bes3_time==0) {updateTips("Join BES-III time is not specified."); return;}
  if (institution.location==INSTLOC_UNDEF) {updateTips("Location must be specified."); return;}
    
	$.post("php/round.php", {oper:"institution_save", institution: institution}, function(data) 
	{		
    if (data.err_no) {tit = "Error ("+data.err_no+")"; displayMessage(tit,data.result);}
    else 
    {
      var sNotice="";
      if (data.val.sendto > 0)
      {
      	 if (data.val.sendto < 3) //send to a single person
           informUser(data.val.to, data.val.subject, data.val.body);
      	 else //if (data.val.sendto==3) //send to both
      	  {
           informUser(data.val.to, data.val.subject, data.val.body);
           informUser(data.val.to2, data.val.subject, data.val.body2);
           alert("The previous and new contact persons are informed on the change.");  //ok here
          //displayMessage("Success","The new contact person informed on the change."); // :((
          }
         sNotice=". Contact person notification sent.";
      }
      displayMessage("Success","Data saved"+sNotice);
      window.location=sweet_home;
    }
  },"json");
});


$("#round_save").click(function()
{
  var now= new Date(); now=noHMS(now);
  var nows=now.getTime()/1000;
  var shiftround = {};
  shiftround.shiftround_name=$("#round-shiftround_name").val();
  shiftround.shiftround_id=$("#round-shiftround_name").attr("shiftround_id");
  shiftround.shiftround_status=$("#round-shiftround_name").attr("shiftround_status");
  
  var d = $("#round-start_date").datepicker("getDate");
  shiftround.start_date =(d)? (d.getTime()/1000) : 0;
  d = $("#round-end_date").datepicker("getDate");
  shiftround.end_date =(d)? (d.getTime()/1000) : 0;

  //check up the necessary fields:
  if (checkLength($("#round-shiftround_name"),4,32) == false) return;
  if (!(shiftround.start_date==0 && shiftround.end_date==0)) //if editable
  {
    if (shiftround.start_date >= shiftround.end_date) {updateTips("Start date >= end date."); return;}
    if ((shiftround.shiftround_id==0) && (nows >=shiftround.start_date && nows <=shiftround.end_date)) {updateTips("New shift round overlaps with the current one!"); return;}
  }  
	$.post("php/round.php", {oper:"round_save", shiftround: shiftround}, function(data) 
	{		
    if (data.err_no) 
    {
      if (data.err_no==403) tit="Notice";
      else tit = "Error ("+data.err_no+")"; 
      displayMessage(tit,data.result);
      }
    else 
    {
      displayMessage("Success","Data saved");
      window.location=sweet_home;
    }
  },"json");    
});

$("#round_open").click(function()
{
  var shiftround=$("#round-shiftround_name").attr("shiftround_id");
	$.post("php/round.php", {oper:"round_open", shiftround: shiftround}, function(data) 
	{		
    if (data.err_no) 
      displayMessage("Error",data.result);
    else 
    {
      displayMessage("Success",data.result);
      window.location=sweet_home;
    }
  },"json");    
});

$("#round_close").click(function()
{
  var shiftround=$("#round-shiftround_name").attr("shiftround_id");
	$.post("php/round.php", {oper:"round_close", shiftround: shiftround}, function(data) 
	{		
    if (data.err_no) 
      displayMessage("Error",data.result);
    else 
    {
      displayMessage("Success",data.result);
      window.location=sweet_home;
    }
  },"json");    
});

$("#holiday_save").click(function()
{
  var holidays = new Array();
  var n=0, d, day, dname;
  var holilist = $("tr[id|='holiday']");
  holilist.each(function(index) 
  {
    d = $(this).find('.adater').val();
    day=new Date(d); day=noHMS(day);
    dname=$(this).find('.holiname').val();
    if (d && d.length > 0)
    {
     holidays[n]=new Object();
     holidays[n].date=day.getTime()/1000;
     holidays[n].holiday=dname;
     n++;
    }
  });  
	$.post("php/round.php", {oper:"holiday_save", holidays: holidays}, function(data) 
	{		
    if (data.err_no) {tit = "Error ("+data.err_no+")"; displayMessage(tit,data.result);}
    else {displayMessage("Success","Data saved");window.location=sweet_home;}
  },"json");
});

$("#cancel").click(function(){window.location=sweet_home;});

$("#cancel_reject").click(function(){$("#conform").html("");});

$("#askfor_reject").click(function(){

  var request = {};
  request.author_id1=$("#shift1").attr("author_id1");
  request.date1=$("#shift1").attr("date1");
  request.dtime1=$("#shift1").attr("dtime1");
  request.type1=$("#shift1").attr("type1");
  request.days1=$("#shift1").attr("days1");
  request.user1=$("#shift1").attr("user1");  
  request.author_id2=0; //duty manager

	$.post("php/shifter.php", {oper:"confreject", request: request}, function(data) 
	{		
    if (data.err_no) {tit = "Error ("+data.err_no+")"; displayMessage(tit,data.result);}
    else 
    {
      informUser(data.val.to, data.val.subject, data.val.body);
      $("#conform").html(""); 
      displayMessage("Success","Request registered.");
    }
  },"json");
  
});

$("#cancel_exchange").click(function(){$("#conform").html(""); $("#conform2").html("");});

$("#askfor_exchange").click(function(){

  var request = {};
  request.author_id1=$("#shift1").attr("author_id1");
  request.date1=$("#shift1").attr("date1");
  request.dtime1=$("#shift1").attr("dtime1");
  request.type1=$("#shift1").attr("type1");
  request.days1=$("#shift1").attr("days1");
  request.user1=$("#shift1").attr("user1");  

  request.author_id2=$("#shift2").attr("author_id2");
  request.date2=$("#shift2").attr("date2");
  request.dtime2=$("#shift2").attr("dtime2");
  request.type2=$("#shift2").attr("type2");
  request.days2=$("#shift2").attr("days2");
  request.user2=$("#shift2").attr("user2");  

  if ((request.type1==RUNCOORDINATOR) && (request.type2!=RUNCOORDINATOR))
  {
  	displayMessage("Notice","Run coordinators may exchange blocks with each other, but not with shifters!");
  	return;
  }

	$.post("php/shifter.php", {oper:"confexchange", request: request}, function(data) 
	{		
    if (data.err_no) 
    {
    	tit = "Error ("+data.err_no+")"; 
    	displayMessage(tit,data.result);
      $("#conform2").html("");
    }
    else 
    {
      informUser(data.val.to, data.val.subject, data.val.body);
      $("#conform").html(""); 
      $("#conform2").html("");
      displayMessage("Success","Request registered.");
      window.location=sweet_home;      
      //window.location.reload(true);
    }
  },"json");
});


//init holiday list datepickers dates range
var table=$("#holiday_list");
if (table)
{
  var from = table.attr("from"); var datefrom= new Date(from);
  var upto = table.attr("upto"); var dateupto= new Date(upto);  
  $('.holidate').each(function(index) 
  {
     $(this).datepicker("option", {minDate: datefrom, maxDate: dateupto});
  });  
}

});

//eof jqform.js
