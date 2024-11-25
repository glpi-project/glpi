<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

use Glpi\Application\View\TemplateRenderer;
use Glpi\RichText\RichText;
use Glpi\Socket;
use Glpi\Toolbox\DataExport;
use Glpi\Toolbox\Sanitizer;
use Glpi\Toolbox\URL;

/**
 * Search Class
 *
 * Generic class for Search Engine
 **/
class Search
{
    /**
     * Default number of items displayed in global search
     * @var int
     * @see GLOBAL_SEARCH
     */
    const GLOBAL_DISPLAY_COUNT = 10;

   // EXPORT TYPE
    /**
     * The global search view (Search across many item types).
     * This is NOT the same as the AllAssets view which is just a special itemtype.
     * @var int
     */
    const GLOBAL_SEARCH        = -1;

    /**
     * The standard view.
     * This includes the following sub-views:
     * - Table/List
     * - Map
     * - Browse
     * @var int
     */
    const HTML_OUTPUT          = 0;

    /**
     * SYLK export format
     * @var int
     */
    const SYLK_OUTPUT          = 1;

    /**
     * PDF export format (Landscape mode)
     * @var int
     */
    const PDF_OUTPUT_LANDSCAPE = 2;

    /**
     * CSV export format
     * @var int
     */
    const CSV_OUTPUT           = 3;

    /**
     * PDF export format (Portrait mode)
     * @var int
     */
    const PDF_OUTPUT_PORTRAIT  = 4;

    /**
     * Names list export format
     * @var int
     */
    const NAMES_OUTPUT         = 5;

    /**
     * Placeholder for a <br> line break
     * @var string
     */
    const LBBR = '#LBBR#';

    /**
     * Placeholder for a <hr> line break
     * @var string
     */
    const LBHR = '#LBHR#';

    /**
     * Separator used to separate values of a same element in CONCAT MySQL function.
     *
     * @var string
     * @see LONGSEP
     */
    const SHORTSEP = '$#$';

    /**
     * Separator used to separate each element in GROUP_CONCAT MySQL function.
     *
     * @var string
     * @see SHORTSEP
     */
    const LONGSEP  = '$$##$$';

    /**
     * Placeholder for a null value
     * @var string
     */
    const NULLVALUE = '__NULL__';

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

        $params = self::manageParams($itemtype, $_GET);
        echo "<div class='search_page row'>";
        TemplateRenderer::getInstance()->display('layout/parts/saved_searches.html.twig', [
            'itemtype' => $itemtype,
        ]);
        echo "<div class='col search-container'>";

        if (
            $itemtype == "Ticket"
            && Session::getCurrentInterface() === 'central'
            && $default = Glpi\Dashboard\Grid::getDefaultDashboardForMenu('mini_ticket', true)
        ) {
            $dashboard = new Glpi\Dashboard\Grid($default, 33, 2);
            $dashboard->show(true);
        }

        self::showGenericSearch($itemtype, $params);
        if ($params['as_map'] == 1) {
            self::showMap($itemtype, $params);
        } elseif ($params['browse'] == 1) {
            $itemtype::showBrowseView($itemtype, $params);
        } else {
            self::showList($itemtype, $params);
        }
        echo "</div>";
        echo "</div>";
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
        $data = self::getDatas($itemtype, $params, $forcedisplay);

        switch ($data['display_type']) {
            case self::CSV_OUTPUT:
            case self::PDF_OUTPUT_LANDSCAPE:
            case self::PDF_OUTPUT_PORTRAIT:
            case self::SYLK_OUTPUT:
            case self::NAMES_OUTPUT:
                self::outputData($data);
                break;
            case self::GLOBAL_SEARCH:
            case self::HTML_OUTPUT:
            default:
                self::displayData($data);
                break;
        }
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
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if ($itemtype == 'Location') {
            $latitude = 21;
            $longitude = 20;
        } else if ($itemtype == 'Entity') {
            $latitude = 67;
            $longitude = 68;
        } else {
            $latitude = 998;
            $longitude = 999;
        }

        $params['criteria'][] = [
            'link'         => 'AND NOT',
            'field'        => $latitude,
            'searchtype'   => 'contains',
            'value'        => 'NULL'
        ];
        $params['criteria'][] = [
            'link'         => 'AND NOT',
            'field'        => $longitude,
            'searchtype'   => 'contains',
            'value'        => 'NULL'
        ];

        $data = self::getDatas($itemtype, $params);
        self::displayData($data);

