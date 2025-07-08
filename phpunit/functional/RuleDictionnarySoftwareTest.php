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

use DbTestCase;

/* Test for inc/ruledictionnarysoftware.class.php */

class RuleDictionnarySoftwareTest extends DbTestCase
{
    public function testMaxActionsCount()
    {
        $rule = new \RuleDictionnarySoftware();
        $this->assertSame(7, $rule->maxActionsCount());
    }

    public function testGetCriteria()
    {
        $rule     = new \RuleDictionnarySoftware();
        $criteria = $rule->getCriterias();
        $this->assertCount(4, $criteria);
    }

    public function testGetActions()
    {
        $rule    = new \RuleDictionnarySoftware();
        $actions = $rule->getActions();
        $this->assertCount(7, $actions);
    }

    public function testAddSpecificParamsForPreview()
    {
        $rule    = new \RuleDictionnarySoftware();

        $input = ['param1' => 'test'];
        $result = $rule->addSpecificParamsForPreview($input);
        $this->assertSame(['param1' => 'test'], $result);

        $_POST['version'] = '1.0';
        $result = $rule->addSpecificParamsForPreview($input);
        $this->assertSame(['param1' => 'test', 'version' => '1.0'], $result);
    }
}
