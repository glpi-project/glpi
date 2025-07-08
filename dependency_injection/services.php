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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Glpi\DependencyInjection\PublicService;
use Glpi\Error\ErrorHandler;

return static function (ContainerConfigurator $container): void {
    $projectDir = dirname(__DIR__);
    $parameters = $container->parameters();
    $services = $container->services();

    // Default secret, just in case
    $parameters->set('glpi.default_secret', bin2hex(random_bytes(32)));
    $parameters->set('env(APP_SECRET_FILE)', $projectDir . '/config/glpicrypt.key');
    $parameters->set('kernel.secret', env('default:glpi.default_secret:file:APP_SECRET_FILE'));

    // Prevent low level errors (e.g. warning) to be converted to exception in dev environment
    $parameters->set('debug.error_handler.throw_at', ErrorHandler::FATAL_ERRORS);

    $services = $services
        ->defaults()
            ->autowire()
            ->autoconfigure()
        ->instanceof(PublicService::class)->public()
    ;

    $services->load('Glpi\Controller\\', $projectDir . '/src/Glpi/Controller');
    $services->load('Glpi\Http\\', $projectDir . '/src/Glpi/Http');
    $services->load('Glpi\Kernel\\Listener\\', $projectDir . '/src/Glpi/Kernel/Listener');
    $services->load('Glpi\DependencyInjection\\', $projectDir . '/src/Glpi/DependencyInjection');
    $services->load('Glpi\Progress\\', $projectDir . '/src/Glpi/Progress')
        ->exclude([
            $projectDir . '/src/Glpi/Progress/ConsoleProgressIndicator.php',
            $projectDir . '/src/Glpi/Progress/StoredProgressIndicator.php',
        ]);
    $services->load(
        'Glpi\Form\Condition\\',
        $projectDir . '/src/Glpi/Form/Condition/*Manager.php'
    );
    $services->load(
        'Glpi\UI\\',
        $projectDir . '/src/Glpi/UI/*Manager.php'
    );

    // Prevent Symfony to register its own default logger.
    // @see \Symfony\Component\HttpKernel\DependencyInjection\LoggerPass
    $services->set('logger')->synthetic();
};
