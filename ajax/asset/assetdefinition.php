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

use Glpi\Asset\AssetDefinition;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Exception\Http\NotFoundHttpException;

Session::checkRight(AssetDefinition::$rightname, READ);

if ($_REQUEST['action'] === 'get_all_fields') {
    Session::writeClose();
    header("Content-Type: application/json; charset=UTF-8");
    $definition = new AssetDefinition();
    if (!$definition->getFromDB($_GET['assetdefinitions_id'])) {
        throw new NotFoundHttpException();
    }
    $all_fields = $definition->getAllFields();
    $field_results = [];
    foreach ($all_fields as $k => $v) {
        if (!empty($_POST['searchText']) && stripos($v['text'], (string) $_POST['searchText']) === false) {
            continue;
        }
        $v['id'] = $k;
        $field_results[] = $v;
    }

    /**
     * Safe JSON response.
     * @psalm-taint-escape has_quotes
     * @psalm-taint-escape html
     */
    $response = json_encode([
        'results' => $field_results,
        'count' => count($all_fields),
    ], JSON_THROW_ON_ERROR);

    echo $response;
    return;
} elseif ($_REQUEST['action'] === 'get_core_field_editor') {
    header("Content-Type: text/html; charset=UTF-8");
    $asset_definition = new AssetDefinition();
    if (!$asset_definition->getFromDB($_GET['assetdefinitions_id'])) {
        throw new NotFoundHttpException();
    }
    $asset_definition->showFieldOptionsForCoreField($_GET['key'], $_GET['field_options'] ?? []);
    return;
}
throw new BadRequestHttpException();
