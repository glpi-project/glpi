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

include('../inc/includes.php');

use Glpi\Dashboard\Grid;

if (!isset($_REQUEST["action"])) {
    exit;
}

// Parse stringified JSON payload (Used to preserve integers)
$request_data = array_merge($_REQUEST, json_decode($_UREQUEST['data'] ?? '{}', true));
unset($request_data['data']);

if (!isset($request_data['embed']) || !$request_data['embed']) {
    Session::checkLoginUser();
} else if (
    !in_array($_REQUEST['action'], [
        'get_dashboard_items',
        'get_card',
        'get_cards'
    ])
) {
    Html::displayRightError();
}

$dashboard = new Glpi\Dashboard\Dashboard($_REQUEST['dashboard'] ?? "");

switch ($_POST['action'] ?? null) {
    case 'save_new_dashboard':
        echo $dashboard->saveNew(
            $_POST['title']   ?? "",
            $_POST['context'] ?? ""
        );
        exit;

    case 'save_items':
        $dashboard->saveitems($_POST['items'] ?? []);
        $dashboard->saveTitle($_POST['title'] ?? "");
        exit;

    case 'save_rights':
        $dashboard->setPrivate($_POST['is_private'] != '0');
        $dashboard->saveRights($_POST['rights'] ?? []);
        exit;

    case 'save_filter_data':
        $dashboard->saveFilter($_POST['filters'] ?? []);
        exit;

    case 'delete_dashboard':
        echo $dashboard->delete(['key' => $_POST['dashboard']]);
        exit;

    case 'set_last_dashboard':
        $grid = new Grid($_POST['dashboard'] ?? "");
        $grid->setLastDashboard($_POST['page'], $_POST['dashboard']);
        exit;

    case 'clone_dashboard':
        $new_dashboard = $dashboard->cloneCurrent();
        echo json_encode($new_dashboard);
        exit;
}

switch ($_GET['action'] ?? null) {
    case 'get_filter_data':
        echo $dashboard->getFilter();
        exit;
}

$grid = new Grid($_REQUEST['dashboard'] ?? "");

header("Content-Type: text/html; charset=UTF-8");
switch ($_REQUEST['action']) {
    case 'add_new':
        $grid->displayAddDashboardForm();
        break;

    case 'edit_rights':
        $grid->displayEditRightsForm();
        break;

    case 'display_edit_widget':
    case 'display_add_widget':
        $grid->displayWidgetForm($_REQUEST);
        break;

    case 'display_embed_form':
        $grid->displayEmbedForm();
        break;

    case 'get_card':
        session_write_close();
        echo $grid->getCardHtml($_REQUEST['card_id'], $_REQUEST);
        break;

    case 'get_cards':
        session_write_close();
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
        $grid->displayFilterForm($_REQUEST);
        break;
    case 'get_dashboard_filters':
        echo $grid->getFiltersSetHtml($_REQUEST['filters'] ?? []);
        break;
    case 'get_filter':
        echo $grid->getFilterHtml($_REQUEST['filter_id']);
        break;

    case 'get_dashboard_items':
        echo $grid->getGridItemsHtml(true, $_REQUEST['embed'] ?? false);
        break;
}
