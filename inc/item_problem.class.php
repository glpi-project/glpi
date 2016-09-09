<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Item_Problem Class
 *
 *  Relation between Problems and Items
**/
class Item_Problem extends CommonDBRelation{


   // From CommonDBRelation
   static public $itemtype_1          = 'Problem';
   static public $items_id_1          = 'problems_id';

   static public $itemtype_2          = 'itemtype';
   static public $items_id_2          = 'items_id';
   static public $checkItem_2_Rights  = self::HAVE_VIEW_RIGHT_ON_ITEM;



   /**
    * @since version 0.84
   **/
   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   /**
    * @see CommonDBTM::prepareInputForAdd()
   **/
   function prepareInputForAdd($input) {

      // Avoid duplicate entry
      $restrict = " `problems_id` = '".$input['problems_id']."'
                   AND `itemtype` = '".$input['itemtype']."'
                   AND `items_id` = '".$input['items_id']."'";
      if (countElementsInTable($this->getTable(),$restrict)>0) {
         return false;
      }
      return parent::prepareInputForAdd($input);
   }


   /**
    * @param $item   CommonDBTM object
   **/
   static function countForItem(CommonDBTM $item) {

      $restrict = "`glpi_items_problems`.`problems_id` = `glpi_problems`.`id`
                   AND `glpi_items_problems`.`items_id` = '".$item->getField('id')."'
                   AND `glpi_items_problems`.`itemtype` = '".$item->getType()."'".
                   getEntitiesRestrictRequest(" AND ", "glpi_problems", '', '', true);

      $nb = countElementsInTable(array('glpi_items_problems', 'glpi_problems'), $restrict);

      return $nb ;
   }


