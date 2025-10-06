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
use Glpi\Dashboard\Dashboard;
use Glpi\Dashboard\Grid;
use Glpi\Debug\Profiler;
use Glpi\Error\ErrorHandler;
use Glpi\Exception\Http\AccessDeniedHttpException;

use function Safe\json_decode;
use function Safe\json_encode;

if (!isset($_REQUEST["action"])) {
    return;
}

// Parse stringified JSON payload (Used to preserve integers)
$request_data = array_merge($_REQUEST, json_decode($_REQUEST['data'] ?? '{}', true));
unset($request_data['data']);

$embed = false;
if (
    in_array($_REQUEST['action'], ['get_dashboard_items', 'get_card', 'get_cards'])
    && array_key_exists('embed', $request_data)
    && (bool) $request_data['embed']
) {
    // Session check is disabled for this script when "embed" mode is used.
    // Indeed, it is declared as stateless by `\Glpi\Http\SessionManager::isResourceStateless()`,
    // to prevent using the session cookie for embed dashboards.
    if (Grid::checkToken($request_data) === false) {
        throw new AccessDeniedHttpException();
    }
    $embed = true;
}

$dashboard = new Dashboard($_REQUEST['dashboard'] ?? "");

switch ($_POST['action'] ?? null) {
    case 'save_new_dashboard':
        header("Content-Type: application/json; charset=UTF-8");

        if (!Session::haveRight('dashboard', CREATE)) {
            throw new AccessDeniedHttpException();
        }

        $key = $dashboard->saveNew(
            $_POST['title']   ?? "",
            $_POST['context'] ?? ""
        );
        echo json_encode($key);
        return;

    case 'save_items':
        if (!$dashboard->canUpdateCurrent()) {
            throw new AccessDeniedHttpException();
        }

        $dashboard->saveitems($_POST['items'] ?? []);
        $dashboard->saveTitle($_POST['title'] ?? "");
        return;

    case 'save_rights':
        if (!$dashboard->canUpdateCurrent()) {
            throw new AccessDeniedHttpException();
        }

        $dashboard->setPrivate($_POST['is_private'] != '0');
        $dashboard->saveRights($_POST['rights'] ?? []);
        return;

    case 'save_filter_data':
        if (!$dashboard->canViewCurrent()) {
            throw new AccessDeniedHttpException();
        }

        $dashboard->saveFilter($_POST['filters'] ?? []);
        return;

    case 'delete_dashboard':
        header("Content-Type: application/json; charset=UTF-8");

        if (!$dashboard->canDeleteCurrent()) {
            throw new AccessDeniedHttpException();
        }

        $success = $dashboard->delete(['key' => $_POST['dashboard']]);
        echo json_encode($success);
        return;

    case 'set_last_dashboard':
        $grid = new Grid($_POST['dashboard'] ?? "");
        $grid->setLastDashboard($_POST['page'], $_POST['dashboard']);
        return;

    case 'clone_dashboard':
        header("Content-Type: application/json; charset=UTF-8");

        if (!Session::haveRight('dashboard', CREATE) || !$dashboard->canViewCurrent()) {
            throw new AccessDeniedHttpException();
        }

        $new_dashboard = $dashboard->cloneCurrent();
        echo json_encode($new_dashboard);
        return;

    case 'disable_placeholders':
        if (!Session::haveRight(Config::$rightname, UPDATE)) {
            throw new AccessDeniedHttpException();
        }
        Config::setConfigurationValues('core', ['is_demo_dashboards' => 0]);
        return;
}

switch ($_GET['action'] ?? null) {
    case 'get_filter_data':
        header("Content-Type: application/json; charset=UTF-8");

        if (!$dashboard->canViewCurrent()) {
            throw new AccessDeniedHttpException();
        }

        /**
         * `Dashboard::getFilter()` already returns a JSON encoded string.
         *
         * @psalm-taint-escape has_quotes
         * @psalm-taint-escape html
         */
        $filter = $dashboard->getFilter();

        echo $filter;
        return;
}

Profiler::getInstance()->start('Grid::construct');
$grid = new Grid($_REQUEST['dashboard'] ?? "");
if ($embed) {
    $grid->initEmbedSession($_REQUEST);
}
Profiler::getInstance()->stop('Grid::construct');

header("Content-Type: text/html; charset=UTF-8");
switch ($_REQUEST['action']) {
    case 'add_new':
        if (!Session::haveRight('dashboard', CREATE)) {
            throw new AccessDeniedHttpException();
        }

        $grid->displayAddDashboardForm();
        break;

    case 'edit_rights':
        // FIXME This endpoint does not seems to be used.
        if (!Session::haveRight('dashboard', UPDATE)) {
            throw new AccessDeniedHttpException();
        }

        $grid->displayEditRightsForm();
        break;

    case 'display_edit_widget':
    case 'display_add_widget':
        if (!$dashboard->canUpdateCurrent()) {
            throw new AccessDeniedHttpException();
        }

        $grid->displayWidgetForm($_REQUEST);
        break;

    case 'display_embed_form':
        if (!Session::haveRight('dashboard', UPDATE)) {
            throw new AccessDeniedHttpException();
        }

        $grid->displayEmbedForm();
        break;

    case 'get_card':
        if (!$dashboard->canViewCurrent() && !$embed) {
            throw new AccessDeniedHttpException();
        }

        Session::writeClose();
        Profiler::getInstance()->start('Get card HTML');
        echo $grid->getCardHtml($_REQUEST['card_id'], $_REQUEST);
        Profiler::getInstance()->stop('Get card HTML');
        break;

    case 'get_cards':
        header("Content-Type: application/json; charset=UTF-8");

        if (!$dashboard->canViewCurrent() && !$embed) {
            throw new AccessDeniedHttpException();
        }

        Session::writeClose();
        $cards = $request_data['cards'];
        unset($request_data['cards']);
        $result = [];
        Profiler::getInstance()->start('Get cards HTML');
        foreach ($cards as $card) {
            try {
                $result[$card['card_id']] = $grid->getCardHtml($card['card_id'], array_merge($request_data, $card));
            } catch (Throwable $e) {
                // Send exception to logger without actually exiting.
                ErrorHandler::logCaughtException($e);
            }
        }
        Profiler::getInstance()->stop('Get cards HTML');
        echo json_encode($result);
        break;

    case 'display_add_filter':
        if (!$dashboard->canUpdateCurrent()) {
            throw new AccessDeniedHttpException();
        }

        $grid->displayFilterForm($_REQUEST);
        break;
    case 'get_dashboard_filters':
        if (!Session::haveRight('dashboard', READ)) {
            throw new AccessDeniedHttpException();
        }

        echo $grid->getFiltersSetHtml($_REQUEST['filters'] ?? []);
        break;
    case 'get_filter':
        if (!Session::haveRight('dashboard', READ)) {
            throw new AccessDeniedHttpException();
        }

        echo $grid->getFilterHtml($_REQUEST['filter_id']);
        break;

    case 'get_dashboard_items':
        if (!$dashboard->canViewCurrent() && !$embed) {
            throw new AccessDeniedHttpException();
        }

        echo $grid->getGridItemsHtml(true, $_REQUEST['embed'] ?? false);
        break;
}
