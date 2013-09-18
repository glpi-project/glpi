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
 * TicketValidation class
 */
class TicketValidation  extends CommonITILValidation {

   // From CommonDBChild
   static public $itemtype           = 'Ticket';
   static public $items_id           = 'tickets_id';

   static $rightname                 = 'ticketvalidation';

   const CREATEREQUEST               = 1024;
   const CREATEINCIDENT              = 2048;
   const VALIDATEREQUEST             = 4096;
   const VALIDATEINCIDENT            = 8192;

   static function getCreateRights() {
      return array(static::CREATEREQUEST, static::CREATEINCIDENT);
   }

   static function getValidateRights() {
      return array(static::VALIDATEREQUEST, static::VALIDATEINCIDENT);
   }
   
   /**
    * @since version 0.85
   **/
   function canCreateItem() {

      if ($this->canChildItem('canViewItem', 'canView')) {
          $ticket = new Ticket();
          if ($ticket->getFromDB($this->fields['tickets_id'])) {
              if ($ticket->fields['type'] == Ticket::INCIDENT_TYPE) {
                 return Session::haveRight(self::$rightname, self::CREATEINCIDENT);
              }
              if ($ticket->fields['type'] == Ticket::DEMAND_TYPE) {
                 return Session::haveRight(self::$rightname, self::CREATEREQUEST);
              }
          }
      }
   }

   /**
    * @since version 0.85
    *
    * @see commonDBTM::getRights()
    **/
   function getRights($interface='central') {

      $values = parent::getRights();
      unset($values[UPDATE], $values[CREATE], $values[READ]);

      $values[self::CREATEREQUEST]    = array('short' => __('Create for request'),
                                              'long'  => __('Create a validation request for a request'));
      $values[self::CREATEINCIDENT]   = array('short' => __('Create for incident'),
                                              'long'  => __('Create a validation request for an incident'));
      $values[self::VALIDATEREQUEST]  = __('Validate a request');
      $values[self::VALIDATEINCIDENT] = __('Validate an incident');

      if ($interface == 'helpdesk') {
         unset($values[PURGE]);
      }

      return $values;
   }

   
   static function alertValidation(Ticket $ticket, $type){
      global $CFG_GLPI;
      
      $status = Ticket::getClosedStatusArray();
      $closed = $status[0];
      $status = Ticket::getSolvedStatusArray();
      $solved = $status[0];
      
      $message = __("This ticket is waiting for approval, do you really want to resolve or close it?");

      switch($type){
         case 'ticket':
            Html::scriptStart();
            echo "$('[name=\"status\"]').change(function() {
                     var status_ko = 0;
                     var input_status = $(this).val();
                     if(input_status != undefined){
                        if ((input_status == ".$solved."
                              || input_status == ".$closed.")
                                 && input_status != ".$ticket->fields['status']."){
                           status_ko = 1;
                        }
                     }
                     if (status_ko == 1 && '".$ticket->fields['global_validation']."' == 'waiting') {
                        alert('".$message."');
                     }
                  });";
            echo Html::scriptEnd();
            break;
      
         case 'solution':
            if($ticket->fields['status'] != $solved
               && $ticket->fields['status'] != $closed 
                  && $ticket->fields['global_validation'] == 'waiting') {
               Html::displayTitle($CFG_GLPI['root_doc']."/pics/warning.png", $message, $message);
            }
            break;
      }

   }
}
?>