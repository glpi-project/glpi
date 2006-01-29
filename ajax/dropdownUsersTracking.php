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
// Make a select box with all glpi users
	$db = new DB;
		$where="'1'='1'";

	if (isset($_POST['value']))
		$where.=" AND  (glpi_users.ID <> '".$_POST['value']."' ";
				
	if (strlen($_POST['searchText'])>0&&$_POST['searchText']!=$cfg_features["ajax_wildcard"])
		$where.=" AND (glpi_users.name LIKE '%".$_POST['searchText']."%' OR glpi_users.realname LIKE '%".$_POST['searchText']."%')";

	$where.=")";	

	$NBMAX=$cfg_layout["dropdown_max"];
	$LIMIT="LIMIT 0,$NBMAX";
	if ($_POST['searchText']==$cfg_features["ajax_wildcard"]) $LIMIT="";
	
	$query = "SELECT DISTINCT glpi_users.ID, glpi_users.name, glpi_users.realname FROM glpi_tracking INNER JOIN glpi_users ON (glpi_users.ID=glpi_tracking.".$_POST['champ']." AND glpi_tracking.".$_POST['champ']." <> '') WHERE $where ORDER BY glpi_users.realname,glpi_users.name $LIMIT";
	$result = $db->query($query);

	echo "<select name=\"".$_POST['myname']."\">";
	$i = 0;

	if ($_POST['searchText']!=$cfg_features["ajax_wildcard"]&&$db->numrows($result)==$NBMAX)
	echo "<option value=\"0\">--".$lang["common"][11]."--</option>";
	
		
	$number = $db->numrows($result);
	
	echo "<option value=\"0\">[ ".$lang["search"][7]." ]</option>";
	
	if (isset($_POST['value'])){
		$output=getUserName($_POST['value']);
		if (!empty($output)&&$output!="&nbsp;")
		echo "<option selected value='".$_POST['value']."' title=\"$output\">".substr($output,0,$cfg_layout["dropdown_limit"])."</option>";
	}	
	
	if ($number > 0) {
		while ($i < $number) {
			$output = $db->result($result, $i, "name");
			$realname=$db->result($result, $i, "realname");
			if (!empty($realname)) $output = $realname;
			$ID = $db->result($result, $i, "ID");
			if ($ID == $value) {
				echo "<option value=\"$ID\" selected title=\"$output\">".substr($output,0,$cfg_layout["dropdown_limit"])."</option>";
			} else {
				echo "<option value=\"$ID\" title=\"$output\">".substr($output,0,$cfg_layout["dropdown_limit"])."</option>";
			}
			$i++;
   		}
	}
	echo "</select>";
?>