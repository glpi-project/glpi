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

use Glpi\DBAL\QueryExpression;
use Glpi\Search\Input\QueryBuilder;
use Glpi\Search\Output\ExportSearchOutput;
use Glpi\Search\Output\HTMLSearchOutput;
use Glpi\Search\Output\MapSearchOutput;
use Glpi\Search\Output\NamesListSearchOutput;
use Glpi\Search\Provider\SQLProvider;
use Glpi\Search\SearchEngine;
use Glpi\Search\SearchOption;

/**
 * Search Class
 *
 * Generic class for Search Engine
 * <hr>
 * **Many of the methods in this class are now stubs. You should use the classes in the Glpi\Search namespace instead.**
 **/
class Search
{
    /**
     * Default number of items displayed in global search
     * @var int
     * @see GLOBAL_SEARCH
     */
    public const GLOBAL_DISPLAY_COUNT = 10;

    // EXPORT TYPE
    /**
     * The global search view (Search across many item types).
     * This is NOT the same as the AllAssets view which is just a special itemtype.
     * @var int
     */
    public const GLOBAL_SEARCH        = -1;

    /**
     * The standard view.
     * This includes the following sub-views:
     * - Table/List
     * - Map
     * - Browse
     * @var int
     */
    public const HTML_OUTPUT          = 0;

    /**
     * PDF export format (Landscape mode)
     * @var int
     */
    public const PDF_OUTPUT_LANDSCAPE = 2;

    /**
     * CSV export format
     * @var int
     */
    public const CSV_OUTPUT           = 3;

    /**
     * PDF export format (Portrait mode)
     * @var int
     */
    public const PDF_OUTPUT_PORTRAIT  = 4;

    /**
     * Names list export format
     * @var int
     */
    public const NAMES_OUTPUT         = 5;

    /**
     * ODS export
     * @var int
     */
    public const ODS_OUTPUT = 6;

    /**
     * XLSX export
     * @var int
     */
    public const XLSX_OUTPUT = 7;

    /**
     * Placeholder for a <br> line break
     * @var string
     */
    public const LBBR = '#LBBR#';

    /**
     * Placeholder for a <hr> line break
     * @var string
     */
    public const LBHR = '#LBHR#';

    /**
     * Separator used to separate values of a same element in CONCAT MySQL function.
     *
     * @var string
     * @see LONGSEP
     */
    public const SHORTSEP = '$#$';

    /**
     * Separator used to separate each element in GROUP_CONCAT MySQL function.
     *
     * @var string
     * @see SHORTSEP
     */
    public const LONGSEP  = '$$##$$';

    /**
     * Placeholder for a null value
     * @var string
     */
    public const NULLVALUE = '__NULL__';

    /**
     * The output format for the search results
     * @var int
     */
    public static $output_type = self::HTML_OUTPUT;
    public static $search = [];

    /**
     * Display search engine for an type
     *
     * @param string  $itemtype Item type to manage
     *
     * @return void
     **/
    public static function show($itemtype)
    {
        SearchEngine::show($itemtype, []);
    }


    /**
     * Display result table for search engine for an type
     *
     * @param class-string<CommonDBTM> $itemtype Item type to manage
     * @param array  $params       Search params passed to
     *                             prepareDatasForSearch function
     * @param array  $forcedisplay Array of columns to display (default empty
     *                             = use display pref and search criteria)
     *
     * @return void
     **/
    public static function showList(
        $itemtype,
        $params,
        array $forcedisplay = []
    ) {
        SearchEngine::showOutput($itemtype, $params, $forcedisplay);
    }

    /**
     * Display result table for search engine for an type as a map
     *
     * @param class-string<CommonDBTM> $itemtype Item type to manage
     * @param array  $params   Search params passed to prepareDatasForSearch function
     *
     * @return void
     **/
    public static function showMap($itemtype, $params)
    {
        $params = MapSearchOutput::prepareInputParams($itemtype, $params);

        $data = SearchEngine::getData($itemtype, $params);
        $data['as_map'] = true;
        Search::displayData($data);
    }


