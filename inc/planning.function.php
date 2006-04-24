<?php
/*
 * @version $Id$
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------

 LICENSE

	This file is part of GLPI.

    GLPI is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    GLPI is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with GLPI; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 ------------------------------------------------------------------------
*/

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------


 
// FUNCTIONS Planning


function titleTrackingPlanning(){
           GLOBAL  $lang,$HTMLRel;
	     
	     echo "<div align='center'><table border='0'><tr><td>";
                echo "<img src=\"".$HTMLRel."pics/reservation.png\" alt='' title=''></td><td><b><span class='icon_sous_nav'>".$lang["planning"][3]."</span>";
		 echo "</b></td></tr></table>&nbsp;</div>";
}


function showTrackingPlanningForm($device_type,$id_device){

global $db,$cfg_glpi,$lang;

if (!haveRight("comment_all_ticket","1")) return false;

$query="select * from glpi_reservation_item where (device_type='$device_type' and id_device='$id_device')";

if ($result = $db->query($query)) {
		$numrows =  $db->numrows($result);
//echo "<form name='resa_form' method='post' action=".$cfg_glpi["root_doc"]."/reservation/index.php>";
echo "<a href=\"".$cfg_glpi["root_doc"]."/reservation/index.php?";
// Ajouter le mat�iel
if ($numrows==0){
echo "id_device=$id_device&amp;device_type=$device_type&amp;add=add\">".$lang["reservation"][7]."</a>";
}
// Supprimer le mat�iel
else {
echo "ID=".$db->result($result,0,"ID")."&amp;delete=delete\">".$lang["reservation"][6]."</a>";
}

}
}


function showAddPlanningTrackingForm($target,$fup,$planID=-1){
	global $lang,$HTMLRel,$cfg_glpi;

	if (!haveRight("comment_all_ticket","1")) return false;

	$split=split(":",$cfg_glpi["planning_begin"]);
	$global_begin=intval($split[0]);

	$split=split(":",$cfg_glpi["planning_end"]);
	$global_end=intval($split[0]);
	
	$planning= new PlanningTracking;

	if ($planID!=-1)
		$planning->getFromDB($planID);
	else {
		$planning->getEmpty();
		$planning->fields["begin"]=date("Y-m-d")." 12:00:00";
		$planning->fields["end"]=date("Y-m-d")." 13:00:00";
	}

	
	$begin=strtotime($planning->fields["begin"]);
	$end=strtotime($planning->fields["end"]);
	
	$begin_date=date("Y-m-d",$begin);
	$end_date=date("Y-m-d",$end);
	$begin_hour=date("H",$begin);
	$end_hour=date("H",$end);
	$begin_min=date("i",$begin);
	$end_min=date("i",$end);

	echo "<div align='center'><form method='post' name='form' action=\"$target\">";
	if ($planID!=-1)
	echo "<input type='hidden' name='ID' value='$planID'>";
	echo "<input type='hidden' name='referer' value='".$_SERVER['HTTP_REFERER']."'>";
	// Ajouter le job
	$followup=new Followup;
	$followup->getfromDB($fup);
	$j=new Job();
	$j->getFromDBwithData($followup->fields['tracking'],0);
	if ($planID==-1){
		if ($j->fields["assign"])
			$planning->fields["id_assign"]=$j->fields["assign"];
	}
	
	echo "<input type='hidden' name='id_followup' value='$fup'>";
	echo "<input type='hidden' name='id_tracking' value='".$followup->fields['tracking']."'>";

	echo "<table class='tab_cadre' cellpadding='2' width='600'>";
	echo "<tr><th colspan='2'><b>";
	echo $lang["planning"][7];
	echo "</b></th></tr>";
	echo "<tr class='tab_bg_1'><td>".$lang["planning"][8].":	</td>";
	echo "<td>";
	echo "<b>".$j->fields['contents']."</b>";
	echo "</td></tr>";
	echo "<tr class='tab_bg_1'><td>".$lang["planning"][10].":	</td>";
	echo "<td>";
	echo "<b>".$j->computername."</b>";
	echo "</td></tr>";

	if (!haveRight("comment_all_ticket","1"))
	echo "<input type='hidden' name='id_assign' value='".$_SESSION["glpiID"]."'>";
	else {
	echo "<tr class='tab_bg_2'><td>".$lang["planning"][9].":	</td>";
	echo "<td>";
	dropdownUsers("id_assign",$planning->fields["id_assign"],"own_ticket",-1);

	echo "</td></tr>";
	
	}


	echo "<tr class='tab_bg_2'><td>".$lang["search"][8].":	</td><td>";
	showCalendarForm("form","begin_date",$begin_date);
    echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td>".$lang["reservation"][12].":	</td>";
	echo "<td>";
	echo "<select name='begin_hour'>";
	for ($i=$global_begin;$i<$global_end;$i++){
	echo "<option value='$i'";
	if ($i==$begin_hour) echo " selected ";
	echo ">$i</option>";
	}
	echo "</select>";
	echo ":";
	echo "<select name='begin_min'>";
	for ($i=0;$i<60;$i+=5){
	echo "<option value='$i'";
	if ($i==$begin_min) echo " selected ";
	echo ">$i</option>";
	}
	echo "</select>";
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td>".$lang["search"][9].":	</td><td>";
	showCalendarForm("form","end_date",$end_date);
    echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td>".$lang["reservation"][13].":	</td>";
	echo "<td>";
	echo "<select name='end_hour'>";
	for ($i=$global_begin;$i<$global_end;$i++){
	echo "<option value='$i'";
	if ($i==$end_hour) echo " selected ";
	echo ">$i</option>";
	}
	echo "</select>";
	echo ":";
	echo "<select name='end_min'>";
	for ($i=0;$i<60;$i+=5){
	echo "<option value='$i'";
	if ($i==$end_min) echo " selected ";
	echo ">$i</option>";
	}
	echo "</select>";
	echo "</td></tr>";



	if ($planID==-1){
	echo "<tr class='tab_bg_2'>";
	echo "<td colspan='2'  valign='top' align='center'>";
	echo "<input type='submit' name='add_planning' value=\"".$lang["buttons"][8]."\" class='submit'>";
	echo "</td></tr>\n";
	} else {
	echo "<tr class='tab_bg_2'>";
	echo "<td valign='top' align='center'>";
	echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
	echo "</td><td valign='top' align='center'>";
	echo "<input type='submit' name='edit_planning' value=\"".$lang["buttons"][14]."\" class='submit'>";
	echo "</td></tr>\n";
	}

	echo "</table>";
	echo "</form></div>";
}

