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

namespace Glpi\Controller\Rule;

use Entity;
use Glpi\Controller\GenericFormController;
use Glpi\Exception\Http\BadRequestHttpException;
use Rule;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class RuleFormController extends GenericFormController
{
    public function __invoke(Request $request): Response
    {
        $class = $request->attributes->getString('class');

        if (!$class) {
            throw new BadRequestHttpException('The "class" attribute is mandatory for rule form routes.');
        }

        if (!\is_a($class, Rule::class, true)) {
            throw new BadRequestHttpException('The "class" attribute must be a valid rule class.');
        }

        if (
            $request->request->has('add')
            && $request->request->has('profiles_id')
            && $request->request->has('entities_id')
            && $request->request->has('is_recursive')
        ) {
            $entity = new Entity();
            $entity->executeAddRule($request->request->all());
        }

        return parent::__invoke($request);
    }
}