    /**
     * Get data based on search parameters
     *
     * @since 0.85
     *
     * @param class-string<CommonDBTM> $itemtype Item type to manage
     * @param array  $params        Search params passed to prepareDatasForSearch function
     * @param array  $forcedisplay  Array of columns to display (default empty = empty use display pref and search criteria)
     *
     * @return array The data
     **/
    public static function getDatas($itemtype, $params, array $forcedisplay = [])
    {
        return SearchEngine::getData($itemtype, $params, $forcedisplay);
    }


    /**
     * Prepare search criteria to be used for a search
     *
     * @since 0.85
     *
     * @param class-string<CommonDBTM> $itemtype Item type
     * @param array  $params        Array of parameters
     *                               may include sort, order, start, list_limit, deleted, criteria, metacriteria
     * @param array  $forcedisplay  Array of columns to display (default empty = empty use display pref and search criterias)
     *
     * @return array prepare to be used for a search (include criteria and others needed information)
     **/
    public static function prepareDatasForSearch($itemtype, array $params, array $forcedisplay = [])
    {
        return SearchEngine::prepareDataForSearch($itemtype, $params, $forcedisplay);
    }


    /**
     * Construct SQL request depending of search parameters
     *
     * Add to data array a field sql containing an array of requests :
     *      search : request to get items limited to wanted ones
     *      count : to count all items based on search criterias
     *                    may be an array a request : need to add counts
     *                    maybe empty : use search one to count
     *
     * @since 0.85
     *
     * @param array $data  Array of search datas prepared to generate SQL
     *
     * @return void|false May return false if the search request data is invalid
     **/
    public static function constructSQL(array &$data)
    {
        return SQLProvider::constructSQL($data);
    }

    /**
     * Construct WHERE (or HAVING) part of the sql based on passed criteria
     *
     * @since 9.4
     *
     * @param  array   $criteria  list of search criterion, we should have these keys:
     *                               - link (optionnal): AND, OR, NOT AND, NOT OR
     *                               - field: id of the searchoption
     *                               - searchtype: how to match value (contains, equals, etc)
     *                               - value
     * @param  array   $data      common array used by search engine,
     *                            contains all the search part (sql, criteria, params, itemtype etc)
     *                            TODO: should be a property of the class
     * @param  array   $searchopt Search options for the current itemtype
     * @param  boolean $is_having Do we construct sql WHERE or HAVING part
     *
     * @return string             the sql sub string
     */
    public static function constructCriteriaSQL($criteria = [], $data = [], $searchopt = [], $is_having = false)
    {
        return SQLProvider::constructCriteriaSQL($criteria, $data, $searchopt, $is_having);
    }

    /**
     * Construct additional SQL (select, joins, etc) for meta-criteria
     *
     * @since 9.4
     *
     * @param  array  $criteria             list of search criterion
     * @param  string &$SELECT              TODO: should be a class property (output parameter)
     * @param  string &$FROM                TODO: should be a class property (output parameter)
     * @param  array  &$already_link_tables TODO: should be a class property (output parameter)
     * @param  array  &$data                TODO: should be a class property (output parameter)
     *
     * @return void
     */
    public static function constructAdditionalSqlForMetacriteria(
        $criteria = [],
        &$SELECT = "",
        &$FROM = "",
        &$already_link_tables = [],
        &$data = []
    ) {
        SQLProvider::constructAdditionalSqlForMetacriteria($criteria, $SELECT, $FROM, $already_link_tables, $data);
    }


    /**
     * Retrieve datas from DB : construct data array containing columns definitions and rows datas
     *
     * add to data array a field data containing :
     *      cols : columns definition
     *      rows : rows data
     *
     * @since 0.85
     *
     * @param array   $data      array of search data prepared to get data
     * @param boolean $onlycount If we just want to count results
     *
     * @return void|false May return false if the SQL data in $data is not valid
     **/
    public static function constructData(array &$data, $onlycount = false)
    {
        return SQLProvider::constructData($data, $onlycount);
    }