function showPlanning($who,$when,$type){
	global $lang,$cfg_glpi;
	
	if (!haveRight("show_planning","1")&&!haveRight("show_all_planning","1")) return false;
	$date=split("-",$when);
	$time=mktime(1,0,0,$date[1],$date[2],$date[0]);
	$dayofweek=date("w",$time);
	// Cas du dimanche
	if ($dayofweek==0) $dayofweek=7;

if ($type!="month"){
	echo "<div align='center'><table class='tab_cadre_fixe'>";
	// Print Headers
	echo "<tr>";
	switch ($type){
	case "week":
		for ($i=1;$i<=7;$i++){
		echo "<th>".$lang["calendarDay"][$i%7]."</th>";
		}
		
		
		break;
	case "day":
		echo "<th>".$lang["calendarDay"][$dayofweek%7]."</th>";
		break;
	}
	echo "</tr>";
	
	// Print Calendar by 15 mns
	$tmp=split(":",$cfg_glpi["planning_begin"]);
	$hour_begin=$tmp[0];
	$tmp=split(":",$cfg_glpi["planning_end"]);
	$hour_end=$tmp[0];
	for ($hour=$hour_begin;$hour<=$hour_end;$hour++){
		echo "<tr>";
		$add="";
		if ($hour<10&&strlen($hour)==1)	$add="0";
		switch ($type){
		case "week":
			for ($i=1;$i<=7;$i++){
			displayplanning($who,date("Y-m-d",strtotime($when)+mktime(0,0,0,0,$i,0)-mktime(0,0,0,0,$dayofweek,0))." $add$hour:00:00",$type);
			}
		
			break;
		case "day":
			displayplanning($who,$when." $add$hour:00:00",$type);
			break;
		}
	echo "</tr>\n";
	
	}
	echo "</table></div>";
}
else {// Month planning
	list($annee_courante,$mois_courant,$jour_mois)=split("-",$when);
	// on v�ifie pour les ann�s bisextiles, on ne sait jamais.
	if (($annee_courante%4)==0) $fev=29; else $fev=28;
	$nb_jour= array(31,$fev,31,30,31,30,31,31,30,31,30,31);

	// Ces variables vont nous servir pour mettre les jours dans les bonnes colonnes    
	$jour_debut_mois=strftime("%w",mktime(0,0,0,$mois_courant,1,$annee_courante));
	if ($jour_debut_mois==0) $jour_debut_mois=7;
	$jour_fin_mois=strftime("%w",mktime(0,0,0,$mois_courant,$nb_jour[$mois_courant-1],$annee_courante));
	// on n'oublie pas de mettre le mois en fran�is et on n'a plus qu'�mettre les en-t�es

	echo "<div align='center'>";

	echo "<table cellpadding='20' ><tr><td><b>".
		$lang["calendarM"][$mois_courant-1]."&nbsp;".$annee_courante."</b></td></tr></table>";

echo "<table class='tab_cadre_fixe'><tr>";
echo "<th width='14%'>".$lang["calendarD"][1]."</th>";
echo "<th width='14%'>".$lang["calendarD"][2]."</th>";
echo "<th width='14%'>".$lang["calendarD"][3]."</th>";
echo "<th width='14%'>".$lang["calendarD"][4]."</th>";
echo "<th width='14%'>".$lang["calendarD"][5]."</th>";
echo "<th width='14%'>".$lang["calendarD"][6]."</th>";
echo "<th width='14%'>".$lang["calendarD"][0]."</th>";
echo "</tr>";
echo "<tr class='tab_bg_3' >";

$when=$annee_courante."-".$mois_courant."-01";
$daytime=mktime(0,0,0,0,2,0)-mktime(0,0,0,0,1,0);
// Il faut ins�er des cases vides pour mettre le premier jour du mois
// en face du jour de la semaine qui lui correspond.
for ($i=1;$i<$jour_debut_mois;$i++)
	echo "<td style='background-color:#ffffff'>&nbsp;</td>";


// voici le remplissage proprement dit
if ($mois_courant<10&&strlen($mois_courant)==1) $mois_courant="0".$mois_courant;
for ($i=1;$i<$nb_jour[$mois_courant-1]+1;$i++){
	if ($i<10) $ii="0".$i;
	else $ii=$i;
	
	echo "<td  valign='top' height='100'>";
	
	echo "<table align='center' ><tr><td align='center' ><span style='font-family: arial,helvetica,sans-serif; font-size: 14px; color: black'>".$i."</span></td></tr>";
	
	if (!empty($ID)){
	echo "<tr><td align='center'><a href=\"".$target."?show=resa&amp;add=$ID&amp;date=".$annee_courante."-".$mois_courant."-".$ii."\"><img style='color: blue; font-family: Arial, Sans, sans-serif; font-size: 10px;' src=\"".$HTMLRel."pics/addresa.png\" alt='".$lang["reservation"][8]."' title='".$lang["reservation"][8]."'></a></td></tr>";
	}

	echo "<tr>";
	displayplanning($who,date("Y-m-d",strtotime($when)+($i-1)*$daytime)." 00:00:00",$type);
	
	echo "</tr>";
	echo "</table>";
	echo "</td>";

// il ne faut pas oubli�d'aller �la ligne suivante enfin de semaine
    if (($i+$jour_debut_mois)%7==1)
        {echo "</tr>";
       if ($i!=$nb_jour[$mois_courant-1])echo "<tr class='tab_bg_3'>";
       }
}

// on recommence pour finir le tableau proprement pour les m�es raisons

if ($jour_fin_mois!=0)
for ($i=0;$i<7-$jour_fin_mois;$i++) 	echo "<td style='background-color:#ffffff'>&nbsp;</td>";

echo "</tr></table>";

echo "</div>";

	
	
}
}

