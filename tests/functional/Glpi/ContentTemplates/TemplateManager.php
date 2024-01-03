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

namespace tests\units\Glpi\ContentTemplates;

use Change;
use CommonITILActor;
use DbTestCase;
use Glpi\Toolbox\Sanitizer;
use Problem;
use Ticket;

/**
 * Functionnals test to make sure content templates work as expected
 */
class TemplateManager extends DbTestCase
{
    /**
     * Get all possible CommonITILObject classes.
     *
     * @return array
     */
    protected function commonITILObjectclassesProvider(): array
    {
        return [Ticket::class, Change::class, Problem::class];
    }

    /**
     * Verify that actors are rendered correctly on creation (commonitilobject)
     *
     * @dataprovider commonITILObjectclassesProvider
     *
     * @return void
     */
    public function testActorsOnCreate(string $common_itil_object_class): void
    {
        $this->login();
        $user = getItemByTypeName('User', TU_USER);

        /** @var \CommonITILObject $common_itil_object */
        $common_itil_object = new $common_itil_object_class();
        $this->boolean($common_itil_object instanceof \CommonITILObject)->isTrue();

        // Test entity
        $entities_id = getItemByTypeName("Entity", "_test_root_entity", true);

        // Create task template
        $template_key = strtolower($common_itil_object_class);
        $task_template = $this->createItem("TaskTemplate", [
            'entities_id' => $entities_id,
            'name'        => 'task template',
            'content'     => "{{ $template_key.id }} {% for user in $template_key.requesters.users %}{{ user.login }}{% endfor %}",
        ]);

        // Create commonitil with specified task template
        /** @var \CommonITILObject $common_itil_object */
        $common_itil_object = $this->createItem($common_itil_object::getType(), [
            'entities_id'       => $entities_id,
            'name'              => 'test commonitil',
            'content'           => 'test commonitil',
            '_tasktemplates_id' => [$task_template->getID()],
            '_actors'            => [
                'requester' => [
                    ['itemtype' => 'User', 'items_id' => $user->getID()]
                ]
            ]
        ]);

        // Validate requester
        $actors = $common_itil_object->getITILActors();
        $this->array($actors[$user->getID()])->isEqualTo([CommonITILActor::REQUESTER]);

        // Get task
        $task_type = $common_itil_object->getTaskClass();
        $tasks = (new $task_type())->find([
            $common_itil_object->getForeignKeyField() => $common_itil_object->getId(),
        ]);
        $this->array($tasks)->hasSize(1);
        $task = array_pop($tasks);
        $this->string(Sanitizer::unsanitize($task['content']))->isEqualTo(
            "<p>{$common_itil_object->getId()} {$user->fields['name']}</p>"
        );
    }
}