    /**
     * Display datas extracted from DB
     *
     * @param array $data   Array of search datas prepared to get datas
     * @param array $params Array of parameters
     *
     * @return bool
     **/
    public static function displayData(array $data, array $params = [])
    {
        /** @var HTMLSearchOutput $output */
        $output = SearchEngine::getOutputForLegacyKey($data['display_type'], $data);
        return $output->displayData($data, $params);
    }

    /**
     * Get meta types available for search engine
     *
     * @param class-string<CommonDBTM> $itemtype Type to display the form
     *
     * @return array Array of available itemtype
     **/
    public static function getMetaItemtypeAvailable($itemtype)
    {
        return SearchEngine::getMetaItemtypeAvailable($itemtype);
    }


    /**
     * Print generic search form
     *
     * Params need to parsed before using Search::manageParams function
     *
     * @param class-string<CommonDBTM> $itemtype  Type to display the form
     * @param array  $params    Array of parameters may include sort, is_deleted, criteria, metacriteria
     *
     * @return void
     **/
    public static function showGenericSearch($itemtype, array $params)
    {
        QueryBuilder::showGenericSearch($itemtype, $params);
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
        QueryBuilder::displayCriteria($request);
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
        QueryBuilder::displayMetaCriteria($request);
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
        QueryBuilder::displayCriteriaGroup($request);
    }

    /**
     * Display first part of criteria (field + searchtype, just after link)
     * will call displaySearchoptionValue for the next part (value)
     *
     * @since 9.4
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
        QueryBuilder::displaySearchoption($request);
    }

    /**
     * Display last part of criteria (value, just after searchtype)
     * called by displaySearchoptionValue
     *
     * @since 9.4
     *
     * @param  array  $request we should have these keys of parameters:
     *                            - searchtype: (contains, equals) passed by displaySearchoption
     *
     * @return void|string
     */
    public static function displaySearchoptionValue($request = [])
    {
        return QueryBuilder::displaySearchoptionValue($request);
    }


    /**
     * Display a criteria field set, this function should be called by ajax/search.php
     *
     * @since 11.0
     *
     * @param  array  $request we should have these keys of parameters:
     *                            - itemtype: main itemtype for criteria, sub one for metacriteria
     *                            - num: index of the criteria
     *                            - p: params of showGenericSearch method
     *
     * @return void
     */
    public static function displaySortCriteria(array $request = []): void
    {
        QueryBuilder::displaySortCriteria($request);
    }


    /**
     * Generic Function to add to a HAVING clause
     *
     * @since 9.4: $num param has been dropped
     *
     * @param string  $LINK           link to use
     * @param bool    $NOT            is is a negative search ?
     * @param string  $itemtype       item type
     * @param int     $ID             ID of the item to search
     * @param string  $searchtype     search type ('contains' or 'equals')
     * @param string  $val            value search
     *
     * @return string HAVING clause sub-string (Does not include the "HAVING" keyword).
     **/
    public static function addHaving($LINK, $NOT, $itemtype, $ID, $searchtype, $val)
    {
        global $DB;
        $criteria = SQLProvider::getHavingCriteria($LINK, $NOT, $itemtype, $ID, $searchtype, $val);
        if (count($criteria) === 0) {
            // Related search option is not valid for SQL searching.
            return '';
        }
        $iterator = new DBmysqlIterator($DB);
        $iterator->buildQuery([
            'FROM' => 'table',
            'HAVING' => $criteria,
        ]);
        $sql = $iterator->getSql();
        // Remove HAVING and everything before it
        $sql = substr($sql, strpos($sql, 'HAVING ') + 6);

        $link = trim($LINK);
        if (empty($link)) {
            return " $sql";
        }
        return " $link $sql";
    }


