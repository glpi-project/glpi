<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

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

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

// Relation between Problems and Items
class Item_Problem extends CommonDBRelation{


   // From CommonDBRelation
   public $itemtype_1 = 'Problem';
   public $items_id_1 = 'problems_id';

   public $itemtype_2 = 'itemtype';
   public $items_id_2 = 'items_id';


   function prepareInputForAdd($input) {

      if (empty($input['itemtype'])
          || empty($input['items_id'])
          || $input['items_id']==0
          || empty($input['problems_id'])
          || $input['problems_id']==0) {
         return false;
      }

      // Avoid duplicate entry
      $restrict = "`problems_id` = '".$input['problems_id']."'
                   AND `itemtype` = '".$input['itemtype']."'
                   AND `items_id` = '".$input['items_id']."'";
      if (countElementsInTable($this->getTable(),$restrict)>0) {
         return false;
      }
      return $input;
   }


   static function countForItem(CommonDBTM $item) {

      $restrict = "`glpi_items_problems`.`problems_id` = `glpi_problems`.`id`
                   AND `glpi_documents_items`.`items_id` = '".$item->getField('id')."'
                   AND `glpi_documents_items`.`itemtype` = '".$item->getType()."'".
                   getEntitiesRestrictRequest(" AND ", "glpi_problems", '', '', true);

      $nb = countElementsInTable(array('glpi_items_problems', 'glpi_problems'), $restrict);

      return $nb ;
   }


   /**
   * Print the HTML array for Items linked to a problem
   *
   * @param $problem problem object
   * @return Nothing (display)
   *
   **/
   static function showForProblem(Problem $problem) {
      global $DB, $CFG_GLPI, $LANG;

      $instID = $problem->fields['id'];

      if (!$problem->can($instID,'r')) {
         return false;
      }
      $canedit = $problem->can($instID,'w');
      $rand    = mt_rand();

      $query = "SELECT DISTINCT `itemtype`
                FROM `glpi_items_problems`
                WHERE `glpi_items_problems`.`problems_id` = '$instID'
                ORDER BY `itemtype`";

      $result = $DB->query($query);
      $number = $DB->numrows($result);

      echo "<div class='center'><table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='5'>";
      if ($DB->numrows($result)==0) {
         echo $LANG['document'][13];
      } else {
         echo $LANG['document'][19];
      }
      echo "</th></tr>";
      if ($canedit) {
         echo "</table></div>";

         echo "<form method='post' name='itemproblem_form$rand' id='itemproblem_form$rand' action=\"".
                $CFG_GLPI["root_doc"]."/front/item_problem.form.php\">";
         echo "<div class='spaced'>";
         echo "<table class='tab_cadre_fixe'>";
         // massive action checkbox
         echo "<tr><th>&nbsp;</th>";
      } else {
         echo "<tr>";
      }
      echo "<th>".$LANG['common'][17]."</th>";
      echo "<th>".$LANG['entity'][0]."</th>";
      echo "<th>".$LANG['common'][16]."</th>";
      echo "<th>".$LANG['common'][19]."</th>";
      echo "<th>".$LANG['common'][20]."</th></tr>";

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
               $ID = "";
               if ($_SESSION["glpiis_ids_visible"] || empty($data["name"])) {
                  $ID = " (".$data["id"].")";
               }
               $link = Toolbox::getItemTypeFormURL($itemtype);
               $name = "<a href=\"".$link."?id=".$data["id"]."\">".$data["name"]."$ID</a>";

               echo "<tr class='tab_bg_1'>";
               if ($canedit) {
                  $sel = "";
                  if (isset($_GET["select"]) && $_GET["select"]=="all") {
                     $sel = "checked";
                  }
                  echo "<td width='10'>";
                  echo "<input type='checkbox' name='item[".$data["IDD"]."]' value='1' $sel></td>";
               }
               if ($prem) {
                  echo "<td class='center top' rowspan='$nb'>".$item->getTypeName().
                        ($nb>1?"&nbsp;:&nbsp;$nb</td>":"</td>");
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
      echo "<tr class='tab_bg_2'>";
      echo "<td class='center' colspan='2'>".($totalnb>0? $LANG['common'][33].
             "&nbsp;=&nbsp;$totalnb</td>" : "&nbsp;</td>");
      echo "<td colspan='4'>&nbsp;</td></tr> ";

      if ($canedit) {
         echo "<tr class='tab_bg_1'><td colspan='4' class='right'>";
         $types = array();
         foreach ($problem->getAllTypesForHelpdesk() as $key => $val) {
            $types[] = $key;
         }
         Dropdown::showAllItems("items_id", 0, 0,
                                ($problem->fields['is_recursive']?-1:$problem->fields['entities_id']),
                                $types);
         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\"".$LANG['buttons'][8]."\" class='submit'>";
         echo "</td><td>&nbsp;</td></tr>";
         echo "</table>";

         Html::openArrowMassives("itemproblem_form$rand", true);
         echo "<input type='hidden' name='problems_id' value='$instID'>";
         Html::closeArrowMassives(array('delete' => $LANG['buttons'][6]));

      } else {
         echo "</table>";
      }
      echo "</div>";
      Html::closeForm();
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG;

      if (!$withtemplate) {
         switch ($item->getType()) {
            case 'Problem' :
               return $LANG['common'][96];

            default :
               if (Session::haveRight("show_all_problem","1")) {
                  $nb = 0;
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     // Direct one
                     $nb = countElementsInTable('glpi_items_problems',
                                                " `itemtype` = '".$item->getType()."'
                                                   AND `items_id` = '".$item->getID()."'");
                     // Linked items
                     if ($subquery = $item->getSelectLinkedItem()) {
                        $nb += countElementsInTable('glpi_items_problems',
                                                      "(`itemtype`,`items_id`) IN (" . $subquery . ")");
                     }
                  }
                  return self::createTabEntry($LANG['Menu'][7], $nb);
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