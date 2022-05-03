<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

if (!defined('GLPI_ROOT')) {
    include('../inc/includes.php');
}

Session::checkRight("user", User::IMPORTEXTAUTHUSERS);

// Need REQUEST to manage initial walues and posted ones
AuthLDAP::manageValuesInSession($_REQUEST);

if (isset($_SESSION['ldap_import']['_in_modal']) && $_SESSION['ldap_import']['_in_modal']) {
    $_REQUEST['_in_modal'] = 1;
}

Html::header(__('LDAP directory link'), $_SERVER['PHP_SELF'], "admin", "user", "ldap");

if (isset($_GET['start'])) {
    $_SESSION['ldap_import']['start'] = $_GET['start'];
}
if (isset($_GET['order'])) {
    $_SESSION['ldap_import']['order'] = $_GET['order'];
}
if ($_SESSION['ldap_import']['action'] == 'show') {
    $authldap = new AuthLDAP();
    $authldap->getFromDB($_SESSION['ldap_import']['authldaps_id']);

    AuthLDAP::showUserImportForm($authldap);

    if (
        isset($_SESSION['ldap_import']['authldaps_id'])
        && ($_SESSION['ldap_import']['authldaps_id'] != NOT_AVAILABLE)
        && (isset($_POST['search']) || isset($_GET['start']) || isset($_POST['glpilist_limit']))
    ) {
        echo "<br />";
        AuthLDAP::searchUser($authldap);
    }
}

Html::footer();
