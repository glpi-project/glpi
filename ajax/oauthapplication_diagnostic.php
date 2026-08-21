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

use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Mail\OauthProvider\ApplicationDiagnostic;

use function Safe\json_encode;

header('Content-Type: application/json; charset=UTF-8');
Html::header_nocache();

// `check()` is used rather than `Session::checkRight()` so that the
// re-authentication ("sudo mode") required by this itemtype is enforced here too.
$application = new OAuthApplication();
$application->check((int) ($_POST['id'] ?? 0), READ);

$diagnostic = new ApplicationDiagnostic($application);

$action = $_POST['action'] ?? '';

if ($action === 'plan') {
    echo json_encode(['steps' => $diagnostic->getPlan()]);
} elseif ($action === 'step' && isset($_POST['step'])) {
    echo json_encode($diagnostic->runStep((string) $_POST['step']));
} else {
    throw new BadRequestHttpException();
}
