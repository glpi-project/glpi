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

namespace Glpi\Search\Input;

use AllAssets;
use CommonDBTM;
use DefaultFilter;
use DisplayPreference;
use Glpi\Application\Environment;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Plugin\Hooks;
use Glpi\Search\DefaultSearchRequestInterface;
use Glpi\Search\SearchEngine;
use Glpi\Search\SearchOption;
use Glpi\Toolbox\URL;
use Html;
use Override;
use Plugin;
use RuntimeException;
use SavedSearch_User;
use Search;
use Session;
use Toolbox;

use function Safe\parse_url;

final class QueryBuilder implements SearchInputInterface
{
    /**
     * Print generic search form
     *
     * Params need to parsed before using Search::manageParams function
     *
     * @param string $itemtype  Type to display the form
     * @param array  $params    Array of parameters may include sort, is_deleted, criteria, metacriteria
     *
     * @return void
     **/
    public static function showGenericSearch(string $itemtype, array $params): void
    {
        // Default values of parameters
        $p['sort']         = '';
        $p['is_deleted']   = 0;
        $p['as_map']       = 0;
        $p['browse']       = 0;
        $p['criteria']     = [];
        $p['metacriteria'] = [];
        if (class_exists($itemtype)) {
            $p['target']       = $itemtype::getSearchURL();
        } else {
            $p['target']       = Toolbox::getItemTypeSearchURL($itemtype);
        }
        $p['showreset']                     = true;
        $p['forcereset']                    = false;
        $p['showbookmark']                  = true;
        $p['showfolding']                   = true;
        $p['mainform']                      = true;
        $p['prefix_crit']                   = '';
        $p['addhidden']                     = [];
        $p['showaction']                    = true;
        $p['actionname']                    = 'search';
        $p['actionvalue']                   = _sx('button', 'Search');
        $p['unpublished']                   = 1;
        $p['hide_controls']                 = false;
        $p['showmassiveactions']            = true;
        $p['extra_actions_templates']       = [];
        $p['hide_criteria']                 = $params['hide_criteria'] ?? false;
        $p['is_criteria_filter']            = $params['is_criteria_filter'] ?? false;

        foreach ($params as $key => $val) {
            $p[$key] = $val;
        }
        $p['target'] = URL::sanitizeURL($p['target']);

        // Itemtype name used in JS function names, etc
        $normalized_itemtype = Toolbox::getNormalizedItemtype($itemtype);
        $linked = SearchEngine::getMetaItemtypeAvailable($itemtype);

        $can_disablefilter = Session::haveRightsOr('search_config', [DisplayPreference::PERSONAL, DisplayPreference::GENERAL]);

        $target_query  = parse_url($p['target'], PHP_URL_QUERY);
        $additional_params = [];
        if ($target_query !== false && $target_query != '') {
            parse_str($target_query, $additional_params);
        }

        TemplateRenderer::getInstance()->display('components/search/query_builder/main.html.twig', [
            'mainform'            => $p['mainform'],
            'showaction'          => $p['showaction'],
            'itemtype'            => $itemtype,
            'normalized_itemtype' => $normalized_itemtype,
            'criteria'            => $p['criteria'],
            'p'                   => $p,
            'linked'              => $linked,
            'can_disablefilter'   => $can_disablefilter,
            'additional_params'   => $additional_params,
        ]);
    }


    /**
     * Print generic ordering form
     *
     * Params need to parsed before using Search::manageParams function
     *
     * @since 11.0
     *
     * @param string $itemtype  Type to display the form
     * @param array  $params    Array of parameters may include sort, is_deleted, criteria, metacriteria
     *
     * @return void
     */
    public static function showGenericSort(string $itemtype, array $params): void
    {
        $p = [
            'sort' => [],
            'order' => [],
        ];

        foreach ($params as $key => $val) {
            $p[$key] = $val;
        }

        TemplateRenderer::getInstance()->display('components/search/query_builder/sort/main.html.twig', [
            'itemtype'            => $itemtype,
            'normalized_itemtype' => Toolbox::getNormalizedItemtype($itemtype),
            'p'                   => $p,
        ]);
    }

