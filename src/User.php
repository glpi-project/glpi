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
use Glpi\Dashboard\Dashboard;
use Glpi\Dashboard\Filter;
use Glpi\Exception\ForgetPasswordException;
use Glpi\Plugin\Hooks;
use Glpi\Toolbox\Sanitizer;
use Sabre\VObject;

class User extends CommonDBTM
{
    use Glpi\Features\Clonable {
        Glpi\Features\Clonable::computeCloneName as baseComputeCloneName;
    }
    use Glpi\Features\TreeBrowse;

   // From CommonDBTM
    public $dohistory         = true;
    public $history_blacklist = ['date_mod', 'date_sync', 'last_login',
        'publicbookmarkorder', 'privatebookmarkorder'
    ];

    private $must_process_ruleright = false;

   // NAME FIRSTNAME ORDER TYPE
    const REALNAME_BEFORE   = 0;
    const FIRSTNAME_BEFORE  = 1;

    const IMPORTEXTAUTHUSERS  = 1024;
    const READAUTHENT         = 2048;
    const UPDATEAUTHENT       = 4096;

    public static $rightname = 'user';

    public static $undisclosedFields = [
        'password',
        'personal_token',
        'api_token',
        'cookie_token',
    ];

    private $entities = null;

    public function getCloneRelations(): array
    {
        return [
            Profile_User::class,
            Group_User::class
        ];
    }

    public function prepareInputForClone($input)
    {
        unset($input['last_login']);
        unset($input['password_forget_token']);
        unset($input['password_forget_token_date']);
        unset($input['personal_token']);
        unset($input['personal_token_date']);
        unset($input['api_token']);
        unset($input['api_token_date']);
        unset($input['cookie_token']);
        unset($input['cookie_token_date']);
        return $input;
    }

    public function post_clone($source, $history)
    {
       //FIXME? clone config
    }

    public static function getTypeName($nb = 0)
    {
        return _n('User', 'Users', $nb);
    }

    public static function getMenuShorcut()
    {
        return 'u';
    }

    public static function getAdditionalMenuOptions()
    {

        if (Session::haveRight('user', self::IMPORTEXTAUTHUSERS)) {
            return [
                'ldap' => [
                    'icon'  => AuthLDAP::getIcon(),
                    'title' => AuthLDAP::getTypeName(Session::getPluralNumber()),
                    'page'  => '/front/ldap.php',
                ],
            ];
        }
        return false;
    }


    public function canViewItem()
    {
        if (
            Session::canViewAllEntities()
            || Session::haveAccessToOneOfEntities($this->getEntities())
        ) {
            return true;
        }
        return false;
    }


    public function canCreateItem()
    {

       // Will be created from form, with selected entity/profile
        if (
            isset($this->input['_profiles_id']) && ($this->input['_profiles_id'] > 0)
            && Profile::currentUserHaveMoreRightThan([$this->input['_profiles_id']])
            && isset($this->input['_entities_id'])
            && Session::haveAccessToEntity($this->input['_entities_id'])
        ) {
            return true;
        }
       // Will be created with default value
        if (
            Session::haveAccessToEntity(0) // Access to root entity (required when no default profile)
            || (Profile::getDefault() > 0)
        ) {
            return true;
        }

        if (
            ($_SESSION['glpiactive_entity'] > 0)
            && (Profile::getDefault() == 0)
        ) {
            echo "<div class='tab_cadre_fixe warning'>" .
                __('You must define a default profile to create a new user') . "</div>";
        }

        return false;
    }


    public function canUpdateItem()
    {

        $entities = Profile_User::getUserEntities($this->fields['id'], false);
        if (
            Session::canViewAllEntities()
            || Session::haveAccessToOneOfEntities($entities)
        ) {
            return true;
        }
        return false;
    }


    public function canDeleteItem()
    {
        if ($this->isLastSuperAdminUser()) {
            return false;
        }

        //prevent delete / purge from API
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;
        if ($this->fields['id'] == $CFG_GLPI['system_user']) {
            return false;
        }

        if (
            Session::canViewAllEntities()
            || Session::haveAccessToAllOfEntities($this->getEntities())
        ) {
            return true;
        }
        return false;
    }


    public function canPurgeItem()
    {
        return $this->canDeleteItem();
    }


    public function isEntityAssign()
    {
       // glpi_users.entities_id is only a pref.
        return false;
    }


