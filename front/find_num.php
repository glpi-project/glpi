<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

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


$NEEDED_ITEMS=array("user");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if($CFG_GLPI["permit_helpdesk"]){
	$id = new Identification();
	$id->initSession();
} else {
	exit();
}

// Send UTF8 Headers
header("Content-Type: text/html; charset=UTF-8");

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>GLPI</title>

<?php
// Appel CSS
echo "<link rel='stylesheet'  href='".$CFG_GLPI["root_doc"]."/css/styles.css' type='text/css' media='screen' >";
// Appel javascript
echo "<script type=\"text/javascript\" src='".$CFG_GLPI["root_doc"]."/script.js'></script>";

?>

</head>

<body>
<script language="javascript" type="text/javascript">
function fillidfield(Type,Id){
	window.opener.document.forms["helpdeskform"].elements["computer"].value = Id;
	window.opener.document.forms["helpdeskform"].elements["device_type"].value = Type;
	window.close();}
	</script>

	<?php 

	echo "<div align='center'>";
	echo "<p><strong>".$LANG["help"][22]."</strong></p>";
	echo " <form name=\"form1\" method=\"post\"  action=\"".$_SERVER['PHP_SELF']."\">"; 

	echo "<table cellspacing='1' width='100%'  class='tab_cadre'>";
	echo "<tr><th align='center'  width='100%' height='29'>".$LANG["help"][23]."</th>";
	echo "</tr><tr><td class='tab_bg_1' align='center' width='100%'>"; 
	echo "<input name='NomContact' type='text' id='NomContact' >";
	echo "<input type='hidden' name='send' value='1' />"; // bug IE ! La validation par enter ne fonctionne pas sans cette ligne  incroyable mais vrai !
	echo "<input type='submit' name='send' value=\"". $LANG["buttons"][0]."\">";
	echo "</td>  </tr> </table> </form></div>";


	if(isset($_POST["send"]))
{
	echo "<table width='100%' class='tab_cadre'>";
	echo " <tr class='tab_bg3'>";
	echo " <td align='center' width='30%'><b>".$LANG["reports"][19]."</b></td>";
	echo " <td align='center' width='20%'><b>".$LANG["help"][24]."</b></td>";
	echo " <td align='center' width='30%'><b>".$LANG["common"][1]."</b></td>";
	echo " <td align='center' width='5%'><b>".$LANG["common"][2]."</b></td>";
	echo " <td align='center' width='20%'><b>".$LANG["common"][19]."&nbsp;/&nbsp;".$LANG["common"][20]."</b></td>";
	echo " </tr>";


	$query = "select name,ID,contact, serial, otherserial from glpi_computers where is_template='0' AND deleted='0' AND (contact like '%".$_POST["NomContact"]."%' OR name like '%".$_POST["NomContact"]."%' OR serial like '%".$_POST["NomContact"]."%' OR otherserial like '%".$_POST["NomContact"]."%')";
	$result = $DB->query($query);
	while($ligne = $DB->fetch_array($result))
	{
		$Comp_num = $ligne['ID'];
		$Contact = $ligne['contact'];
		$Computer = $ligne['name'];
		$s1 = $ligne['serial'];
		$s2 = $ligne['otherserial'];
		echo " <tr class='tab_find' onClick=\"fillidfield(".COMPUTER_TYPE.",".$Comp_num.")\">";
		echo "<td width='25%' align='center'><b>&nbsp;$Contact</b></td>";
		echo "<td width='25%' align='center'><b>&nbsp;".$LANG["help"][25]."</b></td>";
		echo "<td width='25%' align='center'><b>&nbsp;$Computer&nbsp;</b></td>";
		echo "<td  width='25%' align='center'>";
		echo "<b>&nbsp;$Comp_num </b></td>";
		echo "<td width='25%' align='center'>";
		if ($s1!="") echo $s1;
		if ($s1!=""&&$s2!="") echo "&nbsp;/&nbsp;";
		if ($s2!="") echo $s2;
		echo "</td>";
		echo "</tr>";
	}

	$query = "select name,ID,contact, serial, otherserial from glpi_networking where is_template='0' AND deleted='0' AND (contact like '%".$_POST["NomContact"]."%' OR name like '%".$_POST["NomContact"]."%' OR serial like '%".$_POST["NomContact"]."%' OR otherserial like '%".$_POST["NomContact"]."%')";
	$result = $DB->query($query);
	while($ligne = $DB->fetch_array($result))
	{
		$Comp_num = $ligne['ID'];
		$Contact = $ligne['contact'];
		$Computer = $ligne['name'];
		$s1 = $ligne['serial'];
		$s2 = $ligne['otherserial'];
		echo " <tr class='tab_find' onClick=\"fillidfield(".NETWORKING_TYPE.",".$Comp_num.")\">";
		echo "<td width='25%' align='center'><b>&nbsp;$Contact </b></td>";
		echo "<td width='25%' align='center'><b>&nbsp;".$LANG["help"][26]."</b></td>";
		echo "<td width='25%' align='center'><b>&nbsp;$Computer </b></td>";
		echo "<td  width='25%' align='center'>";
		echo "<b>&nbsp;$Comp_num </b></td>";
		echo "<td width='25%' align='center'>";
		if ($s1!="") echo $s1;
		if ($s1!=""&&$s2!="") echo "&nbsp;/&nbsp;";
		if ($s2!="") echo $s2;
		echo "</td>";
		echo "</tr>";
	}

	$query = "select name,ID,contact, serial, otherserial from glpi_printers where is_template='0' AND deleted='0' AND (contact like '%".$_POST["NomContact"]."%' OR name like '%".$_POST["NomContact"]."%' OR serial like '%".$_POST["NomContact"]."%' OR otherserial like '%".$_POST["NomContact"]."%')";
	$result = $DB->query($query);
	while($ligne = $DB->fetch_array($result))
	{
		$Comp_num = $ligne['ID'];
		$Contact = $ligne['contact'];
		$Computer = $ligne['name'];
		$s1 = $ligne['serial'];
		$s2 = $ligne['otherserial'];
		echo " <tr class='tab_find' onClick=\"fillidfield(".PRINTER_TYPE.",".$Comp_num.")\">";
		echo "<td width='25%' align='center'><b>&nbsp;$Contact </b></td>";
		echo "<td width='25%' align='center'><b>&nbsp;".$LANG["help"][27]."</b></td>";
		echo "<td width='25%' align='center'><b>&nbsp;$Computer </b></td>";
		echo "<td  width='25%' align='center'>";
		echo "<b>&nbsp;$Comp_num </b></td>";
		echo "<td width='25%' align='center'>";
		if ($s1!="") echo $s1;
		if ($s1!=""&&$s2!="") echo "&nbsp;/&nbsp;";
		if ($s2!="") echo $s2;
		echo "</td>";
		echo "</tr>";
	}

	$query = "select name,ID,contact, serial, otherserial from glpi_monitors where is_template='0' AND deleted='0' AND (contact like '%".$_POST["NomContact"]."%' OR name like '%".$_POST["NomContact"]."%' OR serial like '%".$_POST["NomContact"]."%' OR otherserial like '%".$_POST["NomContact"]."%')";
	$result = $DB->query($query);
	while($ligne = $DB->fetch_array($result))
	{
		$Comp_num = $ligne['ID'];
		$Contact = $ligne['contact'];
		$Computer = $ligne['name'];
		$s1 = $ligne['serial'];
		$s2 = $ligne['otherserial'];
		echo " <tr class='tab_find' onClick=\"fillidfield(".MONITOR_TYPE.",".$Comp_num.")\">";
		echo "<td width='25%' align='center'><b>&nbsp;$Contact </b></td>";
		echo "<td width='25%' align='center'><b>&nbsp;".$LANG["help"][28]."</b></td>";
		echo "<td width='25%' align='center'><b>&nbsp;$Computer </b></td>";
		echo "<td  width='25%' align='center'>";
		echo "<b>&nbsp;$Comp_num </b></td>";
		echo "<td width='25%' align='center'>";
		if ($s1!="") echo $s1;
		if ($s1!=""&&$s2!="") echo "&nbsp;/&nbsp;";
		if ($s2!="") echo $s2;
		echo "</td>";
		echo "</tr>";
	}

	$query = "select name,ID,contact, serial, otherserial from glpi_peripherals where is_template='0' AND deleted='0' AND (contact like '%".$_POST["NomContact"]."%' OR name like '%".$_POST["NomContact"]."%' OR serial like '%".$_POST["NomContact"]."%' OR otherserial like '%".$_POST["NomContact"]."%')";
	$result = $DB->query($query);
	while($ligne = $DB->fetch_array($result))
	{
		$Comp_num = $ligne['ID'];
		$Contact = $ligne['contact'];
		$Computer = $ligne['name'];
		$s1 = $ligne['serial'];
		$s2 = $ligne['otherserial'];
		echo " <tr class='tab_find' onClick=\"fillidfield(".PERIPHERAL_TYPE.",".$Comp_num.")\">";
		echo "<td width='25%' align='center'><b>&nbsp;$Contact </b></td>";
		echo "<td width='25%' align='center'><b>&nbsp;".$LANG["help"][29]."</b></td>";
		echo "<td width='25%' align='center'><b>&nbsp;$Computer </b></td>";
		echo "<td  width='25%' align='center'>";
		echo "<b>&nbsp;$Comp_num </b></td>";
		echo "<td width='25%' align='center'>";
		if ($s1!="") echo $s1;
		if ($s1!=""&&$s2!="") echo "&nbsp;/&nbsp;";
		if ($s2!="") echo $s2;
		echo "</td>";
		echo "</tr>";
	}

	$query = "select name,ID from glpi_software where is_template='0' AND deleted='0' AND (name like '%".$_POST["NomContact"]."%' )";
	$result = $DB->query($query);
	while($ligne = $DB->fetch_array($result))
	{
		$Comp_num = $ligne['ID'];
		$Computer = $ligne['name'];
		echo " <tr class='tab_find' onClick=\"fillidfield(".SOFTWARE_TYPE.",".$Comp_num.")\">";
		echo "<td width='25%' align='center'>&nbsp;</td>";
		echo "<td width='25%' align='center'><b>&nbsp;".$LANG["help"][31]."</b></td>";
		echo "<td width='25%' align='center'><b>&nbsp;$Computer </b></td>";
		echo "<td  width='25%' align='center'>";
		echo "<b>&nbsp;$Comp_num </b></td>";
		echo "<td width='25%' align='center'>";
		echo "&nbsp;";
		echo "</td>";
		echo "</tr>";
	}

	echo "</table>";
}
?>
</body></html>
