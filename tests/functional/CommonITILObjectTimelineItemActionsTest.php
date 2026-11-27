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

use Glpi\Plugin\Hooks;
use Glpi\Tests\DbTestCase;
use ITILFollowup;
use Psr\Log\LogLevel;
use Ticket;

class CommonITILObjectTimelineItemActionsTest extends DbTestCase
{
    private mixed $hooks_backup = null;

    public function setUp(): void
    {
        global $PLUGIN_HOOKS;

        parent::setUp();
        $this->hooks_backup = $PLUGIN_HOOKS[Hooks::TIMELINE_ITEM_ACTIONS] ?? null;
    }

    public function tearDown(): void
    {
        global $PLUGIN_HOOKS;

        unset($PLUGIN_HOOKS[Hooks::TIMELINE_ITEM_ACTIONS]);
        if ($this->hooks_backup !== null) {
            $PLUGIN_HOOKS[Hooks::TIMELINE_ITEM_ACTIONS] = $this->hooks_backup;
        }
        parent::tearDown();
    }

    private function createTicketWithFollowup(): array
    {
        $this->login('tech', 'tech');

        $ticket = $this->createItem(Ticket::class, [
            'name'    => 'ticket title',
            'content' => 'ticket content',
        ]);
        $followup = $this->createItem(ITILFollowup::class, [
            'itemtype' => Ticket::class,
            'items_id' => $ticket->getID(),
            'content'  => 'followup content',
        ]);

        return [$ticket, $followup];
    }

    public function testActionsAreNormalized(): void
    {
        global $PLUGIN_HOOKS;

        [$ticket, $followup] = $this->createTicketWithFollowup();

        $received = [];
        $PLUGIN_HOOKS[Hooks::TIMELINE_ITEM_ACTIONS]['tester'] = function (array $params) use (&$received) {
            $received[] = $params;
            return [
                [
                    'label'      => 'Translate',
                    'icon'       => 'ti ti-language',
                    'display'    => 'button',
                    'class'      => 'active',
                    'attributes' => ['data-translate-toggle' => 1, 'aria-pressed' => 'true'],
                ],
                [
                    'label' => 'Open in the plugin',
                    'url'   => '/plugins/tester/front/item.php',
                ],
            ];
        };
        // A plugin that is not active is ignored
        $PLUGIN_HOOKS[Hooks::TIMELINE_ITEM_ACTIONS]['notactive'] = fn() => [['label' => 'Ignored']];

        $entry = ['type' => ITILFollowup::class, 'item' => $followup->fields];
        $actions = $ticket->getTimelineItemPluginActions($entry, $followup);

        $this->assertSame(
            [
                'button' => [
                    [
                        'label'      => 'Translate',
                        'icon'       => 'ti ti-language',
                        'url'        => null,
                        'class'      => 'active',
                        'attributes' => ['data-translate-toggle' => '1', 'aria-pressed' => 'true'],
                    ],
                ],
                'menu' => [
                    [
                        'label'      => 'Open in the plugin',
                        'icon'       => '',
                        'url'        => '/plugins/tester/front/item.php',
                        'class'      => '',
                        'attributes' => [],
                    ],
                ],
            ],
            $actions
        );

        $this->assertCount(1, $received);
        $this->assertSame($followup, $received[0]['item']);
        $this->assertSame($ticket, $received[0]['parent']);
        $this->assertSame($entry, $received[0]['entry']);
    }

    public function testInvalidActionsAreIgnored(): void
    {
        global $PLUGIN_HOOKS;

        [$ticket, $followup] = $this->createTicketWithFollowup();

        $PLUGIN_HOOKS[Hooks::TIMELINE_ITEM_ACTIONS]['tester'] = fn() => [
            ['icon' => 'ti ti-x'],
            ['label' => 'Bad display', 'display' => 'footer'],
            [
                'label'      => 'Bad attributes',
                'attributes' => [
                    'onclick" onmouseover="alert(1)' => 'x',
                    'class'                           => 'other',
                    'data-list'                       => ['a', 'b'],
                    'data-valid'                      => 'kept',
                ],
            ],
        ];

        $actions = $ticket->getTimelineItemPluginActions(['type' => ITILFollowup::class, 'item' => $followup->fields], $followup);

        $this->assertSame([], $actions['button']);
        $this->assertCount(1, $actions['menu']);
        $this->assertSame('Bad attributes', $actions['menu'][0]['label']);
        $this->assertSame(['data-valid' => 'kept'], $actions['menu'][0]['attributes']);

        // No label, then bad display
        $this->hasPhpLogRecordThatContains('a label and a "button" or "menu" display are expected', LogLevel::WARNING);
        $this->hasPhpLogRecordThatContains('a label and a "button" or "menu" display are expected', LogLevel::WARNING);
        $this->hasPhpLogRecordThatContains('Invalid attribute "onclick" onmouseover="alert(1)"', LogLevel::WARNING);
        $this->hasPhpLogRecordThatContains('Invalid attribute "class"', LogLevel::WARNING);
        $this->hasPhpLogRecordThatContains('Invalid attribute "data-list"', LogLevel::WARNING);
    }

    public function testInvalidHookResult(): void
    {
        global $PLUGIN_HOOKS;

        [$ticket, $followup] = $this->createTicketWithFollowup();

        $PLUGIN_HOOKS[Hooks::TIMELINE_ITEM_ACTIONS]['tester'] = fn() => 'not an array';

        $this->assertSame(
            ['button' => [], 'menu' => []],
            $ticket->getTimelineItemPluginActions(['type' => ITILFollowup::class, 'item' => $followup->fields], $followup)
        );
        $this->hasPhpLogRecordThatContains('"timeline_item_actions" hook callback result should be an array, "string" returned.', LogLevel::WARNING);
    }

    public function testActionsAreDisplayedInTheTimeline(): void
    {
        global $PLUGIN_HOOKS;

        [$ticket, $followup] = $this->createTicketWithFollowup();

        $PLUGIN_HOOKS[Hooks::TIMELINE_ITEM_ACTIONS]['tester'] = fn(array $params) => [
            [
                'label'      => 'Translate',
                'icon'       => 'ti ti-language',
                'display'    => 'button',
                'attributes' => [
                    'data-testid'         => 'translate-' . $params['entry']['type'],
                    'data-tester-label'   => '"><script>alert(1)</script>',
                ],
            ],
            [
                'label'      => 'Menu action of ' . $params['entry']['type'],
                'icon'       => 'ti ti-star',
                'attributes' => ['data-testid' => 'menu-' . $params['entry']['type']],
            ],
        ];

        $this->assertTrue($ticket->getFromDB($ticket->getID()));
        ob_start();
        $ticket->showForm($ticket->getID());
        $html = ob_get_clean();

        // Followup and description
        $this->assertStringContainsString('data-testid="translate-ITILFollowup"', $html);
        $this->assertStringContainsString('data-testid="translate-Ticket"', $html);
        $this->assertStringContainsString('data-testid="menu-ITILFollowup"', $html);
        $this->assertStringContainsString('data-testid="menu-Ticket"', $html);
        $this->assertStringContainsString('Menu action of ITILFollowup', $html);
        $this->assertStringContainsString('style="--timeline-plugin-buttons: 1"', $html);

        // Attributes values are escaped
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('data-tester-label="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;"', $html);
    }
}