    /**
     * Display first part of criteria (field + searchtype, just after link)
     * will call displaySearchoptionValue for the next part (value)
     *
     * @param  array  $request we should have these keys of parameters:
     *                            - itemtype: main itemtype for criteria, sub one for metacriteria
     *                            - num: index of the criteria
     *                            - field: field key of the criteria
     *                            - p: params of showGenericSearch method
     *
     * @return void
     */
    public static function displaySearchoption($request = [])
    {
        global $CFG_GLPI;
        if (
            !isset($request["itemtype"])
            || !isset($request["field"])
            || !isset($request["num"])
        ) {
            return;
        }

        $p      = $request['p'];
        $num    = (int) $request['num'];
        $prefix = isset($p['prefix_crit']) ? htmlescape($p['prefix_crit']) : '';

        if (!is_subclass_of($request['itemtype'], 'CommonDBTM')) {
            throw new RuntimeException('Invalid itemtype provided!');
        }

        if (isset($request['meta']) && $request['meta']) {
            $fieldname = 'metacriteria';
        } else {
            $fieldname = 'criteria';
            $request['meta'] = 0;
        }

        $actions = SearchOption::getActionsFor($request["itemtype"], $request["field"]);

        // is it a valid action for type ?
        if (
            count($actions)
            && (empty($request['searchtype']) || !isset($actions[$request['searchtype']]))
        ) {
            $tmp = $actions;
            unset($tmp['searchopt']);
            $request['searchtype'] = key($tmp);
            unset($tmp);
        }

        $normalized_itemtype = Toolbox::getNormalizedItemtype($request["itemtype"]);
        $dropdownname = Html::cleanId("spansearchtype$fieldname"
            . $normalized_itemtype
            . $prefix
            . $num);
        $searchopt = [];
        if (count($actions) > 0) {
            // get already get search options
            if (isset($actions['searchopt'])) {
                $searchopt = $actions['searchopt'];
                // No name for clean array with quotes
                unset($searchopt['name']);
                unset($actions['searchopt']);
            }
        }

        TemplateRenderer::getInstance()->display('components/search/query_builder/search_option.html.twig', [
            'itemtype' => $request['itemtype'],
            'fieldname' => $fieldname,
            'searchtype' => $request['searchtype'],
            'actions' => $actions,
            'searchopt' => $searchopt,
            'dropdownname' => $dropdownname,
            'num' => $num,
            'value' => $request['value'],
            'prefix' => $prefix,
            'p' => $p,
        ]);
    }

