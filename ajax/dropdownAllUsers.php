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
	
	if (isset($_POST['value']))
		$where =" AND  (ID <> '".$_POST['value']."' ";

	if (strlen($_POST['searchText'])>0&&$_POST['searchText']!=$cfg_glpi["ajax_wildcard"])
		$where.=" AND (name LIKE '%".$_POST['searchText']."%' OR realname LIKE '%".$_POST['searchText']."%')";

	$where.=")";	

	$NBMAX=$cfg_glpi["dropdown_max"];
	$LIMIT="LIMIT 0,$NBMAX";
	if ($_POST['searchText']==$cfg_glpi["ajax_wildcard"]) $LIMIT="";
	
			
	$query = "SELECT * FROM glpi_users WHERE ID >'1' $where ORDER BY realname,name $LIMIT";
	$result = $db->query($query);

	echo "<select name=\"".$_POST['myname']."\">";

	if ($_POST['searchText']!=$cfg_glpi["ajax_wildcard"]&&$db->numrows($result)==$NBMAX)
	echo "<option value=\"0\">--".$lang["common"][11]."--</option>";
	
		
	if ($all==0)
	echo "<option value=\"0\">[ Nobody ]</option>";
	else if($all==1) echo "<option value=\"0\">[ ".$lang["search"][7]." ]</option>";
	
	if (isset($_POST['value'])){
		$output=getUserName($_POST['value'],2);
		if (!empty($output["name"])&&$output["name"]!="&nbsp;")
		echo "<option selected value='".$_POST['value']."' title=\"".$output["name"]."\">".substr($output["name"],0,$cfg_glpi["dropdown_limit"])."</option>";
	}
		
	if ($db->numrows($result)) {
		while ($data = $db->fetch_array($result)) {
			
			if (!empty($data["realname"])) $display = $data["realname"];
			else $display = $data["name"];
			echo "<option value=\"".$data["ID"]."\" title=\"$display\">".substr($display,0,$cfg_glpi["dropdown_limit"])."</option>";
   		}
	}
	echo "</select>";

	if (isset($_POST['value'])&&$_POST["display_comments"]&&!empty($output["comments"])) {
		$rand=mt_rand();
		echo "<a href='".$output["link"]."'>";
		echo "<img src='".$HTMLRel."/pics/aide.png' onmouseout=\"setdisplay(getElementById('comments_$rand'),'none')\" onmouseover=\"setdisplay(getElementById('comments_$rand'),'block')\">";
		echo "</a>";
		echo "<span class='over_link' id='comments_$rand'>".nl2br($output["comments"])."</span>";
	}


?>