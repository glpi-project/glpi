<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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



if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}


function showCentralReminder($entity = -1, $parent = false){
	// show reminder that are not planned 

	global $DB,$CFG_GLPI, $LANG;

	$author=$_SESSION['glpiID'];	
	$today=$_SESSION["glpi_currenttime"];

	if ($entity < 0) {

		$query = "SELECT * FROM glpi_reminder " .
				"WHERE FK_users='$author' AND private=1 AND (end>='$today' or rv='0') " .
				"ORDER BY `name`";
		$titre = "<a href=\"".$CFG_GLPI["root_doc"]."/front/reminder.php\">".$LANG["reminder"][0]."</a>";	
		$private  = 1;

	} else if ($entity == $_SESSION["glpiactive_entity"]) {
		
		$query = "SELECT * FROM glpi_reminder " .
				"WHERE private=0 ".getEntitiesRestrictRequest("AND","glpi_reminder","",$entity). 
				" ORDER BY `name`";
		$titre = "<a href=\"".$CFG_GLPI["root_doc"]."/front/reminder.php\">".$LANG["reminder"][1]."</a> (".getdropdownName("glpi_entities", $entity).")";
		
		if (haveRight("reminder_public","w")) {
			$private  = 0;
		}
		
	} else if ($parent) {
		
		$query = "SELECT * FROM glpi_reminder " .
				"WHERE private=0 AND recursive=1 ".getEntitiesRestrictRequest("AND","glpi_reminder","",$entity). 
				" ORDER BY `name`";
		$titre = $LANG["reminder"][1]." (".getdropdownName("glpi_entities", $entity).")";		
		
	} else { // Filles
		
		$query = "SELECT * FROM glpi_reminder " .
				"WHERE private = 0 ".getEntitiesRestrictRequest("AND","glpi_reminder","",$entity). 
				" ORDER BY `name`";
		$titre = $LANG["reminder"][1]." (".getdropdownName("glpi_entities", $entity).")";

	}

	$result = $DB->query($query);
	$nb=$DB->numrows($result);

	if ($nb || isset($private)) {
		echo "<br><table class='tab_cadrehov'>";
	
		echo "<tr><th><div class='relative'><span>$titre</span>";
		if (isset($private)){
			echo "<span class='reminder_right'><a href=\"".$CFG_GLPI["root_doc"]."/front/reminder.form.php?private=$private\"><img src=\"".$CFG_GLPI["root_doc"]."/pics/plus.png\" alt='+' title='".$LANG["buttons"][8]."'></a></span>";
		}
		echo "</div></th></tr>\n";
	}
	if ($nb) {
		$rand=mt_rand(); 
		while ($data =$DB->fetch_array($result)){ 

			echo "<tr class='tab_bg_2'><td><div class='relative'><div class='reminder_list'><a href=\"".$CFG_GLPI["root_doc"]."/front/reminder.form.php?ID=".$data["ID"]."\">".$data["name"]."</a>";
			echo "&nbsp;<img alt='' src='".$CFG_GLPI["root_doc"]."/pics/aide.png' onmouseout=\"cleanhide('content_reminder_".$data["ID"].$rand."')\" onmouseover=\"cleandisplay('content_reminder_".$data["ID"].$rand."')\">";
			echo "<div class='over_link' id='content_reminder_".$data["ID"].$rand."'>".$data["text"]."</div>";

			if($data["rv"]=="1"){

				$tab=explode(" ",$data["begin"]);
				$date_url=$tab[0];

				echo "<span class='reminder_right'><a href=\"".$CFG_GLPI["root_doc"]."/front/planning.php?date=".$date_url."&amp;type=day\"><img src=\"".$CFG_GLPI["root_doc"]."/pics/rdv.png\" alt='".$LANG["Menu"][29]."' title='".convDateTime($data["begin"])."=>".convDateTime($data["end"])."'></a></span>";

			}
			echo "</div></div></td></tr>\n";
		}
	}

	if ($nb || isset($private)) {
		echo "</table>";
	}
}


