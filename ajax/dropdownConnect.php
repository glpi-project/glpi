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


	include ("_relpos.php");
	include ($phproot."/glpi/includes.php");
	header("Content-Type: text/html; charset=UTF-8");
	header_nocache();

	checkAuthentication("post-only");

	// Make a select box
	$db = new DB;

		$items=array(
			COMPUTER_TYPE=>"glpi_computers",
			PRINTER_TYPE=>"glpi_printers",
			MONITOR_TYPE=>"glpi_monitors",
			PERIPHERAL_TYPE=>"glpi_peripherals",
		);

		$table=$items[$_POST["idtable"]];
		$where="";		
		if (in_array($table,$deleted_tables))
			$where.=" AND $table.deleted='N' ";
		if (in_array($table,$template_tables))
			$where.=" AND $table.is_template='0' ";		
			
		if (strlen($_POST['searchText'])>0&&$_POST['searchText']!=$cfg_features["ajax_wildcard"])
			$where.=" AND $table.name LIKE '%".$_POST['searchText']."%' ";

		$NBMAX=$cfg_layout["dropdown_max"];
		$LIMIT="LIMIT 0,$NBMAX";

		if ($_POST['searchText']==$cfg_features["ajax_wildcard"]) $LIMIT="";
						
	$CONNECT_SEARCH="(glpi_connect_wire.ID IS NULL";	
	if ($_POST["idtable"]==MONITOR_TYPE||$_POST["idtable"]==PERIPHERAL_TYPE)
		$CONNECT_SEARCH.=" OR $table.is_global='1' ";
	$CONNECT_SEARCH.=")";
	if ($_POST["idtable"]==COMPUTER_TYPE)
		$CONNECT_SEARCH=" '1' = '1' ";
		
	$LEFTJOINCONNECT="";
	if ($_POST["idtable"]!=COMPUTER_TYPE)		
		$LEFTJOINCONNECT="left join glpi_connect_wire on ($table.ID = glpi_connect_wire.end1 AND glpi_connect_wire.type = '".$_POST['idtable']."')";
	$query = "SELECT DISTINCT $table.ID as ID,$table.name as name from $table $LEFTJOINCONNECT WHERE $CONNECT_SEARCH $where order by name ASC";

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
				$ID = $data['ID'];
				if (empty($output)) $output="($ID)";

				echo "<option value=\"$ID\" title=\"$output\">".substr($output,0,$cfg_layout["dropdown_limit"])."</option>";
				$i++;
			}
		}
		echo "</select>";


?>