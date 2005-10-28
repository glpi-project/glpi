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


	include ("_relpos.php");
	include ($phproot."/glpi/includes.php");

	checkAuthentication("post-only");

	// Make a select box
	$db = new DB;

	$where="";		
	if (strlen($_POST['searchText'])>0&&$_POST['searchText']!=$cfg_features["ajax_wildcard"])
		$where.=" AND glpi_licenses.serial LIKE '%".$_POST['searchText']."%' ";
		
	$NBMAX=$cfg_layout["dropdown_max"];
	$LIMIT="LIMIT 0,$NBMAX";

	if ($_POST['searchText']==$cfg_features["ajax_wildcard"]) $LIMIT="";
						
	$query = "SELECT DISTINCT glpi_licenses.* from glpi_licenses ";
	$query.= " LEFT JOIN glpi_inst_software on (glpi_licenses.ID=glpi_inst_software.license)";
	$query.= " WHERE glpi_licenses.sID='".$_POST['sID']."' AND (glpi_inst_software.cID IS NULL OR glpi_licenses.serial='free' OR glpi_licenses.serial='global' ) ";
	$query.= " $where order by serial ASC";

//echo $query;
		
		$result = $db->query($query);
		echo "<select name=\"".$_POST['myname']."\" size='1'>";
		
		if ($_POST['searchText']!=$cfg_features["ajax_wildcard"]&&$db->numrows($result)==$NBMAX)
			echo "<option value=\"0\">--".$lang["common"][11]."--</option>";
	
		echo "<option value=\"0\">-----</option>";
		$i = 0;
		$number = $db->numrows($result);
		$today=date("Y-m-d"); 
		if ($number > 0) {
			while ($data = $db->fetch_assoc($result)) {
				$output = $data['serial']." - ";
				
				$expirer=0;
				if ($data['expire']!=NULL&&$today>$data['expire']) $expirer=1; 

				if ($data['expire']==NULL)
					$output.= $lang["software"][26];
				else {
					if ($expirer) $output.= $lang["software"][27];
					else $output.= $lang["software"][25]."&nbsp;".$data['expire'];
				}
				
				if ($data['buy']=='Y')
					$output.=" - ".$lang["software"][35];
				else 
					$output.=" - ".$lang["software"][37];
											
				if ($data['oem']=='Y'){
					$comp=new Computer();
					$comp->getFromDB($data["oem_computer"]);
					$output.=" - ".$lang['software'][33]. " ".$comp->fields['name']."(".$comp->fields['ID'].")";
				}
				
				
				if (empty($output)) $output="&nbsp;";
				$ID = $data['ID'];
				echo "<option value=\"$ID\">$output</option>";
				$i++;
			}
		} 
		echo "</select>";


?>