<?php
/*
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2005 by the INDEPNET Development Team.
 
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

// device_type
// 1 computers
// 2 networking
// 3 printers
// 4 monitors
// 5 peripherals
// 6 

include ("_relpos.php");
// FUNCTIONS Reservation


function titleReservation(){
           GLOBAL  $lang,$HTMLRel;
           
              
	     
	     echo "<div align='center'><table border='0'><tr><td>";
                echo "<img src=\"".$HTMLRel."pics/reservation.png\" alt='' title=''></td><td><b><span class='icon_nav'>".$lang["reservation"][1]."</span>";
		 echo "</b></td></tr></table>&nbsp;</div>";
	   
	   
	   
}

function searchFormReservationItem($field="",$phrasetype= "",$contains="",$sort= ""){
	// Print Search Form
	
	GLOBAL $cfg_install, $cfg_layout, $layout, $lang;

	$option["glpi_reservation_item.ID"]				= $lang["reservation"][2];
//	$option["glpi_reservation_item.device_type"]			= $lang["reservation"][3];
//	$option["glpi_dropdown_locations.name"]			= $lang["software"][4];
//	$option["glpi_software.version"]			= $lang["software"][5];
	$option["glpi_reservation.comments"]			= $lang["reservation"][23];
	
	echo "<form method=get action=\"".$cfg_install["root"]."/reservation/index.php\">";
	echo "<center><table class='tab_cadre' width='750'>";
	echo "<tr><th colspan='2'><b>".$lang["search"][0].":</b></th></tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>";
	echo "<select name=\"field\" size='1'>";
        echo "<option value='all' ";
	if($field == "all") echo "selected";
	echo ">".$lang["search"][7]."</option>";
        reset($option);
	foreach ($option as $key => $val) {
		echo "<option value=\"".$key."\""; 
		if($key == $field) echo "selected";
		echo ">". $val ."</option>\n";
	}
	echo "</select>&nbsp;";
	echo $lang["search"][1];
	echo "&nbsp;<select name='phrasetype' size='1' >";
	echo "<option value='contains'";
	if($phrasetype == "contains") echo "selected";
	echo ">".$lang["search"][2]."</option>";
	echo "<option value='exact'";
	if($phrasetype == "exact") echo "selected";
	echo ">".$lang["search"][3]."</option>";
	echo "</select>";
	echo "<input type='text' size='15' name=\"contains\" value=\"". $contains ."\" />";
	echo "&nbsp;";
	echo $lang["search"][4];
	echo "&nbsp;<select name='sort' size='1'>";
	reset($option);
	foreach ($option as $key => $val) {
		echo "<option value=\"".$key."\"";
		if($key == $sort) echo "selected";
		echo ">".$val."</option>\n";
	}
	echo "</select> ";
	echo "</td><td width='80' align='center' class='tab_bg_2'>";
	echo "<input type='submit' value=\"".$lang["buttons"][0]."\" class='submit'>";
	echo "</td></tr></table></center></form>";
}

function showReservationItemList($target,$username,$field,$phrasetype,$contains,$sort,$order,$start){
	// Lists Reservation Items

	GLOBAL $cfg_install, $cfg_layout, $cfg_features, $lang, $HTMLRel;

		$db = new DB;

	// Build query
	if($field=="all") {
	/*	$where = " (";
		$where .= "res_item.".$coco . " LIKE '%".$contains."%'";
		$where .= ")";
	*/
	$where=" 1 = 1 ";
	}
	else {
		if ($phrasetype == "contains") {
			$where = "($field LIKE '%".$contains."%')";
		}
		else {
			$where = "($field LIKE '".$contains."')";
		}
	}

	if (!$start) {
		$start = 0;
	}
	if (!$order) {
		$order = "ASC";
	}
	
	$query = "select glpi_reservation_item.ID from glpi_reservation_item ";
	$query .= " where  $where ORDER BY $sort $order";
	//echo $query;
	// Get it from database	
	if ($result = $db->query($query)) {
		$numrows =  $db->numrows($result);

		// Limit the result, if no limit applies, use prior result
		if ($numrows > $cfg_features["list_limit"]) {
			$query_limit = $query ." LIMIT $start,".$cfg_features["list_limit"]." ";
			$result_limit = $db->query($query_limit);
			$numrows_limit = $db->numrows($result_limit);
		} else {
			$numrows_limit = $numrows;
			$result_limit = $result;
		}
		
		if ($numrows_limit>0) {
			// Produce headline
			echo "<div align='center'><table  class='tab_cadre'><tr>";
			// Name
			echo "<th>";
			if ($sort=="glpi_reservation_item.ID") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_reservation_item.ID&order=ASC&start=$start\">";
			echo $lang["reservation"][2]."</a></th>";

			// Location			
			echo "<th>";
			if ($sort=="glpi_reservation_item.device_type") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_reservation_item.device_type&order=ASC&start=$start\">";
			echo $lang["reservation"][3]."</a></th>";

			// Type
			echo "<th>";
			if ($sort=="glpi_reservation_item.id_device") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_reservation_item.id_device&order=ASC&start=$start\">";
			echo $lang["reservation"][4]."</a></th>";

			// Comments
			echo "<th>";
			if ($sort=="glpi_reservation_item.comments") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_reservation_item.comments&order=ASC&start=$start\">";
			echo $lang["reservation"][23]."</a></th>";
			
			
			
			
			
			
			echo "<th>&nbsp;</th>";
			echo "<th>&nbsp;</th>";
			echo "<th>&nbsp;</th>";
			echo "</tr>";

			for ($i=0; $i < $numrows_limit; $i++) {
				$ID = $db->result($result_limit, $i, "ID");
				$ri = new ReservationItem;
				$ri->getfromDB($ID);
				echo "<tr class='tab_bg_2".(isset($ri->obj->fields["deleted"])&&$ri->obj->fields["deleted"]=='Y'?"_2":"")."' align='center'>";
				echo "<td>";
				echo $ri->fields["ID"];
				echo "</td>";
				
				echo "<td>". $ri->getType()."</td>";
				echo "<td><b>". $ri->getLink() ."</b></td>";
				echo "<td>". substr(unhtmlentities_deep($ri->fields["comments"]),0,$cfg_features["cut"])."</td>";
				echo "<td>";
				echo "<a href='".$target."?comment=$ID'>".$lang["reservation"][22]."</a>";
				echo "</td>";

				echo "<td>";
				showReservationForm($ri->fields["device_type"],$ri->fields["id_device"]);
				echo "</td>";
				echo "<td>";
				echo "<a href='".$target."?show=resa&ID=$ID'>".$lang["reservation"][21]."</a>";
				echo "</td>";
				echo "</tr>";
			}

			// Close Table
			echo "</table></div>";

			// Pager
			$parameters="field=$field&phrasetype=$phrasetype&contains=$contains&sort=$sort&order=$order";
			printPager($start,$numrows,$target,$parameters);

		} else {
			echo "<div align='center'><b>".$lang["peripherals"][17]."</b></div>";
			//echo "<hr noshade>";
			//searchFormperipheral();
		}
	}
	
}

