<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

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
 --------------------------------------------------------------------------
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


// FUNCTIONS Reservation


function titleReservation(){
	global  $lang,$HTMLRel;

	echo "<div align='center'><table border='0'><tr><td>";
	echo "<img src=\"".$HTMLRel."pics/reservation.png\" alt='' title=''></td><td><b><span class='icon_sous_nav'>".$lang["reservation"][1]."</span>";
	echo "</b></td><td><a class='icon_consol' href='".$HTMLRel."front/reservation.php?show=resa&amp;ID'>".$lang["reservation"][26]."</a></td></tr></table>&nbsp;</div>";
}

function searchFormReservationItem($field="",$phrasetype= "",$contains="",$sort= ""){
	// Print Search Form

	global $cfg_glpi,  $lang;
	if (!haveRight("reservation_central","r")) return false;

	$option["glpi_reservation_item.ID"]				= $lang["common"][2];
	//	$option["glpi_reservation_item.device_type"]			= $lang["reservation"][3];
	//	$option["glpi_dropdown_locations.name"]			= $lang["common"][15];
	//	$option["glpi_software.version"]			= $lang["software"][5];
	$option["glpi_reservation_item.comments"]			= $lang["common"][25];

	echo "<form method=get action=\"".$cfg_glpi["root_doc"]."/front/reservation.php\">";
	echo "<div align='center'><table class='tab_cadre_fixe'>";
	echo "<tr><th colspan='2'><b>".$lang["search"][0].":</b></th></tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>";
	echo "<input type='text' size='15' name=\"contains\" value=\"". stripslashes($contains) ."\" />&nbsp;";
	echo $lang["search"][10];
	echo "&nbsp;<select name=\"field\" size='1'>";
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
	echo "</td></tr></table></div></form>";
}

function showReservationItemList($target,$username,$field,$phrasetype,$contains,$sort,$order,$start){
	// Lists Reservation Items

	global $db,$cfg_glpi, $lang, $HTMLRel;

	if (!haveRight("reservation_central","r")) return false;

	// Build query
	if($field=="all") {
		$where=" 1 = 1 ";
	}
	else {
		$where=" ($field ".makeTextSearch($contains).") ";
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
		if ($numrows > $cfg_glpi["list_limit"]) {
			$query_limit = $query ." LIMIT $start,".$cfg_glpi["list_limit"]." ";
			$result_limit = $db->query($query_limit);
			$numrows_limit = $db->numrows($result_limit);
		} else {
			$numrows_limit = $numrows;
			$result_limit = $result;
		}

		if ($numrows_limit>0) {
			// Pager
			$parameters="field=$field&amp;phrasetype=$phrasetype&amp;contains=$contains&amp;sort=$sort&amp;order=$order";
			printPager($start,$numrows,$target,$parameters);

			// Produce headline
			echo "<div align='center'><table  class='tab_cadrehov'><tr>";
			// Name
			echo "<th>";
			if ($sort=="glpi_reservation_item.ID") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&amp;phrasetype=$phrasetype&amp;contains=$contains&amp;sort=glpi_reservation_item.ID&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start\">";
			echo $lang["common"][2]."</a></th>";

			// Device_Type			
			echo "<th>";
			if ($sort=="glpi_reservation_item.device_type") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&amp;phrasetype=$phrasetype&amp;contains=$contains&amp;sort=glpi_reservation_item.device_type&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start\">";
			echo $lang["reservation"][3]."</a></th>";

			// device
			echo "<th>";
			if ($sort=="glpi_reservation_item.id_device") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&amp;phrasetype=$phrasetype&amp;contains=$contains&amp;sort=glpi_reservation_item.id_device&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start\">";
			echo $lang["reservation"][4]."</a></th>";

			// Lieu
			echo "<th>";
			echo $lang["common"][15]."</th>";

			// Comments
			echo "<th>";
			if ($sort=="glpi_reservation_item.comments") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&amp;phrasetype=$phrasetype&amp;contains=$contains&amp;sort=glpi_reservation_item.comments&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start\">";
			echo $lang["common"][25]."</a></th>";





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
				echo "<td>". $ri->getLocation() ."</td>";

				echo "<td>". nl2br(substr($ri->fields["comments"],0,$cfg_glpi["cut"]))."</td>";
				echo "<td>";
				echo "<a href='".$target."?comment=$ID'>".$lang["reservation"][22]."</a>";
				echo "</td>";

				echo "<td>";
				showReservationForm($ri->fields["device_type"],$ri->fields["id_device"]);
				echo "</td>";
				echo "<td>";
				echo "<a href='".$target."?show=resa&amp;ID=$ID'>".$lang["reservation"][21]."</a>";
				echo "</td>";
				echo "</tr>";
			}

			// Close Table
			echo "</table></div>";

			// Pager
			echo "<br>";
			printPager($start,$numrows,$target,$parameters);

		} else {
			echo "<div align='center'><b>".$lang["reservation"][33]."</b></div>";
		}
	}

}

