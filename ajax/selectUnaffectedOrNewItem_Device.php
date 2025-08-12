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

/**
 * @since 0.85
 */

use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use Glpi\Exception\Http\BadRequestHttpException;

global $DB;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkCentralAccess();

// Make a select box
if (
    $_POST['items_id']
    && $_POST['itemtype'] && class_exists($_POST['itemtype'])
) {
    $devicetype = $_POST['itemtype'];
    if (!is_subclass_of($devicetype, CommonDevice::class)) {
        throw new BadRequestHttpException();
    }
    $linktype   = $devicetype::getItem_DeviceType();
    $specificities = $linktype::getSpecificities();
    $specificities = array_filter(
        $specificities,
        static fn($spec) => ($spec['datatype'] ?? '') !== 'dropdown' && (!isset($spec['nodisplay']) || !$spec['nodisplay'])
    );

    if (count($specificities)) {
        $keys = array_keys($specificities);
        $name_field = QueryFunction::concat_ws(
            separator: new QueryExpression($DB::quoteValue(' - ')),
            params: array_map(static fn($k) => QueryFunction::ifnull($k, new QueryExpression($DB::quoteValue(''))), $keys),
            alias: 'name'
        );
        $label_pattern = implode(' - ', array_map(
            static fn($key) => $specificities[$key]['short name'] ?? $key,
            $keys
        ));
    } else {
        $name_field = 'id AS name';
        $label_pattern = __('ID');
    }
    $result = $DB->request(
        [
            'SELECT' => ['id', $name_field],
            'FROM'   => $linktype::getTable(),
            'WHERE'  => [
                $devicetype::getForeignKeyField() => $_POST['items_id'],
                'itemtype'                        => '',
            ],
        ]
    );
    $devices = [];
    foreach ($result as $row) {
        $devices[$row['id']] = $row['name'] ?: $row['id'];
    }
    TemplateRenderer::getInstance()->display('components/assets/link_existing_or_new_item_device.html.twig', [
        'devices' => $devices,
        'linktype' => $linktype,
        'label_pattern' => $label_pattern,
    ]);
}
