<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
 * Item_Ticket Class
 *
 *  Relation between Tickets and Items
 **/
class Item_Ticket extends CommonItilObject_Item
{
    // From CommonDBRelation
    public static $itemtype_1          = 'Ticket';
    public static $items_id_1          = 'tickets_id';

    public static $itemtype_2          = 'itemtype';
    public static $items_id_2          = 'items_id';
    public static $checkItem_2_Rights  = self::HAVE_VIEW_RIGHT_ON_ITEM;

    public static function getTypeName($nb = 0)
    {
        return _n('Ticket item', 'Ticket items', $nb);
    }

    public function canCreateItem(): bool
    {
        // Not item linked for closed tickets
        $ticket = new Ticket();
        if (
            $ticket->getFromDB($this->fields['tickets_id'])
            && in_array($ticket->fields['status'], $ticket::getClosedStatusArray())
        ) {
            return false;
        }

        return parent::canCreateItem();
    }
}
