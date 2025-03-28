<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Exception\Http\BadRequestHttpException;

$itils_validationsteps_id = (int)($_GET['itils_validationsteps_id'] ?: 0);
$itils_validation_type = $_GET['itils_validation_type'] ?? null;

if ($itils_validationsteps_id === 0) {
    throw new BadRequestHttpException("Bad request: invalid 'itils_validationsteps_id' in request parameters (\$_GET)");
}

if (!in_array($itils_validation_type, [TicketValidationStep::class, ChangeValidationStep::class])) {
    throw new BadRequestHttpException("Bad request: unexpected ValidationStep type. " . htmlescape($itils_validation_type));
}

$ivs = new $itils_validation_type();

if ($ivs->getFromDB($itils_validationsteps_id) === false) {
    throw new BadRequestHttpException("Bad request: no 'ITIL_ValidationStep' found with id #$itils_validationsteps_id");
}

// form display
TemplateRenderer::getInstance()->display(
    'components/itilobject/form_itils_validationstep.html.twig',
    [
        'item' => $ivs,
        'no_header' => true,
    ]
);
