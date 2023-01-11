<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

    public function canCreateItem()
    {
        // Not item linked for closed tickets
        $ticket = new Ticket();
        if (
            $ticket->getFromDB($this->fields['tickets_id'])
            && in_array($ticket->fields['status'], $ticket->getClosedStatusArray())
        ) {
            return false;
        }

        return parent::canCreateItem();
    }

    public function prepareInputForAdd($input)
    {

       // Avoid duplicate entry
        if (
            countElementsInTable($this->getTable(), ['tickets_id' => $input['tickets_id'],
                'itemtype'   => $input['itemtype'],
                'items_id'   => $input['items_id']
            ]) > 0
        ) {
            return false;
        }

        $ticket = new Ticket();
        $ticket->getFromDB($input['tickets_id']);

       // Get item location if location is not already set in ticket
        if (empty($ticket->fields['locations_id'])) {
            if (($input["items_id"] > 0) && !empty($input["itemtype"])) {
                if ($item = getItemForItemtype($input["itemtype"])) {
                    if ($item->getFromDB($input["items_id"])) {
                        if ($item->isField('locations_id')) {
                             $ticket->fields['_locations_id_of_item'] = $item->fields['locations_id'];

                             // Process Business Rules
                             $rules = new RuleTicketCollection($ticket->fields['entities_id']);

                             $ticket->fields = $rules->processAllRules(
                                 $ticket->fields,
                                 $ticket->fields,
                                 ['recursive' => true]
                             );

                               unset($ticket->fields['_locations_id_of_item']);
                               $ticket->updateInDB(['locations_id']);
                        }
                    }
                }
            }
        }

        return parent::prepareInputForAdd($input);
    }


    /**
     * Print the HTML ajax associated item add
     *
     * @param $ticket Ticket object
     * @param $options   array of possible options:
     *    - id                  : ID of the ticket
     *    - _users_id_requester : ID of the requester user
     *    - items_id            : array of elements (itemtype => array(id1, id2, id3, ...))
     *
     * @return void
     **/
    public static function itemAddForm(Ticket $ticket, $options = [])
    {
        if ($options['id'] ?? 0 > 0) {
            // Get requester
            $class        = new $ticket->userlinkclass();
            $tickets_user = $class->getActors($options['id']);
            if (
                isset($tickets_user[CommonITILActor::REQUESTER])
                && (count($tickets_user[CommonITILActor::REQUESTER]) == 1)
            ) {
                foreach ($tickets_user[CommonITILActor::REQUESTER] as $user_id_single) {
                    $options['_users_id_requester'] = $user_id_single['users_id'];
                }
            }
        }
        parent::displayItemAddForm($ticket, $options);
    }

    /**
     * Print the HTML array for Items linked to a ticket
     *
     * @param $ticket Ticket object
     *
     * @return void
     **/
    public static function showForTicket(Ticket $ticket)
    {
        Toolbox::deprecated();
        static::showForObject($ticket);
    }

    public static function showForObject($ticket, $options = [])
    {
        // Get requester
        $class        = new $ticket->userlinkclass();
        $tickets_user = $class->getActors($ticket->fields['id']);
        $options['_users_id_requester'] = 0;
        if (
            isset($tickets_user[CommonITILActor::REQUESTER])
            && (count($tickets_user[CommonITILActor::REQUESTER]) == 1)
        ) {
            foreach ($tickets_user[CommonITILActor::REQUESTER] as $user_id_single) {
                $options['_users_id_requester'] = $user_id_single['users_id'];
            }
        }
        return parent::showForObject($ticket, $options);
    }
}
