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

namespace Glpi\DependencyInjection\Compiler;

use Glpi\Application\Environment;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Unregister Twig extensions that Symfony TwigBundle auto-registers but that
 * GLPI does not want to activate yet.
 *
 * Some Symfony-provided extensions collide with GLPI extensions.
 * i.g. `twig.extension.routing` exposes `path` and `url` Twig functions,
 * which conflict with `Glpi\Application\View\Extension\RoutingExtension`.
 *
 */
final class RemoveUnwantedTwigExtensionsPass implements CompilerPassInterface
{
    /**
     * Twig extension tag to unload.
     * Can be listed by doing `bin/console symfony:debug:container --tag=twig.extension`
     */
    private const UNWANTED_EXTENSION_IDS = [
        'twig.extension.routing',       // conflicts with Glpi\Application\View\Extension\RoutingExtension (path/url)
        'twig.extension.httpfoundation',
        'twig.extension.httpkernel',
        'twig.extension.assets',
        'twig.extension.form',
        'twig.extension.weblink',
        'twig.extension.serializer',
        'twig.extension.yaml',
        'twig.extension.expression',
        'twig.extension.emoji',
        'twig.extension.htmlsanitizer',
        'twig.extension.debug.stopwatch',
        'twig.extension.debug',
        'twig.extension.profiler',
        'workflow.twig_extension',
    ];

    public function process(ContainerBuilder $container): void
    {
        foreach (self::UNWANTED_EXTENSION_IDS as $id) {
            // We allow debug extensions in dev env
            if (Environment::get()->shouldEnableExtraDevAndDebugTools()) {
                if (in_array($id, ['twig.extension.debug', 'twig.extension.debug.stopwatch', 'twig.extension.profiler'])) {
                    continue;
                }
            }

            if ($container->hasDefinition($id)) {
                $container->getDefinition($id)->clearTag('twig.extension');
            }
        }
    }
}