    /**
     * Generic Function to add ORDER BY to a request
     *
     * @since 9.4: $key param has been dropped
     * @since 10.0.0: Parameters changed to allow multiple sort fields.
     *    Old functionality maintained by checking the type of the first parameter.
     *    This backwards compatibility will be removed in a later version.
     *
     * @param class-string<CommonDBTM> $itemtype The itemtype
     * @param array  $sort_fields The search options to order on. This array should contain one or more associative arrays containing:
     *    - id: The search option ID
     *    - order: The sort direction (Default: ASC). Invalid sort directions will be replaced with the default option
     *
     * @return string ORDER BY query string
     *
     **/
    public static function addOrderBy($itemtype, $sort_fields)
    {
        $order = SQLProvider::getOrderByCriteria($itemtype, $sort_fields);
        if ($order === []) {
            return '';
        }
        return (new DBmysqlIterator(null))->handleOrderClause($order);
    }


    /**
     * Generic Function to add default columns to view
     *
     * @param class-string<CommonDBTM> $itemtype  Item type
     * @param array  $params   array of parameters
     *
     * @return array Array of search option IDs to be shown in the results
     **/
    public static function addDefaultToView($itemtype, $params)
    {
        return SearchOption::getDefaultToView($itemtype, $params);
    }


    /**
     * Generic Function to add default select to a request
     *
     * @param class-string<CommonDBTM> $itemtype device type
     *
     * @return string Select string
     **/
    public static function addDefaultSelect($itemtype)
    {
        $select_criteria = SQLProvider::getDefaultSelectCriteria($itemtype);
        $select_string = '';
        foreach ($select_criteria as $criteria) {
            if (is_a($criteria, QueryExpression::class)) {
                $select_string .= $criteria->getValue() . ', ';
            } else {
                $select_string .= $criteria . ', ';
            }
        }
        return $select_string;
    }


    /**
     * Generic Function to add select to a request
     *
     * @since 9.4: $num param has been dropped
     *
     * @param string  $itemtype     item type
     * @param integer $ID           ID of the item to add
     * @param boolean $meta         boolean is a meta
     * @param string  $meta_type    meta item type
     *
     * @return string Select string
     **/
    public static function addSelect($itemtype, $ID, $meta = false, $meta_type = '')
    {
        if ($meta_type === 0) {
            $meta_type = '';
        }
        $select_criteria = SQLProvider::getSelectCriteria((string) $itemtype, (int) $ID, (bool) $meta, (string) $meta_type);
        $select_string = '';
        foreach ($select_criteria as $criteria) {
            if (is_a($criteria, QueryExpression::class)) {
                $select_string .= $criteria->getValue() . ', ';
            } else {
                $select_string .= $criteria . ', ';
            }
        }
        return $select_string;
    }


    /**
     * Generic Function to add default where to a request
     *
     * @param class-string<CommonDBTM> $itemtype device type
     *
     * @return string Where string
     **/
    public static function addDefaultWhere($itemtype)
    {
        global $DB;
        $criteria = SQLProvider::getDefaultWhereCriteria($itemtype);
        if (count($criteria) === 0) {
            return '';
        }
        $iterator = new DBmysqlIterator($DB);
        $iterator->buildQuery([
            'FROM' => 'table',
            'WHERE' => $criteria,
        ]);
        $sql = $iterator->getSql();
        // Remove WHERE and everything before it
        return substr($sql, strpos($sql, 'WHERE ') + 6);
    }

