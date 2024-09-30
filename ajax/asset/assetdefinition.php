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

use Glpi\Asset\AssetDefinition;

/** @var \Glpi\Controller\LegacyFileLoadController $this */
$this->setAjax();

Session::checkRight(AssetDefinition::$rightname, READ);

header("Content-Type: application/json; charset=UTF-8");

if ($_GET['action'] === 'get_all_fields') {
    $definition = new AssetDefinition();
    if (!$definition->getFromDB($_GET['assetdefinitions_id'])) {
        http_response_code(404);
        exit();
    }
    $all_fields = $definition->getAllFields();
    $field_results = [];
    foreach ($all_fields as $k => $v) {
        $field_info = is_array($v) ? $v : ['text' => $v];
        if (!empty($_POST['searchText']) && stripos($field_info['text'], $_POST['searchText']) === false) {
            continue;
        }
        $field_info['id'] = $k;
        $field_results[] = $field_info;
    }
    echo json_encode([
        'results' => $field_results,
        'count' => count($all_fields)
    ], JSON_THROW_ON_ERROR);
    exit();
}
http_response_code(400);
