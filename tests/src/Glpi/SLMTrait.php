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

namespace Glpi\Tests\Glpi;

use Calendar;
use CronTask;
use DateInterval;
use Group;
use OLA;
use SLA;
use SlaLevel_Ticket;
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
     * @param Group|null $group
     * @param SLM|null $slm
     *
     * @return array{ola: OLA, slm: SLM, group: Group}
     */
    private function createOLA(array $data = [], int $ola_type = SLM::TTO, ?Group $group = null, ?SLM $slm = null): array
    {
        assert(in_array($ola_type, array_keys(OLA::getTypes())));
        $slm ??= $this->createSLM();
        $group ??= getItemByTypeName(Group::class, '_test_group_1');

        [$amount, $unit] = match ($ola_type) {
            SLM::TTO => self::OLA_TTO_DELAY,
            SLM::TTR => self::OLA_TTR_DELAY,
        };

        $ola = $this->createItem(
            OLA::class,
            $data + [
                'name' => 'OLA ' . time(),
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
                'type' => $ola_type,
                'comment' => 'OLA comment ' . time(),
                'number_time' => $amount,
                'definition_time' => $unit,
                'slms_id' => $slm->getID(),
            ]
        );

        return ['ola' => $ola, 'slm' => $slm, 'group' => $group];
    }

    /**
     * @param array $data
     * @param int $sla_type
     * @param SLM|null $slm
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
                'name' => 'SLA ' . time(),
                'is_recursive' => 1,
                'type' => $sla_type,
                'comment' => 'SLA comment ' . time(),
                'number_time' => $amount,
                'definition_time' => $unit,
                'slms_id' => $slm->getID(),
            ]
        );

        return ['sla' => $sla, 'slm' => $slm];
    }

    private function createSLM(array $data = [], ?Calendar $calendar = null): SLM
    {
        $calendar ??= getItemByTypeName(Calendar::class, 'Default');

        return $this->createItem(
            SLM::class,
            $data + [
                'id' => 0,
                'name' => 'SLM name ' . time(),
                'comment' => 'Slm comment text',
                'calendars_id' => $calendar->getID(),
            ]
        );
    }

    private function runSlaCron(): void
    {
        SlaLevel_Ticket::cronSlaTicket(getItemByTypeName(CronTask::class, 'slaticket'));
    }

    /**
     * beware that DateInterval expects self::xxx to be ['minutes, etc ... doc à compléter) @todoseb
     *
     * @return DateInterval
     */
    private function getDefaultOlaTtoDelayInterval(): DateInterval
    {
        [$amount, $unit] = self::OLA_TTO_DELAY;

        return new DateInterval(sprintf('PT%d%s', $amount, strtoupper(substr($unit, 0, 1))));
    }

    private function getDefaultOlaTtrDelayInterval(): DateInterval
    {
        [$amount, $unit] = self::OLA_TTR_DELAY;

        return new DateInterval(sprintf('P%d%s', $amount, strtoupper(substr($unit, 0, 1))));
    }

    private function getDefaultSlaTtoDelayInterval(): DateInterval
    {
        [$amount, $unit] = self::SLA_TTO_DELAY;

        return new DateInterval(sprintf('PT%d%s', $amount, strtoupper(substr($unit, 0, 1))));
    }

    private function getDefaultSlaTtrDelayInterval(): DateInterval
    {
        [$amount, $unit] = self::SLA_TTR_DELAY;

        return new DateInterval(sprintf('P%d%s', $amount, strtoupper(substr($unit, 0, 1))));
    }
}
