<?php

/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
 * @param $sID ID of the software
 * @return nothing
 */
function showVersions($sID) {
	global $DB, $CFG_GLPI, $LANG;

	if (!haveRight("software", "r"))
		return false;

	$soft = new Software;
	$canedit = $soft->can($sID,"w");

	echo "<div class='center'>";

	$query = "SELECT glpi_softwareversions.*,glpi_dropdown_state.name AS sname FROM glpi_softwareversions
				LEFT JOIN glpi_dropdown_state ON (glpi_dropdown_state.ID=glpi_softwareversions.state)
				WHERE (sID = '$sID') ORDER BY name";

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
				addToNavigateListItems(SOFTWAREVERSION_TYPE,$data['ID']);
				$nb=countInstallationsForVersion($data['ID']);

				// Show version if canedit (to update/delete) or if nb (to see installations)
				if ($canedit || $nb) {
					echo "<tr class='tab_bg_2'>";
					echo "<td><a href='softwareversion.form.php?ID=".$data['ID']."'>".$data['name'].(empty($data['name'])?$data['ID']:"")."</a></td>";
					echo "<td align='right'>".$data['sname']."</td>";
					echo "<td align='right'>$nb</td>";
					echo "<td>".$data['comments']."</td></tr>";
				}
			}
			echo "<tr class='tab_bg_1'><td></td><td align='right'>".$LANG['common'][33]."</td><td align='right'>$tot</td><td>";
			if ($canedit){
				echo "<a href='softwareversion.form.php?sID=$sID'>".$LANG['software'][7]."</a>";
			}
			echo "</td></tr>";
			echo "</table>";
		} else {
			echo "<table class='tab_cadre_fixe'>";
			echo "<tr><th>".$LANG['search'][15]."</th></tr>";
			if ($canedit){
				echo "<tr class='tab_bg_2'><td align='center'><a href='softwareversion.form.php?sID=$sID'>".$LANG['software'][7]."</a></td></tr>";
			}
			echo "</table>";
		}

	}
	echo "</div>";
}

/**
 * Show number of installation per entity
 *
 * @param $vID ID of the version
 *
 * @return nothing
 */
