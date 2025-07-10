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

namespace Glpi\Controller\Plugin;

use Document;
use Glpi\Controller\AbstractController;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use Plugin;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function Safe\base64_decode;

final class LogoController extends AbstractController
{
    /**
     * Base64 encoded transparent 1x1 PNG.
     */
    private const EMPTY_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=';

    #[SecurityStrategy(Firewall::STRATEGY_ADMIN_ACCESS)]
    #[Route(
        "/Plugin/{plugin_key}/Logo",
        name: "glpi_plugin_logo",
        methods: "GET"
    )]
    public function __invoke(string $plugin_key): Response
    {
        // Try to serve local logo file.
        $plugin_path = Plugin::getPhpDir($plugin_key);
        $logo = \sprintf('%s/logo.png', $plugin_path);

        if (Document::isImage($logo)) {
            return new BinaryFileResponse($logo);
        }

        // Fallback to an empty PNG to prevent 500 error that would pollute logs.
        $empty_png = base64_decode(self::EMPTY_PNG);
        return new Response(
            $empty_png,
            status: 404,
            headers: [
                'Content-Type' => 'image/png',
            ]
        );
    }
}