   /**
    * Print the HTML array for Items linked to a problem
    *
    * @param $problem Problem object
    *
    * @return Nothing (display)
   **/
   static function showForProblem(Problem $problem) {
      global $DB, $CFG_GLPI;

      $instID = $problem->fields['id'];

      if (!$problem->can($instID, READ)) {
         return false;
      }
      $canedit = $problem->canEdit($instID);
      $rand    = mt_rand();

      $query = "SELECT DISTINCT `itemtype`
                FROM `glpi_items_problems`
                WHERE `glpi_items_problems`.`problems_id` = '$instID'
                ORDER BY `itemtype`";

      $result = $DB->query($query);
      $number = $DB->numrows($result);


      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form name='problemitem_form$rand' id='problemitem_form$rand' method='post'
                action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='2'>".__('Add an item')."</th></tr>";

         echo "<tr class='tab_bg_1'><td>";
         $types = array();
         foreach ($problem->getAllTypesForHelpdesk() as $key => $val) {
            $types[] = $key;
         }
         Dropdown::showSelectItemFromItemtypes(array('itemtypes'
                                                      => $types,
                                                     'entity_restrict'
                                                      => ($problem->fields['is_recursive']
                                                          ?getSonsOf('glpi_entities',
                                                                     $problem->fields['entities_id'])
                                                          :$problem->fields['entities_id'])));
         echo "</td><td class='center' width='30%'>";
         echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "<input type='hidden' name='problems_id' value='$instID'>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($canedit && $number) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = array('container' => 'mass'.__CLASS__.$rand);
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
      for ($i=0 ; $i<$number ; $i++) {
         $itemtype = $DB->result($result, $i, "itemtype");
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }

         if ($item->canView()) {
            $itemtable = getTableForItemType($itemtype);
            $query = "SELECT `$itemtable`.*,
                             `glpi_items_problems`.`id` AS IDD,
                             `glpi_entities`.`id` AS entity
                      FROM `glpi_items_problems`,
                           `$itemtable`";

            if ($itemtype != 'Entity') {
               $query .= " LEFT JOIN `glpi_entities`
                                 ON (`$itemtable`.`entities_id`=`glpi_entities`.`id`) ";
            }

            $query .= " WHERE `$itemtable`.`id` = `glpi_items_problems`.`items_id`
                              AND `glpi_items_problems`.`itemtype` = '$itemtype'
                              AND `glpi_items_problems`.`problems_id` = '$instID'";

            if ($item->maybeTemplate()) {
               $query .= " AND `$itemtable`.`is_template` = '0'";
            }

            $query .= getEntitiesRestrictRequest(" AND", $itemtable, '', '',
                                                 $item->maybeRecursive())."
                      ORDER BY `glpi_entities`.`completename`, `$itemtable`.`name`";

            $result_linked = $DB->query($query);
            $nb            = $DB->numrows($result_linked);

            for ($prem=true ; $data=$DB->fetch_assoc($result_linked) ; $prem=false) {
               $name = $data["name"];
               if ($_SESSION["glpiis_ids_visible"]
                   || empty($data["name"])) {
                  $name = sprintf(__('%1$s (%2$s)'), $name, $data["id"]);
               }
               $link = $itemtype::getFormURLWithID($data['id']);
               $namelink = "<a href=\"".$link."\">".$name."</a>";

               echo "<tr class='tab_bg_1'>";
               if ($canedit) {
                  echo "<td width='10'>";
                  Html::showMassiveActionCheckBox(__CLASS__, $data["IDD"]);
                  echo "</td>";
               }
               if ($prem) {
                  $typename = $item->getTypeName($nb);
                  echo "<td class='center top' rowspan='$nb'>".
                         (($nb > 1) ? sprintf(__('%1$s: %2$s'), $typename, $nb) : $typename)."</td>";
               }
               echo "<td class='center'>";
               echo Dropdown::getDropdownName("glpi_entities", $data['entity'])."</td>";
               echo "<td class='center".
                        (isset($data['is_deleted']) && $data['is_deleted'] ? " tab_bg_2_2'" : "'");
               echo ">".$namelink."</td>";
               echo "<td class='center'>".(isset($data["serial"])? "".$data["serial"]."" :"-").
                    "</td>";
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


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate) {
         $nb = 0;
         switch ($item->getType()) {
            case 'Problem' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable('glpi_items_problems',
                                             "`problems_id` = '".$item->getID()."'");
               }
               return self::createTabEntry(_n('Item', 'Items', Session::getPluralNumber()), $nb);

            case 'User' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countDistinctElementsInTable('glpi_problems_users', 'problems_id',
                                             "`users_id` = '".$item->getID()."'");
               }
               return self::createTabEntry(Problem::getTypeName(Session::getPluralNumber()), $nb);

            case 'Group' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countDistinctElementsInTable('glpi_groups_problems', 'problems_id',
                                             "`groups_id` = '".$item->getID()."'");
               }
               return self::createTabEntry(Problem::getTypeName(Session::getPluralNumber()), $nb);

            case 'Supplier' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countDistinctElementsInTable('glpi_problems_suppliers','problems_id',
                                             "`suppliers_id` = '".$item->getID()."'");
               }
               return self::createTabEntry(Problem::getTypeName(Session::getPluralNumber()), $nb);

            default :
               if (Session::haveRight("problem", Problem::READALL)) {
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     // Direct one
                     $nb = countElementsInTable('glpi_items_problems',
                                                " `itemtype` = '".$item->getType()."'
                                                   AND `items_id` = '".$item->getID()."'");
                     // Linked items
                     $linkeditems = $item->getLinkedItems();

                     if (count($linkeditems)) {
                        foreach ($linkeditems as $type => $tab) {
                           foreach ($tab as $ID) {
                              $nb += countElementsInTable('glpi_items_problems',
                                                          " `itemtype` = '$type'
                                                            AND `items_id` = '$ID'");
                           }
                        }
                     }
                  }
                  return self::createTabEntry(Problem::getTypeName(Session::getPluralNumber()), $nb);
               }
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case 'Problem' :
            self::showForProblem($item);
            break;

         default :
            Problem::showListForItem($item);
      }
      return true;
   }

}
?>
