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

Session::checkRight("config", UPDATE);
if (
    isset($_POST['itemtype'])
    && isset($_POST['id_field'])
) {
    $blacklist = new Fieldblacklist();
    if (isset($_POST['id']) && ($_POST['id'] > 0)) {
        $blacklist->getFromDB($_POST['id']);
    } else {
        $blacklist->getEmpty();
    }
    $blacklist->fields['field']    = $_POST['id_field'];
    $blacklist->fields['itemtype'] = $_POST['itemtype'];
    $blacklist->selectValues($_POST['id_field']);
}