function displayplanning($who,$when,$type){
global $db,$cfg_glpi,$HTMLRel,$lang;


//echo $when;
$debut=$when;
$tmp=split(" ",$when);
$hour=split(":",$tmp[1]);
$day=split("-",$tmp[0]);

$more_day=0;
$more_hour=0;

if ($type=="month"){
	$INTERVAL=" 1 DAY ";
	$more_day=1;
	}
else {
	$INTERVAL=" 59 MINUTE ";
	$more_hour=1;
}


$fin=date("Y-m-d H:i:s",mktime($hour[0]+$more_hour,$hour[1],$hour[2],$day[1],$day[2]+$more_day,$day[0]));

$author="";  // variable pour l'affichage de l'auteur ou non
$img="rdv_private.png"; // variable par defaut pour l'affichage de l'icone du reminder


$ASSIGN="";
if ($who!=0)
$ASSIGN="id_assign='$who' AND";




// ---------------Tracking

$query="SELECT * from glpi_tracking_planning WHERE $ASSIGN (('".$debut."' <= begin AND adddate( '". $debut ."' , INTERVAL $INTERVAL ) >= begin) OR ('".$debut."' < end AND adddate( '". $debut ."' , INTERVAL $INTERVAL ) >= end) OR (begin <= '".$debut."' AND end > '".$debut."') OR (begin <= adddate( '". $debut ."' , INTERVAL $INTERVAL ) AND end > adddate( '". $debut ."' , INTERVAL $INTERVAL ))) ORDER BY begin";

//echo $query;
$result=$db->query($query);

$fup=new Followup();
$job=new Job();

$interv=array();
$i=0;
if ($db->numrows($result)>0)
while ($data=$db->fetch_array($result)){
	$fup->getFromDB($data["id_followup"]);
	$job->getFromDBwithData($fup->fields["tracking"],0);
	
	$interv[$data["begin"]."$$".$i]["id_followup"]=$data["id_followup"];
	$interv[$data["begin"]."$$".$i]["id_tracking"]=$fup->fields["tracking"];
	$interv[$data["begin"]."$$".$i]["id_assign"]=$data["id_assign"];
	$interv[$data["begin"]."$$".$i]["ID"]=$data["ID"];
	if (strcmp($debut,$data["begin"])>0)
		$interv[$data["begin"]."$$".$i]["begin"]=$debut;
	else $interv[$data["begin"]."$$".$i]["begin"]=$data["begin"];
	if (strcmp($fin,$data["end"])<0)
		$interv[$data["begin"]."$$".$i]["end"]=$fin;
	else $interv[$data["begin"]."$$".$i]["end"]=$data["end"];
	$interv[$data["begin"]."$$".$i]["content"]=resume_text($job->fields["contents"],$cfg_glpi["cut"]);
	$interv[$data["begin"]."$$".$i]["device"]=$job->computername;
	$interv[$data["begin"]."$$".$i]["status"]=$job->fields["status"];
	$interv[$data["begin"]."$$".$i]["priority"]=$job->fields["priority"];
	$i++;
}



// ---------------reminder 
		
	$query2="SELECT * from glpi_reminder WHERE rv='1' AND (author='$who' OR type='public')    AND (('".$debut."' <= begin AND adddate( '". $debut ."' , INTERVAL $INTERVAL ) >= begin) OR ('".$debut."' < end AND adddate( '". $debut ."' , INTERVAL $INTERVAL ) >= end) OR (begin <= '".$debut."' AND end > '".$debut."') OR (begin <= adddate( '". $debut ."' , INTERVAL $INTERVAL ) AND end > adddate( '". $debut ."' , INTERVAL $INTERVAL ))) ORDER BY begin";
	
	$result2=$db->query($query2);
	
	
	$remind=new Reminder();
	
	if ($db->numrows($result2)>0)
	while ($data=$db->fetch_array($result2)){
		$remind->getFromDB($data["ID"]);
		
		$interv[$data["begin"]."$$".$i]["id_reminder"]=$remind->fields["ID"];
		if (strcmp($debut,$data["begin"])>0)
	                $interv[$data["begin"]."$$".$i]["begin"]=$debut;
	        else $interv[$data["begin"]."$$".$i]["begin"]=$data["begin"];
	        if (strcmp($fin,$data["end"])<0)
	                $interv[$data["begin"]."$$".$i]["end"]=$fin;
	        else $interv[$data["begin"]."$$".$i]["end"]=$data["end"];
		
		$interv[$data["begin"]."$$".$i]["title"]=resume_text($remind->fields["title"],$cfg_glpi["cut"]);
		$interv[$data["begin"]."$$".$i]["text"]=resume_text($remind->fields["text"],$cfg_glpi["cut"]);
		$interv[$data["begin"]."$$".$i]["author"]=$data["author"];
		$interv[$data["begin"]."$$".$i]["type"]=$data["type"];

		$i++;
	}
	
	
	
	ksort($interv);


//print_r($interv);
echo "<td class='tab_bg_3' width='12%' valign='top' >";
if ($type!="month")
	echo "<b>".display_time($hour[0]).":00</b><br>";


if (count($interv)>0)
foreach ($interv as $key => $val){
	switch ($type){
	case "day" :
		echo "<div style=' margin:auto; text-align:center; border:1px dashed #cccccc; background-color: #d7d7d2; font-size:9px; width:80%;'>";

		if(isset($val["id_tracking"])){  // show tracking

			echo "<img src='$HTMLRel/pics/rdv_interv.png' alt=''>&nbsp;";
			
			
			echo "<a href='".$HTMLRel."tracking/tracking-info-form.php?ID=".$val["id_tracking"]."'>";
			echo date("H:i",strtotime($val["begin"]))." -> ".date("H:i",strtotime($val["end"])).": ".$val["device"];
			echo "&nbsp;<img src=\"".$HTMLRel."pics/".$val["status"].".png\" alt='".getStatusName($val["status"])."' title='".getStatusName($val["status"])."'>";

			if ($who==0){
				echo "<br>";
				echo $lang["planning"][9]." ".getUserName($val["id_assign"]);
			} 
			echo "</a>";
			echo "<br>";
			echo "<strong>".$lang["joblist"][2].":</strong> ".getPriorityName($val["priority"])."<br>";
			echo "<strong>".$lang["joblist"][6].":</strong><br>".$val["content"];
		}else{  // show Reminder
			
				if ($val["type"]=="public"){
					$author="<br>".$lang["planning"][9]." : ".getUserName($val["author"]);
					$img="rdv_public.png";
				} 
			echo "<img src='$HTMLRel/pics/".$img."' alt=''>&nbsp;";
			echo "<a href='".$HTMLRel."reminder/reminder-info-form.php?ID=".$val["id_reminder"]."'>";
			echo date("H:i",strtotime($val["begin"]))." -> ".date("H:i",strtotime($val["end"])).": ".$val["title"];
			echo $author;
			echo "</a>";
			echo "<br>";
			echo $val["text"];
			echo "";

		

		}

	

		echo "</div><br>";
	break;
	case "week" :

		if(isset($val["id_tracking"])){  // show tracking
			$rand=mt_rand();
			echo "<div class='planning' ><img src='$HTMLRel/pics/rdv_interv.png' alt=''>";
			echo "<a onmouseout=\"cleanhide('content_".$val["ID"].$rand."')\" onmouseover=\"cleandisplay('content_".$val["ID"].$rand."')\" href='".$HTMLRel."tracking/tracking-info-form.php?ID=".$val["id_tracking"]."'>";
			echo date("H:i",strtotime($val["begin"]))." -> ".date("H:i",strtotime($val["end"])).":";
			echo "&nbsp;<img src=\"".$HTMLRel."pics/".$val["status"].".png\" alt='".getStatusName($val["status"])."' title='".getStatusName($val["status"])."'>";
			echo "<br>".$val["device"];

				if ($who==0){
					echo "<br>";
					echo $lang["planning"][9]." ".getUserName($val["id_assign"]);
				} 
			echo "</a>";
			echo "</div>";
			
			echo "<div class='over_link' id='content_".$val["ID"].$rand."'><strong>".$lang["joblist"][2].":</strong> ".getPriorityName($val["priority"])."<br>";
			echo "<strong>".$lang["joblist"][6].":</strong><br>".$val["content"]."</div>";
		}else{ // show reminder
			if ($val["type"]=="public"){
					$author="<br>Par ".getUserName($val["author"]);
					$img="rdv_public.png";
				} 
			
			$rand=mt_rand();
			echo "<div class='planning' ><img src='$HTMLRel/pics/".$img."' alt=''>&nbsp;";
			echo "<a onmouseout=\"cleanhide('content_".$val["id_reminder"].$rand."')\" onmouseover=\"cleandisplay('content_".$val["id_reminder"].$rand."')\" href='".$HTMLRel."reminder/reminder-info-form.php?ID=".$val["id_reminder"]."'>";
			echo date("H:i",strtotime($val["begin"]))." -> ".date("H:i",strtotime($val["end"])).": <br>".$val["title"];
				if ($who!=$val["author"]){
					$author="<br>Par ".getUserName($val["author"]);
					$img="rdv_public.png";
				} 
			echo "</a>";
			echo "</div>";
			
			echo "<div class='over_link' id='content_".$val["id_reminder"].$rand."'>".$val["text"]."</div>";
			

		}


	break;
	case "month" :

		if(isset($val["id_tracking"])){  // show tracking
		$rand=mt_rand();
		echo "<div class='planning' ><img src='$HTMLRel/pics/rdv_interv.png' alt=''>";
		echo "<a onmouseout=\"cleanhide('content_".$val["ID"].$rand."')\" onmouseover=\"cleandisplay('content_".$val["ID"].$rand."')\" href='".$HTMLRel."tracking/tracking-info-form.php?ID=".$val["id_tracking"]."'>";
		echo date("H:i",strtotime($val["begin"]))." -> ".date("H:i",strtotime($val["end"])).":";
		echo "&nbsp;<img src=\"".$HTMLRel."pics/".$val["status"].".png\" alt='".getStatusName($val["status"])."' title='".getStatusName($val["status"])."'>";
		echo "<br>".$val["device"];
		if ($who==0){
			echo "<br>";
			echo $lang["planning"][9]." ".getUserName($val["id_assign"]);
		} 
		echo "</a>";
		echo "</div>";
		
		echo "<div class='over_link' id='content_".$val["ID"].$rand."'><strong>".$lang["joblist"][2].":</strong> ".getPriorityName($val["priority"])."<br>";
		echo "<strong>".$lang["joblist"][6].":</strong><br>".$val["content"]."</div>";

		}else{ // show reminder
			if ($val["type"]=="public"){
					$author="<br>Par ".getUserName($val["author"]);
					$img="rdv_public.png";
				} 
		$rand=mt_rand();
		echo "<div class='planning' ><img src='$HTMLRel/pics/".$img."' alt=''>&nbsp;";
		echo "<a onmouseout=\"cleanhide('content_".$val["id_reminder"].$rand."')\" onmouseover=\"cleandisplay('content_".$val["id_reminder"].$rand."')\" href='".$HTMLRel."reminder/reminder-info-form.php?ID=".$val["id_reminder"]."'>";
		echo date("H:i",strtotime($val["begin"]))." -> ".date("H:i",strtotime($val["end"])).": <br>".$val["title"];
			if ($who!=$val["author"]){
					$author="<br>Par ".getUserName($val["author"]);
					$img="rdv_public.png";
				} 
		echo "</a>";
		echo "</div>";
		
		echo "<div class='over_link' id='content_".$val["id_reminder"].$rand."'>".$val["text"]."</div>";


		}
		
	
	break;
	}
}

echo "</td>";

}

