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

use ITILValidationTemplate as GlobalITILValidationTemplate;
use ITILValidationTemplate_Target;
use tests\units\Glpi\AbstractITILChildTemplate;

class ITILValidationTemplateTest extends AbstractITILChildTemplate
{
    public function testPostTargets()
    {
        $validationTemplate = new GlobalITILValidationTemplate();
        $this->assertGreaterThan(
            0,
            (int) $validationTemplate->add([
                'name'         => 'Validation template',
                'description'  => 'a description',
                'content'      => '',
            ])
        );

        // Add a user target
        $validationTemplate->input = [
            'itemtype_target' => 'User',
            'items_id_target' => 1,
        ];

        $validationTemplate->post_addItem();
        $targets = ITILValidationTemplate_Target::getTargets($validationTemplate->getID());
        $this->assertCount(1, $targets);

        $target = current($targets);
        $this->assertEquals(\User::class, $target['itemtype']);
        $this->assertEquals(1, $target['items_id']);
        $this->assertNull($target['groups_id']);

        // Add a group target
        $validationTemplate->input = [
            'itemtype_target' => 'Group',
            'items_id_target' => 1,
        ];

        $validationTemplate->post_addItem();
        $targets = ITILValidationTemplate_Target::getTargets($validationTemplate->getID());
        $this->assertCount(1, $targets);

        $target = current($targets);
        $this->assertEquals(\Group::class, $target['itemtype']);
        $this->assertEquals(1, $target['items_id']);
        $this->assertNull($target['groups_id']);

        // Add a group user target
        $validationTemplate->input = [
            'itemtype_target' => 'User',
            'items_id_target' => [1, 2, 3, 4],
            'groups_id' => 1,
        ];

        $validationTemplate->post_addItem();
        $targets = ITILValidationTemplate_Target::getTargets($validationTemplate->getID());
        $this->assertCount(4, $targets);

        foreach ($targets as $target) {
            $this->assertEquals(\User::class, $target['itemtype']);
            $this->assertContains($target['items_id'], [1, 2, 3, 4]);
            $this->assertEquals(1, $target['groups_id']);
        }
    }

    protected function getInstance(): \AbstractITILChildTemplate
    {
        return new GlobalITILValidationTemplate();
    }
}
