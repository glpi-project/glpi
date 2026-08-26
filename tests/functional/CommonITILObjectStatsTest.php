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

use Change;
use CommonITILObject;
use Glpi\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Problem;
use Ticket;

class CommonITILObjectStatsTest extends DbTestCase
{
    /**
     * Collapses whitespace between tags and uniformises attribute quotes, so a
     * single-line `echo` can be compared to an indented template. The heading
     * ids are random (mt_rand()/random()), so they are normalized too.
     */
    private function normalizeHtml(string $html): string
    {
        $html = preg_replace('/(\s[a-zA-Z-]+)=\'([^\']*)\'/', '$1="$2"', $html);
        $html = preg_replace('/>\s+</', '><', $html);
        $html = preg_replace('/(stats_dates_|stats_times_)\d+/', '$1RAND', $html);

        return trim($html);
    }

    // Ticket overrides showStatsDates() (src/Ticket.php:5406) and does not exercise this code.
    public static function statsDatesProvider(): iterable
    {
        $fields = [
            'date'            => '2026-01-15 09:30:00',
            'time_to_resolve' => '2026-01-20 17:00:00',
            'solvedate'       => null,
            'closedate'       => null,
            'solve_delay_stat' => 0,
            'close_delay_stat' => 0,
            'waiting_duration' => 0,
        ];

        foreach ([Change::class, Problem::class] as $itemtype) {
            yield "$itemtype / nouveau, prise en compte absente" => [
                'itemtype' => $itemtype,
                'fields'   => $fields + ['status' => CommonITILObject::INCOMING],
                'expected' => '<h2 class="header lh-base" id="stats_dates_RAND">Dates</h2><table class="tab_cadre_fixe" aria-labelledby="stats_dates_RAND"><tr class="tab_bg_2"><th scope="row">Opening date</th><td>2026-01-15 09:30</td></tr><tr class="tab_bg_2"><th scope="row">Time to resolve</th><td>2026-01-20 17:00</td></tr></table>',
            ];

            yield "$itemtype / nouveau, prise en compte nulle" => [
                'itemtype' => $itemtype,
                'fields'   => $fields + [
                    'status' => CommonITILObject::INCOMING,
                    'takeintoaccount_delay_stat' => 0,
                ],
                'expected' => '<h2 class="header lh-base" id="stats_dates_RAND">Dates</h2><table class="tab_cadre_fixe" aria-labelledby="stats_dates_RAND"><tr class="tab_bg_2"><th scope="row">Opening date</th><td>2026-01-15 09:30</td></tr><tr class="tab_bg_2"><th scope="row">Time to resolve</th><td>2026-01-20 17:00</td></tr></table>',
            ];

            yield "$itemtype / pris en compte" => [
                'itemtype' => $itemtype,
                'fields'   => $fields + [
                    'status' => CommonITILObject::ASSIGNED,
                    'takeintoaccount_delay_stat' => 300,
                ],
                'expected' => '<h2 class="header lh-base" id="stats_dates_RAND">Dates</h2><table class="tab_cadre_fixe" aria-labelledby="stats_dates_RAND"><tr class="tab_bg_2"><th scope="row">Opening date</th><td>2026-01-15 09:30</td></tr><tr class="tab_bg_2"><th scope="row">Time to resolve</th><td>2026-01-20 17:00</td></tr></table>',
            ];

            yield "$itemtype / resolu" => [
                'itemtype' => $itemtype,
                'fields'   => array_merge($fields, [
                    'status'    => CommonITILObject::SOLVED,
                    'solvedate' => '2026-01-16 11:00:00',
                    'takeintoaccount_delay_stat' => 300,
                    'solve_delay_stat' => 3600,
                ]),
                'expected' => '<h2 class="header lh-base" id="stats_dates_RAND">Dates</h2><table class="tab_cadre_fixe" aria-labelledby="stats_dates_RAND"><tr class="tab_bg_2"><th scope="row">Opening date</th><td>2026-01-15 09:30</td></tr><tr class="tab_bg_2"><th scope="row">Time to resolve</th><td>2026-01-20 17:00</td></tr><tr class="tab_bg_2"><th scope="row">Resolution date</th><td>2026-01-16 11:00</td></tr></table>',
            ];

            yield "$itemtype / cloture apres attente" => [
                'itemtype' => $itemtype,
                'fields'   => array_merge($fields, [
                    'status'    => CommonITILObject::CLOSED,
                    'solvedate' => '2026-01-16 11:00:00',
                    'closedate' => '2026-01-17 08:15:00',
                    'takeintoaccount_delay_stat' => 300,
                    'solve_delay_stat' => 3600,
                    'close_delay_stat' => 7200,
                    'waiting_duration' => 60,
                ]),
                'expected' => '<h2 class="header lh-base" id="stats_dates_RAND">Dates</h2><table class="tab_cadre_fixe" aria-labelledby="stats_dates_RAND"><tr class="tab_bg_2"><th scope="row">Opening date</th><td>2026-01-15 09:30</td></tr><tr class="tab_bg_2"><th scope="row">Time to resolve</th><td>2026-01-20 17:00</td></tr><tr class="tab_bg_2"><th scope="row">Resolution date</th><td>2026-01-16 11:00</td></tr><tr class="tab_bg_2"><th scope="row">Closing date</th><td>2026-01-17 08:15</td></tr></table>',
            ];

            // Closed status but 'NULL' dates: the row is still emitted, with an empty cell.
            yield "$itemtype / cloture avec dates a NULL" => [
                'itemtype' => $itemtype,
                'fields'   => array_merge($fields, [
                    'status'    => CommonITILObject::CLOSED,
                    'solvedate' => 'NULL',
                    'closedate' => 'NULL',
                    'takeintoaccount_delay_stat' => 300,
                ]),
                'expected' => '<h2 class="header lh-base" id="stats_dates_RAND">Dates</h2><table class="tab_cadre_fixe" aria-labelledby="stats_dates_RAND"><tr class="tab_bg_2"><th scope="row">Opening date</th><td>2026-01-15 09:30</td></tr><tr class="tab_bg_2"><th scope="row">Time to resolve</th><td>2026-01-20 17:00</td></tr><tr class="tab_bg_2"><th scope="row">Resolution date</th><td></td></tr><tr class="tab_bg_2"><th scope="row">Closing date</th><td></td></tr></table>',
            ];
        }
    }

