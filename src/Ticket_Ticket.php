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

use Glpi\DBAL\QueryExpression;

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
                echo htmlescape(sprintf(__('%1$s: %2$s'), Ticket::getTypeName(1), __('ID')));
                echo "&nbsp;<input type='text' name='tickets_id_1' value='' size='10'>\n";
                echo "<br><br>";
                echo "<br><br><input type='submit' name='massiveaction' class='btn btn-primary' value='"
                           . _sx('button', 'Post') . "'>";
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


    /**
     * Get linked tickets to a ticket
     *
     * @param integer $ID ID of the ticket id
     * @param boolean $check_view_rights check view rights
     *
     * @return array of linked tickets  array(id=>linktype)
     * @deprecated 11.0.0 Use CommonITILObject_CommonITILObject::getLinkedTo()
     **/
    public static function getLinkedTicketsTo($ID, bool $check_view_rights = false)
    {
        Toolbox::deprecated('Use "Ticket_Ticket::getLinkedTo()"');

        global $DB;

        // Make new database object and fill variables
        if (empty($ID)) {
            return [];
        }

        $table = self::getTable();
        $criteria = [
            'SELECT' => ["{$table}.*"],
            'FROM'   => $table,
            'WHERE'  => [
                'OR'  => [
                    'tickets_id_1' => $ID,
                    'tickets_id_2' => $ID,
                ],
            ],
        ];
        if ($check_view_rights && !Session::haveRight(Ticket::$rightname, Ticket::READALL)) {
            $ticket_table = Ticket::getTable();
            $criteria['LEFT JOIN'] = [
                $ticket_table => [
                    'ON' => new QueryExpression("{$ticket_table}.id=(CASE WHEN {$table}.tickets_id_1={$ID} THEN {$table}.tickets_id_2 ELSE {$table}.tickets_id_1 END)"),
                ],
            ];
            $unused_ref = [];
            $joins_str = Search::addDefaultJoin(Ticket::class, Ticket::getTable(), $unused_ref);
            if (!empty($joins_str)) {
                $db_it = new DBmysqlIterator($DB);
                $criteria['LEFT JOIN'] = [new QueryExpression($db_it->analyseJoins(['LEFT JOIN' => $criteria['LEFT JOIN']]) . ' ' . $joins_str)];
            }
            $criteria['WHERE'][] = new QueryExpression(Search::addDefaultWhere(Ticket::class));
        }
        $iterator = $DB->request($criteria);
        $tickets = [];

        foreach ($iterator as $data) {
            if ($data['tickets_id_1'] != $ID) {
                $tickets[$data['id']] = [
                    'link'         => $data['link'],
                    'tickets_id_1' => $data['tickets_id_1'],
                    'tickets_id'   => $data['tickets_id_1'],
                ];
            } else {
                $tickets[$data['id']] = [
                    'link'       => $data['link'],
                    'tickets_id' => $data['tickets_id_2'],
                ];
            }
        }

        ksort($tickets);
        return $tickets;
    }
}
