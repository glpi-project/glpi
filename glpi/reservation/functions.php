<?php
/*
 
  ----------------------------------------------------------------------
  GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2004 by the INDEPNET Development Team.
 
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
 ----------------------------------------------------------------------
 Original Author of file: Julien Dombre
 Purpose of file:
 ----------------------------------------------------------------------
*/

// device_type
// 1 computers
// 2 networking
// 3 printers
// 4 monitors
// 5 peripherals
// 6 

include ("_relpos.php");
// FUNCTIONS Reservation

/// TOCHANGE
function titleReservation(){
           GLOBAL  $lang,$HTMLRel;
           
           echo "<div align='center'><table border='0'><tr><td>";

           echo "<img src=\"".$HTMLRel."pics/printer.png\" alt='".$lang["printers"][0]."' title='".$lang["printers"][0]."'>".$lang["reservation"][1]."</td>";

           echo "</td></tr></table></div>";
}

function searchFormReservationItem($field="",$phrasetype= "",$contains="",$sort= ""){
	// Print Search Form
	
	GLOBAL $cfg_install, $cfg_layout, $layout, $lang;

	$option["glpi_reservation_item.ID"]				= $lang["software"][1];
//	$option["type"]			= $lang["software"][3];
//	$option["glpi_dropdown_locations.name"]			= $lang["software"][4];
//	$option["glpi_software.version"]			= $lang["software"][5];
//	$option["glpi_software.comments"]			= $lang["software"][6];
	
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
			echo "<center><table  class='tab_cadre'><tr>";
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

			echo "<th>&nbsp;</th>";
			echo "</tr>";

			for ($i=0; $i < $numrows_limit; $i++) {
				$ID = $db->result($result_limit, $i, "ID");
				$ri = new ReservationItem;
				$ri->getfromDB($ID);
				echo "<tr class='tab_bg_2'>";
				echo "<td>";
				echo $ri->fields["ID"];
				echo "</td>";
				
				echo "<td>". $ri->getType()."</td>";
				echo "<td><b>". $ri->getLink() ."</b></td>";
				echo "<td>";
				showReservationForm($ri->fields["device_type"],$ri->fields["id_device"]);
				echo "</td>";
				echo "</tr>";
			}

			// Close Table
			echo "</table></center>";

			// Pager
			$parameters="field=$field&phrasetype=$phrasetype&contains=$contains&sort=$sort&order=$order";
			printPager($start,$numrows,$target,$parameters);

		} else {
			echo "<center><b>".$lang["peripherals"][17]."</b></center>";
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

function printCalendrier($target,$ID){
global $lang;

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
	
// on vérifie pour les années bisextiles, on ne sait jamais.
if (($annee_courante%4)==0) $fev=29; else $fev=28;
$nb_jour= array(31,$fev,31,30,31,30,31,31,30,31,30,31);

// Ces variables vont nous servir pour mettre les jours dans les bonnes colonnes    
$jour_debut_mois=strftime("%w",mktime(0,0,0,$mois_courant,1,$annee_courante));
if ($jour_debut_mois==0) $jour_debut_mois=7;
$jour_fin_mois=strftime("%w",mktime(0,0,0,$mois_courant,$nb_jour[$mois_courant-1],$annee_courante));
// on n'oublie pas de mettre le mois en français et on n'a plus qu'à mettre les en-têtes
echo "<h2><center><a href=\"".$target.$str_precedent."\">".$lang["buttons"][12]."</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".
	$lang["calendarM"][$mois_courant-1]."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"".$target.$str_suivant."\">".$lang["buttons"][11]."</a></center></h2>";
echo "<table border=1 width='100%' align='center'>";
echo "<th width='19%'>".$lang["calendarD"][1]."</th>";
echo "<th width='19%'>".$lang["calendarD"][2]."</th>";
echo "<th width='19%'>".$lang["calendarD"][3]."</th>";
echo "<th width='19%'>".$lang["calendarD"][4]."</th>";
echo "<th width='19%'>".$lang["calendarD"][5]."</th>";
echo "<th width='2%'>".$lang["calendarD"][6]."</th>";
echo "<th width='2%'>".$lang["calendarD"][0]."</th>";

echo "<tr>";

// Il faut insérer des cases vides pour mettre le premier jour du mois
// en face du jour de la semaine qui lui correspond.
for ($i=1;$i<$jour_debut_mois;$i++)
	echo "<td>&nbsp;</td>";

// voici le remplissage proprement dit
if ($mois_courant<10&&strlen($mois_courant)==1) $mois_courant="0".$mois_courant;
for ($i=1;$i<$nb_jour[$mois_courant-1]+1;$i++){
	if ($i<10) $ii="0".$i;
	else $ii=$i;
	
	echo "<td>";
	echo "<center>".$i."<center><br>";
	if (($i-1+$jour_debut_mois)%7!=6&&($i-1+$jour_debut_mois)%7!=0)
	printReservation($target,$ID,$annee_courante."-".$mois_courant."-".$ii);
//	echo $annee_courante."-".$mois_courant."-".$ii;
	if (($i-1+$jour_debut_mois)%7!=6&&($i-1+$jour_debut_mois)%7!=0)
	echo "<br><a href=\"".$target."?show=resa&add=$ID&date=".$annee_courante."-".$mois_courant."-".$ii."\">".$lang["reservation"][8]."</a>";
	echo "</td>";

// il ne faut pas oublié d'aller à la ligne suivante enfin de semaine
    if (($i+$jour_debut_mois)%7==1)
        {echo "</tr>";
       if ($i!=$nb_jour[$mois_courant-1])echo "<tr>";
       }
}

// on recommence pour finir le tableau proprement pour les mêmes raisons

if ($jour_fin_mois!=0)
for ($i=0;$i<7-$jour_fin_mois;$i++) 	echo "<td>&nbsp;</td>";

echo "</tr></table>";
	
}

function showAddReservationForm($target,$ID,$date){
	global $lang,$HTMLRel;
	if ($HTMLRel=="")$HTMLRel=".";
	echo "<center><form method='post' name=form action=\"$target\">";
	echo "<input type='hidden' name='id_user' value='".$_SESSION["glpiID"]."'>";
	echo "<input type='hidden' name='id_item' value='$ID'>";

	echo "<table class='tab_cadre' cellpadding='2'>";
	echo "<tr><th colspan='2'><b>";
	echo $lang["reservation"][9];
	echo "</b></th></tr>";
	// Ajouter le nom du matériel
	$r=new ReservationItem;
	$r->getfromDB($ID);
	echo "<tr><td>".$lang["reservation"][4].":	</td>";
	echo "<td>";
	echo "<b>".$r->getType()." - ".$r->getName()."</b>";
    echo "</td></tr>";


	echo "<tr><td>".$lang["reservation"][10].":	</td>";
	echo "<td><input type='text' name='begin_date' readonly size='10' value='$date'>";
	echo "&nbsp; <input name='button' type='button' class='button'  onClick=\"window.open('$HTMLRel/mycalendar.php?form=form&elem=begin_date&value=$date','".$lang["buttons"][15]."','width=200,height=220')\" value='".$lang["buttons"][15]."...'>";
	echo "&nbsp; <input name='button_reset' type='button' class='button' onClick=\"document.forms['form'].begin_date.value='$date'\" value='reset'>";
    echo "</td></tr>";

	echo "<tr><td>".$lang["reservation"][12].":	</td>";
	echo "<td>";
	echo "<select name='begin_hour'>";
	for ($i=0;$i<24;$i++){
	echo "<option value='$i'";
	if ($i==12) echo " selected ";
	echo ">$i</option>";
	}
	echo "</select>";
	echo ":";
	echo "<select name='begin_min'>";
	for ($i=0;$i<60;$i+=5){
	echo "<option value='$i'";
	echo ">$i</option>";
	}
	echo "</select>";
	echo "</td></tr>";

	echo "<tr><td>".$lang["reservation"][11].":	</td>";
	echo "<td><input type='text' name='end_date' readonly size='10' value='$date'>";
	echo "&nbsp; <input name='button' type='button' class='button'  onClick=\"window.open('$HTMLRel/mycalendar.php?form=form&elem=end_date&value=$date','".$lang["buttons"][15]."','width=200,height=220')\" value='".$lang["buttons"][15]."...'>";
	echo "&nbsp; <input name='button_reset' type='button' class='button' onClick=\"document.forms['form'].end_date.value='$date'\" value='reset'>";
    echo "</td></tr>";

	echo "<tr><td>".$lang["reservation"][13].":	</td>";
	echo "<td>";
	echo "<select name='end_hour'>";
	for ($i=0;$i<24;$i++){
	echo "<option value='$i'";
	if ($i==12) echo " selected ";
	echo ">$i</option>";
	}
	echo "</select>";
	echo ":";
	echo "<select name='end_min'>";
	for ($i=0;$i<60;$i+=5){
	echo "<option value='$i'";
	echo ">$i</option>";
	}
	echo "</select>";
	echo "</td></tr>";
	echo "<tr>";
	echo "<td colspan='2' class='tab_bg_2' valign='top'>";
	echo "<center><input type='submit' name='add_resa' value=\"".$lang["buttons"][8]."\" class='submit' class='submit'></center>";
	echo "</td></tr>\n";
	
	echo "</table></center>";
	echo "</form>";
}

function printReservation($target,$ID,$date){
		global $HTMLRel;

		if ($HTMLRel=="") $HTMLRel=".";

		$id_user=$_SESSION["glpiID"];

		$db = new DB;

		$m=new ReservationItem;
		$m->getfromDB($ID);
		$user=new User;

		$debut=$date." 00:00:00";
		$fin=$date." 23:59:59";
		$query = "SELECT * FROM glpi_reservation_resa".
		" WHERE (('".$debut."' < begin AND '".$fin."' > begin) OR ('".$debut."' < end AND '".$fin."' > end) OR (begin < '".$debut."' AND end > '".$debut."') OR (begin < '".$fin."' AND end > '".$fin."')) AND id_item=$ID ORDER BY begin";
//		echo $query."<br>";
		if ($result=$db->query($query)){
			if ($db->numrows($result)>0){
				echo "<table width='100%'>";
			while ($row=$db->fetch_array($result)){
				echo "<tr><td>";
				$user->getfromDBbyID($row["id_user"]);
$display="";					
				if ($debut>$row['begin']) $heure_debut="00:00";
				else $heure_debut=get_hour_from_sql($row['begin']);

					if ($fin<$row['end']) $heure_fin="24:00";
					else $heure_fin=get_hour_from_sql($row['end']);
					
					if (strcmp($heure_debut,"00:00")==0&&strcmp($heure_fin,"24:00")==0)
						$display="Journée";
					else if (strcmp($heure_debut,"00:00")==0) 
						$display="Jusqu'à ".$heure_fin;
					else if (strcmp($heure_fin,"24:00")==0) 
						$display="Dès ".$heure_debut;
					else $display=$heure_debut."-".$heure_fin;

					$delete="";

					if ($_SESSION["glpiID"]==$user->fields["ID"])
						$delete="<a border=0 href=\"".$target."?show=resa&clear=".$row['ID']."\"><img border=0 height=16 width=16 src=\"".$HTMLRel."/pics/clear.png\"></a>";

		echo $delete.$display.": ".$user->fields["name"];

			echo "</td></tr>";
				
			}

		echo "</table>";
		}
	}

}

function deleteReservation($ID){
	// Delete a Reservation

	$resa = new ReservationResa;
	
	
	if ($resa->getfromDB($ID))
	if (isset($resa->fields["id_user"])&&$resa->fields["id_user"]==$_SESSION["glpiID"])
	return $resa->deleteFromDB($ID);
	
	return false;
}


function addReservation($input){
	// Add a Reservation

	$resa = new ReservationResa;
	
  // set new date.
   $resa->fields["id_item"] = $input["id_item"];
   $resa->fields["id_user"] = $input["id_user"];
   $resa->fields["begin"] = $input["begin_date"]." ".$input["begin_hour"].":".$input["begin_min"].":00";
   $resa->fields["end"] = $input["end_date"]." ".$input["end_hour"].":".$input["end_min"].":00";

	if (!$resa->test_valid_date()){
		$resa->displayError("date");
		return false;
	}
	
	if ($resa->is_reserved()){
		$resa->displayError("is_res");
		return false;
	}
	return $resa->addToDB();

}
function get_hour_from_sql($time){
$t=explode(" ",$time);
$p=explode(":",$t[1]);
return $p[0].":".$p[1];
}

function printReservationItems(){
global $lang,$HTMLRel;

if ($HTMLRel=="")$HTMLRel=".";

$ri=new ReservationItem;

$db=new DB;

$query="select ID from glpi_reservation_item ORDER BY device_type";

	if ($result = $db->query($query)) {
		while ($row=$db->fetch_array($result)){
			$ri->getfromDB($row['ID']);
			echo "<a href='$HTMLRel/helpdesk.php?show=resa&ID=".$row['ID']."'>".$ri->getType()." - ".$ri->getName()."</a><br>";
		}
	}
}

?>