    // showStatsTimes() is overridden nowhere (single implementation): all three itemtypes stay covered.
    public static function statsTimesProvider(): iterable
    {
        $fields = [
            'date'            => '2026-01-15 09:30:00',
            'time_to_resolve' => '2026-01-20 17:00:00',
            'solvedate'       => null,
            'closedate'       => null,
            'solve_delay_stat' => 0,
            'close_delay_stat' => 0,
            'waiting_duration' => 0,
        ];

        foreach ([Ticket::class, Change::class, Problem::class] as $itemtype) {
            // Field absent: the 'Take into account' row is not emitted at all.
            yield "$itemtype / nouveau, prise en compte absente" => [
                'itemtype' => $itemtype,
                'fields'   => $fields + ['status' => CommonITILObject::INCOMING],
                'expected' => '<div class="dates_timelines"><h2 class="header lh-base" id="stats_times_RAND">Times</h2><table class="tab_cadre_fixe" aria-labelledby="stats_times_RAND"><tr class="tab_bg_2"><th scope="row">Pending</th><td>&nbsp;</td></tr></table></div>',
            ];

            // Field present but zero: the row is emitted with &nbsp;.
            yield "$itemtype / nouveau, prise en compte nulle" => [
                'itemtype' => $itemtype,
                'fields'   => $fields + [
                    'status' => CommonITILObject::INCOMING,
                    'takeintoaccount_delay_stat' => 0,
                ],
                'expected' => '<div class="dates_timelines"><h2 class="header lh-base" id="stats_times_RAND">Times</h2><table class="tab_cadre_fixe" aria-labelledby="stats_times_RAND"><tr class="tab_bg_2"><th scope="row">Take into account</th><td>&nbsp;</td></tr><tr class="tab_bg_2"><th scope="row">Pending</th><td>&nbsp;</td></tr></table></div>',
            ];

            yield "$itemtype / pris en compte" => [
                'itemtype' => $itemtype,
                'fields'   => $fields + [
                    'status' => CommonITILObject::ASSIGNED,
                    'takeintoaccount_delay_stat' => 300,
                ],
                'expected' => '<div class="dates_timelines"><h2 class="header lh-base" id="stats_times_RAND">Times</h2><table class="tab_cadre_fixe" aria-labelledby="stats_times_RAND"><tr class="tab_bg_2"><th scope="row">Take into account</th><td>5 minutes</td></tr><tr class="tab_bg_2"><th scope="row">Pending</th><td>&nbsp;</td></tr></table></div>',
            ];

            yield "$itemtype / resolu" => [
                'itemtype' => $itemtype,
                'fields'   => array_merge($fields, [
                    'status'    => CommonITILObject::SOLVED,
                    'solvedate' => '2026-01-16 11:00:00',
                    'takeintoaccount_delay_stat' => 300,
                    'solve_delay_stat' => 3600,
                ]),
                'expected' => '<div class="dates_timelines"><h2 class="header lh-base" id="stats_times_RAND">Times</h2><table class="tab_cadre_fixe" aria-labelledby="stats_times_RAND"><tr class="tab_bg_2"><th scope="row">Take into account</th><td>5 minutes</td></tr><tr class="tab_bg_2"><th scope="row">Resolution</th><td>1 hours 0 minutes</td></tr><tr class="tab_bg_2"><th scope="row">Pending</th><td>&nbsp;</td></tr></table></div>',
            ];

            yield "$itemtype / cloture apres attente" => [
                'itemtype' => $itemtype,
                'fields'   => array_merge($fields, [
                    'status'    => CommonITILObject::CLOSED,
                    'solvedate' => '2026-01-16 11:00:00',
                    'closedate' => '2026-01-17 08:15:00',
                    'takeintoaccount_delay_stat' => 300,
                    'solve_delay_stat' => 3600,
                    'close_delay_stat' => 7200,
                    'waiting_duration' => 60,
                ]),
                'expected' => '<div class="dates_timelines"><h2 class="header lh-base" id="stats_times_RAND">Times</h2><table class="tab_cadre_fixe" aria-labelledby="stats_times_RAND"><tr class="tab_bg_2"><th scope="row">Take into account</th><td>5 minutes</td></tr><tr class="tab_bg_2"><th scope="row">Resolution</th><td>1 hours 0 minutes</td></tr><tr class="tab_bg_2"><th scope="row">Closure</th><td>2 hours 0 minutes 0 seconds</td></tr><tr class="tab_bg_2"><th scope="row">Pending</th><td>1 minute</td></tr></table></div>',
            ];

            // Resolution and Closure at zero: both rows emit &nbsp; (else branches not covered elsewhere).
            yield "$itemtype / cloture sans duree" => [
                'itemtype' => $itemtype,
                'fields'   => array_merge($fields, [
                    'status'    => CommonITILObject::CLOSED,
                    'solvedate' => '2026-01-16 11:00:00',
                    'closedate' => '2026-01-17 08:15:00',
                    'takeintoaccount_delay_stat' => 300,
                    'solve_delay_stat' => 0,
                    'close_delay_stat' => 0,
                ]),
                'expected' => '<div class="dates_timelines"><h2 class="header lh-base" id="stats_times_RAND">Times</h2><table class="tab_cadre_fixe" aria-labelledby="stats_times_RAND"><tr class="tab_bg_2"><th scope="row">Take into account</th><td>5 minutes</td></tr><tr class="tab_bg_2"><th scope="row">Resolution</th><td>&nbsp;</td></tr><tr class="tab_bg_2"><th scope="row">Closure</th><td>&nbsp;</td></tr><tr class="tab_bg_2"><th scope="row">Pending</th><td>&nbsp;</td></tr></table></div>',
            ];
        }
    }

