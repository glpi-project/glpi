<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

include ('../inc/includes.php');

Session::checkRight("reports", "r");

Html::header(Report::getTypeName(2), $_SERVER['PHP_SELF'], "utils", "report");

Report::title();

# Titre

echo "<form name='form' method='post' action='report.contract.list.php'>";

echo "<table class='tab_cadre' >";
echo "<tr><th colspan='2'><span class='big'>".__('Hardware under contract')." </span></th></tr>";

# 3. Selection d'affichage pour generer la liste
echo "<tr class='tab_bg_1'>";
echo "<td class='center' width='200' >";

echo "<p class='b'>".__('Item type')."</p> ";
echo "<p><select name='item_type[]' size='8' multiple>";
echo "<option value='0' selected>".__('All')."</option>";
echo "<option value='Computer'>"._n('Computer', 'Computers', 2)."</option>";
echo "<option value='Printer'>"._n('Printer', 'Printers', 2)."</option>";
echo "<option value='NetworkEquipment'>"._n('Network', 'Networks', 2)."</option>";
echo "<option value='Monitor'>"._n('Monitor', 'Monitors', 2)."</option>";
echo "<option value='Peripheral'>"._n('Device', 'Devices', 2)."</option>";
echo "<option value='Software'>"._n('Software', 'Software', 2)."</option>";
echo "<option value='Phone'>"._n('Phone', 'Phones', 2)."</option>";
echo "</select></p> </td> ";

echo "<td class='center' width='200'>";
echo "<p class='b'>".__('Date')."</p> ";
echo "<p><select name='annee[]' size='8' multiple>";
echo "<option value='toutes' selected>".__('All')."</option>";

$y = date("Y");

for ($i=$y-10 ; $i<=$y ; $i++) {
   echo " <option value='$i'>$i</option>";
}
echo "</select></p></td></tr>";

echo "<tr><td class='tab_bg_1 center' colspan='2'>";
echo "<p><input type='submit' value=\"".__s('Display report')."\" class='submit'></p></td></tr>";

echo "</table>";
Html::closeForm();

Html::footer();
?>