    /**
     * Display last part of criteria (value, just after searchtype)
     * called by displaySearchoptionValue
     *
     * @param  array  $request we should have these keys of parameters:
     *                            - searchtype: (contains, equals) passed by displaySearchoption
     *
     * @return void|string
     */
    public static function displaySearchoptionValue($request = [])
    {
        if (!isset($request['searchtype'])) {
            return '';
        }

        $p                 = $request['p'];
        $prefix            = isset($p['prefix_crit']) ? htmlescape($p['prefix_crit']) : '';
        $searchopt         = $request['searchopt'] ?? [];
        $request['value']  = rawurldecode($request['value']);
        $fieldname         = isset($request['meta']) && $request['meta']
            ? 'metacriteria'
            : 'criteria';
        $inputname         = $fieldname . $prefix . '[' . $request['num'] . '][value]';
        $display           = false;
        $item              = getItemForItemtype($request['itemtype']);
        $options2          = [];
        $options2['value'] = $request['value'];
        $options2['width'] = '100%';
        // For tree dropdpowns
        $options2['permit_select_parent'] = true;

        switch ($request['searchtype']) {
            case "equals":
            case "notequals":
            case "morethan":
            case "lessthan":
            case "under":
            case "notunder":
                if (isset($searchopt['field'])) {
                    // Specific cases
                    switch ($searchopt['table'] . "." . $searchopt['field']) {
                        // Add mygroups choice to searchopt
                        case "glpi_groups.completename":
                            $searchopt['toadd'] = ['mygroups' => __('My groups')];
                            break;

                        case "glpi_changes.status":
                        case "glpi_changes.impact":
                        case "glpi_changes.urgency":
                        case "glpi_problems.status":
                        case "glpi_problems.impact":
                        case "glpi_problems.urgency":
                        case "glpi_tickets.status":
                        case "glpi_tickets.impact":
                        case "glpi_tickets.urgency":
                            $options2['showtype'] = 'search';
                            break;

                        case "glpi_changes.priority":
                        case "glpi_problems.priority":
                        case "glpi_tickets.priority":
                            $options2['showtype']  = 'search';
                            $options2['withmajor'] = true;
                            break;

                        case "glpi_tickets.global_validation":
                            $options2['all'] = true;
                            break;

                        case "glpi_ticketvalidations.status":
                            $options2['all'] = true;
                            break;

                        case "glpi_users.name":
                            $options2['right']            = ($searchopt['right'] ?? 'all');
                            $options2['inactive_deleted'] = 1;
                            $searchopt['toadd'] = [
                                [
                                    'id'    => 'myself',
                                    'text'  => __('Myself'),
                                ],
                            ];

                            break;
                    }

                    // Standard datatype usage
                    if (isset($searchopt['datatype'])) {
                        switch ($searchopt['datatype']) {
                            case "date":
                            case "date_delay":
                            case "datetime":
                                $options2['relative_dates'] = true;
                                break;
                        }
                    }

                    $out = $item->getValueToSelect($searchopt, $inputname, $request['value'], $options2);
                    if (strlen($out)) {
                        echo $out;
                        $display = true;
                    }

                    //Could display be handled by a plugin ?
                    if (
                        !$display
                        && $plug = isPluginItemType(getItemTypeForTable($searchopt['table']))
                    ) {
                        $display = Plugin::doOneHook(
                            $plug['plugin'],
                            Hooks::AUTO_SEARCH_OPTION_VALUES,
                            [
                                'name'           => $inputname,
                                'searchtype'     => $request['searchtype'],
                                'searchoption'   => $searchopt,
                                'value'          => $request['value'],
                            ]
                        );
                    }
                }
                break;
            case 'empty':
                echo "<input type='hidden' name='" . htmlescape($inputname) . "' value='null'>";
                $display = true;
                break;
        }

        // Default case : text field
        if (!$display) {
            $fieldpattern = self::getInputValidationPattern($searchopt['datatype'] ?? '', false);
            $pattern = $fieldpattern['pattern'];
            $message = $fieldpattern['validation_message'];

            $field_title = __s('Criteria value');
            echo "<input type='text' class='form-control' size='13' aria-label='" . htmlescape($field_title) . "' name='" . htmlescape($inputname) . "' value=\""
                . htmlescape($request['value']) . "\" pattern=\"" . htmlescape($pattern) . "\">"
                . "<span class='invalid-tooltip'>" . htmlescape($message) . "</span>";
        }
    }

