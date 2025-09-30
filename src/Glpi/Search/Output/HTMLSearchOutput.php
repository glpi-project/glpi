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

namespace Glpi\Search\Output;

use AllAssets;
use CommonDBTM;
use DefaultFilter;
use DisplayPreference;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Dashboard\Grid;
use Glpi\Features\TreeBrowse;
use Glpi\Plugin\Hooks;
use Glpi\Search\CriteriaFilter;
use Glpi\Search\SearchOption;
use Glpi\Toolbox\URL;
use Html;
use MassiveAction;
use Override;
use Plugin;
use SavedSearch;
use Search;
use Session;
use Ticket;
use Toolbox;

use function Safe\preg_match;
use function Safe\preg_replace;
use function Safe\preg_split;

/**
 *
 * @internal Not for use outside {@link Search} class and the "Glpi\Search" namespace.
 */
class HTMLSearchOutput extends AbstractSearchOutput
{
    #[Override]
    public function canDisplayResultsContainerWithoutExecutingSearch(): bool
    {
        return true;
    }

    public static function showPreSearchDisplay(string $itemtype): void
    {
        if (
            $itemtype === Ticket::class
            && Session::getCurrentInterface() === 'central'
            && $default = Grid::getDefaultDashboardForMenu('mini_ticket', true)
        ) {
            $dashboard = new Grid($default, 33, 2);
            $dashboard->show(true);
        }
    }

