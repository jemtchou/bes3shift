<?php
//rostat.php: handles rosters and statistics. Following is the list of functions currently supported:
header("Content-type: text/html; charset=utf-8");

require "config.inc.php";
require "common.php";

$oper=$_POST['oper'];
if (!isset($oper)) {echo "<script language='JavaScript' type='text/javascript'>window.location='$sweet_home';</script>";	 return;}

require "connect.inc.php";
if (!$link || ($err_no > 0)) {$ret = array('err_no' => $err_no, 'result' => $result);  echo json_encode($ret);  return;} //mySQL error

$val=null; $err_no=ERR_NONE; $result="ok"; $ret=array(); //init default return vars
$zerodate="0000-00-00";

function makeuparr($arr)
{
  $akeys=array_keys($arr);
  $avalues=array_values($arr);
  $len=count($akeys);
  if (count($avalues)!=$len) return null;
  $aret=array();
  for($i=0; $i < $len; $i++) {$key=$akeys[$i]; $aret[$key]=$avalues[$i];}
  return $aret;
}

if ($oper=="roster")
{
  $institution_id=$_POST['inst'];
  $orderby=$_POST['orderby'];
  $orderdir=$_POST['orderdir']; 
  $shiftround_id=$_POST['shiftround_id'];
  $sort="ORDER BY $orderby $orderdir";
  $runclause="";
  if ($shiftround_id >0 ) 
   {
     $shiftquery="SELECT * FROM ShiftRound WHERE shiftround_id=$shiftround_id LIMIT 1";
     $res=mysqli_query($link, $shiftquery);
     $rows=mysqli_num_rows($res);
     if ($rows)
     { 
        $row = mysqli_fetch_array($res, MYSQLI_ASSOC);
        $from = $row['start_date'];
        $upto = $row['end_date']; 
     }
     
     $runclause=" AND join_bes3_time<'$upto' AND (leave_bes3_time>'$from' OR leave_bes3_time='$zerodate')";
   }
  if ($institution_id > 0) $query="SELECT Author.author_id,Author.institution_id,Author.initials,CONVERT(CONVERT(Author.name USING binary) USING utf8) AS name,CONVERT(CONVERT(Author.family_name USING binary) USING utf8) AS family_name,Author.email,Author.join_bes3_time,Author.leave_bes3_time,Author.leave_bes3,Author.chinese_speaking,Author.canbechief,Author.chinese_name_unicode FROM Author WHERE institution_id=$institution_id $runclause"; //Authors
  else  $query="SELECT Institution.full_name,Institution.abbreviation_name,Institution.address1,Author.name FROM Author,Institution WHERE Author.author_id=Institution.contact_person_id "; //Institutions    
  $query=$query." ".$sort;
  $res=mysqli_query($link, $query);
  $rows=mysqli_num_rows($res);
  if (!$rows) {$err_no=ERR_MYSQL_R; $result="No data found!. Table corrupted or truncated.";}
  else {while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) $val[]=$row;}
  }//end ROSTER

