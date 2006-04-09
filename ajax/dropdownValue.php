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

	// Make a select box with preselected values
	if (!isset($_POST["limit"])) $_POST["limit"]=$cfg_glpi["dropdown_limit"];
	
	if($_POST['table'] == "glpi_dropdown_netpoint") {

		$where="";
		if (strlen($_POST['searchText'])>0&&$_POST['searchText']!=$cfg_glpi["ajax_wildcard"])
			$where=" WHERE (t1.name LIKE '%".$_POST['searchText']."%' OR t2.completename LIKE '%".$_POST['searchText']."%')";

		$NBMAX=$cfg_glpi["dropdown_max"];
		$LIMIT="LIMIT 0,$NBMAX";
		if ($_POST['searchText']==$cfg_glpi["ajax_wildcard"]) $LIMIT="";
			
			
		$query = "select t1.ID as ID, t1.name as netpname, t2.completename as loc from glpi_dropdown_netpoint as t1";
		$query .= " left join glpi_dropdown_locations as t2 on (t1.location = t2.ID)";
		$query.=$where;
		$query .= " order by t1.name,t2.name $LIMIT"; 
		
		$result = $db->query($query);

		echo "<select name=\"".$_POST['myname']."\">";
		
		if ($_POST['searchText']!=$cfg_glpi["ajax_wildcard"]&&$db->numrows($result)==$NBMAX)
		echo "<option value=\"0\">--".$lang["common"][11]."--</option>";
		else echo "<option value=\"0\">-----</option>";
		
		$output=getDropdownName($_POST['table'],$_POST['value']);
		if (!empty($output)&&$output!="&nbsp;")
		echo "<option selected value='".$_POST['value']."'>".$output."</option>";
		
				
		if ($db->numrows($result)) {
			while ($data =$db->fetch_array($result)) {
				$output = $data['netpname'];
				$loc=$data['loc'];
				$ID = $data['ID'];
				echo "<option value=\"$ID\" title=\"$output\"";
				if ($ID==$_POST['value']) echo " selected ";
				echo ">".$output." ($loc)</option>";
			}
		}
		echo "</select>";
	} else {
		$first=true;
		$where="WHERE ";
		if (in_array($_POST['table'],$cfg_glpi["deleted_tables"])){
			if (!$first) $where.=" AND ";
			else $first=false;
			$where.=" deleted='N' ";
		}
		if (in_array($_POST['table'],$cfg_glpi["template_tables"])){
			if (!$first) $where.=" AND ";
			else $first=false;
			$where.=" is_template='0' ";
		}
		
		if (!$first) $where.=" AND ";
		else $first=false;
			$where .=" (ID <> '".$_POST['value']."' ";

		$NBMAX=$cfg_glpi["dropdown_max"];
		$LIMIT="LIMIT 0,$NBMAX";
		if ($_POST['searchText']==$cfg_glpi["ajax_wildcard"]) $LIMIT="";


		if (in_array($_POST['table'],$cfg_glpi["dropdowntree_tables"])){
			if ($_POST['searchText']!=$cfg_glpi["ajax_wildcard"])
				$where.=" AND completename LIKE '%".$_POST['searchText']."%' ";
			$where.=")";

			$query = "SELECT ID, name, completename, level FROM ".$_POST['table']." $where ORDER BY completename $LIMIT";
			$result = $db->query($query);
			
			echo "<select name=\"".$_POST['myname']."\" size='1'>";

			if ($_POST['searchText']!=$cfg_glpi["ajax_wildcard"]&&$db->numrows($result)==$NBMAX)
				echo "<option class='tree' value=\"0\">--".$lang["common"][11]."--</option>";

			if ($table=="glpi_dropdown_kbcategories")
				echo "<option class='tree' value=\"0\">--".$lang["knowbase"][12]."--</option>";
			else echo "<option class='tree' value=\"0\">-----</option>";

			$output=getDropdownName($_POST['table'],$_POST['value']);
			if (!empty($output)&&$output!="&nbsp;")
			echo "<option class='tree' selected value='".$_POST['value']."'>".$output."</option>";
	
			if ($db->numrows($result)) {
				while ($data =$db->fetch_array($result)) {
					
					$ID = $data['ID'];
					$level = $data['level'];
					if (empty($data['name'])) $output="($ID)";
					$class="class='tree'";
					if ($level==1) $class="class='treeroot'";
					$style=" $class style=\"color: #202020; padding-left:5px; margin-left: ".(16*($level-1))."px;\" ";
					echo "<option value=\"$ID\" $style title=\"".$data['completename']."\">".substr($data['name'],0,$_POST["limit"])."</option>";
				}
			}
			echo "</select>";

		} else {
			if ($_POST['searchText']!=$cfg_glpi["ajax_wildcard"])
				$where.=" AND name LIKE '%".$_POST['searchText']."%' ";
			$where.=")";

			$query = "SELECT ID, name FROM ".$_POST['table']." $where ORDER BY name $LIMIT";
	
			$result = $db->query($query);

			echo "<select name=\"".$_POST['myname']."\" size='1'>";

			if ($_POST['searchText']!=$cfg_glpi["ajax_wildcard"]&&$db->numrows($result)==$NBMAX)
				echo "<option value=\"0\">--".$lang["common"][11]."--</option>";

			echo "<option value=\"0\">-----</option>";

			$output=getDropdownName($_POST['table'],$_POST['value']);
			if (!empty($output)&&$output!="&nbsp;")
			echo "<option selected value='".$_POST['value']."'>".$output."</option>";
	
			if ($db->numrows($result)) {
				while ($data =$db->fetch_array($result)) {
					$output = $data['name'];
					$ID = $data['ID'];
					
					if (empty($output)) $output="($ID)";
					echo "<option value=\"$ID\" title=\"$output\">".substr($output,0,$_POST["limit"])."</option>";
				}
			}
			echo "</select>";
		}
	}


?>