    /**
     * Creates an item and overwrites its fields in memory (the only way to get a
     * deterministic output and to reach the "field absent" branch), then captures $method.
     */
    private function captureStats(string $itemtype, array $fields, string $method): string
    {
        $this->login();

        // Pin date format and plural number, otherwise the reference depends on local config.
        $_SESSION['glpidate_format']  = 0;
        $_SESSION['glpipluralnumber'] = 2;

        $item = $this->createItem($itemtype, [
            'name'        => 'Stats reference item',
            'content'     => 'Stats reference content',
            'entities_id' => $this->getTestRootEntity(only_id: true),
        ]);
        $this->assertInstanceOf(CommonITILObject::class, $item);

        $item->fields = array_merge($item->fields, $fields);
        foreach (['takeintoaccount_delay_stat'] as $optional) {
            if (!array_key_exists($optional, $fields)) {
                unset($item->fields[$optional]);
            }
        }

        // finally: a template error must surface as itself, not as a leaked buffer.
        ob_start();
        try {
            $item->$method();
        } finally {
            $html = ob_get_clean();
        }

        return $this->normalizeHtml($html);
    }

    #[DataProvider('statsDatesProvider')]
    public function testShowStatsDatesOutputIsStable(string $itemtype, array $fields, string $expected): void
    {
        $this->assertSame($expected, $this->captureStats($itemtype, $fields, 'showStatsDates'));
    }

    #[DataProvider('statsTimesProvider')]
    public function testShowStatsTimesOutputIsStable(string $itemtype, array $fields, string $expected): void
    {
        $this->assertSame($expected, $this->captureStats($itemtype, $fields, 'showStatsTimes'));
    }
}
