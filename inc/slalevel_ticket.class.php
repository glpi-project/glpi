<?php
/*
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

/// Class SLALevel
class SlaLevel_Ticket extends CommonDBTM {


   /**
    * Retrieve an item from the database
    *
    * @param $ID        ID of the item to get
    * @param $slttype
    *
    * @since version 9.1 2 mandatory parameters
    *
    * @return true if succeed else false
   **/
   function getFromDBForTicket($ID, $sltType) {

      $query = "LEFT JOIN `glpi_slalevels`
                     ON (`glpi_slalevels_tickets`.`slalevels_id` = `glpi_slalevels`.`id`)
                LEFT JOIN `glpi_slts` ON (`glpi_slalevels`.`slts_id` = `glpi_slts`.`id`)
                WHERE `".$this->getTable()."`.`tickets_id` = '$ID'
                      AND `glpi_slts`.`type` = '$sltType'
                LIMIT 1";

      return $this->getFromDBByQuery($query);
   }


   /**
    * Delete entries for a ticket
    *
    * @param $tickets_id    Ticket ID
    * @param $type          Type of SLT
    *
    * @since 9.1 2 parameters mandatory
    *
    * @return nothing
   **/
   function deleteForTicket($tickets_id, $sltType) {
      global $DB;

      $query1 = "SELECT `glpi_slalevels_tickets`.`id`
                 FROM `glpi_slalevels_tickets`
                 LEFT JOIN `glpi_slalevels`
                       ON (`glpi_slalevels_tickets`.`slalevels_id` = `glpi_slalevels`.`id`)
                 LEFT JOIN `glpi_slts` ON (`glpi_slalevels`.`slts_id` = `glpi_slts`.`id`)
                 WHERE `glpi_slalevels_tickets`.`tickets_id` = '$tickets_id'
                       AND `glpi_slts`.`type` = '$sltType'";

      foreach ($DB->request($query1) as $data) {
         $this->delete(array('id' => $data['id']));
      }
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

      $query = "SELECT `glpi_slalevels_tickets`.*, `glpi_slts`.`type` as type
                FROM `glpi_slalevels_tickets`
                LEFT JOIN `glpi_slalevels`
                     ON (`glpi_slalevels_tickets`.`slalevels_id` = `glpi_slalevels`.`id`)
                LEFT JOIN `glpi_slts` ON (`glpi_slalevels`.`slts_id` = `glpi_slts`.`id`)
                WHERE `glpi_slalevels_tickets`.`date` < NOW()";

      foreach ($DB->request($query) as $data) {
         $tot++;
         self::doLevelForTicket($data, $data['type']);
      }

      $task->setVolume($tot);
      return ($tot > 0);
   }


   /**
    * Do a specific SLAlevel for a ticket
    *
    * @param $data          array data of an entry of slalevels_tickets
    * @param $sltType             Type of slt
    *
    * @since version 9.1   2 parameters mandatory
    *
    * @return nothing
   **/
   static function doLevelForTicket(array $data, $sltType) {

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
         foreach($ticket->getGroups(CommonITILActor::OBSERVER) as $group) {
            $ticket->fields['_groups_id_observer'][] = $group['groups_id'];
         }

         foreach($ticket->getSuppliers(CommonITILActor::ASSIGN) as $supplier) {
            $ticket->fields['_suppliers_id_assign'][] = $supplier['suppliers_id'];
         }

         $slalevel = new SlaLevel();
         $slt      = new SLT();
         // Check if slt datas are OK
         list($dateField, $sltField) = SLT::getSltFieldNames($sltType);
         if (($ticket->fields[$sltField] > 0)) {
            if ($ticket->fields['status'] == CommonITILObject::CLOSED) {
               // Drop line when status is closed
               $slalevelticket->delete(array('id' => $data['id']));

            } else if ($ticket->fields['status'] != CommonITILObject::SOLVED) {
               // No execution if ticket has been taken into account
               if (!(($sltType == SLT::TTO)
                     && ($ticket->fields['takeintoaccount_delay_stat'] > 0))) {
                  // If status = solved : keep the line in case of solution not validated
                  $input['id']           = $ticket->getID();
                  $input['_auto_update'] = true;

                  if ($slalevel->getRuleWithCriteriasAndActions($data['slalevels_id'], 1, 1)
                      && $slt->getFromDB($ticket->fields[$sltField])) {
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
                  $next = $slalevel->getNextSltLevel($ticket->fields[$sltField],
                                                     $data['slalevels_id']);
                  $slt->addLevelToDo($ticket, $next);
                  // Action done : drop the line
                  $slalevelticket->delete(array('id' => $data['id']));

                  $ticket->update($input);
               } else {
                  // Drop line
                  $slalevelticket->delete(array('id' => $data['id']));
               }
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
    * @param $sltType Type of slt
    *
    * @since version 9.1    2 parameters mandatory
    *
    */
   static function replayForTicket($tickets_id, $sltType) {
      global $DB;

      $query = "SELECT `glpi_slalevels_tickets`.*
                FROM `glpi_slalevels_tickets`
                LEFT JOIN `glpi_slalevels`
                      ON (`glpi_slalevels_tickets`.`slalevels_id` = `glpi_slalevels`.`id`)
                LEFT JOIN `glpi_slts` ON (`glpi_slalevels`.`slts_id` = `glpi_slts`.`id`)
                WHERE `glpi_slalevels_tickets`.`date` < NOW()
                      AND `glpi_slalevels_tickets`.`tickets_id` = '$tickets_id'
                      AND `glpi_slts`.`type` = '$sltType'";

      $number = 0;
      do {
         if ($result = $DB->query($query)) {
            $number = $DB->numrows($result);
            if ($number == 1) {
               $data = $DB->fetch_assoc($result);
               self::doLevelForTicket($data, $sltType);
            }
         }
      } while ($number == 1);
   }

}
