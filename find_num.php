<?php
/*
 
  ----------------------------------------------------------------------
GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2004 by the INDEPNET Development Team.
 
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
*/

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

	include ("_relpos.php");
	include ($phproot . "/glpi/includes.php");
	include ($phproot . "/glpi/includes_setup.php");


if(isset($_GET["name"]) && ($_GET["name"] == "Helpdesk") && ($cfg_features["permit_helpdesk"] == "1"))
{
	$id = new Identification("Helpdesk");
	$id->setCookies();
}
checkAuthentication("post-only");

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">

<?php
include ("_relpos.php");
// Appel CSS
 echo "<link rel='stylesheet'  href='".$HTMLRel."styles.css' type='text/css' media='screen' >";
// Appel javascript
echo "<script type=\"text/javascript\" src='".$HTMLRel."script.js'></script>";

?>

</head>

<body>
<script language="javascript">
function fillidfield(Type,Id){
window.opener.document.forms["helpdeskform"].elements["computer"].value = Id;
window.opener.document.forms["helpdeskform"].elements["device_type"].value = Type;
window.close();}
</script>

<div align="center">
  <p><strong><?echo $lang["help"][22];?></strong></p>
  <form name="form1" method="post" action="find_num.php">
    <table cellspacing="1" width="100%" class='tab_cadre'>
      <tr> 
        <th align='center'  width="100%" height="29"><?echo $lang["help"][23];?></th>
        </tr><tr><td class='tab_bg_1' align='center' width="100%"> 
		<input name="NomContact" type="text" id="NomContact" >
           <input type="submit" name="Submit" value="<?echo $lang["buttons"][0];?>">
 </td>
      </tr>
    </table>
	
  </form>
</div>
<?php

