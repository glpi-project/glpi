<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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


define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if ($CFG_GLPI["use_anonymous_helpdesk"]) {
   $auth = new Auth();
   $auth->initSession();
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
echo "<link rel='stylesheet' href='".$CFG_GLPI["root_doc"]."/css/styles.css' type='text/css' ".
      "media='screen' >";
// Appel javascript
echo "<script type='text/javascript' src='".$CFG_GLPI["root_doc"]."/script.js'></script>";

?>

</head>

<body>
<script language="javascript" type="text/javascript">
function fillidfield(Type,Id) {
   window.opener.document.forms["helpdeskform"].elements["items_id"].value = Id;
   window.opener.document.forms["helpdeskform"].elements["itemtype"].value = Type;
   window.close();
}
</script>

<?php

echo "<div class='center'>";
echo "<p class='b'>".$LANG['help'][22]."</p>";
echo " <form name='form1' method='post'  action='".$_SERVER['PHP_SELF']."'>";

echo "<table class='tab_cadre_fixe'>";
echo "<tr><th height='29'>".$LANG['help'][23]."</th></tr>";
echo "<tr><td class='tab_bg_1 center'>";
echo "<input name='NomContact' type='text' id='NomContact' >";
echo "<input type='hidden' name='send' value='1'>"; // bug IE ! La validation par enter ne fonctionne pas sans cette ligne  incroyable mais vrai !
echo "<input type='submit' name='send' value='". $LANG['buttons'][0]."'>";
echo "</td></tr></table></form></div>";

if (isset($_POST["send"])) {
   echo "<table class='tab_cadre_fixe'>";
   echo " <tr class='tab_bg3'>";
   echo " <td class='center b' width='30%'>".$LANG['reports'][19]."</td>";
   echo " <td class='center b' width='20%'>".$LANG['help'][24]."</td>";
   echo " <td class='center b' width='30%'>".$LANG['common'][1]."</td>";
   echo " <td class='center b' width='5%'>".$LANG['common'][2]."</td>";
   echo " <td class='center b' width='20%'>".$LANG['common'][19]."&nbsp;/&nbsp;".
                                             $LANG['common'][20]."</td>";
   echo " </tr>";

   $types = array('Computer'         => $LANG['help'][25],
                  'NetworkEquipment' => $LANG['help'][26],
                  'Printer'          => $LANG['help'][27],
                  'Monitor'          => $LANG['help'][28],
                  'Peripheral'       => $LANG['help'][29]);
   foreach ($types as $type => $label) {
      $query = "SELECT `name`, `id`, `contact`, `serial`, `otherserial`
                FROM `".getTableForItemType($type)."`
                WHERE `is_template` = '0'
                      AND `is_deleted` = '0'
                      AND (`contact` LIKE '%".$_POST["NomContact"]."%'
                           OR `name` LIKE '%".$_POST["NomContact"]."%'
                           OR `serial` LIKE '%".$_POST["NomContact"]."%'
                           OR `otherserial` LIKE '%".$_POST["NomContact"]."%')
                ORDER BY `name`";
      $result = $DB->query($query);

      while ($ligne = $DB->fetch_array($result)) {
         $Comp_num = $ligne['id'];
         $Contact = $ligne['contact'];
         $Computer = $ligne['name'];
         $s1 = $ligne['serial'];
         $s2 = $ligne['otherserial'];
         echo " <tr class='tab_find' onClick=\"fillidfield(".$type.",".$Comp_num.")\">";
         echo "<td class='center'>&nbsp;$Contact&nbsp;</td>";
         echo "<td class='center'>&nbsp;$label&nbsp;</td>";
         echo "<td class='center b'>&nbsp;$Computer&nbsp;</td>";
         echo "<td class='center'>&nbsp;$Comp_num&nbsp;</td>";
         echo "<td class='center'>";
         if ($s1 != "") {
            echo $s1;
         }
         if ($s1!="" && $s2!="") {
            echo "&nbsp;/&nbsp;";
         }
         if ($s2 != "") {
            echo $s2;
         }
         echo "</td></tr>";
      }
   }

   $query = "SELECT `name`, `id`
             FROM `glpi_softwares`
             WHERE `is_template` = '0'
                   AND `is_deleted` = '0'
                   AND (`name` LIKE '%".$_POST["NomContact"]."%' )
             ORDER BY `name`";
   $result = $DB->query($query);

   while ($ligne = $DB->fetch_array($result)) {
      $Comp_num = $ligne['id'];
      $Computer = $ligne['name'];
      echo " <tr class='tab_find' onClick=\"fillidfield('Software',".$Comp_num.")\">";
      echo "<td class='center'>&nbsp;</td>";
      echo "<td class='center'>&nbsp;".$LANG['help'][31]."&nbsp;</td>";
      echo "<td class='center b'>&nbsp;$Computer&nbsp;</td>";
      echo "<td class='center'>&nbsp;$Comp_num&nbsp;</td>";
      echo "<td class='center'>&nbsp;</td></tr>";
   }

   echo "</table>";
}
echo '</body></html>';

?>