function showReservationForm($device_type,$id_device){

	global $cfg_glpi,$lang;

	if (!haveRight("reservation_central","w")) return false;


	if ($resaID=isReservable($device_type,$id_device)) {
		// Supprimer le matériel
		echo "<a href=\"javascript:confirmAction('".addslashes($lang["reservation"][38])."\\n".addslashes($lang["reservation"][39])."','".$cfg_glpi["root_doc"]."/front/reservation.php?ID=".$resaID."&amp;delete=delete')\">".$lang["reservation"][6]."</a>";	

	}else {
		echo "<a href=\"".$cfg_glpi["root_doc"]."/front/reservation.php?";
		echo "id_device=$id_device&amp;device_type=$device_type&amp;comments=&amp;add=add\">".$lang["reservation"][7]."</a>";      
	}
}

function printCalendrier($target,$ID=""){
	global $lang, $HTMLRel;

	if (!haveRight("reservation_helpdesk","1")) return false;

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

	$str_suivant="?show=resa&amp;ID=$ID&amp;mois_courant=$mois_suivant&amp;annee_courante=$annee_suivante";
	$str_precedent="?show=resa&amp;ID=$ID&amp;mois_courant=$mois_precedent&amp;annee_courante=$annee_precedente";


	if (!empty($ID)){
		$m=new ReservationItem;
		$m->getfromDB($ID);
		$type=$m->getType();
		$name=$m->getName();
		$all="<a href='$target?show=resa&amp;ID=&amp;mois_courant=$mois_courant&amp;annee_courante=$annee_courante'>".$lang["reservation"][26]."</a>";
	} else {
		$type="";
		$name=$lang["reservation"][25];
		$all="&nbsp;";
	}


	echo "<div align='center'><table border='0'><tr><td>";
	echo "<img src=\"".$HTMLRel."pics/reservation.png\" alt='' title=''></td><td><b><span class='icon_sous_nav'>".$type." - ".$name."</span>";
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
	echo "<table width='90%'><tr><td valign='top'  width='100'>";

	echo "<table><tr><td width='100' valign='top'>";

	// date du jour
	$today=getdate(time());
	$mois=$today["mon"];
	$annee=$today["year"];


	$annee_avant = $annee_courante - 1;
	$annee_apres = $annee_courante + 1;


	echo "<div class='verdana1'>";
	echo "<div style='text-align:center'><b>$annee_avant</b></div>";
	for ($i=$mois_courant; $i < 13; $i++) {
		echo "<div style='margin-left: 10px; padding: 2px; -moz-border-radius: 5px; margin-top: 2px; border: 1px solid #cccccc; background-color: #eeeeee;'><a href=\"".$target."?show=resa&amp;ID=$ID&amp;mois_courant=$i&amp;annee_courante=$annee_avant\">".
			$lang["calendarM"][$i-1]."</a></div>";
	}

	echo "<div style='text-align:center'><b>$annee_courante</b></div>";
	for ($i=1; $i < 13; $i++) {
		if ($i == $mois_courant) {
			echo "<div style='margin-left: 10px; padding: 2px; -moz-border-radius: 5px; margin-top: 2px; border: 1px solid #666666; background-color: white;'><b>".
				$lang["calendarM"][$i-1]."</b></div>";
		}
		else {
			echo "<div style='margin-left: 10px; padding: 2px; -moz-border-radius: 5px; margin-top: 2px; border: 1px solid #cccccc; background-color: #eeeeee;'><a href=\"".$target."?show=resa&amp;ID=$ID&amp;mois_courant=$i&amp;annee_courante=$annee_courante\">".
				$lang["calendarM"][$i-1]."</a></div>";
		}
	}

	echo "<div style='text-align:center'><b>$annee_apres</b></div>";
	for ($i=1; $i < $mois_courant+1; $i++) {
		echo "<div style='margin-left: 10px; padding: 2px; -moz-border-radius: 5px; margin-top: 2px; border: 1px solid #cccccc; background-color: #eeeeee;'><a href=\"".$target."?show=resa&amp;ID=$ID&amp;mois_courant=$i&amp;annee_courante=$annee_apres\">".
			$lang["calendarM"][$i-1]."</a></div>";
	}
	echo "</div>";

	echo "</td></tr></table>";

	echo "</td><td valign='top' width='100%'>";



	// test 


	echo "<table class='tab_cadre' width='100%'><tr>";
	echo "<th width='14%'>".$lang["calendarD"][1]."</th>";
	echo "<th width='14%'>".$lang["calendarD"][2]."</th>";
	echo "<th width='14%'>".$lang["calendarD"][3]."</th>";
	echo "<th width='14%'>".$lang["calendarD"][4]."</th>";
	echo "<th width='14%'>".$lang["calendarD"][5]."</th>";
	echo "<th width='14%'>".$lang["calendarD"][6]."</th>";
	echo "<th width='14%'>".$lang["calendarD"][0]."</th>";
	echo "</tr>";
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
			echo "<tr><td align='center'><a href=\"".$target."?show=resa&amp;add=$ID&amp;date=".$annee_courante."-".$mois_courant."-".$ii."\"><img style='color: blue; font-family: Arial, Sans, sans-serif; font-size: 10px;' src=\"".$HTMLRel."pics/addresa.png\" alt='".$lang["reservation"][8]."' title='".$lang["reservation"][8]."'></a></td></tr>";
		}
		//if (($i-1+$jour_debut_mois)%7!=6&&($i-1+$jour_debut_mois)%7!=0){
		echo "<tr><td>";
		printReservation($target,$ID,$annee_courante."-".$mois_courant."-".$ii);
		echo "</td></tr>";
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

	if (!haveRight("reservation_helpdesk","1")) return false;

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
	$begin_hour=date("H:i",$begin);
	$end_hour=date("H:i",$end);

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
	if (!haveRight("reservation_central","w"))
		echo "<input type='hidden' name='id_user' value='".$_SESSION["glpiID"]."'>";
	else {
		echo "<tr class='tab_bg_2'><td>".$lang["reservation"][31].":	</td>";
		echo "<td>";
		if ($resaID==-1)
			dropdownAllUsers("id_user",$_SESSION["glpiID"]);
		else dropdownAllUsers("id_user",$resa->fields["id_user"]);
		echo "</td></tr>";

	}


	echo "<tr class='tab_bg_2'><td>".$lang["search"][8].":	</td><td>";
	showCalendarForm("form","begin_date",$begin_date);
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td>".$lang["reservation"][12].":	</td>";
	echo "<td>";

	dropdownHours("begin_hour",$begin_hour,1);
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td>".$lang["search"][9].":	</td><td>";
	showCalendarForm("form","end_date",$end_date);
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td>".$lang["reservation"][13].":	</td>";
	echo "<td>";
	dropdownHours("end_hour",$end_hour,1);
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

	echo "<tr class='tab_bg_2'><td>".$lang["common"][25].":	</td>";
	echo "<td><textarea name='comment'rows='8' cols='30'>".$resa->fields["comment"]."</textarea>";
	echo "</td></tr>";

	if ($resaID==-1){
		echo "<tr class='tab_bg_2'>";
		echo "<td colspan='2'  valign='top' align='center'>";
		echo "<input type='submit' name='add_resa' value=\"".$lang["buttons"][8]."\" class='submit'>";
		echo "</td></tr>\n";
	} else {
		echo "<tr class='tab_bg_2'>";
		echo "<td valign='top' align='center'>";
		echo "<input type='submit' name='clear_resa' value=\"".$lang["buttons"][6]."\" class='submit'>";
		echo "</td><td valign='top' align='center'>";
		echo "<input type='submit' name='edit_resa' value=\"".$lang["buttons"][14]."\" class='submit'>";
		echo "</td></tr>\n";
	}

	echo "</table>";
	echo "</form></div>";
}

