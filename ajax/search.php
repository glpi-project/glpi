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

use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Search\Input\QueryBuilder;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

if (!isset($_REQUEST['action'])) {
    return;
}

// actions without IDOR
switch ($_REQUEST['action']) {
    case 'display_results':
        if (!isset($_REQUEST['itemtype'])) {
            throw new BadRequestHttpException();
        }

        /** @var class-string<CommonDBTM> $itemtype */
        $itemtype = $_REQUEST['itemtype'];
        if (!$itemtype::canView()) {
            throw new AccessDeniedHttpException();
        }

        // Handle display params
        $params = $_REQUEST['params'] ?? [];
        unset($_REQUEST['params']);

        $search_params = Search::manageParams($itemtype, $_REQUEST, $_REQUEST['usesession'] ?? true);
        $params = array_replace($search_params, $params);
        // Remove hidden criteria such as the longitude and latitude criteria which are injected in the search engine itself for map searches
        $params['criteria'] = array_filter($params['criteria'], static fn($criteria) => !isset($criteria['_hidden']) || !$criteria['_hidden']);

        if (
            isset($search_params['browse'])
            && $search_params['browse'] == 1
            && method_exists($itemtype, 'showBrowseView')
        ) {
            $itemtype::showBrowseView($itemtype, $search_params, true);
        } else {
            $results = Search::getDatas($itemtype, $search_params);
            $results['searchform_id'] = $_REQUEST['searchform_id'] ?? null;
            Search::displayData($results, $params);
        }

        if (isset($_SESSION['glpisearch'][$itemtype]['reset'])) {
            unset($_SESSION['glpisearch'][$itemtype]);
        }
        break;
}

if (!Session::validateIDOR($_REQUEST)) {
    return;
}

// actions with IDOR
switch ($_REQUEST['action']) {
    case "display_criteria":
        Search::displayCriteria($_REQUEST);
        QueryBuilder::resetActiveSavedSearch();
        break;

    case "display_meta_criteria":
        Search::displayMetaCriteria($_REQUEST);
        QueryBuilder::resetActiveSavedSearch();
        break;

    case "display_criteria_group":
        Search::displayCriteriaGroup($_REQUEST);
        QueryBuilder::resetActiveSavedSearch();
        break;

    case "display_searchoption":
        Search::displaySearchoption($_REQUEST);
        break;

    case "display_searchoption_value":
        Search::displaySearchoptionValue($_REQUEST);
        break;

    case "display_sort_criteria":
        Search::displaySortCriteria($_REQUEST);
        break;
}
