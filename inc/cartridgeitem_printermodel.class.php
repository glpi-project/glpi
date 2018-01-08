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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

// Relation between CartridgeItem and PrinterModel
// since version 0.84
class CartridgeItem_PrinterModel extends CommonDBRelation {

   // From CommonDBRelation
   static public $itemtype_1          = 'CartridgeItem';
   static public $items_id_1          = 'cartridgeitems_id';

   static public $itemtype_2          = 'PrinterModel';
   static public $items_id_2          = 'printermodels_id';
   static public $checkItem_2_Rights  = self::DONT_CHECK_ITEM_RIGHTS;



   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      switch ($item->getType()) {
         case 'CartridgeItem' :
            self::showForCartridgeItem($item);
            break;

      }
      return true;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate && Printer::canView()) {
         $nb = 0;
         switch ($item->getType()) {
            case 'CartridgeItem' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = self::countForCartridgeItem($item);
               }
               return self::createTabEntry(PrinterModel::getTypeName(Session::getPluralNumber()), $nb);
         }
      }
      return '';
   }


   /**
    * @param $item   CartridgeItem object
   **/
   static function countForCartridgeItem(CartridgeItem $item) {

      return countElementsInTable(['glpi_printermodels', static::getTable()],
                                  [static::getTable().'.cartridgeitems_id' => $item->getField('id'),
                                    'FKEY' => [static::getTable() => 'printermodels_id', 'glpi_printermodels' => 'id']]);
   }


   /**
    * Show the printer types that are compatible with a cartridge type
    *
    * @param $item   CartridgeItem object
    *
    * @return nothing (display)
   **/
   static function showForCartridgeItem(CartridgeItem $item) {
      global $DB, $CFG_GLPI;

      $instID = $item->getField('id');
      if (!$item->can($instID, READ)) {
         return false;
      }
      $canedit = $item->canEdit($instID);
      $rand    = mt_rand();

      $query = "SELECT `".static::getTable()."`.`id`,
                       `glpi_printermodels`.`name` AS `type`,
                       `glpi_printermodels`.`id` AS `pmid`
                FROM `".static::getTable()."`,
                     `glpi_printermodels`
                WHERE `".static::getTable()."`.`printermodels_id` = `glpi_printermodels`.`id`
                      AND `".static::getTable()."`.`cartridgeitems_id` = '$instID'
                ORDER BY `glpi_printermodels`.`name`";

      $result = $DB->query($query);
      $i      = 0;

      $used  = [];
      $datas = [];
      if ($number = $DB->numrows($result)) {
         while ($data = $DB->fetch_assoc($result)) {
            $used[$data["pmid"]] = $data["pmid"];
            $datas[$data["id"]]  = $data;
         }
      }

      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form name='printermodel_form$rand' id='printermodel_form$rand' method='post'";
         echo " action='".static::getFormURL()."'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'>";
         echo "<th colspan='6'>".__('Add a compatible printer model')."</th></tr>";

         echo "<tr><td class='tab_bg_2 center'>";
         echo "<input type='hidden' name='cartridgeitems_id' value='$instID'>";
         PrinterModel::dropdown(['used' => $used]);
         echo "</td><td class='tab_bg_2 center'>";
         echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      if ($number) {
         echo "<div class='spaced'>";
         if ($canedit) {
            $rand     = mt_rand();
            Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
            $massiveactionparams = ['num_displayed' => min($_SESSION['glpilist_limit'], count($used)),
                              'container'     => 'mass'.__CLASS__.$rand];
            Html::showMassiveActions($massiveactionparams);
         }

         echo "<table class='tab_cadre_fixehov'>";
         $header_begin  = "<tr>";
         $header_top    = '';
         $header_bottom = '';
         $header_end    = '';
         if ($canedit) {
            $header_begin  .= "<th width='10'>";
            $header_top    .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header_bottom .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header_end    .= "</th>";
         }
         $header_end .= "<th>".__('Model')."</th></tr>";
         echo $header_begin.$header_top.$header_end;

         foreach ($datas as $data) {
            echo "<tr class='tab_bg_1'>";
            if ($canedit) {
               echo "<td width='10'>";
               Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
               echo "</td>";
            }
            $opt = [
               'is_deleted' => 0,
               'criteria'   => [
                  [
                     'field'      => 40, // printer model
                     'searchtype' => 'equals',
                     'value'      => $data["pmid"],
                  ]
               ]
            ];
            $url = Printer::getSearchURL()."?".Toolbox::append_params($opt, '&amp;');
            echo "<td class='center'><a href='".$url."'>".$data["type"]."</a></td>";
            echo "</tr>";
         }
         echo $header_begin.$header_bottom.$header_end;
         echo "</table>";
         if ($canedit) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
         }
         echo "</div>";
      } else {
         echo "<p class='center b'>".__('No item found')."</p>";
      }
   }

}
