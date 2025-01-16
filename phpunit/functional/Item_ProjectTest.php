<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use DbTestCase;

/* Test for inc/itil_project.class.php */

class Item_ProjectTest extends DbTestCase
{
    /**
     * Test the presence of the Item_Project tab in the $CFG_GLPI["project_asset_types"]
     *
     * @return void
     */
    public function testItemProjectTab()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $this->login();

        foreach ($CFG_GLPI["project_asset_types"] as $itemtype) {
            $item = new $itemtype();
            $item_id = $item->add(
                [
                    'name' => 'Test project',
                    'entities_id' => 0
                ]
            );
            $this->assertGreaterThan(0, $item_id);

            $item->getFromDB($item_id);
            $tabs = $item->defineTabs();
            $this->assertArrayHasKey('Item_Project$1', $tabs);
        }
    }
}