function printReservation($target,$ID,$date){
	global $db;
	if (!empty($ID))
		printReservationItem($target,$ID,$date);
	else  {

		$debut=$date." 00:00:00";
		$fin=$date." 23:59:59";

		$query = "SELECT DISTINCT glpi_reservation_item.ID FROM glpi_reservation_item INNER JOIN glpi_reservation_resa ON (glpi_reservation_item.ID = glpi_reservation_resa.id_item )".
			" WHERE (('".$debut."' < begin AND '".$fin."' > begin) OR ('".$debut."' < end AND '".$fin."' > end) OR (begin < '".$debut."' AND end > '".$debut."') OR (begin < '".$fin."' AND end > '".$fin."')) ORDER BY begin";
		//echo $query;
		$result=$db->query($query);

		if ($db->numrows($result)>0){
			$m=new ReservationItem;

			while ($data=$db->fetch_array($result)){

				$m->getfromDB($data['ID']);

				list($annee,$mois,$jour)=split("-",$date);
				echo "<tr class='tab_bg_1'><td><a href='$target?show=resa&amp;ID=".$data['ID']."&amp;mois_courant=$mois&amp;annee_courante=$annee'>".$m->getType()." - ".$m->getName()."</a></td></tr>";
				echo "<tr><td>";
				printReservationItem($target,$data['ID'],$date);
				echo "</td></tr>";
			}
		}
	}

}


