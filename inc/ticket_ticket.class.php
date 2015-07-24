<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

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
   die("Sorry. You can't access directly to this file");
}

/// Class Ticket links
class Ticket_Ticket extends CommonDBRelation {


   // From CommonDBRelation
   static public $itemtype_1     = 'Ticket';
   static public $items_id_1     = 'tickets_id_1';
   static public $itemtype_2     = 'Ticket';
   static public $items_id_2     = 'tickets_id_2';

   static public $check_entity_coherency = false;

   // Ticket links
   const LINK_TO        = 1;
   const DUPLICATE_WITH = 2;


   /**
    * @since version 0.85
    *
    * @see CommonDBTM::showMassiveActionsSubForm()
   **/
   static function showMassiveActionsSubForm(MassiveAction $ma) {

      switch ($ma->getAction()) {
         case 'add' :
            $rand = Ticket_Ticket::dropdownLinks('link');
            printf(__('%1$s: %2$s'), __('Ticket'), __('ID'));
            echo "&nbsp;<input type='text' name='tickets_id_1' value='' size='10'>\n";
            echo "<br><br>";
            echo "<br><br><input type='submit' name='massiveaction' class='submit' value='".
                           _sx('button','Post')."'>";
            return true;
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
         case 'add' :
            $input = $ma->getInput();
            $ticket = new Ticket();
            if (isset($input['link'])
                && isset($input['tickets_id_1'])) {
               if ($item->getFromDB($input['tickets_id_1'])) {
                  foreach ($ids as $id) {
                     $input2                          = array();
                     $input2['id']                    = $input['tickets_id_1'];
                     $input2['_link']['tickets_id_1'] = $input['tickets_id_1'];
                     $input2['_link']['link']         = $input['link'];
                     $input2['_link']['tickets_id_2'] = $id;
                     if ($item->can($input['tickets_id_1'], UPDATE)) {
                        if ($ticket->update($input2)) {
                           $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                        } else {
                           $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                           $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                        }
                     } else {
                      $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                      $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                     }
                  }
               }
            }
            return;
      }
      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }


   /**
    * Get linked tickets to a ticket
    *
    * @param $ID ID of the ticket id
    *
    * @return array of linked tickets  array(id=>linktype)
   **/
   static function getLinkedTicketsTo ($ID) {
      global $DB;

      // Make new database object and fill variables
      if (empty($ID)) {
         return false;
      }

      $sql = "SELECT *
              FROM `glpi_tickets_tickets`
              WHERE `tickets_id_1` = '$ID'
                    OR `tickets_id_2` = '$ID'";

      $tickets = array();

      foreach ($DB->request($sql) as $data) {
         if ($data['tickets_id_1'] != $ID) {
            $tickets[$data['id']] = array('link'       => $data['link'],
                                          'tickets_id' => $data['tickets_id_1']);
         } else {
            $tickets[$data['id']] = array('link'       => $data['link'],
                                          'tickets_id' => $data['tickets_id_2']);
         }
      }

      ksort($tickets);
      return $tickets;
   }


   /**
    * Display linked tickets to a ticket
    *
    * @param $ID ID of the ticket id
    *
    * @return nothing display
   **/
   static function displayLinkedTicketsTo ($ID) {
      global $DB, $CFG_GLPI;

      $tickets   = self::getLinkedTicketsTo($ID);
      $canupdate = Session::haveRight('ticket', UPDATE);

      $ticket    = new Ticket();
      if (is_array($tickets) && count($tickets)) {
         foreach ($tickets as $linkID => $data) {
            if ($ticket->getFromDB($data['tickets_id'])) {
               $icons =  "<img src='".Ticket::getStatusIconURL($ticket->fields["status"]).
                             "' alt=\"".Ticket::getStatus($ticket->fields["status"])."\"
                             title=\"". Ticket::getStatus($ticket->fields["status"])."\">";
               if ($canupdate) {
                  $icons .= '&nbsp;'.Html::getSimpleForm(static::getFormURL(), 'purge',
                                                         _x('button', 'Delete permanently'),
                                                         array('id'         => $linkID,
                                                               'tickets_id' => $ID),
                                                         $CFG_GLPI["root_doc"]."/pics/delete.png");
               }
               $text = sprintf(__('%1$s %2$s'), self::getLinkName($data['link']),
                               $ticket->getLink(array('forceid' => true)));
               printf(__('%1$s %2$s'), $text, $icons);

            }
            echo '<br>';
         }
      }
   }


