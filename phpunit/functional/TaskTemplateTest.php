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

namespace tests\units;

include_once __DIR__ . '/../abstracts/AbstractITILChildTemplate.php';

use tests\units\Glpi\AbstractITILChildTemplate;

/* Test for inc/tasktemplate.class.php */

class TaskTemplateTest extends AbstractITILChildTemplate
{
    protected function getInstance(): \AbstractITILChildTemplate
    {
        return new \TaskTemplate();
    }

    public function testCurrentUserFeature()
    {
        $this->login();
        $template = new \TaskTemplate();

        $template_id = $this->createItem(
            'TaskTemplate',
            [
                'name'         => 'Test Current User Template',
                'content'      => 'Test content',
                'users_id_tech' => -1,
            ]
        )->getID();

        $this->assertTrue($template->getFromDB($template_id));

        $this->assertEquals(1, $template->fields['use_current_user']);
        $this->assertEquals(-1, $template->fields['users_id_tech']); // Value should be transformed in post_getFromDB

        $specific_user_id = getItemByTypeName('User', 'tech', true);
        $this->updateItem(
            'TaskTemplate',
            $template_id,
            [
                'users_id_tech' => $specific_user_id,
            ]
        );

        $this->assertTrue($template->getFromDB($template_id));

        $this->assertEquals(0, $template->fields['use_current_user']);
        $this->assertEquals($specific_user_id, (int) $template->fields['users_id_tech']);

        // Test updating back to current user
        $this->updateItem(
            'TaskTemplate',
            $template_id,
            [
                'users_id_tech' => -1,
            ]
        );

        $this->assertTrue($template->getFromDB($template_id));

        $this->assertEquals(1, $template->fields['use_current_user']);
        $this->assertEquals(-1, $template->fields['users_id_tech']); // Value should be transformed in post_getFromDB

        $this->updateItem(
            'TaskTemplate',
            $template_id,
            [
                'users_id_tech' => 0,
            ]
        );

        $this->assertTrue($template->getFromDB($template_id));

        $this->assertEquals(0, $template->fields['use_current_user']);
        $this->assertEquals(0, (int) $template->fields['users_id_tech']);
    }

    public function testSpecificValueToDisplay()
    {
        $this->login();

        $values = [
            'users_id_tech' => -1,
            'use_current_user' => 1,
        ];
        $result = \TaskTemplate::getSpecificValueToDisplay('users_id_tech', $values);
        $this->assertEquals(__('Current logged-in user'), $result);

        $specific_user_id = getItemByTypeName('User', 'tech', true);
        $values = [
            'users_id_tech' => $specific_user_id,
            'use_current_user' => 0,
        ];
        $result = \TaskTemplate::getSpecificValueToDisplay('users_id_tech', $values);
        $this->assertStringContainsString('tech', $result);
    }

    public function testSearchAbility()
    {
        $this->login();

        $this->createItem(
            'TaskTemplate',
            [
                'name'         => 'Search Template Current User',
                'content'      => 'Content 1',
                'users_id_tech' => -1,
            ]
        )->getID();

        $specific_user_id = getItemByTypeName('User', 'tech', true);
        $this->createItem(
            'TaskTemplate',
            [
                'name'         => 'Search Template Specific User',
                'content'      => 'Content 2',
                'users_id_tech' => $specific_user_id,
            ]
        )->getID();

        $this->createItem(
            'TaskTemplate',
            [
                'name'         => 'Search Template No User',
                'content'      => 'Content 3',
                'users_id_tech' => 0,
            ]
        )->getID();

        $condition = \TaskTemplate::addWhere('AND', 0, \TaskTemplate::class, 7, 'equals', -1);
        $this->assertEquals(' AND (`glpi_tasktemplates`.`use_current_user` = 1)', $condition);

        $condition = \TaskTemplate::addWhere('AND', 0, \TaskTemplate::class, 7, 'equals', $specific_user_id);
        $this->assertEquals(" AND (`glpi_tasktemplates`.`use_current_user` = 0 AND `glpi_tasktemplates`.`users_id_tech` = $specific_user_id)", $condition);

        $condition = \TaskTemplate::addWhere('AND', 0, \TaskTemplate::class, 7, 'equals', 0);
        $this->assertEquals(' AND (`glpi_tasktemplates`.`use_current_user` = 0 AND `glpi_tasktemplates`.`users_id_tech` = 0)', $condition);
    }
}