    /**
     * Display a criteria field set, this function should be called by ajax/search.php
     *
     * @since 9.4
     *
     * @param  array  $request we should have these keys of parameters:
     *                            - itemtype: main itemtype for criteria, sub one for metacriteria
     *                            - num: index of the criteria
     *                            - p: params of showGenericSearch method
     *
     * @return void
     */
    public static function displayCriteria($request = [])
    {
        global $CFG_GLPI;

        if (
            !isset($request["itemtype"])
            || !isset($request["num"])
        ) {
            return;
        }

        $num         = (int) $request['num'];
        $p           = $request['p'];

        if ($p['criteria'][$num]['_hidden'] ?? false) {
            return;
        }

        $options     = Search::getCleanedOptions($request["itemtype"]);
        $randrow     = mt_rand();
        $normalized_itemtype = Toolbox::getNormalizedItemtype($request["itemtype"]);
        $rowid       = 'searchrow' . $normalized_itemtype . $randrow;
        $prefix      = isset($p['prefix_crit']) ? htmlescape($p['prefix_crit']) : '';
        $parents_num = $p['parents_num'] ?? [];
        $criteria    = [];
        $from_meta   = isset($request['from_meta']) && $request['from_meta'];

        $sess_itemtype = $request["itemtype"];
        if ($from_meta) {
            $sess_itemtype = $request["parent_itemtype"];
        }

        if (!$criteria = self::findCriteriaInSession($sess_itemtype, $num, $parents_num)) {
            $criteria = self::getDefaultCriteria($request["itemtype"]);
        }

        $values   = [];
        // display select box to define search item
        if ($CFG_GLPI['allow_search_view'] == 2 && !isset($request['from_meta'])) {
            $values['view'] = __('Items seen');
        }

        reset($options);
        $group = '';

        foreach ($options as $key => $val) {
            // print groups
            if (!is_array($val)) {
                $group = $val;
            } elseif (count($val) == 1) {
                $group = $val['name'];
            } else {
                if (
                    (!isset($val['nosearch']) || ($val['nosearch'] == false))
                    && (!$from_meta || !array_key_exists('nometa', $val) || $val['nometa'] !== true)
                ) {
                    $values[$group][$key] = $val["name"];
                }
            }
        }
        if ($CFG_GLPI['allow_search_view'] == 1 && !isset($request['from_meta'])) {
            $values['view'] = __('Items seen');
        }
        if ($CFG_GLPI['allow_search_all'] && !isset($request['from_meta'])) {
            $values['all'] = __('All');
        }
        $value = '';

        if (isset($criteria['field'])) {
            $value = $criteria['field'];
        }

        $p_value    = $criteria['value'] ?? "";

        TemplateRenderer::getInstance()->display('components/search/query_builder/criteria.html.twig', [
            'mainform'    => $p['mainform'],
            'from_meta'   => $from_meta,
            'meta'        => $criteria['meta'] ?? false,
            'sess_itemtype' => $sess_itemtype,
            'values'      => $values,
            'p_value'     => $p_value,
            'criteria_value' => $value,
            'itemtype'   => $request["itemtype"],
            'num'        => $num,
            'criteria'   => $criteria,
            'prefix'     => $prefix,
            'p'         => $p,
            'row_id'      => $rowid,
        ]);
    }

    /**
     * Display a meta-criteria field set, this function should be called by ajax/search.php
     * Call displayCriteria method after displaying its itemtype field
     *
     * @since 9.4
     *
     * @param  array  $request @see displayCriteria method
     *
     * @return void
     */
    public static function displayMetaCriteria($request = [])
    {
        if (
            !isset($request["itemtype"])
            || !isset($request["num"])
        ) {
            return;
        }

        $p            = $request['p'];
        $num          = (int) $request['num'];
        $prefix       = isset($p['prefix_crit']) ? htmlescape($p['prefix_crit']) : '';
        $parents_num  = $p['parents_num'] ?? [];
        $itemtype     = $request["itemtype"];
        $metacriteria = self::findCriteriaInSession($itemtype, $num, $parents_num);

        // If itemtype is "0", it is an empty meta-criteria
        if (is_array($metacriteria) && array_key_exists('itemtype', $metacriteria) && (string) $metacriteria['itemtype'] === '0') {
            $metacriteria = false;
        }

        if (!$metacriteria) {
            $metacriteria = [];
            // Set default field
            $options  = Search::getCleanedOptions($itemtype);

            foreach ($options as $key => $val) {
                if (is_array($val) && isset($val['table'])) {
                    $metacriteria['field'] = $key;
                    break;
                }
            }
        }

        $linked =  SearchEngine::getMetaItemtypeAvailable($itemtype);
        $rand   = mt_rand();

        $rowid  = 'metasearchrow' . Toolbox::getNormalizedItemtype($request['itemtype']) . $rand;
        TemplateRenderer::getInstance()->display('components/search/query_builder/metacriteria.html.twig', [
            'row_id'       => $rowid,
            'metacriteria' => $metacriteria,
            'prefix'      => $prefix,
            'num'         => $num,
            'itemtype'    => $itemtype,
            'linked'      => $linked,
            'p'           => $p,
        ]);
    }


