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


use function Safe\strtotime;

/**
 * @phpstan-type ItemOlaData array{
 *     items_olas_id: int,
 *     name: string,
 *     entities_id: int,
 *     is_recursive: bool,
 *     type: int,
 *     comment: string,
 *     number_time: int,
 *     use_ticket_calendar: bool,
 *     calendars_id: int,
 *     date_mod: string,
 *     definition_time: string,
 *     end_of_working_day: string,
 *     date_creation: string,
 *     slms_id: int,
 *     olas_id: int,
 *     ola_type: SLM::TTR|SLM::TTO,
 *     start_time: string,
 *     due_time: string,
 *     end_time: string,
 *     waiting_time: string,
 *     is_late: string,
 *     class: string,
 *     item: Ticket,
 *     nextaction: false|OlaLevel_Ticket|SlaLevel_Ticket,
 *     level: false|LevelAgreementLevel,
 *     group_name: string}
 */
class Item_Ola extends CommonDBRelation
{
    public static $itemtype_1 = 'itemtype'; // Only Ticket at the moment
    public static $items_id_1 = 'items_id';

    public static $itemtype_2 = OLA::class;
    public static $items_id_2 = 'olas_id';

    /**
     * Prepare the input for add
     *
     * add start_time, due_time and is_late values.
     *
     * @param array{start_time: string, olas_id: int, itemtype: class-string<CommonITILObject>, items_id: int} $input
     * @return array|false
     */
    public function prepareInputForAdd($input)
    {
        if (in_array(['due_time'], array_keys($input))) {
            throw new RuntimeException('due_time is not allowed in the input. Values is computed.');
        }

        // get the related ola (cannot use getConnexityItem() ou getOnePeer() because it is not in the database yet)
        $_ola = new OLA();
        if (!$_ola->getFromDB($input[static::$items_id_2])) {
            throw new RuntimeException('OLA not found #' . $input[static::$items_id_2]);
        }

        return parent::prepareInputForAdd([
            'due_time' => $_ola->computeDate($input['start_time']),
            'start_time' => $input['start_time'],
            'is_late' => false,
        ] + $input);
    }

    /**
     * Compute the OLA data for a ticket
     *
     * @param Ticket $ticket
     * @param mixed $olas_id must exist in the database
     */
    public static function compute(Ticket $ticket, mixed $olas_id): void
    {
        $item_ola = new self();
        if (!$item_ola->getFromDBByCrit(['items_id' => $ticket->getID(), 'itemtype' => $ticket::class, 'olas_id' => $olas_id])) {
            throw new RuntimeException('Item_Ola not found for ticket #' . $ticket->getID() . ' and OLA #' . $olas_id);
        };

        $calendars_id = $ticket->getCalendar();
        $ola = $item_ola->getOla();
        $ola->setTicketCalendar($calendars_id);

        $item_ola_data = $item_ola->fields;
        $item_ola_data['id'] = $item_ola->getID(); // for final CommonDBRelation::update

        // - update start_time skipped
        // nothing to do for TTO : done at creation of item_ola

        // update waiting_time (to do before due_time)
        // update waiting_time for TTR & TTO
        // TTO waiting_time is added only if the OLA group is not assigned to the ticket
        if (
            (
                $item_ola_data['ola_type'] === SLM::TTR
                || (
                    $item_ola_data['ola_type'] === SLM::TTO
                    && !$ticket->haveAGroup(CommonITILActor::ASSIGN, [$ola->fields['groups_id']])
                )
            )
            && !is_null($ticket->fields['begin_waiting_date'])
            && in_array('status', $ticket->updates)
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
            $item_ola_data['waiting_time'] += $ola->getActiveTimeBetween(
                $ticket->fields['begin_waiting_date'] ?? 0,
                $_SESSION["glpi_currenttime"]
            );
        }

        // - update due_time
        // update due_time (former internal_time_to_own, internal_time_to_resolve)
        $item_ola_data['due_time'] = $ola->computeDate(
            $item_ola_data['start_time'],
            $item_ola_data['waiting_time']
        );

        // - update end_time
        // for TTO, endtime is when the ticket is assigned to the dedicated group.
        if ($item_ola_data['ola_type'] === SLM::TTO) {
            if ($item_ola_data['end_time'] == null
                &&
                (
                    $ticket->haveAGroup(CommonITILActor::ASSIGN, [$ola->fields['groups_id']])
                || self::ticketHasAnAssigneeOfOlaGroup($ticket, $ola)
                )
            ) {
                $item_ola_data['end_time'] = Session::getCurrentTime();
            }
        }

        // For TTR, end_time is when the ticket is closed
        // set it only if it is not already set
        if ($item_ola_data['ola_type'] === SLM::TTR && $item_ola_data['end_time'] == null) {
            if ($ticket->isClosed() || $ticket->isSolved()) {
                $item_ola_data['end_time'] = Session::getCurrentTime();
            }
        }

        // update current object
        foreach ($item_ola_data as $field => $value) {
            $item_ola->fields[$field] = $value;
        }

        // - update is_late
        $item_ola_data['is_late'] = (int) $item_ola->isLate($ticket);

        if (!(new Item_Ola())->update($item_ola_data)) {
            throw new Exception('Failed to update item_ola');
        }

        // since dates may be changed, rebuild the levels todo
        $ticket->manageOlaLevel($item_ola->fields['olas_id']);
    }

