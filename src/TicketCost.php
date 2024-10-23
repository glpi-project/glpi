<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

/**
 * TicketCost Class
 *
 * @since 0.84
 **/
class TicketCost extends CommonITILCost
{
   // From CommonDBChild
    public static $itemtype  = 'Ticket';
    public static $items_id  = 'tickets_id';

    public static $rightname        = 'ticketcost';

    public function post_updateItem($history = true)
    {
        parent::post_updateItem($history);

        $this->verifTCOItem();
    }

    public function post_addItem()
    {
        parent::post_addItem();

        $this->verifTCOItem();
    }

    public function post_purgeItem()
    {
        parent::post_purgeItem();

        $this->verifTCOItem();
    }

    private function verifTCOItem(): void
    {
        if ($this->fields['tickets_id']) {
            $item_ticket = new Item_Ticket();
            $item_tickets = $item_ticket->find([
                'tickets_id' => $this->fields['tickets_id']
            ]);
            foreach ($item_tickets as $it) {
                $this->updateTCOItem($it['itemtype'], $it['items_id']);
            }
        }
    }

    public function updateTCOItem(string $itemtype, int $items_id): void
    {
        $item = getItemForItemtype($itemtype);
        if ($item && $item->getFromDB($items_id)) {
            $item->update([
                'id' => $items_id,
                'ticket_tco' => Ticket::computeTco($item)
            ]);
        }
    }
}
