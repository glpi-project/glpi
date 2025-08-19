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

use function Safe\parse_url;

global $CFG_GLPI;

$rr = new Reservation();

if (Session::getCurrentInterface() == "helpdesk") {
    Html::helpHeader(__('Simplified interface'));
} else {
    Html::header(Reservation::getTypeName(Session::getPluralNumber()), '', "tools", "reservationitem");
}

$fn_redirect_back = static function ($begin_year = null, $begin_month = null) {
    $back_url = Html::getBackUrl();
    if ($begin_year === null && $begin_month === null) {
        // Try to get from POST data
        if (isset($_POST['resa']["begin"])) {
            $begin = $_POST['resa']["begin"];
            [$begin_year, $begin_month] = explode("-", $begin);
        } else {
            // Default to current month/year
            $begin_year  = date('Y');
            $begin_month = date('m');
        }
    }

    // Remove old month/year params
    $back_url_params = [];
    $back_url_base = parse_url($back_url, PHP_URL_PATH) ?? '';
    parse_str(parse_url($back_url, PHP_URL_QUERY) ?? '', $back_url_params);
    unset($back_url_params['month'], $back_url_params['year'], $back_url_params['tab_params']);
    if ($back_url_params !== []) {
        $back_url = $back_url_base . '?' . Toolbox::append_params($back_url_params);
    }
    if (str_contains($back_url, 'front/reservation.php')) {
        $back_url .= (!str_contains($back_url, '?') ? '?' : '&') . Toolbox::append_params([
            'month' => $begin_month,
            'year' => $begin_year,
        ]);
    } else {
        $back_url .= (!str_contains($back_url, '?') ? '?' : '&') . Toolbox::append_params([
            'tab_params' => [
                'month' => $begin_month,
                'year' => $begin_year,
            ],
        ]);
    }
    Html::redirect($back_url);
};

if (isset($_POST["update"])) {
    $rr->check($_POST["id"], UPDATE);

    Toolbox::manageBeginAndEndPlanDates($_POST['resa']);
    $_POST['_item']   = key($_POST["items"]);
    $_POST['begin']   = $_POST['resa']["begin"];
    $_POST['end']     = $_POST['resa']["end"];
    $rr->update($_POST);
    $fn_redirect_back();
} elseif (isset($_POST["purge"])) {
    $rr->check($_POST["id"], PURGE);

    $reservationitems_id = key($_POST["items"]);
    if ($rr->delete($_POST, true)) {
        Event::log(
            $_POST["id"],
            "reservation",
            4,
            "inventory",
            //TRANS: %s is the user login
            sprintf(
                __('%1$s purges the reservation for item %2$s'),
                $_SESSION["glpiname"],
                $reservationitems_id
            )
        );
    }

    [$begin_year, $begin_month] = explode("-", $rr->fields["begin"]);
    $fn_redirect_back($begin_year, $begin_month);
} elseif (isset($_POST["add"])) {
    Reservation::handleAddForm($_POST);
    $fn_redirect_back();
} elseif (isset($_GET["id"])) {
    if (!empty($_GET["id"])) {
        $rr->check($_GET["id"], READ);
    }
    if (!isset($_GET['begin'])) {
        $_GET['begin'] = date('Y-m-d H:00:00');
    }
    if (
        empty($_GET["id"])
        && (!isset($_GET['item']) || (count($_GET['item']) == 0))
    ) {
        Html::back();
    }
    if (
        !empty($_GET["id"])
        || (isset($_GET['item']) && isset($_GET['begin']))
    ) {
        $rr->showForm($_GET['id'], $_GET);
    }
}

if (Session::getCurrentInterface() == "helpdesk") {
    Html::helpFooter();
} else {
    Html::footer();
}
