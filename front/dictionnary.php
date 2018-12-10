<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

include ('../inc/includes.php');

Session::checkSeveralRightsOr(['rule_dictionnary_dropdown' => READ,
                                    'rule_dictionnary_software' => READ]);

Html::header(__('Administration'), $_SERVER['PHP_SELF'], "admin", "dictionnary", -1);

RuleCollection::titleBackup();
$dictionnaries = RuleCollection::getDictionnaries();

echo "<div class='center'><table class='tab_cadre'>";
echo "<tr><th colspan='".count($dictionnaries)."'>" . __('Dictionaries') . "</th></tr>";
echo "<tr class='tab_bg_1'>";
foreach ($dictionnaries as $dictionnary) {
   echo "<td class='top'><table class='tab_cadre'>";
   echo "<tr><th>" . $dictionnary['type'] . "</th></tr>";
   foreach ($dictionnary['entries'] as $entry) {
      echo "<tr class='tab_bg_1'><td class='center b'>";
      echo "<a href='".$entry['link']."'>" . $entry['label'] ."</a></td></tr>";
   }
   echo "</td></table>";
}
echo "</tr>";
echo "</table></div>";
Html::footer();
