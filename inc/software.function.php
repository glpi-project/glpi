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

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
	die("Sorry. You can't access directly to this file");
}

/**
 * Show Versions of a software
 *
 * @param $softwares_id ID of the software
 * @return nothing
 */
function showVersions($softwares_id) {
	global $DB, $CFG_GLPI, $LANG;

	if (!haveRight("software", "r"))
		return false;

	$soft = new Software;
	$canedit = $soft->can($softwares_id,"w");

	echo "<div class='center'>";

	$query = "SELECT glpi_softwaresversions.*,glpi_states.name AS sname FROM glpi_softwaresversions
				LEFT JOIN glpi_states ON (glpi_states.id=glpi_softwaresversions.states_id)
				WHERE (softwares_id = '$softwares_id') ORDER BY name";

	initNavigateListItems(SOFTWAREVERSION_TYPE,$LANG['help'][31] ." = ". $soft->fields["name"]);

	if ($result=$DB->query($query)){
		if ($DB->numrows($result)){
			echo "<table class='tab_cadre'><tr>";
			echo "<th>".$LANG['software'][5]."</th>";
			echo "<th>".$LANG['state'][0]."</th>";
			echo "<th>".$LANG['software'][19]."</th>";
			echo "<th>".$LANG['common'][25]."</th>";
			echo "</tr>";
			for ($tot=$nb=0;$data=$DB->fetch_assoc($result);$tot+=$nb){
				addToNavigateListItems(SOFTWAREVERSION_TYPE,$data['id']);
				$nb=countInstallationsForVersion($data['id']);

				// Show version if canedit (to update/delete) or if nb (to see installations)
				if ($canedit || $nb) {
					echo "<tr class='tab_bg_2'>";
					echo "<td><a href='softwareversion.form.php?id=".$data['id']."'>".$data['name'].(empty($data['name'])?$data['id']:"")."</a></td>";
					echo "<td align='right'>".$data['sname']."</td>";
					echo "<td align='right'>$nb</td>";
					echo "<td>".$data['comment']."</td></tr>";
				}
			}
			echo "<tr class='tab_bg_1'><td></td><td align='right'>".$LANG['common'][33]."</td><td align='right'>$tot</td><td>";
			if ($canedit){
				echo "<a href='softwareversion.form.php?softwares_id=$softwares_id'>".$LANG['software'][7]."</a>";
			}
			echo "</td></tr>";
			echo "</table>";
		} else {
			echo "<table class='tab_cadre_fixe'>";
			echo "<tr><th>".$LANG['search'][15]."</th></tr>";
			if ($canedit){
				echo "<tr class='tab_bg_2'><td align='center'><a href='softwareversion.form.php?softwares_id=$softwares_id'>".$LANG['software'][7]."</a></td></tr>";
			}
			echo "</table>";
		}

	}
	echo "</div>";
}

/**
 * Show number of installation per entity
 *
 * @param $softwaresversions_id ID of the version
 *
 * @return nothing
 */
function showInstallationsByEntity($softwaresversions_id) {
	global $DB, $CFG_GLPI, $LANG;

	if (!haveRight("software", "r") || !$softwaresversions_id)
		return false;

	echo "<div class='center'>";
	echo "<table class='tab_cadre'><tr>";
	echo "<th>".$LANG['entity'][0]."</th>";
	echo "<th>".$LANG['software'][19]."</th>";
	echo "</tr>";

	$tot=0;
	if (in_array(0,$_SESSION["glpiactiveentities"])) {
		$nb = countInstallationsForVersion($softwaresversions_id,0);
		if ($nb>0) {
			echo "<tr class='tab_bg_2'><td>" . $LANG['entity'][1] . "</td><td>" . $nb . "</td></tr>\n";
			$tot+=$nb;
		}
	}
	$sql = "SELECT id,completename FROM glpi_entities " .
		getEntitiesRestrictRequest('WHERE', 'glpi_entities') .
		" ORDER BY completename";
	foreach ($DB->request($sql) as $ID => $data) {
		$nb = countInstallationsForVersion($softwaresversions_id,$ID);
		if ($nb>0) {
			echo "<tr class='tab_bg_2'><td>" . $data["completename"] . "</td><td>" . $nb . "</td></tr>\n";
			$tot+=$nb;
		}
	}
	if ($tot>0) {
		echo "<tr class='tab_bg_1'><td>" . $LANG['common'][33] . "</td><td><strong>" . $tot . "</strong></td></tr>\n";
	} else {
		echo "<tr class='tab_bg_1'><td colspan='2'>" . $LANG['search'][15] . "</strong></td></tr>\n";
	}
	echo "</table></div>";
}

/**
 * Show softwares candidates to be merged
 *
 * @param $ID ID of the software
 * @return nothing
 */
function showSoftwareMergeCandidates($ID) {
	global $DB, $CFG_GLPI, $LANG, $INFOFORM_PAGES;

	$soft = new Software();
	$soft->check($ID,"w");
	$rand=mt_rand();

	echo "<div class='center'>";
	$sql = "SELECT glpi_softwares.id, glpi_softwares.name, glpi_entities.completename AS entity " .
			"FROM glpi_softwares " .
			"LEFT JOIN glpi_entities ON (glpi_softwares.entities_id=glpi_entities.id) " .
			"WHERE glpi_softwares.id!=$ID AND glpi_softwares.name='".addslashes($soft->fields["name"])."'".
				"AND glpi_softwares.is_deleted=0 AND glpi_softwares.is_template=0 " .
         getEntitiesRestrictRequest('AND', 'glpi_softwares','entities_id',getSonsOf("glpi_entities",$soft->fields["entities_id"]),false).
			"ORDER BY glpi_entities.completename";
	$req = $DB->request($sql);

	if ($req->numrows()) {
		echo "<form method='post' name='mergesoftware_form$rand' id='mergesoftware_form$rand' action='".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[SOFTWARE_TYPE]."'>";
		echo "<table class='tab_cadrehov'><tr><th>&nbsp;</th>";
		echo "<th>".$LANG['common'][16]."</th>";
		echo "<th>".$LANG['entity'][0]."</th>";
		echo "<th>".$LANG['software'][19]."</th>";
		echo "<th>".$LANG['software'][11]."</th></tr>";

		foreach($req as $data) {
			echo "<tr class='tab_bg_2'>";
			echo "<td><input type='checkbox' name='item[".$data["id"]."]' value='1'></td>";
			echo "<td<a href='".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[SOFTWARE_TYPE]."?id=".$data["id"]."'>".$data["name"]."</a></td>";
			echo "<td>".$data["entity"]."</td>";
			echo "<td align='right'>".countInstallationsForSoftware($data["id"])."</td>";
			echo "<td align='right'>".getNumberOfLicences($data["id"])."</td></tr>\n";
		}

		echo "</table>";

		echo "<table width='80%' class='tab_glpi'>";
		echo "<tr><td><img src=\"".$CFG_GLPI["root_doc"]."/pics/arrow-left.png\" alt=''></td><td><a onclick= \"if ( markCheckboxes('mergesoftware_form$rand') ) return false;\" href='".$_SERVER['PHP_SELF']."?select=all'>".$LANG['buttons'][18]."</a></td>";
		echo "<td>/</td><td ><a onclick=\"if ( unMarkCheckboxes('mergesoftware_form$rand') ) return false;\" href='".$_SERVER['PHP_SELF']."?select=none'>".$LANG['buttons'][19]."</a>";
		echo "</td><td class='left' width='80%'>";
		echo "<input type='hidden' name='id' value=$ID><input type='submit' name='mergesoftware' value=\"".$LANG['software'][48]."\" class='submit'>";
		echo "</td></table>";
		echo "</form>";
	} else {
		echo $LANG['search'][15];
	}

	echo "</div>";
}


/**
 * Merge softwares
 *
 * @param $ID ID of the software (destination)
 * @param $item array of software ID to be merged
 *
 * @return boolean about success
 */
