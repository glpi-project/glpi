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

namespace tests\units\Glpi\Inventory\Asset;

abstract class AbstractInventoryAsset extends \InventoryTestCase
{
    protected $myclass = "";

    protected $log_entries;
    protected $new_log_entries = 0;

    public function setUp(): void
    {
        parent::setUp();
        $this->log_entries = countElementsInTable(\Log::getTable());
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $log_entries = countElementsInTable(\Log::getTable());
        $this->assertSame($this->log_entries + $this->new_log_entries, $log_entries);
    }

    /**
     * Data provider for asset
     *
     * @return array
     */
    abstract public static function assetProvider(): array;
}
