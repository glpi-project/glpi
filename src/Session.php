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
use Glpi\Cache\CacheManager;
use Glpi\Cache\I18nCache;
use Glpi\Controller\InventoryController;
use Glpi\Event;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\SessionExpiredException;
use Glpi\Plugin\Hooks;
use Glpi\Session\SessionInfo;
use Laminas\I18n\Translator\Translator;
use Safe\Exceptions\InfoException;
use Safe\Exceptions\SessionException;
use Symfony\Component\HttpFoundation\Request;

use function Safe\ini_get;
use function Safe\preg_match;
use function Safe\scandir;
use function Safe\session_id;
use function Safe\session_regenerate_id;
use function Safe\session_save_path;
use function Safe\session_start;
use function Safe\session_unset;
use function Safe\session_write_close;
use function Safe\strtotime;

/**
 * Session Class
 * @phpstan-import-type RightDefinition from Profile
 **/
class Session
{
    // GLPI MODE
    public const NORMAL_MODE       = 0;
    public const TRANSLATION_MODE  = 1; // no more used
    public const DEBUG_MODE        = 2;

    /**
     * Max count of CSRF tokens to keep in session.
     * Prevents intensive use of forms from resulting in an excessively cumbersome session.
     */
    private const CSRF_MAX_TOKENS = 500;

    /**
     * Max count of IDOR tokens to keep in session.
     * Prevents intensive use of dropdowns from resulting in an excessively cumbersome session.
     */
    private const IDOR_MAX_TOKENS = 2500;

    /**
     * @var bool $bypass_right_checks
     * @internal
     */
    private static bool $bypass_right_checks = false;

    /**
     * Destroy the current session
     *
     * @return void
     **/
    public static function destroy()
    {

        self::start();
        // Unset all of the session variables.
        session_unset();
        // destroy may cause problems (no login / back to login page)
        $_SESSION = [];
        // write_close may cause troubles (no login / back to login page)
    }

    /**
     * Write and close session, but only if not in debug mode (allows proper use of the debug bar for AJAX calls).
     * @return void
     */
    public static function writeClose()
    {
        if ($_SESSION['glpi_use_mode'] !== self::DEBUG_MODE) {
            session_write_close();
        }
    }

    /**
     * Init session for the user is defined
     *
     * @param Auth $auth Auth object to init session
     *
     * @return void
     **/
    public static function init(Auth $auth)
    {
        global $CFG_GLPI;

        if ($auth->auth_succeded) {
            // Restart GLPI session : complete destroy to prevent lost datas
            $tosave = ['glpi_plugins', 'glpicookietest', 'phpCAS', 'glpicsrftokens',
                'glpiskipMaintenance',
            ];
            $save   = [];
            foreach ($tosave as $t) {
                if (isset($_SESSION[$t])) {
                    $save[$t] = $_SESSION[$t];
                }
            }
            self::destroy();
            if (!defined('TU_USER')) { //FIXME: no idea why this fails with phpunit... :(
                session_regenerate_id();
            }
            self::start();
            $_SESSION = $save;
            $_SESSION['valid_id'] = session_id();
            // Define default time :
            $_SESSION["glpi_currenttime"] = date("Y-m-d H:i:s");

            // Normal mode for this request
            $_SESSION["glpi_use_mode"] = self::NORMAL_MODE;
            // Check ID exists and load complete user from DB (plugins...)
            if (
                isset($auth->user->fields['id'])
                && $auth->user->getFromDB($auth->user->fields['id'])
            ) {
                if (
                    !$auth->user->fields['is_deleted']
                    && ($auth->user->fields['is_active']
                    && (($auth->user->fields['begin_date'] < $_SESSION["glpi_currenttime"])
                        || is_null($auth->user->fields['begin_date']))
                    && (($auth->user->fields['end_date'] > $_SESSION["glpi_currenttime"])
                        || is_null($auth->user->fields['end_date'])))
                ) {
                    $_SESSION["glpiID"]              = $auth->user->fields['id'];
                    $_SESSION["glpifriendlyname"]    = $auth->user->getFriendlyName();
                    $_SESSION["glpiname"]            = $auth->user->fields['name'];
                    $_SESSION["glpirealname"]        = $auth->user->fields['realname'];
                    $_SESSION["glpifirstname"]       = $auth->user->fields['firstname'];
                    $_SESSION["glpidefault_entity"]  = $auth->user->fields['entities_id'];
                    $_SESSION["glpiextauth"]         = $auth->extauth;
                    if (isset($_SESSION['phpCAS']['user'])) {
                        $_SESSION["glpiauthtype"]     = Auth::CAS;
                        $_SESSION["glpiextauth"]      = 0;
                    } else {
                        $_SESSION["glpiauthtype"]     = $auth->user->fields['authtype'];
                    }
                    $_SESSION["glpi_use_mode"]       = $auth->user->fields['use_mode'];
                    $_SESSION["glpi_plannings"]      = importArrayFromDB($auth->user->fields['plannings']);
                    $_SESSION["glpicrontimer"]       = time();
                    // Default tab
                    // $_SESSION['glpi_tab']=1;
                    $_SESSION['glpi_tabs']           = [];

                    $auth->user->computePreferences();
                    foreach ($CFG_GLPI['user_pref_field'] as $field) {
                        if (isset($auth->user->fields[$field])) {
                            $_SESSION["glpi$field"] = $auth->user->fields[$field];
                        }
                    }

                    if (isset($_SESSION['glpidefault_central_tab']) && $_SESSION['glpidefault_central_tab']) {
                        Session::setActiveTab("central", "Central$" . $_SESSION['glpidefault_central_tab']);
                    }
                    // Do it here : do not reset on each page, cause export issue
                    if ($_SESSION["glpilist_limit"] > $CFG_GLPI['list_limit_max']) {
                        $_SESSION["glpilist_limit"] = $CFG_GLPI['list_limit_max'];
                    }
                    // Init not set value for language
                    if (empty($_SESSION["glpilanguage"])) {
                        $_SESSION["glpilanguage"] = self::getPreferredLanguage();
                    }
                    $_SESSION['glpi_dropdowntranslations'] = DropdownTranslation::getAvailableTranslations($_SESSION["glpilanguage"]);

                    self::loadLanguage();

                    if ($auth->password_expired) {
                        // Make sure we are not in debug mode, as it could trigger some ajax request that would
                        // fail the session check (as we use a special partial session here without profiles) and thus
                        // destroy the session (which would make the "password expired" form impossible to submit as the
                        // csrf check would fail as the session data would be empty).
                        $_SESSION["glpi_use_mode"] = self::NORMAL_MODE;
                        $_SESSION['glpi_password_expired'] = 1;
                        // Do not init profiles, as user has to update its password to be able to use GLPI
                        return;
                    }

                    // glpiprofiles -> other available profile with link to the associated entities
                    Plugin::doHook(Hooks::INIT_SESSION);

                    self::initEntityProfiles(self::getLoginUserID());

                    // Use default profile if exist
                    if (isset($_SESSION['glpiprofiles'][$auth->user->fields['profiles_id']])) {
                        self::changeProfile($auth->user->fields['profiles_id']);
                    } else { // Else use first
                        self::changeProfile(key($_SESSION['glpiprofiles']));
                    }

                    if (!Session::getCurrentInterface()) {
                        $auth->auth_succeded = false;
                        $auth->addToError(__("You don't have right to connect"));
                    }
                } else {
                    $auth->auth_succeded = false;
                    $auth->addToError(__("You don't have access to this application because your account was deactivated or removed"));
                }
            } else {
                $auth->auth_succeded = false;
                $auth->addToError(__("You don't have right to connect"));
            }
        }
    }


    /**
     * Set the directory where are store the session file
     *
     * @return void
     **/
    public static function setPath()
    {

        if (
            ini_get("session.save_handler") == "files"
            && session_status() !== PHP_SESSION_ACTIVE
        ) {
            session_save_path(GLPI_SESSION_DIR);
        }
    }