        if ($data['data']['totalcount'] > 0) {
            $target = URL::sanitizeURL($data['search']['target']);
            $criteria = $data['search']['criteria'];
            array_pop($criteria);
            array_pop($criteria);
            $criteria[] = [
                'link'         => 'AND',
                'field'        => ($itemtype == 'Location' || $itemtype == 'Entity') ? 1 : (($itemtype == 'Ticket') ? 83 : 3),
                'searchtype'   => 'equals',
                'value'        => 'CURLOCATION'
            ];
            $globallinkto = Toolbox::append_params(
                [
                    'criteria'     => Sanitizer::unsanitize($criteria),
                    'metacriteria' => Sanitizer::unsanitize($data['search']['metacriteria'])
                ],
                '&amp;'
            );
            $sort_params = Toolbox::append_params([
                'sort'   => $data['search']['sort'],
                'order'  => $data['search']['order']
            ], '&amp;');
            $parameters = "as_map=0&amp;" . $sort_params . '&amp;' .
                        $globallinkto;

            if (strpos($target, '?') == false) {
                $fulltarget = $target . "?" . $parameters;
            } else {
                $fulltarget = $target . "&" . $parameters;
            }
            $typename = class_exists($itemtype) ? $itemtype::getTypeName($data['data']['totalcount']) : $itemtype;

            echo "<div class='card border-top-0 rounded-0 search-as-map'>";
            echo "<div class='card-body px-0' id='map_container'>";
            echo "<small class='text-muted p-1'>" . __('Search results for localized items only') . "</small>";
            $js = "$(function() {
               var map = initMap($('#map_container'), 'map', 'full');
               _loadMap(map, '$itemtype');
            });

         var _loadMap = function(map_elt, itemtype) {
            L.AwesomeMarkers.Icon.prototype.options.prefix = 'far';
            var _micon = 'circle';

            var stdMarker = L.AwesomeMarkers.icon({
               icon: _micon,
               markerColor: 'blue'
            });

            var aMarker = L.AwesomeMarkers.icon({
               icon: _micon,
               markerColor: 'cadetblue'
            });

            var bMarker = L.AwesomeMarkers.icon({
               icon: _micon,
               markerColor: 'purple'
            });

            var cMarker = L.AwesomeMarkers.icon({
               icon: _micon,
               markerColor: 'darkpurple'
            });

            var dMarker = L.AwesomeMarkers.icon({
               icon: _micon,
               markerColor: 'red'
            });

            var eMarker = L.AwesomeMarkers.icon({
               icon: _micon,
               markerColor: 'darkred'
            });


            //retrieve geojson data
            map_elt.spin(true);
            $.ajax({
               dataType: 'json',
               method: 'POST',
               url: '{$CFG_GLPI['root_doc']}/ajax/map.php',
               data: {
                  itemtype: itemtype,
                  params: " . json_encode($params) . "
               }
            }).done(function(data) {
               var _points = data.points;
               var _markers = L.markerClusterGroup({
                  iconCreateFunction: function(cluster) {
                     var childCount = cluster.getChildCount();

                     var markers = cluster.getAllChildMarkers();
                     var n = 0;
                     for (var i = 0; i < markers.length; i++) {
                        n += markers[i].count;
                     }

                     var c = ' marker-cluster-';
                     if (n < 10) {
                        c += 'small';
                     } else if (n < 100) {
                        c += 'medium';
                     } else {
                        c += 'large';
                     }

                     return new L.DivIcon({ html: '<div><span>' + n + '</span></div>', className: 'marker-cluster' + c, iconSize: new L.Point(40, 40) });
                  }
               });

               $.each(_points, function(index, point) {
                  var _title = '<strong>' + point.title + '</strong><br/><a href=\''+'$fulltarget'.replace(/CURLOCATION/, point.loc_id)+'\'>" . sprintf(__('%1$s %2$s'), 'COUNT', $typename) . "'.replace(/COUNT/, point.count)+'</a>';
                  if (point.types) {
                     $.each(point.types, function(tindex, type) {
                        _title += '<br/>" . sprintf(__('%1$s %2$s'), 'COUNT', 'TYPE') . "'.replace(/COUNT/, type.count).replace(/TYPE/, type.name);
                     });
                  }
                  var _icon = stdMarker;
                  if (point.count < 10) {
                     _icon = stdMarker;
                  } else if (point.count < 100) {
                     _icon = aMarker;
                  } else if (point.count < 1000) {
                     _icon = bMarker;
                  } else if (point.count < 5000) {
                     _icon = cMarker;
                  } else if (point.count < 10000) {
                     _icon = dMarker;
                  } else {
                     _icon = eMarker;
                  }
                  var _marker = L.marker([point.lat, point.lng], { icon: _icon, title: point.title });
                  _marker.count = point.count;
                  _marker.bindPopup(_title);
                  _markers.addLayer(_marker);
               });

               map_elt.addLayer(_markers);
               map_elt.fitBounds(
                  _markers.getBounds(), {
                     padding: [50, 50],
                     maxZoom: 12
                  }
               );
            }).fail(function (response) {
               var _data = response.responseJSON;
               var _message = '" . __s('An error occurred loading data :(') . "';
               if (_data.message) {
                  _message = _data.message;
               }
               var fail_info = L.control();
               fail_info.onAdd = function (map) {
                  this._div = L.DomUtil.create('div', 'fail_info');
                  this._div.innerHTML = _message + '<br/><span id=\'reload_data\'><i class=\'fa fa-sync\'></i> " . __s('Reload') . "</span>';
                  return this._div;
               };
               fail_info.addTo(map_elt);
               $('#reload_data').on('click', function() {
                  $('.fail_info').remove();
                  _loadMap(map_elt);
               });
            }).always(function() {
               //hide spinner
               map_elt.spin(false);
            });
         }

         ";
            echo Html::scriptBlock($js);
            echo "</div>"; // .card-body
            echo "</div>"; // .card
        }
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

        $data = self::prepareDatasForSearch($itemtype, $params, $forcedisplay);
        self::constructSQL($data);
        self::constructData($data);

        return $data;
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
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

       // Default values of parameters
        $p['criteria']            = [];
        $p['metacriteria']        = [];
        $p['sort']                = ['1'];
        $p['order']               = ['ASC'];
        $p['start']               = 0;//
        $p['is_deleted']          = 0;
        $p['export_all']          = 0;
        if (class_exists($itemtype)) {
            $p['target']       = $itemtype::getSearchURL();
        } else {
            $p['target']       = Toolbox::getItemTypeSearchURL($itemtype);
        }
        $p['display_type']        = self::HTML_OUTPUT;
        $p['showmassiveactions']  = true;
        $p['dont_flush']          = false;
        $p['show_pager']          = true;
        $p['show_footer']         = true;
        $p['no_sort']             = false;
        $p['list_limit']          = $_SESSION['glpilist_limit'];
        $p['massiveactionparams'] = [];

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
            if ($p['display_type'] == self::GLOBAL_SEARCH) {
                $p['list_limit'] = self::GLOBAL_DISPLAY_COUNT;
            }
        }

        if ($p['export_all']) {
            $p['start'] = 0;
        }

        $p = self::cleanParams($p);

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
                    Html::displayRightError();
                }
            }
        }
        if (!$CFG_GLPI['allow_search_view'] && !array_key_exists('globalsearch', $p)) {
            foreach ($p['criteria'] as $val) {
                if (isset($val['field']) && $val['field'] == 'view') {
                    Html::displayRightError();
                }
            }
        }

       /// Get the items to display
       // Add searched items

        $forcetoview = false;
        if (is_array($forcedisplay) && count($forcedisplay)) {
            $forcetoview = true;
        }
        $data['search']['all_search']  = false;
        $data['search']['view_search'] = false;
       // If no research limit research to display item and compute number of item using simple request
        $data['search']['no_search']   = true;

        $data['toview'] = self::addDefaultToView($itemtype, $params);
        $data['meta_toview'] = [];
        if (!$forcetoview) {
           // Add items to display depending of personal prefs
            $displaypref = DisplayPreference::getForTypeUser($itemtype, Session::getLoginUserID());
            if (count($displaypref)) {
                foreach ($displaypref as $val) {
                    array_push($data['toview'], $val);
                }
            }
        } else {
            $data['toview'] = array_merge($data['toview'], $forcedisplay);
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
                                array_push($data['toview'], $criterion['field']);
                            } else if ($criterion['field'] == 'all') {
                                $data['search']['all_search'] = true;
                            } else if ($criterion['field'] == 'view') {
                                $data['search']['view_search'] = true;
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
            array_unshift($data['toview'], 2);
        }

        $limitsearchopt   = self::getCleanedOptions($itemtype);
       // Clean and reorder toview
        $tmpview = [];
        foreach ($data['toview'] as $val) {
            if (isset($limitsearchopt[$val]) && !in_array($val, $tmpview)) {
                $tmpview[] = $val;
            }
        }
        $data['toview']    = $tmpview;
        $data['tocompute'] = $data['toview'];

       // Force item to display
        if ($forcetoview) {
            foreach ($data['toview'] as $val) {
                if (!in_array($val, $data['tocompute'])) {
                    array_push($data['tocompute'], $val);
                }
            }
        }

        return $data;
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
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        if (!isset($data['itemtype'])) {
            return false;
        }

        $data['sql']['count']  = [];
        $data['sql']['search'] = '';
        $data['sql']['raw']    = [];

        $searchopt        = self::getOptions($data['itemtype']);

        $blacklist_tables = [];
        $orig_table = self::getOrigTableName($data['itemtype']);
        if (isset($CFG_GLPI['union_search_type'][$data['itemtype']])) {
            $itemtable          = $CFG_GLPI['union_search_type'][$data['itemtype']];
            $blacklist_tables[] = $orig_table;
        } else {
            $itemtable = $orig_table;
        }

       // hack for AllAssets and ReservationItem
        if (isset($CFG_GLPI['union_search_type'][$data['itemtype']])) {
            $entity_restrict = true;
        } else {
            $entity_restrict = $data['item']->isEntityAssign() && $data['item']->isField('entities_id');
        }

       // Construct the request

       //// 1 - SELECT
       // request currentuser for SQL supervision, not displayed
        $SELECT = "SELECT DISTINCT `$itemtable`.`id` AS id, '" . Toolbox::addslashes_deep($_SESSION['glpiname']) . "' AS currentuser,
                        " . self::addDefaultSelect($data['itemtype']);

       // Add select for all toview item
        foreach ($data['toview'] as $val) {
            $SELECT .= self::addSelect($data['itemtype'], $val);
        }

        if (isset($data['search']['as_map']) && $data['search']['as_map'] == 1 && $data['itemtype'] != 'Entity') {
            $SELECT .= ' `glpi_locations`.`id` AS loc_id, ';
        }

       //// 2 - FROM AND LEFT JOIN
       // Set reference table
        $FROM = " FROM `$itemtable`";

       // Init already linked tables array in order not to link a table several times
        $already_link_tables = [];
       // Put reference table
        array_push($already_link_tables, $itemtable);

       // Add default join
        $COMMONLEFTJOIN = self::addDefaultJoin($data['itemtype'], $itemtable, $already_link_tables);
        $FROM          .= $COMMONLEFTJOIN;

       // Add all table for toview items
        foreach ($data['tocompute'] as $val) {
            if (!in_array($searchopt[$val]["table"], $blacklist_tables)) {
                $FROM .= self::addLeftJoin(
                    $data['itemtype'],
                    $itemtable,
                    $already_link_tables,
                    $searchopt[$val]["table"],
                    $searchopt[$val]["linkfield"],
                    0,
                    0,
                    $searchopt[$val]["joinparams"],
                    $searchopt[$val]["field"]
                );
            }
        }

       // Search all case :
        if ($data['search']['all_search']) {
            foreach ($searchopt as $key => $val) {
               // Do not search on Group Name
                if (is_array($val) && isset($val['table'])) {
                    if (!in_array($searchopt[$key]["table"], $blacklist_tables)) {
                        $FROM .= self::addLeftJoin(
                            $data['itemtype'],
                            $itemtable,
                            $already_link_tables,
                            $searchopt[$key]["table"],
                            $searchopt[$key]["linkfield"],
                            0,
                            0,
                            $searchopt[$key]["joinparams"],
                            $searchopt[$key]["field"]
                        );
                    }
                }
            }
        }

       //// 3 - WHERE

       // default string
        $COMMONWHERE = self::addDefaultWhere($data['itemtype']);
        $first       = empty($COMMONWHERE);

       // Add deleted if item have it
        if ($data['item'] && $data['item']->maybeDeleted()) {
            $LINK = " AND ";
            if ($first) {
                $LINK  = " ";
                $first = false;
            }
            $COMMONWHERE .= $LINK . "`$itemtable`.`is_deleted` = " . (int)$data['search']['is_deleted'] . " ";
        }

       // Remove template items
        if ($data['item'] && $data['item']->maybeTemplate()) {
            $LINK = " AND ";
            if ($first) {
                $LINK  = " ";
                $first = false;
            }
            $COMMONWHERE .= $LINK . "`$itemtable`.`is_template` = 0 ";
        }

       // Add Restrict to current entities
        if ($entity_restrict) {
            $LINK = " AND ";
            if ($first) {
                $LINK  = " ";
                $first = false;
            }

            if ($data['itemtype'] == 'Entity') {
                $COMMONWHERE .= getEntitiesRestrictRequest($LINK, $itemtable);
            } else if (isset($CFG_GLPI["union_search_type"][$data['itemtype']])) {
               // Will be replace below in Union/Recursivity Hack
                $COMMONWHERE .= $LINK . " ENTITYRESTRICT ";
            } else {
                $COMMONWHERE .= getEntitiesRestrictRequest(
                    $LINK,
                    $itemtable,
                    '',
                    '',
                    $data['item']->maybeRecursive() && $data['item']->isField('is_recursive')
                );
            }
        }
        $WHERE  = "";
        $HAVING = "";

       // Add search conditions
       // If there is search items
        if (count($data['search']['criteria'])) {
            $WHERE  = self::constructCriteriaSQL($data['search']['criteria'], $data, $searchopt);
            $HAVING = self::constructCriteriaSQL($data['search']['criteria'], $data, $searchopt, true);

           // if criteria (with meta flag) need additional join/from sql
            self::constructAdditionalSqlForMetacriteria($data['search']['criteria'], $SELECT, $FROM, $already_link_tables, $data);
        }

       //// 4 - ORDER
        $ORDER = " ORDER BY `id` ";
        $sort_fields = [];
        $sort_count = count($data['search']['sort']);
        for ($i = 0; $i < $sort_count; $i++) {
            foreach ($data['tocompute'] as $val) {
                if ($data['search']['sort'][$i] == $val) {
                    $sort_fields[] = [
                        'searchopt_id' => $data['search']['sort'][$i],
                        'order'        => $data['search']['order'][$i] ?? null
                    ];
                }
            }
        }
        if (count($sort_fields)) {
            $ORDER = self::addOrderBy($data['itemtype'], $sort_fields);
        }

        $SELECT = rtrim(trim($SELECT), ',');

       //// 7 - Manage GROUP BY
        $GROUPBY = "";
       // Meta Search / Search All / Count tickets
        $criteria_with_meta = array_filter($data['search']['criteria'], function ($criterion) {
            return isset($criterion['meta'])
                && $criterion['meta'];
        });
        if (
            (count($data['search']['metacriteria']))
            || count($criteria_with_meta)
            || !empty($HAVING)
            || $data['search']['all_search']
        ) {
            $GROUPBY = " GROUP BY `$itemtable`.`id`";
        }

        if (empty($GROUPBY)) {
            foreach ($data['toview'] as $val2) {
                if (!empty($GROUPBY)) {
                    break;
                }
                if (isset($searchopt[$val2]["forcegroupby"])) {
                    $GROUPBY = " GROUP BY `$itemtable`.`id`";
                }
            }
        }

        $LIMIT   = "";
        $numrows = 0;
       //No search : count number of items using a simple count(ID) request and LIMIT search
        if ($data['search']['no_search']) {
            $LIMIT = " LIMIT " . (int)$data['search']['start'] . ", " . (int)$data['search']['list_limit'];

            $count = "count(DISTINCT `$itemtable`.`id`)";
           // request currentuser for SQL supervision, not displayed
            $query_num = "SELECT $count,
                              '" . Toolbox::addslashes_deep($_SESSION['glpiname']) . "' AS currentuser
                       FROM `$itemtable`" .
                       $COMMONLEFTJOIN;

            $first     = true;

            if (!empty($COMMONWHERE)) {
                $LINK = " AND ";
                if ($first) {
                    $LINK  = " WHERE ";
                    $first = false;
                }
                $query_num .= $LINK . $COMMONWHERE;
            }
           // Union Search :
            if (isset($CFG_GLPI["union_search_type"][$data['itemtype']])) {
                $tmpquery = $query_num;

                foreach ($CFG_GLPI[$CFG_GLPI["union_search_type"][$data['itemtype']]] as $ctype) {
                    $ctable = $ctype::getTable();
                    if (
                        ($citem = getItemForItemtype($ctype))
                        && $citem->canView()
                    ) {
                        // State case
                        if ($data['itemtype'] == AllAssets::getType()) {
                            $query_num  = str_replace(
                                $CFG_GLPI["union_search_type"][$data['itemtype']],
                                $ctable,
                                $tmpquery
                            );
                            $query_num  = str_replace($data['itemtype'], $ctype, $query_num);
                            $query_num .= " AND `$ctable`.`id` IS NOT NULL ";

                         // Add deleted if item have it
                            if ($citem && $citem->maybeDeleted()) {
                                  $query_num .= " AND `$ctable`.`is_deleted` = 0 ";
                            }

                         // Remove template items
                            if ($citem && $citem->maybeTemplate()) {
                                $query_num .= " AND `$ctable`.`is_template` = 0 ";
                            }
                        } else {// Ref table case
                            $reftable = $data['itemtype']::getTable();
                            if ($data['item'] && $data['item']->maybeDeleted()) {
                                $tmpquery = str_replace(
                                    "`" . $CFG_GLPI["union_search_type"][$data['itemtype']] . "`.
                                                   `is_deleted`",
                                    "`$reftable`.`is_deleted`",
                                    $tmpquery
                                );
                            }
                            $replace  = "FROM `$reftable`
                                  INNER JOIN `$ctable`
                                       ON (`$reftable`.`items_id` =`$ctable`.`id`
                                           AND `$reftable`.`itemtype` = '$ctype')";

                            $query_num = str_replace(
                                "FROM `" .
                                        $CFG_GLPI["union_search_type"][$data['itemtype']] . "`",
                                $replace,
                                $tmpquery
                            );
                            $query_num = str_replace(
                                $CFG_GLPI["union_search_type"][$data['itemtype']],
                                $ctable,
                                $query_num
                            );
                        }
                        $query_num = str_replace(
                            "ENTITYRESTRICT",
                            getEntitiesRestrictRequest(
                                '',
                                $ctable,
                                '',
                                '',
                                $citem->maybeRecursive()
                            ),
                            $query_num
                        );
                         $data['sql']['count'][] = $query_num;
                    }
                }
            } else {
                $data['sql']['count'][] = $query_num;
            }
        }

       // If export_all reset LIMIT condition
        if ($data['search']['export_all']) {
            $LIMIT = "";
        }

        if (!empty($WHERE) || !empty($COMMONWHERE)) {
            if (!empty($COMMONWHERE)) {
                $WHERE = ' WHERE ' . $COMMONWHERE . (!empty($WHERE) ? ' AND ( ' . $WHERE . ' )' : '');
            } else {
                $WHERE = ' WHERE ' . $WHERE . ' ';
            }
            $first = false;
        }

        if (!empty($HAVING)) {
            $HAVING = ' HAVING ' . $HAVING;
        }

       // Create QUERY
        if (isset($CFG_GLPI["union_search_type"][$data['itemtype']])) {
            $first = true;
            $QUERY = "";
            foreach ($CFG_GLPI[$CFG_GLPI["union_search_type"][$data['itemtype']]] as $ctype) {
                $ctable = $ctype::getTable();
                if (
                    ($citem = getItemForItemtype($ctype))
                    && $citem->canView()
                ) {
                    if ($first) {
                        $first = false;
                    } else {
                        $QUERY .= " UNION ALL ";
                    }
                    $tmpquery = "";
                   // AllAssets case
                    if ($data['itemtype'] == AllAssets::getType()) {
                         $tmpquery = $SELECT . ", '$ctype' AS TYPE " .
                             $FROM .
                             $WHERE;

                         $tmpquery .= " AND `$ctable`.`id` IS NOT NULL ";

                         // Add deleted if item have it
                        if ($citem && $citem->maybeDeleted()) {
                            $tmpquery .= " AND `$ctable`.`is_deleted` = 0 ";
                        }

                       // Remove template items
                        if ($citem && $citem->maybeTemplate()) {
                            $tmpquery .= " AND `$ctable`.`is_template` = 0 ";
                        }

                        $tmpquery .= $GROUPBY .
                             $HAVING;

                      // Replace 'asset_types' by itemtype table name
                        $tmpquery = str_replace(
                            $CFG_GLPI["union_search_type"][$data['itemtype']],
                            $ctable,
                            $tmpquery
                        );
                        // Replace 'AllAssets' by itemtype
                        // Use quoted value to prevent replacement of AllAssets in column identifiers
                        $tmpquery = str_replace(
                            $DB->quoteValue(AllAssets::getType()),
                            $DB->quoteValue($ctype),
                            $tmpquery
                        );
                    } else {// Ref table case
                        $reftable = $data['itemtype']::getTable();

                        $tmpquery = $SELECT . ", '$ctype' AS TYPE,
                                      `$reftable`.`id` AS refID, " . "
                                      `$ctable`.`entities_id` AS ENTITY " .
                        $FROM .
                        $WHERE;
                        if ($data['item']->maybeDeleted()) {
                            $tmpquery = str_replace(
                                "`" . $CFG_GLPI["union_search_type"][$data['itemtype']] . "`.
                                                `is_deleted`",
                                "`$reftable`.`is_deleted`",
                                $tmpquery
                            );
                        }

                        $replace = "FROM `$reftable`" . "
                              INNER JOIN `$ctable`" . "
                                 ON (`$reftable`.`items_id`=`$ctable`.`id`" . "
                                     AND `$reftable`.`itemtype` = '$ctype')";
                        $tmpquery = str_replace(
                            "FROM `" .
                                 $CFG_GLPI["union_search_type"][$data['itemtype']] . "`",
                            $replace,
                            $tmpquery
                        );
                        $tmpquery = str_replace(
                            $CFG_GLPI["union_search_type"][$data['itemtype']],
                            $ctable,
                            $tmpquery
                        );
                        $name_field = $ctype::getNameField();
                        $tmpquery = str_replace("`$ctable`.`name`", "`$ctable`.`$name_field`", $tmpquery);
                    }
                    $tmpquery = str_replace(
                        "ENTITYRESTRICT",
                        getEntitiesRestrictRequest(
                            '',
                            $ctable,
                            '',
                            '',
                            $citem->maybeRecursive()
                        ),
                        $tmpquery
                    );

                     // SOFTWARE HACK
                    if ($ctype == 'Software') {
                        $tmpquery = str_replace("`glpi_softwares`.`serial`", "''", $tmpquery);
                        $tmpquery = str_replace("`glpi_softwares`.`otherserial`", "''", $tmpquery);
                    }
                    $QUERY .= $tmpquery;
                }
            }
            if (empty($QUERY)) {
                echo self::showError($data['display_type']);
                return;
            }
            $QUERY .= str_replace($CFG_GLPI["union_search_type"][$data['itemtype']] . ".", "", $ORDER) .
                   $LIMIT;
        } else {
            $data['sql']['raw'] = [
                'SELECT' => $SELECT,
                'FROM' => $FROM,
                'WHERE' => $WHERE,
                'GROUPBY' => $GROUPBY,
                'HAVING' => $HAVING,
                'ORDER' => $ORDER,
                'LIMIT' => $LIMIT
            ];
            $QUERY = $SELECT .
                  $FROM .
                  $WHERE .
                  $GROUPBY .
                  $HAVING .
                  $ORDER .
                  $LIMIT;
        }
        $data['sql']['search'] = $QUERY;
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
        $sql = "";

        foreach ($criteria as $criterion) {
            if (
                !isset($criterion['criteria'])
                && (!isset($criterion['value'])
                 || strlen($criterion['value']) <= 0)
            ) {
                continue;
            }

            $itemtype = $data['itemtype'];
            $meta = false;
            if (
                isset($criterion['meta'])
                && $criterion['meta']
                && isset($criterion['itemtype'])
            ) {
                $itemtype = $criterion['itemtype'];
                $meta = true;
                $meta_searchopt = self::getOptions($itemtype);
            } else {
               // Not a meta, use the same search option everywhere
                $meta_searchopt = $searchopt;
            }

           // common search
            if (
                !isset($criterion['field'])
                || ($criterion['field'] != "all"
                 && $criterion['field'] != "view")
            ) {
                $LINK    = " ";
                $NOT     = 0;
                $tmplink = "";

                if (
                    isset($criterion['link'])
                    && in_array($criterion['link'], array_keys(self::getLogicalOperators()))
                ) {
                    if (strstr($criterion['link'], "NOT")) {
                        $tmplink = " " . str_replace(" NOT", "", $criterion['link']);
                        $NOT     = 1;
                    } else {
                        $tmplink = " " . $criterion['link'];
                    }
                } else {
                    $tmplink = " AND ";
                }

               // Manage Link if not first item
                if (!empty($sql)) {
                    $LINK = $tmplink;
                }

                if (isset($criterion['criteria']) && count($criterion['criteria'])) {
                    $sub_sql = self::constructCriteriaSQL($criterion['criteria'], $data, $meta_searchopt, $is_having);
                    if (strlen($sub_sql)) {
                        if ($NOT) {
                             $sql .= "$LINK NOT($sub_sql)";
                        } else {
                            $sql .= "$LINK ($sub_sql)";
                        }
                    }
                } else if (
                    isset($meta_searchopt[$criterion['field']]["usehaving"])
                       || ($meta && "AND NOT" === $criterion['link'])
                ) {
                    if (!$is_having) {
                       // the having part will be managed in a second pass
                        continue;
                    }

                    $new_having = self::addHaving(
                        $LINK,
                        $NOT,
                        $itemtype,
                        $criterion['field'],
                        $criterion['searchtype'],
                        $criterion['value']
                    );
                    if ($new_having !== false) {
                        $sql .= $new_having;
                    }
                } else {
                    if ($is_having) {
                       // the having part has been already managed in the first pass
                        continue;
                    }

                    $new_where = self::addWhere(
                        $LINK,
                        $NOT,
                        $itemtype,
                        $criterion['field'],
                        $criterion['searchtype'],
                        $criterion['value'],
                        $meta
                    );
                    if ($new_where !== false) {
                        $sql .= $new_where;
                    }
                }
            } else if (
                isset($criterion['value'])
                    && strlen($criterion['value']) > 0
            ) { // view and all search
                $LINK       = " OR ";
                $NOT        = 0;
                $globallink = " AND ";
                if (isset($criterion['link'])) {
                    switch ($criterion['link']) {
                        case "AND":
                            $LINK       = ($criterion['searchtype'] == 'notcontains') ? ' AND ' : ' OR ';
                            $globallink = " AND ";
                            break;
                        case "AND NOT":
                            $LINK       = ($criterion['searchtype'] == 'notcontains') ? ' OR ' : ' AND ';
                            $NOT        = 1;
                            $globallink = " AND ";
                            break;
                        case "OR":
                            $LINK       = ($criterion['searchtype'] == 'notcontains') ? ' AND ' : ' OR ';
                            $globallink = " OR ";
                            break;
                        case "OR NOT":
                            $LINK       = ($criterion['searchtype'] == 'notcontains') ? ' OR ' : ' AND ';
                            $NOT        = 1;
                            $globallink = " OR ";
                            break;
                    }
                } else {
                    $tmplink = " AND ";
                }
                // Manage Link if not first item
                if (!empty($sql) && !$is_having) {
                    $sql .= $globallink;
                }
                $first2 = true;
                $items = [];
                if (isset($criterion['field']) && $criterion['field'] == "all") {
                    $items = $searchopt;
                } else { // toview case : populate toview
                    foreach ($data['toview'] as $key2 => $val2) {
                        $items[$val2] = $searchopt[$val2];
                    }
                }
                $view_sql = "";
                foreach ($items as $key2 => $val2) {
                    if (isset($val2['nosearch']) && $val2['nosearch']) {
                        continue;
                    }
                    if (is_array($val2)) {
                       // Add Where clause if not to be done in HAVING CLAUSE
                        if (!$is_having && !isset($val2["usehaving"])) {
                            $tmplink = $LINK;
                            if ($first2) {
                                $tmplink = " ";
                            }

                            $new_where = self::addWhere(
                                $tmplink,
                                $NOT,
                                $itemtype,
                                $key2,
                                $criterion['searchtype'],
                                $criterion['value'],
                                $meta
                            );
                            if ($new_where !== false) {
                                 $first2  = false;
                                 $view_sql .=  $new_where;
                            }
                        }
                    }
                }
                if (strlen($view_sql)) {
                    $sql .= " ($view_sql) ";
                }
            }
        }
        return $sql;
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
        $data['meta_toview'] = [];
        foreach ($criteria as $criterion) {
           // manage sub criteria
            if (isset($criterion['criteria'])) {
                self::constructAdditionalSqlForMetacriteria(
                    $criterion['criteria'],
                    $SELECT,
                    $FROM,
                    $already_link_tables,
                    $data
                );
                continue;
            }

           // parse only criterion with meta flag
            if (
                !isset($criterion['itemtype'])
                || empty($criterion['itemtype'])
                || !isset($criterion['meta'])
                || !$criterion['meta']
                || !isset($criterion['value'])
                || strlen($criterion['value']) <= 0
            ) {
                continue;
            }

            $m_itemtype = $criterion['itemtype'];
            $metaopt = self::getOptions($m_itemtype);
            $sopt    = $metaopt[$criterion['field']];

           //add toview for meta criterion
            $data['meta_toview'][$m_itemtype][] = $criterion['field'];

            $SELECT .= self::addSelect(
                $m_itemtype,
                $criterion['field'],
                true, // meta-criterion
                $m_itemtype
            );

            $FROM .= self::addMetaLeftJoin(
                $data['itemtype'],
                $m_itemtype,
                $already_link_tables,
                $sopt["joinparams"]
            );

            $FROM .= self::addLeftJoin(
                $m_itemtype,
                $m_itemtype::getTable(),
                $already_link_tables,
                $sopt["table"],
                $sopt["linkfield"],
                1,
                $m_itemtype,
                $sopt["joinparams"],
                $sopt["field"]
            );
        }
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
        if (!isset($data['sql']) || !isset($data['sql']['search'])) {
            return false;
        }
        $data['data'] = [];

        // Use a ReadOnly connection if available and configured to be used
        $DBread = DBConnection::getReadConnection();
        $DBread->doQuery("SET SESSION group_concat_max_len = 8194304;");

        $DBread->execution_time = true;
        $result = $DBread->doQuery($data['sql']['search']);

        if ($result) {
            $data['data']['execution_time'] = $DBread->execution_time;
            if (isset($data['search']['savedsearches_id'])) {
                SavedSearch::updateExecutionTime(
                    (int)$data['search']['savedsearches_id'],
                    $DBread->execution_time
                );
            }

            $data['data']['totalcount'] = 0;
           // if real search or complete export : get numrows from request
            if (
                !$data['search']['no_search']
                || $data['search']['export_all']
            ) {
                $data['data']['totalcount'] = $DBread->numrows($result);
            } else {
                if (
                    !isset($data['sql']['count'])
                    || (count($data['sql']['count']) == 0)
                ) {
                    $data['data']['totalcount'] = $DBread->numrows($result);
                } else {
                    foreach ($data['sql']['count'] as $sqlcount) {
                        $result_num = $DBread->doQuery($sqlcount);
                        $data['data']['totalcount'] += $DBread->result($result_num, 0, 0);
                    }
                }
            }

            if ($onlycount) {
               //we just want to coutn results; no need to continue process
                return;
            }

            if ($data['search']['start'] > $data['data']['totalcount']) {
                $data['search']['start'] = 0;
            }

           // Search case
            $data['data']['begin'] = $data['search']['start'];
            $data['data']['end']   = min(
                $data['data']['totalcount'],
                $data['search']['start'] + $data['search']['list_limit']
            ) - 1;
           //map case
            if (isset($data['search']['as_map'])  && $data['search']['as_map'] == 1) {
                $data['data']['end'] = $data['data']['totalcount'] - 1;
            }

           // No search Case
            if ($data['search']['no_search']) {
                $data['data']['begin'] = 0;
                $data['data']['end']   = min(
                    $data['data']['totalcount'] - $data['search']['start'],
                    $data['search']['list_limit']
                ) - 1;
            }
           // Export All case
            if ($data['search']['export_all']) {
                $data['data']['begin'] = 0;
                $data['data']['end']   = $data['data']['totalcount'] - 1;
            }

           // Get columns
            $data['data']['cols'] = [];

            $searchopt = self::getOptions($data['itemtype']);

            foreach ($data['toview'] as $opt_id) {
                $data['data']['cols'][] = [
                    'itemtype'  => $data['itemtype'],
                    'id'        => $opt_id,
                    'name'      => $searchopt[$opt_id]["name"],
                    'meta'      => 0,
                    'searchopt' => $searchopt[$opt_id],
                ];
            }

           // manage toview column for criteria with meta flag
            foreach ($data['meta_toview'] as $m_itemtype => $toview) {
                $m_searchopt = self::getOptions($m_itemtype);
                foreach ($toview as $opt_id) {
                    $data['data']['cols'][] = [
                        'itemtype'  => $m_itemtype,
                        'id'        => $opt_id,
                        'name'      => $m_searchopt[$opt_id]["name"],
                        'meta'      => 1,
                        'searchopt' => $m_searchopt[$opt_id],
                        'groupname' => $m_itemtype,
                    ];
                }
            }

           // Display columns Headers for meta items
            $already_printed = [];

            if (count($data['search']['metacriteria'])) {
                foreach ($data['search']['metacriteria'] as $metacriteria) {
                    if (
                        isset($metacriteria['itemtype']) && !empty($metacriteria['itemtype'])
                        && isset($metacriteria['value']) && (strlen($metacriteria['value']) > 0)
                    ) {
                        if (!isset($already_printed[$metacriteria['itemtype'] . $metacriteria['field']])) {
                            $m_searchopt = self::getOptions($metacriteria['itemtype']);

                            $data['data']['cols'][] = [
                                'itemtype'  => $metacriteria['itemtype'],
                                'id'        => $metacriteria['field'],
                                'name'      => $m_searchopt[$metacriteria['field']]["name"],
                                'meta'      => 1,
                                'searchopt' => $m_searchopt[$metacriteria['field']],
                                'groupname' => $metacriteria['itemtype'],
                            ];

                            $already_printed[$metacriteria['itemtype'] . $metacriteria['field']] = 1;
                        }
                    }
                }
            }

           // search group (corresponding of dropdown optgroup) of current col
            foreach ($data['data']['cols'] as $num => $col) {
               // search current col in searchoptions ()
                while (
                    key($searchopt) !== null
                    && key($searchopt) != $col['id']
                ) {
                    next($searchopt);
                }
                if (key($searchopt) !== null) {
                   //search optgroup (non array option)
                    while (
                        key($searchopt) !== null
                        && is_numeric(key($searchopt))
                        && is_array(current($searchopt))
                    ) {
                        prev($searchopt);
                    }
                    if (
                        key($searchopt) !== null
                        && key($searchopt) !== "common"
                        && !isset($data['data']['cols'][$num]['groupname'])
                    ) {
                        $data['data']['cols'][$num]['groupname'] = current($searchopt);
                    }
                }
               //reset
                reset($searchopt);
            }

           // Get rows

           // if real search seek to begin of items to display (because of complete search)
            if (!$data['search']['no_search']) {
                $DBread->dataSeek($result, $data['search']['start']);
            }

            $i = $data['data']['begin'];
            $data['data']['warning']
            = "For compatibility keep raw data  (ITEM_X, META_X) at the top for the moment. Will be drop in next version";

            $data['data']['rows']  = [];
            $data['data']['items'] = [];

            self::$output_type = $data['display_type'];

            while (($i < $data['data']['totalcount']) && ($i <= $data['data']['end'])) {
                $row = $DBread->fetchAssoc($result);
                $newrow        = [];
                $newrow['raw'] = $row;

               // Parse datas
                foreach ($newrow['raw'] as $key => $val) {
                    if (preg_match('/ITEM(_(\w[^\d]+))?_(\d+)(_(.+))?/', $key, $matches)) {
                        $j = $matches[3];
                        if (isset($matches[2]) && !empty($matches[2])) {
                            $j = $matches[2] . '_' . $matches[3];
                        }
                        $fieldname = 'name';
                        if (isset($matches[5])) {
                            $fieldname = $matches[5];
                        }

                        // No Group_concat case
                        if ($fieldname == 'content' || !is_string($val) || strpos($val, self::LONGSEP) === false) {
                            $newrow[$j]['count'] = 1;

                            $handled = false;
                            if ($fieldname != 'content' && is_string($val) && strpos($val, self::SHORTSEP) !== false) {
                                $split2                    = self::explodeWithID(self::SHORTSEP, $val);
                                if ($j == "User_80") {
                                    $newrow[$j][0][$fieldname] = $split2[0];
                                    $newrow[$j][0]["profiles_id"] = $split2[1];
                                    $newrow[$j][0]["is_recursive"] = $split2[2];
                                    $newrow[$j][0]["is_dynamic"] = $split2[3];
                                    $handled = true;
                                } elseif ($j == "User_20") {
                                    $newrow[$j][0][$fieldname] = $split2[0];
                                    $newrow[$j][0]["entities_id"] = $split2[1];
                                    $newrow[$j][0]["is_recursive"] = $split2[2];
                                    $newrow[$j][0]["is_dynamic"] = $split2[3];
                                    $handled = true;
                                } elseif (is_numeric($split2[1])) {
                                    $newrow[$j][0][$fieldname] = $split2[0];
                                    $newrow[$j][0]['id']       = $split2[1];
                                    $handled = true;
                                }
                            }

                            if (!$handled) {
                                if ($val === self::NULLVALUE) {
                                    $newrow[$j][0][$fieldname] = null;
                                } else {
                                    $newrow[$j][0][$fieldname] = $val;
                                }
                            }
                        } else {
                            if (!isset($newrow[$j])) {
                                $newrow[$j] = [];
                            }
                            $split               = explode(self::LONGSEP, $val);
                            $newrow[$j]['count'] = count($split);
                            foreach ($split as $key2 => $val2) {
                                $handled = false;
                                if (strpos($val2, self::SHORTSEP) !== false) {
                                    $split2                  = self::explodeWithID(self::SHORTSEP, $val2);
                                    if ($j == "User_80") {
                                        $newrow[$j][$key2][$fieldname] = $split2[0];
                                        $newrow[$j][$key2]["profiles_id"] = $split2[1];
                                        $newrow[$j][$key2]["is_recursive"] = $split2[2];
                                        $newrow[$j][$key2]["is_dynamic"] = $split2[3];
                                        $handled = true;
                                    } elseif ($j == "User_20") {
                                        $newrow[$j][$key2][$fieldname] = $split2[0];
                                        $newrow[$j][$key2]["entities_id"] = $split2[1];
                                        $newrow[$j][$key2]["is_recursive"] = $split2[2];
                                        $newrow[$j][$key2]["is_dynamic"] = $split2[3];
                                        $handled = true;
                                    } elseif (is_numeric($split2[1])) {
                                        $newrow[$j][$key2]['id'] = $split2[1];
                                        if ($split2[0] == self::NULLVALUE) {
                                            $newrow[$j][$key2][$fieldname] = null;
                                        } else {
                                             $newrow[$j][$key2][$fieldname] = $split2[0];
                                        }
                                        $handled = true;
                                    }
                                }

                                if (!$handled) {
                                    $newrow[$j][$key2][$fieldname] = $val2;
                                }
                            }
                        }
                    } else {
                        if ($key == 'currentuser') {
                            if (!isset($data['data']['currentuser'])) {
                                $data['data']['currentuser'] = $val;
                            }
                        } else {
                            $newrow[$key] = $val;
                           // Add id to items list
                            if ($key == 'id') {
                                $data['data']['items'][$val] = $i;
                            }
                        }
                    }
                }
                foreach ($data['data']['cols'] as $val) {
                    $newrow[$val['itemtype'] . '_' . $val['id']]['displayname'] = self::giveItem(
                        $val['itemtype'],
                        $val['id'],
                        $newrow
                    );
                }

                $data['data']['rows'][$i] = $newrow;
                $i++;
            }

            $data['data']['count'] = count($data['data']['rows']);
        } else {
            $error_no = $DBread->errno();
            if ($error_no == 1116) { // Too many tables; MySQL can only use 61 tables in a join
                echo self::showError(
                    $data['search']['display_type'],
                    __("'All' criterion is not usable with this object list, " .
                                   "sql query fails (too many tables). " .
                    "Please use 'Items seen' criterion instead")
                );
            } else {
                echo $DBread->error();
            }
        }
    }


    /**
     * Display datas extracted from DB
     *
     * @param array $data Array of search datas prepared to get datas
     *
     * @return void
     **/
    public static function displayData(array $data)
    {
        /** @var array $CFG_GLPI */
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
        $globallinkto  = Toolbox::append_params([
            'criteria'     => Sanitizer::unsanitize($search['criteria']),
            'metacriteria' => Sanitizer::unsanitize($search['metacriteria'])
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
            $out = Plugin::doOneHook($plug['plugin'], 'addParamFordynamicReport', $data['itemtype']);
            if (is_array($out) && count($out)) {
                $parameters .= Toolbox::append_params($out, '&');
            }
        }

        $search['target'] = URL::sanitizeURL($search['target']);
        $prehref = $search['target'] . (strpos($search['target'], "?") !== false ? "&" : "?");
        $href    = $prehref . $parameters;

        Session::initNavigateListItems($data['itemtype'], '', $href);

        TemplateRenderer::getInstance()->display('components/search/display_data.html.twig', [
            'data'                => $data,
            'union_search_type'   => $CFG_GLPI["union_search_type"],
            'rand'                => mt_rand(),
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
            'showmassiveactions'  => ($search['showmassiveactions'] ?? true)
                                  && $data['display_type'] != self::GLOBAL_SEARCH
                                  && ($itemtype == AllAssets::getType()
                                    || count(MassiveAction::getAllMassiveActions($item, $is_deleted))
                                  ),
            'massiveactionparams' => $data['search']['massiveactionparams'] + [
                'is_deleted' => $is_deleted,
                'container'  => "massform$itemtype",
            ],
            'can_config'          => Session::haveRightsOr('search_config', [
                DisplayPreference::PERSONAL,
                DisplayPreference::GENERAL
            ]),
            'may_be_deleted'      => $item instanceof CommonDBTM && $item->maybeDeleted() && !$item->useDeletedToLockIfDynamic(),
            'may_be_located'      => $item instanceof CommonDBTM && $item->maybeLocated(),
            'may_be_browsed'      => $item !== null && Toolbox::hasTrait($item, \Glpi\Features\TreeBrowse::class),
        ]);

        // Add items in item list
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

       // Clean previous selection
        $_SESSION['glpimassiveactionselected'] = [];
    }

    /**
     * Output data (for export in CSV, PDF, ...).
     *
     * @param array $data Array of search datas prepared to get datas
     *
     * @return void
     **/
    public static function outputData(array $data)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (
            !isset($data['data'])
            || !isset($data['data']['totalcount'])
            || $data['data']['count'] <= 0
            || $data['search']['as_map'] != 0
        ) {
            return false;
        }

       // Define begin and end var for loop
       // Search case
        $begin_display = $data['data']['begin'];
        $end_display   = $data['data']['end'];

       // Compute number of columns to display
       // Add toview elements
        $nbcols          = count($data['data']['cols']);

       // Display List Header
        echo self::showHeader($data['display_type'], $end_display - $begin_display + 1, $nbcols);

       // New Line for Header Items Line
        $headers_line        = '';
        $headers_line_top    = '';

        $headers_line_top .= self::showBeginHeader($data['display_type']);
        $headers_line_top .= self::showNewLine($data['display_type']);

        $header_num = 1;

       // Display column Headers for toview items
        $metanames = [];
        foreach ($data['data']['cols'] as $val) {
            $name = $val["name"];

           // prefix by group name (corresponding to optgroup in dropdown) if exists
            if (isset($val['groupname'])) {
                $groupname = $val['groupname'];
                if (is_array($groupname)) {
                    //since 9.2, getSearchOptions has been changed
                    $groupname = $groupname['name'];
                }
                $name  = "$groupname - $name";
            }

           // Not main itemtype add itemtype to display
            if ($data['itemtype'] != $val['itemtype']) {
                if (!isset($metanames[$val['itemtype']])) {
                    if ($metaitem = getItemForItemtype($val['itemtype'])) {
                        $metanames[$val['itemtype']] = $metaitem->getTypeName();
                    }
                }
                $name = sprintf(
                    __('%1$s - %2$s'),
                    $metanames[$val['itemtype']],
                    $val["name"]
                );
            }

            $headers_line .= self::showHeaderItem(
                $data['display_type'],
                $name,
                $header_num,
                '',
                (!$val['meta']
                                                && ($data['search']['sort'] == $val['id'])),
                $data['search']['order']
            );
        }

       // Add specific column Header
        if (isset($CFG_GLPI["union_search_type"][$data['itemtype']])) {
            $headers_line .= self::showHeaderItem(
                $data['display_type'],
                __('Item type'),
                $header_num
            );
        }
       // End Line for column headers
        $headers_line .= self::showEndLine($data['display_type'], true);

        $headers_line_top    .= $headers_line;
        $headers_line_top    .= self::showEndHeader($data['display_type']);

        echo $headers_line_top;

       // Num of the row (1=header_line)
        $row_num = 1;

        $typenames = [];
       // Display Loop
        foreach ($data['data']['rows'] as $row) {
           // Column num
            $item_num = 1;
            $row_num++;
           // New line
            echo self::showNewLine(
                $data['display_type'],
                ($row_num % 2),
                $data['search']['is_deleted']
            );

           // Print other toview items
            foreach ($data['data']['cols'] as $col) {
                $colkey = "{$col['itemtype']}_{$col['id']}";
                if (!$col['meta']) {
                    echo self::showItem(
                        $data['display_type'],
                        $row[$colkey]['displayname'],
                        $item_num,
                        $row_num,
                        self::displayConfigItem(
                            $data['itemtype'],
                            $col['id'],
                            $row
                        )
                    );
                } else { // META case
                    echo self::showItem(
                        $data['display_type'],
                        $row[$colkey]['displayname'],
                        $item_num,
                        $row_num
                    );
                }
            }

            if (isset($CFG_GLPI["union_search_type"][$data['itemtype']])) {
                if (!isset($typenames[$row["TYPE"]])) {
                    if ($itemtmp = getItemForItemtype($row["TYPE"])) {
                        $typenames[$row["TYPE"]] = $itemtmp->getTypeName();
                    }
                }
                echo self::showItem(
                    $data['display_type'],
                    $typenames[$row["TYPE"]],
                    $item_num,
                    $row_num
                );
            }
           // End Line
            echo self::showEndLine($data['display_type']);
        }

       // Create title
        $title = '';
        if (
            ($data['display_type'] == self::PDF_OUTPUT_LANDSCAPE)
            || ($data['display_type'] == self::PDF_OUTPUT_PORTRAIT)
        ) {
            $title = self::computeTitle($data);
        }

       // Display footer (close table)
        echo self::showFooter($data['display_type'], $title, $data['data']['count']);
    }


    /**
     * Compute title (use case of PDF OUTPUT)
     *
     * @param array $data Array data of search
     *
     * @return string Title
     **/
    public static function computeTitle($data)
    {
        $title = "";

        if (count($data['search']['criteria'])) {
           //Drop the first link as it is not needed, or convert to clean link (AND NOT -> NOT)
            if (isset($data['search']['criteria']['0']['link'])) {
                $notpos = strpos($data['search']['criteria']['0']['link'], 'NOT');
                //If link was like '%NOT%' just use NOT. Otherwise remove the link
                if ($notpos > 0) {
                    $data['search']['criteria']['0']['link'] = 'NOT';
                } else if (!$notpos) {
                    unset($data['search']['criteria']['0']['link']);
                }
            }

            foreach ($data['search']['criteria'] as $criteria) {
                if (isset($criteria['itemtype'])) {
                    $searchopt = self::getOptions($criteria['itemtype']);
                } else {
                    $searchopt = self::getOptions($data['itemtype']);
                }
                $titlecontain = '';

                if (isset($criteria['criteria'])) {
                   //This is a group criteria, call computeTitle again and concat
                    $newdata = $data;
                    $oldlink = $criteria['link'];
                    $newdata['search'] = $criteria;
                    $titlecontain = sprintf(
                        __('%1$s %2$s (%3$s)'),
                        $titlecontain,
                        $oldlink,
                        Search::computeTitle($newdata)
                    );
                } else {
                    if (strlen($criteria['value']) > 0) {
                        if (isset($criteria['link'])) {
                             $titlecontain = " " . $criteria['link'] . " ";
                        }
                        $gdname    = '';
                        $valuename = '';

                        switch ($criteria['field']) {
                            case "all":
                                $titlecontain = sprintf(__('%1$s %2$s'), $titlecontain, __('All'));
                                break;

                            case "view":
                                $titlecontain = sprintf(__('%1$s %2$s'), $titlecontain, __('Items seen'));
                                break;

                            default:
                                if (isset($criteria['meta']) && $criteria['meta']) {
                                    $searchoptname = sprintf(
                                        __('%1$s / %2$s'),
                                        $criteria['itemtype'],
                                        $searchopt[$criteria['field']]["name"]
                                    );
                                } else {
                                    $searchoptname = $searchopt[$criteria['field']]["name"];
                                }

                                $titlecontain = sprintf(__('%1$s %2$s'), $titlecontain, $searchoptname);
                                $itemtype     = getItemTypeForTable($searchopt[$criteria['field']]["table"]);
                                $valuename    = '';
                                if ($item = getItemForItemtype($itemtype)) {
                                    $valuename = $item->getValueToDisplay(
                                        $searchopt[$criteria['field']],
                                        $criteria['value']
                                    );
                                }

                                $gdname = Dropdown::getDropdownName(
                                    $searchopt[$criteria['field']]["table"],
                                    $criteria['value']
                                );
                        }

                        if (empty($valuename)) {
                            $valuename = $criteria['value'];
                        }
                        switch ($criteria['searchtype']) {
                            case "equals":
                                if (
                                    in_array(
                                        $searchopt[$criteria['field']]["field"],
                                        ['name', 'completename']
                                    )
                                ) {
                                    $titlecontain = sprintf(__('%1$s = %2$s'), $titlecontain, $gdname);
                                } else {
                                    $titlecontain = sprintf(__('%1$s = %2$s'), $titlecontain, $valuename);
                                }
                                break;

                            case "notequals":
                                if (
                                    in_array(
                                        $searchopt[$criteria['field']]["field"],
                                        ['name', 'completename']
                                    )
                                ) {
                                    $titlecontain = sprintf(__('%1$s <> %2$s'), $titlecontain, $gdname);
                                } else {
                                    $titlecontain = sprintf(__('%1$s <> %2$s'), $titlecontain, $valuename);
                                }
                                break;

                            case "lessthan":
                                $titlecontain = sprintf(__('%1$s < %2$s'), $titlecontain, $valuename);
                                break;

                            case "morethan":
                                $titlecontain = sprintf(__('%1$s > %2$s'), $titlecontain, $valuename);
                                break;

                            case "contains":
                                $titlecontain = sprintf(
                                    __('%1$s = %2$s'),
                                    $titlecontain,
                                    '%' . $valuename . '%'
                                );
                                break;

                            case "notcontains":
                                $titlecontain = sprintf(
                                    __('%1$s <> %2$s'),
                                    $titlecontain,
                                    '%' . $valuename . '%'
                                );
                                break;

                            case "under":
                                $titlecontain = sprintf(
                                    __('%1$s %2$s'),
                                    $titlecontain,
                                    sprintf(__('%1$s %2$s'), __('under'), $gdname)
                                );
                                break;

                            case "notunder":
                                $titlecontain = sprintf(
                                    __('%1$s %2$s'),
                                    $titlecontain,
                                    sprintf(__('%1$s %2$s'), __('not under'), $gdname)
                                );
                                break;

                            default:
                                $titlecontain = sprintf(__('%1$s = %2$s'), $titlecontain, $valuename);
                                break;
                        }
                    }
                }
                $title .= $titlecontain;
            }
        }
        if (
            isset($data['search']['metacriteria']) &&
            count($data['search']['metacriteria'])
        ) {
            $metanames = [];
            foreach ($data['search']['metacriteria'] as $metacriteria) {
                $searchopt = self::getOptions($metacriteria['itemtype']);
                if (!isset($metanames[$metacriteria['itemtype']])) {
                    if ($metaitem = getItemForItemtype($metacriteria['itemtype'])) {
                        $metanames[$metacriteria['itemtype']] = $metaitem->getTypeName();
                    }
                }

                $titlecontain2 = '';
                if (strlen($metacriteria['value']) > 0) {
                    if (isset($metacriteria['link'])) {
                        $titlecontain2 = sprintf(
                            __('%1$s %2$s'),
                            $titlecontain2,
                            $metacriteria['link']
                        );
                    }
                    $titlecontain2
                    = sprintf(
                        __('%1$s %2$s'),
                        $titlecontain2,
                        sprintf(
                            __('%1$s / %2$s'),
                            $metanames[$metacriteria['itemtype']],
                            $searchopt[$metacriteria['field']]["name"]
                        )
                    );

                    $gdname2 = Dropdown::getDropdownName(
                        $searchopt[$metacriteria['field']]["table"],
                        $metacriteria['value']
                    );
                    switch ($metacriteria['searchtype']) {
                        case "equals":
                            if (
                                in_array(
                                    $searchopt[$metacriteria['link']]
                                          ["field"],
                                    ['name', 'completename']
                                )
                            ) {
                                $titlecontain2 = sprintf(
                                    __('%1$s = %2$s'),
                                    $titlecontain2,
                                    $gdname2
                                );
                            } else {
                                $titlecontain2 = sprintf(
                                    __('%1$s = %2$s'),
                                    $titlecontain2,
                                    $metacriteria['value']
                                );
                            }
                            break;

                        case "notequals":
                            if (
                                in_array(
                                    $searchopt[$metacriteria['link']]["field"],
                                    ['name', 'completename']
                                )
                            ) {
                                $titlecontain2 = sprintf(
                                    __('%1$s <> %2$s'),
                                    $titlecontain2,
                                    $gdname2
                                );
                            } else {
                                $titlecontain2 = sprintf(
                                    __('%1$s <> %2$s'),
                                    $titlecontain2,
                                    $metacriteria['value']
                                );
                            }
                            break;

                        case "lessthan":
                            $titlecontain2 = sprintf(
                                __('%1$s < %2$s'),
                                $titlecontain2,
                                $metacriteria['value']
                            );
                            break;

                        case "morethan":
                            $titlecontain2 = sprintf(
                                __('%1$s > %2$s'),
                                $titlecontain2,
                                $metacriteria['value']
                            );
                            break;

                        case "contains":
                              $titlecontain2 = sprintf(
                                  __('%1$s = %2$s'),
                                  $titlecontain2,
                                  '%' . $metacriteria['value'] . '%'
                              );
                            break;

                        case "notcontains":
                               $titlecontain2 = sprintf(
                                   __('%1$s <> %2$s'),
                                   $titlecontain2,
                                   '%' . $metacriteria['value'] . '%'
                               );
                            break;

                        case "under":
                              $titlecontain2 = sprintf(
                                  __('%1$s %2$s'),
                                  $titlecontain2,
                                  sprintf(
                                      __('%1$s %2$s'),
                                      __('under'),
                                      $gdname2
                                  )
                              );
                            break;

                        case "notunder":
                             $titlecontain2 = sprintf(
                                 __('%1$s %2$s'),
                                 $titlecontain2,
                                 sprintf(
                                     __('%1$s %2$s'),
                                     __('not under'),
                                     $gdname2
                                 )
                             );
                            break;

                        default:
                            $titlecontain2 = sprintf(
                                __('%1$s = %2$s'),
                                $titlecontain2,
                                $metacriteria['value']
                            );
                            break;
                    }
                }
                $title .= $titlecontain2;
            }
        }
        return $title;
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
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $itemtype = self::getMetaReferenceItemtype($itemtype);

        if (!(($item = getItemForItemtype($itemtype)) instanceof CommonDBTM)) {
            return [];
        }

        $linked = [];
        foreach ($CFG_GLPI as $key => $values) {
            if ($key === 'link_types') {
               // Links are associated to all items of a type, it does not make any sense to use them in meta search
                continue;
            }
            if ($key === 'ticket_types' && $item instanceof CommonITILObject) {
                // Linked are filtered by CommonITILObject::getAllTypesForHelpdesk()
                $linked = array_merge($linked, array_keys($item::getAllTypesForHelpdesk()));
                continue;
            }

            foreach (self::getMetaParentItemtypesForTypesConfig($key) as $config_itemtype) {
                if ($itemtype === $config_itemtype::getType()) {
                   // List is related to source itemtype, all types of list are so linked
                    $linked = array_merge($linked, $values);
                } elseif (in_array($itemtype, $values)) {
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
        if (preg_match('/^(.+)_types$/', $config_key, $matches) === 0) {
            return [];
        }

        $key_to_itemtypes = [
            'appliance_types'      => ['Appliance'],
            'directconnect_types'  => ['Computer'],
            'infocom_types'        => ['Budget', 'Infocom'],
            'linkgroup_types'      => ['Group'],
         // 'linkgroup_tech_types' => ['Group'], // Cannot handle ambiguity with 'Group' from 'linkgroup_types'
            'linkuser_types'       => ['User'],
         // 'linkuser_tech_types'  => ['User'], // Cannot handle ambiguity with 'User' from 'linkuser_types'
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
    private static function isPossibleMetaSubitemOf(string $parent_itemtype, string $child_itemtype)
    {
        /** @var array $CFG_GLPI */
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
     * Gets the class to use if the specified itemtype extends one of the known reference types.
     *
     * @param class-string<CommonDBTM> $itemtype
     *
     * @return string|false The reference class name. If the provided itemtype is from a plugin, the provided itemtype is returned.
     *                      If the itemtype is not from a plugin and not exactly or extended from a reference itemtype, false will be returned.
     * @since 0.85
     */
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
            'Phone'
        ];
        foreach ($types as $type) {
            if (is_a($itemtype, $type, true)) {
                return $type;
            }
        }

        return false;
    }


    /**
     * Get dropdown options of logical operators.
     * @return string[]|array<string, string>
     * @since 0.85
     **/
    public static function getLogicalOperators($only_not = false)
    {
        if ($only_not) {
            return [
                'AND'     => Dropdown::EMPTY_VALUE,
                'AND NOT' => __("NOT")
            ];
        }

        return [
            'AND'     => __('AND'),
            'OR'      => __('OR'),
            'AND NOT' => __('AND NOT'),
            'OR NOT'  => __('OR NOT')
        ];
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
        /** @var array $CFG_GLPI */
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
            $p['target']       = Toolbox::getItemTypeSearchURL($itemtype);
        }
        $p['showreset']    = true;
        $p['showbookmark'] = true;
        $p['showfolding']  = true;
        $p['mainform']     = true;
        $p['prefix_crit']  = '';
        $p['addhidden']    = [];
        $p['showaction']   = true;
        $p['actionname']   = 'search';
        $p['actionvalue']  = _sx('button', 'Search');

        foreach ($params as $key => $val) {
            $p[$key] = $val;
        }
        $p['target'] = URL::sanitizeURL($p['target']);

       // Itemtype name used in JS function names, etc
        $normalized_itemtype = strtolower(str_replace('\\', '', $itemtype));
        $rand_criteria = mt_rand();
        $main_block_class = '';
        $card_class = 'search-form card card-sm mb-4';
        if ($p['mainform'] && $p['showaction']) {
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
        echo Html::scriptBlock("
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
            echo Html::input("savedsearches_id", [
                'type' => "hidden",
                'value' => $_GET['savedsearches_id'],
            ]);
        }

        echo "<div class='card-footer d-flex search_actions'>";
        $linked = self::getMetaItemtypeAvailable($itemtype);
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
            if ($p['showaction']) {
                // Display submit button
                echo '<button class="btn btn-sm btn-primary me-1" type="submit" name="' . htmlspecialchars($p['actionname']) . '">
                <i class="ti ti-list-search"></i>
                <span class="d-none d-sm-block">' . $p['actionvalue'] . '</span>
                </button>';
            }
            if ($p['showbookmark'] || $p['showreset']) {
                if ($p['showbookmark']) {
                    SavedSearch::showSaveButton(
                        SavedSearch::SEARCH,
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
        $idor_display_criteria       = Session::getNewIDORToken($itemtype);
        $idor_display_meta_criteria  = Session::getNewIDORToken($itemtype);
        $idor_display_criteria_group = Session::getNewIDORToken($itemtype);

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
        echo Html::scriptBlock($JS);

        if (count($p['addhidden'])) {
            foreach ($p['addhidden'] as $key => $val) {
                echo Html::hidden($key, ['value' => $val]);
            }
        }

        if ($p['mainform']) {
           // For dropdown
            echo Html::hidden('itemtype', ['value' => $itemtype]);
           // Reset to start when submit new search
            echo Html::hidden('start', ['value'    => 0]);
        }

        echo "</div>"; // #searchcriteria
        echo "</div>"; // .card
        if ($p['mainform'] && $p['showaction']) {
            Html::closeForm();
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
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (
            !isset($request["itemtype"])
            || !isset($request["num"])
        ) {
            return;
        }

        $num         = (int) $request['num'];
        $p           = $request['p'];
        $options     = self::getCleanedOptions($request["itemtype"]);
        $randrow     = mt_rand();
        $normalized_itemtype = strtolower(str_replace('\\', '', $request["itemtype"]));
        $rowid       = 'searchrow' . $normalized_itemtype . $randrow;
        $addclass    = $num == 0 ? ' headerRow' : '';
        $prefix      = isset($p['prefix_crit']) ? htmlspecialchars($p['prefix_crit'], ENT_QUOTES) : '';
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
                if ($request["itemtype"] != AllAssets::getType()) {
                    $item = getItemForItemtype($request["itemtype"]);
                }
                if ($item && $item->maybeDeleted()) {
                    echo Html::hidden('is_deleted', [
                        'value' => $p['is_deleted'],
                        'id'    => 'is_deleted'
                    ]);
                }
                echo Html::hidden('as_map', [
                    'value' => $p['as_map'],
                    'id'    => 'as_map'
                ]);
                echo Html::hidden('browse', [
                    'value' => $p['browse'],
                    'id'    => 'browse'
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
            $operators = Search::getLogicalOperators(($num == 0));
            Dropdown::showFromArray("criteria{$prefix}[$num][link]", $operators, [
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
        $rand = Dropdown::showFromArray("criteria{$prefix}[$num][field]", $values, [
            'value' => $value,
        ]);
        echo "</div>";
        $field_id = Html::cleanId("dropdown_criteria{$prefix}[$num][field]$rand");
        $spanid   = Html::cleanId('SearchSpan' . $normalized_itemtype . $prefix . $num);

        echo "<div class='col-auto'>";
        echo "<div class='row g-1' id='$spanid'>";

        $used_itemtype = $request["itemtype"];
       // Force Computer itemtype for AllAssets to permit to show specific items
        if ($request["itemtype"] == AllAssets::getType()) {
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
            '_idor_token' => Session::getNewIDORToken($used_itemtype),
            'field'       => $value,
            'searchtype'  => $searchtype,
            'value'       => $p_value,
            'num'         => $num,
            'p'           => $p,
        ];
        Search::displaySearchoption($params);
        echo "</div>";

        Ajax::updateItemOnSelectEvent(
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
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (
            !isset($request["itemtype"])
            || !isset($request["num"])
        ) {
            return "";
        }

        $p            = $request['p'];
        $num          = (int) $request['num'];
        $prefix       = isset($p['prefix_crit']) ? htmlspecialchars($p['prefix_crit'], ENT_QUOTES) : '';
        $parents_num  = isset($p['parents_num']) ? $p['parents_num'] : [];
        $itemtype     = $request["itemtype"];
        $metacriteria = [];

        if (!$metacriteria = self::findCriteriaInSession($itemtype, $num, $parents_num)) {
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

        $linked =  Search::getMetaItemtypeAvailable($itemtype);
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
        Dropdown::showFromArray(
            "criteria{$prefix}[$num][link]",
            Search::getLogicalOperators(),
            [
                'value' => isset($metacriteria["link"])
               ? $metacriteria["link"]
               : "",
            ]
        );
        echo "</div>";

       // Display select of the linked item type available
        echo "<div class='col-auto'>";
        $rand = Dropdown::showItemTypes("criteria{$prefix}[$num][itemtype]", $linked, [
            'value' => isset($metacriteria['itemtype'])
                    && !empty($metacriteria['itemtype'])
                     ? $metacriteria['itemtype']
                     : "",
        ]);
        echo "</div>";
        echo Html::hidden("criteria{$prefix}[$num][meta]", [
            'value' => true
        ]);
        $field_id = Html::cleanId("dropdown_criteria{$prefix}[$num][itemtype]$rand");
        $spanid   = Html::cleanId("show_" . $request["itemtype"] . "_" . $prefix . $num . "_$rand");
       // Ajax script for display search met& item

        $params = [
            'action'          => 'display_criteria',
            'itemtype'        => '__VALUE__',
            'parent_itemtype' => $request['itemtype'],
            'from_meta'       => true,
            'num'             => $num,
            'p'               => $request["p"],
            '_idor_token'     => Session::getNewIDORToken("", [
                'parent_itemtype' => $request['itemtype']
            ])
        ];
        Ajax::updateItemOnSelectEvent(
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
        $addclass    = $num == 0 ? ' headerRow' : '';
        $prefix      = isset($p['prefix_crit']) ? htmlspecialchars($p['prefix_crit'], ENT_QUOTES) : '';
        $parents_num = isset($p['parents_num']) ? $p['parents_num'] : [];

        if (!$criteria = self::findCriteriaInSession($request['itemtype'], $num, $parents_num)) {
            $criteria = [
                'criteria' => self::getDefaultCriteria($request['itemtype']),
            ];
        }

        echo "<div class='list-group-item p-2 border-0 normalcriteria$addclass' id='$rowid'>";
        echo "<div class='row g-1'>";
        echo "<div class='col-auto'>";
        echo "<button class='btn btn-sm btn-icon btn-ghost-secondary remove-search-criteria' type='button' data-rowid='$rowid'
                    data-bs-toggle='tooltip' data-bs-placement='left'
                    title=\"" . __s('Delete a rule') . "\"
      >
         <i class='ti ti-square-minus' alt='-'></i>
      </button>";
        echo "</div>";
        echo "<div class='col-auto'>";
        Dropdown::showFromArray("criteria{$prefix}[$num][link]", Search::getLogicalOperators(), [
            'value' => isset($criteria["link"]) ? $criteria["link"] : '',
        ]);
        echo "</div>";

        $parents_num = isset($p['parents_num']) ? $p['parents_num'] : [];
        array_push($parents_num, $num);
        $params = [
            'mainform'    => false,
            'prefix_crit' => "{$prefix}[$num][criteria]",
            'parents_num' => $parents_num,
            'criteria'    => $criteria['criteria'],
        ];

        echo "<div class='col-auto'>";
        self::showGenericSearch($request['itemtype'], $params);
        echo "</div>";

        echo "</div>";//.row
        echo "</div>";//.list-group-item
    }

    /**
     * Retrieve a single criteria in Session by its index
     *
     * @since 9.4
     *
     * @param  string  $itemtype    which glpi type we must search in session
     * @param  integer $num         index of the criteria
     * @param  array   $parents_num node indexes of the parents (@see displayCriteriaGroup)
     *
     * @return array|false   the found criteria array, or false if nothing found
     */
    public static function findCriteriaInSession($itemtype = '', $num = 0, $parents_num = [])
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
     * construct the default criteria for an itemtype
     *
     * @since 9.4
     *
     * @param string $itemtype
     *
     * @return array criteria
     */
    public static function getDefaultCriteria($itemtype = '')
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $field = '';

        if ($CFG_GLPI['allow_search_view'] == 2) {
            $field = 'view';
        } else {
            $options = self::getCleanedOptions($itemtype);
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
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;
        if (
            !isset($request["itemtype"])
            || !isset($request["field"])
            || !isset($request["num"])
        ) {
            return "";
        }

        $p      = $request['p'];
        $num    = (int) $request['num'];
        $prefix = isset($p['prefix_crit']) ? htmlentities($p['prefix_crit'], ENT_QUOTES) : '';

        if (!is_subclass_of($request['itemtype'], 'CommonDBTM')) {
            throw new \RuntimeException('Invalid itemtype provided!');
        }

        if (isset($request['meta']) && $request['meta']) {
            $fieldname = 'metacriteria';
        } else {
            $fieldname = 'criteria';
            $request['meta'] = 0;
        }

        $actions = Search::getActionsFor($request["itemtype"], $request["field"]);

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
        $dropdownname = Html::cleanId("spansearchtype$fieldname" .
                                    $normalized_itemtype .
                                    $prefix .
                                    $num);
        $searchopt = [];
        $fieldsearch_id = null;
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
            $rands = Dropdown::showFromArray($searchtype_name, $actions, [
                'value' => $request["searchtype"],
            ]);
            echo "</div>";
            $fieldsearch_id = Html::cleanId("dropdown_$searchtype_name$rands");
        }

        echo "<div class='col-auto' id='$dropdownname' data-itemtype='{$request["itemtype"]}' data-fieldname='$fieldname' data-prefix='$prefix' data-num='$num'>";
        $params = [
            'value'       => rawurlencode(Sanitizer::dbUnescape($request['value'])),
            'searchopt'   => $searchopt,
            'searchtype'  => $request["searchtype"],
            'num'         => $num,
            'itemtype'    => $request["itemtype"],
            '_idor_token' => Session::getNewIDORToken($request["itemtype"]),
            'from_meta'   => isset($request['from_meta'])
                           ? $request['from_meta']
                           : false,
            'field'       => $request["field"],
            'p'           => $p,
        ];
        self::displaySearchoptionValue($params);
        echo "</div>";

        if ($fieldsearch_id !== null) {
            Ajax::updateItemOnSelectEvent(
                $fieldsearch_id,
                $dropdownname,
                $CFG_GLPI["root_doc"] . "/ajax/search.php",
                [
                    'action'     => 'display_searchoption_value',
                    'searchtype' => '__VALUE__',
                ] + $params
            );
        }
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
     * @return void
     */
    public static function displaySearchoptionValue($request = [])
    {
        if (!isset($request['searchtype'])) {
            return "";
        }

        $p                 = $request['p'];
        $prefix            = isset($p['prefix_crit']) ? htmlspecialchars($p['prefix_crit'], ENT_QUOTES) : '';
        $searchopt         = isset($request['searchopt']) ? $request['searchopt'] : [];
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
                        $display = Plugin::doOneHook(
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
        }

       // Default case : text field
        if (!$display) {
            echo "<input type='text' class='form-control' size='13' name='$inputname' value=\"" .
                  Html::cleanInputText($request['value']) . "\">";
        }
    }


    /**
     * Generic Function to add to a HAVING clause
     *
     * @since 9.4: $num param has been dropped
     *
     * @param string  $LINK           link to use
     * @param string  $NOT            is is a negative search ?
     * @param string  $itemtype       item type
     * @param integer $ID             ID of the item to search
     * @param string  $searchtype     search type ('contains' or 'equals')
     * @param string  $val            value search
     *
     * @return string|false HAVING clause sub-string (Does not include the "HAVING" keyword).
     *                      May return false if the related search option is not valid for SQL searching.
     **/
    public static function addHaving($LINK, $NOT, $itemtype, $ID, $searchtype, $val)
    {

        /** @var \DBmysql $DB */
        global $DB;

        $searchopt  = self::getOptions($itemtype);
        if (!isset($searchopt[$ID]['table'])) {
            return false;
        }
        $table = $searchopt[$ID]["table"];
        $NAME = "ITEM_{$itemtype}_{$ID}";

       // Plugin can override core definition for its type
        if ($plug = isPluginItemType($itemtype)) {
            $out = Plugin::doOneHook(
                $plug['plugin'],
                'addHaving',
                $LINK,
                $NOT,
                $itemtype,
                $ID,
                $val,
                "{$itemtype}_{$ID}"
            );
            if (!empty($out)) {
                return $out;
            }
        }

       //// Default cases
       // Link with plugin tables
        if (preg_match("/^glpi_plugin_([a-z0-9]+)/", $table, $matches)) {
            if (count($matches) == 2) {
                $plug     = $matches[1];
                $out = Plugin::doOneHook(
                    $plug,
                    'addHaving',
                    $LINK,
                    $NOT,
                    $itemtype,
                    $ID,
                    $val,
                    "{$itemtype}_{$ID}"
                );
                if (!empty($out)) {
                     return $out;
                }
            }
        }

        if (in_array($searchtype, ["notequals", "notcontains"])) {
            $NOT = !$NOT;
        }

       // Preformat items
        if (isset($searchopt[$ID]["datatype"])) {
            if ($searchopt[$ID]["datatype"] == "mio") {
                // Parse value as it may contain a few different formats
                $val = Toolbox::getMioSizeFromString($val);
            }

            switch ($searchopt[$ID]["datatype"]) {
                case "datetime":
                    // FIXME `addHaving` should produce same kind of criterion as `addWhere`
                    //  (i.e. using a comparison with `ADDDATE(NOW(), INTERVAL {$val} MONTH)`).
                    if (in_array($searchtype, ['contains', 'notcontains'])) {
                        break;
                    }

                    $force_day = false;
                    if (strstr($val, 'BEGIN') || strstr($val, 'LAST')) {
                        $force_day = true;
                    }

                    $val = Html::computeGenericDateTimeSearch($val, $force_day);

                    $operator = '';
                    switch ($searchtype) {
                        case 'equals':
                            $operator = !$NOT ? '=' : '!=';
                            break;
                        case 'notequals':
                            $operator = !$NOT ? '!=' : '=';
                            break;
                        case 'lessthan':
                            $operator = !$NOT ? '<' : '>';
                            break;
                        case 'morethan':
                            $operator = !$NOT ? '>' : '<';
                            break;
                    }

                    return " {$LINK} ({$DB->quoteName($NAME)} $operator {$DB->quoteValue($val)}) ";
                break;
                case "count":
                case "mio":
                case "number":
                case "integer":
                case "decimal":
                case "timestamp":
                    $val = Sanitizer::decodeHtmlSpecialChars($val); // Decode "<" and ">" operators
                    if (preg_match("/([<>])(=?)[[:space:]]*(-?)[[:space:]]*([0-9]+(.[0-9]+)?)/", $val, $regs)) {
                        if ($NOT) {
                            if ($regs[1] == '<') {
                                $regs[1] = '>';
                            } else {
                                $regs[1] = '<';
                            }
                        }
                        $regs[1] .= $regs[2];
                        return " $LINK (`$NAME` " . $regs[1] . " " . $regs[3] . $regs[4] . " ) ";
                    }

                    if (is_numeric($val)) {
                        if (isset($searchopt[$ID]["width"])) {
                            if (!$NOT) {
                                return " $LINK (`$NAME` < " . (intval($val) + $searchopt[$ID]["width"]) . "
                                        AND `$NAME` > " .
                                           (intval($val) - $searchopt[$ID]["width"]) . ") ";
                            }
                            return " $LINK (`$NAME` > " . (intval($val) + $searchopt[$ID]["width"]) . "
                                     OR `$NAME` < " .
                                        (intval($val) - $searchopt[$ID]["width"]) . " ) ";
                        }
                       // Exact search
                        if (!$NOT) {
                            return " $LINK (`$NAME` = " . (intval($val)) . ") ";
                        }
                        return " $LINK (`$NAME` <> " . (intval($val)) . ") ";
                    }
                    break;
            }
        }

        return self::makeTextCriteria("`$NAME`", $val, $NOT, $LINK);
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
     * @param string $_id order field (Deprecated)
     *
     * @return string ORDER BY query string
     *
     **/
    public static function addOrderBy($itemtype, $sort_fields, $_id = 'ASC')
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

       // BC parameter conversion
        if (!is_array($sort_fields)) {
           // < 10.0.0 parameters
            Toolbox::deprecated('The parameters for Search::addOrderBy have changed to allow sorting by multiple fields. Please update your calling code.');
            $sort_fields = [
                [
                    'searchopt_id' => $sort_fields,
                    'order'        => $_id
                ]
            ];
        }

        $orderby_criteria = [];
        $searchopt = self::getOptions($itemtype);

        foreach ($sort_fields as $sort_field) {
            $ID = $sort_field['searchopt_id'];
            if (isset($searchopt[$ID]['nosort']) && $searchopt[$ID]['nosort']) {
                continue;
            }
            $order = $sort_field['order'] ?? 'ASC';
           // Order security check
            if ($order != 'ASC') {
                $order = 'DESC';
            }

            $criterion = null;

            $table = $searchopt[$ID]["table"];
            $field = $searchopt[$ID]["field"];

            $addtable = '';

            $is_fkey_composite_on_self = getTableNameForForeignKeyField($searchopt[$ID]["linkfield"]) == $table
            && $searchopt[$ID]["linkfield"] != getForeignKeyFieldForTable($table);
            $orig_table = self::getOrigTableName($itemtype);
            if (
                ($is_fkey_composite_on_self || $table != $orig_table)
                && ($searchopt[$ID]["linkfield"] != getForeignKeyFieldForTable($table))
            ) {
                $addtable .= "_" . $searchopt[$ID]["linkfield"];
            }

            if (isset($searchopt[$ID]['joinparams'])) {
                $complexjoin = self::computeComplexJoinID($searchopt[$ID]['joinparams']);

                if (!empty($complexjoin)) {
                    $addtable .= "_" . $complexjoin;
                }
            }

            if (isset($CFG_GLPI["union_search_type"][$itemtype])) {
                $criterion = "`ITEM_{$itemtype}_{$ID}` $order";
            }

           // Plugin can override core definition for its type
            if ($criterion === null && $plug = isPluginItemType($itemtype)) {
                $out = Plugin::doOneHook(
                    $plug['plugin'],
                    'addOrderBy',
                    $itemtype,
                    $ID,
                    $order,
                    "{$itemtype}_{$ID}"
                );
                $out = $out !== null ? trim($out) : null;
                if (!empty($out)) {
                     $out = preg_replace('/^ORDER BY /', '', $out);
                     $criterion = $out;
                }
            }

            if ($criterion === null) {
                switch ($table . "." . $field) {
                   // FIXME Dead case? Can't see any itemtype referencing this table in their search options to be able to get here.
                    case "glpi_auth_tables.name":
                        $user_searchopt = self::getOptions('User');
                        $criterion = "`glpi_users`.`authtype` $order,
                              `glpi_authldaps" . $addtable . "_" .
                         self::computeComplexJoinID($user_searchopt[30]['joinparams']) . "`.
                                 `name` $order,
                              `glpi_authmails" . $addtable . "_" .
                         self::computeComplexJoinID($user_searchopt[31]['joinparams']) . "`.
                                 `name` $order";
                        break;

                    case "glpi_users.name":
                        if ($itemtype != 'User') {
                            if ($_SESSION["glpinames_format"] == User::FIRSTNAME_BEFORE) {
                                $name1 = 'firstname';
                                $name2 = 'realname';
                            } else {
                                $name1 = 'realname';
                                $name2 = 'firstname';
                            }
                            $addaltemail = "";
                            if (
                                in_array($itemtype, ['Ticket', 'Change', 'Problem'])
                                && isset($searchopt[$ID]['joinparams']['beforejoin']['table'])
                                && in_array($searchopt[$ID]['joinparams']['beforejoin']['table'], ['glpi_tickets_users', 'glpi_changes_users', 'glpi_problems_users'])
                            ) { // For tickets_users
                                $ticket_user_table = $searchopt[$ID]['joinparams']['beforejoin']['table'] . "_" .
                                    self::computeComplexJoinID($searchopt[$ID]['joinparams']['beforejoin']['joinparams']);
                                $addaltemail = ",
                                IFNULL(`$ticket_user_table`.`alternative_email`, '')";
                            }
                            if ((isset($searchopt[$ID]["forcegroupby"]) && $searchopt[$ID]["forcegroupby"])) {
                                $criterion = "GROUP_CONCAT(DISTINCT CONCAT(
                                    IFNULL(`$table$addtable`.`$name1`, ''),
                                    IFNULL(`$table$addtable`.`$name2`, ''),
                                    IFNULL(`$table$addtable`.`name`, '')$addaltemail
                                ) ORDER BY CONCAT(
                                    IFNULL(`$table$addtable`.`$name1`, ''),
                                    IFNULL(`$table$addtable`.`$name2`, ''),
                                    IFNULL(`$table$addtable`.`name`, '')$addaltemail) ASC
                                ) $order";
                            } else {
                                $criterion = "CONCAT(
                                    IFNULL(`$table$addtable`.`$name1`, ''),
                                    IFNULL(`$table$addtable`.`$name2`, ''),
                                    IFNULL(`$table$addtable`.`name`, '')$addaltemail
                                ) $order";
                            }
                        } else {
                            $criterion = "`" . $table . $addtable . "`.`name` $order";
                        }
                        break;
                   //FIXME glpi_networkequipments.ip seems like a dead case
                    case "glpi_networkequipments.ip":
                    case "glpi_ipaddresses.name":
                        $criterion = "INET6_ATON(`$table$addtable`.`$field`) $order";
                        break;
                }
            }

           //// Default cases

           // Link with plugin tables
            if ($criterion === null && preg_match("/^glpi_plugin_([a-z0-9]+)/", $table, $matches)) {
                if (count($matches) == 2) {
                    $plug = $matches[1];
                    $out = Plugin::doOneHook(
                        $plug,
                        'addOrderBy',
                        $itemtype,
                        $ID,
                        $order,
                        "{$itemtype}_{$ID}"
                    );
                    $out = $out !== null ? trim($out) : null;
                    if (!empty($out)) {
                           $out = preg_replace('/^ORDER BY /', '', $out);
                           $criterion = $out;
                    }
                }
            }

           // Preformat items
            if ($criterion === null && isset($searchopt[$ID]["datatype"])) {
                switch ($searchopt[$ID]["datatype"]) {
                    case "date_delay":
                        $interval = "MONTH";
                        if (isset($searchopt[$ID]['delayunit'])) {
                            $interval = $searchopt[$ID]['delayunit'];
                        }

                        $add_minus = '';
                        if (isset($searchopt[$ID]["datafields"][3])) {
                            $add_minus = "- `$table$addtable`.`" . $searchopt[$ID]["datafields"][3] . "`";
                        }
                        $criterion = "ADDDATE(`$table$addtable`.`" . $searchopt[$ID]["datafields"][1] . "`,
                                         INTERVAL (`$table$addtable`.`" .
                        $searchopt[$ID]["datafields"][2] . "` $add_minus)
                                         $interval) $order";
                }
            }

            $orderby_criteria[] = $criterion ?? "`ITEM_{$itemtype}_{$ID}` $order";
        }

        if (count($orderby_criteria) === 0) {
            return '';
        }
        return ' ORDER BY ' . implode(', ', $orderby_criteria) . ' ';
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
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $toview = [];
        $item   = null;
        $entity_check = true;

        if ($itemtype != AllAssets::getType()) {
            $item = getItemForItemtype($itemtype);
            $entity_check = $item->isEntityAssign();
        }
       // Add first element (name)
        array_push($toview, 1);

        if (isset($params['as_map']) && $params['as_map'] == 1) {
           // Add location name when map mode
            array_push($toview, ($itemtype == 'Location' ? 1 : ($itemtype == 'Ticket' ? 83 : 3)));
        }

       // Add entity view :
        if (
            Session::isMultiEntitiesMode()
            && $entity_check
            && (isset($CFG_GLPI["union_search_type"][$itemtype])
              || ($item && $item->maybeRecursive())
              || isset($_SESSION['glpiactiveentities']) && (count($_SESSION["glpiactiveentities"]) > 1))
        ) {
            array_push($toview, 80);
        }
        return $toview;
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
        /** @var \DBmysql $DB */
        global $DB;

        $itemtable = self::getOrigTableName($itemtype);
        $item      = null;
        $mayberecursive = false;
        if ($itemtype != AllAssets::getType()) {
            $item           = getItemForItemtype($itemtype);
            $mayberecursive = $item->maybeRecursive();
        }
        $ret = "";
        switch ($itemtype) {
            case 'FieldUnicity':
                $ret = "`glpi_fieldunicities`.`itemtype` AS ITEMTYPE,";
                break;

            default:
               // Plugin can override core definition for its type
                if ($plug = isPluginItemType($itemtype)) {
                    $ret = Plugin::doOneHook(
                        $plug['plugin'],
                        'addDefaultSelect',
                        $itemtype
                    );
                }
        }
        if ($itemtable == 'glpi_entities') {
            $ret .= "`$itemtable`.`id` AS entities_id, '1' AS is_recursive, ";
        } else if ($mayberecursive) {
            if ($item->isField('entities_id')) {
                $ret .= $DB->quoteName("$itemtable.entities_id") . ", ";
            }
            if ($item->isField('is_recursive')) {
                $ret .= $DB->quoteName("$itemtable.is_recursive") . ", ";
            }
        }
        return $ret;
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
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $searchopt   = self::getOptions($itemtype);
        $table       = $searchopt[$ID]["table"];
        $field       = $searchopt[$ID]["field"];
        $addtable    = "";
        $addtable2   = "";
        $NAME        = "ITEM_{$itemtype}_{$ID}";
        $complexjoin = '';

        if (isset($searchopt[$ID]['joinparams'])) {
            $complexjoin = self::computeComplexJoinID($searchopt[$ID]['joinparams']);
        }

        $is_fkey_composite_on_self = getTableNameForForeignKeyField($searchopt[$ID]["linkfield"]) == $table
         && $searchopt[$ID]["linkfield"] != getForeignKeyFieldForTable($table);

        $orig_table = self::getOrigTableName($itemtype);
        if (
            ((($is_fkey_composite_on_self || $table != $orig_table)
            && (!isset($CFG_GLPI["union_search_type"][$itemtype])
                || ($CFG_GLPI["union_search_type"][$itemtype] != $table)))
            || !empty($complexjoin))
            && ($searchopt[$ID]["linkfield"] != getForeignKeyFieldForTable($table))
        ) {
            $addtable .= "_" . $searchopt[$ID]["linkfield"];
        }

        if (!empty($complexjoin)) {
            $addtable .= "_" . $complexjoin;
            $addtable2 .= "_" . $complexjoin;
        }

        $addmeta = "";
        if ($meta) {
           // $NAME = "META";
            if ($meta_type::getTable() != $table) {
                $addmeta = "_" . $meta_type;
                $addtable  .= $addmeta;
                $addtable2 .= $addmeta;
            }
        }

       // Plugin can override core definition for its type
        if ($plug = isPluginItemType($itemtype)) {
            $out = Plugin::doOneHook(
                $plug['plugin'],
                'addSelect',
                $itemtype,
                $ID,
                "{$itemtype}_{$ID}"
            );
            if (!empty($out)) {
                return $out;
            }
        }

        $tocompute      = "`$table$addtable`.`$field`";
        $tocomputeid    = "`$table$addtable`.`id`";

        $tocomputetrans = "IFNULL(`$table" . $addtable . "_trans_" . $field . "`.`value`,'" . self::NULLVALUE . "') ";

        $ADDITONALFIELDS = '';
        if (
            isset($searchopt[$ID]["additionalfields"])
            && count($searchopt[$ID]["additionalfields"])
        ) {
            foreach ($searchopt[$ID]["additionalfields"] as $key) {
                if (
                    $meta
                    || (isset($searchopt[$ID]["forcegroupby"]) && $searchopt[$ID]["forcegroupby"])
                ) {
                    $ADDITONALFIELDS .= " IFNULL(GROUP_CONCAT(DISTINCT CONCAT(IFNULL(`$table$addtable`.`$key`,
                                                                         '" . self::NULLVALUE . "'),
                                                   '" . self::SHORTSEP . "', $tocomputeid)ORDER BY $tocomputeid SEPARATOR '" . self::LONGSEP . "'), '" . self::NULLVALUE . "')
                                    AS `" . $NAME . "_$key`, ";
                } else {
                    $ADDITONALFIELDS .= "`$table$addtable`.`$key` AS `" . $NAME . "_$key`, ";
                }
            }
        }

       // Virtual display no select : only get additional fields
        if (strpos($field, '_virtual') === 0) {
            return $ADDITONALFIELDS;
        }

        switch ($table . "." . $field) {
            case "glpi_users.name":
                if ($itemtype != 'User') {
                    if ((isset($searchopt[$ID]["forcegroupby"]) && $searchopt[$ID]["forcegroupby"])) {
                        $addaltemail = "";
                        if (
                            in_array($itemtype, ['Ticket', 'Change', 'Problem'])
                            && isset($searchopt[$ID]['joinparams']['beforejoin']['table'])
                            && in_array($searchopt[$ID]['joinparams']['beforejoin']['table'], ['glpi_tickets_users', 'glpi_changes_users', 'glpi_problems_users'])
                        ) { // For tickets_users
                             $ticket_user_table
                             = $searchopt[$ID]['joinparams']['beforejoin']['table'] .
                             "_" . self::computeComplexJoinID($searchopt[$ID]['joinparams']['beforejoin']
                                                                   ['joinparams']) . $addmeta;
                               $addaltemail
                              = "GROUP_CONCAT(DISTINCT CONCAT(`$ticket_user_table`.`users_id`, ' ',
                                                        `$ticket_user_table`.`alternative_email`)
                                                        SEPARATOR '" . self::LONGSEP . "') AS `" . $NAME . "_2`, ";
                        }
                        return " GROUP_CONCAT(DISTINCT `$table$addtable`.`id` SEPARATOR '" . self::LONGSEP . "')
                                       AS `" . $NAME . "`,
                           $addaltemail
                           $ADDITONALFIELDS";
                    }
                    return " `$table$addtable`.`$field` AS `" . $NAME . "`,
                        `$table$addtable`.`realname` AS `" . $NAME . "_realname`,
                        `$table$addtable`.`id`  AS `" . $NAME . "_id`,
                        `$table$addtable`.`firstname` AS `" . $NAME . "_firstname`,
                        $ADDITONALFIELDS";
                }
                break;

            case "glpi_softwarelicenses.number":
                if ($meta) {
                    return " FLOOR(SUM(`$table$addtable2`.`$field`)
                              * COUNT(DISTINCT `$table$addtable2`.`id`)
                              / COUNT(`$table$addtable2`.`id`)) AS `" . $NAME . "`,
                        MIN(`$table$addtable2`.`$field`) AS `" . $NAME . "_min`,
                         $ADDITONALFIELDS";
                } else {
                    return " FLOOR(SUM(`$table$addtable`.`$field`)
                              * COUNT(DISTINCT `$table$addtable`.`id`)
                              / COUNT(`$table$addtable`.`id`)) AS `" . $NAME . "`,
                        MIN(`$table$addtable`.`$field`) AS `" . $NAME . "_min`,
                         $ADDITONALFIELDS";
                }

            case "glpi_profiles.name":
                if (
                    ($itemtype == 'User')
                    && ($ID == 20)
                ) {
                    $addtable2 = '';
                    if ($meta) {
                        $addtable2 = "_" . $meta_type;
                    }
                    return " GROUP_CONCAT(
                        DISTINCT CONCAT(
                                `$table$addtable` . `$field`, '" . self::SHORTSEP .
                                "', `glpi_profiles_users$addtable2`.`entities_id`, '" . self::SHORTSEP .
                                "', `glpi_profiles_users$addtable2`.`is_recursive`, '" . self::SHORTSEP .
                                "', `glpi_profiles_users$addtable2`.`is_dynamic`) SEPARATOR '" . self::LONGSEP .
                        "' ) AS `" . $NAME . "`, $ADDITONALFIELDS";
                }
                break;

            case "glpi_entities.completename":
                if (
                    ($itemtype == 'User')
                    && ($ID == 80)
                ) {
                    $addtable2 = '';
                    if ($meta) {
                        $addtable2 = "_" . $meta_type;
                    }
                    return " GROUP_CONCAT(
                        DISTINCT CONCAT(
                                `$table$addtable` . `completename`, '" . self::SHORTSEP .
                                "', `glpi_profiles_users$addtable2`.`profiles_id`, '" . self::SHORTSEP .
                                "', `glpi_profiles_users$addtable2`.`is_recursive`, '" . self::SHORTSEP .
                                "', `glpi_profiles_users$addtable2`.`is_dynamic`) SEPARATOR '" . self::LONGSEP .
                        "' ) AS `" . $NAME . "`, $ADDITONALFIELDS";
                }
                break;

            case "glpi_auth_tables.name":
                $user_searchopt = self::getOptions('User');
                return " `glpi_users`.`authtype` AS `" . $NAME . "`,
                     `glpi_users`.`auths_id` AS `" . $NAME . "_auths_id`,
                     `glpi_authldaps" . $addtable . "_" .
                           self::computeComplexJoinID($user_searchopt[30]['joinparams']) . $addmeta . "`.`$field`
                              AS `" . $NAME . "_" . $ID . "_ldapname`,
                     `glpi_authmails" . $addtable . "_" .
                           self::computeComplexJoinID($user_searchopt[31]['joinparams']) . $addmeta . "`.`$field`
                              AS `" . $NAME . "_mailname`,
                     $ADDITONALFIELDS";

            case "glpi_softwareversions.name":
                if ($meta && ($meta_type == 'Software')) {
                    return " GROUP_CONCAT(DISTINCT CONCAT(`glpi_softwares`.`name`, ' - ',
                                                     `$table$addtable2`.`$field`, '" . self::SHORTSEP . "',
                                                     `$table$addtable2`.`id`) SEPARATOR '" . self::LONGSEP . "')
                                    AS `" . $NAME . "`,
                        $ADDITONALFIELDS";
                }
                break;

            case "glpi_softwareversions.comment":
                if ($meta && ($meta_type == 'Software')) {
                    return " GROUP_CONCAT(DISTINCT CONCAT(`glpi_softwares`.`name`, ' - ',
                                                     `$table$addtable2`.`$field`,'" . self::SHORTSEP . "',
                                                     `$table$addtable2`.`id`) SEPARATOR '" . self::LONGSEP . "')
                                    AS `" . $NAME . "`,
                        $ADDITONALFIELDS";
                }
                return " GROUP_CONCAT(DISTINCT CONCAT(`$table$addtable`.`name`, ' - ',
                                                  `$table$addtable`.`$field`, '" . self::SHORTSEP . "',
                                                  `$table$addtable`.`id`) SEPARATOR '" . self::LONGSEP . "')
                                 AS `" . $NAME . "`,
                     $ADDITONALFIELDS";

            case "glpi_states.name":
                if ($meta && ($meta_type == 'Software')) {
                    return " GROUP_CONCAT(DISTINCT CONCAT(`glpi_softwares`.`name`, ' - ',
                                                     `glpi_softwareversions$addtable`.`name`, ' - ',
                                                     `$table$addtable2`.`$field`, '" . self::SHORTSEP . "',
                                                     `$table$addtable2`.`id`) SEPARATOR '" . self::LONGSEP . "')
                                     AS `" . $NAME . "`,
                        $ADDITONALFIELDS";
                } else if ($itemtype == 'Software') {
                    return " GROUP_CONCAT(DISTINCT CONCAT(`glpi_softwareversions`.`name`, ' - ',
                                                     `$table$addtable`.`$field`,'" . self::SHORTSEP . "',
                                                     `$table$addtable`.`id`) SEPARATOR '" . self::LONGSEP . "')
                                    AS `" . $NAME . "`,
                        $ADDITONALFIELDS";
                }
                break;

            case "glpi_itilfollowups.content":
            case "glpi_tickettasks.content":
            case "glpi_changetasks.content":
                if (is_subclass_of($itemtype, "CommonITILObject")) {
                   // force ordering by date desc
                    return " GROUP_CONCAT(
                  DISTINCT CONCAT(
                     IFNULL($tocompute, '" . self::NULLVALUE . "'),
                     '" . self::SHORTSEP . "',
                     $tocomputeid
                  )
                  ORDER BY `$table$addtable`.`date` DESC
                  SEPARATOR '" . self::LONGSEP . "'
               ) AS `" . $NAME . "`, $ADDITONALFIELDS";
                }
                break;

            default:
                break;
        }

       //// Default cases
       // Link with plugin tables
        if (preg_match("/^glpi_plugin_([a-z0-9]+)/", $table, $matches)) {
            if (count($matches) == 2) {
                $plug     = $matches[1];
                $out = Plugin::doOneHook(
                    $plug,
                    'addSelect',
                    $itemtype,
                    $ID,
                    "{$itemtype}_{$ID}"
                );
                if (!empty($out)) {
                     return $out;
                }
            }
        }

        if (isset($searchopt[$ID]["computation"])) {
            $tocompute = $searchopt[$ID]["computation"];
            $tocompute = str_replace($DB->quoteName('TABLE'), 'TABLE', $tocompute);
            $tocompute = str_replace("TABLE", $DB->quoteName("$table$addtable"), $tocompute);
        }
       // Preformat items
        if (isset($searchopt[$ID]["datatype"])) {
            switch ($searchopt[$ID]["datatype"]) {
                case "count":
                    return " COUNT(DISTINCT `$table$addtable`.`$field`) AS `" . $NAME . "`,
                     $ADDITONALFIELDS";

                case "date_delay":
                    $interval = "MONTH";
                    if (isset($searchopt[$ID]['delayunit'])) {
                        $interval = $searchopt[$ID]['delayunit'];
                    }

                    $add_minus = '';
                    if (isset($searchopt[$ID]["datafields"][3])) {
                        $add_minus = "-`$table$addtable`.`" . $searchopt[$ID]["datafields"][3] . "`";
                    }
                    if (
                        $meta
                        || (isset($searchopt[$ID]["forcegroupby"]) && $searchopt[$ID]["forcegroupby"])
                    ) {
                        return " GROUP_CONCAT(DISTINCT ADDDATE(`$table$addtable`.`" .
                                                            $searchopt[$ID]["datafields"][1] . "`,
                                                         INTERVAL (`$table$addtable`.`" .
                                                                    $searchopt[$ID]["datafields"][2] .
                                                                    "` $add_minus) $interval)
                                         SEPARATOR '" . self::LONGSEP . "') AS `" . $NAME . "`,
                           $ADDITONALFIELDS";
                    }
                    return "ADDDATE(`$table$addtable`.`" . $searchopt[$ID]["datafields"][1] . "`,
                               INTERVAL (`$table$addtable`.`" . $searchopt[$ID]["datafields"][2] .
                                          "` $add_minus) $interval) AS `" . $NAME . "`,
                       $ADDITONALFIELDS";

                case "itemlink":
                    if (
                        $meta
                        || (isset($searchopt[$ID]["forcegroupby"]) && $searchopt[$ID]["forcegroupby"])
                    ) {
                        $TRANS = '';
                        if (Session::haveTranslations(getItemTypeForTable($table), $field)) {
                            $TRANS = "GROUP_CONCAT(DISTINCT CONCAT(IFNULL($tocomputetrans, '" . self::NULLVALUE . "'),
                                                             '" . self::SHORTSEP . "',$tocomputeid) ORDER BY $tocomputeid
                                             SEPARATOR '" . self::LONGSEP . "')
                                     AS `" . $NAME . "_trans_" . $field . "`, ";
                        }

                        return " GROUP_CONCAT(DISTINCT CONCAT($tocompute, '" . self::SHORTSEP . "' ,
                                                        `$table$addtable`.`id`) ORDER BY `$table$addtable`.`id`
                                        SEPARATOR '" . self::LONGSEP . "') AS `" . $NAME . "`,
                           $TRANS
                           $ADDITONALFIELDS";
                    }
                    return " $tocompute AS `" . $NAME . "`,
                        `$table$addtable`.`id` AS `" . $NAME . "_id`,
                        $ADDITONALFIELDS";
            }
        }

       // Default case
        if (
            $meta
            || (isset($searchopt[$ID]["forcegroupby"]) && $searchopt[$ID]["forcegroupby"]
              && (!isset($searchopt[$ID]["computation"])
                  || isset($searchopt[$ID]["computationgroupby"])
                     && $searchopt[$ID]["computationgroupby"]))
        ) { // Not specific computation
            $TRANS = '';
            if (Session::haveTranslations(getItemTypeForTable($table), $field)) {
                $TRANS = "GROUP_CONCAT(DISTINCT CONCAT(IFNULL($tocomputetrans, '" . self::NULLVALUE . "'),
                                                   '" . self::SHORTSEP . "',$tocomputeid) ORDER BY $tocomputeid SEPARATOR '" . self::LONGSEP . "')
                                  AS `" . $NAME . "_trans_" . $field . "`, ";
            }
            return " GROUP_CONCAT(DISTINCT CONCAT(IFNULL($tocompute, '" . self::NULLVALUE . "'),
                                               '" . self::SHORTSEP . "',$tocomputeid) ORDER BY $tocomputeid SEPARATOR '" . self::LONGSEP . "')
                              AS `" . $NAME . "`,
                  $TRANS
                  $ADDITONALFIELDS";
        }
        $TRANS = '';
        if (Session::haveTranslations(getItemTypeForTable($table), $field)) {
            $TRANS = $tocomputetrans . " AS `" . $NAME . "_trans_" . $field . "`, ";
        }
        return "$tocompute AS `" . $NAME . "`, $TRANS $ADDITONALFIELDS";
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
        $condition = '';

        switch ($itemtype) {
            case 'Reservation':
                $condition = getEntitiesRestrictRequest("", ReservationItem::getTable(), '', '', true);
                break;

            case 'Reminder':
                $condition = Reminder::addVisibilityRestrict();
                break;

            case 'RSSFeed':
                $condition = RSSFeed::addVisibilityRestrict();
                break;

            case 'Notification':
                if (!Config::canView()) {
                    $condition = " `glpi_notifications`.`itemtype` NOT IN ('CronTask', 'DBConnection') ";
                }
                break;

           // No link
            case 'User':
               // View all entities
                if (!Session::canViewAllEntities()) {
                    $condition = getEntitiesRestrictRequest("", "glpi_profiles_users", '', '', true);
                }
                break;

            case 'ProjectTask':
                $condition  = '';
                $teamtable  = 'glpi_projecttaskteams';
                $condition .= "`glpi_projects`.`is_template` = 0";
                $condition .= " AND ((`$teamtable`.`itemtype` = 'User'
                             AND `$teamtable`.`items_id` = '" . Session::getLoginUserID() . "')";
                if (count($_SESSION['glpigroups'])) {
                    $condition .= " OR (`$teamtable`.`itemtype` = 'Group'
                                    AND `$teamtable`.`items_id`
                                       IN (" . implode(",", $_SESSION['glpigroups']) . "))";
                }
                $condition .= ") ";
                break;

            case 'Project':
                $condition = '';
                if (!Session::haveRight("project", Project::READALL)) {
                    $teamtable  = 'glpi_projectteams';
                    $condition .= "(`glpi_projects`.users_id = '" . Session::getLoginUserID() . "'
                               OR (`$teamtable`.`itemtype` = 'User'
                                   AND `$teamtable`.`items_id` = '" . Session::getLoginUserID() . "')";
                    if (count($_SESSION['glpigroups'])) {
                        $condition .= " OR (`glpi_projects`.`groups_id`
                                       IN (" . implode(",", $_SESSION['glpigroups']) . "))";
                        $condition .= " OR (`$teamtable`.`itemtype` = 'Group'
                                      AND `$teamtable`.`items_id`
                                          IN (" . implode(",", $_SESSION['glpigroups']) . "))";
                    }
                    $condition .= ") ";
                }
                break;

            case 'Ticket':
               // Same structure in addDefaultJoin
                $condition = '';
                if (!Session::haveRight("ticket", Ticket::READALL)) {
                    $searchopt
                    = self::getOptions($itemtype);
                    $requester_table
                    = '`glpi_tickets_users_' .
                     self::computeComplexJoinID($searchopt[4]['joinparams']['beforejoin']
                                                          ['joinparams']) . '`';
                    $requestergroup_table
                     = '`glpi_groups_tickets_' .
                     self::computeComplexJoinID($searchopt[71]['joinparams']['beforejoin']
                                                          ['joinparams']) . '`';

                    $assign_table
                     = '`glpi_tickets_users_' .
                     self::computeComplexJoinID($searchopt[5]['joinparams']['beforejoin']
                                                          ['joinparams']) . '`';
                    $assigngroup_table
                     = '`glpi_groups_tickets_' .
                     self::computeComplexJoinID($searchopt[8]['joinparams']['beforejoin']
                                                          ['joinparams']) . '`';

                    $observer_table
                     = '`glpi_tickets_users_' .
                     self::computeComplexJoinID($searchopt[66]['joinparams']['beforejoin']
                                                          ['joinparams']) . '`';
                    $observergroup_table
                     = '`glpi_groups_tickets_' .
                     self::computeComplexJoinID($searchopt[65]['joinparams']['beforejoin']
                                                          ['joinparams']) . '`';

                    $condition = "(";

                    if (Session::haveRight("ticket", Ticket::READMY)) {
                          $condition .= " $requester_table.users_id = '" . Session::getLoginUserID() . "'
                                    OR $observer_table.users_id = '" . Session::getLoginUserID() . "'
                                    OR `glpi_tickets`.`users_id_recipient` = '" . Session::getLoginUserID() . "'";
                    } else {
                        $condition .= "0=1";
                    }

                    if (Session::haveRight("ticket", Ticket::READGROUP)) {
                        if (count($_SESSION['glpigroups'])) {
                            $condition .= " OR $requestergroup_table.`groups_id`
                                             IN (" . implode(",", $_SESSION['glpigroups']) . ")";
                            $condition .= " OR $observergroup_table.`groups_id`
                                             IN (" . implode(",", $_SESSION['glpigroups']) . ")";
                        }
                    }

                    if (Session::haveRight("ticket", Ticket::OWN)) {// Can own ticket : show assign to me
                        $condition .= " OR $assign_table.users_id = '" . Session::getLoginUserID() . "' ";
                    }

                    if (Session::haveRight("ticket", Ticket::READASSIGN)) { // assign to me
                        $condition .= " OR $assign_table.`users_id` = '" . Session::getLoginUserID() . "'";
                        if (count($_SESSION['glpigroups'])) {
                            $condition .= " OR $assigngroup_table.`groups_id`
                                             IN (" . implode(",", $_SESSION['glpigroups']) . ")";
                        }
                        if (Session::haveRight('ticket', Ticket::ASSIGN)) {
                            $condition .= " OR `glpi_tickets`.`status`='" . CommonITILObject::INCOMING . "'";
                        }
                    }

                    if (
                        Session::haveRightsOr(
                            'ticketvalidation',
                            [TicketValidation::VALIDATEINCIDENT,
                                TicketValidation::VALIDATEREQUEST
                            ]
                        )
                    ) {
                        $condition .= " OR `glpi_ticketvalidations`.`users_id_validate`
                                          = '" . Session::getLoginUserID() . "'";
                    }
                    $condition .= ") ";
                }
                break;

            case 'Change':
            case 'Problem':
                if ($itemtype == 'Change') {
                    $right       = 'change';
                    $table       = 'changes';
                    $groupetable = "`glpi_changes_groups_";
                } else if ($itemtype == 'Problem') {
                    $right       = 'problem';
                    $table       = 'problems';
                    $groupetable = "`glpi_groups_problems_";
                }
               // Same structure in addDefaultJoin
                $condition = '';
                if (!Session::haveRight("$right", $itemtype::READALL)) {
                    $searchopt       = self::getOptions($itemtype);
                    if (Session::haveRight("$right", $itemtype::READMY)) {
                        $requester_table      = '`glpi_' . $table . '_users_' .
                                          self::computeComplexJoinID($searchopt[4]['joinparams']
                                                                     ['beforejoin']['joinparams']) . '`';
                        $requestergroup_table = $groupetable .
                                          self::computeComplexJoinID($searchopt[71]['joinparams']
                                                                     ['beforejoin']['joinparams']) . '`';

                        $observer_table       = '`glpi_' . $table . '_users_' .
                                          self::computeComplexJoinID($searchopt[66]['joinparams']
                                                                     ['beforejoin']['joinparams']) . '`';
                        $observergroup_table  = $groupetable .
                                          self::computeComplexJoinID($searchopt[65]['joinparams']
                                                                    ['beforejoin']['joinparams']) . '`';

                        $assign_table         = '`glpi_' . $table . '_users_' .
                                          self::computeComplexJoinID($searchopt[5]['joinparams']
                                                                     ['beforejoin']['joinparams']) . '`';
                        $assigngroup_table    = $groupetable .
                                          self::computeComplexJoinID($searchopt[8]['joinparams']
                                                                     ['beforejoin']['joinparams']) . '`';
                    }
                    $condition = "(";

                    if (Session::haveRight("$right", $itemtype::READMY)) {
                        $condition .= " $requester_table.users_id = '" . Session::getLoginUserID() . "'
                                 OR $observer_table.users_id = '" . Session::getLoginUserID() . "'
                                 OR $assign_table.users_id = '" . Session::getLoginUserID() . "'
                                 OR `glpi_" . $table . "`.`users_id_recipient` = '" . Session::getLoginUserID() . "'";
                        if (count($_SESSION['glpigroups'])) {
                            $my_groups_keys = "'" . implode("','", $_SESSION['glpigroups']) . "'";
                            $condition .= " OR $requestergroup_table.groups_id IN ($my_groups_keys)
                                 OR $observergroup_table.groups_id IN ($my_groups_keys)
                                 OR $assigngroup_table.groups_id IN ($my_groups_keys)";
                        }
                    } else {
                        $condition .= "0=1";
                    }

                    $condition .= ") ";
                }
                break;

            case 'Config':
                $availableContexts = array_merge(['core', 'inventory'], Plugin::getPlugins());
                $availableContexts = implode("', '", $availableContexts);
                $condition = "`context` IN ('$availableContexts')";
                break;

            case 'SavedSearch':
                $condition = SavedSearch::addVisibilityRestrict();
                break;

            case 'TicketTask':
               // Filter on is_private
                $allowed_is_private = [];
                if (Session::haveRight(TicketTask::$rightname, CommonITILTask::SEEPRIVATE)) {
                    $allowed_is_private[] = 1;
                }
                if (Session::haveRight(TicketTask::$rightname, CommonITILTask::SEEPUBLIC)) {
                    $allowed_is_private[] = 0;
                }

               // If the user can't see public and private
                if (!count($allowed_is_private)) {
                    $condition = "0 = 1";
                    break;
                }

                $in = "IN ('" . implode("','", $allowed_is_private) . "')";
                $condition = "(`glpi_tickettasks`.`is_private` $in ";

               // Check for assigned or created tasks
                $condition .= "OR `glpi_tickettasks`.`users_id` = " . Session::getLoginUserID() . " ";
                $condition .= "OR `glpi_tickettasks`.`users_id_tech` = " . Session::getLoginUserID() . " ";

               // Check for parent item visibility unless the user can see all the
               // possible parents
                if (!Session::haveRight('ticket', Ticket::READALL)) {
                    $condition .= "AND " . TicketTask::buildParentCondition();
                }

                $condition .= ")";

                break;

            case 'ITILFollowup':
               // Filter on is_private
                $allowed_is_private = [];
                if (Session::haveRight(ITILFollowup::$rightname, ITILFollowup::SEEPRIVATE)) {
                    $allowed_is_private[] = 1;
                }
                if (Session::haveRight(ITILFollowup::$rightname, ITILFollowup::SEEPUBLIC)) {
                    $allowed_is_private[] = 0;
                }

               // If the user can't see public and private
                if (!count($allowed_is_private)) {
                    $condition = "0 = 1";
                    break;
                }

                // Build base condition using entity restrictions
                // TODO 11.0: use $CFG_GLPI['itil_types']
                $itil_types = [Ticket::class, Change::class, Problem::class];
                $entity_restrictions = [];
                foreach ($itil_types as $itil_itemtype) {
                    $entity_restrictions[] = getEntitiesRestrictRequest(
                        '',
                        $itil_itemtype::getTable() . '_items_id_' . self::computeComplexJoinID([
                            'condition' => "AND REFTABLE.`itemtype` = '$itil_itemtype'"
                        ]),
                        'entities_id',
                        ''
                    );
                }
                $condition = "(" . implode(" OR ", $entity_restrictions) . ")";

                $in = "IN ('" . implode("','", $allowed_is_private) . "')";
                $condition .= " AND (`glpi_itilfollowups`.`is_private` $in ";

               // Now filter on parent item visiblity
                $condition .= "AND (";

               // Filter for "ticket" parents
                $condition .= ITILFollowup::buildParentCondition(\Ticket::getType());
                $condition .= "OR ";

               // Filter for "change" parents
                $condition .= ITILFollowup::buildParentCondition(
                    \Change::getType(),
                    'changes_id',
                    "glpi_changes_users",
                    "glpi_changes_groups"
                );
                $condition .= "OR ";

               // Fitler for "problem" parents
                $condition .= ITILFollowup::buildParentCondition(
                    \Problem::getType(),
                    'problems_id',
                    "glpi_problems_users",
                    "glpi_groups_problems"
                );
                $condition .= "))";

                break;

            case 'PlanningExternalEvent':
                $condition .= PlanningExternalEvent::addVisibilityRestrict();
                break;

            default:
               // Plugin can override core definition for its type
                if ($plug = isPluginItemType($itemtype)) {
                    $condition = Plugin::doOneHook($plug['plugin'], 'addDefaultWhere', $itemtype);
                }
                break;
        }

       /* Hook to restrict user right on current itemtype */
        list($itemtype, $condition) = Plugin::doHookFunction('add_default_where', [$itemtype, $condition]);
        return $condition;
    }

    /**
     * Generic Function to add where to a request
     *
     * @param string  $link         Link string
     * @param boolean $nott         Is it a negative search ?
     * @param string  $itemtype     Item type
     * @param integer $ID           ID of the item to search
     * @param string  $searchtype   Searchtype used (equals or contains)
     * @param string  $val          Item num in the request
     * @param integer $meta         Is a meta search (meta=2 in search.class.php) (default 0)
     *
     * @return false|string Where string
     **/
    public static function addWhere($link, $nott, $itemtype, $ID, $searchtype, $val, $meta = 0)
    {

        /** @var \DBmysql $DB */
        global $DB;

        $searchopt = self::getOptions($itemtype);
        if (!isset($searchopt[$ID]['table'])) {
            return false;
        }
        $table     = $searchopt[$ID]["table"];
        $field     = $searchopt[$ID]["field"];

        $inittable = $table;
        $addtable  = '';
        $is_fkey_composite_on_self = getTableNameForForeignKeyField($searchopt[$ID]["linkfield"]) == $table
         && $searchopt[$ID]["linkfield"] != getForeignKeyFieldForTable($table);
        $orig_table = self::getOrigTableName($itemtype);
        if (
            ($table != 'asset_types')
            && ($is_fkey_composite_on_self || $table != $orig_table)
            && ($searchopt[$ID]["linkfield"] != getForeignKeyFieldForTable($table))
        ) {
            $addtable = "_" . $searchopt[$ID]["linkfield"];
            $table   .= $addtable;
        }

        if (isset($searchopt[$ID]['joinparams'])) {
            $complexjoin = self::computeComplexJoinID($searchopt[$ID]['joinparams']);

            if (!empty($complexjoin)) {
                $table .= "_" . $complexjoin;
            }
        }

        $addmeta = "";
        if (
            $meta
            && ($itemtype::getTable() != $inittable)
        ) {
            $addmeta = "_" . $itemtype;
            $table .= $addmeta;
        }

       // Hack to allow search by ID on every sub-table
        if (preg_match('/^\$\$\$\$([0-9]+)$/', $val, $regs)) {
            return $link . " (`$table`.`id` " . ($nott ? "<>" : "=") . $regs[1] . " " .
                         (($regs[1] == 0) ? " OR `$table`.`id` IS NULL" : '') . ") ";
        }

       // Preparse value
        if (isset($searchopt[$ID]["datatype"])) {
            switch ($searchopt[$ID]["datatype"]) {
                case "datetime":
                case "date":
                case "date_delay":
                    $force_day = true;
                    if (
                        $searchopt[$ID]["datatype"] == 'datetime'
                        && !(strstr($val, 'BEGIN') || strstr($val, 'LAST') || strstr($val, 'DAY'))
                    ) {
                        $force_day = false;
                    }

                    $val = Html::computeGenericDateTimeSearch($val, $force_day);

                    break;
            }
        }

        $SEARCH = "";

        // Is the current criteria on a linked children item ? (e.g. search
        // option 65 for CommonITILObjects)
        // These search options will need an additionnal subquery in their WHERE
        // clause to ensure accurate results
        // See https://github.com/glpi-project/glpi/pull/13684 for detailed examples
        $should_use_subquery = $searchopt[$ID]["use_subquery"] ?? false;

        // Default mode for most search types that use a subquery
        $use_subquery_on_id_search = false;

        // Special case for "contains" or "not contains" search type
        $use_subquery_on_text_search = false;

        // Special case when searching for an user (need to compare with login, firstname, ...)
        $subquery_specific_username = false;
        $subquery_specific_username_firstname_real_name = '';
        $subquery_specific_username_anonymous = '';

        // The subquery operator will be "IN" or "NOT IN" depending on the context and criteria
        $subquery_operator = "";

        switch ($searchtype) {
            case "notcontains":
                $nott = !$nott;
               //negated, use contains case
            case "contains":
                // FIXME
                // `field LIKE '%test%'` condition is not supposed to be relevant, and can sometimes result in SQL performances issues/warnings/errors,
                // or at least to unexpected results, when following datatype are used:
                //  - integer
                //  - number
                //  - decimal
                //  - count
                //  - mio
                //  - percentage
                //  - timestamp
                //  - datetime
                //  - date_delay
                //  - mac
                //  - color
                //  - language
                // Values should be filtered to accept only valid pattern according to given datatype.

                if (isset($searchopt[$ID]["datatype"]) && ($searchopt[$ID]["datatype"] === 'decimal')) {
                    $matches = [];
                    if (preg_match('/^(\d+.?\d?)/', $val, $matches)) {
                        $val = $matches[1];
                        if (!str_contains($val, '.')) {
                            $val .= '.';
                        }
                    }
                }

                // To search for '&' in rich text
                if (
                    (($searchopt[$ID]['datatype'] ?? null) === 'text')
                    && (($searchopt[$ID]['htmltext'] ?? null) === true)
                ) {
                    $val = str_replace('&#38;', '38;amp;', $val);
                }
                if ($should_use_subquery) {
                    // Subquery will be needed to get accurate results
                    $use_subquery_on_text_search = true;

                    // Potential negation will be handled by the subquery operator
                    $SEARCH = self::makeTextSearch($val, false);
                    $subquery_operator = $nott ? "NOT IN" : "IN";
                } else {
                    $SEARCH = self::makeTextSearch($val, $nott);
                }
                break;

            case "equals":
                if ($should_use_subquery) {
                    // Subquery will be needed to get accurate results
                    $use_subquery_on_id_search = true;

                    // Potential negation will be handled by the subquery operator
                    $SEARCH = " = " . DBmysql::quoteValue($val);
                    $subquery_operator = $nott ? "NOT IN" : "IN";
                } else {
                    if ($nott) {
                        $SEARCH = " <> " . DBmysql::quoteValue($val);
                    } else {
                        $SEARCH = " = " . DBmysql::quoteValue($val);
                    }
                }
                break;

            case "notequals":
                if ($should_use_subquery) {
                    // Subquery will be needed to get accurate results
                    $use_subquery_on_id_search = true;

                    // Potential negation will be handled by the subquery operator
                    $SEARCH = " = " . DBmysql::quoteValue($val);
                    $subquery_operator = $nott ? "IN" : "NOT IN";
                } else {
                    if ($nott) {
                        $SEARCH = " = " . DBmysql::quoteValue($val);
                    } else {
                        $SEARCH = " <> " . DBmysql::quoteValue($val);
                    }
                }

                break;

            case "under":
                // Sometimes $val is not numeric (mygroups)
                // In this case we must set an invalid value and let the related
                // specific code handle in later on
                $sons = is_numeric($val) ? implode("','", getSonsOf($inittable, $val)) : 'not yet set';
                if ($should_use_subquery) {
                    // Subquery will be needed to get accurate results
                    $use_subquery_on_id_search = true;

                    // // Potential negation will be handled by the subquery operator
                    $SEARCH = " IN ('$sons')";
                    $subquery_operator = $nott ? "NOT IN" : "IN";
                } else {
                    if ($nott) {
                        $SEARCH = " NOT IN ('$sons')";
                    } else {
                        $SEARCH = " IN ('$sons')";
                    }
                }
                break;

            case "notunder":
                // Sometimes $val is not numeric (mygroups)
                // In this case we must set an invalid value and let the related
                // specific code handle in later on
                $sons = is_numeric($val) ? implode("','", getSonsOf($inittable, $val)) : 'not yet set';
                if ($should_use_subquery) {
                    // Subquery will be needed to get accurate results
                    $use_subquery_on_id_search = true;

                    // Potential negation will be handled by the subquery operator
                    $SEARCH = " IN ('$sons')";
                    $subquery_operator = $nott ? "IN" : "NOT IN";
                } else {
                    if ($nott) {
                        $SEARCH = " IN ('$sons')";
                    } else {
                        $SEARCH = " NOT IN ('$sons')";
                    }
                }
                break;
        }

       //Check in current item if a specific where is defined
        if (method_exists($itemtype, 'addWhere')) {
            $out = $itemtype::addWhere($link, $nott, $itemtype, $ID, $searchtype, $val);
            if (!empty($out)) {
                return $out;
            }
        }

       // Plugin can override core definition for its type
        if ($plug = isPluginItemType($itemtype)) {
            $out = Plugin::doOneHook(
                $plug['plugin'],
                'addWhere',
                $link,
                $nott,
                $itemtype,
                $ID,
                $val,
                $searchtype
            );
            if (!empty($out)) {
                return $out;
            }
        }

        switch ($inittable . "." . $field) {
           // case "glpi_users_validation.name" :

            case "glpi_users.name":
                if ($val === 'myself') {
                    switch ($searchtype) {
                        case 'equals':
                            $SEARCH = " = " . $DB->quoteValue($_SESSION['glpiID']) . " ";
                            break;

                        case 'notequals':
                            if ($use_subquery_on_id_search) {
                                // Potential negation will be handled by the subquery operator
                                $SEARCH = " = " . $DB->quoteValue($_SESSION['glpiID']) . " ";
                            } else {
                                $SEARCH = " <> " . $DB->quoteValue($_SESSION['glpiID']) . " ";
                            }
                            break;
                    }
                }

                if ($itemtype == 'User') { // glpi_users case / not link table
                    if (in_array($searchtype, ['equals', 'notequals'])) {
                        $search_str = "`$table`.`id`" . $SEARCH;

                        if ($searchtype == 'notequals') {
                            $nott = !$nott;
                        }

                        // Add NULL if $val = 0 and not negative search
                        // Or negative search on real value
                        if ((!$nott && ($val == 0)) || ($nott && ($val != 0))) {
                            $search_str .= " OR `$table`.`id` IS NULL";
                        }

                        return " $link ($search_str)";
                    }
                    return self::makeTextCriteria("`$table`.`$field`", $val, $nott, $link);
                }
                if ($_SESSION["glpinames_format"] == User::FIRSTNAME_BEFORE) {
                    $name1 = 'firstname';
                    $name2 = 'realname';
                } else {
                    $name1 = 'realname';
                    $name2 = 'firstname';
                }

                if (in_array($searchtype, ['equals', 'notequals'])) {
                    // Seems to be obsolete code that is no longer needed
                    // We still want to break to get out of this specific section
                    break;
                    // return " $link (`$table`.`id`" . $SEARCH .
                    //            (($val == 0) ? " OR `$table`.`id` IS" .
                    //                (($searchtype == "notequals") ? " NOT" : "") . " NULL" : '') . ') ';
                }
                $toadd   = '';

                $tmplink = 'OR';
                if ($nott) {
                    $tmplink = 'AND';
                }

                if (is_a($itemtype, CommonITILObject::class, true)) {
                    if (
                        isset($searchopt[$ID]["joinparams"]["beforejoin"]["table"])
                        && isset($searchopt[$ID]["joinparams"]["beforejoin"]["joinparams"])
                        && in_array($searchopt[$ID]['joinparams']['beforejoin']['table'], ['glpi_tickets_users', 'glpi_changes_users', 'glpi_problems_users'])
                    ) {
                        $bj        = $searchopt[$ID]["joinparams"]["beforejoin"];
                        $linktable = $bj['table'] . '_' . self::computeComplexJoinID($bj['joinparams']) . $addmeta;
                       //$toadd     = "`$linktable`.`alternative_email` $SEARCH $tmplink ";
                        $toadd     = self::makeTextCriteria(
                            "`$linktable`.`alternative_email`",
                            $val,
                            $nott,
                            $tmplink
                        );
                        // TODO: Should be deleted on next major, same as doing "Is -----"
                        // No reason to maiting a specific code + syntax for the same thing
                        if ($val == '^$') {
                             return $link . " ((`$linktable`.`users_id` IS NULL)
                            OR `$linktable`.`alternative_email` IS NULL)";
                        }
                    }
                }
                $toadd2 = '';
                if (
                    $nott
                    && ($val != 'NULL') && ($val != 'null')
                ) {
                    $toadd2 = " OR `$table`.`$field` IS NULL";
                }

                if ($use_subquery_on_text_search) {
                    $subquery_specific_username = true;
                    $subquery_specific_username_firstname_real_name = " OR `$name1` $SEARCH "
                        . "OR `$name2` $SEARCH "
                        . "OR CONCAT(`$name1`, ' ', `$name2`) $SEARCH";
                    $subquery_specific_username_anonymous = self::makeTextCriteria(
                        "`alternative_email`",
                        $val,
                        false,
                        'OR'
                    );
                    break;
                } else {
                    return $link . " (((`$table`.`$name1` $SEARCH
                        $tmplink `$table`.`$name2` $SEARCH
                        $tmplink `$table`.`$field` $SEARCH
                        $tmplink CONCAT(`$table`.`$name1`, ' ', `$table`.`$name2`) $SEARCH )
                        $toadd2) $toadd)";
                }

            case "glpi_groups.completename":
                if ($val == 'mygroups') {
                    switch ($searchtype) {
                        case 'equals':
                            $SEARCH = "IN ('" . implode("','", $_SESSION['glpigroups']) . "') ";
                            break;

                        case 'notequals':
                            if ($use_subquery_on_id_search) {
                                // Potential negation will be handled by the subquery operator
                                $SEARCH = "IN ('" . implode("','", $_SESSION['glpigroups']) . "') ";
                            } else {
                                $SEARCH = "NOT IN ('" . implode("','", $_SESSION['glpigroups']) . "') ";
                            }
                            break;

                        case 'under':
                            $groups = $_SESSION['glpigroups'];
                            foreach ($_SESSION['glpigroups'] as $g) {
                                $groups += getSonsOf($inittable, $g);
                            }
                            $groups = array_unique($groups);

                            $SEARCH = "IN ('" . implode("','", $groups) . "') ";
                            break;

                        case 'notunder':
                            $groups = $_SESSION['glpigroups'];
                            foreach ($_SESSION['glpigroups'] as $g) {
                                 $groups += getSonsOf($inittable, $g);
                            }
                            $groups = array_unique($groups);

                            if ($use_subquery_on_id_search) {
                                // Potential negation will be handled by the subquery operator
                                $SEARCH = "IN ('" . implode("','", $groups) . "') ";
                            } else {
                                $SEARCH = "NOT IN ('" . implode("','", $groups) . "') ";
                            }
                            break;
                    }
                }
                break;

            case "glpi_auth_tables.name":
                $user_searchopt = self::getOptions('User');
                $tmplink        = 'OR';
                if ($nott) {
                    $tmplink = 'AND';
                }
                return $link . " (`glpi_authmails" . $addtable . "_" .
                              self::computeComplexJoinID($user_searchopt[31]['joinparams']) . $addmeta . "`.`name`
                           $SEARCH
                           $tmplink `glpi_authldaps" . $addtable . "_" .
                              self::computeComplexJoinID($user_searchopt[30]['joinparams']) . $addmeta . "`.`name`
                           $SEARCH ) ";

            case "glpi_ipaddresses.name":
                $val = Sanitizer::decodeHtmlSpecialChars($val); // Decode "<" and ">" operators
                if (preg_match("/^\s*([<>])([=]*)[[:space:]]*([0-9\.]+)/", $val, $regs)) {
                    if ($nott) {
                        if ($regs[1] == '<') {
                            $regs[1] = '>';
                        } else {
                            $regs[1] = '<';
                        }
                    }
                    $regs[1] .= $regs[2];
                    return $link . " (INET_ATON(`$table`.`$field`) " . $regs[1] . " INET_ATON('" . $regs[3] . "')) ";
                }
                break;

            case "glpi_tickets.status":
            case "glpi_problems.status":
            case "glpi_changes.status":
                $tocheck = [];
                $item = getItemForItemtype($itemtype);
                if ($item instanceof CommonITILObject) {
                    switch ($val) {
                        case 'process':
                            $tocheck = $item->getProcessStatusArray();
                            break;

                        case 'notclosed':
                            $tocheck = $item->getAllStatusArray();
                            foreach ($item->getClosedStatusArray() as $status) {
                                if (isset($tocheck[$status])) {
                                    unset($tocheck[$status]);
                                }
                            }
                            $tocheck = array_keys($tocheck);
                            break;

                        case 'old':
                            $tocheck = array_merge(
                                $item->getSolvedStatusArray(),
                                $item->getClosedStatusArray()
                            );
                            break;

                        case 'notold':
                            $tocheck = $item::getNotSolvedStatusArray();
                            break;

                        case 'all':
                            $tocheck = array_keys($item->getAllStatusArray());
                            break;
                    }

                    if (count($tocheck) == 0) {
                        $statuses = $item->getAllStatusArray();
                        if (isset($statuses[$val])) {
                            $tocheck = [$val];
                        }
                    }
                }

                if (count($tocheck)) {
                    if ($nott) {
                        return $link . " `$table`.`$field` NOT IN ('" . implode("','", $tocheck) . "')";
                    }
                    return $link . " `$table`.`$field` IN ('" . implode("','", $tocheck) . "')";
                }
                break;

            case "glpi_tickets_tickets.tickets_id_1":
                $tmplink = 'OR';
                $compare = '=';
                if ($nott) {
                    $tmplink = 'AND';
                    $compare = '<>';
                }
                $toadd2 = '';
                if (
                    $nott
                    && ($val != 'NULL') && ($val != 'null')
                ) {
                    $toadd2 = " OR `$table`.`$field` IS NULL";
                }

                return $link . " (((`$table`.`tickets_id_1` $compare '$val'
                              $tmplink `$table`.`tickets_id_2` $compare '$val')
                             AND `glpi_tickets`.`id` <> '$val')
                            $toadd2)";

            case "glpi_tickets.priority":
            case "glpi_tickets.impact":
            case "glpi_tickets.urgency":
            case "glpi_problems.priority":
            case "glpi_problems.impact":
            case "glpi_problems.urgency":
            case "glpi_changes.priority":
            case "glpi_changes.impact":
            case "glpi_changes.urgency":
            case "glpi_projects.priority":
                if (is_numeric($val)) {
                    if ($val > 0) {
                        $compare = ($nott ? '<>' : '=');
                        return $link . " `$table`.`$field` $compare '$val'";
                    }
                    if ($val < 0) {
                        $compare = ($nott ? '<' : '>=');
                        return $link . " `$table`.`$field` $compare '" . abs($val) . "'";
                    }
                   // Show all
                    $compare = ($nott ? '<' : '>=');
                    return $link . " `$table`.`$field` $compare '0' ";
                }
                return "";

            case "glpi_tickets.global_validation":
            case "glpi_ticketvalidations.status":
            case "glpi_changes.global_validation":
            case "glpi_changevalidations.status":
                if ($val != 'can' && !is_numeric($val)) {
                    return "";
                }
                $tocheck = [];
                if ($val === 'can') {
                    $tocheck = CommonITILValidation::getCanValidationStatusArray();
                }
                if (count($tocheck) == 0) {
                    $tocheck = [$val];
                }
                if ($nott) {
                    return $link . " `$table`.`$field` NOT IN ('" . implode("','", $tocheck) . "')";
                }
                return $link . " `$table`.`$field` IN ('" . implode("','", $tocheck) . "')";

            case "glpi_notifications.event":
                if (in_array($searchtype, ['equals', 'notequals']) && strpos($val, self::SHORTSEP)) {
                    $not = 'notequals' === $searchtype ? 'NOT' : '';
                    list($itemtype_val, $event_val) = explode(self::SHORTSEP, $val);
                    return " $link $not(`$table`.`event` = '$event_val'
                               AND `$table`.`itemtype` = '$itemtype_val')";
                }
                break;
        }

       //// Default cases

       // Link with plugin tables
        if (preg_match("/^glpi_plugin_([a-z0-9]+)/", $inittable, $matches)) {
            if (count($matches) == 2) {
                $plug     = $matches[1];
                $out = Plugin::doOneHook(
                    $plug,
                    'addWhere',
                    $link,
                    $nott,
                    $itemtype,
                    $ID,
                    $val,
                    $searchtype
                );
                if (!empty($out)) {
                     return $out;
                }
            }
        }

        $tocompute      = "`$table`.`$field`";
        $tocomputetrans = "`" . $table . "_trans_" . $field . "`.`value`";
        if (isset($searchopt[$ID]["computation"])) {
            $tocompute = $searchopt[$ID]["computation"];
            $tocompute = str_replace($DB->quoteName('TABLE'), 'TABLE', $tocompute);
            $tocompute = str_replace("TABLE", $DB->quoteName("$table"), $tocompute);
        }

       // Preformat items
        if (isset($searchopt[$ID]["datatype"])) {
            if ($searchopt[$ID]["datatype"] == "mio") {
                // Parse value as it may contain a few different formats
                $val = Toolbox::getMioSizeFromString($val);
            }

            switch ($searchopt[$ID]["datatype"]) {
                case "itemtypename":
                    if (in_array($searchtype, ['equals', 'notequals'])) {
                        return " $link (`$table`.`$field`" . $SEARCH . ') ';
                    }
                    break;

                case "itemlink":
                    if ($should_use_subquery) {
                        // Condition will be handled by the subquery
                        break;
                    }

                    if (in_array($searchtype, ['equals', 'notequals', 'under', 'notunder'])) {
                        return " $link (`$table`.`id`" . $SEARCH . ') ';
                    }
                    break;

                case "datetime":
                case "date":
                case "date_delay":
                    if ($searchopt[$ID]["datatype"] == 'datetime') {
                       // Specific search for datetime
                        if (in_array($searchtype, ['equals', 'notequals'])) {
                             $val = preg_replace("/:00$/", '', $val);
                             $val = '^' . $val;
                            if ($searchtype == 'notequals') {
                                $nott = !$nott;
                            }
                            return self::makeTextCriteria("`$table`.`$field`", $val, $nott, $link);
                        }
                    }
                    if ($searchtype == 'lessthan') {
                        $val = '<' . $val;
                    }
                    if ($searchtype == 'morethan') {
                        $val = '>' . $val;
                    }
                    $date_computation = null;
                    if ($searchtype) {
                        $date_computation = $tocompute;
                    }
                    if (in_array($searchtype, ["contains", "notcontains"])) {
                        // FIXME `CONVERT` operation should not be necessary if we only allow legitimate date/time chars
                        $default_charset = DBConnection::getDefaultCharset();
                        $date_computation = "CONVERT($date_computation USING {$default_charset})";
                    }
                    $search_unit = ' MONTH ';
                    if (isset($searchopt[$ID]['searchunit'])) {
                        $search_unit = $searchopt[$ID]['searchunit'];
                    }
                    if ($searchopt[$ID]["datatype"] == "date_delay") {
                        $delay_unit = ' MONTH ';
                        if (isset($searchopt[$ID]['delayunit'])) {
                            $delay_unit = $searchopt[$ID]['delayunit'];
                        }
                        $add_minus = '';
                        if (isset($searchopt[$ID]["datafields"][3])) {
                            $add_minus = "-`$table`.`" . $searchopt[$ID]["datafields"][3] . "`";
                        }
                        $date_computation = "ADDDATE(`$table`." . $searchopt[$ID]["datafields"][1] . ",
                                               INTERVAL (`$table`." . $searchopt[$ID]["datafields"][2] . "
                                                         $add_minus)
                                               $delay_unit)";
                    }
                    if (in_array($searchtype, ['equals', 'notequals'])) {
                        return " $link ($date_computation " . $SEARCH . ') ';
                    }
                    $val = Sanitizer::decodeHtmlSpecialChars($val); // Decode "<" and ">" operators
                    if (preg_match("/^\s*([<>])(=?)(.+)$/", $val, $regs)) {
                        $numeric_matches = [];
                        if (preg_match('/^\s*(-?)\s*([0-9]+(.[0-9]+)?)\s*$/', $regs[3], $numeric_matches)) {
                            if ($searchtype === "notcontains") {
                                $nott = !$nott;
                            }
                            if ($nott) {
                                if ($regs[1] == '<') {
                                    $regs[1] = '>';
                                } else {
                                    $regs[1] = '<';
                                }
                            }
                            return $link . " $date_computation " . $regs[1] . $regs[2] . "
                            ADDDATE(NOW(), INTERVAL " . $numeric_matches[1] . $numeric_matches[2] . " $search_unit) ";
                        }
                       // ELSE Reformat date if needed
                        $regs[3] = preg_replace(
                            '@(\d{1,2})(-|/)(\d{1,2})(-|/)(\d{4})@',
                            '\5-\3-\1',
                            $regs[3]
                        );
                        if (preg_match('/[0-9]{2,4}-[0-9]{1,2}-[0-9]{1,2}/', $regs[3])) {
                             $ret = $link;
                            if ($nott) {
                                $ret .= " NOT(";
                            }
                             $ret .= " $date_computation {$regs[1]}{$regs[2]} '{$regs[3]}'";
                            if ($nott) {
                                $ret .= ")";
                            }
                            return $ret;
                        }
                        return "";
                    }
                   // ELSE standard search
                   // Date format modification if needed
                    $val = preg_replace('@(\d{1,2})(-|/)(\d{1,2})(-|/)(\d{4})@', '\5-\3-\1', $val);
                    if ($date_computation) {
                        return self::makeTextCriteria($date_computation, $val, $nott, $link);
                    }
                    return '';

                case "right":
                    if ($searchtype == 'notequals') {
                        $nott = !$nott;
                    }
                    return $link . ($nott ? ' NOT' : '') . " ($tocompute & '$val') ";

                case "bool":
                    if (!is_numeric($val)) {
                        if (strcasecmp($val, __('No')) == 0) {
                             $val = 0;
                        } else if (strcasecmp($val, __('Yes')) == 0) {
                            $val = 1;
                        }
                    }
                   // No break here : use number comparaison case

                case "count":
                case "mio":
                case "number":
                case "integer":
                case "decimal":
                case "timestamp":
                case "progressbar":
                    $decimal_contains = $searchopt[$ID]["datatype"] === 'decimal' && $searchtype === 'contains';
                    $val = Sanitizer::decodeHtmlSpecialChars($val); // Decode "<" and ">" operators

                    if (preg_match("/([<>])(=?)[[:space:]]*(-?)[[:space:]]*([0-9]+(.[0-9]+)?)/", $val, $regs)) {
                        if (in_array($searchtype, ["notequals", "notcontains"])) {
                            $nott = !$nott;
                        }
                        if ($nott) {
                            if ($regs[1] == '<') {
                                $regs[1] = '>';
                            } else {
                                $regs[1] = '<';
                            }
                        }
                        $regs[1] .= $regs[2];
                        return $link . " ($tocompute " . $regs[1] . " " . $regs[3] . $regs[4] . ") ";
                    }

                    if (is_numeric($val) && !$decimal_contains) {
                        $numeric_val = floatval($val);

                        if (in_array($searchtype, ["notequals", "notcontains"])) {
                            $nott = !$nott;
                        }

                        if (isset($searchopt[$ID]["width"])) {
                            $ADD = "";
                            if (
                                $nott
                                && ($val != 'NULL') && ($val != 'null')
                            ) {
                                $ADD = " OR $tocompute IS NULL";
                            }
                            if ($nott) {
                                return $link . " ($tocompute < " . ($numeric_val - $searchopt[$ID]["width"]) . "
                                        OR $tocompute > " . ($numeric_val + $searchopt[$ID]["width"]) . "
                                        $ADD) ";
                            }
                            return $link . " (($tocompute >= " . ($numeric_val - $searchopt[$ID]["width"]) . "
                                      AND $tocompute <= " . ($numeric_val + $searchopt[$ID]["width"]) . ")
                                     $ADD) ";
                        }
                        if (!$nott) {
                            return " $link ($tocompute = $numeric_val) ";
                        }
                        return " $link ($tocompute <> $numeric_val) ";
                    }
                    break;
            }
        }

        // Using subquery in the WHERE clause
        if ($use_subquery_on_id_search || $use_subquery_on_text_search) {
            // Compute tables and fields names
            $main_table = getTableForItemType($itemtype);
            $fk = getForeignKeyFieldForTable($main_table);
            $beforejoin = $searchopt[$ID]['joinparams']['beforejoin'];
            $child_table = $searchopt[$ID]['table'];
            $link_table = $beforejoin['table'];
            $linked_fk = $beforejoin['joinparams']['linkfield'] ?? getForeignKeyFieldForTable($searchopt[$ID]['table']);

            // Handle extra condition (e.g. filtering group type)
            $addcondition = '';
            if (isset($beforejoin['joinparams']['condition'])) {
                $condition = $beforejoin['joinparams']['condition'];
                if (is_array($condition)) {
                    $it = new DBmysqlIterator(null);
                    $condition = ' AND ' . $it->analyseCrit($condition);
                }
                $from         = ["`REFTABLE`", "REFTABLE", "`NEWTABLE`", "NEWTABLE"];
                $to           = ["`$main_table`", "`$main_table`", "`$link_table`", "`$link_table`"];
                $addcondition = str_replace($from, $to, $condition);
                $addcondition = $addcondition . " ";
            }

            if ($use_subquery_on_id_search) {
                // Subquery for "Is not", "Not + is", "Not under" and "Not + Under" search types
                // As an example, when looking for tickets that don't have a
                // given observer group (id = 4), $out will look like this:
                //
                // AND `glpi_tickets`.`id` NOT IN (
                //     SELECT `tickets_id`
                //     FROM `glpi_groups_tickets`
                //     WHERE `groups_id` = '4' AND `glpi_groups_tickets`.`type` = '3'
                // )
                if (is_numeric($val) && (int)$val === 0) {
                    // Special case, search criteria is empty
                    $subquery_operator = $subquery_operator == "IN" ? "NOT IN" : "IN";
                    $out = " $link `$main_table`.`id` $subquery_operator (
                        SELECT `$fk`
                        FROM `$link_table`
                        WHERE 1 $addcondition
                    )";
                } else {
                    $out = " $link `$main_table`.`id` $subquery_operator (
                        SELECT `$fk`
                        FROM `$link_table`
                        WHERE `$linked_fk` $SEARCH $addcondition
                    )";
                }
            } elseif ($use_subquery_on_text_search) {
                // Subquery for "Not contains" and "Not + contains" search types
                // As an example, when looking for tickets that don't have a
                // given observer group (name = "groupname"), $out will look like this:
                //
                // AND `glpi_tickets`.`id` NOT IN (
                //      SELECT `tickets_id`
                //      FROM `glpi_groups_tickets`
                //      WHERE `groups_id` IN (
                //          SELECT `id`
                //          FROM `glpi_groups`
                //          WHERE `completename`LIKE '%groupname%'
                //      ) AND `glpi_groups_tickets`.`type` = '3'
                // )

                if ($subquery_specific_username) {
                    $out = " $link `$main_table`.`id` $subquery_operator (
                        SELECT `$fk`
                        FROM `$link_table`
                        WHERE (`$linked_fk` IN (
                            SELECT `id`
                            FROM `$child_table`
                            WHERE `$field` $SEARCH $subquery_specific_username_firstname_real_name
                        ) $subquery_specific_username_anonymous) $addcondition
                    )";
                } else {
                    $out = " $link `$main_table`.`id` $subquery_operator (
                        SELECT `$fk`
                        FROM `$link_table`
                        WHERE `$linked_fk` IN (
                            SELECT `id`
                            FROM `$child_table`
                            WHERE `$field` $SEARCH
                        ) $addcondition
                    )";
                }
            }
            return $out;
        }

       // Default case
        if (in_array($searchtype, ['equals', 'notequals','under', 'notunder'])) {
            if (
                (!isset($searchopt[$ID]['searchequalsonfield'])
                || !$searchopt[$ID]['searchequalsonfield'])
                && ($itemtype == AllAssets::getType()
                || $table != $itemtype::getTable())
            ) {
                $out = " $link (`$table`.`id`" . $SEARCH;
            } else {
                $out = " $link (`$table`.`$field`" . $SEARCH;
            }
            if ($searchtype == 'notequals') {
                $nott = !$nott;
            }
           // Add NULL if $val = 0 and not negative search
           // Or negative search on real value
            if (
                (!$nott && ($val == 0))
                || ($nott && ($val != 0))
            ) {
                $out .= " OR `$table`.`id` IS NULL";
            }
            $out .= ')';
            return $out;
        }
        $transitemtype = getItemTypeForTable($inittable);
        if (Session::haveTranslations($transitemtype, $field)) {
            return " $link (" . self::makeTextCriteria($tocompute, $val, $nott, '') . "
                          OR " . self::makeTextCriteria($tocomputetrans, $val, $nott, '') . ")";
        }

        return self::makeTextCriteria($tocompute, $val, $nott, $link);
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
        $out = '';

        switch ($itemtype) {
           // No link
            case 'User':
                $out = self::addLeftJoin(
                    $itemtype,
                    $ref_table,
                    $already_link_tables,
                    "glpi_profiles_users",
                    "profiles_users_id",
                    0,
                    0,
                    ['jointype' => 'child']
                );
                break;

            case 'Reservation':
                $out .= self::addLeftJoin(
                    $itemtype,
                    $ref_table,
                    $already_link_tables,
                    ReservationItem::getTable(),
                    ReservationItem::getForeignKeyField(),
                );
                break;

            case 'Reminder':
                $out = Reminder::addVisibilityJoins();
                break;

            case 'RSSFeed':
                $out = RSSFeed::addVisibilityJoins();
                break;

            case 'ProjectTask':
               // Same structure in addDefaultWhere
                $out .= self::addLeftJoin(
                    $itemtype,
                    $ref_table,
                    $already_link_tables,
                    "glpi_projects",
                    "projects_id"
                );
                $out .= self::addLeftJoin(
                    $itemtype,
                    $ref_table,
                    $already_link_tables,
                    "glpi_projecttaskteams",
                    "projecttaskteams_id",
                    0,
                    0,
                    ['jointype' => 'child']
                );
                break;

            case 'Project':
               // Same structure in addDefaultWhere
                if (!Session::haveRight("project", Project::READALL)) {
                    $out .= self::addLeftJoin(
                        $itemtype,
                        $ref_table,
                        $already_link_tables,
                        "glpi_projectteams",
                        "projectteams_id",
                        0,
                        0,
                        ['jointype' => 'child']
                    );
                }
                break;

            case 'Ticket':
               // Same structure in addDefaultWhere
                if (!Session::haveRight("ticket", Ticket::READALL)) {
                    $searchopt = self::getOptions($itemtype);

                   // show mine : requester
                    $out .= self::addLeftJoin(
                        $itemtype,
                        $ref_table,
                        $already_link_tables,
                        "glpi_tickets_users",
                        "tickets_users_id",
                        0,
                        0,
                        $searchopt[4]['joinparams']['beforejoin']['joinparams']
                    );

                    if (Session::haveRight("ticket", Ticket::READGROUP)) {
                        if (count($_SESSION['glpigroups'])) {
                            $out .= self::addLeftJoin(
                                $itemtype,
                                $ref_table,
                                $already_link_tables,
                                "glpi_groups_tickets",
                                "groups_tickets_id",
                                0,
                                0,
                                $searchopt[71]['joinparams']['beforejoin']
                                ['joinparams']
                            );
                        }
                    }

                   // show mine : observer
                    $out .= self::addLeftJoin(
                        $itemtype,
                        $ref_table,
                        $already_link_tables,
                        "glpi_tickets_users",
                        "tickets_users_id",
                        0,
                        0,
                        $searchopt[66]['joinparams']['beforejoin']['joinparams']
                    );

                    if (count($_SESSION['glpigroups'])) {
                           $out .= self::addLeftJoin(
                               $itemtype,
                               $ref_table,
                               $already_link_tables,
                               "glpi_groups_tickets",
                               "groups_tickets_id",
                               0,
                               0,
                               $searchopt[65]['joinparams']['beforejoin']['joinparams']
                           );
                    }

                    if (Session::haveRight("ticket", Ticket::OWN)) { // Can own ticket : show assign to me
                        $out .= self::addLeftJoin(
                            $itemtype,
                            $ref_table,
                            $already_link_tables,
                            "glpi_tickets_users",
                            "tickets_users_id",
                            0,
                            0,
                            $searchopt[5]['joinparams']['beforejoin']['joinparams']
                        );
                    }

                    if (Session::haveRightsOr("ticket", [Ticket::READMY, Ticket::READASSIGN])) { // show mine + assign to me
                        $out .= self::addLeftJoin(
                            $itemtype,
                            $ref_table,
                            $already_link_tables,
                            "glpi_tickets_users",
                            "tickets_users_id",
                            0,
                            0,
                            $searchopt[5]['joinparams']['beforejoin']['joinparams']
                        );

                        if (count($_SESSION['glpigroups'])) {
                              $out .= self::addLeftJoin(
                                  $itemtype,
                                  $ref_table,
                                  $already_link_tables,
                                  "glpi_groups_tickets",
                                  "groups_tickets_id",
                                  0,
                                  0,
                                  $searchopt[8]['joinparams']['beforejoin']
                                  ['joinparams']
                              );
                        }
                    }

                    if (
                        Session::haveRightsOr(
                            'ticketvalidation',
                            [TicketValidation::VALIDATEINCIDENT,
                                TicketValidation::VALIDATEREQUEST
                            ]
                        )
                    ) {
                        $out .= self::addLeftJoin(
                            $itemtype,
                            $ref_table,
                            $already_link_tables,
                            "glpi_ticketvalidations",
                            "ticketvalidations_id",
                            0,
                            0,
                            $searchopt[58]['joinparams']['beforejoin']['joinparams']
                        );
                    }
                }
                break;

            case 'Change':
            case 'Problem':
                if ($itemtype == 'Change') {
                    $right       = 'change';
                    $table       = 'changes';
                    $groupetable = "glpi_changes_groups";
                    $linkfield   = "changes_groups_id";
                } else if ($itemtype == 'Problem') {
                    $right       = 'problem';
                    $table       = 'problems';
                    $groupetable = "glpi_groups_problems";
                    $linkfield   = "groups_problems_id";
                }

               // Same structure in addDefaultWhere
                $out = '';
                if (!Session::haveRight("$right", $itemtype::READALL)) {
                    $searchopt = self::getOptions($itemtype);

                    if (Session::haveRight("$right", $itemtype::READMY)) {
                       // show mine : requester
                        $out .= self::addLeftJoin(
                            $itemtype,
                            $ref_table,
                            $already_link_tables,
                            "glpi_" . $table . "_users",
                            $table . "_users_id",
                            0,
                            0,
                            $searchopt[4]['joinparams']['beforejoin']['joinparams']
                        );
                        if (count($_SESSION['glpigroups'])) {
                              $out .= self::addLeftJoin(
                                  $itemtype,
                                  $ref_table,
                                  $already_link_tables,
                                  $groupetable,
                                  $linkfield,
                                  0,
                                  0,
                                  $searchopt[71]['joinparams']['beforejoin']['joinparams']
                              );
                        }

                       // show mine : observer
                        $out .= self::addLeftJoin(
                            $itemtype,
                            $ref_table,
                            $already_link_tables,
                            "glpi_" . $table . "_users",
                            $table . "_users_id",
                            0,
                            0,
                            $searchopt[66]['joinparams']['beforejoin']['joinparams']
                        );
                        if (count($_SESSION['glpigroups'])) {
                              $out .= self::addLeftJoin(
                                  $itemtype,
                                  $ref_table,
                                  $already_link_tables,
                                  $groupetable,
                                  $linkfield,
                                  0,
                                  0,
                                  $searchopt[65]['joinparams']['beforejoin']['joinparams']
                              );
                        }

                       // show mine : assign
                        $out .= self::addLeftJoin(
                            $itemtype,
                            $ref_table,
                            $already_link_tables,
                            "glpi_" . $table . "_users",
                            $table . "_users_id",
                            0,
                            0,
                            $searchopt[5]['joinparams']['beforejoin']['joinparams']
                        );
                        if (count($_SESSION['glpigroups'])) {
                              $out .= self::addLeftJoin(
                                  $itemtype,
                                  $ref_table,
                                  $already_link_tables,
                                  $groupetable,
                                  $linkfield,
                                  0,
                                  0,
                                  $searchopt[8]['joinparams']['beforejoin']['joinparams']
                              );
                        }
                    }
                }
                break;

            case ITILFollowup::class:
                // TODO 11.0: use $CFG_GLPI['itil_types']
                $itil_types = [Ticket::class, Change::class, Problem::class];
                foreach ($itil_types as $itil_itemtype) {
                    $out .= self::addLeftJoin(
                        $itemtype,
                        $ref_table,
                        $already_link_tables,
                        $itil_itemtype::getTable(),
                        'items_id',
                        false,
                        '',
                        [
                            'condition' => "AND REFTABLE.`itemtype` = '$itil_itemtype'"
                        ]
                    );
                }
                break;

            default:
               // Plugin can override core definition for its type
                if ($plug = isPluginItemType($itemtype)) {
                    $plugin_name   = $plug['plugin'];
                    $hook_function = 'plugin_' . strtolower($plugin_name) . '_addDefaultJoin';
                    $hook_closure  = function () use ($hook_function, $itemtype, $ref_table, &$already_link_tables) {
                        if (is_callable($hook_function)) {
                              return $hook_function($itemtype, $ref_table, $already_link_tables);
                        }
                    };
                    $out = Plugin::doOneHook($plugin_name, $hook_closure);
                }
                break;
        }

        list($itemtype, $out) = Plugin::doHookFunction('add_default_join', [$itemtype, $out]);
        return $out;
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

       // Rename table for meta left join
        $AS = "";
        $nt = $new_table;
        $cleannt    = $nt;

       // Virtual field no link
        if (strpos($linkfield, '_virtual') === 0) {
            return '';
        }

        $complexjoin = self::computeComplexJoinID($joinparams);

        $is_fkey_composite_on_self = getTableNameForForeignKeyField($linkfield) == $ref_table
         && $linkfield != getForeignKeyFieldForTable($ref_table);

       // Auto link
        if (
            ($ref_table == $new_table)
            && empty($complexjoin)
            && !$is_fkey_composite_on_self
        ) {
            $transitemtype = getItemTypeForTable($new_table);
            if (Session::haveTranslations($transitemtype, $field)) {
                $transAS            = $nt . '_trans_' . $field;
                return self::joinDropdownTranslations(
                    $transAS,
                    $nt,
                    $transitemtype,
                    $field
                );
            }
            return "";
        }

       // Multiple link possibilies case
        if (!empty($linkfield) && ($linkfield != getForeignKeyFieldForTable($new_table))) {
            $nt .= "_" . $linkfield;
            $AS  = " AS `$nt`";
        }

        if (!empty($complexjoin)) {
            $nt .= "_" . $complexjoin;
            $AS  = " AS `$nt`";
        }

        $addmetanum = "";
        $rt         = $ref_table;
        $cleanrt    = $rt;
        if ($meta && $meta_type::getTable() != $new_table) {
            $addmetanum = "_" . $meta_type;
            $AS         = " AS `$nt$addmetanum`";
            $nt         = $nt . $addmetanum;
        }

       // Do not take into account standard linkfield
        $tocheck = $nt . "." . $linkfield;
        if ($linkfield == getForeignKeyFieldForTable($new_table)) {
            $tocheck = $nt;
        }

        if (in_array($tocheck, $already_link_tables)) {
            return "";
        }
        array_push($already_link_tables, $tocheck);

        $specific_leftjoin = '';

       // Plugin can override core definition for its type
        if ($plug = isPluginItemType($itemtype)) {
            $plugin_name   = $plug['plugin'];
            $hook_function = 'plugin_' . strtolower($plugin_name) . '_addLeftJoin';
            $hook_closure  = function () use ($hook_function, $itemtype, $ref_table, $new_table, $linkfield, &$already_link_tables) {
                if (is_callable($hook_function)) {
                      return $hook_function($itemtype, $ref_table, $new_table, $linkfield, $already_link_tables);
                }
            };
            $specific_leftjoin = Plugin::doOneHook($plugin_name, $hook_closure);
        }

       // Link with plugin tables : need to know left join structure
        if (
            empty($specific_leftjoin)
            && preg_match("/^glpi_plugin_([a-z0-9]+)/", $new_table, $matches)
        ) {
            if (count($matches) == 2) {
                $plugin_name   = $matches[1];
                $hook_function = 'plugin_' . strtolower($plugin_name) . '_addLeftJoin';
                $hook_closure  = function () use ($hook_function, $itemtype, $ref_table, $new_table, $linkfield, &$already_link_tables) {
                    if (is_callable($hook_function)) {
                          return $hook_function($itemtype, $ref_table, $new_table, $linkfield, $already_link_tables);
                    }
                };
                $specific_leftjoin = Plugin::doOneHook($plugin_name, $hook_closure);
            }
        }
        if (!empty($linkfield)) {
            $before = '';

            if (isset($joinparams['beforejoin']) && is_array($joinparams['beforejoin'])) {
                if (isset($joinparams['beforejoin']['table'])) {
                    $joinparams['beforejoin'] = [$joinparams['beforejoin']];
                }

                foreach ($joinparams['beforejoin'] as $tab) {
                    if (isset($tab['table'])) {
                        $intertable = $tab['table'];
                        if (isset($tab['linkfield'])) {
                            $interlinkfield = $tab['linkfield'];
                        } else {
                            $interlinkfield = getForeignKeyFieldForTable($intertable);
                        }

                        $interjoinparams = [];
                        if (isset($tab['joinparams'])) {
                             $interjoinparams = $tab['joinparams'];
                        }
                        $before .= self::addLeftJoin(
                            $itemtype,
                            $rt,
                            $already_link_tables,
                            $intertable,
                            $interlinkfield,
                            $meta,
                            $meta_type,
                            $interjoinparams
                        );

                        // No direct link with the previous joins
                        if (!isset($tab['joinparams']['nolink']) || !$tab['joinparams']['nolink']) {
                            $cleanrt     = $intertable;
                            $complexjoin = self::computeComplexJoinID($interjoinparams);
                            if (!empty($interlinkfield) && ($interlinkfield != getForeignKeyFieldForTable($intertable))) {
                                $intertable .= "_" . $interlinkfield;
                            }
                            if (!empty($complexjoin)) {
                                $intertable .= "_" . $complexjoin;
                            }
                            if ($meta && $meta_type::getTable() != $cleanrt) {
                                $intertable .= "_" . $meta_type;
                            }
                            $rt = $intertable;
                        }
                    }
                }
            }

            $addcondition = '';
            if (isset($joinparams['condition'])) {
                $condition = $joinparams['condition'];
                if (is_array($condition)) {
                    $it = new DBmysqlIterator(null);
                    $condition = ' AND ' . $it->analyseCrit($condition);
                }
                $from         = ["`REFTABLE`", "REFTABLE", "`NEWTABLE`", "NEWTABLE"];
                $to           = ["`$rt`", "`$rt`", "`$nt`", "`$nt`"];
                $addcondition = str_replace($from, $to, $condition);
                $addcondition = $addcondition . " ";
            }

            if (!isset($joinparams['jointype'])) {
                $joinparams['jointype'] = 'standard';
            }

            if (empty($specific_leftjoin)) {
                switch ($new_table) {
                   // No link
                    case "glpi_auth_tables":
                         $user_searchopt     = self::getOptions('User');

                         $specific_leftjoin  = self::addLeftJoin(
                             $itemtype,
                             $rt,
                             $already_link_tables,
                             "glpi_authldaps",
                             'auths_id',
                             0,
                             0,
                             $user_searchopt[30]['joinparams']
                         );
                           $specific_leftjoin .= self::addLeftJoin(
                               $itemtype,
                               $rt,
                               $already_link_tables,
                               "glpi_authmails",
                               'auths_id',
                               0,
                               0,
                               $user_searchopt[31]['joinparams']
                           );
                        break;
                }
            }

            if (empty($specific_leftjoin)) {
                switch ($joinparams['jointype']) {
                    case 'child':
                        $linkfield = getForeignKeyFieldForTable($cleanrt);
                        if (isset($joinparams['linkfield'])) {
                            $linkfield = $joinparams['linkfield'];
                        }

                        // Child join
                        $specific_leftjoin = " LEFT JOIN `$new_table` $AS
                                             ON (`$rt`.`id` = `$nt`.`$linkfield`
                                                 $addcondition)";
                        break;

                    case 'item_item':
                       // Item_Item join
                        $specific_leftjoin = " LEFT JOIN `$new_table` $AS
                                          ON ((`$rt`.`id`
                                                = `$nt`.`" . getForeignKeyFieldForTable($cleanrt) . "_1`
                                               OR `$rt`.`id`
                                                 = `$nt`.`" . getForeignKeyFieldForTable($cleanrt) . "_2`)
                                              $addcondition)";
                        break;

                    case 'item_item_revert':
                       // Item_Item join reverting previous item_item
                        $specific_leftjoin = " LEFT JOIN `$new_table` $AS
                                          ON ((`$nt`.`id`
                                                = `$rt`.`" . getForeignKeyFieldForTable($cleannt) . "_1`
                                               OR `$nt`.`id`
                                                 = `$rt`.`" . getForeignKeyFieldForTable($cleannt) . "_2`)
                                              $addcondition)";
                        break;

                    case "mainitemtype_mainitem":
                        $addmain = 'main';
                       //addmain defined to be used in itemtype_item case

                    case "itemtype_item":
                        if (!isset($addmain)) {
                            $addmain = '';
                        }
                        $used_itemtype = $itemtype;
                        if (
                            isset($joinparams['specific_itemtype'])
                            && !empty($joinparams['specific_itemtype'])
                        ) {
                            $used_itemtype = $joinparams['specific_itemtype'];
                        }
                       // Itemtype join
                        $specific_leftjoin = " LEFT JOIN `$new_table` $AS
                                          ON (`$rt`.`id` = `$nt`.`" . $addmain . "items_id`
                                              AND `$nt`.`" . $addmain . "itemtype` = '$used_itemtype'
                                              $addcondition) ";
                        break;

                    case "itemtype_item_revert":
                        $used_itemtype = $itemtype;
                        if (
                            isset($joinparams['specific_itemtype'])
                            && !empty($joinparams['specific_itemtype'])
                        ) {
                            $used_itemtype = $joinparams['specific_itemtype'];
                        }
                       // Itemtype join
                        $specific_leftjoin = " LEFT JOIN `$new_table` $AS
                                          ON (`$nt`.`id` = `$rt`.`" . "items_id`
                                              AND `$rt`.`" . "itemtype` = '$used_itemtype'
                                              $addcondition) ";
                        break;

                    case "itemtypeonly":
                        $used_itemtype = $itemtype;
                        if (
                            isset($joinparams['specific_itemtype'])
                            && !empty($joinparams['specific_itemtype'])
                        ) {
                            $used_itemtype = $joinparams['specific_itemtype'];
                        }
                       // Itemtype join
                        $specific_leftjoin = " LEFT JOIN `$new_table` $AS
                                          ON (`$nt`.`itemtype` = '$used_itemtype'
                                              $addcondition) ";
                        break;

                    default:
                       // Standard join
                        $specific_leftjoin = "LEFT JOIN `$new_table` $AS
                                          ON (`$rt`.`$linkfield` = `$nt`.`id`
                                              $addcondition)";
                        $transitemtype = getItemTypeForTable($new_table);
                        if (Session::haveTranslations($transitemtype, $field)) {
                            $transAS            = $nt . '_trans_' . $field;
                            $specific_leftjoin .= self::joinDropdownTranslations(
                                $transAS,
                                $nt,
                                $transitemtype,
                                $field
                            );
                        }
                        break;
                }
            }
            return $before . $specific_leftjoin;
        }

        return '';
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
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $from_referencetype = self::getMetaReferenceItemtype($from_type);

        $LINK = " LEFT JOIN ";

        $from_table = $from_type::getTable();
        $from_fk    = getForeignKeyFieldForTable($from_table);
        $to_table   = $to_type::getTable();
        $to_fk      = getForeignKeyFieldForTable($to_table);

        $to_obj        = getItemForItemtype($to_type);
        $to_entity_restrict = $to_obj->isField('entities_id') ? getEntitiesRestrictRequest('AND', $to_table) : '';

        $complexjoin = self::computeComplexJoinID($joinparams);
        $alias_suffix = ($complexjoin != '' ? '_' . $complexjoin : '') . '_' . $to_type;

        $JOIN = "";

       // Specific JOIN
        if ($from_referencetype === 'Software' && in_array($to_type, $CFG_GLPI['software_types'])) {
           // From Software to software_types
            $softwareversions_table = "glpi_softwareversions{$alias_suffix}";
            if (!in_array($softwareversions_table, $already_link_tables2)) {
                array_push($already_link_tables2, $softwareversions_table);
                $JOIN .= "$LINK `glpi_softwareversions` AS `$softwareversions_table`
                         ON (`$softwareversions_table`.`softwares_id` = `$from_table`.`id`) ";
            }
            $items_softwareversions_table = "glpi_items_softwareversions_{$alias_suffix}";
            if (!in_array($items_softwareversions_table, $already_link_tables2)) {
                array_push($already_link_tables2, $items_softwareversions_table);
                $JOIN .= "$LINK `glpi_items_softwareversions` AS `$items_softwareversions_table`
                         ON (`$items_softwareversions_table`.`softwareversions_id` = `$softwareversions_table`.`id`
                             AND `$items_softwareversions_table`.`itemtype` = '$to_type'
                             AND `$items_softwareversions_table`.`is_deleted` = 0) ";
            }
            if (!in_array($to_table, $already_link_tables2)) {
                array_push($already_link_tables2, $to_table);
                $JOIN .= "$LINK `$to_table`
                         ON (`$items_softwareversions_table`.`items_id` = `$to_table`.`id`
                             AND `$items_softwareversions_table`.`itemtype` = '$to_type'
                             $to_entity_restrict) ";
            }
            return $JOIN;
        }

        if ($to_type === 'Software' && in_array($from_referencetype, $CFG_GLPI['software_types'])) {
           // From software_types to Software
            $items_softwareversions_table = "glpi_items_softwareversions{$alias_suffix}";
            if (!in_array($items_softwareversions_table, $already_link_tables2)) {
                array_push($already_link_tables2, $items_softwareversions_table);
                $JOIN .= "$LINK `glpi_items_softwareversions` AS `$items_softwareversions_table`
                         ON (`$items_softwareversions_table`.`items_id` = `$from_table`.`id`
                             AND `$items_softwareversions_table`.`itemtype` = '$from_type'
                             AND `$items_softwareversions_table`.`is_deleted` = 0) ";
            }
            $softwareversions_table = "glpi_softwareversions{$alias_suffix}";
            if (!in_array($softwareversions_table, $already_link_tables2)) {
                array_push($already_link_tables2, $softwareversions_table);
                $JOIN .= "$LINK `glpi_softwareversions` AS `$softwareversions_table`
                         ON (`$items_softwareversions_table`.`softwareversions_id` = `$softwareversions_table`.`id`) ";
            }
            if (!in_array($to_table, $already_link_tables2)) {
                array_push($already_link_tables2, $to_table);
                $JOIN .= "$LINK `$to_table`
                         ON (`$softwareversions_table`.`softwares_id` = `$to_table`.`id`) ";
            }
            $softwarelicenses_table = "glpi_softwarelicenses{$alias_suffix}";
            if (!in_array($softwarelicenses_table, $already_link_tables2)) {
                array_push($already_link_tables2, $softwarelicenses_table);
                $JOIN .= "$LINK `glpi_softwarelicenses` AS `$softwarelicenses_table`
                        ON ($to_table.`id` = `$softwarelicenses_table`.`softwares_id`"
                          . getEntitiesRestrictRequest(' AND', $softwarelicenses_table, '', '', true) . ") ";
            }
            return $JOIN;
        }

        if ($from_referencetype === 'Budget' && in_array($to_type, $CFG_GLPI['infocom_types'])) {
           // From Budget to infocom_types
            $infocom_alias = "glpi_infocoms{$alias_suffix}";
            if (!in_array($infocom_alias, $already_link_tables2)) {
                array_push($already_link_tables2, $infocom_alias);
                $JOIN .= "$LINK `glpi_infocoms` AS `$infocom_alias`
                         ON (`$from_table`.`id` = `$infocom_alias`.`budgets_id`) ";
            }
            if (!in_array($to_table, $already_link_tables2)) {
                array_push($already_link_tables2, $to_table);
                $JOIN .= "$LINK `$to_table`
                         ON (`$to_table`.`id` = `$infocom_alias`.`items_id`
                             AND `$infocom_alias`.`itemtype` = '$to_type'
                             $to_entity_restrict) ";
            }
            return $JOIN;
        }

        if ($to_type === 'Budget' && in_array($from_referencetype, $CFG_GLPI['infocom_types'])) {
           // From infocom_types to Budget
            $infocom_alias = "glpi_infocoms{$alias_suffix}";
            if (!in_array($infocom_alias, $already_link_tables2)) {
                array_push($already_link_tables2, $infocom_alias);
                $JOIN .= "$LINK `glpi_infocoms` AS `$infocom_alias`
                         ON (`$from_table`.`id` = `$infocom_alias`.`items_id`
                             AND `$infocom_alias`.`itemtype` = '$from_type') ";
            }
            if (!in_array($to_table, $already_link_tables2)) {
                array_push($already_link_tables2, $to_table);
                $JOIN .= "$LINK `$to_table`
                         ON (`$infocom_alias`.`$to_fk` = `$to_table`.`id`
                             $to_entity_restrict) ";
            }
            return $JOIN;
        }

        if ($from_referencetype === 'Reservation' && in_array($to_type, $CFG_GLPI['reservation_types'])) {
           // From Reservation to reservation_types
            $reservationitems_alias = "glpi_reservationitems{$alias_suffix}";
            if (!in_array($reservationitems_alias, $already_link_tables2)) {
                array_push($already_link_tables2, $reservationitems_alias);
                $JOIN .= "$LINK `glpi_reservationitems` AS `$reservationitems_alias`
                         ON (`$from_table`.`reservationitems_id` = `$reservationitems_alias`.`id`) ";
            }
            if (!in_array($to_table, $already_link_tables2)) {
                array_push($already_link_tables2, $to_table);
                $JOIN .= "$LINK `$to_table`
                         ON (`$to_table`.`id` = `$reservationitems_alias`.`items_id`
                             AND `$reservationitems_alias`.`itemtype` = '$to_type'
                             $to_entity_restrict) ";
            }
            return $JOIN;
        }

        if ($to_type === 'Reservation' && in_array($from_referencetype, $CFG_GLPI['reservation_types'])) {
           // From reservation_types to Reservation
            $reservationitems_alias = "glpi_reservationitems{$alias_suffix}";
            if (!in_array($reservationitems_alias, $already_link_tables2)) {
                array_push($already_link_tables2, $reservationitems_alias);
                $JOIN .= "$LINK `glpi_reservationitems` AS `$reservationitems_alias`
                         ON (`$from_table`.`id` = `$reservationitems_alias`.`items_id`
                             AND `$reservationitems_alias`.`itemtype` = '$from_type') ";
            }
            if (!in_array($to_table, $already_link_tables2)) {
                array_push($already_link_tables2, $to_table);
                $JOIN .= "$LINK `$to_table`
                         ON (`$reservationitems_alias`.`id` = `$to_table`.`reservationitems_id`
                             $to_entity_restrict) ";
            }
            return $JOIN;
        }

       // Generic JOIN
        $from_obj      = getItemForItemtype($from_referencetype);
        $from_item_obj = null;
        $to_obj        = getItemForItemtype($to_type);
        $to_item_obj   = null;
        if (self::isPossibleMetaSubitemOf($from_referencetype, $to_type)) {
            $from_item_obj = getItemForItemtype($from_referencetype . '_Item');
            if (!$from_item_obj) {
                $from_item_obj = getItemForItemtype('Item_' . $from_referencetype);
            }
        }
        if (self::isPossibleMetaSubitemOf($to_type, $from_referencetype)) {
            $to_item_obj   = getItemForItemtype($to_type . '_Item');
            if (!$to_item_obj) {
                $to_item_obj = getItemForItemtype('Item_' . $to_type);
            }
        }

        if ($from_obj && $from_obj->isField($to_fk)) {
           // $from_table has a foreign key corresponding to $to_table
            if (!in_array($to_table, $already_link_tables2)) {
                array_push($already_link_tables2, $to_table);
                $JOIN .= "$LINK `$to_table`
                         ON (`$from_table`.`$to_fk` = `$to_table`.`id`
                             $to_entity_restrict) ";
            }
        } else if ($to_obj && $to_obj->isField($from_fk)) {
           // $to_table has a foreign key corresponding to $from_table
            if (!in_array($to_table, $already_link_tables2)) {
                array_push($already_link_tables2, $to_table);
                $JOIN .= "$LINK `$to_table`
                         ON (`$from_table`.`id` = `$to_table`.`$from_fk`
                             $to_entity_restrict) ";
            }
        } else if ($from_obj && $from_obj->isField('itemtype') && $from_obj->isField('items_id')) {
           // $from_table has items_id/itemtype fields
            if (!in_array($to_table, $already_link_tables2)) {
                array_push($already_link_tables2, $to_table);
                $JOIN .= "$LINK `$to_table`
                         ON (`$from_table`.`items_id` = `$to_table`.`id`
                             AND `$from_table`.`itemtype` = '$to_type'
                             $to_entity_restrict) ";
            }
        } else if ($to_obj && $to_obj->isField('itemtype') && $to_obj->isField('items_id')) {
           // $to_table has items_id/itemtype fields
            if (!in_array($to_table, $already_link_tables2)) {
                array_push($already_link_tables2, $to_table);
                $JOIN .= "$LINK `$to_table`
                         ON (`$from_table`.`id` = `$to_table`.`items_id`
                             AND `$to_table`.`itemtype` = '$from_type'
                             $to_entity_restrict) ";
            }
        } else if ($from_item_obj && $from_item_obj->isField($from_fk)) {
           // glpi_$from_items table exists and has a foreign key corresponding to $to_table
            $items_table = $from_item_obj::getTable();
            $items_table_alias = $items_table . $alias_suffix;
            if (!in_array($items_table_alias, $already_link_tables2)) {
                array_push($already_link_tables2, $items_table_alias);
                $deleted = $from_item_obj->isField('is_deleted') ? "AND `$items_table_alias`.`is_deleted` = 0" : "";
                $JOIN .= "$LINK `$items_table` AS `$items_table_alias`
                         ON (`$items_table_alias`.`$from_fk` = `$from_table`.`id`
                             AND `$items_table_alias`.`itemtype` = '$to_type'
                             $deleted)";
            }
            if (!in_array($to_table, $already_link_tables2)) {
                array_push($already_link_tables2, $to_table);
                $JOIN .= "$LINK `$to_table`
                         ON (`$items_table_alias`.`items_id` = `$to_table`.`id`
                             $to_entity_restrict) ";
            }
        } else if ($to_item_obj && $to_item_obj->isField($to_fk)) {
           // glpi_$to_items table exists and has a foreign key corresponding to $from_table
            $items_table = $to_item_obj::getTable();
            $items_table_alias = $items_table . $alias_suffix;
            if (!in_array($items_table_alias, $already_link_tables2)) {
                array_push($already_link_tables2, $items_table_alias);
                $deleted = $to_item_obj->isField('is_deleted') ? "AND `$items_table_alias`.`is_deleted` = 0" : "";
                $JOIN .= "$LINK `$items_table` AS `$items_table_alias`
                         ON (`$items_table_alias`.`items_id` = `$from_table`.`id`
                             AND `$items_table_alias`.`itemtype` = '$from_type'
                             $deleted)";
            }
            if (!in_array($to_table, $already_link_tables2)) {
                array_push($already_link_tables2, $to_table);
                $JOIN .= "$LINK `$to_table`
                         ON (`$items_table_alias`.`$to_fk` = `$to_table`.`id`
                             $to_entity_restrict) ";
            }
        }

        return $JOIN;
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

        $searchopt  = self::getOptions($itemtype);

        $table      = $searchopt[$ID]["table"];
        $field      = $searchopt[$ID]["field"];

       // Plugin can override core definition for its type
        if ($plug = isPluginItemType($itemtype)) {
            $out = Plugin::doOneHook(
                $plug['plugin'],
                'displayConfigItem',
                $itemtype,
                $ID,
                $data,
                "{$itemtype}_{$ID}"
            );
            if (!empty($out)) {
                 return $out;
            }
        }

        $out = "";
        $NAME = "{$itemtype}_{$ID}";

        switch ($table . "." . $field) {
            case "glpi_tickets.time_to_resolve":
            case "glpi_tickets.internal_time_to_resolve":
            case "glpi_problems.time_to_resolve":
            case "glpi_changes.time_to_resolve":
                if (in_array($ID, [151, 181])) {
                    break; // Skip "TTR + progress" search options
                }

                $value      = $data[$NAME][0]['name'];
                $status     = $data[$NAME][0]['status'];
                $solve_date = $data[$NAME][0]['solvedate'];

                $is_late = !empty($value)
                    && $status != CommonITILObject::WAITING
                    && (
                        $solve_date > $value
                        || ($solve_date == null && $value < $_SESSION['glpi_currenttime'])
                    );

                if ($is_late) {
                    $out = " class=\"shadow-none\" style=\"background-color: #cf9b9b\" ";
                }
                break;
            case "glpi_tickets.time_to_own":
            case "glpi_tickets.internal_time_to_own":
                if (in_array($ID, [158, 186])) {
                    break; // Skip "TTO + progress" search options
                }

                $value        = $data[$NAME][0]['name'];
                $status       = $data[$NAME][0]['status'];
                $opening_date = $data[$NAME][0]['date'];
                $tia_delay    = $data[$NAME][0]['takeintoaccount_delay_stat'];
                $tia_date     = $data[$NAME][0]['takeintoaccountdate'];
                // Fallback to old and incorrect computation for tickets saved before introducing takeintoaccountdate field
                if ($tia_delay > 0 && $tia_date == null) {
                    $tia_date = strtotime($opening_date) + $tia_delay;
                }

                $is_late = !empty($value)
                    && $status != CommonITILObject::WAITING
                    && (
                        $tia_date > $value
                        || ($tia_date == null && $value < $_SESSION['glpi_currenttime'])
                    );

                if ($is_late) {
                    $out = " class=\"shadow-none\" style=\"background-color: #cf9b9b\" ";
                }
                break;
            case "glpi_certificates.date_expiration":
                if (
                    !in_array($ID, [151, 158, 181, 186])
                    && !empty($data[$NAME][0]['name'])
                ) {
                    $out = "";
                    if ($before = Entity::getUsedConfig('send_certificates_alert_before_delay', $_SESSION['glpiactive_entity'])) {
                        $before = date('Y-m-d', strtotime($_SESSION['glpi_currenttime'] . " + $before days"));
                        if ($data[$NAME][0]['name'] < $_SESSION['glpi_currenttime']) {
                            $out = " class=\"shadow-none\" style=\"color: white; background-color: #d63939\" ";
                        } elseif ($data[$NAME][0]['name'] < $before) {
                            $out = " class=\"shadow-none\"  style=\"background-color: #de5d06\" ";
                        } elseif ($data[$NAME][0]['name'] >= $before) {
                            $out = " class=\"shadow-none\"  style=\"background-color: #a1cf66\" ";
                        }
                    } else {
                        if ($data[$NAME][0]['name'] < $_SESSION['glpi_currenttime']) {
                            $out = " class=\"shadow-none\" style=\"background-color: #cf9b9b\" ";
                        }
                    }
                }
                break;
            case "glpi_projectstates.color":
            case "glpi_cables.color":
                $bg_color = $data[$NAME][0]['name'];
                if (!empty($bg_color)) {
                    $out = " class=\"shadow-none\" style=\"background-color: $bg_color;\" ";
                }
                break;

            case "glpi_projectstates.name":
                if (array_key_exists('color', $data[$NAME][0])) {
                    $bg_color = $data[$NAME][0]['color'];
                    if (!empty($bg_color)) {
                        $out = " class=\"shadow-none\" style=\"background-color: $bg_color;\" ";
                    }
                }
                break;

            case "glpi_domains.date_expiration":
                if (
                    !empty($data[$NAME][0]['name'])
                    && ($data[$NAME][0]['name'] < $_SESSION['glpi_currenttime'])
                ) {
                    $out = " class=\"shadow-none\" style=\"background-color: #cf9b9b\" ";
                }
                break;
        }

        return $out;
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
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $searchopt = self::getOptions($itemtype);
        if (
            isset($CFG_GLPI["union_search_type"][$itemtype])
            && ($CFG_GLPI["union_search_type"][$itemtype] == $searchopt[$ID]["table"])
        ) {
            $oparams = [];
            if (
                isset($searchopt[$ID]['addobjectparams'])
                && $searchopt[$ID]['addobjectparams']
            ) {
                $oparams = $searchopt[$ID]['addobjectparams'];
            }

           // Search option may not exists in subtype
           // This is the case for "Inventory number" for a Software listed from ReservationItem search
            $subtype_so = self::getOptions($data["TYPE"]);
            if (!array_key_exists($ID, $subtype_so)) {
                return '';
            }

            return self::giveItem($data["TYPE"], $ID, $data, $meta, $oparams, $itemtype);
        }
        $so = $searchopt[$ID];
        $orig_id = $ID;
        $ID = ($orig_itemtype !== null ? $orig_itemtype : $itemtype) . '_' . $ID;

        if (count($addobjectparams)) {
            $so = array_merge($so, $addobjectparams);
        }
       // Plugin can override core definition for its type
        if ($plug = isPluginItemType($itemtype)) {
            $out = Plugin::doOneHook(
                $plug['plugin'],
                'giveItem',
                $itemtype,
                $orig_id,
                $data,
                $ID
            );
            if (!empty($out)) {
                return $out;
            }
        }

        $html_output = in_array(
            self::$output_type,
            [
                self::HTML_OUTPUT,
                self::GLOBAL_SEARCH, // For a global search, output will be done in HTML context
            ]
        );

        if (isset($so["table"])) {
            $table     = $so["table"];
            $field     = $so["field"];
            $linkfield = $so["linkfield"];

           /// TODO try to clean all specific cases using SpecificToDisplay
            switch ($table . '.' . $field) {
                case "glpi_users.name":
                    // USER search case
                    if (
                        ($itemtype != 'User')
                        && isset($so["forcegroupby"]) && $so["forcegroupby"]
                    ) {
                        $out           = "";
                        $count_display = 0;
                        $added         = [];

                        $showuserlink = 0;
                        if (Session::haveRight('user', READ)) {
                            $showuserlink = 1;
                        }

                        for ($k = 0; $k < $data[$ID]['count']; $k++) {
                            if (
                                (isset($data[$ID][$k]['name']) && ($data[$ID][$k]['name'] > 0))
                                || (isset($data[$ID][$k][2]) && ($data[$ID][$k][2] != ''))
                            ) {
                                if ($count_display) {
                                    $out .= self::LBBR;
                                }

                                if ($itemtype == 'Ticket') {
                                    if (
                                        isset($data[$ID][$k]['name'])
                                        && $data[$ID][$k]['name'] > 0
                                    ) {
                                        if (
                                            Session::getCurrentInterface() == 'helpdesk'
                                            && $orig_id == 5 // -> Assigned user
                                            && !empty($anon_name = User::getAnonymizedNameForUser(
                                                $data[$ID][$k]['name'],
                                                $itemtype::getById($data['id'])->getEntityId()
                                            ))
                                        ) {
                                            $out .= $anon_name;
                                        } else {
                                            $userdata = getUserName($data[$ID][$k]['name'], 2);
                                            $tooltip  = "";
                                            if (Session::haveRight('user', READ)) {
                                                $tooltip = Html::showToolTip(
                                                    $userdata["comment"],
                                                    ['link'    => $userdata["link"],
                                                        'display' => false
                                                    ]
                                                );
                                            }
                                            $out .= sprintf(__('%1$s %2$s'), $userdata['name'], $tooltip);
                                        }

                                        $count_display++;
                                    }
                                } else {
                                    $out .= getUserName($data[$ID][$k]['name'], $showuserlink);
                                    $count_display++;
                                }

                           // Manage alternative_email for tickets_users
                                if (
                                    ($itemtype == 'Ticket')
                                    && isset($data[$ID][$k][2])
                                ) {
                                        $split = explode(self::LONGSEP, $data[$ID][$k][2]);
                                    for ($l = 0; $l < count($split); $l++) {
                                        $split2 = explode(" ", $split[$l]);
                                        if ((count($split2) == 2) && ($split2[0] == 0) && !empty($split2[1])) {
                                            if ($count_display) {
                                                $out .= self::LBBR;
                                            }
                                            $count_display++;
                                            $out .= "<a href='mailto:" . $split2[1] . "'>" . $split2[1] . "</a>";
                                        }
                                    }
                                }
                            }
                        }
                        return $out;
                    }
                    if ($itemtype != 'User') {
                        $toadd = '';
                        if (
                            ($itemtype == 'Ticket')
                            && ($data[$ID][0]['id'] > 0)
                        ) {
                            $userdata = getUserName($data[$ID][0]['id'], 2);
                            $toadd    = Html::showToolTip(
                                $userdata["comment"],
                                ['link'    => $userdata["link"],
                                    'display' => false
                                ]
                            );
                        }
                        $usernameformat = formatUserName(
                            $data[$ID][0]['id'],
                            $data[$ID][0]['name'],
                            $data[$ID][0]['realname'],
                            $data[$ID][0]['firstname'],
                            1
                        );
                        return sprintf(__('%1$s %2$s'), $usernameformat, $toadd);
                    }

                    if ($html_output) {
                        $current_users_id = $data[$ID][0]['id'] ?? 0;
                        if ($current_users_id > 0) {
                            return TemplateRenderer::getInstance()->render('components/user/picture.html.twig', [
                                'users_id'      => $current_users_id,
                                'display_login' => true,
                                'force_login'   => true,
                                'avatar_size'   => "avatar-sm",
                            ]);
                        }
                    }
                    break;

                case "glpi_profiles.name":
                    if (
                        ($itemtype == 'User')
                         && ($orig_id == 20)
                    ) {
                        $out           = "";

                        $count_display = 0;
                        $added         = [];
                        for ($k = 0; $k < $data[$ID]['count']; $k++) {
                            if (
                                isset($data[$ID][$k]['name'])
                                && strlen(trim($data[$ID][$k]['name'])) > 0
                                && !in_array(
                                    $data[$ID][$k]['name'] . "-" . $data[$ID][$k]['entities_id'],
                                    $added
                                )
                            ) {
                                $text = sprintf(
                                    __('%1$s - %2$s'),
                                    $data[$ID][$k]['name'],
                                    Dropdown::getDropdownName(
                                        'glpi_entities',
                                        $data[$ID][$k]['entities_id']
                                    )
                                );
                                   $comp = '';
                                if ($data[$ID][$k]['is_recursive']) {
                                    $comp = __('R');
                                    if ($data[$ID][$k]['is_dynamic']) {
                                        $comp = sprintf(__('%1$s%2$s'), $comp, ", ");
                                    }
                                }
                                if ($data[$ID][$k]['is_dynamic']) {
                                    $comp = sprintf(__('%1$s%2$s'), $comp, __('D'));
                                }
                                if (!empty($comp)) {
                                    $text = sprintf(__('%1$s %2$s'), $text, "(" . $comp . ")");
                                }
                                if ($count_display) {
                                    $out .= self::LBBR;
                                }
                                $count_display++;
                                $out     .= $text;
                                $added[]  = $data[$ID][$k]['name'] . "-" . $data[$ID][$k]['entities_id'];
                            }
                        }
                        return $out;
                    }
                    break;

                case "glpi_entities.completename":
                    if ($itemtype == 'User') {
                        $out           = "";
                        $added         = [];
                        $count_display = 0;
                        for ($k = 0; $k < $data[$ID]['count']; $k++) {
                            if (
                                isset($data[$ID][$k]['name'])
                                 && (strlen(trim($data[$ID][$k]['name'])) > 0)
                                 && !in_array(
                                     $data[$ID][$k]['name'] . "-" . $data[$ID][$k]['profiles_id'],
                                     $added
                                 )
                            ) {
                                $text = sprintf(
                                    __('%1$s - %2$s'),
                                    Entity::badgeCompletename($data[$ID][$k]['name']),
                                    Dropdown::getDropdownName(
                                        'glpi_profiles',
                                        $data[$ID][$k]['profiles_id']
                                    )
                                );
                                $comp = '';
                                if ($data[$ID][$k]['is_recursive']) {
                                    $comp = __('R');
                                    if ($data[$ID][$k]['is_dynamic']) {
                                        $comp = sprintf(__('%1$s%2$s'), $comp, ", ");
                                    }
                                }
                                if ($data[$ID][$k]['is_dynamic']) {
                                    $comp = sprintf(__('%1$s%2$s'), $comp, __('D'));
                                }
                                if (!empty($comp)) {
                                    $text = sprintf(__('%1$s %2$s'), $text, "(" . $comp . ")");
                                }
                                if ($count_display) {
                                    $out .= self::LBBR;
                                }
                                $count_display++;
                                $out    .= $text;
                                $added[] = $data[$ID][$k]['name'] . "-" . $data[$ID][$k]['profiles_id'];
                            }
                        }
                        return $out;
                    } elseif (($so["datatype"] ?? "") != "itemlink" && !empty($data[$ID][0]['name'])) {
                        $completename = $data[$ID][0]['name'];
                        if ($html_output) {
                            if (!$_SESSION['glpiuse_flat_dropdowntree_on_search_result']) {
                                $split_name = explode(">", $completename);
                                $entity_name = trim(end($split_name));
                                return Entity::badgeCompletename($entity_name, CommonTreeDropdown::sanitizeSeparatorInCompletename($completename));
                            }
                            return Entity::badgeCompletename($completename);
                        } else { //export
                            if (!$_SESSION['glpiuse_flat_dropdowntree_on_search_result']) {
                                $split_name = explode(">", $completename);
                                $entity_name = trim(end($split_name));
                                return $entity_name;
                            }
                            return Entity::sanitizeSeparatorInCompletename($completename);
                        }
                    }
                    break;
                case $table . ".completename":
                    if (
                        $itemtype != getItemTypeForTable($table)
                        && $data[$ID][0]['name'] != null //column have value in DB
                        && !$_SESSION['glpiuse_flat_dropdowntree_on_search_result'] //user doesn't want the completename
                    ) {
                        $split_name = explode(">", $data[$ID][0]['name']);
                        return trim(end($split_name));
                    }
                    break;
                case "glpi_documenttypes.icon":
                    if (!empty($data[$ID][0]['name'])) {
                        return "<img class='middle' alt='' src='" . $CFG_GLPI["typedoc_icon_dir"] . "/" .
                           $data[$ID][0]['name'] . "'>";
                    }
                    return '';

                case "glpi_documents.filename":
                    $doc = new Document();
                    if ($doc->getFromDB($data['id'])) {
                        return $doc->getDownloadLink();
                    }
                    return NOT_AVAILABLE;

                case "glpi_tickets_tickets.tickets_id_1":
                    $out        = "";
                    $displayed  = [];
                    for ($k = 0; $k < $data[$ID]['count']; $k++) {
                        $linkid = ($data[$ID][$k]['tickets_id_2'] == $data['id'])
                                 ? $data[$ID][$k]['name']
                                 : $data[$ID][$k]['tickets_id_2'];

                        // If link ID is int or integer string, force conversion to int. Coversion to int and then string to compare is needed to ensure it isn't a decimal
                        if (is_numeric($linkid) && ((string)(int)$linkid === (string)$linkid)) {
                            $linkid = (int) $linkid;
                        }
                        if ((is_int($linkid) && $linkid > 0) && !isset($displayed[$linkid])) {
                            $link_text = Dropdown::getDropdownName('glpi_tickets', $linkid);
                            if ($_SESSION["glpiis_ids_visible"] || empty($link_text)) {
                                $link_text = sprintf(__('%1$s (%2$s)'), $link_text, $linkid);
                            }
                            $text  = "<a ";
                            $text .= "href=\"" . Ticket::getFormURLWithID($linkid) . "\">";
                            $text .= $link_text . "</a>";
                            if (count($displayed)) {
                                $out .= self::LBBR;
                            }
                            $displayed[$linkid] = $linkid;
                            $out               .= $text;
                        }
                    }
                    return $out;

                case "glpi_problems.id":
                    if ($so["datatype"] == 'count') {
                        if (
                            ($data[$ID][0]['name'] > 0)
                            && Session::haveRight("problem", Problem::READALL)
                        ) {
                            if ($itemtype == 'ITILCategory') {
                                $options['criteria'][0]['field']      = 7;
                                $options['criteria'][0]['searchtype'] = 'equals';
                                $options['criteria'][0]['value']      = $data['id'];
                                $options['criteria'][0]['link']       = 'AND';
                            } else {
                                $options['criteria'][0]['field']       = 12;
                                $options['criteria'][0]['searchtype']  = 'equals';
                                $options['criteria'][0]['value']       = 'all';
                                $options['criteria'][0]['link']        = 'AND';

                                $options['metacriteria'][0]['itemtype']   = $itemtype;
                                $options['metacriteria'][0]['field']      = self::getOptionNumber(
                                    $itemtype,
                                    'name'
                                );
                                $options['metacriteria'][0]['searchtype'] = 'equals';
                                $options['metacriteria'][0]['value']      = $data['id'];
                                $options['metacriteria'][0]['link']       = 'AND';
                            }

                            $options['reset'] = 'reset';

                            $out  = "<a id='problem$itemtype" . $data['id'] . "' ";
                            $out .= "href=\"" . $CFG_GLPI["root_doc"] . "/front/problem.php?" .
                              Toolbox::append_params($options, '&amp;') . "\">";
                            $out .= $data[$ID][0]['name'] . "</a>";
                            return $out;
                        }
                    }
                    break;

                case "glpi_tickets.id":
                    if ($so["datatype"] == 'count') {
                        if (
                            ($data[$ID][0]['name'] > 0)
                            && Session::haveRight("ticket", Ticket::READALL)
                        ) {
                            if ($itemtype == 'User') {
                            // Requester
                                if ($ID == 'User_60') {
                                    $options['criteria'][0]['field']      = 4;
                                    $options['criteria'][0]['searchtype'] = 'equals';
                                    $options['criteria'][0]['value']      = $data['id'];
                                    $options['criteria'][0]['link']       = 'AND';
                                }

                            // Writer
                                if ($ID == 'User_61') {
                                    $options['criteria'][0]['field']      = 22;
                                    $options['criteria'][0]['searchtype'] = 'equals';
                                    $options['criteria'][0]['value']      = $data['id'];
                                    $options['criteria'][0]['link']       = 'AND';
                                }
                            // Assign
                                if ($ID == 'User_64') {
                                    $options['criteria'][0]['field']      = 5;
                                    $options['criteria'][0]['searchtype'] = 'equals';
                                    $options['criteria'][0]['value']      = $data['id'];
                                    $options['criteria'][0]['link']       = 'AND';
                                }
                            } else if ($itemtype == 'ITILCategory') {
                                $options['criteria'][0]['field']      = 7;
                                $options['criteria'][0]['searchtype'] = 'equals';
                                $options['criteria'][0]['value']      = $data['id'];
                                $options['criteria'][0]['link']       = 'AND';
                            } else {
                                $options['criteria'][0]['field']       = 12;
                                $options['criteria'][0]['searchtype']  = 'equals';
                                $options['criteria'][0]['value']       = 'all';
                                $options['criteria'][0]['link']        = 'AND';

                                $options['metacriteria'][0]['itemtype']   = $itemtype;
                                $options['metacriteria'][0]['field']      = self::getOptionNumber(
                                    $itemtype,
                                    'name'
                                );
                                $options['metacriteria'][0]['searchtype'] = 'equals';
                                $options['metacriteria'][0]['value']      = $data['id'];
                                $options['metacriteria'][0]['link']       = 'AND';
                            }

                            $options['reset'] = 'reset';

                            $out  = "<a id='ticket$itemtype" . $data['id'] . "' ";
                            $out .= "href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?" .
                              Toolbox::append_params($options, '&amp;') . "\">";
                            $out .= $data[$ID][0]['name'] . "</a>";
                            return $out;
                        }
                    }
                    break;

                case "glpi_tickets.time_to_resolve":
                case "glpi_problems.time_to_resolve":
                case "glpi_changes.time_to_resolve":
                case "glpi_tickets.time_to_own":
                case "glpi_tickets.internal_time_to_own":
                case "glpi_tickets.internal_time_to_resolve":
                   // Due date + progress
                    if (in_array($orig_id, [151, 158, 181, 186])) {
                        $out = Html::convDateTime($data[$ID][0]['name']);

                       // No due date in waiting status
                        if ($data[$ID][0]['status'] == CommonITILObject::WAITING) {
                             return '';
                        }
                        if (empty($data[$ID][0]['name'])) {
                            return '';
                        }
                        if (
                            ($data[$ID][0]['status'] == Ticket::SOLVED)
                            || ($data[$ID][0]['status'] == Ticket::CLOSED)
                        ) {
                            return $out;
                        }

                        $itemtype = getItemTypeForTable($table);
                        $item = new $itemtype();
                        $item->getFromDB($data['id']);
                        $percentage  = 0;
                        $totaltime   = 0;
                        $currenttime = 0;
                        $slaField    = 'slas_id';
                        $sla_class   = 'SLA';

                       // define correct sla field
                        switch ($table . '.' . $field) {
                            case "glpi_tickets.time_to_resolve":
                                $slaField = 'slas_id_ttr';
                                $sla_class = 'SLA';
                                break;
                            case "glpi_tickets.time_to_own":
                                $slaField = 'slas_id_tto';
                                $sla_class = 'SLA';
                                break;
                            case "glpi_tickets.internal_time_to_own":
                                $slaField = 'olas_id_tto';
                                $sla_class = 'OLA';
                                break;
                            case "glpi_tickets.internal_time_to_resolve":
                                $slaField = 'olas_id_ttr';
                                $sla_class = 'OLA';
                                break;
                        }

                        switch ($table . '.' . $field) {
                           // If ticket has been taken into account : no progression display
                            case "glpi_tickets.time_to_own":
                            case "glpi_tickets.internal_time_to_own":
                                if (($item->fields['takeintoaccount_delay_stat'] > 0)) {
                                     return $out;
                                }
                                break;
                        }

                        if ($item->isField($slaField) && $item->fields[$slaField] != 0) { // Have SLA
                            $sla = new $sla_class();
                            $sla->getFromDB($item->fields[$slaField]);
                            $currenttime = $sla->getActiveTimeBetween(
                                $item->fields['date'],
                                date('Y-m-d H:i:s')
                            );
                            $totaltime   = $sla->getActiveTimeBetween(
                                $item->fields['date'],
                                $data[$ID][0]['name']
                            );
                            $waitingtime = $slaField === 'slas_id_ttr' ? $item->fields['sla_waiting_duration'] : 0;
                        } else {
                            $calendars_id = Entity::getUsedConfig(
                                'calendars_strategy',
                                $item->fields['entities_id'],
                                'calendars_id',
                                0
                            );
                            $calendar = new Calendar();
                            if ($calendars_id > 0 && $calendar->getFromDB($calendars_id)) { // Ticket entity have calendar
                                $currenttime = $calendar->getActiveTimeBetween(
                                    $item->fields['date'],
                                    date('Y-m-d H:i:s')
                                );
                                $totaltime   = $calendar->getActiveTimeBetween(
                                    $item->fields['date'],
                                    $data[$ID][0]['name']
                                );
                            } else { // No calendar
                                $currenttime = strtotime(date('Y-m-d H:i:s'))
                                                 - strtotime($item->fields['date']);
                                $totaltime   = strtotime($data[$ID][0]['name'])
                                                 - strtotime($item->fields['date']);
                            }
                            $waitingtime = 0;
                        }
                        if (($totaltime - $waitingtime) != 0) {
                            $percentage  = round((100 * ($currenttime - $waitingtime)) / ($totaltime - $waitingtime));
                        } else {
                           // Total time is null : no active time
                            $percentage = 100;
                        }
                        if ($percentage > 100) {
                            $percentage = 100;
                        }
                        $percentage_text = $percentage;

                        $less_warn_limit = 0;
                        $less_warn       = 0;
                        if ($_SESSION['glpiduedatewarning_unit'] == '%') {
                            $less_warn_limit = $_SESSION['glpiduedatewarning_less'];
                            $less_warn       = (100 - $percentage);
                        } else if ($_SESSION['glpiduedatewarning_unit'] == 'hour') {
                            $less_warn_limit = $_SESSION['glpiduedatewarning_less'] * HOUR_TIMESTAMP;
                            $less_warn       = ($totaltime - $currenttime);
                        } else if ($_SESSION['glpiduedatewarning_unit'] == 'day') {
                            $less_warn_limit = $_SESSION['glpiduedatewarning_less'] * DAY_TIMESTAMP;
                            $less_warn       = ($totaltime - $currenttime);
                        }

                        $less_crit_limit = 0;
                        $less_crit       = 0;
                        if ($_SESSION['glpiduedatecritical_unit'] == '%') {
                            $less_crit_limit = $_SESSION['glpiduedatecritical_less'];
                            $less_crit       = (100 - $percentage);
                        } else if ($_SESSION['glpiduedatecritical_unit'] == 'hour') {
                            $less_crit_limit = $_SESSION['glpiduedatecritical_less'] * HOUR_TIMESTAMP;
                            $less_crit       = ($totaltime - $currenttime);
                        } else if ($_SESSION['glpiduedatecritical_unit'] == 'day') {
                            $less_crit_limit = $_SESSION['glpiduedatecritical_less'] * DAY_TIMESTAMP;
                            $less_crit       = ($totaltime - $currenttime);
                        }

                        $color = $_SESSION['glpiduedateok_color'];
                        if ($less_crit < $less_crit_limit) {
                            $color = $_SESSION['glpiduedatecritical_color'];
                        } else if ($less_warn < $less_warn_limit) {
                            $color = $_SESSION['glpiduedatewarning_color'];
                        }

                        if (!isset($so['datatype'])) {
                            $so['datatype'] = 'progressbar';
                        }

                        $progressbar_data = [
                            'text'         => Html::convDateTime($data[$ID][0]['name']),
                            'percent'      => $percentage,
                            'percent_text' => $percentage_text,
                            'color'        => $color
                        ];
                    }
                    break;

                case "glpi_softwarelicenses.number":
                    if ($data[$ID][0]['min'] == -1) {
                        return __('Unlimited');
                    }
                    if (empty($data[$ID][0]['name'])) {
                        return 0;
                    }
                    return $data[$ID][0]['name'];

                case "glpi_auth_tables.name":
                    return Auth::getMethodName(
                        $data[$ID][0]['name'],
                        $data[$ID][0]['auths_id'],
                        1,
                        $data[$ID][0]['ldapname'] . $data[$ID][0]['mailname']
                    );

                case "glpi_reservationitems.comment":
                    if (empty($data[$ID][0]['name'])) {
                        $text = __('None');
                    } else {
                        $text = Html::resume_text($data[$ID][0]['name']);
                    }
                    if (Session::haveRight('reservation', UPDATE)) {
                        return "<a title=\"" . __s('Modify the comment') . "\"
                           href='" . ReservationItem::getFormURLWithID($data['refID']) . "' >" . $text . "</a>";
                    }
                    return $text;

                case 'glpi_crontasks.description':
                    $tmp = new CronTask();
                    return $tmp->getDescription($data[$ID][0]['name']);

                case 'glpi_changes.status':
                    $status = Change::getStatus($data[$ID][0]['name']);
                    return "<span class='text-nowrap'>" .
                      Change::getStatusIcon($data[$ID][0]['name']) . "&nbsp;$status" .
                      "</span>";

                case 'glpi_problems.status':
                    $status = Problem::getStatus($data[$ID][0]['name']);
                    return "<span class='text-nowrap'>" .
                      Problem::getStatusIcon($data[$ID][0]['name']) . "&nbsp;$status" .
                      "</span>";

                case 'glpi_tickets.status':
                    $status = Ticket::getStatus($data[$ID][0]['name']);
                    return "<span class='text-nowrap'>" .
                      Ticket::getStatusIcon($data[$ID][0]['name']) . "&nbsp;$status" .
                      "</span>";

                case 'glpi_projectstates.name':
                    $out = '';
                    $name = $data[$ID][0]['name'];
                    if (isset($data[$ID][0]['trans'])) {
                        $name = $data[$ID][0]['trans'];
                    }
                    if ($itemtype == 'ProjectState') {
                        $out =   "<a href='" . ProjectState::getFormURLWithID($data[$ID][0]["id"]) . "'>" . $name . "</a></div>";
                    } else {
                        $out = $name;
                    }
                    return $out;

                case 'glpi_items_tickets.items_id':
                case 'glpi_items_problems.items_id':
                case 'glpi_changes_items.items_id':
                case 'glpi_certificates_items.items_id':
                case 'glpi_appliances_items.items_id':
                    if (!empty($data[$ID])) {
                        $items = [];
                        foreach ($data[$ID] as $key => $val) {
                            if (is_numeric($key)) {
                                if (
                                    !empty($val['itemtype'])
                                    && ($item = getItemForItemtype($val['itemtype']))
                                ) {
                                    if ($item->getFromDB($val['name'])) {
                                        $items[] = $item->getLink(['comments' => true]);
                                    }
                                }
                            }
                        }
                        if (!empty($items)) {
                            return implode("<br>", $items);
                        }
                    }
                    return '';

                case 'glpi_items_tickets.itemtype':
                case 'glpi_items_problems.itemtype':
                    if (!empty($data[$ID])) {
                        $itemtypes = [];
                        foreach ($data[$ID] as $key => $val) {
                            if (is_numeric($key)) {
                                if (
                                    !empty($val['name'])
                                    && ($item = getItemForItemtype($val['name']))
                                ) {
                                    $item = new $val['name']();
                                    $name = $item->getTypeName();
                                    $itemtypes[] = __($name);
                                }
                            }
                        }
                        if (!empty($itemtypes)) {
                            return implode("<br>", $itemtypes);
                        }
                    }

                    return '';

                case 'glpi_tickets.name':
                case 'glpi_problems.name':
                case 'glpi_changes.name':
                    if (
                        isset($data[$ID][0]['id'])
                        && isset($data[$ID][0]['status'])
                    ) {
                        $link = $itemtype::getFormURLWithID($data[$ID][0]['id']);

                        $out  = "<a id='$itemtype" . $data[$ID][0]['id'] . "' href=\"" . $link;
                        // Force solution tab if solved
                        if ($item = getItemForItemtype($itemtype)) {
                            /** @var CommonITILObject $item */
                            if (in_array($data[$ID][0]['status'], $item->getSolvedStatusArray())) {
                                $out .= "&amp;forcetab=$itemtype$2";
                            }
                        }
                        $out .= "\">";
                        $name = $data[$ID][0]['name'];
                        if (
                            $_SESSION["glpiis_ids_visible"]
                            || empty($data[$ID][0]['name'])
                        ) {
                            $name = sprintf(__('%1$s (%2$s)'), $name, $data[$ID][0]['id']);
                        }
                        $out    .= $name . "</a>";

                        // Add tooltip
                        $id = $data[$ID][0]['id'];
                        $itemtype = getItemTypeForTable($table);
                        $out     = sprintf(
                            __('%1$s %2$s'),
                            $out,
                            Html::showToolTip(
                                __('Loading...'),
                                [
                                    'applyto' => $itemtype . $data[$ID][0]['id'],
                                    'display' => false,
                                    'url'     => "/ajax/get_item_content.php?itemtype=$itemtype&items_id=$id"
                                ]
                            )
                        );
                        return $out;
                    }
                    break;

                case 'glpi_ticketvalidations.status':
                    $out   = '';
                    for ($k = 0; $k < $data[$ID]['count']; $k++) {
                        if ($data[$ID][$k]['name']) {
                             $status  = TicketValidation::getStatus($data[$ID][$k]['name']);
                             $bgcolor = TicketValidation::getStatusColor($data[$ID][$k]['name']);
                             $out    .= (empty($out) ? '' : self::LBBR) .
                                 "<div style=\"background-color:" . $bgcolor . ";\">" . $status . '</div>';
                        }
                    }
                    return $out;

                case 'glpi_changevalidations.status':
                    $out   = '';
                    for ($k = 0; $k < $data[$ID]['count']; $k++) {
                        if ($data[$ID][$k]['name']) {
                             $status  = ChangeValidation::getStatus($data[$ID][$k]['name']);
                             $bgcolor = ChangeValidation::getStatusColor($data[$ID][$k]['name']);
                             $out    .= (empty($out) ? '' : self::LBBR) .
                                 "<div style=\"background-color:" . $bgcolor . ";\">" . $status . '</div>';
                        }
                    }
                    return $out;

                case 'glpi_cables.color':
                   //do not display 'real' value (#.....)
                    return "";

                case 'glpi_ticketsatisfactions.satisfaction':
                    if ($html_output) {
                        return TicketSatisfaction::displaySatisfaction($data[$ID][0]['name']);
                    }
                    break;

                case 'glpi_projects._virtual_planned_duration':
                    return Html::timestampToString(
                        ProjectTask::getTotalPlannedDurationForProject($data["id"]),
                        false
                    );

                case 'glpi_projects._virtual_effective_duration':
                    return Html::timestampToString(
                        ProjectTask::getTotalEffectiveDurationForProject($data["id"]),
                        false
                    );

                case 'glpi_cartridgeitems._virtual':
                    return Cartridge::getCount(
                        $data["id"],
                        $data[$ID][0]['alarm_threshold'],
                        !$html_output
                    );

                case 'glpi_printers._virtual':
                    return Cartridge::getCountForPrinter(
                        $data["id"],
                        !$html_output
                    );

                case 'glpi_consumableitems._virtual':
                    return Consumable::getCount(
                        $data["id"],
                        $data[$ID][0]['alarm_threshold'],
                        !$html_output
                    );

                case 'glpi_links._virtual':
                     $out = '';
                     $link = new Link();
                    if (
                        ($item = getItemForItemtype($itemtype))
                         && $item->getFromDB($data['id'])
                    ) {
                        $data = Link::getLinksDataForItem($item);
                        $count_display = 0;
                        foreach ($data as $val) {
                            $links = Link::getAllLinksFor($item, $val);
                            foreach ($links as $link) {
                                if ($count_display) {
                                    $out .=  self::LBBR;
                                }
                                $out .= $link;
                                $count_display++;
                            }
                        }
                    }
                    return $out;

                case 'glpi_reservationitems._virtual':
                    if ($data[$ID][0]['is_active']) {
                        return "<a href='reservation.php?reservationitems_id=" .
                                          $data["refID"] . "' title=\"" . __s('See planning') . "\">" .
                                          "<i class='far fa-calendar-alt'></i><span class='sr-only'>" . __('See planning') . "</span></a>";
                    } else {
                        return '';
                    }

                case "glpi_tickets.priority":
                case "glpi_problems.priority":
                case "glpi_changes.priority":
                case "glpi_projects.priority":
                    $index = $data[$ID][0]['name'];
                    $color = $_SESSION["glpipriority_$index"];
                    $name  = CommonITILObject::getPriorityName($index);
                    return "<div class='priority_block' style='border-color: $color'>
                        <span style='background: $color'></span>&nbsp;$name
                       </div>";
            }
        }

       //// Default case

        if (
            $itemtype == 'Ticket'
            && Session::getCurrentInterface() == 'helpdesk'
            && $orig_id == 8
            && !empty($anon_name = Group::getAnonymizedName(
                $itemtype::getById($data['id'])->getEntityId()
            ))
        ) {
           // Assigned groups
            return $anon_name;
        }

       // Link with plugin tables : need to know left join structure
        if (isset($table) && isset($field)) {
            if (preg_match("/^glpi_plugin_([a-z0-9]+)/", $table . '.' . $field, $matches)) {
                if (count($matches) == 2) {
                    $plug     = $matches[1];
                    $out = Plugin::doOneHook(
                        $plug,
                        'giveItem',
                        $itemtype,
                        $orig_id,
                        $data,
                        $ID
                    );
                    if (!empty($out)) {
                        return $out;
                    }
                }
            }
        }
        $unit = '';
        if (isset($so['unit'])) {
            $unit = $so['unit'];
        }

       // Preformat items
        if (isset($so["datatype"])) {
            switch ($so["datatype"]) {
                case "itemlink":
                    $linkitemtype  = getItemTypeForTable($so["table"]);

                    $out           = "";
                    $count_display = 0;
                    $separate      = self::LBBR;
                    if (isset($so['splititems']) && $so['splititems']) {
                        $separate = self::LBHR;
                    }

                    for ($k = 0; $k < $data[$ID]['count']; $k++) {
                        if (isset($data[$ID][$k]['id'])) {
                            if ($count_display) {
                                $out .= $separate;
                            }
                            $count_display++;
                            $page  = $linkitemtype::getFormURLWithID($data[$ID][$k]['id']);
                            $name  = $data[$ID][$k]['name'];
                            if ($_SESSION["glpiis_ids_visible"] || empty($data[$ID][$k]['name'])) {
                                 $name = sprintf(__('%1$s (%2$s)'), $name, $data[$ID][$k]['id']);
                            }
                            if (isset($field) && $field === 'completename') {
                                $chunks = preg_split('/ > /', $name);
                                $completename = '';
                                foreach ($chunks as $key => $element_name) {
                                    $class = $key === array_key_last($chunks) ? '' : 'class="text-muted"';
                                    $separator = $key === array_key_last($chunks) ? '' : ' &gt; ';
                                    $completename .= sprintf('<span %s>%s</span>%s', $class, $element_name, $separator);
                                }
                                $name = $completename;
                            }

                            $out  .= "<a id='" . $linkitemtype . "_" . $data['id'] . "_" .
                                $data[$ID][$k]['id'] . "' href='$page'>" .
                               $name . "</a>";
                        }
                    }
                    return $out;

                case "text":
                    $separate = self::LBBR;
                    if (isset($so['splititems']) && $so['splititems']) {
                        $separate = self::LBHR;
                    }

                    $out           = '';
                    $count_display = 0;
                    for ($k = 0; $k < $data[$ID]['count']; $k++) {
                        if (strlen(trim((string)$data[$ID][$k]['name'])) > 0) {
                            if ($count_display) {
                                $out .= $separate;
                            }
                            $count_display++;

                            $plaintext = '';
                            if (isset($so['htmltext']) && $so['htmltext']) {
                                if ($html_output) {
                                    $plaintext = RichText::getTextFromHtml($data[$ID][$k]['name'], false, true, $html_output);
                                } else {
                                    $plaintext = RichText::getTextFromHtml($data[$ID][$k]['name'], true, true, $html_output);
                                }
                            } else {
                                $plaintext = nl2br($data[$ID][$k]['name']);
                            }

                            if ($html_output && (Toolbox::strlen($plaintext) > $CFG_GLPI['cut'])) {
                                $rand = mt_rand();
                                $popup_params = [
                                    'display'       => false,
                                    'awesome-class' => 'fa-comments',
                                    'autoclose'     => false,
                                    'onclick'       => true,
                                ];
                                $out .= sprintf(
                                    __('%1$s %2$s'),
                                    "<span id='text$rand'>" . Html::resume_text($plaintext, $CFG_GLPI['cut']) . '</span>',
                                    Html::showToolTip(
                                        '<div class="fup-popup">' . RichText::getEnhancedHtml($data[$ID][$k]['name']) . '</div>',
                                        $popup_params
                                    )
                                );
                            } else {
                                $out .= $plaintext;
                            }
                        }
                    }
                    return $out;

                case "date":
                case "date_delay":
                    $out   = '';
                    for ($k = 0; $k < $data[$ID]['count']; $k++) {
                        if (
                            is_null($data[$ID][$k]['name'])
                            && isset($so['emptylabel']) && $so['emptylabel']
                        ) {
                            $out .= (empty($out) ? '' : self::LBBR) . $so['emptylabel'];
                        } else {
                            $out .= (empty($out) ? '' : self::LBBR) . Html::convDate($data[$ID][$k]['name']);
                        }
                    }
                    $out = "<span class='text-nowrap'>$out</span>";
                    return $out;

                case "datetime":
                    $out   = '';
                    for ($k = 0; $k < $data[$ID]['count']; $k++) {
                        if (
                            is_null($data[$ID][$k]['name'])
                            && isset($so['emptylabel']) && $so['emptylabel']
                        ) {
                            $out .= (empty($out) ? '' : self::LBBR) . $so['emptylabel'];
                        } else {
                            $out .= (empty($out) ? '' : self::LBBR) . Html::convDateTime($data[$ID][$k]['name']);
                        }
                    }
                    $out = "<span class='text-nowrap'>$out</span>";
                    return $out;

                case "timestamp":
                    $withseconds = false;
                    if (isset($so['withseconds'])) {
                        $withseconds = $so['withseconds'];
                    }
                    $withdays = true;
                    if (isset($so['withdays'])) {
                        $withdays = $so['withdays'];
                    }

                    $out   = '';
                    for ($k = 0; $k < $data[$ID]['count']; $k++) {
                        $out .= (empty($out) ? '' : '<br>') . Html::timestampToString(
                            $data[$ID][$k]['name'],
                            $withseconds,
                            $withdays
                        );
                    }
                    $out = "<span class='text-nowrap'>$out</span>";
                    return $out;

                case "email":
                    $out           = '';
                    $count_display = 0;
                    for ($k = 0; $k < $data[$ID]['count']; $k++) {
                        if ($count_display) {
                             $out .= self::LBBR;
                        }
                        $count_display++;
                        if (!empty($data[$ID][$k]['name'])) {
                            $out .= (empty($out) ? '' : self::LBBR);
                            $out .= "<a href='mailto:" . Html::entities_deep($data[$ID][$k]['name']) . "'>" . $data[$ID][$k]['name'];
                            $out .= "</a>";
                        }
                    }
                    return (empty($out) ? '' : $out);

                case "weblink":
                    $orig_link = trim((string)$data[$ID][0]['name']);
                    if (!empty($orig_link) && Toolbox::isValidWebUrl($orig_link)) {
                       // strip begin of link
                        $link = preg_replace('/https?:\/\/(www[^\.]*\.)?/', '', $orig_link);
                        $link = preg_replace('/\/$/', '', $link);
                        if (Toolbox::strlen($link) > $CFG_GLPI["url_maxlength"]) {
                             $link = Toolbox::substr($link, 0, $CFG_GLPI["url_maxlength"]) . "...";
                        }
                        return "<a href=\"" . Toolbox::formatOutputWebLink($orig_link) . "\" target='_blank'>$link</a>";
                    }
                    return '';

                case "count":
                case "number":
                case "integer":
                case "mio":
                    $out           = "";
                    $count_display = 0;
                    for ($k = 0; $k < $data[$ID]['count']; $k++) {
                        if (strlen(trim((string)$data[$ID][$k]['name'])) > 0) {
                            if ($count_display) {
                                $out .= self::LBBR;
                            }
                            $count_display++;
                            if (
                                isset($so['toadd'])
                                && isset($so['toadd'][$data[$ID][$k]['name']])
                            ) {
                                $out .= $so['toadd'][$data[$ID][$k]['name']];
                            } else {
                                $out .= Dropdown::getValueWithUnit($data[$ID][$k]['name'], $unit);
                            }
                        }
                    }
                    $out = "<span class='text-nowrap'>$out</span>";
                    return $out;

                case "decimal":
                    $out           = "";
                    $count_display = 0;
                    for ($k = 0; $k < $data[$ID]['count']; $k++) {
                        if (strlen(trim((string)$data[$ID][$k]['name'])) > 0) {
                            if ($count_display) {
                                $out .= self::LBBR;
                            }
                            $count_display++;
                            if (
                                isset($so['toadd'])
                                && isset($so['toadd'][$data[$ID][$k]['name']])
                            ) {
                                $out .= $so['toadd'][$data[$ID][$k]['name']];
                            } else {
                                $out .= Dropdown::getValueWithUnit($data[$ID][$k]['name'], $unit, $CFG_GLPI["decimal_number"]);
                            }
                        }
                    }
                    $out = "<span class='text-nowrap'>$out</span>";
                    return $out;

                case "bool":
                    $out           = "";
                    $count_display = 0;
                    for ($k = 0; $k < $data[$ID]['count']; $k++) {
                        if (strlen(trim((string)$data[$ID][$k]['name'])) > 0) {
                            if ($count_display) {
                                $out .= self::LBBR;
                            }
                            $count_display++;
                            $out .= Dropdown::getYesNo($data[$ID][$k]['name']);
                        }
                    }
                    return $out;

                case "itemtypename":
                    if ($obj = getItemForItemtype($data[$ID][0]['name'])) {
                        return $obj->getTypeName();
                    }
                    return $data[$ID][0]['name'];

                case "language":
                    if (isset($CFG_GLPI['languages'][$data[$ID][0]['name']])) {
                        return $CFG_GLPI['languages'][$data[$ID][0]['name']][0];
                    }
                    return __('Default value');
                case 'progressbar':
                    if (!isset($progressbar_data)) {
                        $bar_color = 'green';
                        $percent   = ltrim(($data[$ID][0]['name'] ?? ""), 0);
                        $progressbar_data = [
                            'percent'      => $percent,
                            'percent_text' => $percent,
                            'color'        => $bar_color,
                            'text'         => ''
                        ];
                    }

                    $out = "";
                    if ($progressbar_data['percent'] !== null) {
                        $out = <<<HTML
                  <span class='text-nowrap'>
                     {$progressbar_data['text']}
                  </span>
                  <div class="progress" style="height: 16px">
                     <div class="progress-bar progress-bar-striped" role="progressbar"
                          style="width: {$progressbar_data['percent']}%; background-color: {$progressbar_data['color']};"
                          aria-valuenow="{$progressbar_data['percent']}"
                          aria-valuemin="0" aria-valuemax="100">
                        {$progressbar_data['percent_text']}%
                     </div>
                  </div>
HTML;
                    }

                    return $out;
                break;
            }
        }
       // Manage items with need group by / group_concat
        $out           = "";
        $count_display = 0;
        $separate      = self::LBBR;
        if (isset($so['splititems']) && $so['splititems']) {
            $separate = self::LBHR;
        }
        for ($k = 0; $k < $data[$ID]['count']; $k++) {
            if ($count_display) {
                $out .= $separate;
            }
            $count_display++;
           // Get specific display if available
            if (isset($table) && isset($field)) {
                $itemtype = getItemTypeForTable($table);
                if ($item = getItemForItemtype($itemtype)) {
                    $tmpdata  = $data[$ID][$k];
                   // Copy name to real field
                    $tmpdata[$field] = $data[$ID][$k]['name'] ?? '';

                    $specific = $item->getSpecificValueToDisplay(
                        $field,
                        $tmpdata,
                        [
                            'html'      => true,
                            'searchopt' => $so,
                            'raw_data'  => $data
                        ]
                    );
                }
            }
            if (!empty($specific)) {
                $out .= $specific;
            } else {
                if (
                    isset($so['toadd'])
                    && isset($so['toadd'][$data[$ID][$k]['name']])
                ) {
                    $out .= $so['toadd'][$data[$ID][$k]['name']];
                } else {
                    // Trans field exists
                    if (isset($data[$ID][$k]['trans']) && !empty($data[$ID][$k]['trans'])) {
                        $out .= $data[$ID][$k]['trans'];
                    } elseif (isset($data[$ID][$k]['trans_completename']) && !empty($data[$ID][$k]['trans_completename'])) {
                        $out .= CommonTreeDropdown::sanitizeSeparatorInCompletename($data[$ID][$k]['trans_completename']);
                    } elseif (isset($data[$ID][$k]['trans_name']) && !empty($data[$ID][$k]['trans_name'])) {
                        $out .= $data[$ID][$k]['trans_name'];
                    } else {
                        $value = $data[$ID][$k]['name'];
                        $out .= $so['field'] === 'completename'
                            ? CommonTreeDropdown::sanitizeSeparatorInCompletename($value)
                            : $value;
                    }
                }
            }
        }
        return $out;
    }


    /**
     * Reset save searches
     *
     * @return void
     **/
    public static function resetSaveSearch()
    {

        unset($_SESSION['glpisearch']);
        $_SESSION['glpisearch']       = [];
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
        $default_values = [];

        $default_values["start"]       = 0;
        $default_values["order"]       = "ASC";
        $default_values["sort"]        = 1;
        $default_values["is_deleted"]  = 0;
        $default_values["as_map"]      = 0;
        $default_values["browse"]      = 0;

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

        if ($itemtype != AllAssets::getType() && class_exists($itemtype)) {
           // retrieve default values for current itemtype
            $itemtype_default_values = [];
            if (method_exists($itemtype, 'getDefaultSearchRequest')) {
                $itemtype_default_values = call_user_func([$itemtype, 'getDefaultSearchRequest']);
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
            $user_default_values = SavedSearch_User::getDefault(Session::getLoginUserID(), $itemtype);
            if ($user_default_values) {
                $_SESSION['glpisearch'][$itemtype] = [];
               // Only get datas for bookmarks
                if ($forcebookmark) {
                    $params = $user_default_values;
                } else {
                    $bookmark = new SavedSearch();
                    $bookmark->load($user_default_values['savedsearches_id']);
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
                    && ($key == 'is_deleted' || $key == 'as_map' || $key == 'browse' || !isset($saved_params['criteria'])) // retrieve session only if not a new request
                    && isset($_SESSION['glpisearch'][$itemtype][$key])
                ) {
                    $params[$key] = $_SESSION['glpisearch'][$itemtype][$key];
                } else {
                    $params[$key]                    = $val;
                    $_SESSION['glpisearch'][$itemtype][$key] = $val;
                }
            }
        }

        return self::cleanParams($params);
    }

    public static function cleanParams(array $params): array
    {
        $int_params = [
            'sort'
        ];

        foreach ($params as $key => &$val) {
            if (in_array($key, $int_params)) {
                if (is_array($val)) {
                    foreach ($val as &$subval) {
                        $subval = (int)$subval;
                    }
                } else {
                    $val = (int)$val;
                }
            }
        }

        return $params;
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
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $options = self::getOptions($itemtype, $withplugins);
        $todel   = [];

        if (
            !Session::haveRight('infocom', $action)
            && Infocom::canApplyOn($itemtype)
        ) {
            $itemstodel = Infocom::getSearchOptionsToAdd($itemtype);
            $todel      = array_merge($todel, array_keys($itemstodel));
        }

        if (
            !Session::haveRight('contract', $action)
            && in_array($itemtype, $CFG_GLPI["contract_types"])
        ) {
            $itemstodel = Contract::getSearchOptionsToAdd();
            $todel      = array_merge($todel, array_keys($itemstodel));
        }

        if (
            !Session::haveRight('document', $action)
            && Document::canApplyOn($itemtype)
        ) {
            $itemstodel = Document::getSearchOptionsToAdd();
            $todel      = array_merge($todel, array_keys($itemstodel));
        }

       // do not show priority if you don't have right in profile
        if (
            ($itemtype == 'Ticket')
            && ($action == UPDATE)
            && !Session::haveRight('ticket', Ticket::CHANGEPRIORITY)
        ) {
            $todel[] = 3;
        }

        if ($itemtype == 'Computer') {
            if (!Session::haveRight('networking', $action)) {
                $itemstodel = NetworkPort::getSearchOptionsToAdd($itemtype);
                $todel      = array_merge($todel, array_keys($itemstodel));
            }
        }
        if (!Session::haveRight(strtolower($itemtype), READNOTE)) {
            $todel[] = 90;
        }

        if (count($todel)) {
            foreach ($todel as $ID) {
                if (isset($options[$ID])) {
                    unset($options[$ID]);
                }
            }
        }

        return $options;
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

        $table = $itemtype::getTable();
        $opts  = self::getOptions($itemtype);

        foreach ($opts as $num => $opt) {
            if (
                is_array($opt) && isset($opt['table'])
                && ($opt['table'] == $table)
                && ($opt['field'] == $field)
            ) {
                return $num;
            }
        }
        return 0;
    }


    /**
     * Get the SEARCH_OPTION array
     *
     * @param string  $itemtype     Item type
     * @param boolean $withplugins  Get search options from plugins (true by default)
     *
     * @return array The reference to the array of search options for the given item type
     **/
    public static function &getOptions($itemtype, $withplugins = true)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $item = null;

        if (!isset(self::$search[$itemtype])) {
           // standard type first
            switch ($itemtype) {
                case 'Internet':
                    self::$search[$itemtype]['common']            = __('Characteristics');

                    self::$search[$itemtype][1]['table']          = 'networkport_types';
                    self::$search[$itemtype][1]['field']          = 'name';
                    self::$search[$itemtype][1]['name']           = __('Name');
                    self::$search[$itemtype][1]['datatype']       = 'itemlink';
                    self::$search[$itemtype][1]['searchtype']     = 'contains';

                    self::$search[$itemtype][2]['table']          = 'networkport_types';
                    self::$search[$itemtype][2]['field']          = 'id';
                    self::$search[$itemtype][2]['name']           = __('ID');
                    self::$search[$itemtype][2]['searchtype']     = 'contains';

                    self::$search[$itemtype][31]['table']         = 'glpi_states';
                    self::$search[$itemtype][31]['field']         = 'completename';
                    self::$search[$itemtype][31]['name']          = __('Status');

                    self::$search[$itemtype] += NetworkPort::getSearchOptionsToAdd('networkport_types');
                    break;

                case AllAssets::getType():
                    self::$search[$itemtype]['common']            = __('Characteristics');

                    self::$search[$itemtype][1]['table']          = 'asset_types';
                    self::$search[$itemtype][1]['field']          = 'name';
                    self::$search[$itemtype][1]['name']           = __('Name');
                    self::$search[$itemtype][1]['datatype']       = 'itemlink';
                    self::$search[$itemtype][1]['searchtype']     = 'contains';

                    self::$search[$itemtype][2]['table']          = 'asset_types';
                    self::$search[$itemtype][2]['field']          = 'id';
                    self::$search[$itemtype][2]['name']           = __('ID');
                    self::$search[$itemtype][2]['searchtype']     = 'contains';

                    self::$search[$itemtype][31]['table']         = 'glpi_states';
                    self::$search[$itemtype][31]['field']         = 'completename';
                    self::$search[$itemtype][31]['name']          = __('Status');

                    self::$search[$itemtype] += Location::getSearchOptionsToAdd();

                    self::$search[$itemtype][5]['table']          = 'asset_types';
                    self::$search[$itemtype][5]['field']          = 'serial';
                    self::$search[$itemtype][5]['name']           = __('Serial number');

                    self::$search[$itemtype][6]['table']          = 'asset_types';
                    self::$search[$itemtype][6]['field']          = 'otherserial';
                    self::$search[$itemtype][6]['name']           = __('Inventory number');

                    self::$search[$itemtype][16]['table']         = 'asset_types';
                    self::$search[$itemtype][16]['field']         = 'comment';
                    self::$search[$itemtype][16]['name']          = __('Comments');
                    self::$search[$itemtype][16]['datatype']      = 'text';

                    self::$search[$itemtype][70]['table']         = 'glpi_users';
                    self::$search[$itemtype][70]['field']         = 'name';
                    self::$search[$itemtype][70]['name']          = User::getTypeName(1);

                    self::$search[$itemtype][7]['table']          = 'asset_types';
                    self::$search[$itemtype][7]['field']          = 'contact';
                    self::$search[$itemtype][7]['name']           = __('Alternate username');
                    self::$search[$itemtype][7]['datatype']       = 'string';

                    self::$search[$itemtype][8]['table']          = 'asset_types';
                    self::$search[$itemtype][8]['field']          = 'contact_num';
                    self::$search[$itemtype][8]['name']           = __('Alternate username number');
                    self::$search[$itemtype][8]['datatype']       = 'string';

                    self::$search[$itemtype][71]['table']         = 'glpi_groups';
                    self::$search[$itemtype][71]['field']         = 'completename';
                    self::$search[$itemtype][71]['name']          = Group::getTypeName(1);

                    self::$search[$itemtype][19]['table']         = 'asset_types';
                    self::$search[$itemtype][19]['field']         = 'date_mod';
                    self::$search[$itemtype][19]['name']          = __('Last update');
                    self::$search[$itemtype][19]['datatype']      = 'datetime';
                    self::$search[$itemtype][19]['massiveaction'] = false;

                    self::$search[$itemtype][23]['table']         = 'glpi_manufacturers';
                    self::$search[$itemtype][23]['field']         = 'name';
                    self::$search[$itemtype][23]['name']          = Manufacturer::getTypeName(1);

                    self::$search[$itemtype][24]['table']         = 'glpi_users';
                    self::$search[$itemtype][24]['field']         = 'name';
                    self::$search[$itemtype][24]['linkfield']     = 'users_id_tech';
                    self::$search[$itemtype][24]['name']          = __('Technician in charge');
                    self::$search[$itemtype][24]['condition']     = ['is_assign' => 1];

                    self::$search[$itemtype][49]['table']          = 'glpi_groups';
                    self::$search[$itemtype][49]['field']          = 'completename';
                    self::$search[$itemtype][49]['linkfield']      = 'groups_id_tech';
                    self::$search[$itemtype][49]['name']           = __('Group in charge');
                    self::$search[$itemtype][49]['condition']      = ['is_assign' => 1];
                    self::$search[$itemtype][49]['datatype']       = 'dropdown';

                    self::$search[$itemtype][80]['table']         = 'glpi_entities';
                    self::$search[$itemtype][80]['field']         = 'completename';
                    self::$search[$itemtype][80]['name']          = Entity::getTypeName(1);
                    break;

                default:
                    if ($item = getItemForItemtype($itemtype)) {
                        self::$search[$itemtype] = $item->searchOptions();
                    }
                    break;
            }

            if (
                Session::getLoginUserID()
                && in_array($itemtype, $CFG_GLPI["ticket_types"])
            ) {
                self::$search[$itemtype]['tracking']          = ['name' => __('Assistance')];

                self::$search[$itemtype][60]['table']         = 'glpi_tickets';
                self::$search[$itemtype][60]['field']         = 'id';
                self::$search[$itemtype][60]['datatype']      = 'count';
                self::$search[$itemtype][60]['name']          = _x('quantity', 'Number of tickets');
                self::$search[$itemtype][60]['forcegroupby']  = true;
                self::$search[$itemtype][60]['usehaving']     = true;
                self::$search[$itemtype][60]['massiveaction'] = false;
                self::$search[$itemtype][60]['joinparams']    = ['beforejoin'
                                                              => ['table'
                                                                        => 'glpi_items_tickets',
                                                                  'joinparams'
                                                                        => ['jointype'
                                                                                  => 'itemtype_item'
                                                                        ]
                                                              ],
                    'condition'
                                                              => getEntitiesRestrictRequest(
                                                                  'AND',
                                                                  'NEWTABLE'
                                                              )
                ];

                self::$search[$itemtype][140]['table']         = 'glpi_problems';
                self::$search[$itemtype][140]['field']         = 'id';
                self::$search[$itemtype][140]['datatype']      = 'count';
                self::$search[$itemtype][140]['name']          = _x('quantity', 'Number of problems');
                self::$search[$itemtype][140]['forcegroupby']  = true;
                self::$search[$itemtype][140]['usehaving']     = true;
                self::$search[$itemtype][140]['massiveaction'] = false;
                self::$search[$itemtype][140]['joinparams']    = ['beforejoin'
                                                              => ['table'
                                                                        => 'glpi_items_problems',
                                                                  'joinparams'
                                                                        => ['jointype'
                                                                                  => 'itemtype_item'
                                                                        ]
                                                              ],
                    'condition'
                                                              => getEntitiesRestrictRequest(
                                                                  'AND',
                                                                  'NEWTABLE'
                                                              )
                ];

                self::$search[$itemtype][141]['table']         = 'glpi_changes';
                self::$search[$itemtype][141]['field']         = 'id';
                self::$search[$itemtype][141]['datatype']      = 'count';
                self::$search[$itemtype][141]['name']          = _x('quantity', 'Number of changes');
                self::$search[$itemtype][141]['forcegroupby']  = true;
                self::$search[$itemtype][141]['usehaving']     = true;
                self::$search[$itemtype][141]['massiveaction'] = false;
                self::$search[$itemtype][141]['joinparams']    = ['beforejoin'
                => ['table'
                    => 'glpi_changes_items',
                    'joinparams'
                        => ['jointype'
                        => 'itemtype_item'
                        ]
                ],
                    'condition'
                    => getEntitiesRestrictRequest(
                        'AND',
                        'NEWTABLE'
                    )
                ];
            }

            $fn_append_options = static function ($new_options) use ($itemtype) {
                // Check duplicate keys between new options and existing options
                $duplicate_keys = array_intersect(array_keys(self::$search[$itemtype]), array_keys($new_options));
                if (count($duplicate_keys) > 0) {
                    trigger_error(
                        sprintf(
                            'Duplicate keys found in search options for item type %s: %s',
                            $itemtype,
                            implode(', ', $duplicate_keys)
                        ),
                        E_USER_WARNING
                    );
                }
                self::$search[$itemtype] += $new_options;
            };

            if (
                in_array($itemtype, $CFG_GLPI["networkport_types"])
                || ($itemtype == AllAssets::getType())
            ) {
                $fn_append_options(NetworkPort::getSearchOptionsToAdd($itemtype));
            }

            if (
                in_array($itemtype, $CFG_GLPI["contract_types"])
                || ($itemtype == AllAssets::getType())
            ) {
                $fn_append_options(Contract::getSearchOptionsToAdd());
            }

            if (
                Document::canApplyOn($itemtype)
                || ($itemtype == AllAssets::getType())
            ) {
                $fn_append_options(Document::getSearchOptionsToAdd());
            }

            if (
                Infocom::canApplyOn($itemtype)
                || ($itemtype == AllAssets::getType())
            ) {
                $fn_append_options(Infocom::getSearchOptionsToAdd($itemtype));
            }

            if (
                in_array($itemtype, $CFG_GLPI["domain_types"])
                || ($itemtype == AllAssets::getType())
            ) {
                $fn_append_options(Domain::getSearchOptionsToAdd($itemtype));
            }

            if (
                in_array($itemtype, $CFG_GLPI["appliance_types"])
                || ($itemtype == AllAssets::getType())
            ) {
                $fn_append_options(Appliance::getSearchOptionsToAdd($itemtype));
            }

            if (in_array($itemtype, $CFG_GLPI["link_types"])) {
                self::$search[$itemtype]['link'] = ['name' => Link::getTypeName(Session::getPluralNumber())];
                $fn_append_options(Link::getSearchOptionsToAdd($itemtype));
                self::$search[$itemtype]['manuallink'] = ['name' => ManualLink::getTypeName(Session::getPluralNumber())];
                $fn_append_options(ManualLink::getSearchOptionsToAdd($itemtype));
            }

            if ($withplugins) {
               // Search options added by plugins
                $plugsearch = Plugin::getAddSearchOptions($itemtype);
                $plugsearch = $plugsearch + Plugin::getAddSearchOptionsNew($itemtype);
                if (count($plugsearch)) {
                    self::$search[$itemtype] += ['plugins' => ['name' => _n('Plugin', 'Plugins', Session::getPluralNumber())]];
                    $fn_append_options($plugsearch);
                }
            }

           // Complete linkfield if not define
            if (is_null($item)) { // Special union type
                $itemtable = $CFG_GLPI['union_search_type'][$itemtype];
            } else {
                $itemtable = $item->getTable();
            }

            foreach (self::$search[$itemtype] as $key => $val) {
                if (!is_array($val) || count($val) == 1) {
                   // skip sub-menu
                    continue;
                }
               // Compatibility before 0.80 : Force massive action to false if linkfield is empty :
                if (isset($val['linkfield']) && empty($val['linkfield'])) {
                    self::$search[$itemtype][$key]['massiveaction'] = false;
                }

               // Set default linkfield
                if (!isset($val['linkfield']) || empty($val['linkfield'])) {
                    if (
                        (strcmp($itemtable, $val['table']) == 0)
                        && (!isset($val['joinparams']) || (count($val['joinparams']) == 0))
                    ) {
                        self::$search[$itemtype][$key]['linkfield'] = $val['field'];
                    } else {
                        self::$search[$itemtype][$key]['linkfield'] = getForeignKeyFieldForTable($val['table']);
                    }
                }
               // Add default joinparams
                if (!isset($val['joinparams'])) {
                    self::$search[$itemtype][$key]['joinparams'] = [];
                }
            }
        }

        return self::$search[$itemtype];
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
        if (!Infocom::canApplyOn($itemtype)) {
            return false;
        }

        $infocom_options = Infocom::rawSearchOptionsToAdd($itemtype);
        $found_infocoms  = array_filter($infocom_options, function ($option) use ($searchID) {
            return isset($option['id']) && $searchID == $option['id'];
        });

        return (count($found_infocoms) > 0);
    }


    /**
     * @param string  $itemtype
     * @param integer $field_num
     **/
    public static function getActionsFor($itemtype, $field_num)
    {

        $searchopt = self::getOptions($itemtype);
        $actions   = [
            'contains'    => __('contains'),
            'notcontains' => __('not contains'),
            'searchopt'   => []
        ];

        if (isset($searchopt[$field_num]) && isset($searchopt[$field_num]['table'])) {
            $actions['searchopt'] = $searchopt[$field_num];

           // Force search type
            if (isset($actions['searchopt']['searchtype'])) {
               // Reset search option
                $actions              = [];
                $actions['searchopt'] = $searchopt[$field_num];
                if (!is_array($actions['searchopt']['searchtype'])) {
                    $actions['searchopt']['searchtype'] = [$actions['searchopt']['searchtype']];
                }
                foreach ($actions['searchopt']['searchtype'] as $searchtype) {
                    switch ($searchtype) {
                        case "equals":
                            $actions['equals'] = __('is');
                            break;

                        case "notequals":
                            $actions['notequals'] = __('is not');
                            break;

                        case "contains":
                             $actions['contains']    = __('contains');
                             $actions['notcontains'] = __('not contains');
                            break;

                        case "notcontains":
                             $actions['notcontains'] = __('not contains');
                            break;

                        case "under":
                            $actions['under'] = __('under');
                            break;

                        case "notunder":
                            $actions['notunder'] = __('not under');
                            break;

                        case "lessthan":
                            $actions['lessthan'] = __('before');
                            break;

                        case "morethan":
                            $actions['morethan'] = __('after');
                            break;
                    }
                }
                return $actions;
            }

            if (isset($searchopt[$field_num]['datatype'])) {
                switch ($searchopt[$field_num]['datatype']) {
                    case 'mio':
                    case 'count':
                    case "integer":
                    case 'number':
                        $opt = [
                            'contains'    => __('contains'),
                            'notcontains' => __('not contains'),
                            'equals'      => __('is'),
                            'notequals'   => __('is not'),
                            'searchopt'   => $searchopt[$field_num]
                        ];
                        // No is / isnot if no limits defined
                        if (
                            !isset($searchopt[$field_num]['min'])
                            && !isset($searchopt[$field_num]['max'])
                        ) {
                            unset($opt['equals']);
                            unset($opt['notequals']);

                         // https://github.com/glpi-project/glpi/issues/6917
                         // change filter wording for numeric values to be more
                         // obvious if the number dropdown will not be used
                            $opt['contains']    = __('is');
                            $opt['notcontains'] = __('is not');
                        }
                        return $opt;

                    case 'bool':
                        return [
                            'equals'      => __('is'),
                            'notequals'   => __('is not'),
                            'contains'    => __('contains'),
                            'notcontains' => __('not contains'),
                            'searchopt'   => $searchopt[$field_num]
                        ];

                    case 'right':
                        return ['equals'    => __('is'),
                            'notequals' => __('is not'),
                            'searchopt' => $searchopt[$field_num]
                        ];

                    case 'itemtypename':
                        return ['equals'    => __('is'),
                            'notequals' => __('is not'),
                            'searchopt' => $searchopt[$field_num]
                        ];

                    case 'date':
                    case 'datetime':
                    case 'date_delay':
                        return [
                            'equals'      => __('is'),
                            'notequals'   => __('is not'),
                            'lessthan'    => __('before'),
                            'morethan'    => __('after'),
                            'contains'    => __('contains'),
                            'notcontains' => __('not contains'),
                            'searchopt'   => $searchopt[$field_num]
                        ];
                }
            }

           // switch ($searchopt[$field_num]['table']) {
           //    case 'glpi_users_validation' :
           //       return array('equals'    => __('is'),
           //                    'notequals' => __('is not'),
           //                    'searchopt' => $searchopt[$field_num]);
           // }

            switch ($searchopt[$field_num]['field']) {
                case 'id':
                    return ['equals'    => __('is'),
                        'notequals' => __('is not'),
                        'searchopt' => $searchopt[$field_num]
                    ];

                case 'name':
                case 'completename':
                    $actions = [
                        'contains'    => __('contains'),
                        'notcontains' => __('not contains'),
                        'equals'      => __('is'),
                        'notequals'   => __('is not'),
                        'searchopt'   => $searchopt[$field_num]
                    ];

                   // Specific case of TreeDropdown : add under
                    $itemtype_linked = getItemTypeForTable($searchopt[$field_num]['table']);
                    if ($itemlinked = getItemForItemtype($itemtype_linked)) {
                        if ($itemlinked instanceof CommonTreeDropdown) {
                            $actions['under']    = __('under');
                            $actions['notunder'] = __('not under');
                        }
                        return $actions;
                    }
            }
        }
        return $actions;
    }


    /**
     * Print generic Header Column
     *
     * @param integer          $type     Display type (0=HTML, 1=Sylk, 2=PDF, 3=CSV)
     * @param string           $value    Value to display
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
        $out = "";
        switch ($type) {
            case self::PDF_OUTPUT_LANDSCAPE:
            case self::PDF_OUTPUT_PORTRAIT:
                /** @var string $PDF_TABLE */
                global $PDF_TABLE;
                $PDF_TABLE .= "<th $options>";
                $PDF_TABLE .= htmlspecialchars($value);
                $PDF_TABLE .= "</th>";
                break;

            case self::SYLK_OUTPUT: //sylk
                /**
                 * @var array $SYLK_HEADER
                 * @var array $SYLK_SIZE
                 */
                global $SYLK_HEADER, $SYLK_SIZE;
                $SYLK_HEADER[$num] = self::sylk_clean($value);
                $SYLK_SIZE[$num]   = Toolbox::strlen($SYLK_HEADER[$num]);
                break;

            case self::CSV_OUTPUT: //CSV
                $out = "\"" . self::csv_clean($value) . "\"" . $_SESSION["glpicsv_delimiter"];
                break;

            case self::NAMES_OUTPUT:
                $out = "";
                break;

            default:
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
        }
        $num++;
        return $out;
    }


    /**
     * Print generic normal Item Cell
     *
     * @param integer $type        Display type (0=HTML, 1=Sylk, 2=PDF, 3=CSV)
     * @param string  $value       Value to display
     * @param integer &$num        Column number
     * @param integer $row         Row number
     * @param string  $extraparam  Extra parameters for display (default '')
     *
     * @return string HTML to display
     **/
    public static function showItem($type, $value, &$num, $row, $extraparam = '')
    {

        $out = "";
        // Handle null values
        if ($value === null) {
            $value = '';
        }

        switch ($type) {
            case self::PDF_OUTPUT_LANDSCAPE: //pdf
            case self::PDF_OUTPUT_PORTRAIT:
                /** @var string $PDF_TABLE */
                global $PDF_TABLE;
                $value = DataExport::normalizeValueForTextExport($value);
                $value = htmlspecialchars($value);
                $value = preg_replace('/' . self::LBBR . '/', '<br>', $value);
                $value = preg_replace('/' . self::LBHR . '/', '<hr>', $value);
                $PDF_TABLE .= "<td $extraparam valign='top'>";
                $PDF_TABLE .= $value;
                $PDF_TABLE .= "</td>";

                break;

            case self::SYLK_OUTPUT: //sylk
                /**
                 * @var array $SYLK_ARRAY
                 * @var array $SYLK_SIZE
                 */
                global $SYLK_ARRAY, $SYLK_SIZE;
                $value = DataExport::normalizeValueForTextExport($value);
                $value = preg_replace('/' . self::LBBR . '/', '<br>', $value);
                $value = preg_replace('/' . self::LBHR . '/', '<hr>', $value);
                $SYLK_ARRAY[$row][$num] = self::sylk_clean($value);
                $SYLK_SIZE[$num]        = max(
                    $SYLK_SIZE[$num],
                    Toolbox::strlen($SYLK_ARRAY[$row][$num])
                );
                break;

            case self::CSV_OUTPUT: //csv
                $value = DataExport::normalizeValueForTextExport($value);
                $value = preg_replace('/' . self::LBBR . '/', '<br>', $value);
                $value = preg_replace('/' . self::LBHR . '/', '<hr>', $value);
                $out   = "\"" . self::csv_clean($value) . "\"" . $_SESSION["glpicsv_delimiter"];
                break;

            case self::NAMES_OUTPUT:
               // We only want to display one column (the name of the item).
               // The name field is always the first column expect for tickets
               // which have their ids as the first column instead, thus moving the
               // name to the second column.
               // We don't have access to the itemtype so we must rely on data
               // types to figure which column to use :
               //    - Ticket will have a numeric first column (id) and an HTML
               //    link containing the name as the second column.
               //    - Other items will have an HTML link containing the name as
               //    the first column and a simple string containing the entity
               //    name as the second column.
               // -> We can check that the column is the first or second AND is html
                if (
                    strip_tags($value) !== $value
                    && ($num == 1 || $num == 2)
                ) {
                   // Use a regex to keep only the link, there may be other content
                   // after that we don't need (script, tooltips, ...)
                    if (preg_match('/<a.*<\/a>/', $value, $matches)) {
                        $out = Sanitizer::decodeHtmlSpecialChars(strip_tags($matches[0]));
                    }
                }
                break;

            default:
                /** @var array $CFG_GLPI */
                global $CFG_GLPI;
                $out = "<td $extraparam valign='top'>";

                if (!preg_match('/' . self::LBHR . '/', $value)) {
                    $values = preg_split('/' . self::LBBR . '/i', $value);
                    $line_delimiter = '<br>';
                } else {
                    $values = preg_split('/' . self::LBHR . '/i', $value);
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
                    $value = preg_replace('/' . self::LBBR . '/', '<br>', $value);
                    $value = preg_replace('/' . self::LBHR . '/', '<hr>', $value);
                    $value = '<div class="fup-popup">' . $value . '</div>';
                    $valTip = ' ' . Html::showToolTip(
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
                    $value = preg_replace('/' . self::LBBR . '/', '<br>', $value);
                    $value = preg_replace('/' . self::LBHR . '/', '<hr>', $value);
                    $out .= $value;
                }
                $out .= "</td>\n";
        }
        $num++;
        return $out;
    }


    /**
     * Print generic error
     *
     * @param integer $type     Display type (0=HTML, 1=Sylk, 2=PDF, 3=CSV)
     * @param string  $message  Message to display, if empty "no item found" will be displayed
     *
     * @return string HTML to display
     **/
    public static function showError($type, $message = "")
    {
        if (strlen($message) == 0) {
            $message = __('No item found');
        }

        $out = "";
        switch ($type) {
            case self::PDF_OUTPUT_LANDSCAPE: //pdf
            case self::PDF_OUTPUT_PORTRAIT:
            case self::SYLK_OUTPUT: //sylk
            case self::CSV_OUTPUT: //csv
                break;

            default:
                $out = "<div class='center b'>$message</div>\n";
        }
        return $out;
    }


    /**
     * Print generic footer
     *
     * @param integer $type  Display type (0=HTML, 1=Sylk, 2=PDF, 3=CSV)
     * @param string  $title title of file : used for PDF (default '')
     * @param integer $count Total number of results
     *
     * @return string HTML to display
     **/
    public static function showFooter($type, $title = "", $count = null)
    {

        $out = "";
        switch ($type) {
            case self::PDF_OUTPUT_LANDSCAPE: //pdf
            case self::PDF_OUTPUT_PORTRAIT:
                /** @var string $PDF_TABLE */
                global $PDF_TABLE;

                $font       = 'helvetica';
                $fontsize   = 8;
                if (isset($_SESSION['glpipdffont']) && $_SESSION['glpipdffont']) {
                    $font       = $_SESSION['glpipdffont'];
                }

                $pdf = new GLPIPDF(
                    [
                        'font_size'  => $fontsize,
                        'font'       => $font,
                        'orientation'        => $type == self::PDF_OUTPUT_LANDSCAPE ? 'L' : 'P',
                    ],
                    $count,
                    $title,
                );

                $PDF_TABLE .= '</table>';
                $pdf->writeHTML($PDF_TABLE, true, false, true);
                $pdf->Output('glpi.pdf', 'I');
                break;

            case self::SYLK_OUTPUT: //sylk
                /**
                 * @var array $SYLK_ARRAY
                 * @var array $SYLK_HEADER
                 * @var array $SYLK_SIZE
                 */
                global $SYLK_ARRAY, $SYLK_HEADER, $SYLK_SIZE;
               // largeurs des colonnes
                foreach ($SYLK_SIZE as $num => $val) {
                    $out .= "F;W" . $num . " " . $num . " " . min(50, $val) . "\n";
                }
                $out .= "\n";
               // Header
                foreach ($SYLK_HEADER as $num => $val) {
                    $out .= "F;SDM4;FG0C;" . ($num == 1 ? "Y1;" : "") . "X$num\n";
                    $out .= "C;N;K\"$val\"\n";
                    $out .= "\n";
                }
               // Datas
                foreach ($SYLK_ARRAY as $row => $tab) {
                    foreach ($tab as $num => $val) {
                        $out .= "F;P3;FG0L;" . ($num == 1 ? "Y" . $row . ";" : "") . "X$num\n";
                        $out .= "C;N;K\"$val\"\n";
                    }
                }
                $out .= "E\n";
                break;

            case self::CSV_OUTPUT: //csv
            case self::NAMES_OUTPUT:
                break;

            default:
                $out = "</table></div>\n";
        }
        return $out;
    }


    /**
     * Print generic footer
     *
     * @param integer         $type   Display type (0=HTML, 1=Sylk, 2=PDF, 3=CSV)
     * @param integer         $rows   Number of rows
     * @param integer         $cols   Number of columns
     * @param boolean|integer $fixed  Used tab_cadre_fixe table for HTML export ? (default 0)
     *
     * @return string HTML to display
     **/
    public static function showHeader($type, $rows, $cols, $fixed = 0)
    {

        $out = "";
        switch ($type) {
            case self::PDF_OUTPUT_LANDSCAPE: //pdf
            case self::PDF_OUTPUT_PORTRAIT:
                /** @var string $PDF_TABLE */
                global $PDF_TABLE;
                $PDF_TABLE = "<table cellspacing=\"0\" cellpadding=\"1\" border=\"1\" >";
                break;

            case self::SYLK_OUTPUT: // Sylk
                /**
                 * @var array $SYLK_ARRAY
                 * @var array $SYLK_HEADER
                 * @var array $SYLK_SIZE
                 */
                global $SYLK_ARRAY, $SYLK_HEADER, $SYLK_SIZE;
                $SYLK_ARRAY  = [];
                $SYLK_HEADER = [];
                $SYLK_SIZE   = [];
               // entetes HTTP
                header("Expires: Mon, 26 Nov 1962 00:00:00 GMT");
                header('Pragma: private'); /// IE BUG + SSL
                header('Cache-control: private, must-revalidate'); /// IE BUG + SSL
                header("Content-disposition: filename=glpi.slk");
                header('Content-type: application/octetstream');
               // entete du fichier
                echo "ID;PGLPI_EXPORT\n"; // ID;Pappli
                echo "\n";
               // formats
                echo "P;PGeneral\n";
                echo "P;P#,##0.00\n";       // P;Pformat_1 (reels)
                echo "P;P#,##0\n";          // P;Pformat_2 (entiers)
                echo "P;P@\n";              // P;Pformat_3 (textes)
                echo "\n";
               // polices
                echo "P;EArial;M200\n";
                echo "P;EArial;M200\n";
                echo "P;EArial;M200\n";
                echo "P;FArial;M200;SB\n";
                echo "\n";
               // nb lignes * nb colonnes
                echo "B;Y" . $rows;
                echo ";X" . $cols . "\n"; // B;Yligmax;Xcolmax
                echo "\n";
                break;

            case self::CSV_OUTPUT: // csv
                header("Expires: Mon, 26 Nov 1962 00:00:00 GMT");
                header('Pragma: private'); /// IE BUG + SSL
                header('Cache-control: private, must-revalidate'); /// IE BUG + SSL
                header("Content-disposition: filename=glpi.csv");
                header('Content-type: text/csv');
               // zero width no break space (for excel)
                echo"\xEF\xBB\xBF";
                break;

            case self::NAMES_OUTPUT:
                if (!defined('TU_USER')) {
                    header("Content-disposition: filename=glpi.txt");
                    header('Content-type: file/txt');
                }
                break;

            default:
                if ($fixed) {
                    $out = "<div class='center'><table border='0' class='table'>";
                } else {
                    $out = "<div class='center'><table border='0' class='table card-table table-hover'>";
                }
        }
        return $out;
    }


    /**
     * Print begin of header part
     *
     * @param integer $type   Display type (0=HTML, 1=Sylk, 2=PDF, 3=CSV)
     *
     * @since 0.85
     *
     * @return string HTML to display
     **/
    public static function showBeginHeader($type)
    {

        $out = "";
        switch ($type) {
            case self::PDF_OUTPUT_LANDSCAPE: //pdf
            case self::PDF_OUTPUT_PORTRAIT:
                /** @var string $PDF_TABLE */
                global $PDF_TABLE;
                $PDF_TABLE .= "<thead>";
                break;

            case self::SYLK_OUTPUT: //sylk
            case self::CSV_OUTPUT: //csv
            case self::NAMES_OUTPUT:
                break;

            default:
                $out = "<thead>";
        }
        return $out;
    }


    /**
     * Print end of header part
     *
     * @param integer $type   Display type (0=HTML, 1=Sylk, 2=PDF, 3=CSV)
     *
     * @since 0.85
     *
     * @return string to display
     **/
    public static function showEndHeader($type)
    {

        $out = "";
        switch ($type) {
            case self::PDF_OUTPUT_LANDSCAPE: //pdf
            case self::PDF_OUTPUT_PORTRAIT:
                /** @var string $PDF_TABLE */
                global $PDF_TABLE;
                $PDF_TABLE .= "</thead>";
                break;

            case self::SYLK_OUTPUT: //sylk
            case self::CSV_OUTPUT: //csv
            case self::NAMES_OUTPUT:
                break;

            default:
                $out = "</thead>";
        }
        return $out;
    }


    /**
     * Print generic new line
     *
     * @param integer $type        Display type (0=HTML, 1=Sylk, 2=PDF, 3=CSV)
     * @param boolean $odd         Is it a new odd line ? (false by default)
     * @param boolean $is_deleted  Is it a deleted search ? (false by default)
     *
     * @return string HTML to display
     **/
    public static function showNewLine($type, $odd = false, $is_deleted = false)
    {

        $out = "";
        switch ($type) {
            case self::PDF_OUTPUT_LANDSCAPE: //pdf
            case self::PDF_OUTPUT_PORTRAIT:
                /** @var string $PDF_TABLE */
                global $PDF_TABLE;
                $style = "";
                if ($odd) {
                    $style = " style=\"background-color:#DDDDDD;\" ";
                }
                $PDF_TABLE .= "<tr $style nobr=\"true\">";
                break;

            case self::SYLK_OUTPUT: //sylk
            case self::CSV_OUTPUT: //csv
            case self::NAMES_OUTPUT:
                break;

            default:
                $class = " class='tab_bg_2" . ($is_deleted ? '_2' : '') . "' ";
                if ($odd) {
                    $class = " class='tab_bg_1" . ($is_deleted ? '_2' : '') . "' ";
                }
                $out = "<tr $class>";
        }
        return $out;
    }


    /**
     * Print generic end line
     *
     * @param integer $type  Display type (0=HTML, 1=Sylk, 2=PDF, 3=CSV)
     *
     * @return string HTML to display
     **/
    public static function showEndLine($type, bool $is_header_line = false)
    {

        $out = "";
        switch ($type) {
            case self::PDF_OUTPUT_LANDSCAPE: //pdf
            case self::PDF_OUTPUT_PORTRAIT:
                /** @var string $PDF_TABLE */
                global $PDF_TABLE;
                $PDF_TABLE .= '</tr>';
                break;

            case self::SYLK_OUTPUT: //sylk
                break;

            case self::CSV_OUTPUT: //csv
            case self::NAMES_OUTPUT:
                // NAMES_OUTPUT has no output on header lines
                $newline = $type != self::NAMES_OUTPUT || !$is_header_line;
                if ($newline) {
                    $out = "\n";
                }
                break;

            default:
                $out = "</tr>";
        }
        return $out;
    }


    /**
     * @param array $joinparams
     */
    public static function computeComplexJoinID(array $joinparams)
    {

        $complexjoin = '';

        if (isset($joinparams['condition'])) {
            if (!is_array($joinparams['condition'])) {
                $complexjoin .= $joinparams['condition'];
            } else {
                /** @var \DBmysql $DB */
                global $DB;
                $dbi = new DBmysqlIterator($DB);
                $sql_clause = $dbi->analyseCrit($joinparams['condition']);
                $complexjoin .= ' AND ' . $sql_clause; //TODO: and should came from conf
            }
        }

       // For jointype == child
        if (
            isset($joinparams['jointype']) && ($joinparams['jointype'] == 'child')
            && isset($joinparams['linkfield'])
        ) {
            $complexjoin .= $joinparams['linkfield'];
        }

        if (isset($joinparams['beforejoin'])) {
            if (isset($joinparams['beforejoin']['table'])) {
                $joinparams['beforejoin'] = [$joinparams['beforejoin']];
            }
            foreach ($joinparams['beforejoin'] as $tab) {
                if (isset($tab['table'])) {
                    $complexjoin .= $tab['table'];
                }
                if (isset($tab['joinparams']) && isset($tab['joinparams']['condition'])) {
                    if (!is_array($tab['joinparams']['condition'])) {
                        $complexjoin .= $tab['joinparams']['condition'];
                    } else {
                        /** @var \DBmysql $DB */
                        global $DB;
                        $dbi = new DBmysqlIterator($DB);
                        $sql_clause = $dbi->analyseCrit($tab['joinparams']['condition']);
                        $complexjoin .= ' AND ' . $sql_clause; //TODO: and should came from conf
                    }
                }
            }
        }

        if (!empty($complexjoin)) {
            $complexjoin = md5($complexjoin);
        }
        return $complexjoin;
    }


    /**
     * Clean display value for csv export
     *
     * @param string $value value
     *
     * @return string Clean value
     **/
    public static function csv_clean($value)
    {

        $value = str_replace("\"", "''", $value);

        return $value;
    }


    /**
     * Clean display value for sylk export
     *
     * @param string $value value
     *
     * @return string Clean value
     **/
    public static function sylk_clean($value)
    {

        $value = preg_replace('/\x0A/', ' ', $value);
        $value = preg_replace('/\x0D/', '', $value);
        $value = str_replace("\"", "''", $value);
        $value = str_replace("\n", " | ", $value);

        return $value;
    }


    /**
     * Create SQL search condition
     *
     * @param string  $field  Nname (should be ` protected)
     * @param string  $val    Value to search
     * @param boolean $not    Is a negative search ? (false by default)
     * @param string  $link   With previous criteria (default 'AND')
     *
     * @return string search SQL string
     **/
    public static function makeTextCriteria($field, $val, $not = false, $link = 'AND')
    {

        $sql = $field . self::makeTextSearch($val, $not);

        if (strtolower($val) == "null") {
            // FIXME
            // `OR field = ''` condition is not supposed to be relevant, and can sometimes result in SQL performances issues/warnings/errors,
            // when following datatype are used:
            //  - integer
            //  - number
            //  - decimal
            //  - count
            //  - mio
            //  - percentage
            //  - timestamp
            //  - datetime
            //  - date_delay
            //
            // Removing this condition requires, at least, to use the `int`/`float`/`double`/`timestamp`/`date` types in DB,
            // to ensure that the `''` value will not be stored in DB.

            if ($not) {
                $sql .= " AND $field <> ''";
            } else {
                $sql .= " OR $field = ''";
            }
        }

        if (
            ($not && ($val != 'NULL') && ($val != 'null') && ($val != '^$'))    // Not something
            || (!$not && ($val == '^$'))
        ) {   // Empty
            $sql = "($sql OR $field IS NULL)";
        }

        return " $link ($sql)";
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
        // `$val` will mostly comes from sanitized input, but may also be raw value.
        // 1. Unsanitize value to be sure to use raw value.
        // 2. Escape raw value to protect SQL special chars.
        $val = Sanitizer::dbEscape(Sanitizer::unsanitize($val));

        // Backslashes must be doubled in LIKE clause, according to MySQL documentation:
        // https://dev.mysql.com/doc/refman/8.0/en/string-comparison-functions.html
        // > To search for \, specify it as \\\\; this is because the backslashes are stripped once by the parser
        // > and again when the pattern match is made, leaving a single backslash to be matched against.
        //
        // At this point, backslashes are already escaped, so escaped backslashes (\\) have to be transformed to \\\\.
        $val = str_replace('\\\\', '\\\\\\\\', $val);

       // escape _ char used as wildcard in mysql likes
        $val = str_replace('_', '\\_', $val);

        // special case for & char
        $val = str_replace('&', '&#38;', $val);

        if ($val === 'NULL' || $val === 'null') {
            return null;
        }

        $val = trim($val);

        if ($val === '^') {
           // Special case, searching "^" means we are searching for a non empty/null field
            return '%';
        }

        if ($val === '' || $val === '^$' || $val === '$') {
            return '';
        }

        if (preg_match('/^\^/', $val)) {
           // Remove leading `^`
            $val = ltrim(preg_replace('/^\^/', '', $val));
        } else {
           // Add % wildcard before searched string if not begining by a `^`
            $val = '%' . $val;
        }

        if (preg_match('/\$$/', $val)) {
           // Remove trailing `$`
            $val = rtrim(preg_replace('/\$$/', '', $val));
        } else {
           // Add % wildcard after searched string if not ending by a `$`
            $val = $val . '%';
        }

        return $val;
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

        $NOT = "";
        if ($not) {
            $NOT = "NOT";
        }

        $val = self::makeTextSearchValue($val);
        if ($val == null) {
            $SEARCH = " IS $NOT NULL ";
        } else {
            $SEARCH = " $NOT LIKE " . DBmysql::quoteValue($val) . " ";
        }
        return $SEARCH;
    }


    /**
     * @since 0.84
     *
     * @param string $pattern
     * @param string $subject
     **/
    public static function explodeWithID($pattern, $subject)
    {

        $tab = explode($pattern, $subject);

        if (isset($tab[1]) && !is_numeric($tab[1])) {
           // Report $ to tab[0]
            if (preg_match('/^(\\$*)(.*)/', $tab[1], $matchs)) {
                if (isset($matchs[2]) && is_numeric($matchs[2])) {
                    $tab[1]  = $matchs[2];
                    $tab[0] .= $matchs[1];
                }
            }
        }
       // Manage NULL value
        if ($tab[0] == self::NULLVALUE) {
            $tab[0] = null;
        }
        return $tab;
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
     */
    public static function joinDropdownTranslations($alias, $table, $itemtype, $field)
    {
        return "LEFT JOIN `glpi_dropdowntranslations` AS `$alias`
                  ON (`$alias`.`itemtype` = '$itemtype'
                        AND `$alias`.`items_id` = `$table`.`id`
                        AND `$alias`.`language` = '" .
                              $_SESSION['glpilanguage'] . "'
                        AND `$alias`.`field` = '$field')";
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
        return (is_a($itemtype, CommonDBTM::class, true)) ? $itemtype::getTable() : getTableForItemType($itemtype);
    }
}