    /**
     * Generic Function to add where to a request
     *
     * @param string  $link         Link string
     * @param boolean $nott         Is it a negative search ?
     * @param class-string<CommonDBTM>  $itemtype     Item type
     * @param integer $ID           ID of the item to search
     * @param string  $searchtype   Searchtype used (equals or contains)
     * @param string  $val          Item num in the request
     * @param bool    $meta         Is a meta search (meta=2 in search.class.php) (default 0)
     *
     * @return string|false Where string or false if an error occurred or if there was no valid WHERE string that could be created.
     **/
    public static function addWhere($link, $nott, $itemtype, $ID, $searchtype, $val, $meta = false)
    {
        global $DB;
        $criteria = SQLProvider::getWhereCriteria($nott, $itemtype, $ID, $searchtype, $val, $meta);
        if (count($criteria) === 0) {
            return '';
        }
        $iterator = new DBmysqlIterator($DB);
        $iterator->buildQuery([
            'FROM' => 'table',
            'WHERE' => $criteria,
        ]);
        $sql = $iterator->getSql();
        // Remove WHERE and everything before it
        $sql = substr($sql, strpos($sql, 'WHERE ') + 6);

        $link = trim($link);
        if (empty($link)) {
            return " $sql";
        }
        return " $link $sql";
    }

    /**
     * Generic Function to add Default left join to a request
     *
     * @param class-string<CommonDBTM> $itemtype Reference item type
     * @param string $ref_table            Reference table
     * @param array &$already_link_tables  Array of tables already joined
     *
     * @return string Left join string
     **/
    public static function addDefaultJoin($itemtype, $ref_table, array &$already_link_tables)
    {
        global $DB;
        $criteria = SQLProvider::getDefaultJoinCriteria($itemtype, $ref_table, $already_link_tables);
        $iterator = new DBmysqlIterator($DB);
        $iterator->buildQuery([
            'FROM' => 'table',
        ] + $criteria);
        $sql = $iterator->getSql();
        // Remove FROM $table clause and everything before it
        $prefix = 'SELECT * FROM `table` ';
        $sql = substr($sql, strlen($prefix));
        return $sql . ' ';
    }


    /**
     * Generic Function to add left join to a request
     *
     * @param string  $itemtype             Item type
     * @param string  $ref_table            Reference table
     * @param array   $already_link_tables  Array of tables already joined
     * @param string  $new_table            New table to join
     * @param string  $linkfield            Linkfield for LeftJoin
     * @param boolean $meta                 Is it a meta item ? (default 0)
     * @param string  $meta_type            Meta item type
     * @param array   $joinparams           Array join parameters (condition / joinbefore...)
     * @param string  $field                Field to display (needed for translation join) (default '')
     *
     * @return string Left join string
     **/
    public static function addLeftJoin(
        $itemtype,
        $ref_table,
        array &$already_link_tables,
        $new_table,
        $linkfield,
        $meta = false,
        $meta_type = '',
        $joinparams = [],
        $field = ''
    ) {
        global $DB;
        $criteria = SQLProvider::getLeftJoinCriteria(
            $itemtype,
            $ref_table,
            $already_link_tables,
            $new_table,
            $linkfield,
            (bool) $meta,
            (string) $meta_type,
            $joinparams,
            $field
        );
        $iterator = new DBmysqlIterator($DB);
        $iterator->buildQuery([
            'FROM' => 'table',
        ] + $criteria);
        $sql = $iterator->getSql();
        // Remove FROM $table clause and everything before it
        $prefix = 'SELECT * FROM `table` ';
        $sql = substr($sql, strlen($prefix));
        return $sql . ' ';
    }


    /**
     * Generic Function to add left join for meta items
     *
     * @param string $from_type             Reference item type ID
     * @param string $to_type               Item type to add
     * @param array  $already_link_tables2  Array of tables already joined
     *showGenericSearch
     * @return string Meta Left join string
     **/
    public static function addMetaLeftJoin(
        $from_type,
        $to_type,
        array &$already_link_tables2,
        $joinparams = []
    ) {
        global $DB;
        $joins = SQLProvider::getMetaLeftJoinCriteria($from_type, $to_type, $already_link_tables2, $joinparams);
        $iterator = new DBmysqlIterator($DB);
        return $iterator->analyseJoins($joins) . ' ';
    }


