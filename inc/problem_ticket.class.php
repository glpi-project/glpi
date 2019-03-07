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

class Problem_Ticket extends CommonDBRelation{

   // From CommonDBRelation
   static public $itemtype_1   = 'Problem';
   static public $items_id_1   = 'problems_id';

   static public $itemtype_2   = 'Ticket';
   static public $items_id_2   = 'tickets_id';


   /**
    * @since 0.84
   **/
   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   static function getTypeName($nb = 0) {
      return _n('Link Ticket/Problem', 'Links Ticket/Problem', $nb);
   }


   /**
    * @see CommonGLPI::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (static::canView()) {
         $nb = 0;
         switch ($item->getType()) {
            case 'Ticket' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable('glpi_problems_tickets',
                                             ['tickets_id' => $item->getID()]);
               }
               return self::createTabEntry(Problem::getTypeName(Session::getPluralNumber()), $nb);

            case 'Problem' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable('glpi_problems_tickets',
                                             ['problems_id' => $item->getID()]);
               }
               return self::createTabEntry(Ticket::getTypeName(Session::getPluralNumber()), $nb);
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      switch ($item->getType()) {
         case 'Ticket' :
            self::showForTicket($item);
            break;

         case 'Problem' :
            self::showForProblem($item);
            break;
      }
      return true;
   }


   /**
    * @since 0.84
   **/
   function post_addItem() {
      global $CFG_GLPI;

      $donotif = !isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"];

      if ($donotif) {
         $problem = new Problem();
         if ($problem->getFromDB($this->input["problems_id"])) {
            $options = [];
            NotificationEvent::raiseEvent("new", $problem, $options);
         }
      }

      parent::post_addItem();
   }


   /**
    * @since 0.84
   **/
   function post_deleteFromDB() {
      global $CFG_GLPI;

      $donotif = !isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"];

      if ($donotif) {
         $problem = new Problem();
         if ($problem->getFromDB($this->fields["problems_id"])) {
            $options = [];
            NotificationEvent::raiseEvent("delete", $problem, $options);
         }
      }

      parent::post_deleteFromDB();
   }


   /**
    * @since 0.85
    *
    * @see CommonDBTM::showMassiveActionsSubForm()
   **/
   static function showMassiveActionsSubForm(MassiveAction $ma) {
      global $CFG_GLPI;

      switch ($ma->getAction()) {
         case 'add_task' :
            $tasktype = 'TicketTask';
            if ($ttype = getItemForItemtype($tasktype)) {
               $ttype->showMassiveActionAddTaskForm();
               return true;
            }
            return false;

         case "solveticket" :
            $problem = new Problem();
            $input = $ma->getInput();
            if (isset($input['problems_id']) && $problem->getFromDB($input['problems_id'])) {
               $problem->showMassiveSolutionForm($problem);
               echo "<br>";
               echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
               return true;
            }
            return false;
      }
      return parent::showMassiveActionsSubForm($ma);
   }