function mergeSoftware($ID, $item) {
	global $DB, $LANG;

	echo "<div class='center'>";
	echo "<table class='tab_cadrehov'><tr><th>".$LANG['software'][47]."</th></tr>";

	echo "<tr class='tab_bg_2'><td>";
	createProgressBar($LANG['rulesengine'][90]);
	echo "</td></tr></table>";
	echo "</div>";

	$item=array_keys($item);

	// Search for software version
	$req = $DB->request("glpi_softwaresversions", array("softwares_id"=>$item));
	$i=0;
	if ($nb=$req->numrows()) {
		foreach ($req as $from) {

			$found=false;
			foreach ($DB->request("glpi_softwaresversions", array("softwares_id"=>$ID, "name"=>$from["name"])) as $dest) {
				// Update version ID on License
				$sql="UPDATE glpi_softwareslicenses SET softwaresversions_id_buy='".$dest["id"]."' WHERE softwaresversions_id_buy='".$from["id"]."'";
				$DB->query($sql);
				$sql="UPDATE glpi_softwareslicenses SET softwaresversions_id_use='".$dest["id"]."' WHERE softwaresversions_id_use='".$from["id"]."'";
				$DB->query($sql);

				// Move installation to existing version in destination software
				$sql="UPDATE glpi_computers_softwaresversions SET softwaresversions_id='".$dest["id"]."' WHERE softwaresversions_id='".$from["id"]."'";
				$found=$DB->query($sql);
			}
			if ($found) {
				// Installation has be moved, delete the source version
				$sql="DELETE FROM glpi_softwaresversions WHERE id='".$from["id"]."'";
			} else {
				// Move version to destination software
				$sql="UPDATE glpi_softwaresversions SET softwares_id='$ID' WHERE id='".$from["id"]."'";
			}
			if ($DB->query($sql)) $i++;
			changeProgressBarPosition($i,$nb+1);
		}
	}
	// Move software license
	$sql="UPDATE glpi_softwareslicenses SET softwares_id='$ID' WHERE softwares_id IN ('".implode("','",$item)."')";
	if ($DB->query($sql)) $i++;

	if ($i==($nb+1)) {
		//error_log ("All merge operations ok.");

		foreach ($item as $old) {
			putSoftwareInTrash($old,$LANG['software'][49]);
		}
	}
	changeProgressBarPosition($i,$nb+1,$LANG['rulesengine'][91]);

	return $i==($nb+1);
}

/**
 * Show Licenses of a software
 *
 * @param $softwares_id ID of the software
 * @return nothing
 */
function showLicenses($softwares_id) {
	global $DB, $CFG_GLPI, $LANG;

	$software = new Software;
	$license = new SoftwareLicense;
	$computer = new Computer();

	if (!$software->getFromDB($softwares_id) || !$software->can($softwares_id,"r")) {
		return false;
	}

	if (isset($_REQUEST["start"])) {
		$start = $_REQUEST["start"];
	} else {
		$start = 0;
	}

	if (isset($_REQUEST["sort"]) && !empty($_REQUEST["sort"])) {
		$sort = "`".$_REQUEST["sort"]."`";
	} else {
		$sort = "entity, name";
	}

	if (isset($_REQUEST["order"]) && $_REQUEST["order"]=="DESC") {
		$order = "DESC";
	} else {
		$order = "ASC";
	}

	// Righ type is enough. Can add a License on a software we have Read access
	$canedit = haveRight("software", "w");

	// Total Number of events
	$number = countElementsInTable("glpi_softwareslicenses", "glpi_softwareslicenses.softwares_id = $softwares_id " . getEntitiesRestrictRequest('AND', 'glpi_softwareslicenses', '', '', true));
	echo "<br><div class='center'>";
	if ($number < 1) {
		echo "<table class='tab_cadre_fixe'>";
		echo "<tr><th>".$LANG['search'][15]."</th></tr>";
		if ($canedit){
			echo "<tr class='tab_bg_2'><td align='center'><a href='softwarelicense.form.php?softwares_id=$softwares_id'>".$LANG['software'][8]."</a></td></tr>";
		}
		echo "</table>";
		echo "</div>";
		return;
	}

	// Display the pager
	printAjaxPager($LANG['software'][11],$start,$number);


	$rand=mt_rand();
	$query = "SELECT glpi_softwareslicenses.*, buyvers.name as buyname, usevers.name AS usename, glpi_entities.completename AS entity, glpi_softwareslicensestypes.name AS typename
		FROM glpi_softwareslicenses
		LEFT JOIN glpi_softwaresversions AS buyvers ON (buyvers.id = glpi_softwareslicenses.softwaresversions_id_buy)
		LEFT JOIN glpi_softwaresversions AS usevers ON (usevers.id = glpi_softwareslicenses.softwaresversions_id_use)
		LEFT JOIN glpi_entities ON (glpi_entities.id = glpi_softwareslicenses.entities_id)
		LEFT JOIN glpi_softwareslicensestypes ON (glpi_softwareslicensestypes.id = glpi_softwareslicenses.softwareslicensestypes_id)
		WHERE (glpi_softwareslicenses.softwares_id = '$softwares_id') " .
			getEntitiesRestrictRequest('AND', 'glpi_softwareslicenses', '', '', true) .
		"ORDER BY " . $sort." ".$order . " LIMIT ".intval($start)."," . intval($_SESSION['glpilist_limit']);

	initNavigateListItems(SOFTWARELICENSE_TYPE,$LANG['help'][31] ." = ". $software->fields["name"]);

	if ($result=$DB->query($query)){
		if ($DB->numrows($result)){
			if ($canedit){

				echo "<form method='post' name='massiveactionlicense_form$rand' id='massiveactionlicense_form$rand' action=\"".$CFG_GLPI["root_doc"]."/front/massiveaction.php\">";
			}

			$sort_img="<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/" . ($order == "DESC" ? "puce-down.png" : "puce-up.png") . "\" alt='' title=''>";
			echo "<table class='tab_cadrehov'><tr>";
			echo "<th>&nbsp;</th>";

			echo "<th>".($sort=="name"?$sort_img:"")."<a href='javascript:reloadTab(\"sort=name&order=".($order=="ASC"?"DESC":"ASC")."&start=0\");'>".$LANG['common'][16]."</a></th>";

			if ($software->isRecursive()) {
				// Ereg to search entity in string for match default order
				echo "<th>".(strstr($order,"entity")?$sort_img:"")."<a href='javascript:reloadTab(\"sort=entity&order=".($order=="ASC"?"DESC":"ASC")."&start=0\");'>".$LANG['entity'][0]."</a></th>";
			}
			echo "<th>".($sort=="serial"?$sort_img:"")."<a href='javascript:reloadTab(\"sort=serial&order=".($order=="ASC"?"DESC":"ASC")."&start=0\");'>".$LANG['common'][19]."</a></th>";
			echo "<th>".$LANG['tracking'][29]."</th>";
			echo "<th>".($sort=="typename"?$sort_img:"")."<a href='javascript:reloadTab(\"sort=typename&order=".($order=="ASC"?"DESC":"ASC")."&start=0\");'>".$LANG['common'][17]."</a></th>";
			echo "<th>".($sort=="buyname"?$sort_img:"")."<a href='javascript:reloadTab(\"sort=buyname&order=".($order=="ASC"?"DESC":"ASC")."&start=0\");'>".$LANG['software'][1]."</a></th>";
			echo "<th>".($sort=="usename"?$sort_img:"")."<a href='javascript:reloadTab(\"sort=usename&order=".($order=="ASC"?"DESC":"ASC")."&start=0\");'>".$LANG['software'][2]."</a></th>";
			echo "<th>".($sort=="expire"?$sort_img:"")."<a href='javascript:reloadTab(\"sort=expire&order=".($order=="ASC"?"DESC":"ASC")."&start=0\");'>".$LANG['software'][32]."</a></th>";
			echo "<th>".$LANG['help'][25]."</th>"; // "Computer" rather than "Affected To computer" ($LANG['software'][50] is too long) ??
			//echo "<th>".$LANG['financial'][3]."</th>";
			echo "</tr>";
			for ($tot=0;$data=$DB->fetch_assoc($result);){
				addToNavigateListItems(SOFTWARELICENSE_TYPE,$data['id']);

				echo "<tr class='tab_bg_2'>";

				if ($license->can($data['id'],"w")){
					echo "<td><input type='checkbox' name='item[".$data["id"]."]' value='1'></td>";
					echo "<td><a href='softwarelicense.form.php?id=".$data['id']."'>".$data['name'].(empty($data['name'])?$data['id']:"")."</a></td>";
				} else {
					echo "<td>&nbsp;</td>";
					echo "<td>".$data['name'].(empty($data['name'])?$data['id']:"")."</td>";
				}
				if ($software->isRecursive()) {
					echo "<td>".$data['entity']."</td>";
				}
				echo "<td>".$data['serial']."</td>";
				echo "<td align='right'>".($data['number']>0?$data['number']:$LANG['software'][4])."</td>";
				echo "<td>".$data['typename']."</td>";
				echo "<td>".$data['buyname']."</td>";
				echo "<td>".$data['usename']."</td>";
				echo "<td>".convDate($data['expire'])."</td>";
				if ($data['computers_id']>0 && $computer->getFromDB($data['computers_id'])) {
					$link = $computer->fields['name'];
					if (empty($link) || $_SESSION['glpiis_ids_visible']) {
						$link .= " (".$computer->fields['id'].")";
					}
					if ($computer->fields['is_deleted']) {
						$link .= " (".$LANG['common'][28].")";
					}
					echo "<td><a href='computer.form.php?id=".$data['computers_id']."'>".$link."</a>";


					// search installed version name
					// should be same as name of used_version, except for multiple installation
					$sql = "SELECT glpi_softwaresversions.name " .
							"FROM glpi_softwaresversions, glpi_computers_softwaresversions " .
							"WHERE glpi_softwaresversions.softwares_id='$softwares_id' " .
							"  AND glpi_computers_softwaresversions.softwaresversions_id=glpi_softwaresversions.id" .
							"  AND glpi_computers_softwaresversions.computers_id='".$data['computers_id']."' " .
							"ORDER BY name";

					$installed='';
					foreach ($DB->request($sql) as $inst) {
						$installed .= (empty($installed)?'':', ').$inst['name'];
					}
					echo " (".(empty($installed) ? $LANG['plugins'][1] : $installed).")"; // TODO : move lang to common ?
					echo "</td>";
				} else {
					echo "<td>&nbsp;</td>";
				}

				/*echo "<td>";
				showDisplayInfocomLink(SOFTWARELICENSE_TYPE, $data['id'], 1);
				echo "</td>";*/
				echo "</tr>";

				if ($data['number']<0) {
					// One illimited license, total is illimited
					$tot = -1;
				} else if ($tot>=0) {
					// Not illimited, add the current number
					$tot += $data['number'];
				}
			}
			echo "<tr class='tab_bg_1'><td colspan='".($software->isRecursive()?4:3)."' align='right'>".$LANG['common'][33].
				"</td><td align='right'>".($tot>0?$tot:$LANG['software'][4])."</td><td colspan='5' align='center'>";
			if ($canedit){
				echo "<a href='softwarelicense.form.php?softwares_id=$softwares_id'>".$LANG['software'][8]."</a>";
			}
			echo "</td></tr>";
			echo "</table>";

			if ($canedit){
				echo "<table width='80%' class='tab_glpi'>";
				echo "<tr><td><img src=\"".$CFG_GLPI["root_doc"]."/pics/arrow-left.png\" alt=''></td><td><a onclick= \"if ( markCheckboxes('massiveactionlicense_form$rand') ) return false;\" href='".$_SERVER['PHP_SELF']."?select=all'>".$LANG['buttons'][18]."</a></td>";

				echo "<td>/</td><td ><a onclick=\"if ( unMarkCheckboxes('massiveactionlicense_form$rand') ) return false;\" href='".$_SERVER['PHP_SELF']."?select=none'>".$LANG['buttons'][19]."</a>";
				echo "</td><td class='left' width='80%'>";
				dropdownMassiveAction(SOFTWARELICENSE_TYPE,0,array('softwares_id'=>$softwares_id));
				echo "</td></table>";
				echo "</form>";

			}

		} else {
			echo $LANG['search'][15];
		}

	}
	echo "</div>";
}

