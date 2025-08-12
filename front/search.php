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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Exception\Http\AccessDeniedHttpException;

global $CFG_GLPI;

Session::checkCentralAccess();
Html::header(__('Search'));

if (!$CFG_GLPI['allow_search_global']) {
    throw new AccessDeniedHttpException();
}
if (isset($_GET["globalsearch"])) {
    $searchtext = trim($_GET["globalsearch"]);
    $no_result = [];

    echo "<div class='search_page search_page_global flex-row flex-wrap'>";
    foreach ($CFG_GLPI["globalsearch_types"] as $itemtype) {
        if (
            ($item = getItemForItemtype($itemtype))
            && $item->canView()
        ) {
            $_GET["reset"]        = 'reset';

            $params                 = Search::manageParams($itemtype, $_GET, false, true);
            $params["display_type"] = Search::GLOBAL_SEARCH;

            $count                  = count($params["criteria"]);

            $params["criteria"][$count]["field"]       = 'view';
            $params["criteria"][$count]["searchtype"]  = 'contains';
            $params["criteria"][$count]["value"]       = $searchtext;

            $data = Search::getDatas($itemtype, $params);
            if ($data['data']['count'] > 0) {
                echo "<div class='search-container w-100 disable-overflow-y' counter='" . (int) $data['data']['count'] . "'>";
                Search::displayData($data);
                echo "</div>";
            } else {
                $no_result[] = $itemtype::getTypeName(1);
            }
        }
    }

    // language=Twig
    echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
        <div class="search-container w-100 disable-overflow-y" counter="0">
            <div class="ajax-container search-display-data">
                <div class="card card-sm mt-0 search-card">
                    <div class="card-header d-flex justify-content-between search-header pe-0">
                        <h2>{{ label }}</h2>
                    </div>
                    <ul>
                        {% for itemtype in no_result %}
                            <li>{{ itemtype }}</li>
                        {% endfor %}
                    </ul>
                </div>
            </div>
        </div>
TWIG, ['label' => __('Other searches with no item found'),'no_result' => $no_result]);
}

Html::footer();
