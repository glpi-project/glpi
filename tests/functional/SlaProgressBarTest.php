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

namespace tests\units;

use Calendar;
use CalendarSegment;
use Entity;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\Glpi\SLMTrait;
use SLA;
use SLM;
use Ticket;

use function Safe\date;
use function Safe\preg_match;
use function Safe\strtotime;

class SlaProgressBarTest extends DbTestCase
{
    use SLMTrait;

    /**
     * "Time to resolve + Progress" must use the entity's calendar, not a naive 24/7 diff,
     * when the SLA uses the "Calendar of the ticket" strategy.
     */
    public function testProgressBarUsesEntityCalendarWhenSlaUsesTicketCalendar()
    {
        $this->login();

        $entities_id = getItemByTypeName('Entity', '_test_root_entity', true);

        // Business hours: Monday to Friday, 08:00-18:00 (10h/day, closed on week-ends).
        $calendar = $this->createItem(Calendar::class, ['name' => 'SlaProgressBarTest calendar']);
        $this->createItems(CalendarSegment::class, [
            ['calendars_id' => $calendar->getID(), 'day' => 1, 'begin' => '08:00:00', 'end' => '18:00:00'],
            ['calendars_id' => $calendar->getID(), 'day' => 2, 'begin' => '08:00:00', 'end' => '18:00:00'],
            ['calendars_id' => $calendar->getID(), 'day' => 3, 'begin' => '08:00:00', 'end' => '18:00:00'],
            ['calendars_id' => $calendar->getID(), 'day' => 4, 'begin' => '08:00:00', 'end' => '18:00:00'],
            ['calendars_id' => $calendar->getID(), 'day' => 5, 'begin' => '08:00:00', 'end' => '18:00:00'],
        ]);

        // Assign this calendar explicitly to the test entity.
        $this->updateItem(Entity::class, $entities_id, [
            'calendars_strategy' => 0,
            'calendars_id'       => $calendar->getID(),
        ]);

        // "Calendar of the ticket" strategy; raw add() since createItem() would reject the -1 -> 0 storage conversion.
        $slm = new SLM();
        $slms_id = $slm->add([
            'name'         => 'SlaProgressBarTest SLM',
            'entities_id'  => $entities_id,
            'is_recursive' => 1,
            'calendars_id' => -1,
        ]);
        $this->assertGreaterThan(0, $slms_id);
        $this->assertTrue($slm->getFromDB($slms_id));

        // SLA2's 70h duration sits 20h above the 50 business hours elapsed over the 7-day window below,
        // keeping the calendar-aware vs naive gap safely above the assertion's tolerance.
        $sla1 = $this->createSLA([
            'name'            => 'SlaProgressBarTest SLA1',
            'entities_id'     => $entities_id,
            'number_time'     => 15,
            'definition_time' => 'minute',
        ], SLM::TTR, $slm)['sla'];
        $sla2 = $this->createSLA([
            'name'            => 'SlaProgressBarTest SLA2',
            'entities_id'     => $entities_id,
            'number_time'     => 70,
            'definition_time' => 'hour',
        ], SLM::TTR, $slm)['sla'];

        // Exactly 7 days ago: always one of each weekday, so elapsed time is deterministic
        // (50 business hours calendar-aware vs 168h naive), whatever day the test runs on.
        $ticket_date = date('Y-m-d H:i:s', strtotime('-7 days'));

        $ticket = $this->createItem(Ticket::class, [
            'name'        => 'SlaProgressBarTest ticket',
            'content'     => 'SlaProgressBarTest ticket',
            'entities_id' => $entities_id,
            'date'        => $ticket_date,
            'slas_id_ttr' => $sla1->getID(),
        ]);

        // Escalate: reassign the SLA, as a SLA level action would during a real escalation.
        $ticket = $this->updateItem(Ticket::class, $ticket->getID(), [
            'slas_id_ttr' => $sla2->getID(),
        ]);

        $expected_percentage = $this->computeExpectedProgress($ticket, $calendar);
        $naive_percentage    = $this->computeNaive24_7Progress($ticket);

        // Sanity check: this scenario must discriminate calendar-aware from naive 24/7.
        $this->assertGreaterThan(5, abs($expected_percentage - $naive_percentage));

        $displayed_percentage = $this->getDisplayedTimeToResolveProgress($ticket);

        // Delta absorbs the few seconds between this "now" and the one used by the search.
        $this->assertEqualsWithDelta($expected_percentage, $displayed_percentage, 2);
    }

    private function computeExpectedProgress(Ticket $ticket, Calendar $calendar): float
    {
        $sla = new SLA();
        $this->assertTrue($sla->getFromDB($ticket->fields['slas_id_ttr']));
        $sla->setTicketCalendar($calendar->getID());

        $currenttime = $sla->getActiveTimeBetween($ticket->fields['date'], date('Y-m-d H:i:s'));
        $totaltime   = $sla->getActiveTimeBetween($ticket->fields['date'], $ticket->fields['time_to_resolve']);
        $waitingtime = $ticket->fields['sla_waiting_duration'];

        return min(100, round((100 * ($currenttime - $waitingtime)) / ($totaltime - $waitingtime)));
    }

    private function computeNaive24_7Progress(Ticket $ticket): float
    {
        $currenttime = strtotime(date('Y-m-d H:i:s')) - strtotime($ticket->fields['date']);
        $totaltime   = strtotime($ticket->fields['time_to_resolve']) - strtotime($ticket->fields['date']);
        $waitingtime = $ticket->fields['sla_waiting_duration'];

        return min(100, round((100 * ($currenttime - $waitingtime)) / ($totaltime - $waitingtime)));
    }

    private function getDisplayedTimeToResolveProgress(Ticket $ticket): float
    {
        $params = \Search::manageParams(Ticket::class, [
            'reset'      => 'reset',
            'is_deleted' => 0,
            'criteria'   => [
                [
                    'field'      => 2, // ID
                    'searchtype' => 'equals',
                    'value'      => $ticket->getID(),
                ],
            ],
        ]);
        $data = \Search::getDatas(Ticket::class, $params, [151]);

        $this->assertSame(1, $data['data']['totalcount']);

        $html = $data['data']['rows'][0]['Ticket_151']['displayname'];
        $this->assertMatchesRegularExpression('/aria-valuenow="([0-9.]+)"/', $html);
        preg_match('/aria-valuenow="([0-9.]+)"/', $html, $matches);

        return (float) $matches[1];
    }
}
