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

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

if (!empty($_POST['type']) && isset($_POST['items_id']) && ($_POST['items_id'] > 0)) {
    $prefix = '';
    $suffix = '';
    if (!empty($_POST['prefix'])) {
        $prefix = $_POST['prefix'] . '[';
        $suffix = ']';
    }

    switch ($_POST['type']) {
        case 'Group':
        case 'Profile':
            $params = ['value' => $_SESSION['glpiactive_entity'],
                'name'  => $prefix . 'entities_id' . $suffix
            ];
            if (Session::canViewAllEntities()) {
                $params['toadd'] = [-1 => __('No restriction')];
            }
            if (isset($_POST['entity']) && $_POST['entity'] >= 0) {
                $params['entity'] = $_POST['entity'];
                $params['entity_sons'] = $_POST['is_recursive'] ?? false;
            }
            echo "<table class='tab_format'><tr><td>";
            echo htmlescape(Entity::getTypeName(1));
            echo "</td><td>";
            Entity::dropdown($params);
            echo "</td><td>";
            echo __s('Child entities');
            echo "</td><td>";
            Dropdown::showYesNo($prefix . 'is_recursive' . $suffix);
            echo "</td></tr></table>";
            break;
    }
}