    /**
     * Generic Function to display Items
     *
     * @since 9.4: $num param has been dropped
     *
     * @param string  $itemtype item type
     * @param integer $ID       ID of the SEARCH_OPTION item
     * @param array   $data     array retrieved data array
     *
     * @return string String to print
     **/
    public static function displayConfigItem($itemtype, $ID, $data = [])
    {
        return ExportSearchOutput::displayConfigItem($itemtype, $ID, $data);
    }


    /**
     * Generic Function to display Items
     *
     * @since 9.4: $num param has been dropped
     *
     * @param string  $itemtype        item type
     * @param integer $ID              ID of the SEARCH_OPTION item
     * @param array   $data            array containing data results
     * @param boolean $meta            is a meta item ? (default false)
     * @param array   $addobjectparams array added parameters for union search
     * @param string  $orig_itemtype   Original itemtype, used for union_search_type
     *
     * @return string String to print
     **/
    public static function giveItem(
        $itemtype,
        $ID,
        array $data,
        $meta = false,
        array $addobjectparams = [],
        $orig_itemtype = null
    ) {
        return SQLProvider::giveItem($itemtype, $ID, $data, $meta, $addobjectparams, $orig_itemtype);
    }


    /**
     * Reset save searches
     *
     * @return void
     **/
    public static function resetSaveSearch()
    {
        SearchEngine::resetSaveSearch();
    }


    /**
     * Completion of the URL $_GET values with the $_SESSION values or define default values
     *
     * @param string  $itemtype        Item type to manage
     * @param array   $params          Params to parse
     * @param boolean $usesession      Use datas save in session (true by default)
     * @param boolean $forcebookmark   Force trying to load parameters from default bookmark:
     *                                  used for global search (false by default)
     *
     * @return array parsed params
     **/
    public static function manageParams(
        $itemtype,
        $params = [],
        $usesession = true,
        $forcebookmark = false
    ) {
        return QueryBuilder::manageParams($itemtype, $params, $usesession, $forcebookmark);
    }


    /**
     * Clean search options depending of user active profile
     *
     * @param string  $itemtype     Item type to manage
     * @param integer $action       Action which is used to manupulate searchoption
     *                               (default READ)
     * @param boolean $withplugins  Get plugins options (true by default)
     *
     * @return array Clean $SEARCH_OPTION array
     **/
    public static function getCleanedOptions($itemtype, $action = READ, $withplugins = true)
    {
        return SearchOption::getCleanedOptions($itemtype, $action, $withplugins);
    }


    /**
     *
     * Get an option number in the SEARCH_OPTION array
     *
     * @param class-string<CommonDBTM> $itemtype  Item type
     * @param string $field     Name
     *
     * @return integer
     **/
    public static function getOptionNumber($itemtype, $field)
    {

        return SearchOption::getOptionNumber($itemtype, $field);
    }


    /**
     * Get the SEARCH_OPTION array
     *
     * @param string  $itemtype     Item type
     * @param boolean $withplugins  Get search options from plugins (true by default)
     *
     * @return array The array of search options for the given item type
     **/
    public static function getOptions($itemtype, $withplugins = true)
    {
        return SearchOption::getOptionsForItemtype($itemtype, $withplugins);
    }

    /**
     * Is the search item related to infocoms
     *
     * @param string  $itemtype  Item type
     * @param integer $searchID  ID of the element in $SEARCHOPTION
     *
     * @return boolean
     **/
    public static function isInfocomOption($itemtype, $searchID)
    {
        return SearchOption::isInfocomOption($itemtype, $searchID);
    }


    /**
     * @param string  $itemtype
     * @param integer $field_num
     **/
    public static function getActionsFor($itemtype, $field_num)
    {
        return SearchOption::getActionsFor($itemtype, $field_num);
    }


