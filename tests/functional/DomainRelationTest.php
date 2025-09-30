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

class DomainRelationTest extends DbTestCase
{
    public function testDeleteProtectedRelations()
    {
        $this->login();

        $relation = new \DomainRelation();
        $this->assertGreaterThan(
            0,
            $unprotected_id = $relation->add([
                'name' => __FUNCTION__,
            ])
        );

        // Should not be able to delete the domain relations added to GLPI by default
        $this->assertFalse($relation->delete(['id' => \DomainRelation::BELONGS]));
        $this->assertFalse($relation->delete(['id' => \DomainRelation::MANAGE]));

        // Should be able to delete the domain relation added by the test
        $this->assertTrue($relation->delete(['id' => $unprotected_id]));
    }
}
