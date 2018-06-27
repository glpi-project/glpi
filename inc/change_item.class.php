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

/**
 * Change_Item Class
 *
 * Relation between Changes and Items
**/
class Change_Item extends CommonDBRelation{


   // From CommonDBRelation
   static public $itemtype_1          = 'Change';
   static public $items_id_1          = 'changes_id';

   static public $itemtype_2          = 'itemtype';
   static public $items_id_2          = 'items_id';
   static public $checkItem_2_Rights  = self::DONT_CHECK_ITEM_RIGHTS;



   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   function prepareInputForAdd($input) {

      // Well, if I remember my PHP: empty(0) == true ...
      if (empty($input['changes_id']) || ($input['changes_id'] == 0)) {
         return false;
      }

      // Avoid duplicate entry
      if (countElementsInTable($this->getTable(), ['changes_id' => $input['changes_id'],
                                                  'itemtype' => $input['itemtype'],
                                                  'items_id' => $input['items_id']])>0) {
         return false;
      }
      return parent::prepareInputForAdd($input);
   }


   static function countForItem(CommonDBTM $item) {

      $restrict = "`glpi_changes_items`.`changes_id` = `glpi_changes`.`id`
                   AND `glpi_changes_items`.`items_id` = '".$item->getField('id')."'
                   AND `glpi_changes_items`.`itemtype` = '".$item->getType()."'".
                   getEntitiesRestrictRequest(" AND ", "glpi_changes", '', '', true);

      $nb = countElementsInTable(['glpi_changes_items', 'glpi_changes'], $restrict);

      return $nb;
   }


   /**
    * Print the HTML array for Items linked to a change
    *
    * @param $change Change object
    *
    * @return Nothing (display)
   **/
   static function showForChange(Change $change) {
      global $DB, $CFG_GLPI;

      $instID = $change->fields['id'];

      if (!$change->can($instID, READ)) {
         return false;
      }
      $canedit = $change->canEdit($instID);
      $rand    = mt_rand();

      $query = "SELECT DISTINCT `itemtype`
                FROM `glpi_changes_items`
                WHERE `glpi_changes_items`.`changes_id` = '$instID'
                ORDER BY `itemtype`";

      $result = $DB->query($query);
      $number = $DB->numrows($result);

      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form name='changeitem_form$rand' id='changeitem_form$rand' method='post'
                action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='2'>".__('Add an item')."</th></tr>";

         echo "<tr class='tab_bg_1'><td>";
         $types = [];
         foreach ($change->getAllTypesForHelpdesk() as $key => $val) {
            $types[] = $key;
         }
         Dropdown::showSelectItemFromItemtypes(['itemtypes'
                                                      => $types,
                                                     'entity_restrict'
                                                      => ($change->fields['is_recursive']
                                                          ?getSonsOf('glpi_entities',
                                                                     $change->fields['entities_id'])
                                                          :$change->fields['entities_id'])]);
         echo "</td><td class='center' width='30%'>";
         echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "<input type='hidden' name='changes_id' value='$instID'>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($canedit && $number) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = ['container' => 'mass'.__CLASS__.$rand];
         Html::showMassiveActions($massiveactionparams);
      }

      echo "<table class='tab_cadre_fixehov'>";
      $header_begin  = "<tr>";
      $header_top    = '';
      $header_bottom = '';
      $header_end    = '';
      if ($canedit && $number) {
         $header_top    .= "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         $header_top    .= "</th>";
         $header_bottom .= "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         $header_bottom .= "</th>";
      }
      $header_end .= "<th>".__('Type')."</th>";
      $header_end .= "<th>".__('Entity')."</th>";
      $header_end .= "<th>".__('Name')."</th>";
      $header_end .= "<th>".__('Serial number')."</th>";
      $header_end .= "<th>".__('Inventory number')."</th></tr>";
      echo $header_begin.$header_top.$header_end;

