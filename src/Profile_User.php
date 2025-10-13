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
use Glpi\DBAL\QuerySubQuery;

/**
 * Profile_User Class
 **/
class Profile_User extends CommonDBRelation
{
    // From CommonDBTM
    public $auto_message_on_action               = false;

    // From CommonDBRelation
    public static $itemtype_1                    = 'User';
    public static $items_id_1                    = 'users_id';

    public static $itemtype_2                    = 'Profile';
    public static $items_id_2                    = 'profiles_id';
    public static $checkItem_2_Rights            = self::DONT_CHECK_ITEM_RIGHTS;

    // Manage Entity properties forwarding
    public static $disableAutoEntityForwarding   = true;

    /**
     * @since 0.84
     *
     * @see CommonDBTM::getForbiddenStandardMassiveAction()
     **/
    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }


    public function maybeRecursive()
    {
        // Store is_recursive fields but not really recursive object
        return false;
    }


    // TODO CommonDBConnexity : check in details if we can replace canCreateItem by canRelationItem ...
    public function canCreateItem(): bool
    {

        $user = new User();
        return $user->can($this->fields['users_id'], READ)
             && Profile::currentUserHaveMoreRightThan([$this->fields['profiles_id']
                                                               => $this->fields['profiles_id'],
             ])
             && Session::haveAccessToEntity($this->fields['entities_id']);
    }

    public function canPurgeItem(): bool
    {
        // We can't delete the last super admin profile authorization
        if ($this->isLastSuperAdminAuthorization()) {
            return false;
        }

        return true;
    }

    public function prepareInputForAdd($input)
    {
        // TODO: check if the entities should not be inherited from the profile or the user
        $valid_entity = isset($input['entities_id']) && $input['entities_id'] >= 0;
        $valid_profile = isset($input['profiles_id']) && $input['profiles_id'] > 0;
        $valid_user = isset($input['users_id']) && $input['users_id'] > 0;
        if (!$valid_entity || !$valid_user || !$valid_profile) {
            Session::addMessageAfterRedirect(
                __s('No selected element or badly defined operation'),
                false,
                ERROR
            );
            return false;
        }

        return parent::prepareInputForAdd($input);
    }

    /**
     * Show rights of a user
     *
     * @param User $user object
     **/
    public static function showForUser(User $user)
    {
        $ID = $user->getField('id');
        if (!$user->can($ID, READ)) {
            return false;
        }

        $canedit = $user->canEdit($ID);

        $strict_entities = self::getUserEntities($ID, false);
        if (!Session::haveAccessToOneOfEntities($strict_entities) && !Session::canViewAllEntities()) {
            $canedit = false;
        }

        $canshowentity = Entity::canView();

        if ($canedit) {
            TemplateRenderer::getInstance()->display('pages/admin/add_profile_authorization.html.twig', [
                'source_itemtype' => User::class,
                'source_items_id' => $ID,
            ]);
        }

        $start       = (int) ($_GET["start"] ?? 0);
        $limit       = $_SESSION["glpilist_limit"];
        $sort        = $_GET["sort"] ?? "";
        $order       = strtoupper($_GET["order"] ?? "");
        $sort_params = [];
        if ($sort !== '') {
            $sort_params = [$sort => $order === 'DESC' ? 'DESC' : 'ASC'];
        }
        $iterator = self::getListForItem($user, $start, $limit, $sort_params);
        $total_num = self::countForItem($user);

        $entries = [];
        foreach ($iterator as $data) {
            $entry = [
                'itemtype' => self::class,
                'id'       => $data['linkid'],
            ];
            $link = $data["completename"];
            if ($_SESSION["glpiis_ids_visible"]) {
                $link = sprintf(__('%1$s (%2$s)'), $link, $data["entities_id"]);
            }
            if ($canshowentity) {
                $link = sprintf(
                    '<a href="%s">%s</a>',
                    htmlescape(Entity::getFormURLWithID($data["entities_id"])),
                    htmlescape($link)
                );
            }
            $entry['entity'] = $link;

            if (Profile::canView()) {
                $profile_name = sprintf(
                    '<a href="%s">%s</a>',
                    htmlescape(Profile::getFormURLWithID($data['id'])),
                    htmlescape($data['name'])
                );
            } else {
                $profile_name = htmlescape($data['name']);
            }

            if ($data['is_dynamic'] || $data['is_recursive']) {
                $profile_name = sprintf(__s('%1$s %2$s'), $profile_name, "<span class='b'>(");
                if ($data['is_dynamic']) {
                    $profile_name = sprintf(__s('%1$s%2$s'), $profile_name, __s('D'));
                }
                if ($data['is_dynamic'] && $data['is_recursive']) {
                    $profile_name = sprintf(__s('%1$s%2$s'), $profile_name, ", ");
                }
                if ($data['is_recursive']) {
                    $profile_name = sprintf(__s('%1$s%2$s'), $profile_name, __s('R'));
                }
                $profile_name = sprintf(__s('%1$s%2$s'), $profile_name, ")</span>");
            }
            $entry['profile'] = $profile_name;
            $entries[] = $entry;
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'start' => $start,
            'limit' => $limit,
            'sort' => $sort,
            'order' => $order,
            'is_tab' => true,
            'nofilter' => true,
            'columns' => [
                'entity' => Entity::getTypeName(Session::getPluralNumber()),
                'profile' => sprintf(
                    __('%1$s (%2$s)'),
                    self::getTypeName(Session::getPluralNumber()),
                    __('D=Dynamic, R=Recursive')
                ),
            ],
            'formatters' => [
                'entity' => 'raw_html',
                'profile' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => $total_num,
            'filtered_number' => $total_num,
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed'    => min($_SESSION['glpilist_limit'], count($entries)),
                'container'        => 'mass' . self::class . mt_rand(),
                'specific_actions' => ['purge' => _x('button', 'Delete permanently')],
            ],
        ]);
    }


    /**
     * Show users of an entity
     *
     * @param Entity $entity object
     **/
    public static function showForEntity(Entity $entity)
    {
        global $DB;

        $ID = $entity->getField('id');
        if (!$entity->can($ID, READ)) {
            return false;
        }

        $canedit     = $entity->canEdit($ID);
        $rand        = mt_rand();

        if ($canedit) {
            TemplateRenderer::getInstance()->display('pages/admin/add_profile_authorization.html.twig', [
                'source_itemtype' => Entity::class,
                'source_items_id' => $ID,
                'used_users'      => [],
            ]);
        }

        $putable = Profile_User::getTable();
        $ptable = Profile::getTable();
        $utable = User::getTable();
        $start       = (int) ($_GET["start"] ?? 0);
        $limit       = $_SESSION["glpilist_limit"];
        $sort        = $_GET["sort"] ?? "";
        $order       = strtoupper($_GET["order"] ?? "");
        $sort_params = [];
        $filters = $_GET['filters'] ?? [];

        if ($sort === 'name') {
            $sort_params = [
                "$utable.name $order",
                "$utable.realname $order",
                "$utable.firstname $order",
            ];
        } elseif ($sort === 'profile') {
            $sort_params = ["$ptable.name $order"];
        } elseif ($sort !== '') {
            $sort_params = [$sort . ' ' . ($order === 'DESC' ? 'DESC' : 'ASC')];
        }
        if ($sort_params === []) {
            $sort_params = [
                "$utable.name ASC",
                "$utable.realname ASC",
                "$utable.firstname ASC",
            ];
        }

        $filter_conditions = [];
        foreach ($filters as $k => $v) {
            if ($k === 'name') {
                $filter_conditions[] = [
                    'OR' => [
                        "$utable.name" => ['LIKE', "%$v%"],
                        "$utable.realname" => ['LIKE', "%$v%"],
                        "$utable.firstname" => ['LIKE', "%$v%"],
                    ],
                ];
            } elseif ($k === 'profile') {
                $filter_conditions[] = [
                    "$ptable.name" => ['LIKE', "%$v%"],
                ];
            }
        }

        $criteria = [
            'SELECT'       => [
                "glpi_users" => ['id', 'name', 'realname', 'firstname', 'picture'],
                "$putable.id AS linkid",
                "$putable.is_recursive",
                "$putable.is_dynamic",
                "$ptable.id AS pid",
                "$ptable.name AS pname",
            ],
            'FROM'         => $putable,
            'INNER JOIN'   => [
                $utable => [
                    'ON' => [
                        $putable => 'users_id',
                        $utable  => 'id',
                    ],
                ],
                $ptable  => [
                    'ON' => [
                        $putable => 'profiles_id',
                        $ptable  => 'id',
                    ],
                ],
            ],
            'WHERE'        => [
                "$utable.is_deleted"    => 0,
                "$putable.entities_id"  => $ID,
            ],
            'ORDER'      => $sort_params,
            'START'      => $start,
            'LIMIT'      => $limit,
        ];
        if (count($filter_conditions)) {
            $criteria['WHERE'] += $filter_conditions;
        }
        $iterator = $DB->request($criteria);
        $nb = count($iterator);

        $count_criteria = $criteria;
        unset($count_criteria['START'], $count_criteria['LIMIT'], $count_criteria['SELECT']);
        $count_criteria['COUNT'] = 'cpt';
        $total_count = $DB->request($count_criteria)->current()['cpt'];

        $entries = [];
        foreach ($iterator as $data) {
            $username = formatUserLink(
                $data["id"],
                $data["name"],
                $data["realname"],
                $data["firstname"],
            );
            if ($data["is_dynamic"] || $data["is_recursive"]) {
                $username = sprintf(__s('%1$s %2$s'), $username, "<span class='b'>(");
                if ($data["is_dynamic"]) {
                    $username = sprintf(__s('%1$s%2$s'), $username, __s('D'));
                }
                if ($data["is_dynamic"] && $data["is_recursive"]) {
                    $username = sprintf(__s('%1$s%2$s'), $username, ", ");
                }
                if ($data["is_recursive"]) {
                    $username = sprintf(__s('%1$s%2$s'), $username, __s('R'));
                }
                $username = sprintf(__s('%1$s%2$s'), $username, ")</span>");
            }
            $initials = User::getInitialsForUserName($data['name'], $data['firstname'] ?? '', $data['realname'] ?? '');
            $avatar_params = [
                'picture' => User::getThumbnailURLForPicture($data['picture'] ?? ''),
                'initials' => $initials,
                'initials_bg' => Toolbox::getColorForString($initials),
            ];
            $username = TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                {% set bg_color = picture is not empty ? 'inherit' : initials_bg %}
                <span class="avatar avatar-md me-2"
                    style="{% if picture is not null %} background-image: url({{ picture }}); {% endif %} background-color: {{ bg_color }}">
                    {% if picture is empty %}
                        {{ initials }}
                    {% endif %}
                </span>
TWIG, $avatar_params) . $username;

            $entries[] = [
                'itemtype' => self::class,
                'id' => $data['id'],
                'name' => $username,
                'profile' => $data['pname'],
            ];
        }

        $super_header = sprintf(
            __('%1$s (%2$s)'),
            User::getTypeName(Session::getPluralNumber()),
            __('D=Dynamic, R=Recursive')
        );
        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'start' => $start,
            'limit' => $limit,
            'sort' => $sort,
            'order' => $order,
            'is_tab' => true,
            'filters' => $filters,
            'super_header' => $super_header,
            'columns' => [
                'name' => __('Name'),
                'profile' => Profile::getTypeName(1),
            ],
            'formatters' => [
                'name' => 'raw_html',
            ],
            'total_number' => $total_count,
            'filtered_number' => $total_count,
            'entries' => $entries,
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed'    => min($_SESSION['glpilist_limit'], $nb),
                'container'        => 'mass' . self::class . $rand,
                'specific_actions' => ['purge' => _x('button', 'Delete permanently')],
            ],
        ]);
    }

    /**
     * Show the User having a profile, in allowed Entity
     *
     * @param Profile $prof object
     **/
    public static function showForProfile(Profile $prof)
    {
        global $DB;

        $ID      = $prof->fields['id'];
        $canedit = Session::haveRightsOr("user", [CREATE, UPDATE, DELETE, PURGE]);
        $rand = mt_rand();
        if (!$prof->can($ID, READ)) {
            return false;
        }

        $start       = (int) ($_GET["start"] ?? 0);
        $limit       = $_SESSION["glpilist_limit"];
        $sort        = $_GET["sort"] ?? "";
        $order       = strtoupper($_GET["order"] ?? "");
        $sort_params = [];
        $filters = $_GET['filters'] ?? [];
        $utable = User::getTable();
        $putable = Profile_User::getTable();
        $etable = Entity::getTable();

        if ($sort === 'name') {
            $sort_params = [
                "$utable.name $order",
                "$utable.realname $order",
                "$utable.firstname $order",
            ];
        } elseif ($sort === 'entity') {
            $sort_params = ["$etable.completename $order"];
        } elseif ($sort !== '') {
            $sort_params = [$sort . ' ' . ($order === 'DESC' ? 'DESC' : 'ASC')];
        }
        if ($sort_params === []) {
            $sort_params = ["$etable.completename ASC"];
        }

        $filter_conditions = [];
        foreach ($filters as $k => $v) {
            if ($k === 'name') {
                $filter_conditions[] = [
                    'OR' => [
                        "$utable.name" => ['LIKE', "%$v%"],
                        "$utable.realname" => ['LIKE', "%$v%"],
                        "$utable.firstname" => ['LIKE', "%$v%"],
                    ],
                ];
            } elseif ($k === 'entity') {
                $filter_conditions[] = [
                    "$etable.completename" => ['LIKE', "%$v%"],
                ];
            }
        }

        $criteria = [
            'SELECT'          => [
                $utable => ['id', 'name', 'realname', 'firstname', 'picture'],
                "$putable.entities_id AS entity",
                "$putable.id AS linkid",
                "$putable.is_dynamic",
                "$putable.is_recursive",
            ],
            'DISTINCT'        => true,
            'FROM'            => $putable,
            'LEFT JOIN'       => [
                $etable  => [
                    'ON' => [
                        $putable => 'entities_id',
                        $etable  => 'id',
                    ],
                ],
                $utable  => [
                    'ON' => [
                        $putable => 'users_id',
                        $utable  => 'id',
                    ],
                ],
            ],
            'WHERE'           => [
                "$putable.profiles_id"  => $ID,
                "$utable.is_deleted"    => 0,
            ] + getEntitiesRestrictCriteria($putable, 'entities_id', $_SESSION['glpiactiveentities'], true),
            'ORDER'         => $sort_params,
            'START'         => $start,
            'LIMIT'         => $limit,
        ];
        if (count($filter_conditions)) {
            $criteria['WHERE'] += $filter_conditions;
        }
        $iterator = $DB->request($criteria);

        $nb = count($iterator);

        $count_criteria = $criteria;
        unset($count_criteria['START'], $count_criteria['LIMIT'], $count_criteria['SELECT'], $count_criteria['DISTINCT']);
        $count_criteria['COUNT'] = 'cpt';
        $total_count = $DB->request($count_criteria)->current()['cpt'];

        $entries = [];
        $entity_names = [];
        $used_users = [];
        foreach ($iterator as $data) {
            $used_users[] = $data['id'];
            if (!isset($entity_names[$data['entity']])) {
                $entity_names[$data['entity']] = Dropdown::getDropdownName('glpi_entities', $data['entity']);
            }
            $username = formatUserLink(
                $data["id"],
                $data["name"],
                $data["realname"],
                $data["firstname"],
            );
            if ($data["is_dynamic"] || $data["is_recursive"]) {
                $username = sprintf(__s('%1$s %2$s'), $username, "<span class='b'>(");
                if ($data["is_dynamic"]) {
                    $username = sprintf(__s('%1$s%2$s'), $username, __s('D'));
                }
                if ($data["is_dynamic"] && $data["is_recursive"]) {
                    $username = sprintf(__s('%1$s%2$s'), $username, ", ");
                }
                if ($data["is_recursive"]) {
                    $username = sprintf(__s('%1$s%2$s'), $username, __s('R'));
                }
                $username = sprintf(__s('%1$s%2$s'), $username, ")</span>");
            }
            $initials = User::getInitialsForUserName($data['name'], $data['firstname'] ?? '', $data['realname'] ?? '');
            $avatar_params = [
                'picture' => User::getThumbnailURLForPicture($data['picture'] ?? ''),
                'initials' => $initials,
                'initials_bg' => Toolbox::getColorForString($initials),
            ];
            $username = TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                {% set bg_color = picture is not empty ? 'inherit' : initials_bg %}
                <span class="avatar avatar-md me-2"
                    style="{% if picture is not null %} background-image: url({{ picture }}); {% endif %} background-color: {{ bg_color }}">
                    {% if picture is empty %}
                        {{ initials }}
                    {% endif %}
                </span>
TWIG, $avatar_params) . $username;
            $entries[] = [
                'itemtype' => self::class,
                'id' => $data['id'],
                'name' => $username,
                'entity' => $entity_names[$data['entity']],
            ];
        }

        if ($canedit) {
            TemplateRenderer::getInstance()->display('pages/admin/add_profile_authorization.html.twig', [
                'source_itemtype' => Profile::class,
                'source_items_id' => $ID,
                'used_users'      => $used_users,
            ]);
        }

        $super_header = sprintf(
            __('%1$s (%2$s)'),
            User::getTypeName(Session::getPluralNumber()),
            __('D=Dynamic, R=Recursive')
        );
        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'start' => $start,
            'limit' => $limit,
            'sort' => $sort,
            'order' => $order,
            'is_tab' => true,
            'filters' => $filters,
            'super_header' => $super_header,
            'columns' => [
                'name' => __('Name'),
                'entity' => Entity::getTypeName(1),
            ],
            'formatters' => [
                'name' => 'raw_html',
            ],
            'total_number' => $total_count,
            'filtered_number' => $total_count,
            'entries' => $entries,
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed'    => min($_SESSION['glpilist_limit'], $nb),
                'container'        => 'mass' . self::class . $rand,
                'specific_actions' => ['purge' => _x('button', 'Delete permanently')],
            ],
        ]);
    }


    /**
     * Get entities for which a user have a right
     *
     * @param $user_ID         user ID
     * @param $is_recursive    check also using recursive rights (true by default)
     * @param $default_first   user default entity first (false by default)
     *
     * @return array of entities ID
     **/
    public static function getUserEntities($user_ID, $is_recursive = true, $default_first = false)
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT'          => [
                'entities_id',
                'is_recursive',
            ],
            'DISTINCT'        => true,
            'FROM'            => 'glpi_profiles_users',
            'WHERE'           => ['users_id' => (int) $user_ID],
        ]);
        $entities = [];

        foreach ($iterator as $data) {
            if ($data['is_recursive'] && $is_recursive) {
                $tab      = getSonsOf('glpi_entities', $data['entities_id']);
                $entities = array_merge($tab, $entities);
            } else {
                $entities[] = $data['entities_id'];
            }
        }

        // Set default user entity at the beginning
        if ($default_first) {
            $user = new User();
            if ($user->getFromDB((int) $user_ID)) {
                $ent = $user->getField('entities_id');
                if (in_array($ent, $entities)) {
                    array_unshift($entities, $ent);
                }
            }
        }

        return array_unique($entities);
    }


    /**
     * Get entities for which a user have a right
     *
     * @since 0.84
     * @since 9.2  Add $rightname parameter
     *
     * @param integer $user_ID      user ID
     * @param string  $rightname    name of the rights to check (CommonDBTM::$rightname)
     * @param integer $rights       rights to check (may be a OR combinaison of several rights)
     *                              (exp: CommonDBTM::READ | CommonDBTM::UPDATE ...)
     * @param boolean $is_recursive check also using recursive rights (true by default)
     *
     * @return array of entities ID
     **/
    public static function getUserEntitiesForRight($user_ID, $rightname, $rights, $is_recursive = true)
    {
        global $DB;

        $putable = Profile_User::getTable();
        $ptable = Profile::getTable();
        $prtable = ProfileRight::getTable();
        $iterator = $DB->request([
            'SELECT'          => [
                "$putable.entities_id",
                "$putable.is_recursive",
            ],
            'DISTINCT'        => true,
            'FROM'            => $putable,
            'INNER JOIN'      => [
                $ptable  => [
                    'ON' => [
                        $putable => 'profiles_id',
                        $ptable  => 'id',
                    ],
                ],
                $prtable => [
                    'ON' => [
                        $prtable => 'profiles_id',
                        $ptable  => 'id',
                    ],
                ],
            ],
            'WHERE'           => [
                "$putable.users_id"  => (int) $user_ID,
                "$prtable.name"      => $rightname,
                "$prtable.rights"    => ['&', $rights],
            ],
        ]);

        if (count($iterator) > 0) {
            $entities = [];

            foreach ($iterator as $data) {
                if ($data['is_recursive'] && $is_recursive) {
                    $tab      = getSonsOf('glpi_entities', $data['entities_id']);
                    $entities = array_merge($tab, $entities);
                } else {
                    $entities[] = $data['entities_id'];
                }
            }

            return array_unique($entities);
        }

        return [];
    }


    /**
     * Get user profiles (no entity association, use sqlfilter if needed)
     *
     * @since 9.3 can pass sqlfilter as a parameter
     *
     * @param int $user_ID      User ID
     * @param array $sqlfilter  Additional filter (default [])
     *
     * @return array of the IDs of the profiles
     **/
    public static function getUserProfiles($user_ID, $sqlfilter = [])
    {
        global $DB;

        $profiles = [];

        $where = ['users_id' => (int) $user_ID];
        if (count($sqlfilter) > 0) {
            $where += $sqlfilter;
        }

        $iterator = $DB->request([
            'SELECT'          => 'profiles_id',
            'DISTINCT'        => true,
            'FROM'            => 'glpi_profiles_users',
            'WHERE'           => $where,
        ]);

        foreach ($iterator as $data) {
            $profiles[$data['profiles_id']] = $data['profiles_id'];
        }

        return $profiles;
    }


    /**
     * retrieve the entities allowed to a user for a profile
     *
     * @param $users_id     Integer  ID of the user
     * @param $profiles_id  Integer  ID of the profile
     * @param $child        Boolean  when true, include child entity when recursive right
     *                               (false by default)
     *
     * @return Array of entity ID
     **/
    public static function getEntitiesForProfileByUser($users_id, $profiles_id, $child = false)
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => ['entities_id', 'is_recursive'],
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'users_id'     => (int) $users_id,
                'profiles_id'  => (int) $profiles_id,
            ],
        ]);

        $entities = [];
        foreach ($iterator as $data) {
            if (
                $child
                && $data['is_recursive']
            ) {
                foreach (getSonsOf('glpi_entities', $data['entities_id']) as $id) {
                    $entities[$id] = $id;
                }
            } else {
                $entities[$data['entities_id']] = $data['entities_id'];
            }
        }
        return $entities;
    }


    /**
     * retrieve the entities associated to a user
     *
     * @param $users_id     Integer  ID of the user
     * @param $child        Boolean  when true, include child entity when recursive right
     *                               (false by default)
     *
     * @since 0.85
     *
     * @return Array of entity ID
     **/
    public static function getEntitiesForUser($users_id, $child = false)
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => ['entities_id', 'is_recursive'],
            'FROM'   => 'glpi_profiles_users',
            'WHERE'  => ['users_id' => (int) $users_id],
        ]);

        $entities = [];
        foreach ($iterator as $data) {
            if (
                $child
                && $data['is_recursive']
            ) {
                foreach (getSonsOf('glpi_entities', $data['entities_id']) as $id) {
                    $entities[$id] = $id;
                }
            } else {
                $entities[$data['entities_id']] = $data['entities_id'];
            }
        }
        return $entities;
    }


    /**
     * Get entities for which a user have a right
     *
     * @param int  $user_ID      user ID
     * @param bool $only_dynamic get only recursive rights (false by default)
     *
     * @return array of entities ID
     **/
    public static function getForUser($user_ID, $only_dynamic = false)
    {
        $condition = ['users_id' => (int) $user_ID];

        if ($only_dynamic) {
            $condition['is_dynamic'] = 1;
        }

        return getAllDataFromTable('glpi_profiles_users', $condition);
    }


    /**
     * @param $user_ID
     * @param $profile_id
     **/
    public static function haveUniqueRight($user_ID, $profile_id)
    {
        global $DB;

        $result = $DB->request([
            'COUNT'  => 'cpt',
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'users_id'     => (int) $user_ID,
                'profiles_id'  => (int) $profile_id,
            ],
        ])->current();
        return $result['cpt'];
    }


    /**
     * @param $user_ID
     * @param $only_dynamic    (false by default)
     **/
    public static function deleteRights($user_ID, $only_dynamic = false)
    {

        $crit = [
            'users_id' => (int) $user_ID,
        ];

        if ($only_dynamic) {
            $crit['is_dynamic'] = '1';
        }

        $obj = new self();
        $obj->deleteByCriteria($crit);
    }


    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics'),
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number',
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'is_dynamic',
            'name'               => __('Dynamic'),
            'datatype'           => 'bool',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => 'glpi_profiles',
            'field'              => 'name',
            'name'               => self::getTypeName(1),
            'datatype'           => 'dropdown',
            'massiveaction'      => false,
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
            'id'                 => '80',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'massiveaction'      => true,
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '86',
            'table'              => $this->getTable(),
            'field'              => 'is_recursive',
            'name'               => __('Child entities'),
            'datatype'           => 'bool',
            'massiveaction'      => false,
        ];

        return $tab;
    }


    public static function getTypeName($nb = 0)
    {
        return _n('Profile', 'Profiles', $nb);
    }

    protected function computeFriendlyName()
    {

        $name = sprintf(
            __('%1$s, %2$s'),
            Dropdown::getDropdownName('glpi_profiles', $this->fields['profiles_id']),
            Dropdown::getDropdownName('glpi_entities', $this->fields['entities_id'])
        );

        if (isset($this->fields['is_dynamic']) && $this->fields['is_dynamic']) {
            //TRANS: D for Dynamic
            $dyn  = __('D');
            $name = sprintf(__('%1$s, %2$s'), $name, $dyn);
        }
        if (isset($this->fields['is_recursive']) && $this->fields['is_recursive']) {
            //TRANS: R for Recursive
            $rec  = __('R');
            $name = sprintf(__('%1$s, %2$s'), $name, $rec);
        }
        return $name;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        global $DB;

        if (!$withtemplate) {
            $nb = 0;
            switch (get_class($item)) {
                case Entity::class:
                    if (Session::haveRight('user', READ)) {
                        if ($_SESSION['glpishow_count_on_tabs']) {
                            $count = $DB->request([
                                'COUNT'     => 'cpt',
                                'FROM'      => $this->getTable(),
                                'LEFT JOIN' => [
                                    User::getTable() => [
                                        'FKEY' => [
                                            $this->getTable() => 'users_id',
                                            User::getTable()  => 'id',
                                        ],
                                    ],
                                ],
                                'WHERE'     => [
                                    User::getTable() . '.is_deleted'    => 0,
                                    $this->getTable() . '.entities_id'  => $item->getID(),
                                ],
                            ])->current();
                            $nb        = $count['cpt'];
                        }
                        return self::createTabEntry(User::getTypeName(Session::getPluralNumber()), $nb, $item::getType(), User::getIcon());
                    }
                    break;

                case Profile::class:
                    if (Session::haveRight('user', READ)) {
                        if ($_SESSION['glpishow_count_on_tabs']) {
                            $nb = self::countForItem($item);
                        }
                        return self::createTabEntry(User::getTypeName(Session::getPluralNumber()), $nb, $item::getType());
                    }
                    break;

                case User::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = self::countForItem($item);
                    }
                    return self::createTabEntry(_n(
                        'Authorization',
                        'Authorizations',
                        Session::getPluralNumber()
                    ), $nb, $item::getType());
            }
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        switch (get_class($item)) {
            case Entity::class:
                self::showForEntity($item);
                break;

            case Profile::class:
                self::showForProfile($item);
                break;

            case User::class:
                self::showForUser($item);
                break;
        }
        return true;
    }


    /**
     * @since 0.85
     *
     * @see CommonDBRelation::getRelationMassiveActionsSpecificities()
     **/
    public static function getRelationMassiveActionsSpecificities()
    {
        $specificities                            = parent::getRelationMassiveActionsSpecificities();

        $specificities['dropdown_method_2']       = 'dropdownUnder';
        $specificities['can_remove_all_at_once']  = false;

        return $specificities;
    }


    /**
     * @since 0.85
     *
     * @see CommonDBRelation::showRelationMassiveActionsSubForm()
     **/
    public static function showRelationMassiveActionsSubForm(MassiveAction $ma, $peer_number)
    {

        if (
            ($ma->getAction() == 'add')
            && ($peer_number == 2)
        ) {
            echo "<br><br>" . htmlescape(sprintf(__('%1$s: %2$s'), Entity::getTypeName(1), ''));
            Entity::dropdown(['entity' => $_SESSION['glpiactiveentities']]);
            echo "<br><br>" . htmlescape(sprintf(__('%1$s: %2$s'), __('Recursive'), ''));
            Html::showCheckbox(['name' => 'is_recursive']);
        }
    }


    /**
     * @since 0.85
     *
     * @see CommonDBRelation::getRelationInputForProcessingOfMassiveActions()
     **/
    public static function getRelationInputForProcessingOfMassiveActions(
        $action,
        CommonDBTM $item,
        array $ids,
        array $input
    ) {
        $result = [];
        if (isset($input['entities_id'])) {
            $result['entities_id'] = $input['entities_id'];
        }
        if (isset($input['is_recursive'])) {
            $result['is_recursive'] = $input['is_recursive'];
        }

        return $result;
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
        $params['SELECT'][] = self::getTable() . '.entities_id';
        $params['SELECT'][] = self::getTable() . '.is_recursive';
        $params['SELECT'][] = 'glpi_entities.completename AS completename';
        $params['LEFT JOIN']['glpi_entities'] = [
            'FKEY'   => [
                self::getTable()  => 'entities_id',
                'glpi_entities'   => 'id',
            ],
        ];
        return $params;
    }

    /**
     * Check if this Profile_User is the last authorization of the last super-admin
     * profile (a "super-admin profile" is a profile that can edit other profiles)
     *
     * @return bool
     */
    protected function isLastSuperAdminAuthorization(): bool
    {
        $profile = Profile::getById($this->fields["profiles_id"]);

        if (!$profile instanceof Profile) {
            return false;
        }

        if (!$profile->isLastSuperAdminProfile()) {
            // Can't be the last super admin auth if not targeting the last
            // super admin profile
            return false;
        }

        // Find all active authorizations for the current profile (which is the last super admin profile)
        $super_admin_authorizations = $this->find([
            'profiles_id' => $this->fields['profiles_id'],
            'users_id' => new QuerySubQuery([
                'SELECT' => 'id',
                'FROM'   => 'glpi_users',
                'WHERE'  => ['is_active' => 1, 'is_deleted' => 0],
            ]),
        ]);
        $authorizations_ids = array_column($super_admin_authorizations, 'id');

        return
            count($authorizations_ids) == 1 // Only one super admin auth
            && $authorizations_ids[0] == $this->fields['id'] // Id match this auth
        ;
    }

    public function post_addItem()
    {
        $this->logOperation('add');
    }

    public function post_deleteFromDB()
    {
        $this->logOperation('delete');
    }

    /**
     * Log add/delete operation.
     * @param string $type
     */
    private function logOperation(string $type): void
    {
        if ((isset($this->input['_no_history']) && $this->input['_no_history'])) {
            return;
        }

        $profile_flags = [];
        if ((bool) $this->fields['is_dynamic']) {
            //TRANS: letter 'D' for Dynamic
            $profile_flags[] = __('D');
        }
        if ((bool) $this->fields['is_recursive']) {
            //TRANS: letter 'D' for Dynamic
            $profile_flags[] = __('R');
        }

        $user    = User::getById($this->fields['users_id']);
        $profile = Profile::getById($this->fields['profiles_id']);
        $entity  = Entity::getById($this->fields['entities_id']);

        $username    = $user->getNameID(['forceid' => true, 'complete' => 1]);
        $profilename = $profile->getNameID(['forceid' => true, 'complete' => 1]);
        $entityname  = $entity->getNameID(['forceid' => true, 'complete' => 1]);

        // Log on user
        if ($user->dohistory) {
            $log_entry = sprintf(__('%1$s, %2$s'), $entityname, $profilename);
            if (count($profile_flags) > 0) {
                $log_entry = sprintf(__('%s (%s)'), $log_entry, implode(', ', $profile_flags));
            }
            $changes = [
                '0',
                $type === 'delete' ? $log_entry : '',
                $type === 'add' ? $log_entry : '',
            ];
            Log::history(
                $user->getID(),
                $user->getType(),
                $changes,
                $profile->getType(),
                constant(sprintf('Log::HISTORY_%s_SUBITEM', strtoupper($type)))
            );
        }

        // Log on profile
        if ($profile->dohistory) {
            $log_entry = sprintf(__('%1$s, %2$s'), $username, $entityname);
            if (count($profile_flags) > 0) {
                $log_entry = sprintf(__('%s (%s)'), $log_entry, implode(', ', $profile_flags));
            }
            $changes = [
                '0',
                $type === 'delete' ? $log_entry : '',
                $type === 'add' ? $log_entry : '',
            ];
            Log::history(
                $profile->getID(),
                $profile->getType(),
                $changes,
                $user->getType(),
                constant(sprintf('Log::HISTORY_%s_SUBITEM', strtoupper($type)))
            );
        }

        // Log on entity
        if ($entity->dohistory) {
            $log_entry = sprintf(__('%1$s, %2$s'), $username, $profilename);
            if (count($profile_flags) > 0) {
                $log_entry = sprintf(__('%s (%s)'), $log_entry, implode(', ', $profile_flags));
            }
            $changes = [
                '0',
                $type === 'delete' ? $log_entry : '',
                $type === 'add' ? $log_entry : '',
            ];
            Log::history(
                $entity->getID(),
                $entity->getType(),
                $changes,
                $user->getType(),
                constant(sprintf('Log::HISTORY_%s_SUBITEM', strtoupper($type)))
            );
        }
    }
}
