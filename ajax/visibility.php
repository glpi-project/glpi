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

global $CFG_GLPI;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkCentralAccess();

if (
    isset($_POST['type']) && !empty($_POST['type'])
    && isset($_POST['right'])
) {
    $display = false;
    $rand    = mt_rand();
    $prefix = '';
    $suffix = '';
    if (isset($_POST['prefix']) && !empty($_POST['prefix'])) {
        $prefix = $_POST['prefix'] . '[';
        $suffix = ']';
    } else {
        $_POST['prefix'] = '';
    }

    echo "<div class='d-flex'>";
    switch ($_POST['type']) {
        case 'User':
            $params = [
                'right' => isset($_POST['allusers']) ? 'all' : $_POST['right'],
                'name' => $prefix . 'users_id' . $suffix,
            ];
            User::dropdown($params);
            $display = true;
            break;

        case 'Group':
            $params             = ['rand' => $rand,
                'name' => $prefix . 'groups_id' . $suffix,
            ];
            $params['toupdate'] = ['value_fieldname'
                                                  => 'value',
                'to_update'  => "subvisibility$rand",
                'url'        => $CFG_GLPI["root_doc"] . "/ajax/subvisibility.php",
                'moreparams' => ['items_id' => '__VALUE__',
                    'type'     => $_POST['type'],
                    'prefix'   => $_POST['prefix'],
                ],
            ];

            Group::dropdown($params);
            echo "<span id='subvisibility$rand'></span>";
            $display = true;
            break;

        case 'Entity':
            Entity::dropdown([
                'value'       => $_SESSION['glpiactive_entity'],
                'name'        => $prefix . 'entities_id' . $suffix,
                'entity'      => $_POST['entity'] ?? -1,
                'entity_sons' => $_POST['is_recursive'] ?? false,
            ]);
            echo '<div class="ms-3">' . __s('Child entities') . '</div>';
            Dropdown::showYesNo($prefix . 'is_recursive' . $suffix);
            $display = true;
            break;

        case 'Profile':
            $checkright   = (READ | CREATE | UPDATE | PURGE);
            $righttocheck = $_POST['right'];
            if ($_POST['right'] == 'faq') {
                $righttocheck = 'knowbase';
                $checkright   = KnowbaseItem::READFAQ;
            }
            $params             = [
                'rand'      => $rand,
                'name'      => $prefix . 'profiles_id' . $suffix,
                'condition' => [
                    'glpi_profilerights.name'     => $righttocheck,
                    'glpi_profilerights.rights'   => ['&', $checkright],
                ],
            ];
            $params['toupdate'] = ['value_fieldname'
                                                  => 'value',
                'to_update'  => "subvisibility$rand",
                'url'        => $CFG_GLPI["root_doc"] . "/ajax/subvisibility.php",
                'moreparams' => ['items_id' => '__VALUE__',
                    'type'     => $_POST['type'],
                    'prefix'   => $_POST['prefix'],
                ],
            ];

            Profile::dropdown($params);
            echo "<span id='subvisibility$rand'></span>";
            $display = true;
            break;
    }

    if ($display && (!isset($_POST['nobutton']) || !$_POST['nobutton'])) {
        echo "<input type='submit' name='addvisibility' value=\"" . _sx('button', 'Add') . "\"
                   class='btn btn-primary ms-3'>";
    }
    echo "</div>";
}
