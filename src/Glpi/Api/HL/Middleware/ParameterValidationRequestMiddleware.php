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
use Glpi\Api\HL\Doc\Parameter;
use Glpi\Api\HL\Doc\Schema;
use Glpi\Api\HL\Doc\SchemaValidator;
use Glpi\Api\HL\Router;

/**
 * Validate the parameters declared by the matched route (first stage of the input validation).
 * The body is validated later against the resolved schema by {@link \Glpi\Api\HL\ResourceAccessor}.
 *
 * Missing required parameters are always reported. Before API v4, they are reported in the original format.
 * From API v4, the type, format and enum of query and header parameters are also validated and all the problems are
 * reported at once in the same format as the validation of the body.
 *
 * Constraints other than the type, format and enum (like `maxLength`) are not validated here as they are not kept by {@link Schema}.
 */
class ParameterValidationRequestMiddleware extends AbstractMiddleware implements RequestMiddlewareInterface
{
    public function process(MiddlewareInput $input, callable $next): void
    {
        $request = $input->request;
        $api_version = Router::normalizeAPIVersion($request->getHeaderLine('GLPI-API-Version') ?: Router::API_VERSION);
        $is_strict = SchemaValidator::isStrictVersion($api_version);

        $params = $input->route_path->getRouteDoc($request->getMethod())?->getParameters() ?? [];
        $missing = [];
        $errors = [];
        foreach ($params as $param) {
            $name = $param->getName();
            if (!$request->hasParameter($name)) {
                if ($param->getRequired()) {
                    $missing[] = $name;
                    $errors[$name][] = [
                        'error' => 'required',
                        'message' => 'This field is required',
                    ];
                }
                continue;
            }

            $schema = $param->getSchema();
            if ($is_strict && $schema instanceof Schema && in_array($param->getLocation(), [Parameter::LOCATION_QUERY, Parameter::LOCATION_HEADER], true)) {
                $param_errors = SchemaValidator::validateValue($schema->toArray(), $request->getParameter($name), ['api_version' => $api_version]);
                if ($param_errors !== []) {
                    $errors[$name] = $param_errors;
                }
            }
        }

        if ($errors !== []) {
            $input->response = $is_strict
                ? AbstractController::getInvalidInputErrorResponse($errors)
                : AbstractController::getInvalidParametersErrorResponse(['missing' => $missing]);
            return;
        }

        $next($input);
    }
}
