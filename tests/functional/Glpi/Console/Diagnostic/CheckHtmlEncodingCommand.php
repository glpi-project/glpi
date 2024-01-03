<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace tests\units\Glpi\Console\Diagnostic;

use DbTestCase;

class CheckHtmlEncodingCommand extends DbTestCase
{
    protected function providerFixOneItem(): iterable
    {
        $itemtypes = [
            \Change::class => [
                'fields' => ['content'],
            ],
            \ChangeTask::class => [
                'fields' => ['content'],
            ],
            \ChangeValidation::class => [
                'fields' => ['comment_submission', 'comment_validation'],
            ],
            \ITILFollowup::class => [
                'fields' => ['content'],
                'additional_input' => ['itemtype' => \Ticket::class],
            ],
            \ITILFollowupTemplate::class => [
                'fields' => ['content'],
            ],
            \KnowbaseItem::class => [
                'fields' => ['answer'],
            ],
            \PlanningExternalEvent::class => [
                'fields' => ['text'],
            ],
            \PlanningExternalEventTemplate::class => [
                'fields' => ['text'],
            ],
            \Problem::class => [
                'fields' => ['content'],
            ],
            \ProblemTask::class => [
                'fields' => ['content'],
            ],
            \Reminder::class => [
                'fields' => ['text'],
            ],
            \SolutionTemplate::class => [
                'fields' => ['content'],
            ],
            \TaskTemplate::class => [
                'fields' => ['content'],
            ],
            \Ticket::class => [
                'fields' => ['content'],
            ],
            \TicketTask::class => [
                'fields' => ['content'],
            ],
            \TicketValidation::class => [
                'fields' => ['comment_submission', 'comment_validation'],
            ],
        ];

        foreach ($itemtypes as $itemtype => $specs) {
            $fields = $specs['fields'];
            $additional_input = $specs['additional_input'] ?? [];

            // Sample from a real case having missing semicolon on `&quot`.
            yield [
                'itemtype' => $itemtype,
                'fields'   => $fields,
                'input'    => $additional_input + array_fill_keys(
                    $fields,
                    '&lt;td style="width:100.0%;background:#FDF2F4;padding:5.25pt 3.75pt 5.25pt 11.25pt; word-wrap:break-word; width: `&quot100%`""&gt;'
                ),
                'output'   => array_fill_keys(
                    $fields,
                    '&lt;td style="width:100.0%;background:#FDF2F4;padding:5.25pt 3.75pt 5.25pt 11.25pt; word-wrap:break-word; width: `&quot;100%`""&gt;'
                ),
            ];

            // Missing semicolon on `&quot` on a text node.
            yield [
                'itemtype' => $itemtype,
                'fields'   => $fields,
                'input'    => $additional_input + array_fill_keys(
                    $fields,
                    '&lt;td&gt;&quot&lt;/td&gt;'
                ),
                'output'   => array_fill_keys(
                    $fields,
                    '&lt;td&gt;&quot;&lt;/td&gt;'
                ),
            ];

            // Triple encoding of emails
            // only fixed on tickets/followups
            $output = in_array($itemtype, [\Ticket::class, \ITILFollowup::class])
                ? '&#38;lt;helpdesk@some-domain.com&#38;gt;'
                : '&#38;amp;lt;helpdesk@some-domain.com&#38;amp;gt;';
            yield [
                'itemtype' => $itemtype,
                'fields'   => $fields,
                'input'    => $additional_input + array_fill_keys(
                    $fields,
                    '&#38;amp;lt;helpdesk@some-domain.com&#38;amp;gt;'
                ),
                'output'   => array_fill_keys(
                    $fields,
                    $output
                ),
            ];
        }
    }

    /**
     * @DataProvider providerFixOneItem
     *
     * @param string $itemtype
     * @param array $fields
     * @param array $input
     * @param array $output
     * @return void
     */
    public function testFixOneItem(string $itemtype, array $fields, array $input, array $output): void
    {
        global $DB;

        $instance = $this->newTestedInstance();

        // Create and load item
        $insert = $DB->insert($itemtype::getTable(), $input);
        $this->boolean($insert)->isTrue();
        $id = $DB->insertId();
        $this->integer($id)->isGreaterThan(0);

        $item = new $itemtype();
        $this->boolean($item->getFromDB($id));

        // Check updated properties
        $this->callPrivateMethod($instance, 'fixOneItem', $item, $fields);

        $this->boolean($item->getFromDB($item->fields[$item::getIndexName()]))->isTrue();
        foreach ($output as $field => $value) {
            $this->string($item->fields[$field])->isEqualTo($value);
        }
    }
}
