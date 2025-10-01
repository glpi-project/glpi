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

namespace Glpi\Routing\Attribute;

use Attribute;
use CommonDBTM;
use Symfony\Component\Routing\Attribute\Route;

use function Safe\preg_replace;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class ItemtypeFormRoute extends Route
{
    /**
     * @phpstan-param class-string<CommonDBTM> $itemtype
     */
    public function __construct(string $itemtype)
    {
        $path = $itemtype::getFormURL(false);

        if (\isPluginItemType($itemtype)) {
            // Plugin routes path should not contain the `/plugins/{plugin_key}` prefix that is added automatically.
            // @see `\Glpi\Router\PluginRoutesLoader::load()`
            $path = preg_replace('#^/plugins/[^/]+(/.*)?$#', '$1', $path);
        }

        parent::__construct(
            path: $path,
            name: 'glpi_itemtype_' . \strtolower($itemtype) . '_form',
        );
    }
}
