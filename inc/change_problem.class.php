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
 * @since version 0.84
 *
 * Change_Problem Class
 *
 * Relation between Changes and Problems
**/
class Change_Problem extends CommonDBRelation{

   // From CommonDBRelation
   static public $itemtype_1   = 'Change';
   static public $items_id_1   = 'changes_id';

   static public $itemtype_2   = 'Problem';
   static public $items_id_2   = 'problems_id';



   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   static function getTypeName($nb=0) {
      return _n('Link Problem/Change','Links Problem/Change',$nb);
   }


   /**
    * @see CommonGLPI::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (static::canView()) {
         $nb = 0;
         switch ($item->getType()) {
            case 'Change' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable('glpi_changes_problems',
                                             "`changes_id` = '".$item->getID()."'");
               }
               return self::createTabEntry(Problem::getTypeName(Session::getPluralNumber()), $nb);

            case 'Problem' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable('glpi_changes_problems',
                                             "`problems_id` = '".$item->getID()."'");
               }
               return self::createTabEntry(Change::getTypeName(Session::getPluralNumber()), $nb);
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case 'Change' :
            self::showForChange($item);
            break;

         case 'Problem' :
            self::showForProblem($item);
            break;
      }
      return true;
   }


   /**
    * Get search function for the class
    *
    * @return array of search option
   **/
   function getSearchOptions() {
      return parent::getSearchOptions();
   }


   /**
    * Show tickets for a problem
    *
    * @param $problem Problem object
   **/
   static function showForProblem(Problem $problem) {
      global $DB, $CFG_GLPI;

      $ID = $problem->getField('id');
      if (!$problem->can($ID, READ)) {
         return false;
      }

      $canedit       = $problem->canEdit($ID);
      $rand          = mt_rand();
      $showentities  = Session::isMultiEntitiesMode();

      $query = "SELECT DISTINCT `glpi_changes_problems`.`id` AS linkID,
                                `glpi_changes`.*
                FROM `glpi_changes_problems`
                LEFT JOIN `glpi_changes`
                     ON (`glpi_changes_problems`.`changes_id` = `glpi_changes`.`id`)
                WHERE `glpi_changes_problems`.`problems_id` = '$ID'
                ORDER BY `glpi_changes`.`name`";
      $result = $DB->query($query);

      $changes = array();
      $used    = array();
      if ($numrows = $DB->numrows($result)) {
         while ($data = $DB->fetch_assoc($result)) {
            $changes[$data['id']] = $data;
            $used[$data['id']]    = $data['id'];
         }
      }
      if ($canedit) {
         echo "<div class='firstbloc'>";

         echo "<form name='changeproblem_form$rand' id='changeproblem_form$rand' method='post'
                action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='3'>".__('Add a change')."</th></tr>";

         echo "<tr class='tab_bg_2'><td>";
         echo "<input type='hidden' name='problems_id' value='$ID'>";
         Change::dropdown(array('used'        => $used,
                                'entity'      => $problem->getEntityID(),
                                'entity_sons' => $problem->isRecursive()));
         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "</td><td>";
         if (Session::haveRight('change', CREATE)) {
            echo "<a href='".Toolbox::getItemTypeFormURL('Change')."?problems_id=$ID'>";
            _e('Create a change from this problem');
            echo "</a>";
         }
         echo "</td></tr></table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($canedit && $numrows) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = array('num_displayed' => min($_SESSION['glpilist_limit'], $numrows),
                                      'container'     => 'mass'.__CLASS__.$rand);
         Html::showMassiveActions($massiveactionparams);
      }

      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr class='noHover'><th colspan='12'>".Change::getTypeName($numrows)."</th>";
      echo "</tr>";
      if ($numrows) {
         Change::commonListHeader(Search::HTML_OUTPUT,'mass'.__CLASS__.$rand);
         Session::initNavigateListItems('Change',
                                 //TRANS : %1$s is the itemtype name,
                                 //        %2$s is the name of the item (used for headings of a list)
                                         sprintf(__('%1$s = %2$s'), Problem::getTypeName(1),
                                                 $problem->fields["name"]));

         $i = 0;
         foreach ($changes as $data) {
            Session::addToNavigateListItems('Change', $data["id"]);
            Change::showShort($data['id'], array('row_num'                => $i,
                                                 'type_for_massiveaction' => __CLASS__,
                                                 'id_for_massiveaction'   => $data['linkID']));
            $i++;
         }
         Change::commonListHeader(Search::HTML_OUTPUT,'mass'.__CLASS__.$rand);
      }
      echo "</table>";

      if ($canedit && $numrows) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";

   }


   /**
    * Show problems for a change
    *
    * @param $change Change object
   **/
   static function showForChange(Change $change) {
      global $DB, $CFG_GLPI;

      $ID = $change->getField('id');
      if (!$change->can($ID, READ)) {
         return false;
      }

      $canedit      = $change->canEdit($ID);
      $rand         = mt_rand();
      $showentities = Session::isMultiEntitiesMode();

      $query = "SELECT DISTINCT `glpi_changes_problems`.`id` AS linkID,
                                `glpi_problems`.*
                FROM `glpi_changes_problems`
                LEFT JOIN `glpi_problems`
                     ON (`glpi_changes_problems`.`problems_id` = `glpi_problems`.`id`)
                WHERE `glpi_changes_problems`.`changes_id` = '$ID'
                ORDER BY `glpi_problems`.`name`";
      $result = $DB->query($query);

      $problems = array();
      $used     = array();
      if ($numrows = $DB->numrows($result)) {
         while ($data = $DB->fetch_assoc($result)) {
            $problems[$data['id']] = $data;
            $used[$data['id']]     = $data['id'];
         }
      }

      if ($canedit) {
         echo "<div class='firstbloc'>";

         echo "<form name='changeproblem_form$rand' id='changeproblem_form$rand' method='post'
                action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='2'>".__('Add a problem')."</th></tr>";

         echo "<tr class='tab_bg_2'><td>";
         echo "<input type='hidden' name='changes_id' value='$ID'>";
         Problem::dropdown(array('used'   => $used,
                                 'entity' => $change->getEntityID()));
         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "</td></tr></table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($canedit && $numrows) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = array('num_displayed' => min($_SESSION['glpilist_limit'], $numrows),
                                      'container'     => 'mass'.__CLASS__.$rand);
         Html::showMassiveActions($massiveactionparams);
      }

      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr class='noHover'><th colspan='12'>".Problem::getTypeName($numrows)."</th>";
      echo "</tr>";
      if ($numrows) {
         Problem::commonListHeader(Search::HTML_OUTPUT,'mass'.__CLASS__.$rand);
         Session::initNavigateListItems('Problem',
                                 //TRANS : %1$s is the itemtype name,
                                 //        %2$s is the name of the item (used for headings of a list)
                                         sprintf(__('%1$s = %2$s'), Change::getTypeName(1),
                                                 $change->fields["name"]));

         $i = 0;
         foreach ($problems as $data) {
            Session::addToNavigateListItems('Problem', $data["id"]);
            Problem::showShort($data['id'], array('row_num'               => $i,
                                                 'type_for_massiveaction' => __CLASS__,
                                                 'id_for_massiveaction'   => $data['linkID']));
            $i++;
         }
         Problem::commonListHeader(Search::HTML_OUTPUT,'mass'.__CLASS__.$rand);
      }
      echo "</table>";

      if ($canedit && $numrows) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";

   }


}
?>
