//common.js

//<COOKIES>
var cookie_prefix="bes3shift_";
var today = new Date();
var exp_day = new Date(today.getTime() +86400000);
var exp_month = new Date(today.getTime() +30*86400000);
var exp_year = new Date(today.getTime() +31536000000);	

var date_from = new Date(2006,1-1,1);//the date of 1st shift (month -> 0..11)

function my_cookieson() {return (navigator.cookieEnabled)? true : false;}
function my_delcookie(sName) {document.cookie = cookie_prefix + sName + "=undefined; expires=Fri, 31 Dec 1999 23:59:59 GMT;";}
function my_setcookie(sName, value, expires, path, domain, secure)
{
  document.cookie = cookie_prefix + sName + "=" + escape(value) + 
  ((expires) ? "; expires=" + expires.toGMTString() : "") +
  ((path) ? "; path=" + path : "") +
  ((domain) ? "; domain=" + domain : "") +
  ((secure) ? "; secure=" + secure : "");  
}
function my_getcookie(sName) 
{  
  var name=cookie_prefix + sName
  var aCookie = document.cookie.split("; ");
  for (var i=0; i < aCookie.length; i++)
  {   
    var aCrumb = aCookie[i].split("=");
    if (name == aCrumb[0]) return unescape(aCrumb[1]);
  }
  return null;
}
//</COOKIES>

//<CONTEXT>
var context = new Object();
//user
context.utype=my_getcookie("utype"); if (!context.utype) {context.utype=666; my_setcookie("utype",context.utype,exp_year,"/");}
context.user=my_getcookie("user"); if (!context.user) {context.user="guest"; my_setcookie("user",context.user,exp_year,"/");}
context.userc=my_getcookie("userc"); if (!context.userc) {context.userc=""; my_setcookie("userc",context.userc,exp_year,"/");}
context.uuid=my_getcookie("uuid"); if (!context.uuid) {context.uuid=0; my_setcookie("uuid",context.uuid,exp_year,"/");}
context.instid=my_getcookie("instid"); if (!context.instid) {context.instid=0; my_setcookie("instid",context.instid,exp_year,"/");}
context.loggedin=my_getcookie("loggedin"); if (!context.loggedin) {context.loggedin=0; my_setcookie("loggedin",context.loggedin,exp_year,"/");}

context.scope=my_getcookie("scope"); if (!context.scope) {context.scope="m"; my_setcookie("scope",context.scope,exp_year,"/");}
context.msmonth=my_getcookie("msmonth"); if (!context.msmonth) {context.msmonth=0; my_setcookie("msmonth",context.msmonth,exp_month,"/");}
context.shown_1=my_getcookie("shown_1"); if (!context.shown_1) {context.shown_1=0; my_setcookie("shown_1",context.shown_1,exp_day,"/");} //No shift round selected, assuming the latest one
//</CONTEXT>

//<FUNCTIONS>
function getURLParameter(name){return unescape((RegExp(name + '=' +'(.+?)(&|$)').exec(location.search)||[,null])[1]);}
function trimstring(str){ if (str.trim)  return str.trim(); else  return str.replace(/(^\s*)|(\s*$)/g, "");} //find and remove spaces from left and right hand side of string  
function noHMS(dobj){return new Date(dobj.getFullYear(),dobj.getMonth(),dobj.getDate());}
//</FUNCTIONS>

//<MISC>
var dtnames = new Array('all','night','day','evening');
var stypes = new Array("","ordinary","chief","coordinator");
var shift_role = new Array("shifter","instrep","manager");
var alocations = new Array("Beijing","non-Beijing","outside","all");
var roundstatus = new Array("new","opened","closed");
//<MISC>

//eof common.js