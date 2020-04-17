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
 * TicketCost Class
 *
 * @since 0.84
**/
class TicketCost extends CommonITILCost {

   // From CommonDBChild
   static public $itemtype  = 'Ticket';
   static public $items_id  = 'tickets_id';

   static $rightname        = 'ticketcost';

   /**
   * Given a tickets_id, updates the ticket_tco field of linked items with
   * the computed TCO (glpi_ticketcosts).
   *
   * @param $tickets_id integer The primary key of the ticket.
   *
   * @return bool True if any linked item was updated. False if no changes.
   **/
   static function updateLinkedItemsTCO($tickets_id) {
      $returnValue = false;
      if (is_numeric($tickets_id) && $tickets_id > 0) {
         $item_ticket = new Item_Ticket();
         $ticketLinkedItems = $item_ticket->find(['tickets_id' => $tickets_id]);
         foreach ($ticketLinkedItems as $linkedItem) {
            if ($linkedItem && ($item = getItemForItemtype($linkedItem['itemtype']))) {
               if ($item->getFromDB($linkedItem['items_id'])) {
                  $newinput               = [];
                  $newinput['id']         = $linkedItem['items_id'];
                  $newinput['ticket_tco'] = Ticket::computeTco($item);
                  $item->update($newinput);
                  $returnValue = true;
               }
            }
         }
      }
      return $returnValue;
   }

   /**
   * Given an items_id and itemtype, updates the ticket_tco field of the item
   * computed TCO (glpi_ticketcosts).
   *
   * @param $items_id integer The primary key of the item.
   * @param $itemtype string The item type so glpi knows where to look.
   *
   * @return bool True if item was updated. False if no changes.
   **/
   static function updateItemsTCO($items_id, $itemtype) {
      $returnValue = false;
      if (is_numeric($items_id) && $itemtype) {
         if ($item = getItemForItemtype($itemtype)) {
            if ($item->getFromDB($items_id)) {
               $newinput               = [];
               $newinput['id']         = $items_id;
               $newinput['ticket_tco'] = Ticket::computeTco($item);
               $item->update($newinput);
               $returnValue = true;
            }
         }
      }
      return $returnValue;
   }

}