function showReservationForm($device_type,$id_device){

GLOBAL $cfg_install,$lang;

$query="select * from glpi_reservation_item where (device_type='$device_type' and id_device='$id_device')";
$db=new DB;
if ($result = $db->query($query)) {
		$numrows =  $db->numrows($result);
//echo "<form name='resa_form' method='post' action=".$cfg_install["root"]."/reservation/index.php>";
echo "<a href=\"".$cfg_install["root"]."/reservation/index.php?";
// Ajouter le matériel
if ($numrows==0){
//echo "<input type='hidden' name='id_device' value='$id_device'>";
//echo "<input type='hidden' name='device_type' value='$device_type'>";
//echo "<input class='submit' type='submit' name='add' value='".$lang["reservation"][7]."'>";
echo "id_device=$id_device&device_type=$device_type&add=add\">".$lang["reservation"][7]."</a>";
}
// Supprimer le matériel
else {
//echo "<input type='hidden' name='ID' value='".$db->result($result,0,"ID")."'>";
//echo "<input class='submit' type='submit' name='delete' value='".$lang["reservation"][6]."'>";
echo "ID=".$db->result($result,0,"ID")."&delete=delete\">".$lang["reservation"][6]."</a>";
}

//echo "</form>";
}
}

function addReservationItem($input){
// Add Reservation Item, nasty hack until we get PHP4-array-functions

	$ri = new ReservationItem;

	// dump status
	$null = array_pop($input);
	
	// fill array for update
	foreach ($input as $key => $val) {
		if (empty($sw->fields[$key]) || $sw->fields[$key] != $input[$key]) {
			$ri->fields[$key] = $input[$key];
		}
	}

	if ($ri->addToDB()) {
		return true;
	} else {
		return false;
	}


}

