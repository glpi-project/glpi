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
// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkSeveralRightsOr(array('rule_dictionnary_dropdown' => 'r',
                           'rule_dictionnary_software' => 'r'));

commonHeader($LANG['common'][12],$_SERVER['PHP_SELF'],"admin","dictionnary",-1);

echo "<div class='center'><table class='tab_cadre'>";
echo "<tr><th colspan='4'>" . $LANG['rulesengine'][77] . "</th></tr>";
echo "<tr class='tab_bg_1'><td class='top'><table class='tab_cadre'>";
echo "<tr><th>".$LANG['rulesengine'][80]."</th></tr>";

if (haveRight("rule_dictionnary_software","r")) {
   echo "<tr class='tab_bg_1'><td class='center b'>";
   echo "<a href=\"ruledictionnarysoftware.php\">" . $LANG['rulesengine'][35] ."</a></td></tr>";
}
if (haveRight("rule_dictionnary_dropdown","r")) {
   echo "<tr class='tab_bg_1'><td class='center b'>";
   echo "<a href=\"ruledictionnarymanufacturer.php\">" . $LANG['rulesengine'][36] ."</a></td></tr>";
}
if (haveRight("rule_dictionnary_printer","r")) {
   echo "<tr class='tab_bg_1'><td class='center b'>";
   echo "<a href=\"ruledictionnaryprinter.php\">" . $LANG['rulesengine'][39] ."</a></td></tr>";
}

echo "</table></td>";

echo "<td class='top'><table class='tab_cadre'>";
if (haveRight("rule_dictionnary_dropdown","r")) {
   echo "<tr><th>".$LANG['rulesengine'][56]."</th></tr>";
   echo "<tr class='tab_bg_1'><td class='center b'>".
         "<a href='ruledictionnarycomputermodel.php'>" . $LANG['rulesengine'][50] . "</a></td>";
   echo "</tr><tr class='tab_bg_1'><td class='center b'>".
         "<a href='ruledictionnarymonitormodel.php'>" . $LANG['rulesengine'][51] . "</a></td>";
   echo "</tr><tr class='tab_bg_1'><td class='center b'>".
         "<a href='ruledictionnaryprintermodel.php'>" . $LANG['rulesengine'][54] . "</a></td>";
   echo "</tr><tr class='tab_bg_1'><td class='center b'>".
         "<a href='ruledictionnaryperipheralmodel.php'>" . $LANG['rulesengine'][53] . "</a></td>";
   echo "</tr><tr class='tab_bg_1'><td class='center b'>".
         "<a href='ruledictionnarynetworkequipmentmodel.php'>" . $LANG['rulesengine'][55] . "</a></td>";
   echo "</tr><tr class='tab_bg_1'><td class='center b'>".
         "<a href='ruledictionnaryphonemodel.php'>" . $LANG['rulesengine'][52] . "</a></td></tr>";
}
echo "</table></td>";

echo "<td class='top'><table class='tab_cadre'>";
if (haveRight("rule_dictionnary_dropdown","r")) {
   echo "<tr><th>".$LANG['rulesengine'][66]."</th></tr>";
   echo "<tr class='tab_bg_1'><td class='center b'>".
         "<a href='ruledictionnarycomputertype.php'>" . $LANG['rulesengine'][60] . "</a></td>";
   echo "</tr><tr class='tab_bg_1'><td class='center b'>".
         "<a href='ruledictionnarymonitortype.php'>" . $LANG['rulesengine'][61] . "</a></td>";
   echo "</tr><tr class='tab_bg_1'><td class='center b'>".
         "<a href='ruledictionnaryprintertype.php'>" . $LANG['rulesengine'][64] . "</a></td>";
   echo "</tr><tr class='tab_bg_1'><td class='center b'>".
         "<a href='ruledictionnaryperipheraltype.php'>" . $LANG['rulesengine'][63] . "</a></td>";
   echo "</tr><tr class='tab_bg_1'><td class='center b'>".
         "<a href='ruledictionnarynetworkequipmenttype.php'>" . $LANG['rulesengine'][65] . "</a></td>";
   echo "</tr><tr class='tab_bg_1'><td class='center b'>".
         "<a href='ruledictionnaryphonetype.php'>" . $LANG['rulesengine'][62] . "</a></td></tr>";
}
echo "</table></td>";

echo "<td class='top'><table class='tab_cadre'>";
if (haveRight("rule_dictionnary_dropdown","r")) {
   echo "<tr><th>".$LANG['computers'][9]."</th></tr>";
   echo "<tr class='tab_bg_1'><td class='center b'>".
         "<a href='ruledictionnaryoperatingsystem.php'>" . $LANG['rulesengine'][67] . "</a></td></tr>";
   echo "<tr class='tab_bg_1'><td class='center b'>".
         "<a href='ruledictionnaryoperatingsystemservicepack.php'>" . $LANG['rulesengine'][68] . "</a></td></tr>";
   echo "<tr class='tab_bg_1'><td class='center b'>".
         "<a href='ruledictionnaryoperatingsystemversion.php'>" . $LANG['rulesengine'][69] . "</a></td></tr>";
}
echo "</table></td></tr>";

echo "</table></div>";
commonFooter();

?>