function showInstallationsByEntity($vID) {
	global $DB, $CFG_GLPI, $LANG;

	if (!haveRight("software", "r") || !$vID)
		return false;

	echo "<div class='center'>";
	echo "<table class='tab_cadre'><tr>";
	echo "<th>".$LANG['entity'][0]."</th>";
	echo "<th>".$LANG['software'][19]."</th>";
	echo "</tr>";

	$tot=0;
	if (in_array(0,$_SESSION["glpiactiveentities"])) {
		$nb = countInstallationsForVersion($vID,0);
		if ($nb>0) {
			echo "<tr class='tab_bg_2'><td>" . $LANG['entity'][1] . "</td><td>" . $nb . "</td></tr>\n";
			$tot+=$nb;
		}
	}
	$sql = "SELECT ID,completename FROM glpi_entities " .
		getEntitiesRestrictRequest('WHERE', 'glpi_entities') .
		" ORDER BY completename";
	foreach ($DB->request($sql) as $ID => $data) {
		$nb = countInstallationsForVersion($vID,$ID);
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
	$sql = "SELECT glpi_software.ID, glpi_software.name, glpi_entities.completename AS entity " .
			"FROM glpi_software " .
			"LEFT JOIN glpi_entities ON (glpi_software.FK_entities=glpi_entities.ID) " .
			"WHERE glpi_software.ID!=$ID AND glpi_software.name='".addslashes($soft->fields["name"])."'".
				"AND glpi_software.deleted=0 AND glpi_software.is_template=0 " .
				getEntitiesRestrictRequest('AND', 'glpi_software','FK_entities',getEntitySons($soft->fields["FK_entities"]),false).
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
			echo "<td><input type='checkbox' name='item[".$data["ID"]."]' value='1'></td>";
			echo "<td<a href='".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[SOFTWARE_TYPE]."?ID=".$data["ID"]."'>".$data["name"]."</a></td>";
			echo "<td>".$data["entity"]."</td>";
			echo "<td align='right'>".countInstallationsForSoftware($data["ID"])."</td>";
			echo "<td align='right'>".getNumberOfLicences($data["ID"])."</td></tr>\n";
		}

		echo "</table>";

		echo "<table width='80%' class='tab_glpi'>";
		echo "<tr><td><img src=\"".$CFG_GLPI["root_doc"]."/pics/arrow-left.png\" alt=''></td><td><a onclick= \"if ( markCheckboxes('mergesoftware_form$rand') ) return false;\" href='".$_SERVER['PHP_SELF']."?select=all'>".$LANG['buttons'][18]."</a></td>";
		echo "<td>/</td><td ><a onclick=\"if ( unMarkCheckboxes('mergesoftware_form$rand') ) return false;\" href='".$_SERVER['PHP_SELF']."?select=none'>".$LANG['buttons'][19]."</a>";
		echo "</td><td class='left' width='80%'>";
		echo "<input type='hidden' name='ID' value=$ID><input type='submit' name='mergesoftware' value=\"".$LANG['software'][48]."\" class='submit'>";
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
	$req = $DB->request("glpi_softwareversions", array("sID"=>$item));
	$i=0;
	if ($nb=$req->numrows()) {
		foreach ($req as $from) {

			$found=false;
			foreach ($DB->request("glpi_softwareversions", array("sID"=>$ID, "name"=>$from["name"])) as $dest) {
				// Update version ID on License
				$sql="UPDATE glpi_softwarelicenses SET buy_version='".$dest["ID"]."' WHERE buy_version='".$from["ID"]."'";
				$DB->query($sql);
				$sql="UPDATE glpi_softwarelicenses SET use_version='".$dest["ID"]."' WHERE use_version='".$from["ID"]."'";
				$DB->query($sql);

				// Move installation to existing version in destination software
				$sql="UPDATE glpi_inst_software SET vID='".$dest["ID"]."' WHERE vID='".$from["ID"]."'";
				$found=$DB->query($sql);
			}
			if ($found) {
				// Installation has be moved, delete the source version
				$sql="DELETE FROM glpi_softwareversions WHERE ID='".$from["ID"]."'";
			} else {
				// Move version to destination software
				$sql="UPDATE glpi_softwareversions SET sID='$ID' WHERE ID='".$from["ID"]."'";
			}
			if ($DB->query($sql)) $i++;
			changeProgressBarPosition($i,$nb+1);
		}
	}
	// Move software license
	$sql="UPDATE glpi_softwarelicenses SET sID='$ID' WHERE sID IN ('".implode("','",$item)."')";
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
 * @param $sID ID of the software
 * @return nothing
 */
function showLicenses($sID) {
	global $DB, $CFG_GLPI, $LANG;

	$software = new Software;
	$license = new SoftwareLicense;
	$computer = new Computer();

	if (!$software->getFromDB($sID) || !$software->can($sID,"r")) {
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
	$number = countElementsInTable("glpi_softwarelicenses", "glpi_softwarelicenses.sID = $sID " . getEntitiesRestrictRequest('AND', 'glpi_softwarelicenses', '', '', true));
	echo "<br><div class='center'>";
	if ($number < 1) {
		echo "<table class='tab_cadre_fixe'>";
		echo "<tr><th>".$LANG['search'][15]."</th></tr>";
		if ($canedit){
			echo "<tr class='tab_bg_2'><td align='center'><a href='softwarelicense.form.php?sID=$sID'>".$LANG['software'][8]."</a></td></tr>";
		}
		echo "</table>";
		echo "</div>";
		return;
	}

	// Display the pager
	printAjaxPager($LANG['software'][11],$start,$number);


	$rand=mt_rand();
	$query = "SELECT glpi_softwarelicenses.*, buyvers.name as buyname, usevers.name AS usename, glpi_entities.completename AS entity, glpi_dropdown_licensetypes.name AS typename
		FROM glpi_softwarelicenses
		LEFT JOIN glpi_softwareversions AS buyvers ON (buyvers.ID = glpi_softwarelicenses.buy_version)
		LEFT JOIN glpi_softwareversions AS usevers ON (usevers.ID = glpi_softwarelicenses.use_version)
		LEFT JOIN glpi_entities ON (glpi_entities.ID = glpi_softwarelicenses.FK_entities)
		LEFT JOIN glpi_dropdown_licensetypes ON (glpi_dropdown_licensetypes.ID = glpi_softwarelicenses.type)
		WHERE (glpi_softwarelicenses.sID = '$sID') " .
			getEntitiesRestrictRequest('AND', 'glpi_softwarelicenses', '', '', true) .
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

			echo "<th>".($sort=="`name`"?$sort_img:"")."<a href='javascript:reloadTab(\"sort=name&order=".($order=="ASC"?"DESC":"ASC")."&start=0\");'>".$LANG['common'][16]."</a></th>";

			if ($software->isRecursive()) {
				// Ereg to search entity in string for match default order
				echo "<th>".(strstr($sort,"entity")?$sort_img:"")."<a href='javascript:reloadTab(\"sort=entity&order=".($order=="ASC"?"DESC":"ASC")."&start=0\");'>".$LANG['entity'][0]."</a></th>";
			}
			echo "<th>".($sort=="`serial`"?$sort_img:"")."<a href='javascript:reloadTab(\"sort=serial&order=".($order=="ASC"?"DESC":"ASC")."&start=0\");'>".$LANG['common'][19]."</a></th>";
			echo "<th>".$LANG['tracking'][29]."</th>";
			echo "<th>".($sort=="`typename`"?$sort_img:"")."<a href='javascript:reloadTab(\"sort=typename&order=".($order=="ASC"?"DESC":"ASC")."&start=0\");'>".$LANG['common'][17]."</a></th>";
			echo "<th>".($sort=="`buyname`"?$sort_img:"")."<a href='javascript:reloadTab(\"sort=buyname&order=".($order=="ASC"?"DESC":"ASC")."&start=0\");'>".$LANG['software'][1]."</a></th>";
			echo "<th>".($sort=="`usename`"?$sort_img:"")."<a href='javascript:reloadTab(\"sort=usename&order=".($order=="ASC"?"DESC":"ASC")."&start=0\");'>".$LANG['software'][2]."</a></th>";
			echo "<th>".($sort=="`expire`"?$sort_img:"")."<a href='javascript:reloadTab(\"sort=expire&order=".($order=="ASC"?"DESC":"ASC")."&start=0\");'>".$LANG['software'][32]."</a></th>";
			echo "<th>".$LANG['help'][25]."</th>"; // "Computer" rather than "Affected To computer" ($LANG['software'][50] is too long) ??
			//echo "<th>".$LANG['financial'][3]."</th>";
			echo "</tr>";
			for ($tot=0;$data=$DB->fetch_assoc($result);){
				addToNavigateListItems(SOFTWARELICENSE_TYPE,$data['ID']);

				echo "<tr class='tab_bg_2'>";

				if ($license->can($data['ID'],"w")){
					echo "<td><input type='checkbox' name='item[".$data["ID"]."]' value='1'></td>";
				} else {
					echo "<td>&nbsp;</td>";
				}
            echo "<td><a href='softwarelicense.form.php?ID=".$data['ID']."'>".
               $data['name'].(empty($data['name'])?$data['ID']:"")."</a></td>";
				if ($software->isRecursive()) {
					echo "<td>".$data['entity']."</td>";
				}
				echo "<td>".$data['serial']."</td>";
				echo "<td align='right'>".($data['number']>0?$data['number']:$LANG['software'][4])."</td>";
				echo "<td>".$data['typename']."</td>";
				echo "<td>".$data['buyname']."</td>";
				echo "<td>".$data['usename']."</td>";
				echo "<td>".convDate($data['expire'])."</td>";
				if ($data['FK_computers']>0 && $computer->getFromDB($data['FK_computers'])) {
					$link = $computer->fields['name'];
					if (empty($link) || $_SESSION['glpiview_ID']) {
						$link .= " (".$computer->fields['ID'].")";
					}
					if ($computer->fields['deleted']) {
						$link .= " (".$LANG['common'][28].")";
					}
					echo "<td><a href='computer.form.php?ID=".$data['FK_computers']."'>".$link."</a>";


					// search installed version name
					// should be same as name of used_version, except for multiple installation
					$sql = "SELECT glpi_softwareversions.name " .
							"FROM glpi_softwareversions, glpi_inst_software " .
							"WHERE glpi_softwareversions.sID='$sID' " .
							"  AND glpi_inst_software.vID=glpi_softwareversions.ID" .
							"  AND glpi_inst_software.cID='".$data['FK_computers']."' " .
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
				showDisplayInfocomLink(SOFTWARELICENSE_TYPE, $data['ID'], 1);
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
				echo "<a href='softwarelicense.form.php?sID=$sID'>".$LANG['software'][8]."</a>";
			}
			echo "</td></tr>";
			echo "</table>";

			if ($canedit){
				echo "<table width='80%' class='tab_glpi'>";
				echo "<tr><td><img src=\"".$CFG_GLPI["root_doc"]."/pics/arrow-left.png\" alt=''></td><td><a onclick= \"if ( markCheckboxes('massiveactionlicense_form$rand') ) return false;\" href='".$_SERVER['PHP_SELF']."?select=all'>".$LANG['buttons'][18]."</a></td>";

				echo "<td>/</td><td ><a onclick=\"if ( unMarkCheckboxes('massiveactionlicense_form$rand') ) return false;\" href='".$_SERVER['PHP_SELF']."?select=none'>".$LANG['buttons'][19]."</a>";
				echo "</td><td class='left' width='80%'>";
				dropdownMassiveAction(SOFTWARELICENSE_TYPE,0,array('sID'=>$sID));
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
function showInstallations($searchID, $crit="sID") {
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
	if ($crit=="sID") {
		// Software ID
		$number = countElementsInTable("glpi_inst_software,glpi_computers,glpi_softwareversions",
			"glpi_inst_software.cID = glpi_computers.ID AND glpi_inst_software.vID = glpi_softwareversions.ID AND glpi_softwareversions.sID=$searchID" .
			getEntitiesRestrictRequest(' AND', 'glpi_computers') .
			" AND glpi_computers.deleted=0 AND glpi_computers.is_template=0");
	} else {
		//SoftwareVersion ID
		$number = countElementsInTable("glpi_inst_software,glpi_computers",
			"glpi_inst_software.cID = glpi_computers.ID AND glpi_inst_software.vID = $searchID" .
			getEntitiesRestrictRequest(' AND', 'glpi_computers') .
			" AND glpi_computers.deleted=0 AND glpi_computers.is_template=0");
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

	$query = "SELECT glpi_inst_software.*,glpi_computers.name AS compname, glpi_computers.ID AS cID,
			glpi_computers.name AS compname, glpi_computers.serial, glpi_computers.otherserial, glpi_users.name AS username,
			glpi_softwareversions.name as version, glpi_softwareversions.ID as vID, glpi_softwareversions.sID as sID, glpi_softwareversions.name as vername,
			glpi_entities.completename AS entity, glpi_dropdown_locations.completename AS location, glpi_dropdown_state.name AS state, glpi_groups.name AS groupe,
			glpi_softwarelicenses.name AS lname, glpi_softwarelicenses.ID AS lID
		FROM glpi_inst_software
		INNER JOIN glpi_softwareversions ON (glpi_inst_software.vID = glpi_softwareversions.ID)
		INNER JOIN glpi_computers ON (glpi_inst_software.cID = glpi_computers.ID)
		LEFT JOIN glpi_entities ON (glpi_computers.FK_entities=glpi_entities.ID)
		LEFT JOIN glpi_dropdown_locations ON (glpi_computers.location=glpi_dropdown_locations.ID)
		LEFT JOIN glpi_dropdown_state ON (glpi_computers.state=glpi_dropdown_state.ID)
		LEFT JOIN glpi_groups ON (glpi_computers.FK_groups=glpi_groups.ID)
		LEFT JOIN glpi_users ON (glpi_computers.FK_users=glpi_users.ID)
		LEFT JOIN glpi_softwarelicenses ON (glpi_softwarelicenses.sID=glpi_softwareversions.sID AND glpi_softwarelicenses.FK_computers=glpi_computers.ID)
		WHERE (glpi_softwareversions.$crit = '$searchID') " .
			getEntitiesRestrictRequest(' AND', 'glpi_computers') .
			" AND glpi_computers.deleted=0 AND glpi_computers.is_template=0 " .
		"ORDER BY " . $sort." ".$order . " LIMIT ".intval($start)."," . intval($_SESSION['glpilist_limit']);

	$rand=mt_rand();


	if ($result=$DB->query($query)){
		if ($data=$DB->fetch_assoc($result)){
			$sID = $data['sID'];

			$soft = new Software;
			$showEntity = ($soft->getFromDB($sID) && $soft->isRecursive());

			$title=$LANG['help'][31] ." = ". $soft->fields["name"];
			if ($crit=="ID") {
				$title .= " - " . $data["vername"];
			}
			initNavigateListItems(COMPUTER_TYPE,$title);

			$sort_img="<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/" . ($order == "DESC" ? "puce-down.png" : "puce-up.png") . "\" alt='' title=''>";
			if ($canedit) {
				echo "<form name='softinstall".$rand."' id='softinstall".$rand."' method='post' action=\"".$CFG_GLPI["root_doc"]."/front/software.licenses.php\">";
				echo "<input type='hidden' name='sID' value='$sID'>";
				echo "<table class='tab_cadrehov'><tr>";
				echo "<th>&nbsp;</th>";
			} else {
				echo "<table class='tab_cadrehov'><tr>";
			}

			if ($crit=="sID") {
				echo "<th>".($sort=="`vername`"?$sort_img:"")."<a href='javascript:reloadTab(\"sort=vername&order=".($order=="ASC"?"DESC":"ASC")."&start=0\");'>".$LANG['software'][5]."</a></th>";
			}
			echo "<th>".($sort=="`compname`"?$sort_img:"")."<a href='javascript:reloadTab(\"sort=compname&order=".($order=="ASC"?"DESC":"ASC")."&start=0\");'>".$LANG['common'][16]."</a></th>";
			if ($showEntity) {
				echo "<th>".(strstr($sort,"entity")?$sort_img:"")."<a href='javascript:reloadTab(\"sort=entity,compname&order=".($order=="ASC"?"DESC":"ASC")."&start=0\");'>".$LANG['entity'][0]."</a></th>";
			}
			echo "<th>".($sort=="`serial`"?$sort_img:"")."<a href='javascript:reloadTab(\"sort=serial&order=".($order=="ASC"?"DESC":"ASC")."&start=0\");'>".$LANG['common'][19]."</a></th>";
			echo "<th>".($sort=="`otherserial`"?$sort_img:"")."<a href='javascript:reloadTab(\"sort=otherserial&order=".($order=="ASC"?"DESC":"ASC")."&start=0\");'>".$LANG['common'][20]."</a></th>";
			echo "<th>".(strstr($sort,"location")?$sort_img:"")."<a href='javascript:reloadTab(\"sort=location,compname&order=".($order=="ASC"?"DESC":"ASC")."&start=0\");'>".$LANG['common'][15]."</a></th>";
			echo "<th>".(strstr($sort,"state")?$sort_img:"")."<a href='javascript:reloadTab(\"sort=state,compname&order=".($order=="ASC"?"DESC":"ASC")."&start=0\");'>".$LANG['state'][0]."</a></th>";
			echo "<th>".(strstr($sort,"groupe")?$sort_img:"")."<a href='javascript:reloadTab(\"sort=groupe,compname&order=".($order=="ASC"?"DESC":"ASC")."&start=0\");'>".$LANG['common'][35]."</a></th>";
			echo "<th>".(strstr($sort,"username")?$sort_img:"")."<a href='javascript:reloadTab(\"sort=username,compname&order=".($order=="ASC"?"DESC":"ASC")."&start=0\");'>".$LANG['common'][34]."</a></th>";
			echo "<th>".($sort=="`lname`"?$sort_img:"")."<a href='javascript:reloadTab(\"sort=lname&order=".($order=="ASC"?"DESC":"ASC")."&start=0\");'>".$LANG['software'][11]."</a></th>";
			echo "</tr>\n";

			do {
				addToNavigateListItems(COMPUTER_TYPE,$data["cID"]);

				echo "<tr class='tab_bg_2'>";
				if ($canedit){
					echo "<td><input type='checkbox' name='item[".$data["ID"]."]' value='1'></td>";
				}
				if ($crit=="sID") {
					echo "<td><a href='softwareversion.form.php?ID=".$data['vID']."'>".$data['version']."</a></td>";
				}
				$compname=$data['compname'];
				if (empty($compname) || $_SESSION['glpiview_ID']) {
					$compname .= " (".$data['cID'].")";
				}
				if ($canshowcomputer){
					echo "<td><a href='computer.form.php?ID=".$data['cID']."'>$compname</a></td>";
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
					echo "<td><a href='softwarelicense.form.php?ID=".$data['lID']."'>".$data['lname']."</a></td>";
				} else {
					echo "<td>&nbsp;</td>";
				}
				echo "</tr>\n";

			} while ($data=$DB->fetch_assoc($result));

			echo "</table>";

			if ($canedit){
				echo "<table width='80%' class='tab_glpi'>";
				echo "<tr><td><img src=\"".$CFG_GLPI["root_doc"]."/pics/arrow-left.png\" alt=''></td>";
				echo "<td class='left' width='100%'><a onclick= \"if ( markCheckboxes('softinstall".$rand."') ) return false;\" href='".$_SERVER['PHP_SELF']."?ID=$sID&amp;select=all'>".$LANG['buttons'][18]."</a>";
				echo "&nbsp;/&nbsp;<a onclick= \"if ( unMarkCheckboxes('softinstall".$rand."') ) return false;\" href='".$_SERVER['PHP_SELF']."?ID=$sID&amp;select=none'>".$LANG['buttons'][19]."</a>";

				dropdownSoftwareVersions("versionID",$sID);
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
 * @param $cID ID of the computer where to install a software
 * @param $vID ID of the version to install
 * @param $dohistory Do history ?
 * @return nothing
 */
function installSoftwareVersion($cID, $vID, $dohistory=1){
//function installSoftware($cID, $lID, $sID = '', $dohistory = 1) {

	global $DB,$LANG;

	if (!empty ($vID) && $vID > 0) {

		$query_exists = "SELECT ID FROM glpi_inst_software WHERE cID='".$cID."' AND vID='".$vID."'";
		$result = $DB->query($query_exists);
		if ($DB->numrows($result) > 0){
			return $DB->result($result, 0, "ID");
		} else {
			$query = "INSERT INTO glpi_inst_software (`cID`,`vID`) VALUES ('$cID','$vID')";

			if ($result = $DB->query($query)) {
				$newID = $DB->insert_id();
				$vers = new SoftwareVersion();
				if ($vers->getFromDB($vID)) {
					// Update use_version for Affected License
					$DB->query("UPDATE glpi_softwarelicenses
                           SET use_version='$vID'
                           WHERE sID='".$vers->fields["sID"]."'
                             AND FK_computers='$cID'
                             AND use_version=0");

					if ($dohistory) {
						$soft = new Software();
						if ($soft->getFromDB($vers->fields["sID"])) {
							$changes[0] = '0';
							$changes[1] = "";
							$changes[2] = addslashes($soft->fields["name"] . " " . $vers->fields["name"]);
							// Log on Computer history
							historyLog($cID, COMPUTER_TYPE, $changes, 0, HISTORY_INSTALL_SOFTWARE);
						}
						$comp = new Computer();
						if ($comp->getFromDB($cID)) {
							$changes[0] = '0';
							$changes[1] = "";
							$changes[2] = addslashes($comp->fields["name"]);
							// Log on SoftwareVersion history
							historyLog($vID, SOFTWAREVERSION_TYPE, $changes, 0, HISTORY_INSTALL_SOFTWARE);

							/* Log on Software history
							$changes[2] = addslashes($comp->fields["name"] . " " . $vers->fields["name"]);
							historyLog($vers->fields["sID"], SOFTWARE_TYPE, $changes, 0, HISTORY_INSTALL_SOFTWARE);
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

	$query_exists = "SELECT * FROM glpi_inst_software WHERE ID='".$instID."'";
	$result = $DB->query($query_exists);
	if ($DB->numrows($result) > 0){
		$cID=$DB->result($result, 0, "cID");
		$vID=$DB->result($result, 0, "vID");
		if ($vID!=$newvID && $newvID>0){
			uninstallSoftwareVersion($instID, $dohistory);
			installSoftwareVersion($cID, $newvID, $dohistory);
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

	$query2 = "SELECT * FROM glpi_inst_software WHERE (ID = '$ID')";
	$result2 = $DB->query($query2);
	$data = $DB->fetch_array($result2);
	// Not found => nothing to do
	if (!$data) return false;

	$query = "DELETE FROM glpi_inst_software WHERE (ID = '$ID')";

	if ($result = $DB->query($query)) {
		$vers = new SoftwareVersion();
		if ($vers->getFromDB($data["vID"])) {
			// Clear use_version for Affected License
			// If uninstalled is the used_version (OCS install new before uninstall old)
			$DB->query("UPDATE glpi_softwarelicenses SET use_version=0
				WHERE sID='".$vers->fields["sID"]."'
				  AND FK_computers='".$data["cID"]."'
				  AND use_version='".$vers->fields["ID"]."'");

			if ($dohistory) {
				$soft = new Software();
				if ($soft->getFromDB($vers->fields["sID"])) {
					$changes[0] = '0';
					$changes[1] = addslashes($soft->fields["name"] . " " . $vers->fields["name"]);
					$changes[2] = "";
					// Log on Computer history
					historyLog($data["cID"], COMPUTER_TYPE, $changes, 0, HISTORY_UNINSTALL_SOFTWARE);
				}
				$comp = new Computer();
				if ($comp->getFromDB($data["cID"])) {
					$changes[0] = '0';
					$changes[1] = addslashes($comp->fields["name"]);
					$changes[2] = "";
					// Log on SoftwareVersion history
					historyLog($data["vID"], SOFTWAREVERSION_TYPE, $changes, 0, HISTORY_UNINSTALL_SOFTWARE);

					/* Log on Software history
					$changes[1] = addslashes($comp->fields["name"] . " " . $vers->fields["name"]);
					historyLog($vers->fields["sID"], SOFTWARE_TYPE, $changes, 0, HISTORY_UNINSTALL_SOFTWARE);
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
 * @param $instID ID of the computer
 * @param $withtemplate template case of the view process
 * @return nothing
 */
function showSoftwareInstalled($instID, $withtemplate = '') {

	global $DB, $CFG_GLPI, $LANG;


	if (!haveRight("software", "r"))
		return false;

	$rand=mt_rand();
	$comp = new Computer();
	$comp->getFromDB($instID);
	$canedit=haveRight("software", "w");
	$FK_entities = $comp->fields["FK_entities"];

	$query = "SELECT glpi_software.category as category_id,
                    glpi_software.name as softname,
                    glpi_inst_software.ID as ID,
                    glpi_dropdown_state.name AS state,
                    glpi_softwareversions.sID,
                    glpi_softwareversions.name AS version,
                    glpi_softwareversions.id AS verid
            FROM glpi_inst_software
            LEFT JOIN glpi_softwareversions ON ( glpi_inst_software.vID = glpi_softwareversions.ID )
            LEFT JOIN glpi_dropdown_state ON ( glpi_dropdown_state.ID = glpi_softwareversions.state )
            LEFT JOIN glpi_software ON (glpi_softwareversions.sID = glpi_software.ID)
            WHERE glpi_inst_software.cID = '$instID'
            ORDER BY category, softname, version";

	$DB->query("SET SESSION group_concat_max_len = 9999999;");

	$result = $DB->query($query);
	$i = 0;

	echo "<div class='center'><table class='tab_cadre_fixe'>";

	if ((empty ($withtemplate) || $withtemplate != 2) && $canedit) {
		echo "<tr class='tab_bg_1'><td align='center' colspan='5'>";
		echo "<form method='post' action=\"" . $CFG_GLPI["root_doc"] . "/front/software.licenses.php\">";

		echo "<div class='software-instal'>";
		echo "<input type='hidden' name='cID' value='$instID'>";
		dropdownSoftwareToInstall("vID", $FK_entities);
		echo "<input type='submit' name='install' value=\"" . $LANG['buttons'][4] . "\" class='submit'>";
		echo "</div>";
		echo "</form>";
		echo "</td></tr>";
	}

	echo "<tr><th colspan='5'>" . $LANG['software'][17] . ":</th></tr>";

	$cat = -1;

	initNavigateListItems(SOFTWARE_TYPE,$LANG['help'][25]." = ".
      (empty($comp->fields["name"]) ? "(".$comp->fields["ID"].")":$comp->fields["name"]));
   initNavigateListItems(SOFTWARELICENSE_TYPE,$LANG['help'][25]." = ".
      (empty($comp->fields["name"]) ? "(".$comp->fields["ID"].")":$comp->fields["name"]));

	$installed=array();
	if ($DB->numrows($result)) {
		while ($data = $DB->fetch_array($result)) {
			if ($data["category_id"] != $cat) {
				displayCategoryFooter($cat,$rand,$canedit);
				$cat = displayCategoryHeader($instID, $data,$rand,$canedit);
			}

			$licids = displaySoftsByCategory($data, $instID, $withtemplate,$canedit);
			addToNavigateListItems(SOFTWARE_TYPE,$data["sID"]);
         foreach ($licids as $licid) {
            addToNavigateListItems(SOFTWARELICENSE_TYPE,$licid);
            $installed[]=$licid;
         }
		}

		displayCategoryFooter($cat,$rand,$canedit);

		/* seems not used
		$q = "SELECT count(*) FROM glpi_software WHERE deleted='0' AND is_template='0'";
		$result = $DB->query($q);
		$nb = $DB->result($result, 0, 0);
		*/
	}

	// Affected licenses NOT installed
	$query = "SELECT `glpi_softwarelicenses`.*,
                     glpi_software.name as softname,
                     glpi_softwareversions.name AS version,
                     glpi_dropdown_state.name AS state
            FROM glpi_softwarelicenses
            INNER JOIN glpi_software ON (glpi_softwarelicenses.sID = glpi_software.ID)
            LEFT JOIN glpi_softwareversions ON ( glpi_softwarelicenses.buy_version = glpi_softwareversions.ID )
            LEFT JOIN glpi_dropdown_state ON ( glpi_dropdown_state.ID = glpi_softwareversions.state )
            WHERE glpi_softwarelicenses.FK_computers = '$instID' ";
	if (count($installed)) {
		$query .= " AND glpi_softwarelicenses.ID NOT IN (".implode(',',$installed).")";
	}
	$req=$DB->request($query);
	if ($req->numrows()) {
		$cat=true;
		foreach ($req as $data) {
			if ($cat) {
				displayCategoryHeader($instID, $data,$rand,$canedit);
				$cat = false;
			}
			displaySoftsByLicense($data, $instID, $withtemplate, $canedit);
         addToNavigateListItems(SOFTWARELICENSE_TYPE,$data["ID"]);
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
			echo "<tr><td><img src=\"".$CFG_GLPI["root_doc"]."/pics/arrow-left.png\" alt=''></td><td><a onclick= \"if ( markCheckboxes('lic_form$cat$rand') ) return false;\" href='".$_SERVER['PHP_SELF']."?ID=".$cat."&amp;select=all'>".$LANG['buttons'][18]."</a></td>";
			echo "<td>/</td><td ><a onclick=\"if ( unMarkCheckboxes('lic_form$cat$rand') ) return false;\" href='".$_SERVER['PHP_SELF']."?ID=".$cat."&amp;select=none'>".$LANG['buttons'][19]."</a>";
			echo "</td><td class='left' width='80%'>";

			echo "<select name='update_licenses$cat$rand' id='update_licenses_choice$cat$rand'>";
			echo "<option value=''>-----</option>";
			if (isset($cat)) {
				echo "<option value='uninstall_license'>".$LANG['buttons'][5]."</option>";
			} else {
				echo "<option value='install_license'>".$LANG['buttons'][4]."</option>";
			}
			echo "</select>";

			$params=array('type'=>'__VALUE__');
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
 * @param $cID ID of the computer
 * @param $data data used to display
 * @param $rand random for unicity
 * @param $canedit boolean
 *
 * @return new category ID
 */
function displayCategoryHeader($cID,$data,$rand,$canedit) {
	global $LANG, $CFG_GLPI;

	$display = "none";

	if (isset($data["category_id"])) {
		$cat = $data["category_id"];
		if ($cat) {
			// Categorized
			$catname = getDropdownName('glpi_dropdown_software_category',$cat);
			$display = $_SESSION["glpiexpand_soft_categorized"];
		} else {
			// Not categorized
			$catname = $LANG['softwarecategories'][3];
			$display = $_SESSION["glpiexpand_soft_not_categorized"];
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
	echo "<input type='hidden' name='cID' value='$cID'>";
	echo "			<table class='tab_cadre_fixe'>";
	echo "				<tr>";
	if ($canedit) {
		echo "<th>&nbsp;</th>";
	}
   echo "<th>" . $LANG['common'][16] . "</th><th>" . $LANG['state'][0] . "</th><th>" .
      (isset($data["category_id"]) ? $LANG['software'][5] : $LANG['software'][1])  .
      "</th><th>" . $LANG['software'][11] . "</th></tr>\n";

	return $cat;
}

/**
 * Display a installed software for a category
 *
 * @param $data data used to display
 * @param $instID ID of the computer
 * @param $withtemplate template case of the view process
 * @return nothing
 */
function displaySoftsByCategory($data, $instID, $withtemplate,$canedit) {
	global $DB, $LANG, $CFG_GLPI, $INFOFORM_PAGES;

	$ID = $data["ID"];
   $verid = $data["verid"];
	$multiple = false;

	echo "<tr class='tab_bg_1'>";
	if ($canedit) {
		echo "<td><input type='checkbox' name='license_".$data['ID']."'></td>";
	}
	echo "<td class='center'><strong><a href=\"" . $CFG_GLPI["root_doc"] . "/front/software.form.php?ID=" . $data['sID'] . "\">";
	echo $data["softname"] . ($_SESSION["glpiview_ID"] ? " (" . $data['sID'] . ")" : "") . "</a>";
	echo "</strong></td>";
	echo "<td>" . $data["state"] . "</td>";

	echo "<td>" . $data["version"];
	if ((empty ($withtemplate) || $withtemplate != 2) && $canedit) {
		echo " - <a href=\"" . $CFG_GLPI["root_doc"] . "/front/software.licenses.php?uninstall=uninstall&amp;ID=$ID&amp;cID=$instID\">";
		echo "<strong>" . $LANG['buttons'][5] . "</strong></a>";
	}
	echo "</td><td>";

   $query = "SELECT `glpi_softwarelicenses`.*, `glpi_dropdown_licensetypes`.`name` as type
             FROM `glpi_softwarelicenses`
             LEFT JOIN `glpi_dropdown_licensetypes`
                  ON `glpi_softwarelicenses`.`type`=`glpi_dropdown_licensetypes`.`ID`
             WHERE `glpi_softwarelicenses`.`FK_computers`='$instID'
               AND (`glpi_softwarelicenses`.`use_version`='$verid'
                    OR (`glpi_softwarelicenses`.`use_version`=0
                        AND `glpi_softwarelicenses`.`buy_version`='$verid'))";

   $licids=array();
   foreach ($DB->request($query) as $licdata) {
      $licids[]=$licdata['ID'];
      echo "<strong>". $licdata['name'] . "</strong>&nbsp; ";
      if ($licdata['type']) {
         echo "(".$licdata['type'].")&nbsp; ";
      }
      $link = GLPI_ROOT.'/'.$INFOFORM_PAGES[SOFTWARELICENSE_TYPE]."?ID=".$licdata['ID'];
      displayToolTip ($LANG['common'][16]."&nbsp;: ".$licdata['name']."<br>".
                      $LANG['common'][19]."&nbsp;: ".$licdata['serial']."<br>".$licdata['comments'],
                      $link);
      echo "<br>";
   }
   if (!count($licids)) {
      echo "&nbsp;";
   }

   echo "</td></tr>\n";

   return $licids;
}

/**
 * Display a software for a License (not installed)
 *
 * @param $data data used to display
 * @param $instID ID of the computer
 * @param $withtemplate template case of the view process
 * @return nothing
 */
function displaySoftsByLicense($data, $instID, $withtemplate,$canedit) {
	global $LANG, $CFG_GLPI, $INFOFORM_PAGES;

	$ID = $data["buy_version"];
	$multiple = false;
   $link = GLPI_ROOT.'/'.$INFOFORM_PAGES[SOFTWARELICENSE_TYPE]."?ID=".$data['ID'];

	echo "<tr class='tab_bg_1'>";
   if ($canedit) {
      echo "<td>";
      if ((empty ($withtemplate) || $withtemplate != 2) && $ID>0) {
         echo "<input type='checkbox' name='version_$ID'>";
      }
      echo "</td>";
   }
	echo "<td class='center'><strong><a href=\"" . $CFG_GLPI["root_doc"] . "/front/software.form.php?ID=" . $data['sID'] . "\">";
	echo $data["softname"] . ($_SESSION["glpiview_ID"] ? " (" . $data['sID'] . ")" : "") . "</a>";
	echo "</strong></td>";
	echo "<td>" . $data["state"] . "</td>";

	echo "<td>" . $data["version"];
   if ((empty ($withtemplate) || $withtemplate != 2) && $canedit && $ID>0) {
		echo " - <a href=\"" . $CFG_GLPI["root_doc"] . "/front/software.licenses.php?install=install&amp;vID=$ID&amp;cID=$instID\">";
		echo "<strong>" . $LANG['buttons'][4] . "</strong></a>";
	}
   echo "</td></td><strong>" . $data["name"] . "</strong>&nbsp; ";
   if ($data["type"]) {
      echo " (". getDropdownName("glpi_dropdown_licensetypes",$data["type"]).")&nbsp; ";
   }
   displayToolTip ($LANG['common'][16]."&nbsp;: ".$data['name']."<br>".
                   $LANG['common'][19]."&nbsp;: ".$data['serial']."<br>".$data['comments'],
                   $link);
   echo "</td></tr>";
}

/**
 * Count Installations of a software and create string to display
 *
 * @param $sID ID of the software
 * @param $nohtml do not use HTML to highlight ?
 * @return string contains counts
 */
function countInstallations($sID, $nohtml = 0) {

	global $DB, $CFG_GLPI, $LANG;
	$installed = countInstallationsForSoftware($sID);
	$out="";
	if (!$nohtml)
		$out .= $LANG['software'][19] . ": <strong>$installed</strong>";
	else
		$out .= $LANG['software'][19] . ": $installed";

	$total=getNumberOfLicences($sID);

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
 * @param $sID software ID
 * @return number of installations
 */
function countInstallationsForSoftware($sID) {
	global $DB;
	$query = "SELECT count(glpi_inst_software.ID)
			FROM glpi_softwareversions
			INNER JOIN glpi_inst_software ON (glpi_softwareversions.ID = glpi_inst_software.vID)
			INNER JOIN glpi_computers ON ( glpi_inst_software.cID=glpi_computers.ID)
			WHERE glpi_softwareversions.sID='$sID'
				AND glpi_computers.deleted=0 AND glpi_computers.is_template=0 " .
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
 * @param $vID version ID
 * @param $entity to search for computer in (default = all active entities)
 *
 * @return number of installations
 */
function countInstallationsForVersion($vID, $entity='') {
	global $DB;
	$query = "SELECT count(glpi_inst_software.ID)
			FROM glpi_inst_software
			INNER JOIN glpi_computers ON ( glpi_inst_software.cID=glpi_computers.ID)
			WHERE glpi_inst_software.vID='$vID'
				AND glpi_computers.deleted=0 AND glpi_computers.is_template=0 " .
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
 * @param $vID version ID
 * @param $entity to search for licenses in (default = all active entities)
 *
 * @return number of installations
 */
function countLicensesForVersion($vID, $entity='') {
	global $DB;
	$query = "SELECT count(*)
			FROM glpi_softwarelicenses
			WHERE buy_version='$vID' " .
			getEntitiesRestrictRequest('AND', 'glpi_softwarelicenses','',$entity);

	$result = $DB->query($query);

	if ($DB->numrows($result) != 0) {
		return $DB->result($result, 0, 0);
	} else
		return 0;

}

/**
 * Get number of licenses to buy of a software
 *
 * @param $sID software ID
 * @return number of licenses to buy
 */
/*
function getLicenceToBuy($sID) {
	global $DB;
	$query = "SELECT ID FROM glpi_licenses WHERE (sID = '$sID' AND buy ='0'  AND serial <> 'free' AND serial <> 'global')";
	$result = $DB->query($query);
	return $DB->numrows($result);
}
*/
/**
 * Get number of licensesof a software
 *
 * @param $sID software ID
 * @return number of licenses
 */
function getNumberOfLicences($sID) {
	global $DB;

	$query = "SELECT ID FROM glpi_softwarelicenses WHERE (sID = '$sID' AND number='-1') " .
		getEntitiesRestrictRequest('AND', 'glpi_softwarelicenses', '', '', true);
	$result = $DB->query($query);
	if ($DB->numrows($result)){
		return -1;
	} else {
		$query = "SELECT SUM(number) FROM glpi_softwarelicenses WHERE (sID = '$sID' AND number > 0) " .
			getEntitiesRestrictRequest('AND', 'glpi_softwarelicenses', '', '', true);
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
 * @param comments
 * @return the software's ID
 */
function addSoftware($name, $manufacturer, $entity, $comments = '') {
	global $LANG, $DB,$CFG_GLPI;
	$software = new Software;

	$manufacturer_id = 0;
	if ($manufacturer != '') {
		$manufacturer_id = externalImportDropdown("glpi_dropdown_manufacturer", $manufacturer);
	}

	$sql = "SELECT ID FROM glpi_software " .
		"WHERE FK_glpi_enterprise='$manufacturer_id' AND name='".$name."' " .
		getEntitiesRestrictRequest('AND', 'glpi_software', 'FK_entities', $entity, true);

	$res_soft = $DB->query($sql);
	if ($soft = $DB->fetch_array($res_soft)) {
		$id = $soft["ID"];
	} else {
		$input["name"] = $name;
		$input["FK_glpi_enterprise"] = $manufacturer_id;
		$input["FK_entities"] = $entity;
		// No comments
		//$input["comments"] = $LANG['rulesengine'][88];
		$input["helpdesk_visible"] = $CFG_GLPI["software_helpdesk_visible"];

		//Process software's category rules
		$softcatrule = new SoftwareCategoriesRuleCollection;
		$result = $softcatrule->processAllRules(null, null, $input);
		if (!empty ($result) && isset ($result["category"])) {
			$input["category"] = $result["category"];
		} else {
			$input["category"] = 0;
		}

		$id = $software->add($input);
	}
	return $id;
}

/**
 * Put software in trash because it's been removed by GLPI software dictionnary
 * @param $ID  the ID of the software to put in trash
 * @param $comments the comments to add to the already existing software's comments
 */
function putSoftwareInTrash($ID, $comments = '') {
	global $LANG,$CFG_GLPI;
	$software = new Software;
	//Get the software's fields
	$software->getFromDB($ID);

	$input["ID"] = $ID;
	$input["deleted"] = 1;

	$config = new Config;
	$config->getFromDB($CFG_GLPI["ID"]);

	//change category of the software on deletion (if defined in glpi_config)
	if (isset($config->fields["category_on_software_delete"]) && $config->fields["category_on_software_delete"] != 0)
		$input["category"] = $config->fields["category_on_software_delete"];

	//Add dictionnary comment to the current comments
	$input["comments"] = ($software->fields["comments"] != '' ? "\n" : '') . $comments;

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

	if (!empty ($result) && isset ($result["category"]))
		$input["category"] = $result["category"];
	else
		$input["category"] = 0;

	$s->restore(array (
		"ID" => $ID
	));
}
/**
 * Add a Software. If already exist in trash restore it
 * @param name the software's name
 * @param manufacturer the software's manufacturer
 * @param entity the entity in which the software must be added
 * @param comments comments
 */
function addSoftwareOrRestoreFromTrash($name,$manufacturer,$entity,$comments='') {
	global $DB;
	//Look for the software by his name in GLPI for a specific entity
	$query_search = "SELECT glpi_software.ID as ID, glpi_software.deleted as deleted
			FROM glpi_software
			WHERE name = '".$name."' AND is_template='0' AND FK_entities='".$entity."'";
	$result_search = $DB->query($query_search);
	if ($DB->numrows($result_search) > 0) {
		//Software already exists for this entity, get his ID
		$data = $DB->fetch_array($result_search);
		$ID = $data["ID"];

		// restore software
		if ($data['deleted'])
			removeSoftwareFromTrash($ID);
	} else {
		$ID = 0;
	}

	if (!$ID)
		$ID = addSoftware($name, $manufacturer, $entity, $comments);
	return $ID;
}

/**
 * Cron action on softwares : alert on expired licences
 * @param $display display informations instead or log in file ?
 * @return 0 : nothing to do 1 : done with success
 **/
function cron_software($display=false){
	global $DB,$CFG_GLPI,$LANG;

	if (!$CFG_GLPI["mailing"]){
		return false;
	}

	loadLanguage($CFG_GLPI["language"]);

	$message=array();
	$items_notice=array();
	$items_end=array();

	// Check notice
	$query="SELECT glpi_softwarelicenses.*, glpi_softwarelicenses.FK_entities, glpi_software.name as softname
		FROM glpi_softwarelicenses
      INNER JOIN glpi_software ON (glpi_softwarelicenses.sID = glpi_software.ID)
		LEFT JOIN glpi_alerts ON (glpi_softwarelicenses.ID = glpi_alerts.FK_device
					AND glpi_alerts.device_type='".SOFTWARELICENSE_TYPE."'
					AND glpi_alerts.type='".ALERT_END."')
		WHERE glpi_alerts.date IS NULL
			AND glpi_softwarelicenses.expire IS NOT NULL
			AND glpi_softwarelicenses.expire < CURDATE()
         AND `glpi_software`.`deleted`='0'
         AND `glpi_software`.`is_template`='0'";

	$result=$DB->query($query);
	if ($DB->numrows($result)>0){
		while ($data=$DB->fetch_array($result)){
			if (!isset($message[$data["FK_entities"]])){
				$message[$data["FK_entities"]]="";
			}
			if (!isset($items_notice[$data["FK_entities"]])){
				$items[$data["FK_entities"]]=array();
			}

			$name = $data['softname'].' - '.$data['name'].' - '.$data['serial'];

			// define message alert
			if (strstr($message[$data["FK_entities"]],$name)===false){
				$message[$data["FK_entities"]].=$LANG['mailing'][51]." ".$name.": ".convDate($data["expire"])."<br>\n";
			}
			$items[$data["FK_entities"]][]=$data["ID"];
		}


	}
	if (count($message)>0){
		foreach ($message as $entity => $msg){
			$mail=new MailingAlert("alertlicense",$msg,$entity);
			if ($mail->send()){
				if ($display){
					addMessageAfterRedirect(getDropdownName("glpi_entities",$entity).":  $msg");
				}
				logInFile("cron",getDropdownName("glpi_entities",$entity).":  $msg\n");

				// Mark alert as done
				$alert=new Alert();
				$input["device_type"]=SOFTWARELICENSE_TYPE;

				$input["type"]=ALERT_END;
				if (isset($items[$entity])){
					foreach ($items[$entity] as $ID){
						$input["FK_device"]=$ID;
						$alert->add($input);
						unset($alert->fields['ID']);
					}
				}
			} else {
				if ($display){
					addMessageAfterRedirect(getDropdownName("glpi_entities",$entity).":  Send licenses alert failed",false,ERROR);
				}
				logInFile("cron",getDropdownName("glpi_entities",$entity).":  Send licenses alert failed\n");
			}
		}
		return 1;
	}

	return 0;


}


?>