else if ($oper=="stats")
{
  $request=$_POST['request'];
  $orderby=$_POST['orderby'];
  $orderdir=$_POST['orderdir']; 
  $sort = (strlen($orderby) > 0)? "ORDER BY $orderby $orderdir" : "";	//output table sorting

  //------------------------------------------------------ info
  $bSingleRound=($request['shiftround_id']==0)? false : true;
  $bSingleInst=($request['institution_id']==0)? false : true;
  $bSingleAuthor=($request['author_id']==0)? false : true;

  //arrays contain all the needed info, including the resulting totals
  $around=array(); 
  $ainst=array();	
  $aauthor=array();	//all authors for a given institution (if institution_id!=0)

  //arrays contain output totals
  $around_out=array(); 
  $ainst_out=array();	
  $aauthor_out=array();	//all authors for a given institution (if institution_id!=0)

  $around_out['shifts']=0;
  $around_out['points']=0;
  $around_out['manpower']=0;  
  $around_out['authors']=0;
  
  //GET ROUNDS initial info:
  if ($err_no==ERR_NONE)
  {
    if ($bSingleRound==false) $fclause="WHERE shiftround_status >0 ORDER BY start_date ASC";
    else $fclause="WHERE shiftround_id={$request[shiftround_id]} AND shiftround_status >= 0 LIMIT 1";
    $query="SELECT * FROM ShiftRound $fclause";
    $res=mysqli_query($link, $query);
    $rows=mysqli_num_rows($res);
    if (!$rows) {$err_no=ERR_MYSQL_R; $result="No opened or closed rounds found!";}
    else {while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) $around[]=$row;}

    //FOR EACH ROUND: get holidays info and oncalls + calc shifts and points
    if ($err_no==ERR_NONE)
    {
      $points_per_day=2*(POINTS_NIGHT + POINTS_DAY + POINTS_EVENING) + POINTS_COORDINATOR;
      $points_per_holiday=SHIFTS_PER_DAY*POINTS_HOLIDAY;
      for($n=0; $n < count($around); $n++)
      {
        $from=$around[$n]['start_date'];
        $upto=$around[$n]['end_date'];
 	$roundid=$around[$n]['shiftround_id'];      
      //GET HOLIDAYS list for this round if any    
        $query="SELECT * FROM ShiftHolidays WHERE ShiftHolidays.date >= '$from' AND ShiftHolidays.date <= '$upto' ORDER BY date ASC";
        $res=mysqli_query($link, $query);
        $rows=mysqli_num_rows($res);
        if ($rows > 0){while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {$round[$n]['holidays'][] = $row['date'];}}
        else $round[$n]['holidays']=array(); //not set
        if ($err_no!=ERR_NONE) break;
       // GET ONCALL SHIFTS from this round
        $query="SELECT sum(oncall_points) AS oncall FROM OncallShifts WHERE shiftround_id='$roundid'";
        $res=mysqli_query($link, $query);
        $rows=mysqli_num_rows($res);
        if ($rows > 0){while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {$round[$n]['oncall'] = $row['oncall'];}}
        else $round[$n]['oncall']=0; //not set
        if ($err_no!=ERR_NONE) break;

       //CALC SHIFTS and POINTS for each period        
       $days=round((strtotime($upto)-strtotime($from))/86400)+1; //add 1 since dates are inclusive
       $holidays=count($round[$n]['holidays']);	//==$rows
       $oncall=$round[$n]['oncall'];
       $round[$n]['shifts']=$days*SHIFTS_PER_DAY; 
       $round[$n]['points']=$points_per_day*$days + $points_per_holiday*$holidays+$oncall;
       $around_out['shifts']+=$round[$n]['shifts'];
       $around_out['points']+=$round[$n]['points'];
      } //for each round
    }
  }
  
  //GET AUTHORS initial info:
  if (($err_no==ERR_NONE) && ($bSingleAuthor==true || $bSingleInst==true))
  {
    $authclause=" case when is_author='yes' then 'A' when is_author='no' then '' end as auth ";
    if ($bSingleAuthor==true) $fclause="WHERE author_id={$request[author_id]} LIMIT 1";
    else $fclause="WHERE institution_id={$request[institution_id]} ORDER BY author_id ASC";
    $query="SELECT Author.author_id,Author.institution_id,Author.initials,CONVERT(CONVERT(Author.name USING binary) USING utf8) AS name,CONVERT(CONVERT(Author.family_name USING binary) USING utf8) AS family_name,Author.email,Author.join_bes3_time,Author.leave_bes3_time,Author.leave_bes3,Author.chinese_speaking,Author.canbechief,Author.chinese_name_unicode, $authclause FROM Author $fclause";
    $res=mysqli_query($link, $query);
    $rows=mysqli_num_rows($res);
    if (!$rows) {
//$err_no=ERR_MYSQL_R; $result="Statistics is calculated for authors only!.";
    }
    else {while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) $aauthor[]=$row;}
  }

  //GET INSTITUTIONS initial info:
  if ($err_no==ERR_NONE && $bSingleAuthor==false)
  {
    if ($bSingleInst==false) $fclause="ORDER BY join_bes3_time ASC";
    else $fclause="WHERE institution_id={$request[institution_id]} LIMIT 1";
    $query="SELECT * FROM Institution $fclause";
    $res=mysqli_query($link, $query);
    $rows=mysqli_num_rows($res);
    if (!$rows) {$err_no=ERR_MYSQL_R; $result="No data found!. Table corrupted or truncated.";}
    else {while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) $ainst[]=$row;}
    
    //FOR EACH INSITUTION and each ROUND: get manpower (number of authors): 1->whole period, fraction - if  from<since, till<upto            
    if ($err_no==ERR_NONE)
    {
      $inst_num=count($ainst); 
      $round_num=count($around);
      for($i=0; $i < $inst_num; $i++)
      {
        $institution_id=$ainst[$i]['institution_id'];
        $ainst[$i]['manpower']=0;
        $ainst[$i]['author']=0; // current number of authors
	$query="SELECT COUNT(author_id) as authnb FROM Author WHERE is_author='yes' AND institution_id=$institution_id AND (join_author_list_time='0000-00-00' OR join_author_list_time < NOW()) AND (leave_author_list_time ='0000-00-00' OR leave_author_list_time >NOW()) LIMIT 1";
          $res=mysqli_query($link, $query);
          $rows=mysqli_num_rows($res);
          if ($rows > 0)
          {
	    $row = mysqli_fetch_array($res, MYSQLI_ASSOC);
	    $ainst[$i]['author']=$row['authnb'];
          }

        for($n=0; $n < $round_num; $n++)
        {
          $from=$around[$n]['start_date']; $tfrom=strtotime($from);
          $upto=$around[$n]['end_date'];   $tupto=strtotime($upto);          
        //COUNT manpower (all those, who are/were in the authors_list within the round span) :
          $query="SELECT author_id,join_author_list_time,leave_author_list_time FROM Author WHERE is_author='yes' AND institution_id=$institution_id AND (join_author_list_time='0000-00-00' OR join_author_list_time < '$upto') AND (leave_author_list_time ='0000-00-00' OR leave_author_list_time >'$from') ORDER BY join_author_list_time ASC";
          $res=mysqli_query($link, $query);
          $rows=mysqli_num_rows($res);
          if ($rows > 0)
          {
          	$manpower=0.0;
          	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) 
          	{
          		$since=$row['join_author_list_time'];
          		$till=$row['leave_author_list_time'];
          		if ($since!=$zerodate)	//if equal - that's a managers fault
          		{
          			$tsince=strtotime($since); if ($tsince <= $tfrom) $tsince = $tfrom; 
          		} else $tsince = $tfrom; 
          		
          		if ($till!=$zerodate)
          		{
          			$ttill=strtotime($till); if ($ttill >= $tupto) $ttill = $tupto;
          		} else $ttill = $tupto;
          		if ($tsince==$tfrom && $ttill==$tupto || true) $manpower+=1.0; //full round period presence
          		else $manpower+=1.0*($ttill - $tsince)/($tupto-$tfrom);	//partial period presence          		
		}
        //  	$ainst[$i]['manpower'][$n]=$manpower;	//store manpower for this round period for this institution
          	$ainst[$i]['manpower']+=$manpower;	//store manpower for all round periods for this institution
                $around_out['manpower']+=$manpower;  //store manpower for all round periods (one or all) from all institutions
          }
          else
          {
          //	 $ainst[$i]['manpower'][$n]=0.0; //not set
          }

	// take into account pubcom privilege
        $roundid = $around[$n]['shiftround_id'];
        $query = "SELECT count(*) AS pubcom FROM PubcomShifts WHERE shift_round='$roundid'";
        $res = mysqli_query($link, $query);
        $rows = mysqli_num_rows($res);
        if ($rows > 0)
        {
           $row = mysqli_fetch_array($res, MYSQLI_ASSOC);
      //     $ainst[$i]['manpower'] -= $row['pubcom'];
      //     $around_out['manpower'] -= $row['pubcom'];
        }

         if ($err_no!=ERR_NONE) break;
        } //for each round
      }//for each institution      
    }//if no err

    //FOR EACH INSITUTION and each ROUND: get ONCALL points 07.06.2012 15:33:12
    if ($err_no==ERR_NONE)
    {
      $inst_num=count($ainst); 
      $round_num=count($around);
      for($i=0; $i < $inst_num; $i++)
      {
        $institution_id=$ainst[$i]['institution_id'];
        $ainst[$i]['oncall_points']=0.0;
        for($n=0; $n < $round_num; $n++)
        {
          $shiftround_id=$around[$n]['shiftround_id'];
          $query="SELECT oncall_points FROM OncallShifts WHERE institution_id=$institution_id AND shiftround_id=$shiftround_id LIMIT 1";
          $res=mysqli_query($link, $query);
          $rows=mysqli_num_rows($res);
          if ($rows > 0)
          {
            $row = mysqli_fetch_array($res, MYSQLI_ASSOC);
            $ainst[$i]['oncall_points'] += $row['oncall_points'];
          }
        }
      }
    }//end ONCALL stuff
    
  }// if not one author

  //------------------------------------------------------ stats
  
  //GET SINGLE AUTHOR stats:
  if ($err_no==ERR_NONE && $bSingleAuthor==true)
  {
   // $since=$aauthor[0]['join_author_list_time'];        
    $since=$aauthor[0]['join_bes3_time'];
    if ($bSingleRound==false) $fclause="WHERE author_id={$request[author_id]} AND Shifts.date >= '$since' ORDER BY Shifts.date ASC";
    else 
    {
      $from=$around[0]['start_date'];
      $upto=$around[0]['end_date'];      
    	$fclause="WHERE author_id={$request[author_id]} AND (Shifts.date >= '$from') AND (Shifts.date <= '$upto') AND Shifts.date >= '$since' ORDER BY Shifts.date ASC";
    }
    $query="SELECT date,dtime,type,score FROM Shifts $fclause";
    $res=mysqli_query($link, $query);
    $rows=mysqli_num_rows($res);
    if (!$rows) {
//$err_no=ERR_MYSQL_R; $result="No data found!"; 
$val[]=array($zerodate,0,0,0);}
    else {while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) $val[]=$row;} //{date,dtime,type,score}
  }    
  
  //GET SINGLE INSTITUTION stats:
  
  if (($err_no==ERR_NONE) && ($bSingleAuthor==false) && ($bSingleInst==true))
  {
  	$institution_id=$ainst[0]['institution_id'];
    if ($bSingleRound==false) 
    	$fclause="WHERE (Author.is_author='yes') AND (Author.institution_id=$institution_id)";
    else 
    {
      $from=$around[0]['start_date'];
      $upto=$around[0]['end_date'];      
//    	$fclause="WHERE (Shifts.date >= '$from') AND (Shifts.date <= '$upto') AND (Author.is_author='yes') AND (Author.institution_id=$institution_id) AND Author.institution_id=Shifts.institution_id";
$fclause="WHERE (Shifts.date >= '$from') AND (Shifts.date <= '$upto') AND Author.author_id=Shifts.author_id AND Shifts.institution_id=$institution_id";
    }
    $query="SELECT CONVERT(CONVERT(Author.name USING binary) USING utf8) AS name, COUNT(Shifts.score) AS shiftssum, SUM(Shifts.score) AS pointssum FROM Author LEFT JOIN Shifts ON (Author.author_id=Shifts.author_id) $fclause GROUP BY Author.author_id ORDER BY Author.join_author_list_time ASC";
    $res=mysqli_query($link, $query);
    $rows=mysqli_num_rows($res);
    if (!$rows) {
//$err_no=ERR_MYSQL_R; $result="No data found!";
     $val[]=array("noname",0,0);
    }
    else {while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) $val[]=$row;} //{name,shiftssum,pointssum}
  }
  
  //GET ALL INSTITUTIONS stats:    
  if ($err_no==ERR_NONE && $bSingleAuthor==false && $bSingleInst==false)
  {
    if ($bSingleRound==false)
    { 
    	$fclause=" WHERE (Shifts.date >= '0000-00-00') ";//"WHERE (Author.is_author='yes')";
    }
    else 
    {
      $from=$around[0]['start_date'];
      $upto=$around[0]['end_date'];      
    	$fclause=" WHERE (Shifts.date >= '$from') AND (Shifts.date <= '$upto') ";
    }
    $ainstall=array();
    $ainstnight=array();
/*
    $query="SELECT Institution.institution_id FROM Institution ORDER BY Institution.join_bes3_time ASC";
    $res=mysqli_query($link, $query);
    $rows=mysqli_num_rows($res);
    if (!$rows) {$err_no=ERR_MYSQL_R; $result="No data found in table Institutions!";}
    else
    {
	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {$ainstall[]=array('institution_id'=>$row['institution_id'],'shiftssum'=>0,'pointssum'=>0);} // institution_id
    }
*/
    $query = "SELECT Institution.institution_id, COUNT(Shifts.score) AS shiftssum, SUM(Shifts.score) AS pointssum FROM Institution LEFT JOIN Shifts ON Shifts.institution_id=Institution.institution_id $fclause GROUP BY Institution.institution_id ORDER BY Institution.join_bes3_time ASC";

//    $query="SELECT Institution.institution_id, COUNT(Shifts.score) AS shiftssum, SUM(Shifts.score) AS pointssum FROM Institution LEFT JOIN (Author,Shifts) ON (Author.institution_id=Institution.institution_id AND Shifts.author_id=Author.author_id) $fclause GROUP BY Institution.institution_id ORDER BY Institution.join_bes3_time ASC";
    $res=mysqli_query($link, $query);
    $rows=mysqli_num_rows($res);
    if (!$rows) {
      //$err_no=ERR_MYSQL_R; $result="No DaTa found!";
      //$ainstall[]=array(14,0,0);
    }
    else 
    {
    	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {$ainstall[]=$row; }//{institution_id,shiftssum,pointssum}

      //get NIGHT shifts:
      $andclause="AND Shifts.dtime=1 ";
      $query = "SELECT Institution.institution_id, COUNT(Shifts.score) AS shiftssum, SUM(Shifts.score) AS pointssum FROM Institution LEFT JOIN Shifts ON Shifts.institution_id=Institution.institution_id $fclause $andclause GROUP BY Institution.institution_id ORDER BY Institution.join_bes3_time ASC";
      // $err_no=ERR_MYSQL_R; $result=$query;
//      $query="SELECT Institution.institution_id, COUNT(Shifts.score) AS shiftssum, SUM(Shifts.score) AS pointssum FROM Institution LEFT JOIN (Author,Shifts) ON (Author.institution_id=Institution.institution_id AND Shifts.author_id=Author.author_id) $fclause $andclause GROUP BY Institution.institution_id ORDER BY Institution.join_bes3_time ASC";
      $res=mysqli_query($link, $query);
      $rows=mysqli_num_rows($res);
      if (!$rows) {
//$err_no=ERR_MYSQL_R; $result="No data found!";
    //  $ainstnight[]=array(14,0,0);
}
    	else while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {$ainstnight[]=$row;}//{institution_id,shiftssum,pointssum}

      //FILL UP 'ainst' array with info:
      for($i=0; $i < count($ainst); $i++)
      {
        $ainst[$i]['shiftssum']=0;
        $ainst[$i]['pointssum']=0.0;
        for ($j=0; $j < count($ainstall); $j++) //{institution_id,shiftssum,pointssum}
        {
          if ($ainstall[$j]['institution_id']==$ainst[$i]['institution_id'])
          {
            $ainst[$i]['shiftssum']=$ainstall[$j]['shiftssum'];
            $ainst[$i]['pointssum']=$ainstall[$j]['pointssum'];
            break;
          }
        }
      }
      //CALCULATE nightshiftspc:
      for($i=0; $i < count($ainst); $i++)
      {
        $ainst[$i]['nightshiftspc']=0.0;
        for ($j=0; $j < count($ainstnight); $j++) //{institution_id,shiftssum,pointssum}
        {
          if ($ainstnight[$j]['institution_id']==$ainst[$i]['institution_id'])
          {
            if($ainst[$i]['shiftssum']>0){
                $ainst[$i]['nightshiftspc']=round(100.0*($ainstnight[$j]['shiftssum']/$ainst[$i]['shiftssum']),2);}
	    else {
             $ainst[$i]['nightshiftspc']=0;
            }
            break;
          }
        }
      }
    	//here we'got everyting needed to PREPARE the OUTPUT:
      //CALCULATE the rest for 'ainst' array using 'around_out':
    	//PREPARE the OUTPUT: (not to push the whole 'ainst' buffer to a client)
    	$val=array(); //{name,author_nb,shiftssum,nightpc,pointsreq,pointssum, pointsleft,compliance}  	  
      $around_out['pointsgained']=0.0;
      for($i=0; $i < count($ainst); $i++)
      {
        $ainst[$i]['pointssum'] += $ainst[$i]['oncall_points']; //ONCALL addition 07.06.2012 15:32:57
        $around_out['pointsgained']+=$ainst[$i]['pointssum'];
        $ainst[$i]['pointsreq']=round($around_out['points']*($ainst[$i]['manpower']/$around_out['manpower']),1);
        $ainst[$i]['pointsleft']=round($ainst[$i]['pointsreq']-$ainst[$i]['pointssum'],1);
        //if ($ainst[$i]['pointsleft'] < 0) $ainst[$i]['pointsleft']=0.0;
        $ainst[$i]['compliance']=round(100.0*($ainst[$i]['pointssum']/($ainst[$i]['pointsreq']+0.000001)),2);

        $val[]=array(
        'name'=> $ainst[$i]['abbreviation_name'], 
        'author' => $ainst[$i]['author'], 
        'shiftssum' => $ainst[$i]['shiftssum'], 
        'nightpc' => $ainst[$i]['nightshiftspc'], 
        'pointsreq'=> $ainst[$i]['pointsreq'], 
        'pointssum'=> round($ainst[$i]['pointssum'],1), 
        'pointsleft'=> $ainst[$i]['pointsleft'], 
        'compliance'=> $ainst[$i]['compliance']
        );
	if ($err_no==ERR_NONE) 
	$result=array('shifts' => $around_out['shifts'],'points'=> $around_out['points'], 'pointsgained'=> round($around_out['pointsgained'],1), 'manpower' => round($around_out['manpower'],1));        
       }
    }
  }  
}//if oper==stats

mysqli_close($link);
if (count($ret)==0) $ret = array('err_no' => $err_no, 'result' => $result, 'val' => $val);
echo json_encode($ret);
?>
