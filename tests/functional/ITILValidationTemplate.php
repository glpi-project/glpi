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

namespace tests\units;

include_once __DIR__ . '/../abstracts/AbstractITILChildTemplate.php';

use ITILValidationTemplate as GlobalITILValidationTemplate;
use ITILValidationTemplate_Target;
use tests\units\Glpi\AbstractITILChildTemplate;

class ITILValidationTemplate extends AbstractITILChildTemplate
{
    public function testPostTargets()
    {
        $validationTemplate = new GlobalITILValidationTemplate();
        $this->integer((int)$validationTemplate->add([
            'name'         => 'Validation template',
            'description'  => 'a description',
            'content'      => '',
        ]))->isGreaterThan(0);

        // Add a user target
        $validationTemplate->input = [
            'itemtype_target' => 'User',
            'items_id_target' => 1
        ];

        $validationTemplate->post_addItem();
        $targets = ITILValidationTemplate_Target::getTargets($validationTemplate->getID());
        $this->array($targets)->hasSize(1);

        $target = current($targets);
        $this->string($target['itemtype'])->isEqualTo('User');
        $this->integer($target['items_id'])->isEqualTo(1);
        $this->variable($target['groups_id'])->isNull();

        // Add a group target
        $validationTemplate->input = [
            'itemtype_target' => 'Group',
            'items_id_target' => 1
        ];

        $validationTemplate->post_addItem();
        $targets = ITILValidationTemplate_Target::getTargets($validationTemplate->getID());
        $this->array($targets)->hasSize(1);

        $target = current($targets);
        $this->string($target['itemtype'])->isEqualTo('Group');
        $this->integer($target['items_id'])->isEqualTo(1);
        $this->variable($target['groups_id'])->isNull();

        // Add a group user target
        $validationTemplate->input = [
            'itemtype_target' => 'User',
            'items_id_target' => [1, 2, 3, 4],
            'groups_id' => 1
        ];

        $validationTemplate->post_addItem();
        $targets = ITILValidationTemplate_Target::getTargets($validationTemplate->getID());
        $this->array($targets)->hasSize(4);

        foreach ($targets as $target) {
            $this->string($target['itemtype'])->isEqualTo('User');
            $this->array([1, 2, 3, 4])->contains($target['items_id']);
            $this->integer($target['groups_id'])->isEqualTo(1);
        }
    }
}