    public function displayData(array $data, array $params = [])
    {
        global $CFG_GLPI;

        $search_was_executed = $params['execute_search'] ?? true;
        $search_error = false;

        if (
            $search_was_executed
            && (!isset($data['data']) || !isset($data['data']['totalcount']))
        ) {
            $search_error = true;
        }

        $search     = $data['search'];
        $itemtype   = $data['itemtype'];
        $item       = $data['item'];
        $is_deleted = $search['is_deleted'];

        foreach ($search['criteria'] as $key => $criteria) {
            if (isset($criteria['virtual']) && $criteria['virtual']) {
                unset($search['criteria'][$key]);
            }
        }

        // Construct parameters
        $globallinkto  = Toolbox::append_params([
            'criteria'     => $search['criteria'],
            'metacriteria' => $search['metacriteria'],
        ], '&');

        $parameters = http_build_query([
            'sort'   => $search['sort'],
            'order'  => $search['order'],
        ]);

        $parameters .= "&{$globallinkto}";

        if (isset($_GET['_in_modal'])) {
            $parameters .= "&_in_modal=1";
        }

        // For plugin add new parameter if available
        if ($plug = isPluginItemType($data['itemtype'])) {
            $out = Plugin::doOneHook($plug['plugin'], Hooks::AUTO_ADD_PARAM_FOR_DYNAMIC_REPORT, $data['itemtype']);
            if (is_array($out) && count($out)) {
                $parameters .= Toolbox::append_params($out, '&');
            }
        }

        $search['target'] = URL::sanitizeURL($search['target']);
        $prehref = $search['target'] . (str_contains($search['target'], "?") ? "&" : "?");
        $href    = $prehref . $parameters;

        Session::initNavigateListItems($data['itemtype'], '', $href);

        // search if any saved search is active
        $soptions = SearchOption::getOptionsForItemtype($itemtype);
        $active_search_name = '';
        $active_savedsearch = false;
        if (isset($_SESSION['glpi_loaded_savedsearch'])) {
            $savedsearch = new SavedSearch();
            $savedsearch->getFromDB($_SESSION['glpi_loaded_savedsearch']);
            if ($itemtype === $savedsearch->fields['itemtype']) {
                $active_search_name = $savedsearch->getName();
                $active_savedsearch = true;
            }
        } elseif (count($data['search']['criteria']) > 0) {
            // check if it isn't the default search
            $default = CriteriaFilter::getDefaultSearch($itemtype);
            if ($default != $data['search']['criteria']) {
                $used_fields = array_column($data['search']['criteria'], 'field');
                $used_fields = array_unique($used_fields);

                // remove view field
                $is_view_fields = in_array('view', $used_fields);
                if ($is_view_fields) {
                    unset($used_fields[array_search('view', $used_fields)]);
                }

                $used_soptions = array_intersect_key($soptions, array_flip($used_fields));
                $used_soptions_names = array_column($used_soptions, 'name');

                if ($is_view_fields) {
                    $used_soptions_names[] = _n('View', 'Views', 1);
                }

                // check also if there is any default filters
                if ($defaultfilter = DefaultFilter::getSearchCriteria($itemtype)) {
                    array_unshift($used_soptions_names, $defaultfilter['name']);
                }

                // remove latitude and longitude if as map is enabled
                $as_map = $data['search']['as_map'] ?? 0;
                if ($as_map == 1) {
                    unset($used_soptions_names[array_search(__('Latitude'), $used_soptions_names)]);
                    unset($used_soptions_names[array_search(__('Longitude'), $used_soptions_names)]);
                }

                $active_search_name = sprintf(__("Filtered by %s"), implode(', ', $used_soptions_names));
            }
        }

        $active_sort_name = "";
        $active_sort = false;
        // should be sorted (0 => 0 : is the default value -> no sort)
        if (count($data['search']['sort']) > 0 && $data['search']['sort'] != [0 => 0]) {
            $used_fields = array_unique($data['search']['sort']);
            $used_fields = array_filter($used_fields, fn($value) => !is_null($value) && $value !== '');

            $used_soptions_names = [];
            foreach ($used_fields as $sopt_id) {
                $used_soptions_names[] = $soptions[$sopt_id]['name'];
            }

            $active_sort_name = sprintf(__("Sorted by %s"), implode(', ', $used_soptions_names));

            $active_sort = true;
        }

        $count = $data['data']['totalcount'] ?? 0;

        $rand = mt_rand();
        TemplateRenderer::getInstance()->display('components/search/display_data.html.twig', [
            'search_error'        => $search_error,
            'search_was_executed' => $search_was_executed,
            'data'                => $data,
            'union_search_type'   => $CFG_GLPI["union_search_type"],
            'rand'                => $rand,
            'no_sort'             => $search['no_sort'] ?? false,
            'order'               => $search['order'] ?? [],
            'sort'                => $search['sort'] ?? [],
            'start'               => $search['start'] ?? 0,
            'limit'               => $_SESSION['glpilist_limit'],
            'count'               => $count,
            'item'                => $item,
            'itemtype'            => $itemtype,
            'href'                => $href,
            'prehref'             => $prehref,
            'posthref'            => $globallinkto,
            'push_history'        => $params['push_history'] ?? true,
            'hide_controls'       => $params['hide_controls'] ?? false,
            'hide_search_toggle'  => $params['hide_criteria'] ?? false,
            'showmassiveactions'  => ($params['showmassiveactions'] ?? $search['showmassiveactions'] ?? true)
                && $data['display_type'] != Search::GLOBAL_SEARCH
                && (
                    $itemtype == AllAssets::getType()
                    || count(MassiveAction::getAllMassiveActions($item, $is_deleted))
                ),
            'massiveactionparams' => $data['search']['massiveactionparams'] + [
                'num_displayed' => min($_SESSION['glpilist_limit'], $count),
                'is_deleted'    => $is_deleted,
                'container'     => "massform" . \str_replace('\\', '', $itemtype) . $rand,
            ],
            'can_config'          => Session::haveRightsOr('search_config', [
                DisplayPreference::PERSONAL,
                DisplayPreference::GENERAL,
            ]),
            'may_be_deleted'      => $item instanceof CommonDBTM && $item->maybeDeleted() && !$item->useDeletedToLockIfDynamic(),
            'may_be_located'      => $item instanceof CommonDBTM && $item->maybeLocated(),
            'may_be_browsed'      => $item !== null && Toolbox::hasTrait($item, TreeBrowse::class),
            'may_be_unpublished'  => $itemtype == 'KnowbaseItem' && $item->canUpdate(),
            'original_params'     => $params,
            'active_savedsearch'  => $active_savedsearch,
            'active_search_name'  => $active_search_name,
            'active_sort_name'    => $active_sort_name,
            'active_sort'         => $active_sort,
        ] + ($params['extra_twig_params'] ?? []));

        // Add items in item list
        if (isset($data['data']['rows'])) {
            foreach ($data['data']['rows'] as $row) {
                if ($itemtype !== AllAssets::class) {
                    Session::addToNavigateListItems($itemtype, $row["id"]);
                } else {
                    // In case of a global search, reset and empty navigation list to ensure navigation in
                    // item header context is not shown. Indeed, this list does not support navigation through
                    // multiple itemtypes, so it should not be displayed in global search context.
                    Session::initNavigateListItems($row['TYPE'] ?? $data['itemtype']);
                }
            }
        }

        // Clean previous selection
        $_SESSION['glpimassiveactionselected'] = [];
    }

