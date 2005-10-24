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

	if($_POST['table'] == "glpi_dropdown_netpoint") {
		
		if (!empty($_POST['searchText'])&&$_POST['searchText']!=$cfg_features["ajax_wildcard"])
			$where=" WHERE (t1.name LIKE '%".$_POST['searchText']."%' OR t2.completename LIKE '%".$_POST['searchText']."%')";
			
		$NBMAX=$cfg_layout["dropdown_max"];
		$LIMIT="LIMIT 0,$NBMAX";
		if ($_POST['searchText']==$cfg_features["ajax_wildcard"]) $LIMIT="";
	
		$query = "select t1.ID as ID, t1.name as netpname, t2.name as locname from glpi_dropdown_netpoint as t1";
		$query .= " left join glpi_dropdown_locations as t2 on t1.location = t2.ID";
		$query.=$where;
		$query .= " order by t2.name, t1.name $LIMIT"; 
		$result = $db->query($query);
		echo "<select name=\"".$_POST['myname']."\">";
		
		if ($_POST['searchText']!=$cfg_features["ajax_wildcard"]&&$db->numrows($result)==$NBMAX)
		echo "<option value=\"0\">--".$lang["common"][11]."--</option>";
		
		$i = 0;
		$number = $db->numrows($result);
		if ($number > 0) {
			while ($i < $number) {
				$output = $db->result($result, $i, "netpname");
				$loc = $db->result($result, $i, "locname");
				$ID = $db->result($result, $i, "ID");
				echo "<option value=\"$ID\">$output ($loc)</option>";
				$i++;
			}
		}
		echo "</select>";
	}
 else {
		$where="WHERE '1'='1' ";
		if (in_array($_POST['table'],$deleted_tables))
			$where.=" AND deleted='N' ";
		if (in_array($_POST['table'],$template_tables))
			$where.=" AND is_template='0' ";		
			
		if (!empty($_POST['searchText'])&&$_POST['searchText']!=$cfg_features["ajax_wildcard"])
		if (in_array($_POST['table'],$dropdowntree_tables))
		$where.=" AND completename LIKE '%".$_POST['searchText']."%' ";
		else if ($_POST['table']=="glpi_users")
			$where.=" AND (name LIKE '%".$_POST['searchText']."%'  OR realname LIKE '%".$_POST['searchText']."%')";
		else $where.=" AND name LIKE '%".$_POST['searchText']."%' ";

		$NBMAX=$cfg_layout["dropdown_max"];
		$LIMIT="LIMIT 0,$NBMAX";
		if ($_POST['searchText']==$cfg_features["ajax_wildcard"]) $LIMIT="";
						
		if (in_array($_POST['table'],$dropdowntree_tables))
			$query = "SELECT ID, completename as name FROM ".$_POST['table']." $where ORDER BY completename $LIMIT";
		else $query = "SELECT * FROM ".$_POST['table']." $where ORDER BY name $LIMIT";
		$result = $db->query($query);
		echo "<select name=\"".$_POST['myname']."\" size='1'>";
		
		if ($_POST['searchText']!=$cfg_features["ajax_wildcard"]&&$db->numrows($result)==$NBMAX)
			echo "<option value=\"0\">--".$lang["common"][11]."--</option>";
	
		echo "<option value=\"0\">-----</option>";
		$i = 0;
		$number = $db->numrows($result);
		if ($number > 0) {
			while ($data = $db->fetch_array($result)) {
				$output = $data['name'];
				if ($_POST["table"]=="glpi_users"&&!empty($data["realname"])) $output = $data['realname'];
				if (empty($output)) $output="&nbsp;";
				$ID = $data['ID'];
				echo "<option value=\"$ID\">$output</option>";
				$i++;
			}
		}
		echo "</select>";
	}


?>