    public function getOla(): OLA
    {
        $item = $this->getConnexityItem(self::$itemtype_2, self::$items_id_2);
        if ($item instanceof OLA) {
            return $item;
        }

        throw new RuntimeException('Linked OLA not found');
    }

    /**
     * Get data from Item_Ola + linked OLA for a Ticket
     * @param Ticket $ticket
     *
     * @return array<ItemOlaData>
     */
    public function getDataFromDBForTicket(Ticket $ticket): array
    {
        /** array ola + item_ola datas */
        $merged_data = [];
        // each $item_ola_data row contains linked OLA fields + items_olas_id in 'linkid' field
        $olas_data = iterator_to_array(self::getListForItem($ticket));

        // merge data from ola dans items_ola
        foreach ($olas_data as $ola_data) {
            $merged_data[] = $this->fillItemOlaData($ola_data, $ticket);
        }

        return  $this->sort($merged_data);
    }

    private static function ticketHasAnAssigneeOfOlaGroup(Ticket $ticket, OLA $ola): bool
    {
        $users_ids_of_ticket = array_column($ticket->getUsers(CommonITILActor::ASSIGN), 'users_id');
        $users_of_dedicated_group = array_column(Group_User::getGroupUsers($ola->fields['groups_id']), 'id');

        return !empty(array_intersect(
            $users_ids_of_ticket,
            $users_of_dedicated_group
        ));
    }


    /**
     * @param Ticket $ticket
     * @param array<int> $olas_ids
     * @return array<ItemOlaData>
     */
    public function getDataFromOlasIdsForTicket(Ticket $ticket, array $olas_ids): array
    {
        /** array ola + item_ola datas */
        $merged_data = [];
        $ola = new OLA();

        // get ola data from DB - each $item_ola_data row contains linked OLA fields + items_olas_id in 'linkid' field
        $olas_in_db_data = array_values(iterator_to_array(self::getListForItem($ticket)));

        // complete with olas not associated to ticket - not yet in items_ola
        $fetched_olas_ids = array_column($olas_in_db_data, 'id');
        $olas_not_yet_fetched = array_diff(array_values($olas_ids), $fetched_olas_ids);
        $olas_missing_data = $olas_not_yet_fetched === [] ? [] : $ola->find(['id' => $olas_not_yet_fetched]);

        $olas_data = array_merge($olas_in_db_data, $olas_missing_data);

        // merge data from ola dans items_ola
        foreach ($olas_data as $ola_data) {
            $merged_data[] = $this->fillItemOlaData($ola_data, $ticket);
        }

        return $this->sort($merged_data);
    }

    /**
     * @todo better fetch item from $this, but for performance issue, we rely on existing Ticket instance.
     */
    private function isLate(Ticket $ticket): bool
    {
        $now_timestamp = strtotime(Session::getCurrentTime());
        $due_time_timestamp = strtotime($this->fields['due_time']);
        $end_time_timestamp = is_null($this->fields['end_time']) ? null : strtotime($this->fields['end_time']);

        // Ticket is WAITING : never late
        if ($ticket->fields['status'] == CommonITILObject::WAITING) {
            return false;
        }

        // end_time is after due_time
        if (!is_null($end_time_timestamp) && $end_time_timestamp > $due_time_timestamp) {
            return true;
        }

        // end time is not set, due_time is in the past
        if (is_null($end_time_timestamp) && $due_time_timestamp < $now_timestamp) {
            return true;
        }

        return false;
    }

