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

namespace Glpi\Features;

use Glpi\Toolbox\MapperInterface;
use Glpi\Toolbox\SingletonTrait;
use InvalidArgumentException;
use Override;

final class CloneMapper implements MapperInterface
{
    use SingletonTrait;

    /** @var array<class-string<\CommonDBTM>, array<int, int>> */
    private array $mapped_ids = [];

    #[Override]
    /** @param class-string<\CommonDBTM> $class */
    public function addMappedItem(string $class, string|int $old_id, int $new_id): void
    {
        if (!isset($this->mapped_ids[$class])) {
            $this->mapped_ids[$class] = [];
        }

        $this->mapped_ids[$class][$old_id] = $new_id;
    }

    #[Override]
    /** @param class-string<\CommonDBTM> $class */
    public function getItemId(string $class, string|int $old_id): int
    {
        $new_id = $this->mapped_ids[$class][$old_id] ?? null;
        if (!$new_id) {
            $target = "$class::$old_id";
            throw new InvalidArgumentException("Item $target was never cloned");
        }

        return $this->mapped_ids[$class][$old_id];
    }

    public function cleanMappedIds(): void
    {
        $this->mapped_ids = [];
    }
}
