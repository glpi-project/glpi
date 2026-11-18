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

use Glpi\Application\Environment;

return static function (ContainerConfigurator $container): void {
    // Queries are collected for the web profiler only, as the collected data grows with each query
    // and would make long running processes (e.g. tests suite, CLI commands) exhaust memory.
    $profiling = Environment::get()->shouldEnableExtraDevAndDebugTools();

    $container->extension('doctrine', [
        'dbal' => [
            'default_connection' => 'default',
            // Connection parameters are provided at runtime by `Glpi\DBAL\Doctrine\Middleware\LegacyConfigurationMiddleware`.
            'connections' => [
                'default' => [
                    'driver'    => 'mysqli',
                    // Queries are already logged by GLPI SQL debug tools.
                    'logging'   => false,
                    'profiling' => $profiling,
                ],
                'replica' => [
                    'driver'    => 'mysqli',
                    'logging'   => false,
                    'profiling' => $profiling,
                ],
            ],
        ],
    ]);
};
