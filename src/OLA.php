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
 * OLA Class
 * @since 9.2
 **/
class OLA extends LevelAgreement
{
    protected static $prefix = 'ola';

    public function prepareInputForAdd($input)
    {
        $groups_id = (int) ($input['groups_id'] ?? 0);

        return $this->validateGroupInput($groups_id) ? parent::prepareInputForAdd($input) : false;
    }

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
        /** @var \DBmysql $DB */
        global $DB;

        // Clean levels
        $ola_fk = getForeignKeyFieldForItemType(static::class);
        $level = new static::$levelclass();
        $level->deleteByCriteria([$ola_fk => $this->getID()]);

        // Clean levels todo
        $ticket = new Ticket();
        $ticket->deleteLevelAgreement(static::class, $this->getID(), $this->fields['type']);

        Rule::cleanForItemAction($this);
    }

    //    protected static $prefixticket = 'internal_'; // @todoseb plus utilisé, doit pouvoir dégager, à maintenir pour compatibilité ascendante ?
    protected static $levelclass = 'OlaLevel';
    protected static $levelticketclass = 'OlaLevel_Ticket';
    protected static $forward_entity_to = ['OlaLevel'];

    /**
     * Get table fields
     *
     * @param integer $subtype of OLA/SLA, can be SLM::TTO or SLM::TTR
     *
     * @return array of 'date' and 'sla' field names
     */
    public static function getFieldNames($subtype)
    {
        throw new LogicException(__FUNCTION__ . '() is not supported by OLA - no olas field in tickets - fix the code');
    }

    /**
     * Add a level to do for a ticket
     *
     * Add an entry in slalevels_tickets | olalevels_tickets table
     * The level is set by $levels_id parameter or the current level set in slalevels_id_ttr | olalevels_id_ttr (if set)
     *
     * @param Ticket $ticket
     * @param integer $levels_id
     * @param integer $olas_id
     *
     * @return void
     **/
    public function addLevelToDo(Ticket $ticket, $levels_id, $olas_id)
    {
        $pre = static::$prefix;

        $items_ola = new Item_Ola();
        if (!$items_ola->getFromDBByCrit(['olas_id' => $olas_id, 'items_id' => $ticket->fields['id'], 'itemtype' => Ticket::class])) {
            throw new \LogicException('Item_ola not found');
        }
        $ola = new OLA();
        if (!$ola->getFromDB($olas_id)) {
            throw new \LogicException('OLA not found #' . $olas_id);
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
            $_ticket->getFromDB($ticket->getID()) &&
            $this->levelCanBeAddedInLevelsTodo($ticket, $ola->fields['type']) &&
            $date !== null
        ) {
            $toadd = [];
            $toadd['date'] = $date;
            $toadd[$pre . 'levels_id'] = $levels_id;
            $toadd['tickets_id'] = $ticket->fields["id"];
            $levelticket = new static::$levelticketclass();
            $levelticket->add($toadd);
        }
    }

    /**
     * remove all levels to do for a ticket
     *
     * @param Ticket $ticket object
     *
     * @return void
     **/
    public static function deleteLevelsToDo(Ticket $ticket)
    {
        /** @var \DBmysql $DB */
        global $DB;

        // on original code, we checked that the ticket has an associated OLA_TTR then we delete all levels todo (TTR + TTO)
        // but the function is likely always called if the ticket has an OLA TTR
        // so it's performed all the time
        // on this version I remove the check, all levels todo are deleted, whatever OLA type is.
        $levelticket = new static::$levelticketclass();
        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM' => $levelticket::getTable(),
            'WHERE' => ['tickets_id' => $ticket->fields['id']],
        ]);

        foreach ($iterator as $data) {
            $levelticket->delete(['id' => $data['id']]);
        }
    }

    public static function getTypeName($nb = 0)
    {
        // Acronym, no plural
        return __('OLA');
    }

    public static function getSectorizedDetails(): array
    {
        return ['config', SLM::class, self::class];
    }

    public static function getLogDefaultServiceName(): string
    {
        return 'setup';
    }

    public static function getIcon()
    {
        return SLM::getIcon();
    }

    public function showFormWarning()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        echo "<img src='" . $CFG_GLPI["root_doc"] . "/pics/warning.png' alt='" . __s('Warning') . "'>";
        echo __s('The internal time is recalculated when assigning the OLA');
    }

    public function getAddConfirmation(): array
    {
        return [
            __("The assignment of an OLA to a ticket causes the recalculation of the date."),
            __("Escalations defined in the OLA will be triggered under this new date."),
        ];
    }

    private function levelCanBeAddedInLevelsTodo(Ticket $ticket, int $olaType): bool
    {
        if (
            $ticket->isDeleted() ||
            $ticket->fields['status'] == CommonITILObject::CLOSED ||
            $ticket->fields['status'] == CommonITILObject::SOLVED ||
            ($olaType == SLM::TTO && $ticket->fields['takeintoaccount_delay_stat'] > 0)
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
                __('You must select a group to associate with the OLA.'),
                false,
                ERROR
            );
            return false;
        }

        if (!$this->canGroupBeAssociated($groups_id)) {
            Session::addMessageAfterRedirect(
                sprintf(
                    __('The group #%s is not allowed to be associated with an OLA. group.is_assign must be set to 1'),
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
        // @todoseb maybe not the correct way to check ?
        return (new Group())->getFromDBByCrit(['id' => $groups_id, 'is_assign' => 1]);
    }
}
