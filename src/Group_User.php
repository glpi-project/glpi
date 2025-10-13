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
use Glpi\DBAL\QueryParam;
use Glpi\Event;

/**
 * Group_User Class
 *
 *  Relation between Group and User
 **/
class Group_User extends CommonDBRelation
{
    // From CommonDBRelation
    public static $itemtype_1                 = 'User';
    public static $items_id_1                 = 'users_id';

    public static $itemtype_2                 = 'Group';
    public static $items_id_2                 = 'groups_id';

    /**
     * Check if a user belongs to a group
     *
     * @since 9.4
     *
     * @param integer $users_id  the user ID
     * @param integer $groups_id the group ID
     *
     * @return boolean true if the user belongs to the group
     */
    public static function isUserInGroup($users_id, $groups_id): bool
    {
        return countElementsInTable(
            'glpi_groups_users',
            [
                'users_id' => $users_id,
                'groups_id' => $groups_id,
            ]
        ) > 0;
    }

    /**
     * Get groups for a user
     *
     * @param integer $users_id  User id
     * @param array   $condition Query extra condition (default [])
     *
     * @return array
     **/
    public static function getUserGroups($users_id, $condition = []): array
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => [
                'glpi_groups.*',
                'glpi_groups_users.id AS IDD',
                'glpi_groups_users.id AS linkid',
                'glpi_groups_users.is_dynamic AS is_dynamic',
                'glpi_groups_users.is_manager AS is_manager',
                'glpi_groups_users.is_userdelegate AS is_userdelegate',
            ],
            'FROM'   => self::getTable(),
            'LEFT JOIN'    => [
                Group::getTable() => [
                    'FKEY' => [
                        Group::getTable() => 'id',
                        self::getTable()  => 'groups_id',
                    ],
                ],
            ],
            'WHERE'        => [
                'glpi_groups_users.users_id' => $users_id,
            ] + $condition,
            'ORDER'        => 'glpi_groups.name',
        ]);

        return array_values(iterator_to_array($iterator));
    }

    /**
     * Get users for a group
     *
     * @since 0.84
     *
     * @param integer $groups_id Group ID
     * @param array   $condition Query extra condition (default [])
     *
     * @return array
     **/
    public static function getGroupUsers($groups_id, $condition = [])
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => [
                'glpi_users.*',
                'glpi_groups_users.id AS IDD',
                'glpi_groups_users.id AS linkid',
                'glpi_groups_users.is_dynamic AS is_dynamic',
                'glpi_groups_users.is_manager AS is_manager',
                'glpi_groups_users.is_userdelegate AS is_userdelegate',
            ],
            'FROM'   => self::getTable(),
            'LEFT JOIN'    => [
                User::getTable() => [
                    'FKEY' => [
                        User::getTable() => 'id',
                        self::getTable()  => 'users_id',
                    ],
                ],
            ],
            'WHERE'        => [
                'glpi_groups_users.groups_id' => $groups_id,
            ] + $condition,
            'ORDER'        => 'glpi_users.name',
        ]);

        return array_values(iterator_to_array($iterator));
    }

    /**
     * Show groups of a user
     *
     * @param User $user   User object
     **/
    public static function showForUser(User $user)
    {
        $ID = $user->fields['id'];
        if (
            !Group::canView()
            || !$user->can($ID, READ)
        ) {
            return false;
        }

        $canedit = $user->can($ID, UPDATE);

        $rand    = mt_rand();

        $iterator = self::getListForItem($user);
        $groups = [];
        $used    = [];
        foreach ($iterator as $data) {
            $used[$data["id"]] = $data["id"];
            $groups[] = $data;
        }

        if ($canedit) {
            $group_user = new self();
            $group_user->fields['users_id'] = $ID;
            TemplateRenderer::getInstance()->display('pages/admin/group_user.html.twig', [
                'source_itemtype' => User::class,
                'item' => $group_user,
                'no_header' => true,
                'used' => $used,
            ]);
        }

        $group = new Group();
        $entries = [];
        $yes_icon = '<i class="ti ti-check" title="' . __s('Yes') . '"></i>';
        $no_icon  = '<span class="visually-hidden" aria-label="' . __s('No') . '"></span>';
        foreach ($groups as $data) {
            if (!$group->getFromDB($data["id"])) {
                continue;
            }
            $entries[] = [
                'itemtype' => self::class,
                'id'       => $data["linkid"],
                'group'    => $group->getLink(),
                'dynamic'  => $data['is_dynamic'] ? $yes_icon : $no_icon,
                'manager'  => $data['is_manager'] ? $yes_icon : $no_icon,
                'delegatee' => $data['is_userdelegate'] ? $yes_icon : $no_icon,
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'columns' => [
                'group' => Group::getTypeName(1),
                'dynamic' => __('Dynamic'),
                'manager' => _n('Manager', 'Managers', 1),
                'delegatee' => __('Delegatee'),
            ],
            'formatters' => [
                'group' => 'raw_html',
                'dynamic' => 'raw_html',
                'manager' => 'raw_html',
                'delegatee' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . $rand,
            ],
        ]);
    }

    /**
     * Show form to add a user in current group
     *
     * @since 0.83
     *
     * @param Group $group
     * @param array $used_ids Array of already added users
     * @param array $entityrestrict Array of entities
     **/
    private static function showAddUserForm(Group $group, $used_ids, $entityrestrict)
    {
        $res  = User::getSqlSearchResult(true, "all", $entityrestrict, 0, $used_ids, '', 0, -1, false, 1);
        $nb = count($res);
        if ($nb) {
            $group_user = new self();
            $group_user->fields['groups_id'] = $group->getID();
            TemplateRenderer::getInstance()->display('pages/admin/group_user.html.twig', [
                'source_itemtype' => Group::class,
                'item' => $group_user,
                'no_header' => true,
                'used' => $used_ids,
                'entityrestrict' => $entityrestrict,
            ]);
        }
    }


    /**
     * Retrieve list of member of a Group
     *
     * @since 0.83
     *
     * @param Group    $group            Group object
     * @param array    $members          Array filled on output of member (filtered)
     * @param array    $ids              Array of ids (not filtered)
     * @param string|array $crit         Filter key (is_manager, is_userdelegate) or array of filters (default '')
     * @param bool|int $tree             True to include member of sub-group (default 0)
     * @param bool     $check_entities   Apply entities restrictions ?
     *
     * @return array|int Entities for restriction
     **/
    public static function getDataForGroup(
        Group $group,
        &$members,
        &$ids,
        $crit = '',
        $tree = 0,
        bool $check_entities = true
    ) {
        global $DB;

        // Entity restriction for this group, according to user allowed entities
        if ($group->fields['is_recursive']) {
            $entityrestrict = getSonsOf('glpi_entities', $group->fields['entities_id']);

            // active entity could be a child of object entity
            if (
                ($_SESSION['glpiactive_entity'] != $group->fields['entities_id'])
                && in_array($_SESSION['glpiactive_entity'], $entityrestrict)
            ) {
                $entityrestrict = getSonsOf('glpi_entities', $_SESSION['glpiactive_entity']);
            }
        } else {
            $entityrestrict = $group->fields['entities_id'];
        }

        if ($tree) {
            $restrict = getSonsOf('glpi_groups', $group->getID());
        } else {
            $restrict = $group->getID();
        }

        $group_users_table = self::getTable();

        // All group members
        $pu_table = Profile_User::getTable();
        $query = [
            'SELECT' => [
                'glpi_users.id',
                'glpi_users.is_active',
                'glpi_groups_users.id AS linkid',
                'glpi_groups_users.groups_id',
                'glpi_groups_users.is_dynamic AS is_dynamic',
                'glpi_groups_users.is_manager AS is_manager',
                'glpi_groups_users.is_userdelegate AS is_userdelegate',
            ],
            'DISTINCT'  => true,
            'FROM'      => $group_users_table,
            'LEFT JOIN' => [
                User::getTable() => [
                    'ON' => [
                        $group_users_table => 'users_id',
                        User::getTable() => 'id',
                    ],
                ],
                $pu_table => [
                    'ON' => [
                        $pu_table        => 'users_id',
                        User::getTable() => 'id',
                    ],
                ],
            ],
            'WHERE' => [
                $group_users_table . '.groups_id'  => $restrict,
            ],
            'ORDERBY' => [
                User::getTable() . '.realname',
                User::getTable() . '.firstname',
                User::getTable() . '.name',
            ],
        ];

        // Add entities restrictions
        if ($check_entities) {
            $query['WHERE']['OR'] = [
                "$pu_table.entities_id" => null,
            ] + getEntitiesRestrictCriteria($pu_table, '', $entityrestrict, true);
        }

        $iterator = $DB->request($query);

        foreach ($iterator as $data) {
            // Add to display list, according to criterion
            $add = true;
            if (is_array($crit)) {
                foreach ($crit as $key => $value) {
                    $add = $value === '' || match ($key) {
                        'dynamic' => $data['is_dynamic'] === (int) $value,
                        'manager' => $data['is_manager'] === (int) $value,
                        'delegatee' => $data['is_userdelegate'] === (int) $value,
                        'is_active' => $data[$key] === (int) $value,
                        default => true
                    };
                    if (!$add) {
                        break;
                    }
                }
            }
            if (empty($crit) || $add || (is_string($crit) && $data[$crit])) {
                $members[] = $data;
            }
            // Add to member list (member of sub-group are not member)
            if ((int) $data['groups_id'] === $group->getID()) {
                $ids[]  = $data['id'];
            }
        }

        return $entityrestrict;
    }

    /**
     * Show users of a group
     *
     * @param Group $group
     * @since 0.83
     */
    public static function showForGroup(Group $group)
    {
        $ID = $group->getID();
        if (
            !User::canView()
            || !$group->can($ID, READ)
        ) {
            return false;
        }

        // Have right to manage members
        $canedit = self::canUpdate();
        $rand    = mt_rand();
        $user    = new User();
        $used    = [];
        $ids     = [];

        self::getDataForGroup($group, $used, $ids, $_GET['filters'] ?? [], true, false);
        $all_groups = count($used);
        $used    = [];
        $ids     = [];

        // Retrieve member list
        // TODO: migrate to use CommonDBRelation::getListForItem()
        $entityrestrict = self::getDataForGroup($group, $used, $ids, $_GET['filters'] ?? [], true, true);

        // We will load implicits members from parents groups and display
        // them after all the "direct" members
        $parents_members = self::getParentsMembers($group, '');

        foreach ($parents_members as $parent) {
            // Flag group as implicit, will be used to disallow massive
            // actions for this group
            $parent['implicit'] = true;
            $used[] = $parent;
        }

        // Remove duplicated data (explicit membership will be shown over
        // implicits one. In case of no explicits membership and multiple
        // implicites one, only the firt one will be shown)
        // array_values is used to avoid gaps in the keys, which is needed
        // because some code below do a for loop on the data
        //TODO The results from this don't seem correct and gives inconsistent results when filtering by columns like manager, dynamic, etc.
        //$used = array_values(self::clearDuplicatedGroupData($used));

        if ($canedit) {
            self::showAddUserForm($group, $ids, $entityrestrict);
        }

        $number = count($used);
        $start  = (isset($_GET['start']) ? (int) $_GET['start'] : 0);
        if ($start >= $number) {
            $start = 0;
        }

        if ($number != $all_groups) {
            echo "<div class='alert alert-primary d-flex align-items-center mb-4' role='alert'>";
            echo "<i class='ti ti-info-circle fs-1'></i>";
            echo "<span class='ms-2'>";
            echo __s("Some users are not listed as they are not visible from your current entity.");
            echo "</span>";
            echo "</div>";
        }

        $tmpgrp = new Group();
        $entries = [];
        $yes_icon = '<i class="ti ti-check" title="' . __s('Yes') . '"></i>';
        $no_icon  = '<span class="visually-hidden" aria-label="' . __s('No') . '"></span>';
        for ($i = $start, $j = 0; ($i < $number) && ($j < $_SESSION['glpilist_limit']); $i++, $j++) {
            $data = $used[$i];
            $user->getFromDB($data["id"]);
            $group_link = '';
            if ($tmpgrp->getFromDB($data['groups_id'])) {
                $group_link = $tmpgrp->getLink(['comments' => true]);
            }
            $entries[] = [
                'itemtype'  => self::class,
                'id'        => $data["linkid"],
                'row_class' => $user->isDeleted() ? 'table-danger' : '',
                'user'      => $user->getLink(),
                'group'     => $group_link,
                'dynamic'   => $data['is_dynamic'] ? $yes_icon : $no_icon,
                'manager'   => $data['is_manager'] ? $yes_icon : $no_icon,
                'delegatee' => $data['is_userdelegate'] ? $yes_icon : $no_icon,
                'active'    => $user->fields['is_active'] ? $yes_icon : $no_icon,
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'start' => $start,
            'limit' => $_SESSION['glpilist_limit'],
            'is_tab' => true,
            'use_pager' => true,
            'nosort' => true,
            'items_id' => $ID,
            'filters' => $_GET['filters'] ?? [],
            'columns' => [
                'user' => [
                    'label' => User::getTypeName(1),
                    'no_filter' => true,
                ],
                'group' => [
                    'label' => Group::getTypeName(1),
                    'no_filter' => true,
                ],
                'dynamic' => [
                    'label' => __('Dynamic'),
                    'filter_formatter' => 'yesno',
                ],
                'manager' => [
                    'label' => _n('Manager', 'Managers', 1),
                    'filter_formatter' => 'yesno',
                ],
                'delegatee' => [
                    'label' => __('Delegatee'),
                    'filter_formatter' => 'yesno',
                ],
                'is_active' => [
                    'label' => __('Active'),
                    'filter_formatter' => 'yesno',
                ],
            ],
            'formatters' => [
                'user' => 'raw_html',
                'group' => 'raw_html',
                'dynamic' => 'raw_html',
                'manager' => 'raw_html',
                'delegatee' => 'raw_html',
                'is_active' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => $number,
            'filtered_number' => $number,
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . $rand,
            ],
        ]);
    }

    public static function getRelationMassiveActionsSpecificities()
    {
        $specificities                           = parent::getRelationMassiveActionsSpecificities();

        $specificities['select_items_options_1'] = ['right'     => 'all'];
        $specificities['select_items_options_2'] = [
            'condition' => [
                'is_usergroup' => 1,
            ] + getEntitiesRestrictCriteria(Group::getTable(), '', '', true),
        ];

        // Define normalized action for add_item and remove_item
        $specificities['normalized']['add'][]    = 'add_supervisor';
        $specificities['normalized']['add'][]    = 'add_delegatee';

        $specificities['button_labels']['add_supervisor'] = $specificities['button_labels']['add'];
        $specificities['button_labels']['add_delegatee']  = $specificities['button_labels']['add'];

        $specificities['update_if_different'] = true;

        return $specificities;
    }

    public static function getRelationInputForProcessingOfMassiveActions(
        $action,
        CommonDBTM $item,
        array $ids,
        array $input
    ) {
        return match ($action) {
            'add_supervisor' => ['is_manager' => 1],
            'add_delegatee' => ['is_userdelegate' => 1],
            default => [],
        };
    }

    /**
     * Get search function for the class
     *
     * @return array of search option
     **/
    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics'),
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => static::getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number',
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => static::getTable(),
            'field'              => 'is_dynamic',
            'name'               => __('Dynamic'),
            'datatype'           => 'bool',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => 'glpi_groups',
            'field'              => 'completename',
            'name'               => Group::getTypeName(1),
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'name'               => User::getTypeName(1),
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
            'right'              => 'all',
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => static::getTable(),
            'field'              => 'is_manager',
            'name'               => _n('Manager', 'Managers', 1),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => static::getTable(),
            'field'              => 'is_userdelegate',
            'name'               => __('Delegatee'),
            'datatype'           => 'bool',
        ];

        return $tab;
    }

    public static function rawSearchOptionsToAdd($itemtype = null)
    {
        $tab = [];
        $name = _n('User', 'Users', Session::getPluralNumber());

        $tab[] = [
            'id'                 => 'user',
            'name'               => $name,
        ];

        $tab[] = [
            'id'                 => '150',
            'table'              => 'glpi_groups_users',
            'field'              => 'id',
            'name'               => _x('quantity', 'Number of users'),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'datatype'           => 'count',
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child',
            ],
        ];

        return $tab;
    }

    /**
     * @param $user_ID
     * @param $only_dynamic (false by default
     **/
    public static function deleteGroups($user_ID, $only_dynamic = false)
    {
        $crit['users_id'] = $user_ID;
        if ($only_dynamic) {
            $crit['is_dynamic'] = '1';
        }
        $obj = new self();
        $obj->deleteByCriteria($crit);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$withtemplate) {
            $nb = 0;
            switch ($item::class) {
                case User::class:
                    if (Group::canView()) {
                        if ($_SESSION['glpishow_count_on_tabs']) {
                            $nb = self::countForItem($item);
                        }
                        return self::createTabEntry(Group::getTypeName(Session::getPluralNumber()), $nb, $item::class);
                    }
                    break;

                case Group::class:
                    if (User::canView()) {
                        if ($_SESSION['glpishow_count_on_tabs']) {
                            $nb = self::countForItem($item);
                        }
                        return self::createTabEntry(User::getTypeName(Session::getPluralNumber()), $nb, $item::class);
                    }
                    break;
            }
        }
        return '';
    }

    public static function countForItem(CommonGLPI $item)
    {
        if ($item instanceof Group) {
            $members = [];
            $ids = [];
            self::getDataForGroup($item, $members, $ids, '', true, false);

            // We will also count implicits members from parents groups
            $members = array_merge(
                $members,
                self::getParentsMembers($item, '')
            );

            //TODO The results from this don't seem correct
            //$members = self::clearDuplicatedGroupData($members);

            return count($members);
        }

        if ($item instanceof User) {
            return parent::countForItem($item);
        }

        return 0;
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        switch ($item::class) {
            case User::class:
                self::showForUser($item);
                break;

            case Group::class:
                self::showForGroup($item);
                break;
        }
        return true;
    }

    /**
     * Get linked items list for specified item
     *
     * @since 9.3.1
     *
     * @param CommonDBTM $item  Item instance
     * @param boolean    $noent Flag to not compute entity information (see Document_Item::getListForItemParams)
     *
     * @return array
     */
    protected static function getListForItemParams(CommonDBTM $item, $noent = false)
    {
        $params = parent::getListForItemParams($item, $noent);
        $params['SELECT'][] = self::getTable() . '.is_manager';
        $params['SELECT'][] = self::getTable() . '.is_userdelegate';
        return $params;
    }

    public function post_addItem()
    {
        global $DB;

        parent::post_addItem();

        // add new user to plannings
        $groups_id  = $this->fields['groups_id'];
        $planning_k = 'group_' . $groups_id . '_users';

        // find users with the current group in their plannings
        $user_inst = new User();
        $users = $user_inst->find([
            'plannings' => ['LIKE', "%$planning_k%"],
        ]);

        // add the new user to found plannings
        $query = $DB->buildUpdate(
            User::getTable(),
            [
                'plannings' => new QueryParam(),
            ],
            [
                'id'        => new QueryParam(),
            ]
        );
        $stmt = $DB->prepare($query);
        $DB->beginTransaction();

        foreach ($users as $user) {
            $users_id  = $user['id'];
            $plannings = importArrayFromDB($user['plannings']);
            $nb_users  = count($plannings['plannings'][$planning_k]['users']);

            // add the planning for the user
            $plannings['plannings'][$planning_k]['users']['user_' . $this->fields['users_id']] = [
                'color'   => Planning::getPaletteColor('bg', $nb_users),
                'display' => true,
                'type'    => 'user',
            ];

            // if current user logged, append also to its session
            if ($users_id === Session::getLoginUserID()) {
                $_SESSION['glpi_plannings'] = $plannings;
            }

            // save the planning completed to db
            $json_plannings = exportArrayToDB($plannings);
            $stmt->bind_param('si', $json_plannings, $users_id);
            $DB->executeStatement($stmt);
        }

        $DB->commit();
        $stmt->close();

        // Group cache must be invalidated when a user is added to a group
        Group::updateLastGroupChange();
    }

    public function post_purgeItem()
    {
        global $DB;

        parent::post_purgeItem();

        if (Session::getLoginUserID() !== false) {
            Event::log(
                $this->fields['groups_id'],
                "groups",
                4,
                "setup",
                sprintf(__('%s deletes users from a group'), $_SESSION["glpiname"])
            );
        }

        // remove user from plannings
        $groups_id  = $this->fields['groups_id'];
        $users_id = $this->fields['users_id'];

        // find users with the current group in their plannings
        $user_inst = new User();

        // If user's default group is affected, remove it from user
        if ($user_inst->getFromDB($users_id) && $user_inst->fields['groups_id'] == $groups_id) {
            $user_inst->update(
                [
                    'id'        => $users_id,
                    'groups_id' => 0,
                ]
            );
        }

        // remove user from plannings
        $planning_k = 'group_' . $groups_id . '_users';
        $users = $user_inst->find([
            'plannings' => ['LIKE', "%$planning_k%"],
        ]);

        // remove the deleted user to found plannings
        $query = $DB->buildUpdate(
            User::getTable(),
            [
                'plannings' => new QueryParam(),
            ],
            [
                'id'        => new QueryParam(),
            ]
        );
        $stmt = $DB->prepare($query);
        $DB->beginTransaction();
        foreach ($users as $user) {
            $users_id  = $user['id'];
            $plannings = importArrayFromDB($user['plannings']);

            // delete planning for the user
            unset($plannings['plannings'][$planning_k]['users']['user_' . $this->fields['users_id']]);

            // if current user logged, append also to its session
            if ($users_id === Session::getLoginUserID()) {
                $_SESSION['glpi_plannings'] = $plannings;
            }

            // save the planning completed to db
            $json_plannings = exportArrayToDB($plannings);
            $stmt->bind_param('si', $json_plannings, $users_id);
            $DB->executeStatement($stmt);
        }

        $DB->commit();
        $stmt->close();

        // Group cache must be invalidated when a user is remove from a group
        Group::updateLastGroupChange();
    }

    /**
     * Get parents members for a given group
     *
     * @param Group $group
     * @param mixed $crit
     *
     * @return array Array of array, which will contain the keys set in
     *               self::getDataForGroup ('id', 'linkid', 'groups_id',
     *               'is_dynamic', 'is_manager' and 'is_userdelegate')
     */
    protected static function getParentsMembers(Group $group, $crit): array
    {
        // No more parents, end recursion
        if (!$group->fields['groups_id']) {
            return [];
        }

        // Load parent
        $parent = Group::getById($group->fields['groups_id']);

        // Parent doesn't support recursive membership, end recursion
        if (!$parent->fields['recursive_membership']) {
            return [];
        }

        // Get parents members
        $members = [];
        $ids = [];
        self::getDataForGroup($parent, $members, $ids, $crit);

        return array_merge($members, self::getParentsMembers($parent, $crit));
    }

    /**
     * When computer members from a group, some users may be counted as members
     * multiple times if they are part of one or more parents groups that
     * support recursion
     *
     * @param array $data
     *
     * return @array
     */
    protected static function clearDuplicatedGroupData(array $data): array
    {
        $user_ids = [];

        return array_filter($data, static function ($user_data) use (&$user_ids) {
            if (!isset($user_ids[$user_data['id']])) {
                $user_ids[$user_data['id']] = true;
                return true;
            }
            return false;
        });
    }
}
