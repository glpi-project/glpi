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
    public function testGetRenderedContent()
    {
        global $CFG_GLPI;

        $this->login();

        $template = $this->newTestedInstance;
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
        $this->string($template->getRenderedContent($change))
         ->isEqualTo(<<<HTML
Itemtype: Change
<a href="{$CFG_GLPI['root_doc']}/front/change.form.php?id={$change->fields['id']}" title="test change">test change</a>
HTML
        );

        $problem = $this->createItem('Problem', [
            'name'         => 'test problem',
            'content'      => '<p>test content</p>',
            'entities_id'  => getItemByTypeName('Entity', '_test_child_2', true),
        ]);
        $this->string($template->getRenderedContent($problem))
         ->isEqualTo(<<<HTML
Itemtype: Problem
<a href="{$CFG_GLPI['root_doc']}/front/problem.form.php?id={$problem->fields['id']}" title="test problem">test problem</a>
HTML
        );

        $ticket = $this->createItem('Ticket', [
            'name'         => 'test ticket',
            'content'      => '<p>test content</p>',
            'entities_id'  => getItemByTypeName('Entity', '_test_child_2', true),
        ]);
        $this->string($template->getRenderedContent($ticket))
         ->isEqualTo(<<<HTML
Itemtype: Ticket
<a href="{$CFG_GLPI['root_doc']}/front/ticket.form.php?id={$ticket->fields['id']}" title="test ticket">test ticket</a>
HTML
        );
    }

    protected function prepareInputProvider(): iterable
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

        $template = $this->newTestedInstance;

        $result = $template->add(['content' => $content]);
        if ($is_valid) {
            $this->integer($result)->isGreaterThan(0);
        } else {
            $this->boolean($result)->isFalse();
            $this->hasSessionMessages(ERROR, [$error]);
        }
    }

    /**
     * @dataProvider prepareInputProvider
     */
    public function testPrepareInputForUpdate(string $content, bool $is_valid, ?string $error = null)
    {
        $this->login();

        $template = $this->newTestedInstance;

        $template_id = $template->add(['content' => 'test']);
        $this->integer($template_id)->isGreaterThan(0);

        $result = $template->update(['id' => $template_id, 'content' => $content]);
        $this->boolean($result)->isEqualTo($is_valid);
        if (!$is_valid) {
            $this->hasSessionMessages(ERROR, [$error]);
        }
    }
}