    /**
     * Display a sort-criteria field set, this function should be called by ajax/search.php
     *
     * @since 11.0
     *
     * @param  array  $request @see displayCriteria method
     *
     * @return void
     */
    public static function displaySortCriteria(array $request = []): void
    {
        if (
            !isset($request["itemtype"])
            || !isset($request["num"])
        ) {
            return;
        }

        $num         = (int) $request['num'];
        $p           = $request['p'] ?? [];

        $sorts      = $p['sort'] ?? [];
        $orders     = $p['order'] ?? [];
        $used       = $request['used'] ?? [];
        $soptions   = SearchOption::getCleanedOptions($request["itemtype"]);
        $soption_id = $sorts[$num] ?? '';
        $soption    = $soptions[$soption_id] ?? [];

        $order = [
            'soption_id' => $soption_id,
            'name'       => $soption['name'] ?? '',
            'order'      => $orders[$num] ?? 'ASC',
        ];

        $randrow     = mt_rand();
        $normalized_itemtype = Toolbox::getNormalizedItemtype($request["itemtype"]);
        $rowid       = 'orderrow' . $normalized_itemtype . $randrow;

        $values   = [];
        reset($soptions);
        $group = '';
        foreach ($soptions as $key => $val) {
            // print groups
            if (!is_array($val)) {
                $group = $val;
            } elseif (count($val) == 1) {
                $group = $val['name'];
            } else {
                if (
                    (!isset($val['nosearch']) || ($val['nosearch'] == false))
                ) {
                    $values[$group][$key] = $val["name"];
                }
            }
        }

        TemplateRenderer::getInstance()->display('components/search/query_builder/sort/criteria.html.twig', [
            'itemtype' => $request["itemtype"],
            'num'      => $num,
            'order'    => $order,
            'rowid'    => $rowid,
            'values'   => $values,
            'used'     => array_combine($used, $used),
        ]);
    }

    /**
     * Display a group of nested criteria.
     * A group (parent) criteria  can contain children criteria (who also contain children, etc)
     *
     * @since 9.4
     *
     * @param  array  $request @see displayCriteria method
     *
     * @return void
     */
    public static function displayCriteriaGroup($request = [])
    {

        $num         = (int) $request['num'];
        $p           = $request['p'];
        $randrow     = mt_rand();
        $rowid       = 'searchrow' . Toolbox::getNormalizedItemtype($request['itemtype']) . $randrow;
        $prefix      = isset($p['prefix_crit']) ? htmlescape($p['prefix_crit']) : '';
        $parents_num = $p['parents_num'] ?? [];

        if (!$criteria = self::findCriteriaInSession($request['itemtype'], $num, $parents_num)) {
            $criteria = [
                'criteria' => self::getDefaultCriteria($request['itemtype']),
            ];
        }

        TemplateRenderer::getInstance()->display('components/search/query_builder/criteria_group.html.twig', [
            'num' => $num,
            'row_id' => $rowid,
            'prefix' => $prefix,
            'criteria' => $criteria,
            'parents_num' => $parents_num,
            'itemtype' => $request['itemtype'],
            'p' => $p,
        ]);
    }

