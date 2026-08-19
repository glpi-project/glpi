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

namespace Glpi\Controller\UI\Illustration;

use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use Glpi\UI\IllustrationManager;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CustomIllustrationController extends AbstractController
{
    /**
     * Number of seconds a custom illustration response may be kept in the
     * browser's cache before it must be revalidated with the server.
     *
     * Illustrations are only replaced by an explicit re-upload (see
     * IllustrationManager::saveCustomIllustration()), so a long duration is
     * safe here; the ETag/Last-Modified pair still lets the browser detect
     * a change immediately if one occurs.
     */
    private const CACHE_MAX_AGE = 604800; // 7 days

    public function __construct(
        private IllustrationManager $illustration_manager
    ) {}

    #[SecurityStrategy(Firewall::STRATEGY_AUTHENTICATED)]
    #[Route(
        "/UI/Illustration/CustomIllustration/{id}",
        name: "glpi_ui_illustration_custom_illustration",
        methods: "GET",
    )]
    public function __invoke(string $id): Response
    {
        $file = $this->illustration_manager->getCustomIllustrationFile($id);
        if (!$file) {
            throw new BadRequestHttpException();
        }

        // Read parameters
        $response = new BinaryFileResponse($file);
        $response->setAutoEtag();
        $response->setAutoLastModified();
        // The route requires an authenticated session, so the response must stay
        // "private" (per-user), but it can still be cached by the browser instead
        // of being re-fetched on every page that displays the illustration.
        $response->setCache([
            'private' => true,
            'max_age' => self::CACHE_MAX_AGE,
        ]);

        return $response;
    }
}