    /**
     * Print generic Header Column
     *
     * @param integer          $type     Display type (see Search::*_OUTPUT constants)
     * @param string           $value    Value to display. This value may contain HTML data. Non-HTML content should be escaped before calling this function.
     * @param integer          &$num     Column number
     * @param string           $linkto   Link display element (HTML specific) (default '')
     * @param boolean|integer  $issort   Is the sort column ? (default 0)
     * @param string           $order    Order type ASC or DESC (defaut '')
     * @param string           $options  Options to add (default '')
     *
     * @return string HTML to display
     **/
    public static function showHeaderItem(
        $type,
        $value,
        &$num,
        $linkto = "",
        $issort = 0,
        $order = "",
        $options = ""
    ) {
        $output = SearchEngine::getOutputForLegacyKey($type);
        if ($output instanceof HTMLSearchOutput) {
            return $output::showHeaderItem($value, $num, $linkto, $issort, $order, $options);
        }
        return '';
    }


    /**
     * Print generic normal Item Cell
     *
     * @param integer $type        Display type (see Search::*_OUTPUT constants)
     * @param string  $value       Value to display
     * @param integer &$num        Column number
     * @param integer $row         Row number
     * @param string  $extraparam  Extra parameters for display (default '')
     *
     * @return string HTML to display
     **/
    public static function showItem($type, $value, &$num, $row, $extraparam = '')
    {
        // Handle null values
        if ($value === null) {
            $value = '';
        }

        $output = SearchEngine::getOutputForLegacyKey($type);
        if ($output instanceof HTMLSearchOutput || $output instanceof NamesListSearchOutput) {
            return $output::showItem($value, $num, $row, $extraparam);
        }
        return '';
    }


    /**
     * Print generic error
     *
     * @param integer $type     Display type (see Search::*_OUTPUT constants)
     * @param string  $message  Message to display, if empty "no item found" will be displayed
     *
     * @return string HTML to display
     **/
    public static function showError($type, $message = "")
    {
        if (strlen($message) == 0) {
            $message = __('No results found');
        }

        $output = SearchEngine::getOutputForLegacyKey($type);
        if ($output instanceof HTMLSearchOutput) {
            return $output::showError($message);
        }
        return '';
    }


    /**
     * Print generic footer
     *
     * @param integer $type  Display type (see Search::*_OUTPUT constants)
     * @param string  $title title of file : used for PDF (default '')
     * @param integer $count Total number of results
     *
     * @return string HTML to display
     **/
    public static function showFooter($type, $title = "", $count = null)
    {
        $output = SearchEngine::getOutputForLegacyKey($type);
        if ($output instanceof HTMLSearchOutput) {
            return $output::showFooter($title, $count);
        }
        return '';
    }


    /**
     * Print generic footer
     *
     * @param integer         $type   Display type (see Search::*_OUTPUT constants)
     * @param integer         $rows   Number of rows
     * @param integer         $cols   Number of columns
     * @param boolean|integer $fixed  Used tab_cadre_fixe table for HTML export ? (default 0)
     *
     * @return string HTML to display
     **/
    public static function showHeader($type, $rows, $cols, $fixed = 0)
    {
        $output = SearchEngine::getOutputForLegacyKey($type);
        if (($output instanceof HTMLSearchOutput || $output instanceof NamesListSearchOutput) && !defined('TU_USER')) {
            return $output::showHeader($rows, $cols, $fixed);
        }
        return '';
    }


    /**
     * Print begin of header part
     *
     * @param integer $type   Display type (see Search::*_OUTPUT constants)
     *
     * @since 0.85
     *
     * @return string HTML to display
     **/
    public static function showBeginHeader($type)
    {
        $output = SearchEngine::getOutputForLegacyKey($type);
        if ($output instanceof HTMLSearchOutput) {
            return $output::showBeginHeader();
        }
        return '';
    }


    /**
     * Print end of header part
     *
     * @param integer $type   Display type (see Search::*_OUTPUT constants)
     *
     * @since 0.85
     *
     * @return string to display
     **/
    public static function showEndHeader($type)
    {
        $output = SearchEngine::getOutputForLegacyKey($type);
        if ($output instanceof HTMLSearchOutput) {
            return $output::showEndHeader();
        }
        return '';
    }