    #[Override]
    public static function manageParams($itemtype, $params = [], $usesession = true, $forcebookmark = false): array
    {
        global $CFG_GLPI;

        $default_values = [];

        $default_values['itemtype'] = $itemtype;
        $default_values["start"]       = 0;
        $default_values["order"]       = "ASC";
        if (
            (
                empty($params['criteria'] ?? [])  // No search criteria
                || $params['criteria'] == self::getDefaultCriteria($itemtype) // Default criteria
            )
            && ( // Is an asset
                in_array($itemtype, $CFG_GLPI['asset_types'])
                || $itemtype == AllAssets::getType()
            )
        ) {
            // Disable sort on assets default search request
            // This improve significantly performances on default search queries without much functional costs as users
            // often don't care about the default search results
            $default_values["sort"] = 0;
            // Defining "sort" to 0 is no enough as the search engine will default to sorting on the `id` column.
            // Sorting by id still has a high performance cost on heavy requests, thus we must explicitly request
            // that no ORDER BY clause will be set
            $default_values["disable_order_by_fallback"] = true;
        } else {
            // Search engine will automatically determine the best sorting column (ID or the first displayed column)
            $default_values["sort"]    = 0;
        }
        $default_values["is_deleted"]  = 0;
        $default_values["as_map"]      = 0;
        $default_values["browse"]      = $itemtype::$browse_default ?? 0;
        $default_values["unpublished"] = 1;

        if (isset($params['start'])) {
            $params['start'] = (int) $params['start'];
        }

        $default_values["criteria"]     = self::getDefaultCriteria($itemtype);
        $default_values["metacriteria"] = [];

        // Reorg search array
        // start
        // order
        // sort
        // is_deleted
        // itemtype
        // criteria : array (0 => array (link =>
        //                               field =>
        //                               searchtype =>
        //                               value =>   (contains)
        // metacriteria : array (0 => array (itemtype =>
        //                                  link =>
        //                                  field =>
        //                                  searchtype =>
        //                                  value =>   (contains)

        if ($itemtype != AllAssets::getType() && class_exists($itemtype)) {
            // retrieve default values for current itemtype
            $itemtype_default_values = [];
            $item = getItemForItemtype($itemtype);
            if ($item instanceof DefaultSearchRequestInterface) {
                $itemtype_default_values = $item::getDefaultSearchRequest();
            }

            // retrieve default values for the current user
            $user_default_values = SavedSearch_User::getDefault(Session::getLoginUserID(), $itemtype);
            if ($user_default_values === false) {
                $user_default_values = [];
            }

            // we construct default values in this order:
            // - general default
            // - itemtype default
            // - user default
            //
            // The last ones erase values or previous
            // So, we can combine each part (order from itemtype, criteria from user, etc)
            $default_values = array_merge(
                $default_values,
                $itemtype_default_values,
                $user_default_values
            );
        }

        // First view of the page or force bookmark : try to load a bookmark
        if (
            $forcebookmark
            || ($usesession
                && !isset($params["reset"])
                && !isset($_SESSION['glpisearch'][$itemtype]))
        ) {
            if (!isset($user_default_values)) {
                $user_default_values = SavedSearch_User::getDefault(Session::getLoginUserID(), $itemtype);
            }
            if ($user_default_values) {
                $_SESSION['glpisearch'][$itemtype] = [];
                // Only get data for bookmarks
                if ($forcebookmark) {
                    $params = $user_default_values;
                } else {
                    // remember the loaded saved search on first load
                    // this is usefull to adapt the search button control label
                    $_SESSION['glpi_loaded_savedsearch'] = $user_default_values['savedsearches_id'];
                }
            }
        }
        // Force reorder criterias
        if (
            isset($params["criteria"])
            && is_array($params["criteria"])
            && count($params["criteria"])
        ) {
            $tmp                = $params["criteria"];
            $params["criteria"] = [];
            foreach ($tmp as $val) {
                $params["criteria"][] = $val;
            }
        }

        // transform legacy meta-criteria in criteria (with flag meta=true)
        // at the end of the array, as before there was only at the end of the query
        if (
            isset($params["metacriteria"])
            && is_array($params["metacriteria"])
        ) {
            // as we will append meta to criteria, check the key exists
            if (!isset($params["criteria"])) {
                $params["criteria"] = [];
            }
            foreach ($params["metacriteria"] as $val) {
                $params["criteria"][] = $val + ['meta' => 1];
            }
            $params["metacriteria"] = [];
        }

        if (
            $usesession
            && isset($params["reset"])
        ) {
            if (isset($_SESSION['glpisearch'][$itemtype])) {
                unset($_SESSION['glpisearch'][$itemtype]);
            }

            // if we ask for reset but without precising particular bookmark
            // then remove current active bookmark
            if (!isset($params['savedsearches_id'])) {
                unset($_SESSION['glpi_loaded_savedsearch']);
            }
        }

        if (
            is_array($params)
            && $usesession
        ) {
            foreach ($params as $key => $val) {
                $_SESSION['glpisearch'][$itemtype][$key] = $val;
            }
        }

        $saved_params = $params;
        foreach ($default_values as $key => $val) {
            if (!isset($params[$key])) {
                if (
                    $usesession
                    && ($key == 'is_deleted' || $key == 'as_map' || $key == 'browse' || $key === 'unpublished' || !isset($saved_params['criteria'])) // retrieve session only if not a new request
                    && isset($_SESSION['glpisearch'][$itemtype][$key])
                ) {
                    $params[$key] = $_SESSION['glpisearch'][$itemtype][$key];
                } else {
                    $params[$key]                    = $val;
                    $_SESSION['glpisearch'][$itemtype][$key] = $val;
                }
            }
        }

        if ($defaultfilter = DefaultFilter::getSearchCriteria($itemtype)) {
            $params['defaultfilter'] = $defaultfilter;
            $can_disablefilter = Session::haveRightsOr('search_config', [DisplayPreference::PERSONAL, DisplayPreference::GENERAL]);
            if (!isset($params['nodefault']) || !$can_disablefilter) {
                $defaultfilter['search_criteria']['_hidden'] = true;
                $params['criteria'][] = $defaultfilter['search_criteria'];
            }
        }
        return self::cleanParams($params);
    }