function deleteReservationItem($input){

	// Delete Reservation Item 
	
	$ri = new ReservationItem;
	$ri->deleteFromDB($input["ID"]);
}

function printCalendrier($target,$ID=""){
global $lang, $HTMLRel;


if (!isset($_GET["mois_courant"]))
	$mois_courant=strftime("%m");
else $mois_courant=$_GET["mois_courant"];
if (!isset($_GET["annee_courante"]))
	$annee_courante=strftime("%Y");
else $annee_courante=$_GET["annee_courante"];

$mois_suivant=$mois_courant+1;
$mois_precedent=$mois_courant-1;
$annee_suivante=$annee_courante;
$annee_precedente=$annee_courante;
if ($mois_precedent==0){
	$mois_precedent=12;
	$annee_precedente--;
}

if ($mois_suivant==13){
	$mois_suivant=1;
	$annee_suivante++;
}

$str_suivant="?show=resa&ID=$ID&mois_courant=$mois_suivant&annee_courante=$annee_suivante";
$str_precedent="?show=resa&ID=$ID&mois_courant=$mois_precedent&annee_courante=$annee_precedente";


if (!empty($ID)){
$m=new ReservationItem;
$m->getfromDB($ID);
$type=$m->getType();
$name=$m->getName();
$all="<a href='$target?show=resa&ID=&mois_courant=$mois_courant&annee_courante=$annee_courante'>".$lang["reservation"][26]."</a>";
} else {
$type="";
$name=$lang["reservation"][25];
$all="&nbsp;";
}



 echo "<div align='center'><table border='0'><tr><td>";
                echo "<img src=\"".$HTMLRel."pics/reservation.png\" alt='' title=''></td><td><b><span class='icon_nav'>".$type." - ".$name."</span>";
		 echo "</b></td></tr><tr><td colspan='2' align ='center'>$all</td></tr></table></div>";


	
// on vérifie pour les années bisextiles, on ne sait jamais.
if (($annee_courante%4)==0) $fev=29; else $fev=28;
$nb_jour= array(31,$fev,31,30,31,30,31,31,30,31,30,31);

// Ces variables vont nous servir pour mettre les jours dans les bonnes colonnes    
$jour_debut_mois=strftime("%w",mktime(0,0,0,$mois_courant,1,$annee_courante));
if ($jour_debut_mois==0) $jour_debut_mois=7;
$jour_fin_mois=strftime("%w",mktime(0,0,0,$mois_courant,$nb_jour[$mois_courant-1],$annee_courante));
// on n'oublie pas de mettre le mois en français et on n'a plus qu'à mettre les en-têtes

echo "<div align='center'>";

echo "<table cellpadding='20' ><tr><td><a href=\"".$target.$str_precedent."\"><img src=\"".$HTMLRel."pics/left.png\" alt='".$lang["buttons"][12]."' title='".$lang["buttons"][12]."'></a></td><td><b>".
	$lang["calendarM"][$mois_courant-1]."&nbsp;".$annee_courante."</b></td><td><a href=\"".$target.$str_suivant."\"><img src=\"".$HTMLRel."pics/right.png\" alt='".$lang["buttons"][11]."' title='".$lang["buttons"][11]."'></a></td></tr></table>";
// test
echo "<table><tr><td valign='top'>";

echo "<table><tr><td width='100' valign='top'>";

		// date du jour
		$today=getdate(time());
		$mois=$today["mon"];
		$annee=$today["year"];
		
		
		$annee_avant = $annee_courante - 1;
		$annee_apres = $annee_courante + 1;
		
		
		echo "<div class='verdana1'>";
			echo "<div><b>$annee_avant</b></div>";
			for ($i=$mois_courant; $i < 13; $i++) {
				echo "<div style='margin-left: 10px; padding: 2px; -moz-border-radius: 5px; margin-top: 2px; border: 1px solid #cccccc; background-color: #eeeeee;'><a href=\"".$target."?show=resa&ID=$ID&mois_courant=$i&annee_courante=$annee_avant\">".
	$lang["calendarM"][$i-1]."</a></div>";
			}
		
		echo "<div><b>$annee_courante</b></div>";
		for ($i=1; $i < 13; $i++) {
			if ($i == $mois_courant) {
				echo "<div style='margin-left: 10px; padding: 2px; -moz-border-radius: 5px; margin-top: 2px; border: 1px solid #666666; background-color: white;'><b>".
	$lang["calendarM"][$i-1]."</b></div>";
			}
			else {
				echo "<div style='margin-left: 10px; padding: 2px; -moz-border-radius: 5px; margin-top: 2px; border: 1px solid #cccccc; background-color: #eeeeee;'><a href=\"".$target."?show=resa&ID=$ID&mois_courant=$i&annee_courante=$annee_courante\">".
	$lang["calendarM"][$i-1]."</a></div>";
			}
		}

			echo "<div><b>$annee_apres</b></div>";
			for ($i=1; $i < $mois_courant+1; $i++) {
				echo "<div style='margin-left: 10px; padding: 2px; -moz-border-radius: 5px; margin-top: 2px; border: 1px solid #cccccc; background-color: #eeeeee;'><a href=\"".$target."?show=resa&ID=$ID&mois_courant=$i&annee_courante=$annee_apres\">".
	$lang["calendarM"][$i-1]."</a></div>";
		}
		echo "</div>";
	
	echo "</td></tr></table>";

echo "</td><td valign='top'>";



// test 
	
	
echo "<table class='tab_cadre' width='700'>";
echo "<th width='14%'>".$lang["calendarD"][1]."</th>";
echo "<th width='14%'>".$lang["calendarD"][2]."</th>";
echo "<th width='14%'>".$lang["calendarD"][3]."</th>";
echo "<th width='14%'>".$lang["calendarD"][4]."</th>";
echo "<th width='14%'>".$lang["calendarD"][5]."</th>";
echo "<th width='14%'>".$lang["calendarD"][6]."</th>";
echo "<th width='14%'>".$lang["calendarD"][0]."</th>";

echo "<tr class='tab_bg_3' >";

// Il faut insérer des cases vides pour mettre le premier jour du mois
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
	echo "<tr><td align='center'><a href=\"".$target."?show=resa&add=$ID&date=".$annee_courante."-".$mois_courant."-".$ii."\"><img style='color: blue; font-family: Arial, Sans, sans-serif; font-size: 10px;' src=\"".$HTMLRel."pics/addresa.png\" alt='".$lang["reservation"][8]."' title='".$lang["reservation"][8]."'></a></td></tr>";
	}
	//if (($i-1+$jour_debut_mois)%7!=6&&($i-1+$jour_debut_mois)%7!=0){
	echo "<tr><td>";
	printReservation($target,$ID,$annee_courante."-".$mois_courant."-".$ii);
	echo "</tr></td>";
	//}