   /**
    * @since 0.85
    *
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
   **/
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {

      switch ($ma->getAction()) {
         case 'add_task' :
            if (!($task = getItemForItemtype('TicketTask'))) {
               $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
               break;
            }
            $ticket = new Ticket();
            $field = $ticket->getForeignKeyField();

            $input = $ma->getInput();

            foreach ($ids as $id) {
               if ($item->can($id, READ)) {
                  if ($ticket->getFromDB($item->fields['tickets_id'])) {
                     $input2 = [$field              => $item->fields['tickets_id'],
                                  'taskcategories_id' => $input['taskcategories_id'],
                                  'actiontime'        => $input['actiontime'],
                                  'content'           => $input['content']];
                     if ($task->can(-1, CREATE, $input2)) {
                        if ($task->add($input2)) {
                           $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                        } else {
                           $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                           $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                        }
                     } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                        $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                     }
                  } else {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                     $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                  }
               }
            }
            return;

         case 'solveticket' :
            $input  = $ma->getInput();
            $ticket = new Ticket();
            foreach ($ids as $id) {
               if ($item->can($id, READ)) {
                  if ($ticket->getFromDB($item->fields['tickets_id'])
                      && $ticket->canSolve()) {
                     $solution = new ITILSolution();
                     $added = $solution->add([
                        'itemtype'  => $ticket->getType(),
                        'items_id'  => $ticket->getID(),
                        'solutiontypes_id'   => $input['solutiontypes_id'],
                        'content'            => $input['content']
                     ]);

                     if ($added) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                     } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        $ma->addMessage($ticket->getErrorMessage(ERROR_ON_ACTION));
                     }
                  } else {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                     $ma->addMessage($ticket->getErrorMessage(ERROR_RIGHT));
                  }
               } else {
                  $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                  $ma->addMessage($ticket->getErrorMessage(ERROR_RIGHT));
               }
            }
            return;
      }
      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
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

      $canedit = $problem->canEdit($ID);

      $rand = mt_rand();

      $iterator = $DB->request([
         'SELECT DISTINCT' => 'glpi_problems_tickets.id AS linkID',
         'FIELDS'          => 'glpi_tickets.*',
         'FROM'            => 'glpi_problems_tickets',
         'LEFT JOIN'       => [
            'glpi_tickets' => [
               'ON' => [
                  'glpi_problems_tickets' => 'tickets_id',
                  'glpi_tickets'          => 'id'
               ]
            ]
         ],
         'WHERE'           => [
            'glpi_problems_tickets.problems_id' => $ID
         ],
         'ORDERBY'         => 'glpi_tickets.name'
      ]);

      $tickets = [];
      $used    = [];
      $numrows = count($iterator);
      while ($data = $iterator->next()) {
         $tickets[$data['id']] = $data;
         $used[$data['id']]    = $data['id'];
      }

      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form name='changeticket_form$rand' id='changeticket_form$rand' method='post'
               action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='2'>".__('Add a ticket')."</th></tr>";

         echo "<tr class='tab_bg_2'><td class='right'>";
         echo "<input type='hidden' name='problems_id' value='$ID'>";
         $condition = [
            'NOT' => [
               'glpi_tickets.status' => array_merge(
                  Ticket::getSolvedStatusArray(),
                  Ticket::getClosedStatusArray()
               )
            ]
         ];
         Ticket::dropdown([
            'used'        => $used,
            'entity'      => $problem->getEntityID(),
            'entity_sons' => $problem->isRecursive(),
            'condition'   => $condition,
            'displaywith' => ['id']
         ]);
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
                                      'container'        => 'mass'.__CLASS__.$rand,
                                      'specific_actions' => ['purge'
                                                                    => _x('button',
                                                                          'Delete permanently'),
                                                                  __CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'solveticket'
                                                                    => __('Solve tickets'),
                                                                  __CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'add_task'
                                                                    => __('Add a new task')],
                                      'extraparams'      => ['problems_id' => $problem->getID()],
                                      'width'            => 1000,
                                      'height'           => 500];
         Html::showMassiveActions($massiveactionparams);
      }
      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr class='noHover'><th colspan='12'>".Ticket::getTypeName($numrows)."</th>";
      echo "</tr>";
      if ($numrows) {
         Ticket::commonListHeader(Search::HTML_OUTPUT, 'mass'.__CLASS__.$rand);
         Session::initNavigateListItems('Ticket',
                                 //TRANS : %1$s is the itemtype name,
                                 //        %2$s is the name of the item (used for headings of a list)
                                         sprintf(__('%1$s = %2$s'), Problem::getTypeName(1),
                                                 $problem->fields["name"]));

         $i = 0;
         foreach ($tickets as $data) {
            Session::addToNavigateListItems('Ticket', $data["id"]);
            Ticket::showShort($data['id'], ['followups'              => false,
                                                 'row_num'                => $i,
                                                 'type_for_massiveaction' => __CLASS__,
                                                 'id_for_massiveaction'   => $data['linkID']]);
            $i++;
         }
         Ticket::commonListHeader(Search::HTML_OUTPUT, 'mass'.__CLASS__.$rand);
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
    * Show problems for a ticket
    *
    * @param $ticket Ticket object
   **/
   static function showForTicket(Ticket $ticket) {
      global $DB, $CFG_GLPI;

      $ID = $ticket->getField('id');
      if (!Session::haveRight("problem", Problem::READALL)
          || !$ticket->can($ID, READ)) {

         return false;
      }

      $canedit = $ticket->can($ID, UPDATE);

      $rand = mt_rand();

      $iterator = $DB->request([
         'SELECT DISTINCT' => 'glpi_problems_tickets.id AS linkID',
         'FIELDS'          => 'glpi_problems.*',
         'FROM'            => 'glpi_problems_tickets',
         'LEFT JOIN'       => [
            'glpi_problems'   => [
               'ON' => [
                  'glpi_problems_tickets' => 'problems_id',
                  'glpi_problems'         => 'id'
               ]
            ]
         ],
         'WHERE'           => [
            'glpi_problems_tickets.tickets_id'  => $ID
         ],
         'ORDERBY'         => 'glpi_problems.name'
      ]);

      $problems = [];
      $used     = [];
      $numrows  = count($iterator);
      while ($data = $iterator->next()) {
         $problems[$data['id']] = $data;
         $used[$data['id']]     = $data['id'];
      }
      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form name='problemticket_form$rand' id='problemticket_form$rand' method='post'
                action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='3'>".__('Add a problem')."</th></tr>";
         echo "<tr class='tab_bg_2'><td>";
         echo "<input type='hidden' name='tickets_id' value='$ID'>";
         $condition = [
            'NOT' => [
               'glpi_problems.status' => array_merge(
                  Problem::getSolvedStatusArray(),
                  Problem::getClosedStatusArray()
               )
            ]
         ];

         Problem::dropdown([
            'used'      => $used,
            'entity'    => $ticket->getEntityID(),
            'condition' => $condition
         ]);
         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "</td><td>";
         echo "<a href='".Toolbox::getItemTypeFormURL('Problem')."?tickets_id=$ID'>";
         echo __('Create a problem from this ticket');
         echo "</a>";

         echo "</td></tr></table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($canedit && $numrows) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = ['num_displayed'  => min($_SESSION['glpilist_limit'], $numrows),
                                      'container'      => 'mass'.__CLASS__.$rand];
         Html::showMassiveActions($massiveactionparams);
      }
      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr class='noHover'><th colspan='12'>".Problem::getTypeName($numrows)."</th>";
      echo "</tr>";
      if ($numrows) {
         Problem::commonListHeader(Search::HTML_OUTPUT, 'mass'.__CLASS__.$rand);
         Session::initNavigateListItems('Problem',
                              //TRANS : %1$s is the itemtype name,
                              //        %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'),
                                                Ticket::getTypeName(1), $ticket->fields["name"]));

         $i = 0;
         foreach ($problems as $data) {
            Session::addToNavigateListItems('Problem', $data["id"]);
            Problem::showShort($data['id'], ['row_num'                => $i,
                                                  'type_for_massiveaction' => __CLASS__,
                                                  'id_for_massiveaction'   => $data['linkID']]);
            $i++;
         }
         Problem::commonListHeader(Search::HTML_OUTPUT, 'mass'.__CLASS__.$rand);
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
