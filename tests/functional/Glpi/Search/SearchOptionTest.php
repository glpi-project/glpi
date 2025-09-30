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

namespace tests\units\Glpi\Search;

use DbTestCase;
use Glpi\Asset\AssetDefinition;
use Glpi\Search\SearchOption;
use PHPUnit\Framework\Attributes\DataProvider;

class SearchOptionTest extends DbTestCase
{
    public static function getDefaultToViewProvider(): array
    {
        return [
            [\Computer::class, [1, 80]], // Name, Entity
            [\ITILCategory::class, [1, 80]], // Completename, Entity
            [\Item_DeviceSimcard::class, [10, 80]], // Serial (marked as the name field), Entity
            [\Ticket::class, [2, 1, 80]], // ID (Always shown for ITIL Objects), Name, Entity
            [\KnowbaseItem::class, [1, 80]], // Name, Entity Target
            [\RSSFeed::class, [1]], // Name (Not Entity assignable)
            [\AllAssets::class, [1, 80]], // Name, Entity
            [AssetDefinition::class, [2]], // Label (Not Entity assignable)
        ];
    }

    #[DataProvider('getDefaultToViewProvider')]
    public function testGetDefaultToView(string $itemtype, array $expected): void
    {
        $this->login();
        $this->assertEquals(
            $expected,
            SearchOption::getDefaultToView($itemtype)
        );
    }
}
