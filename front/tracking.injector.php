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

$SECURITY_STRATEGY = 'no_check'; // Anonymous access may be allowed by configuration.

include('../inc/includes.php');

if (
    empty($_POST["_type"])
    || ($_POST["_type"] != "Helpdesk")
    || !$CFG_GLPI["use_anonymous_helpdesk"]
) {
    Session::checkRight("ticket", CREATE);
}

$track = new Ticket();

// Security check
if (empty($_POST) || (count($_POST) == 0)) {
    Html::redirect($CFG_GLPI["root_doc"] . "/front/helpdesk.public.php");
}

if (isset($_POST["_type"]) && ($_POST["_type"] == "Helpdesk")) {
    Html::nullHeader(Ticket::getTypeName(Session::getPluralNumber()));
} else if ($_POST["_from_helpdesk"]) {
    Html::helpHeader(__('Simplified interface'));
} else {
    Html::header(__('Simplified interface'), '', $_SESSION["glpiname"], "helpdesk", "tracking");
}

if (isset($_POST['_actors']) && is_string($_POST['_actors'])) {
    try {
        $_POST['_actors'] = json_decode($_UPOST['_actors'], true, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException $e) {
        $_POST['_actors'] = [];
    }
}
if (isset($_POST['add'])) {
    if (!$CFG_GLPI["use_anonymous_helpdesk"]) {
        $track->check(-1, CREATE, $_POST);
    } else {
        $track->getEmpty();
    }
    $_POST['check_delegatee'] = true;
    if (isset($_UPOST['_actors'])) {
        $_POST['_actors'] = json_decode($_UPOST['_actors'], true);
       // with self-service, we only have observers
        unset($_POST['_actors']['requester'], $_POST['_actors']['assign']);
    }
    if ($track->add($_POST)) {
        if ($_SESSION['glpibackcreated'] && Ticket::canView()) {
            Html::redirect($track->getLinkURL());
        }
        if (isset($_POST["_type"]) && ($_POST["_type"] == "Helpdesk")) {
            echo "<div class='center spaced'>" .
                __('Your ticket has been registered, its treatment is in progress.');
            Html::displayBackLink();
            echo "</div>";
        } else {
            echo "<div class='center b spaced'>";
            echo "<img src='" . $CFG_GLPI["root_doc"] . "/pics/ok.png' alt='" . __s('OK') . "'>";
            Session::addMessageAfterRedirect(__('Thank you for using our automatic helpdesk system.'));
            Html::displayMessageAfterRedirect();
            echo "</div>";
        }
    } else {
        if (isset($_POST["_type"]) && ($_POST["_type"] == "Helpdesk")) {
            Html::redirect($CFG_GLPI["root_doc"] . "/front/helpdesk.php");
        } else {
            Html::redirect($CFG_GLPI["root_doc"] . "/front/helpdesk.public.php?create_ticket=1");
        }
    }
    Html::nullFooter();
} else { // reload display form
    $track->showFormHelpdesk(Session::getLoginUserID());
    Html::helpFooter();
}