/**
 * Show installations of a software
 *
 * @param $searchID valeur to the ID to search
 * @param $crit to search : sID (software) or ID (version)
 * @return nothing
 */
function showInstallations($searchID, $crit="softwares_id") {
	global $DB, $CFG_GLPI, $LANG;
	if (!haveRight("software", "r") || !$searchID) {
		return false;
	}

	$canedit = haveRight("software", "w");
	$canshowcomputer = haveRight("computer", "r");

	if (isset($_REQUEST["start"])) {
		$start = $_REQUEST["start"];
	} else {
		$start = 0;
	}

	if (isset($_REQUEST["sort"]) && !empty($_REQUEST["sort"])) {
		// manage several param like location,compname
		$tmp=explode(",",$_REQUEST["sort"]);
		$sort="`".implode("`,`",$tmp)."`";
	} else {
		$sort = "entity, version";
	}

	if (isset($_REQUEST["order"]) && $_REQUEST["order"]=="DESC") {
		$order = "DESC";
	} else {
		$order = "ASC";
	}

	// Total Number of events
	if ($crit=="softwares_id") {
		// Software ID
		$number = countElementsInTable("glpi_computers_softwaresversions,glpi_computers,glpi_softwaresversions",
			"glpi_computers_softwaresversions.computers_id = glpi_computers.id
         AND glpi_computers_softwaresversions.softwaresversions_id = glpi_softwaresversions.id
         AND glpi_softwaresversions.softwares_id=$searchID" .
			getEntitiesRestrictRequest(' AND', 'glpi_computers') .
			" AND glpi_computers.is_deleted=0 AND glpi_computers.is_template=0");
	} else {
		//SoftwareVersion ID
		$number = countElementsInTable("glpi_computers_softwaresversions,glpi_computers",
			"glpi_computers_softwaresversions.computers_id = glpi_computers.id
         AND glpi_computers_softwaresversions.softwaresversions_id = $searchID" .
			getEntitiesRestrictRequest(' AND', 'glpi_computers') .
			" AND glpi_computers.is_deleted=0 AND glpi_computers.is_template=0");
	}

	echo "<br><div class='center'>";
	if ($number < 1) {
		echo "<table class='tab_cadre_fixe'>";
		echo "<tr><th>".$LANG['search'][15]."</th></tr>";
		echo "</table>";
		echo "</div>";
		return;
	}

	// Display the pager
	printAjaxPager($LANG['software'][19],$start,$number);

	$query = "SELECT glpi_computers_softwaresversions.*,glpi_computers.name AS compname, glpi_computers.id AS cID,
			glpi_computers.name AS compname, glpi_computers.serial, glpi_computers.otherserial, glpi_users.name AS username,
			glpi_softwaresversions.name as version, glpi_softwaresversions.id as vID, glpi_softwaresversions.softwares_id as sID, glpi_softwaresversions.name as vername,
			glpi_entities.completename AS entity, glpi_locations.completename AS location, glpi_states.name AS state, glpi_groups.name AS groupe,
			glpi_softwareslicenses.name AS lname, glpi_softwareslicenses.id AS lID
		FROM glpi_computers_softwaresversions
		INNER JOIN glpi_softwaresversions ON (glpi_computers_softwaresversions.softwaresversions_id = glpi_softwaresversions.id)
		INNER JOIN glpi_computers ON (glpi_computers_softwaresversions.computers_id = glpi_computers.id)
		LEFT JOIN glpi_entities ON (glpi_computers.entities_id=glpi_entities.id)
		LEFT JOIN glpi_locations ON (glpi_computers.locations_id=glpi_locations.id)
		LEFT JOIN glpi_states ON (glpi_computers.states_id=glpi_states.id)
		LEFT JOIN glpi_groups ON (glpi_computers.groups_id=glpi_groups.id)
		LEFT JOIN glpi_users ON (glpi_computers.users_id=glpi_users.id)
		LEFT JOIN glpi_softwareslicenses ON (glpi_softwareslicenses.softwares_id=glpi_softwaresversions.softwares_id AND glpi_softwareslicenses.computers_id=glpi_computers.id)
		WHERE (glpi_softwaresversions.$crit = '$searchID') " .
			getEntitiesRestrictRequest(' AND', 'glpi_computers') .
			" AND glpi_computers.is_deleted=0 AND glpi_computers.is_template=0 " .
		"ORDER BY " . $sort." ".$order . " LIMIT ".intval($start)."," . intval($_SESSION['glpilist_limit']);

	$rand=mt_rand();


	if ($result=$DB->query($query)){
		if ($data=$DB->fetch_assoc($result)){
			$softwares_id = $data['sID'];

			$soft = new Software;
			$showEntity = ($soft->getFromDB($softwares_id) && $soft->isRecursive());

			$title=$LANG['help'][31] ." = ". $soft->fields["name"];
			if ($crit=="id") {
				$title .= " - " . $data["vername"];
			}
			initNavigateListItems(COMPUTER_TYPE,$title);

			$sort_img="<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/" . ($order == "DESC" ? "puce-down.png" : "puce-up.png") . "\" alt='' title=''>";
			if ($canedit) {
				echo "<form name='softinstall".$rand."' id='softinstall".$rand."' method='post' action=\"".$CFG_GLPI["root_doc"]."/front/software.licenses.php\">";
				echo "<input type='hidden' name='softwares_id' value='$softwares_id'>";
				echo "<table class='tab_cadrehov'><tr>";
				echo "<th>&nbsp;</th>";
			} else {
				echo "<table class='tab_cadrehov'><tr>";
			}

			if ($crit=="softwares_id") {
				echo "<th>".($sort=="vername"?$sort_img:"")."<a href='javascript:reloadTab(\"sort=vername&order=".($order=="ASC"?"DESC":"ASC")."&start=0\");'>".$LANG['software'][5]."</a></th>";
			}
			echo "<th>".($sort=="compname"?$sort_img:"")."<a href='javascript:reloadTab(\"sort=compname&order=".($order=="ASC"?"DESC":"ASC")."&start=0\");'>".$LANG['common'][16]."</a></th>";
			if ($showEntity) {
				echo "<th>".(strstr($sort,"entity")?$sort_img:"")."<a href='javascript:reloadTab(\"sort=entity,compname&order=".($order=="ASC"?"DESC":"ASC")."&start=0\");'>".$LANG['entity'][0]."</a></th>";
			}
			echo "<th>".($sort=="serial"?$sort_img:"")."<a href='javascript:reloadTab(\"sort=serial&order=".($order=="ASC"?"DESC":"ASC")."&start=0\");'>".$LANG['common'][19]."</a></th>";
			echo "<th>".($sort=="otherserial"?$sort_img:"")."<a href='javascript:reloadTab(\"sort=otherserial&order=".($order=="ASC"?"DESC":"ASC")."&start=0\");'>".$LANG['common'][20]."</a></th>";
			echo "<th>".(strstr($sort,"location")?$sort_img:"")."<a href='javascript:reloadTab(\"sort=location,compname&order=".($order=="ASC"?"DESC":"ASC")."&start=0\");'>".$LANG['common'][15]."</a></th>";
			echo "<th>".(strstr($sort,"state")?$sort_img:"")."<a href='javascript:reloadTab(\"sort=state,compname&order=".($order=="ASC"?"DESC":"ASC")."&start=0\");'>".$LANG['state'][0]."</a></th>";
			echo "<th>".(strstr($sort,"groupe")?$sort_img:"")."<a href='javascript:reloadTab(\"sort=groupe,compname&order=".($order=="ASC"?"DESC":"ASC")."&start=0\");'>".$LANG['common'][35]."</a></th>";
			echo "<th>".(strstr($sort,"username")?$sort_img:"")."<a href='javascript:reloadTab(\"sort=username,compname&order=".($order=="ASC"?"DESC":"ASC")."&start=0\");'>".$LANG['common'][34]."</a></th>";
			echo "<th>".($sort=="lname"?$sort_img:"")."<a href='javascript:reloadTab(\"sort=lname&order=".($order=="ASC"?"DESC":"ASC")."&start=0\");'>".$LANG['software'][11]."</a></th>";
			echo "</tr>\n";

			do {
				addToNavigateListItems(COMPUTER_TYPE,$data["cID"]);

				echo "<tr class='tab_bg_2'>";
				if ($canedit){
					echo "<td><input type='checkbox' name='item[".$data["id"]."]' value='1'></td>";
				}
				if ($crit=="softwares_id") {
					echo "<td><a href='softwareversion.form.php?id=".$data['vID']."'>".$data['version']."</a></td>";
				}
				$compname=$data['compname'];
				if (empty($compname) || $_SESSION['glpiis_ids_visible']) {
					$compname .= " (".$data['cID'].")";
				}
				if ($canshowcomputer){
					echo "<td><a href='computer.form.php?id=".$data['cID']."'>$compname</a></td>";
				} else {
					echo "<td>".$compname."</td>";
				}
				if ($showEntity) {
					echo "<td>".(empty($data['entity']) ? $LANG['entity'][2] : $data['entity'])."</td>";
				}
				echo "<td>".$data['serial']."</td>";
				echo "<td>".$data['otherserial']."</td>";
				echo "<td>".$data['location']."</td>";
				echo "<td>".$data['state']."</td>";
				echo "<td>".$data['groupe']."</td>";
				echo "<td>".$data['username']."</td>";
				if ($data['lID']>0) {
					echo "<td><a href='softwarelicense.form.php?id=".$data['lID']."'>".$data['lname']."</a></td>";
				} else {
					echo "<td>&nbsp;</td>";
				}
				echo "</tr>\n";

			} while ($data=$DB->fetch_assoc($result));

			echo "</table>";

			if ($canedit){
				echo "<table width='80%' class='tab_glpi'>";
				echo "<tr><td><img src=\"".$CFG_GLPI["root_doc"]."/pics/arrow-left.png\" alt=''></td>";
				echo "<td class='left' width='100%'><a onclick= \"if ( markCheckboxes('softinstall".$rand."') ) return false;\" href='".$_SERVER['PHP_SELF']."?id=$softwares_id&amp;select=all'>".$LANG['buttons'][18]."</a>";
				echo "&nbsp;/&nbsp;<a onclick= \"if ( unMarkCheckboxes('softinstall".$rand."') ) return false;\" href='".$_SERVER['PHP_SELF']."?id=$softwares_id&amp;select=none'>".$LANG['buttons'][19]."</a>";

				dropdownSoftwareVersions("versionID",$softwares_id);
				echo "&nbsp;<input type='submit' name='moveinstalls' value=\"".$LANG['buttons'][20]."\"
 class='submit'>";

				echo "&nbsp;<input type='submit' name='deleteinstalls' value=\"".$LANG['buttons'][6]."\" class='submit'>";

				echo "</td></tr>\n";
				echo "</table>";
				echo "</form>";
			}

		} else { // Not found
			echo $LANG['search'][15];
		}
	} // Query
	echo "</div>";
}

