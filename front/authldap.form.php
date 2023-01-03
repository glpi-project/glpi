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

include('../inc/includes.php');

Session::checkRight("config", UPDATE);

$config_ldap = new AuthLDAP();

if (!isset($_GET['id'])) {
    $_GET['id'] = "";
}
//LDAP Server add/update/delete
if (isset($_POST["update"])) {
    if (array_key_exists('rootdn_passwd', $_POST)) {
       // Password must not be altered, it will be encrypted and never displayed, so sanitize is not necessary.
        $_POST['rootdn_passwd'] = $_UPOST['rootdn_passwd'];
    }
    $config_ldap->update($_POST);
    Html::back();
} else if (isset($_POST["add"])) {
    if (array_key_exists('rootdn_passwd', $_POST)) {
       // Password must not be altered, it will be encrypt and never displayed, so sanitize is not necessary.
        $_POST['rootdn_passwd'] = $_UPOST['rootdn_passwd'];
    }
   //If no name has been given to this configuration, then go back to the page without adding
    if ($_POST["name"] != "") {
        if ($newID = $config_ldap->add($_POST)) {
            if (AuthLDAP::testLDAPConnection($newID)) {
                Session::addMessageAfterRedirect(__('Test successful'));
            } else {
                Session::addMessageAfterRedirect(__('Test failed'), false, ERROR);
                GLPINetwork::addErrorMessageAfterRedirect();
            }
            Html::redirect($CFG_GLPI["root_doc"] . "/front/authldap.php?next=extauth_ldap&id=" . $newID);
        }
    }
    Html::back();
} else if (isset($_POST["purge"])) {
    $config_ldap->delete($_POST, 1);
    $_SESSION['glpi_authconfig'] = 1;
    $config_ldap->redirectToList();
} else if (isset($_POST["test_ldap"])) {
    $config_ldap->getFromDB($_POST["id"]);

    if (AuthLDAP::testLDAPConnection($_POST["id"])) {
                                       //TRANS: %s is the description of the test
        $_SESSION["LDAP_TEST_MESSAGE"] = sprintf(
            __('Test successful: %s'),
            //TRANS: %s is the name of the LDAP main server
            sprintf(__('Main server %s'), $config_ldap->fields["name"])
        );
    } else {
                                       //TRANS: %s is the description of the test
        $_SESSION["LDAP_TEST_MESSAGE"] = sprintf(
            __('Test failed: %s'),
            //TRANS: %s is the name of the LDAP main server
            sprintf(__('Main server %s'), $config_ldap->fields["name"])
        );
        GLPINetwork::addErrorMessageAfterRedirect();
    }
    Html::back();
} else if (isset($_POST["test_ldap_replicate"])) {
    $replicate = new AuthLdapReplicate();
    $replicate->getFromDB($_POST["ldap_replicate_id"]);

    if (AuthLDAP::testLDAPConnection($_POST["id"], $_POST["ldap_replicate_id"])) {
                                       //TRANS: %s is the description of the test
        $_SESSION["LDAP_TEST_MESSAGE"] = sprintf(
            __('Test successful: %s'),
            //TRANS: %s is the name of the LDAP replica server
            sprintf(__('Replicate %s'), $replicate->fields["name"])
        );
    } else {
                                        //TRANS: %s is the description of the test
        $_SESSION["LDAP_TEST_MESSAGE"] = sprintf(
            __('Test failed: %s'),
            //TRANS: %s is the name of the LDAP replica server
            sprintf(__('Replicate %s'), $replicate->fields["name"])
        );
        GLPINetwork::addErrorMessageAfterRedirect();
    }
    Html::back();
} else if (isset($_POST["add_replicate"])) {
    $replicate = new AuthLdapReplicate();
    unset($_POST["next"]);
    unset($_POST["id"]);
    $replicate->add($_POST);
    Html::back();
}

$menus = ['config', 'auth', 'ldap'];
AuthLDAP::displayFullPageForItem($_GET['id'], $menus, $_GET);
