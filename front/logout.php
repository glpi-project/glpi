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

/**
 * @since 0.85
 */

include('../inc/includes.php');


if (
    $CFG_GLPI["ssovariables_id"] > 0
    && strlen($CFG_GLPI['ssologout_url']) > 0
) {
    Session::cleanOnLogout();
    Html::redirect($CFG_GLPI["ssologout_url"]);
}

if (
    !isset($_SESSION["noAUTO"])
    && isset($_SESSION["glpiauthtype"])
    && $_SESSION["glpiauthtype"] == Auth::CAS
    && Toolbox::canUseCAS()
) {
    // Adapt phpCAS::client() signature.
    // A new signature has been introduced in 1.6.0 version of the official package.
    // This new signature has been backported in the `1.3.6-1` version of the debian package,
    // so we have to check for method argument names too.
    $has_service_base_url_arg = version_compare(phpCAS::getVersion(), '1.6.0', '>=')
        || ((new ReflectionMethod(phpCAS::class, 'client'))->getParameters()[4]->getName() ?? null) === 'service_base_url';
    if (!$has_service_base_url_arg) {
        // Prior to version 1.6.0, `$service_base_url` argument was not present, and 5th argument was `$changeSessionID`.
        phpCAS::client(
            constant($CFG_GLPI["cas_version"]),
            $CFG_GLPI["cas_host"],
            intval($CFG_GLPI["cas_port"]),
            $CFG_GLPI["cas_uri"],
            false
        );
    } else {
        // Starting from version 1.6.0, `$service_base_url` argument was added at 5th position, and `$changeSessionID`
        // was moved at 6th position.
        phpCAS::client(
            constant($CFG_GLPI["cas_version"]),
            $CFG_GLPI["cas_host"],
            intval($CFG_GLPI["cas_port"]),
            $CFG_GLPI["cas_uri"],
            $CFG_GLPI["url_base"],
            false
        );
    }
    phpCAS::setServerLogoutURL(strval($CFG_GLPI["cas_logout"]));
    phpCAS::logout();
}

$toADD = "";

// Redirect management
if (isset($_POST['redirect']) && (strlen($_POST['redirect']) > 0)) {
    $toADD = "?redirect=" . $_POST['redirect'];
} else if (isset($_GET['redirect']) && (strlen($_GET['redirect']) > 0)) {
    $toADD = "?redirect=" . $_GET['redirect'];
}

if (isset($_SESSION["noAUTO"]) || isset($_GET['noAUTO'])) {
    if (empty($toADD)) {
        $toADD .= "?";
    } else {
        $toADD .= "&";
    }
    $toADD .= "noAUTO=1";
}

Session::cleanOnLogout();

// Redirect to the login-page
Html::redirect($CFG_GLPI["root_doc"] . "/index.php" . $toADD);