    #[Override]
    public static function cleanParams(array $params): array
    {
        $int_params = [
            'sort',
        ];

        foreach ($params as $key => &$val) {
            if (in_array($key, $int_params)) {
                if (is_array($val)) {
                    foreach ($val as &$subval) {
                        $subval = (int) $subval;
                    }
                } else {
                    $val = (int) $val;
                }
            }
        }

        self::validateCriteria($params);

        return $params;
    }

    private static function validateCriteria(array &$params): void
    {
        if (!isset($params['criteria'])) {
            return;
        }
        $valid_main_opts = SearchOption::getOptionsForItemtype($params['itemtype']);

        // Validate criteria
        $invalid_criteria = [];
        foreach ($params['criteria'] as $k => $criterion) {
            if (!array_key_exists('field', $criterion) || !is_numeric($criterion['field'])) {
                continue;
            }
            if (isset($criterion['itemtype']) && $criterion['itemtype'] !== $params['itemtype']) {
                // In the criteria array, the search options are from the metatype POV (Agent Name for example is ID 1 in criteria array, but 900 from the POV of Computer)
                $valid_meta_opts = SearchOption::getOptionsForItemtype($criterion['itemtype']);
                if (!isset($valid_meta_opts[(int) $criterion['field']])) {
                    $invalid_criteria[] = (int) $criterion['field'];
                    unset($params['criteria'][$k]);
                }
            } elseif (!isset($valid_main_opts[(int) $criterion['field']])) {
                $invalid_criteria[] = (int) $criterion['field'];
                unset($params['criteria'][$k]);
            }
        }

        // Validate sorts
        if (isset($params['sort'])) {
            if (!is_array($params['sort'])) {
                $params['sort'] = [(int) $params['sort']];
                $params['order'] = [$params['order'] ?? 'ASC'];
            }
            foreach ($params['sort'] as $k => $sorted_id) {
                // Validate sort (IDs are always from the POV of the main itemtype)
                if (!isset($valid_main_opts[$sorted_id])) {
                    unset($params['sort'][$k], $params['order'][$k]);
                }
            }
        }
        if (empty($params['sort'])) {
            $params['sort'] = [0];
            $params['order'] = ['ASC'];
        }

        if (!($params['silent_validation'] ?? false) && count($invalid_criteria) > 0) {
            // There is probably no need to show more information about the invalid criteria
            Session::addMessageAfterRedirect(__s('Some search criteria were removed because they are invalid'), false, WARNING);
            if (
                Environment::get()->shouldEnableExtraDevAndDebugTools()
                || $_SESSION['glpi_use_mode'] === Session::DEBUG_MODE
            ) {
                trigger_error(
                    'Attempted to use invalid search options from itemtype: "' . $params['itemtype'] . '" with IDs ' . implode(', ', $invalid_criteria),
                    E_USER_WARNING
                );
            }
        }
    }

    /**
     * Remove the active saved search in session
     *
     * @since 11.0
     *
     * @return void
     */
    public static function resetActiveSavedSearch(): void
    {
        unset($_SESSION['glpi_loaded_savedsearch']);
    }