function showListReminder($private=1,$recursive=0){
	// show reminder that are not planned 

	global $DB,$CFG_GLPI, $LANG;

	$planningRight=haveRight("show_planning","1");

	$author=$_SESSION['glpiID'];	

	if(!$private && $recursive){ // show public reminder
		$query="SELECT * FROM glpi_reminder WHERE private=0 and recursive = 1  ".getEntitiesRestrictRequest("AND","glpi_reminder","","",true);
		$titre=$LANG["reminder"][16];
	} else if(!$private && !$recursive){ // show public reminder
		$query="SELECT * FROM glpi_reminder WHERE private=0 and recursive = 0 ".getEntitiesRestrictRequest("AND","glpi_reminder");
		$titre=$LANG["reminder"][1];
	} else { // show private reminder
		$query="SELECT * FROM glpi_reminder WHERE FK_users='$author' AND private = 1 ";
		$titre=$LANG["reminder"][0];
	}


	$result = $DB->query($query);

	$tabremind=array();

	$remind=new Reminder();

	if ($DB->numrows($result)>0)
		for ($i=0 ; $data=$DB->fetch_array($result) ; $i++) {
			$remind->getFromDB($data["ID"]);

			if($data["rv"]==1){ //Un rdv on va trier sur la date begin
				$sort=$data["begin"];
			}else{ // non programmé on va trier sur la date de modif...
				$sort=$data["date"];
			}

			$tabremind[$sort."$$".$i]["id_reminder"]=$remind->fields["ID"];
			$tabremind[$sort."$$".$i]["FK_users"]=$remind->fields["FK_users"];
			$tabremind[$sort."$$".$i]["entity"]=$remind->fields["FK_entities"];
			$tabremind[$sort."$$".$i]["begin"]=($data["rv"]==1?"".$data["begin"]."":"".$data["date"]."");
			$tabremind[$sort."$$".$i]["end"]=($data["rv"]==1?"".$data["end"]."":"");
			$tabremind[$sort."$$".$i]["name"]=resume_text($remind->fields["name"],$CFG_GLPI["cut"]);
			$tabremind[$sort."$$".$i]["text"]=resume_text($remind->fields["text"],$CFG_GLPI["cut"]);
		}



	ksort($tabremind);


	
	echo "<br><table class='tab_cadre_fixehov'>";
	if ($private) {
		echo "<tr><th>"."$titre"."</th><th colspan='2'>".$LANG["common"][27]."</th></tr>";
	} else {
		echo "<tr><th colspan='5'>"."$titre"."</th></tr>" .
			 "<tr><th>".$LANG["entity"][0]."</th><th>".$LANG["common"][37]."</th><th>".$LANG["title"][37]."</th>" .
			 "<th colspan='2'>".$LANG["common"][27]."</th></tr>";
		
	}

	if (count($tabremind)>0){
		foreach ($tabremind as $key => $val){

			echo "<tr class='tab_bg_2'>";
			
			if (!$private) {
				// preg to split line (if needed) before ">" sign in completename
				echo "<td>" .preg_replace("/ ([[:alnum:]])/", "&nbsp;\\1", getdropdownName("glpi_entities", $val["entity"])). "</td>".
					 "<td>" .getdropdownName("glpi_users", $val["FK_users"]) . "</td>";
			}
			echo 	"<td width='60%' class='left'><a href=\"".$CFG_GLPI["root_doc"]."/front/reminder.form.php?ID=".$val["id_reminder"]."\">".$val["name"]."</a>" .
				"<div class='kb_resume'>".resume_text($val["text"],125);
				
			/*
			if ($type != 'private') {
				echo "<br />&nbsp;<br /><strong>".
					getdropdownName("glpi_entities", $val["entity"]). "</strong> / ".
					getdropdownName("glpi_users", $val["FK_users"]);
			} 
			*/
			echo "</div></td>";

			if($val["end"]!=""){	
				echo "<td class='center'>";

				$tab=explode(" ",$val["begin"]);
				$date_url=$tab[0];
				if ($planningRight){
					echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/planning.php?date=".$date_url."&amp;type=day\">";
				}
				echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/rdv.png\" alt='".$LANG["Menu"][29]."' title='".$LANG["Menu"][29]."'>";
				if ($planningRight){
					echo "</a>";
				}
				echo "</td>";
				echo "<td class='center' >".convDateTime($val["begin"]);
				echo "<br>".convDateTime($val["end"])."";

			}else{
				echo "<td>&nbsp;";
				echo "</td>";
				echo "<td  class='center'><span style='color:#aaaaaa;'>".convDateTime($val["begin"])."</span>";

			}
			echo "</td></tr>";
		}

	}

	echo "</table>";
}







?>