function display_time($time){

$time=round($time);
if ($time<10&&strlen($time)) return "0".$time;
else return $time;

}


function ShowPlanningCentral($who){

	global $db,$cfg_glpi,$HTMLRel,$lang;

	if (!haveRight("show_planning","1")) return false;
	
	$when=strftime("%Y-%m-%d");
	$debut=$when;
	
	// followup
	$ASSIGN="";
	if ($who!=0)
	$ASSIGN="id_assign='$who' AND";
	
	
	$INTERVAL=" 1 DAY "; // we want to show planning of the day
	
	$query="SELECT * from glpi_tracking_planning WHERE $ASSIGN (('".$debut."' <= begin AND adddate( '". $debut ."' , INTERVAL $INTERVAL ) >= begin) OR ('".$debut."' < end AND adddate( '". $debut ."' , INTERVAL $INTERVAL ) >= end) OR (begin <= '".$debut."' AND end > '".$debut."') OR (begin <= adddate( '". $debut ."' , INTERVAL $INTERVAL ) AND end > adddate( '". $debut ."' , INTERVAL $INTERVAL ))) ORDER BY begin";
	
	
	$result=$db->query($query);
	
	$fup=new Followup();
	$job=new Job();
	
	$interv=array();
	$i=0;
	if ($db->numrows($result)>0)
	while ($data=$db->fetch_array($result)){
		$fup->getFromDB($data["id_followup"]);
		$job->getFromDBwithData($fup->fields["tracking"],0);
		
		$interv[$data["begin"]."$$".$i]["id_tracking"]=$fup->fields["tracking"];
		$interv[$data["begin"]."$$".$i]["begin"]=$data["begin"];
		$interv[$data["begin"]."$$".$i]["end"]=$data["end"];
		$interv[$data["begin"]."$$".$i]["content"]=resume_text($job->fields["contents"],$cfg_glpi["cut"]);
		$interv[$data["begin"]."$$".$i]["device"]=$job->computername;
		$i++;
	}
	
	
	// reminder 
		
	$query2="SELECT * from glpi_reminder WHERE rv='1' AND (author='$who' OR type='public')    AND (('".$debut."' <= begin AND adddate( '". $debut ."' , INTERVAL $INTERVAL ) >= begin) OR ('".$debut."' < end AND adddate( '". $debut ."' , INTERVAL $INTERVAL ) >= end) OR (begin <= '".$debut."' AND end > '".$debut."') OR (begin <= adddate( '". $debut ."' , INTERVAL $INTERVAL ) AND end > adddate( '". $debut ."' , INTERVAL $INTERVAL ))) ORDER BY begin";
	
	$result2=$db->query($query2);
	
	
	$remind=new Reminder();
	
	$i=0;
	if ($db->numrows($result2)>0)
	while ($data=$db->fetch_array($result2)){
		$remind->getFromDB($data["ID"]);
		
		
		$interv[$data["begin"]."$$".$i]["id_reminder"]=$remind->fields["ID"];
		$interv[$data["begin"]."$$".$i]["begin"]=$data["begin"];
		$interv[$data["begin"]."$$".$i]["end"]=$data["end"];
		$interv[$data["begin"]."$$".$i]["title"]=resume_text($remind->fields["title"],$cfg_glpi["cut"]);
		$interv[$data["begin"]."$$".$i]["text"]=resume_text($remind->fields["text"],$cfg_glpi["cut"]);
		
		$i++;
	}
	
	
	
	ksort($interv);
	
	echo "<table class='tab_cadre' width='80%'><tr><th colspan='3'><a href='".$HTMLRel."planning/index.php'>".$lang["planning"][15]."</a></th></tr><tr><th>".$lang["buttons"][33]."</th><th>".$lang["buttons"][32]."</th><th>".$lang["joblist"][6]."</th></tr>";
		if (count($interv)>0){
			foreach ($interv as $key => $val){
						
			echo "<tr class='tab_bg_1'>";
			echo "<td>";		
			echo date("H:i",strtotime($val["begin"]));
			echo "</td>";
			echo "<td>";
			echo date("H:i",strtotime($val["end"]));
			echo "</td>";
			if(isset($val["id_tracking"])){
				echo "<td>".$val["device"]."<a href='".$HTMLRel."tracking/tracking-info-form.php?ID=".$val["id_tracking"]."'>";
				echo ": ".resume_text($val["content"],125)."</a>";
			}else{
				echo "<td><a href='".$HTMLRel."reminder/reminder-info-form.php?ID=".$val["id_reminder"]."'>".$val["title"]."";
				echo "</a>: ".resume_text($val["text"],125);
			}
	
			echo "</td></tr>";
							
			}
		
		}
	echo "</table>";

}
