    /**
     * construct the default criteria for an itemtype
     *
     * @param class-string<CommonDBTM> $itemtype
     *
     * @return array Criteria
     */
    public static function getDefaultCriteria($itemtype): array
    {
        global $CFG_GLPI;

        $field = '';

        if ($CFG_GLPI['allow_search_view'] == 2) {
            $field = 'view';
        } else {
            $options = SearchOption::getCleanedOptions($itemtype);
            foreach ($options as $key => $val) {
                if (
                    is_array($val)
                    && isset($val['table'])
                ) {
                    $field = $key;
                    break;
                }
            }
        }

        return [
            [
                'link'       => 'AND',
                'field'      => $field,
                'searchtype' => 'contains',
                'value'      => '',
            ],
        ];
    }

    /**
     * Retrieve a single criteria in Session by its index
     *
     * @param class-string<CommonDBTM> $itemtype which glpi type we must search in session
     * @param  integer $num         index of the criteria
     * @param  array   $parents_num node indexes of the parents (@see displayCriteriaGroup)
     *
     * @return array|false The found criteria array or false if nothing found
     */
    private static function findCriteriaInSession($itemtype, $num, $parents_num)
    {
        if (!isset($_SESSION['glpisearch'][$itemtype]['criteria'])) {
            return false;
        }
        $criteria = &$_SESSION['glpisearch'][$itemtype]['criteria'];

        if (count($parents_num)) {
            foreach ($parents_num as $parent) {
                if (!isset($criteria[$parent]['criteria'])) {
                    return false;
                }
                $criteria = &$criteria[$parent]['criteria'];
            }
        }

        if (
            isset($criteria[$num])
            && is_array($criteria[$num])
        ) {
            return $criteria[$num];
        }

        return false;
    }

    /**
     * Get the input value validation pattern for given datatype.
     *
     * @param string    $datatype
     * @param bool      $with_delimiters
     *      True to return a complete pattern, including delimiters.
     *      False to return a pattern without delimiters, that can be used inside another regex or in a HTML input pattern.
     *
     * @return array An array with
     *      - a `pattern` entry that contains the pattern that can be used to validate the value;
     *      - a `validation_message` entry that contains a message that indicates what is the expected value format.
     */
    final public static function getInputValidationPattern(string $datatype, bool $with_delimiters = true): array
    {
        $starting_limit_pattern = '\^?';
        $ending_limit_pattern   = '\$?';
        $relative_operators_pattern = '((>|<|>=|<=)\s*)?';

        switch ($datatype) {
            case 'bool':
                $pattern = '0|1';
                $message = __('must be a boolean (0 or 1)');
                break;

            case 'color':
                $pattern = $starting_limit_pattern . '#?[0-9a-fA-F]{1,6}' . $ending_limit_pattern;
                $message = __('must be a color (6 hexadecimal characters)');
                break;

            case 'count':
            case 'integer':
            case 'number':
            case 'actiontime':
            case 'decimal':
            case 'timestamp':
                $pattern = $relative_operators_pattern . '-?\s*\d+(\.\d+)?';
                $message = __('must be a number');
                break;

            case 'datetime':
                $pattern = $relative_operators_pattern . '([\d:\- ]+|-?\s*\d+(\.\d+)?)';
                $message = __('must be a date time (YYYY-MM-DD HH:mm:SS) or be a relative number of months (e.g. > -6 for dates higher than 6 months ago)');
                break;

            case 'date':
            case 'date_delay':
                $pattern = $relative_operators_pattern . '([\d\-]+|-?\s*\d+(\.\d+)?)';
                $message = __('must be a date (YYYY-MM-DD) or be a relative number of months (e.g. > -6 for dates higher than 6 months ago)');
                break;

            case 'dropdown':
            case 'email':
            case 'itemlink':
            case 'itemtypename':
            case 'specific':
            case 'string':
            case 'text':
            default:
                $pattern = '.*';
                $message = '';
                break;
        }
        if ($pattern != '.*') {
            $pattern = 'NULL|null|(\s*' . $pattern . '\s*)';
        }

        if ($with_delimiters) {
            $pattern = '/^(' . $pattern . ')$/';
        }

        return ['pattern' => $pattern, 'validation_message' => $message];
    }
}
