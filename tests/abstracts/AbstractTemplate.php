<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */
namespace tests\units\Glpi;

abstract class AbstractTemplate extends \DbTestCase {

   public function testGetRenderedContent() {
      global $CFG_GLPI;

      $this->login();

      $solution = $this->newTestedInstance;
      $solution->fields['content'] = <<<TPL
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
      $this->string($solution->getRenderedContent($change))
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
      $this->string($solution->getRenderedContent($problem))
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
      $this->string($solution->getRenderedContent($ticket))
         ->isEqualTo(<<<HTML
Itemtype: Ticket
<a href="{$CFG_GLPI['root_doc']}/front/ticket.form.php?id={$ticket->fields['id']}" title="test ticket">test ticket</a>
HTML
      );
   }
}
