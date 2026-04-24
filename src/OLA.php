<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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
 * OLA Class
 * @since 9.2
 * @phpstan-type OLAFields array{id: int, name: string, entities_id: int, is_recursive: int, type: SLM::TTR|SLM::TTO, comment: string, number_time: int, use_ticket_calendar: int, calendars_id: int, date_mod: string, definition_time: string, end_of_working_day: int, date_creation: string, slms_id: int, groups_id: int}
 * @property OLAFields $fields
 **/
class OLA extends LevelAgreement
{
    protected static string $prefix = 'ola';
    protected static $levelclass = OlaLevel::class;
    protected static $levelticketclass = OlaLevel_Ticket::class;
    protected static array $forward_entity_to = [OlaLevel::class];

    /**
     * @param array<int> $_olas_id
     * @return array{0: array<int>, 1: array<int>}
     */
    public static function splitIdsByType(array $_olas_id): array
    {
        if ($_olas_id === []) {
            return [SLM::TTR => [],SLM::TTO =>  []];
        }
        $_ola = new static();
        $all_ids_ttr = array_column($_ola->find(['type' => SLM::TTR]), 'id');
        $all_ids_tto = array_column($_ola->find(['type' => SLM::TTO]), 'id');

        $input[SLM::TTR] = array_intersect($_olas_id, $all_ids_ttr);
        $input[SLM::TTO] = array_intersect($_olas_id, $all_ids_tto);

        return $input;
    }

    #[Override]
    public function prepareInputForAdd($input)
    {
        $groups_id = (int) ($input['groups_id'] ?? 0);

        return $this->validateGroupInput($groups_id) ? parent::prepareInputForAdd($input) : false;
    }

    #[Override]
    public function prepareInputForUpdate($input)
    {
        $groups_id = (int) ($input['groups_id'] ?? 0);

        return !isset($input['groups_id']) || $this->validateGroupInput($groups_id)
            ? parent::prepareInputForUpdate($input)
            : false;
    }


    /**
     * Remove OLA associations
     *
     * - remove ola's levels
     * - remove Item_Olas (deleteLevelAgreement())
     * - OlaLevel_Ticket (deleteLevelAgreement() (levels todo))
     * - remove rules
     */
    public function cleanDBonPurge()
    {
        // Clean levels
        $ola_fk = getForeignKeyFieldForItemType(static::class);
        /** @var OlaLevel $level */
        $level = getItemForItemtype(static::$levelclass);
        $level->deleteByCriteria([$ola_fk => $this->getID()]);

        // Clean levels todo
        $ticket = new Ticket();
        $ticket->deleteLevelAgreement(static::class, $this->getID(), $this->fields['type']);

        Rule::cleanForItemAction($this);
    }

    #[Override()]
    public static function getFieldNames($subtype)
    {
        throw new LogicException(__FUNCTION__ . '() is not supported by OLA - no olas field in tickets - fix the code');
    }

    /**
     * Add a level to do for a ticket
     *
     * Add an entry in slalevels_tickets | olalevels_tickets table
     * The level is set by $levels_id parameter
     *
     * @param int $levels_id
     * @param int $olas_id
     *
     * @return void
     **/
    public function addLevelToDo(Ticket $ticket, $levels_id, $olas_id)
    {
        $items_ola = new Item_Ola();
        if (!$items_ola->getFromDBByCrit(['olas_id' => $olas_id, 'items_id' => $ticket->fields['id'], 'itemtype' => Ticket::class])) {
            throw new LogicException('Item_ola not found');
        }
        $ola = new OLA();
        if (!$ola->getFromDB($olas_id)) {
            throw new LogicException('OLA not found #' . $olas_id);
        }

        $start_date = $items_ola->fields['start_time'];
        $waiting_duration = $items_ola->fields['waiting_time'];

        $date = $this->computeExecutionDate(
            $start_date,
            $levels_id,
            $waiting_duration
        );

        $_ticket = new Ticket();
        if (
            $_ticket->getFromDB($ticket->getID())
            && $this->levelCanBeAddedInLevelsTodo($ticket, $items_ola)
            && $date !== null
        ) {
            $toadd = [];
            $toadd['date'] = $date;
            $toadd['olalevels_id'] = $levels_id;
            $toadd['tickets_id'] = $ticket->fields["id"];
            /** @var OlaLevel_Ticket $levelticket */
            $levelticket = getItemForItemtype(static::$levelticketclass);
            $levelticket->add($toadd);
        }
    }

    #[Override]
    public static function deleteLevelsToDo(Ticket $ticket)
    {
        /** @var OlaLevel_Ticket $levelticket */
        $levelticket = getItemForItemtype(static::$levelticketclass);
        $levelticket->deleteByCriteria(['tickets_id' => $ticket->fields['id']]);
    }

    /**
     * remove all levels to do
     **/
    public static function deleteAllLevelsToDo(): void
    {
        /** @var OlaLevel_Ticket $levelticket */
        $levelticket = getItemForItemtype(static::$levelticketclass);
        $levelticket->deleteByCriteria([new QueryExpression('true')]);
    }

    #[Override]
    public static function getTypeName($nb = 0)
    {
        // Acronym, no plural
        return __('OLA');
    }

    #[Override]
    public static function getSectorizedDetails(): array
    {
        return ['config', SLM::class, self::class];
    }

    #[Override]
    public static function getLogDefaultServiceName(): string
    {
        return 'setup';
    }

    #[Override]
    public static function getIcon()
    {
        return SLM::getIcon();
    }

    public function showFormWarning()
    {
        global $CFG_GLPI;

        echo "<img src='" . htmlescape($CFG_GLPI["root_doc"]) . "/pics/warning.png' alt='" . __s('Warning') . "'>";
        echo __s('The internal time is recalculated when assigning the OLA');
    }

    public function getAddConfirmation(): array
    {
        return [
            __("The assignment of an OLA to a ticket causes the recalculation of the date."),
            __("Escalations defined in the OLA will be triggered under this new date."),
        ];
    }

    /**
     * Check if the level can be added in levels todo
     *
     * It means that the ola is not completed
     */
    private function levelCanBeAddedInLevelsTodo(Ticket $ticket, Item_Ola $items_ola): bool
    {
        if (
            $ticket->isDeleted()
            || $ticket->fields['status'] == CommonITILObject::CLOSED
            || $ticket->fields['status'] == CommonITILObject::SOLVED
            || !is_null($items_ola->fields['end_time'])
        ) {
            return false;
        }

        return true;
    }

    /**
     * Validate groups_id value
     *
     * - group must be set
     * - group must be allowed to be assigned to a ticket
     */
    private function validateGroupInput(int $groups_id): bool
    {
        if (0 === $groups_id) {
            Session::addMessageAfterRedirect(
                __s('You must select a group to associate with the OLA.'),
                false,
                ERROR
            );
            return false;
        }

        if (!$this->canGroupBeAssociated($groups_id)) {
            Session::addMessageAfterRedirect(
                sprintf(
                    __s('The group #%d is not allowed to be associated with an OLA. group.is_assign must be set to 1'),
                    $groups_id
                ),
                false,
                ERROR
            );
            return false;
        }

        return true;
    }

    private function canGroupBeAssociated(int $groups_id): bool
    {
        return (new Group())->getFromDBByCrit(['id' => $groups_id, 'is_assign' => 1]);
    }
}
