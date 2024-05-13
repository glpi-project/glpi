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

use \Glpi\DBAL\QueryExpression;

/// Class Ticket links
class Ticket_Ticket extends CommonITILObject_CommonITILObject
{
   // From CommonDBRelation
    public static $itemtype_1     = 'Ticket';
    public static $items_id_1     = 'tickets_id_1';
    public static $itemtype_2     = 'Ticket';
    public static $items_id_2     = 'tickets_id_2';

    public static $check_entity_coherency = false;

    public static function getTypeName($nb = 0)
    {
        return _n('Linked ticket', 'Linked tickets', $nb);
    }

    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {

        switch ($ma->getAction()) {
            case 'add':
                Toolbox::deprecated('Ticket_Ticket "add" massive action is deprecated. Use CommonITILObject_CommonITILObject "add" massive action.');
                Ticket_Ticket::dropdownLinks('link');
                printf(__('%1$s: %2$s'), Ticket::getTypeName(1), __('ID'));
                echo "&nbsp;<input type='text' name='tickets_id_1' value='' size='10'>\n";
                echo "<br><br>";
                echo "<br><br><input type='submit' name='massiveaction' class='btn btn-primary' value='" .
                           _sx('button', 'Post') . "'>";
                return true;
        }
        return parent::showMassiveActionsSubForm($ma);
    }

    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {

        switch ($ma->getAction()) {
            case 'add':
                Toolbox::deprecated('Ticket_Ticket "add" massive action is deprecated. Use CommonITILObject_CommonITILObject "add" massive action.');
                $input = $ma->getInput();
                $ticket = new Ticket();
                if (
                    isset($input['link'])
                    && isset($input['tickets_id_1'])
                ) {
                    if ($item->getFromDB($input['tickets_id_1'])) {
                        foreach ($ids as $id) {
                              $input2                          = [];
                              $input2['id']                    = $input['tickets_id_1'];
                              $input2['_link']['tickets_id_1'] = $id;
                              $input2['_link']['link']         = $input['link'];
                              $input2['_link']['tickets_id_2'] = $input['tickets_id_1'];
                            if ($item->can($input['tickets_id_1'], UPDATE)) {
                                if ($ticket->update($input2)) {
                                    $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                                } else {
                                    $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                                    $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                                }
                            } else {
                                $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                                $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                            }
                        }
                    }
                }
                return;
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }
}