    /**
     * Start the GLPI php session
     *
     * @return void
     **/
    public static function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        self::initVars();
    }

    /**
     * Initialize session variables.
     */
    public static function initVars(): void
    {
        // Define current time for sync of action timing
        $_SESSION["glpi_currenttime"] = date("Y-m-d H:i:s");

        // Define session default mode
        if (!isset($_SESSION['glpi_use_mode'])) {
            $_SESSION['glpi_use_mode'] = Session::NORMAL_MODE;
        }

        // Define default language
        if (!isset($_SESSION['glpilanguage'])) {
            $_SESSION['glpilanguage'] = Session::getPreferredLanguage();
        }

        // Init messages array
        if (!isset($_SESSION["MESSAGE_AFTER_REDIRECT"])) {
            $_SESSION["MESSAGE_AFTER_REDIRECT"] = [];
        }
    }


    /**
     * Get root entity name
     *
     * @since 0.84
     *
     * @return string
     **/
    public function getRootEntityName()
    {

        if (isset($_SESSION['glpirootentityname'])) {
            return $_SESSION['glpirootentityname'];
        }

        $entity = new Entity();
        if ($entity->getFromDB(0)) {
            $_SESSION['glpirootentityname'] = $entity->fields['name'];
        } else {
            $_SESSION['glpirootentityname'] = 'No root entity / DB troubles';
        }
        return $_SESSION['glpirootentityname'];
    }


    /**
     * Is GLPI used in multi-entities mode?
     *
     * @return boolean
     **/
    public static function isMultiEntitiesMode()
    {

        if (!isset($_SESSION['glpi_multientitiesmode'])) {
            if (countElementsInTable("glpi_entities") > 1) {
                $_SESSION['glpi_multientitiesmode'] = 1;
            } else {
                $_SESSION['glpi_multientitiesmode'] = 0;
            }
        }

        return $_SESSION['glpi_multientitiesmode'];
    }


    /**
     * Does user have right to see all entities?
     *
     * @since 9.3.2
     *
     * @return boolean
     **/
    public static function canViewAllEntities()
    {
        // Command line can see all entities
        return (isCommandLine()
              || ((countElementsInTable("glpi_entities")) == count($_SESSION["glpiactiveentities"] ?? [])));
    }


    /** Add an item to the navigate through search results list
     *
     * @param string  $itemtype Device type
     * @param integer $ID       ID of the item
     **/
    public static function addToNavigateListItems($itemtype, $ID)
    {
        $_SESSION['glpilistitems'][$itemtype][] = $ID;
    }


    /** Initialise a list of items to use navigate through search results
     *
     * @param string $itemtype Device type
     * @param string $title    List title (default '')
     **/
    public static function initNavigateListItems($itemtype, $title = "", $url = null)
    {
        if (Request::createFromGlobals()->isXmlHttpRequest() && $url === null) {
            return;
        }

        if (empty($title)) {
            $title = __('List');
        }
        if ($url === null) {
            $url = '';

            if (!isset($_SERVER['REQUEST_URI']) || (strpos($_SERVER['REQUEST_URI'], "tabs") > 0)) {
                $url = Html::getRefererUrl();
            } else {
                $url = $_SERVER['REQUEST_URI'];
            }
        }

        $_SESSION['glpilisttitle'][$itemtype] = $title;
        $_SESSION['glpilistitems'][$itemtype] = [];
        $_SESSION['glpilisturl'][$itemtype]   = $url;
    }


    /**
     * Check if active entities should be reloaded.
     *
     * @return bool true if active entities should be reloaded, false otherwise
     */
    public static function shouldReloadActiveEntities(): bool
    {
        if (!array_key_exists('glpiactive_entity', $_SESSION)) {
            return false;
        }
        $glpiactiveentities = $_SESSION['glpiactiveentities'] ?? [];
        if (count($glpiactiveentities)) {
            $glpiactive_entity = $_SESSION['glpiactive_entity'];
            $glpiactive_entity_recursive = $_SESSION['glpiactive_entity_recursive'] ?? false;
            $entities = [$glpiactive_entity => $glpiactive_entity];
            if (
                ($_SESSION["glpientity_fullstructure"] ?? false)
                && isset($_SESSION['glpiactiveprofile']['entities'])
            ) {
                foreach ($_SESSION['glpiactiveprofile']['entities'] as $val) {
                    $entities[$val['id']] = $val['id'];
                    if ($val['is_recursive']) {
                        $sons = getSonsOf("glpi_entities", $val['id']);
                        foreach ($sons as $key2 => $val2) {
                            $entities[$key2] = $key2;
                        }
                    }
                }
            } elseif ($glpiactive_entity_recursive) {
                $entities = getSonsOf("glpi_entities", $glpiactive_entity);
            }

            return count($entities) !== count($glpiactiveentities)
                || array_diff($entities, $glpiactiveentities) !== []
                || array_diff($glpiactiveentities, $entities) !== [];
        }
        return false;
    }


    /**
     * Change active entity to the $ID one. Update glpiactiveentities session variable.
     * Reload groups related to this entity.
     *
     * @param integer|string $ID           ID of the new active entity ("all"=>load all possible entities)
     *                                     (default 'all')
     * @param boolean         $is_recursive Also display sub entities of the active entity? (false by default)
     *
     * @return boolean true on success, false on failure
     **/
    public static function changeActiveEntities($ID = "all", $is_recursive = false)
    {

        $newentities = [];
        $ancestors = [];

        $_SESSION["glpientity_fullstructure"] = ($ID === 'all');

        if (isset($_SESSION['glpiactiveprofile'])) {
            if ($ID === "all") {
                foreach ($_SESSION['glpiactiveprofile']['entities'] as $val) {
                    $ancestors               = array_unique(array_merge(
                        getAncestorsOf(
                            "glpi_entities",
                            $val['id']
                        ),
                        $ancestors
                    ));
                    $newentities[$val['id']] = $val['id'];

                    if ($val['is_recursive']) {
                        $entities = getSonsOf("glpi_entities", $val['id']);
                        if (count($entities)) {
                            foreach (array_keys($entities) as $key2) {
                                $newentities[$key2] = $key2;
                            }
                        }
                    }
                }
                $is_recursive = true;
            } else {
                $ID = (int) $ID;

                /// Check entity validity
                $ancestors = getAncestorsOf("glpi_entities", $ID);
                $ok        = false;
                foreach ($_SESSION['glpiactiveprofile']['entities'] as $val) {
                    if (($val['id'] == $ID) || in_array($val['id'], $ancestors)) {
                        // Not recursive or recursive and root entity is recursive
                        if (!$is_recursive || $val['is_recursive']) {
                            $ok = true;
                        }
                    }
                }
                if (!$ok) {
                    return false;
                }

                $newentities[$ID] = $ID;
                if ($is_recursive) {
                    $entities = getSonsOf("glpi_entities", $ID);
                    if (count($entities)) {
                        foreach (array_keys($entities) as $key2) {
                            $newentities[$key2] = $key2;
                        }
                    }
                }
            }
        }

        if (count($newentities) > 0) {
            $_SESSION['glpiactiveentities']           = $newentities;
            $_SESSION['glpiactiveentities_string']    = "'" . implode("', '", $newentities) . "'";
            $active                                   = reset($newentities);
            $_SESSION['glpiparententities']           = $ancestors;
            $_SESSION['glpiparententities_string']    = implode("', '", $ancestors);
            if (!empty($_SESSION['glpiparententities_string'])) {
                $_SESSION['glpiparententities_string'] = "'" . $_SESSION['glpiparententities_string'] . "'";
            }
            // Active entity loading
            $_SESSION["glpiactive_entity"]           = $active;
            $_SESSION["glpiactive_entity_recursive"] = $is_recursive;
            $_SESSION["glpiactive_entity_name"]      = Dropdown::getDropdownName(
                "glpi_entities",
                $active
            );
            $_SESSION["glpiactive_entity_shortname"] = getTreeLeafValueName("glpi_entities", $active);
            if ($ID == "all") {
                //TRANS: %s is the entity name
                $_SESSION["glpiactive_entity_name"]      = sprintf(
                    __('%1$s (%2$s)'),
                    $_SESSION["glpiactive_entity_name"],
                    __('full structure')
                );
                $_SESSION["glpiactive_entity_shortname"] = sprintf(
                    __('%1$s (%2$s)'),
                    $_SESSION["glpiactive_entity_shortname"],
                    __('full structure')
                );
            } elseif ($is_recursive) {
                //TRANS: %s is the entity name
                $_SESSION["glpiactive_entity_name"]      = sprintf(
                    __('%1$s (%2$s)'),
                    $_SESSION["glpiactive_entity_name"],
                    __('tree structure')
                );
                $_SESSION["glpiactive_entity_shortname"] = sprintf(
                    __('%1$s (%2$s)'),
                    $_SESSION["glpiactive_entity_shortname"],
                    __('tree structure')
                );
            }

            if (countElementsInTable('glpi_entities') <= count($_SESSION['glpiactiveentities'])) {
                $_SESSION['glpishowallentities'] = 1;
            } else {
                $_SESSION['glpishowallentities'] = 0;
            }
            // Clean session variable to search system
            if (isset($_SESSION['glpisearch']) && count($_SESSION['glpisearch'])) {
                foreach ($_SESSION['glpisearch'] as $itemtype => $tab) {
                    if (isset($tab['start']) && ($tab['start'] > 0)) {
                        $_SESSION['glpisearch'][$itemtype]['start'] = 0;
                    }
                }
            }
            self::loadGroups();
            Plugin::doHook(Hooks::CHANGE_ENTITY);
            return true;
        }
        return false;
    }


    /**
     * Change active profile to the $ID one. Update glpiactiveprofile session variable.
     *
     * @param integer $ID ID of the new profile
     *
     * @return void
     **/
    public static function changeProfile($ID)
    {

        if (
            isset($_SESSION['glpiprofiles'][$ID])
            && count($_SESSION['glpiprofiles'][$ID]['entities'])
        ) {
            $profile = new Profile();
            if ($profile->getFromDB($ID)) {
                $profile->cleanProfile();
                $data             = $profile->fields;
                $data['entities'] = $_SESSION['glpiprofiles'][$ID]['entities'];

                $_SESSION['glpiactiveprofile']  = $data;
                $_SESSION['glpiactiveentities'] = [];

                Search::resetSaveSearch();
                $active_entity_done = false;

                // Try to load default entity if it is a root entity
                foreach ($data['entities'] as $val) {
                    if ($val['id'] === $_SESSION["glpidefault_entity"]) {
                        if (self::changeActiveEntities($val['id'], $val['is_recursive'])) {
                            $active_entity_done = true;
                        }
                    }
                }
                if (!$active_entity_done) {
                    // Try to load default entity
                    if (
                        $_SESSION["glpidefault_entity"] === null
                        || !self::changeActiveEntities($_SESSION["glpidefault_entity"], true)
                    ) {
                        // Load all entities
                        self::changeActiveEntities("all");
                    }
                }
                Plugin::doHook(Hooks::CHANGE_PROFILE);
            }
        }
        // Clean specific datas
        if (isset($_SESSION['glpimenu'])) {
            unset($_SESSION['glpimenu']);
        }
    }


    /**
     * Set the entities session variable. Load all entities from DB
     *
     * @param integer $userID ID of the user
     *
     * @return void
     **/
    public static function initEntityProfiles($userID)
    {
        global $DB;

        $_SESSION['glpiprofiles'] = [];

        if (!$DB->tableExists('glpi_profiles_users')) {
            //table does not exists in old GLPI versions
            return;
        }

        $iterator = $DB->request([
            'SELECT'          => [
                'glpi_profiles.id',
                'glpi_profiles.name',
            ],
            'DISTINCT'        => true,
            'FROM'            => 'glpi_profiles_users',
            'INNER JOIN'      => [
                'glpi_profiles'   => [
                    'ON' => [
                        'glpi_profiles_users'   => 'profiles_id',
                        'glpi_profiles'         => 'id',
                    ],
                ],
            ],
            'WHERE'           => [
                'glpi_profiles_users.users_id'   => $userID,
            ],
            'ORDERBY'         => 'glpi_profiles.name',
        ]);

        if (count($iterator)) {
            foreach ($iterator as $data) {
                $key = $data['id'];
                $_SESSION['glpiprofiles'][$key]['name'] = $data['name'];
                $entities_iterator = $DB->request([
                    'SELECT'    => [
                        'glpi_profiles_users.entities_id AS eID',
                        'glpi_profiles_users.id AS kID',
                        'glpi_profiles_users.is_recursive',
                        'glpi_entities.*',
                    ],
                    'FROM'      => 'glpi_profiles_users',
                    'LEFT JOIN' => [
                        'glpi_entities'   => [
                            'ON' => [
                                'glpi_profiles_users'   => 'entities_id',
                                'glpi_entities'         => 'id',
                            ],
                        ],
                    ],
                    'WHERE'     => [
                        'glpi_profiles_users.profiles_id'   => $key,
                        'glpi_profiles_users.users_id'      => $userID,
                    ],
                    'ORDERBY'   => 'glpi_entities.completename',
                ]);

                foreach ($entities_iterator as $data) {
                    // Do not override existing entity if define as recursive
                    if (
                        !isset($_SESSION['glpiprofiles'][$key]['entities'][$data['eID']])
                         || $data['is_recursive']
                    ) {
                        $_SESSION['glpiprofiles'][$key]['entities'][$data['eID']] = [
                            'id'           => $data['eID'],
                            'name'         => $data['name'],
                            'is_recursive' => $data['is_recursive'],
                        ];
                    }
                }
            }
        }
    }


    /**
     * Load current user's group on active entity
     *
     * @return void
     **/
    public static function loadGroups()
    {
        global $DB;

        $_SESSION["glpigroups"] = [];

        $entity_restriction = getEntitiesRestrictCriteria(
            Group::getTable(),
            'entities_id',
            $_SESSION['glpiactiveentities'],
            true
        );

        // Build select depending on whether or not the recursive_membership
        // column exist.
        // Needed because this code will be executed during the upgrade processs
        // BEFORE the recursive_membership column is added
        $SELECT = [Group_User::getTableField('groups_id')];
        if ($DB->fieldExists(Group::getTable(), 'recursive_membership')) {
            $SELECT[] = Group::getTableField('recursive_membership');
        }
        $iterator = $DB->request([
            'SELECT'    => $SELECT,
            'FROM'      => Group_User::getTable(),
            'LEFT JOIN' => [
                Group::getTable() => [
                    'ON' => [
                        Group::getTable()       => 'id',
                        Group_User::getTable()  => 'groups_id',
                    ],
                ],
            ],
            'WHERE'     => [
                Group_User::getTable() . '.users_id' => self::getLoginUserID(),
            ] + $entity_restriction,
        ]);

        foreach ($iterator as $data) {
            $_SESSION["glpigroups"][] = $data["groups_id"];

            // Add children groups
            if ($data['recursive_membership']) {
                // Stack of children to load
                $children_to_load = [$data["groups_id"]];

                while ($children_to_load !== []) {
                    $next_child_to_load = array_pop($children_to_load);

                    // Note: we can't use getSonsOf here because some groups in the
                    // hierarchy might disable recursive membership for their own
                    // children
                    $children_data = $DB->request([
                        'SELECT' => ['id', 'recursive_membership'],
                        'FROM'   => Group::getTable(),
                        'WHERE'  => ['groups_id' => $next_child_to_load] + $entity_restriction,
                    ]);

                    // Iterate on the children
                    foreach ($children_data as $data) {
                        // Add the child to the user's groups
                        $_SESSION["glpigroups"][] = $data['id'];

                        // If the child support recursive membership, load its
                        // children too
                        if ($data['recursive_membership']) {
                            $children_to_load[] = $data['id'];
                        }
                    }
                }
            }
        }

        // Clear duplicates
        $_SESSION["glpigroups"] = array_unique($_SESSION["glpigroups"]);

        // Set new valid cache date
        $_SESSION['glpigroups_cache_date'] = $_SESSION["glpi_currenttime"];
    }


    /**
     * Include the good language dict.
     *
     * Get the default language from current user in $_SESSION["glpilanguage"].
     * And load the dict that correspond.
     *
     * @param string  $forcelang     Force to load a specific lang
     * @param boolean $with_plugins  Whether to load plugin languages or not
     *
     * @return string
     **/
    public static function loadLanguage($forcelang = '', $with_plugins = true)
    {
        global $CFG_GLPI, $TRANSLATE;

        if (!isset($_SESSION["glpilanguage"])) {
            $_SESSION["glpilanguage"] = self::getPreferredLanguage();
        }

        $trytoload = $_SESSION["glpilanguage"];
        // Force to load a specific lang
        if (!empty($forcelang)) {
            $trytoload = $forcelang;
        }

        // If not set try default lang file
        if (empty($trytoload)) {
            $trytoload = $CFG_GLPI["language"];
        }

        if (isset($CFG_GLPI["languages"][$trytoload])) {
            $newfile = "/" . $CFG_GLPI["languages"][$trytoload][1];
        }

        if (empty($newfile) || !is_file(GLPI_I18N_DIR . $newfile)) {
            $newfile = "/en_GB.mo";
        }

        if (isset($CFG_GLPI["languages"][$trytoload][5])) {
            $_SESSION['glpipluralnumber'] = $CFG_GLPI["languages"][$trytoload][5];
        }

        $_SESSION['glpiisrtl'] = self::isRTL($trytoload);

        // Redefine Translator caching logic to be able to drop laminas/laminas-cache dependency.
        $i18n_cache = !defined('TU_USER') ? new I18nCache((new CacheManager())->getTranslationsCacheInstance()) : null;
        $TRANSLATE = new class ($i18n_cache) extends Translator { // @phpstan-ignore class.extendsFinalByPhpDoc
            public function __construct(?I18nCache $cache)
            {
                $this->cache = $cache; // @phpstan-ignore assign.propertyType (laminas...)
            }
        };

        $TRANSLATE->setLocale($trytoload);

        if (class_exists('Locale')) {
            // Locale class may be missing if intl extension is not installed.
            // In this case, we may still want to be able to load translations (for instance for requirements checks).
            Locale::setDefault($trytoload);
        } else {
            trigger_error('Missing required intl PHP extension', E_USER_WARNING);
        }

        $TRANSLATE->addTranslationFile('gettext', GLPI_I18N_DIR . $newfile, 'glpi', $trytoload);

        $core_folders = is_dir(GLPI_LOCAL_I18N_DIR) ? scandir(GLPI_LOCAL_I18N_DIR) : [];
        $core_folders = array_filter($core_folders, function ($dir) {
            if (!is_dir(GLPI_LOCAL_I18N_DIR . "/$dir")) {
                return false;
            }

            if ($dir == 'core') {
                return true;
            }

            return str_starts_with($dir, 'core_');
        });

        foreach ($core_folders as $core_folder) {
            $mofile = GLPI_LOCAL_I18N_DIR . "/$core_folder/" . $newfile;
            $phpfile = str_replace('.mo', '.php', $mofile);

            // Load local PHP file if it exists
            if (file_exists($phpfile)) {
                $TRANSLATE->addTranslationFile('phparray', $phpfile, 'glpi', $trytoload);
            }

            // Load local MO file if it exists -- keep last so it gets precedence
            if (file_exists($mofile)) {
                $TRANSLATE->addTranslationFile('gettext', $mofile, 'glpi', $trytoload);
            }
        }

        // Load plugin dicts
        if ($with_plugins) {
            foreach (Plugin::getPlugins() as $plug) {
                Plugin::loadLang($plug, $forcelang, $trytoload);
            }
        }

        return $trytoload;
    }

    /**
     * Loads all locales from the core for the translation system.
     * Should only be used during the install or update process to allow initialization of text in multiple languages.
     * @return void
     */
    public static function loadAllCoreLocales(): void
    {
        global $CFG_GLPI, $TRANSLATE;

        $core_folders = is_dir(GLPI_LOCAL_I18N_DIR) ? scandir(GLPI_LOCAL_I18N_DIR) : [];
        $core_folders = array_filter($core_folders, static function ($dir) {
            if (!is_dir(GLPI_LOCAL_I18N_DIR . "/$dir")) {
                return false;
            }

            if ($dir === 'core') {
                return true;
            }

            return str_starts_with($dir, 'core_');
        });
        $core_folders = array_map(static fn($dir) => GLPI_LOCAL_I18N_DIR . "/$dir", $core_folders);
        $core_folders = [GLPI_I18N_DIR, ...$core_folders];

        foreach ($core_folders as $core_folder) {
            foreach ($CFG_GLPI['languages'] as $lang => $data) {
                $mofile = "$core_folder/" . $data['1'];
                $phpfile = str_replace('.mo', '.php', $mofile);

                // Load local PHP file if it exists
                if (file_exists($phpfile)) {
                    $TRANSLATE->addTranslationFile('phparray', $phpfile, 'glpi', $lang);
                }

                // Load local MO file if it exists -- keep last so it gets precedence
                if (file_exists($mofile)) {
                    $TRANSLATE->addTranslationFile('gettext', $mofile, 'glpi', $lang);
                }
            }
        }
    }

    /**
     * Return preffered language (from HTTP headers, fallback to default GLPI lang).
     *
     * @return string
     */
    public static function getPreferredLanguage(): string
    {
        global $CFG_GLPI;

        // Extract accepted languages from headers
        // Accept-Language: fr-FR, fr;q=0.9, en;q=0.8, de;q=0.7, *;q=0.5
        $accepted_languages = [];
        $values = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
        foreach ($values as $value) {
            $parts = explode(';q=', trim($value));
            $language = str_replace('-', '_', $parts[0]);
            $qfactor  = $parts[1] ?? 1; //q-factor defaults to 1
            $accepted_languages[$language] = $qfactor;
        }
        arsort($accepted_languages); // sort by qfactor

        foreach (array_keys($accepted_languages) as $language) {
            if (array_key_exists($language, $CFG_GLPI['languages'])) {
                return $language;
            }
        }

        if (isset($CFG_GLPI['language'])) {
            // Default config in GLPI >= 0.72
            return $CFG_GLPI['language'];
        } elseif (isset($CFG_GLPI['default_language'])) {
            // Default config in GLPI < 0.72 : keep it for upgrade process
            return $CFG_GLPI['default_language'];
        }

        return 'en_GB';
    }

    /**
     * Get plural form number
     *
     * @return integer
     */
    public static function getPluralNumber()
    {
        /** @var int $DEFAULT_PLURAL_NUMBER */
        global $DEFAULT_PLURAL_NUMBER;

        if (isset($_SESSION['glpipluralnumber'])) {
            return $_SESSION['glpipluralnumber'];
        } else {
            return $DEFAULT_PLURAL_NUMBER;
        }
    }

    /**
     * Detect cron mode or interactive
     *
     * @since 0.84
     *
     * @return boolean
     **/
    public static function isCron()
    {
        return (self::isInventory() || isset($_SESSION["glpicronuserrunning"]))
            && (
                isCommandLine()
                || str_starts_with(Request::createFromGlobals()->getPathInfo(), '/front/cron.php')
            );
    }

    /**
     * Detect inventory mode
     *
     * @return boolean
     **/
    public static function isInventory(): bool
    {

        return (isset($_SESSION["glpiinventoryuserrunning"])
              && (
                  InventoryController::$is_running === true
                  || defined('TU_USER')
              )
        );
    }

    /**
     * Get the Login User ID or return cron user ID for cron jobs
     *
     * @param boolean $force_human Force human / do not return cron user (true by default)
     *
     * @return false|int|string false if user is not logged in
     *                          int for user id, string for cron jobs
     **/
    public static function getLoginUserID($force_human = true)
    {
        if (self::isInventory()) { // Check inventory
            return $_SESSION["glpiinventoryuserrunning"];
        }

        if (
            !$force_human
            && self::isCron()
        ) { // Check cron jobs
            return $_SESSION["glpicronuserrunning"] ?? $_SESSION['glpiinventoryuserrunning'];
        }
        return $_SESSION["glpiID"] ?? false;
    }

    /**
     * Global check of session to prevent PHP vulnerability
     *
     * @since 0.85
     *
     * @see https://wiki.php.net/rfc/strict_sessions
     *
     * @return void|true
     **/
    public static function checkValidSessionId()
    {
        global $DB;

        if (
            !isset($_SESSION['valid_id'])
            || ($_SESSION['valid_id'] !== session_id())
        ) {
            throw new SessionExpiredException();
        }

        $user_id    = self::getLoginUserID();
        $profile_id = $_SESSION['glpiactiveprofile']['id'] ?? null;
        $entity_id  = $_SESSION['glpiactive_entity'] ?? null;

        if (!is_numeric($user_id) || $profile_id === null || $entity_id === null) {
            throw new SessionExpiredException();
        }

        $user_table = User::getTable();
        $pu_table   = Profile_User::getTable();
        $profile_table = Profile::getTable();
        $result = $DB->request(
            [
                'COUNT'     => 'count',
                'SELECT'    => [$profile_table . '.last_rights_update'],
                'FROM'      => $user_table,
                'LEFT JOIN' => [
                    $pu_table => [
                        'FKEY'  => [
                            Profile_User::getTable() => 'users_id',
                            $user_table         => 'id',
                        ],
                    ],
                    $profile_table => [
                        'FKEY'  => [
                            $pu_table => 'profiles_id',
                            $profile_table => 'id',
                        ],
                    ],
                ],
                'WHERE'     => [
                    $user_table . '.id'         => $user_id,
                    $user_table . '.is_active'  => 1,
                    $user_table . '.is_deleted' => 0,
                    $pu_table . '.profiles_id'  => $profile_id,
                ] + getEntitiesRestrictCriteria($pu_table, 'entities_id', $entity_id, true),
                'GROUPBY'   => [$profile_table . '.id'],
            ]
        );

        $row = $result->current();

        if ($row === null || $row['count'] === 0) {
            // The current profile cannot be found for the current user in the database.
            // The session information are stale, therefore the session should be considered as expired.
            throw new SessionExpiredException();
        }

        if (
            $row['last_rights_update'] !== null
            && $row['last_rights_update'] > ($_SESSION['glpiactiveprofile']['last_rights_update'] ?? 0)
        ) {
            Session::reloadCurrentProfile();
            $_SESSION['glpiactiveprofile']['last_rights_update'] = $row['last_rights_update'];
        }

        return true;
    }

    /**
     * Check if I have access to the central interface
     *
     * @return void
     **/
    public static function checkCentralAccess()
    {
        self::checkValidSessionId();
        if (Session::getCurrentInterface() != "central") {
            throw new AccessDeniedHttpException("The current profile does not use the standard interface");
        }
    }


    /**
     * Check if I have the right to access to the FAQ (profile or anonymous FAQ)
     *
     * @return void
     **/
    public static function checkFaqAccess()
    {
        global $CFG_GLPI;

        if (!$CFG_GLPI["use_public_faq"]) {
            self::checkValidSessionId();
            if (!Session::haveRightsOr('knowbase', [KnowbaseItem::READFAQ, READ])) {
                throw new AccessDeniedHttpException("Missing FAQ right");
            }
        }
    }


    /**
     * Check if I have access to the helpdesk interface
     *
     * @return void
     **/
    public static function checkHelpdeskAccess()
    {
        self::checkValidSessionId();
        if (Session::getCurrentInterface() != "helpdesk") {
            throw new AccessDeniedHttpException("The current profile does not use the simplified interface");
        }
    }

    /**
     * Check if I am logged in
     *
     * @return void
     **/
    public static function checkLoginUser()
    {
        self::checkValidSessionId();
        if (!isset($_SESSION["glpiname"])) {
            throw new AccessDeniedHttpException("User has no valid session but seems to be logged in");
        }
    }

    /**
     * Get the name of the right.
     * This should only be used when it is expected that the request going to be terminated.
     * The session will be closed by this method.
     *
     * @param string $module The module
     * @param int $right The right
     * @return string The right name
     * @internal No backwards compatibility promise. Use in core only.
     */
    public static function getRightNameForError(string $module, int $right): string
    {
        // Well known rights
        $rights = [
            READ => 'READ',
            UPDATE => 'UPDATE',
            CREATE => 'CREATE',
            DELETE => 'DELETE',
            PURGE => 'PURGE',
            ALLSTANDARDRIGHT => 'ALLSTANDARDRIGHT',
            READNOTE => 'READNOTE',
            UPDATENOTE => 'UPDATENOTE',
            UNLOCK => 'UNLOCK',
        ];
        // Close session and force the default language so the logged right name is standardized
        try {
            session_write_close();
        } catch (SessionException $e) {
            //empty catch; session may already be closed
        }
        $current_lang = $_SESSION['glpilanguage'];
        self::loadLanguage('en_GB');

        $all_specific_rights = Profile::getRightsForForm(self::getCurrentInterface());
        $specific_rights = [];
        foreach ($all_specific_rights as $forms) {
            foreach ($forms as $group_rights) {
                foreach ($group_rights as $right_definition) {
                    if ($right_definition['field'] === $module) {
                        $rights_arr = $right_definition['rights'];
                        foreach ($rights_arr as $right_val => $right_label) {
                            $label = $right_label;
                            if (is_array($label) && isset($label['short'])) {
                                $label = $label['short'];
                            }
                            if (!is_array($label)) {
                                $specific_rights[$right_val] = $label;
                            }
                        }
                    }
                }
            }
        }

        // Restore language so the error displayed is in the user language
        self::loadLanguage($current_lang);

        return $specific_rights[$right] ?? $rights[$right] ?? 'unknown right name';
    }

    /**
     * Check if I have the right $right to module $module (compare to session variable)
     *
     * @param string  $module Module to check
     * @param integer $right  Right to check
     *
     * @return void
     **/
    public static function checkRight($module, $right)
    {
        self::checkValidSessionId();
        if (!self::haveRight($module, $right)) {
            $right_name = self::getRightNameForError($module, $right);
            throw new AccessDeniedHttpException("User is missing the $right ($right_name) right for $module");
        }
    }

    /**
     * Check if I one right of array $rights to module $module (compare to session variable)
     *
     * @param string $module Module to check
     * @param array  $rights Rights to check
     *
     * @return void
     **/
    public static function checkRightsOr($module, $rights = [])
    {
        self::checkValidSessionId();
        if (!self::haveRightsOr($module, $rights)) {
            $info = "User is missing all of the following rights: ";
            foreach ($rights as $right) {
                $right_name = self::getRightNameForError($module, $right);
                $info .= $right . " ($right_name), ";
            }
            $info = substr($info, 0, -2);
            $info .= " for $module";
            throw new AccessDeniedHttpException($info);
        }
    }


    /**
     * Check if I have one of the right specified
     *
     * You can't use this function if several rights for same module name
     *
     * @param array $modules Array of modules where keys are modules and value are right
     *
     * @return void
     **/
    public static function checkSeveralRightsOr($modules)
    {
        self::checkValidSessionId();

        $valid = false;
        if (count($modules)) {
            foreach ($modules as $mod => $right) {
                // Itemtype
                if (preg_match('/[A-Z]/', $mod[0])) {
                    if ($item = getItemForItemtype($mod)) {
                        if ($item->canGlobal($right)) {
                            $valid = true;
                        }
                    }
                } elseif (self::haveRight($mod, $right)) {
                    $valid = true;
                }
            }
        }

        if (!$valid) {
            $info = "User is missing all of the following rights: ";
            foreach ($modules as $mod => $right) {
                $right_name = self::getRightNameForError($mod, $right);
                $info .= $right . " ($right_name) for module $mod, ";
            }
            $info = substr($info, 0, -2);
            throw new AccessDeniedHttpException($info);
        }
    }


    /**
     * Check if you could access to ALL the entities of an list
     *
     * @param array $tab List ID of entities
     *
     * @return boolean
     **/
    public static function haveAccessToAllOfEntities($tab)
    {

        if (is_array($tab) && count($tab)) {
            foreach ($tab as $val) {
                if (!self::haveAccessToEntity($val)) {
                    return false;
                }
            }
        }
        return true;
    }


    /**
     * Check if you could access (read) to the entity of id = $ID
     *
     * @param integer $ID           ID of the entity
     * @param boolean $is_recursive if recursive item (default false)
     *
     * @return boolean
     **/
    public static function haveAccessToEntity($ID, $is_recursive = false)
    {

        // Quick response when passing wrong ID : default value of getEntityID is -1
        if ($ID < 0) {
            return false;
        }

        if (!isset($_SESSION['glpiactiveentities'])) {
            return false;
        }

        if (in_array($ID, $_SESSION['glpiactiveentities'])) {
            return true;
        }

        if (!$is_recursive) {
            return false;
        }

        /// Recursive object
        return in_array($ID, getAncestorsOf("glpi_entities", $_SESSION['glpiactiveentities']));
    }


    /**
     * Check if you could access to one entity of a list
     *
     * @param array   $tab          list ID of entities
     * @param boolean $is_recursive if recursive item (default false)
     *
     * @return boolean
     **/
    public static function haveAccessToOneOfEntities($tab, $is_recursive = false)
    {

        if (is_array($tab) && count($tab)) {
            foreach ($tab as $val) {
                if (self::haveAccessToEntity($val, $is_recursive)) {
                    return true;
                }
            }
        }
        return false;
    }


    /**
     * Check if you could create recursive object in the entity of id = $ID
     *
     * @param integer $ID ID of the entity
     *
     * @return boolean
     **/
    public static function haveRecursiveAccessToEntity($ID)
    {

        // Right by profile
        foreach ($_SESSION['glpiactiveprofile']['entities'] as $val) {
            if ($val['id'] == $ID) {
                return $val['is_recursive'];
            }
        }
        // Right is from a recursive profile
        if (isset($_SESSION['glpiactiveentities'])) {
            return in_array($ID, $_SESSION['glpiactiveentities']);
        }
        return false;
    }


    /**
     * Have I the right $right to module $module (compare to session variable)
     *
     * @param string  $module Module to check
     * @param integer $right  Right to check
     *
     * @return boolean|int
     **/
    public static function haveRight($module, $right)
    {
        global $DB;

        if (self::isRightChecksDisabled() || Session::isInventory() || Session::isCron()) {
            return true;
        }

        //If GLPI is using the slave DB -> read only mode
        if (
            $DB->isSlave()
            && ($right & (CREATE | UPDATE | DELETE | PURGE))
        ) {
            return false;
        }

        if (isset($_SESSION["glpiactiveprofile"][$module])) {
            return (int) $_SESSION["glpiactiveprofile"][$module] & $right;
        }

        return false;
    }


    /**
     * Have I all rights of array $rights to module $module (compare to session variable)
     *
     * @param string    $module Module to check
     * @param integer[] $rights Rights to check
     *
     * @return boolean
     **/
    public static function haveRightsAnd($module, $rights = [])
    {

        foreach ($rights as $right) {
            if (!Session::haveRight($module, $right)) {
                return false;
            }
        }
        return true;
    }


    /**
     * Have I one right of array $rights to module $module (compare to session variable)
     *
     * @param string    $module Module to check
     * @param integer[] $rights Rights to check
     *
     * @return boolean
     **/
    public static function haveRightsOr($module, $rights = [])
    {

        foreach ($rights as $right) {
            if (Session::haveRight($module, $right)) {
                return true;
            }
        }
        return false;
    }


    /**
     *  Get active Tab for an itemtype
     *
     * @param string $itemtype item type
     *
     * @return string
     **/
    public static function getActiveTab($itemtype)
    {

        return $_SESSION['glpi_tabs'][strtolower($itemtype)] ?? "";
    }

    /**
     * Add multiple messages to be displayed after redirect
     *
     * @param array $messages     Messages to add
     * @param bool  $check_once   Check if the message is not already added (false by default)
     * @param int   $message_type Message type (INFO, WARNING, ERROR) (default INFO)
     *
     * @return void
     **/
    public static function addMessagesAfterRedirect(
        $messages,
        $check_once = false,
        $message_type = INFO
    ) {
        foreach ($messages as $message) {
            self::addMessageAfterRedirect(
                $message,
                $check_once,
                $message_type,
                false // Does not make sense for multiple messages, must always be false
            );
        }
    }

    /**
     * Add a message to be displayed after redirect
     *
     * @param string  $msg          Message to add
     * @param boolean $check_once   Check if the message is not already added (false by default)
     * @param integer $message_type Message type (INFO, WARNING, ERROR) (default INFO)
     * @param boolean $reset        Clear previous added message (false by default)
     *
     * @return void
     *
     * @psalm-taint-specialize (to report each unsafe usage as a distinct error)
     * @psalm-taint-sink html $msg (message will be sent to output without being escaped)
     */
    public static function addMessageAfterRedirect(
        $msg,
        $check_once = false,
        $message_type = INFO,
        $reset = false
    ) {
        if (!empty($msg)) {
            if (self::isCron()) {
                // We are in cron mode
                // Do not display message in user interface, but record error
                if ($message_type == ERROR) {
                    Toolbox::logInFile('cron', $msg . "\n");
                }
            } else {
                $array = &$_SESSION['MESSAGE_AFTER_REDIRECT'];

                if ($reset) {
                    $array = [];
                }

                if (!isset($array[$message_type])) {
                    $array[$message_type] = [];
                }

                if (
                    !$check_once
                    || !isset($array[$message_type])
                    || in_array($msg, $array[$message_type]) === false
                ) {
                    $array[$message_type][] = $msg;
                }
            }
        }
    }

    /**
     * Delete a session message
     *
     * @param string  $msg          Message to delete
     * @param integer $message_type Message type (INFO, WARNING, ERROR) (default INFO)
     *
     * @return void
     */
    public static function deleteMessageAfterRedirect(
        string $msg,
        int $message_type = INFO
    ): void {
        if (!empty($msg)) {
            $array = &$_SESSION['MESSAGE_AFTER_REDIRECT'];

            if (isset($array[$message_type])) {
                $key = array_search($msg, $array[$message_type]);
                if ($key !== false) {
                    unset($array[$message_type][$key]);
                }
            }

            // Reorder keys
            $array[$message_type] = array_values($array[$message_type]);
        }
    }

    /**
     *  Force active Tab for an itemtype
     *
     * @param string  $itemtype item type
     * @param mixed $tab      ID of the tab
     *
     * @return void
     **/
    public static function setActiveTab($itemtype, $tab)
    {
        $_SESSION['glpi_tabs'][strtolower($itemtype)] = $tab;
    }


    /**
     * Get a saved option from request or session
     * if get from request, save it
     *
     * @since 0.83
     *
     * @param string $itemtype  name of itemtype
     * @param string $name      name of the option
     * @param mixed  $defvalue  mixed default value for option
     *
     * @return mixed
     **/
    public static function getSavedOption($itemtype, $name, $defvalue)
    {

        if (isset($_REQUEST[$name])) {
            return $_SESSION['glpi_saved'][$itemtype][$name] = $_REQUEST[$name];
        }
        return $_SESSION['glpi_saved'][$itemtype][$name] ?? $defvalue;
    }


    /**
     * Is the current account read-only
     *
     * @since 0.83
     *
     * @return boolean
     **/
    public static function isReadOnlyAccount()
    {

        foreach ($_SESSION['glpiactiveprofile'] as $name => $val) {
            if (
                is_numeric($val)
                && ($name != 'search_config')
                && ($val & ~READ)
            ) {
                return false;
            }
        }
        return true;
    }



    /**
     * Get new CSRF token
     *
     * @param bool $standalone
     *    Generates a standalone token that will not be shared with other component of current request.
     *
     * @since 0.83.3
     *
     * @return string
     **/
    public static function getNewCSRFToken(bool $standalone = false)
    {
        /** @var string $CURRENTCSRFTOKEN */
        global $CURRENTCSRFTOKEN;

        $token = $standalone ? '' : $CURRENTCSRFTOKEN;

        if (empty($token)) {
            do {
                $token = bin2hex(random_bytes(32));
            } while ($token == '');
        }

        if (!isset($_SESSION['glpicsrftokens'])) {
            $_SESSION['glpicsrftokens'] = [];
        }
        $_SESSION['glpicsrftokens'][$token] = 1;

        if (!$standalone) {
            $CURRENTCSRFTOKEN = $token;
        }

        return $token;
    }


    /**
     * Clean expired CSRF tokens
     *
     * @since 0.83.3
     *
     * @return void
     **/
    public static function cleanCSRFTokens()
    {
        if (
            isset($_SESSION['glpicsrftokens'])
            && is_array($_SESSION['glpicsrftokens'])
            && count($_SESSION['glpicsrftokens']) > self::CSRF_MAX_TOKENS
        ) {
            $overflow = count($_SESSION['glpicsrftokens']) - self::CSRF_MAX_TOKENS;
            $_SESSION['glpicsrftokens'] = array_slice(
                $_SESSION['glpicsrftokens'],
                $overflow,
                null,
                true
            );
        }
    }


    /**
     * Validate that the page has a CSRF token in the POST data
     * and that the token is legit/not expired.  If the token is valid
     * it will be removed from the list of valid tokens.
     *
     * @since 0.83.3
     *
     * @param array $data           $_POST data
     * @param bool  $preserve_token Whether to preserve token after it has been validated.
     *
     * @return boolean
     **/
    public static function validateCSRF($data, bool $preserve_token = false)
    {
        Session::cleanCSRFTokens();

        if (!isset($data['_glpi_csrf_token'])) {
            return false;
        }
        $requestToken = $data['_glpi_csrf_token'];
        if (isset($_SESSION['glpicsrftokens'][$requestToken])) {
            if (!$preserve_token) {
                unset($_SESSION['glpicsrftokens'][$requestToken]);
            }
            return true;
        }

        return false;
    }


    /**
     * Check CSRF data
     *
     * @since 0.84.2
     *
     * @param array $data           $_POST data
     * @param bool  $preserve_token Whether to preserve token after it has been validated.
     *
     * @return void
     **/
    public static function checkCSRF($data, bool $preserve_token = false)
    {
        if (!Session::validateCSRF($data, $preserve_token)) {
            $requested_url = ($_SERVER['REQUEST_URI'] ?? 'Unknown');
            $user_id = self::getLoginUserID() ?? 'Anonymous';
            Toolbox::logInFile('access-errors', "CSRF check failed for User ID: $user_id at $requested_url\n");

            $exception = new AccessDeniedHttpException();
            $exception->setMessageToDisplay(__('The action you have requested is not allowed.'));
            throw $exception;
        }
    }


    /**
     * Get new IDOR token
     * This token validates the itemtype used by an ajax request is the one asked by a dropdown.
     * So, we avoid IDOR request where an attacker asks for another itemtype
     * than the originally intended
     *
     * @since 9.5.3
     *
     * @param string $itemtype
     * @param array  $add_params more criteria to check validity of IDOR tokens
     *
     * @return string
     **/
    public static function getNewIDORToken(string $itemtype = "", array $add_params = []): string
    {
        if ($itemtype === '' && count($add_params) === 0) {
            trigger_error('IDOR token cannot be generated with empty criteria.', E_USER_WARNING);
            return '';
        }

        $token = "";
        do {
            $token = bin2hex(random_bytes(32));
        } while ($token == '');

        if (!isset($_SESSION['glpiidortokens'])) {
            $_SESSION['glpiidortokens'] = [];
        }

        $_SESSION['glpiidortokens'][$token] = ($itemtype !== "" ? ['itemtype' => $itemtype] : []) + $add_params;

        return $token;
    }


    /**
     * Validate that the page has a IDOR token in the POST data
     * and that the token is legit/not expired.
     * Tokens are kept in session until their time is expired (by default 2h)
     * to permits multiple ajax calls for a dropdown
     *
     * @since 9.5.3
     *
     * @param array $data $_POST data
     *
     * @return boolean
     **/
    public static function validateIDOR(array $data = []): bool
    {
        self::cleanIDORTokens();

        if (!isset($data['_idor_token'])) {
            return false;
        }

        $token = $data['_idor_token'];

        if (isset($_SESSION['glpiidortokens'][$token])) {
            $idor_data =  $_SESSION['glpiidortokens'][$token];

            // Ensure that `displaywith` and `condition` is checked if passed in data
            $mandatory_properties = [
                'displaywith' => [],
                'condition'   => [],
            ];
            foreach ($mandatory_properties as $property_name => $default_value) {
                if (!array_key_exists($property_name, $data)) {
                    $data[$property_name] = $default_value;
                }
                if (!array_key_exists($property_name, $idor_data)) {
                    $idor_data[$property_name] = $default_value;
                }
            }

            // check all stored data for the IDOR token are present (and identical) in the posted data
            $match_expected = function ($expected, $given) use (&$match_expected) {
                if (is_array($expected)) {
                    if (!is_array($given)) {
                        return false;
                    }
                    foreach ($expected as $key => $value) {
                        if (!array_key_exists($key, $given) || !$match_expected($value, $given[$key])) {
                            return false;
                        }
                    }
                    return true;
                } else {
                    return $expected == $given;
                }
            };

            return $match_expected($idor_data, $data);
        }

        return false;
    }

    /**
     * Clean expired IDOR tokens
     *
     * @since 9.5.3
     *
     * @return void
     **/
    public static function cleanIDORTokens()
    {
        if (
            isset($_SESSION['glpiidortokens'])
            && is_array($_SESSION['glpiidortokens'])
            && count($_SESSION['glpiidortokens']) > self::IDOR_MAX_TOKENS
        ) {
            $overflow = count($_SESSION['glpiidortokens']) - self::IDOR_MAX_TOKENS;
            $_SESSION['glpiidortokens'] = array_slice(
                $_SESSION['glpiidortokens'],
                $overflow,
                null,
                true
            );
        }
    }


    /**
     * Is field having translations ?
     *
     * @since 0.85
     *
     * @param string $itemtype itemtype
     * @param string $field    field
     *
     * @return boolean
     **/
    public static function haveTranslations($itemtype, $field)
    {
        if (!is_a($itemtype, CommonDropdown::class, true)) {
            return false;
        }

        return (isset($_SESSION['glpi_dropdowntranslations'][$itemtype])
              && isset($_SESSION['glpi_dropdowntranslations'][$itemtype][$field]));
    }

    /**
     * Get current interface name extracted from session var (if exists)
     *
     * @since  9.2.2
     *
     * @return string|false Returns "helpdesk" or "central" if there is a session and the interface property is set.
     *                      Returns false if there is no session or the interface property is not set.
     */
    public static function getCurrentInterface()
    {
        return $_SESSION['glpiactiveprofile']['interface'] ?? false;
    }

    /**
     * Check if current user can impersonate another user having given id.
     *
     * @param integer $user_id
     *
     * @return boolean
     */
    public static function canImpersonate($user_id, ?string &$message = null)
    {
        global $DB;

        $is_super_admin = self::haveRight(Config::$rightname, UPDATE);

        // Stop here if the user can't impersonate (doesn't have the right + isn't admin)
        if (!self::haveRight('user', User::IMPERSONATE) && !$is_super_admin) {
            return false;
        }

        if (
            $user_id <= 0 || self::getLoginUserID() == $user_id
            || (self::isImpersonateActive() && self::getImpersonatorId() == $user_id)
        ) {
            $message = __("You can't impersonate yourself.");
            return false; // Cannot impersonate invalid user, self, or already impersonated user
        }

        // Cannot impersonate inactive user
        $user = new User();
        if (!$user->getFromDB($user_id) || !$user->getField('is_active')) {
            $message = __("The user is not active.");
            return false;
        }

        // Cannot impersonate user with no profile
        $other_user_profiles = Profile_User::getUserProfiles($user_id);
        if (count($other_user_profiles) === 0) {
            $message = __("The user doesn't have any profile.");
            return false;
        }

        if ($is_super_admin) {
            return true; // User can impersonate anyone
        }

        // Check if user can impersonate lower-privileged users (or same level)
        // Get all less-privileged (or equivalent) profiles than current one
        $criteria = Profile::getUnderActiveProfileRestrictCriteria();
        $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => Profile::getTable(),
            'WHERE'  => $criteria,
        ]);
        $profiles = [];
        foreach ($iterator as $data) {
            $profiles[] = $data['id'];
        }
        // Check if all profiles of the user are less-privileged than current one
        if (count($other_user_profiles) !== count(array_intersect($profiles, array_keys($other_user_profiles)))) {
            $message = __("User has more rights than you. You can't impersonate him.");
            return false;
        }

        return true;
    }

    /**
     * Impersonate user having given id.
     *
     * @param integer $user_id
     *
     * @return boolean
     */
    public static function startImpersonating($user_id)
    {

        if (!self::canImpersonate($user_id)) {
            return false;
        }

        $user = new User();
        if (!$user->getFromDB($user_id)) {
            return false;
        }

        //store user who impersonated another user
        $impersonator = $_SESSION['glpiname'];

        // Store current user values
        $impersonator_id  = self::isImpersonateActive()
         ? $_SESSION['impersonator_id']
         : self::getLoginUserID();
        $lang             = $_SESSION['glpilanguage'];
        $session_use_mode = $_SESSION['glpi_use_mode'];

        $impersonator_info = [
            'id'                            => $impersonator_id,
            'glpiname'                      => $impersonator,
            'glpilanguage'                  => $lang,
            'glpi_use_mode'                 => $session_use_mode,
            'glpiactive_entity'             => $_SESSION['glpiactive_entity'],
            'glpiactive_entity_recursive'   => $_SESSION['glpiactive_entity_recursive'],
            'profiles_id'                   => $_SESSION['glpiactiveprofile']['id'],
        ];

        $auth = new Auth();
        $auth->auth_succeded = true;
        $auth->user = $user;
        Session::init($auth);

        // Force usage of current user lang and session mode
        $_SESSION['glpilanguage'] = $lang;
        $_SESSION['glpi_use_mode'] = $session_use_mode;
        Session::loadLanguage();

        $_SESSION['impersonator_id'] = $impersonator_id;
        $_SESSION['impersonator_info'] = $impersonator_info;

        Event::log(0, "system", 3, "Impersonate", sprintf(
            __('%1$s starts impersonating user %2$s'),
            $impersonator,
            $user->fields['name']
        ));

        return true;
    }

    /**
     * Stop impersonating any user.
     *
     * @return boolean
     */
    public static function stopImpersonating()
    {

        if (!self::isImpersonateActive()) {
            return true; // Nothing to do
        }

        $user = new User();
        if (!$user->getFromDB($_SESSION['impersonator_id'])) {
            return false;
        }

        //store user which was impersonated by another user
        $impersonate_user = $_SESSION['glpiname'];
        $impersonator_info = $_SESSION['impersonator_info'] ?? [];

        $auth = new Auth();
        $auth->auth_succeded = true;
        $auth->user = $user;
        Session::init($auth);

        // Restore previous user values
        if (!empty($impersonator_info)) {
            // Basic values
            $_SESSION['glpilanguage'] = $impersonator_info['glpilanguage'];
            $_SESSION['glpi_use_mode'] = $impersonator_info['glpi_use_mode'];
            // Restore profile/entity
            self::changeProfile($impersonator_info['profiles_id']);
            self::changeActiveEntities($impersonator_info['glpiactive_entity'], $impersonator_info['glpiactive_entity_recursive']);
        }

        Event::log(0, "system", 3, "Impersonate", sprintf(
            __('%1$s stops impersonating user %2$s'),
            $user->fields['name'],
            $impersonate_user
        ));

        return true;
    }

    /**
     * Check if impersonate feature is currently used.
     *
     * @return boolean
     */
    public static function isImpersonateActive()
    {

        return array_key_exists('impersonator_id', $_SESSION);
    }

    /**
     * Return impersonator user id.
     *
     * @return int|null
     */
    public static function getImpersonatorId()
    {
        return self::isImpersonateActive() ? (int) $_SESSION['impersonator_id'] : null;
    }

    /**
     * Check if current connected user password has expired.
     *
     * @return boolean
     */
    public static function mustChangePassword()
    {
        return array_key_exists('glpi_password_expired', $_SESSION);
    }

    /**
     * Get active entity id.
     *
     * @since 9.5
     *
     * @return int
     */
    public static function getActiveEntity()
    {
        return $_SESSION['glpiactive_entity'] ?? 0;
    }

    /**
     * Get actives entities id.
     *
     * @return array<int>
     */
    public static function getActiveEntities(): array
    {
        return $_SESSION['glpiactiveentities'] ?? [];
    }

    /**
     * Filter given entities ID list to return only these tht are matching current active entities in session.
     *
     * @since 10.0.13
     *
     * @param int|int[] $entities_ids
     *
     * @return int|int[]
     */
    public static function getMatchingActiveEntities(/*int|array*/ $entities_ids)/*: int|array*/
    {
        if (
            (int) $entities_ids === -1
            || (is_array($entities_ids) && count($entities_ids) === 1 && (int) reset($entities_ids) === -1)
        ) {
            // Special value that is generally used to fallback to all active entities.
            return $entities_ids;
        }

        if (
            !is_array($entities_ids)
            && !is_int($entities_ids)
            && (!is_string($entities_ids) || !ctype_digit($entities_ids))
        ) {
            // Unexpected value type.
            return [];
        }

        $active_entities_ids = [];
        foreach ($_SESSION['glpiactiveentities'] ?? [] as $active_entity_id) {
            if (
                !is_int($active_entity_id)
                && (!is_string($active_entity_id) || !ctype_digit($active_entity_id))
            ) {
                // Ensure no unexpected value converted to int
                // as it would be converted to `0` and would permit access to root entity
                trigger_error(
                    sprintf('Unexpected value `%s` found in `$_SESSION[\'glpiactiveentities\']`.', $active_entity_id ?? 'null'),
                    E_USER_WARNING
                );
                continue;
            }
            $active_entities_ids[] = (int) $active_entity_id;
        }

        if (!is_array($entities_ids) && in_array((int) $entities_ids, $active_entities_ids, true)) {
            return (int) $entities_ids;
        }

        $filtered = [];
        foreach ((array) $entities_ids as $entity_id) {
            if (
                (is_int($entity_id) || (is_string($entity_id) && ctype_digit($entity_id)))
                && in_array((int) $entity_id, $active_entities_ids, true)
            ) {
                $filtered[] = (int) $entity_id;
            }
        }
        return $filtered;
    }

    /**
     * Get recursive state of active entity selection.
     *
     * @since 9.5.5
     *
     * @return bool
     */
    public static function getIsActiveEntityRecursive(): bool
    {
        return $_SESSION['glpiactive_entity_recursive'] ?? false;
    }

    /**
     * Start session for a given user
     *
     * @param string    $token
     * @param string    $token_type
     * @param int|null  $entities_id
     * @param bool|null $is_recursive
     *
     * @return User|false
     */
    public static function authWithToken(
        string $token,
        string $token_type,
        ?int $entities_id,
        ?bool $is_recursive
    ) {
        $user = new User();

        // Try to load from token
        if (!$user->getFromDBByToken($token, $token_type)) {
            return false;
        }

        $auth = new Auth();
        $auth->auth_succeded = true;
        $auth->user = $user;
        Session::init($auth);

        if (!is_null($entities_id) && !is_null($is_recursive)) {
            self::loadEntity($entities_id, $is_recursive);
        }

        return $user;
    }

    /**
     * Load given entity.
     *
     * @param integer $entities_id  Entity to use
     * @param boolean $is_recursive Whether to load entities recursively or not
     *
     * @return void
     */
    public static function loadEntity($entities_id, $is_recursive): void
    {
        $_SESSION["glpiactive_entity"]           = $entities_id;
        $_SESSION["glpiactive_entity_recursive"] = $is_recursive;
        if ($is_recursive) {
            $entities = getSonsOf("glpi_entities", $entities_id);
        } else {
            $entities = [$entities_id];
        }
        $_SESSION['glpiactiveentities']        = $entities;
        $_SESSION['glpiactiveentities_string'] = "'" . implode("', '", $entities) . "'";
    }

    /**
    * clean what needs to be cleaned on logout
    *
    * @since 10.0.4
    *
    * @return void
    */
    public static function cleanOnLogout()
    {
        Session::destroy();
        //Remove cookie to allow new login
        Auth::setRememberMeCookie('');
    }

    /**
     * Get the current language
     *
     * @return null|string language corresponding to a key of `$CFG_GLPI['languages']` or null if not set
     */
    public static function getLanguage(): ?string
    {
        return $_SESSION['glpilanguage'] ?? null;
    }

    /**
     * Helper function to get the date + time stored in $_SESSION['glpi_currenttime']
     *
     * @return null|string timestamp formated as 'Y-m-d H:i:s' or null if not set
     */
    public static function getCurrentTime(): ?string
    {
        // TODO replace references to $_SESSION['glpi_currenttime'] by a call to this function
        return $_SESSION['glpi_currenttime'] ?? null;
    }

    /**
     * Helper function to get the date stored in $_SESSION['glpi_currenttime']
     *
     * @return null|string
     */
    public static function getCurrentDate(): ?string
    {
        return date('Y-m-d', strtotime(self::getCurrentTime()));
    }

    /**
     * Checks if the GLPI sessions directory can be written to if the PHP session save handler is set to "files".
     * @return bool True if the directory is writable, or if the session save handler is not set to "files".
     */
    public static function canWriteSessionFiles(): bool
    {
        try {
            $session_handler = ini_get('session.save_handler');
        } catch (InfoException $e) {
            $session_handler = false;
        }
        return $session_handler !== false
            && (strtolower($session_handler) !== 'files' || is_writable(GLPI_SESSION_DIR));
    }

    /**
     * Reload the current profile from the database
     * Update the session variable accordingly
     *
     * @return void
     */
    public static function reloadCurrentProfile(): void
    {
        $current_profile_id = $_SESSION['glpiactiveprofile']['id'];

        $profile = new Profile();
        if ($profile->getFromDB($current_profile_id)) {
            $profile->cleanProfile();
            $_SESSION['glpiactiveprofile'] = array_merge(
                $_SESSION['glpiactiveprofile'],
                $profile->fields
            );
        }
    }

    public static function isAuthenticated(): bool
    {
        return self::getLoginUserID() !== false;
    }

    /**
     * Get a SessionInfo object with the current session information.
     *
     * @return ?SessionInfo
     */
    public static function getCurrentSessionInfo(): ?SessionInfo
    {
        if (!self::isAuthenticated()) {
            return null;
        }

        return new SessionInfo(
            user_id   : self::getLoginUserID(),
            group_ids : $_SESSION['glpigroups'] ?? [],
            profile_id: $_SESSION['glpiactiveprofile']['id'],
            active_entities_ids: $_SESSION['glpiactiveentities'],
            current_entity_id: self::getActiveEntity(),
        );
    }

    public static function getCurrentProfile(): Profile
    {
        $profile_id = $_SESSION['glpiactiveprofile']['id'] ?? null;
        if ($profile_id === null) {
            throw new RuntimeException("No active session");
        }

        $profile = Profile::getById($profile_id);
        if (!$profile instanceof Profile) {
            throw new RuntimeException("Failed to load profile: $profile_id");
        }

        return $profile;
    }

    /**
     * Runs a callable with the right checks disabled.
     * @return mixed|void The return value of the callable.
     * @throws Throwable Any throwable that was caught from the callable if any.
     */
    public static function callAsSystem(callable $fn)
    {
        $caught_throwable = null;
        try {
            self::$bypass_right_checks = true;
            return $fn();
        } catch (Throwable $e) {
            $caught_throwable = $e;
        } finally {
            self::$bypass_right_checks = false;
        }
        throw $caught_throwable;
    }

    /**
     * @return bool Whether the right checks are disabled.
     * @internal No backwards compatibility promise.
     */
    public static function isRightChecksDisabled(): bool
    {
        return self::$bypass_right_checks;
    }

    /**
     * Is locale RTL
     * See native PHP 8.5 function locale_is_right_to_left
     *
     * @param $locale
     *
     * @return bool
     */
    public static function isRTL($locale): bool
    {
        if (function_exists('locale_is_right_to_left')) {
            return locale_is_right_to_left($locale);
        }

        return (bool) preg_match('/^(?:ar|he|fa|ur|ps|sd|ug|ckb|yi|dv|ku_arab|ku-arab)(?:[_-].*)?$/i', $locale);
    }
}
