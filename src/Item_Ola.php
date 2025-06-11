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

class Item_Ola extends CommonDBRelation
{
    public static $itemtype_1 = 'itemtype'; // Only Ticket at the moment
    public static $items_id_1 = 'items_id';

    public static $itemtype_2 = OLA::class;
    public static $items_id_2 = 'olas_id';

    //    public static $rightname = 'device'; // @todoseb
    //      public static $checkItem_1_Rights = self::DONT_CHECK_ITEM_RIGHTS;  // @todoseb voir implications
    //      public static $checkItem_2_Rights = self::DONT_CHECK_ITEM_RIGHTS;  // @todoseb voir implications

    /**
     * Prepare the input for add
     *
     * add start_time and due_time values.
     * @param $input
     * @return array|false
     */
    public function prepareInputForAdd($input)
    {
        // @todoseb attention filter si TTO ou TTR ? -> test
        if (in_array(['due_time', 'start_time'], array_keys($input))) {
            throw new \RuntimeException('due_time and start_time are not allowed in the input. Values are computed.');
        }

        // get the related ola (cannot use getConnexityItem() ou getOnePeer() because it is not in the database yet)
        $_ola = new OLA();
        if (!$_ola->getFromDB($input[static::$items_id_2])) {
            throw new \RuntimeException('OLA not found #' . $input[static::$items_id_2]);
        }

        return parent::prepareInputForAdd([
            'due_time' => $_ola->computeDate($_SESSION['glpi_currenttime']),
            'start_time' => $_SESSION['glpi_currenttime'],
        ] + $input);
    }

    /**
     * Compute the OLA for a ticket
     *
     * @param \Ticket $ticket
     * @param mixed $olas_id must exist in the database
     */
    public static function compute(Ticket $ticket, mixed $olas_id): void
    {
        $item_ola = new self();
        if (!$item_ola->getFromDBByCrit(['items_id' => $ticket->getID(), 'itemtype' => $ticket::class, 'olas_id' => $olas_id])) {
            throw new \RuntimeException('Item_Ola not found for ticket #' . $ticket->getID() . ' and OLA #' . $olas_id);
        };

        $calendars_id = $ticket->getCalendar();
        $ola = $item_ola->getOla();
        $ola->setTicketCalendar($calendars_id);

        $item_ola_data['id'] = $item_ola->getID();

        // - update start_time skipped
        // nothing to do for TTO : done at creation of item_ola

        // update waiting_time (to do before due_time)
        // update waiting_time for TTR only, TTO is not impacted by waiting time
        if (
            $ola->fields['type'] === SLM::TTR
            && !is_null($ticket->fields['begin_waiting_date'])
            && ($key = array_search('status', $ticket->updates)) !== false
            && (
                $ticket->oldvalues['status'] == CommonITILObject::WAITING
                // From solved to another state than closed
                || (
                    in_array($ticket->oldvalues["status"], $ticket->getSolvedStatusArray())
                    && !in_array($ticket->fields["status"], $ticket->getClosedStatusArray())
                )
                // From closed to any open state
                || (
                    in_array($ticket->oldvalues["status"], $ticket->getClosedStatusArray())
                    && in_array($ticket->fields["status"], $ticket->getNotSolvedStatusArray())
                )
            )
        ) {
            $item_ola_data['waiting_time'] = $item_ola->fields['waiting_time'] + $ola->getActiveTimeBetween(
                $ticket->fields['begin_waiting_date'] ?? 0,
                $_SESSION["glpi_currenttime"]
            );
            $item_ola->fields['waiting_time'] = $item_ola_data['waiting_time'];
        }

        // - update due_time
        // update due_time (former internal_time_to_own, internal_time_to_resolve)
        $item_ola_data['due_time'] = $ola->computeDate(
            $item_ola->fields['start_time'],
            $item_ola->fields['waiting_time']
        );

        // - update end_time
        // for TTO, endtime is when the ticket is assigned to the dedicated group.
        if ($ola->fields['type'] === SLM::TTO) {
            if ($item_ola->fields['end_time'] == null
                &&
                (
                    $ticket->haveAGroup(CommonITILActor::ASSIGN, [$ola->fields['groups_id']])
                || self::ticketHasAnAssigneeOfOlaGroup($ticket, $ola)
                )
            ) {
                $item_ola->fields['end_time'] = Session::getCurrentTime();
                $item_ola_data['end_time'] = $item_ola->fields['end_time'];
            }
        }

        // For TTR, end_time is when the ticket is closed
        if ($ola->fields['type'] === SLM::TTR) {
            if ($ticket->isClosed() || $ticket->isSolved()) {
                $item_ola->fields['end_time'] = Session::getCurrentTime();
                $item_ola_data['end_time'] = $item_ola->fields['end_time'];
            }
        }

        if (!(new Item_Ola())->update($item_ola_data)) {
            throw new \Exception('Failed to update item_ola');
        }
        // since dates may be changed, rebuild the levels todo
        $ticket->manageOlaLevel($item_ola->fields['olas_id']);
        //
        //            $this->updates[] = "waiting_duration";
        //            $this->fields["waiting_duration"] += $delay_time;
        //
        //            // Reset begin_waiting_date
        //            $this->updates[] = "begin_waiting_date";
        //            $this->fields["begin_waiting_date"] = 'NULL';

    }

    public function getOla(): OLA
    {
        $item = $this->getConnexityItem(self::$itemtype_2, self::$items_id_2);
        if ($item instanceof OLA) {
            return $item;
        }

        throw new \RuntimeException('Linked OLA not found');
    }

    private static function ticketHasAnAssigneeOfOlaGroup(Ticket $ticket, OLA $ola): bool
    {
        $users_ids_of_ticket = array_column($ticket->getUsers(\CommonITILActor::ASSIGN), 'users_id');
        $users_of_dedicated_group = array_column(Group_User::getGroupUsers($ola->fields['groups_id']), 'id');

        return !empty(array_intersect(
            $users_ids_of_ticket,
            $users_of_dedicated_group
        ));
    }
}
