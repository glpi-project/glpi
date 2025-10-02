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

use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryParam;
use Glpi\Error\ErrorHandler;
use Glpi\Features\Clonable;
use Glpi\Toolbox\ArrayNormalizer;
use Safe\DateTime;

use function Safe\parse_url;
use function Safe\preg_replace;

/**
 * Saved searches class
 *
 * @since 9.2
 **/
class SavedSearch extends CommonDBTM implements ExtraVisibilityCriteria
{
    use Clonable;

    public static $rightname               = 'bookmark_public';

    public const SEARCH = 1; //SEARCH SYSTEM bookmark
    public const URI    = 2;
    public const ALERT  = 3; //SEARCH SYSTEM search alert

    public const COUNT_NO = 0;
    public const COUNT_YES = 1;
    public const COUNT_AUTO = 2;

    public static function getForbiddenActionsForMenu()
    {
        return ['add'];
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Saved search', 'Saved searches', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['tools', self::class];
    }

    public function canUpdateItem(): bool
    {
        return Session::haveRight(self::$rightname, UPDATE)
            || $this->fields["users_id"] === Session::getLoginUserID();
    }

    public function getForbiddenStandardMassiveAction()
    {
        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        $forbidden[] = 'clone';
        return $forbidden;
    }

    public function getSpecificMassiveActions($checkitem = null)
    {
        $actions = parent::getSpecificMassiveActions($checkitem);

        $actions[self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'unset_default']
                     = "<i class='ti ti-star'></i>" . __s('Unset as default');
        $actions[self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'change_count_method']
                     = "<i class='ti ti-adjustments-alt'></i>" . __s('Change count method');
        $actions[self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'change_visibility']
                     = "<i class='ti ti-eye-search'></i>" . __s('Change visibility');
        if (Session::haveRight('transfer', READ)) {
            $actions[self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'change_entity']
                     = "<i class='ti ti-corner-right-up'></i>" . __s('Change entity');
        }
        return $actions;
    }

    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {

        switch ($ma->getAction()) {
            case 'change_count_method':
                $values = [self::COUNT_AUTO  => __('Auto'),
                    self::COUNT_YES   => __('Yes'),
                    self::COUNT_NO    => __('No'),
                ];
                Dropdown::showFromArray('do_count', $values, ['width' => '20%']);
                break;

            case 'change_entity':
                Entity::dropdown(['entity' => $_SESSION['glpiactiveentities'],
                    'value'  => $_SESSION['glpiactive_entity'],
                    'name'   => 'entities_id',
                ]);
                echo '<br/>';
                echo __s('Child entities');
                Dropdown::showYesNo('is_recursive');
                echo '<br/>';
                break;
            case 'change_visibility':
                echo __s('Visibility');
                Dropdown::showFromArray(
                    'is_private',
                    [
                        1  => __('Private'),
                        0  => __('Public'),
                    ],
                );
                break;
        }
        return parent::showMassiveActionsSubForm($ma);
    }

    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {
        /** @var SavedSearch $item */
        $input = $ma->getInput();
        switch ($ma->getAction()) {
            case 'unset_default':
                foreach ($ids as $id) {
                    $saved_search = new SavedSearch();
                    if ($saved_search->getFromDB($id)) {
                        if ($saved_search->can($id, UPDATE)) {
                            $success = (new SavedSearch_User())->deleteByCriteria(['savedsearches_id' => $id]);
                            if ($success) {
                                $ma->itemDone($saved_search->getType(), $id, MassiveAction::ACTION_OK);
                            } else {
                                $ma->itemDone($saved_search->getType(), $id, MassiveAction::ACTION_KO);
                                $ma->addMessage($saved_search->getErrorMessage(ERROR_ON_ACTION));
                            }
                        } else {
                            $ma->itemDone($saved_search->getType(), $id, MassiveAction::ACTION_NORIGHT);
                            $ma->addMessage($saved_search->getErrorMessage(ERROR_RIGHT));
                        }
                    } else {
                        $ma->itemDone($saved_search->getType(), $id, MassiveAction::ACTION_KO);
                        $ma->addMessage($saved_search->getErrorMessage(ERROR_NOT_FOUND));
                    }
                }
                break;

            case 'change_count_method':
                foreach ($ids as $id) {
                    $saved_search = new SavedSearch();
                    if ($saved_search->getFromDB($id)) {
                        if ($saved_search->can($id, UPDATE)) {
                            $success = $saved_search->update([
                                'id' => $id,
                                'do_count' => $input['do_count'],
                            ]);
                            if ($success) {
                                $ma->itemDone($saved_search->getType(), $id, MassiveAction::ACTION_OK);
                            } else {
                                $ma->itemDone($saved_search->getType(), $id, MassiveAction::ACTION_KO);
                                $ma->addMessage($saved_search->getErrorMessage(ERROR_ON_ACTION));
                            }
                        } else {
                            $ma->itemDone($saved_search->getType(), $id, MassiveAction::ACTION_NORIGHT);
                            $ma->addMessage($saved_search->getErrorMessage(ERROR_RIGHT));
                        }
                    } else {
                        $ma->itemDone($saved_search->getType(), $id, MassiveAction::ACTION_KO);
                        $ma->addMessage($saved_search->getErrorMessage(ERROR_NOT_FOUND));
                    }
                }
                break;

            case 'change_entity':
                foreach ($ids as $id) {
                    $saved_search = new SavedSearch();
                    if ($saved_search->getFromDB($id)) {
                        if ($saved_search->can($id, UPDATE)) {
                            $success = $saved_search->update([
                                'id' => $id,
                                'entities_id' => $input['entities_id'],
                                'is_recursive' => $input['is_recursive'],
                            ]);
                            if ($success) {
                                $ma->itemDone($saved_search->getType(), $id, MassiveAction::ACTION_OK);
                            } else {
                                $ma->itemDone($saved_search->getType(), $id, MassiveAction::ACTION_KO);
                                $ma->addMessage($saved_search->getErrorMessage(ERROR_ON_ACTION));
                            }
                        } else {
                            $ma->itemDone($saved_search->getType(), $id, MassiveAction::ACTION_NORIGHT);
                            $ma->addMessage($saved_search->getErrorMessage(ERROR_RIGHT));
                        }
                    } else {
                        $ma->itemDone($saved_search->getType(), $id, MassiveAction::ACTION_KO);
                        $ma->addMessage($saved_search->getErrorMessage(ERROR_NOT_FOUND));
                    }
                }
                break;
            case 'change_visibility':
                $input = $ma->getInput();
                foreach ($ids as $id) {
                    $saved_search = new SavedSearch();
                    if ($saved_search->getFromDB($id)) {
                        if ($saved_search->can($id, UPDATE)) {
                            $success = $saved_search->update([
                                'id' => $id,
                                'is_private' => $input['is_private'],
                            ]);
                            if ($success) {
                                $ma->itemDone($saved_search->getType(), $id, MassiveAction::ACTION_OK);
                            } else {
                                $ma->itemDone($saved_search->getType(), $id, MassiveAction::ACTION_KO);
                                $ma->addMessage($saved_search->getErrorMessage(ERROR_ON_ACTION));
                            }
                        } else {
                            $ma->itemDone($saved_search->getType(), $id, MassiveAction::ACTION_NORIGHT);
                            $ma->addMessage($saved_search->getErrorMessage(ERROR_RIGHT));
                        }
                    } else {
                        $ma->itemDone($saved_search->getType(), $id, MassiveAction::ACTION_KO);
                        $ma->addMessage($saved_search->getErrorMessage(ERROR_NOT_FOUND));
                    }
                }
                break;
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }

    public function canCreateItem(): bool
    {

        if ($this->fields['is_private'] == 1) {
            return (Session::haveRight('config', UPDATE)
                 || $this->fields['users_id'] == Session::getLoginUserID());
        }
        return parent::canCreateItem();
    }

    public function canViewItem(): bool
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
           ->addStandardTab(SavedSearch_Alert::class, $ong, $options);
        return $ong;
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = ['id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false, // implicit field is id
            'datatype'           => 'number',
        ];

        $tab[] = ['id'                 => 3,
            'table'              => User::getTable(),
            'field'              => 'name',
            'name'               => User::getTypeName(1),
            'datatype'           => 'dropdown',
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
            'types'              => self::getUsedItemtypes(),
        ];

        $tab[] = ['id'                 => 9,
            'table'              => $this->getTable(),
            'field'              => 'last_execution_time',
            'name'               => __('Last duration (ms)'),
            'massiveaction'      => false,
            'datatype'           => 'number',
        ];

        $tab[] = ['id'                 => 10,
            'table'              => $this->getTable(),
            'field'              => 'do_count',
            'name'               => __('Count'),
            'massiveaction'      => true,
            'datatype'           => 'specific',
            'searchtype'         => 'equals',
        ];

        $tab[] = [
            'id'            => 11,
            'table'         => SavedSearch_User::getTable(),
            'field'         => 'users_id',
            'name'          => __('Default'),
            'massiveaction' => false,
            'joinparams'    => [
                'jointype'  => 'child',
                'condition' => ['NEWTABLE.users_id' => Session::getLoginUserID()],
            ],
            'datatype'      => 'specific',
            'searchtype'    => [
                0 => 'equals',
                1 => 'notequals',
            ],
        ];

        $tab[] = ['id'                 => 12,
            'table'              => $this->getTable(),
            'field'              => 'counter',
            'name'               => __('Counter'),
            'massiveaction'      => false,
            'datatype'           => 'number',
        ];

        $tab[] = ['id'                 => 13,
            'table'              => $this->getTable(),
            'field'              => 'last_execution_date',
            'name'               => __('Last execution date'),
            'massiveaction'      => false,
            'datatype'           => 'datetime',
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'datatype'           => 'dropdown',
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
        $taburl = parse_url($input['url']);

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
        $this->fields["entities_id"]  = Session::getActiveEntity();
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

    public function showForm($ID, array $options = [])
    {
        if (empty($this->fields) && $ID > 0) {
            $this->getFromDB($ID);
        }
        // If this form is used to edit a saved search from the search screen
        $is_ajax = $options['ajax'] ?? false;
        if ($is_ajax && $this->getID() > 0) {
            // Allow an extra option to save as a new search instead of editing the current one
            $options['addbuttons'] = ["add" => __("Save as a new search")];
            // Do not allow delete from this modal
            $options['candel'] = false;
        }

        TemplateRenderer::getInstance()->display('pages/tools/savedsearch/form.html.twig', [
            'item' => $this,
            'can_create' => self::canCreate(),
            'params' => $options,
        ]);
        return true;
    }

    /**
     * Prepare query to store depending on the type
     *
     * @param integer $type      Saved search type (self::SEARCH, self::URI or self::ALERT)
     * @param array   $query_tab Parameters
     *
     * @return array clean query array
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
                    '_glpi_csrf_token',
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

        $itemtype = $this->fields['itemtype'];
        $url = $itemtype::getSearchURL();

        // Prevents parameter duplication
        $parse_url = parse_url($url);
        if (isset($parse_url['query'])) {
            parse_str($parse_url['query'], $url_params);
            $url = $parse_url['path'];
            $params = array_merge($url_params, $params);
        }
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
        $query_tab['reset'] = 'reset';
        return $query_tab;
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
                    'itemtype'  => $this->fields['itemtype'],
                ],
            ]);

            if ($result = $iterator->current()) {
                // already exists update it
                $updateID = $result['id'];
                $dd->update([
                    'id'                 => $updateID,
                    'savedsearches_id'   => $ID,
                ]);
            } else {
                $dd->add([
                    'savedsearches_id'   => $ID,
                    'users_id'           => Session::getLoginUserID(),
                    'itemtype'           => $this->fields['itemtype'],
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
                    'itemtype'           => $this->fields['itemtype'],
                ],
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
                    'savedsearches_id'   => $ids,
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
     *
     * @return array
     */
    public function getMine(?string $itemtype = null, bool $inverse = false): array
    {
        global $DB;

        $searches = [];

        $table = $this->getTable();
        $utable = 'glpi_savedsearches_users';
        $criteria = [
            'SELECT'    => [
                "$table.*",
                new QueryExpression(
                    "IF($utable.users_id = " . Session::getLoginUserID() . ", $utable.id, NULL) AS is_default"
                ),
            ],
            'FROM'      => $table,
            'LEFT JOIN' => [
                $utable => [
                    'ON' => [
                        $utable  => 'savedsearches_id',
                        $table   => 'id',
                    ],
                ],
            ],
            'ORDERBY'   => [
                'itemtype',
                'name',
            ],
        ] + self::getVisibilityCriteriaForMine();

        if ($itemtype != null) {
            if (!$inverse) {
                $criteria['WHERE'] += [
                    "$table.itemtype" => $itemtype,
                ];
            } else {
                $criteria['WHERE'] += [
                    'NOT' => ["$table.itemtype" => $itemtype],
                ];
            }
        }

        $iterator = $DB->request($criteria);
        foreach ($iterator as $data) {
            $error = false;

            if ($_SESSION['glpishow_count_on_tabs']) {
                $this->fields = $data;
                $count = null;
                $search_data = null;
                try {
                    $search_data = $this->execute();
                } catch (Throwable $e) {
                    ErrorHandler::logCaughtException($e);
                    ErrorHandler::displayCaughtExceptionMessage($e);
                    $error = true;
                }

                if ($error) {
                    $info_message = __s('A fatal error occurred while executing this saved search. It is not able to be used.');
                    $count = "<span class='ti ti-alert-triangle-filled' title='$info_message'></span>";
                } elseif (isset($search_data['data']['totalcount'])) {
                    $count = $search_data['data']['totalcount'];
                } else {
                    $info_message = ($this->fields['do_count'] == self::COUNT_NO)
                                ? __s('Count for this saved search has been disabled.')
                                : __s('Counting this saved search would take too long, it has been skipped.');
                    // no count, just inform the user
                    $count = "<span class='ti ti-info-circle' title='$info_message'></span>";
                }

                $data['count'] = $count;
            }

            $data['_error'] = $error;

            $searches[$data['id']] = $data;
        }

        // get personal order
        $user               = new User();
        $personalorderfield = $this->getPersonalOrderField();
        $ordered            = [];

        $personalorder = [];
        if ($user->getFromDB(Session::getLoginUserID())) {
            $personalorder = importArrayFromDB($user->fields[$personalorderfield]);
        }

        // Add on personal order
        if (count($personalorder)) {
            foreach ($personalorder as $id) {
                if (isset($searches[$id])) {
                    $ordered[$id] = $searches[$id];
                    unset($searches[$id]);
                }
            }
        }

        // Add unsaved in order
        if (count($searches)) {
            foreach ($searches as $id => $val) {
                $ordered[$id] = $val;
            }
        }

        return $ordered;
    }

    /**
     * return Html list of saved searches for a given itemtype
     *
     * @param string|null $itemtype
     * @param bool   $inverse
     *
     * @return void
     */
    public function displayMine(?string $itemtype = null, bool $inverse = false)
    {
        TemplateRenderer::getInstance()->display('layout/parts/saved_searches_list.html.twig', [
            'active'         => $_SESSION['glpi_loaded_savedsearch'] ?? "",
            'saved_searches' => $this->getMine($itemtype, $inverse),
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

            $user->update([
                'id'                 => Session::getLoginUserID(),
                $personalorderfield  => exportArrayToDB(
                    ArrayNormalizer::normalizeValues($items, 'intval')
                ),
            ]);
            return true;
        }
        return false;
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
            'FROM'            => static::getTable(),
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
                    'counter'               => new QueryExpression($DB->quoteName('counter') . ' + 1'),
                ],
                [
                    'id' => $id,
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
                        return __s('No');

                    case SavedSearch::COUNT_YES:
                        return __s('Yes');

                    case SavedSearch::COUNT_AUTO:
                        return __s('Auto');
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

        if (count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        $tab = [self::COUNT_AUTO  => __('Auto'),
            self::COUNT_YES   => __('Yes'),
            self::COUNT_NO    => __('No'),
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
                'do_count' => $do_count,
            ],
            [
                'id' => $ids,
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
                'is_recursive' => $recur,
            ],
            [
                'id' => $ids,
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
     * @return integer
     **/
    public static function croncountAll($task)
    {
        global $CFG_GLPI, $DB;

        $cron_status = 0;

        if ($CFG_GLPI['show_count_on_tabs'] != -1) {
            $lastdate = new DateTime($task->getField('lastrun'));
            $lastdate->sub(new DateInterval('P7D'));

            $iterator = $DB->request(['FROM'   => self::getTable(),
                'FIELDS' => ['id', 'query', 'itemtype', 'type'],
                'WHERE'  => ['last_execution_date'
                                                => ['<', $lastdate->format('Y-m-d H:i:s')],
                ],
            ]);

            if ($iterator->numrows()) {
                //prepare variables we'll use
                $self = new self();
                $now = date('Y-m-d H:i:s');

                $query = $DB->buildUpdate(
                    self::getTable(),
                    [
                        'last_execution_time'   => new QueryParam(),
                        'last_execution_date'   => new QueryParam(),
                    ],
                    [
                        'id'                    => new QueryParam(),
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

                $DB->beginTransaction();

                foreach ($iterator as $row) {
                    try {
                        $self->fields = $row;
                        if ($data = $self->execute(true)) {
                            $execution_time = $data['data']['execution_time'];

                            $stmt->bind_param('sss', $execution_time, $now, $row['id']);
                            $DB->executeStatement($stmt);
                        }
                    } catch (Throwable $e) {
                        ErrorHandler::logCaughtException($e);
                        ErrorHandler::displayCaughtExceptionMessage($e);
                    }
                }

                $stmt->close();
                $DB->commit();

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
     *
     * @throws RuntimeException
     *
     * @return array|null
     **/
    public function execute($force = false)
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

            $params = class_exists($this->getField('itemtype')) ? $query_tab : null;

            if (!$params) {
                throw new RuntimeException('Saved search #' . $this->getID() . ' seems to be broken!');
            } else {
                $params['silent_validation'] = true;
                $data                   = $search->prepareDatasForSearch(
                    $this->getField('itemtype'),
                    $params
                );
                // force saved search ID to indicate to Search to save execution time
                $data['search']['savedsearches_id'] = $this->getID();
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
            $notif->add(['name'            => SavedSearch::getTypeName(1) . ' ' . $this->getName(),
                'entities_id'     => $_SESSION["glpidefault_entity"],
                'itemtype'        => SavedSearch_Alert::getType(),
                'event'           => 'alert_' . $this->getID(),
                'is_active'       => 0,
                'date_creation' => date('Y-m-d H:i:s'),
            ]);

            Session::addMessageAfterRedirect(__s('Notification has been created!'), false, INFO);
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

        $it = new DBmysqlIterator(null);
        $it->buildQuery($criteria);
        $sql = $it->getSql();
        $sql = preg_replace('/.*WHERE /', '', $sql);

        return $sql;
    }

    private static function getVisibilityCriteriaForMine(): array
    {
        $criteria = ['WHERE' => []];
        $restrict = [
            self::getTable() . '.is_private' => 1,
            self::getTable() . '.users_id'    => Session::getLoginUserID(),
        ];

        if (Session::haveRight(self::$rightname, READ)) {
            $restrict = [
                'OR' => [
                    $restrict,
                    [self::getTable() . '.is_private' => 0],
                ],
            ];
        }

        $criteria['WHERE'] = $restrict + getEntitiesRestrictCriteria(self::getTable(), '', '', true);
        return $criteria;
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
        if (Session::haveRight('config', UPDATE)) {
            return ['WHERE' => []];
        }

        return self::getVisibilityCriteriaForMine();
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
