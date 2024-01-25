<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

include('../inc/includes.php');
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

switch ($_REQUEST['action']) {
    case "getItemslist":
        $params = [
            'start'              => $_REQUEST['start'],
            'is_deleted'         => $_REQUEST['is_deleted'],
            'browse'             => 1,
            'as_map'             => 0,
            'showmassiveactions' => true,
            'criteria'           => $_REQUEST['criteria'],
        ];

        $itemtype = $_REQUEST['itemtype'];
        $category_itemtype = $itemtype::getCategoryItemType($itemtype);
        $category_table = $category_itemtype::getTable();
        $item = new $itemtype();
        $so = $item->rawSearchOptions();

        $field = 0;
        foreach ($so as $value) {
            if (isset($value['field'])) {
                if (($value['field'] == 'name' || $value['field'] == 'completename') && $value['table'] == $category_table) {
                    $field = $value['id'];
                }
            }
        }

        $params['criteria'][] = [
            'link'   => "AND",
            'field'  => $field,
            'searchtype'   => "equals",
            'virtual'      => true,
            'value'  => ($_REQUEST['cat_id'] > 0) ? $_REQUEST['cat_id'] : 0,
        ];
        Search::showList($itemtype, $params);
        break;
}
http_response_code(400);
return;
