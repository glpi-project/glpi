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

require_once(__DIR__ . '/_check_webserver_config.php');

Session::checkRightsOr('group', [CREATE, UPDATE]);
Session::checkRight('user', User::UPDATEAUTHENT);
AuthLDAP::manageRequestValues(false);

Html::header(__('LDAP directory link'), '', "admin", "group", "ldap");

$authldap = new AuthLDAP();
$authldap->getFromDB($_REQUEST['authldaps_id'] ?? 0);
AuthLDAP::showGroupImportForm($authldap);

if (
    (isset($_REQUEST['authldaps_id']) && ((int) $_REQUEST['authldaps_id'] > 0))
    && (isset($_REQUEST['search']) || isset($_REQUEST['start']) || isset($_REQUEST['glpilist_limit']))
) {
    AuthLDAP::showLdapGroups(
        $_REQUEST['start'] ?? 0,
        0,
        $_REQUEST["ldap_group_filter"] ?? '',
        $_REQUEST["ldap_group_filter2"] ?? '',
        $_SESSION["glpiactive_entity"]
    );
}

Html::footer();