//*******************************************************************************************************************************
// *********************************** Implementation ICAL ***************************************************************
//*******************************************************************************************************************************


/**
* Generate URL for ICAL
*
*  
* @param $who 
* @Return Nothing (display function)
*
**/      
function urlIcal ($who) {

GLOBAL  $cfg_glpi, $lang;

echo "<a href=\"".$cfg_glpi["root_doc"]."/planning/ical.php?uID=$who\"><span style='font-size:10px'>-".$lang["planning"][12]."</span></a>";
echo "<br>";

// Todo recup l'url complete de glpi proprement, ? nouveau champs table config ?
echo "<a href=\"webcal://".$_SERVER['HTTP_HOST'].$cfg_glpi["root_doc"]."/planning/ical.php?uID=$who\"><span style='font-size:10px'>-".$lang["planning"][13]."</span></a>";

}


/**
* Convert date mysql to timestamp
* 
* @param $date  date in mysql format
* @Return timestamp
*
**/      
 function date_mysql_to_timestamp($date){
 if (!preg_match('/(\d\d\d\d)-(\d\d)-(\d\d) (\d\d):(\d\d):(\d\d)/', $date, $r)){
  return false;
  }
 
  return mktime($r[4], $r[5], $r[6], $r[2], $r[3], $r[1] );
}


