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

global $CFG_GLPI;

Session::checkRight("config", UPDATE);

$config_ldap = new AuthLDAP();

if (!isset($_GET['id'])) {
    $_GET['id'] = "";
}
//LDAP Server add/update/delete
if (isset($_POST["update"])) {
    $config_ldap->update($_POST);
    Html::back();
} elseif (isset($_POST["add"])) {
    //If no name has been given to this configuration, then go back to the page without adding
    if ($_POST["name"] != "") {
        if ($newID = $config_ldap->add($_POST)) {
            if (AuthLDAP::testLDAPConnection($newID)) {
                Session::addMessageAfterRedirect(__s('Test successful'));
            } else {
                Session::addMessageAfterRedirect(__s('Test failed'), false, ERROR);
                GLPINetwork::addErrorMessageAfterRedirect();
            }
            Html::redirect($CFG_GLPI["root_doc"] . "/front/authldap.php?next=extauth_ldap&id=" . $newID);
        }
    }
    Html::back();
} elseif (isset($_POST["purge"])) {
    $config_ldap->delete($_POST, true);
    $_SESSION['glpi_authconfig'] = 1;
    $config_ldap->redirectToList();
} elseif (isset($_POST["add_replicate"])) {
    $replicate = new AuthLdapReplicate();
    unset($_POST["next"]);
    unset($_POST["id"]);
    $replicate->add($_POST);
    Html::back();
}

$menus = ['config', 'auth', 'AuthLDAP'];
AuthLDAP::displayFullPageForItem($_GET['id'], $menus, $_GET);
