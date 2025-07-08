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

use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;

use function Safe\json_encode;

// Direct access to file

header("Content-Type: application/json; charset=UTF-8");

// Tech only
if (Session::getCurrentInterface() !== "central") {
    throw new AccessDeniedHttpException();
}

// Read parameter and load pending reason
$pending_reason = PendingReason::getById($_REQUEST['pendingreasons_id'] ?? null);
if (!$pending_reason) {
    throw new BadRequestHttpException();
}

echo json_encode([
    'followup_frequency'          => $pending_reason->fields['followup_frequency'],
    'followups_before_resolution' => $pending_reason->fields['followups_before_resolution'],
]);
