<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Twig\Extra\String\StringExtension;

return static function (ContainerConfigurator $container): void {
    // Without Symfony Flex, twig/extra-* packages are not auto-configured.
    // Each one must be registered here: extension (twig.extension tag) and,
    // when the filter uses a runtime, the runtime class (twig.runtime tag)
    // plus any interface binding it depends on.
    // Remember to update this block whenever a new twig/* extra package is added to composer.json.
    $services = $container->services();
    $services->set(StringExtension::class)->tag('twig.extension');
};
