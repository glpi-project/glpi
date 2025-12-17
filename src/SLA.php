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
 * @since 9.2
 */


/**
 * SLA Class
 **/
class SLA extends LevelAgreement
{
    /**
     * @var string
     */
    protected static $prefix            = 'sla';
    protected static $levelclass        = SlaLevel::class;
    protected static $levelticketclass  = SlaLevel_Ticket::class;
    protected static $forward_entity_to = [SlaLevel::class];

    /**
     * @param Ticket $ticket
     * @param int $levels_id
     * @return void
     */
    public function addLevelToDo(Ticket $ticket, $levels_id = 0)
    {
        $pre = static::$prefix;

        if (!$levels_id && isset($ticket->fields[$pre . 'levels_id_ttr'])) {
            $levels_id = $ticket->fields[$pre . "levels_id_ttr"];
        }

        if ($levels_id) {

            $date = $this->computeExecutionDate(
                $ticket->fields['date'],
                $levels_id,
                $ticket->fields[$pre . '_waiting_duration']
            );

            $toadd = [];
            if ($date !== null) {
                $toadd['date']           = $date;
                $toadd[$pre . 'levels_id'] = $levels_id;
                $toadd['tickets_id']     = $ticket->fields["id"];
                $levelticket             = getItemForItemtype(static::$levelticketclass);
                $levelticket->add($toadd);
            }
        }
    }

    /**
     * remove a level to do for a ticket
     *
     * @param Ticket $ticket object
     *
     * @return void
     **/
    public static function deleteLevelsToDo(Ticket $ticket)
    {
        /** @var DBmysql $DB */
        global $DB;

        $ticketfield = static::$prefix . "levels_id_ttr";

        if ($ticket->fields[$ticketfield] > 0) {
            $levelticket = getItemForItemtype(static::$levelticketclass);
            $iterator = $DB->request([
                'SELECT' => 'id',
                'FROM'   => $levelticket::getTable(),
                'WHERE'  => ['tickets_id' => $ticket->fields['id']],
            ]);

            foreach ($iterator as $data) {
                $levelticket->delete(['id' => $data['id']]);
            }
        }
    }

    public static function getTypeName($nb = 0)
    {
        // Acronym, no plural
        return __('SLA');
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

    public function cleanDBonPurge()
    {
        /** @var DBmysql $DB */
        global $DB;

        // Clean levels
        $fk        = getForeignKeyFieldForItemType(static::class);
        $level     = getItemForItemtype(static::$levelclass);
        $level->deleteByCriteria([$fk => $this->getID()]);

        // Update tickets : clean SLA
        [, $laField] = static::getFieldNames($this->fields['type']);
        $iterator =  $DB->request([
            'SELECT' => 'id',
            'FROM'   => 'glpi_tickets',
            'WHERE'  => [$laField => $this->fields['id']],
        ]);

        if (count($iterator)) {
            $ticket = new Ticket();
            foreach ($iterator as $data) {
                $ticket->deleteLevelAgreement(static::class, $data['id'], $this->fields['type']);
            }
        }

        Rule::cleanForItemAction($this);
    }

    public function showFormWarning() {}

    public function getAddConfirmation(): array
    {
        return [
            __("The assignment of a SLA to a ticket causes the recalculation of the date."),
            __("Escalations defined in the SLA will be triggered under this new date."),
        ];
    }
}