if(isset($_POST["Submit"]))
{
	echo "<table width='100%' class='tab_cadre'>";
	echo " <tr class='tab_bg3'>";
	echo " <td align='center' width='30%'><b>".$lang["reports"][19]."</b></td>";
	echo " <td align='center' width='20%'><b>".$lang["help"][24]."</b></td>";
	echo " <td align='center' width='30%'><b>".$lang["common"][1]."</b></td>";
	echo " <td align='center' width='5%'><b>".$lang["common"][2]."</b></td>";
	echo " <td align='center' width='20%'><b>".$lang["computers"][17]."&nbsp;/&nbsp;".$lang["computers"][18]."</b></td>";
	echo " </tr>";
	

	$db = new DB;
	$query = "select name,ID,contact, serial, otherserial from glpi_computers where contact like '%".$_POST["NomContact"]."%' OR name like '%".$_POST["NomContact"]."%' OR serial like '%".$_POST["NomContact"]."%' OR otherserial like '%".$_POST["NomContact"]."%'";
	$result = $db->query($query);
	while($ligne = $db->fetch_array($result))
	{
		$Comp_num = $ligne['ID'];
		$Contact = $ligne['contact'];
		$Computer = $ligne['name'];
		$s1 = $ligne['serial'];
		$s2 = $ligne['otherserial'];
		echo " <tr class='tab_bg_1' onClick=\"fillidfield(1,".$Comp_num.")\">";
		echo "<td width='25%' align='center'><b> $Contact </b></td>";
		echo "<td width='25%' align='center'><b> ".$lang["help"][25]."</b></td>";
		echo "<td width='25%' align='center'><b> $Computer </b></td>";
		echo "<td  width='25%' align='center'>";
		echo "<b> $Comp_num </b></td>";
		echo "<td width='25%' align='center'>";
		if ($s1!="") echo $s1;
		if ($s1!=""&&$s2!="") echo "&nbsp;/&nbsp;";
		if ($s2!="") echo $s2;
		echo "</td>";
		echo "</tr>";
	}

	$query = "select name,ID,contact, serial, otherserial from glpi_networking where contact like '%".$_POST["NomContact"]."%' OR name like '%".$_POST["NomContact"]."%' OR serial like '%".$_POST["NomContact"]."%' OR otherserial like '%".$_POST["NomContact"]."%'";
	$result = $db->query($query);
	while($ligne = $db->fetch_array($result))
	{
		$Comp_num = $ligne['ID'];
		$Contact = $ligne['contact'];
		$Computer = $ligne['name'];
		$s1 = $ligne['serial'];
		$s2 = $ligne['otherserial'];
		echo " <tr class='tab_bg_1' onClick=\"fillidfield(2,".$Comp_num.")\">";
		echo "<td width='25%' align='center'><b> $Contact </b></td>";
		echo "<td width='25%' align='center'><b> ".$lang["help"][26]."</b></td>";
		echo "<td width='25%' align='center'><b> $Computer </b></td>";
		echo "<td  width='25%' align='center'>";
		echo "<b> $Comp_num </b></td>";
		echo "<td width='25%' align='center'>";
		if ($s1!="") echo $s1;
		if ($s1!=""&&$s2!="") echo "&nbsp;/&nbsp;";
		if ($s2!="") echo $s2;
		echo "</td>";
		echo "</tr>";
	}

	$query = "select name,ID,contact, serial, otherserial from glpi_printers where contact like '%".$_POST["NomContact"]."%' OR name like '%".$_POST["NomContact"]."%' OR serial like '%".$_POST["NomContact"]."%' OR otherserial like '%".$_POST["NomContact"]."%'";
	$result = $db->query($query);
	while($ligne = $db->fetch_array($result))
	{
		$Comp_num = $ligne['ID'];
		$Contact = $ligne['contact'];
		$Computer = $ligne['name'];
		$s1 = $ligne['serial'];
		$s2 = $ligne['otherserial'];
		echo " <tr class='tab_bg_1' onClick=\"fillidfield(3,".$Comp_num.")\">";
		echo "<td width='25%' align='center'><b> $Contact </b></td>";
		echo "<td width='25%' align='center'><b> ".$lang["help"][27]."</b></td>";
		echo "<td width='25%' align='center'><b> $Computer </b></td>";
		echo "<td  width='25%' align='center'>";
		echo "<b> $Comp_num </b></td>";
		echo "<td width='25%' align='center'>";
		if ($s1!="") echo $s1;
		if ($s1!=""&&$s2!="") echo "&nbsp;/&nbsp;";
		if ($s2!="") echo $s2;
		echo "</td>";
		echo "</tr>";
	}

	$query = "select name,ID,contact, serial, otherserial from glpi_monitors where contact like '%".$_POST["NomContact"]."%' OR name like '%".$_POST["NomContact"]."%' OR serial like '%".$_POST["NomContact"]."%' OR otherserial like '%".$_POST["NomContact"]."%'";
	$result = $db->query($query);
	while($ligne = $db->fetch_array($result))
	{
		$Comp_num = $ligne['ID'];
		$Contact = $ligne['contact'];
		$Computer = $ligne['name'];
		$s1 = $ligne['serial'];
		$s2 = $ligne['otherserial'];
		echo " <tr class='tab_bg_1' onClick=\"fillidfield(4,".$Comp_num.")\">";
		echo "<td width='25%' align='center'><b> $Contact </b></td>";
		echo "<td width='25%' align='center'><b> ".$lang["help"][28]."</b></td>";
		echo "<td width='25%' align='center'><b> $Computer </b></td>";
		echo "<td  width='25%' align='center'>";
		echo "<b> $Comp_num </b></td>";
		echo "<td width='25%' align='center'>";
		if ($s1!="") echo $s1;
		if ($s1!=""&&$s2!="") echo "&nbsp;/&nbsp;";
		if ($s2!="") echo $s2;
		echo "</td>";
		echo "</tr>";
	}

	$query = "select name,ID,contact, serial, otherserial from glpi_peripherals where contact like '%".$_POST["NomContact"]."%' OR name like '%".$_POST["NomContact"]."%' OR serial like '%".$_POST["NomContact"]."%' OR otherserial like '%".$_POST["NomContact"]."%'";
	$result = $db->query($query);
	while($ligne = $db->fetch_array($result))
	{
		$Comp_num = $ligne['ID'];
		$Contact = $ligne['contact'];
		$Computer = $ligne['name'];
		$s1 = $ligne['serial'];
		$s2 = $ligne['otherserial'];
		echo " <tr class='tab_bg_1' onClick=\"fillidfield(5,".$Comp_num.")\">";
		echo "<td width='25%' align='center'><b> $Contact </b></td>";
		echo "<td width='25%' align='center'><b> ".$lang["help"][29]."</b></td>";
		echo "<td width='25%' align='center'><b> $Computer </b></td>";
		echo "<td  width='25%' align='center'>";
		echo "<b> $Comp_num </b></td>";
		echo "<td width='25%' align='center'>";
		if ($s1!="") echo $s1;
		if ($s1!=""&&$s2!="") echo "&nbsp;/&nbsp;";
		if ($s2!="") echo $s2;
		echo "</td>";
		echo "</tr>";
	}

	$query = "select name,ID from glpi_software where name like '%".$_POST["NomContact"]."%' ";
	$result = $db->query($query);
	while($ligne = $db->fetch_array($result))
	{
		$Comp_num = $ligne['ID'];
		$Computer = $ligne['name'];
		echo " <tr class='tab_bg_1' onClick=\"fillidfield(6,".$Comp_num.")\">";
		echo "<td width='25%' align='center'>&nbsp;</td>";
		echo "<td width='25%' align='center'><b>".$lang["help"][31]."</b></td>";
		echo "<td width='25%' align='center'><b> $Computer </b></td>";
		echo "<td  width='25%' align='center'>";
		echo "<b> $Comp_num </b></td>";
		echo "<td width='25%' align='center'>";
		echo "&nbsp;/&nbsp;";
		echo "</td>";
		echo "</tr>";
	}

     echo "</table>";
}
?>
</body></html>
