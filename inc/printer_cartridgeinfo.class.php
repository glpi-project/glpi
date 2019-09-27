<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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
**/
class Printer_CartridgeInfo extends CommonDBChild {

   static public $itemtype        = 'Printer';
   static public $items_id        = 'printers_id';
   public $dohistory              = true;

   static function getTypeName($nb = 0) {
      return _x('Cartridge inventoried information', 'Cartridge inventoried information', $nb);
   }

   public function getInfoForPrinter(Printer $printer) {
      global $DB;

      $iterator = $DB->request([
         'FROM'   => $this->getTable(),
         'WHERE'  => [
            self::$items_id => $printer->fields['id']
         ]
      ]);

      $info = [];
      while ($row = $iterator->next()) {
         $info[$row['id']] = $row;
      }

      return $info;
   }

   public function showForPrinter(Printer $printer) {
      $info = $this->getInfoForPrinter($printer);

      echo "<h3>".$this->getTypeName(Session::getPluralNumber())."</h3>";

      echo "<table class='tab_cadre_fixehov'>";
      echo "<thead><tr><th>".__('Property')."</th><th>".__('Value')."</th></tr></thead>";

      $asset = new Glpi\Inventory\Asset\Cartridge($printer);
      $tags = $asset->knownTags();

      foreach ($info as $row) {
         $property = $row['property'];
         $value = $row['value'];
         echo "<tr>";
         echo sprintf("<td>%s</td>", $tags[$property]['name'] ?? $property);

         if (strstr($value, 'pages')) {
            $pages = str_replace('pages', '', $value);
            $value = sprintf(
               _x('%1$s remaining page', '%1$s remaining pages', $pages),
               $pages
            );
         } else if ($value == 'OK') {
            $value = __('OK');
         }

         if (is_numeric($value)) {
            $bar_color = 'green';
            $progressbar_data = [
               'percent'      => $value,
               'percent_text' => $value,
               'color'        => $bar_color,
               'text'         => ''
            ];

            $out = "{$progressbar_data['text']}<div class='center' style='background-color: #ffffff; width: 100%;
                     border: 1px solid #9BA563; position: relative;' >";
            $out .= "<div style='position:absolute;'>&nbsp;{$progressbar_data['percent_text']}%</div>";
            $out .= "<div class='center' style='background-color: {$progressbar_data['color']};
                     width: {$progressbar_data['percent']}%; height: 12px' ></div>";
            $out .= "</div>";
         } else {
            $out = $value;
         }
         echo sprintf("<td>%s</td>", $out);

         echo "</tr>";
      }
      echo "</table>";
   }
}