   /**
    * Dropdown for links between tickets
    *
    * @param $myname    select name
    * @param $value     default value (default self::LINK_TO)
   **/
   static function dropdownLinks($myname, $value=self::LINK_TO) {

      $tmp[self::LINK_TO]        = __('Linked to');
      $tmp[self::DUPLICATE_WITH] = __('Duplicates');
      Dropdown::showFromArray($myname, $tmp, array('value' => $value));
   }


   /**
    * Get Link Name
    *
    * @param $value default value
   **/
   static function getLinkName($value) {

      $tmp[self::LINK_TO]        = __('Linked to');
      $tmp[self::DUPLICATE_WITH] = __('Duplicates');

      if (isset($tmp[$value])) {
         return $tmp[$value];
      }
      return NOT_AVAILABLE;
   }


   function prepareInputForAdd($input) {
      // Clean values
      $input['tickets_id_1'] = Toolbox::cleanInteger($input['tickets_id_1']);
      $input['tickets_id_2'] = Toolbox::cleanInteger($input['tickets_id_2']);

      // Check of existance of rights on both Ticket(s) is done by the parent
      if ($input['tickets_id_2'] == $input['tickets_id_1']) {
         return false;
      }

      if (!isset($input['link'])) {
         $input['link'] = self::LINK_TO;
      }

      // No multiple links
      $tickets = self::getLinkedTicketsTo($input['tickets_id_1']);
      if (count($tickets)) {
         foreach ($tickets as $key => $t) {
            if ($t['tickets_id'] == $input['tickets_id_2']) {
               // Delete old simple link
               if (($input['link'] == self::DUPLICATE_WITH)
                   && ($t['link'] == self::LINK_TO)) {
                  $tt = new Ticket_Ticket();
                  $tt->delete(array("id" => $key));
               } else { // No duplicate link
                  return false;
               }
            }
         }
      }

      return parent::prepareInputForAdd($input);
   }


   function post_deleteFromDB() {
      global $CFG_GLPI;

      $t = new Ticket();
      $t->updateDateMod($this->fields['tickets_id_1']);
      $t->updateDateMod($this->fields['tickets_id_2']);
      parent::post_deleteFromDB();

      $donotif = $CFG_GLPI["use_mailing"];
      if ($donotif) {
         $t->getFromDB($this->fields['tickets_id_1']);
         NotificationEvent::raiseEvent("update", $t);
         $t->getFromDB($this->fields['tickets_id_2']);
         NotificationEvent::raiseEvent("update", $t);
      }
   }


   function post_addItem() {
      global $CFG_GLPI;

      $t = new Ticket();
      $t->updateDateMod($this->fields['tickets_id_1']);
      $t->updateDateMod($this->fields['tickets_id_2']);
      parent::post_addItem();

      $donotif = $CFG_GLPI["use_mailing"];
      if ($donotif) {
         $t->getFromDB($this->fields['tickets_id_1']);
         NotificationEvent::raiseEvent("update", $t);
         $t->getFromDB($this->fields['tickets_id_2']);
         NotificationEvent::raiseEvent("update", $t);
      }

   }


  /**
    * Affect the same solution for duplicates tickets
    *
    * @param $ID ID of the ticket id
    *
    * @return nothing do the change
   **/
   static function manageLinkedTicketsOnSolved($ID) {

      $ticket = new Ticket();

      if ($ticket->getfromDB($ID)) {
         $input['solution']         = addslashes($ticket->fields['solution']);
         $input['solutiontypes_id'] = addslashes($ticket->fields['solutiontypes_id']);

         $tickets = self::getLinkedTicketsTo($ID);
         if (count($tickets)) {
            foreach ($tickets as $data) {
               $input['id'] = $data['tickets_id'];
               if ($ticket->can($input['id'], UPDATE)
                   && ($data['link'] == self::DUPLICATE_WITH)
                   && ($ticket->fields['status'] != CommonITILObject::SOLVED)
                   && ($ticket->fields['status'] != CommonITILObject::CLOSED)) {
                  $ticket->update($input);
               }
            }
         }
      }
   }
}
?>