/**
* Convert timestamp to date in ical format
* 
* @param $date  timestamp
* @Return date in ical format
*
**/      
function date_ical($date) {
	return date("Ymd\THis", date_mysql_to_timestamp($date));
}



/**
*
* Generate header for ical file
* 
* @param $name 
* @Return $debut_cal  
*
**/      
function debutIcal($name) {

GLOBAL  $cfg_glpi, $lang;

 	$debut_cal = "BEGIN:VCALENDAR\n";
        $debut_cal .= "VERSION:2.0\n";

	if ( ! empty ( $cfg_glpi["version"]) ) {
		$debut_cal.= "PRODID:-//GLPI-Planning-".$cfg_glpi["version"]."\n";
		} else {
		$debut_cal.= "PRODID:-//GLPI-Planning-UnknownVersion\n";
	}

	$debut_cal.= "METHOD:PUBLISH\n"; // Outlook want's this in the header, why I don't know...
        $debut_cal .= "X-WR-CALNAME ;VALUE=TEXT:$name\n";
    
     //   $debut_cal .= "X-WR-RELCALID:n";
     //   $debut_cal .= "X-WR-TIMEZONE:US/Pacific\n";
        $debut_cal .= "CALSCALE:GREGORIAN\n\n";
        return (string) $debut_cal;
    }


