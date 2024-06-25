<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\Config\LegacyConfigurators;

use Glpi\Asset\AssetDefinitionManager;
use Glpi\Config\LegacyConfigProviderInterface;
use Plugin;
use Update;

final readonly class InitializePlugins implements LegacyConfigProviderInterface
{
    public function execute(): void
    {
        if (!Update::isDbUpToDate()) {
            return;
        }

        /**
         * @var bool|null $PLUGINS_EXCLUDED
         */
        global $PLUGINS_EXCLUDED;

        // Assets classes autoload
        AssetDefinitionManager::getInstance()->registerAssetsAutoload();

        /* On startup, register all plugins configured for use. */
        $PLUGINS_EXCLUDED = isset($PLUGINS_EXCLUDED) ? $PLUGINS_EXCLUDED : [];
        $plugin = new Plugin();
        $plugin->init(true, $PLUGINS_EXCLUDED);

        // Assets classes bootstraping.
        // Must be done after plugins initialization, to allow plugin to register new capacities.
        AssetDefinitionManager::getInstance()->boostrapAssets();
    }
}
