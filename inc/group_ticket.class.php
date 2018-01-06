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
 * Group_Ticket Class
 *
 * @since 0.85
 *
 * Relation between Groups and Tickets
**/
class Group_Ticket extends CommonITILActor {

   // From CommonDBRelation
   static public $itemtype_1 = 'Ticket';
   static public $items_id_1 = 'tickets_id';
   static public $itemtype_2 = 'Group';
   static public $items_id_2 = 'groups_id';


   function post_addItem() {

      switch ($this->input['type']) {  // Values from CommonITILObject::getSearchOptionsActors()
         case CommonITILActor::REQUESTER:
            $this->_force_log_option = 71;
            break;
         case CommonITILActor::OBSERVER:
            $this->_force_log_option = 65;
            break;
         case CommonITILActor::ASSIGN:
            $this->_force_log_option = 8;
            break;
      }
      parent::post_addItem();
      unset($this->_force_log_option);
   }
}
