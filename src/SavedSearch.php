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

use Glpi\Application\ErrorHandler;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Features\Clonable;
use Glpi\Toolbox\Sanitizer;

/**
 * Saved searches class
 *
 * @since 9.2
 **/
class SavedSearch extends CommonDBTM implements ExtraVisibilityCriteria
{
    use Clonable;

    public static $rightname               = 'bookmark_public';

    const SEARCH = 1; //SEARCH SYSTEM bookmark
    const URI    = 2;
    const ALERT  = 3; //SEARCH SYSTEM search alert

    const COUNT_NO = 0;
    const COUNT_YES = 1;
    const COUNT_AUTO = 2;


    public static function getForbiddenActionsForMenu()
    {
        return ['add'];
    }


    public static function getTypeName($nb = 0)
    {
        return _n('Saved search', 'Saved searches', $nb);
    }


    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }


    public function getSpecificMassiveActions($checkitem = null)
    {

        $actions[get_called_class() . MassiveAction::CLASS_ACTION_SEPARATOR . 'unset_default']
                     = __('Unset as default');
        $actions[get_called_class() . MassiveAction::CLASS_ACTION_SEPARATOR . 'change_count_method']
                     = __('Change count method');
        if (Session::haveRight('transfer', READ)) {
            $actions[get_called_class() . MassiveAction::CLASS_ACTION_SEPARATOR . 'change_entity']
                     = __('Change visibility');
        }
        return $actions;
    }


    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {

        switch ($ma->getAction()) {
            case 'change_count_method':
                $values = [self::COUNT_AUTO  => __('Auto'),
                    self::COUNT_YES   => __('Yes'),
                    self::COUNT_NO    => __('No')
                ];
                Dropdown::showFromArray('do_count', $values, ['width' => '20%']);
                break;

            case 'change_entity':
                Entity::dropdown(['entity' => $_SESSION['glpiactiveentities'],
                    'value'  => $_SESSION['glpiactive_entity'],
                    'name'   => 'entities_id'
                ]);
                echo '<br/>';
                echo __('Child entities');
                Dropdown::showYesNo('is_recursive');
                echo '<br/>';
                break;
        }
        return parent::showMassiveActionsSubForm($ma);
    }


    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {
        $input = $ma->getInput();
        switch ($ma->getAction()) {
            case 'unset_default':
                if ($item->unmarkDefaults($ids)) {
                    $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_OK);
                } else {
                    $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
                }
                return;
            break;

            case 'change_count_method':
                if ($item->setDoCount($ids, $input['do_count'])) {
                    $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_OK);
                } else {
                    $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
                }
                break;

            case 'change_entity':
                if ($item->setEntityRecur($ids, $input['entities_id'], $input['is_recursive'])) {
                    $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_OK);
                } else {
                    $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
                }
                break;
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }


    public function canCreateItem()
    {

        if ($this->fields['is_private'] == 1) {
            return (Session::haveRight('config', UPDATE)
                 || $this->fields['users_id'] == Session::getLoginUserID());
        }
        return parent::canCreateItem();
    }


    public function canViewItem()
    {

        if ($this->fields['is_private'] == 1) {
            return (Session::haveRight('config', READ)
                 || $this->fields['users_id'] == Session::getLoginUserID());
        }
        return parent::canViewItem();
    }


    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong)
           ->addStandardTab('SavedSearch_Alert', $ong, $options);
        return $ong;
    }


    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = ['id'                 => 'common',
            'name'               => __('Characteristics')
        ];

        $tab[] = ['id'                 => '1',
            'table'              => $this->getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false, // implicit key==1
        ];

        $tab[] = ['id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false, // implicit field is id
            'datatype'           => 'number'
        ];

        $tab[] = ['id'                 => 3,
            'table'              => User::getTable(),
            'field'              => 'name',
            'name'               => User::getTypeName(1),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'  => 4,
            'table'           => $this->getTable(),
            'field'           => 'is_private',
            'name'            => __('Is private'),
            'datatype'        => 'bool',
            'massiveaction'   => false,
        ];

        $tab[] = ['id'                 => '8',
            'table'              => $this->getTable(),
            'field'              => 'itemtype',
            'name'               => __('Item type'),
            'massiveaction'      => false,
            'datatype'           => 'itemtypename',
            'types'              => self::getUsedItemtypes()
        ];

        $tab[] = ['id'                 => 9,
            'table'              => $this->getTable(),
            'field'              => 'last_execution_time',
            'name'               => __('Last duration (ms)'),
            'massiveaction'      => false,
            'datatype'           => 'number'
        ];

        $tab[] = ['id'                 => 10,
            'table'              => $this->getTable(),
            'field'              => 'do_count',
            'name'               => __('Count'),
            'massiveaction'      => true,
            'datatype'           => 'specific',
            'searchtype'         => 'equals'
        ];

        $tab[] = [
            'id'            => 11,
            'table'         => SavedSearch_User::getTable(),
            'field'         => 'users_id',
            'name'          => __('Default'),
            'massiveaction' => false,
            'joinparams'    => [
                'jointype'  => 'child',
                'condition' => "AND NEWTABLE.users_id = " . Session::getLoginUserID()
            ],
            'datatype'      => 'specific',
            'searchtype'    => [
                0 => 'equals',
                1 => 'notequals'
            ],
        ];

        $tab[] = ['id'                 => 12,
            'table'              => $this->getTable(),
            'field'              => 'counter',
            'name'               => __('Counter'),
            'massiveaction'      => false,
            'datatype'           => 'number'
        ];

        $tab[] = ['id'                 => 13,
            'table'              => $this->getTable(),
            'field'              => 'last_execution_date',
            'name'               => __('Last execution date'),
            'massiveaction'      => false,
            'datatype'           => 'datetime'
        ];

        return $tab;
    }

    /**
     * Prepare Search url before saving it do db on creation or update
     *
     * @param array $input
     *
     * @return array $input
     */
    public function prepareSearchUrlForDB(array $input): array
    {
        $taburl = parse_url(Sanitizer::unsanitize($input['url']));

        $query_tab = [];

        if (isset($taburl["query"])) {
            parse_str($taburl["query"], $query_tab);
        }

        $input['query'] = Toolbox::append_params(
            $this->prepareQueryToStore($input['type'], $query_tab)
        );

        return $input;
    }

    public function prepareInputForAdd($input)
    {

        if (!isset($input['url']) || !isset($input['type'])) {
            return false;
        }

        $input = $this->prepareSearchUrlForDB($input);
        return $input;
    }

    public function prepareInputForUpdate($input)
    {

        if (isset($input['url']) && $input['type']) {
            $input = $this->prepareSearchUrlForDB($input);
        }
        return $input;
    }

    public function pre_updateInDB()
    {

       // Set new user if initial user have been deleted
        if (
            ($this->fields['users_id'] == 0)
            && ($uid = Session::getLoginUserID())
        ) {
            $this->input['users_id']  = $uid;
            $this->fields['users_id'] = $uid;
            $this->updates[]          = "users_id";
        }
    }


    public function post_getEmpty()
    {

        $this->fields["users_id"]     = Session::getLoginUserID();
        $this->fields["is_private"]   = 1;
        $this->fields["is_recursive"] = 1;
        $this->fields["entities_id"]  = $_SESSION["glpiactive_entity"];
    }


    public function cleanDBonPurge()
    {

        $this->deleteChildrenAndRelationsFromDb(
            [
                SavedSearch_Alert::class,
                SavedSearch_User::class,
            ]
        );
    }


    /**
     * Print the saved search form
     *
     * @param integer $ID      ID of the item
     * @param array   $options possible options:
     *                         - target for the Form
     *                         - type when adding
     *                         - url when adding
     *                         - itemtype when adding
     *
     * @return void
     **/
    public function showForm($ID, array $options = [])
    {

       // Try to load id from fields if not specified
        if ($ID == 0) {
            $ID = $this->getID();
        }

        $this->initForm($ID, $options);
        $options['formtitle'] = false;
        $this->showFormHeader($options);

        if (isset($options['itemtype'])) {
            echo Html::hidden('itemtype', ['value' => $options['itemtype']]);
        }

        if (isset($options['type']) && ($options['type'] != 0)) {
            echo Html::hidden('type', ['value' => $options['type']]);
        }

        if (isset($options['url'])) {
            echo Html::hidden('url', ['value' => $options['url']]);
        }

        echo "<tr><th colspan='4'>";
        if ($ID > 0) {
           // TRANS: %1$s is the Itemtype name and $2$d the ID of the item
            printf(__('%1$s - ID %2$d'), $this->getTypeName(1), $ID);
        } else {
            echo __('New saved search');
        }

        echo "</th></tr>";

        echo "<tr><td class='tab_bg_1'>" . __('Name') . "</td>";
        echo "<td class='tab_bg_1'>";
        echo Html::input('name', ['value' => $this->fields['name']]);
        echo "</td>";
        if (Session::haveRight("config", UPDATE)) {
            echo "<td class='tab_bg_1'>" . __('Do count') . "</td>" .
              "<td class='tab_bg_1'>";
            $values = [self::COUNT_AUTO  => __('Auto'),
                self::COUNT_YES   => __('Yes'),
                self::COUNT_NO    => __('No')
            ];
            Dropdown::showFromArray('do_count', $values, ['value' => $this->getField('do_count')]);
        } else {
            echo "<td colspan='2'>";
        }
        echo "</td></tr>";

        $rand = mt_rand();
        echo "<tr class='tab_bg_2'><td><label for='dropdown_is_private$rand'>" . __('Visibility') . "</label></td>";
        if ($this->canCreate()) {
            echo "<td colspan='3'>";
            Dropdown::showFromArray(
                'is_private',
                [
                    1  => __('Private'),
                    0  => __('Public')
                ],
                [
                    'value'  => $this->fields['is_private'],
                    'rand'   => $rand
                ]
            );
            echo "</td></tr>";
            echo "<tr class='tab_bg_2'><td>" . Entity::getTypeName(1) . "</td>";
            echo "</td><td>";
            Entity::dropdown(['value' => $this->fields["entities_id"]]);
            echo "</td><td>" . __('Child entities') . "</td><td>";
            Dropdown::showYesNo('is_recursive', $this->fields["is_recursive"]);
        } else {
            echo "<td colspan='3'>";
            if ($this->fields["is_private"]) {
                echo __('Private');
            } else {
                echo __('Public');
            }
        }
        if ($ID <= 0) { // add
            echo Html::hidden('users_id', ['value' => $this->fields['users_id']]);
            if (!self::canCreate()) {
                echo Html::hidden('is_private', ['value' => 1]);
            }
        } else {
            echo Html::hidden('id', ['value' => $ID]);
        }
        echo "</td></tr>";

        if (isset($options['ajax'])) {
            $js = "$(function() {
            $('form[name=form_save_query]').submit(function (e) {
               e.preventDefault();
               var _this = $(this);
               $.ajax({
                  url: _this.attr('action').replace(/\/front\//, '/ajax/').replace(/\.form/, ''),
                  method: 'POST',
                  data: _this.serialize(),
                  success: function(res) {
                     if (res.success == true) {
                        glpi_close_all_dialogs();
                     }
                     displayAjaxMessageAfterRedirect();
                  }
               });
            });
         });";
            echo Html::scriptBlock($js);
        }

       // If this form is used to edit a saved search from the search screen
        $is_ajax = $options['ajax'] ?? false;
        if ($is_ajax && $ID > 0) {
           // Allow an extra option to save as a new search instead of editing the current one
            $options['addbuttons'] = ["add" => __("Save as a new search")];

           // Do not allow delete from this modal
            $options['candel'] = false;
        }

        $this->showFormButtons($options);
    }


    /**
     * Prepare query to store depending of the type
     *
     * @param integer $type      Saved search type (self::SEARCH, self::URI or self::ALERT)
     * @param array   $query_tab Parameters
     *
     * @return clean query array
     **/
    protected function prepareQueryToStore($type, $query_tab)
    {

        switch ($type) {
            case self::SEARCH:
            case self::ALERT:
                $fields_toclean = ['add_search_count',
                    'add_search_count2',
                    'delete_search_count',
                    'delete_search_count2',
                    'start',
                    '_glpi_csrf_token'
                ];
                foreach ($fields_toclean as $field) {
                    if (isset($query_tab[$field])) {
                        unset($query_tab[$field]);
                    }
                }
                break;
        }
        return $query_tab;
    }


    /**
     * Prepare query to use depending of the type
     *
     * @param integer $type      Saved search type (see SavedSearch constants)
     * @param array   $query_tab Parameters array
     * @param bool    $enable_partial_warnings display warning messages about partial loading
     *
     * @return array prepared query array
     **/
    public function prepareQueryToUse($type, $query_tab, $enable_partial_warnings = true)
    {

        switch ($type) {
            case self::SEARCH:
            case self::ALERT:
                // Check if all data are valid
                $opt            = Search::getCleanedOptions($this->fields['itemtype']);
                $query_tab_save = $query_tab;
                $partial_load   = false;
                // Standard search
                if (isset($query_tab_save['criteria']) && count($query_tab_save['criteria'])) {
                    unset($query_tab['criteria']);
                    $new_key = 0;
                    foreach ($query_tab_save['criteria'] as $key => $val) {
                        if (
                            isset($val['field'])
                            && $val['field'] != 'view'
                            && $val['field'] != 'all'
                            && (!isset($opt[$val['field']])
                            || (isset($opt[$val['field']]['nosearch'])
                              && $opt[$val['field']]['nosearch']))
                        ) {
                            $partial_load = true;
                        } else {
                            $query_tab['criteria'][$new_key] = $val;
                            $new_key++;
                        }
                    }
                }
                // Meta search
                if (isset($query_tab_save['metacriteria']) && count($query_tab_save['metacriteria'])) {
                    $meta_ok = Search::getMetaItemtypeAvailable($query_tab['itemtype']);
                    unset($query_tab['metacriteria']);
                    $new_key = 0;
                    foreach ($query_tab_save['metacriteria'] as $key => $val) {
                        if (isset($val['itemtype'])) {
                             $opt = Search::getCleanedOptions($val['itemtype']);
                        }
                       // Use if meta type is valid and option available
                        if (
                            !isset($val['itemtype']) || !in_array($val['itemtype'], $meta_ok)
                            || !isset($opt[$val['field']])
                        ) {
                            $partial_load = true;
                        } else {
                            $query_tab['metacriteria'][$new_key] = $val;
                            $new_key++;
                        }
                    }
                }
               // Display message
                if (
                    $enable_partial_warnings
                    && $partial_load
                    && Session::getCurrentInterface() != "helpdesk"
                ) {
                    Session::addMessageAfterRedirect(
                        sprintf(__('Partial load of the saved search: %s'), $this->getName()),
                        false,
                        ERROR
                    );
                }
               // add reset value
                $query_tab['reset'] = 'reset';
                break;
        }
        return $query_tab;
    }


    /**
     * Load a saved search
     *
     * @param integer $ID ID of the saved search
     *
     * @return void
     **/
    public function load($ID)
    {
        if (($params = $this->getParameters($ID)) === false) {
            return;
        }

        $url = Toolbox::getItemTypeSearchURL($this->fields['itemtype']);
        $url .= "?" . Toolbox::append_params($params);

       // keep last loaded to set an active state on saved search panel
        $_SESSION['glpi_loaded_savedsearch'] = $ID;

        Html::redirect($url);
    }


    /**
     * Get saved search parameters
     *
     * @param integer $ID ID of the saved search
     *
     * @return array|false
     **/
    public function getParameters($ID)
    {

        if ($this->getFromDB($ID) === false) {
            return false;
        }
        if (!class_exists($this->fields['itemtype'])) {
            return false;
        }

        $query_tab = [];
        parse_str($this->fields["query"], $query_tab);
        $query_tab['savedsearches_id'] = $ID;
        return $this->prepareQueryToUse($this->fields["type"], $query_tab);
    }


    /**
     * Mark saved search as default view for the currect user
     *
     * @param integer $ID ID of the saved search
     *
     * @return void
     **/
    public function markDefault($ID)
    {
        global $DB;

        if (
            $this->getFromDB($ID)
            && ($this->fields['type'] != self::URI)
        ) {
            $dd = new SavedSearch_User();
           // Is default view for this itemtype already exists ?
            $iterator = $DB->request([
                'SELECT' => 'id',
                'FROM'   => 'glpi_savedsearches_users',
                'WHERE'  => [
                    'users_id'  => Session::getLoginUserID(),
                    'itemtype'  => $this->fields['itemtype']
                ]
            ]);

            if ($result = $iterator->current()) {
                // already exists update it
                $updateID = $result['id'];
                $dd->update([
                    'id'                 => $updateID,
                    'savedsearches_id'   => $ID
                ]);
            } else {
                $dd->add([
                    'savedsearches_id'   => $ID,
                    'users_id'           => Session::getLoginUserID(),
                    'itemtype'           => $this->fields['itemtype']
                ]);
            }
        }
    }


    /**
     * Unmark savedsearch as default view for the current user
     *
     * @param integer $ID ID of the saved search
     *
     * @return void
     **/
    public function unmarkDefault($ID)
    {
        global $DB;

        if (
            $this->getFromDB($ID)
            && ($this->fields['type'] != self::URI)
        ) {
            $dd = new SavedSearch_User();
           // Is default view for this itemtype already exists ?
            $iterator = $DB->request([
                'SELECT' => 'id',
                'FROM'   => 'glpi_savedsearches_users',
                'WHERE'  => [
                    'users_id'           => Session::getLoginUserID(),
                    'savedsearches_id'   => $ID,
                    'itemtype'           => $this->fields['itemtype']
                ]
            ]);

            if ($result = $iterator->current()) {
                // already exists delete it
                $deleteID = $result['id'];
                $dd->delete(['id' => $deleteID]);
            }
        }
    }


    /**
     * Unmark savedsearch as default view
     *
     * @param array $ids IDs of the saved searches
     *
     * @return boolean
     **/
    public function unmarkDefaults(array $ids)
    {
        global $DB;

        if (Session::haveRight('config', UPDATE)) {
            return $DB->delete(
                'glpi_savedsearches_users',
                [
                    'savedsearches_id'   => $ids
                ]
            );
        }

        return false;
    }


    /**
     * return an array of saved searches for a given itemtype
     *
     * @param string $itemtype if given filter saved search by only this one
     * @param bool   $inverse if true, the `itemtype` params filter by "not" criteria
     * @param bool   $enable_partial_warnings display warning messages about partial loading
     *
     * @return array
     */
    public function getMine(string $itemtype = null, bool $inverse = false, bool $enable_partial_warnings = true): array
    {
        global $DB;

        $searches = [];

        $table = $this->getTable();
        $utable = 'glpi_savedsearches_users';
        $criteria = [
            'SELECT'    => [
                "$table.*",
                "$utable.id AS is_default"
            ],
            'FROM'      => $table,
            'LEFT JOIN' => [
                $utable => ['ON' => [
                    $utable  => 'savedsearches_id',
                    $table   => 'id', [
                        'AND' => [
                            "$table.itemtype"  => new \QueryExpression("$utable.itemtype"),
                            "$utable.users_id" => Session::getLoginUserID()
                        ]
                    ]
                ]
                ]
            ],
            'WHERE'     => [
                'OR' => [
                    [
                        "$table.is_private" => 0,
                    ] + getEntitiesRestrictCriteria($table, '', '', true),
                    "$table.users_id"   => Session::getLoginUserID()
                ]
            ],
            'ORDERBY'   => [
                'itemtype',
                'name'
            ]
        ];

        if ($itemtype != null) {
            if (!$inverse) {
                $criteria['WHERE'] += [
                    "$table.itemtype" => $itemtype
                ];
            } else {
                $criteria['WHERE'] += [
                    'NOT' => ["$table.itemtype" => $itemtype]
                ];
            }
        }

        $iterator = $DB->request($criteria);
        foreach ($iterator as $data) {
            if ($_SESSION['glpishow_count_on_tabs']) {
                $this->fields = $data;
                $search_data = $this->execute(false, $enable_partial_warnings);

                $count = null;
                try {
                    $search_data = $this->execute(false, $enable_partial_warnings);
                } catch (\RuntimeException $e) {
                    ErrorHandler::getInstance()->handleException($e);
                    $search_data = false;
                }
                if (isset($search_data['data']['totalcount'])) {
                    $count = $search_data['data']['totalcount'];
                } else {
                    $info_message = ($this->fields['do_count'] == self::COUNT_NO)
                                ? __s('Count for this saved search has been disabled.')
                                : __s('Counting this saved search would take too long, it has been skipped.');
                    if ($count === null) {
                       //no count, just inform the user
                        $count = "<span class='ti ti-info-circle' title='$info_message'></span>";
                    }
                }

                $data['count'] = $count;
            }

            $searches[$data['id']] = $data;
        }

        return $searches;
    }

    /**
     * return Html list of saved searches for a given itemtype
     *
     * @param string $itemtype
     * @param bool   $inverse
     * @param bool   $enable_partial_warnings display warning messages about partial loading
     *
     * @return void
     */
    public function displayMine(string $itemtype = null, bool $inverse = false, bool $enable_partial_warnings = true)
    {
        TemplateRenderer::getInstance()->display('layout/parts/saved_searches_list.html.twig', [
            'active'         => $_SESSION['glpi_loaded_savedsearch'] ?? "",
            'saved_searches' => $this->getMine($itemtype, $inverse, $enable_partial_warnings),
        ]);
    }


    /**
     * Save order
     *
     * @param array $items Ordered ids
     *
     * @return boolean
     */
    public function saveOrder(array $items)
    {
        if (count($items)) {
            $user               = new User();
            $personalorderfield = $this->getPersonalOrderField();

            $user->update(['id'                 => Session::getLoginUserID(),
                $personalorderfield  => exportArrayToDB($items)
            ]);
            return true;
        }
        return false;
    }

    /**
     * Display buttons
     *
     * @param integer $type     SavedSearch type to use
     * @param integer $itemtype Device type of item where is the bookmark (default 0)
     * @param bool    $active   Should the icon be displayed as active ?
     *
     * @return void
     **/
    public static function showSaveButton($type, $itemtype = 0, bool $active = false)
    {
        global $CFG_GLPI;

        echo "<a href='#' class='btn btn-ghost-secondary btn-icon btn-sm me-1 bookmark_record save'
             title='" . __s('Save current search') . "'>";
        echo "<i class='ti ti-star " . ($active ? 'active' : '') . "'></i>";
        echo "</a>";

        $params = [
            'action'   => "create",
            'itemtype' => $itemtype,
            'type'     => $type,
        ];

       // If we are on a saved search, add the search id in the query so we can
       // update it if needed
        if (isset($_GET['savedsearches_id'])) {
            $params['id'] = $_GET['savedsearches_id'];
        }

        $url = $CFG_GLPI['root_doc'] . "/ajax/savedsearch.php?" . http_build_query($params);

        echo "<div id='savedsearch-modal' class='modal' data-url='$url'></div>";
    }


    /**
     * Get personal order field name
     *
     * @return string
     **/
    protected function getPersonalOrderField()
    {
        return 'privatebookmarkorder';
    }


    /**
     * Get all itemtypes used
     *
     * @return array of itemtypes
     **/
    public static function getUsedItemtypes()
    {
        global $DB;

        $types = [];
        $iterator = $DB->request([
            'SELECT'          => 'itemtype',
            'DISTINCT'        => true,
            'FROM'            => static::getTable()
        ]);
        foreach ($iterator as $data) {
            $types[] = $data['itemtype'];
        }
        return $types;
    }


    /**
     * Update bookmark execution time after it has been loaded
     *
     * @param integer $id   Saved search ID
     * @param integer $time Execution time, in milliseconds
     *
     * @return void
     **/
    public static function updateExecutionTime($id, $time)
    {
        global $DB;

        if ($_SESSION['glpishow_count_on_tabs']) {
            $DB->update(
                static::getTable(),
                [
                    'last_execution_time'   => $time,
                    'last_execution_date'   => date('Y-m-d H:i:s'),
                    'counter'               => new \QueryExpression($DB->quoteName('counter') . ' + 1')
                ],
                [
                    'id' => $id
                ]
            );
        }
    }


    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'do_count':
                switch ($values[$field]) {
                    case SavedSearch::COUNT_NO:
                        return __('No');

                    case SavedSearch::COUNT_YES:
                        return __('Yes');

                    case SavedSearch::COUNT_AUTO:
                        return ('Auto');
                }
                break;
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }


    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;

        switch ($field) {
            case 'do_count':
                $options['name']  = $name;
                $options['value'] = $values[$field];
                return self::dropdownDoCount($options);
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }


    /**
     * Dropdown of do_count possible values
     *
     * @param array $options array of options:
     *                       - name     : select name (default is do_count)
     *                       - value    : default value (default self::COUNT_AUTO)
     *                       - display  : boolean if false get string
     *
     * @return void|string
     **/
    public static function dropdownDoCount(array $options = [])
    {

        $p['name']      = 'do_count';
        $p['value']     = self::COUNT_AUTO;
        $p['display']   = true;

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        $tab = [self::COUNT_AUTO  => __('Auto'),
            self::COUNT_YES   => __('Yes'),
            self::COUNT_NO    => __('No')
        ];

        return Dropdown::showFromArray($p['name'], $tab, $p);
    }


    /**
     * Set do_count from massive actions
     *
     * @param array   $ids      Items IDs
     * @param integer $do_count One of self::COUNT_*
     *
     * @return boolean
     */
    public function setDoCount(array $ids, $do_count)
    {
        global $DB;

        $result = $DB->update(
            $this->getTable(),
            [
                'do_count' => $do_count
            ],
            [
                'id' => $ids
            ]
        );
        return $result;
    }


    /**
     * Set entity and recursivity from massive actions
     *
     * @param array   $ids   Items IDs
     * @param integer $eid   Entityy ID
     * @param boolean $recur Recursivity
     *
     * @return boolean
     */
    public function setEntityRecur(array $ids, $eid, $recur)
    {
        global $DB;

        $result = $DB->update(
            $this->getTable(),
            [
                'entities_id'  => $eid,
                'is_recursive' => $recur
            ],
            [
                'id' => $ids
            ]
        );
        return $result;
    }


    public static function cronInfo($name)
    {

        switch ($name) {
            case 'countAll':
                return ['description' => __('Update all bookmarks execution time')];
        }
        return [];
    }


    /**
     * Update all bookmarks execution time
     *
     * @param CronTask $task CronTask instance
     *
     * @return void
     **/
    public static function croncountAll($task)
    {
        global $DB, $CFG_GLPI;

        $cron_status = 0;

        if ($CFG_GLPI['show_count_on_tabs'] != -1) {
            $lastdate = new \DateTime($task->getField('lastrun'));
            $lastdate->sub(new \DateInterval('P7D'));

            $iterator = $DB->request(['FROM'   => self::getTable(),
                'FIELDS' => ['id', 'query', 'itemtype', 'type'],
                'WHERE'  => ['last_execution_date'
                                                => ['<' , $lastdate->format('Y-m-d H:i:s')]
                ]
            ]);

            if ($iterator->numrows()) {
                 //prepare variables we'll use
                 $self = new self();
                 $now = date('Y-m-d H:i:s');

                 $query = $DB->buildUpdate(
                     self::getTable(),
                     [
                         'last_execution_time'   => new QueryParam(),
                         'last_execution_date'   => new QueryParam()
                     ],
                     [
                         'id'                    => new QueryParam()
                     ]
                 );
                 $stmt = $DB->prepare($query);

                if (!isset($_SESSION['glpiname'])) {
                     //required from search class
                     $_SESSION['glpiname'] = 'crontab';
                }
                if (!isset($_SESSION['glpigroups'])) {
                    $_SESSION['glpigroups'] = [];
                }

                 $in_transaction = $DB->inTransaction();
                if (!$in_transaction) {
                    $DB->beginTransaction();
                }
                foreach ($iterator as $row) {
                    try {
                        $self->fields = $row;
                        if ($data = $self->execute(true)) {
                              $execution_time = $data['data']['execution_time'];

                              $stmt->bind_param('sss', $execution_time, $now, $row['id']);
                              $DB->executeStatement($stmt);
                        }
                    } catch (\Exception $e) {
                        ErrorHandler::getInstance()->handleException($e);
                    }
                }

                $stmt->close();
                if (!$in_transaction) {
                     $DB->commit();
                }

                $cron_status = 1;
            }
        } else {
            trigger_error('Count on tabs has been disabled; crontask is inefficient.', E_USER_WARNING);
        }

        return $cron_status;
    }


    /**
     * Execute current saved search and return results
     *
     * @param boolean $force Force query execution even if it should not be executed
     *                       (default false)
     * @param boolean $enable_partial_warnings display warning messages about partial loading
     *
     * @throws RuntimeException
     *
     * @return array|null
     **/
    public function execute($force = false, bool $enable_partial_warnings = true)
    {
        global $CFG_GLPI;

        if (
            ($force === true)
            || (($this->fields['do_count'] == self::COUNT_YES)
              || ($this->fields['do_count'] == self::COUNT_AUTO)
              && ($this->getField('last_execution_time') != null)
              && ($this->fields['last_execution_time'] <= $CFG_GLPI['max_time_for_count']))
        ) {
            $search = new Search();
           //Do the same as self::getParameters() but getFromDB is useless
            $query_tab = [];
            parse_str($this->getField('query'), $query_tab);

            $params = null;
            if (class_exists($this->getField('itemtype'))) {
                $params = $this->prepareQueryToUse(
                    $this->getField('type'),
                    $query_tab,
                    $enable_partial_warnings
                );
            }

            if (!$params) {
                throw new \RuntimeException('Saved search #' . $this->getID() . ' seems to be broken!');
            } else {
                $data                   = $search->prepareDatasForSearch(
                    $this->getField('itemtype'),
                    $params
                );
                $data['search']['sort'] = [];
                $search->constructSQL($data);
                $search->constructData($data, true);
                return $data;
            }
        }

        return null;
    }


    /**
     * Create specific notification for a public saved search
     *
     * @return void
     */
    public function createNotif()
    {

        $notif = new Notification();
        $notif->getFromDBByCrit(['event' => 'alert_' . $this->getID()]);

        if ($notif->isNewItem()) {
            $notif->check(-1, CREATE);
            $notif->add(['name'            => SavedSearch::getTypeName(1) . ' ' . addslashes($this->getName()),
                'entities_id'     => $_SESSION["glpidefault_entity"],
                'itemtype'        => SavedSearch_Alert::getType(),
                'event'           => 'alert_' . $this->getID(),
                'is_active'       => 0,
                'date_creation' => date('Y-m-d H:i:s')
            ]);

            Session::addMessageAfterRedirect(__('Notification has been created!'), INFO);
        }
    }

    /**
     * Return visibility SQL restriction to add
     *
     * @return string restrict to add
     **/
    public static function addVisibilityRestrict()
    {
       //not deprecated because used in Search

        if (Session::haveRight('config', UPDATE)) {
            return '';
        }

       //get and clean criteria
        $criteria = self::getVisibilityCriteria();
        unset($criteria['LEFT JOIN']);
        $criteria['FROM'] = self::getTable();

        $it = new \DBmysqlIterator(null);
        $it->buildQuery($criteria);
        $sql = $it->getSql();
        $sql = preg_replace('/.*WHERE /', '', $sql);

        return $sql;
    }

    /**
     * Return visibility joins to add to DBIterator parameters
     *
     * @since 9.4
     *
     * @param boolean $forceall force all joins (false by default)
     *
     * @return array
     */
    public static function getVisibilityCriteria(bool $forceall = false): array
    {
        $criteria = ['WHERE' => []];
        if (Session::haveRight('config', UPDATE)) {
            return $criteria;
        }

        $restrict = [
            self::getTable() . '.is_private' => 1,
            self::getTable() . '.users_id'    => Session::getLoginUserID()
        ];

        if (Session::haveRight(self::$rightname, READ)) {
            $restrict = [
                'OR' => [
                    $restrict,
                    self::getTable() . '.is_private' => 0
                ]
            ];
        }

        $criteria['WHERE'] = $restrict;
        return $criteria;
    }


    public static function getIcon()
    {
        return "ti ti-bookmarks";
    }

    public function getCloneRelations(): array
    {
        return [];
    }
}
