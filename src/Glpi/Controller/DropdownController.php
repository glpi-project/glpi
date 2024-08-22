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

namespace Glpi\Controller;

use CommonDropdown;
use Html;
use Search;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class DropdownController extends AbstractController
{
    #[Route("/Dropdown/{class}", name: "glpi_dropdown")]
    public function __invoke(Request $request): Response
    {
        $class = $request->attributes->getString('class');

        if (!$class) {
            throw new BadRequestException('The "class" attribute is mandatory for dropdown routes.');
        }

        if (!\is_subclass_of($class, CommonDropdown::class)) {
            throw new BadRequestException('The "class" attribute is mandatory for dropdown routes.');
        }

        return new StreamedResponse(function () use ($class, $request) {
            $dropdown = new $class();
            $this->loadDropdown($request, $dropdown);
        });
    }

    public static function loadDropdown(Request $request, CommonDropdown $dropdown): void
    {
        if (!$dropdown->canView()) {
            throw new AccessDeniedHttpException();
        }

        $dropdown::displayCentralHeader();

        Search::show($dropdown::class);

        Html::footer();
    }
}