    /**
     * Print generic new line
     *
     * @param integer $type        Display type (see Search::*_OUTPUT constants)
     * @param boolean $odd         Is it a new odd line ? (false by default)
     * @param boolean $is_deleted  Is it a deleted search ? (false by default)
     *
     * @return string HTML to display
     **/
    public static function showNewLine($type, $odd = false, $is_deleted = false)
    {
        $output = SearchEngine::getOutputForLegacyKey($type);
        if ($output instanceof HTMLSearchOutput) {
            return $output::showNewLine($odd, $is_deleted);
        }
        return '';
    }


    /**
     * Print generic end line
     *
     * @param integer $type  Display type (see Search::*_OUTPUT constants)
     *
     * @return string HTML to display
     **/
    public static function showEndLine($type, bool $is_header_line = false)
    {
        $output = SearchEngine::getOutputForLegacyKey($type);
        if ($output instanceof HTMLSearchOutput || $output instanceof NamesListSearchOutput) {
            return $output::showEndLine();
        }
        return '';
    }

    /**
     * @param array $joinparams
     */
    public static function computeComplexJoinID(array $joinparams)
    {
        return SQLProvider::computeComplexJoinID($joinparams);
    }

    /**
     * Create SQL search condition
     *
     * @param string  $field  Nname (should be ` protected)
     * @param string  $val    Value to search
     * @param boolean $not    Is a negative search ? (false by default)
     * @param string  $link   With previous criteria (default 'AND')
     *
     * @return string Search SQL string
     **/
    public static function makeTextCriteria($field, $val, $not = false, $link = 'AND')
    {
        return SQLProvider::makeTextCriteria($field, $val, $not, $link);
    }

    /**
     * Create SQL search value
     *
     * @since 9.4
     *
     * @param string  $val value to search
     *
     * @return string|null
     **/
    public static function makeTextSearchValue($val)
    {
        return SQLProvider::makeTextSearchValue($val);
    }

    /**
     * Create SQL search condition
     *
     * @param string  $val  Value to search
     * @param boolean $not  Is a negative search ? (false by default)
     *
     * @return string Search string
     **/
    public static function makeTextSearch($val, $not = false)
    {
        return SQLProvider::makeTextSearch($val, $not);
    }

    /**
     * @since 0.84
     *
     * @param string $pattern
     * @param string $subject
     * @return string[]|false
     **/
    public static function explodeWithID($pattern, $subject)
    {
        return SQLProvider::explodeWithID($pattern, $subject);
    }

    /**
     * Add join for dropdown translations
     *
     * @param string $alias    Alias for translation table
     * @param string $table    Table to join on
     * @param class-string<CommonDBTM> $itemtype Item type
     * @param string $field    Field name
     *
     * @return string
     * @deprecated 11.0.0
     */
    public static function joinDropdownTranslations($alias, $table, $itemtype, $field)
    {
        global $DB;

        Toolbox::deprecated();

        return "LEFT JOIN " . $DB::quoteName('glpi_dropdowntranslations') . " AS " . $DB::quoteName($alias) . "
                  ON (" . $DB::quoteName($alias . '.itemtype') . " = " . $DB->quote($itemtype) . "
                    AND " . $DB::quoteName($alias . '.items_id') . " = " . $DB::quoteName($table . '.id') . "
                    AND " . $DB::quoteName($alias . '.language') . " = " . $DB->quote($_SESSION['glpilanguage']) . "
                    AND " . $DB::quoteName($alias . '.field') . " = " . $DB->quote($field) . ")";
    }

    /**
     * Get table name for item type
     *
     * @param class-string<CommonDBTM> $itemtype
     *
     * @return string
     */
    public static function getOrigTableName(string $itemtype): string
    {
        return SearchEngine::getOrigTableName($itemtype);
    }

    /**
     * Check if the given field is virtual (not mapped directly with the database schema)
     * @param string $field The field name
     * @return bool
     */
    public static function isVirtualField(string $field): bool
    {
        return str_starts_with($field, '_virtual');
    }
}
