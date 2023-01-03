<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

include('../inc/includes.php');

$group = new Group();
$group->checkGlobal(UPDATE);
Session::checkRight('user', User::UPDATEAUTHENT);

Html::header(__('LDAP directory link'), $_SERVER['PHP_SELF'], "admin", "group", "ldap");

if (isset($_SESSION["ldap_import"])) {
    unset($_SESSION["ldap_import"]);
}
if (isset($_SESSION["ldap_import_entities"])) {
    unset($_SESSION["ldap_import_entities"]);
}
if (isset($_SESSION["ldap_server"])) {
    unset($_SESSION["ldap_server"]);
}
if (isset($_SESSION["entity"])) {
    unset($_SESSION["entity"]);
}
if (isset($_SESSION["ldap_sortorder"])) {
    unset($_SESSION["ldap_sortorder"]);
}

//Reset session variable related to filters
if (isset($_SESSION["ldap_group_filter"])) {
    unset($_SESSION["ldap_group_filter"]);
}
if (isset($_SESSION["ldap_group_filter2"])) {
    unset($_SESSION["ldap_group_filter2"]);
}

echo TemplateRenderer::getInstance()->render(
    'pages/admin/ldap.groups.html.twig'
);

Html::footer();
