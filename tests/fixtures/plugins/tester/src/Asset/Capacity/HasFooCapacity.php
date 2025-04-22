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

namespace GlpiPlugin\Tester\Asset\Capacity;

use Override;
use Glpi\Asset\Capacity\AbstractCapacity;
use GlpiPlugin\Tester\Asset\Foo;
use Glpi\Asset\Asset;
use Glpi\Asset\CapacityConfig;

final class HasFooCapacity extends AbstractCapacity
{
    #[Override]
    public function getLabel(): string
    {
        return Foo::getTypeName();
    }

    #[Override]
    public function getIcon(): string
    {
        return Foo::getIcon();
    }

    #[Override]
    public function getCapacityUsageDescription(string $classname): string
    {
        return '';
    }

    #[Override]
    public function getSearchOptions(string $classname): array
    {
        return Foo::rawSearchOptionsToAdd();
    }

    #[Override]
    public function getCloneRelations(): array
    {
        return [
            Foo::class,
        ];
    }

    #[Override]
    public function onObjectInstanciation(Asset $object, CapacityConfig $config): void
    {
        $object->fields['_added_by_hasfoocapacity'] = 'abc';
    }
}