//	echo $annee_courante."-".$mois_courant."-".$ii;
	//if (($i-1+$jour_debut_mois)%7!=6&&($i-1+$jour_debut_mois)%7!=0)
	
	
	
	
	echo "</table>";
	echo "</td>";

// il ne faut pas oublié d'aller à la ligne suivante enfin de semaine
    if (($i+$jour_debut_mois)%7==1)
        {echo "</tr>";
       if ($i!=$nb_jour[$mois_courant-1])echo "<tr class='tab_bg_3'>";
       }
}

// on recommence pour finir le tableau proprement pour les mêmes raisons

if ($jour_fin_mois!=0)
for ($i=0;$i<7-$jour_fin_mois;$i++) 	echo "<td style='background-color:#ffffff'>&nbsp;</td>";

echo "</tr></table>";

echo "</td></tr></table></div>";
	
}

function showAddReservationForm($target,$ID,$date,$resaID=-1){
	global $lang,$HTMLRel;
	
	$resa= new ReservationResa;
	if ($resaID!=-1)
		$resa->getFromDB($resaID);
	else {
		$resa->getEmpty();
		$resa->fields["begin"]=$date." 12:00:00";
		$resa->fields["end"]=$date." 13:00:00";
	}
	$begin=strtotime($resa->fields["begin"]);
	$end=strtotime($resa->fields["end"]);
	
	$begin_date=date("Y-m-d",$begin);
	$end_date=date("Y-m-d",$end);
	$begin_hour=date("H",$begin);
	$end_hour=date("H",$end);
	$begin_min=date("i",$begin);
	$end_min=date("i",$end);

	echo "<div align='center'><form method='post' name=form action=\"$target\">";
	
	if ($resaID!=-1)
	echo "<input type='hidden' name='ID' value='$resaID'>";

	echo "<input type='hidden' name='id_item' value='$ID'>";

	echo "<table class='tab_cadre' cellpadding='2'>";
	echo "<tr><th colspan='2'><b>";
	echo $lang["reservation"][9];
	echo "</b></th></tr>";
	// Ajouter le nom du matériel
	$r=new ReservationItem;
	$r->getfromDB($ID);
	echo "<tr class='tab_bg_1'><td>".$lang["reservation"][4].":	</td>";
	echo "<td>";
	echo "<b>".$r->getType()." - ".$r->getName()."</b>";
    echo "</td></tr>";
	if (!isAdmin($_SESSION["glpitype"]))
	echo "<input type='hidden' name='id_user' value='".$_SESSION["glpiID"]."'>";
	else {
	echo "<tr class='tab_bg_2'><td>".$lang["reservation"][31].":	</td>";
	echo "<td>";
	if ($resaID==-1)
	dropdownValue("glpi_users","id_user",$_SESSION["glpiID"]);
	else dropdownValue("glpi_users","id_user",$resa->fields["id_user"]);
    echo "</td></tr>";
	
	}


	echo "<tr class='tab_bg_2'><td>".$lang["reservation"][10].":	</td><td>";
	showCalendarForm("form","begin_date",$begin_date);
    echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td>".$lang["reservation"][12].":	</td>";
	echo "<td>";
	echo "<select name='begin_hour'>";
	for ($i=0;$i<24;$i++){
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

	echo "<tr class='tab_bg_2'><td>".$lang["reservation"][11].":	</td><td>";
	showCalendarForm("form","end_date",$end_date);
    echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td>".$lang["reservation"][13].":	</td>";
	echo "<td>";
	echo "<select name='end_hour'>";
	for ($i=0;$i<24;$i++){
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

	if ($resaID==-1){
		echo "<tr class='tab_bg_2'><td>".$lang["reservation"][27].":	</td>";
		echo "<td>";
		echo "<select name='periodicity'>";
		echo "<option value='day'>".$lang["reservation"][29]."</option>";	
		echo "<option value='week'>".$lang["reservation"][28]."</option>";		
		echo "</select>";	
		echo "<select name='periodicity_times'>";
		for ($i=1;$i<60;$i+=1){
		echo "<option value='$i'";
		echo ">$i</option>";
		}
		echo "</select>";

		echo $lang["reservation"][30];
		echo "</td></tr>";
	}

	echo "<tr class='tab_bg_2'><td>".$lang["reservation"][23].":	</td>";
	echo "<td><input type='text' name='comment' size='30' value='".$resa->fields["comment"]."'>";
    echo "</td></tr>";

	if ($resaID==-1){
	echo "<tr class='tab_bg_2'>";
	echo "<td colspan='2'  valign='top' align='center'>";
	echo "<input type='submit' name='add_resa' value=\"".$lang["buttons"][8]."\" class='submit' class='submit'>";
	echo "</td></tr>\n";
	} else {
	echo "<tr class='tab_bg_2'>";
	echo "<td valign='top' align='center'>";
	echo "<input type='submit' name='clear_resa' value=\"".$lang["buttons"][6]."\" class='submit' class='submit'>";
	echo "</td><td valign='top' align='center'>";
	echo "<input type='submit' name='edit_resa' value=\"".$lang["buttons"][14]."\" class='submit' class='submit'>";
	echo "</td></tr>\n";
	}
	
	echo "</table></div>";
	echo "</form>";
}

function printReservation($target,$ID,$date){
if (!empty($ID))
	printReservationItem($target,$ID,$date);
else  {
$db=new DB();
$query="SELECT ID FROM glpi_reservation_item";
$result=$db->query($query);
if ($db->numrows($result)>0)
while ($data=$db->fetch_array($result)){
	$m=new ReservationItem;
	$m->getfromDB($data['ID']);
	$debut=$date." 00:00:00";
	$fin=$date." 23:59:59";
	$query2 = "SELECT * FROM glpi_reservation_resa".
	" WHERE (('".$debut."' < begin AND '".$fin."' > begin) OR ('".$debut."' < end AND '".$fin."' > end) OR (begin < '".$debut."' AND end > '".$debut."') OR (begin < '".$fin."' AND end > '".$fin."')) AND id_item=".$data['ID']." ORDER BY begin";
	$result2=$db->query($query2);
	if ($db->numrows($result2)>0){
		list($annee,$mois,$jour)=split("-",$date);
		echo "<tr class='tab_bg_1'><td><a href='$target?show=resa&ID=".$data['ID']."&mois_courant=$mois&annee_courante=$annee'>".$m->getType()." - ".$m->getName()."</a></td></tr>";
		echo "<tr><td>";
		printReservationItem($target,$data['ID'],$date);
		echo "</td></tr>";
	}


}


}


}


function printReservationItem($target,$ID,$date){
		global $lang, $HTMLRel;

		$id_user=$_SESSION["glpiID"];

		$db = new DB;

		$m=new ReservationItem;
		$m->getfromDB($ID);
		$user=new User;
		list($year,$month,$day)=split("-",$date);
		$debut=$date." 00:00:00";
		$fin=$date." 23:59:59";
		$query = "SELECT * FROM glpi_reservation_resa".
		" WHERE (('".$debut."' < begin AND '".$fin."' > begin) OR ('".$debut."' < end AND '".$fin."' > end) OR (begin < '".$debut."' AND end > '".$debut."') OR (begin < '".$fin."' AND end > '".$fin."')) AND id_item=$ID ORDER BY begin";
//		echo $query."<br>";
		if ($result=$db->query($query)){
			if ($db->numrows($result)>0){
				echo "<table width='100%' >";
			while ($row=$db->fetch_array($result)){
				echo "<tr>";
				$user->getfromDBbyID($row["id_user"]);
				$display="";					
				if ($debut>$row['begin']) $heure_debut="00:00";
				else $heure_debut=get_hour_from_sql($row['begin']);

					if ($fin<$row['end']) $heure_fin="24:00";
					else $heure_fin=get_hour_from_sql($row['end']);
					
					if (strcmp($heure_debut,"00:00")==0&&strcmp($heure_fin,"24:00")==0)
						$display=$lang["reservation"][15];
					else if (strcmp($heure_debut,"00:00")==0) 
						$display=$lang["reservation"][16]."&nbsp;".$heure_fin;
					else if (strcmp($heure_fin,"24:00")==0) 
						$display=$lang["reservation"][17]."&nbsp;".$heure_debut;
					else $display=$heure_debut."-".$heure_fin;

					$delete="";
					$modif="";
					if ($_SESSION["glpiID"]==$user->fields["ID"]||isAdmin($_SESSION["glpitype"])){
						$modif="<a  href=\"".$target."?show=resa&edit=".$row['ID']."&item=$ID&mois_courant=$month&annee_courante=$year\"  title='".$row['comment']."'>";
						$modif_end="</a>";
						
						}

		
		echo "<td   align='center' class='tab_resa'>". $modif."<span>".$display."<br><b>".$user->fields["name"]."</b></span>";

			echo "</td>".$modif_end."</tr>";
				
			}

		echo "</table>";
		}
	}

}

function deleteReservation($ID){
	// Delete a Reservation

	$resa = new ReservationResa;
	
	
	if ($resa->getfromDB($ID))
	if (isset($resa->fields["id_user"])&&($resa->fields["id_user"]==$_SESSION["glpiID"]||isAdmin($_SESSION["glpitype"])))
	return $resa->deleteFromDB($ID);
	
	return false;
}


function addReservation($input,$target,$ok=true){
	// Add a Reservation
	if ($ok){
	$resa = new ReservationResa;
	
  // set new date.
   $resa->fields["id_item"] = $input["id_item"];
   $resa->fields["comment"] = $input["comment"];
   $resa->fields["id_user"] = $input["id_user"];
   $resa->fields["begin"] = $input["begin_date"]." ".$input["begin_hour"].":".$input["begin_min"].":00";
   $resa->fields["end"] = $input["end_date"]." ".$input["end_hour"].":".$input["end_min"].":00";

	if (!$resa->test_valid_date()){
		$resa->displayError("date",$input["id_item"],$target);
		return false;
	}
	
	if ($resa->is_reserved()){
		$resa->displayError("is_res",$input["id_item"],$target);
		return false;
	}
	if ($input["id_user"]>0)
		return $resa->addToDB();
	else return true;
}
}
function get_hour_from_sql($time){
$t=explode(" ",$time);
$p=explode(":",$t[1]);
return $p[0].":".$p[1];
}

function printReservationItems($target){
global $lang,$HTMLRel;

$ri=new ReservationItem;

$db=new DB;

$query="select ID from glpi_reservation_item ORDER BY device_type";

	if ($result = $db->query($query)) {
		echo "<div align='center'><table class='tab_cadre' cellspacing='5'>";
		echo "<tr><th colspan='2'>".$lang["reservation"][1]."</th></tr>";
		while ($row=$db->fetch_array($result)){
			$ri->getfromDB($row['ID']);
			if (isset($ri->obj->fields["deleted"])&&$ri->obj->fields["deleted"]=='N'){
			echo "<tr class='tab_bg_2'><td><a href='".$target."?show=resa&ID=".$row['ID']."'>".$ri->getType()." - ".$ri->getName()."</a></td>";
			echo "<td>".nl2br($ri->fields["comments"])."</td>";
			echo "</tr>";
			}
		}
	echo "</table></div>";
	
	}
}


function showReservationCommentForm($target,$ID){
	global $lang,$HTMLRel;


	$r=new ReservationItem;
	if ($r->getfromDB($ID)){

	echo "<div align='center'><form method='post' name=form action=\"$target\">";
	echo "<input type='hidden' name='ID' value='$ID'>";

	echo "<table class='tab_cadre' cellpadding='2'>";
	echo "<tr><th colspan='2'><b>";
	echo $lang["reservation"][22];
	echo "</b></th></tr>";
	// Ajouter le nom du matériel
	echo "<tr class='tab_bg_1'><td>".$lang["reservation"][4].":	</td>";
	echo "<td>";
	echo "<b>".$r->getType()." - ".$r->getName()."</b>";
    echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["reservation"][23].":	</td>";
	echo "<td>";
	echo "<textarea name='comments' cols='30' rows='10' >".$r->fields["comments"]."</textarea>";
    echo "</td></tr>";


	echo "<tr class='tab_bg_2'>";
	echo "<td colspan='2'  valign='top' align='center'>";
	echo "<input type='submit' name='updatecomment' value=\"".$lang["buttons"][14]."\" class='submit' class='submit'>";
	echo "</td></tr>\n";
	
	echo "</table></div>";
	echo "</form>";
	return true;
	} else return false;
}

function updateReservationComment($input){

	// Update  in the database

	$ri = new ReservationResa;
	$ri->getFromDB($input["ID"]);
	
	print_r($input);
	// Pop off the last two attributes, no longer needed
	$null=array_pop($input);
	print_r($input);
	exit();
	// Get all flags and fill with 0 if unchecked in form
	foreach ($ri->fields as $key => $val) {
		if (eregi("\.*flag\.*",$key)) {
			if (!isset($input[$key])) {
				$input[$key]=0;
			}
		}
	}	

	// Fill the update-array with changes
	$x=0;
	foreach ($input as $key => $val) {
		if ($ri->fields[$key] != $input[$key]) {
			$ri->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}
	if (isset($updates))
		$ri->updateInDB($updates);

}

function updateReservationResa($input,$target,$item){
global $lang;
	// Update a printer in the database

	$ri = new ReservationResa;
	$ri->getFromDB($input["ID"]);

	// Get all flags and fill with 0 if unchecked in form
	foreach ($ri->fields as $key => $val) {
		if (eregi("\.*flag\.*",$key)) {
			if (!isset($input[$key])) {
				$input[$key]=0;
			}
		}
	}	

	// Fill the update-array with changes
	$x=0;
	foreach ($input as $key => $val) {
		if ($ri->fields[$key] != $input[$key]) {
			$ri->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}
	$ri->fields["begin"]=$_POST["begin"];
	$ri->fields["end"]=$_POST["end"];
	if (!$ri->test_valid_date()){
		$ri->displayError("date",$item,$target);
		return false;
	}
	
	if ($ri->is_reserved()){
		$ri->displayError("is_res",$item,$target);
		return false;
	}
	
	
	if (isset($updates))
		$ri->updateInDB($updates);
	return true;
}

?>