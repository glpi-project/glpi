<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

namespace Glpi\Api\HL\Middleware;

use Glpi\Api\HL\Controller\AbstractController;
use Glpi\Api\HL\Doc\Schema;
use Glpi\Api\HL\Doc\SchemaValidator;

/**
 * Validate the parameters declared by the matched route (first stage of the input validation).
 * The body is validated later against the resolved schema by {@link \Glpi\Api\HL\ResourceAccessor}.
 *
 * Constraints other than the enum, pattern and type/format (like `maxLength`) are not validated here as they are not kept by {@link Schema}.
 */
class ParameterValidationRequestMiddleware extends AbstractMiddleware implements RequestMiddlewareInterface
{
    public function process(MiddlewareInput $input, callable $next): void
    {
        $request = $input->request;
        $properties = [];
        foreach ($input->route_path->getRouteDoc($request->getMethod())?->getParameters() ?? [] as $param) {
            // Body parameters reference a schema that is only resolved later (see ResourceAccessor)
            if ($param->getSchema() instanceof Schema) {
                $properties[$param->getName()] = [...$param->getSchema()->toArray(), 'required' => $param->getRequired()];
            }
        }

        $parameters = $request->getParameters();
        $errors = SchemaValidator::validate(['properties' => $properties], $parameters);
        if ($errors !== []) {
            $input->response = AbstractController::getInvalidInputErrorResponse($errors);
            return;
        }
        // Use the values that were coerced to the type of their parameter
        foreach (array_intersect_key($parameters, $properties) as $name => $value) {
            $request->setParameter($name, $value);
        }

        $next($input);
    }
}
