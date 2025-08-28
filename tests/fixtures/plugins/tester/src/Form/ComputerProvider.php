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

namespace GlpiPlugin\Tester\Form;

use Computer;
use ComputerType;
use Glpi\Form\ServiceCatalog\Provider\LeafProviderInterface;
use Glpi\Form\ServiceCatalog\ItemRequest;
use Override;

/** @implements LeafProviderInterface<ComputerForServiceCatalog> */
final class ComputerProvider implements LeafProviderInterface
{
    #[Override]
    public function getItems(ItemRequest $item_request): array
    {
        $target_type = new ComputerType();
        if (!$target_type->getFromDBByCrit(['name' => 'test'])) {
            return [];
        }

        $computers = [];
        $raw_computers = (new Computer())->find([
            ComputerType::getForeignKeyField() => $target_type->getId(),
        ]);
        foreach ($raw_computers as $raw_computer) {
            $computer = new Computer();
            $computer->getFromResultSet($raw_computer);
            $computer->post_getFromDB();
            $computers[] = new ComputerForServiceCatalog($computer);
        }

        return $computers;
    }

    #[Override]
    public function getItemsLabel(): string
    {
        return __("Computers with the 'test' type");
    }

    #[Override]
    public function getWeight(): int
    {
        return 30;
    }
}
