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
	NETWORKING_TYPE=>"glpi_networking",
	PRINTER_TYPE=>"glpi_printers",
	PERIPHERAL_TYPE=>"glpi_peripherals",
	);


		$table=$items[$_POST["type"]];

		$where="";		
		$where.=" AND $table.deleted='N' ";
		$where.=" AND $table.is_template='0' ";		
			
		$query =  "SELECT DISTINCT glpi_networking_wire.ID as WID, glpi_networking_ports.ID as DID, $table.name as CNAME, glpi_networking_ports.name  as NNAME, glpi_networking_ports.ifaddr as IP, glpi_networking_ports.ifmac as MAC";
		$query.= " FROM $table ";
		$query.= " LEFT JOIN glpi_networking_ports ON (glpi_networking_ports.on_device='".$_POST['item']."' AND glpi_networking_ports.device_type='".$_POST['type']."' AND glpi_networking_ports.on_device=$table.ID) "; 
		$query.= " LEFT JOIN glpi_networking_wire ON (glpi_networking_wire.end1=glpi_networking_ports.ID OR glpi_networking_wire.end2=glpi_networking_ports.ID)";
		$query.= " WHERE glpi_networking_wire.ID IS NULL AND glpi_networking_ports.ID IS NOT NULL AND glpi_networking_ports.ID <> '".$_POST['current']."' ";
		$query.= $where;
		$query.= " ORDER BY glpi_networking_ports.ID";
	$result = $db->query($query);
	echo "<select name=\"".$_POST['myname']."\" size='1'>";
		
		echo "<option value=\"0\">-----</option>";
		$i = 0;
		$number = $db->numrows($result);
		if ($number > 0) {
			while ($data = $db->fetch_array($result)) {
				$output = $data['CNAME'];
				if (!empty($data['NNAME'])) $output.= " - ".$data['NNAME'];
				if (!empty($data['IP'])) $output.= " - ".$data['IP'];
				if (!empty($data['MAC'])) $output.= " - ".$data['MAC'];
				$ID = $data['DID'];
				if (empty($output)) $output="($ID)";
				echo "<option value=\"$ID\">$output</option>";
				$i++;
			}
		}
		echo "</select>";

?>