function printReservationItem($target,$ID,$date){
	global $db,$lang, $HTMLRel;

	$id_user=$_SESSION["glpiID"];

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
				$user->getfromDB($row["id_user"]);
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
				$modif_end="";
				$comment="";
				$rand=mt_rand();
				if ($_SESSION["glpiID"]==$user->fields["ID"]||haveRight("reservation_central","r")){
					$modif="<a onmouseout=\"cleanhide('content_".$ID.$rand."')\" onmouseover=\"cleandisplay('content_".$ID.$rand."')\" href=\"".$target."?show=resa&amp;edit=".$row['ID']."&amp;item=$ID&amp;mois_courant=$month&amp;annee_courante=$year\">";
					$modif_end="</a>";
					$comment="<div class='over_link' id='content_".$ID.$rand."'>".nl2br($row["comment"])."</div>";
				}


				echo "<td   align='center' class='tab_resa'>". $modif."<span>".$display."<br><b>".$user->fields["name"]."</b></span>";


				echo $modif_end.$comment."</td></tr>";

			}

			echo "</table>";
		}
	}

}


function printReservationItems($target){
	global $db,$lang,$HTMLRel;

	if (!haveRight("reservation_helpdesk","1")) return false;

	$ri=new ReservationItem;


	$query="select ID from glpi_reservation_item ORDER BY device_type";

	if ($result = $db->query($query)) {
		echo "<div align='center'><table class='tab_cadre' cellpadding='5'>";
		echo "<tr><th colspan='3'>".$lang["reservation"][1]."</th></tr>";
		while ($row=$db->fetch_array($result)){
			$ri->getfromDB($row['ID']);
			if (isset($ri->obj->fields["deleted"])&&$ri->obj->fields["deleted"]=='N'){
				echo "<tr class='tab_bg_2'><td><a href='".$target."?show=resa&amp;ID=".$row['ID']."'>".$ri->getType()." - ".$ri->getName()."</a></td>";
				echo "<td>".$ri->getLocation()."</td>";
				echo "<td>".nl2br($ri->fields["comments"])."</td>";
				echo "</tr>";
			}
		}
		echo "</table></div>";

	}
}


