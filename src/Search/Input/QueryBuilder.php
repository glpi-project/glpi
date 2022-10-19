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

namespace Glpi\Search\Input;

use Glpi\Application\View\TemplateRenderer;
use Glpi\Search\SearchEngine;
use Glpi\Search\SearchOption;
use Glpi\Toolbox\Sanitizer;

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
    public static function showGenericSearch($itemtype, array $params)
    {
        global $CFG_GLPI;

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
            $p['target']       = \Toolbox::getItemTypeSearchURL($itemtype);
        }
        $p['showreset']    = true;
        $p['showbookmark'] = true;
        $p['showfolding']  = true;
        $p['mainform']     = true;
        $p['prefix_crit']  = '';
        $p['addhidden']    = [];
        $p['actionname']   = 'search';
        $p['actionvalue']  = _sx('button', 'Search');
        $p['unpublished'] = 1;

        foreach ($params as $key => $val) {
            $p[$key] = $val;
        }

        // Itemtype name used in JS function names, etc
        $normalized_itemtype = strtolower(str_replace('\\', '', $itemtype));
        $rand_criteria = mt_rand();
        $main_block_class = '';
        $card_class = 'search-form card card-sm mb-4';
        if ($p['mainform']) {
            echo "<form name='searchform$normalized_itemtype' class='search-form-container' method='get' action='" . $p['target'] . "'>";
        } else {
            $main_block_class = "sub_criteria";
            $card_class = 'border d-inline-block ms-1';
        }
        $display = $_SESSION['glpifold_search'] ? 'style="display: none;"' : '';
        echo "<div class='$card_class' $display>";

        echo "<div id='searchcriteria$rand_criteria' class='$main_block_class' >";
        $nbsearchcountvar      = 'nbcriteria' . $normalized_itemtype . mt_rand();
        $searchcriteriatableid = 'criteriatable' . $normalized_itemtype . mt_rand();
        // init criteria count
        echo \Html::scriptBlock("
         var $nbsearchcountvar = " . count($p['criteria']) . ";
      ");

        echo "<div class='list-group list-group-flush list-group-hoverable criteria-list pt-2' id='$searchcriteriatableid'>";

        // Display normal search parameters
        $i = 0;
        foreach (array_keys($p['criteria']) as $i) {
            self::displayCriteria([
                'itemtype' => $itemtype,
                'num'      => $i,
                'p'        => $p
            ]);
        }

        echo "<a id='more-criteria$rand_criteria' role='button'
            class='normalcriteria fold-search list-group-item p-2 border-0'
            style='display: none;'></a>";

        echo "</div>"; // .list

        // Keep track of the current savedsearches on reload
        if (isset($_GET['savedsearches_id'])) {
            echo \Html::input("savedsearches_id", [
                'type' => "hidden",
                'value' => $_GET['savedsearches_id'],
            ]);
        }

        echo "<div class='card-footer d-flex search_actions'>";
        $linked = SearchEngine::getMetaItemtypeAvailable($itemtype);
        echo "<button id='addsearchcriteria$rand_criteria' class='btn btn-sm btn-outline-secondary me-1' type='button'>
               <i class='ti ti-square-plus'></i>
               <span class='d-none d-sm-block'>" . __s('rule') . "</span>
            </button>";
        if (count($linked)) {
            echo "<button id='addmetasearchcriteria$rand_criteria' class='btn btn-sm btn-outline-secondary me-1' type='button'>
                  <i class='ti ti-circle-plus'></i>
                  <span class='d-none d-sm-block'>" . __s('global rule') . "</span>
               </button>";
        }
        echo "<button id='addcriteriagroup$rand_criteria' class='btn btn-sm btn-outline-secondary me-1' type='button'>
               <i class='ti ti-code-plus'></i>
               <span class='d-none d-sm-block'>" . __s('group') . "</span>
            </button>";
        $json_p = json_encode($p);

        if ($p['mainform']) {
            // Display submit button
            echo "<button class='btn btn-sm btn-primary me-1' type='submit' name='" . $p['actionname'] . "'>
               <i class='ti ti-list-search'></i>
               <span class='d-none d-sm-block'>" . $p['actionvalue'] . "</span>
            </button>";
            if ($p['showbookmark'] || $p['showreset']) {
                if ($p['showbookmark']) {
                    \SavedSearch::showSaveButton(
                        \SavedSearch::SEARCH,
                        $itemtype,
                        isset($_GET['savedsearches_id'])
                    );
                }

                if ($p['showreset']) {
                    echo "<a class='btn btn-ghost-secondary btn-icon btn-sm me-1 search-reset'
                        data-bs-toggle='tooltip' data-bs-placement='bottom'
                        href='"
                        . $p['target']
                        . (strpos($p['target'], '?') ? '&amp;' : '?')
                        . "reset=reset' title=\"" . __s('Blank') . "\"
                  ><i class='ti ti-circle-x'></i></a>";
                }
            }
        }
        echo "</div>"; //.search_actions

        // idor checks
        $idor_display_criteria       = \Session::getNewIDORToken($itemtype);
        $idor_display_meta_criteria  = \Session::getNewIDORToken($itemtype);
        $idor_display_criteria_group = \Session::getNewIDORToken($itemtype);

        $itemtype_escaped = addslashes($itemtype);
        $JS = <<<JAVASCRIPT
         $('#addsearchcriteria$rand_criteria').on('click', function(event) {
            event.preventDefault();
            $.post('{$CFG_GLPI['root_doc']}/ajax/search.php', {
               'action': 'display_criteria',
               'itemtype': '$itemtype_escaped',
               'num': $nbsearchcountvar,
               'p': $json_p,
               '_idor_token': '$idor_display_criteria'
            })
            .done(function(data) {
               $(data).insertBefore('#more-criteria$rand_criteria');
               $nbsearchcountvar++;
            });
         });

         $('#addmetasearchcriteria$rand_criteria').on('click', function(event) {
            event.preventDefault();
            $.post('{$CFG_GLPI['root_doc']}/ajax/search.php', {
               'action': 'display_meta_criteria',
               'itemtype': '$itemtype_escaped',
               'meta': true,
               'num': $nbsearchcountvar,
               'p': $json_p,
               '_idor_token': '$idor_display_meta_criteria'
            })
            .done(function(data) {
               $(data).insertBefore('#more-criteria$rand_criteria');
               $nbsearchcountvar++;
            });
         });

         $('#addcriteriagroup$rand_criteria').on('click', function(event) {
            event.preventDefault();
            $.post('{$CFG_GLPI['root_doc']}/ajax/search.php', {
               'action': 'display_criteria_group',
               'itemtype': '$itemtype_escaped',
               'meta': true,
               'num': $nbsearchcountvar,
               'p': $json_p,
               '_idor_token': '$idor_display_criteria_group'
            })
            .done(function(data) {
               $(data).insertBefore('#more-criteria$rand_criteria');
               $nbsearchcountvar++;
            });
         });
JAVASCRIPT;

        if ($p['mainform']) {
            $JS .= <<<JAVASCRIPT
         var toggle_fold_search = function(show_search) {
            $('#searchcriteria{$rand_criteria}').closest('.search-form').toggle(show_search);
         };

         // Init search_criteria state
         var search_criteria_visibility = window.localStorage.getItem('show_full_searchcriteria');
         if (search_criteria_visibility !== undefined && search_criteria_visibility == 'false') {
            $('.fold-search').click();
         }

         $(document).on("click", ".remove-search-criteria", function() {
            // force removal of tooltip
            var tooltip = bootstrap.Tooltip.getInstance($(this)[0]);
            if (tooltip !== null) {
               tooltip.dispose();
            }

            var rowID = $(this).data('rowid');
            $('#' + rowID).remove();
            $('#searchcriteria{$rand_criteria} .criteria-list .list-group-item:first-child').addClass('headerRow').show();
         });
JAVASCRIPT;
        }
        echo \Html::scriptBlock($JS);

        if (count($p['addhidden'])) {
            foreach ($p['addhidden'] as $key => $val) {
                echo \Html::hidden($key, ['value' => $val]);
            }
        }

        if ($p['mainform']) {
            // For dropdown
            echo \Html::hidden('itemtype', ['value' => $itemtype]);
            // Reset to start when submit new search
            echo \Html::hidden('start', ['value'    => 0]);
        }

        echo "</div>"; // #searchcriteria
        echo "</div>"; // .card
        if ($p['mainform']) {
            \Html::closeForm();
        }
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
        $prefix = isset($p['prefix_crit']) ? $p['prefix_crit'] : '';

        if (!is_subclass_of($request['itemtype'], 'CommonDBTM')) {
            throw new \RuntimeException('Invalid itemtype provided!');
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

        $rands = -1;
        $normalized_itemtype = strtolower(str_replace('\\', '', $request["itemtype"]));
        $dropdownname = \Html::cleanId("spansearchtype$fieldname" .
            $normalized_itemtype .
            $prefix .
            $num);
        $searchopt = [];
        if (count($actions) > 0) {
            // get already get search options
            if (isset($actions['searchopt'])) {
                $searchopt = $actions['searchopt'];
                // No name for clean array with quotes
                unset($searchopt['name']);
                unset($actions['searchopt']);
            }
            $searchtype_name = "{$fieldname}{$prefix}[$num][searchtype]";
            echo "<div class='col-auto'>";
            $rands = \Dropdown::showFromArray($searchtype_name, $actions, [
                'value' => $request["searchtype"],
            ]);
            echo "</div>";
            $fieldsearch_id = \Html::cleanId("dropdown_$searchtype_name$rands");
        }

        echo "<div class='col-auto' id='$dropdownname' data-itemtype='{$request["itemtype"]}' data-fieldname='$fieldname' data-prefix='$prefix' data-num='$num'>";
        $params = [
            'value'       => rawurlencode(Sanitizer::dbUnescape($request['value'])),
            'searchopt'   => $searchopt,
            'searchtype'  => $request["searchtype"],
            'num'         => $num,
            'itemtype'    => $request["itemtype"],
            '_idor_token' => \Session::getNewIDORToken($request["itemtype"]),
            'from_meta'   => isset($request['from_meta'])
                ? $request['from_meta']
                : false,
            'field'       => $request["field"],
            'p'           => $p,
        ];
        self::displaySearchoptionValue($params);
        echo "</div>";

        \Ajax::updateItemOnSelectEvent(
            $fieldsearch_id,
            $dropdownname,
            $CFG_GLPI["root_doc"] . "/ajax/search.php",
            [
                'action'     => 'display_searchoption_value',
                'searchtype' => '__VALUE__',
            ] + $params
        );
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
        $prefix            = $p['prefix_crit'] ?? '';
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
                if (!$display && isset($searchopt['field'])) {
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
                            $options2['right']            = (isset($searchopt['right']) ? $searchopt['right'] : 'all');
                            $options2['inactive_deleted'] = 1;
                            $searchopt['toadd'] = [
                                [
                                    'id'    => 'myself',
                                    'text'  => __('Myself'),
                                ]
                            ];

                            break;
                    }

                    // Standard datatype usage
                    if (!$display && isset($searchopt['datatype'])) {
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
                        $display = \Plugin::doOneHook(
                            $plug['plugin'],
                            'searchOptionsValues',
                            [
                                'name'           => $inputname,
                                'searchtype'     => $request['searchtype'],
                                'searchoption'   => $searchopt,
                                'value'          => $request['value']
                            ]
                        );
                    }
                }
                break;
            case 'empty':
                echo "<input type='hidden' name='$inputname' value='null'>";
                $display = true;
                break;
        }

        // Default case : text field
        if (!$display) {
            echo "<input type='text' class='form-control' size='13' name='$inputname' value=\"" .
                \Html::cleanInputText($request['value']) . "\">";
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
        $options     = \Search::getCleanedOptions($request["itemtype"]);
        $randrow     = mt_rand();
        $normalized_itemtype = strtolower(str_replace('\\', '', $request["itemtype"]));
        $rowid       = 'searchrow' . $normalized_itemtype . $randrow;
        $addclass    = $num == 0 ? ' headerRow' : '';
        $prefix      = isset($p['prefix_crit']) ? $p['prefix_crit'] : '';
        $parents_num = isset($p['parents_num']) ? $p['parents_num'] : [];
        $criteria    = [];
        $from_meta   = isset($request['from_meta']) && $request['from_meta'];

        $sess_itemtype = $request["itemtype"];
        if ($from_meta) {
            $sess_itemtype = $request["parent_itemtype"];
        }

        if (!$criteria = self::findCriteriaInSession($sess_itemtype, $num, $parents_num)) {
            $criteria = self::getDefaultCriteria($request["itemtype"]);
        }

        if (
            isset($criteria['meta'])
            && $criteria['meta']
            && !$from_meta
        ) {
            self::displayMetaCriteria($request);
            return;
        }

        if (
            isset($criteria['criteria'])
            && is_array($criteria['criteria'])
        ) {
            self::displayCriteriaGroup($request);
            return;
        }

        $add_padding = "p-2";
        if (isset($request["from_meta"])) {
            $add_padding = "p-0";
        }

        echo "<div class='list-group-item $add_padding border-0 normalcriteria$addclass' id='$rowid'>";
        echo "<div class='row g-1'>";

        if (!$from_meta) {
            // First line display add / delete images for normal and meta search items
            if (
                $num == 0
                && isset($p['mainform'])
                && $p['mainform']
            ) {
                // Instanciate an object to access method
                $item = null;
                if ($request["itemtype"] != \AllAssets::getType()) {
                    $item = getItemForItemtype($request["itemtype"]);
                }
                if ($item && $item->maybeDeleted()) {
                    echo \Html::hidden('is_deleted', [
                        'value' => $p['is_deleted'],
                        'id'    => 'is_deleted'
                    ]);
                }
                echo \Html::hidden('as_map', [
                    'value' => $p['as_map'],
                    'id'    => 'as_map'
                ]);
                echo \Html::hidden('browse', [
                    'value' => $p['browse'],
                    'id'    => 'browse'
                ]);
                echo \Html::hidden('unpublished', [
                    'value' => $p['unpublished'],
                    'id'    => 'unpublished'
                ]);
            }
            echo "<div class='col-auto'>";
            echo "<button class='btn btn-sm btn-icon btn-ghost-secondary remove-search-criteria' type='button' data-rowid='$rowid'
                       data-bs-toggle='tooltip' data-bs-placement='left'
                       title=\"" . __s('Delete a rule') . "\">
            <i class='ti ti-square-minus' alt='-'></i>
         </button>";
            echo "</div>";
        }

        // Display link item
        $value = '';
        if (!$from_meta) {
            echo "<div class='col-auto'>";
            if (isset($criteria["link"])) {
                $value = $criteria["link"];
            }
            $operators = SearchEngine::getLogicalOperators(($num == 0));
            \Dropdown::showFromArray("criteria{$prefix}[$num][link]", $operators, [
                'value' => $value,
            ]);
            echo "</div>";
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
            } else if (count($val) == 1) {
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

        echo "<div class='col-auto'>";
        $rand = \Dropdown::showFromArray("criteria{$prefix}[$num][field]", $values, [
            'value' => $value,
        ]);
        echo "</div>";
        $field_id = \Html::cleanId("dropdown_criteria{$prefix}[$num][field]$rand");
        $spanid   = \Html::cleanId('SearchSpan' . $normalized_itemtype . $prefix . $num);

        echo "<div class='col-auto'>";
        echo "<div class='row g-1' id='$spanid'>";

        $used_itemtype = $request["itemtype"];
        // Force Computer itemtype for AllAssets to permit to show specific items
        if ($request["itemtype"] == \AllAssets::getType()) {
            $used_itemtype = 'Computer';
        }

        $searchtype = isset($criteria['searchtype'])
            ? $criteria['searchtype']
            : "";
        $p_value    = isset($criteria['value'])
            ? Sanitizer::dbUnescape($criteria['value'])
            : "";

        $params = [
            'itemtype'    => $used_itemtype,
            '_idor_token' => \Session::getNewIDORToken($used_itemtype),
            'field'       => $value,
            'searchtype'  => $searchtype,
            'value'       => $p_value,
            'num'         => $num,
            'p'           => $p,
        ];
        self::displaySearchoption($params);
        echo "</div>";

        \Ajax::updateItemOnSelectEvent(
            $field_id,
            $spanid,
            $CFG_GLPI["root_doc"] . "/ajax/search.php",
            [
                'action'     => 'display_searchoption',
                'field'      => '__VALUE__',
            ] + $params
        );
        echo "</div>"; //.row
        echo "</div>"; //#$spanid
        echo "</div>";
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
        global $CFG_GLPI;

        if (
            !isset($request["itemtype"])
            || !isset($request["num"])
        ) {
            return;
        }

        $p            = $request['p'];
        $num          = (int) $request['num'];
        $prefix       = isset($p['prefix_crit']) ? $p['prefix_crit'] : '';
        $parents_num  = isset($p['parents_num']) ? $p['parents_num'] : [];
        $itemtype     = $request["itemtype"];
        $metacriteria = [];

        if (!$metacriteria = self::findCriteriaInSession($itemtype, $num, $parents_num)) {
            $metacriteria = [];
            // Set default field
            $options  = \Search::getCleanedOptions($itemtype);

            foreach ($options as $key => $val) {
                if (is_array($val) && isset($val['table'])) {
                    $metacriteria['field'] = $key;
                    break;
                }
            }
        }

        $linked =  SearchEngine::getMetaItemtypeAvailable($itemtype);
        $rand   = mt_rand();

        $rowid  = 'metasearchrow' . $request['itemtype'] . $rand;

        echo "<div class='list-group-item border-0 metacriteria p-2' id='$rowid'>";
        echo "<div class='row g-1'>";

        echo "<div class='col-auto'>";
        echo "<button class='btn btn-sm btn-icon btn-ghost-secondary remove-search-criteria' type='button' data-rowid='$rowid'>
         <i class='ti ti-square-minus' alt='-' title=\"" .
            __s('Delete a global rule') . "\"></i>
      </button>";
        echo "</div>";

        // Display link item (not for the first item)
        echo "<div class='col-auto'>";
        \Dropdown::showFromArray(
            "criteria{$prefix}[$num][link]",
            SearchEngine::getLogicalOperators(),
            [
                'value' => isset($metacriteria["link"])
                    ? $metacriteria["link"]
                    : "",
            ]
        );
        echo "</div>";

        // Display select of the linked item type available
        echo "<div class='col-auto'>";
        $rand = \Dropdown::showItemTypes("criteria{$prefix}[$num][itemtype]", $linked, [
            'value' => isset($metacriteria['itemtype'])
            && !empty($metacriteria['itemtype'])
                ? $metacriteria['itemtype']
                : "",
        ]);
        echo "</div>";
        echo \Html::hidden("criteria{$prefix}[$num][meta]", [
            'value' => true
        ]);
        $field_id = \Html::cleanId("dropdown_criteria{$prefix}[$num][itemtype]$rand");
        $spanid   = \Html::cleanId("show_" . $request["itemtype"] . "_" . $prefix . $num . "_$rand");
        // Ajax script for display search met& item

        $params = [
            'action'          => 'display_criteria',
            'itemtype'        => '__VALUE__',
            'parent_itemtype' => $request['itemtype'],
            'from_meta'       => true,
            'num'             => $num,
            'p'               => $request["p"],
            '_idor_token'     => \Session::getNewIDORToken("", [
                'parent_itemtype' => $request['itemtype']
            ])
        ];
        \Ajax::updateItemOnSelectEvent(
            $field_id,
            $spanid,
            $CFG_GLPI["root_doc"] . "/ajax/search.php",
            $params
        );

        echo "<div class='col-auto' id='$spanid'>";
        echo "<div class=row'>";
        if (
            isset($metacriteria['itemtype'])
            && !empty($metacriteria['itemtype'])
        ) {
            $params['itemtype'] = $metacriteria['itemtype'];
            self::displayCriteria($params);
        }
        echo "</div>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
    }

    /**
     * Display a group of nested criteria.
     * A group (parent) criteria  can contains children criteria (who also cantains children, etc)
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
        $rowid       = 'searchrow' . $request['itemtype'] . $randrow;
        $prefix      = isset($p['prefix_crit']) ? $p['prefix_crit'] : '';
        $parents_num = isset($p['parents_num']) ? $p['parents_num'] : [];

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
        ]);
    }

    /**
     * Completion of the URL $_GET values with the $_SESSION values or define default values
     *
     * @param class-string<\CommonDBTM>  $itemtype Item type to manage
     * @param array   $params          Params to parse
     * @param boolean $usesession      Use data saved in the session (true by default)
     * @param boolean $forcebookmark   Force trying to load parameters from default bookmark:
     *                                  used for global search (false by default)
     *
     * @return array parsed params
     **/
    public static function manageParams($itemtype, $params = [], $usesession = true, $forcebookmark = false): array
    {
        $default_values = [];

        $default_values["start"]       = 0;
        $default_values["order"]       = "ASC";
        $default_values["sort"]        = 1;
        $default_values["is_deleted"]  = 0;
        $default_values["as_map"]      = 0;
        $default_values["browse"]      = $itemtype::$browse_default ?? 0;
        $default_values["unpublished"] = 1;

        if (isset($params['start'])) {
            $params['start'] = (int)$params['start'];
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

        if ($itemtype != \AllAssets::getType() && class_exists($itemtype)) {
            // retrieve default values for current itemtype
            $itemtype_default_values = [];
            if (method_exists($itemtype, 'getDefaultSearchRequest')) {
                $itemtype_default_values = call_user_func([$itemtype, 'getDefaultSearchRequest']);
            }

            // retrieve default values for the current user
            $user_default_values = \SavedSearch_User::getDefault(\Session::getLoginUserID(), $itemtype);
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
            $user_default_values = \SavedSearch_User::getDefault(\Session::getLoginUserID(), $itemtype);
            if ($user_default_values) {
                $_SESSION['glpisearch'][$itemtype] = [];
                // Only get data for bookmarks
                if ($forcebookmark) {
                    $params = $user_default_values;
                } else {
                    $bookmark = new \SavedSearch();
                    $bookmark->load($user_default_values['savedsearches_id'], false);
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
        }

        if (
            isset($params) && is_array($params)
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

        return $params;
    }

    /**
     * construct the default criteria for an itemtype
     *
     * @param class-string<\CommonDBTM> $itemtype
     *
     * @return array Criteria
     */
    private static function getDefaultCriteria($itemtype = ''): array
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
                'field' => $field,
                'link'  => 'contains',
                'value' => ''
            ]
        ];
    }

    /**
     * Retrieve a single criteria in Session by its index
     *
     * @param  class-string<\CommonDBTM>  $itemtype    which glpi type we must search in session
     * @param  integer $num         index of the criteria
     * @param  array   $parents_num node indexes of the parents (@see displayCriteriaGroup)
     *
     * @return array|false The found criteria array or false if nothing found
     */
    private static function findCriteriaInSession($itemtype = '', $num = 0, $parents_num = [])
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
}
