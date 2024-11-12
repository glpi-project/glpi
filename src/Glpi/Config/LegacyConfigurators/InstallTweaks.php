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

use Glpi\Config\ConfigProviderHasRequestTrait;
use Glpi\Config\ConfigProviderWithRequestInterface;
use Glpi\Config\LegacyConfigProviderInterface;

final class InstallTweaks implements LegacyConfigProviderInterface, ConfigProviderWithRequestInterface
{
    use ConfigProviderHasRequestTrait;

    public function execute(): void
    {
        $request = $this->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/install/')) {
            return;
        }

        /**
         * @var array $CFG_GLPI
         */
        global $CFG_GLPI;

        $CFG_GLPI['url_base'] = $request->getBasePath();
        \Config::detectRootDoc();
    }
}
