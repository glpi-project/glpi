<?php
/*
 
 ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
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

 ----------------------------------------------------------------------
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
*/
?>




<div align="center">
  <p><font size="+1"><strong>Rechercher votre numéro de machine </strong></font></p>
  <form name="form1" method="post" action="Recup_computer_num.php">
    <table cellspacing=1 width="100%" border="0">
      <tr> 
        <td align=center bgcolor="#CCCCCC" width="100%" height="29">Saisissez votre nom ou les 
          premières lettres de votre nom </td>
        </tr><tr><td bgcolor="#CCCCCC" align=center width="100%"> <input name="NomContact" type="text" id="NomContact" value="<?php echo $NomContact ; ?>"> 
           <input type="submit" name="Submit" value="Rechercher">
 </td>
      </tr>
    </table>
	
  </form>
</div>
<?php

 



if(isset($Submit))
{
include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
echo "<table width='100%' border='0'>";
echo " <tr bgcolor=#D2F2D5>";
echo " <td align=center width='70%'><b>Nom du contact </b></td>";
echo " <td align=center width='30%'><b>N° machine </b></td>";
echo " </tr>";
echo "</table>";

$db = new DB;
$query = "select ID,contact from computers where contact like '%$NomContact%'";
$result = $db->query($query);
while($ligne = $db->fetch_array($result))

{
$Comp_num = $ligne['ID'];
$Contact = $ligne['contact'];
echo "<table width='100%' border='0'>";
echo " <tr bgcolor=#cccccc>";
echo " <td width='70%'><b> $Contact </b></td>";
echo " <td align=center width='30%'><b> $Comp_num </b></td>";
echo " </tr>";
echo "</table>";
}

}
?>

