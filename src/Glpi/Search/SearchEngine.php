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

namespace Glpi\Search;

use AllAssets;
use CommonDBTM;
use CommonITILObject;
use CommonITILTask;
use CommonITILValidation;
use DisplayPreference;
use Dropdown;
use Entity;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Asset\Asset_PeripheralAsset;
use Glpi\Debug\Profiler;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Features\TreeBrowseInterface;
use Glpi\Plugin\Hooks;
use Glpi\Search\Input\QueryBuilder;
use Glpi\Search\Input\SearchInputInterface;
use Glpi\Search\Output\AbstractSearchOutput;
use Glpi\Search\Output\Csv;
use Glpi\Search\Output\HTMLSearchOutput;
use Glpi\Search\Output\MapSearchOutput;
use Glpi\Search\Output\NamesListSearchOutput;
use Glpi\Search\Output\Ods;
use Glpi\Search\Output\Pdf;
use Glpi\Search\Output\Xlsx;
use Glpi\Search\Provider\SearchProviderInterface;
use Glpi\Search\Provider\SQLProvider;
use Glpi\Socket;
use ITILFollowup;
use ITILSolution;
use KnowbaseItem;
use Plugin;
use RuntimeException;
use Search;
use Session;
use Toolbox;

use function Safe\preg_match;

/**
 * The search engine.
 *
 * This is not currently the recommended entrypoint for the Search engine.
 * Use {@link Search} instead!
 *
 * @internal Not for use outside {@link Search} class and the "Glpi\Search" namespace.
 */
final class SearchEngine
{
    /**
     * @param int $output_type
     * @param array $data
     * @return AbstractSearchOutput
     */
    public static function getOutputForLegacyKey(int $output_type, array $data = []): AbstractSearchOutput
    {
        switch ($output_type) {
            case Search::GLOBAL_SEARCH:
                return new HTMLSearchOutput();
            case Search::HTML_OUTPUT:
                return (isset($data['as_map']) && $data['as_map']) ? new MapSearchOutput() : new HTMLSearchOutput();
            case Search::PDF_OUTPUT_LANDSCAPE:
                return new Pdf(Pdf::LANDSCAPE);
            case Search::PDF_OUTPUT_PORTRAIT:
                return new Pdf(Pdf::PORTRAIT);
            case Search::CSV_OUTPUT:
                return new Csv();
            case Search::ODS_OUTPUT:
                return new Ods();
            case Search::XLSX_OUTPUT:
                return new Xlsx();
            case Search::NAMES_OUTPUT:
                return new NamesListSearchOutput();
            default:
                throw new RuntimeException('Unknown output type: ' . $output_type);
        }
    }

    /**
     * Get meta types available for search engine
     *
     * @param string $itemtype Type to display the form
     *
     * @return class-string<CommonDBTM>[] Array of available itemtype
     **/
    public static function getMetaItemtypeAvailable($itemtype): array
    {
        global $CFG_GLPI;

        $ref_itemtype = self::getMetaReferenceItemtype($itemtype);
        if ($ref_itemtype === false) {
            return [];
        }

        if (!(($item = getItemForItemtype($ref_itemtype)) instanceof CommonDBTM)) {
            return [];
        }

        $linked = [];
        foreach ($CFG_GLPI as $key => $values) {
            if ($key === 'link_types') {
                // Links are associated to all items of a type, it does not make any sense to use them in metasearch
                continue;
            }
            if ($key === 'ticket_types' && $item instanceof CommonITILObject) {
                // Linked are filtered by CommonITILObject::getAllTypesForHelpdesk()
                $linked = array_merge($linked, array_keys($item::getAllTypesForHelpdesk()));
                continue;
            }

            if ($key === 'itil_types') {
                if ($item instanceof CommonITILTask || $item instanceof CommonITILValidation) {
                    $linked[] = $item::getItilObjectItemType();
                } else {
                    $timeline_types = [ITILFollowup::class, ITILSolution::class];
                    foreach ($timeline_types as $timeline_type) {
                        if ($item instanceof $timeline_type) {
                            $linked = [...$linked, ...$values];
                        }
                    }
                }
            }

            foreach (self::getMetaParentItemtypesForTypesConfig($key) as $config_itemtype) {
                if ($ref_itemtype === $config_itemtype::getType()) {
                    // List is related to source itemtype, all types of list are so linked
                    $linked = array_merge($linked, $values);
                } elseif (in_array($ref_itemtype, $values)) {
                    // Source itemtype is inside list, type corresponding to list is so linked
                    $linked[] = $config_itemtype::getType();
                }
            }
        }

        // Add entity meta if needed
        if ($item->isField('entities_id') && !($item instanceof Entity)) {
            $linked[] = Entity::getType();
        }

        return array_unique($linked);
    }

