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
 * ProjectTask_Ticket Class
 *
 * Relation between ProjectTasks and Tickets
 *
 * @since 0.85
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


   static function getTypeName($nb = 0) {
      return _n('Link Ticket/Project task', 'Links Ticket/Project task', $nb);
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (static::canView()) {
         $nb = 0;
         switch ($item->getType()) {
            case 'ProjectTask' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = self::countForItem($item);
               }
               return self::createTabEntry(Ticket::getTypeName(Session::getPluralNumber()), $nb);

            case 'Ticket' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = self::countForItem($item);
               }
               return self::createTabEntry(ProjectTask::getTypeName(Session::getPluralNumber()), $nb);
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      switch ($item->getType()) {
         case 'ProjectTask' :
            self::showForProjectTask($item);
            break;

         case 'Ticket' :
            self::showForTicket($item);
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

      $iterator = $DB->request([
         'SELECT'       => new QueryExpression('SUM(glpi_tickets.actiontime) AS duration'),
         'FROM'         => self::getTable(),
         'INNER JOIN'   => [
            'glpi_tickets' => [
               'FKEY'   => [
                  self::getTable()  => 'tickets_id',
                  'glpi_tickets'    => 'id'
               ]
            ]
         ],
         'WHERE'        => ['projecttasks_id' => $projecttasks_id]
      ]);

      if ($row = $iterator->next()) {
         return $row['duration'];
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

      $iterator = self::getListForItem($projecttask);
      $numrows = count($iterator);

      $tickets = [];
      $used    = [];
      while ($data = $iterator->next()) {
         $tickets[$data['id']] = $data;
         $used[$data['id']]    = $data['id'];
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
         Ticket::dropdown(['used'        => $used,
                                'entity'      => $projecttask->getEntityID(),
                                'entity_sons' => $projecttask->isRecursive(),
                                'condition'   => $condition,
                                'displaywith' => ['id']]);

         echo "</td><td width='20%'>";
         echo "<a href='".Toolbox::getItemTypeFormURL('Ticket')."?_projecttasks_id=$ID'>";
         echo __('Create a ticket from this task');
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
         $massiveactionparams = ['num_displayed'    => min($_SESSION['glpilist_limit'], $numrows),
                                      'container'        => 'mass'.__CLASS__.$rand];
         Html::showMassiveActions($massiveactionparams);
      }

      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr><th colspan='12'>".Ticket::getTypeName($numrows)."</th>";
      echo "</tr>";
      if ($numrows) {
         Ticket::commonListHeader(Search::HTML_OUTPUT, 'mass'.__CLASS__.$rand);
         Session::initNavigateListItems('Ticket',
                                 //TRANS : %1$s is the itemtype name,
                                 //        %2$s is the name of the item (used for headings of a list)
                                         sprintf(__('%1$s = %2$s'), ProjectTask::getTypeName(1),
                                                 $projecttask->fields["name"]));

         $i = 0;
         foreach ($tickets as $data) {
            Session::addToNavigateListItems('Ticket', $data["id"]);
            Ticket::showShort($data['id'], ['followups'              => false,
                                                 'row_num'                => $i,
                                                 'type_for_massiveaction' => __CLASS__,
                                                 'id_for_massiveaction'   => $data['linkid']]);
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


   /**
    * Show projecttasks for a ticket
    *
    * @param $ticket Ticket object
    **/
   static function showForTicket(Ticket $ticket) {
      global $DB, $CFG_GLPI;

      $ID = $ticket->getField('id');
      if (!$ticket->can($ID, READ)) {
         return false;
      }

      $canedit = $ticket->canEdit($ID);
      $rand    = mt_rand();

      $iterator = self::getListForItem($ticket);
      $numrows = count($iterator);

      $pjtasks = [];
      $used    = [];
      while ($data = $iterator->next()) {
         $pjtasks[$data['id']] = $data;
         $used[$data['id']]    = $data['id'];
      }

      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form name='projecttaskticket_form$rand' id='projecttaskticket_form$rand'
                 method='post' action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='4'>".__('Add a project task')."</th></tr>";

         $rand = mt_rand();

         $finished_states_it = $DB->request(
            [
               'SELECT' => ['id'],
               'FROM'   => ProjectState::getTable(),
               'WHERE'  => [
                  'is_finished' => 1
               ],
            ]
         );
         $finished_states_ids = [];
         foreach ($finished_states_it as $finished_state) {
            $finished_states_ids[] = $finished_state['id'];
         }

         echo "<tr class='tab_bg_2'><td class='right'>";
         echo Html::hidden('tickets_id', ['value' => $ID]);
         Project::dropdown(['entity'      => $ticket->getEntityID(),
                            'entity_sons' => $ticket->isRecursive(),
                            'condition'   => '`glpi_projects`.`projectstates_id`
                                                 NOT IN(' . implode($finished_states_ids, ", ") . ')',
                            'rand'        => $rand]);

         $p = ['projects_id'     => '__VALUE__',
               'entity_restrict' => $ticket->getEntityID(),
               'used'            => $used,
               'rand'            => $rand,
               'myname'          => "projects"];

         Ajax::updateItemOnSelectEvent("dropdown_projects_id$rand", "results_projects$rand",
                                       $CFG_GLPI["root_doc"].
                                       "/ajax/dropdownProjectTaskTicket.php",
                                       $p);

         echo "</td>";

         $excluded_projects_it = $DB->request(
            [
               'SELECT' => ['id'],
               'FROM'   => Project::getTable(),
               'WHERE'  => [
                  'OR' => [
                     'projectstates_id' => $finished_states_ids,
                     'is_template'      => 1
                  ]
               ],
            ]
         );
         $ecluded_projects_ids = [];
         foreach ($excluded_projects_it as $ecluded_project) {
            $ecluded_projects_ids[] = $ecluded_project['id'];
         }
         echo "<td class='right'>";
         echo "<span id='results_projects$rand'>";
         ProjectTask::dropdown(['used'        => $used,
                                'entity'      => $ticket->getEntityID(),
                                'entity_sons' => $ticket->isRecursive(),
                                'condition'   => '`glpi_projecttasks`.`projectstates_id`
                                                     NOT IN(' . implode($finished_states_ids, ", ") . ')
                                                  AND `glpi_projecttasks`.`projects_id`
                                                     NOT IN(' . implode($ecluded_projects_ids, ", ") . ')',
                                'displaywith' => ['id']]);
         echo "</span>";

         echo "</td><td width='20%'>";
         echo "</td><td class='center'>";
         echo Html::submit(_sx('button', 'Add'), ['name' => 'add']);
         echo "</td></tr>";

         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";

      if ($numrows) {
         $columns = ['projectname'      => Project::getTypeName(Session::getPluralNumber()),
                          'name'             => ProjectTask::getTypeName(Session::getPluralNumber()),
                          'tname'            => __('Type'),
                          'sname'            => __('Status'),
                          'percent_done'     => __('Percent done'),
                          'plan_start_date'  => __('Planned start date'),
                          'plan_end_date'    => __('Planned end date'),
                          'planned_duration' => __('Planned duration'),
                          '_effect_duration' => __('Effective duration'),
                          'fname'            => __('Father'),];

         if (isset($_GET["order"]) && ($_GET["order"] == "DESC")) {
            $order = "DESC";
         } else {
            $order = "ASC";
         }

         if (!isset($_GET["sort"]) || empty($_GET["sort"])) {
            $_GET["sort"] = "plan_start_date";
         }

         if (isset($_GET["sort"]) && !empty($_GET["sort"]) && isset($columns[$_GET["sort"]])) {
            $sort = "`".$_GET["sort"]."`";
         } else {
            $sort = "`plan_start_date` $order, `name`";
         }
         $iterator = $DB->request([
            'SELECT'    => [
               'glpi_projecttasks.*',
               'glpi_projecttasktypes.name AS tname',
               'glpi_projectstates.name AS sname',
               'glpi_projectstates.color',
               'father.name AS fname',
               'father.id AS fID',
               'glpi_projects.name AS projectname',
               'glpi_projects.content AS projectcontent'
            ],
            'FROM'      => 'glpi_projecttasks',
            'LEFT JOIN' => [
               'glpi_projecttasktypes' => [
                  'ON' => [
                     'glpi_projecttasktypes' => 'id',
                     'glpi_projecttasks'     => 'projecttasktypes_id'
                  ]
               ],
               'glpi_projectstates'    => [
                  'ON' => [
                     'glpi_projectstates' => 'id',
                     'glpi_projecttasks'  => 'projectstates_id'
                  ]
               ],
               'glpi_projecttasks AS father' => [
                  'ON' => [
                     'father'             => 'id',
                     'glpi_projecttasks'  => 'projecttasks_id'
                  ]
               ],
               'glpi_projecttasks_tickets'   => [
                  'ON' => [
                     'glpi_projecttasks_tickets'   => 'projecttasks_id',
                     'glpi_projecttasks'           => 'id'
                  ]
               ],
               'glpi_projects'               => [
                  'ON' => [
                     'glpi_projecttasks'  => 'projects_id',
                     'glpi_projects'      => 'id'
                  ]
               ]
            ],
            'WHERE'     => [
               'glpi_projecttasks_tickets.tickets_id' => $ID
            ],
            'ORDERBY'   => [
               "$sort $order"
            ]
         ]);

         Session::initNavigateListItems('ProjectTask',
                                        //TRANS : %1$s is the itemtype name,
                                        //       %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'), $ticket::getTypeName(1),
                                                $ticket->getName()));

         if (count($iterator)) {
            echo "<table class='tab_cadre_fixehov'>";
            echo "<tr><th colspan='10'>".ProjectTask::getTypeName($numrows)."</th>";
            echo "</tr>";

            $header = '<tr>';
            foreach ($columns as $key => $val) {
               // Non order column
               if ($key[0] == '_') {
                  $header .= "<th>$val</th>";
               } else {
                  $header .= "<th".($sort == "`$key`" ? " class='order_$order'" : '').">".
                              "<a href='javascript:reloadTab(\"sort=$key&amp;order=".
                                 (($order == "ASC") ?"DESC":"ASC")."&amp;start=0\");'>$val</a></th>";
               }
            }
            $header .= "</tr>\n";
            echo $header;

            while ($data = $iterator->next()) {
               Session::addToNavigateListItems('ProjectTask', $data['id']);
               $rand = mt_rand();
               echo "<tr class='tab_bg_2'>";
               echo "<td>";
               $link = "<a id='Project".$data["projects_id"].$rand."' href='".
                           Project::getFormURLWithID($data['projects_id'])."'>".$data['projectname'].
                           (empty($data['projectname'])?"(".$data['projects_id'].")":"")."</a>";
               echo sprintf(__('%1$s %2$s'), $link,
                              Html::showToolTip($data['projectcontent'],
                                                ['display' => false,
                                                      'applyto' => "Project".$data["projects_id"].$rand]));
               echo "</td>";
               echo "<td>";
               $link = "<a id='ProjectTask".$data["id"].$rand."' href='".
                           ProjectTask::getFormURLWithID($data['id'])."'>".$data['name'].
                           (empty($data['name'])?"(".$data['id'].")":"")."</a>";
               echo sprintf(__('%1$s %2$s'), $link,
                              Html::showToolTip($data['content'],
                                                ['display' => false,
                                                      'applyto' => "ProjectTask".$data["id"].$rand]));
               echo "</td>";
               echo "<td>".$data['tname']."</td>";
               echo "<td";
               echo " style=\"background-color:".$data['color']."\"";
               echo ">".$data['sname']."</td>";
               echo "<td>";
               echo Dropdown::getValueWithUnit($data["percent_done"], "%");
               echo "</td>";
               echo "<td>".Html::convDateTime($data['plan_start_date'])."</td>";
               echo "<td>".Html::convDateTime($data['plan_end_date'])."</td>";
               echo "<td>".Html::timestampToString($data['planned_duration'], false)."</td>";
               echo "<td>".Html::timestampToString(ProjectTask::getTotalEffectiveDuration($data['id']),
                                                   false)."</td>";
               echo "<td>";
               if ($data['projecttasks_id']>0) {
                  $father = Dropdown::getDropdownName('glpi_projecttasks', $data['projecttasks_id']);
                  echo "<a id='ProjectTask".$data["projecttasks_id"].$rand."' href='".
                        ProjectTask::getFormURLWithID($data['projecttasks_id'])."'>".$father.
                        (empty($father)?"(".$data['projecttasks_id'].")":"")."</a>";
               }
               echo "</td></tr>";
            }
            echo $header;
            echo "</table>\n";
         } else {
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th>".__('No item found')."</th></tr>";
            echo "</table>\n";
         }
         echo "</div>";
      }
   }

}
