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

namespace Glpi\Controller\Security;

use Config;
use Glpi\Controller\AbstractController;
use Glpi\Security\ContentSecurityPolicy\CspState;
use Glpi\Toolbox\URL;
use Plugin;
use Session;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Toolbox;

/**
 * Explains why the Content-Security-Policy is disabled and how to make plugins compatible with it.
 */
final class CspPluginsController extends AbstractController
{
    #[Route("/CSP/Plugins", name: "glpi_csp_plugins", methods: ['GET'])]
    public function __invoke(): Response
    {
        Session::checkRight(Config::$rightname, UPDATE);

        $plugins = \array_map(
            static fn(string $plugin_key): array => [
                'name'     => Toolbox::stripTags(self::getPluginInfo($plugin_key, 'name')) ?: $plugin_key,
                'version'  => Toolbox::stripTags(self::getPluginInfo($plugin_key, 'version')),
                'author'   => Toolbox::stripTags(self::getPluginInfo($plugin_key, 'author')),
                'homepage' => URL::sanitizeURL(self::getPluginInfo($plugin_key, 'homepage')),
            ],
            CspState::getIncompatiblePlugins()
        );

        return $this->render('pages/setup/csp_plugins.html.twig', [
            'plugins' => $plugins,
        ]);
    }

    /**
     * Get a plugin information, as a string.
     */
    private static function getPluginInfo(string $plugin_key, string $info): string
    {
        $value = Plugin::getInfo($plugin_key, $info);

        return \is_string($value) ? $value : '';
    }
}
