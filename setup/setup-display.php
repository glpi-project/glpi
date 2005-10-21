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

// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");

if (isset($_POST["which"]))$which=$_POST["which"];
elseif (isset($_GET["which"]))$which=$_GET["which"];
else $which=COMPUTER_TYPE;

checkAuthentication("normal");
commonHeader($lang["title"][2],$_SERVER["PHP_SELF"]);
include ($phproot . "/glpi/includes_search.php");

$db=new DB();

if (isset($_POST["add"])) {

$query="SELECT MAX(rank) FROM glpi_display WHERE type='$which' ";
$result=$db->query($query);
$newrank=$db->result($result,0,0)+1;
$query="INSERT INTO glpi_display (type,num,rank) VALUES ('$which','".$_POST['to_add']."','$newrank')";
$db->query($query);

} else if (isset($_POST["delete"])) {

$query="DELETE FROM glpi_display WHERE ID='".$_POST['ID']."';";
$db->query($query);
} else if (isset($_POST["up"])) {
// Get current item
$query="SELECT rank FROM glpi_display WHERE ID='".$_POST['ID']."';";
$result=$db->query($query);
$rank1=$db->result($result,0,0);
// Get previous item
$query="SELECT ID,rank FROM glpi_display WHERE type='$which' AND rank<'$rank1' ORDER BY rank DESC;";
$result=$db->query($query);
$rank2=$db->result($result,0,"rank");
$ID2=$db->result($result,0,"ID");
// Update items
$query="UPDATE glpi_display SET rank='$rank2' WHERE ID ='".$_POST['ID']."'";
$db->query($query);
$query="UPDATE glpi_display SET rank='$rank1' WHERE ID ='$ID2'";
$db->query($query);

} else if (isset($_POST["down"])) {
// Get current item
$query="SELECT rank FROM glpi_display WHERE ID='".$_POST['ID']."';";
$result=$db->query($query);
$rank1=$db->result($result,0,0);
// Get next item
$query="SELECT ID,rank FROM glpi_display WHERE type='$which' AND rank>'$rank1' ORDER BY rank ASC;";
$result=$db->query($query);
$rank2=$db->result($result,0,"rank");
$ID2=$db->result($result,0,"ID");
// Update items
$query="UPDATE glpi_display SET rank='$rank2' WHERE ID ='".$_POST['ID']."'";
$db->query($query);
$query="UPDATE glpi_display SET rank='$rank1' WHERE ID ='$ID2'";
$db->query($query);
}
	
	$dp=array(
		COMPUTER_TYPE=> $lang["Menu"][0],
		NETWORKING_TYPE => $lang["Menu"][1],
		PRINTER_TYPE => $lang["Menu"][2],
		MONITOR_TYPE => $lang["Menu"][3],
		PERIPHERAL_TYPE => $lang["Menu"][16],
		SOFTWARE_TYPE => $lang["Menu"][4],
		CONTACT_TYPE => $lang["Menu"][22],
		ENTERPRISE_TYPE => $lang["Menu"][23],
		//INFOCOM_TYPE => $lang["Menu"][24],
		CONTRACT_TYPE => $lang["Menu"][25],
		CARTRIDGE_TYPE => $lang["Menu"][21],
		TYPEDOC_TYPE => $lang["document"][7],
		DOCUMENT_TYPE => $lang["Menu"][27],
		//KNOWBASE_TYPE => $lang["title"][5],
		USER_TYPE => $lang["Menu"][14],
		//TRACKING_TYPE => "????",
		CONSUMABLE_TYPE => $lang["Menu"][32],
		//CONSUMABLE_ITEM_TYPE => "??",
		//CARTRIDGE_ITEM_TYPE => "??",
		//LICENSE_TYPE => "??",
	);
	
	
//	asort($dp);
	
	echo "<div align='center'><form method='post' action=\"".$cfg_install["root"]."/setup/setup-display.php\">";
	echo "<table class='tab_cadre' cellpadding='5'><tr><th colspan='2'>";
	echo $lang["setup"][251].": </th></tr><tr class='tab_bg_1'><td><select name='which'>";

