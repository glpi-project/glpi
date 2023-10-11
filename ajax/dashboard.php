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

$SECURITY_STRATEGY = 'no_check'; // specific checks done later to allow anonymous access to embed dashboards

include('../inc/includes.php');

use Glpi\Dashboard\Grid;

if (!isset($_REQUEST["action"])) {
    exit;
}

// Parse stringified JSON payload (Used to preserve integers)
$request_data = array_merge($_REQUEST, json_decode($_UREQUEST['data'] ?? '{}', true));
unset($request_data['data']);

$embed = false;
if (
    in_array($_REQUEST['action'], ['get_dashboard_items', 'get_card', 'get_cards'])
    && array_key_exists('embed', $request_data)
    && (bool)$request_data['embed']
) {
    if (Grid::checkToken($request_data) === false) {
        http_response_code(403);
        exit;
    }
    $embed = true;
} else {
    Session::checkLoginUser();
}

$dashboard = new Glpi\Dashboard\Dashboard($_REQUEST['dashboard'] ?? "");

switch ($_POST['action'] ?? null) {
    case 'save_new_dashboard':
        if (!Session::haveRight('dashboard', CREATE)) {
            http_response_code(403);
            exit();
        }

        echo $dashboard->saveNew(
            $_POST['title']   ?? "",
            $_POST['context'] ?? ""
        );
        exit;

    case 'save_items':
        if (!$dashboard->canUpdateCurrent()) {
            http_response_code(403);
            exit();
        }

        $dashboard->saveitems($_POST['items'] ?? []);
        $dashboard->saveTitle($_POST['title'] ?? "");
        exit;

    case 'save_rights':
        if (!$dashboard->canUpdateCurrent()) {
            http_response_code(403);
            exit();
        }

        $dashboard->setPrivate($_POST['is_private'] != '0');
        $dashboard->saveRights($_POST['rights'] ?? []);
        exit;

    case 'save_filter_data':
        if (!$dashboard->canViewCurrent()) {
            http_response_code(403);
            exit();
        }

        $dashboard->saveFilter($_POST['filters'] ?? []);
        exit;

    case 'delete_dashboard':
        if (!$dashboard->canDeleteCurrent()) {
            http_response_code(403);
            exit();
        }

        echo $dashboard->delete(['key' => $_POST['dashboard']]);
        exit;

    case 'set_last_dashboard':
        $grid = new Grid($_POST['dashboard'] ?? "");
        $grid->setLastDashboard($_POST['page'], $_POST['dashboard']);
        exit;

    case 'clone_dashboard':
        if (!Session::haveRight('dashboard', CREATE) || !$dashboard->canViewCurrent()) {
            http_response_code(403);
            exit();
        }

        $new_dashboard = $dashboard->cloneCurrent();
        echo json_encode($new_dashboard);
        exit;
}

switch ($_GET['action'] ?? null) {
    case 'get_filter_data':
        if (!$dashboard->canViewCurrent()) {
            http_response_code(403);
            exit();
        }

        echo $dashboard->getFilter();
        exit;
}

$grid = new Grid($_REQUEST['dashboard'] ?? "");

header("Content-Type: text/html; charset=UTF-8");
switch ($_REQUEST['action']) {
    case 'add_new':
        if (!Session::haveRight('dashboard', CREATE)) {
            http_response_code(403);
            exit();
        }

        $grid->displayAddDashboardForm();
        break;

    case 'edit_rights':
        // FIXME This endpoint does not seems to be used.
        if (!Session::haveRight('dashboard', UPDATE)) {
            http_response_code(403);
            exit();
        }

        $grid->displayEditRightsForm();
        break;

    case 'display_edit_widget':
    case 'display_add_widget':
        if (!$dashboard->canUpdateCurrent()) {
            http_response_code(403);
            exit();
        }

        $grid->displayWidgetForm($_REQUEST);
        break;

    case 'display_embed_form':
        if (!Session::haveRight('dashboard', UPDATE)) {
            http_response_code(403);
            exit();
        }

        $grid->displayEmbedForm();
        break;

    case 'get_card':
        if (!$dashboard->canViewCurrent() && !$embed) {
            http_response_code(403);
            exit();
        }

        Session::writeClose();
        echo $grid->getCardHtml($_REQUEST['card_id'], $_REQUEST);
        break;

    case 'get_cards':
        if (!$dashboard->canViewCurrent() && !$embed) {
            http_response_code(403);
            exit();
        }

        Session::writeClose();
        header("Content-Type: application/json; charset=UTF-8");
        $cards = $request_data['cards'];
        unset($request_data['cards']);
        $result = [];
        foreach ($cards as $card) {
            try {
                $result[$card['card_id']] = $grid->getCardHtml($card['card_id'], array_merge($request_data, $card));
            } catch (\Throwable $e) {
               // Send exception to logger without actually exiting.
               // Use quiet mode to not break JSON result.
                global $GLPI;
                $GLPI->getErrorHandler()->handleException($e, true);
            }
        }
        echo json_encode($result);
        break;

    case 'display_add_filter':
        if (!$dashboard->canUpdateCurrent()) {
            http_response_code(403);
            exit();
        }

        $grid->displayFilterForm($_REQUEST);
        break;
    case 'get_dashboard_filters':
        if (!Session::haveRight('dashboard', READ)) {
            http_response_code(403);
            exit();
        }

        echo $grid->getFiltersSetHtml($_REQUEST['filters'] ?? []);
        break;
    case 'get_filter':
        if (!Session::haveRight('dashboard', READ)) {
            http_response_code(403);
            exit();
        }

        echo $grid->getFilterHtml($_REQUEST['filter_id']);
        break;

    case 'get_dashboard_items':
        if (!$dashboard->canViewCurrent() && !$embed) {
            http_response_code(403);
            exit();
        }

        echo $grid->getGridItemsHtml(true, $_REQUEST['embed'] ?? false);
        break;
}
