<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Class Group_Ticket
class Group_Ticket extends CommonDBRelation {

   // From CommonDBRelation
   public $itemtype_1 = 'Ticket';
   public $items_id_1 = 'tickets_id';
   public $itemtype_2 = 'Group';
   public $items_id_2 = 'groups_id';


   static function getTicketGroups($tickets_id) {
      global $DB;

      $groups = array();
      $query = "SELECT `glpi_groups_tickets`.*
                FROM `glpi_groups_tickets`
                WHERE `tickets_id` = '$tickets_id'";

      foreach ($DB->request($query) as $data) {
         $groups[$data['type']][$data['groups_id']] = $data;
      }
      return $groups;
   }


   function post_deleteFromDB() {

      $t = new Ticket();
      $t->updateDateMod($this->fields['tickets_id']);
      parent::post_deleteFromDB();
   }


   function post_addItem() {


      $t = new Ticket();
      $no_stat_computation = true;
      if ($this->input['type']==Ticket::ASSIGN) {
         $no_stat_computation = false;
      }
      $t->updateDateMod($this->fields['tickets_id'],$no_stat_computation);

      parent::post_addItem();
   }

}

?>
