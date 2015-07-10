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

/// Class SLA
class SlaLevel_Ticket extends CommonDBTM {


   /**
    * Retrieve an item from the database
    *
    * @param $ID ID of the item to get
    *
    * @return true if succeed else false
   **/
   function getFromDBForTicket($ID) {
      return $this->getFromDBByQuery("WHERE `".$this->getTable()."`.`tickets_id` = '$ID'");
   }


   /**
    * Delete entries for a ticket
    *
    * @param $tickets_id Ticket ID
    *
    * @return nothing
   **/
   static function deleteForTicket($tickets_id) {
      global $DB;

      $query1 = "DELETE
                 FROM `glpi_slalevels_tickets`
                 WHERE `tickets_id` = '$tickets_id'";
      $DB->query($query1);
   }


   /**
    * Give cron information
    *
    * @param $name : task's name
    *
    * @return arrray of information
   **/
   static function cronInfo($name) {

      switch ($name) {
         case 'slaticket' :
            return array('description' => __('Automatic actions of SLA'));
      }
      return array();
   }


   /**
    * Cron for ticket's automatic close
    *
    * @param $task : CronTask object
    *
    * @return integer (0 : nothing done - 1 : done)
   **/
   static function cronSlaTicket(CronTask $task) {
      global $DB;

      $tot = 0;

      $query = "SELECT *
                FROM `glpi_slalevels_tickets`
                WHERE `glpi_slalevels_tickets`.`date` < NOW()";

      foreach ($DB->request($query) as $data) {
         $tot++;
         self::doLevelForTicket($data);
      }

      $task->setVolume($tot);
      return ($tot > 0);
   }


   /**
    * Do a specific SLAlevel for a ticket
    *
    * @param $data array data of an entry of slalevels_tickets
    *
    * @return nothing
   **/
   static function doLevelForTicket(array $data) {

      $ticket         = new Ticket();
      $slalevelticket = new self();

      // existing ticket and not deleted
      if ($ticket->getFromDB($data['tickets_id'])
          && !$ticket->isDeleted()) {

         // search all actors of a ticket
         foreach($ticket->getUsers(CommonITILActor::REQUESTER) as $user) {
            $ticket->fields['_users_id_requester'][] = $user['users_id'];
         }
         foreach($ticket->getUsers(CommonITILActor::ASSIGN) as $user) {
            $ticket->fields['_users_id_assign'][] = $user['users_id'];
         }
         foreach($ticket->getUsers(CommonITILActor::OBSERVER) as $user) {
            $ticket->fields['_users_id_observer'][] = $user['users_id'];
         }

         foreach($ticket->getGroups(CommonITILActor::REQUESTER) as $group) {
            $ticket->fields['_groups_id_requester'][] = $group['groups_id'];
         }
         foreach($ticket->getGroups(CommonITILActor::ASSIGN) as $group) {
            $ticket->fields['_groups_id_assign'][] = $group['groups_id'];
         }
         foreach($ticket->getGroups(CommonITILActor::OBSERVER) as $groups) {
            $ticket->fields['_groups_id_observer'][] = $group['groups_id'];
         }

         foreach($ticket->getSuppliers(CommonITILActor::ASSIGN) as $supplier) {
            $ticket->fields['_suppliers_id_assign'][] = $supplier['suppliers_id'];
         }

         $slalevel = new SlaLevel();
         $sla      = new SLA();
         // Check if sla datas are OK
         if (($ticket->fields['slas_id'] > 0)
             && ($ticket->fields['slalevels_id'] == $data['slalevels_id'])) {

            if ($ticket->fields['status'] == CommonITILObject::CLOSED) {
               // Drop line when status is closed
               $slalevelticket->delete(array('id' => $data['id']));

            } else if ($ticket->fields['status'] != CommonITILObject::SOLVED) {
               // If status = solved : keep the line in case of solution not validated
               $input['id']           = $ticket->getID();
               $input['_auto_update'] = true;

               if ($slalevel->getRuleWithCriteriasAndActions($data['slalevels_id'], 1, 1)
                   && $sla->getFromDB($ticket->fields['slas_id'])) {
                   $doit = true;
                   if (count($slalevel->criterias)) {
                     $doit = $slalevel->checkCriterias($ticket->fields);
                   }
                  // Process rules
                  if ($doit) {
                     $input = $slalevel->executeActions($input, array());
                  }
               }

               // Put next level in todo list
               $next                  = $slalevel->getNextSlaLevel($ticket->fields['slas_id'],
                                                                   $ticket->fields['slalevels_id']);
               $input['slalevels_id'] = $next;
               $ticket->update($input);
               $sla->addLevelToDo($ticket);
               // Action done : drop the line
               $slalevelticket->delete(array('id' => $data['id']));
            }
         } else {
            // Drop line
            $slalevelticket->delete(array('id' => $data['id']));
         }

      } else {
         // Drop line
         $slalevelticket->delete(array('id' => $data['id']));
      }
   }


   /**
    * Replay all task needed for a specific ticket
    *
    * @param $tickets_id Ticket ID
    *
    * @return nothing
   **/
   static function replayForTicket($tickets_id) {
      global $DB;

      $query = "SELECT *
                FROM `glpi_slalevels_tickets`
                WHERE `glpi_slalevels_tickets`.`date` < NOW()
                      AND `tickets_id` = '$tickets_id'";

      $number = 0;
      do {
         if ($result = $DB->query($query)) {
            $number = $DB->numrows($result);
            if ($number == 1) {
               $data = $DB->fetch_assoc($result);
               self::doLevelForTicket($data);
            }
         }
      } while ($number == 1);
   }

}
?>