    /**
     * Returns parents itemtypes having subitems defined in given config key.
     * This list is filtered and is only valid in a "meta" search context.
     *
     * @param string $config_key
     *
     * @return string[]
     */
    private static function getMetaParentItemtypesForTypesConfig(string $config_key): array
    {
        $matches = [];
        if (str_contains($config_key, 'rule') || preg_match('/^(.+)_types$/', $config_key, $matches) === 0) {
            return [];
        }

        $key_to_itemtypes = [
            'appliance_types'      => ['Appliance'],
            'directconnect_types'  => Asset_PeripheralAsset::getPeripheralHostItemtypes(),
            'infocom_types'        => ['Budget', 'Infocom'],
            'assignable_types'     => ['Group', 'User'],
            'project_asset_types'  => ['Project'],
            'rackable_types'       => ['Enclosure', 'Rack'],
            'socket_types'         => [Socket::class],
            'ticket_types'         => ['Change', 'Problem', 'Ticket'],
        ];

        if (array_key_exists($config_key, $key_to_itemtypes)) {
            return $key_to_itemtypes[$config_key];
        }

        $itemclass = $matches[1];
        if (is_a($itemclass, CommonDBTM::class, true)) {
            return [$itemclass::getType()];
        }

        return [];
    }

    /**
     * Check if an itemtype is a possible subitem of another itemtype in a "meta" search context.
     *
     * @param string $parent_itemtype
     * @param string $child_itemtype
     *
     * @return boolean
     */
    public static function isPossibleMetaSubitemOf(string $parent_itemtype, string $child_itemtype): bool
    {
        global $CFG_GLPI;

        if (
            is_a($parent_itemtype, CommonITILObject::class, true)
            && in_array($child_itemtype, array_keys($parent_itemtype::getAllTypesForHelpdesk()))
        ) {
            return true;
        }

        foreach ($CFG_GLPI as $key => $values) {
            if (
                in_array($parent_itemtype, self::getMetaParentItemtypesForTypesConfig($key))
                && in_array($child_itemtype, $values)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     *
     * @param $itemtype
     * @return class-string<CommonDBTM>|false
     **/
    public static function getMetaReferenceItemtype($itemtype)
    {

        if (!isPluginItemType($itemtype)) {
            return $itemtype;
        }

        // Use reference type if given itemtype extends a reference type.
        $types = [
            'Computer',
            'Problem',
            'Change',
            'Ticket',
            'Printer',
            'Monitor',
            'Peripheral',
            'Software',
            'Phone',
        ];
        foreach ($types as $type) {
            if (is_a($itemtype, $type, true)) {
                return $type;
            }
        }

        return false;
    }

    /**
     * Prepare search criteria to be used for a search
     *
     * @since 0.85
     *
     * @param string $itemtype      Item type
     * @param array  $params        Array of parameters
     *                               may include sort, order, start, list_limit, deleted, criteria, metacriteria
     * @param array  $forcedisplay  Array of columns to display (default empty = empty use display pref and search criteria)
     *
     * @return array prepare to be used for a search (include criteria and others needed information)
     **/
    public static function prepareDataForSearch($itemtype, array $params, array $forcedisplay = []): array
    {
        global $CFG_GLPI;

        // Default values of parameters
        $p = [
            'itemtype'                  => $itemtype,
            'criteria'                  => [],
            'metacriteria'              => [],
            'sort'                      => [0],
            'order'                     => ['ASC'],
            'start'                     => 0,
            'is_deleted'                => 0,
            'export_all'                => 0,
            'display_type'              => Search::HTML_OUTPUT,
            'showmassiveactions'        => true,
            'dont_flush'                => false,
            'show_pager'                => true,
            'show_footer'               => true,
            'no_sort'                   => false,
            'list_limit'                => $_SESSION['glpilist_limit'],
            'massiveactionparams'       => [],
            'disable_order_by_fallback' => false,
        ];
        if (class_exists($itemtype)) {
            $p['target']       = $itemtype::getSearchURL();
        } else {
            $p['target']       = Toolbox::getItemTypeSearchURL($itemtype);
        }

        if ($itemtype == KnowbaseItem::class) {
            $params = KnowbaseItem::getAdditionalSearchCriteria($params);
        }

        foreach ($params as $key => $val) {
            switch ($key) {
                case 'order':
                    if (!is_array($val)) {
                        // Backward compatibility with GLPI < 10.0 links
                        if (in_array($val, ['ASC', 'DESC'])) {
                            $p[$key] = [$val];
                        }
                        break;
                    }
                    $p[$key] = $val;
                    break;
                case 'sort':
                    if (!is_array($val)) {
                        // Backward compatibility with GLPI < 10.0 links
                        $val = (int) $val;
                        if ($val >= 0) {
                            $p[$key] = [$val];
                        }
                        break;
                    }
                    $p[$key] = $val;
                    break;
                case 'is_deleted':
                    if ($val == 1) {
                        $p[$key] = '1';
                    }
                    break;
                default:
                    $p[$key] = $val;
                    break;
            }
        }

        // Set display type for export if define
        if (isset($p['display_type'])) {
            // Limit to 10 element
            if ($p['display_type'] == Search::GLOBAL_SEARCH) {
                $p['list_limit'] = Search::GLOBAL_DISPLAY_COUNT;
            }
        }

        if ($p['export_all']) {
            $p['start'] = 0;
        }

        $search_input_class = self::getSearchInputClass($p);
        $p = $search_input_class::cleanParams($p);

        $data             = [];
        $data['search']   = $p;
        $data['itemtype'] = $itemtype;

        // Instanciate an object to access method
        $data['item'] = null;

        if ($itemtype != AllAssets::getType()) {
            $data['item'] = getItemForItemtype($itemtype);
        }

        $data['display_type'] = $data['search']['display_type'];

        if (!$CFG_GLPI['allow_search_all']) {
            foreach ($p['criteria'] as $val) {
                if (isset($val['field']) && $val['field'] == 'all') {
                    throw new AccessDeniedHttpException();
                }
            }
        }
        if (!$CFG_GLPI['allow_search_view'] && !array_key_exists('globalsearch', $p)) {
            foreach ($p['criteria'] as $val) {
                if (isset($val['field']) && $val['field'] == 'view') {
                    throw new AccessDeniedHttpException();
                }
            }
        }

        /// Get the items to display
        // Add searched items

        $forcetoview = count($forcedisplay) || isset($p['forcetoview']);
        $data['search']['all_search']  = false;
        $data['search']['view_search'] = false;
        // If no research limit research to display item and compute number of item using simple request
        $data['search']['no_search']   = true;

        $data['toview'] = SearchOption::getDefaultToView($itemtype, $params);
        if ($p['sort'] === [0]) {
            $p['sort'] = [array_values($data['toview'])[0]];
        }
        $data['meta_toview'] = [];
        if (!$forcetoview) {
            // Add items to display depending on personal prefs
            $displaypref = DisplayPreference::getForTypeUser($itemtype, Session::getLoginUserID(), Session::getCurrentInterface());
            if (count($displaypref)) {
                foreach ($displaypref as $val) {
                    $data['toview'][] = $val;
                }
            }
        } else {
            $data['toview'] = array_merge($data['toview'], ($p['forcetoview'] ?? []), $forcedisplay);
        }

        if (count($p['criteria']) > 0) {
            // use a recursive closure to push searchoption when using nested criteria
            $parse_criteria = function ($criteria) use (&$parse_criteria, &$data) {
                foreach ($criteria as $criterion) {
                    // recursive call
                    if (isset($criterion['criteria'])) {
                        $parse_criteria($criterion['criteria']);
                    } else {
                        // normal behavior
                        if (
                            isset($criterion['field'])
                            && !in_array($criterion['field'], $data['toview'])
                        ) {
                            if (
                                $criterion['field'] != 'all'
                                && $criterion['field'] != 'view'
                                && (!isset($criterion['meta'])
                                    || !$criterion['meta'])
                            ) {
                                $data['toview'][] = $criterion['field'];
                            } elseif ($criterion['field'] == 'all') {
                                $data['search']['all_search'] = true;
                            } elseif ($criterion['field'] == 'view') {
                                $data['search']['view_search'] = true;
                            }
                            if (isset($criterion['virtual']) && $criterion['virtual']) {
                                $data['virtual'][$criterion['field']] = $criterion['field'];
                            }
                        }

                        if (
                            isset($criterion['value'])
                            && (strlen($criterion['value']) > 0)
                        ) {
                            $data['search']['no_search'] = false;
                        }
                    }
                }
            };

            // call the closure
            $parse_criteria($p['criteria']);
        }

        if (count($p['metacriteria'])) {
            $data['search']['no_search'] = false;
        }

        // Add order item
        $to_add_view = array_diff($p['sort'], $data['toview']);
        array_push($data['toview'], ...$to_add_view);

        // Special case for CommonITILObjects : put ID in front
        if (is_a($itemtype, CommonITILObject::class, true)) {
            $id_opt = SearchOption::getOptionNumber($itemtype, 'id');
            if ($id_opt > 0) {
                array_unshift($data['toview'], $id_opt);
            }
        }

        $limitsearchopt   = SearchOption::getCleanedOptions($itemtype);
        // Clean and reorder toview
        $tmpview = [];
        foreach ($data['toview'] as $val) {
            if (isset($limitsearchopt[$val]) && !in_array($val, $tmpview)) {
                $tmpview[] = $val;
            }
        }
        $data['tocompute'] = $tmpview;
        $data['toview'] = array_diff($data['tocompute'], $data['virtual'] ?? []);

        // Force item to display
        if ($forcetoview) {
            foreach ($data['toview'] as $val) {
                if (!in_array($val, $data['tocompute'])) {
                    $data['tocompute'][] = $val;
                }
            }
        }

        return $data;
    }

    /**
     * Reset save searches
     *
     * @return void
     **/
    public static function resetSaveSearch(): void
    {
        unset($_SESSION['glpisearch']);
        $_SESSION['glpisearch'] = [];
    }

    /**
     * @param bool $only_not
     * @return array
     */
    public static function getLogicalOperators($only_not = false): array
    {
        if ($only_not) {
            return [
                'AND'     => Dropdown::EMPTY_VALUE,
                'AND NOT' => __("NOT"),
            ];
        }

        return [
            'AND'     => __('AND'),
            'OR'      => __('OR'),
            'AND NOT' => __('AND NOT'),
            'OR NOT'  => __('OR NOT'),
        ];
    }

    /**
     * Get table name for item type
     *
     * @param string $itemtype
     *
     * @return string
     */
    public static function getOrigTableName(string $itemtype): string
    {
        return (is_a($itemtype, CommonDBTM::class, true)) ? $itemtype::getTable() : getTableForItemType($itemtype);
    }

    /**
     * @param array $params
     * @return class-string<SearchInputInterface>
     */
    private static function getSearchInputClass(array $params = []): string
    {
        // TODO Maybe have a plugin hook here for custom search input classes
        return QueryBuilder::class;
    }

    /**
     * @param array $params
     * @return class-string<SearchProviderInterface>
     */
    private static function getSearchProviderClass(array $params = []): string
    {
        // TODO Maybe have a plugin hook here for custom search provider classes
        return SQLProvider::class;
    }

    /**
     * Show the search engine.
     *
     * If you want to override some default parameters, you may need to provide them in $get.
     * The parameters are handled as follows:
     * - The $_GET or $get array is passed to the search input class to be parsed and have some default values set.
     * - The returned parameters are then merged with the $params array. Anything set in both arrays will be overwritten by the result of {@link SearchInputInterface::manageParams()}.
     * @param class-string<CommonDBTM> $itemtype
     * @param array $params Array of options:
     *                       - (bool) init_session_data - default: false
     * @return void
     */
    public static function show(string $itemtype, array $params = []): void
    {
        Profiler::getInstance()->start('SearchEngine::show', Profiler::CATEGORY_SEARCH);
        Plugin::doHook(Hooks::PRE_ITEM_LIST, ['itemtype' => $itemtype, 'options' => []]);

        if (($params['init_session_data'] ?? false) && isset($params['criteria'])) {
            // Search engine need session data to display criteria properly.
            // This parameter is needed when rendering multiple searches with
            // different criteria from a twig template (as we can't set the
            // session data from twig).
            $_SESSION['glpisearch'][$itemtype]['criteria'] = $params['criteria'];
        }

        $search_input_class = self::getSearchInputClass($params);
        $params = array_merge($params, $search_input_class::manageParams($itemtype, $_GET));

        $params['display_type'] = Search::HTML_OUTPUT;

        echo "<div class='search_page row'>";
        TemplateRenderer::getInstance()->display('layout/parts/saved_searches.html.twig', [
            'itemtype' => $itemtype,
        ]);
        echo "<div class='col search-container' data-glpi-search-container>";

        $output = self::getOutputForLegacyKey($params['display_type'], $params);
        if ($output instanceof HTMLSearchOutput) {
            $output::showPreSearchDisplay($itemtype);
        }

        if ($_SESSION['glpishow_search_form']) {
            $search_input_class::showGenericSearch($itemtype, $params);
        }

        $params = $output::prepareInputParams($itemtype, $params);
        $item = getItemForItemtype($itemtype);
        if ((int) $params['browse'] === 1 && $item instanceof TreeBrowseInterface) {
            $item::showBrowseView($itemtype, $params);
        } else {
            self::showOutput($itemtype, $params);
        }
        echo "</div>";
        echo "</div>";

        Plugin::doHook(Hooks::POST_ITEM_LIST, ['itemtype' => $itemtype, 'options' => []]);
        Profiler::getInstance()->stop('SearchEngine::show');
    }

    public static function getData(string $itemtype, array $params, array $forced_display = []): array
    {
        $data = self::prepareDataForSearch($itemtype, $params, $forced_display);
        $search_provider_class = self::getSearchProviderClass($params);

        $output = self::getOutputForLegacyKey(
            $params['display_type'] ?? Search::HTML_OUTPUT,
            $params
        );
        if (!$output->canDisplayResultsContainerWithoutExecutingSearch()) {
            // Force search execution
            $execute_search = true;
        } else {
            // Search execution is optional
            $execute_search = $params['execute_search'] ?? true;
        }

        if ($execute_search) {
            $search_provider_class::constructSQL($data);
            $search_provider_class::constructData($data);
        }

        return $data;
    }

    /**
     * @param string $itemtype The itemtype being displayed
     * @param array $params The search parameters
     * @param array $forced_display Array of columns to display (default empty = empty use display pref and search criteria)
     * @return void
     */
    public static function showOutput(string $itemtype, array $params, array $forced_display = []): void
    {
        $output = self::getOutputForLegacyKey($params['display_type'] ?? Search::HTML_OUTPUT, $params);
        $data = self::getData($itemtype, $params, $forced_display);
        $output->displayData($data, $params);
    }
}
