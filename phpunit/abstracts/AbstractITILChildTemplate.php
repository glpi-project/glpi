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

namespace tests\units\Glpi;

abstract class AbstractITILChildTemplate extends \DbTestCase
{
    abstract protected function getInstance(): \AbstractITILChildTemplate;

    public function testGetRenderedContent()
    {
        global $CFG_GLPI;

        $this->login();

        $template = $this->getInstance();
        $template->fields['content'] = <<<TPL
Itemtype: {{ itemtype }}
{%if itemtype == 'Ticket' %}{{ ticket.link|raw }}{% endif %}
{%if itemtype == 'Change' %}{{ change.link|raw }}{% endif %}
{%if itemtype == 'Problem' %}{{ problem.link|raw }}{% endif %}
TPL;

        $change = $this->createItem('Change', [
            'name'         => 'test change',
            'content'      => '<p>test content</p>',
            'entities_id'  => getItemByTypeName('Entity', '_test_child_2', true),
        ]);
        $this->assertEquals(
            <<<HTML
Itemtype: Change
<a href="{$CFG_GLPI['root_doc']}/front/change.form.php?id={$change->fields['id']}" title="test change">test change</a>
HTML,
            $template->getRenderedContent($change)
        );

        $problem = $this->createItem('Problem', [
            'name'         => 'test problem',
            'content'      => '<p>test content</p>',
            'entities_id'  => getItemByTypeName('Entity', '_test_child_2', true),
        ]);
        $this->assertEquals(
            <<<HTML
Itemtype: Problem
<a href="{$CFG_GLPI['root_doc']}/front/problem.form.php?id={$problem->fields['id']}" title="test problem">test problem</a>
HTML,
            $template->getRenderedContent($problem)
        );

        $ticket = $this->createItem('Ticket', [
            'name'         => 'test ticket',
            'content'      => '<p>test content</p>',
            'entities_id'  => getItemByTypeName('Entity', '_test_child_2', true),
        ]);
        $this->assertEquals(
            <<<HTML
Itemtype: Ticket
<a href="{$CFG_GLPI['root_doc']}/front/ticket.form.php?id={$ticket->fields['id']}" title="test ticket">test ticket</a>
HTML,
            $template->getRenderedContent($ticket)
        );
    }

    public static function prepareInputProvider(): iterable
    {
        yield [
            'content'  => '{{ itemtype }}',
            'is_valid' => true,
        ];

        yield [
            'content'  => 'Invalid template {{',
            'is_valid' => false,
            'error'    => 'Content: Invalid twig template syntax',
        ];

        yield [
            'content'  => 'Unauthorized tag {% set var = 15 %}',
            'is_valid' => false,
            'error'    => 'Content: Invalid twig template (Tag "set" is not allowed in "template" at line 1.)',
        ];
    }

    /**
     * @dataProvider prepareInputProvider
     */
    public function testPrepareInputForAdd(string $content, bool $is_valid, ?string $error = null)
    {
        $this->login();

        $template = $this->getInstance();

        $result = $template->add(['content' => $content]);
        if ($is_valid) {
            $this->assertGreaterThan(0, $result);
        } else {
            $this->assertFalse($result);
            $this->hasSessionMessages(ERROR, [$error]);
        }
    }

    /**
     * @dataProvider prepareInputProvider
     */
    public function testPrepareInputForUpdate(string $content, bool $is_valid, ?string $error = null)
    {
        $this->login();

        $template = $this->getInstance();

        $template_id = $template->add(['content' => 'test']);
        $this->assertGreaterThan(0, $template_id);

        $result = $template->update(['id' => $template_id, 'content' => $content]);
        $this->assertEquals($is_valid, $result);
        if (!$is_valid) {
            $this->hasSessionMessages(ERROR, [$error]);
        }
    }
}