      $totalnb = 0;
      for ($i=0; $i<$number; $i++) {
         $itemtype = $DB->result($result, $i, "itemtype");
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }
         if ($item->canView()) {
            $itemtable = getTableForItemType($itemtype);
            $query = "SELECT `$itemtable`.*,
                             `glpi_changes_items`.`id` AS IDD,
                             `glpi_entities`.`id` AS entity
                      FROM `glpi_changes_items`,
                           `$itemtable`";

            if ($itemtype != 'Entity') {
               $query .= " LEFT JOIN `glpi_entities`
                                 ON (`$itemtable`.`entities_id`=`glpi_entities`.`id`) ";
            }

            $query .= " WHERE `$itemtable`.`id` = `glpi_changes_items`.`items_id`
                              AND `glpi_changes_items`.`itemtype` = '$itemtype'
                              AND `glpi_changes_items`.`changes_id` = '$instID'";

            if ($item->maybeTemplate()) {
               $query .= " AND `$itemtable`.`is_template` = 0";
            }

            $query .= getEntitiesRestrictRequest(" AND", $itemtable, '', '',
                                                 $item->maybeRecursive())."
                      ORDER BY `glpi_entities`.`completename`, `$itemtable`.`name`";

            $result_linked = $DB->query($query);
            $nb            = $DB->numrows($result_linked);

            for ($prem=true; $data=$DB->fetch_assoc($result_linked); $prem=false) {
               $link     = $itemtype::getFormURLWithID($data['id']);
               $linkname = $data["name"];
               if ($_SESSION["glpiis_ids_visible"]
                   || empty($data["name"])) {
                  $linkname = sprintf(__('%1$s (%2$s)'), $linkname, $data["id"]);
               }
               $name = "<a href=\"".$link."\">".$linkname."</a>";

               echo "<tr class='tab_bg_1'>";
               if ($canedit) {
                  echo "<td width='10'>";
                  Html::showMassiveActionCheckBox(__CLASS__, $data["IDD"]);
                  echo "</td>";
               }
               if ($prem) {
                  $itemname = $item->getTypeName($nb);
                  echo "<td class='center top' rowspan='$nb'>".
                         ($nb>1 ? sprintf(__('%1$s: %2$s'), $itemname, $nb) : $itemname)."</td>";
               }
               echo "<td class='center'>";
               echo Dropdown::getDropdownName("glpi_entities", $data['entity'])."</td>";
               echo "<td class='center".
                      (isset($data['is_deleted']) && $data['is_deleted'] ? " tab_bg_2_2'" : "'");
               echo ">".$name."</td>";
               echo "<td class='center'>".
                      (isset($data["serial"])? "".$data["serial"]."" :"-")."</td>";
               echo "<td class='center'>".
                      (isset($data["otherserial"])? "".$data["otherserial"]."" :"-")."</td>";
               echo "</tr>";
            }
            $totalnb += $nb;
         }
      }

      if ($number) {
         echo $header_begin.$header_bottom.$header_end;
      }

      echo "</table>";
      if ($canedit && $number) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate) {
         $nb = 0;
         switch ($item->getType()) {
            case 'Change' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable('glpi_changes_items',
                                             ['changes_id' => $item->getID()]);
               }
               return self::createTabEntry(_n('Item', 'Items', Session::getPluralNumber()), $nb);

            case 'User' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countDistinctElementsInTable('glpi_changes_users', 'changes_id',
                                             "`users_id` = '".$item->getID()."'");
               }
               return self::createTabEntry(Change::getTypeName(Session::getPluralNumber()), $nb);

            case 'Group' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countDistinctElementsInTable('glpi_changes_groups', 'changes_id',
                                             "`groups_id` = '".$item->getID()."'");
               }
               return self::createTabEntry(Change::getTypeName(Session::getPluralNumber()), $nb);

            case 'Supplier' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countDistinctElementsInTable('glpi_changes_suppliers', 'changes_id',
                                             "`suppliers_id` = '".$item->getID()."'");
               }
               return self::createTabEntry(Change::getTypeName(Session::getPluralNumber()), $nb);

            default :
               if (Session::haveRight("change", Change::READALL)) {
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     // Direct one
                     $nb = countElementsInTable('glpi_changes_items',
                                                   ['itemtype' => $item->getType(),
                                                    'items_id' => $item->getID()]);
                     // Linked items
                     $linkeditems = $item->getLinkedItems();

                     if (count($linkeditems)) {
                        foreach ($linkeditems as $type => $tab) {
                           foreach ($tab as $ID) {
                              $nb += countElementsInTable('glpi_changes_items',
                                                          ['itemtype' => $type,
                                                           'items_id' => $ID]);
                           }
                        }
                     }
                  }
                  return self::createTabEntry(Change::getTypeName(Session::getPluralNumber()), $nb);
               }

         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      switch ($item->getType()) {
         case 'Change' :
            self::showForChange($item);
            break;

         default :
            Change::showListForItem($item);
      }
      return true;

   }

}