function showReservationCommentForm($target,$ID){
	global $lang,$HTMLRel;

	if (!haveRight("reservation_central","w")) return false;

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

		echo "<tr class='tab_bg_1'><td>".$lang["common"][25].":	</td>";
		echo "<td>";
		echo "<textarea name='comments' cols='30' rows='10' >".$r->fields["comments"]."</textarea>";
		echo "</td></tr>";


		echo "<tr class='tab_bg_2'>";
		echo "<td colspan='2'  valign='top' align='center'>";
		echo "<input type='submit' name='updatecomment' value=\"".$lang["buttons"][14]."\" class='submit'>";
		echo "</td></tr>\n";

		echo "</table>";
		echo "</form></div>";
		return true;
	} else return false;
}

function showDeviceReservations($target,$type,$ID){
	global $db,$lang,$cfg_glpi;
	$resaID=0;

	if (!haveRight("reservation_central","r")) return false;

	if ($resaID=isReservable($type,$ID)){
		echo "<div align='center'>";

		echo "<a href='".$cfg_glpi["root_doc"]."/front/reservation.php?show=resa&ID=$resaID'>".$lang["reservation"][21]."</a>";
		$now=date("Y-m-d H:i:s");
		// Print reservation in progress
		$query = "SELECT * FROM glpi_reservation_resa WHERE end > '".$now."' AND id_item='$resaID' ORDER BY begin";
		$result=$db->query($query);

		echo "<table class='tab_cadrehov'><tr><th colspan='4'>".$lang["reservation"][35]."</th></tr>";
		if ($db->numrows($result)==0){	
			echo "<tr class='tab_bg_2'><td align='center' colspan='4'>".$lang["reservation"][37]."</td></tr>";
		} else {
			echo "<tr><th>".$lang["search"][8]."</th><th>".$lang["search"][9]."</th><th>".$lang["reservation"][31]."</th><th>".$lang["common"][25]."</th></tr>";
			while ($data=$db->fetch_assoc($result)){
				echo "<tr class='tab_bg_2'>";
				echo "<td align='center'>".convDateTime($data["begin"])."</td>";
				echo "<td align='center'>".convDateTime($data["end"])."</td>";
				echo "<td align='center'>".getUserName($data["id_user"])."</td>";
				echo "<td align='center'>".nl2br($data["comment"])."</td>";
				echo "</tr>";
			}
		}
		echo "</table>";
		echo "<br>";
		// Print old reservations

		$query = "SELECT * FROM glpi_reservation_resa WHERE end <= '".$now."' AND id_item='$resaID' ORDER BY begin DESC";
		$result=$db->query($query);

		echo "<table class='tab_cadrehov'><tr><th colspan='4'>".$lang["reservation"][36]."</th></tr>";
		if ($db->numrows($result)==0){	
			echo "<tr class='tab_bg_2'><td align='center' colspan='4'>".$lang["reservation"][37]."</td></tr>";
		} else {
			echo "<tr><th>".$lang["search"][8]."</th><th>".$lang["search"][9]."</th><th>".$lang["reservation"][31]."</th><th>".$lang["common"][25]."</th></tr>";
			while ($data=$db->fetch_assoc($result)){
				echo "<tr class='tab_bg_2'>";
				echo "<td align='center'>".convDateTime($data["begin"])."</td>";
				echo "<td align='center'>".convDateTime($data["end"])."</td>";
				echo "<td align='center'>".getUserName($data["id_user"])."</td>";
				echo "<td align='center'>".nl2br($data["comment"])."</td>";
				echo "</tr>";
			}
		}
		echo "</table>";
		echo "<br>";

		echo "</div>";

	} else echo "<div align='center'><strong>".$lang["reservation"][34]."</strong></div>";
}

