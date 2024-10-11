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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class GenericFormController extends AbstractController
{
    #[Route("/{type}/form", name: "generic_form")]
    public function __invoke(Request $request): Response
    {
        $type = $request->attributes->getString('type');

        $class = $this->getClassFromType($type);

        if (!$class) {
            throw new NotFoundHttpException(\sprintf('No class found for type "%s".', $type));
        }

        if (!$class::canView()) {
            throw new AccessDeniedHttpException();
        }

        return new Response('Todo...');
    }

    /**
     * @return class-string<\CommonDBTM>|null
     */
    private function getClassFromType(string $type): ?string
    {
        $class = (new \DbUtils())->fixItemtypeCase($type);

        if (
            $class
            && \class_exists($class)
            && \is_subclass_of($class, \CommonDBTM::class)
        ) {
            return $this->normalizeClass($class);
        }

        $namespacedClass = \preg_replace_callback('~\\\([a-z])~Uu', static fn($i) => '\\' . \ucfirst($i[1]), 'Glpi\\' . \str_replace('/', '\\', $class));

        if (
            $namespacedClass
            && \class_exists($namespacedClass)
            && \is_subclass_of($namespacedClass, \CommonDBTM::class)
        ) {
            return $this->normalizeClass($namespacedClass);
        }

        return null;
    }

    private function normalizeClass(string $class): string
    {
        if (!\class_exists($class)) {
            throw new \RuntimeException('Class "$class" does not exist.');
        }

        return (new \ReflectionClass($class))->getName();
    }
}
