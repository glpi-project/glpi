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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------


define('GLPI_ROOT','..');
$AJAX_INCLUDE=1;
$NEEDED_ITEMS=array("software","computer");
include (GLPI_ROOT."/inc/includes.php");

header("Content-Type: text/html; charset=UTF-8");
header_nocache();

checkRight("software","w");

if ($_POST['sID']>0){
	// Make a select box

	$where="";
	if (!$_POST["only_globalfree"]){
		$where=" glpi_inst_software.cID IS NULL OR glpi_inst_software.cID = 0 OR ";
	}
	$query = "SELECT DISTINCT glpi_licenses.* from glpi_licenses ";
	$query.= " LEFT JOIN glpi_inst_software on (glpi_licenses.ID=glpi_inst_software.license)";
	$query.= " WHERE glpi_licenses.sID='".$_POST['sID']."' AND ($where glpi_licenses.serial='free' OR glpi_licenses.serial='global' ) ";
	$query.= " GROUP BY version, serial, expire, oem, oem_computer, buy ORDER BY version, serial ASC";


	$result = $DB->query($query);
	$number=$DB->numrows($result);
	echo "<select name=\"".$_POST['myname']."\" size='1'>";


	echo "<option value=\"0\">-----</option>";

	if ($number==0&&!isGlobalSoftware($_POST["sID"])&&!isFreeSoftware($_POST["sID"]))
		echo "<option value=\"-1\">--".$LANG["software"][43]."--</option>";

	$today=date("Y-m-d"); 
	if ($number) {
		while ($data = $DB->fetch_assoc($result)) {

			$output = $data['version']." ".$data['serial']." - ";

			$expirer=0;
			if ($data['expire']!=NULL&&$today>$data['expire']) $expirer=1; 

			if ($data['expire']==NULL)
				$output.= $LANG["software"][26];
			else {
				if ($expirer) $output.= $LANG["software"][27];
				else $output.= $LANG["software"][25]."&nbsp;".$data['expire'];
			}

			if ($data['buy'])
				$output.=" - ".$LANG["software"][35];
			else 
				$output.=" - ".$LANG["software"][37];

			if ($data['oem']){
				$comp=new Computer();
				$comp->getFromDB($data["oem_computer"]);
				$output.=" - ".$LANG["software"][28]. " ".$comp->fields['name'];
				if ($CFG_GLPI["view_ID"]) $output.=" (".$comp->fields['ID'].")";
			}


			$ID = $data['ID'];
			if (empty($output)) $output="($ID)";
			echo "<option value=\"$ID\" title=\"".cleanInputText($output)."\">".$output."</option>";
		}
	} 
	echo "</select>&nbsp;";
}

?>