function showUserReservations($target,$ID){
	global $db,$lang,$cfg_glpi;
	$resaID=0;

	if (!haveRight("reservation_central","r")) return false;

	echo "<div align='center'>";

	$now=date("Y-m-d H:i:s");

	// Print reservation in progress
	$query = "SELECT * FROM glpi_reservation_resa WHERE end > '".$now."' AND id_user='$ID' ORDER BY begin";
	$result=$db->query($query);
	$ri=new ReservationItem();
	echo "<table class='tab_cadrehov'><tr><th colspan='5'>".$lang["reservation"][35]."</th></tr>";
	if ($db->numrows($result)==0){	
		echo "<tr class='tab_bg_2'><td align='center' colspan='5'>".$lang["reservation"][37]."</td></tr>";
	} else {
		echo "<tr><th>".$lang["search"][8]."</th><th>".$lang["search"][9]."</th><th>".$lang["common"][1]."</th><th>".$lang["reservation"][31]."</th><th>".$lang["common"][25]."</th></tr>";

		while ($data=$db->fetch_assoc($result)){
			echo "<tr class='tab_bg_2'>";
			echo "<td align='center'>".convDateTime($data["begin"])."</td>";
			echo "<td align='center'>".convDateTime($data["end"])."</td>";
			if ($ri->getFromDB($data["id_item"]))
				echo "<td align='center'>".$ri->getLink()."</td>";
			else echo "<td align='center'>&nbsp;</td>";
			echo "<td align='center'>".getUserName($data["id_user"])."</td>";
			echo "<td align='center'>".nl2br($data["comment"])."</td>";
			echo "</tr>";
		}
	}
	echo "</table>";
	echo "<br>";
	// Print old reservations

	$query = "SELECT * FROM glpi_reservation_resa WHERE end <= '".$now."' AND id_user='$ID' ORDER BY begin DESC";
	$result=$db->query($query);

	echo "<table class='tab_cadrehov'><tr><th colspan='5'>".$lang["reservation"][36]."</th></tr>";
	if ($db->numrows($result)==0){	
		echo "<tr class='tab_bg_2'><td align='center' colspan='5'>".$lang["reservation"][37]."</td></tr>";
	} else {
		echo "<tr><th>".$lang["search"][8]."</th><th>".$lang["search"][9]."</th><th>".$lang["common"][1]."</th><th>".$lang["reservation"][31]."</th><th>".$lang["common"][25]."</th></tr>";
		while ($data=$db->fetch_assoc($result)){
			echo "<tr class='tab_bg_2'>";
			echo "<td align='center'>".convDateTime($data["begin"])."</td>";
			echo "<td align='center'>".convDateTime($data["end"])."</td>";
			if ($ri->getFromDB($data["id_item"]))
				echo "<td align='center'>".$ri->getLink()."</td>";
			else echo "<td align='center'>&nbsp;</td>";
			echo "<td align='center'>".getUserName($data["id_user"])."</td>";
			echo "<td align='center'>".nl2br($data["comment"])."</td>";
			echo "</tr>";
		}
	}
	echo "</table>";
	echo "<br>";

	echo "</div>";

}

function isReservable($type,$ID){

	global $db;
	$query="SELECT ID FROM glpi_reservation_item WHERE device_type='$type' AND id_device='$ID'";
	$result=$db->query($query);
	if ($db->numrows($result)==0){
		return false;
	} else return $db->result($result,0,0);
}

?>
