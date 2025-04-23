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

namespace Glpi\Controller;

use CommonGLPI;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class GenericListController extends AbstractController
{
    public function __invoke(Request $request): Response
    {
        $class = $request->attributes->getString('class');

        $this->checkIsValidClass($class);

        return $this->render('pages/generic_list.html.twig', [
            'class' => $class,
        ]);
    }

    private function checkIsValidClass(string $class): void
    {
        if ($class === '') {
            throw new BadRequestHttpException('The "class" attribute is mandatory for itemtype routes.');
        }

        if (!\class_exists($class)) {
            throw new BadRequestHttpException(\sprintf("Class \"%s\" does not exist.", $class));
        }

        if (!\is_subclass_of($class, CommonGLPI::class)) {
            throw new BadRequestHttpException(\sprintf("Class \"%s\" is not a valid itemtype.", $class));
        }

        if (!$class::canView()) {
            throw new AccessDeniedHttpException();
        }
    }
}
