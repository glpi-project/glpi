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

namespace Glpi\Tests\Controller\Tabler;

use Glpi\Application\Environment;
use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TablerIconController extends AbstractController
{
    #[Route(path: '/test/tabler/icons', name: "test_tabler_icons")]
    public function __invoke(): Response
    {
        $env = Environment::get();

        // Not needed because the route shouldn't be loaded in this case
        // but it doesn't hurt to double check.
        if (!$env->shouldLoadTestsRoutes()) {
            throw new NotFoundHttpException();
        }

        return $this->render("tests/tabler/icons.html.twig", ['title' => "Icons"]);
    }
}