    /**
     * @param array $ola_data fields from ola + possibly 'linkid' field representing items_olas_id
     * @param Ticket $ticket
     *
     * If 'linkid' is set, it will be used to populate the data from Item_Ola otherwise it will be filled with default values.
     *
     * @return ItemOlaData
     */
    private function fillItemOlaData(array $ola_data, Ticket $ticket): array
    {
        $_ola = new OLA();
        $_group = new Group();
        $_group->getFromDB($ola_data['groups_id']);
        $group_name = $_group->getName();
        // start with the ola data
        $_merged_data = $ola_data;

        // data defaults for item_ola
        $_merged_data['itemtype'] = $ticket::class;
        $_merged_data['items_id'] = $ticket->getID();
        $_merged_data['olas_id'] = $ola_data['id'];
        $_merged_data['start_time'] = 0;
        $_merged_data['due_time'] = 0;
        $_merged_data['end_time'] = 0;
        $_merged_data['waiting_time'] = 0;
        $_merged_data['items_olas_id'] = 0;
        // add data for template
        $_merged_data['class'] = OLA::class;
        $_merged_data['item'] = $ticket; // object, not just fields, functions used in template
        $_merged_data['nextaction'] = $_ola->getNextActionForTicket($ticket, $_merged_data['type']);
        $_merged_data['level'] = $_ola->getLevelFromAction($_merged_data['nextaction']);
        $_merged_data['group_name'] = $group_name;

        // if linkid is set (items_olas exists), use it to populate the data
        if (isset($ola_data['linkid'])) {
            $item_Ola = new static();
            if (!$item_Ola->getFromDB($_merged_data['linkid'])) {
                throw new LogicException('Item_Ola not found for linkid ' . $_merged_data['linkid']);
            }

            $_merged_data = array_merge($_merged_data, $item_Ola->fields);
            $_merged_data['items_olas_id'] = $ola_data['linkid'];
            $_merged_data['olas_id'] = $ola_data['id'];
            if ($ola_data['type'] !== $item_Ola->fields['ola_type']) {
                throw new LogicException('inconsistent type for Item_Ola #' . $item_Ola->getID());
            }
        }

        if (isset($_merged_data['id'])) {
            unset($_merged_data['id']); // removed to avoid confusion with items_olas_id in template, both olas_id and items_olas_id are defined above
        }

        return $_merged_data;
    }

    /**
     * Ola Cron Tasks
     *
     * - recompute ola data is_late field
     * - refresh ola levels_todo
     * - closes ticket (via compute()->doLevelForTicket())
     *
     * update items_ola which has no end_time of tickets
     * @todo also filter by ticket status ? to ovoid useless updates
     * @used-by CronTask
     *
     * @return int 1 if at least one item_ola has been processed, 0 otherwise
     */
    public static function cronOlaTicket(CronTask $cronTask): int
    {
        $io = new static();
        $ios = $io->find(['end_time' => null, 'itemtype' => Ticket::class]);

        OLA::deleteAllLevelsToDo(); // todo levels are rebuild in Item_Ola::compute()

        $processed = 0;
        foreach ($ios as $io) {
            $itil = getItemForItemtype($io['itemtype']);
            if (!$itil instanceof Ticket) {
                throw new RuntimeException('Item_Ola cron only works for Ticket at the moment. Implemetation needed.');
            }
            $itil->getFromDB($io['items_id']);
            static::compute($itil, $io['olas_id']);
            $processed++;
        }

        $cronTask->setVolume($processed);

        return (int) ($processed > 0);
    }

    /**
     * Sort the merged data by group then type (tto, then ttr)
     */
    private function sort(array $merged_data): array
    {
        usort($merged_data, function ($line_1, $line_2) {
            // group_name ordering
            $groupComparison = strcmp($line_1['group_name'], $line_2['group_name']);
            if ($groupComparison !== 0) {
                return $groupComparison;
            }

            // type ordering (priorité à tto sur ttr)
            $typePriority = [SLM::TTO => 1, SLM::TTR => 2];
            return $typePriority[$line_1['type']] <=> $typePriority[$line_2['type']];
        });

        return $merged_data;
    }
}
