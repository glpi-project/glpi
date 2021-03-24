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

class Item_DeviceCamera_ImageFormat extends CommonDBRelation {

   static public $itemtype_1 = 'Item_DeviceCamera';
   static public $items_id_1 = 'item_devicecameras_id';

   static public $itemtype_2 = 'ImageFormat';
   static public $items_id_2 = 'imageformats_id';

   static function getTypeName($nb = 0) {
      return _nx('camera', 'Format', 'Formats', $nb);
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      $nb = 0;
      switch ($item->getType()) {
         default:
            if ($_SESSION['glpishow_count_on_tabs']) {
               $nb = countElementsInTable(
                  self::getTable(), [
                     'item_devicecameras_id' => $item->getID()
                  ]
               );
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      self::showItems($item, $withtemplate);
   }

   function getForbiddenStandardMassiveAction() {
      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'MassiveAction:update';
      $forbidden[] = 'CommonDBConnexity:affect';
      $forbidden[] = 'CommonDBConnexity:unaffect';

      return $forbidden;
   }

   /**
    * Print items
    * @param  DeviceCamera $camera the current camera instance
    * @return void
    */
   static function showItems(DeviceCamera $camera) {
      global $DB, $CFG_GLPI;

      $ID = $camera->getID();
      $rand = mt_rand();

      if (!$camera->getFromDB($ID)
          || !$camera->can($ID, READ)) {
         return false;
      }
      $canedit = $camera->canEdit($ID);

      $items = $DB->request([
         'FROM'   => Item_DeviceCamera_ImageFormat::getTable(),
         'WHERE'  => [
            'item_devicecameras_id' => $camera->getID()
         ]
      ]);
      $link = new self();

      echo "<div>";

      if (!count($items)) {
         echo "<table class='tab_cadre_fixe'><tr><th>".__('No item found')."</th></tr>";
         echo "</table>";
      } else {
         Session::initNavigateListItems(
            self::getType(),
            //TRANS : %1$s is the itemtype name,
            //        %2$s is the name of the item (used for headings of a list)
            sprintf(
               __('%1$s = %2$s'),
               $camera->getTypeName(1),
               $camera->getName()
            )
         );

         if ($canedit) {
            Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
            $massiveactionparams = [
               'num_displayed'   => min($_SESSION['glpilist_limit'], count($items)),
               'container'       => 'mass'.__CLASS__.$rand
            ];
            Html::showMassiveActions($massiveactionparams);
         }

         echo "<table class='tab_cadre_fixehov'>";
         $header = "<tr>";
         if ($canedit) {
            $header .= "<th width='10'>";
            $header .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header .= "</th>";
         }
         $header .= "<th>".ImageFormat::getTypeName(1)."</th>";
         $header .= "<th>".__('Is dynamic')."</th>";
         $header .= "</tr>";

         echo $header;
         while ($row = $items->next()) {
            $item = new ImageFormat();
            $item->getFromDB($row['imageformats_id']);
            echo "<tr lass='tab_bg_1'>";
            if ($canedit) {
               echo "<td>";
               Html::showMassiveActionCheckBox(__CLASS__, $row["id"]);
               echo "</td>";
            }
            echo "<td>" . $item->getLink() . "</td>";
            echo "<td>{$row['is_dynamic']}</td>";
            echo "</tr>";
         }
         echo $header;
         echo "</table>";

         if ($canedit && count($items)) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
         }
         if ($canedit) {
            Html::closeForm();
         }
      }

      echo "</div>";
   }
}
