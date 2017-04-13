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
 * Change_Ticket Class
 *
 * Relation between Changes and Tickets
**/
class Change_Ticket extends CommonDBRelation{

   // From CommonDBRelation
   static public $itemtype_1   = 'Change';
   static public $items_id_1   = 'changes_id';

   static public $itemtype_2   = 'Ticket';
   static public $items_id_2   = 'tickets_id';



   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   static function getTypeName($nb=0) {
      return _n('Link Ticket/Change','Links Ticket/Change',$nb);
   }


   /**
    * @since version 0.85
    *
    * @see CommonGLPI::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (static::canView()) {
         $nb = 0;
         switch ($item->getType()) {
            case 'Change' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable('glpi_changes_tickets',
                                             "`changes_id` = '".$item->getID()."'");
               }
               return self::createTabEntry(Ticket::getTypeName(Session::getPluralNumber()), $nb);

            case 'Ticket' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable('glpi_changes_tickets',
                                             "`tickets_id` = '".$item->getID()."'");
               }
               return self::createTabEntry(Change::getTypeName(Session::getPluralNumber()), $nb);
         }
      }
      return '';
   }


   /**
    * @since version 0.85
   **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case 'Change' :
            self::showForChange($item);
            break;

         case 'Ticket' :
            self::showForTicket($item);
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
    * @since version 0.85
    *
    * @see CommonDBTM::showMassiveActionsSubForm()
   **/
   static function showMassiveActionsSubForm(MassiveAction $ma) {
      global $CFG_GLPI;

      switch ($ma->getAction()) {
         case 'add_task' :
            $tasktype = 'TicketTask';
            if ($ttype = getItemForItemtype($tasktype)) {
               $ttype->showFormMassiveAction();
               return true;
            }
            return false;
            
         case "solveticket" :
            $change = new Change();
            $input = $ma->getInput();
            if (isset($input['changes_id']) && $change->getFromDB($input['changes_id'])) {
               Ticket::showMassiveSolutionForm($change->getEntityID());
               echo "<br><br>";
               echo Html::submit(_x('button','Post'), array('name' => 'massiveaction'));
               return true;
            }
            return false;
      }
      return parent::showMassiveActionsSubForm($ma);
   }


   /**
    * @since version 0.85
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
                     $input2 = array($field              => $item->fields['tickets_id'],
                                  'taskcategories_id' => $input['taskcategories_id'],
                                  'actiontime'        => $input['actiontime'],
                                  'content'           => $input['content']);
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
                     $toupdate                     = array();
                     $toupdate['id']               = $ticket->getID();
                     $toupdate['solutiontypes_id'] = $input['solutiontypes_id'];
                     $toupdate['solution']         = $input['solution'];

                     if ($ticket->update($toupdate)) {
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
    * Show tickets for a change
    *
    * @param $change Change object
   **/
   static function showForChange(Change $change) {
      global $DB, $CFG_GLPI;

      $ID = $change->getField('id');
      if (!$change->can($ID, READ)) {
         return false;
      }

      $canedit = $change->canEdit($ID);
      $rand    = mt_rand();

      $query = "SELECT DISTINCT `glpi_changes_tickets`.`id` AS linkID,
                                `glpi_tickets`.*
                FROM `glpi_changes_tickets`
                LEFT JOIN `glpi_tickets`
                     ON (`glpi_changes_tickets`.`tickets_id` = `glpi_tickets`.`id`)
                WHERE `glpi_changes_tickets`.`changes_id` = '$ID'
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
         echo "<form name='changeticket_form$rand' id='changeticket_form$rand' method='post'
                action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='2'>".__('Add a ticket')."</th></tr>";

         echo "<tr class='tab_bg_2'><td class='right'>";
         echo "<input type='hidden' name='changes_id' value='$ID'>";
         Ticket::dropdown(array('used'        => $used,
                                'entity'      => $change->getEntityID(),
                                'entity_sons' => $change->isRecursive(),
                                'displaywith' => array('id')));
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
         $massiveactionparams
            = array('num_displayed'    => min($_SESSION['glpilist_limit'], $numrows),
                    'specific_actions' => array('purge' => _x('button', 'Delete permanently'),
                                                 __CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'solveticket'
                                                        => __('Solve tickets'),
                                                 __CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'add_task' 
                                                        => __('Add a new task')),
                     'container'        => 'mass'.__CLASS__.$rand,
                     'extraparams'      => array('changes_id' => $change->getID()),
                     'width'            => 1000,
                     'height'           => 500);
         Html::showMassiveActions($massiveactionparams);
      }

      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr class='noHover'><th colspan='12'>".Ticket::getTypeName($numrows)."</th>";
      echo "</tr>";
      if ($numrows) {
         Ticket::commonListHeader(Search::HTML_OUTPUT,'mass'.__CLASS__.$rand);
         Session::initNavigateListItems('Ticket',
                                 //TRANS : %1$s is the itemtype name,
                                 //        %2$s is the name of the item (used for headings of a list)
                                         sprintf(__('%1$s = %2$s'), Change::getTypeName(1),
                                                 $change->fields["name"]));

         $i = 0;
         foreach ($tickets as $data) {
            Session::addToNavigateListItems('Ticket', $data["id"]);
            Ticket::showShort($data['id'], array('followups'              => false,
                                                 'row_num'                => $i,
                                                 'type_for_massiveaction' => __CLASS__,
                                                 'id_for_massiveaction'   => $data['linkID']));
            $i++;
         }
         Ticket::commonListHeader(Search::HTML_OUTPUT,'mass'.__CLASS__.$rand);
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
    * Show changes for a ticket
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

      $query = "SELECT DISTINCT `glpi_changes_tickets`.`id` AS linkID,
                                `glpi_changes`.*
                FROM `glpi_changes_tickets`
                LEFT JOIN `glpi_changes`
                     ON (`glpi_changes_tickets`.`changes_id` = `glpi_changes`.`id`)
                WHERE `glpi_changes_tickets`.`tickets_id` = '$ID'
                ORDER BY `glpi_changes`.`name`";
      $result = $DB->query($query);

      $changes = array();
      $used = array();
      if ($numrows = $DB->numrows($result)) {
         while ($data = $DB->fetch_assoc($result)) {
            $changes[$data['id']] = $data;
            $used[$data['id']] = $data['id'];
         }
      }
      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form name='changeticket_form$rand' id='changeticket_form$rand' method='post'
               action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='3'>".__('Add a change')."</th></tr>";
         echo "<tr class='tab_bg_2'><td>";
         echo "<input type='hidden' name='tickets_id' value='$ID'>";
         Change::dropdown(array('used'        => $used,
                                'entity'      => $ticket->getEntityID()));
         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\""._sx('button','Add')."\" class='submit'>";
         echo "</td><td>";
         if (Session::haveRight('change', CREATE)) {
            echo "<a href='".Toolbox::getItemTypeFormURL('Change')."?tickets_id=$ID'>";
            _e('Create a change from this ticket');
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
                                         sprintf(__('%1$s = %2$s'), Ticket::getTypeName(1),
                                                 $ticket->fields["name"]));

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


}
?>