    public static function showNewLine($odd = false, $is_deleted = false): string
    {
        $class = " class='tab_bg_2" . ($is_deleted ? '_2' : '') . "' ";
        if ($odd) {
            $class = " class='tab_bg_1" . ($is_deleted ? '_2' : '') . "' ";
        }
        return "<tr $class>";
    }

    public static function showEndLine(): string
    {
        return '</tr>';
    }

    public static function showBeginHeader(): string
    {
        return '<thead>';
    }

    public static function showHeader($rows, $cols, $fixed = 0): string
    {
        if ($fixed) {
            return "<div class='text-center'><table class='table'>";
        }

        return "<div class='text-center'><table class='table card-table table-hover'>";
    }

    public static function showHeaderItem($value, &$num, $linkto = "", $issort = 0, $order = "", $options = ""): string
    {
        $class = "";
        if ($issort) {
            $class = "order_$order";
        }
        $out = "<th $options class='" . \htmlescape($class) . "'>";
        if (!empty($linkto)) {
            $out .= "<a href=\"" . \htmlescape($linkto) . "\">";
        }
        $out .= $value;
        if (!empty($linkto)) {
            $out .= "</a>";
        }
        $out .= "</th>";
        $num++;
        return $out;
    }

    public static function showEndHeader(): string
    {
        return '</thead>';
    }

    public static function showItem($value, &$num, $row, $extraparam = ''): string
    {
        global $CFG_GLPI;
        $out = "<td $extraparam valign='top'>";

        if (!preg_match('/' . Search::LBHR . '/', $value)) {
            $values = preg_split('/' . Search::LBBR . '/i', $value);
            $line_delimiter = '<br>';
        } else {
            $values = preg_split('/' . Search::LBHR . '/i', $value);
            $line_delimiter = '<hr>';
        }

        if (
            count($values) > 1
            && Toolbox::strlen($value) > $CFG_GLPI['cut']
        ) {
            $value = '';
            foreach ($values as $v) {
                $value .= $v . $line_delimiter;
            }
            $value = preg_replace('/' . Search::LBBR . '/', '<br>', $value);
            $value = preg_replace('/' . Search::LBHR . '/', '<hr>', $value);
            $value = '<div class="fup-popup">' . $value . '</div>';
            $valTip = ' ' . Html::showToolTip(
                $value,
                [
                    'awesome-class'   => 'fa-comments',
                    'display'         => false,
                    'autoclose'       => false,
                    'onclick'         => true,
                ]
            );
            $out .= $values[0] . $valTip;
        } else {
            $value = preg_replace('/' . Search::LBBR . '/', '<br>', $value);
            $value = preg_replace('/' . Search::LBHR . '/', '<hr>', $value);
            $out .= $value;
        }
        $out .= "</td>\n";
        return $out;
    }

    public static function showFooter($title = "", $count = null): string
    {
        return "</table></div>\n";
    }

    public static function showError($message = ''): string
    {
        return "<div class='center b'>" . \htmlescape($message) . "</div>";
    }
}
