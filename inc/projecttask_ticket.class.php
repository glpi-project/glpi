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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * ProjectTask_Ticket Class
 *
 * Relation between ProjectTasks and Tickets
 *
 * @since version 0.85
**/
class ProjectTask_Ticket extends CommonDBRelation{

   // From CommonDBRelation
   static public $itemtype_1   = 'ProjectTask';
   static public $items_id_1   = 'projecttasks_id';

   static public $itemtype_2   = 'Ticket';
   static public $items_id_2   = 'tickets_id';



   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   static function getTypeName($nb=0) {
      return _n('Link Ticket/Project task','Links Ticket/Project task',$nb);
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (static::canView()) {
         $nb = 0;
         switch ($item->getType()) {
            case 'ProjectTask' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable('glpi_projecttasks_tickets',
                                             "`projecttasks_id` = '".$item->getID()."'");
               }
               return self::createTabEntry(Ticket::getTypeName(Session::getPluralNumber()), $nb);

         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case 'ProjectTask' :
            self::showForProjectTask($item);
            break;
      }
      return true;
   }


   /**
    * Get total duration of tickets linked to a project task
    *
    * @param $projecttasks_id    integer    $projecttasks_id ID of the project task
    *
    * @return integer total actiontime
   **/
   static function getTicketsTotalActionTime($projecttasks_id) {
      global $DB;

      $query = "SELECT SUM(`glpi_tickets`.`actiontime`)
                FROM `glpi_projecttasks_tickets`
                INNER JOIN `glpi_tickets`
                   ON (`glpi_projecttasks_tickets`.`tickets_id` = `glpi_tickets`.`id`)
                WHERE `glpi_projecttasks_tickets`.`projecttasks_id` = '$projecttasks_id';";
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            return $DB->result($result, 0, 0);
         }
      }
      return 0;
   }


   /**
    * Show tickets for a projecttask
    *
    * @param $projecttask ProjectTask object
   **/
   static function showForProjectTask(ProjectTask $projecttask) {
      global $DB, $CFG_GLPI;

      $ID = $projecttask->getField('id');
      if (!$projecttask->can($ID, READ)) {
         return false;
      }

      $canedit = $projecttask->canEdit($ID);
      $rand    = mt_rand();

      $query = "SELECT DISTINCT `glpi_projecttasks_tickets`.`id` AS linkID,
                                `glpi_tickets`.*
                FROM `glpi_projecttasks_tickets`
                LEFT JOIN `glpi_tickets`
                     ON (`glpi_projecttasks_tickets`.`tickets_id` = `glpi_tickets`.`id`)
                WHERE `glpi_projecttasks_tickets`.`projecttasks_id` = '$ID'
                ORDER BY `glpi_tickets`.`name`";
      $result = $DB->query($query);

      $tickets = array();
      $used    = array();
      if ($numrows = $DB->numrows($result)) {
         while ($data = $DB->fetch_assoc($result)) {
            $tickets[$data['id']] = $data;
            $used[$data['id']]    = $data['id'];
         }
      }

      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form name='projecttaskticket_form$rand' id='projecttaskticket_form$rand'
                method='post' action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='3'>".__('Add a ticket')."</th></tr>";

         echo "<tr class='tab_bg_2'><td class='right'>";
         echo "<input type='hidden' name='projecttasks_id' value='$ID'>";
         $condition = "`glpi_tickets`.`status`
                        NOT IN ('".implode("', '",
                                           array_merge(Ticket::getSolvedStatusArray(),
                                                       Ticket::getClosedStatusArray()))."')";
         Ticket::dropdown(array('used'        => $used,
                                'entity'      => $projecttask->getEntityID(),
                                'entity_sons' => $projecttask->isRecursive(),
                                'condition'   => $condition,
                                'displaywith' => array('id')));

         echo "</td><td width='20%'>";
         echo "<a href='".Toolbox::getItemTypeFormURL('Ticket')."?projecttasks_id=$ID'>";
         _e('Create a ticket from this task');
         echo "</a>";
         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "</td></tr>";

         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($canedit && $numrows) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = array('num_displayed'    => $numrows,
                                      'container'        => 'mass'.__CLASS__.$rand);
         Html::showMassiveActions($massiveactionparams);
      }

      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr><th colspan='12'>".Ticket::getTypeName($numrows)."</th>";
      echo "</tr>";
      if ($numrows) {
         Ticket::commonListHeader(Search::HTML_OUTPUT,'mass'.__CLASS__.$rand);
         Session::initNavigateListItems('Ticket',
                                 //TRANS : %1$s is the itemtype name,
                                 //        %2$s is the name of the item (used for headings of a list)
                                         sprintf(__('%1$s = %2$s'), ProjectTask::getTypeName(1),
                                                 $projecttask->fields["name"]));

         $i = 0;
         foreach ($tickets as $data) {
            Session::addToNavigateListItems('Ticket', $data["id"]);
            Ticket::showShort($data['id'], array('followups'              => false,
                                                 'row_num'                => $i,
                                                 'type_for_massiveaction' => __CLASS__,
                                                 'id_for_massiveaction'   => $data['linkID']));
            $i++;
         }
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
