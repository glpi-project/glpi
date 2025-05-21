<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\PHPUnit\Tests\Glpi;

use OLA;
use SLA;
use SLM;

trait SLMTrait
{
    // default delay for tto & ttr ola
    // see @\LevelAgreement::getDefinitionTimeValues() for available unit values
    public const OLA_TTO_DELAY = [90, 'minute'];
    public const OLA_TTR_DELAY = [2, 'day'];
    public const SLA_TTO_DELAY = [180, 'minute'];
    public const SLA_TTR_DELAY = [3, 'day'];

    /**
     * @param array $data
     * @param int $ola_type
     * @param \Group|null $group
     * @param \SLM|null $slm
     *
     * @return array{ola: OLA, slm: SLM, group: \Group}
     */
    private function createOLA(array $data = [], int $ola_type = SLM::TTO, ?\Group $group = null, ?SLM $slm = null): array
    {
        assert(in_array($ola_type, array_keys(OLA::getTypes())));
        $slm ??= $this->createSLM();
        $group ??= $this->createGroup();

        [$amount, $unit] = match ($ola_type) {
            SLM::TTO => self::OLA_TTO_DELAY,
            SLM::TTR => self::OLA_TTR_DELAY,
        };

        $ola = $this->createItem(
            OLA::class,
            $data + [
                //                'id' => 0,
                'name' => 'OLA ' . time(),
                //                'entities_id' => 0,
                'is_recursive' => 1, // @todoseb voir avec quelqu'un le fonctionnement de l'entité 0 et de la récursivité. car la requete Item_Ola::getListForItem() dans \Ticket::getAssociatedOlas attend soit de la récursivité, soit une entité != 0
                'type' => $ola_type,
                'comment' => 'OLA comment ' . time(),
                'number_time' => $amount,
                'definition_time' => $unit,
                //                'use_ticket_calendar' => 0,
                //                'calendars_id' => 0,
                //                'date_mod' => null,
                //                'end_of_working_day' => 0,
                //                'date_creation' => null,
                'slms_id' => $slm->getID(),
                //                'groups_id' => $group->getID(),
            ]
        );

        return ['ola' => $ola, 'slm' => $slm, 'group' => $group];
    }

    /**
     * @param array $data
     * @param int $sla_type
     * @param \SLM|null $slm
     *
     * @return array{sla: SLA, slm: SLM}
     */
    private function createSLA(array $data = [], int $sla_type = SLM::TTO, ?SLM $slm = null): array
    {
        assert(in_array($sla_type, array_keys(SLA::getTypes())));
        $slm ??= $this->createSLM();

        [$amount, $unit] = match ($sla_type) {
            SLM::TTO => self::SLA_TTO_DELAY,
            SLM::TTR => self::SLA_TTR_DELAY,
        };

        $sla = $this->createItem(
            SLA::class,
            $data + [
                //                'id' => 0,
                'name' => 'SLA ' . time(),
                //                'entities_id' => 0,
                'is_recursive' => 1,
                'type' => $sla_type,
                'comment' => 'SLA comment ' . time(),
                'number_time' => $amount,
                'definition_time' => $unit,
                //                'use_ticket_calendar' => 0,
                //                'calendars_id' => 0,
                //                'date_mod' => null,
                //                'end_of_working_day' => 0,
                //                'date_creation' => null,
                'slms_id' => $slm->getID(),
                //                'groups_id' => $group->getID(),
            ]
        );

        return ['sla' => $sla, 'slm' => $slm];
    }

    private function createSLM(array $data = [], ?\Calendar $calendar = null): SLM
    {
        $calendar ??= $this->createCalendar();

        return $this->createItem(
            SLM::class,
            $data + [
                'id' => 0,
                'name' => 'SLM name ' . time(),
                //                'entities_id' => 0,
                //                'is_recursive' => 0,
                'comment' => 'Slm comment text',
                //                'use_ticket_calendar' => 0,
                'calendars_id' => $calendar->getID(),
                //                'date_mod' => null,
                //                'date_creation' => null,
            ]
        );
    }

    private function createGroup(): \Group
    {
        return $this->createItem(\Group::class, [
            //            'id' => 0,
            //            'entities_id' => 0,
            //            'is_recursive' => 0,
            'name' => 'Group name ' . time(),
            //            'code' => null,
            'comment' => 'Group comment text ' . time(),
            //            'ldap_field' => null,
            //            'ldap_value' => null,
            //            'ldap_group_dn' => null,
            //            'date_mod' => null,
            //            'groups_id' => 0,
            //            'completename' => null,
            //            'level' => 0,
            //            'ancestors_cache' => null,
            //            'sons_cache' => null,
            //            'is_requester' => 1,
            //            'is_watcher' => 1,
            //            'is_assign' => 1,
            //            'is_task' => 1,
            //            'is_notify' => 1,
            //            'is_itemgroup' => 1,
            //            'is_usergroup' => 1,
            //            'is_manager' => 1,
            //            'date_creation' => null,
            //            'recursive_membership' => 0,
            //            '2fa_enforced' => 0,
        ]);
    }

    /**
     * Calendar with today & tomorrow set a working day (9:00 to 19:00)
     */
    private function createCalendar(): \Calendar
    {
        $calendar = $this->createItem(\Calendar::class, ['name' => 'Test Calendar ' . time()]);
        // today
        $this->createItem(
            \CalendarSegment::class,
            [
                'calendars_id' => $calendar->getID(),
                'day' => (int) date('w'),
                'begin' => '09:00:00',
                'end' => '19:00:00',
            ]
        );
        // tomorrow
        $this->createItem(
            \CalendarSegment::class,
            [
                'calendars_id' => $calendar->getID(),
                'day' => (int) date('w') === 6 ? 0 : (int) date('w') + 1, // day of the week number
                'begin' => '09:00:00',
                'end' => '19:00:00',
            ]
        );

        return $calendar;
    }

}