/**
 * Install a software on a computer
 *
 * @param $computers_id ID of the computer where to install a software
 * @param $softwaresversions_id ID of the version to install
 * @param $dohistory Do history ?
 * @return nothing
 */
function installSoftwareVersion($computers_id, $softwaresversions_id, $dohistory=1){
//function installSoftware($computers_id, $lID, $softwares_id = '', $dohistory = 1) {

	global $DB,$LANG;

	if (!empty ($softwaresversions_id) && $softwaresversions_id > 0) {

		$query_exists = "SELECT id FROM glpi_computers_softwaresversions
            WHERE computers_id='".$computers_id."'
               AND softwaresversions_id='".$softwaresversions_id."'";
		$result = $DB->query($query_exists);
		if ($DB->numrows($result) > 0){
			return $DB->result($result, 0, "id");
		} else {
			$query = "INSERT INTO glpi_computers_softwaresversions
                     (`computers_id`,`softwaresversions_id`)
               VALUES ('$computers_id','$softwaresversions_id')";

			if ($result = $DB->query($query)) {
				$newID = $DB->insert_id();
				$vers = new SoftwareVersion();
				if ($vers->getFromDB($softwaresversions_id)) {
					// Update softwaresversions_id_use for Affected License
					$DB->query("UPDATE glpi_softwareslicenses SET softwaresversions_id_use='$softwaresversions_id'
						WHERE softwares_id='".$vers->fields["softwares_id"]."' AND computers_id='$computers_id'");

					if ($dohistory) {
						$soft = new Software();
						if ($soft->getFromDB($vers->fields["softwares_id"])) {
							$changes[0] = '0';
							$changes[1] = "";
							$changes[2] = addslashes($soft->fields["name"] . " " . $vers->fields["name"]);
							// Log on Computer history
							historyLog($computers_id, COMPUTER_TYPE, $changes, 0, HISTORY_INSTALL_SOFTWARE);
						}
						$comp = new Computer();
						if ($comp->getFromDB($computers_id)) {
							$changes[0] = '0';
							$changes[1] = "";
							$changes[2] = addslashes($comp->fields["name"]);
							// Log on SoftwareVersion history
							historyLog($softwaresversions_id, SOFTWAREVERSION_TYPE, $changes, 0, HISTORY_INSTALL_SOFTWARE);

							/* Log on Software history
							$changes[2] = addslashes($comp->fields["name"] . " " . $vers->fields["name"]);
							historyLog($vers->fields["softwares_id"], SOFTWARE_TYPE, $changes, 0, HISTORY_INSTALL_SOFTWARE);
							*/
						}
					}
				}
				return $newID;
			} else {
				return false;
			}
		}
	}
}

/**
 * Update version installed on a computer
 *
 * @param $instID ID of the install software lienk
 * @param $newvID ID of the new version
 * @param $dohistory Do history ?
 * @return nothing
 */
function updateInstalledVersion($instID, $newvID, $dohistory=1) {
	global $DB;

	$query_exists = "SELECT * FROM glpi_computers_softwaresversions WHERE id='".$instID."'";
	$result = $DB->query($query_exists);
	if ($DB->numrows($result) > 0){
		$computers_id=$DB->result($result, 0, "computers_id");
		$softwaresversions_id=$DB->result($result, 0, "softwaresversions_id");
		if ($softwaresversions_id!=$newvID && $newvID>0){
			uninstallSoftwareVersion($instID, $dohistory);
			installSoftwareVersion($computers_id, $newvID, $dohistory);
		}
	}
}


/**
 * Uninstall a software on a computer
 *
 * @param $ID ID of the install software link (license/computer)
 * @param $dohistory Do history ?
 * @return nothing
 */
function uninstallSoftwareVersion($ID, $dohistory = 1) {
//function uninstallSoftware($ID, $dohistory = 1) {

	global $DB;

	$query2 = "SELECT * FROM glpi_computers_softwaresversions WHERE (id = '$ID')";
	$result2 = $DB->query($query2);
	$data = $DB->fetch_array($result2);
	// Not found => nothing to do
	if (!$data) return false;

	$query = "DELETE FROM glpi_computers_softwaresversions WHERE (id = '$ID')";

	if ($result = $DB->query($query)) {
		$vers = new SoftwareVersion();
		if ($vers->getFromDB($data["softwaresversions_id"])) {
			// Clear softwaresversions_id_use for Affected License
			// If uninstalled is the used_version (OCS install new before uninstall old)
			$DB->query("UPDATE glpi_softwareslicenses SET softwaresversions_id_use=0
				WHERE softwares_id='".$vers->fields["softwares_id"]."'
				  AND computers_id='".$data["computers_id"]."'
				  AND softwaresversions_id_use='".$vers->fields["id"]."'");

			if ($dohistory) {
				$soft = new Software();
				if ($soft->getFromDB($vers->fields["softwares_id"])) {
					$changes[0] = '0';
					$changes[1] = addslashes($soft->fields["name"] . " " . $vers->fields["name"]);
					$changes[2] = "";
					// Log on Computer history
					historyLog($data["computers_id"], COMPUTER_TYPE, $changes, 0, HISTORY_UNINSTALL_SOFTWARE);
				}
				$comp = new Computer();
				if ($comp->getFromDB($data["computers_id"])) {
					$changes[0] = '0';
					$changes[1] = addslashes($comp->fields["name"]);
					$changes[2] = "";
					// Log on SoftwareVersion history
					historyLog($data["softwaresversions_id"], SOFTWAREVERSION_TYPE, $changes, 0, HISTORY_UNINSTALL_SOFTWARE);

					/* Log on Software history
					$changes[1] = addslashes($comp->fields["name"] . " " . $vers->fields["name"]);
					historyLog($vers->fields["softwares_id"], SOFTWARE_TYPE, $changes, 0, HISTORY_UNINSTALL_SOFTWARE);
					*/
				}
			}
		}
		return true;
	} else {
		return false;
	}
}

/**
 * SHow softwrae installed on a computer
 *
 * @param $computers_id ID of the computer
 * @param $withtemplate template case of the view process
 * @return nothing
 */
function showSoftwareInstalled($computers_id, $withtemplate = '') {

	global $DB, $CFG_GLPI, $LANG;


	if (!haveRight("software", "r"))
		return false;

	$rand=mt_rand();
	$comp = new Computer();
	$comp->getFromDB($computers_id);
	$canedit=haveRight("software", "w");
	$entities_id = $comp->fields["entities_id"];

	$query_cat = "SELECT 1 as TYPE, glpi_softwarescategories.name as category, glpi_softwares.softwarescategories_id,
		glpi_softwares.name as softname, glpi_computers_softwaresversions.id, glpi_softwares.is_deleted, glpi_states.name AS state,
		glpi_softwaresversions.softwares_id, glpi_softwaresversions.name AS version,glpi_softwareslicenses.computers_id AS computers_id,glpi_softwareslicenses.softwareslicensestypes_id AS lictype
		FROM glpi_computers_softwaresversions
		LEFT JOIN glpi_softwaresversions ON ( glpi_computers_softwaresversions.softwaresversions_id = glpi_softwaresversions.id )
		LEFT JOIN glpi_states ON ( glpi_states.id = glpi_softwaresversions.states_id )
		LEFT JOIN glpi_softwareslicenses ON ( glpi_softwaresversions.softwares_id = glpi_softwareslicenses.softwares_id AND glpi_softwareslicenses.computers_id = '$computers_id')
		LEFT JOIN glpi_softwares ON (glpi_softwaresversions.softwares_id = glpi_softwares.id)
		LEFT JOIN glpi_softwarescategories ON (glpi_softwarescategories.id = glpi_softwares.softwarescategories_id)";
	$query_cat .= " WHERE glpi_computers_softwaresversions.computers_id = '$computers_id' AND glpi_softwares.softwarescategories_id > 0";

	$query_nocat = "SELECT 2 as TYPE, glpi_softwarescategories.name as category, glpi_softwares.softwarescategories_id,
		glpi_softwares.name as softname, glpi_computers_softwaresversions.id, glpi_softwares.is_deleted, glpi_states.name AS state,
		glpi_softwaresversions.softwares_id, glpi_softwaresversions.name AS version,glpi_softwareslicenses.computers_id AS computers_id,glpi_softwareslicenses.softwareslicensestypes_id AS lictype
	    FROM glpi_computers_softwaresversions
		LEFT JOIN glpi_softwaresversions ON ( glpi_computers_softwaresversions.softwaresversions_id = glpi_softwaresversions.id )
		LEFT JOIN glpi_states ON ( glpi_states.id = glpi_softwaresversions.states_id )
		LEFT JOIN glpi_softwareslicenses ON ( glpi_softwaresversions.softwares_id = glpi_softwareslicenses.softwares_id AND glpi_softwareslicenses.computers_id = '$computers_id')
	    LEFT JOIN glpi_softwares ON (glpi_softwaresversions.softwares_id = glpi_softwares.id)
	    LEFT JOIN glpi_softwarescategories ON (glpi_softwarescategories.id = glpi_softwares.softwarescategories_id)";
	$query_nocat .= " WHERE glpi_computers_softwaresversions.computers_id = '$computers_id' AND (glpi_softwares.softwarescategories_id <= 0 OR glpi_softwares.softwarescategories_id IS NULL )";

	$query = "( $query_cat ) UNION ($query_nocat) ORDER BY TYPE, softwarescategories_id, softname, version";

	$DB->query("SET SESSION group_concat_max_len = 9999999;");

	$result = $DB->query($query);
	$i = 0;

	echo "<div class='center'><table class='tab_cadre_fixe'>";

	if ((empty ($withtemplate) || $withtemplate != 2) && $canedit) {
		echo "<tr class='tab_bg_1'><td align='center' colspan='5'>";
		echo "<form method='post' action=\"" . $CFG_GLPI["root_doc"] . "/front/software.licenses.php\">";

		echo "<div class='software-instal'>";
		echo "<input type='hidden' name='computers_id' value='$computers_id'>";
		dropdownSoftwareToInstall("softwaresversions_id", $entities_id);
		echo "<input type='submit' name='install' value=\"" . $LANG['buttons'][4] . "\" class='submit'>";
		echo "</div>";
		echo "</form>";
		echo "</td></tr>";
	}

	echo "<tr><th colspan='5'>" . $LANG['software'][17] . ":</th></tr>";

	$cat = -1;

	initNavigateListItems(SOFTWARE_TYPE,$LANG['help'][25]." = ".(empty($comp->fields["name"]) ? "(".$comp->fields["id"].")":$comp->fields["name"]));

	$installed=array();
	if ($DB->numrows($result)) {
		while ($data = $DB->fetch_array($result)) {
			if ($data["softwarescategories_id"] != $cat) {
				displayCategoryFooter($cat,$rand,$canedit);
				$cat = displayCategoryHeader($computers_id, $data,$rand,$canedit);
			}

			displaySoftsByCategory($data, $computers_id, $withtemplate,$canedit);
			addToNavigateListItems(SOFTWARE_TYPE,$data["softwares_id"]);
			$installed[]=$data["softwares_id"];
		}

		displayCategoryFooter($cat,$rand,$canedit);

		/* seems not used
		$q = "SELECT count(*) FROM glpi_softwares WHERE is_deleted='0' AND is_template='0'";
		$result = $DB->query($q);
		$nb = $DB->result($result, 0, 0);
		*/
	}

	// Affected licenses NOT installed
	$query = "SELECT glpi_softwares.name as softname, glpi_softwares.is_deleted, glpi_states.name AS state, glpi_softwareslicenses.softwaresversions_id_buy,
		glpi_softwareslicenses.softwares_id, glpi_softwaresversions.name AS version, glpi_softwareslicenses.softwareslicensestypes_id AS lictype
		FROM glpi_softwareslicenses
		INNER JOIN glpi_softwares ON (glpi_softwareslicenses.softwares_id = glpi_softwares.id)
		LEFT JOIN glpi_softwarescategories ON (glpi_softwarescategories.id = glpi_softwares.softwarescategories_id)
		LEFT JOIN glpi_softwaresversions ON ( glpi_softwareslicenses.softwaresversions_id_buy = glpi_softwaresversions.id )
		LEFT JOIN glpi_states ON ( glpi_states.id = glpi_softwaresversions.states_id )
		WHERE glpi_softwareslicenses.computers_id = '$computers_id' ";
	if (count($installed)) {
		$query .= " AND glpi_softwareslicenses.softwares_id NOT IN (".implode(',',$installed).")";
	}
	$req=$DB->request($query);
	if ($req->numrows()) {
		$cat=true;
		foreach ($req as $data) {
			if ($cat) {
				displayCategoryHeader($computers_id, $data,$rand,$canedit);
				$cat = false;
			}
			displaySoftsByLicense($data, $computers_id, $withtemplate, $canedit);
		}
		displayCategoryFooter(NULL,$rand,$canedit);
	}

	echo "</table></div><br>";

}

/**
 * Display category footer for showSoftwareInstalled function
 *
 * @param $cat current category ID
 * @param $rand random for unicity
 * @param $canedit boolean
 *
 * @return new category ID
 */
function displayCategoryFooter($cat,$rand,$canedit) {
	global $LANG, $CFG_GLPI;

	// Close old one
	if ($cat != -1) {
		echo "</table>";

		if ($canedit) {
			echo "<table width='950px'>";
			echo "<tr><td><img src=\"".$CFG_GLPI["root_doc"]."/pics/arrow-left.png\" alt=''></td><td><a onclick= \"if ( markCheckboxes('lic_form$cat$rand') ) return false;\" href='".$_SERVER['PHP_SELF']."?id=".$cat."&amp;select=all'>".$LANG['buttons'][18]."</a></td>";
			echo "<td>/</td><td ><a onclick=\"if ( unMarkCheckboxes('lic_form$cat$rand') ) return false;\" href='".$_SERVER['PHP_SELF']."?id=".$cat."&amp;select=none'>".$LANG['buttons'][19]."</a>";
			echo "</td><td class='left' width='80%'>";

			echo "<select name='update_licenses$cat$rand' id='update_licenses_choice$cat$rand'>";
			echo "<option value=''>-----</option>";
			if (isset($cat)) {
				echo "<option value='uninstall_license'>".$LANG['buttons'][5]."</option>";
			} else {
				echo "<option value='install_license'>".$LANG['buttons'][4]."</option>";
			}
			echo "</select>";

			$params=array('actiontype'=>'__VALUE__');
			ajaxUpdateItemOnSelectEvent("update_licenses_choice$cat$rand","update_licenses_view$cat$rand",$CFG_GLPI["root_doc"]."/ajax/updateLicenses.php",$params,false);

			echo "<span id='update_licenses_view$cat$rand'>\n";
			echo "&nbsp;";
			echo "</span>\n";
			echo "</td></tr></table>";
		}
		echo "</form>";
		echo "</div></td></tr>";
	}
}

/**
 * Display category header for showSoftwareInstalled function
 *
 * @param $computers_ID ID of the computer
 * @param $data data used to display
 * @param $rand random for unicity
 * @param $canedit boolean
 *
 * @return new category ID
 */
function displayCategoryHeader($computers_ID,$data,$rand,$canedit) {
	global $LANG, $CFG_GLPI;

	$display = "none";

	if (isset($data["softwarescategories_id"])) {
		$cat = $data["softwarescategories_id"];
		if ($cat) {
			// Categorized
			$catname = $data["category"];
			$display = $_SESSION["glpiis_categorized_soft_expanded"];
		} else {
			// Not categorized
			$catname = $LANG['softwarecategories'][3];
			$display = $_SESSION["glpiis_not_categorized_soft_expanded"];
		}
	} else {
		// Not installed
		$cat = '';
		$catname = $LANG['software'][50] . " - " . $LANG['plugins'][1];
		$display = true;
	}

	echo "	<tr class='tab_bg_2'>";
	echo "  	<td align='center' colspan='5'>";
	echo "			<a  href=\"javascript:showHideDiv('softcat$cat$rand','imgcat$cat','" . GLPI_ROOT . "/pics/folder.png','" . GLPI_ROOT . "/pics/folder-open.png');\">";
	echo "				<img alt='' name='imgcat$cat' src=\"" . GLPI_ROOT . "/pics/folder" . (!$display ? '' : "-open") . ".png\">&nbsp;<strong>" . $catname . "</strong>";
	echo "			</a>";
	echo "		</td>";
	echo "	</tr>";
	echo "<tr class='tab_bg_2'>";
	echo "		<td colspan='5'>
				     <div align='center' id='softcat$cat$rand' " . (!$display ? "style=\"display:none;\"" : '') . ">";
	echo "<form id='lic_form$cat$rand' name='lic_form$cat$rand' method='post' action=\"".$CFG_GLPI["root_doc"]."/front/software.licenses.php\">";
	echo "<input type='hidden' name='computers_ID' value='$computers_ID'>";
	echo "			<table class='tab_cadre_fixe'>";
	echo "				<tr>";
	if ($canedit) {
		echo "<th>&nbsp;</th>";
	}
	echo "					<th>" . $LANG['common'][16] . "</th><th>" . $LANG['state'][0] . "</th><th>" . $LANG['software'][5] . "</th>";
	echo "				</tr>";

	return $cat;
}

/**
 * Display a installed software for a category
 *
 * @param $data data used to display
 * @param $computers_ID ID of the computer
 * @param $withtemplate template case of the view process
 * @return nothing
 */
function displaySoftsByCategory($data, $computers_ID, $withtemplate,$canedit) {
	global $LANG, $CFG_GLPI;

	$ID = $data["id"];
	$multiple = false;

	echo "<tr class='tab_bg_1'>";
	if ($canedit) {
		echo "<td><input type='checkbox' name='license_".$data['id']."'></td>";
	}
	echo "<td class='center'><strong><a href=\"" . $CFG_GLPI["root_doc"] . "/front/software.form.php?id=" . $data['softwares_id'] . "\">";
	echo $data["softname"] . ($_SESSION["glpiis_ids_visible"] ? " (" . $data['softwares_id'] . ")" : "") . "</a>";
	echo "</strong></td>";
	echo "<td>" . $data["state"] . "</td>";

	echo "<td>" . $data["version"];
	if ($data["computers_id"]==$computers_ID) {
		echo " - <strong>". getDropdownName("glpi_softwareslicensestypes",$data["lictype"]) . "</strong>";
	}
	if ((empty ($withtemplate) || $withtemplate != 2) && $canedit) {
		echo " - <a href=\"" . $CFG_GLPI["root_doc"] . "/front/software.licenses.php?uninstall=uninstall&amp;id=$ID&amp;computers_ID=$computers_ID\">";
		echo "<strong>" . $LANG['buttons'][5] . "</strong></a>";
	}
	echo "</td>";
	echo "</tr>";
}

/**
 * Display a software for a License (not installed)
 *
 * @param $data data used to display
 * @param $computers_ID ID of the computer
 * @param $withtemplate template case of the view process
 * @return nothing
 */
function displaySoftsByLicense($data, $computers_ID, $withtemplate,$canedit) {
	global $LANG, $CFG_GLPI;

	$ID = $data["softwaresversions_id_buy"];
	$multiple = false;

	echo "<tr class='tab_bg_1'>";
	if ($canedit) {
      echo "<td>";
      if ($ID>0) {
         echo "<input type='checkbox' name='version_$ID'>";
      }
      echo "</td>";
	}
	echo "<td class='center'><strong><a href=\"" . $CFG_GLPI["root_doc"] . "/front/software.form.php?id=" . $data['softwares_id'] . "\">";
	echo $data["softname"] . ($_SESSION["glpiis_ids_visible"] ? " (" . $data['softwares_id'] . ")" : "") . "</a>";
	echo "</strong></td>";
	echo "<td>" . $data["state"] . "</td>";

	echo "<td>" . $data["version"];
	echo " - <strong>". getDropdownName("glpi_softwareslicensestypes",$data["lictype"]) . "</strong>";
	if ((empty ($withtemplate) || $withtemplate != 2) && $canedit && $ID>0) {
		echo " - <a href=\"" . $CFG_GLPI["root_doc"] . "/front/software.licenses.php?install=install&amp;softwaresversions_id=$ID&amp;computers_ID=$computers_ID\">";
		echo "<strong>" . $LANG['buttons'][4] . "</strong></a>";
	}
	echo "</td>";
	echo "</tr>";
}

/**
 * Count Installations of a software and create string to display
 *
 * @param $softwares_id ID of the software
 * @param $nohtml do not use HTML to highlight ?
 * @return string contains counts
 */
function countInstallations($softwares_id, $nohtml = 0) {

	global $DB, $CFG_GLPI, $LANG;
	$installed = countInstallationsForSoftware($softwares_id);
	$out="";
	if (!$nohtml)
		$out .= $LANG['software'][19] . ": <strong>$installed</strong>";
	else
		$out .= $LANG['software'][19] . ": $installed";

	$total=getNumberOfLicences($softwares_id);

	if ($total < 0 ){
		if (!$nohtml)
			$out .= "&nbsp;&nbsp;".$LANG['software'][11] . ": <strong>".$LANG['software'][4]."</strong>";
		else
			$out .= "&nbsp;&nbsp;".$LANG['software'][11] . ": ".$LANG['software'][4];
	} else {
		if ($total >=$installed) {
			$color = "green";
		} else {
			$color = "blue";
		}

		if (!$nohtml){
			$total = "<span class='$color'>$total";
			$total .= "</span>";
			$out .= "&nbsp;&nbsp;".$LANG['software'][11] . ": <strong>$total</strong>";
		} else
			$out .= "&nbsp;&nbsp;".$LANG['software'][11] . ": ".$total;
	}

	return $out;
}

/**
 * Get number of installed licenses of a software
 *
 * @param $softwares_id software ID
 * @return number of installations
 */
function countInstallationsForSoftware($softwares_id) {
	global $DB;
	$query = "SELECT count(glpi_computers_softwaresversions.id)
			FROM glpi_softwaresversions
			INNER JOIN glpi_computers_softwaresversions ON (glpi_softwaresversions.id = glpi_computers_softwaresversions.softwaresversions_id)
			INNER JOIN glpi_computers ON ( glpi_computers_softwaresversions.computers_id=glpi_computers.id)
			WHERE glpi_softwaresversions.softwares_id='$softwares_id'
				AND glpi_computers.is_deleted=0 AND glpi_computers.is_template=0 " .
				getEntitiesRestrictRequest('AND', 'glpi_computers');

	$result = $DB->query($query);

	if ($DB->numrows($result) != 0) {
		return $DB->result($result, 0, 0);
	} else
		return 0;
}
/**
 * Get number of installed licenses of a version
 *
 * @param $softwaresversions_id version ID
 * @param $entity to search for computer in (default = all active entities)
 *
 * @return number of installations
 */
function countInstallationsForVersion($softwaresversions_id, $entity='') {
	global $DB;
	$query = "SELECT count(glpi_computers_softwaresversions.id)
			FROM glpi_computers_softwaresversions
			INNER JOIN glpi_computers
            ON ( glpi_computers_softwaresversions.computers_id = glpi_computers.id)
			WHERE glpi_computers_softwaresversions.softwaresversions_id='$softwaresversions_id'
				AND glpi_computers.is_deleted=0 AND glpi_computers.is_template=0 " .
				getEntitiesRestrictRequest('AND', 'glpi_computers','',$entity);

	$result = $DB->query($query);

	if ($DB->numrows($result) != 0) {
		return $DB->result($result, 0, 0);
	} else
		return 0;

}

/**
 * Get number of bought licenses of a version
 *
 * @param $softwaresversions_id version ID
 * @param $entity to search for licenses in (default = all active entities)
 *
 * @return number of installations
 */
function countLicensesForVersion($softwaresversions_id, $entity='') {
	global $DB;
	$query = "SELECT count(*)
			FROM glpi_softwareslicenses
			WHERE softwaresversions_id_buy='$softwaresversions_id' " .
			getEntitiesRestrictRequest('AND', 'glpi_softwareslicenses','',$entity);

	$result = $DB->query($query);

	if ($DB->numrows($result) != 0) {
		return $DB->result($result, 0, 0);
	} else
		return 0;

}

/**
 * Get number of licenses to buy of a software
 *
 * @param $softwares_id software ID
 * @return number of licenses to buy
 */
/*
function getLicenceToBuy($softwares_id) {
	global $DB;
	$query = "SELECT id FROM glpi_licenses WHERE (softwares_id = '$softwares_id' AND buy ='0'  AND serial <> 'free' AND serial <> 'global')";
	$result = $DB->query($query);
	return $DB->numrows($result);
}
*/
/**
 * Get number of licensesof a software
 *
 * @param $softwares_id software ID
 * @return number of licenses
 */
function getNumberOfLicences($softwares_id) {
	global $DB;

	$query = "SELECT id FROM glpi_softwareslicenses WHERE (softwares_id = '$softwares_id' AND number='-1') " .
		getEntitiesRestrictRequest('AND', 'glpi_softwareslicenses', '', '', true);
	$result = $DB->query($query);
	if ($DB->numrows($result)){
		return -1;
	} else {
		$query = "SELECT SUM(number) FROM glpi_softwareslicenses WHERE (softwares_id = '$softwares_id' AND number > 0) " .
			getEntitiesRestrictRequest('AND', 'glpi_softwareslicenses', '', '', true);
		$result = $DB->query($query);
		$nb = $DB->result($result,0,0);
		return ($nb ? $nb : 0);
	}
}

/**
 * Create a new software
 * @param name the software's name
 * @param manufacturer the software's manufacturer
 * @param entity the entity in which the software must be added
 * @param comment
 * @return the software's ID
 */
function addSoftware($name, $manufacturer, $entity, $comment = '') {
	global $LANG, $DB,$CFG_GLPI;
	$software = new Software;

	$manufacturer_id = 0;
	if ($manufacturer != '') {
		$manufacturer_id = externalImportDropdown("glpi_manufacturers", $manufacturer);
	}

	$sql = "SELECT id FROM glpi_softwares ".
		"WHERE manufacturers_id='$manufacturer_id' AND name='".$name."' " .
		getEntitiesRestrictRequest('AND', 'glpi_softwares', 'entities_id', $entity, true);

	$res_soft = $DB->query($sql);
	if ($soft = $DB->fetch_array($res_soft)) {
		$id = $soft["id"];
	} else {
		$input["name"] = $name;
		$input["manufacturers_id"] = $manufacturer_id;
		$input["entities_id"] = $entity;
		// No comment
		//$input["comment"] = $LANG['rulesengine'][88];
		$input["is_helpdesk_visible"] = $CFG_GLPI["default_software_helpdesk_visible"];

		//Process software's category rules
		$softcatrule = new SoftwareCategoriesRuleCollection;
		$result = $softcatrule->processAllRules(null, null, $input);
		if (!empty ($result) && isset ($result["softwarescategories_id"])) {
			$input["softwarescategories_id"] = $result["softwarescategories_id"];
		} else {
			$input["softwarescategories_id"] = 0;
		}

		$id = $software->add($input);
	}
	return $id;
}

/**
 * Put software in trash because it's been removed by GLPI software dictionnary
 * @param $ID  the ID of the software to put in trash
 * @param $comment the comment to add to the already existing software's comment
 */
function putSoftwareInTrash($ID, $comment = '') {
	global $LANG,$CFG_GLPI;
	$software = new Software;
	//Get the software's fields
	$software->getFromDB($ID);

	$input["id"] = $ID;
	$input["is_deleted"] = 1;

	$config = new Config;
	$config->getFromDB($CFG_GLPI["id"]);

	//change category of the software on deletion (if defined in glpi_configs)
	if (isset($config->fields["softwarescategories_id_ondelete"])
         && $config->fields["softwarescategories_id_ondelete"] != 0) {
		$input["softwarescategories_id"] = $config->fields["softwarescategories_id_ondelete"];
   }

	//Add dictionnary comment to the current comment
	$input["comment"] = ($software->fields["comment"] != '' ? "\n" : '') . $comment;

	$software->update($input);
}

/**
 * Restore a software from trash
 * @param $ID  the ID of the software to put in trash
 */
function removeSoftwareFromTrash($ID) {
	$s = new Software;

	$s->getFromDB($ID);
	$softcatrule = new SoftwareCategoriesRuleCollection;
	$result = $softcatrule->processAllRules(null, null, $s->fields);

	if (!empty ($result) && isset ($result["softwarescategories_id"])) {
		$input["softwarescategories_id"] = $result["softwarescategories_id"];
	} else {
		$input["softwarescategories_id"] = 0;
   }

	$s->restore(array (
		"id" => $ID
	));
}
/**
 * Add a Software. If already exist in trash restore it
 * @param name the software's name
 * @param manufacturer the software's manufacturer
 * @param entity the entity in which the software must be added
 * @param comment comment
 */
function addSoftwareOrRestoreFromTrash($name,$manufacturer,$entity,$comment='') {
	global $DB;
	//Look for the software by his name in GLPI for a specific entity
	$query_search = "SELECT glpi_softwares.id, glpi_softwares.is_deleted
			FROM glpi_softwares
			WHERE name = '".$name."' AND is_template='0' AND entities_id='".$entity."'";
	$result_search = $DB->query($query_search);
	if ($DB->numrows($result_search) > 0) {
		//Software already exists for this entity, get his ID
		$data = $DB->fetch_array($result_search);
		$ID = $data["id"];

		// restore software
		if ($data['is_deleted'])
			removeSoftwareFromTrash($ID);
	} else {
		$ID = 0;
	}

	if (!$ID)
		$ID = addSoftware($name, $manufacturer, $entity, $comment);
	return $ID;
}


/**
 * Cron action on softwares : alert on expired licences
 *
 * @param $task to log, if NULL display
 *
 * @return 0 : nothing to do 1 : done with success
 **/
function cron_software($task=NULL){
	global $DB,$CFG_GLPI,$LANG;

	if (!$CFG_GLPI["use_mailing"]){
		return false;
	}

	loadLanguage($CFG_GLPI["language"]);

	$message=array();
	$items_notice=array();
	$items_end=array();

	// Check notice
	$query="SELECT glpi_softwareslicenses.*, glpi_softwares.name as softname
		FROM glpi_softwareslicenses
		LEFT JOIN glpi_alerts ON (glpi_softwareslicenses.id = glpi_alerts.items_id
					AND glpi_alerts.itemtype='".SOFTWARELICENSE_TYPE."'
					AND glpi_alerts.type='".ALERT_END."')
		LEFT JOIN glpi_softwares ON (glpi_softwareslicenses.softwares_id = glpi_softwares.id)
		WHERE glpi_alerts.date IS NULL
			AND glpi_softwareslicenses.expire IS NOT NULL
			AND glpi_softwareslicenses.expire < CURDATE()
		";

	$result=$DB->query($query);
	if ($DB->numrows($result)>0){
		while ($data=$DB->fetch_array($result)){
			if (!isset($message[$data["entities_id"]])){
				$message[$data["entities_id"]]="";
			}
			if (!isset($items_notice[$data["entities_id"]])){
				$items[$data["entities_id"]]=array();
			}

			$name = $data['softname'].' - '.$data['name'].' - '.$data['serial'];

			// define message alert
			if (strstr($message[$data["entities_id"]],$name)===false){
				$message[$data["entities_id"]].=$LANG['mailing'][51]." ".$name.": ".convDate($data["expire"])."<br>\n";
			}
			$items[$data["entities_id"]][]=$data["id"];
		}


	}

	if (count($message)>0){
		foreach ($message as $entity => $msg){
			$mail=new MailingAlert("alertlicense",$msg,$entity);
			if ($mail->send()){
            if ($task) {
               $task->log(getDropdownName("glpi_entities",$entity).":  $msg\n");
               $task->addVolume(1);
            } else {
               addMessageAfterRedirect(getDropdownName("glpi_entities",$entity).":  $msg");
            }

				// Mark alert as done
				$alert=new Alert();
				$input["itemtype"]=SOFTWARELICENSE_TYPE;

				$input["type"]=ALERT_END;
				if (isset($items[$entity])){
					foreach ($items[$entity] as $ID){
						$input["items_id"]=$ID;
						$alert->add($input);
						unset($alert->fields['id']);
					}
				}
			} else {
				if ($task) {
               $task->log(getDropdownName("glpi_entities",$entity).":  Send licenses alert failed\n");
            } else {
					addMessageAfterRedirect(getDropdownName("glpi_entities",$entity).":  Send licenses alert failed",false,ERROR);
				}
			}
		}
		return 1;
	}

	return 0;
}

?>