    public static function isMassiveActionAllowed(int $items_id): bool
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;
        return !($CFG_GLPI['system_user'] == $items_id);
    }


    /**
     * Compute preferences for the current user mixing config and user data.
     *
     * @return void
     */
    public function computePreferences()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (isset($this->fields['id'])) {
            foreach ($CFG_GLPI['user_pref_field'] as $f) {
                if (array_key_exists($f, $CFG_GLPI) && (!array_key_exists($f, $this->fields) || is_null($this->fields[$f]))) {
                    $this->fields[$f] = $CFG_GLPI[$f];
                }
            }
        }
       /// Specific case for show_count_on_tabs : global config can forbid
        if ($CFG_GLPI['show_count_on_tabs'] == -1) {
            $this->fields['show_count_on_tabs'] = 0;
        }

        // Fallback for invalid language
        if (!isset($CFG_GLPI['languages'][$this->fields["language"]])) {
            $this->fields["language"] = $CFG_GLPI["language"];
        }
    }

    /**
     * Cache preferences for the current user in session.
     *
     * @return void
     */
    final public function loadPreferencesInSession(): void
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $this->computePreferences();
        foreach ($CFG_GLPI['user_pref_field'] as $field) {
            if (isset($this->fields[$field])) {
                $_SESSION["glpi$field"] = $this->fields[$field];
            }
        }
    }

    /**
     * Load minimal session for user.
     *
     * @param integer $entities_id  Entity to use
     * @param boolean $is_recursive Whether to load entities recursivly or not
     *
     * @return void
     *
     * @since 0.83.7
     */
    public function loadMinimalSession($entities_id, $is_recursive)
    {
        if (isset($this->fields['id']) && !isset($_SESSION["glpiID"])) {
            Session::destroy();
            Session::start();
            $_SESSION["glpiID"]                      = $this->fields['id'];
            $_SESSION["glpi_use_mode"]               = Session::NORMAL_MODE;
            Session::loadEntity($entities_id, $is_recursive);
            $this->loadPreferencesInSession();
            Session::loadGroups();
            Session::loadLanguage();
        }
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        switch ($item->getType()) {
            case __CLASS__:
                $ong    = [];
                $ong[1] = __('Used items');
                $ong[2] = __('Managed items');
                return $ong;

            case 'Preference':
                return __('Main');
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        switch (get_class($item)) {
            case self::class:
                $item->showItems($tabnum == 2);
                return true;

            case Preference::class:
                $user = new self();
                $user->showMyForm(
                    $CFG_GLPI['root_doc'] . "/front/preference.php",
                    Session::getLoginUserID()
                );
                return true;
        }
        return false;
    }


    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong);

        $config = Config::getConfigurationValues('core');
        if ($config['system_user'] == $this->getID()) {
            return $ong;
        }

        $this->addImpactTab($ong, $options);
        $this->addStandardTab('Profile_User', $ong, $options);
        $this->addStandardTab('Group_User', $ong, $options);
        $this->addStandardTab('Config', $ong, $options);
        $this->addStandardTab(__CLASS__, $ong, $options);
        $this->addStandardTab('Ticket', $ong, $options);
        $this->addStandardTab('Item_Problem', $ong, $options);
        $this->addStandardTab('Change_Item', $ong, $options);
        $this->addStandardTab('Document_Item', $ong, $options);
        $this->addStandardTab('Reservation', $ong, $options);
        $this->addStandardTab('Auth', $ong, $options);
        $this->addStandardTab('ManualLink', $ong, $options);
        $this->addStandardTab('Certificate_Item', $ong, $options);
        $this->addStandardTab('Log', $ong, $options);

        return $ong;
    }


    public function post_getEmpty()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $this->fields["is_active"] = 1;
        if (isset($CFG_GLPI["language"])) {
            $this->fields['language'] = $CFG_GLPI["language"];
        } else {
            $this->fields['language'] = "en_GB";
        }
    }


    public function pre_deleteItem()
    {
        /** @var \DBmysql $DB */
        global $DB;

        $entities = $this->getEntities();
        $view_all = Session::canViewAllEntities();
        // Have right on all entities ?
        $all      = true;
        if (!$view_all) {
            foreach ($entities as $ent) {
                if (!Session::haveAccessToEntity($ent)) {
                    $all = false;
                }
            }
        }
        if ($all) { // Mark as deleted
            return true;
        }

        // only delete profile
        foreach ($entities as $ent) {
            if (Session::haveAccessToEntity($ent)) {
                $DB->delete(
                    'glpi_profiles_users',
                    [
                        'users_id'     => $this->fields['id'],
                        'entities_id'  => $ent
                    ]
                );
            }
        }
        return false;
    }


    public function cleanDBonPurge()
    {

        /** @var \DBmysql $DB */
        global $DB;

       // ObjectLock does not extends CommonDBConnexity
        $ol = new ObjectLock();
        $ol->deleteByCriteria(['users_id' => $this->fields['id']]);

       // Reminder does not extends CommonDBConnexity
        $r = new Reminder();
        $r->deleteByCriteria(['users_id' => $this->fields['id']]);
        $reminder_translation = new ReminderTranslation();
        $reminder_translation->deleteByCriteria(['users_id' => $this->fields['id']]);

       // Delete private bookmark
        $ss = new SavedSearch();
        $ss->deleteByCriteria(
            [
                'users_id'   => $this->fields['id'],
                'is_private' => 1,
            ]
        );

       // Set no user to public bookmark
        $DB->update(
            SavedSearch::getTable(),
            [
                'users_id' => 0
            ],
            [
                'users_id' => $this->fields['id']
            ]
        );

       // Set no user to consumables
        $DB->update(
            'glpi_consumables',
            [
                'items_id' => 0,
                'itemtype' => 'NULL',
                'date_out' => 'NULL'
            ],
            [
                'items_id' => $this->fields['id'],
                'itemtype' => 'User'
            ]
        );

        $this->deleteChildrenAndRelationsFromDb(
            [
                Change_User::class,
                Group_User::class,
                Item_Kanban::class,
                KnowbaseItem_User::class,
                Problem_User::class,
                Profile_User::class,
                ProjectTaskTeam::class,
                ProjectTeam::class,
                Reminder_User::class,
                RSSFeed_User::class,
                SavedSearch_User::class,
                Ticket_User::class,
                UserEmail::class,
            ]
        );

        if ($this->fields['id'] > 0) { // Security
            // DisplayPreference does not extends CommonDBConnexity
            $dp = new DisplayPreference();
            $dp->deleteByCriteria(['users_id' => $this->fields['id']]);

            // Delete private dashboards and private dashboard filters
            $dashboard = new Dashboard();
            $dashboard->deleteByCriteria(['users_id' => $this->fields['id']]);
            $dashboard_filters = new Filter();
            $dashboard_filters->deleteByCriteria(['users_id' => $this->fields['id']]);
        }

        $this->dropPictureFiles($this->fields['picture']);

       // Ticket rules use various _users_id_*
        Rule::cleanForItemAction($this, '_users_id%');
        Rule::cleanForItemCriteria($this, '_users_id%');

       // Alert does not extends CommonDBConnexity
        $alert = new Alert();
        $alert->cleanDBonItemDelete($this->getType(), $this->fields['id']);
    }


    /**
     * Retrieve a user from the database using its login.
     *
     * @param string $name Login of the user
     *
     * @return boolean
     */
    public function getFromDBbyName($name)
    {
        return $this->getFromDBByCrit(['name' => (string)$name]);
    }

    /**
     * Retrieve a user from the database using its login.
     *
     * @param string  $name     Login of the user
     * @param integer $authtype Auth type (see Auth constants)
     * @param integer $auths_id ID of auth server
     *
     * @return boolean
     */
    public function getFromDBbyNameAndAuth($name, $authtype, $auths_id)
    {
        return $this->getFromDBByCrit([
            'name'     => $name,
            'authtype' => $authtype,
            'auths_id' => $auths_id
        ]);
    }

    /**
     * Retrieve a user from the database using value of the sync field.
     *
     * @param string $value Value of the sync field
     *
     * @return boolean
     */
    public function getFromDBbySyncField($value)
    {
        return $this->getFromDBByCrit(['sync_field' => $value]);
    }

    /**
     * Retrieve a user from the database using it's dn.
     *
     * @param string $user_dn dn of the user
     *
     * @return boolean
     */
    public function getFromDBbyDn($user_dn)
    {
        $raw_user_dn = Sanitizer::unsanitize($user_dn);

        /**
         * We use the 'user_dn_hash' field instead of 'user_dn' for performance reasons.
         * The 'user_dn_hash' field is a hashed version of the 'user_dn' field
         * and is indexed in the database, making it faster to search.
         */
        return $this->getFromDBByCrit([
            'user_dn_hash' => md5($raw_user_dn)
        ]);
    }

    /**
     * Get users ids matching the given email
     *
     * @param string $email     Email to search for
     * @param array  $condition Extra conditions
     *
     * @return array Found users ids
     */
    public static function getUsersIdByEmails(string $email, array $condition = []): array
    {
        /** @var \DBmysql $DB */
        global $DB;

        $query = [
            'SELECT'    => self::getTable() . '.id',
            'FROM'      => self::getTable(),
            'LEFT JOIN' => [
                UserEmail::getTable() => [
                    'FKEY' => [
                        self::getTable()      => 'id',
                        UserEmail::getTable() => self::getForeignKeyField()
                    ]
                ]
            ],
            'WHERE' =>
                [
                    'RAW' => [
                        'LOWER(' . UserEmail::getTable() . '.email' . ')'  => Toolbox::strtolower($email)
                    ]
                ]
             + $condition
        ];

        $data = iterator_to_array($DB->request($query));
        return array_column($data, 'id');
    }

    /**
     * Get the number of users using the given email
     *
     * @param string $email     Email to search for
     * @param array  $condition Extra conditions
     *
     * @return int Number of users found
     */
    public static function countUsersByEmail($email, $condition = []): int
    {
        return count(self::getUsersIdByEmails($email, $condition));
    }


    /**
     * Retrieve a user from the database using its email.
     *
     * @since 9.3 Can pass condition as a parameter
     *
     * @param string $email     user email
     * @param array  $condition add condition
     *
     * @return boolean
     */
    public function getFromDBbyEmail($email, $condition = [])
    {
        $ids = self::getUsersIdByEmails($email, $condition);

        if (count($ids) == 1) {
            return $this->getFromDB(current($ids));
        }

        return false;
    }


    /**
     * Get the default email of the user.
     *
     * @return string
     */
    public function getDefaultEmail()
    {

        if ($this->isNewItem()) {
            return '';
        }

        return UserEmail::getDefaultForUser($this->fields['id']);
    }


    /**
     * Get all emails of the user.
     *
     * @return string[]
     */
    public function getAllEmails()
    {

        if (!isset($this->fields['id'])) {
            return [];
        }
        return UserEmail::getAllForUser($this->fields['id']);
    }


    /**
     * Check if the email is attached to the current user.
     *
     * @param string $email
     *
     * @return boolean
     */
    public function isEmail($email)
    {

        if (!isset($this->fields['id'])) {
            return false;
        }
        return UserEmail::isEmailForUser($this->fields['id'], $email);
    }


    /**
     * Retrieve a user from the database using its personal token.
     *
     * @param string $token user token
     * @param string $field the field storing the token
     *
     * @return boolean
     */
    public function getFromDBbyToken($token, $field = 'personal_token')
    {
        if (!is_string($token)) {
            trigger_error(
                sprintf('Unexpected token value received: "string" expected, received "%s".', gettype($token)),
                E_USER_WARNING
            );
            return false;
        }

        $fields = ['personal_token', 'api_token'];
        if (!in_array($field, $fields)) {
            trigger_error(
                'User::getFromDBbyToken() can only be called with $field parameter with theses values: \'' . implode('\', \'', $fields) . '\'',
                E_USER_WARNING
            );
            return false;
        }

        return $this->getFromDBByCrit([$this->getTable() . ".$field" => $token]);
    }

    public static function unsetUndisclosedFields(&$fields)
    {
        parent::unsetUndisclosedFields($fields);

        if (
            array_key_exists('password_forget_token', $fields)
            || array_key_exists('password_forget_token_date', $fields)
        ) {
            if (array_key_exists('id', $fields)) {
                // `id` is present mainly when the whole object is fetched.
                // In this case, we must show the token only if the user is allowed to read it.
                $user = new self();
                $can_see_token = Session::getLoginUserID() === $fields['id']
                    || (
                        $user->can($fields['id'], UPDATE)
                        && $user->currentUserHaveMoreRightThan($fields['id'])
                    );
            } else {
                // `id` may be missing when a partial object is fetch.
                // In this case, we cannot ensure that the user is allowed to read the token
                // and we must NOT show it.
                $can_see_token = false;
            }
            if (!$can_see_token) {
                unset($fields['password_forget_token'], $fields['password_forget_token_date']);
            }
        }
    }

    public function prepareInputForAdd($input)
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (isset($input['_stop_import'])) {
            return false;
        }

        if (empty($input['name']) || !Auth::isValidLogin(stripslashes($input['name']))) {
            Session::addMessageAfterRedirect(
                __('The login is not valid. Unable to add the user.'),
                false,
                ERROR
            );
            return false;
        }

       // avoid xss (picture field is autogenerated)
        if (isset($input['picture'])) {
            $input['picture'] = 'NULL';
        }

        if (!isset($input["authtype"])) {
            $input["authtype"] = Auth::DB_GLPI;
        }

        if (!isset($input["auths_id"])) {
            $input["auths_id"] = 0;
        }

       // Check if user does not exists
        $iterator = $DB->request([
            'FROM'   => $this->getTable(),
            'WHERE'  => [
                'name'      => $input['name'],
                'authtype'  => $input['authtype'],
                'auths_id'  => $input['auths_id']
            ],
            'LIMIT'  => 1
        ]);

        if (count($iterator)) {
            Session::addMessageAfterRedirect(
                __('Unable to add. The user already exists.'),
                false,
                ERROR
            );
            return false;
        }

        if (isset($input["password2"])) {
            if (empty($input["password"])) {
                unset($input["password"]);
            } else {
                if ($input["password"] == $input["password2"]) {
                    if (Config::validatePassword($input["password"])) {
                        $input["password"]
                        = Auth::getPasswordHash(Sanitizer::unsanitize($input["password"]));

                        $input['password_last_update'] = $_SESSION['glpi_currenttime'];
                    } else {
                        unset($input["password"]);
                    }
                    unset($input["password2"]);
                } else {
                    Session::addMessageAfterRedirect(
                        __('Error: the two passwords do not match'),
                        false,
                        ERROR
                    );
                    return false;
                }
            }
        }

        if (isset($input["_extauth"])) {
            $input["password"] = "";
        }

       // Force DB default values : not really needed
        if (!isset($input["is_active"])) {
            $input["is_active"] = 1;
        }

        if (!isset($input["is_deleted"])) {
            $input["is_deleted"] = 0;
        }

        if (!isset($input["entities_id"])) {
            $input["entities_id"] = 0;
        }

        if (!isset($input["profiles_id"])) {
            $input["profiles_id"] = 0;
        }

        return $input;
    }

    public function computeCloneName(
        string $current_name,
        ?int $copy_index = null
    ): string {
        return Toolbox::slugify(
            $this->baseComputeCloneName($current_name, $copy_index)
        );
    }

    public function pre_addInDB()
    {
        // Hash user_dn if set
        if (isset($this->input['user_dn']) && is_string($this->input['user_dn']) && strlen($this->input['user_dn']) > 0) {
            $this->input['user_dn_hash'] = md5(Sanitizer::unsanitize($this->input['user_dn']));
        }
    }

    public function post_addItem()
    {

        $this->updateUserEmails();
        $this->syncLdapGroups();
        $this->syncDynamicEmails();

        $this->applyGroupsRules();
        $rulesplayed = $this->applyRightRules();
        $picture     = $this->syncLdapPhoto();

       //add picture in user fields
        if (!empty($picture)) {
            $this->update(['id'      => $this->fields['id'],
                'picture' => $picture
            ]);
        }

       // Add default profile
        if (!$rulesplayed) {
            $affectation = [];
            if (
                isset($this->input['_profiles_id']) && $this->input['_profiles_id']
                && Profile::currentUserHaveMoreRightThan([$this->input['_profiles_id']])
            ) {
                $profile                   = $this->input['_profiles_id'];
               // Choosen in form, so not dynamic
                $affectation['is_dynamic'] = 0;
            } else {
                $profile                   = Profile::getDefault();
               // Default right as dynamic. If dynamic rights are set it will disappear.
                $affectation['is_dynamic'] = 1;
                $affectation['is_default_profile'] = 1;
            }

            if ($profile) {
                if (isset($this->input["_entities_id"])) {
                   // entities_id (user's pref) always set in prepareInputForAdd
                   // use _entities_id for default right
                    $affectation["entities_id"] = $this->input["_entities_id"];
                } else if (isset($_SESSION['glpiactive_entity'])) {
                    $affectation["entities_id"] = $_SESSION['glpiactive_entity'];
                } else {
                    $affectation["entities_id"] = 0;
                }
                if (isset($this->input["_is_recursive"])) {
                    $affectation["is_recursive"] = $this->input["_is_recursive"];
                } else {
                    $affectation["is_recursive"] = 0;
                }

                $affectation["profiles_id"]  = $profile;
                $affectation["users_id"]     = $this->fields["id"];
                $right                       = new Profile_User();
                $right->add($affectation);
            }
        }
    }


    public function prepareInputForUpdate($input)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

       // avoid xss (picture name is autogenerated when uploading/synchronising the picture)
        unset($input['picture']);

       //picture manually uploaded by user
        if (isset($input["_blank_picture"]) && $input["_blank_picture"]) {
            self::dropPictureFiles($this->fields['picture']);
            $input['picture'] = 'NULL';
        } else {
            $newPicture = false;
            if (!isAPI()) {
                if (isset($input["_picture"][0]) && !empty($input["_picture"][0])) {
                    $input["_picture"] = $input["_picture"][0];
                }
            }
            if (isset($input["_picture"]) && !empty($input["_picture"])) {
                $newPicture = true;
            }
            if ($newPicture) {
                if (!$fullpath = realpath(GLPI_TMP_DIR . "/" . $input["_picture"])) {
                    return false;
                }
                if (!str_starts_with($fullpath, realpath(GLPI_TMP_DIR))) {
                    trigger_error(sprintf('Invalid picture path `%s`', $input["_picture"]), E_USER_WARNING);
                }
                if (Document::isImage($fullpath)) {
                   // Unlink old picture (clean on changing format)
                    self::dropPictureFiles($this->fields['picture']);
                   // Move uploaded file
                    $filename     = uniqid($this->fields['id'] . '_');
                    $sub          = substr($filename, -2); /* 2 hex digit */

                   // output images with possible transparency to png, other to jpg
                    $extension = strtolower(pathinfo($fullpath, PATHINFO_EXTENSION));
                    $extension = in_array($extension, ['png', 'gif'])
                    ? 'png'
                    : 'jpg';

                    @mkdir(GLPI_PICTURE_DIR . "/$sub");
                    $picture_path = GLPI_PICTURE_DIR  . "/{$sub}/{$filename}.{$extension}";
                    self::dropPictureFiles("{$sub}/{$filename}.{$extension}");

                    if (Document::renameForce($fullpath, $picture_path)) {
                        Session::addMessageAfterRedirect(__('The file is valid. Upload is successful.'));
                        // For display
                        $input['picture'] = "{$sub}/{$filename}.{$extension}";

                        //prepare a thumbnail
                        $thumb_path = GLPI_PICTURE_DIR . "/{$sub}/{$filename}_min.{$extension}";
                        Toolbox::resizePicture($picture_path, $thumb_path);
                    } else {
                        Session::addMessageAfterRedirect(
                            __('Moving temporary file failed.'),
                            false,
                            ERROR
                        );
                        @unlink($fullpath);
                    }
                } else {
                    Session::addMessageAfterRedirect(
                        __('The file is not an image file.'),
                        false,
                        ERROR
                    );
                    @unlink($fullpath);
                }
            } else {
               //ldap jpegphoto synchronisation.
                $picture = $this->syncLdapPhoto();
                if (!empty($picture)) {
                    $input['picture'] = $picture;
                }
            }
        }

        if (isset($input["password2"])) {
           // Empty : do not update
            if (empty($input["password"])) {
                unset($input["password"]);
            } else {
                if ($input["password"] == $input["password2"]) {
                   // Check right : my password of user with lesser rights
                    if (
                        isset($input['id'])
                        && !Auth::checkPassword($input['password'], $this->fields['password']) // Validate that password is not same as previous
                        && Config::validatePassword($input["password"])
                        && (($input['id'] == Session::getLoginUserID())
                        || $this->currentUserHaveMoreRightThan($input['id'])
                        // Permit to change password with token and email
                        || (($input['password_forget_token'] == $this->fields['password_forget_token'])
                           && (abs(strtotime($_SESSION["glpi_currenttime"])
                               - strtotime($this->fields['password_forget_token_date'])) < DAY_TIMESTAMP)))
                    ) {
                        $input["password"]
                        = Auth::getPasswordHash(Sanitizer::unsanitize($input["password"]));

                        $input['password_last_update'] = $_SESSION["glpi_currenttime"];
                    } else {
                        unset($input["password"]);
                    }
                    unset($input["password2"]);
                } else {
                    Session::addMessageAfterRedirect(
                        __('Error: the two passwords do not match'),
                        false,
                        ERROR
                    );
                    return false;
                }
            }
        } else if (isset($input["password"])) { // From login
            unset($input["password"]);
        }

        if (
            Session::getLoginUserID() !== false
            && ((int) $input['id']) !== Session::getLoginUserID()
        ) {
            // Security checks to prevent an unathorized user to update sensitive fields of another user.
            // These checks are done only if a "user" session is active.
            $protected_input_keys = [
                // Security tokens
                'api_token',
                '_reset_api_token',
                'cookie_token',
                'password_forget_token',
                'personal_token',
                '_reset_personal_token',

                // Prevent changing emails that could then be used to get the password reset token
                '_useremails',
                '_emails',

                // Prevent disabling another user account
                'is_active',
            ];
            if (
                count(array_intersect($protected_input_keys, array_keys($input))) > 0
                && !$this->currentUserHaveMoreRightThan($input['id'])
            ) {
                foreach ($protected_input_keys as $input_key) {
                    unset($input[$input_key]);
                }
            }
        }

       // blank password when authtype changes
        if (
            isset($input["authtype"])
            && $input["authtype"] != Auth::DB_GLPI
            && $input["authtype"] != $this->getField('authtype')
        ) {
            $input["password"] = "";
        }

       // Update User in the database
        if (
            !isset($input["id"])
            && isset($input["name"])
        ) {
            if ($this->getFromDBbyName($input["name"])) {
                $input["id"] = $this->fields["id"];
            }
        }

        if (
            isset($input["entities_id"])
            && (Session::getLoginUserID() == $input['id'])
        ) {
            $_SESSION["glpidefault_entity"] = $input["entities_id"];
        }

       // Security on default profile update
        if (isset($input['profiles_id'])) {
            if (!in_array($input['profiles_id'], Profile_User::getUserProfiles($input['id']))) {
                unset($input['profiles_id']);
            }
        }

       // Security on default entity  update
        if (isset($input['entities_id'])) {
            if (
                ($input['entities_id'] > 0)
                && (!in_array($input['entities_id'], Profile_User::getUserEntities($input['id'])))
            ) {
                unset($input['entities_id']);
            } elseif ($input['entities_id'] == -1) {
                $input['entities_id'] = 'NULL';
            }
        }

       // Security on default group  update
        if (
            isset($input['groups_id'])
            && !Group_User::isUserInGroup($input['id'], $input['groups_id'])
        ) {
            unset($input['groups_id']);
        }

        if (
            isset($input['_reset_personal_token'])
            && $input['_reset_personal_token']
        ) {
            $input['personal_token']      = self::getUniqueToken('personal_token');
            $input['personal_token_date'] = $_SESSION['glpi_currenttime'];
        }

        if (
            isset($input['_reset_api_token'])
            && $input['_reset_api_token']
        ) {
            $input['api_token']      = self::getUniqueToken('api_token');
            $input['api_token_date'] = $_SESSION['glpi_currenttime'];
        }

       // Manage preferences fields
        if (Session::getLoginUserID() == $input['id']) {
            if (
                isset($input['use_mode'])
                && ($_SESSION['glpi_use_mode'] !=  $input['use_mode'])
            ) {
                $_SESSION['glpi_use_mode'] = $input['use_mode'];
                unset($_SESSION['glpimenu']); // Force menu regeneration
               //Session::loadLanguage();
            }
        }

        foreach ($CFG_GLPI['user_pref_field'] as $f) {
            if (isset($input[$f])) {
                $pref_value = Sanitizer::dbUnescape($input[$f]);
                if (Session::getLoginUserID() == $input['id']) {
                    if ($_SESSION["glpi$f"] != $pref_value) {
                        $_SESSION["glpi$f"] = $pref_value;
                      // reinit translations
                        if ($f == 'language') {
                             $_SESSION['glpi_dropdowntranslations'] = DropdownTranslation::getAvailableTranslations($_SESSION["glpilanguage"]);
                             unset($_SESSION['glpimenu']);
                        }
                    }
                }
                if ($pref_value == $CFG_GLPI[$f]) {
                    $input[$f] = "NULL";
                }
            }
        }

        if (isset($input['language']) && GLPI_DEMO_MODE) {
            unset($input['language']);
        }

        if (array_key_exists('timezone', $input) && empty($input['timezone'])) {
            $input['timezone'] = 'NULL';
        }

        if (
            $this->fields['is_active'] == true
            && isset($input['is_active'])
            && $input['is_active'] == false // User is no longer active
            && $this->isLastSuperAdminUser()
        ) {
            unset($input['is_active']);
            Session::addMessageAfterRedirect(
                __("Can't set user as inactive as it is the only remaining super administrator."),
                false,
                ERROR
            );
        }

        return $input;
    }


    public function post_updateItem($history = true)
    {
       //handle timezone change for current user
        if ($this->fields['id'] == Session::getLoginUserID()) {
            if (null == $this->fields['timezone'] || 'null' === strtolower($this->fields['timezone'])) {
                unset($_SESSION['glpi_tz']);
            } else {
                $_SESSION['glpi_tz'] = $this->fields['timezone'];
            }
        }

        $this->updateUserEmails();
        $this->syncLdapGroups();
        $this->syncDynamicEmails();
        $this->applyGroupsRules();
        $this->applyRightRules();

        if (in_array('password', $this->updates)) {
            $alert = new Alert();
            $alert->deleteByCriteria(
                [
                    'itemtype' => $this->getType(),
                    'items_id' => $this->fields['id'],
                ],
                true
            );
        }
    }

    /**
     * Apply rules to determine dynamic rights of the user.
     *
     * @return boolean true if rules are applied, false otherwise
     */
    public function applyRightRules()
    {

        $return = false;

        if (
            $this->must_process_ruleright === true
        ) {
            $dynamic_profiles = Profile_User::getForUser($this->fields["id"], true);

            if (
                isset($this->fields["id"])
                && ($this->fields["id"] > 0)
                && isset($this->input["_ldap_rules"])
                && count($this->input["_ldap_rules"])
            ) {
               //and add/update/delete only if it's necessary !
                if (isset($this->input["_ldap_rules"]["rules_entities_rights"])) {
                    $entities_rules = $this->input["_ldap_rules"]["rules_entities_rights"];
                } else {
                    $entities_rules = [];
                }

                if (isset($this->input["_ldap_rules"]["rules_entities"])) {
                    $entities = $this->input["_ldap_rules"]["rules_entities"];
                } else {
                    $entities = [];
                }

                if (isset($this->input["_ldap_rules"]["rules_rights"])) {
                    $rights = $this->input["_ldap_rules"]["rules_rights"];
                } else {
                    $rights = [];
                }

                $retrieved_dynamic_profiles = [];

               //For each affectation -> write it in DB
                foreach ($entities_rules as $entity) {
                   //Multiple entities assignation
                    if (is_array($entity[0])) {
                        foreach ($entity[0] as $ent) {
                            $unicity = $ent . "-" . $entity[1] . "-" . $entity[2];
                            $retrieved_dynamic_profiles[$unicity] = [
                                'entities_id'  => $ent,
                                'profiles_id'  => $entity[1],
                                'is_recursive' => $entity[2],
                                'users_id'     => $this->fields['id'],
                                'is_dynamic'   => 1,
                            ];
                        }
                    } else {
                        $unicity = $entity[0] . "-" . $entity[1] . "-" . $entity[2];
                        $retrieved_dynamic_profiles[$unicity] = [
                            'entities_id'  => $entity[0],
                            'profiles_id'  => $entity[1],
                            'is_recursive' => $entity[2],
                            'users_id'     => $this->fields['id'],
                            'is_dynamic'   => 1,
                        ];
                    }
                }

                if (
                    (count($entities) > 0)
                    && (count($rights) == 0)
                ) {
                    if ($def_prof = Profile::getDefault()) {
                        $rights[] = $def_prof;
                    }
                }

                if (
                    (count($rights) > 0)
                    && (count($entities) > 0)
                ) {
                    foreach ($rights as $right) {
                        foreach ($entities as $entity) {
                            $unicity = $entity[0] . "-" . $right . "-" . $entity[1];
                            $retrieved_dynamic_profiles[$unicity] = [
                                'entities_id'  => $entity[0],
                                'profiles_id'  => $right,
                                'is_recursive' => $entity[1],
                                'users_id'     => $this->fields['id'],
                                'is_dynamic'   => 1,
                            ];
                        }
                    }
                }

               // Compare retrived profiles to existing ones : clean arrays to do purge and add
                if (count($retrieved_dynamic_profiles)) {
                    foreach ($retrieved_dynamic_profiles as $keyretr => $retr_profile) {
                        foreach ($dynamic_profiles as $keydb => $db_profile) {
                             // Found existing profile : unset values in array
                            if (
                                ($db_profile['entities_id']  == $retr_profile['entities_id'])
                                && ($db_profile['profiles_id']  == $retr_profile['profiles_id'])
                                && ($db_profile['is_recursive'] == $retr_profile['is_recursive'])
                            ) {
                                unset($retrieved_dynamic_profiles[$keyretr]);
                                unset($dynamic_profiles[$keydb]);
                            }
                        }
                    }
                }

               // Add new dynamic profiles
                if (count($retrieved_dynamic_profiles)) {
                    $right = new Profile_User();
                    foreach ($retrieved_dynamic_profiles as $keyretr => $retr_profile) {
                        $right->add($retr_profile);
                    }
                }

               //Unset all the temporary tables
                unset($this->input["_ldap_rules"]);

                $return = true;
            } else if (count($dynamic_profiles) == 1) {
                $dynamic_profile = reset($dynamic_profiles);

               // If no rule applied and only one dynamic profile found, check if
               // it is the default profile
                if ($dynamic_profile['is_default_profile'] == true) {
                    $default_profile = Profile::getDefault();

                   // Remove from to be deleted list
                    $dynamic_profiles = [];

                   // Update profile if need to match the current default profile
                    if ($dynamic_profile['profiles_id'] !== $default_profile) {
                        $pu = new Profile_User();
                        $dynamic_profile['profiles_id'] = $default_profile;
                        $pu->add($dynamic_profile);
                        $pu->delete([
                            'id' => $dynamic_profile['id']
                        ]);
                    }
                }
            }

           // Delete old dynamic profiles
            if (count($dynamic_profiles)) {
                $right = new Profile_User();
                foreach ($dynamic_profiles as $keydb => $db_profile) {
                    $right->delete($db_profile);
                }
            }
            $this->must_process_ruleright = false;
        }
        return $return;
    }


    /**
     * Synchronise LDAP group of the user.
     *
     * @return void
     */
    public function syncLdapGroups()
    {
        /** @var \DBmysql $DB */
        global $DB;

       // input["_groups"] not set when update from user.form or preference
        if (
            isset($this->fields["authtype"])
            && isset($this->input["_groups"])
            && (($this->fields["authtype"] == Auth::LDAP)
              || Auth::isAlternateAuth($this->fields['authtype']))
        ) {
            if (isset($this->fields["id"]) && ($this->fields["id"] > 0)) {
                $authtype = Auth::getMethodsByID($this->fields["authtype"], $this->fields["auths_id"]);

                if (count($authtype)) {
                    // Clean groups
                    $this->input["_groups"] = array_unique($this->input["_groups"]);

                    // Delete not available groups like to LDAP
                    $iterator = $DB->request([
                        'SELECT'    => [
                            'glpi_groups_users.id',
                            'glpi_groups_users.groups_id',
                            'glpi_groups_users.is_dynamic'
                        ],
                        'FROM'      => 'glpi_groups_users',
                        'LEFT JOIN' => [
                            'glpi_groups'  => [
                                'FKEY'   => [
                                    'glpi_groups_users'  => 'groups_id',
                                    'glpi_groups'        => 'id'
                                ]
                            ]
                        ],
                        'WHERE'     => [
                            'glpi_groups_users.users_id' => $this->fields['id']
                        ]
                    ]);

                     $groupuser = new Group_User();
                    foreach ($iterator as $data) {
                        if (in_array($data["groups_id"], $this->input["_groups"])) {
                         // Delete found item in order not to add it again
                            unset($this->input["_groups"][array_search(
                                $data["groups_id"],
                                $this->input["_groups"]
                            )]);
                        } else if ($data['is_dynamic']) {
                            $groupuser->delete(['id' => $data["id"]]);
                        }
                    }

                     //If the user needs to be added to one group or more
                    if (count($this->input["_groups"]) > 0) {
                        foreach ($this->input["_groups"] as $group) {
                              $groupuser->add(['users_id'   => $this->fields["id"],
                                  'groups_id'  => $group,
                                  'is_dynamic' => 1
                              ]);
                        }
                        unset($this->input["_groups"]);
                    }
                }
            }
        }
    }


    /**
     * Synchronize picture (photo) of the user.
     *
     * @since 0.85
     *
     * @return string|boolean Filename to be stored in user picture field, false if no picture found
     */
    public function syncLdapPhoto()
    {

        if (
            isset($this->fields["authtype"])
            && (($this->fields["authtype"] == Auth::LDAP)
               || ($this->fields["authtype"] == Auth::NOT_YET_AUTHENTIFIED
                   && !empty($this->fields["auths_id"]))
               || Auth::isAlternateAuth($this->fields['authtype']))
        ) {
            if (isset($this->fields["id"]) && ($this->fields["id"] > 0)) {
                $config_ldap = new AuthLDAP();
                $ds          = false;

               //connect ldap server
                if ($config_ldap->getFromDB($this->fields['auths_id'])) {
                    $ds = $config_ldap->connect();
                }

                if ($ds) {
                   //get picture fields
                    $picture_field = $config_ldap->fields['picture_field'];
                    if (empty($picture_field)) {
                        return false;
                    }

                   //get picture content in ldap
                    $info = AuthLDAP::getUserByDn(
                        $ds,
                        $this->fields['user_dn'],
                        [$picture_field]
                    );

                   //getUserByDn returns an array. If the picture is empty,
                   //$info[$picture_field][0] is null
                    if (!isset($info[$picture_field][0]) || empty($info[$picture_field][0])) {
                          return "";
                    }
                   //prepare paths
                    $img       = array_pop($info[$picture_field]);
                    $filename  = uniqid($this->fields['id'] . '_');
                    $sub       = substr($filename, -2); /* 2 hex digit */
                    $file      = GLPI_PICTURE_DIR . "/{$sub}/{$filename}.jpg";

                    if (array_key_exists('picture', $this->fields)) {
                        $oldfile = GLPI_PICTURE_DIR . "/" . $this->fields["picture"];
                    } else {
                        $oldfile = null;
                    }

                   // update picture if not exist or changed
                    if (
                        empty($this->fields["picture"])
                        || !file_exists($oldfile)
                        || sha1_file($oldfile) !== sha1($img)
                    ) {
                        if (!is_dir(GLPI_PICTURE_DIR . "/$sub")) {
                            mkdir(GLPI_PICTURE_DIR . "/$sub");
                        }

                       //save picture
                        $outjpeg = fopen($file, 'wb');
                        fwrite($outjpeg, $img);
                        fclose($outjpeg);

                       //save thumbnail
                        $thumb = GLPI_PICTURE_DIR . "/{$sub}/{$filename}_min.jpg";
                        Toolbox::resizePicture($file, $thumb);

                        return "{$sub}/{$filename}.jpg";
                    }
                    return $this->fields["picture"];
                }
            }
        }

        return false;
    }


    /**
     * Update emails of the user.
     * Uses _useremails set from UI, not _emails set from LDAP.
     *
     * @return void
     */
    public function updateUserEmails()
    {
       // Update emails  (use _useremails set from UI, not _emails set from LDAP)

        $userUpdated = false;

        if (isset($this->input['_useremails']) && count($this->input['_useremails'])) {
            foreach ($this->input['_useremails'] as $id => $email) {
                $email = trim($email);

                $useremail = new UserEmail();
                if ($id > 0 && $useremail->getFromDB($id) && $useremail->fields['users_id'] === $this->getID()) {
                    // Existing email attached to current user

                    $params = ['id' => $id];

                    if (strlen($email) === 0) {
                        // Empty email, delete it
                        $deleted = $useremail->delete($params);
                        $userUpdated = $userUpdated || $deleted;
                    } else {
                        // Update email
                        $params['email'] = $email;
                        $params['is_default'] = $this->input['_default_email'] == $id ? 1 : 0;

                        $existingUserEmail = new UserEmail();
                        if (
                            $existingUserEmail->getFromDB($id)
                            && $params['email'] == $existingUserEmail->fields['email']
                            && $params['is_default'] == $existingUserEmail->fields['is_default']
                        ) {
                             // Do not update if email has not changed
                             continue;
                        }

                        $updated = $useremail->update($params);
                        $userUpdated = $userUpdated || $updated;
                    }
                } else {
                    // New email
                    $email_input = [
                        'email'    => $email,
                        'users_id' => $this->fields['id']
                    ];
                    if (
                        isset($this->input['_default_email'])
                        && ($this->input['_default_email'] == $id)
                    ) {
                        $email_input['is_default'] = 1;
                    } else {
                        $email_input['is_default'] = 0;
                    }
                    $added = $useremail->add($email_input);
                    $userUpdated = $userUpdated || $added;
                }
            }
        }

        if ($userUpdated) {
           // calling $this->update() here leads to loss in $this->input
            $user = new User();
            $user->update(['id' => $this->fields['id'], 'date_mod' => $_SESSION['glpi_currenttime']]);
        }
    }


    /**
     * Synchronise Dynamics emails of the user.
     * Uses _emails (set from getFromLDAP), not _usermails set from UI.
     *
     * @return void
     */
    public function syncDynamicEmails()
    {
        /** @var \DBmysql $DB */
        global $DB;

        $userUpdated = false;

       // input["_emails"] not set when update from user.form or preference
        if (
            isset($this->fields["authtype"])
            && isset($this->input["_emails"])
            && (($this->fields["authtype"] == Auth::LDAP)
              || Auth::isAlternateAuth($this->fields['authtype'])
              || ($this->fields["authtype"] == Auth::MAIL))
        ) {
            if (isset($this->fields["id"]) && ($this->fields["id"] > 0)) {
                $authtype = Auth::getMethodsByID($this->fields["authtype"], $this->fields["auths_id"]);

                if (
                    count($authtype)
                    || $this->fields["authtype"] == Auth::EXTERNAL
                ) {
                    // Clean emails
                    // Do a case insensitive comparison as it seems that some LDAP servers
                    // may return same email with different case sensitivity.
                    $unique_emails = [];
                    foreach ($this->input["_emails"] as $email) {
                        if (!in_array(strtolower($email), array_map('strtolower', $unique_emails))) {
                            $unique_emails[] = $email;
                        }
                    }
                    $this->input["_emails"] = $unique_emails;

                    // Delete not available groups like to LDAP
                    $iterator = $DB->request([
                        'SELECT' => [
                            'id',
                            'users_id',
                            'email',
                            'is_dynamic'
                        ],
                        'FROM'   => 'glpi_useremails',
                        'WHERE'  => ['users_id' => $this->fields['id']]
                    ]);

                     $useremail = new UserEmail();
                    foreach ($iterator as $data) {
                        // Do a case insensitive comparison as email may be stored with a different case
                        $i = array_search(strtolower($data["email"]), array_map('strtolower', $this->input["_emails"]));
                        if ($i !== false) {
                            // Delete found item in order not to add it again
                            unset($this->input["_emails"][$i]);
                        } else if ($data['is_dynamic']) {
                            // Delete not found email
                            $deleted = $useremail->delete(['id' => $data["id"]]);
                            $userUpdated = $userUpdated || $deleted;
                        }
                    }

                     //If the email need to be added
                    if (count($this->input["_emails"]) > 0) {
                        foreach ($this->input["_emails"] as $email) {
                              $added = $useremail->add(['users_id'   => $this->fields["id"],
                                  'email'      => $email,
                                  'is_dynamic' => 1
                              ]);
                              $userUpdated = $userUpdated || $added;
                        }
                        unset($this->input["_emails"]);
                    }
                }
            }
        }

        if ($userUpdated) {
           // calling $this->update() here leads to loss in $this->input
            $user = new User();
            $user->update(['id' => $this->fields['id'], 'date_mod' => $_SESSION['glpi_currenttime']]);
        }
    }

    protected function computeFriendlyName()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (isset($this->fields["id"]) && ($this->fields["id"] > 0)) {
           //computeFriendlyName should not add ID
            $bkp_conf = $CFG_GLPI['is_ids_visible'];
            $CFG_GLPI['is_ids_visible'] = 0;
            $bkp_sessconf = (isset($_SESSION['glpiis_ids_visible']) ? $_SESSION["glpiis_ids_visible"] : 0);
            $_SESSION["glpiis_ids_visible"] = 0;
            $name = formatUserName(
                $this->fields["id"],
                $this->fields["name"],
                (isset($this->fields["realname"]) ? $this->fields["realname"] : ''),
                (isset($this->fields["firstname"]) ? $this->fields["firstname"] : '')
            );

            $CFG_GLPI['is_ids_visible'] = $bkp_conf;
            $_SESSION["glpiis_ids_visible"] = $bkp_sessconf;
            return $name;
        }
        return '';
    }


    /**
     * Function that tries to load the user membership from LDAP
     * by searching in the attributes of the User.
     *
     * @param resource $ldap_connection LDAP connection
     * @param array    $ldap_method     LDAP method
     * @param string   $userdn          Basedn of the user
     * @param string   $login           User login
     *
     * @return void
     */
    private function getFromLDAPGroupVirtual($ldap_connection, array $ldap_method, $userdn, $login): void
    {
        /** @var \DBmysql $DB */
        global $DB;

       // Search in DB the ldap_field we need to search for in LDAP
        $iterator = $DB->request([
            'SELECT'          => 'ldap_field',
            'DISTINCT'        => true,
            'FROM'            => 'glpi_groups',
            'WHERE'           => ['NOT' => ['ldap_field' => '']],
            'ORDER'           => 'ldap_field'
        ]);
        $group_fields = [];

        foreach ($iterator as $data) {
            $group_fields[] = Toolbox::strtolower($data["ldap_field"]);
        }
        if (count($group_fields)) {
            //Need to sort the array because edirectory don't like it!
            sort($group_fields);

            // If the groups must be retrieved from the ldap user object
            $sr = @ldap_read($ldap_connection, $userdn, "objectClass=*", $group_fields);
            if ($sr === false) {
                // 32 = LDAP_NO_SUCH_OBJECT => This error can be silented as it just means that search produces no result.
                if (ldap_errno($ldap_connection) !== 32) {
                    trigger_error(
                        AuthLDAP::buildError(
                            $ldap_connection,
                            sprintf('Unable to get LDAP groups for user having DN `%s` with filter `%s', $userdn, "objectClass=*")
                        ),
                        E_USER_WARNING
                    );
                }
                return;
            }
            $v  = AuthLDAP::get_entries_clean($ldap_connection, $sr);

            for ($i = 0; $i < $v['count']; $i++) {
               //Try to find is DN in present and needed: if yes, then extract only the OU from it
                if (
                    (($ldap_method["group_field"] == 'dn') || in_array('ou', $group_fields))
                    && isset($v[$i]['dn'])
                ) {
                    $v[$i]['ou'] = [];
                    for ($tmp = $v[$i]['dn']; count($tmptab = explode(',', $tmp, 2)) == 2; $tmp = $tmptab[1]) {
                        $v[$i]['ou'][] = $tmptab[1];
                    }

                    // Search in DB for group with ldap_group_dn
                    if (
                        ($ldap_method["group_field"] == 'dn')
                        && (count($v[$i]['ou']) > 0)
                    ) {
                        $group_iterator = $DB->request([
                            'SELECT' => 'id',
                            'FROM'   => 'glpi_groups',
                            'WHERE'  => ['ldap_group_dn' => Sanitizer::sanitize($v[$i]['ou'])]
                        ]);

                        foreach ($group_iterator as $group) {
                            $this->fields["_groups"][] = $group['id'];
                        }
                    }

                   // searching with ldap_field='OU' and ldap_value is also possible
                    $v[$i]['ou']['count'] = count($v[$i]['ou']);
                }

               // For each attribute retrieve from LDAP, search in the DB
                foreach ($group_fields as $field) {
                    if (
                        isset($v[$i][$field])
                        && isset($v[$i][$field]['count'])
                        && ($v[$i][$field]['count'] > 0)
                    ) {
                        unset($v[$i][$field]['count']);
                        $lgroups = [];
                        foreach (Sanitizer::sanitize($v[$i][$field]) as $lgroup) {
                            $lgroups[] = [
                                new \QueryExpression($DB->quoteValue($lgroup) .
                                             " LIKE " .
                                             $DB->quoteName('ldap_value'))
                            ];
                        }
                        $group_iterator = $DB->request([
                            'SELECT' => 'id',
                            'FROM'   => 'glpi_groups',
                            'WHERE'  => [
                                'ldap_field' => $field,
                                'OR'         => $lgroups
                            ]
                        ]);

                        foreach ($group_iterator as $group) {
                            $this->fields["_groups"][] = $group['id'];
                        }
                    }
                }
            }
        }
    }


    /**
     * Function that tries to load the user membership from LDAP
     * by searching in the attributes of the Groups.
     *
     * @param resource $ldap_connection    LDAP connection
     * @param array    $ldap_method        LDAP method
     * @param string   $userdn             Basedn of the user
     * @param string   $login              User login
     *
     * @return boolean true if search is applicable, false otherwise
     */
    private function getFromLDAPGroupDiscret($ldap_connection, array $ldap_method, $userdn, $login)
    {
        /** @var \DBmysql $DB */
        global $DB;

       // No group_member_field : unable to get group
        if (empty($ldap_method["group_member_field"])) {
            return false;
        }

        if ($ldap_method["use_dn"]) {
            $user_tmp = $userdn;
        } else {
           //Don't add $ldap_method["login_field"]."=", because sometimes it may not work (for example with posixGroup)
            $user_tmp = $login;
        }

        $v = $this->ldap_get_user_groups(
            $ldap_connection,
            $ldap_method["basedn"],
            $user_tmp,
            Sanitizer::unsanitize($ldap_method["group_condition"]),
            $ldap_method["group_member_field"],
            $ldap_method["use_dn"],
            $ldap_method["login_field"]
        );
        foreach ($v as $result) {
            if (
                isset($result[$ldap_method["group_member_field"]])
                && is_array($result[$ldap_method["group_member_field"]])
                && (count($result[$ldap_method["group_member_field"]]) > 0)
            ) {
                $iterator = $DB->request([
                    'SELECT' => 'id',
                    'FROM'   => 'glpi_groups',
                    'WHERE'  => ['ldap_group_dn' => Sanitizer::sanitize($result[$ldap_method["group_member_field"]])]
                ]);

                foreach ($iterator as $group) {
                    $this->fields["_groups"][] = $group['id'];
                }
            }
        }
        return true;
    }


    /**
     * Function that tries to load the user information from LDAP.
     *
     * @param resource $ldap_connection LDAP connection
     * @param array    $ldap_method     LDAP method
     * @param string   $userdn          Basedn of the user
     * @param string   $login           User Login
     * @param boolean  $import          true for import, false for update
     *
     * @return boolean true if found / false if not
     */
    public function getFromLDAP($ldap_connection, array $ldap_method, $userdn, $login, $import = true)
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

       // we prevent some delay...
        if (empty($ldap_method["host"])) {
            return false;
        }

        if (
            is_resource($ldap_connection)
            || (class_exists(\LDAP\Connection::class) && $ldap_connection instanceof \LDAP\Connection)
        ) {
           //Set all the search fields
            $this->fields['password'] = "";

            $fields  = AuthLDAP::getSyncFields($ldap_method);

           //Hook to allow plugin to request more attributes from ldap
            $fields = Plugin::doHookFunction(Hooks::RETRIEVE_MORE_FIELD_FROM_LDAP, $fields);

            $fields  = array_filter($fields);
            $f       = self::getLdapFieldNames($fields);

            $sr      = @ldap_read($ldap_connection, $userdn, "objectClass=*", $f);
            if ($sr === false) {
                // 32 = LDAP_NO_SUCH_OBJECT => This error can be silented as it just means that search produces no result.
                if (ldap_errno($ldap_connection) !== 32) {
                    trigger_error(
                        AuthLDAP::buildError(
                            $ldap_connection,
                            sprintf('Unable to get LDAP user having DN `%s` with filter `%s`', $userdn, "objectClass=*")
                        ),
                        E_USER_WARNING
                    );
                }
                return false;
            }
            $v       = AuthLDAP::get_entries_clean($ldap_connection, $sr);

            if (
                !is_array($v)
                || ( count($v) == 0)
                || empty($v[0][$fields['name']][0])
            ) {
                return false;
            }

           //Store user's dn
            $this->fields['user_dn']    = Sanitizer::sanitize($userdn);
           //Store date_sync
            $this->fields['date_sync']  = $_SESSION['glpi_currenttime'];
           // Empty array to ensure than syncDynamicEmails will be done
            $this->fields["_emails"]    = [];
           // force authtype as we retrieve this user by ldap (we could have login with SSO)
            $this->fields["authtype"] = Auth::LDAP;

            $import_fields = [];
            foreach ($fields as $k => $e) {
                $val = AuthLDAP::getFieldValue(
                    [$e => self::getLdapFieldValue($e, $v)],
                    $e
                );
                if (empty($val)) {
                    switch ($k) {
                        case "language":
                             // Not set value : managed but user class
                            break;

                        case "usertitles_id":
                        case "usercategories_id":
                        case 'locations_id':
                        case 'users_id_supervisor':
                            $this->fields[$k] = 0;
                            break;

                        default:
                            $this->fields[$k] = "";
                    }
                } else {
                    $val = Sanitizer::sanitize($val);
                    switch ($k) {
                        case "email1":
                        case "email2":
                        case "email3":
                        case "email4":
                          // Manage multivaluable fields
                            if (!empty($v[0][$e])) {
                                foreach ($v[0][$e] as $km => $m) {
                                    if (!preg_match('/count/', $km)) {
                                         $this->fields["_emails"][] = addslashes($m);
                                    }
                                }
                                // Only get them once if duplicated
                                $this->fields["_emails"] = array_unique($this->fields["_emails"]);
                            }
                            break;

                        case "language":
                            $language = Config::getLanguage($val);
                            if ($language != '') {
                                $this->fields[$k] = $language;
                            }
                            break;

                        case "usertitles_id":
                        case 'locations_id':
                        case "usercategories_id":
                        case 'users_id_supervisor':
                            $import_fields[$k] = $val;
                            break;

                        default:
                              $this->fields[$k] = $val;
                    }
                }
            }

           // Empty array to ensure than syncLdapGroups will be done
            $this->fields["_groups"] = [];

           ///The groups are retrieved by looking into an ldap user object
            if (
                ($ldap_method["group_search_type"] == 0)
                || ($ldap_method["group_search_type"] == 2)
            ) {
                $this->getFromLDAPGroupVirtual($ldap_connection, $ldap_method, $userdn, $login);
            }

           ///The groups are retrived by looking into an ldap group object
            if (
                ($ldap_method["group_search_type"] == 1)
                || ($ldap_method["group_search_type"] == 2)
            ) {
                $this->getFromLDAPGroupDiscret($ldap_connection, $ldap_method, $userdn, $login);
            }

           ///Only process rules if working on the master database
            if (!$DB->isSlave()) {
               //Instanciate the affectation's rule
                $rule = new RuleRightCollection();

               //Process affectation rules :
               //we don't care about the function's return because all
               //the datas are stored in session temporary
                if (isset($this->fields["_groups"])) {
                    $groups = $this->fields["_groups"];
                } else {
                    $groups = [];
                }

                $this->fields = $rule->processAllRules($groups, Toolbox::stripslashes_deep($this->fields), [
                    'type'        => Auth::LDAP,
                    'ldap_server' => $ldap_method["id"],
                    'connection'  => $ldap_connection,
                    'userdn'      => $userdn,
                    'login'       => $this->fields['name'],
                    'mail_email'  => $this->fields['_emails']
                ]);

                $this->willProcessRuleRight();

               //If rule  action is ignore import
                if (
                    $import
                    && isset($this->fields["_stop_import"])
                ) {
                     return false;
                }
               //or no rights found & do not import users with no rights
                if (
                    $import
                    && !$CFG_GLPI["use_noright_users_add"]
                ) {
                    $ok = false;
                    if (
                        isset($this->fields["_ldap_rules"])
                        && count($this->fields["_ldap_rules"])
                    ) {
                        if (
                            isset($this->fields["_ldap_rules"]["rules_entities_rights"])
                            && count($this->fields["_ldap_rules"]["rules_entities_rights"])
                        ) {
                            $ok = true;
                        }
                        if (!$ok) {
                            $entity_count = 0;
                            $right_count  = 0;
                            if (Profile::getDefault()) {
                                $right_count++;
                            }
                            if (isset($this->fields["_ldap_rules"]["rules_entities"])) {
                                $entity_count += count($this->fields["_ldap_rules"]["rules_entities"]);
                            }
                            if (isset($this->input["_ldap_rules"]["rules_rights"])) {
                                $right_count += count($this->fields["_ldap_rules"]["rules_rights"]);
                            }
                            if ($entity_count && $right_count) {
                                $ok = true;
                            }
                        }
                    }
                    if (!$ok) {
                        $this->fields["_stop_import"] = true;
                        return false;
                    }
                }

                foreach ($import_fields as $k => $val) {
                    switch ($k) {
                        case "usertitles_id":
                            $this->fields[$k] = Dropdown::importExternal('UserTitle', $val);
                            break;
                        case 'locations_id':
                            // use import to build the location tree
                            $this->fields[$k] = Dropdown::import(
                                'Location',
                                ['completename' => $val,
                                    'entities_id'  => 0,
                                    'is_recursive' => 1
                                ]
                            );
                            break;
                        case "usercategories_id":
                            $this->fields[$k] = Dropdown::importExternal('UserCategory', $val);
                            break;
                        case 'users_id_supervisor':
                            $supervisor_id = self::getIdByField('user_dn', $val, false);
                            if ($supervisor_id) {
                                $this->fields[$k] = $supervisor_id;
                            }
                            break;
                    }
                }

               // Add ldap result to data send to the hook
                $this->fields['_ldap_result'] = $v;
                $this->fields['_ldap_conn']   = $ldap_connection;
               //Hook to retrieve more information for ldap
                $this->fields = Plugin::doHookFunction(Hooks::RETRIEVE_MORE_DATA_FROM_LDAP, $this->fields);
                unset($this->fields['_ldap_result']);
            }

            return true;
        }
        return false;
    }


    /**
     * Get all groups a user belongs to.
     *
     * @param resource $ds                 ldap connection
     * @param string   $ldap_base_dn       Basedn used
     * @param string   $user_dn            Basedn of the user
     * @param string   $group_condition    group search condition
     * @param string   $group_member_field group field member in a user object
     * @param boolean  $use_dn             search dn of user ($login_field=$user_dn) in group_member_field
     * @param string   $login_field        user login field
     *
     * @return array Groups of the user located in [0][$group_member_field] in returned array
     */
    public function ldap_get_user_groups(
        $ds,
        $ldap_base_dn,
        $user_dn,
        $group_condition,
        $group_member_field,
        $use_dn,
        $login_field
    ) {

        $groups     = [];
        $listgroups = [];

       //User dn may contain ['(', ')', ',', '\'] then it needs to be escaped!
        $user_dn = ldap_escape($user_dn, "", LDAP_ESCAPE_FILTER);

       //Only retrive cn and member attributes from groups
        $attrs = ['dn'];

        $group_condition = Sanitizer::unsanitize($group_condition);
        if (!$use_dn) {
            $filter = "(& $group_condition (|($group_member_field=$user_dn)
                                          ($group_member_field=$login_field=$user_dn)))";
        } else {
            $filter = "(& $group_condition ($group_member_field=$user_dn))";
        }

       //Perform the search
        $sr = @ldap_search($ds, $ldap_base_dn, $filter, $attrs);

        if ($sr === false) {
            // 32 = LDAP_NO_SUCH_OBJECT => This error can be silented as it just means that search produces no result.
            if (ldap_errno($ds) !== 32) {
                trigger_error(
                    AuthLDAP::buildError(
                        $ds,
                        sprintf('LDAP search with base DN `%s` and filter `%s` failed', $ldap_base_dn, $filter)
                    ),
                    E_USER_WARNING
                );
            }
            return $groups;
        }

       //Get the result of the search as an array
        $info = AuthLDAP::get_entries_clean($ds, $sr);
       //Browse all the groups
        $info_count = count($info);
        for ($i = 0; $i < $info_count; $i++) {
           //Get the cn of the group and add it to the list of groups
            if (isset($info[$i]["dn"]) && ($info[$i]["dn"] != '')) {
                $listgroups[$i] = $info[$i]["dn"];
            }
        }

       //Create an array with the list of groups of the user
        $groups[0][$group_member_field] = $listgroups;
       //Return the groups of the user
        return $groups;
    }


    /**
     * Function that tries to load the user information from IMAP.
     *
     * @param array  $mail_method  mail method description array
     * @param string $name         login of the user
     *
     * @return boolean true if method is applicable, false otherwise
     */
    public function getFromIMAP(array $mail_method, $name)
    {
        /** @var \DBmysql $DB */
        global $DB;

       // we prevent some delay..
        if (empty($mail_method["host"])) {
            return false;
        }

       // some defaults...
        $this->fields['password']  = "";
       // Empty array to ensure than syncDynamicEmails will be done
        $this->fields["_emails"]   = [];
        $email                     = '';
        if (strpos($name, "@")) {
            $email = $name;
        } else {
            $email = $name . "@" . $mail_method["host"];
        }
        $this->fields["_emails"][] = $email;

        $this->fields['name']      = $name;
       //Store date_sync
        $this->fields['date_sync'] = $_SESSION['glpi_currenttime'];
       // force authtype as we retrieve this user by imap (we could have login with SSO)
        $this->fields["authtype"] = Auth::MAIL;

        if (!$DB->isSlave()) {
           //Instanciate the affectation's rule
            $rule = new RuleRightCollection();

           //Process affectation rules :
           //we don't care about the function's return because all the datas are stored in session temporary
            if (isset($this->fields["_groups"])) {
                $groups = $this->fields["_groups"];
            } else {
                $groups = [];
            }
            $this->fields = $rule->processAllRules($groups, Toolbox::stripslashes_deep($this->fields), [
                'type'        => Auth::MAIL,
                'mail_server' => $mail_method["id"],
                'login'       => $name,
                'email'       => $email
            ]);
            $this->willProcessRuleRight();
        }
        return true;
    }


    /**
     * Function that tries to load the user information from the SSO server.
     *
     * @since 0.84
     *
     * @return boolean true if method is applicable, false otherwise
     */
    public function getFromSSO()
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $a_field = [];
        foreach ($CFG_GLPI as $key => $value) {
            if (
                !is_array($value) && !empty($value)
                && strstr($key, "_ssofield")
            ) {
                $key = str_replace('_ssofield', '', $key);
                $a_field[$key] = $value;
            }
        }

        if (count($a_field) == 0) {
            return true;
        }
        $this->willProcessRuleRight();
        foreach ($a_field as $field => $key) {
            $value = $_SERVER[$key] ?? null;
            if (empty($value)) {
                switch ($field) {
                    case "title":
                        $this->fields['usertitles_id'] = 0;
                        break;

                    case "category":
                        $this->fields['usercategories_id'] = 0;
                        break;

                    default:
                        $this->fields[$field] = "";
                }
            } else {
                if (!mb_check_encoding($value, 'UTF-8') && mb_check_encoding($value, 'ISO-8859-1')) {
                    // Some applications, like Microsoft Azure Enterprise Applications (Header-based Single sign-on),
                    // will provide ISO-8859-1 encoded values. They have to be converted into UTF-8 to prevent
                    // encoding issues (see #12898).
                    $value = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
                }
                $value = Sanitizer::sanitize($value); // $_SERVER is not automatically sanitized
                switch ($field) {
                    case "email1":
                    case "email2":
                    case "email3":
                    case "email4":
                        // Manage multivaluable fields
                        if (!preg_match('/count/', $value)) {
                            $this->fields["_emails"][] = $value;
                        }
                        // Only get them once if duplicated
                        $this->fields["_emails"] = array_unique($this->fields["_emails"]);
                        break;

                    case "language":
                        $language = Config::getLanguage($value);
                        if ($language != '') {
                            $this->fields[$field] = $language;
                        }
                        break;

                    case "title":
                        $this->fields['usertitles_id'] = Dropdown::importExternal('UserTitle', $value);
                        break;

                    case "category":
                        $this->fields['usercategories_id'] = Dropdown::importExternal('UserCategory', $value);
                        break;

                    default:
                        $this->fields[$field] = $value;
                        break;
                }
            }
        }
       ///Only process rules if working on the master database
        if (!$DB->isSlave()) {
           //Instanciate the affectation's rule
            $rule = new RuleRightCollection();

            $groups_id = [];
            if (!$this->isNewItem()) {
                $groups = Group_User::getUserGroups($this->fields['id']);
                $groups_id = array_column($groups, 'id');
            }

            $this->fields = $rule->processAllRules($groups_id, Toolbox::stripslashes_deep($this->fields), [
                'type'   => Auth::EXTERNAL,
                'email'  => $this->fields["_emails"] ?? [],
                'login'  => $this->fields["name"]
            ]);

           //If rule  action is ignore import
            if (isset($this->fields["_stop_import"])) {
                return false;
            }
        }
        return true;
    }


    /**
     * Blank passwords field of a user in the DB.
     * Needed for external auth users.
     *
     * @return void
     */
    public function blankPassword()
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (!empty($this->fields["name"])) {
            $DB->update(
                $this->getTable(),
                [
                    'password' => ''
                ],
                [
                    'name' => $this->fields['name']
                ]
            );
        }
    }


    /**
     * Print a good title for user pages.
     *
     * @return void
     */
    public function title()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $buttons = [];
        $title   = self::getTypeName(Session::getPluralNumber());

        if (static::canCreate()) {
            $buttons["user.form.php"] = "<i class='fas fa-user-plus fa-lg me-2'></i>" . __('Add user...');
            $title = __("Actions");

            if (
                Auth::useAuthExt()
                && Session::haveRight("user", self::IMPORTEXTAUTHUSERS)
            ) {
                // This requires write access because don't use entity config.
                $buttons["user.form.php?new=1&amp;ext_auth=1"] = "<i class='fas fa-user-cog fa-lg me-2'></i>" . __('... From an external source');
            }
        }
        if (
            Session::haveRight("user", self::IMPORTEXTAUTHUSERS)
            && (static::canCreate() || static::canUpdate())
        ) {
            if (AuthLDAP::useAuthLdap()) {
                $buttons["ldap.php"] = "<i class='fas fa-cog fa-lg me-2'></i>" . __('LDAP directory link');
            }
        }
        Html::displayTitle(
            "",
            self::getTypeName(Session::getPluralNumber()),
            $title,
            $buttons
        );
    }


    /**
     * Check if current user have more right than the specified one.
     *
     * @param integer $ID ID of the user
     *
     * @return boolean
     */
    public function currentUserHaveMoreRightThan($ID)
    {

        $user_prof = Profile_User::getUserProfiles($ID);
        return Profile::currentUserHaveMoreRightThan($user_prof);
    }

    protected function getFormHeaderToolbar(): array
    {
        $ID = $this->getID();
        $toolbar = [];

        if ($ID > 0) {
            $vcard_lbl = __s('Download user VCard');
            $vcard_url = self::getFormURLWithID($ID) . "&amp;getvcard=1";
            $vcard_btn = <<<HTML
            <a href="{$vcard_url}" target="_blank"
                     class="btn btn-icon btn-sm btn-ghost-secondary"
                     title="{$vcard_lbl}"
                     data-bs-toggle="tooltip" data-bs-placement="bottom">
               <i class="far fa-address-card fa-lg"></i>
            </a>
HTML;
            $toolbar[] = $vcard_btn;

            $error_message = null;
            $impersonate_form = self::getFormURLWithID($ID);
            if (Session::canImpersonate($ID, $error_message)) {
                $impersonate_lbl = __s('Impersonate');
                $csrf_token = Session::getNewCSRFToken();
                $impersonate_btn = <<<HTML
                    <form method="post" action="{$impersonate_form}">
                        <input type="hidden" name="id" value="{$ID}">
                        <input type="hidden" name="_glpi_csrf_token" value="{$csrf_token}">
                        <button type="button" name="impersonate" value="1"
                            class="btn btn-icon btn-sm btn-ghost-secondary btn-impersonate"
                            title="{$impersonate_lbl}"
                            data-bs-toggle="tooltip" data-bs-placement="bottom">
                            <i class="fas fa-user-secret fa-lg"></i>
                        </button>
                    </form>
HTML;

                // "impersonate" button type is set to "button" on form display to prevent it to be used
                // by default (as it is the first found in current form) when pressing "enter" key.
                // When clicking it, switch to "submit" type to make it submit current user form.
                $impersonate_js = <<<JAVASCRIPT
               (function($) {
                  $('button[type="button"][name="impersonate"]').click(
                     function () {
                        $(this).attr('type', 'submit');
                     }
                  );
               })(jQuery);
JAVASCRIPT;
                $toolbar[] = $impersonate_btn . Html::scriptBlock($impersonate_js);
            } elseif ($error_message !== null) {
                $impersonate_btn = <<<HTML
               <button type="button" name="impersonate" value="1"
                       class="btn btn-icon btn-sm  btn-ghost-danger btn-impersonate"
                       title="{$error_message}"
                       data-bs-toggle="tooltip" data-bs-placement="bottom">
                  <i class="fas fa-user-secret fa-lg"></i>
               </button>
HTML;
                $toolbar[] = $impersonate_btn;
            }
        }
        return $toolbar;
    }

    /**
     * Print the user form.
     *
     * @param integer $ID    ID of the user
     * @param array $options Options
     *     - string   target        Form target
     *     - boolean  withtemplate  Template or basic item
     *
     * @return boolean true if user found, false otherwise
     */
    public function showForm($ID, array $options = [])
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

       // Affiche un formulaire User
        if (($ID != Session::getLoginUserID()) && !self::canView()) {
            return false;
        }

        $config = Config::getConfigurationValues('core');
        if ($this->getID() > 0 && $config['system_user'] == $this->getID()) {
            return $this->showSystemUserForm($ID, $options);
        }

        $this->initForm($ID, $options);

        $ismyself = $ID == Session::getLoginUserID();
        $higherrights = $this->currentUserHaveMoreRightThan($ID);
        if ($ID) {
            $caneditpassword = $higherrights || ($ismyself && Session::haveRight('password_update', 1));
        } else {
           // can edit on creation form
            $caneditpassword = true;
        }

        $extauth = !(($this->fields["authtype"] == Auth::DB_GLPI)
                   || (($this->fields["authtype"] == Auth::NOT_YET_AUTHENTIFIED)
                       && !empty($this->fields["password"])));

        $formtitle = $this->getTypeName(1);

        $options['formtitle']      = $formtitle;
        $options['formoptions']    = ($options['formoptions'] ?? '') . " enctype='multipart/form-data'";
        if (!self::isNewID($ID)) {
            $options['no_header'] = true;
        }
        $this->showFormHeader($options);
        $rand = mt_rand();

        echo "<tr class='tab_bg_1'>";
        echo "<td><label for='name'>" . __('Login') . "</label></td>";
        if (
            $this->fields["name"] == "" ||
            !empty($this->fields["password"]) ||
            ($this->fields["authtype"] == Auth::DB_GLPI)
        ) {
           //display login field for new records, or if this is not external auth
            echo "<td><input name='name' id='name' value=\"" . $this->fields["name"] . "\" class='form-control'></td>";
        } else {
            echo "<td class='b'>" . $this->fields["name"];
            echo "<input type='hidden' name='name' value=\"" . $this->fields["name"] . "\" class='form-control'></td>";
        }

        if (!empty($this->fields["name"])) {
            echo "<td rowspan='7'>" . _n('Picture', 'Pictures', 1) . "</td>";
            echo "<td rowspan='7'>";
            echo self::getPictureForUser($ID);

            echo Html::file(['name' => 'picture', 'display' => false, 'onlyimages' => true]);
            echo "<input type='checkbox' name='_blank_picture'>&nbsp;" . __('Clear');
            echo "</td>";
        } else {
            echo "<td rowspan='7'></td>";
            echo "<td rowspan='7'></td>";
        }
        echo "</tr>";

       //If it's an external auth, check if the sync_field must be displayed
        if (
            $extauth
            && $this->fields['auths_id']
            && AuthLDAP::isSyncFieldConfigured($this->fields['auths_id'])
        ) {
            $syncrand = mt_rand();
            echo "<tr class='tab_bg_1'><td><label for='textfield_sync_field$syncrand'>" . __('Synchronization field') . "</label></td><td>";
            if (
                self::canUpdate()
                && (!$extauth || empty($ID))
            ) {
                echo Html::input(
                    'sync_field',
                    [
                        'value' => $this->fields['sync_field'],
                        'id'    => "textfield_sync_field$syncrand",
                    ]
                );
            } else {
                if (empty($this->fields['sync_field'])) {
                    echo Dropdown::EMPTY_VALUE;
                } else {
                    echo $this->fields['sync_field'];
                }
            }
            echo "</td></tr>";
        } else {
            echo "<tr class='tab_bg_1'><td colspan='2'></td></tr>";
        }

        $surnamerand = mt_rand();
        echo "<tr class='tab_bg_1'><td><label for='textfield_realname$surnamerand'>" . __('Surname') . "</label></td><td>";
        echo Html::input(
            'realname',
            [
                'value' => $this->fields['realname'],
                'id'    => "textfield_realname$surnamerand",
            ]
        );
        echo "</td></tr>";

        $firstnamerand = mt_rand();
        echo "<tr class='tab_bg_1'><td><label for='textfield_firstname$firstnamerand'>" . __('First name') . "</label></td><td>";
        echo Html::input(
            'firstname',
            [
                'value' => $this->fields['firstname'],
                'id'    => "textfield_firstname$firstnamerand",
            ]
        );
        echo "</td></tr>";

       //do some rights verification
        if (
            self::canUpdate()
            && (!$extauth || empty($ID))
            && $caneditpassword
        ) {
            echo "<tr class='tab_bg_1'>";
            echo "<td><label for='password'>" . __('Password') . "</label></td>";
            echo "<td><input id='password' type='password' name='password' value='' size='20'
                    autocomplete='new-password' onkeyup=\"return passwordCheck();\" class='form-control'></td>";

            echo "<tr class='tab_bg_1'>";
            echo "<td><label for='password2'>" . __('Password confirmation') . "</label></td>";
            echo "<td><input type='password' id='password2' name='password2' value='' size='20' autocomplete='new-password' class='form-control'>";
            echo "</td></tr>";

            if ($CFG_GLPI["use_password_security"]) {
                echo "<tr class='tab_bg_1'>";
                echo "<td rowspan='2'>";
                echo __('Password security policy');
                echo "</td>";
                echo "<td rowspan='2'>";
                Config::displayPasswordSecurityChecks();
                echo "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr class='tab_bg_1'><td></td><td></td></tr>";
            echo "<tr class='tab_bg_1'><td></td><td></td></tr>";
        }

        if ($DB->use_timezones || Session::haveRight("config", READ)) {
            echo "<tr class='tab_bg_1'>";
            echo "<td><label for='timezone'>" . __('Time zone') . "</label></td><td>";
            if ($DB->use_timezones) {
                $timezones = $DB->getTimezones();
                Dropdown::showFromArray(
                    'timezone',
                    $timezones,
                    [
                        'value'                 => $this->fields["timezone"],
                        'display_emptychoice'   => true,
                        'emptylabel'            => __('Use server configuration')
                    ]
                );
            } else if (Session::haveRight("config", READ)) {
               // Display a warning but only if user is more or less an admin
                echo __('Timezone usage has not been activated.')
                . ' '
                . sprintf(__('Run the "%1$s" command to activate it.'), 'php bin/console database:enable_timezones');
            }
            echo "</td></tr>";
        }

        echo "<tr class='tab_bg_1'>";
        if (!GLPI_DEMO_MODE) {
            $activerand = mt_rand();
            echo "<td><label for='dropdown_is_active$activerand'>" . __('Active') . "</label></td><td>";
            Dropdown::showYesNo('is_active', $this->fields['is_active'], -1, ['rand' => $activerand]);
            echo "</td>";
        } else {
            echo "<td colspan='2'></td>";
        }
        echo "<td>" . _n('Email', 'Emails', Session::getPluralNumber());
        UserEmail::showAddEmailButton($this);
        echo "</td><td>";
        UserEmail::showForUser($this);
        echo "</td>";
        echo "</tr>";

        if (!GLPI_DEMO_MODE) {
            $sincerand = mt_rand();
            echo "<tr class='tab_bg_1'>";
            echo "<td><label for='showdate$sincerand'>" . __('Valid since') . "</label></td><td>";
            Html::showDateTimeField("begin_date", ['value'       => $this->fields["begin_date"],
                'rand'        => $sincerand,
                'maybeempty'  => true
            ]);
            echo "</td>";

            $untilrand = mt_rand();
            echo "<td><label for='showdate$untilrand'>" . __('Valid until') . "</label></td><td>";
            Html::showDateTimeField("end_date", ['value'       => $this->fields["end_date"],
                'rand'        => $untilrand,
                'maybeempty'  => true
            ]);
            echo "</td></tr>";
        }

        $phonerand = mt_rand();
        echo "<tr class='tab_bg_1'>";
        echo "<td><label for='textfield_phone$phonerand'>" .  Phone::getTypeName(1) . "</label></td><td>";
        echo Html::input(
            'phone',
            [
                'value' => $this->fields['phone'],
                'id'    => "textfield_phone$phonerand",
            ]
        );
        echo "</td>";
       //Authentications information : auth method used and server used
       //don't display is creation of a new user'
        if (!empty($ID)) {
            if (Session::haveRight(self::$rightname, self::READAUTHENT)) {
                echo "<td>" . __('Authentication') . "</td><td>";
                echo Auth::getMethodName($this->fields["authtype"], $this->fields["auths_id"]);
                if (!empty($this->fields["date_sync"])) {
                    //TRANS: %s is the date of last sync
                    echo '<br>' . sprintf(
                        __('Last synchronization on %s'),
                        Html::convDateTime($this->fields["date_sync"])
                    );
                }
                if (!empty($this->fields["user_dn"])) {
                  //TRANS: %s is the user dn
                    echo '<br>' . sprintf(__('%1$s: %2$s'), __('User DN'), $this->fields["user_dn"]);
                }
                if ($this->fields['is_deleted_ldap']) {
                    echo '<br>' . __('User missing in LDAP directory');
                }

                echo "</td>";
            } else {
                echo "<td colspan='2'>&nbsp;</td>";
            }
        } else {
            echo "<td colspan='2'><input type='hidden' name='authtype' value='1'></td>";
        }

        echo "</tr>";

        $mobilerand = mt_rand();
        echo "<tr class='tab_bg_1'>";
        echo "<td><label for='textfield_mobile$mobilerand'>" . __('Mobile phone') . "</label></td><td>";
        echo Html::input(
            'mobile',
            [
                'value' => $this->fields['mobile'],
                'id'    => "textfield_mobile$mobilerand",
            ]
        );
        echo "</td>";
        $catrand = mt_rand();
        echo "<td><label for='dropdown_usercategories_id$catrand'>" . _n('Category', 'Categories', 1) . "</label></td><td>";
        UserCategory::dropdown(['value' => $this->fields["usercategories_id"], 'rand' => $catrand]);
        echo "</td></tr>";

        $phone2rand = mt_rand();
        echo "<tr class='tab_bg_1'>";
        echo "<td><label for='textfield_phone2$phone2rand'>" .  __('Phone 2') . "</label></td><td>";
        echo Html::input(
            'phone2',
            [
                'value' => $this->fields['phone2'],
                'id'    => "textfield_phone2$phone2rand",
            ]
        );
        echo "</td>";
        echo "<td rowspan='4' class='middle'><label for='comment'>" . __('Comments') . "</label></td>";
        echo "<td class='center middle' rowspan='4'>";
        echo "<textarea class='form-control' id='comment' name='comment' >" . $this->fields["comment"] . "</textarea>";
        echo "</td></tr>";

        $admnumrand = mt_rand();
        echo "<tr class='tab_bg_1'><td><label for='textfield_registration_number$admnumrand'>" . _x('user', 'Administrative number') . "</label></td><td>";
        echo Html::input(
            'registration_number',
            [
                'value' => $this->fields['registration_number'],
                'id'    => "textfield_registration_number$admnumrand",
            ]
        );
        echo "</td></tr>";

        $titlerand = mt_rand();
        echo "<tr class='tab_bg_1'><td><label for='dropdown_usertitles_id$titlerand'>" . _x('person', 'Title') . "</label></td><td>";
        UserTitle::dropdown(['value' => $this->fields["usertitles_id"], 'rand' => $titlerand]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        if (!empty($ID)) {
            $locrand = mt_rand();
            echo "<td><label for='dropdown_locations_id$locrand'>" . Location::getTypeName(1) . "</label></td><td>";
            $entities = $this->getEntities();
            if (count($entities) <= 0) {
                $entities = -1;
            }
            Location::dropdown(['value'  => $this->fields["locations_id"],
                'rand'   => $locrand,
                'entity' => $entities
            ]);
            echo "</td>";
        }
        echo "</tr>";

        if (empty($ID)) {
            echo "<tr class='tab_bg_1'>";
            echo "<th colspan='2'>" . _n('Authorization', 'Authorizations', 1) . "</th>";
            $recurrand = mt_rand();
            echo "<td><label for='dropdown__is_recursive$recurrand'>" .  __('Recursive') . "</label></td><td>";
            Dropdown::showYesNo("_is_recursive", 0, -1, ['rand' => $recurrand]);
            echo "</td></tr>";
            $profilerand = mt_rand();
            echo "<tr class='tab_bg_1'>";
            echo "<td><label for='dropdown__profiles_id$profilerand'>" .  Profile::getTypeName(1) . "</label></td><td>";
            Profile::dropdownUnder(['name'  => '_profiles_id',
                'rand'  => $profilerand,
                'value' => Profile::getDefault()
            ]);

            $entrand = mt_rand();
            echo "</td><td><label for='dropdown__entities_id$entrand'>" .  Entity::getTypeName(1) . "</label></td><td>";
            Entity::dropdown(['name'                => '_entities_id',
                'display_emptychoice' => false,
                'rand'                => $entrand,
                'entity'              => $_SESSION['glpiactiveentities']
            ]);
            echo "</td></tr>";
        } else {
            if ($higherrights || $ismyself) {
                $profilerand = mt_rand();
                echo "<tr class='tab_bg_1'>";
                echo "<td><label for='dropdown_profiles_id$profilerand'>" .  __('Default profile') . "</label></td><td>";

                $options   = Dropdown::getDropdownArrayNames(
                    'glpi_profiles',
                    Profile_User::getUserProfiles($this->fields['id'])
                );

                Dropdown::showFromArray(
                    "profiles_id",
                    $options,
                    ['value'               => $this->fields["profiles_id"],
                        'rand'                => $profilerand,
                        'display_emptychoice' => true
                    ]
                );
            }
            if ($higherrights) {
                $entrand = mt_rand();
                echo "</td><td><label for='dropdown_entities_id$entrand'>" .  __('Default entity') . "</label></td><td>";
                $entities = $this->getEntities();
                $toadd = [-1 => __('Full structure')];
                Entity::dropdown([
                    'value'  => ($this->fields['entities_id'] === null) ? -1 : $this->fields['entities_id'],
                    'rand'   => $entrand,
                    'entity' => $entities,
                    'toadd'  => $toadd,
                ]);
                echo "</td></tr>";

                $grouprand = mt_rand();
                echo "<tr class='tab_bg_1'>";
                echo "<td><label for='dropdown_profiles_id$grouprand'>" .  __('Default group') . "</label></td><td>";

                $options = [];
                foreach (Group_User::getUserGroups($this->fields['id']) as $group) {
                     $options[$group['id']] = $group['completename'];
                }

                Dropdown::showFromArray(
                    "groups_id",
                    $options,
                    ['value'               => $this->fields["groups_id"],
                        'rand'                => $grouprand,
                        'display_emptychoice' => true
                    ]
                );

                echo "</td>";
                $userrand = mt_rand();
                echo "<td><label for='dropdown_users_id_supervisor_$userrand'>" .  __('Responsible') . "</label></td><td>";

                User::dropdown(['name'   => 'users_id_supervisor',
                    'value'         => $this->fields["users_id_supervisor"],
                    'rand'          => $userrand,
                    'entity'        => $_SESSION["glpiactive_entity"],
                    'entity_sons'   => $_SESSION["glpiactive_entity_recursive"],
                    'used'          => [$this->getID()],
                    'right'         => 'all'
                ]);
                echo "</td></tr>";
            }

            if (
                (
                    Entity::getAnonymizeConfig() == Entity::ANONYMIZE_USE_NICKNAME
                    || Entity::getAnonymizeConfig() == Entity::ANONYMIZE_USE_NICKNAME_USER
                )
                && Session::getCurrentInterface() == "central"
            ) {
                echo "<tr class='tab_bg_1'>";
                echo "<td><label for='nickname$rand'> " . __('Nickname') . "</label></td>";
                echo "<td>";
                if ($this->can($ID, UPDATE)) {
                    echo Html::input('nickname', [
                        'value' => $this->fields['nickname']
                    ]);
                } else {
                    echo $this->fields['nickname'];
                }
                echo "</td>";
                echo "</tr>";
            }

            if ($caneditpassword) {
                echo "<tr class='tab_bg_1'><th colspan='4'>" . __('Remote access keys') . "</th></tr>";

                echo "<tr class='tab_bg_1'><td>";
                echo __("API token");
                echo "</td><td colspan='2'>";
                if (!empty($this->fields["api_token"])) {
                     echo "<div class='copy_to_clipboard_wrapper'>";
                     echo Html::input('_api_token', [
                         'value'    => $this->fields["api_token"],
                         'style'    => 'width:90%'
                     ]);
                     echo "</div>";
                     echo "(" . sprintf(
                         __('generated on %s'),
                         Html::convDateTime($this->fields["api_token_date"])
                     ) . ")";
                }
                echo "</td><td>";
                Html::showCheckbox(['name'  => '_reset_api_token',
                    'title' => __('Regenerate')
                ]);
                echo "&nbsp;&nbsp;" . __('Regenerate');
                echo "</td></tr>";
            }

            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='2' class='center'>";
            if ($this->fields["last_login"]) {
                printf(__('Last login on %s'), Html::convDateTime($this->fields["last_login"]));
            }
            echo "</td><td colspan='2'class='center'>";

            echo "</td></tr>";
        }

        $this->showFormButtons($options);

        return true;
    }


    /**
     * Print the user preference form.
     *
     * @param string  $target Form target
     * @param integer $ID     ID of the user
     *
     * @return boolean true if user found, false otherwise
     */
    public function showMyForm($target, $ID)
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

       // Affiche un formulaire User
        if (
            ($ID != Session::getLoginUserID())
            && !$this->currentUserHaveMoreRightThan($ID)
        ) {
            return false;
        }
        if ($this->getFromDB($ID)) {
            $rand     = mt_rand();
            $authtype = $this->getAuthMethodsByID();

            $extauth  = !(($this->fields["authtype"] == Auth::DB_GLPI)
                       || (($this->fields["authtype"] == Auth::NOT_YET_AUTHENTIFIED)
                           && !empty($this->fields["password"])));

            echo "<div class='center'>";
            echo "<form method='post' name='user_manager' enctype='multipart/form-data' action='" . $target . "' autocomplete='off'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th colspan='4'>" . sprintf(__('%1$s: %2$s'), __('Login'), $this->fields["name"]);
            echo "<input type='hidden' name='name' value='" . $this->fields["name"] . "'>";
            echo "<input type='hidden' name='id' value='" . $this->fields["id"] . "'>";
            echo "</th></tr>";

            $surnamerand = mt_rand();
            echo "<tr class='tab_bg_1'><td><label for='textfield_realname$surnamerand'>" . __('Surname') . "</label></td><td>";

            if (
                $extauth
                && isset($authtype['realname_field'])
                && !empty($authtype['realname_field'])
            ) {
                echo $this->fields["realname"];
            } else {
                echo Html::input(
                    'realname',
                    [
                        'value' => $this->fields['realname'],
                        'id'    => "textfield_realname$surnamerand",
                    ]
                );
            }
            echo "</td>";

            if (!empty($this->fields["name"])) {
                echo "<td rowspan='7'>" . _n('Picture', 'Pictures', 1) . "</td>";
                echo "<td rowspan='7'>";
                echo self::getPictureForUser($ID);

                echo Html::file(['name' => 'picture', 'display' => false, 'onlyimages' => true]);

                echo "&nbsp;";
                Html::showCheckbox(['name' => '_blank_picture', 'title' => __('Clear')]);
                echo "&nbsp;" . __('Clear');

                echo "</td>";
                echo "</tr>";
            }

            $firstnamerand = mt_rand();
            echo "<tr class='tab_bg_1'><td><label for='textfield_firstname$firstnamerand'>" . __('First name') . "</label></td><td>";
            if (
                $extauth
                && isset($authtype['firstname_field'])
                && !empty($authtype['firstname_field'])
            ) {
                echo $this->fields["firstname"];
            } else {
                echo Html::input(
                    'firstname',
                    [
                        'value' => $this->fields['firstname'],
                        'id'    => "textfield_firstname$firstnamerand",
                    ]
                );
            }
            echo "</td></tr>";

            if (
                $extauth
                && $this->fields['auths_id']
                && AuthLDAP::isSyncFieldConfigured($this->fields['auths_id'])
            ) {
                echo "<tr class='tab_bg_1'><td>" . __('Synchronization field') . "</td><td>";
                if (empty($this->fields['sync_field'])) {
                    echo Dropdown::EMPTY_VALUE;
                } else {
                    echo $this->fields['sync_field'];
                }
                echo "</td></tr>";
            } else {
                echo "<tr class='tab_bg_1'><td colspan='2'></td></tr>";
            }

            echo "<tr class='tab_bg_1'>";

            if (!GLPI_DEMO_MODE) {
                $langrand = mt_rand();
                echo "<td><label for='dropdown_language$langrand'>" . __('Language') . "</label></td><td>";
               // Language is stored as null in DB if value is same as the global config.
                $language = $this->fields["language"];
                if (null === $this->fields["language"] || !isset($CFG_GLPI['languages'][$this->fields["language"]])) {
                    $language = $CFG_GLPI['language'];
                }
                Dropdown::showLanguages(
                    "language",
                    [
                        'rand'  => $langrand,
                        'value' => $language,
                    ]
                );
                echo "</td>";
            } else {
                echo "<td colspan='2'>&nbsp;</td>";
            }
            echo "</tr>";

           //do some rights verification
            if (
                !$extauth
                && Session::haveRight("password_update", "1")
            ) {
                echo "<tr class='tab_bg_1'>";
                echo "<td><label for='password'>" . __('Password') . "</label></td>";
                echo "<td><input id='password' type='password' name='password' value='' size='30' autocomplete='new-password' onkeyup=\"return passwordCheck();\" class='form-control'>";
                echo "</td>";
                echo "</tr>";

                echo "<tr class='tab_bg_1'>";
                echo "<td><label for='password2'>" . __('Password confirmation') . "</label></td>";
                echo "<td><input type='password' name='password2' id='password2' value='' size='30' autocomplete='new-password' class='form-control'>";
                echo "</td></tr>";

                if ($CFG_GLPI["use_password_security"]) {
                    echo "<tr class='tab_bg_1'>";
                    echo "<td rowspan='2'>";
                    echo __('Password security policy');
                    echo "</td>";
                    echo "<td rowspan='2'>";
                    Config::displayPasswordSecurityChecks();
                    echo "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr class='tab_bg_1'><td colspan='2'></td></tr>";
                echo "<tr class='tab_bg_1'><td colspan='2'></td></tr>";
                echo "<tr class='tab_bg_1'><td colspan='2'></td></tr>";
            }

            if ($DB->use_timezones || Session::haveRight("config", READ)) {
                echo "<tr class='tab_bg_1'>";
                echo "<td><label for='timezone'>" . __('Time zone') . "</label></td><td>";
                if ($DB->use_timezones) {
                    $timezones = $DB->getTimezones();
                    Dropdown::showFromArray(
                        'timezone',
                        $timezones,
                        [
                            'value'                 => $this->fields["timezone"],
                            'display_emptychoice'   => true,
                            'emptylabel'            => __('Use server configuration')
                        ]
                    );
                } else if (Session::haveRight("config", READ)) {
                   // Display a warning but only if user is more or less an admin
                    echo __('Timezone usage has not been activated.')
                    . ' '
                    . sprintf(__('Run the "%1$s" command to activate it.'), 'php bin/console database:enable_timezones');
                }
                echo "</td>";
                if (
                    $extauth
                    || !Session::haveRight("password_update", "1")
                ) {
                    echo "<td colspan='2'></td>";
                }
                echo "</tr>";
            }

            $phonerand = mt_rand();
            echo "<tr class='tab_bg_1'><td><label for='textfield_phone$phonerand'>" .  Phone::getTypeName(1) . "</label></td><td>";

            if (
                $extauth
                && isset($authtype['phone_field']) && !empty($authtype['phone_field'])
            ) {
                echo $this->fields["phone"];
            } else {
                echo Html::input(
                    'phone',
                    [
                        'value' => $this->fields['phone'],
                        'id'    => "textfield_phone$phonerand",
                    ]
                );
            }
            echo "</td>";
            echo "<td class='top'>" . _n('Email', 'Emails', Session::getPluralNumber());
            UserEmail::showAddEmailButton($this);
            echo "</td><td>";
            UserEmail::showForUser($this);
            echo "</td>";
            echo "</tr>";

            $mobilerand = mt_rand();
            echo "<tr class='tab_bg_1'><td><label for='textfield_mobile$mobilerand'>" . __('Mobile phone') . "</label></td><td>";

            if (
                $extauth
                && isset($authtype['mobile_field']) && !empty($authtype['mobile_field'])
            ) {
                echo $this->fields["mobile"];
            } else {
                echo Html::input(
                    'mobile',
                    [
                        'value' => $this->fields['mobile'],
                        'id'    => "textfield_mobile$mobilerand",
                    ]
                );
            }
            echo "</td>";

            if (count($_SESSION['glpiprofiles']) > 1) {
                $profilerand = mt_rand();
                echo "<td><label for='dropdown_profiles_id$profilerand'>" . __('Default profile') . "</label></td><td>";

                $options = Dropdown::getDropdownArrayNames(
                    'glpi_profiles',
                    Profile_User::getUserProfiles($this->fields['id'])
                );
                Dropdown::showFromArray(
                    "profiles_id",
                    $options,
                    ['value'               => $this->fields["profiles_id"],
                        'rand'                => $profilerand,
                        'display_emptychoice' => true
                    ]
                );
                echo "</td>";
            } else {
                echo "<td colspan='2'>&nbsp;</td>";
            }
            echo "</tr>";

            $phone2rand = mt_rand();
            echo "<tr class='tab_bg_1'><td><label for='textfield_phone2$phone2rand'>" .  __('Phone 2') . "</label></td><td>";

            if (
                $extauth
                && isset($authtype['phone2_field']) && !empty($authtype['phone2_field'])
            ) {
                echo $this->fields["phone2"];
            } else {
                echo Html::input(
                    'phone2',
                    [
                        'value' => $this->fields['phone2'],
                        'id'    => "textfield_phone2$phone2rand",
                    ]
                );
            }
            echo "</td>";

            $entities = $this->getEntities();
            if (
                !GLPI_DEMO_MODE
                && (count($_SESSION['glpiactiveentities']) > 1)
            ) {
                $entrand = mt_rand();
                echo "<td><label for='dropdown_entities_id$entrand'>" . __('Default entity') . "</td><td>";
                $toadd = [-1 => __('Full structure')];
                Entity::dropdown([
                    'value'  => ($this->fields['entities_id'] === null) ? -1 : $this->fields['entities_id'],
                    'rand'   => $entrand,
                    'entity' => $entities,
                    'toadd'  => $toadd,
                ]);
            } else {
                echo "<td colspan='2'>&nbsp;";
            }
            echo "</td></tr>";

            $admnumrand = mt_rand();
            echo "<tr class='tab_bg_1'><td><label for='textfield_registration_number$admnumrand'>" . _x('user', 'Administrative number') . "</label></td><td>";
            if (
                $extauth
                && isset($authtype['registration_number_field']) && !empty($authtype['registration_number_field'])
            ) {
                echo $this->fields["registration_number"];
            } else {
                echo Html::input(
                    'registration_number',
                    [
                        'value' => $this->fields['registration_number'],
                        'id'    => "textfield_registration_number$admnumrand",
                    ]
                );
            }
            echo "</td><td colspan='2'></td></tr>";

            $locrand = mt_rand();
            echo "<tr class='tab_bg_1'><td><label for='dropdown_locations_id$locrand'>" . Location::getTypeName(1) . "</label></td><td>";
            Location::dropdown(['value'  => $this->fields['locations_id'],
                'rand'   => $locrand,
                'entity' => $entities
            ]);

            if (Config::canUpdate()) {
                $moderand = mt_rand();
                echo "<td><label for='dropdown_use_mode$moderand'>" . __('Use GLPI in mode') . "</label></td><td>";
                $modes = [
                    Session::NORMAL_MODE => __('Normal'),
                    Session::DEBUG_MODE  => __('Debug'),
                ];
                Dropdown::showFromArray('use_mode', $modes, ['value' => $this->fields["use_mode"], 'rand' => $moderand]);
            } else {
                echo "<td colspan='2'>&nbsp;";
            }
            echo "</td></tr>";

            if (
                (
                    Entity::getAnonymizeConfig() == Entity::ANONYMIZE_USE_NICKNAME
                    || Entity::getAnonymizeConfig() == Entity::ANONYMIZE_USE_NICKNAME_USER
                )
                && Session::getCurrentInterface() == "central"
            ) {
                echo "<tr class='tab_bg_1'>";
                echo "<td><label for='nickname$rand'> " . __('Nickname') . "</label></td>";
                echo "<td>";
                echo Html::input('nickname', [
                    'value' => $this->fields['nickname']
                ]);
                echo "</td>";
                echo "</tr>";
            }

            echo "<tr class='tab_bg_1'><th colspan='4'>" . __('Remote access keys') . "</th></tr>";

            echo "<tr class='tab_bg_1'><td>";
            echo __("API token");
            echo "</td><td colspan='2'>";
            if (!empty($this->fields["api_token"])) {
                echo "<div class='copy_to_clipboard_wrapper'>";
                echo Html::input('_api_token', [
                    'value'    => $this->fields["api_token"],
                    'style'    => 'width:90%'
                ]);
                 echo "</div>";
                 echo "(" . sprintf(
                     __('generated on %s'),
                     Html::convDateTime($this->fields["api_token_date"])
                 ) . ")";
            }
            echo "</td><td>";
            Html::showCheckbox(['name'  => '_reset_api_token',
                'title' => __('Regenerate')
            ]);
            echo "&nbsp;&nbsp;" . __('Regenerate');
            echo "</td></tr>";

            echo "<tr><td class='tab_bg_2 center' colspan='4'>";
            echo "<input type='submit' name='update' value=\"" . _sx('button', 'Save') . "\" class='btn btn-primary'>";
            echo "</td></tr>";

            echo "</table>";
            Html::closeForm();
            echo "</div>";
            return true;
        }
        return false;
    }


    /**
     * Get all the authentication method parameters for the current user.
     *
     * @return array
     */
    public function getAuthMethodsByID()
    {
        return Auth::getMethodsByID($this->fields["authtype"], $this->fields["auths_id"]);
    }


    public function pre_updateInDB()
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (($key = array_search('name', $this->updates)) !== false) {
           /// Check if user does not exists
            $iterator = $DB->request([
                'FROM'   => $this->getTable(),
                'WHERE'  => [
                    'name'   => $this->input['name'],
                    'id'     => ['<>', $this->input['id']]
                ]
            ]);

            if (count($iterator)) {
                 //To display a message
                 $this->fields['name'] = $this->oldvalues['name'];
                 unset($this->updates[$key]);
                 unset($this->oldvalues['name']);
                 Session::addMessageAfterRedirect(
                     __('Unable to update login. A user already exists.'),
                     false,
                     ERROR
                 );
            }

            if (!Auth::isValidLogin(stripslashes($this->input['name']))) {
                $this->fields['name'] = $this->oldvalues['name'];
                unset($this->updates[$key]);
                unset($this->oldvalues['name']);
                Session::addMessageAfterRedirect(
                    __('The login is not valid. Unable to update login.'),
                    false,
                    ERROR
                );
            }
        }

       // ## Security system except for login update:
       //
       // An **external** (ldap, mail) user without User::UPDATE right
       // should not be able to update its own fields
       // (for example, fields concerned by ldap synchronisation)
       // except on login action (which triggers synchronisation).
        if (
            Session::getLoginUserID() === (int)$this->input['id']
            && !Session::haveRight("user", UPDATE)
            && !strpos($_SERVER['PHP_SELF'], "/front/login.php")
            && isset($this->fields["authtype"])
        ) {
           // extauth ldap case
            if (
                $_SESSION["glpiextauth"]
                && ($this->fields["authtype"] == Auth::LDAP
                 || Auth::isAlternateAuth($this->fields["authtype"]))
            ) {
                $authtype = Auth::getMethodsByID(
                    $this->fields["authtype"],
                    $this->fields["auths_id"]
                );
                if (count($authtype)) {
                     $fields = AuthLDAP::getSyncFields($authtype);
                    foreach ($fields as $key => $val) {
                        if (
                            !empty($val)
                            && (($key2 = array_search($key, $this->updates)) !== false)
                        ) {
                            unset($this->updates[$key2]);
                            unset($this->oldvalues[$key]);
                        }
                    }
                }
            }

            if (($key = array_search("is_active", $this->updates)) !== false) {
                unset($this->updates[$key]);
                unset($this->oldvalues['is_active']);
            }

            if (($key = array_search("comment", $this->updates)) !== false) {
                unset($this->updates[$key]);
                unset($this->oldvalues['comment']);
            }
        }

        // Hash user_dn if is updated
        if (in_array('user_dn', $this->updates)) {
            $this->updates[] = 'user_dn_hash';
            $this->fields['user_dn_hash'] = is_string($this->input['user_dn']) && strlen($this->input['user_dn']) > 0
                ? md5(Sanitizer::unsanitize($this->input['user_dn']))
                : null;
        }
    }

    public function getSpecificMassiveActions($checkitem = null)
    {

        $isadmin = static::canUpdate();
        $actions = parent::getSpecificMassiveActions($checkitem);
        if ($isadmin) {
            $actions['Group_User' . MassiveAction::CLASS_ACTION_SEPARATOR . 'add']
                                                         = "<i class='fas fa-users'></i>" .
                                                           __('Associate to a group');
            $actions['Group_User' . MassiveAction::CLASS_ACTION_SEPARATOR . 'remove']
                                                         = __('Dissociate from a group');
            $actions['Profile_User' . MassiveAction::CLASS_ACTION_SEPARATOR . 'add']
                                                         = "<i class='fas fa-user-shield'></i>" .
                                                           __('Associate to a profile');
            $actions['Profile_User' . MassiveAction::CLASS_ACTION_SEPARATOR . 'remove']
                                                         = __('Dissociate from a profile');
            $actions['Group_User' . MassiveAction::CLASS_ACTION_SEPARATOR . 'change_group_user']
                                                         = "<i class='fas fa-users-cog'></i>" .
                                                           __("Move to group");
        }

        if (Session::haveRight(self::$rightname, self::UPDATEAUTHENT)) {
            $prefix                                    = __CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR;
            $actions[$prefix . 'change_authtype']        = "<i class='fas fa-user-cog'></i>" .
                                                      _x('button', 'Change the authentication method');
            $actions[$prefix . 'force_user_ldap_update'] = "<i class='fas fa-sync'></i>" .
                                                      __('Force synchronization');
            $actions[$prefix . 'clean_ldap_fields'] = "<i class='fas fa-broom'></i>" .
                                                    __('Clean LDAP fields and force synchronisation');
        }
        return $actions;
    }

    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        switch ($ma->getAction()) {
            case 'change_authtype':
                $rand             = Auth::dropdown(['name' => 'authtype']);
                $paramsmassaction = ['authtype' => '__VALUE__'];
                Ajax::updateItemOnSelectEvent(
                    "dropdown_authtype$rand",
                    "show_massiveaction_field",
                    $CFG_GLPI["root_doc"] .
                                             "/ajax/dropdownMassiveActionAuthMethods.php",
                    $paramsmassaction
                );
                echo "<span id='show_massiveaction_field'><br><br>";
                echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']) . "</span>";
                return true;
        }
        return parent::showMassiveActionsSubForm($ma);
    }

    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {

        switch ($ma->getAction()) {
            case 'force_user_ldap_update':
            case 'clean_ldap_fields':
                foreach ($ids as $id) {
                    if ($item->can($id, UPDATE)) {
                        if (
                            ($item->fields["authtype"] == Auth::LDAP)
                            || ($item->fields["authtype"] == Auth::EXTERNAL)
                        ) {
                            if (AuthLDAP::forceOneUserSynchronization($item, ($ma->getAction() == 'clean_ldap_fields'), false)) {
                                $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                            } else {
                                $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                                $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                            }
                        } else {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                            $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                        }
                    } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                        $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                    }
                }
                return;

            case 'change_authtype':
                $input = $ma->getInput();
                if (
                    !isset($input["authtype"])
                    || !isset($input["auths_id"])
                ) {
                    $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
                    $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                    return;
                }
                if (Session::haveRight(self::$rightname, self::UPDATEAUTHENT)) {
                    if (User::changeAuthMethod($ids, $input["authtype"], $input["auths_id"])) {
                        $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_OK);
                    } else {
                        $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
                    }
                } else {
                    $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_NORIGHT);
                    $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                }
                return;
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }


    public function rawSearchOptions()
    {
       // forcegroup by on name set force group by for all items
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics')
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => $this->getTable(),
            'field'              => 'name',
            'name'               => __('Login'),
            'datatype'           => 'itemlink',
            'forcegroupby'       => true,
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number'
        ];

        $tab[] = [
            'id'                 => '34',
            'table'              => $this->getTable(),
            'field'              => 'realname',
            'name'               => __('Last name'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '9',
            'table'              => $this->getTable(),
            'field'              => 'firstname',
            'name'               => __('First name'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => 'glpi_useremails',
            'field'              => 'email',
            'name'               => _n('Email', 'Emails', Session::getPluralNumber()),
            'datatype'           => 'email',
            'joinparams'         => [
                'jointype'           => 'child'
            ],
            'forcegroupby'       => true,
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '150',
            'table'              => $this->getTable(),
            'field'              => 'picture',
            'name'               => _n('Picture', 'Pictures', 1),
            'datatype'           => 'specific',
            'nosearch'           => true,
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '28',
            'table'              => $this->getTable(),
            'field'              => 'sync_field',
            'name'               => __('Synchronization field'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

        $tab[] = [
            'id'                 => '8',
            'table'              => $this->getTable(),
            'field'              => 'is_active',
            'name'               => __('Active'),
            'datatype'           => 'bool'
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => $this->getTable(),
            'field'              => 'phone',
            'name'               => Phone::getTypeName(1),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '10',
            'table'              => $this->getTable(),
            'field'              => 'phone2',
            'name'               => __('Phone 2'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '11',
            'table'              => $this->getTable(),
            'field'              => 'mobile',
            'name'               => __('Mobile phone'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '13',
            'table'              => 'glpi_groups',
            'field'              => 'completename',
            'name'               => Group::getTypeName(Session::getPluralNumber()),
            'forcegroupby'       => true,
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
            'use_subquery'       => true,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_groups_users',
                    'joinparams'         => [
                        'jointype'           => 'child'
                    ]
                ]
            ]
        ];

        $tab[] = [
            'id'                 => '14',
            'table'              => $this->getTable(),
            'field'              => 'last_login',
            'name'               => __('Last login'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '15',
            'table'              => $this->getTable(),
            'field'              => 'authtype',
            'name'               => __('Authentication'),
            'massiveaction'      => false,
            'datatype'           => 'specific',
            'searchtype'         => 'equals',
            'additionalfields'   => [
                '0'                  => 'auths_id'
            ]
        ];

        $tab[] = [
            'id'                 => '30',
            'table'              => 'glpi_authldaps',
            'field'              => 'name',
            'linkfield'          => 'auths_id',
            'name'               => __('LDAP directory for authentication'),
            'massiveaction'      => false,
            'joinparams'         => [
                'condition'          => ['REFTABLE.authtype' => Auth::LDAP]
            ],
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '31',
            'table'              => 'glpi_authmails',
            'field'              => 'name',
            'linkfield'          => 'auths_id',
            'name'               => __('Email server for authentication'),
            'massiveaction'      => false,
            'joinparams'         => [
                'condition'          => ['REFTABLE.authtype' => Auth::MAIL]
            ],
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => $this->getTable(),
            'field'              => 'comment',
            'name'               => __('Comments'),
            'datatype'           => 'text'
        ];

        $tab[] = [
            'id'                 => '17',
            'table'              => $this->getTable(),
            'field'              => 'language',
            'name'               => __('Language'),
            'datatype'           => 'language',
            'display_emptychoice' => true,
            'emptylabel'         => 'Default value'
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => $this->getTable(),
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '121',
            'table'              => $this->getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '20',
            'table'              => 'glpi_profiles',
            'field'              => 'name',
            'name'               => sprintf(
                __('%1$s (%2$s)'),
                Profile::getTypeName(Session::getPluralNumber()),
                Entity::getTypeName(1)
            ),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_profiles_users',
                    'joinparams'         => [
                        'jointype'           => 'child'
                    ]
                ]
            ]
        ];

        $tab[] = [
            'id'                 => '21',
            'table'              => $this->getTable(),
            'field'              => 'user_dn',
            'name'               => __('User DN'),
            'massiveaction'      => false,
            'datatype'           => 'text'
        ];

        $tab[] = [
            'id'                 => '22',
            'table'              => $this->getTable(),
            'field'              => 'registration_number',
            'name'               => _x('user', 'Administrative number'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '23',
            'table'              => $this->getTable(),
            'field'              => 'date_sync',
            'datatype'           => 'datetime',
            'name'               => __('Last synchronization'),
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '24',
            'table'              => $this->getTable(),
            'field'              => 'is_deleted_ldap',
            'name'               => __('Deleted user in LDAP directory'),
            'datatype'           => 'bool',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => 'glpi_entities',
            'linkfield'          => 'entities_id',
            'field'              => 'completename',
            'name'               => sprintf(
                __('%1$s (%2$s)'),
                Entity::getTypeName(Session::getPluralNumber()),
                Profile::getTypeName(1)
            ),
            'forcegroupby'       => true,
            'datatype'           => 'dropdown',
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_profiles_users',
                    'joinparams'         => [
                        'jointype'           => 'child'
                    ]
                ]
            ]
        ];

        $tab[] = [
            'id'                 => '81',
            'table'              => 'glpi_usertitles',
            'field'              => 'name',
            'name'               => __('Title'),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '82',
            'table'              => 'glpi_usercategories',
            'field'              => 'name',
            'name'               => _n('Category', 'Categories', 1),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '79',
            'table'              => 'glpi_profiles',
            'field'              => 'name',
            'name'               => __('Default profile'),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '77',
            'table'              => 'glpi_entities',
            'field'              => 'name',
            'massiveaction'      => true,
            'name'               => __('Default entity'),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '62',
            'table'              => $this->getTable(),
            'field'              => 'begin_date',
            'name'               => __('Begin date'),
            'datatype'           => 'datetime'
        ];

        $tab[] = [
            'id'                 => '63',
            'table'              => $this->getTable(),
            'field'              => 'end_date',
            'name'               => __('End date'),
            'datatype'           => 'datetime'
        ];

        $tab[] = [
            'id'                 => '60',
            'table'              => 'glpi_tickets',
            'field'              => 'id',
            'name'               => __('Number of tickets as requester'),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'datatype'           => 'count',
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_tickets_users',
                    'joinparams'         => [
                        'jointype'           => 'child',
                        'condition'          => ['NEWTABLE.type' => CommonITILActor::REQUESTER]
                    ]
                ]
            ]
        ];

        $tab[] = [
            'id'                 => '61',
            'table'              => 'glpi_tickets',
            'field'              => 'id',
            'name'               => __('Number of written tickets'),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'datatype'           => 'count',
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child',
                'linkfield'          => 'users_id_recipient'
            ]
        ];

        $tab[] = [
            'id'                 => '64',
            'table'              => 'glpi_tickets',
            'field'              => 'id',
            'name'               => __('Number of assigned tickets'),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'datatype'           => 'count',
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_tickets_users',
                    'joinparams'         => [
                        'jointype'           => 'child',
                        'condition'          => ['NEWTABLE.type' => CommonITILActor::ASSIGN]
                    ]
                ]
            ]
        ];

        $tab[] = [
            'id'                 => '99',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'linkfield'          => 'users_id_supervisor',
            'name'               => __('Responsible'),
            'datatype'           => 'dropdown',
            'massiveaction'      => false,
            'additionalfields'   => [
                '0' => 'id'
            ]
        ];

       // add objectlock search options
        $tab = array_merge($tab, ObjectLock::rawSearchOptionsToAdd(get_class($this)));

        return $tab;
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'authtype':
                $auths_id = 0;
                if (isset($values['auths_id']) && !empty($values['auths_id'])) {
                    $auths_id = $values['auths_id'];
                }
                return Auth::getMethodName($values[$field], $auths_id);
            case 'picture':
                if (isset($options['html']) && $options['html']) {
                    return Html::image(
                        self::getThumbnailURLForPicture($values['picture']),
                        ['class' => 'user_picture_small', 'alt' => _n('Picture', 'Pictures', 1)]
                    );
                }
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
            case 'authtype':
                $options['name'] = $name;
                $options['value'] = $values[$field];
                return Auth::dropdown($options);
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }


    /**
     * Get all groups where the current user have delegating.
     *
     * @since 0.83
     *
     * @param integer|string $entities_id ID of the entity to restrict
     *
     * @return integer[]
     */
    public static function getDelegateGroupsForUser($entities_id = '')
    {
        /** @var \DBmysql $DB */
        global $DB;

        $iterator = $DB->request([
            'SELECT'          => 'glpi_groups_users.groups_id',
            'DISTINCT'        => true,
            'FROM'            => 'glpi_groups_users',
            'INNER JOIN'      => [
                'glpi_groups'  => [
                    'FKEY'   => [
                        'glpi_groups_users'  => 'groups_id',
                        'glpi_groups'        => 'id'
                    ]
                ]
            ],
            'WHERE'           => [
                'glpi_groups_users.users_id'        => Session::getLoginUserID(),
                'glpi_groups_users.is_userdelegate' => 1
            ] + getEntitiesRestrictCriteria('glpi_groups', '', $entities_id, 1)
        ]);

        $groups = [];
        foreach ($iterator as $data) {
            $groups[$data['groups_id']] = $data['groups_id'];
        }
        return $groups;
    }


    /**
     * Execute the query to select box with all glpi users where select key = name
     *
     * Internaly used by showGroup_Users, dropdownUsers and ajax/getDropdownUsers.php
     *
     * @param boolean         $count            true if execute an count(*) (true by default)
     * @param string|string[] $right            limit user who have specific right (default 'all')
     * @param integer         $entity_restrict  Restrict to a defined entity (default -1)
     * @param integer         $value            default value (default 0)
     * @param integer[]       $used             Already used items ID: not to display in dropdown
     * @param string          $search           pattern (default '')
     * @param integer         $start            start LIMIT value (default 0)
     * @param integer         $limit            limit LIMIT value (default -1 no limit)
     * @param boolean         $inactive_deleted true to retrieve also inactive or deleted users
     *
     * @return DBmysqlIterator
     */
    public static function getSqlSearchResult(
        $count = true,
        $right = "all",
        $entity_restrict = -1,
        $value = 0,
        array $used = [],
        $search = '',
        $start = 0,
        $limit = -1,
        $inactive_deleted = false,
        $with_no_right = 0
    ) {
        /** @var \DBmysql $DB */
        global $DB;



       // No entity define : use active ones
        if ($entity_restrict < 0) {
            $entity_restrict = $_SESSION["glpiactiveentities"];
        }

        $joinprofile      = false;
        $joinprofileright = false;
        $WHERE = [];


        switch ($right) {
            case "interface":
                $joinprofile = true;
                $WHERE = [
                    'glpi_profiles.interface' => 'central'
                ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_restrict, 1);
                break;

            case "id":
                $WHERE = ['glpi_users.id' => Session::getLoginUserID()];
                break;

            case "delegate":
                $groups = self::getDelegateGroupsForUser($entity_restrict);
                $users  = [];
                if (count($groups)) {
                    $iterator = $DB->request([
                        'SELECT'    => 'glpi_users.id',
                        'FROM'      => 'glpi_groups_users',
                        'LEFT JOIN' => [
                            'glpi_users'   => [
                                'FKEY'   => [
                                    'glpi_groups_users'  => 'users_id',
                                    'glpi_users'         => 'id'
                                ]
                            ]
                        ],
                        'WHERE'     => [
                            'glpi_groups_users.groups_id' => $groups,
                            'glpi_groups_users.users_id'  => ['<>', Session::getLoginUserID()]
                        ]
                    ]);
                    foreach ($iterator as $data) {
                           $users[$data["id"]] = $data["id"];
                    }
                }
               // Add me to users list for central
                if (Session::getCurrentInterface() == 'central') {
                    $users[Session::getLoginUserID()] = Session::getLoginUserID();
                }

                if (count($users)) {
                    $WHERE = ['glpi_users.id' => $users];
                } else {
                    $WHERE = ['0'];
                }
                break;

            case "groups":
                $groups = [];
                if (isset($_SESSION['glpigroups'])) {
                    $groups = $_SESSION['glpigroups'];
                }
                $users  = [];
                if (count($groups)) {
                    $iterator = $DB->request([
                        'SELECT'    => 'glpi_users.id',
                        'FROM'      => 'glpi_groups_users',
                        'LEFT JOIN' => [
                            'glpi_users'   => [
                                'FKEY'   => [
                                    'glpi_groups_users'  => 'users_id',
                                    'glpi_users'         => 'id'
                                ]
                            ]
                        ],
                        'WHERE'     => [
                            'glpi_groups_users.groups_id' => $groups,
                            'glpi_groups_users.users_id'  => ['<>', Session::getLoginUserID()]
                        ]
                    ]);
                    foreach ($iterator as $data) {
                          $users[$data["id"]] = $data["id"];
                    }
                }
               // Add me to users list for central
                if (Session::getCurrentInterface() == 'central') {
                    $users[Session::getLoginUserID()] = Session::getLoginUserID();
                }

                if (count($users)) {
                    $WHERE = ['glpi_users.id' => $users];
                } else {
                    $WHERE = ['0'];
                }

                break;

            case "all":
                $WHERE = [
                    'glpi_users.id' => ['>', 0],
                    'OR' => getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_restrict, 1)
                ];

                if ($with_no_right) {
                    $WHERE['OR'][] = ['glpi_profiles_users.entities_id' => null];
                }
                break;

            default:
                $joinprofile = true;
                $joinprofileright = true;
                if (!is_array($right)) {
                    $right = [$right];
                }
                $forcecentral = true;

                $ORWHERE = [];
                foreach ($right as $r) {
                    switch ($r) {
                        case 'own_ticket':
                             $ORWHERE[] = [
                                 [
                                     'glpi_profilerights.name'     => 'ticket',
                                     'glpi_profilerights.rights'   => ['&', Ticket::OWN]
                                 ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_restrict, 1)
                             ];
                            break;

                        case 'create_ticket_validate':
                            $ORWHERE[] = [
                                [
                                    'glpi_profilerights.name'  => 'ticketvalidation',
                                    'OR'                       => [
                                        ['glpi_profilerights.rights'   => ['&', TicketValidation::CREATEREQUEST]],
                                        ['glpi_profilerights.rights'   => ['&', TicketValidation::CREATEINCIDENT]]
                                    ]
                                ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_restrict, 1)
                            ];
                            $forcecentral = false;
                            break;

                        case 'validate_request':
                            $ORWHERE[] = [
                                [
                                    'glpi_profilerights.name'     => 'ticketvalidation',
                                    'glpi_profilerights.rights'   => ['&', TicketValidation::VALIDATEREQUEST]
                                ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_restrict, 1)
                            ];
                            $forcecentral = false;
                            break;

                        case 'validate_incident':
                            $ORWHERE[] = [
                                [
                                    'glpi_profilerights.name'     => 'ticketvalidation',
                                    'glpi_profilerights.rights'   => ['&', TicketValidation::VALIDATEINCIDENT]
                                ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_restrict, 1)
                            ];
                            $forcecentral = false;
                            break;

                        case 'validate':
                            $ORWHERE[] = [
                                [
                                    'glpi_profilerights.name'     => 'changevalidation',
                                    'glpi_profilerights.rights'   => ['&', ChangeValidation::VALIDATE]
                                ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_restrict, 1)
                            ];
                            break;

                        case 'create_validate':
                            $ORWHERE[] = [
                                [
                                    'glpi_profilerights.name'     => 'changevalidation',
                                    'glpi_profilerights.rights'   => ['&', CREATE]
                                ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_restrict, 1)
                            ];
                            break;

                        case 'see_project':
                            $ORWHERE[] = [
                                [
                                    'glpi_profilerights.name'     => 'project',
                                    'glpi_profilerights.rights'   => ['&', Project::READMY]
                                ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_restrict, 1)
                            ];
                            break;

                        case 'faq':
                            $ORWHERE[] = [
                                [
                                    'glpi_profilerights.name'     => 'knowbase',
                                    'glpi_profilerights.rights'   => ['&', KnowbaseItem::READFAQ]
                                ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_restrict, 1)
                            ];
                            break;

                        default:
                           // Check read or active for rights
                            $ORWHERE[] = [
                                [
                                    'glpi_profilerights.name'     => $r,
                                    'glpi_profilerights.rights'   => [
                                        '&',
                                        READ | CREATE | UPDATE | DELETE | PURGE
                                    ]
                                ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_restrict, 1)
                            ];
                    }
                    if (in_array($r, Profile::$helpdesk_rights)) {
                        $forcecentral = false;
                    }
                }

                if (count($ORWHERE)) {
                    $WHERE[] = ['OR' => $ORWHERE];
                }

                if ($forcecentral) {
                    $WHERE['glpi_profiles.interface'] = 'central';
                }
        }

        if (!$inactive_deleted) {
            $WHERE = array_merge(
                $WHERE,
                [
                    'glpi_users.is_deleted' => 0,
                    'glpi_users.is_active'  => 1,
                    [
                        'OR' => [
                            ['glpi_users.begin_date' => null],
                            ['glpi_users.begin_date' => ['<', new QueryExpression('NOW()')]]
                        ]
                    ],
                    [
                        'OR' => [
                            ['glpi_users.end_date' => null],
                            ['glpi_users.end_date' => ['>', new QueryExpression('NOW()')]]
                        ]
                    ]

                ]
            );
        }

        if (
            (is_numeric($value) && $value)
            || count($used)
        ) {
            $WHERE[] = [
                'NOT' => [
                    'glpi_users.id' => $used
                ]
            ];
        }

        // remove helpdesk user
        $config = Config::getConfigurationValues('core');
        $WHERE[] = [
            'NOT' => [
                'glpi_users.id' => $config['system_user']
            ]
        ];

        $criteria = [
            'FROM'            => 'glpi_users',
            'LEFT JOIN'       => [
                'glpi_useremails'       => [
                    'ON' => [
                        'glpi_useremails' => 'users_id',
                        'glpi_users'      => 'id',
                        ['AND' => ['glpi_useremails.is_default' => 1]]
                    ]
                ],
                'glpi_profiles_users'   => [
                    'ON' => [
                        'glpi_profiles_users'   => 'users_id',
                        'glpi_users'            => 'id'
                    ]
                ]
            ]
        ];
        if ($count) {
            $criteria['SELECT'] = ['COUNT' => 'glpi_users.id AS CPT'];
            $criteria['DISTINCT'] = true;
        } else {
            $criteria['SELECT'] = ['glpi_users.*', 'glpi_useremails.email AS default_email'];
            $criteria['DISTINCT'] = true;
        }

        if ($joinprofile) {
            $criteria['LEFT JOIN']['glpi_profiles'] = [
                'ON' => [
                    'glpi_profiles_users'   => 'profiles_id',
                    'glpi_profiles'         => 'id'
                ]
            ];
            if ($joinprofileright) {
                $criteria['LEFT JOIN']['glpi_profilerights'] = [
                    'ON' => [
                        'glpi_profilerights' => 'profiles_id',
                        'glpi_profiles'      => 'id'
                    ]
                ];
            }
        }

        if (!$count) {
            if (strlen((string)$search) > 0) {
                $txt_search = Search::makeTextSearchValue($search);

                $firstname_field = $DB->quoteName(self::getTableField('firstname'));
                $realname_field = $DB->quoteName(self::getTableField('realname'));
                $fields = $_SESSION["glpinames_format"] == self::FIRSTNAME_BEFORE
                ? [$firstname_field, $realname_field]
                : [$realname_field, $firstname_field];

                $concat = new \QueryExpression(
                    'CONCAT(' . implode(',' . $DB->quoteValue(' ') . ',', $fields) . ')'
                    . ' LIKE ' . $DB->quoteValue($txt_search)
                );
                $WHERE[] = [
                    'OR' => [
                        'glpi_users.name'                => ['LIKE', $txt_search],
                        'glpi_users.realname'            => ['LIKE', $txt_search],
                        'glpi_users.firstname'           => ['LIKE', $txt_search],
                        'glpi_users.phone'               => ['LIKE', $txt_search],
                        'glpi_users.registration_number' => ['LIKE', $txt_search],
                        'glpi_useremails.email'          => ['LIKE', $txt_search],
                        $concat
                    ]
                ];
            }

            if ($_SESSION["glpinames_format"] == self::FIRSTNAME_BEFORE) {
                $criteria['ORDERBY'] = [
                    'glpi_users.firstname',
                    'glpi_users.realname',
                    'glpi_users.name'
                ];
            } else {
                $criteria['ORDERBY'] = [
                    'glpi_users.realname ASC',
                    'glpi_users.firstname ASC',
                    'glpi_users.name ASC',
                ];
            }

            if ($limit > 0) {
                $criteria['LIMIT'] = $limit;
                $criteria['START'] = $start;
            }
        }
        $criteria['WHERE'] = $WHERE;
        return $DB->request($criteria);
    }


    /**
     * Make a select box with all glpi users where select key = name
     *
     * @param $options array of possible options:
     *    - name             : string / name of the select (default is users_id)
     *    - value
     *    - values           : in case of select[multiple], pass the array of multiple values
     *    - right            : string / limit user who have specific right :
     *                             id -> only current user (default case);
     *                             interface -> central;
     *                             all -> all users;
     *                             specific right like Ticket::READALL, CREATE.... (is array passed one of all passed right is needed)
     *    - comments         : boolean / is the comments displayed near the dropdown (default true)
     *    - entity           : integer or array / restrict to a defined entity or array of entities
     *                          (default -1 : no restriction)
     *    - entity_sons      : boolean / if entity restrict specified auto select its sons
     *                          only available if entity is a single value not an array(default false)
     *    - all              : Nobody or All display for none selected
     *                             all=0 (default) -> Nobody
     *                             all=1 -> All
     *                             all=-1-> nothing
     *    - rand             : integer / already computed rand value
     *    - toupdate         : array / Update a specific item on select change on dropdown
     *                          (need value_fieldname, to_update, url
     *                          (see Ajax::updateItemOnSelectEvent for information)
     *                          and may have moreparams)
     *    - used             : array / Already used items ID: not to display in dropdown (default empty)
     *    - ldap_import
     *    - on_change        : string / value to transmit to "onChange"
     *    - display          : boolean / display or get string (default true)
     *    - width            : specific width needed
     *    - specific_tags    : array of HTML5 tags to add to the field
     *    - class            : class to pass to html select
     *    - url              : url of the ajax php code which should return the json data to show in
     *                         the dropdown (default /ajax/getDropdownUsers.php)
     *    - inactive_deleted : retreive also inactive or deleted users
     *    - hide_if_no_elements  : boolean / hide dropdown if there is no elements (default false)
     *    - readonly         : boolean / return getUserName is true (default false)
     *
     * @return integer|string Random value if displayed, string otherwise
     */
    public static function dropdown($options = [])
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;
       // Default values
        $p = [
            'name'                => 'users_id',
            'value'               => '',
            'values'              => [],
            'right'               => 'id',
            'all'                 => 0,
            'display_emptychoice' => true,
            'emptylabel'          => Dropdown::EMPTY_VALUE,
            'placeholder'         => '',
            'on_change'           => '',
            'comments'            => 1,
            'width'               => '',
            'entity'              => -1,
            'entity_sons'         => false,
            'used'                => [],
            'ldap_import'         => false,
            'toupdate'            => '',
            'rand'                => mt_rand(),
            'display'             => true,
            '_user_index'         => 0,
            'specific_tags'       => [],
            'class'               => "form-select",
            'url'                 => $CFG_GLPI['root_doc'] . "/ajax/getDropdownUsers.php",
            'inactive_deleted'    => 0,
            'with_no_right'       => 0,
            'toadd'               => [],
            'hide_if_no_elements' => false,
            'readonly'            => false,
            'multiple'            => false,
        ];

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        if ($p['multiple']) {
            $p['display_emptychoice'] = false;
            $p['values'] = $p['value'] ?? [];
            $p['comments'] = false;
            unset($p['value']);
        }

       // check default value (in case of multiple observers)
        if (isset($p['value']) && is_array($p['value'])) {
            $p['value'] = $p['value'][$p['_user_index']] ?? 0;
        }

       // Check default value for dropdown : need to be a numeric (or null)
        if (
            isset($p['value'])
            && ((strlen($p['value']) == 0) || !is_numeric($p['value']) && $p['value'] !== 'myself')
        ) {
            $p['value'] = 0;
        }

        $output = '';

        if (!($p['entity'] < 0) && $p['entity_sons']) {
            if (is_array($p['entity'])) {
                $output .= "entity_sons options is not available with array of entity";
            } else {
                $p['entity'] = getSonsOf('glpi_entities', $p['entity']);
            }
        }
        $p['entity'] = Session::getMatchingActiveEntities($p['entity']);

        // Make a select box with all glpi users
        $view_users = self::canView();

        $default = '';
        $valuesnames = [];

        if (!$p['multiple']) {
            /** @var array $user */
            $user = getUserName($p['value'], 2, true);

            if ($p['readonly']) {
                return '<span class="form-control" readonly>' . $user["name"] . '</span>';
            }

            if ($p['value'] === 'myself') {
                $default = __("Myself");
            } else if (!empty($p['value']) && ($p['value'] > 0)) {
                $default = $user["name"];
            } else {
                if ($p['all']) {
                    $default = __('All');
                } else {
                    $default = $p['emptylabel'];
                }
            }
        } else {
            // get multiple values name
            foreach ($p['values'] as $value) {
                if (!empty($value) && ($value > 0)) {
                    /** @var array $user */
                    $user = getUserName($value, 2);
                    $valuesnames[] = $user["name"];
                } else {
                    unset($p['values'][$value]);
                }
            }

            if ($p['readonly']) {
                return '<span class="form-control" readonly>' . implode(', ', $valuesnames) . '</span>';
            }
        }


        $field_id = Html::cleanId("dropdown_" . $p['name'] . $p['rand']);
        $param    = [
            'multiple'            => $p['multiple'],
            'width'               => $p['width'],
            'all'                 => $p['all'],
            'display_emptychoice' => $p['display_emptychoice'],
            'placeholder'         => $p['placeholder'],
            'right'               => $p['right'],
            'on_change'           => $p['on_change'],
            'used'                => $p['used'],
            'inactive_deleted'    => $p['inactive_deleted'],
            'with_no_right'       => $p['with_no_right'],
            'entity_restrict'     => ($entity_restrict = (is_array($p['entity']) ? json_encode(array_values($p['entity'])) : $p['entity'])),
            'specific_tags'       => $p['specific_tags'],
            'toadd'               => $p['toadd'],
            'class'               => $p['class'],
            '_idor_token'         => Session::getNewIDORToken(__CLASS__, [
                'right'           => $p['right'],
                'entity_restrict' => $entity_restrict,
            ]),
        ];

        if ($p['multiple']) {
            $param['values'] = $p['values'];
            $param['valuesnames'] = $valuesnames;
        } else {
            $param['value'] = $p['value'];
            $param['valuename'] = $default;
        }

        if ($p['hide_if_no_elements']) {
            $result = Dropdown::getDropdownUsers(
                ['display_emptychoice' => false, 'page' => 1, 'page_limit' => 1] + $param,
                false
            );
            if ($result['count'] === 0) {
                return '';
            }
        }

        $output = Html::jsAjaxDropdown(
            $p['name'],
            $field_id,
            $p['url'],
            $param
        );

       // Display comment
        $icons = "";
        if ($p['comments']) {
            $comment_id = Html::cleanId("comment_" . $p['name'] . $p['rand']);
            $link_id = Html::cleanId("comment_link_" . $p["name"] . $p['rand']);
            if (!$view_users) {
                $user["link"] = '';
            } else if (empty($user["link"])) {
                $user["link"] = $CFG_GLPI['root_doc'] . "/front/user.php";
            }

            if (empty($user['comment'])) {
                $user['comment'] = Toolbox::ucfirst(
                    sprintf(
                        __('Show %1$s'),
                        self::getTypeName(Session::getPluralNumber())
                    )
                );
            }

            $paramscomment = [
                'value'    => '__VALUE__',
                'itemtype' => User::getType()
            ];

            if ($view_users) {
                $paramscomment['withlink'] = $link_id;
            }
            $icons .= '<div class="btn btn-outline-secondary">';
            $icons .= Ajax::updateItemOnSelectEvent(
                $field_id,
                $comment_id,
                $CFG_GLPI["root_doc"] . "/ajax/comments.php",
                $paramscomment,
                false
            );

            $icons .= Html::showToolTip($user["comment"], [
                'contentid' => $comment_id,
                'display'   => false,
                'link'      => $user["link"],
                'linkid'    => $link_id
            ]);
            $icons .= '</div>';
        }

        if (
            Session::haveRight('user', self::IMPORTEXTAUTHUSERS)
            && $p['ldap_import']
            && Entity::isEntityDirectoryConfigured($_SESSION['glpiactive_entity'])
        ) {
            $icons .= '<div class="btn btn-outline-secondary">';
            $icons .= Ajax::createIframeModalWindow(
                'userimport' . $p['rand'],
                $CFG_GLPI["root_doc"] .
                                                      "/front/ldap.import.php?entity=" .
                                                      $_SESSION['glpiactive_entity'],
                ['title'   => __('Import a user'),
                    'display' => false
                ]
            );
            $icons .= "<span title=\"" . __s('Import a user') . "\"" .
            " data-bs-toggle='modal' data-bs-target='#userimport{$p['rand']}'>
            <i class='fas fa-plus fa-fw '></i>
            <span class='sr-only'>" . __s('Import a user') . "</span>
         </span>";
            $icons .= '</div>';
        }

        if (strlen($icons) > 0) {
            $output = "<div class='btn-group btn-group-sm " . ($p['width'] == "100%" ? "w-100" : "") . "' role='group'>{$output} {$icons}</div>";
        }

        $output .= Ajax::commonDropdownUpdateItem($p, false);

        if ($p['display']) {
            echo $output;
            return $p['rand'];
        }
        return $output;
    }


    /**
     * Show simple add user form for external auth.
     *
     * @return void|boolean false if user does not have rights to import users from external sources,
     *    print form otherwise
     */
    public static function showAddExtAuthForm()
    {

        if (!Session::haveRight("user", self::IMPORTEXTAUTHUSERS)) {
            return false;
        }

        echo "<div class='center'>\n";
        echo "<form method='post' action='" . Toolbox::getItemTypeFormURL('User') . "'>\n";

        echo "<table class='tab_cadre'>\n";
        echo "<tr><th colspan='4'>" . __('Automatically add a user of an external source') . "</th></tr>\n";

        echo "<tr class='tab_bg_1'><td>" . __('Login') . "</td>\n";
        echo "<td><input type='text' name='login' class='form-control'></td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td class='tab_bg_2 center' colspan='2'>\n";
        echo "<input type='submit' name='add_ext_auth_ldap' value=\"" . __s('Import from directories') . "\"
             class='btn btn-primary'>\n";
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td class='tab_bg_2 center' colspan='2'>\n";
        echo "<input type='submit' name='add_ext_auth_simple' value=\"" . __s('Import from other sources') . "\"
             class='btn btn-primary'>\n";
        echo "</td></tr>\n";

        echo "</table>";
        Html::closeForm();
        echo "</div>\n";
    }


    /**
     * Change auth method for given users.
     *
     * @param integer[] $IDs      IDs of users
     * @param integer   $authtype Auth type (see Auth constants)
     * @param integer   $server   ID of auth server
     *
     * @return boolean
     */
    public static function changeAuthMethod(array $IDs = [], $authtype = 1, $server = -1)
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (!Session::haveRight(self::$rightname, self::UPDATEAUTHENT)) {
            return false;
        }

        if (
            !empty($IDs)
            && in_array($authtype, [Auth::DB_GLPI, Auth::LDAP, Auth::MAIL, Auth::EXTERNAL])
        ) {
            $result = $DB->update(
                self::getTable(),
                [
                    'authtype'        => $authtype,
                    'auths_id'        => $server,
                    'password'        => '',
                    'is_deleted_ldap' => 0
                ],
                [
                    'id' => $IDs
                ]
            );
            if ($result) {
                foreach ($IDs as $ID) {
                    $changes = [
                        0,
                        '',
                        addslashes(
                            sprintf(
                                __('%1$s: %2$s'),
                                __('Update authentification method to'),
                                Auth::getMethodName($authtype, $server)
                            )
                        )
                    ];
                    Log::history($ID, __CLASS__, $changes, '', Log::HISTORY_LOG_SIMPLE_MESSAGE);
                }

                return true;
            }
        }
        return false;
    }


    /**
     * Generate vcard for the current user.
     *
     * @return void
     */
    public function generateVcard()
    {

       // prepare properties for the Vcard
        if (
            !empty($this->fields["realname"])
            || !empty($this->fields["firstname"])
        ) {
            $name = [$this->fields["realname"], $this->fields["firstname"], "", "", ""];
        } else {
            $name = [$this->fields["name"], "", "", "", ""];
        }

        $title = null;
        if ($this->fields['usertitles_id'] !== 0) {
            $title = new UserTitle();
            $title->getFromDB($this->fields['usertitles_id']);
        }
       // create vcard
        $vcard = new VObject\Component\VCard([
            'N'     => $name,
            'EMAIL' => $this->getDefaultEmail(),
            'NOTE'  => $this->fields["comment"],
        ]);
        if ($title) {
            $vcard->add('TITLE', $title->fields['name']);
        }
        if ($this->fields['timezone']) {
            $vcard->add('TZ', $this->fields['timezone']);
        }
        $vcard->add('TEL', $this->fields["phone"], ['type' => 'PREF;WORK;VOICE']);
        $vcard->add('TEL', $this->fields["phone2"], ['type' => 'HOME;VOICE']);
        $vcard->add('TEL', $this->fields["mobile"], ['type' => 'WORK;CELL']);

       // Get more data from plugins such as an IM contact
        $data = Plugin::doHook(Hooks::VCARD_DATA, ['item' => $this, 'data' => []])['data'];
        foreach ($data as $field => $additional_field) {
            $vcard->add($additional_field['name'], $additional_field['value'] ?? '', $additional_field['params'] ?? []);
        }

       // send the  VCard
        $output   = $vcard->serialize();
        $filename = implode("_", array_filter($name)) . ".vcf";

        @header("Content-Disposition: attachment; filename=\"$filename\"");
        @header("Content-Length: " . Toolbox::strlen($output));
        @header("Connection: close");
        @header("content-type: text/x-vcard; charset=UTF-8");

        echo $output;
    }


    /**
     * Show items of the current user.
     *
     * @param boolean $tech false to display items owned by user, true to display items managed by user
     *
     * @return void
     */
    public function showItems($tech)
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $ID = $this->getField('id');

        if ($tech) {
            $type_user   = $CFG_GLPI['linkuser_tech_types'];
            $type_group  = $CFG_GLPI['linkgroup_tech_types'];
            $field_user  = 'users_id_tech';
            $field_group = 'groups_id_tech';
        } else {
            $type_user   = $CFG_GLPI['linkuser_types'];
            $type_group  = $CFG_GLPI['linkgroup_types'];
            $field_user  = 'users_id';
            $field_group = 'groups_id';
        }

        $group_where = "";
        $groups      = [];

        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_groups.id',
                'glpi_groups.name'
            ],
            'FROM'      => 'glpi_groups',
            'LEFT JOIN' => [
                'glpi_groups_users' => [
                    'FKEY' => [
                        'glpi_groups_users'  => 'groups_id',
                        'glpi_groups'        => 'id'
                    ]
                ]
            ],
            'WHERE'     => ['glpi_groups_users.users_id' => $ID]
        ]);
        $number = count($iterator);

        $group_where = [];
        foreach ($iterator as $data) {
            $group_where[$field_group][] = $data['id'];
            $groups[$data["id"]] = $data["name"];
        }

        echo "<div class='spaced'><table class='tab_cadre_fixehov'>";
        $header = "<tr><th>" . _n('Type', 'Types', 1) . "</th>";
        $header .= "<th>" . Entity::getTypeName(1) . "</th>";
        $header .= "<th>" . __('Name') . "</th>";
        $header .= "<th>" . __('Serial number') . "</th>";
        $header .= "<th>" . __('Inventory number') . "</th>";
        $header .= "<th>" . __('Status') . "</th>";
        $header .= "<th>&nbsp;</th></tr>";
        echo $header;

        foreach ($type_user as $itemtype) {
            if (!($item = getItemForItemtype($itemtype))) {
                continue;
            }
            if ($item->canView()) {
                $itemtable = getTableForItemType($itemtype);
                $iterator_params = [
                    'FROM'   => $itemtable,
                    'WHERE'  => [$field_user => $ID]
                ];

                if ($item->maybeTemplate()) {
                    $iterator_params['WHERE']['is_template'] = 0;
                }
                if ($item->maybeDeleted()) {
                    $iterator_params['WHERE']['is_deleted'] = 0;
                }

                $item_iterator = $DB->request($iterator_params);

                $type_name = $item->getTypeName();

                foreach ($item_iterator as $data) {
                    $cansee = $item->can($data["id"], READ);
                    $link   = $data[$item->getNameField()];
                    if ($cansee) {
                        $link_item = $item::getFormURLWithID($data['id']);
                        if ($_SESSION["glpiis_ids_visible"] || empty($link)) {
                             $link = sprintf(__('%1$s (%2$s)'), $link, $data["id"]);
                        }
                        $link = "<a href='" . $link_item . "'>" . $link . "</a>";
                    }
                    $linktype = "";
                    if ($data[$field_user] == $ID) {
                        $linktype = self::getTypeName(1);
                    }
                    echo "<tr class='tab_bg_1'><td class='center'>$type_name</td>";
                    echo "<td class='center'>" . Dropdown::getDropdownName(
                        "glpi_entities",
                        $data["entities_id"]
                    ) . "</td>";
                    echo "<td class='center'>$link</td>";
                    echo "<td class='center'>";
                    if (isset($data["serial"]) && !empty($data["serial"])) {
                          echo $data["serial"];
                    } else {
                        echo '&nbsp;';
                    }
                    echo "</td><td class='center'>";
                    if (isset($data["otherserial"]) && !empty($data["otherserial"])) {
                        echo $data["otherserial"];
                    } else {
                        echo '&nbsp;';
                    }
                    echo "</td><td class='center'>";
                    if (isset($data["states_id"])) {
                        echo Dropdown::getDropdownName("glpi_states", $data['states_id']);
                    } else {
                        echo '&nbsp;';
                    }

                    echo "</td><td class='center'>$linktype</td></tr>";
                }
            }
        }
        if ($number) {
            echo $header;
        }
        echo "</table></div>";

        if (count($group_where)) {
            echo "<div class='spaced'><table class='tab_cadre_fixehov'>";
            $header = "<tr>" .
               "<th>" . _n('Type', 'Types', 1) . "</th>" .
               "<th>" . Entity::getTypeName(1) . "</th>" .
               "<th>" . __('Name') . "</th>" .
               "<th>" . __('Serial number') . "</th>" .
               "<th>" . __('Inventory number') . "</th>" .
               "<th>" . __('Status') . "</th>" .
               "<th>&nbsp;</th></tr>";
            echo $header;
            $nb = 0;
            foreach ($type_group as $itemtype) {
                if (!($item = getItemForItemtype($itemtype))) {
                    continue;
                }
                if ($item->canView() && $item->isField($field_group)) {
                    $itemtable = getTableForItemType($itemtype);
                    $iterator_params = [
                        'FROM'   => $itemtable,
                        'WHERE'  => [
                            'entities_id' => $this->getEntities(),
                            'OR'          => $group_where
                        ]
                    ];

                    if ($item->maybeTemplate()) {
                        $iterator_params['WHERE']['is_template'] = 0;
                    }
                    if ($item->maybeDeleted()) {
                        $iterator_params['WHERE']['is_deleted'] = 0;
                    }

                    $group_iterator = $DB->request($iterator_params);

                    $type_name = $item->getTypeName();

                    foreach ($group_iterator as $data) {
                        $nb++;
                        $cansee = $item->can($data["id"], READ);
                        $link   = $data["name"];
                        if ($cansee) {
                            $link_item = $item::getFormURLWithID($data['id']);
                            if ($_SESSION["glpiis_ids_visible"] || empty($link)) {
                                $link = sprintf(__('%1$s (%2$s)'), $link, $data["id"]);
                            }
                            $link = "<a href='" . $link_item . "'>" . $link . "</a>";
                        }
                        $linktype = "";
                        if (isset($groups[$data[$field_group]])) {
                            $linktype = sprintf(
                                __('%1$s = %2$s'),
                                Group::getTypeName(1),
                                $groups[$data[$field_group]]
                            );
                        }
                        echo "<tr class='tab_bg_1'><td class='center'>$type_name</td>";
                        echo "<td class='center'>" . Dropdown::getDropdownName(
                            "glpi_entities",
                            $data["entities_id"]
                        );
                        echo "</td><td class='center'>$link</td>";
                        echo "<td class='center'>";
                        if (isset($data["serial"]) && !empty($data["serial"])) {
                             echo $data["serial"];
                        } else {
                            echo '&nbsp;';
                        }
                        echo "</td><td class='center'>";
                        if (isset($data["otherserial"]) && !empty($data["otherserial"])) {
                            echo $data["otherserial"];
                        } else {
                            echo '&nbsp;';
                        }
                        echo "</td><td class='center'>";
                        if (isset($data["states_id"])) {
                            echo Dropdown::getDropdownName("glpi_states", $data['states_id']);
                        } else {
                            echo '&nbsp;';
                        }

                        echo "</td><td class='center'>$linktype</td></tr>";
                    }
                }
            }
            if ($nb) {
                echo $header;
            }
            echo "</table></div>";
        }
    }


    /**
     * Get user by email, importing it from LDAP if not existing.
     *
     * @param string $email
     *
     * @return integer ID of user, 0 if not found nor imported
     */
    public static function getOrImportByEmail($email = '')
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $iterator = $DB->request([
            'SELECT'    => 'users_id AS id',
            'FROM'      => 'glpi_useremails',
            'LEFT JOIN' => [
                'glpi_users' => [
                    'FKEY' => [
                        'glpi_useremails' => 'users_id',
                        'glpi_users'      => 'id'
                    ]
                ]
            ],
            'WHERE'     => [
                'glpi_useremails.email' => $DB->escape(stripslashes($email))
            ],
            'ORDER'     => ['glpi_users.is_active DESC', 'is_deleted ASC']
        ]);

       //User still exists in DB
        if (count($iterator)) {
            $result = $iterator->current();
            return $result['id'];
        } else {
            if ($CFG_GLPI["is_users_auto_add"]) {
               //Get all ldap servers with email field configured
                $ldaps = AuthLDAP::getServersWithImportByEmailActive();
               //Try to find the user by his email on each ldap server

                foreach ($ldaps as $ldap) {
                    $params = [
                        'method' => AuthLDAP::IDENTIFIER_EMAIL,
                        'value'  => $email,
                    ];
                    $res = AuthLDAP::ldapImportUserByServerId(
                        $params,
                        AuthLDAP::ACTION_IMPORT,
                        $ldap
                    );

                    if (isset($res['id'])) {
                        return $res['id'];
                    }
                }
            }
        }
        return 0;
    }


    /**
     * Handle user deleted in LDAP using configured policy.
     *
     * @param integer $users_id
     *
     * @return void
     */
    public static function manageDeletedUserInLdap($users_id)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

       //The only case where users_id can be null if when a user has been imported into GLPI
       //it's dn still exists, but doesn't match the connection filter anymore
       //In this case, do not try to process the user
        if (!$users_id) {
            return;
        }

        $myuser = new self();
        if (
            !$myuser->getFromDB($users_id) // invalid user
            || $myuser->fields['is_deleted_ldap'] == 1 // user already considered as deleted from LDAP
        ) {
            return;
        }

       //User is present in DB but not in the directory : it's been deleted in LDAP
        $tmp = [
            'id'              => $users_id,
            'is_deleted_ldap' => 1,
        ];

        switch ($CFG_GLPI['user_deleted_ldap']) {
           //DO nothing
            default:
            case AuthLDAP::DELETED_USER_PRESERVE:
                $myuser->update($tmp);
                break;

           //Put user in trashbin
            case AuthLDAP::DELETED_USER_DELETE:
               // Make sure the `is_deleted_ldap` flag is changed before deleting the user (Used for a potential future restore)
                $myuser->update($tmp);
                $myuser->delete($tmp);
                break;

           //Delete all user dynamic habilitations and groups
            case AuthLDAP::DELETED_USER_WITHDRAWDYNINFO:
                Profile_User::deleteRights($users_id, true);
                Group_User::deleteGroups($users_id, true);
                $myuser->update($tmp);
                break;

           //Deactivate the user
            case AuthLDAP::DELETED_USER_DISABLE:
                $tmp['is_active'] = 0;
                $myuser->update($tmp);
                break;

           //Deactivate the user+ Delete all user dynamic habilitations and groups
            case AuthLDAP::DELETED_USER_DISABLEANDWITHDRAWDYNINFO:
                $tmp['is_active'] = 0;
                $myuser->update($tmp);
                Profile_User::deleteRights($users_id, true);
                Group_User::deleteGroups($users_id, true);
                break;

            case AuthLDAP::DELETED_USER_DISABLEANDDELETEGROUPS:
                $tmp['is_active'] = 0;
                $myuser->update($tmp);
                Group_User::deleteGroups($users_id, true);
                break;
        }
       /*
       $changes[0] = '0';
       $changes[1] = '';
       $changes[2] = __('Deleted user in LDAP directory');
       Log::history($users_id, 'User', $changes, 0, Log::HISTORY_LOG_SIMPLE_MESSAGE);*/
    }

    /**
     * Handle user restored in LDAP using configured policy.
     *
     * @since 10.0.0
     * @param $users_id
     *
     * @return void
     */
    public static function manageRestoredUserInLdap($users_id): void
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

       //The only case where users_id can be null if when a user has been imported into GLPI
       //it's dn still exists, but doesn't match the connection filter anymore
       //In this case, do not try to process the user
        if (!$users_id) {
            return;
        }

        $myuser = new self();
        if (
            !$myuser->getFromDB($users_id) // invalid user
            || $myuser->fields['is_deleted_ldap'] == 0 // user already considered as restored from LDAP
        ) {
            return;
        }

       //User is present in DB and in the directory but 'is_ldap_deleted' was true : it's been restored in LDAP
        $tmp = [
            'id'              => $users_id,
            'is_deleted_ldap' => 0,
        ];

       // Calling the update function for the user will reapply dynamic rights {@see User::post_updateItem()}
        switch ($CFG_GLPI['user_restored_ldap']) {
           // Do nothing except update the 'is_ldap_deleted' field to prevent re-processing the restore for each sync
            default:
            case AuthLDAP::RESTORED_USER_PRESERVE:
                $myuser->update($tmp);
                break;

           // Restore the user from the trash
            case AuthLDAP::RESTORED_USER_RESTORE:
                $myuser->restore($tmp);
                $myuser->update($tmp);
                break;

           // Enable the user
            case AuthLDAP::RESTORED_USER_ENABLE:
                $tmp['is_active'] = 1;
                $myuser->update($tmp);
                break;
        }
    }

    /**
     * Get user ID from its name.
     *
     * @param string $name User name
     *
     * @return integer
     */
    public static function getIdByName($name)
    {
        return self::getIdByField('name', $name);
    }


    /**
     * Get user ID from a field
     *
     * @since 0.84
     *
     * @param string $field Field name
     * @param string $value Field value
     *
     * @return false|integer
     */
    public static function getIdByField($field, $value, $escape = true)
    {
        /** @var \DBmysql $DB */
        global $DB;

        if ($escape) {
            $value = Sanitizer::sanitize($value);
        }

        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => self::getTable(),
            'WHERE'  => [$field => $value]
        ]);

        if (count($iterator) == 1) {
            $row = $iterator->current();
            return (int)$row['id'];
        }
        return false;
    }


    /**
     * Show password update form for current user.
     *
     * @param array $error_messages
     *
     * @return void
     */
    public function showPasswordUpdateForm(array $error_messages = [])
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        echo '<form method="post" action="' . $CFG_GLPI['root_doc'] . '/front/updatepassword.php">';
        echo '<table class="tab_cadre">';
        echo '<tr><th colspan="2">' . __('Password update') . '</th></tr>';

        if (Session::mustChangePassword()) {
            echo '<tr class="tab_bg_2 center">';
            echo '<td colspan="2" class="red b">';
            echo __('Your password has expired. You must change it to be able to login.');
            echo '</td>';
            echo '</tr>';
        }

        echo '<tr class="tab_bg_1">';
        echo '<td>';
        echo __('Login');
        echo '</td>';
        echo '<td>';
        echo '<input type="text" name="name" value="' . $this->fields['name'] . '" readonly="readonly" />';
        echo '</td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>';
        echo '<label for="current_password">' . __('Current password') . '</label>';
        echo '</td>';
        echo '<td>';
        echo '<input type="password" id="current_password" name="current_password" />';
        echo '</td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>';
        echo '<label for="password">' . __('New password') . '</label>';
        echo '</td>';
        echo '<td>';
        echo '<input type="password" id="password" name="password" autocomplete="new-password" onkeyup="return passwordCheck();" class="form-control" />';
        echo '</td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>';
        echo '<label for="password2">' . __('New password confirmation') . '</label>';
        echo '</td>';
        echo '<td>';
        echo '<input type="password" id="password2" name="password2" autocomplete="new-password" class="form-control" />';
        echo '</td>';
        echo '</tr>';

        if ($CFG_GLPI['use_password_security']) {
            echo '<tr class="tab_bg_1">';
            echo '<td>' . __('Password security policy') . '</td>';
            echo '<td>';
            Config::displayPasswordSecurityChecks();
            echo '</td>';
            echo '</tr>';
        }

        echo '<tr class="tab_bg_2 center">';
        echo '<td colspan="2">';
        echo '<input type="submit" name="update" value="' . __s('Save') . '" class="btn btn-primary" />';
        echo '</td>';
        echo '</tr>';

        if (!empty($error_messages)) {
            echo '<tr class="tab_bg_2 center">';
            echo '<td colspan="2" class="red b">';
            echo implode('<br/>', $error_messages);
            echo '</td>';
            echo '</tr>';
        }

        echo '</table>';
        Html::closeForm();
    }


    /**
     * Show new password form of password recovery process.
     *
     * @param $token
     *
     * @return void
     */
    public static function showPasswordForgetChangeForm($token)
    {
        TemplateRenderer::getInstance()->display('password_form.html.twig', [
            'title'    => __('Forgotten password?'),
            'token'    => $token,
            'token_ok' => User::getUserByForgottenPasswordToken($token) !== null,
        ]);
    }


    /**
     * Show request form of password recovery process.
     *
     * @return void
     */
    public static function showPasswordForgetRequestForm()
    {
        TemplateRenderer::getInstance()->display('password_form.html.twig', [
            'title' => __('Forgotten password?'),
        ]);
    }


    /**
     * Handle password recovery form submission.
     *
     * @param array $input
     *
     * @throws ForgetPasswordException when requirements are not met
     *
     * @return boolean true if password successfully changed, false otherwise
     */
    public function updateForgottenPassword(array $input)
    {
        // Invalid token
        if (
            !array_key_exists('password_forget_token', $input)
            || (string)$input['password_forget_token'] === ''
            || ($user = self::getUserByForgottenPasswordToken($input['password_forget_token'])) === null
        ) {
            throw new ForgetPasswordException(
                __('Your password reset request has expired or is invalid. Please renew it.')
            );
        }

        // Check if the user is no longer active, it might happen if for some
        // reasons the user is disabled manually after requesting a password reset
        if ($user->fields['is_active'] == 0 || $user->fields['is_deleted'] == 1) {
            throw new ForgetPasswordException(
                __("Unable to reset password, please contact your administrator")
            );
        }

        // Same check but for the account activation dates
        if (
            ($user->fields['begin_date'] !== null && $user->fields['begin_date'] > $_SESSION['glpi_currenttime'])
            || ($user->fields['end_date'] !== null && $user->fields['end_date'] < $_SESSION['glpi_currenttime'])
        ) {
            throw new ForgetPasswordException(
                __("Unable to reset password, please contact your administrator")
            );
        }

        // Safety check that the user authentication method support passwords changes
        if ($user->fields["authtype"] !== Auth::DB_GLPI && Auth::useAuthExt()) {
            throw new ForgetPasswordException(
                __("The authentication method configuration doesn't allow you to change your password.")
            );
        }

        $input['id'] = $user->fields['id'];

        // Check new password validity, throws exception on failure
        Config::validatePassword($input["password"], false);

        // Try to set new password
        if (!$user->update($input)) {
            return false;
        }

        // Clear password reset token data.
        // Use a direct DB query to bypass rights checks.
        /** @var \DBmysql $DB */
        global $DB;
        $DB->update(
            'glpi_users',
            [
                'password_forget_token'      => '',
                'password_forget_token_date' => 'NULL',
            ],
            [
                'id' => $user->fields['id'],
            ]
        );

        $this->getFromDB($user->fields['id']);

        return true;
    }


    /**
     * Displays password recovery result.
     *
     * @param array $input
     *
     * @return void
     */
    public function showUpdateForgottenPassword(array $input)
    {
        try {
            if ($this->updateForgottenPassword($input)) {
                Session::addMessageAfterRedirect(__('Reset password successful.'));
            }
        } catch (\Glpi\Exception\ForgetPasswordException $e) {
            Session::addMessageAfterRedirect($e->getMessage(), false, ERROR);
        } catch (\Glpi\Exception\PasswordTooWeakException $e) {
           // Force display on error
            foreach ($e->getMessages() as $message) {
                Session::addMessageAfteRredirect($message, false, ERROR);
            }
        }

        TemplateRenderer::getInstance()->display('password_form.html.twig', [
            'title'         => __('Forgotten password?'),
            'messages_only' => true,
        ]);
    }


    /**
     * Send password recovery for a user and display result message.
     *
     * @param string $email email of the user
     *
     * @return void
     */
    public function showForgetPassword($email)
    {
        try {
            $this->forgetPassword($email);
        } catch (\Glpi\Exception\ForgetPasswordException $e) {
            Session::addMessageAfterRedirect($e->getMessage(), false, ERROR);
            return;
        }
        Session::addMessageAfteRredirect(__('If the given email address match an exisiting GLPI user, you will receive an email containing the informations required to reset your password. Please contact your administrator if you do not receive any email.'));

        TemplateRenderer::getInstance()->display('password_form.html.twig', [
            'title'         => __('Forgotten password?'),
            'messages_only' => true,
        ]);
    }

    /**
     * Send password recovery email for a user.
     *
     * @param string $email
     *
     * @throws ForgetPasswordException If the process failed and the user should
     *                                 be aware of it (e.g. incorrect email)
     *
     * @return bool Return true if the password reset notification was sent,
     *              false if the process failed but the user should not be aware
     *              of it to avoid exposing whether or not the given email exist
     *              in our database.
     */
    public function forgetPassword(string $email): bool
    {
        $condition = [
            'glpi_users.is_active'  => 1,
            'glpi_users.is_deleted' => 0, [
                'OR' => [
                    ['glpi_users.begin_date' => null],
                    ['glpi_users.begin_date' => ['<', new QueryExpression('NOW()')]]
                ],
            ], [
                'OR'  => [
                    ['glpi_users.end_date'   => null],
                    ['glpi_users.end_date'   => ['>', new QueryExpression('NOW()')]]
                ]
            ]
        ];

        // Randomly increase the response time to prevent an attacker to be able to detect whether
        // a notification was sent (a longer response time could correspond to a SMTP operation).
        sleep(rand(1, 3));

        // Try to find a single user matching the given email
        if (!$this->getFromDBbyEmail($email, $condition)) {
            $count = self::countUsersByEmail($email, $condition);
            trigger_error(
                "Failed to find a single user for '$email', $count user(s) found.",
                E_USER_WARNING
            );

            return false;
        }

        // Check that the configuration allow this user to change his password
        if ($this->fields["authtype"] !== Auth::DB_GLPI && Auth::useAuthExt()) {
            trigger_error(
                __("The authentication method configuration doesn't allow the user '$email' to change their password."),
                E_USER_WARNING
            );

            return false;
        }

        // Check that the given email is valid
        if (!NotificationMailing::isUserAddressValid($email)) {
            throw new ForgetPasswordException(__('Invalid email address'));
        }

        // Store password reset token and date.
        // Use a direct DB query to bypass rights checks.
        /** @var \DBmysql $DB */
        global $DB;
        $DB->update(
            'glpi_users',
            [
                'password_forget_token'      => sha1(Toolbox::getRandomString(30)),
                'password_forget_token_date' => $_SESSION["glpi_currenttime"],
            ],
            [
                'id' => $this->fields['id'],
            ]
        );

        $this->getFromDB($this->fields['id']); // reload user to get up-to-date fields

        // Notication on root entity (glpi_users.entities_id is only a pref)
        NotificationEvent::raiseEvent('passwordforget', $this, ['entities_id' => 0]);
        QueuedNotification::forceSendFor($this->getType(), $this->fields['id']);

        return true;
    }


    /**
     * Display information from LDAP server for user.
     *
     * @return void
     */
    private function showLdapDebug()
    {

        if ($this->fields['authtype'] != Auth::LDAP) {
            return;
        }
        echo "<div class='spaced'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr><th colspan='4'>" . AuthLDAP::getTypeName(1) . "</th></tr>";

        echo "<tr class='tab_bg_2'><td>" . __('User DN') . "</td>";
        echo "<td>" . $this->fields['user_dn'] . "</td></tr>\n";

        if ($this->fields['user_dn']) {
            echo "<tr class='tab_bg_2'><td>" . __('User information') . "</td><td>";
            $config_ldap = new AuthLDAP();
            $ds          = false;

            if ($config_ldap->getFromDB($this->fields['auths_id'])) {
                $ds = $config_ldap->connect();
            }

            if ($ds) {
                $info = AuthLDAP::getUserByDn(
                    $ds,
                    $this->fields['user_dn'],
                    ['*', 'createTimeStamp', 'modifyTimestamp']
                );
                if (is_array($info)) {
                     Html::printCleanArray($info);
                } else {
                    echo __('No item to display');
                }
            } else {
                echo __('Connection failed');
            }

            echo "</td></tr>\n";
        }

        echo "</table></div>";
    }


    /**
     * Display debug information for current object.
     *
     * @return void
     */
    public function showDebug()
    {

        NotificationEvent::debugEvent($this);
        $this->showLdapDebug();
    }

    public function getUnicityFieldsToDisplayInErrorMessage()
    {

        return ['id'          => __('ID'),
            'entities_id' => Entity::getTypeName(1)
        ];
    }


    public function getUnallowedFieldsForUnicity()
    {

        return array_merge(
            parent::getUnallowedFieldsForUnicity(),
            ['auths_id', 'date_sync', 'entities_id', 'last_login', 'profiles_id']
        );
    }


    /**
     * Get a unique generated token.
     *
     * @param string $field Field storing the token
     *
     * @return string
     */
    public static function getUniqueToken($field = 'personal_token')
    {
        /** @var \DBmysql $DB */
        global $DB;

        $ok = false;
        do {
            $key    = Toolbox::getRandomString(40);
            $row = $DB->request([
                'COUNT'  => 'cpt',
                'FROM'   => self::getTable(),
                'WHERE'  => [$field => $key]
            ])->current();

            if ($row['cpt'] == 0) {
                 return $key;
            }
        } while (!$ok);
    }


    /**
     * Get token of a user. If not exists generate it.
     *
     * @param integer $ID    User ID
     * @param string  $field Field storing the token
     *
     * @return string|boolean User token, false if user does not exist
     */
    public static function getToken($ID, $field = 'personal_token')
    {

        $user = new self();
        if ($user->getFromDB($ID)) {
            return $user->getAuthToken($field);
        }

        return false;
    }

    /**
     * Get token of a user. If it does not exists  then generate it.
     *
     * @since 9.4
     *
     * @param string $field the field storing the token
     * @param boolean $force_new force generation of a new token
     *
     * @return string|false token or false in case of error
     */
    public function getAuthToken($field = 'personal_token', $force_new = false)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if ($this->isNewItem()) {
            return false;
        }

       // check date validity for cookie token
        $outdated = false;
        if ($field === 'cookie_token') {
            if (empty($this->fields[$field . "_date"])) {
                $outdated = true;
            } else {
                $date_create = new DateTime($this->fields[$field . "_date"]);
                $date_expir = $date_create->add(new DateInterval('PT' . $CFG_GLPI["login_remember_time"] . 'S'));

                if ($date_expir < new DateTime()) {
                    $outdated = true;
                }
            }
        }

       // token exists, is not oudated, and we may use it
        if (!empty($this->fields[$field]) && !$force_new && !$outdated) {
            return $this->fields[$field];
        }

       // else get a new token
        $token = self::getUniqueToken($field);

       // for cookie token, we need to store it hashed
        $hash = $token;
        if ($field === 'cookie_token') {
            $hash = Auth::getPasswordHash($token);
        }

       // save this token in db
        $this->update(['id'             => $this->getID(),
            $field           => $hash,
            $field . "_date" => $_SESSION['glpi_currenttime']
        ]);

        return $token;
    }


    /**
     * Get name of users using default passwords
     *
     * @return string[]
     */
    public static function checkDefaultPasswords()
    {
        /** @var \DBmysql $DB */
        global $DB;

        $passwords = ['glpi'      => 'glpi',
            'tech'      => 'tech',
            'normal'    => 'normal',
            'post-only' => 'postonly'
        ];
        $default_password_set = [];

        $crit = ['FIELDS'     => ['name', 'password'],
            'is_active'  => 1,
            'is_deleted' => 0,
            'name'       => array_keys($passwords)
        ];

        foreach ($DB->request(self::getTable(), $crit) as $data) {
            if (Auth::checkPassword($passwords[strtolower($data['name'])], $data['password'])) {
                $default_password_set[] = $data['name'];
            }
        }

        return $default_password_set;
    }


    /**
     * Get picture URL from picture field.
     *
     * @since 0.85
     *
     * @param string $picture Picture field value
     * @param bool  $full     get full path
     *
     * @return string
     */
    public static function getURLForPicture($picture, $full = true)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $url = Toolbox::getPictureUrl($picture, $full);
        if (null !== $url) {
            return $url;
        }

        return ($full ? $CFG_GLPI["root_doc"] : "") . "/pics/picture.png";
    }


    /**
     * Get thumbnail URL from picture field.
     *
     * @since 0.85
     *
     * @param string $picture Picture field value
     *
     * @return string
     */
    public static function getThumbnailURLForPicture(?string $picture = null)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

       // prevent xss
        $picture = Html::cleanInputText($picture);

        if (!empty($picture)) {
            $tmp = explode(".", $picture);
            if (count($tmp) == 2) {
                return $CFG_GLPI["root_doc"] . "/front/document.send.php?file=_pictures/" . $tmp[0] .
                   "_min." . $tmp[1];
            }
        }

        return "";
    }


    /**
     * Drop existing files for user picture.
     *
     * @since 0.85
     *
     * @param string $picture Picture field value
     *
     * @return void
     */
    public static function dropPictureFiles($picture)
    {
        if (!empty($picture)) {
            if (!$filepath = realpath(GLPI_PICTURE_DIR . "/$picture")) {
                return;
            }
            if (!str_starts_with($filepath, realpath(GLPI_PICTURE_DIR))) {
                trigger_error(sprintf('Invalid picture path `%s`', $picture), E_USER_WARNING);
            }
            // unlink main file
            if (file_exists($filepath)) {
                @unlink($filepath);
            }
            // unlink Thumbnail
            $tmp = explode(".", $picture);
            if (count($tmp) == 2) {
                if (!$thumbpath = realpath(GLPI_PICTURE_DIR . "/" . $tmp[0] . "_min." . $tmp[1])) {
                    return;
                }
                if (!str_starts_with($thumbpath, realpath(GLPI_PICTURE_DIR))) {
                    trigger_error(sprintf('Invalid picture path `%s`', $tmp[0] . "_min." . $tmp[1]), E_USER_WARNING);
                }
                if (file_exists($thumbpath)) {
                    @unlink($thumbpath);
                }
            }
        }
    }

    public function getRights($interface = 'central')
    {

        $values = parent::getRights();
       //TRANS: short for : Add users from an external source
        $values[self::IMPORTEXTAUTHUSERS] = ['short' => __('Add external'),
            'long'  => __('Add users from an external source')
        ];
       //TRANS: short for : Read method for user authentication and synchronization
        $values[self::READAUTHENT]        = ['short' => __('Read auth'),
            'long'  => __('Read user authentication and synchronization method')
        ];
       //TRANS: short for : Update method for user authentication and synchronization
        $values[self::UPDATEAUTHENT]      = ['short' => __('Update auth and sync'),
            'long'  => __('Update method for user authentication and synchronization')
        ];

        return $values;
    }


    /**
     * Retrieve the list of LDAP field names from a list of fields
     * allow pattern substitution, e.g. %{name}.
     *
     * @since 9.1
     *
     * @param string[] $map array of fields
     *
     * @return string[]
     */
    private static function getLdapFieldNames(array $map)
    {

        $ret =  [];
        foreach ($map as $v) {
            /** @var array $reg */
            if (preg_match_all('/%{(.*)}/U', $v, $reg)) {
                // e.g. "%{country} > %{city} > %{site}"
                foreach ($reg [1] as $f) {
                    $ret [] = $f;
                }
            } else {
               // single field name
                $ret [] = $v;
            }
        }
        return $ret;
    }


    /**
     * Retrieve the value of a fields from a LDAP result applying needed substitution of %{value}.
     *
     * @since 9.1
     *
     * @param string $map String with field format
     * @param array  $res LDAP result
     *
     * @return string
     */
    private static function getLdapFieldValue($map, array $res)
    {

        $map = Sanitizer::unsanitize($map);
        $ret = preg_replace_callback(
            '/%{(.*)}/U',
            function ($matches) use ($res) {
                return (isset($res[0][$matches[1]][0]) ? $res[0][$matches[1]][0] : '');
            },
            $map
        );

        return $ret == $map ? (isset($res[0][$map][0]) ? $res[0][$map][0] : '') : $ret;
    }

    /**
     * Print the switch language form.
     *
     * @return string
     */
    public static function showSwitchLangForm()
    {
        $params = [
            'value'     => $_SESSION["glpilanguage"],
            'display'   => false,
            'on_change' => 'this.form.submit()'
        ];

        $out = "<form method='post' name='switchlang' action='" . User::getFormURL() . "' autocomplete='off'>";
        $out .= Dropdown::showLanguages("language", $params);
        $out .= Html::closeForm(false);

        return $out;
    }

    /**
     * Get list of entities ids for current user.
     *
     * @return integer[]
     */
    private function getEntities()
    {
       //get user entities
        if ($this->entities == null) {
            $this->entities = Profile_User::getUserEntities($this->fields['id'], true);
        }
        return $this->entities;
    }


    /**
     * Give cron information.
     *
     * @param string $name Task's name
     *
     * @return array
     */
    public static function cronInfo(string $name): array
    {

        $info = [];
        switch ($name) {
            case 'passwordexpiration':
                $info = [
                    'description' => __('Handle users passwords expiration policy'),
                    'parameter'   => __('Maximum expiration notifications to send at once'),
                ];
                break;
        }
        return $info;
    }

    /**
     * Cron that notify users about when their password expire and deactivate their account
     * depending on password expiration policy.
     *
     * @param CronTask $task
     *
     * @return integer
     */
    public static function cronPasswordExpiration(CronTask $task)
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $expiration_delay   = (int)$CFG_GLPI['password_expiration_delay'];
        $notice_time        = (int)$CFG_GLPI['password_expiration_notice'];
        $notification_limit = (int)$task->fields['param'];
        $lock_delay         = (int)$CFG_GLPI['password_expiration_lock_delay'];

        if (-1 === $expiration_delay || (-1 === $notice_time && -1 === $lock_delay)) {
           // Nothing to do if passwords does not expire
           // or if password expires without notice and with no lock delay
            return 0;
        }

       // Notify users about expiration of their password.
        $to_notify_count = 0;
        if (-1 !== $notice_time) {
            $notification_request = [
                'FROM'      => self::getTable(),
                'LEFT JOIN' => [
                    Alert::getTable() => [
                        'ON' => [
                            Alert::getTable() => 'items_id',
                            self::getTable()  => 'id',
                            [
                                'AND' => [
                                    Alert::getTableField('itemtype') => self::getType(),
                                ]
                            ],
                        ]
                    ]
                ],
                'WHERE'     => [
                    self::getTableField('is_deleted') => 0,
                    self::getTableField('is_active')  => 1,
                    self::getTableField('authtype')   => Auth::DB_GLPI,
                    new QueryExpression(
                        sprintf(
                            'NOW() > ADDDATE(%s, INTERVAL %s DAY)',
                            $DB->quoteName(self::getTableField('password_last_update')),
                            $expiration_delay - $notice_time
                        )
                    ),
               // Get only users that has not yet been notified within last day
                    'OR'                              => [
                        [Alert::getTableField('date') => null],
                        [Alert::getTableField('date') => ['<', new QueryExpression('CURRENT_TIMESTAMP() - INTERVAL 1 day')]],
                    ],
                ],
            ];

            $to_notify_count_request = array_merge(
                $notification_request,
                [
                    'COUNT'  => 'cpt',
                ]
            );
            $to_notify_count = $DB->request($to_notify_count_request)->current()['cpt'];

            $notification_data_request  = array_merge(
                $notification_request,
                [
                    'SELECT'    => [
                        self::getTableField('id as user_id'),
                        Alert::getTableField('id as alert_id'),
                    ],
                    'LIMIT'     => $notification_limit,
                ]
            );
            $notification_data_iterator = $DB->request($notification_data_request);

            foreach ($notification_data_iterator as $notification_data) {
                $user_id  = $notification_data['user_id'];
                $alert_id = $notification_data['alert_id'];

                $user = new User();
                $user->getFromDB($user_id);

                $is_notification_send = NotificationEvent::raiseEvent(
                    'passwordexpires',
                    $user,
                    ['entities_id' => 0] // Notication on root entity (glpi_users.entities_id is only a pref)
                );
                if (!$is_notification_send) {
                     continue;
                }

                 $task->addVolume(1);

                 $alert = new Alert();

                 // Delete existing alert if any
                if (null !== $alert_id) {
                    $alert->delete(['id' => $alert_id]);
                }

                 // Add an alert to not warn user for at least one day
                 $alert->add(
                     [
                         'itemtype' => 'User',
                         'items_id' => $user_id,
                         'type'     => Alert::NOTICE,
                     ]
                 );
            }
        }

       // Disable users if their password has expire for too long.
        if (-1 !== $lock_delay) {
            $DB->update(
                self::getTable(),
                [
                    'is_active'         => 0,
                    'cookie_token'      => null,
                    'cookie_token_date' => null,
                ],
                [
                    'is_deleted' => 0,
                    'is_active'  => 1,
                    'authtype'   => Auth::DB_GLPI,
                    new QueryExpression(
                        sprintf(
                            'NOW() > ADDDATE(ADDDATE(%s, INTERVAL %d DAY), INTERVAL %s DAY)',
                            $DB->quoteName(self::getTableField('password_last_update')),
                            $expiration_delay,
                            $lock_delay
                        )
                    ),
                ]
            );
        }

        return -1 !== $notice_time && $to_notify_count > $notification_limit
         ? -1 // -1 for partial process (remaining notifications to send)
         : 1; // 1 for fully process
    }

    /**
     * Get password expiration time.
     *
     * @return null|int Password expiration time, or null if expiration mechanism is not active.
     */
    public function getPasswordExpirationTime()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (!array_key_exists('id', $this->fields) || $this->fields['id'] < 1) {
            return null;
        }

        $expiration_delay = (int)$CFG_GLPI['password_expiration_delay'];

        if (-1 === $expiration_delay) {
            return null;
        }

        if (null === $this->fields['password_last_update']) {
            // password never updated
            return strtotime(
                '+ ' . $expiration_delay . ' days',
                strtotime($this->fields['date_creation'])
            );
        }
        return strtotime(
            '+ ' . $expiration_delay . ' days',
            strtotime($this->fields['password_last_update'])
        );
    }

    /**
     * Check if password should be changed (if it expires soon).
     *
     * @return boolean
     */
    public function shouldChangePassword()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if ($this->hasPasswordExpired()) {
            return true; // too late to change password, but returning false would not be logical here
        }

        $expiration_time = $this->getPasswordExpirationTime();
        if (null === $expiration_time) {
            return false;
        }

        $notice_delay    = (int)$CFG_GLPI['password_expiration_notice'];
        if (-1 === $notice_delay) {
            return false;
        }

        $notice_time = strtotime('- ' . $notice_delay . ' days', $expiration_time);

        return $notice_time < time();
    }

    /**
     * Check if password expired.
     *
     * @return boolean
     */
    public function hasPasswordExpired()
    {

        $expiration_time = $this->getPasswordExpirationTime();
        if (null === $expiration_time) {
            return false;
        }

        return $expiration_time < time();
    }

    public function getPasswordExpirationMessage(): ?string
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;
        $expiration_msg = null;
        if ($this->fields['authtype'] == Auth::DB_GLPI && $this->shouldChangePassword()) {
            $expire_time = $this->getPasswordExpirationTime();
            $expire_has_passed = $expire_time < time();
            if ($expire_has_passed) {
                $expiration_msg = __('Your password has expired.');
            } else {
                $expiration_msg = sprintf(
                    __('Your password will expire on %s.'),
                    Html::convDateTime(date('Y-m-d H:i:s', $expire_time))
                );
            }
        }
        return $expiration_msg;
    }

    public static function getFriendlyNameSearchCriteria(string $filter): array
    {
        $table     = self::getTable();
        $login     = DBmysql::quoteName("$table.name");
        $firstname = DBmysql::quoteName("$table.firstname");
        $lastname  = DBmysql::quoteName("$table.realname");

        $filter = strtolower($filter);
        $filter_no_spaces = str_replace(" ", "", $filter);

        return [
            'OR' => [
                ['RAW' => ["LOWER($login)" => ['LIKE', "%$filter%"]]],
                ['RAW' => ["LOWER(REPLACE(CONCAT($firstname, $lastname), ' ', ''))" => ['LIKE', "%$filter_no_spaces%"]]],
                ['RAW' => ["LOWER(REPLACE(CONCAT($lastname, $firstname), ' ', ''))" => ['LIKE', "%$filter_no_spaces%"]]],
            ]
        ];
    }

    public static function getFriendlyNameFields(string $alias = "name")
    {
        $config = Config::getConfigurationValues('core');
        if ($config['names_format'] == User::FIRSTNAME_BEFORE) {
            $first = "firstname";
            $second = "realname";
        } else {
            $first = "realname";
            $second = "firstname";
        }

        $table  = self::getTable();
        $first  = DBmysql::quoteName("$table.$first");
        $second = DBmysql::quoteName("$table.$second");
        $alias  = DBmysql::quoteName($alias);
        $name   = DBmysql::quoteName($table . '.' . self::getNameField());

        return new QueryExpression("IF(
            $first <> '' && $second <> '',
            CONCAT($first, ' ', $second),
            $name
         ) AS $alias");
    }

    public static function getIcon()
    {
        return "ti ti-user";
    }

    /**
     * Add groups stored in "_ldap_rules/groups_id" special input
     */
    public function applyGroupsRules()
    {
        if (!isset($this->input["_ldap_rules"]['groups_id'])) {
            return;
        }

        $group_ids = array_unique($this->input["_ldap_rules"]['groups_id']);
        foreach ($group_ids as $group_id) {
            $group_user = new Group_User();

            $data = [
                'groups_id' => $group_id,
                'users_id'  => $this->getId()
            ];

            if (!$group_user->getFromDBByCrit($data)) {
                $group_user->add($data);
            }
        }
    }

    /**
     * Get anonymized name for user instance.
     *
     * @param ?int $entities_id
     *
     * @return string|null
     */
    public function getAnonymizedName(?int $entities_id = null): ?string
    {
        switch (Entity::getAnonymizeConfig($entities_id)) {
            default:
            case Entity::ANONYMIZE_DISABLED:
                return null;

            case Entity::ANONYMIZE_USE_GENERIC:
            case Entity::ANONYMIZE_USE_GENERIC_USER:
                return __("Helpdesk user");

            case Entity::ANONYMIZE_USE_NICKNAME:
            case Entity::ANONYMIZE_USE_NICKNAME_USER:
                return $this->fields['nickname'];
        }

        return null;
    }

    /**
     * Get anonymized name for user having given ID.
     *
     * @param int $users_id
     * @param int $entities_id
     *
     * @return string|null
     */
    public static function getAnonymizedNameForUser(int $users_id, ?int $entities_id = null): ?string
    {
        switch (Entity::getAnonymizeConfig($entities_id)) {
            default:
            case Entity::ANONYMIZE_DISABLED:
                return null;

            case Entity::ANONYMIZE_USE_GENERIC:
            case Entity::ANONYMIZE_USE_GENERIC_USER:
                return __("Helpdesk user");

            case Entity::ANONYMIZE_USE_NICKNAME:
            case Entity::ANONYMIZE_USE_NICKNAME_USER:
                $user = new User();
                if (!$user->getFromDB($users_id)) {
                    return '';
                }

                return $user->fields['nickname'] ?? '';
        }

        return null;
    }

    /**
     * Print a simplified user form.
     *
     * @param integer $ID    ID of the user
     * @param array $options Options
     *     - string   target        Form target
     *     - boolean  withtemplate  Template or basic item
     *
     * @return boolean true
     */
    public function showSystemUserForm($ID, array $options = []): bool
    {
        $this->initForm($ID, $options);

        $formtitle = $this->getTypeName(1);
        $options['formtitle']   = $formtitle;
        $options['formoptions'] = ($options['formoptions'] ?? '') . " enctype='multipart/form-data'";
        $options['candel'] = false;
        $options['canedit'] = self::canUpdate();
        $this->showFormHeader($options);
        $rand = mt_rand();

        echo "<tr class='tab_bg_1'>";
        $surnamerand = mt_rand();
        echo "<td><label for='textfield_realname$surnamerand'>" . __('Surname') . "</label></td>";
        echo "<td>";
        echo Html::input(
            'realname',
            [
                'value' => $this->fields['realname'],
                'id'    => "textfield_realname$surnamerand",
            ]
        );
        echo "</td>";

        echo "<td rowspan='3'>" . _n('Picture', 'Pictures', 1) . "</td>";
        echo "<td rowspan='3'>";
        echo self::getPictureForUser($ID);

        echo Html::file(['name' => 'picture', 'display' => false, 'onlyimages' => true]);
        echo "<input type='checkbox' name='_blank_picture'>&nbsp;" . __('Clear');
        echo "</td>";
        echo "</tr>";

        $firstnamerand = mt_rand();
        echo "<tr class='tab_bg_1'><td><label for='textfield_firstname$firstnamerand'>" . __('First name') . "</label></td><td>";
        echo Html::input(
            'firstname',
            [
                'value' => $this->fields['firstname'],
                'id'    => "textfield_firstname$firstnamerand",
            ]
        );
        echo "</td></tr>";

        echo "<tr><td colspan='2'>";
        echo "<span>";
        echo  __("This is a special user used for automated actions. ");
        echo '<br>';
        echo  __("You can set its name to your organisation's name. ");
        echo "</span>";
        echo "</td></tr>";

        $this->showFormButtons($options);

        return true;
    }

    public function getPictureForUser(int $ID): string
    {
        return TemplateRenderer::getInstance()->render('components/user/picture.html.twig', [
            'users_id'  => $ID,
            'with_link' => false,
        ]);
    }

    /**
     * Get user link.
     *
     * @param bool $enable_anonymization
     *
     * @return string
     */
    public function getUserLink(bool $enable_anonymization = false): string
    {
        if (
            $enable_anonymization
            && $this->fields['id'] != $_SESSION['glpiID']
            && Session::getCurrentInterface() == 'helpdesk'
            && ($anon = $this->getAnonymizedName()) !== null
        ) {
           // if anonymized name active, return only the anonymized name
            return $anon;
        }

        return $this->getLink();
    }

    /**
     * Get user picture path.
     *
     * @param bool $enable_anonymization
     *
     * @return string
     */
    public function getPicturePath(bool $enable_anonymization = false): string
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if ($enable_anonymization && Session::getCurrentInterface() == 'helpdesk' && Entity::getAnonymizeConfig() !== Entity::ANONYMIZE_DISABLED) {
            return $CFG_GLPI["root_doc"] . '/pics/picture.png';
        }

        $path = Toolbox::getPictureUrl($this->fields['picture'], false);
        if (!empty($path)) {
            return $path;
        }

        return $CFG_GLPI["root_doc"] . '/pics/picture.png';
    }

    /**
     * Get user thumbnail picture path.
     *
     * @param bool $enable_anonymization
     *
     * @return null|string
     */
    public function getThumbnailPicturePath(bool $enable_anonymization = false): ?string
    {

        if ($enable_anonymization && Session::getCurrentInterface() == 'helpdesk' && Entity::getAnonymizeConfig() !== Entity::ANONYMIZE_DISABLED) {
            return null;
        }

        $path = User::getThumbnailURLForPicture($this->fields['picture']);
        if (!empty($path)) {
            return $path;
        }

        return null;
    }

    /**
     * Get user initials.
     *
     * @param bool $enable_anonymization
     *
     * @return string
     */
    public function getUserInitials(bool $enable_anonymization = false): string
    {

        if ($enable_anonymization && Session::getCurrentInterface() == 'helpdesk' && ($anon = $this->getAnonymizedName()) !== null) {
           // if anonymized name active, return two first letters of the anon name
            return mb_strtoupper(mb_substr($anon, 0, 2));
        }

        $initials = mb_substr($this->fields['firstname'] ?? '', 0, 1) . mb_substr($this->fields['realname'] ?? '', 0, 1);
        if (empty($initials)) {
            $initials = mb_substr($this->fields['name'] ?? '', 0, 2);
        }
        return mb_strtoupper($initials);
    }

    /**
     * Return background color corresponding to user initials.
     *
     * @param bool $enable_anonymization
     *
     * @return string
     */
    public function getUserInitialsBgColor(bool $enable_anonymization = false): string
    {
        return Toolbox::getColorForString($this->getUserInitials($enable_anonymization));
    }

    /**
     * Find one user which match the given token and asked for a password reset
     * less than one day ago
     *
     * @param string $token password_forget_token
     *
     * @return User|null The matching user or null if zero or more than one user
     *                   were found
     */
    public static function getUserByForgottenPasswordToken(string $token): ?User
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (empty($token)) {
            return null;
        }

        // Find users which match the given token and asked for a password reset
        // less than one day ago
        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'password_forget_token'       => $token,
                new \QueryExpression('NOW() < ADDDATE(' . $DB->quoteName('password_forget_token_date') . ', INTERVAL 1 DAY)')
            ]
        ]);

        // Check that we found exactly one user
        if (count($iterator) !== 1) {
            return null;
        }

        // Get first row, should use current() when updated to GLPI 10
        $data = iterator_to_array($iterator);
        $data = array_pop($data);

        // Try to load the user
        $user = new self();
        if (!$user->getFromDB($data['id'])) {
            return null;
        }

        return $user;
    }

    /**
     * Get name of the user with ID
     *
     * @param integer $ID   ID of the user.
     *
     * @return string username string (realname if not empty and name if realname is empty).
     */
    public static function getNameForLog(int $ID): string
    {
        /** @var \DBmysql $DB */
        global $DB;

        $iterator = $DB->request(
            'glpi_users',
            [
                'WHERE' => ['id' => $ID]
            ]
        );

        if (count($iterator) === 1) {
            $data     = $iterator->current();

            if (!empty($data['realname'])) {
                $formatted = $data['realname'];

                if (!empty($data['firstname'])) {
                    $formatted .= " " . $data["firstname"];
                }
            } else {
                $formatted = $data["name"];
            }
            return sprintf(__('%1$s (%2$s)'), $formatted, $ID);
        }

        return __('Unknown user');
    }

    /**
     * Check if this User is the last super-admin user.
     * A "super-admin user" is a user that can edit GLPI's profiles.
     *
     * @return bool
     */
    protected function isLastSuperAdminUser(): bool
    {
        // Find all active authorizations for the super admins
        $super_admin_authorizations = (new Profile_User())->find([
            'profiles_id' => Profile::getSuperAdminProfilesId(),
            'users_id' => new QuerySubQuery([
                'SELECT' => 'id',
                'FROM'   => 'glpi_users',
                'WHERE'  => ['is_active' => 1, 'is_deleted' => 0],
            ])
        ]);
        $users_ids = array_column($super_admin_authorizations, 'users_id');

        return
            count($users_ids) == 1 // Only one super admin auth
            && $users_ids[0] == $this->fields['id'] // Id match our user
        ;
    }

    public function willProcessRuleRight(): void
    {
        $this->must_process_ruleright = true;
    }

    /**
     * Toggle pin of given itemtype saved search.
     *
     * @param string $itemtype
     *
     * @return bool
     */
    public function toggleSavedSearchPin(string $itemtype): bool
    {
        if (getItemForItemtype($itemtype) === false) {
            return false;
        }

        $all_pinned     = importArrayFromDB($this->fields['savedsearches_pinned']);
        $already_pinned = $all_pinned[$itemtype] ?? 0;

        $all_pinned[$itemtype] = $already_pinned ? 0 : 1;

        return $this->update(Sanitizer::sanitize([
            'id'                   => $this->fields['id'],
            'savedsearches_pinned' => exportArrayToDB($all_pinned),
        ]));
    }
}
