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

namespace Glpi\Search\Output;

use Glpi\Application\View\TemplateRenderer;
use Glpi\Dashboard\Grid;
use Ticket;

/**
 *
 * @internal Not for use outside {@link Search} class and the "Glpi\Search" namespace.
 */
abstract class HTMLSearchOutput extends AbstractSearchOutput
{
    public static function showPreSearchDisplay(string $itemtype): void
    {
        if (
            $itemtype === Ticket::class
            && \Session::getCurrentInterface() === 'central'
            && $default = Grid::getDefaultDashboardForMenu('mini_ticket', true)
        ) {
            $dashboard = new Grid($default, 33, 2);
            $dashboard->show(true);
        }
    }

    public static function displayData(array $data, array $params = [])
    {
        global $CFG_GLPI;

        if (!isset($data['data']) || !isset($data['data']['totalcount'])) {
            return false;
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

        // Contruct parameters
        $globallinkto  = \Toolbox::append_params([
            'criteria'     => $search['criteria'],
            'metacriteria' => $search['metacriteria'],
        ], '&');

        $parameters = http_build_query([
            'sort'   => $search['sort'],
            'order'  => $search['order']
        ]);

        $parameters .= "&{$globallinkto}";

        if (isset($_GET['_in_modal'])) {
            $parameters .= "&_in_modal=1";
        }

        // For plugin add new parameter if available
        if ($plug = isPluginItemType($data['itemtype'])) {
            $out = \Plugin::doOneHook($plug['plugin'], 'addParamFordynamicReport', $data['itemtype']);
            if (is_array($out) && count($out)) {
                $parameters .= \Toolbox::append_params($out, '&');
            }
        }

        $prehref = $search['target'] . (strpos($search['target'], "?") !== false ? "&" : "?");
        $href    = $prehref . $parameters;

        \Session::initNavigateListItems($data['itemtype'], '', $href);

        $rand = mt_rand();
        TemplateRenderer::getInstance()->display('components/search/display_data.html.twig', [
            'data'                => $data,
            'union_search_type'   => $CFG_GLPI["union_search_type"],
            'rand'                => $rand,
            'no_sort'             => $search['no_sort'] ?? false,
            'order'               => $search['order'] ?? [],
            'sort'                => $search['sort'] ?? [],
            'start'               => $search['start'] ?? 0,
            'limit'               => $_SESSION['glpilist_limit'],
            'count'               => $data['data']['totalcount'] ?? 0,
            'item'                => $item,
            'itemtype'            => $itemtype,
            'href'                => $href,
            'prehref'             => $prehref,
            'posthref'            => $globallinkto,
            'push_history'        => $params['push_history'] ?? true,
            'hide_controls'       => $params['hide_controls'] ?? false,
            'hide_search_toggle'  => $params['hide_criteria'] ?? false,
            'showmassiveactions'  => ($params['showmassiveactions'] ?? $search['showmassiveactions'] ?? true)
                && $data['display_type'] != \Search::GLOBAL_SEARCH
                && ($itemtype == \AllAssets::getType()
                    || count(\MassiveAction::getAllMassiveActions($item, $is_deleted))
                ),
            'massiveactionparams' => $data['search']['massiveactionparams'] + [
                'is_deleted' => $is_deleted,
                'container'  => "massform$itemtype$rand",
            ],
            'can_config'          => \Session::haveRightsOr('search_config', [
                \DisplayPreference::PERSONAL,
                \DisplayPreference::GENERAL
            ]),
            'may_be_deleted'      => $item instanceof \CommonDBTM && $item->maybeDeleted() && !$item->useDeletedToLockIfDynamic(),
            'may_be_located'      => $item instanceof \CommonDBTM && $item->maybeLocated(),
            'may_be_browsed'      => $item !== null && \Toolbox::hasTrait($item, \Glpi\Features\TreeBrowse::class),
            'may_be_unpublished'  => $itemtype == 'KnowbaseItem' && $item->canUpdate(),
        ] + ($params['extra_twig_params'] ?? []));

        // Add items in item list
        foreach ($data['data']['rows'] as $row) {
            if ($itemtype !== \AllAssets::class) {
                \Session::addToNavigateListItems($itemtype, $row["id"]);
            } else {
                // In case of a global search, reset and empty navigation list to ensure navigation in
                // item header context is not shown. Indeed, this list does not support navigation through
                // multiple itemtypes, so it should not be displayed in global search context.
                \Session::initNavigateListItems($row['TYPE'] ?? $data['itemtype']);
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

    public static function showEndLine(bool $is_header_line): string
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
            return "<div class='center'><table border='0' class='table'>";
        }

        return "<div class='center'><table border='0' class='table card-table table-hover'>";
    }

    public static function showHeaderItem($value, &$num, $linkto = "", $issort = 0, $order = "", $options = ""): string
    {
        $class = "";
        if ($issort) {
            $class = "order_$order";
        }
        $out = "<th $options class='$class'>";
        if (!empty($linkto)) {
            $out .= "<a href=\"$linkto\">";
        }
        $out .= $value;
        if (!empty($linkto)) {
            $out .= "</a>";
        }
        $out .= "</th>\n";
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

        if (!preg_match('/' . \Search::LBHR . '/', $value)) {
            $values = preg_split('/' . \Search::LBBR . '/i', $value);
            $line_delimiter = '<br>';
        } else {
            $values = preg_split('/' . \Search::LBHR . '/i', $value);
            $line_delimiter = '<hr>';
        }

        if (
            count($values) > 1
            && \Toolbox::strlen($value) > $CFG_GLPI['cut']
        ) {
            $value = '';
            foreach ($values as $v) {
                $value .= $v . $line_delimiter;
            }
            $value = preg_replace('/' . \Search::LBBR . '/', '<br>', $value);
            $value = preg_replace('/' . \Search::LBHR . '/', '<hr>', $value);
            $value = '<div class="fup-popup">' . $value . '</div>';
            $valTip = "&nbsp;" . \Html::showToolTip(
                $value,
                [
                    'awesome-class'   => 'fa-comments',
                    'display'         => false,
                    'autoclose'       => false,
                    'onclick'         => true
                ]
            );
            $out .= $values[0] . $valTip;
        } else {
            $value = preg_replace('/' . \Search::LBBR . '/', '<br>', $value);
            $value = preg_replace('/' . \Search::LBHR . '/', '<hr>', $value);
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
        return "<div class='center b'>$message</div>\n";
    }
}
