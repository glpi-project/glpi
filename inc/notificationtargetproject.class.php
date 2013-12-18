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

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * NotificationTargetTicket Class
**/
class NotificationTargetProject extends NotificationTarget {

   /**
    *Get events related to tickets
   **/
   function getEvents() {

      $events = array('new'               => __('New project'),
                      'update'            => __('Update of a project'),
                      'delete'            => __('Deletion of a project'));
      asort($events);
      return $events;
   }

   function getAdditionalTargets($event='') {
      $this->addTarget(Notification::MANAGER_USER,
                        __('Manager'));
      $this->addTarget(Notification::MANAGER_GROUP,
                        __('Manager group'));
      $this->addTarget(Notification::MANAGER_GROUP_SUPERVISOR,
                        __('Manager group supervisor'));
      $this->addTarget(Notification::MANAGER_GROUP_WITHOUT_SUPERVISOR,
                        __('Manager group without supervisor'));
      $this->addTarget(Notification::TEAM_USER,
                        __('Project team user'));
      $this->addTarget(Notification::TEAM_GROUP,
                        __('Project team group'));
      $this->addTarget(Notification::TEAM_GROUP_SUPERVISOR,
                        __('Project team group supervisor'));
      $this->addTarget(Notification::TEAM_GROUP_WITHOUT_SUPERVISOR,
                        __('Project team group without supervisor'));
      $this->addTarget(Notification::TEAM_CONTACT,
                        __('Project team contact'));
      $this->addTarget(Notification::TEAM_SUPPLIER,
                        __('Project team supplier'));
   }
   
   function getSpecificTargets($data, $options) {

      //Look for all targets whose type is Notification::ITEM_USER
      switch ($data['type']) {
         case Notification::USER_TYPE :

            switch ($data['items_id']) {
               case Notification::MANAGER_USER :
                  $this->getItemAuthorAddress();
                  break;

               //Send to the manager group of the project
               case Notification::MANAGER_GROUP :
                  $this->getItemGroupAddress();
                  break;

               //Send to the manager group supervisor of the project
               case Notification::MANAGER_GROUP_SUPERVISOR :
                  $this->getItemGroupSupervisorAddress();
                  break;

               //Send to the manager group without supervisor of the project
               case Notification::MANAGER_GROUP_WITHOUT_SUPERVISOR :
                  $this->getItemGroupWithoutSupervisorAddress();
                  break;
                  
               //Send to the users in project team
               case Notification::TEAM_USER :
                  $this->getTeamUsers();
                  break;
                  
               //Send to the groups in project team
               case Notification::TEAM_GROUP :
                  $this->getTeamGroups(0);
                  break;

               //Send to the group supervisors in project team
               case Notification::TEAM_GROUP_SUPERVISOR :
                  $this->getTeamGroups(1);
                  break;

               //Send to the groups without supervisors in project team
               case Notification::TEAM_GROUP_WITHOUT_SUPERVISOR :
                  $this->getTeamGroups(2);
                  break;

               //Send to the contact in project team
               case Notification::TEAM_CONTACT :
                  $this->getTeamContacts();
                  break;

                  //Send to the contact in project team
               case Notification::TEAM_SUPPLIER :
                  $this->getTeamSuppliers();
                  break;
                  
            }
         }
   }

   /**
    * Add team users to the notified user list
    *
   **/
   function getTeamUsers() {
      global $DB;

      $query = "SELECT `items_id`
                FROM `glpi_projectteams`
                WHERE `glpi_projectteams`.`itemtype` = 'User'
                     AND `glpi_projectteams`.`projects_id` = '".$this->obj->fields["id"]."'";
      $user = new User;
      foreach ($DB->request($query) as $data) {
         if ($user->getFromDB($data['items_id'])) {
            $this->addToAddressesList(array('language' => $user->getField('language'),
                                            'users_id' => $user->getField('id')));
         }
      }
   }
   
   /**
    * Add team groups to the notified user list
    * @param $manager      0 all users, 1 only supervisors, 2 all users without supervisors
    *
   **/
   function getTeamGroups($manager) {
      global $DB;

      $query = "SELECT `items_id`
                FROM `glpi_projectteams`
                WHERE `glpi_projectteams`.`itemtype` = 'Group'
                     AND `glpi_projectteams`.`projects_id` = '".$this->obj->fields["id"]."'";
      foreach ($DB->request($query) as $data) {
         $this->getAddressesByGroup($manager, $data['items_id']);
      }
   }

   /**
    * Add team contacts to the notified user list
    *
   **/
   function getTeamContacts() {
      global $DB, $CFG_GLPI;

      $query = "SELECT `items_id`
                FROM `glpi_projectteams`
                WHERE `glpi_projectteams`.`itemtype` = 'Contact'
                     AND `glpi_projectteams`.`projects_id` = '".$this->obj->fields["id"]."'";
      $contact = new Contact();
      foreach ($DB->request($query) as $data) {
         if ($contact->getFromDB($data['items_id'])) {
            $this->addToAddressesList(array("email"    => $contact->fields["email"],
                                            "name"     => $contact->fields["name"]." ".$contact->fields["firstname"],
                                            "language" => $CFG_GLPI["language"],
                                            'usertype' => NotificationTarget::ANONYMOUS_USER));
         }
      }
   }
   /**
    * Add team suppliers to the notified user list
    *
   **/
   function getTeamSuppliers() {
      global $DB, $CFG_GLPI;

      $query = "SELECT `items_id`
                FROM `glpi_projectteams`
                WHERE `glpi_projectteams`.`itemtype` = 'Supplier'
                     AND `glpi_projectteams`.`projects_id` = '".$this->obj->fields["id"]."'";
      $supplier = new Supplier();
      foreach ($DB->request($query) as $data) {
         if ($supplier->getFromDB($data['items_id'])) {
            $this->addToAddressesList(array("email"    => $supplier->fields["email"],
                                            "name"     => $supplier->fields["name"],
                                            "language" => $CFG_GLPI["language"],
                                            'usertype' => NotificationTarget::ANONYMOUS_USER));
         }
      }
   }    
}
?>