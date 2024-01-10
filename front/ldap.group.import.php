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

/** @var array $CFG_GLPI */
global $CFG_GLPI;

include('../inc/includes.php');


Session::checkRightsOr('group', [CREATE, UPDATE]);
Session::checkRight('user', User::UPDATEAUTHENT);

Html::header(__('LDAP directory link'), $_SERVER['PHP_SELF'], "admin", "group", "ldap");

if (isset($_GET['next']) || !isset($_SESSION['ldap_server']) && !isset($_POST['ldap_server'])) {
    AuthLDAP::ldapChooseDirectory($_SERVER['PHP_SELF']);
} else {
    if (isset($_POST["change_ldap_filter"])) {
        if (isset($_POST["ldap_filter"])) {
            $_SESSION["ldap_group_filter"] = $_POST["ldap_filter"];
        }
        if (isset($_POST["ldap_filter2"])) {
            $_SESSION["ldap_group_filter2"] = $_POST["ldap_filter2"];
        }
        Html::redirect($_SERVER['PHP_SELF']);
    } else {
        if (!isset($_GET['start'])) {
            $_GET['start'] = 0;
        }
        if (isset($_SESSION["ldap_import"])) {
            unset($_SESSION["ldap_import"]);
        }

        if (!isset($_SESSION["ldap_server"])) {
            if (isset($_POST["ldap_server"])) {
                $_SESSION["ldap_server"] = $_POST["ldap_server"];
            } else {
                Html::redirect($CFG_GLPI["root_doc"] . "/front/ldap.php");
            }
        }

        if (!AuthLDAP::testLDAPConnection($_SESSION["ldap_server"])) {
            unset($_SESSION["ldap_server"]);
            echo "<div class='center b'>" . __('Unable to connect to the LDAP directory') . "<br>";
            echo "<a href='" . $_SERVER['PHP_SELF'] . "?next=listservers'>" . __('Back') . "</a></div>";
        } else {
            if (!isset($_SESSION["ldap_group_filter"])) {
                $_SESSION["ldap_group_filter"] = '';
            }
            if (!isset($_SESSION["ldap_group_filter2"])) {
                $_SESSION["ldap_group_filter2"] = '';
            }
            if (isset($_GET["order"])) {
                $_SESSION["ldap_sortorder"] = $_GET["order"];
            }
            if (!isset($_SESSION["ldap_sortorder"])) {
                $_SESSION["ldap_sortorder"] = "ASC";
            }

            AuthLDAP::displayLdapFilter($_SERVER['PHP_SELF'], false);

            AuthLDAP::showLdapGroups(
                $_SERVER['PHP_SELF'],
                $_GET['start'],
                0,
                $_SESSION["ldap_group_filter"],
                $_SESSION["ldap_group_filter2"],
                $_SESSION["glpiactive_entity"],
                $_SESSION["ldap_sortorder"]
            );
        }
    }
}

Html::footer();