foreach ($dp as $key => $val){
$sel="";
if ($which==$key) $sel="selected";
echo "<option value='$key' $sel>".$val."</option>";
}
	echo "</select></td>";
	echo "<td><input type='submit' value=\"".$lang["buttons"][2]."\" class='submit' ></td></tr>";
	echo "</table></form></div>";

	echo "<div align='center'>";
	echo "<table class='tab_cadre' cellpadding='2' width='50%' ><tr><th colspan='4'>";
	echo $lang["setup"][252].": </th></tr><tr class='tab_bg_1'><td colspan='4' align='center'>";
	echo "<form method='post' action=\"".$cfg_install["root"]."/setup/setup-display.php\">";
	echo "<input type='hidden' name='which' value='$which'>";
	echo "<select name='to_add'>";
	foreach ($SEARCH_OPTION[$which] as $key => $val)
	if ($key!=1)
		echo "<option value='$key' $sel>".$val["name"]."</option>";
	
	echo "</select>";
	
	echo "&nbsp;&nbsp;&nbsp;<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit' >";
	echo "</form>";
	echo "</td></tr>";
	
	// Defined items
	$query="SELECT * from glpi_display WHERE type='$which' order by rank";
	$result=$db->query($query);
	
	// print first element 
	echo "<tr class='tab_bg_2'><td  align='center' width='50%'>";
	echo $SEARCH_OPTION[$which][1]["name"];
	echo "</td><td colspan='3'>&nbsp;</td></tr>";
	$i=0;
	$numrows=0;
	if (($numrows=$db->numrows($result))>0)
	while ($data=$db->fetch_array($result))
	if ($data["num"]!=1){
		echo "<tr class='tab_bg_2'><td align='center' width='50%' >";
		echo $SEARCH_OPTION[$which][$data["num"]]["name"];
		echo "</td>";
		if ($i!=0){
			echo "<td align='center' valign='middle'>";
			echo "<form method='post' action=\"".$cfg_install["root"]."/setup/setup-display.php\">";
			echo "<input type='hidden' name='ID' value='".$data["ID"]."'>";
			echo "<input type='hidden' name='which' value='$which'>";
			echo "<input type='image' name='up'  value=\"".$lang["buttons"][24]."\"  src=\"".$HTMLRel."pics/puce-up2.png\" alt=\"".$lang["buttons"][24]."\"  title=\"".$lang["buttons"][24]."\" >";	
			echo "</form>";
			echo "</td>";
		} else echo "<td>&nbsp;</td>";
		if ($i!=$numrows-1){
			echo "<td align='center' valign='middle'>";
			echo "<form method='post' action=\"".$cfg_install["root"]."/setup/setup-display.php\">";
			echo "<input type='hidden' name='ID' value='".$data["ID"]."'>";
			echo "<input type='hidden' name='which' value='$which'>";
			echo "<input type='image' name='down' value=\"".$lang["buttons"][25]."\" src=\"".$HTMLRel."pics/puce-down2.png\" alt=\"".$lang["buttons"][25]."\"  title=\"".$lang["buttons"][25]."\" >";	
			echo "</form>";
			echo "</td>";
		} else echo "<td>&nbsp;</td>";
		echo "<td align='center' valign='middle'>";
		echo "<form method='post' action=\"".$cfg_install["root"]."/setup/setup-display.php\">";
		echo "<input type='hidden' name='ID' value='".$data["ID"]."'>";
		echo "<input type='hidden' name='which' value='$which'>";
		echo "<input type='image' name='delete' value=\"".$lang["buttons"][6]."\"src=\"".$HTMLRel."pics/puce-delete2.png\" alt=\"".$lang["buttons"][6]."\"  title=\"".$lang["buttons"][6]."\" >";	
		echo "</form>";
		echo "</td>";
		echo "</tr>";
		$i++;
	}
		
	echo "</table></div>";

		
	commonFooter();


?>
