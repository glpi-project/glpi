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

use Glpi\Event;
use Glpi\Exception\Http\NotFoundHttpException;
use Glpi\Security\TOTPManager;

global $CFG_GLPI;

if (isset($_POST['language']) && !Session::getLoginUserID()) {
    // Offline lang change, keep it before session validity check
    $_SESSION["glpilanguage"] = $_POST['language'];
    Session::addMessageAfterRedirect(__s('Lang has been changed!'));
    Html::back();
}

if (empty($_GET["id"])) {
    $_GET["id"] = "";
}

$user      = new User();
$groupuser = new Group_User();

if (empty($_GET["id"]) && isset($_GET["name"])) {
    Session::checkRight(User::$rightname, READ);
    if ($user->getFromDBbyName($_GET["name"])) {
        $user->check($user->fields['id'], READ);
        Html::redirect($user->getFormURLWithID($user->fields['id']));
    }
    throw new NotFoundHttpException();
}

if (empty($_GET["name"])) {
    $_GET["name"] = "";
}

if (isset($_GET['getvcard'])) {
    if (empty($_GET["id"])) {
        Html::redirect($CFG_GLPI["root_doc"] . "/front/user.php");
    }
    $user->check($_GET['id'], READ);
    $user->generateVcard();
} elseif (isset($_POST["add"])) {
    $user->check(-1, CREATE, $_POST);

    if (($newID = $user->add($_POST))) {
        Event::log(
            $newID,
            "users",
            4,
            "setup",
            sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"])
        );
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($user->getLinkURL());
        }
    }
    Html::back();
} elseif (isset($_POST["delete"])) {
    $user->check($_POST['id'], DELETE);
    $user->delete($_POST);
    Event::log(
        $_POST["id"],
        "users",
        4,
        "setup",
        //TRANS: %s is the user login
        sprintf(__('%s deletes an item'), $_SESSION["glpiname"])
    );
    $user->redirectToList();
} elseif (isset($_POST["restore"])) {
    $user->check($_POST['id'], DELETE);
    $user->restore($_POST);
    Event::log(
        $_POST["id"],
        "users",
        4,
        "setup",
        //TRANS: %s is the user login
        sprintf(__('%s restores an item'), $_SESSION["glpiname"])
    );
    $user->redirectToList();
} elseif (isset($_POST["purge"])) {
    $user->check($_POST['id'], PURGE);
    $user->delete($_POST, true);
    Event::log(
        $_POST["id"],
        "users",
        4,
        "setup",
        sprintf(__('%s purges an item'), $_SESSION["glpiname"])
    );
    $user->redirectToList();
} elseif (isset($_POST["force_ldap_resynch"])) {
    Session::checkRight('user', User::UPDATEAUTHENT);

    $user->getFromDB($_POST["id"]);
    AuthLDAP::forceOneUserSynchronization($user);
    Html::back();
} elseif (isset($_POST["clean_ldap_fields"])) {
    Session::checkRight('user', User::UPDATEAUTHENT);

    $user->getFromDB($_POST["id"]);
    AuthLDAP::forceOneUserSynchronization($user, true);
    Html::back();
} elseif (isset($_POST["update"])) {
    $user->check($_POST['id'], UPDATE);
    $user->update($_POST);
    Event::log(
        $_POST['id'],
        "users",
        5,
        "setup",
        //TRANS: %s is the user login
        sprintf(__('%s updates an item'), $_SESSION["glpiname"])
    );
    Html::back();
} elseif (isset($_POST["change_auth_method"])) {
    Session::checkRight('user', User::UPDATEAUTHENT);

    if (isset($_POST["auths_id"])) {
        User::changeAuthMethod([$_POST["id"]], $_POST["authtype"], $_POST["auths_id"]);
    }
    Html::back();
} elseif (isset($_POST['language'])) {
    $user->update(
        [
            'id'        => Session::getLoginUserID(),
            'language'  => $_POST['language'],
        ]
    );
    Session::addMessageAfterRedirect(__s('Lang has been changed!'));
    Html::back();
} elseif (isset($_POST['impersonate']) && $_POST['impersonate']) {
    if (!Session::startImpersonating($_POST['id'])) {
        Session::addMessageAfterRedirect(__s('Unable to impersonate user'), false, ERROR);
        Html::back();
    }

    Html::redirect($CFG_GLPI['root_doc'] . '/');
} elseif (isset($_POST['impersonate']) && !$_POST['impersonate']) {
    $impersonated_user_id = Session::getLoginUserID();

    if (!Session::stopImpersonating()) {
        Session::addMessageAfterRedirect(__s('Unable to stop impersonating user'), false, ERROR);
        Html::back();
    }

    Html::redirect(User::getFormURLWithID($impersonated_user_id));
} elseif (isset($_POST['disable_2fa'])) {
    Session::checkRight('user', User::UPDATEAUTHENT);
    (new TOTPManager())->disable2FAForUser($_POST['id']);
    Html::back();
} else {
    if (isset($_GET["ext_auth"])) {
        Html::header(User::getTypeName(Session::getPluralNumber()), '', "admin", "user");
        User::showAddExtAuthForm();
        Html::footer();
    } elseif (isset($_POST['add_ext_auth_ldap'])) {
        Session::checkRight("user", User::IMPORTEXTAUTHUSERS);

        if (isset($_POST['login']) && !empty($_POST['login'])) {
            AuthLDAP::importUserFromServers(['name' => $_POST['login']]);
        }
        Html::back();
    } elseif (isset($_POST['add_ext_auth_simple'])) {
        if (isset($_POST['login']) && !empty($_POST['login'])) {
            Session::checkRight("user", User::IMPORTEXTAUTHUSERS);
            $input = ['name'     => $_POST['login'],
                '_extauth' => 1,
                'add'      => 1,
            ];
            $user->check(-1, CREATE, $input);
            $newID = $user->add($input);
            Event::log(
                $newID,
                "users",
                4,
                "setup",
                sprintf(
                    __('%1$s adds the item %2$s'),
                    $_SESSION["glpiname"],
                    $_POST["login"]
                )
            );
        }

        Html::back();
    } else {
        $options = $_GET;
        $options['formoptions'] = "data-track-changes=true";
        $menus = ["admin", "user"];
        User::displayFullPageForItem($_GET["id"], $menus, $options);
    }
}
