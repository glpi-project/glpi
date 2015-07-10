<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

include ('../inc/includes.php');

Session::checkSeveralRightsOr(array('rule_dictionnary_dropdown' => READ,
                                    'rule_dictionnary_software' => READ));

Html::header(__('Administration'), $_SERVER['PHP_SELF'], "admin", "dictionnary", -1);

RuleCollection::titleBackup();

echo "<div class='center'><table class='tab_cadre'>";
echo "<tr><th colspan='4'>" . __('Dictionaries') . "</th></tr>";
echo "<tr class='tab_bg_1'><td class='top'><table class='tab_cadre'>";
echo "<tr><th>".__('Global dictionary')."</th></tr>";

if (Session::haveRight("rule_dictionnary_software", READ)) {
   echo "<tr class='tab_bg_1'><td class='center b'>";
   echo "<a href=\"ruledictionnarysoftware.php\">" . _n('Software','Software',2) ."</a></td></tr>";
}
if (Session::haveRight("rule_dictionnary_dropdown", READ)) {
   echo "<tr class='tab_bg_1'><td class='center b'>";
   echo "<a href=\"ruledictionnarymanufacturer.php\">" . _n('Manufacturer','Manufacturers',2) .
        "</a></td></tr>";
}
if (Session::haveRight("rule_dictionnary_printer", READ)) {
   echo "<tr class='tab_bg_1'><td class='center b'>";
   echo "<a href=\"ruledictionnaryprinter.php\">" . _n('Printer','Printers',2) ."</a></td></tr>";
}

echo "</table></td>";

echo "<td class='top'><table class='tab_cadre'>";
if (Session::haveRight("rule_dictionnary_dropdown", READ)) {
   echo "<tr><th>"._n('Model','Models',2)."</th></tr>";
   echo "<tr class='tab_bg_1'><td class='center b'>".
         "<a href='ruledictionnarycomputermodel.php'>" . _n('Computer model','Computer models',2) .
         "</a></td>";
   echo "</tr><tr class='tab_bg_1'><td class='center b'>".
         "<a href='ruledictionnarymonitormodel.php'>" . _n('Monitor model','Monitor models',2) .
         "</a></td>";
   echo "</tr><tr class='tab_bg_1'><td class='center b'>".
         "<a href='ruledictionnaryprintermodel.php'>" . _n('Printer model','Printer models',2) .
         "</a></td>";
   echo "</tr><tr class='tab_bg_1'><td class='center b'>".
         "<a href='ruledictionnaryperipheralmodel.php'>" . _n('Device model','Device models',2) .
         "</a></td>";
   echo "</tr><tr class='tab_bg_1'><td class='center b'>".
         "<a href='ruledictionnarynetworkequipmentmodel.php'>". _n('Network equipment model',
                                                                   'Network equipment models',2) .
         "</a></td>";
   echo "</tr><tr class='tab_bg_1'><td class='center b'>".
         "<a href='ruledictionnaryphonemodel.php'>" . _n('Phone model','Phone models',2) .
         "</a></td></tr>";
}
echo "</table></td>";

echo "<td class='top'><table class='tab_cadre'>";
if (Session::haveRight("rule_dictionnary_dropdown", READ)) {
   echo "<tr><th>"._n('Type','Types',2)."</th></tr>";
   echo "<tr class='tab_bg_1'><td class='center b'>".
         "<a href='ruledictionnarycomputertype.php'>" . _n('Computer type','Computer types',2) .
         "</a></td>";
   echo "</tr><tr class='tab_bg_1'><td class='center b'>".
         "<a href='ruledictionnarymonitortype.php'>" . _n('Monitor type','Monitor types',2) .
         "</a></td>";
   echo "</tr><tr class='tab_bg_1'><td class='center b'>".
         "<a href='ruledictionnaryprintertype.php'>" . _n('Printer type','Printer types',2) .
         "</a></td>";
   echo "</tr><tr class='tab_bg_1'><td class='center b'>".
         "<a href='ruledictionnaryperipheraltype.php'>" . _n('Device type','Device types',2) .
         "</a></td>";
   echo "</tr><tr class='tab_bg_1'><td class='center b'>".
         "<a href='ruledictionnarynetworkequipmenttype.php'>". _n('Network equipment type',
                                                                  'Network equipment types',2) .
         "</a></td>";
   echo "</tr><tr class='tab_bg_1'><td class='center b'>".
         "<a href='ruledictionnaryphonetype.php'>" . _n('Phone type','Phone types',2) .
         "</a></td></tr>";
}
echo "</table></td>";

echo "<td class='top'><table class='tab_cadre'>";
if (Session::haveRight("rule_dictionnary_dropdown", READ)) {
   echo "<tr><th>"._n('Operating system','Operating systems',2)."</th></tr>";
   echo "<tr class='tab_bg_1'><td class='center b'>".
         "<a href='ruledictionnaryoperatingsystem.php'>".
           _n('Operating system','Operating systems',2)."</a></td></tr>";
   echo "<tr class='tab_bg_1'><td class='center b'>".
         "<a href='ruledictionnaryoperatingsystemservicepack.php'>".
           _n('Service pack', 'Service packs',2)."</a></td></tr>";
   echo "<tr class='tab_bg_1'><td class='center b'>".
         "<a href='ruledictionnaryoperatingsystemversion.php'>" . _n('Version','Versions',2) .
         "</a></td></tr>";
}
echo "</table></td></tr>";

echo "</table></div>";
Html::footer();
?>