/**
*  Generate ical body file
*  
* @param $who
* @Return $debutcal $event $fincal
**/      
function generateIcal($who){

GLOBAL  $db,$cfg_glpi, $lang;

// export job
$query="SELECT * from glpi_tracking_planning WHERE id_assign=$who";

$result=$db->query($query);

$job=new Job();
$fup=new Followup();
$interv=array();
$i=0;
if ($db->numrows($result)>0)
while ($data=$db->fetch_array($result)){
	
	 $fup->getFromDB($data["id_followup"]); 
	 $job->getFromDBwithData($fup->fields["tracking"],0);
		
	$interv[$data["begin"]."$$".$i]["id_tracking"]=$data['id_followup'];
	$interv[$data["begin"]."$$".$i]["id_assign"]=$data['id_assign'];
	$interv[$data["begin"]."$$".$i]["ID"]=$data['ID'];
	$interv[$data["begin"]."$$".$i]["begin"]=$data['begin'];
	$interv[$data["begin"]."$$".$i]["end"]=$data['end'];
	//$interv[$i]["content"]=substr($job->contents,0,$cfg_glpi["cut"]);
	$interv[$data["begin"]."$$".$i]["content"]=substr($job->fields['contents'],0,$cfg_glpi["cut"]);
	$interv[$data["begin"]."$$".$i]["device"]=$job->computername;
	$i++;
}


// reminder 
		
	$query2="SELECT * from glpi_reminder WHERE rv='1' AND (author='$who' OR type='public')";
	
	$result2=$db->query($query2);
	
	
	$remind=new Reminder();
	
	$i=0;
	if ($db->numrows($result2)>0)
	while ($data=$db->fetch_array($result2)){
		$remind->getFromDB($data["ID"]);
		
		
		$interv[$data["begin"]."$$".$i]["id_reminder"]=$remind->fields["ID"];
		$interv[$data["begin"]."$$".$i]["begin"]=$data["begin"];
		$interv[$data["begin"]."$$".$i]["end"]=$data["end"];
		$interv[$data["begin"]."$$".$i]["title"]=$remind->fields["title"];
		$interv[$data["begin"]."$$".$i]["content"]=$remind->fields["text"];
		
		$i++;
	}

$debutcal="";
$event="";
$fincal="";


ksort($interv);

if (count($interv)>0) {
	
$debutcal=debutIcal(getUserName($who));
	
	foreach ($interv as $key => $val){

		$event .= "BEGIN:VEVENT\n";

		if(isset($val["id_tracking"])){
			$event.="UID:Job#".$val["id_tracking"]."\n";
			}else{
			$event.="UID:Event#".$val["id_reminder"]."\n";
		}		

		$event.="DTSTAMP:".date_ical($val["begin"])."\n";

		$event .= "DTSTART:".date_ical($val["begin"])."\n";

		$event .= "DTEND:".date_ical($val["end"])."\n";

		if(isset($val["id_tracking"])){
 			$event .= "SUMMARY:".$lang["planning"][8]." # ".$val["id_tracking"]." ".$lang["planning"][10]." # ".$val["device"]."\n";
			}else{
			$event .= "SUMMARY:".$val["title"]."\n";
		}

		$event .= "DESCRIPTION:".$val["content"]."\n";
		
		//todo recup la cat�orie d'intervention.
		//$event .= "CATEGORIES:".$val["categorie"]."\n";
		if(isset($val["id_tracking"])){
			$event .= "URL:".$cfg_glpi["url_base"]."/index.php?redirect=tracking_".$val["id_tracking"]."\n";
		}

  		$event .= "END:VEVENT\n\n";
		}
$fincal= "END:VCALENDAR\n";	
}

return utf8_decode($debutcal.$event.$fincal);

}


?>
