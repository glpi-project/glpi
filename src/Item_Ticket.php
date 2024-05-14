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

    #[Override]
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        // The Item_Ticket tab is only supported on CommonDBTM objects
        if (!($item instanceof CommonDBTM)) {
            return "";
        }

        // This tab might be hidden on assets
        if (in_array($item::getType(), $CFG_GLPI['asset_types']) && !$this->shouldDisplayTabForAsset($item)) {
            return '';
        }

        $nb = 0;
        if (!($item instanceof Ticket)) {
            $title = Ticket::getTypeName(Session::getPluralNumber());

            if ($_SESSION['glpishow_count_on_tabs']) {
                // Direct one
                $nb = countElementsInTable(
                    'glpi_items_tickets',
                    [
                        'INNER JOIN' => [
                            'glpi_tickets' => [
                                'FKEY' => [
                                    'glpi_items_tickets' => 'tickets_id',
                                    'glpi_tickets'       => 'id'
                                ]
                            ]
                        ],
                        'WHERE' => [
                            'itemtype' => $item->getType(),
                            'items_id' => $item->getID(),
                            'is_deleted' => 0
                        ]
                    ]
                );

                // Linked items
                $linkeditems = $item->getLinkedItems();
                if (count($linkeditems)) {
                    foreach ($linkeditems as $type => $tab) {
                        $nb += countElementsInTable(
                            'glpi_items_tickets',
                            [
                                'INNER JOIN' => [
                                    'glpi_tickets' => [
                                        'FKEY' => [
                                            'glpi_items_tickets' => 'tickets_id',
                                            'glpi_tickets'       => 'id'
                                        ]
                                    ]
                                ],
                                'WHERE' => [
                                    'itemtype' => $type,
                                    'items_id' => $tab,
                                    'is_deleted' => 0
                                ]
                            ]
                        );
                    }
                }
            }
        } else {
            $title = _n('Item', 'Items', Session::getPluralNumber());
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = self::countForMainItem($item);
            }
        }

        return self::createTabEntry($title, $nb, $item::getType());
    }

    #[Override]
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        // The Item_Ticket tab is only supported on CommonDBTM objects
        if (!($item instanceof CommonDBTM)) {
            return "";
        }

        if ($item instanceof Ticket) {
            self::showForObject($item);
            return true;
        } else {
            Ticket::showListForItem($item, $withtemplate);
            return true;
        }
    }
}
