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

class RuleTicketCollection extends RuleCollection {

   // From RuleCollection
   static $rightname                             = 'rule_ticket';
   public $use_output_rule_process_as_next_input = true;
   public $menu_option                           = 'ticket';


   /**
    * @param $entity (default 0)
   **/
   function __construct($entity=0) {
      $this->entity = $entity;
   }


   /**
    * @since version 0.84
    **/
   static function canView() {
      return Session::haveRightsOr(self::$rightname, array(READ, RuleTicket::PARENT));
   }


   function canList() {
      return static::canView();
   }


   function getTitle() {
      return __('Business rules for tickets');
   }


   /**
    * @see RuleCollection::preProcessPreviewResults()
   **/
   function preProcessPreviewResults($output) {

      $output = parent::preProcessPreviewResults($output);
      return Ticket::showPreviewAssignAction($output);
   }


   /**
    * @see RuleCollection::showInheritedTab()
   **/
   function showInheritedTab() {
      return (Session::haveRight(self::$rightname, RuleTicket::PARENT) && ($this->entity));
   }


   /**
    * @see RuleCollection::showChildrensTab()
   **/
   function showChildrensTab() {

      return (Session::haveRight(self::$rightname, READ)
              && (count($_SESSION['glpiactiveentities']) > 1));
   }


   /**
    * @see RuleCollection::prepareInputDataForProcess()
   **/
   function prepareInputDataForProcess($input, $params) {

      // Pass x-priority header if exists
      if (isset($input['_head']['x-priority'])) {
         $input['_x-priority'] = $input['_head']['x-priority'];
      }
      $input['_groups_id_of_requester'] = array();
      // Get groups of users
      if (isset($input['_users_id_requester'])) {
         foreach (Group_User::getUserGroups($input['_users_id_requester']) as $g) {
            $input['_groups_id_of_requester'][$g['id']] = $g['id'];
         }
      }
      return $input;
   }

}
?>