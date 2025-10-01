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
use Glpi\Error\ErrorHandler;
use Glpi\Toolbox\Filesystem;
use LDAP\Connection;
use LDAP\Result;
use Safe\Exceptions\DatetimeException;
use Safe\Exceptions\LdapException;
use Safe\Exceptions\NetworkException;

use function Safe\fsockopen;
use function Safe\gmmktime;
use function Safe\ldap_bind;
use function Safe\ldap_get_entries;
use function Safe\ldap_set_option;
use function Safe\parse_url;
use function Safe\preg_match;
use function Safe\preg_replace;
use function Safe\strtotime;
use function Safe\unpack;

/**
 *  Class used to manage Auth LDAP config
 */
class AuthLDAP extends CommonDBTM
{
    public const SIMPLE_INTERFACE = 'simple';
    public const EXPERT_INTERFACE = 'expert';

    public const ACTION_IMPORT      = 0;
    public const ACTION_SYNCHRONIZE = 1;
    public const ACTION_ALL         = 2;

    public const USER_IMPORTED      = 0;
    public const USER_SYNCHRONIZED  = 1;
    public const USER_DELETED_LDAP  = 2;
    public const USER_RESTORED_LDAP = 3;

    /** Import user by giving his login */
    public const IDENTIFIER_LOGIN = 'login';

    /** Import user by giving his email */
    public const IDENTIFIER_EMAIL = 'email';

    public const GROUP_SEARCH_USER    = 0;
    public const GROUP_SEARCH_GROUP   = 1;
    public const GROUP_SEARCH_BOTH    = 2;

    /**
     * Deleted user strategy: preserve user.
     * @var int
     * @deprecated
     */
    public const DELETED_USER_PRESERVE = 0;

    /**
     * Deleted user strategy: put user in trashbin.
     * @var int
     * @deprecated
     */
    public const DELETED_USER_DELETE = 1;

    /**
     * Deleted user strategy: withdraw dynamic authorizations and groups.
     * @var int
     * @deprecated
     */
    public const DELETED_USER_WITHDRAWDYNINFO = 2;

    /**
     * Deleted user strategy: disable user.
     * @var int
     * @deprecated
     */
    public const DELETED_USER_DISABLE = 3;

    /**
     * Deleted user strategy: disable user and withdraw dynamic authorizations and groups.
     * @var int
     * @deprecated
     */
    public const DELETED_USER_DISABLEANDWITHDRAWDYNINFO = 4;

    /**
     * Deleted user strategy: disable user and withdraw groups.
     * @var int
     * @deprecated
     */
    public const DELETED_USER_DISABLEANDDELETEGROUPS = 5;

    // Deleted user strategies for user
    public const DELETED_USER_ACTION_USER_DO_NOTHING = 0;
    public const DELETED_USER_ACTION_USER_DISABLE = 1;
    public const DELETED_USER_ACTION_USER_MOVE_TO_TRASHBIN = 2;

    // Deleted user strategies for groups
    public const DELETED_USER_ACTION_GROUPS_DO_NOTHING = 0;
    public const DELETED_USER_ACTION_GROUPS_DELETE_DYNAMIC = 1;
    public const DELETED_USER_ACTION_GROUPS_DELETE_ALL = 2;

    // Deleted user strategies for authorizations
    public const DELETED_USER_ACTION_AUTHORIZATIONS_DO_NOTHING = 0;
    public const DELETED_USER_ACTION_AUTHORIZATIONS_DELETE_DYNAMIC = 1;
    public const DELETED_USER_ACTION_AUTHORIZATIONS_DELETE_ALL = 2;

    /**
     * Restored user strategy: Make no change to GLPI user
     * @var integer
     * @since 10.0.0
     */
    public const RESTORED_USER_PRESERVE = 0;

    /**
     * Restored user strategy: Restore user from trash
     * @var integer
     * @since 10.0.0
     */
    public const RESTORED_USER_RESTORE = 1;

    /**
     * Restored user strategy: Re-enable user
     * @var integer
     * @since 10.0.0
     */
    public const RESTORED_USER_ENABLE  = 3;

    /**
     * List of TLS versions
     * @var array
     * @since 11.0.0
     */
    public const TLS_VERSIONS = [
        '1.0' => '1.0',
        '1.1' => '1.1',
        '1.2' => '1.2',
        '1.3' => '1.3',
    ];

    // From CommonDBTM
    public $dohistory = true;

    public static $rightname = 'config';

    /** connection caching stuff */
    public static $conn_cache = [];

    public static $undisclosedFields = [
        'rootdn_passwd',
    ];

    /**
     * Message of last error occurred during connection.
     * @var ?string
     */
    private static ?string $last_error = null;
    /**
     * Numero of last error occurred during connection.
     * @var ?int
     */
    private static ?int $last_errno = null;

    public static function getTypeName($nb = 0)
    {
        return _n('LDAP directory', 'LDAP directories', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['config', Auth::class, self::class];
    }

    public static function canCreate(): bool
    {
        return static::canUpdate();
    }

    public static function canPurge(): bool
    {
        return static::canUpdate();
    }

    public function post_getEmpty()
    {
        $this->fields['port']                        = '389';
        $this->fields['condition']                   = '';
        $this->fields['login_field']                 = 'uid';
        $this->fields['sync_field']                  = null;
        $this->fields['use_tls']                     = 0;
        $this->fields['group_field']                 = '';
        $this->fields['group_condition']             = '';
        $this->fields['group_search_type']           = self::GROUP_SEARCH_USER;
        $this->fields['group_member_field']          = '';
        $this->fields['email1_field']                = 'mail';
        $this->fields['email2_field']                = '';
        $this->fields['email3_field']                = '';
        $this->fields['email4_field']                = '';
        $this->fields['realname_field']              = 'sn';
        $this->fields['firstname_field']             = 'givenname';
        $this->fields['phone_field']                 = 'telephonenumber';
        $this->fields['phone2_field']                = '';
        $this->fields['mobile_field']                = '';
        $this->fields['registration_number_field']   = '';
        $this->fields['comment_field']               = '';
        $this->fields['title_field']                 = '';
        $this->fields['use_dn']                      = 0;
        $this->fields['use_bind']                    = 1;
        $this->fields['picture_field']               = '';
        $this->fields['responsible_field']           = '';
        $this->fields['can_support_pagesize']        = 0;
        $this->fields['pagesize']                    = 0;
        $this->fields['ldap_maxlimit']               = 0;
        $this->fields['begin_date_field']            = '';
        $this->fields['end_date_field']              = '';
    }

    /**
     * Preconfig datas for standard system
     *
     * @param string $type type of standard system : AD
     *
     * @return void
     */
    public function preconfig($type)
    {
        switch ($type) {
            case 'AD':
                $this->fields['port']                      = "389";
                $this->fields['condition']
                 = '(&(objectClass=user)(objectCategory=person)(!(userAccountControl:1.2.840.113556.1.4.803:=2)))';
                $this->fields['login_field']               = 'samaccountname';
                $this->fields['sync_field']                = 'objectguid';
                $this->fields['use_tls']                   = 0;
                $this->fields['group_field']               = 'memberof';
                $this->fields['group_condition']
                 = '(&(objectClass=user)(objectCategory=person)(!(userAccountControl:1.2.840.113556.1.4.803:=2)))';
                $this->fields['group_search_type']         = self::GROUP_SEARCH_USER;
                $this->fields['group_member_field']        = '';
                $this->fields['email1_field']              = 'mail';
                $this->fields['email2_field']              = '';
                $this->fields['email3_field']              = '';
                $this->fields['email4_field']              = '';
                $this->fields['realname_field']            = 'sn';
                $this->fields['firstname_field']           = 'givenname';
                $this->fields['phone_field']               = 'telephonenumber';
                $this->fields['phone2_field']              = 'othertelephone';
                $this->fields['mobile_field']              = 'mobile';
                $this->fields['registration_number_field'] = 'employeenumber';
                $this->fields['comment_field']             = 'info';
                $this->fields['title_field']               = 'title';
                $this->fields['use_dn']                    = 1;
                $this->fields['can_support_pagesize']      = 1;
                $this->fields['pagesize']                  = '1000';
                $this->fields['picture_field']             = '';
                $this->fields['responsible_field']         = 'manager';
                $this->fields['begin_date_field']          = 'whenCreated';
                $this->fields['end_date_field']            = 'accountExpires';
                break;
            case 'OpenLDAP':
                $this->fields['port']                      = "389";
                $this->fields['condition']                 = '(objectClass=inetOrgPerson)';
                $this->fields['login_field']               = 'uid';
                $this->fields['sync_field']                = 'entryuuid';
                $this->fields['use_tls']                   = 0;
                $this->fields['group_field']               = '';
                $this->fields['group_condition']           = '(objectClass=inetOrgPerson)';
                $this->fields['group_search_type']         = self::GROUP_SEARCH_GROUP;
                $this->fields['group_member_field']        = 'member';
                $this->fields['email1_field']              = 'mail';
                $this->fields['email2_field']              = '';
                $this->fields['email3_field']              = '';
                $this->fields['email4_field']              = '';
                $this->fields['realname_field']            = 'sn';
                $this->fields['firstname_field']           = 'givenname';
                $this->fields['phone_field']               = 'telephonenumber';
                $this->fields['phone2_field']              = 'homephone';
                $this->fields['mobile_field']              = 'mobile';
                $this->fields['registration_number_field'] = 'employeenumber';
                $this->fields['comment_field']             = 'description';
                $this->fields['title_field']               = 'title';
                $this->fields['use_dn']                    = 1;
                $this->fields['can_support_pagesize']      = 1;
                $this->fields['pagesize']                  = '1000';
                $this->fields['picture_field']             = 'jpegphoto';
                $this->fields['responsible_field']         = 'manager';
                $this->fields['category_field']            = 'businesscategory';
                $this->fields['language_field']            = 'preferredlanguage';
                $this->fields['location_field']            = 'l';
                break;

            default:
                $this->post_getEmpty();
        }
    }

    public function prepareInputForUpdate($input)
    {
        if (isset($input["rootdn_passwd"])) {
            if (empty($input["rootdn_passwd"])) {
                unset($input["rootdn_passwd"]);
            } else {
                $input["rootdn_passwd"] = (new GLPIKey())->encrypt($input["rootdn_passwd"]);
            }
        }

        if (isset($input["_blank_passwd"]) && $input["_blank_passwd"]) {
            $input['rootdn_passwd'] = '';
        }

        // Set attributes in lower case
        if (count($input)) {
            foreach ($input as $key => $val) {
                if (str_ends_with($key, '_field')) {
                    $input[$key] = Toolbox::strtolower($val);
                }
            }
        }

        // do not permit to override sync_field
        if (
            $this->isSyncFieldEnabled()
            && isset($input['sync_field'])
            && $this->isSyncFieldUsed()
        ) {
            if ($input['sync_field'] === $this->fields['sync_field']) {
                unset($input['sync_field']);
            } else {
                Session::addMessageAfterRedirect(
                    __s('Synchronization field cannot be changed once in use.'),
                    false,
                    ERROR
                );
                return false;
            };
        }

        if (!$this->checkFilesExist($input)) {
            return false;
        }

        return $input;
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'group_search_type':
                return htmlescape(self::getGroupSearchTypeName($values[$field]));
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
            case 'group_search_type':
                $options['value'] = $values[$field];
                $options['name']  = $name;
                return self::dropdownGroupSearchType($options);
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    public static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item, array $ids)
    {
        $input = $ma->getInput();

        switch ($ma->getAction()) {
            case 'import_group':
                $group = new Group();
                if (
                    !Session::haveRight("user", User::UPDATEAUTHENT)
                    || !$group->canGlobal(UPDATE)
                ) {
                    $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_NORIGHT);
                    $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                    return;
                }
                foreach ($ids as $id) {
                    if (isset($input["dn"][$id])) {
                        $group_dn = $input["dn"][$id];
                        if (isset($input["ldap_import_entities"][$id])) {
                            $entity = (int) $input["ldap_import_entities"][$id];
                        } else {
                            $entity = $_SESSION["glpiactive_entity"];
                        }
                        // Is recursive is in the main form and thus, don't pass through
                        // zero_on_empty mechanism inside massive action form ...
                        $is_recursive = (empty($input['ldap_import_recursive'][$id]) ? 0 : 1);
                        $options      = [
                            'authldaps_id' => $_REQUEST['authldaps_id'],
                            'entities_id'  => $entity,
                            'is_recursive' => $is_recursive,
                            'type'         => $input['ldap_import_type'][$id],
                        ];
                        if (self::ldapImportGroup($group_dn, $options)) {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                        } else {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                            $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION, htmlescape($group_dn)));
                        }
                    }
                    // Clean history as id does not correspond to group
                    $_SESSION['glpimassiveactionselected'] = [];
                }
                return;

            case 'import':
            case 'sync':
                if (!Session::haveRight("user", User::IMPORTEXTAUTHUSERS)) {
                    $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_NORIGHT);
                    $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                    return;
                }
                foreach ($ids as $id) {
                    if (
                        self::ldapImportUserByServerId(
                            ['method' => self::IDENTIFIER_LOGIN,
                                'value'  => $id,
                            ],
                            (int) $_REQUEST['mode'],
                            $_REQUEST['authldaps_id'],
                            true
                        )
                    ) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                    } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION, htmlescape($id)));
                    }
                }
                return;
        }

        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }

    /**
     * Print the auth ldap form
     *
     * @param integer $ID      ID of the item
     * @param array   $options Options
     *     - target for the form
     *
     * @return void|boolean (display) Returns false if there is a rights error.
     */
    public function showForm($ID, array $options = [])
    {
        if (!Config::canUpdate()) {
            return false;
        }
        if (empty($ID)) {
            $this->getEmpty();
            if (isset($options['preconfig'])) {
                $this->preconfig($options['preconfig']);
            }
        } else {
            $this->getFromDB($ID);
        }

        if (Toolbox::canUseLdap()) {
            // Fill fields when using preconfiguration models
            $hidden_fields = [];
            if (!$ID) {
                $hidden_fields = [
                    'comment_field', 'email1_field', 'email2_field',
                    'email3_field', 'email4_field',
                    'firstname_field', 'group_condition',
                    'group_field', 'group_member_field', 'group_search_type',
                    'mobile_field', 'phone_field', 'phone2_field',
                    'realname_field', 'registration_number_field', 'title_field',
                    'use_dn', 'use_tls', 'picture_field', 'responsible_field', 'begin_date_field', 'end_date_field',
                    'category_field', 'language_field', 'location_field',
                    'can_support_pagesize', 'pagesize',
                ];
            }

            TemplateRenderer::getInstance()->display('pages/setup/ldap/form.html.twig', [
                'item' => $this,
                'params' => $options,
                'hidden_fields' => $hidden_fields,
            ]);
        } else {
            $twig_params = [
                'missing_ext' => sprintf(__('%s extension is missing'), 'LDAP'),
                'impossible_to_use_ldap' => __('Impossible to use LDAP as external source of connection'),
                'support_promote_message' => GLPINetwork::getSupportPromoteMessage(),
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                <div class="text-center alert alert-danger">
                    <i class="ti ti-alert-triangle alert-icon"></i>
                    <div class="alert-text">
                        {{ missing_ext }}
                        <br>
                        {{ impossible_to_use_ldap }}
                    </div>
                    <span class="text-secondary fw-bold">{{ support_promote_message }}</span>
                </div>
TWIG, $twig_params);
        }
    }

    /**
     * Show advanced config form
     *
     * @return void
     */
    public function showFormAdvancedConfig()
    {
        TemplateRenderer::getInstance()->display('pages/setup/ldap/adv_info.html.twig', [
            'item' => $this,
            'page_size_available' => self::isLdapPageSizeAvailable(false, false),
            'gmt_values' => Dropdown::getGMTValues(),
            'params' => [
                'formfooter' => false,
                'candel' => false, // No deletion outside the main tab
            ],
        ]);
    }

    /**
     * Show config replicates form
     *
     * @return void
     */
    public function showFormReplicatesConfig()
    {
        global $DB;

        $ID     = $this->getID();
        $target = static::getFormURL();

        AuthLdapReplicate::addNewReplicateForm($target, $ID);

        $iterator = $DB->request([
            'FROM'   => 'glpi_authldapreplicates',
            'WHERE'  => [
                'authldaps_id' => $ID,
            ],
            'ORDER'  => ['name'],
        ]);

        if (count($iterator) > 0) {
            // language=Twig
            $test_button = TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                <button type="button" class="btn btn-primary" name="test_ldap_replicate">{{ msg }}</button>
TWIG, ['msg' => _x('button', 'Test')]);
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                <script>
                    $(() => {
                        $('button[name="test_ldap_replicate"]').on('click', (e) => {
                            const replicate_id = $(e.target).closest('tr').data('id');
                            $(e.target).prepend(`<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>`);
                            $(e.target).prop('disabled', true);
                            $.post(
                                '{{ path('ajax/ldap.php')|e('js') }}',
                                {
                                    id: '{{ authldaps_id|e('js') }}',
                                    ldap_replicate_id: replicate_id,
                                    action: 'test_ldap_replicate'
                                }
                            ).then(() => {
                                displaySessionMessages();
                                $(e.target).find('.spinner-border').remove();
                                $(e.target).prop('disabled', false);
                            });
                        });
                    });
                </script>
TWIG, ['authldaps_id' => $ID]);

            $entries = [];
            foreach ($iterator as $ldap_replicate) {
                $entries[] = [
                    'itemtype' => 'AuthLdapReplicate',
                    'id'       => $ldap_replicate["id"],
                    'name'     => $ldap_replicate["name"],
                    'replicate' => $ldap_replicate["host"] . ':' . $ldap_replicate["port"],
                    'timeout'  => $ldap_replicate["timeout"],
                    'test'     => $test_button,
                ];
            }

            TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
                'is_tab' => true,
                'nofilter' => true,
                'nosort' => true,
                'super_header' => __('List of LDAP directory replicates'),
                'columns' => [
                    'name' => __('Name'),
                    'replicate' => _n('Replicate', 'Replicates', 1),
                    'timeout' => __('Timeout'),
                    'test' => '',
                ],
                'formatters' => [
                    'timeout' => 'integer',
                    'test' => 'raw_html',
                ],
                'entries' => $entries,
                'total_number' => count($entries),
                'filtered_number' => count($entries),
                'showmassiveactions' => true,
                'massiveactionparams' => [
                    'num_displayed' => count($entries),
                    'container'     => 'massAuthLdapReplicate' . mt_rand(),
                    'item'          => $this,
                ],
            ]);
        }
    }

    /**
     * Build a dropdown
     *
     * @since 0.84
     *
     * @param array $options Options
     *
     * @return string
     */
    public static function dropdownGroupSearchType(array $options)
    {
        $p = array_replace([
            'name'    => 'group_search_type',
            'value'   => self::GROUP_SEARCH_USER,
            'display' => true,
        ], $options);

        $tab = self::getGroupSearchTypeName();
        return Dropdown::showFromArray($p['name'], $tab, $p);
    }

    /**
     * Get the possible value for contract alert
     *
     * @since 0.83
     *
     * @param integer $val if not set, ask for all values, else for 1 value (default NULL)
     *
     * @return array|string
     */
    public static function getGroupSearchTypeName($val = null)
    {
        $tmp = [
            self::GROUP_SEARCH_USER    => __('In users'),
            self::GROUP_SEARCH_GROUP   => __('In groups'),
            self::GROUP_SEARCH_BOTH    => __('In users and groups'),
        ];

        if (is_null($val)) {
            return $tmp;
        }
        return $tmp[$val] ?? NOT_AVAILABLE;
    }

    /**
     * Show group config form
     *
     * @return void
     */
    public function showFormGroupsConfig()
    {
        TemplateRenderer::getInstance()->display('pages/setup/ldap/group_config.html.twig', [
            'item' => $this,
            'params' => [
                'formfooter' => false,
                'candel' => false, // No deletion outside the main tab
            ],
        ]);
    }

    /**
     * Show ldap test form results.
     *
     * @return void
     */
    public function showFormTestLDAP()
    {
        $tests = $this->testLDAPServer();

        // Mark the last checked test as "active"
        $previous_test = null;
        foreach ($tests as $test => $result) {
            if (!$result['checked'] && $previous_test !== null) {
                $tests[$previous_test]['active'] = true;
                break;
            }
            $previous_test = $test;
        }

        TemplateRenderer::getInstance()->display('pages/setup/ldap/test_form.html.twig', [
            'servername' => $this->getField('name'),
            'tests' => $tests,
        ]);
    }

    /**
     * Performs many tests on the specified LDAP server.
     *
     * @return array result of tests
     */
    private function testLDAPServer(): array
    {
        $tests = [
            'testLDAPSockopen'   => [
                'title'   => __('TCP stream'),
                'checked' => false,
                'success' => false,
                'message' => '',
            ],
            'testLDAPBaseDN'     => [
                'title'   => __('Base DN'),
                'checked' => false,
                'success' => false,
                'message' => '',
            ],
            'testLDAPURI'        => [
                'title'   => __('LDAP URI'),
                'checked' => false,
                'success' => false,
                'message' => '',
            ],
            'testLDAPBind'       => [
                'title'   => __('Bind connection'),
                'checked' => false,
                'success' => false,
                'message' => '',
            ],
            'testLDAPSearch'     => [
                'title'   => __('Search (50 first entries)'),
                'checked' => false,
                'success' => false,
                'message' => '',
            ],
        ];

        $connection = null;
        foreach (array_keys($tests) as $testFunction) {
            $result = $this->$testFunction($connection);
            $tests[$testFunction]['checked'] = true;
            $tests[$testFunction]['success'] = $result['success'];
            $tests[$testFunction]['message'] = $result['message'];
            if (!$result['success']) {
                break;
            }
        }

        return $tests;
    }

    /**
     * Show user config form
     *
     * @return void
     */
    public function showFormUserConfig()
    {
        TemplateRenderer::getInstance()->display('pages/setup/ldap/user_config_form.html.twig', [
            'item' => $this,
            'params' => [
                'formfooter' => false,
                'candel' => false, // No deletion outside the main tab
            ],
            'fields' => [
                'realname_field'            => __('Surname'),
                'firstname_field'           => __('First name'),
                'comment_field'             => _n('Comment', 'Comments', Session::getPluralNumber()),
                'registration_number_field' => _x('user', 'Administrative number'),
                'email1_field'              => _n('Email', 'Emails', 1),
                'email2_field'              => sprintf('%1$s %2$s', _n('Email', 'Emails', 1), '2'),
                'email3_field'              => sprintf('%1$s %2$s', _n('Email', 'Emails', 1), '3'),
                'email4_field'              => sprintf('%1$s %2$s', _n('Email', 'Emails', 1), '4'),
                'phone_field'               => _x('ldap', 'Phone'),
                'phone2_field'              => __('Phone 2'),
                'mobile_field'              => __('Mobile phone'),
                'title_field'               => _x('person', 'Title'),
                'category_field'            => _n('Category', 'Categories', 1),
                'language_field'            => __('Language'),
                'picture_field'             => _n('Picture', 'Pictures', 1),
                'location_field'            => Location::getTypeName(1),
                'begin_date_field'          => __('Valid since'),
                'end_date_field'            => __('Valid until'),
                'responsible_field'         => __('Supervisor'),
            ],
        ]);
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(self::class, $ong, $options);
        $this->addImpactTab($ong, $options);
        $this->addStandardTab(Log::class, $ong, $options);

        return $ong;
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => static::getTypeName(1),
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => static::getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => static::getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'datatype'           => 'number',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => static::getTable(),
            'field'              => 'host',
            'name'               => __('Server'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => static::getTable(),
            'field'              => 'port',
            'name'               => _n('Port', 'Ports', 1),
            'datatype'           => 'integer',
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => static::getTable(),
            'field'              => 'basedn',
            'name'               => __('BaseDN'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => static::getTable(),
            'field'              => 'condition',
            'name'               => __('Connection filter'),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => static::getTable(),
            'field'              => 'is_default',
            'name'               => __('Default server'),
            'datatype'           => 'bool',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '8',
            'table'              => static::getTable(),
            'field'              => 'login_field',
            'name'               => __('Login field'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '9',
            'table'              => static::getTable(),
            'field'              => 'realname_field',
            'name'               => __('Surname'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '10',
            'table'              => static::getTable(),
            'field'              => 'firstname_field',
            'name'               => __('First name'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '11',
            'table'              => static::getTable(),
            'field'              => 'phone_field',
            'name'               =>  _x('ldap', 'Phone'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '12',
            'table'              => static::getTable(),
            'field'              => 'phone2_field',
            'name'               => __('Phone 2'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '13',
            'table'              => static::getTable(),
            'field'              => 'mobile_field',
            'name'               => __('Mobile phone'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '14',
            'table'              => static::getTable(),
            'field'              => 'title_field',
            'name'               => _x('person', 'Title'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '15',
            'table'              => static::getTable(),
            'field'              => 'category_field',
            'name'               => _n('Category', 'Categories', 1),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => static::getTable(),
            'field'              => 'comment',
            'name'               => _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '17',
            'table'              => static::getTable(),
            'field'              => 'email1_field',
            'name'               => _n('Email', 'Emails', 1),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '25',
            'table'              => static::getTable(),
            'field'              => 'email2_field',
            'name'               => sprintf(__('%1$s %2$s'), _n('Email', 'Emails', 1), '2'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '26',
            'table'              => static::getTable(),
            'field'              => 'email3_field',
            'name'               => sprintf(__('%1$s %2$s'), _n('Email', 'Emails', 1), '3'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '27',
            'table'              => static::getTable(),
            'field'              => 'email4_field',
            'name'               => sprintf(__('%1$s %2$s'), _n('Email', 'Emails', 1), '4'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '18',
            'table'              => static::getTable(),
            'field'              => 'use_dn',
            'name'               => __('Use DN in the search'),
            'datatype'           => 'bool',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => static::getTable(),
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '121',
            'table'              => static::getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '20',
            'table'              => static::getTable(),
            'field'              => 'language_field',
            'name'               => __('Language'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '21',
            'table'              => static::getTable(),
            'field'              => 'group_field',
            'name'               => __('User attribute containing its groups'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '22',
            'table'              => static::getTable(),
            'field'              => 'group_condition',
            'name'               => __('Filter to search in groups'),
            'massiveaction'      => false,
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '23',
            'table'              => static::getTable(),
            'field'              => 'group_member_field',
            'name'               => __('Group attribute containing its users'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '24',
            'table'              => static::getTable(),
            'field'              => 'group_search_type',
            'datatype'           => 'specific',
            'name'               => __('Search type'),
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '30',
            'table'              => static::getTable(),
            'field'              => 'is_active',
            'name'               => __('Active'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '28',
            'table'              => static::getTable(),
            'field'              => 'sync_field',
            'name'               => __('Synchronization field'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '29',
            'table'              => static::getTable(),
            'field'              => 'responsible_field',
            'name'               => __('Supervisor'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '31',
            'table'              => static::getTable(),
            'field'              => 'inventory_domain',
            'name'               => __('Domain name used by inventory tool'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '32',
            'table'              => static::getTable(),
            'field'              => 'timeout',
            'name'               => __('Timeout'),
            'massiveaction'      => false,
            'datatype'           => 'number',
            'unit'               => 'second',
            'toadd'              => [
                '0'                  => __('No timeout'),
            ],
        ];

        $tab[] = [
            'id'                 => '33',
            'table'              => static::getTable(),
            'field'              => 'begin_date_field',
            'name'               => __('Valid since'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '34',
            'table'              => static::getTable(),
            'field'              => 'end_date_field',
            'name'               => __('Valid until'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        return $tab;
    }

    /**
     * Get system information
     *
     * @return array
     * @phpstan-return array{label: string, content: string}
     * @used-by templates/pages/setup/general/systeminfo_table.html.twig
     */
    public function getSystemInformation(): array
    {
        // No need to translate, this part always display in english (for copy/paste to forum)
        $ldap_servers = self::getLdapServers();
        $content = '';

        if (!empty($ldap_servers)) {
            foreach ($ldap_servers as $value) {
                $fields = [
                    'Server'            => 'host',
                    'Port'              => 'port',
                    'BaseDN'            => 'basedn',
                    'Connection filter' => 'condition',
                    'RootDN'            => 'rootdn',
                    'Use TLS'           => 'use_tls',
                ];
                $msg   = '';
                $first = true;
                foreach ($fields as $label => $field) {
                    $msg .= (!$first ? ', ' : '')
                        . ($label !== 'Server' ? "\n\t" : '') . $label . ': '
                        . ($value[$field] ? '\'' . $value[$field] . '\'' : 'none');
                    $first = false;
                }
                $content .= $msg . "\n\n";
            }
        }

        return [
            'label' => self::getTypeName(Session::getPluralNumber()),
            'content' => $content,
        ];
    }

    /**
     * Get LDAP fields to sync to GLPI data from a glpi_authldaps array
     *
     * @param array $authtype_array Authentication method config array (from table)
     *
     * @return array of "user table field name" => "config value"
     */
    public static function getSyncFields(array $authtype_array)
    {
        $ret    = [];
        $fields = [
            'login_field'               => 'name',
            'email1_field'              => 'email1',
            'email2_field'              => 'email2',
            'email3_field'              => 'email3',
            'email4_field'              => 'email4',
            'realname_field'            => 'realname',
            'firstname_field'           => 'firstname',
            'phone_field'               => 'phone',
            'phone2_field'              => 'phone2',
            'mobile_field'              => 'mobile',
            'location_field'            => 'locations_id',
            'comment_field'             => 'comment',
            'title_field'               => 'usertitles_id',
            'category_field'            => 'usercategories_id',
            'language_field'            => 'language',
            'registration_number_field' => 'registration_number',
            'picture_field'             => 'picture',
            'responsible_field'         => 'users_id_supervisor',
            'sync_field'                => 'sync_field',
            'begin_date_field'          => 'begin_date',
            'end_date_field'            => 'end_date',
        ];

        foreach ($fields as $key => $val) {
            if (!empty($authtype_array[$key])) {
                $ret[$val] = $authtype_array[$key];
            }
        }
        return $ret;
    }

    /**
     * Converts LDAP timestamps over to Unix timestamps
     *
     * @param string  $ldapstamp        LDAP timestamp
     * @param integer $ldap_time_offset time offset (default 0)
     *
     * @return integer|'' unix timestamp or an empty string if the LDAP timestamp is invalid
     */
    public static function ldapStamp2UnixStamp($ldapstamp, $ldap_time_offset = 0)
    {
        global $CFG_GLPI;

        // Check if timestamp is well format, otherwise return ''
        if (!preg_match("/[\d]{14}(\.[\d]{0,4})*Z/", $ldapstamp)) {
            return '';
        }

        $year    = (int) substr($ldapstamp, 0, 4);
        $month   = (int) substr($ldapstamp, 4, 2);
        $day     = (int) substr($ldapstamp, 6, 2);
        $hour    = (int) substr($ldapstamp, 8, 2);
        $minute  = (int) substr($ldapstamp, 10, 2);
        $seconds = (int) substr($ldapstamp, 12, 2);
        $stamp   = gmmktime($hour, $minute, $seconds, $month, $day, $year);
        $stamp  += $CFG_GLPI["time_offset"] - $ldap_time_offset;

        return $stamp;
    }

    /**
     * Converts a Unix timestamp to an LDAP timestamps
     *
     * @param string $date datetime
     *
     * @return string ldap timestamp
     */
    public static function date2ldapTimeStamp($date)
    {
        try {
            $strdate = strtotime($date);
        } catch (DatetimeException $e) {
            $strdate = 0;
        }
        return date("YmdHis", $strdate) . '.0Z';
    }

    /**
     * Return the LDAP field to use for user synchronization
     * It may be sync_field if defined, or login_field
     * @since 9.2
     *
     * @return string the ldap field to use for user synchronization
     */
    public function getLdapIdentifierToUse()
    {
        if (!empty($this->fields['sync_field'])) {
            return $this->fields['sync_field'];
        }
        return $this->fields['login_field'];
    }

    /**
     * Return the database field to use for user synchronization
     * @since 9.2
     *
     * @return string the database field to use for user synchronization
     */
    public function getDatabaseIdentifierToUse()
    {
        if (!empty($this->fields['sync_field'])) {
            return 'sync_field';
        }
        return 'name';
    }

    /**
     * Indicates if there's a sync_field enabled in the LDAP configuration
     * @since 9.2
     *
     * @return boolean true if the sync_field is enabled (the field is filled)
     */
    public function isSyncFieldEnabled()
    {
        return (!empty($this->fields['sync_field']));
    }

    /**
     * Check if the sync_field is configured for an LDAP server
     *
     * @since 9.2
     * @param integer $authldaps_id the LDAP server ID
     *
     * @return boolean true if configured, false if not configured
     */
    public static function isSyncFieldConfigured($authldaps_id)
    {
        $authldap = new self();
        $authldap->getFromDB($authldaps_id);
        return ($authldap->isSyncFieldEnabled());
    }

    /**
     * Test a LDAP connection
     *
     * @param integer $auths_id ID of the LDAP server
     * @param integer $replicate_id use a replicate if > 0 (default -1)
     *
     * @return boolean connection succeeded?
     * @throws SodiumException
     */
    public static function testLDAPConnection($auths_id, $replicate_id = -1)
    {

        $config_ldap = new self();
        $res         = $config_ldap->getFromDB($auths_id);

        // we prevent some delay...
        if (!$res) {
            return false;
        }

        //Test connection to a replicate
        if ($replicate_id !== -1) {
            $replicate = new AuthLdapReplicate();
            $replicate->getFromDB($replicate_id);
            $host = $replicate->fields["host"];
            $port = $replicate->fields["port"];
        } else {
            //Test connection to a master ldap server
            $host = $config_ldap->fields['host'];
            $port = $config_ldap->fields['port'];
        }
        $ds = self::connectToServer(
            $host,
            $port,
            $config_ldap->fields['rootdn'],
            (new GLPIKey())->decrypt($config_ldap->fields['rootdn_passwd']),
            $config_ldap->fields['use_tls'],
            $config_ldap->fields['deref_option'],
            $config_ldap->fields['tls_certfile'],
            $config_ldap->fields['tls_keyfile'],
            $config_ldap->fields['use_bind'],
            $config_ldap->fields['timeout'],
            $config_ldap->fields['tls_version']
        );
        if ($ds) {
            return true;
        }
        return false;
    }

    /**
     * Test if a socket connection is possible towards the LDAP server.
     *
     * @param Connection|null $connection
     * @return array [success => boolean, message => string]
     * @used-by self::testLDAPServer()
     */
    private function testLDAPSockopen(?Connection &$connection): array
    {
        $hostname = $this->fields['host'] ?? '';
        $port_num = $this->fields['port'];

        $matches = [];
        if (preg_match('/(ldaps?:\/\/)(?<host>.+)/', $hostname, $matches)) {
            $host = $matches['host'];
        } else {
            $host = $hostname;
        }

        $errno = null;
        $errstr = null;

        if ($host === '') {
            $errno = 0;
            $errstr = __('No hostname provided');
        }

        try {
            @fsockopen($host, $port_num, $errno, $errstr, 5);
            return [
                'success' => true,
                'message' => sprintf(__('Connection to %s on port %s succeeded'), $host, $port_num),
            ];
        } catch (NetworkException $e) {
            return [
                'success' => false,
                'message' => sprintf(__('%s (ERR: %s) to %s on port %s'), $errstr, $errno, $host, $port_num),
            ];
        }
    }

    /**
     * Test if basedn field is correctly configured.
     *
     * @param Connection|null $connection
     * @return array [success => boolean, message => string]
     * @used-by self::testLDAPServer()
     */
    private function testLDAPBaseDN(?Connection &$connection): array
    {
        if (!empty($this->fields['basedn'])) {
            return [
                'success' => true,
                'message' => sprintf(__('Base DN "%s" is configured'), $this->fields['basedn']),
            ];
        } else {
            return [
                'success' => false,
                'message' => __('Base DN is not configured'),
            ];
        }
    }

    /**
     * Test if a LDAP connect object initialisation is possible.
     *
     * @param Connection|null $connection
     * @return array [success => boolean, message => string]
     * @used-by self::testLDAPServer()
     */
    private function testLDAPURI(?Connection &$connection): array
    {
        if (@ldap_connect($this->fields['host'], $this->fields['port'])) {
            return [
                'success' => true,
                'message' => __('LDAP URI check succeeded'),
            ];
        } else {
            return [
                'success' => false,
                'message' => sprintf(__('LDAP URI was not parseable (%s:%s)'), $this->fields['host'], $this->fields['port']),
            ];
        }
    }

    /**
     * Test if a LDAP bind is possible.
     *
     * @param Connection|null $connection
     * @return array [success => boolean, message => string]
     * @used-by self::testLDAPServer()
     */
    private function testLDAPBind(?Connection &$connection): array
    {
        if ($this->fields['use_bind']) {
            $connection_result = self::connectToServer(
                $this->fields['host'],
                $this->fields['port'],
                $this->fields['rootdn'],
                (new GLPIKey())->decrypt($this->fields['rootdn_passwd']),
                $this->fields['use_tls'],
                $this->fields['deref_option'],
                $this->fields['tls_certfile'],
                $this->fields['tls_keyfile'],
                $this->fields['use_bind'],
                $this->fields['timeout'],
                $this->fields['tls_version'],
                true
            );
            if ($connection_result !== false) {
                $connection = $connection_result;
                return [
                    'success' => true,
                    'message' => __('Authentication succeeded'),
                ];
            } else {
                return [
                    'success' => false,
                    'message' => sprintf(__('Authentication failed: %s(%s)'), self::$last_error, self::$last_errno),
                ];
            }
        } else {
            return [
                'success' => true,
                'message' => __('Bind user / password authentication is disabled.'),
            ];
        }
    }

    /**
     * Test if a LDAP search is possible.
     *
     * @param Connection|null $connection
     * @return array [success => boolean, message => string]
     * @used-by self::testLDAPServer()
     */
    private function testLDAPSearch(?Connection &$connection): array
    {
        if ($connection === null) {
            $connection_result = self::connectToServer(
                $this->fields['host'],
                $this->fields['port'],
                $this->fields['rootdn'],
                (new GLPIKey())->decrypt($this->fields['rootdn_passwd']),
                $this->fields['use_tls'],
                $this->fields['deref_option'],
                $this->fields['tls_certfile'],
                $this->fields['tls_keyfile'],
                $this->fields['use_bind'],
                $this->fields['timeout'],
                $this->fields['tls_version'],
                true
            );
            if ($connection_result !== false) {
                $connection = $connection_result;
            }
        }
        if ($connection) {
            $filter = $this->fields['condition'];
            if (empty($filter)) {
                $filter = '(objectclass=*)';
            }
            $sr = @ldap_search($connection, $this->fields['basedn'], $filter, [], 0, 50);
            if ($sr) {
                $info = @ldap_get_entries($connection, $sr);
                if ($info['count'] > 0) {
                    return [
                        'success' => true,
                        'message' => sprintf(__('Search succeeded (%d entries found)'), $info['count']),
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => sprintf(__('Search failed: %s(%s)'), ldap_error($connection), ldap_errno($connection)),
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => sprintf(__('Search failed: %s(%s)'), ldap_error($connection), ldap_errno($connection)),
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => sprintf(__('Search failed: %s(%s)'), ldap_error($connection), ldap_errno($connection)),
            ];
        }
    }

    /**
     * Display a warning about size limit
     *
     * @since 0.84
     *
     * @param boolean $limitexceeded (false by default)
     *
     * @return void
     */
    public static function displaySizeLimitWarning($limitexceeded = false)
    {
        if ($limitexceeded) {
            $twig_params = [
                'warning' => __('Warning'),
                'warning_long' => __('Warning: The request exceeds the limit of the directory. The results are only partial.'),
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                <div class="mb-3">
                    <div class="alert alert-warning" role="alert">
                        <i class="alert-icon ti ti-alert-triangle"></i>
                        <div class="alert-title">{{ warning }}</div>
                        <span class="text-secondary">{{ warning_long }}</span>
                </div>
TWIG, $twig_params);
        }
    }

    /**
     * Show LDAP users to add or synchronise
     *
     * @return void
     */
    public static function showLdapUsers()
    {
        $values = array_replace([
            'order' => 'DESC',
            'start' => 0,
        ], $_REQUEST);

        $results       = [];
        $limitexceeded = false;
        $ldap_users    = self::getUsers($values, $results, $limitexceeded);
        $total_results = count($ldap_users);

        $config_ldap   = new AuthLDAP();
        $config_ldap->getFromDB($values['authldaps_id']);

        echo "<div class='card p-3 mt-3'>";
        self::displaySizeLimitWarning($limitexceeded);

        // delete end
        array_splice($ldap_users, $values['start'] + $_SESSION['glpilist_limit']);
        // delete begin
        if ($values['start'] > 0) {
            array_splice($ldap_users, 0, $values['start']);
        }

        if ($values['mode']) {
            $textbutton  = _x('button', 'Synchronize');
            $form_action = self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'sync';
        } else {
            $textbutton  = _x('button', 'Import');
            $form_action = self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'import';
        }

        $entries = [];
        foreach ($ldap_users as $userinfos) {
            $entry = [
                'id' => $userinfos['uid'],
            ];
            if ($config_ldap->isSyncFieldEnabled()) {
                $entry['sync_field'] = $userinfos['uid'];
            }
            if (isset($userinfos['id']) && User::canView()) {
                $entry['user'] = "<a href='" . htmlescape($userinfos['link']) . "'>" . htmlescape($userinfos['name']) . "</a>";
            } else {
                $entry['user'] = htmlescape($userinfos['link']);
            }

            $date_mod = '';
            if ($userinfos['stamp'] !== '') {
                $date_mod = date("Y-m-d H:i:s", $userinfos['stamp']);
            }
            if ($values['mode'] && $userinfos['date_sync'] !== '') {
                $date_mod = $userinfos['date_sync'];
            }
            $entry['date_mod'] = $date_mod;

            $entry['itemtype'] = self::class; // required for massive actions

            $entries[] = $entry;
        }

        $columns = [];
        if ($config_ldap->isSyncFieldEnabled()) {
            $columns['sync_field'] = __('Synchronization field');
        }
        $columns['user'] = User::getTypeName(1);
        $columns['date_mod'] = $values['mode'] ? __('Last update in GLPI') : __('Last update in the LDAP directory');

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => false,
            'nofilter' => true,
            'nosort' => true,
            'start' => $values['start'],
            'limit' => $_SESSION['glpilist_limit'],
            'columns' => $columns,
            'formatters' => [
                'user' => 'raw_html',
                'date_mod' => 'datetime',
            ],
            'entries' => $entries,
            'total_number' => $total_results,
            'filtered_number' => $total_results,
            'showmassiveactions' => true,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . self::class . mt_rand(),
                'specific_actions' => [$form_action => $textbutton],
                'extraparams' => [
                    'authldaps_id' => $config_ldap->getID(),
                    'mode'         => $values['mode'],
                    'massive_action_fields' => [
                        'authldaps_id',
                        'mode',
                    ],
                ],
            ],
        ]);
        echo "</div>";
    }

    /**
     * Search users
     *
     * @param Connection $ds An LDAP link identifier
     * @param array    $values        values to search
     * @param string   $filter        search filter
     * @param array    $attrs         An array of the required attributes
     * @param boolean  $limitexceeded is limit exceeded
     * @param array    $user_infos    user information
     * @param array    $ldap_users    ldap users
     * @param object   $config_ldap   ldap configuration
     *
     * @return boolean
     */
    public static function searchForUsers(
        $ds,
        $values,
        $filter,
        $attrs,
        &$limitexceeded,
        &$user_infos,
        &$ldap_users,
        $config_ldap
    ) {

        // If paged results cannot be used (PHP < 5.4)
        $cookie   = ''; // Cookie used to perform query using pages
        $count    = 0;  // Store the number of results ldap_search

        do {
            if (self::isLdapPageSizeAvailable($config_ldap)) {
                $controls = [
                    [
                        'oid'       => LDAP_CONTROL_PAGEDRESULTS,
                        'iscritical' => true,
                        'value'     => [
                            'size'   => $config_ldap->fields['pagesize'],
                            'cookie' => $cookie,
                        ],
                    ],
                ];
                $sr = @ldap_search($ds, $values['basedn'], $filter, $attrs, 0, -1, -1, LDAP_DEREF_NEVER, $controls);
                if (
                    $sr === false
                    || @ldap_parse_result($ds, $sr, $errcode, $matcheddn, $errmsg, $referrals, $controls) === false // @phpstan-ignore theCodingMachineSafe.function
                ) {
                    // 32 = LDAP_NO_SUCH_OBJECT => This error can be silented as it just means that search produces no result.
                    if (ldap_errno($ds) !== 32) {
                        trigger_error(
                            static::buildError(
                                $ds,
                                sprintf('LDAP search with base DN `%s` and filter `%s` failed', $values['basedn'], $filter)
                            ),
                            E_USER_WARNING
                        );
                    }
                    return false;
                }
                if (isset($controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'])) {
                    $cookie = $controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'];
                } else {
                    $cookie = '';
                }
            } else {
                $sr = @ldap_search($ds, $values['basedn'], $filter, $attrs);
                if ($sr === false) {
                    // 32 = LDAP_NO_SUCH_OBJECT => This error can be silented as it just means that search produces no result.
                    if (ldap_errno($ds) !== 32) {
                        trigger_error(
                            static::buildError(
                                $ds,
                                sprintf('LDAP search with base DN `%s` and filter `%s` failed', $values['basedn'], $filter)
                            ),
                            E_USER_WARNING
                        );
                    }
                    return false;
                }
            }

            if (in_array(ldap_errno($ds), [4,11])) {
                // openldap return 4 for Size limit exceeded
                $limitexceeded = true;
            }

            $info = self::get_entries_clean($ds, $sr);
            if (in_array(ldap_errno($ds), [4,11])) {
                // openldap return 4 for Size limit exceeded
                $limitexceeded = true;
            }

            $count += $info['count'];
            //If page results are enabled and the number of results is greater than the maximum allowed
            //warn user that limit is exceeded and stop search
            if (
                self::isLdapPageSizeAvailable($config_ldap)
                && $config_ldap->fields['ldap_maxlimit']
                && ($count > $config_ldap->fields['ldap_maxlimit'])
            ) {
                $limitexceeded = true;
                break;
            }

            $field_for_sync = $config_ldap->getLdapIdentifierToUse();
            $login_field = $config_ldap->fields['login_field'];

            for ($ligne = 0; $ligne < $info["count"]; $ligne++) {
                if (in_array($field_for_sync, $info[$ligne])) {
                    $uid = self::getFieldValue($info[$ligne], $field_for_sync);

                    if ($login_field != $field_for_sync && !isset($info[$ligne][$login_field])) {
                        trigger_error("Missing field $login_field for LDAP entry $field_for_sync $uid", E_USER_WARNING);
                        //Login field may be missing... Skip the user
                        continue;
                    }

                    if (isset($info[$ligne]['modifytimestamp'])) {
                        $user_infos[$uid]["timestamp"] = self::ldapStamp2UnixStamp(
                            $info[$ligne]['modifytimestamp'][0],
                            $config_ldap->fields['time_offset']
                        );
                    } else {
                        $user_infos[$uid]["timestamp"] = '';
                    }

                    $user_infos[$uid]["user_dn"] = $info[$ligne]['dn'];
                    $user_infos[$uid][$field_for_sync] = $uid;
                    if ($config_ldap->isSyncFieldEnabled()) {
                        $user_infos[$uid][$login_field] = $info[$ligne][$login_field][0];
                    }

                    if ($values['mode'] == self::ACTION_IMPORT) {
                        //If ldap add
                        $ldap_users[$uid] = $uid;
                    } else {
                        //If ldap synchronisation
                        if (isset($info[$ligne]['modifytimestamp'])) {
                            $ldap_users[$uid] = self::ldapStamp2UnixStamp(
                                $info[$ligne]['modifytimestamp'][0],
                                $config_ldap->fields['time_offset']
                            );
                        } else {
                            $ldap_users[$uid] = '';
                        }
                        $user_infos[$uid]["name"] = $info[$ligne][$login_field][0];
                    }
                }
            }
        } while ($cookie != '');

        return true;
    }

    /**
     * Get the list of LDAP users to add/synchronize
     *
     * @param array   $options       possible options:
     *          - authldaps_id ID of the server to use
     *          - mode user to synchronize or add?
     *          - ldap_filter ldap filter to use
     *          - basedn force basedn (default authldaps_id one)
     *          - order display order
     *          - begin_date begin date to time limit
     *          - end_date end date to time limit
     *          - script true if called by an external script
     * @param array   $results       result stats
     * @param boolean $limitexceeded limit exceeded exception
     *
     * @return false|array
     */
    public static function getAllUsers(array $options, &$results, &$limitexceeded)
    {
        global $DB;

        $config_ldap = new self();
        $res         = $config_ldap->getFromDB($options['authldaps_id']);

        $values = [
            'order'       => 'DESC',
            'mode'        => self::ACTION_SYNCHRONIZE,
            'ldap_filter' => '',
            'basedn'      => $config_ldap->fields['basedn'],
            'begin_date'  => null,
            'end_date'    => date('Y-m-d H:i:s', time() - DAY_TIMESTAMP),
            'script'      => 0, //Called by an external script or not
        ];

        foreach ($options as $option => $value) {
            // this test break mode detection - if ($value != '') {
            $values[$option] = $value;
            //}
        }

        $ldap_users    = [];
        $user_infos    = [];
        $limitexceeded = false;

        // we prevent some delay...
        if (!$res) {
            return false;
        }
        if ($values['order'] !== "DESC") {
            $values['order'] = "ASC";
        }
        $ds = $config_ldap->connect();
        $field_for_sync = $config_ldap->getLdapIdentifierToUse();
        $field_for_db   = $config_ldap->getDatabaseIdentifierToUse();
        if ($ds) {
            //Search for ldap login AND modifyTimestamp,
            //which indicates the last update of the object in directory
            $attrs = [$config_ldap->fields['login_field'], "modifyTimestamp"];
            if ($field_for_sync !== $config_ldap->fields['login_field']) {
                $attrs[] = $field_for_sync;
            }

            // Try a search to find the DN
            if ($values['ldap_filter'] === '') {
                $filter = "(" . $field_for_sync . "=*)";
                if (!empty($config_ldap->fields['condition'])) {
                    $filter = "(& $filter " . $config_ldap->fields['condition'] . ")";
                }
            } else {
                $filter = $values['ldap_filter'];
            }

            if ($values['script'] && !empty($values['begin_date'])) {
                $filter_timestamp = self::addTimestampRestrictions(
                    $values['begin_date'],
                    $values['end_date']
                );
                $filter           = "(&$filter $filter_timestamp)";
            }
            $result = self::searchForUsers(
                $ds,
                $values,
                $filter,
                $attrs,
                $limitexceeded,
                $user_infos,
                $ldap_users,
                $config_ldap
            );
            if (!$result) {
                return false;
            }
        } else {
            return false;
        }

        $glpi_users = [];

        $select = [
            'FROM'   => User::getTable(),
            'ORDER'  => ['name ' . $values['order']],
        ];

        if ($values['mode'] !== self::ACTION_IMPORT) {
            $select['WHERE'] = [
                'authtype'  => [-1, Auth::NOT_YET_AUTHENTIFIED, Auth::LDAP, Auth::EXTERNAL, Auth::CAS],
                'auths_id'  => $options['authldaps_id'],
            ];
        }

        $iterator = $DB->request($select);

        foreach ($iterator as $user) {
            $tmpuser = new User();

            //Ldap add : fill the array with the login of the user
            if ($values['mode'] === self::ACTION_IMPORT) {
                $glpi_users[$user['name']] = $user['name'];
            } else {
                //Ldap synchronisation : look if the user exists in the directory
                //and compares the modifications dates (ldap and glpi db)
                $userfound = self::dnExistsInLdap($user_infos, $user['user_dn']);
                if (!empty($ldap_users[$user[$field_for_db]]) || $userfound) {
                    // userfound seems that user dn is present in GLPI DB but do not correspond to an GLPI user
                    // -> renaming case
                    if ($userfound) {
                        //Get user in DB with this dn
                        if (!$tmpuser->getFromDBByDn($user['user_dn'])) {
                            //This should never happened
                            //If a user_dn is present more than one time in database
                            //Just skip user synchronization to avoid errors
                            continue;
                        }
                        $glpi_users[] = ['id'         => $user['id'],
                            'user'       => $userfound['name'],
                            $field_for_sync => ($userfound[$config_ldap->fields['sync_field']] ?? 'NULL'),
                            'timestamp'  => $user_infos[$userfound[$field_for_sync]]['timestamp'],
                            'date_sync'  => $tmpuser->fields['date_sync'],
                            'dn'         => $user['user_dn'],
                        ];
                    } elseif (
                        ($values['mode'] === self::ACTION_ALL)
                          || (($ldap_users[$user[$field_for_db]] - strtotime($user['date_sync'])) > 0)
                    ) {
                        //If entry was modified or if script should synchronize all the users
                        $glpi_users[] = ['id'         => $user['id'],
                            'user'       => $user['name'],
                            $field_for_sync => $user['sync_field'],
                            'timestamp'  => $user_infos[$user[$field_for_db]]['timestamp'],
                            'date_sync'  => $user['date_sync'],
                            'dn'         => $user['user_dn'],
                        ];
                    }
                } elseif (
                    ($values['mode'] === self::ACTION_ALL)
                        && !$limitexceeded
                ) {
                    // Only manage deleted user if ALL (because of entity visibility in delegated mode)

                    if ($user['auths_id'] === $options['authldaps_id']) {
                        if ((int) $user['is_deleted_ldap'] === 0) {
                            // If user is marked as coming from LDAP, but is not present in it anymore
                            User::manageDeletedUserInLdap($user['id']);
                            $results[self::USER_DELETED_LDAP]++;
                        } elseif ((int) $user['is_deleted_ldap'] === 1) {
                            // User is marked as coming from LDAP, but was previously deleted
                            User::manageRestoredUserInLdap($user['id']);
                            $results[self::USER_RESTORED_LDAP]++;
                        }
                    }
                }
            }
        }

        // If add, do the difference between ldap users and glpi users
        if ($values['mode'] === self::ACTION_IMPORT) {
            $diff    = array_diff_ukey($ldap_users, $glpi_users, 'strcasecmp');
            $list    = [];
            $tmpuser = new User();

            foreach ($diff as $user) {
                // If user dn exists in DB, it means that user login field has changed
                if (!$tmpuser->getFromDBByDn($user_infos[$user]["user_dn"])) {
                    $entry  = ["user"      => $user_infos[$user][$config_ldap->fields['login_field']],
                        "timestamp" => $user_infos[$user]["timestamp"],
                        "date_sync" => Dropdown::EMPTY_VALUE,
                    ];
                    if ($config_ldap->isSyncFieldEnabled()) {
                        $entry[$field_for_sync] = $user_infos[$user][$field_for_sync];
                    }
                    $list[] = $entry;
                }
            }
            if ($values['order'] === 'DESC') {
                rsort($list);
            } else {
                sort($list);
            }

            return $list;
        }
        return $glpi_users;
    }

    /**
     * Check if a user DN exists in a ldap user search result
     *
     * @since 0.84
     *
     * @param array  $ldap_infos ldap user search result
     * @param string $user_dn    user dn to look for
     *
     * @return false|array false if the user dn doesn't exist, user ldap infos otherwise
     */
    public static function dnExistsInLdap($ldap_infos, $user_dn)
    {
        $found = false;
        foreach ($ldap_infos as $ldap_info) {
            if ($ldap_info['user_dn'] === $user_dn) {
                $found = $ldap_info;
                break;
            }
        }
        return $found;
    }

    /**
     * Show LDAP groups to add or synchronize in an entity
     *
     * @param integer $start   where to start the list
     * @param integer $sync    synchronize or add? (default 0)
     * @param string  $filter  ldap filter to use (default '')
     * @param string  $filter2 second ldap filter to use (which case?) (default '')
     * @param integer $entity  working entity
     *
     * @return void
     *
     * @since 11.0.0 The `$target` and the `$order` parameters have been removed.
     */
    public static function showLdapGroups(
        $start,
        $sync = 0,
        $filter = '',
        $filter2 = '',
        $entity = 0
    ) {
        $limitexceeded = false;
        $ldap_groups   = self::getAllGroups(
            $_REQUEST['authldaps_id'],
            $filter,
            $filter2,
            $entity,
            $limitexceeded
        );
        $total_results = count($ldap_groups);

        echo "<div class='card p-3 mt-3'>";
        self::displaySizeLimitWarning($limitexceeded);

        // delete end
        array_splice($ldap_groups, $start + $_SESSION['glpilist_limit']);
        // delete begin
        if ($start > 0) {
            array_splice($ldap_groups, 0, $start);
        }

        $dn_index = 1;
        $entries = [];
        foreach ($ldap_groups as $groupinfos) {
            $entry = [
                'id'       => $dn_index,
                'itemtype' => self::class, // required for massive actions
            ];
            $group       = $groupinfos["cn"];
            $group_dn    = $groupinfos["dn"];
            $search_type = $groupinfos["search_type"];
            $group_cell = '';
            $group_cell .= Html::hidden("dn[$dn_index]", [
                'value'                 => $group_dn,
                'data-glpicore-ma-tags' => 'common',
            ]);
            $group_cell .= Html::hidden("ldap_import_type[$dn_index]", [
                'value'                 => $search_type,
                'data-glpicore-ma-tags' => 'common',
            ]);
            if (Session::isMultiEntitiesMode()) {
                $group_cell .= Html::hidden("ldap_import_recursive[$dn_index]", [
                    'value'                 => 0,
                    'data-glpicore-ma-tags' => 'common',
                ]);
            }
            $group_cell .= htmlescape($group);
            $entry['group'] = $group_cell;
            $entry['group_dn'] = $group_dn;
            if (Session::isMultiEntitiesMode()) {
                $entry['entity'] = Entity::dropdown([
                    'value'         => $entity,
                    'name'          => "ldap_import_entities[$dn_index]",
                    'specific_tags' => ['data-glpicore-ma-tags' => 'common'],
                    'display'       => false,
                ]);
                $entry['child_entities'] = Html::getCheckbox([
                    'name'          => "ldap_import_recursive[$dn_index]",
                    'massive_tags' => 'select_item_child_entities',
                    'specific_tags' => ['data-glpicore-ma-tags' => 'common'],
                ]);
            }
            $entries[] = $entry;
            $dn_index++;
        }

        $columns = [
            'group' => Group::getTypeName(1),
            'group_dn' => __('Group DN'),
        ];
        if (Session::isMultiEntitiesMode()) {
            $columns['entity'] = __('Destination entity');

            $chk_all_child_entities = Html::getCheckbox([
                'criterion' => ['tag_for_massive' => 'select_item_child_entities'],
            ]);
            $columns['child_entities'] = [
                'label' => $chk_all_child_entities . __s('Child entities'),
                'raw_header' => true,
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => false,
            'nofilter' => true,
            'nosort' => true,
            'start' => $start,
            'limit' => $_SESSION['glpilist_limit'],
            'columns' => $columns,
            'formatters' => [
                'group' => 'raw_html', // Raw because there are some hidden inputs added here. The Group itself is pre-sanitized.
                'entity' => 'raw_html', // Select HTML element
                'child_entities' => 'raw_html', // Checkbox HTML element
            ],
            'entries' => $entries,
            'total_number' => $total_results,
            'filtered_number' => $total_results,
            'showmassiveactions' => true,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . self::class . mt_rand(),
                'specific_actions' => [
                    self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'import_group' => _sx('button', 'Import'),
                ],
                'extraparams' => [
                    'authldaps_id' => $_REQUEST['authldaps_id'],
                    'massive_action_fields' => [
                        'authldaps_id',
                        'dn',
                        'ldap_import_type',
                        'ldap_import_entities',
                        'ldap_import_recursive',
                    ],
                ],
            ],
        ]);

        echo "</div>";
    }

    /**
     * Get all LDAP groups from a ldap server which are not already in an entity
     *
     * @since 0.84 new parameter $limitexceeded
     *
     * @param integer $auths_id      ID of the server to use
     * @param string  $filter        ldap filter to use
     * @param string  $filter2       second ldap filter to use if needed
     * @param int     $entity        entity to search
     * @param boolean $limitexceeded is limit exceeded
     *
     * @return array of the groups
     *
     * @since 11.0.0 $order parameter has been removed.
     */
    public static function getAllGroups(
        $auths_id,
        $filter,
        $filter2,
        $entity,
        &$limitexceeded
    ) {
        global $DB;

        $config_ldap = new self();
        $config_ldap->getFromDB($auths_id);
        $infos       = [];
        $groups      = [];

        $ds = $config_ldap->connect();
        if ($ds) {
            switch ($config_ldap->fields["group_search_type"]) {
                case self::GROUP_SEARCH_USER:
                    $infos = self::getGroupsFromLDAP(
                        $ds,
                        $config_ldap,
                        $filter,
                        $limitexceeded,
                        false,
                        $infos
                    );
                    break;

                case self::GROUP_SEARCH_GROUP:
                    $infos = self::getGroupsFromLDAP(
                        $ds,
                        $config_ldap,
                        $filter,
                        $limitexceeded,
                        true,
                        $infos
                    );
                    break;

                case self::GROUP_SEARCH_BOTH:
                    $infos = self::getGroupsFromLDAP(
                        $ds,
                        $config_ldap,
                        $filter,
                        $limitexceeded,
                        true,
                        $infos
                    );
                    $infos = self::getGroupsFromLDAP(
                        $ds,
                        $config_ldap,
                        $filter2,
                        $limitexceeded,
                        false,
                        $infos
                    );
                    break;
            }
            if (!empty($infos)) {
                $glpi_groups = [];

                //Get all groups from GLPI DB for the current entity and the subentities
                $iterator = $DB->request([
                    'SELECT' => ['ldap_group_dn','ldap_value'],
                    'FROM'   => 'glpi_groups',
                    'WHERE'  => getEntitiesRestrictCriteria('glpi_groups'),
                ]);

                //If the group exists in DB -> unset it from the LDAP groups
                foreach ($iterator as $group) {
                    //use DN for next step
                    //depending on the type of search when groups are imported
                    //the DN may be in two separate fields
                    if (!empty($group["ldap_group_dn"])) {
                        $glpi_groups[$group["ldap_group_dn"]] = 1;
                    } elseif (!empty($group["ldap_value"])) {
                        $glpi_groups[$group["ldap_value"]] = 1;
                    }
                }
                $ligne = 0;

                foreach ($infos as $dn => $info) {
                    //reconcile by DN
                    if (!isset($glpi_groups[$dn])) {
                        $groups[$ligne]["dn"]          = $dn;
                        $groups[$ligne]["cn"]          = $info["cn"];
                        $groups[$ligne]["search_type"] = $info["search_type"];
                        $ligne++;
                    }
                }
            }

            usort(
                $groups,
                static fn($a, $b) => strcasecmp($a['cn'], $b['cn'])
            );
        }
        return $groups;
    }

    /**
     * Get the group's cn by giving his DN
     *
     * @param Connection $ldap_connection ldap connection to use
     * @param string   $group_dn        the group's dn
     *
     * @return false|string the group cn
     */
    public static function getGroupCNByDn($ldap_connection, $group_dn)
    {
        $sr = @ldap_read($ldap_connection, $group_dn, "objectClass=*", ["cn"]);
        if ($sr === false) {
            // 32 = LDAP_NO_SUCH_OBJECT => This error can be silented as it just means that search produces no result.
            if (ldap_errno($ldap_connection) !== 32) {
                trigger_error(
                    static::buildError(
                        $ldap_connection,
                        sprintf(
                            'Unable to get LDAP group having DN `%s`',
                            $group_dn
                        )
                    ),
                    E_USER_WARNING
                );
            }
            return false;
        }
        $v  = self::get_entries_clean($ldap_connection, $sr);
        if (!is_array($v) || (count($v) === 0) || empty($v[0]["cn"][0])) {
            return false;
        }
        return $v[0]["cn"][0];
    }

    /**
     * Set groups from ldap
     *
     * @since 0.84 new parameter $limitexceeded
     *
     * @param Connection $ldap_connection LDAP connection
     * @param object   $config_ldap      LDAP configuration
     * @param string   $filter           Filters
     * @param boolean  $limitexceeded    Is limit exceeded
     * @param boolean  $search_in_groups Search in groups (true by default)
     * @param array    $groups           Groups to search
     *
     * @return array
     */
    public static function getGroupsFromLDAP(
        $ldap_connection,
        $config_ldap,
        $filter,
        &$limitexceeded,
        $search_in_groups = true,
        $groups = []
    ) {
        global $DB;

        //First look for groups in group objects
        $extra_attribute = ($search_in_groups ? "cn" : $config_ldap->fields["group_field"]);
        $attrs           = ["dn", $extra_attribute];

        if ($filter === '') {
            if ($search_in_groups) {
                $filter = (!empty($config_ldap->fields['group_condition'])
                       ? $config_ldap->fields['group_condition'] : "(objectclass=*)");
            } else {
                $filter = (!empty($config_ldap->fields['condition'])
                       ? $config_ldap->fields['condition'] : "(objectclass=*)");
            }
        }
        $cookie = '';
        $count  = 0;
        do {
            if (self::isLdapPageSizeAvailable($config_ldap)) {
                $controls = [
                    [
                        'oid'       => LDAP_CONTROL_PAGEDRESULTS,
                        'iscritical' => true,
                        'value'     => [
                            'size'   => $config_ldap->fields['pagesize'],
                            'cookie' => $cookie,
                        ],
                    ],
                ];
                $sr = @ldap_search($ldap_connection, $config_ldap->fields['basedn'], $filter, $attrs, 0, -1, -1, LDAP_DEREF_NEVER, $controls);
                if (
                    $sr === false
                    || @ldap_parse_result($ldap_connection, $sr, $errcode, $matcheddn, $errmsg, $referrals, $controls) === false // @phpstan-ignore theCodingMachineSafe.function
                ) {
                    // 32 = LDAP_NO_SUCH_OBJECT => This error can be silented as it just means that search produces no result.
                    if (ldap_errno($ldap_connection) !== 32) {
                        trigger_error(
                            static::buildError(
                                $ldap_connection,
                                sprintf('LDAP search with base DN `%s` and filter `%s` failed', $config_ldap->fields['basedn'], $filter)
                            ),
                            E_USER_WARNING
                        );
                    }
                    return $groups;
                }
                if (isset($controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'])) {
                    $cookie = $controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'];
                } else {
                    $cookie = '';
                }
            } else {
                $sr = @ldap_search($ldap_connection, $config_ldap->fields['basedn'], $filter, $attrs);
                if ($sr === false) {
                    // 32 = LDAP_NO_SUCH_OBJECT => This error can be silented as it just means that search produces no result.
                    if (ldap_errno($ldap_connection) !== 32) {
                        trigger_error(
                            static::buildError(
                                $ldap_connection,
                                sprintf('LDAP search with base DN `%s` and filter `%s` failed', $config_ldap->fields['basedn'], $filter)
                            ),
                            E_USER_WARNING
                        );
                    }
                    return $groups;
                }
            }

            if (in_array(ldap_errno($ldap_connection), [4,11])) {
                // openldap return 4 for Size limit exceeded
                $limitexceeded = true;
            }

            $infos  = self::get_entries_clean($ldap_connection, $sr);
            if (in_array(ldap_errno($ldap_connection), [4,11])) {
                // openldap return 4 for Size limit exceeded
                $limitexceeded = true;
            }

            $count += $infos['count'];
            // If page results are enabled and the number of results is greater than the maximum allowed
            // warn user that limit is exceeded and stop search
            if (
                self::isLdapPageSizeAvailable($config_ldap)
                && $config_ldap->fields['ldap_maxlimit']
                && ($count > $config_ldap->fields['ldap_maxlimit'])
            ) {
                $limitexceeded = true;
                break;
            }

            for ($ligne = 0; $ligne < $infos["count"]; $ligne++) {
                if ($search_in_groups) {
                    // No cn : not a real object
                    if (isset($infos[$ligne]["cn"][0])) {
                        $groups[$infos[$ligne]["dn"]] = (["cn" => $infos[$ligne]["cn"][0],
                            "search_type" => "groups",
                        ]);
                    }
                } else {
                    if (isset($infos[$ligne][$extra_attribute])) {
                        if (
                            ($config_ldap->fields["group_field"] == 'dn')
                            || in_array('ou', $groups)
                        ) {
                            $dn = $infos[$ligne][$extra_attribute];
                            $ou = [];
                            for ($tmp = $dn; count($tmptab = explode(',', $tmp, 2)) == 2; $tmp = $tmptab[1]) {
                                $ou[] = $tmptab[1];
                            }

                            // Search in DB for group with ldap_group_dn
                            if (
                                ($config_ldap->fields["group_field"] == 'dn')
                                && (count($ou) > 0)
                            ) {
                                $iterator = $DB->request([
                                    'SELECT' => ['ldap_value'],
                                    'FROM'   => 'glpi_groups',
                                    'WHERE'  => [
                                        'ldap_group_dn' => $ou,
                                    ],
                                ]);
                                foreach ($iterator as $group) {
                                    $groups[$group['ldap_value']] = ["cn"          => $group['ldap_value'],
                                        "search_type" => "users",
                                    ];
                                }
                            }
                        } else {
                            for (
                                $ligne_extra = 0; $ligne_extra < $infos[$ligne][$extra_attribute]["count"];
                                $ligne_extra++
                            ) {
                                $groups[$infos[$ligne][$extra_attribute][$ligne_extra]]
                                = ["cn"   => self::getGroupCNByDn(
                                    $ldap_connection,
                                    $infos[$ligne][$extra_attribute][$ligne_extra]
                                ),
                                    "search_type"
                                         => "users",
                                ];
                            }
                        }
                    }
                }
            }
        } while ($cookie != '');

        return $groups;
    }

    /**
     * Force synchronization for one user
     *
     * @param User    $user              User to synchronize
     * @param boolean $clean_ldap_fields empty user_dn and sync_field before import user again
     * @param boolean $display           Display message information on redirect (true by default)
     *
     * @return array|boolean  with state, else false
     */
    public static function forceOneUserSynchronization(User $user, $clean_ldap_fields = false, $display = true)
    {
        $authldap = new AuthLDAP();

        //Get the LDAP server from which the user has been imported
        if ($authldap->getFromDB($user->fields['auths_id'])) {
            // clean ldap fields if asked by admin
            if ($clean_ldap_fields) {
                $user->update([
                    'id'         => $user->fields['id'],
                    'user_dn'    => '',
                    'sync_field' => '',
                ]);
            }

            $user_field = 'name';
            $id_field = $authldap->fields['login_field'];
            if ($authldap->isSyncFieldEnabled() && !empty($user->fields['sync_field'])) {
                $user_field = 'sync_field';
                $id_field   = $authldap->fields['sync_field'];
            }
            return self::ldapImportUserByServerId(
                [
                    'method'             => self::IDENTIFIER_LOGIN,
                    'value'              => $user->fields[$user_field],
                    'identifier_field'   => $id_field,
                    'user_field'         => $user_field,
                ],
                self::ACTION_SYNCHRONIZE,
                $user->fields["auths_id"],
                $display
            );
        }
        return false;
    }

    /**
     * Import a user from a specific ldap server
     *
     * @param array $params of parameters: method (IDENTIFIER_LOGIN or IDENTIFIER_EMAIL) + value
     * @param int $action synchronize (self::ACTION_SYNCHRONIZE) or import (self::ACTION_IMPORT)
     * @param integer $ldap_server ID of the LDAP server to use
     * @param boolean $display display message information on redirect (false by default)
     *
     * @return array|boolean  with state, else false
     * @throws SodiumException
     */
    public static function ldapImportUserByServerId(
        array $params,
        $action,
        $ldap_server,
        $display = false
    ) {
        global $DB;

        $config_ldap = new self();
        $res         = $config_ldap->getFromDB($ldap_server);
        $input = [];

        // we prevent some delay...
        if (!$res) {
            return false;
        }

        if (!isset($params['identifier_field'])) {
            $params['identifier_field'] = $config_ldap->getLdapIdentifierToUse();
        }
        if (!isset($params['user_field'])) {
            $params['user_field'] = $config_ldap->getDatabaseIdentifierToUse();
        }

        $search_parameters = [];
        //Connect to the directory
        if (
            isset(self::$conn_cache[$ldap_server])
            // check that connection is still alive
            && @ldap_read(self::$conn_cache[$ldap_server], '', '(objectclass=*)', ['dn'], 0, 1) !== false
        ) {
            $ds = self::$conn_cache[$ldap_server];
        } else {
            $ds = $config_ldap->connect();
        }
        if ($ds) {
            self::$conn_cache[$ldap_server] = $ds;
            $search_parameters['method']                         = $params['method'];
            $search_parameters['fields'][self::IDENTIFIER_LOGIN] = $params['identifier_field'];

            if ($params['method'] === self::IDENTIFIER_EMAIL) {
                $search_parameters['fields'][self::IDENTIFIER_EMAIL]
                                       = $config_ldap->fields['email1_field'];
            }

            //Get the user's dn & login
            $attribs = ['basedn'            => $config_ldap->fields['basedn'],
                'login_field'       => $search_parameters['fields'][$search_parameters['method']],
                'search_parameters' => $search_parameters,
                'user_params'       => $params,
                'condition'         => $config_ldap->fields['condition'],
            ];

            try {
                $error = null;
                $infos = self::searchUserDn($ds, $attribs, $error);

                if ($error === true) {
                    return false;
                }

                if ($infos && $infos['dn']) {
                    $user_dn = $infos['dn'];
                    $user    = new User();

                    $login   = self::getFieldValue($infos, $search_parameters['fields'][$search_parameters['method']]);

                    //Get information from LDAP
                    if (
                        $user->getFromLDAP(
                            $ds,
                            $config_ldap->fields,
                            $user_dn,
                            $login,
                            ($action === self::ACTION_IMPORT)
                        )
                    ) {
                        //Get the ID by sync field (Used to check if restoration is needed)
                        $searched_user = new User();
                        $user_found = false;
                        if ($login === null || !($user_found = $searched_user->getFromDBbySyncField($login))) {
                            //In case user id has changed : get id by dn (Used to check if restoration is needed)
                            $user_found = $searched_user->getFromDBbyDn($user_dn);
                        }
                        if ($user_found && $searched_user->fields['is_deleted_ldap'] && $searched_user->fields['user_dn']) {
                            User::manageRestoredUserInLdap($searched_user->fields['id']);
                            return ['action' => self::USER_RESTORED_LDAP,
                                'id' => $searched_user->fields['id'],
                            ];
                        }

                        // Add the auth method
                        // Force date sync
                        $user->fields["date_sync"] = $_SESSION["glpi_currenttime"];
                        $user->fields['is_deleted_ldap'] = 0;

                        //Save information in database !
                        $input = $user->fields;

                        //clean picture from input
                        // (picture managed in User::post_addItem and prepareInputForUpdate)
                        unset($input['picture']);

                        if ($action === self::ACTION_IMPORT) {
                            $input["authtype"] = Auth::LDAP;
                            $input["auths_id"] = $ldap_server;
                            // Display message after redirect
                            if ($display) {
                                $input['add'] = 1;
                            }

                            $user->fields["id"] = $user->add($input);
                            return ['action' => self::USER_IMPORTED,
                                'id'     => $user->fields["id"],
                            ];
                        }
                        // Get the ID by user name
                        if (!($id = User::getIdByfield($params['user_field'], $login))) {
                            //In case user id as changed : get id by dn
                            $id = User::getIdByfield('user_dn', $user_dn);
                        }
                        $input['id'] = $id;

                        if ($display) {
                            $input['update'] = 1;
                        }
                        $user->update($input);
                        return ['action' => self::USER_SYNCHRONIZED,
                            'id'     => $input['id'],
                        ];
                    }
                    return false;
                }
                if ($action !== self::ACTION_IMPORT) {
                    $users_id = User::getIdByField($params['user_field'], $params['value']);
                    User::manageDeletedUserInLdap($users_id);
                    return ['action' => self::USER_DELETED_LDAP,
                        'id'     => $users_id,
                    ];
                }
            } catch (RuntimeException $e) {
                ErrorHandler::logCaughtException($e);
                ErrorHandler::displayCaughtExceptionMessage($e);
                return false;
            }
        }
        return false;
    }

    /**
     * Import grousp from an LDAP directory
     *
     * @param string $group_dn dn of the group to import
     * @param array $options array for
     *             - authldaps_id
     *             - entities_id where group must to be imported
     *             - is_recursive
     *
     * @return integer|false
     * @throws SodiumException
     */
    public static function ldapImportGroup($group_dn, $options = [])
    {
        $config_ldap = new self();
        $res         = $config_ldap->getFromDB($options['authldaps_id']);

        // we prevent some delay...
        if (!$res) {
            return false;
        }

        // Connect to the directory
        $ds = $config_ldap->connect();
        if ($ds) {
            $group_infos = self::getGroupByDn($ds, $group_dn);
            $group       = new Group();
            if ($options['type'] === "groups") {
                return $group->add(["name"          => $group_infos["cn"][0],
                    "ldap_group_dn" => $group_infos["dn"],
                    "entities_id"   => $options['entities_id'],
                    "is_recursive"  => $options['is_recursive'],
                ]);
            }
            return $group->add(["name"         => $group_infos["cn"][0],
                "ldap_field"   => $config_ldap->fields["group_field"],
                "ldap_value"   => $group_infos["dn"],
                "entities_id"  => $options['entities_id'],
                "is_recursive" => $options['is_recursive'],
            ]);
        }
        return false;
    }

    /**
     * Open LDAP connection to current server
     *
     * @return boolean|Connection
     * @throws SodiumException
     */
    public function connect()
    {
        return self::connectToServer(
            $this->fields['host'],
            $this->fields['port'],
            $this->fields['rootdn'],
            (new GLPIKey())->decrypt($this->fields['rootdn_passwd']),
            $this->fields['use_tls'],
            $this->fields['deref_option'],
            $this->fields['tls_certfile'],
            $this->fields['tls_keyfile'],
            $this->fields['use_bind'],
            $this->fields['timeout'],
            $this->fields['tls_version']
        );
    }

    /**
     * Connect to a LDAP server
     *
     * @param string  $host                 LDAP host to connect
     * @param string  $port                 port to use
     * @param string  $login                login to use (default '')
     * @param string  $password             password to use (default '')
     * @param boolean $use_tls              use a TLS connection? (false by default)
     * @param integer $deref_options        deref options used
     * @param string  $tls_certfile         TLS CERT file name within config directory (default '')
     * @param string  $tls_keyfile          TLS KEY file name within config directory (default '')
     * @param boolean $use_bind             do we need to do an ldap_bind? (true by default)
     * @param string  $tls_version          TLS VERSION (default '')
     * @param bool    $silent_bind_errors   Indicates whether bind errors must be silented
     *
     * @return false|Connection link to the LDAP server : false if connection failed
     */
    public static function connectToServer(
        $host,
        $port,
        $login = "",
        $password = "",
        $use_tls = false,
        $deref_options = 0,
        $tls_certfile = "",
        $tls_keyfile = "",
        $use_bind = true,
        $timeout = 0,
        $tls_version = "",
        bool $silent_bind_errors = false
    ) {
        self::$last_errno = null;
        self::$last_error = null;

        if (!is_string($host) || empty($host)) {
            throw new RuntimeException(
                'No host provided for connection!'
            );
        }
        if (!is_numeric($port) || empty($port)) {
            throw new RuntimeException(
                'No port provided for connection!'
            );
        }

        $ldapuri = self::buildUri($host, (int) $port);
        $ds = @ldap_connect($ldapuri);

        if ($ds === false) {
            trigger_error(
                sprintf(
                    "Unable to connect to LDAP server %s:%s",
                    $host,
                    $port
                ),
                E_USER_WARNING
            );
            return false;
        }

        $ldap_options = [
            LDAP_OPT_PROTOCOL_VERSION => 3,
            LDAP_OPT_REFERRALS        => 0,
            LDAP_OPT_DEREF            => $deref_options,
        ];

        if ($timeout > 0) {
            // Apply the timeout unless it is "unlimited" ("unlimited" is the default value defined in `libldap`).
            // see https://linux.die.net/man/3/ldap_set_option
            $ldap_options[LDAP_OPT_NETWORK_TIMEOUT] = $timeout;
        }

        foreach ($ldap_options as $option => $value) {
            try {
                @ldap_set_option($ds, $option, $value);
            } catch (LdapException $e) {
                trigger_error(
                    static::buildError(
                        $ds,
                        sprintf(
                            "Unable to set LDAP option `%s` to `%s`",
                            $option,
                            $value
                        )
                    ),
                    E_USER_WARNING
                );
            }
        }

        if (!empty($tls_certfile)) {
            if (!Filesystem::isFilepathSafe($tls_certfile)) {
                trigger_error("TLS certificate path is not safe.", E_USER_WARNING);
            } elseif (!file_exists($tls_certfile)) {
                trigger_error("TLS certificate path is not valid.", E_USER_WARNING);
            } else {
                try {
                    @ldap_set_option(null, LDAP_OPT_X_TLS_CERTFILE, $tls_certfile);
                } catch (LdapException $e) {
                    trigger_error("Unable to set LDAP option `LDAP_OPT_X_TLS_CERTFILE`", E_USER_WARNING);
                }
            }
        }
        if (!empty($tls_keyfile)) {
            if (!Filesystem::isFilepathSafe($tls_keyfile)) {
                trigger_error("TLS key file path is not safe.", E_USER_WARNING);
            } elseif (!file_exists($tls_keyfile)) {
                trigger_error("TLS key file path is not valid.", E_USER_WARNING);
            } else {
                try {
                    @ldap_set_option(null, LDAP_OPT_X_TLS_KEYFILE, $tls_keyfile);
                } catch (LdapException $e) {
                    trigger_error("Unable to set LDAP option `LDAP_OPT_X_TLS_KEYFILE`", E_USER_WARNING);
                }
            }
        }
        if (!empty($tls_version)) {
            $cipher_suite = 'NORMAL';
            foreach (self::TLS_VERSIONS as $tls_version_value) {
                $cipher_suite .= ($tls_version_value == $tls_version ? ':+' : ':!') . 'VERS-TLS' . $tls_version_value;
            }
            try {
                @ldap_set_option(null, LDAP_OPT_X_TLS_CIPHER_SUITE, $cipher_suite);
            } catch (LdapException $e) {
                trigger_error("Unable to set LDAP option `LDAP_OPT_X_TLS_CIPHER_SUITE`", E_USER_WARNING);
            }
        }

        if ($use_tls) {
            if (!@ldap_start_tls($ds)) {
                self::$last_errno = ldap_errno($ds);
                self::$last_error = ldap_error($ds);

                trigger_error(
                    static::buildError(
                        $ds,
                        sprintf(
                            "Unable to start TLS connection to LDAP server `%s:%s`",
                            $host,
                            $port
                        )
                    ),
                    E_USER_WARNING
                );
                return false;
            }
        }

        if (!$use_bind) {
            return $ds;
        }

        try {
            if ($login !== '') {
                // Auth bind
                @ldap_bind($ds, $login, $password);
            } else {
                // Anonymous bind
                @ldap_bind($ds);
            }
        } catch (LdapException $e) {
            self::$last_errno = ldap_errno($ds);
            self::$last_error = ldap_error($ds);

            if ($silent_bind_errors === false) {
                trigger_error(
                    static::buildError(
                        $ds,
                        sprintf(
                            "Unable to bind to LDAP server `%s:%s` %s",
                            $host,
                            $port,
                            ($login !== '' ? "with RDN `$login`" : 'anonymously')
                        )
                    ),
                    E_USER_WARNING
                );
            }
            return false;
        }

        return $ds;
    }

    /**
     * Try to connect to a ldap server
     *
     * @param array $ldap_method ldap_method array to use
     * @param string $login User Login
     * @param string $password User Password
     *
     * @return Connection|boolean link to the LDAP server : false if connection failed
     * @throws SodiumException
     */
    public static function tryToConnectToServer($ldap_method, $login, $password)
    {
        if (!function_exists('ldap_connect')) {
            trigger_error("ldap_connect function is missing. Did you miss install php-ldap extension?", E_USER_WARNING);
            return false;
        }
        $ds = self::connectToServer(
            $ldap_method['host'],
            $ldap_method['port'],
            $ldap_method['rootdn'],
            (new GLPIKey())->decrypt($ldap_method['rootdn_passwd']),
            $ldap_method['use_tls'],
            $ldap_method['deref_option'],
            $ldap_method['tls_certfile'] ?? '',
            $ldap_method['tls_keyfile'] ?? '',
            $ldap_method['use_bind'],
            $ldap_method['timeout'],
            $ldap_method['tls_version'] ?? ''
        );

        // Test with login and password of the user if exists
        if (
            !$ds
            && !empty($login)
            && (bool) $ldap_method['use_bind']
        ) {
            $ds = self::connectToServer(
                $ldap_method['host'],
                $ldap_method['port'],
                $login,
                $password,
                $ldap_method['use_tls'],
                $ldap_method['deref_option'],
                $ldap_method['tls_certfile'] ?? '',
                $ldap_method['tls_keyfile'] ?? '',
                $ldap_method['use_bind'],
                $ldap_method['timeout'],
                $ldap_method['tls_version'] ?? '',
                true // silent bind error when trying to bind with user login/password
            );
        }

        //If connection is not successful on this directory, try replicates (if replicates exists)
        if (
            !$ds
            && ($ldap_method['id'] > 0)
        ) {
            foreach (self::getAllReplicateForAMaster($ldap_method['id']) as $replicate) {
                $ds = self::connectToServer(
                    $replicate["host"],
                    $replicate["port"],
                    $ldap_method['rootdn'],
                    (new GLPIKey())->decrypt($ldap_method['rootdn_passwd']),
                    $ldap_method['use_tls'],
                    $ldap_method['deref_option'],
                    $ldap_method['tls_certfile'] ?? '',
                    $ldap_method['tls_keyfile'] ?? '',
                    $ldap_method['use_bind'],
                    $ldap_method['timeout'],
                    $ldap_method['tls_version'] ?? ''
                );

                // Test with login and password of the user
                if (
                    !$ds
                    && !empty($login)
                    && (bool) $ldap_method['use_bind']
                ) {
                    $ds = self::connectToServer(
                        $replicate["host"],
                        $replicate["port"],
                        $login,
                        $password,
                        $ldap_method['use_tls'],
                        $ldap_method['deref_option'],
                        $ldap_method['tls_certfile'] ?? '',
                        $ldap_method['tls_keyfile'] ?? '',
                        $ldap_method['use_bind'],
                        $ldap_method['timeout'],
                        $ldap_method['tls_version'] ?? '',
                        true // silent bind error when trying to bind with user login/password
                    );
                }
                if ($ds) {
                    return $ds;
                }
            }
        }
        return $ds;
    }

    /**
     * Get LDAP servers
     *
     * @return array
     */
    public static function getLdapServers(bool $active_only = false)
    {
        $criteria = [
            'ORDER' => 'is_default DESC',
        ];
        if ($active_only) {
            $criteria['is_active'] = 1;
        }
        return getAllDataFromTable('glpi_authldaps', $criteria);
    }

    /**
     * Is the LDAP authentication used?
     *
     * @return boolean
     */
    public static function useAuthLdap()
    {
        return (countElementsInTable('glpi_authldaps', ['is_active' => 1]) > 0);
    }

    /**
     * Import a user from ldap
     * Check all the directories. When the user is found, then import it
     *
     * @param array $options array containing condition:
     *                 array('name'=>'glpi') or array('email' => 'test at test.com')
     *
     * @return array|boolean false if fail
     * @throws SodiumException
     */
    public static function importUserFromServers($options = [])
    {
        $auth   = new Auth();
        $params = [];
        if (isset($options['name'])) {
            $params['value']  = $options['name'];
            $params['method'] = self::IDENTIFIER_LOGIN;
        }
        if (isset($options['email'])) {
            $params['value']  = $options['email'];
            $params['method'] = self::IDENTIFIER_EMAIL;
        }

        // If the user does not exist
        if ($auth->userExists($options) === Auth::USER_DOESNT_EXIST) {
            $auth->user_present = true;
            $auth->getAuthMethods();
            $ldap_methods = $auth->authtypes["ldap"];

            foreach ($ldap_methods as $ldap_method) {
                if ($ldap_method['is_active']) {
                    //we're looking for a user login
                    $params['identifier_field']   = $ldap_method['login_field'];
                    $params['user_field']         = 'name';
                    $result = self::ldapImportUserByServerId($params, 0, $ldap_method["id"], true);
                    if ($result !== false) {
                        return $result;
                    }
                }
            }
            Session::addMessageAfterRedirect(__s('User not found or several users found'), false, ERROR);
        } else {
            Session::addMessageAfterRedirect(
                __s('Unable to add. The user already exist.'),
                false,
                ERROR
            );
        }
        return false;
    }

    /**
     * Authentify a user by checking a specific directory
     *
     * @param Auth      $auth        identification object
     * @param string    $login       user login
     * @param string    $password    user password
     * @param array     $ldap_method ldap_method array to use
     * @param bool      $user_dn     user LDAP DN if present (note: value is always ignored, TODO: investigate and cleanup)
     * @param bool      $error       Boolean flag that will be set to `true` if a LDAP error occurs during connection
     *
     * @return object identification object
     */
    public static function ldapAuth($auth, $login, $password, $ldap_method, $user_dn, bool &$error = false)
    {
        $auth->auth_succeded = false;
        $auth->extauth       = 1;

        $infos = $auth->connection_ldap($ldap_method, $login, $password, $error);

        if ($infos === false) {
            return $auth;
        }

        // Get another fresh connection using the root credentials.
        // This connection may permit to retrieve more information (especially groups info)
        // than a connection explicitely bound to the user DN.
        // See https://github.com/glpi-project/glpi/issues/17492.
        //
        // Use an empty login to only try a connection with configured root credentials.
        $root_ldap_connection = self::tryToConnectToServer($ldap_method, '', '');

        if ($root_ldap_connection === false) {
            return $auth;
        }

        $user_dn = $infos['dn'];
        $user_sync = ($infos['sync_field'] ?? null);

        if ($user_dn) {
            $auth->auth_succeded            = true;
            // try by login+auth_id and next by dn
            if (
                $auth->user->getFromDBbyNameAndAuth($login, Auth::LDAP, $ldap_method['id'])
                || $auth->user->getFromDBbyDn($user_dn)
            ) {
                //There's already an existing user in DB with the same DN but its login field has changed
                $auth->user->fields['name'] = $login;
                $auth->user_present         = true;
                $auth->user_dn              = $user_dn;
            } elseif ($user_sync !== null && $auth->user->getFromDBbySyncField($user_sync)) {
                //user login/dn have changed
                $auth->user->fields['name']      = $login;
                $auth->user->fields['user_dn']   = $user_dn;
                $auth->user_present              = true;
                $auth->user_dn                   = $user_dn;
            } else { // The user is a new user
                $auth->user_present = false;
            }
            $auth->user->getFromLDAP(
                $root_ldap_connection,
                $ldap_method,
                $user_dn,
                $login,
                !$auth->user_present
            );
            $auth->user->fields["authtype"] = Auth::LDAP;
            $auth->user->fields["auths_id"] = $ldap_method["id"];
        }
        return $auth;
    }

    /**
     * Try to authentify a user by checking all the directories
     *
     * @param Auth    $auth     identification object
     * @param string  $login    user login
     * @param string  $password user password
     * @param integer $auths_id auths_id already used for the user (default 0)
     * @param boolean $user_dn  user LDAP DN if present (false by default)
     * @param boolean $break    if user is not found in the first directory,
     *                          continue searching on the following ones (true by default)
     *
     * @return object identification object
     */
    public static function tryLdapAuth($auth, $login, $password, $auths_id = 0, $user_dn = false, $break = true)
    {
        global $DB;

        //If no specific source is given, test all ldap directories
        if ($auths_id <= 0) {
            $user_found = false;

            $ldap_methods = $auth->authtypes["ldap"];

            // Sort servers to first try on known servers for given login.
            // It is necessary to still necessary to try to connect on all servers to handle following cases:
            //  - there are multiple users having same login on different LDAP servers,
            //  - a user has been migrated from a LDAP server to another one, but GLPI is not yet aware of this.
            // Caveat: if user uses a wrong password, a login attempt will still be done on all active LDAP servers.
            $known_servers = $DB->request(
                [
                    'SELECT' => 'auths_id',
                    'FROM'   => User::getTable(),
                    'WHERE'  => ['name' => $login],
                ]
            );
            $known_servers_id = array_column(iterator_to_array($known_servers), 'auths_id');
            usort(
                $ldap_methods,
                static function (array $a, array $b) use ($known_servers_id) {
                    if (in_array($a['id'], $known_servers_id) && !in_array($b['id'], $known_servers_id)) {
                        return -1;
                    }
                    if (!in_array($a['id'], $known_servers_id) && in_array($b['id'], $known_servers_id)) {
                        return 1;
                    }
                    return $a['id'] <=> $b['id'];
                }
            );

            foreach ($ldap_methods as $ldap_method) {
                if ($ldap_method['is_active']) {
                    $error = false;
                    $auth = self::ldapAuth($auth, $login, $password, $ldap_method, $user_dn, $error);

                    if ($error === true && in_array($ldap_method['id'], $known_servers_id)) {
                        // Remember that an error occurs on the server on which we expect user to be find.
                        // This will prevent user to be considered as deleted from the LDAP server.
                        $auth->user_ldap_error = true;
                    }

                    if ($auth->user_found) {
                        $user_found = true;
                    }

                    if (
                        $auth->auth_succeded
                        && $break
                    ) {
                        break;
                    }
                }
            }

            $auth->user_found = $user_found;
        } elseif (array_key_exists($auths_id, $auth->authtypes["ldap"])) {
            // Check if the ldap server indicated as the last good one still exists !
            //A specific ldap directory is given, test it and only this one !
            $auth = self::ldapAuth(
                $auth,
                $login,
                $password,
                $auth->authtypes["ldap"][$auths_id],
                $user_dn
            );
        }
        return $auth;
    }

    /**
     * Get dn for a user
     *
     * @param Connection $ds LDAP link
     * @param array    $options array of possible options:
     *          - basedn : base dn used to search
     *          - login_field : attribute to store login
     *          - search_parameters array of search parameters
     *          - user_params  array of parameters : method (IDENTIFIER_LOGIN or IDENTIFIER_EMAIL) + value
     *          - condition : ldap condition used
     * @param bool|null $error  Boolean flag that will be set to `true` if a LDAP error occurs during operation
     *
     * @return array|boolean dn of the user, else false
     * @throws RuntimeException
     */
    public static function searchUserDn($ds, $options = [], ?bool &$error = null)
    {
        $values = [
            'basedn'            => '',
            'login_field'       => '',
            'search_parameters' => [],
            'user_params'       => '',
            'condition'         => '',
            'user_dn'           => false,
        ];

        foreach ($options as $key => $value) {
            $values[$key] = $value;
        }

        // By default authenticate users by login
        $login_attr      = $values['search_parameters']['fields'][self::IDENTIFIER_LOGIN];
        $sync_attr       = $values['search_parameters']['fields']['sync_field'] ?? null;

        $attrs = ["dn"];
        foreach ($values['search_parameters']['fields'] as $attr) {
            $attrs[] = $attr;
        }

        // First : if a user dn is provided, look for it in the directory
        // Before trying to find the user using his login_field
        if ($values['user_dn']) {
            $info = self::getUserByDn($ds, $values['user_dn'], $attrs, true, $error);

            if ($error === true) {
                return false;
            }

            if ($info) {
                $ret = [
                    'dn'        => $values['user_dn'],
                    $login_attr => $info[$login_attr][0],
                ];
                if ($sync_attr !== null && isset($info[0][$sync_attr])) {
                    $ret['sync_field'] = self::getFieldValue($info[0], $sync_attr);
                }
                return $ret;
            }
        }

        // Try a search to find the DN
        $filter_value = $values['user_params']['value'];
        if ($values['login_field'] === 'objectguid' && self::isValidGuid($filter_value)) {
            $filter_value = self::guidToHex($filter_value);
        } else {
            $filter_value = ldap_escape($filter_value, '', LDAP_ESCAPE_FILTER);
        }
        $filter = "(" . $values['login_field'] . "=" . $filter_value . ")";

        if (!empty($values['condition'])) {
            $filter = "(& $filter " . $values['condition'] . ")";
        }

        $result = @ldap_search($ds, $values['basedn'], $filter, $attrs);
        if ($result === false) {
            // 32 = LDAP_NO_SUCH_OBJECT => This error can be silented as it just means that search produces no result.
            if (ldap_errno($ds) !== 32) {
                $error = true;
                trigger_error(
                    static::buildError(
                        $ds,
                        sprintf('LDAP search with base DN `%s` and filter `%s` failed', $values['basedn'], $filter)
                    ),
                    E_USER_WARNING
                );
            }
            return false;
        }

        //search has been done, let's check for found results
        $info = self::get_entries_clean($ds, $result, $error);

        if ($error === true) {
            return false;
        }

        if (is_array($info) && ($info['count'] === 1)) {
            $ret = [
                'dn'        => $info[0]['dn'],
                $login_attr => $info[0][$login_attr][0],
            ];
            if ($sync_attr !== null && isset($info[0][$sync_attr])) {
                $ret['sync_field'] = self::getFieldValue($info[0], $sync_attr);
            }
            return $ret;
        }
        return false;
    }

    /**
     * Get an object from LDAP by giving his DN
     *
     * @param Connection $ds the active connection to the directory
     * @param string    $condition  the LDAP filter to use for the search
     * @param string    $dn         DN of the object
     * @param array     $attrs      Array of the attributes to retrieve
     * @param boolean   $clean      (true by default)
     * @param bool|null $error      Boolean flag that will be set to `true` if a LDAP error occurs during operation
     *
     * @return array|boolean false if failed
     */
    public static function getObjectByDn($ds, $condition, $dn, $attrs = [], $clean = true, ?bool &$error = null)
    {
        if (!$clean) {
            Toolbox::deprecated('Use of $clean = false is deprecated');
        }

        $result = @ldap_read($ds, $dn, $condition, $attrs);
        if ($result === false) {
            // 32 = LDAP_NO_SUCH_OBJECT => This error can be silented as it just means that search produces no result.
            if (ldap_errno($ds) !== 32) {
                $error = true;
                trigger_error(
                    static::buildError(
                        $ds,
                        sprintf('Unable to get LDAP object having DN `%s` with filter `%s`', $dn, $condition)
                    ),
                    E_USER_WARNING
                );
            }
            return false;
        }

        $info = self::get_entries_clean($ds, $result, $error);

        if ($error === true) {
            return false;
        }

        if (is_array($info) && ($info['count'] == 1)) {
            return $info[0];
        }

        return false;
    }

    /**
     * Get user by domain name
     *
     * @param Connection $ds the active connection to the directory
     * @param string    $user_dn    domain name
     * @param array     $attrs      attributes
     * @param boolean   $clean      (true by default)
     * @param bool|null $error      Boolean flag that will be set to `true` if a LDAP error occurs during operation
     *
     * @return array|boolean false if failed
     */
    public static function getUserByDn($ds, $user_dn, $attrs, $clean = true, ?bool &$error = null)
    {
        if (!$clean) {
            Toolbox::deprecated('Use of $clean = false is deprecated');
        }

        return self::getObjectByDn($ds, "objectClass=*", $user_dn, $attrs, $clean, $error);
    }

    /**
     * Get infos for groups
     *
     * @param Connection $ds LDAP link
     * @param string   $group_dn dn of the group
     *
     * @return array|boolean group infos if found, else false
     */
    public static function getGroupByDn($ds, $group_dn)
    {
        return self::getObjectByDn($ds, "objectClass=*", $group_dn, ["cn"]);
    }

    /**
     * Sets the default values for the LDAP import if not already set.
     * @param bool $is_users If true, the default values are set for user actions, otherwise for group actions.
     * @return void
     */
    public static function manageRequestValues(bool $is_users = true): void
    {
        if (!$is_users) {
            if (!isset($_REQUEST['authldaps_id']) || (int) $_REQUEST['authldaps_id'] <= 0) {
                // Use default from the current entity or global default
                $entity = new Entity();
                $entity->getFromDB($_SESSION['glpiactive_entity']);
                $_REQUEST['authldaps_id'] = $entity->getField('authldaps_id');
                if ((int) $_REQUEST['authldaps_id'] <= 0) {
                    $defaultAuth = Auth::getDefaultAuth();
                    if ($defaultAuth instanceof AuthLDAP) {
                        $_REQUEST['authldaps_id'] = $defaultAuth->getID();
                    }
                }
                // If there is still no LDAP selected, use the first active one
                $servers = array_values(self::getLdapServers(true));
                if (
                    $_REQUEST['authldaps_id'] <= 0
                    && count($servers) > 0
                ) {
                    $_REQUEST['authldaps_id'] = $servers[0]['id'];
                }
            }
            $_REQUEST['authldaps_id'] = (int) $_REQUEST['authldaps_id'];
            $_REQUEST['mode'] = self::ACTION_IMPORT;
            return;
        }
        //If form accessed via modal, do not show expert mode link
        // Manage new value is set : entity or mode
        if (isset($_REQUEST['entity']) || isset($_REQUEST['mode'])) {
            if (isset($_REQUEST['_in_modal']) && $_REQUEST['_in_modal']) {
                //If coming form the helpdesk form : reset all criterias
                $_REQUEST['no_expert_mode'] = 1;
                $_REQUEST['action'] = 'show';
                $_REQUEST['interface'] = self::SIMPLE_INTERFACE;
                $_REQUEST['mode'] = self::ACTION_IMPORT;
            } else {
                $_REQUEST['_in_modal'] = 0;
                $_REQUEST['no_expert_mode'] = 0;
            }
        }

        $_REQUEST['mode'] = (int) ($_REQUEST['mode'] ?? self::ACTION_IMPORT);

        $_REQUEST['entities_id'] ??= $_SESSION['glpiactive_entity'];
        if (isset($_REQUEST['toprocess'])) {
            $_REQUEST['action'] = 'process';
        }

        if (isset($_REQUEST['change_directory'])) {
            $_REQUEST['ldap_filter'] = '';
        }

        $_REQUEST['authldaps_id'] ??= 0;

        if (
            (!Config::canUpdate()
                && !Entity::canUpdate())
            || !isset($_REQUEST['interface'])
        ) {
            $_REQUEST['interface'] = self::SIMPLE_INTERFACE;
        }

        if (
            isset($_REQUEST['begin_date'])
            && ($_REQUEST['begin_date'] === 'NULL')
        ) {
            $_REQUEST['begin_date'] = '';
        }
        if (
            isset($_REQUEST['end_date'])
            && ($_REQUEST['end_date'] === 'NULL')
        ) {
            $_REQUEST['end_date'] = '';
        }
        $_REQUEST['criterias'] ??= [];

        $authldap = new self();
        //Filter computation
        if ($_REQUEST['interface'] === self::SIMPLE_INTERFACE) {
            $entity = new Entity();

            if (
                $entity->getFromDB($_REQUEST['entities_id'])
                && ($entity->getField('authldaps_id') > 0)
            ) {
                $authldap->getFromDB($_REQUEST['authldaps_id']);

                if ($_REQUEST['authldaps_id'] === 0) {
                    // authldaps_id wasn't submitted by the user -> take entity config
                    $_REQUEST['authldaps_id'] = $entity->getField('authldaps_id');
                }

                $_REQUEST['basedn']       = $entity->getField('ldap_dn');

                // No dn specified in entity : use standard one
                $_REQUEST['basedn'] ??= $authldap->getField('basedn');

                if ($entity->getField('entity_ldapfilter') !== 0) {
                    $_REQUEST['entity_filter'] = $entity->getField('entity_ldapfilter');
                }
            } else {
                if (
                    $_REQUEST['authldaps_id'] === 0
                    || !$_REQUEST['authldaps_id']
                ) {
                    $defaultAuth = Auth::getDefaultAuth();
                    if ($defaultAuth instanceof AuthLDAP) {
                        $_REQUEST['authldaps_id'] = $defaultAuth->getID();
                    } else {
                        $_REQUEST['authldaps_id'] = 0;
                    }
                }

                if ($_REQUEST['authldaps_id'] > 0) {
                    $authldap->getFromDB($_REQUEST['authldaps_id']);
                    $_REQUEST['basedn'] = $authldap->getField('basedn');
                }
            }

            if ($_REQUEST['authldaps_id'] > 0) {
                $_REQUEST['ldap_filter'] = self::buildLdapFilter($authldap);
            }
        } else {
            if (
                $_REQUEST['authldaps_id'] === 0
                || !$_REQUEST['authldaps_id']
            ) {
                $defaultAuth = Auth::getDefaultAuth();
                if ($defaultAuth instanceof AuthLDAP) {
                    $_REQUEST['authldaps_id'] = $defaultAuth->getID();

                    if ($_REQUEST['authldaps_id'] > 0) {
                        $authldap->getFromDB($_REQUEST['authldaps_id']);
                        $_REQUEST['basedn'] = $authldap->getField('basedn');
                    }
                }
            }
            if (
                !isset($_REQUEST['ldap_filter'])
                || $_REQUEST['ldap_filter'] === ''
            ) {
                $authldap->getFromDB($_REQUEST['authldaps_id']);
                $_REQUEST['basedn']      = $authldap->getField('basedn');
                $_REQUEST['ldap_filter'] = self::buildLdapFilter($authldap);
            }
        }

        // If there is still no LDAP selected, use the first active one
        $servers = array_values(self::getLdapServers(true));
        if (
            $_REQUEST['authldaps_id'] <= 0
            && count($servers) > 0
        ) {
            $_REQUEST['authldaps_id'] = $servers[0]['id'];
            $authldap->getFromDB($_REQUEST['authldaps_id']);
            $_REQUEST['basedn']      = $authldap->getField('basedn');
            if (($_REQUEST['ldap_filter'] ?? '') === '') {
                $_REQUEST['ldap_filter'] = self::buildLdapFilter($authldap);
            }
        }
    }

    /**
     * Show import user form
     *
     * @param AuthLDAP $authldap AuthLDAP object
     *
     * @return void
     */
    public static function showUserImportForm(AuthLDAP $authldap)
    {
        // Get data related to entity (directory and ldap filter)
        $authldap->getFromDB($_REQUEST['authldaps_id']);
        TemplateRenderer::getInstance()->display('pages/admin/ldap.user_criteria.html.twig', [
            'has_multiple_servers' => self::getNumberOfServers() > 1,
            'authldap'             => $authldap,
            'can_use_expert_interface' => (Config::canUpdate() || Entity::canUpdate())
                && (!isset($_REQUEST['no_expert_mode']) || (int) $_REQUEST['no_expert_mode'] !== 1),
        ]);
    }

    /**
     * Show import group form
     *
     * @param AuthLDAP $authldap AuthLDAP object
     *
     * @return void
     */
    public static function showGroupImportForm(AuthLDAP $authldap)
    {
        // Get data related to entity (directory and ldap filter)
        $authldap->getFromDB($_REQUEST['authldaps_id']);

        TemplateRenderer::getInstance()->display('pages/admin/ldap.group_criteria.html.twig', [
            'has_multiple_servers' => self::getNumberOfServers() > 1,
            'authldap'             => $authldap,
        ]);
    }

    /**
     * Get number of active servers
     *
     * @return integer
     */
    public static function getNumberOfServers()
    {
        return countElementsInTable('glpi_authldaps', ['is_active' => 1]);
    }

    /**
     * Build LDAP filter
     *
     * @param AuthLDAP $authldap AuthLDAP object
     *
     * @return string
     */
    public static function buildLdapFilter(AuthLDAP $authldap)
    {
        // Build search filter
        $filter  = '';

        if (
            !empty($_REQUEST['criterias'])
            && ($_REQUEST['interface'] === self::SIMPLE_INTERFACE)
        ) {
            foreach ($_REQUEST['criterias'] as $criteria => $value) {
                if ($value !== '') {
                    $begin = 0;
                    $end   = 0;
                    if (($length = strlen($value)) > 0) {
                        if ($value[0] === '^') {
                            $begin = 1;
                        }
                        if ($value[$length - 1] === '$') {
                            $end = 1;
                        }
                    }
                    if ($begin || $end) {
                        // no Toolbox::substr, to be consistent with strlen result
                        $value = substr($value, $begin, $length - $end - $begin);
                    }
                    $filter .= '(' . $authldap->fields[$criteria] . '=' . ($begin ? '' : '*') . $value . ($end ? '' : '*') . ')';
                }
            }
        } else {
            $filter = "(" . $authldap->getField("login_field") . "=*)";
        }

        // If time restriction
        $begin_date = $_REQUEST['begin_date'] ?? null;
        $end_date   = $_REQUEST['end_date'] ?? null;
        $filter    .= self::addTimestampRestrictions($begin_date, $end_date);
        $ldap_condition = $authldap->getField('condition');
        // Add entity filter and filter filled in directory's configuration form
        return  "(&" . ($_REQUEST['entity_filter'] ?? '') . " $filter $ldap_condition)";
    }

    /**
     * Add timestamp restriction
     *
     * @param string $begin_date datetime begin date to search (NULL if not take into account)
     * @param string $end_date   datetime end date to search (NULL if not take into account)
     *
     * @return string
     */
    public static function addTimestampRestrictions($begin_date, $end_date)
    {
        $condition = '';
        // If begin date
        if (!empty($begin_date)) {
            $stampvalue = self::date2ldapTimeStamp($begin_date);
            $condition .= "(modifyTimestamp>=" . $stampvalue . ")";
        }
        // If end date
        if (!empty($end_date)) {
            $stampvalue = self::date2ldapTimeStamp($end_date);
            $condition .= "(modifyTimestamp<=" . $stampvalue . ")";
        }
        return $condition;
    }

    /**
     * Search user
     *
     * @param AuthLDAP $authldap AuthLDAP object
     *
     * @return void
     * @throws SodiumException
     */
    public static function searchUser(AuthLDAP $authldap)
    {
        if (
            self::connectToServer(
                $authldap->fields['host'],
                $authldap->fields['port'],
                $authldap->fields['rootdn'],
                (new GLPIKey())->decrypt($authldap->fields['rootdn_passwd']),
                $authldap->fields['use_tls'],
                $authldap->fields['deref_option'],
                $authldap->fields['tls_certfile'],
                $authldap->fields['tls_keyfile'],
                $authldap->fields['use_bind'],
                $authldap->fields['timeout'],
                $authldap->fields['tls_version']
            )
        ) {
            self::showLdapUsers();
        } else {
            echo "<div class='text-center fw-bold mb-3'>" . __s('Unable to connect to the LDAP directory') . "</div>";
        }
    }

    public function post_updateItem($history = true)
    {
        if ($this->fields["is_default"]) {
            $this->removeDefaultFromOtherItems();
        }

        parent::post_updateItem($history);
    }

    /**
     * @return void
     */
    public function post_addItem()
    {
        if ($this->fields["is_default"]) {
            $this->removeDefaultFromOtherItems();
        }

        parent::post_addItem();
    }

    public function prepareInputForAdd($input)
    {
        if (empty($input['can_support_pagesize'] ?? '')) {
            $input['can_support_pagesize'] = 0;
        }

        if (!empty($input["rootdn_passwd"])) {
            $input["rootdn_passwd"] = (new GLPIKey())->encrypt($input["rootdn_passwd"]);
        }

        $this->checkFilesExist($input);

        return $input;
    }

    /**
     * Get LDAP deleted user action options regarding the deleted user
     *
     * @return array
     */
    public static function getLdapDeletedUserActionOptions_User(): array
    {
        return [
            self::DELETED_USER_ACTION_USER_DO_NOTHING       => __('Do nothing'),
            self::DELETED_USER_ACTION_USER_DISABLE          => __('Disable'),
            self::DELETED_USER_ACTION_USER_MOVE_TO_TRASHBIN => __('Move to trashbin'),
        ];
    }

    /**
     * Get LDAP deleted user action options regarding the deleted user's groups
     *
     * @return array
     */
    public static function getLdapDeletedUserActionOptions_Groups(): array
    {
        return [
            self::DELETED_USER_ACTION_GROUPS_DO_NOTHING     => __('Do nothing'),
            self::DELETED_USER_ACTION_GROUPS_DELETE_DYNAMIC => __('Delete dynamic groups'),
            self::DELETED_USER_ACTION_GROUPS_DELETE_ALL     => __('Delete all groups'),
        ];
    }

    /**
     * Get LDAP deleted user action options regarding the deleted user's authorizations
     *
     * @return array
     */
    public static function getLdapDeletedUserActionOptions_Authorizations(): array
    {
        return [
            self::DELETED_USER_ACTION_AUTHORIZATIONS_DO_NOTHING     => __('Do nothing'),
            self::DELETED_USER_ACTION_AUTHORIZATIONS_DELETE_DYNAMIC => __('Delete dynamic authorizations'),
            self::DELETED_USER_ACTION_AUTHORIZATIONS_DELETE_ALL     => __('Delete all authorizations'),
        ];
    }

    /**
     * Get LDAP restored user action options.
     *
     * @since 10.0.0
     * @return array
     */
    public static function getLdapRestoredUserActionOptions()
    {
        return [
            self::RESTORED_USER_PRESERVE  => __('Do nothing'),
            self::RESTORED_USER_RESTORE   => __('Restore (move out of trashbin)'),
            self::RESTORED_USER_ENABLE    => __('Enable'),
        ];
    }

    /**
     * Return all the ldap servers where email field is configured
     *
     * @return array of LDAP server's ID
     */
    public static function getServersWithImportByEmailActive()
    {
        global $DB;

        $ldaps = [];
        // Always get default first

        $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM'  => 'glpi_authldaps',
            'WHERE' => [
                'is_active' => 1,
                'OR'        => [
                    'email1_field' => ['<>', ''],
                    'email2_field' => ['<>', ''],
                    'email3_field' => ['<>', ''],
                    'email4_field' => ['<>', ''],
                ],
            ],
            'ORDER'  => ['is_default DESC'],
        ]);
        foreach ($iterator as $data) {
            $ldaps[] = $data['id'];
        }
        return $ldaps;
    }

    public function cleanDBonPurge()
    {
        Rule::cleanForItemCriteria($this, 'LDAP_SERVER');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        /** @var CommonDBTM $item */
        if (
            !$withtemplate
            && $item->can($item->getField('id'), READ)
        ) {
            $ong     = [];
            $ong[1]  = self::createTabEntry(_x('button', 'Test'), 0, $item::class, "ti ti-stethoscope"); // test connexion
            $ong[2]  = self::createTabEntry(User::getTypeName(Session::getPluralNumber()), 0, $item::class, User::getIcon());
            $ong[3]  = self::createTabEntry(Group::getTypeName(Session::getPluralNumber()), 0, $item::class, User::getIcon());
            $ong[5]  = self::createTabEntry(__('Advanced information'));   // params for entity advanced config
            $ong[6]  = self::createTabEntry(_n('Replicate', 'Replicates', Session::getPluralNumber()));

            return $ong;
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        /** @var AuthLDAP $item */
        switch ($tabnum) {
            case 1:
                $item->showFormTestLDAP();
                break;
            case 2:
                $item->showFormUserConfig();
                break;
            case 3:
                $item->showFormGroupsConfig();
                break;
            case 5:
                $item->showFormAdvancedConfig();
                break;
            case 6:
                $item->showFormReplicatesConfig();
                break;
        }
        return true;
    }

    /**
     * Get ldap query results and clean them at the same time
     *
     * @param Connection $link link to the directory connection
     * @param Result     $result the query results
     * @param bool|null $error  Boolean flag that will be set to `true` if a LDAP error occurs during operation
     *
     * @return array which contains ldap query results
     */
    public static function get_entries_clean($link, $result, ?bool &$error = null)
    {
        try {
            $entries = @ldap_get_entries($link, $result);
            return $entries;
        } catch (LdapException $e) {
            $error = true;
            trigger_error(
                static::buildError(
                    $link,
                    'Error while getting LDAP entries'
                ),
                E_USER_WARNING
            );
        }
        return [];
    }

    /**
     * Get all replicate servers for a master one
     *
     * @param integer $master_id master ldap server ID
     *
     * @return array of the replicate servers
     */
    public static function getAllReplicateForAMaster($master_id)
    {
        global $DB;

        $replicates = [];
        $criteria = [
            'SELECT' => ['id', 'host', 'port'],
            'FROM'   => 'glpi_authldapreplicates',
            'WHERE'  => ['authldaps_id' => $master_id],
        ];
        foreach ($DB->request($criteria) as $replicate) {
            $replicates[] = [
                "id"   => $replicate["id"],
                "host" => $replicate["host"],
                "port" => $replicate["port"],
            ];
        }
        return $replicates;
    }

    /**
     * Check if ldap results can be paged or not
     * This functionality is available for PHP 5.4 and higher
     *
     * @since 0.84
     *
     * @param ($check_config_value is true ? object : false)   $config_ldap        LDAP configuration. May only be false if $check_config_value is also false.
     * @param boolean  $check_config_value Whether to check config values
     *
     * @return boolean true if maxPageSize can be used, false otherwise
     */
    public static function isLdapPageSizeAvailable($config_ldap, $check_config_value = true)
    {
        return (
            extension_loaded('ldap')
            && (
                !$check_config_value
                || $config_ldap->fields['can_support_pagesize']
            )
        );
    }

    /**
     * Does LDAP user already exists in the database?
     *
     * @param string  $name          User login/name
     * @param integer $authldaps_id  LDAP authentication server ID
     * @param ?string $sync          Sync field
     *
     * @return false|User
     */
    public function getLdapExistingUser($name, $authldaps_id, $sync = null)
    {
        global $DB;
        $user = new User();

        if ($sync !== null && $user->getFromDBbySyncField($DB->escape($sync))) {
            return $user;
        }

        if ($user->getFromDBbyNameAndAuth($name, Auth::LDAP, $authldaps_id)) {
            return $user;
        }

        return false;
    }

    /**
     * Is synchronisation field used for current server
     *
     * @return boolean
     */
    public function isSyncFieldUsed()
    {
        if ($this->getID() <= 0) {
            return false;
        }
        $count = countElementsInTable(
            'glpi_users',
            [
                'auths_id'  => $this->getID(),
                'NOT'       => ['sync_field' => null],
            ]
        );
        return $count > 0;
    }

    /**
     * Get a LDAP field value
     *
     * @param array  $infos LDAP entry infos
     * @param string $field Field name to retrieve
     *
     * @return string
     */
    public static function getFieldValue($infos, $field)
    {
        $value = null;
        if (array_key_exists($field, $infos)) {
            if (is_array($infos[$field])) {
                $value = $infos[$field][0];
            } else {
                $value = $infos[$field];
            }
        }
        if ($field !== 'objectguid') {
            return $value;
        }

        // handle special objectguid from AD directories
        try {
            // prevent double encoding
            if (!self::isValidGuid($value)) {
                $value = self::guidToString($value);
                if (!self::isValidGuid($value)) {
                    throw new RuntimeException('Not an objectguid!');
                }
            }
        } catch (Throwable $e) {
            // well... this is not an objectguid apparently
            $value = $infos[$field];
        }

        return $value;
    }

    /**
     * Converts a string representation of an objectguid to hexadecimal
     * Used to build filters
     *
     * @param string $guid_str String representation
     *
     * @return string
     */
    public static function guidToHex($guid_str)
    {
        $str_g = explode('-', $guid_str);

        $str_g[0] = strrev($str_g[0]);
        $str_g[1] = strrev($str_g[1]);
        $str_g[2] = strrev($str_g[2]);

        $guid_hex = '\\';
        $strrev = 0;
        foreach ($str_g as $str) {
            for ($i = 0; $i < strlen($str) + 2; $i++) {
                if ($strrev < 3) {
                    $guid_hex .= strrev(substr($str, 0, 2)) . '\\';
                } else {
                    $guid_hex .= substr($str, 0, 2) . '\\';
                }
                $str = substr($str, 2);
            }
            if ($strrev < 3) {
                $guid_hex .= strrev($str);
            } else {
                $guid_hex .= $str;
            }
            $strrev++;
        }
        return $guid_hex;
    }

    /**
     * Converts binary objectguid to string representation
     *
     * @param mixed $guid_bin Binary objectguid from AD
     *
     * @return string
     */
    public static function guidToString($guid_bin)
    {
        $guid_hex = unpack("H*hex", $guid_bin);
        $hex = $guid_hex["hex"];

        $hex1 = substr($hex, -26, 2) . substr($hex, -28, 2) . substr($hex, -30, 2) . substr($hex, -32, 2);
        $hex2 = substr($hex, -22, 2) . substr($hex, -24, 2);
        $hex3 = substr($hex, -18, 2) . substr($hex, -20, 2);
        $hex4 = substr($hex, -16, 4);
        $hex5 = substr($hex, -12, 12);

        $guid_str = $hex1 . "-" . $hex2 . "-" . $hex3 . "-" . $hex4 . "-" . $hex5;
        return $guid_str;
    }

    /**
     * Check if text representation of an objectguid is valid
     *
     * @param string $guid_str String representation
     *
     * @return boolean
     */
    public static function isValidGuid($guid_str)
    {
        return (bool) preg_match('/^([0-9a-fA-F]){8}(-([0-9a-fA-F]){4}){3}-([0-9a-fA-F]){12}$/', $guid_str);
    }

    /**
     * Get the list of LDAP users to add/synchronize
     * When importing, already existing users will be filtered
     *
     * @param array   $values        possible options:
     *          - authldaps_id ID of the server to use
     *          - mode user to synchronise or add?
     *          - ldap_filter ldap filter to use
     *          - basedn force basedn (default authldaps_id one)
     *          - order display order
     *          - begin_date begin date to time limit
     *          - end_date end date to time limit
     *          - script true if called by an external script
     * @param array   $results       result stats
     * @param boolean $limitexceeded limit exceeded exception
     *
     * @return array
     */
    public static function getUsers($values, &$results, &$limitexceeded)
    {
        $users = [];
        $ldap_users    = self::getAllUsers($values, $results, $limitexceeded);

        $config_ldap   = new AuthLDAP();
        $config_ldap->getFromDB($values['authldaps_id']);

        if (!is_array($ldap_users) || count($ldap_users) === 0) {
            return $users;
        }


        $sync_field = $config_ldap->isSyncFieldEnabled() ? $config_ldap->fields['sync_field'] : null;

        foreach ($ldap_users as $userinfos) {
            $user_to_add = [];
            $user = new User();

            $user_sync_field = $config_ldap->isSyncFieldEnabled() && isset($userinfos[$sync_field])
                ? self::getFieldValue($userinfos, $sync_field)
                : null;

            $user = $config_ldap->getLdapExistingUser(
                $userinfos['user'],
                $values['authldaps_id'],
                $user_sync_field
            );
            if ($values['mode'] === self::ACTION_IMPORT && $user) {
                // Do not display existing users on import mode
                continue;
            }
            $user_to_add['link'] = $userinfos["user"];
            if (isset($userinfos['id']) && User::canView()) {
                $user_to_add['id']   = $userinfos['id'];
                $user_to_add['name'] = $user->fields['name'];
                $user_to_add['link'] = Toolbox::getItemTypeFormURL('User') . '?id=' . $userinfos['id'];
            }

            $user_to_add['stamp']      = $userinfos["timestamp"] ?? '';
            $user_to_add['date_sync']  = $userinfos["date_sync"] ?? '';

            $user_to_add['uid'] = $userinfos['user'];
            if ($config_ldap->isSyncFieldEnabled()) {
                if (isset($userinfos[$sync_field])) {
                    $user_to_add['uid'] = self::getFieldValue($userinfos, $sync_field);
                }

                $field_for_sync = $config_ldap->getLdapIdentifierToUse();
                if (isset($userinfos[$field_for_sync])) {
                    $user_to_add['sync_field'] = $userinfos[$field_for_sync];
                }
            }

            $users[] = $user_to_add;
        }

        return $users;
    }

    public function checkFilesExist(&$input)
    {
        if (
            isset($input['tls_certfile'])
            && $input['tls_certfile'] !== ''
            && (!Filesystem::isFilepathSafe($input['tls_certfile']) || !file_exists($input['tls_certfile']))
        ) {
            Session::addMessageAfterRedirect(
                __s('TLS certificate path is incorrect'),
                false,
                ERROR
            );
            return false;
        }

        if (
            isset($input['tls_keyfile'])
            && $input['tls_keyfile'] !== ''
            && (!Filesystem::isFilepathSafe($input['tls_keyfile']) || !file_exists($input['tls_keyfile']))
        ) {
            Session::addMessageAfterRedirect(
                __s('TLS key file path is incorrect'),
                false,
                ERROR
            );
            return false;
        }

        return true;
    }

    public static function getIcon()
    {
        return "ti ti-address-book";
    }

    /**
     * Get date (Y-m-d H:i:s) from ldap value
     *
     * @param string $date Value found in LDAP
     *
     * @return string
     */
    public static function getLdapDateValue(string $date): string
    {
        // See https://www.epochconverter.com/ldap

        if (strlen($date) === 18 && (int) $date > 0) {
            // 18-digit LDAP/FILETIME timestamps
            // $date is the number of "100-nanoseconds-intervals" since 01/01/1601
            // Divide by 10M to convert to seconds (10M nano seconds = 0.01 seconds = 1 "100-nanoseconds-intervals")
            // Then substract 11644473600  (number of seconds between 01/01/1601
            // and 01/01/1970) to get an unix timestamp
            // See https://learn.microsoft.com/en-us/windows/win32/sysinfo/converting-a-time-t-value-to-a-file-time?redirectedfrom=MSDN
            $time = intval($date) / (10000000) - 11644473600;
            return $time > 0 ? date('Y-m-d H:i:s', $time) : '';
        } elseif (preg_match('/^(\d{14})\.0Z$/', $date, $matches)) {
            // Ymdhis.0Z LDAP timestamps
            $date = DateTime::createFromFormat('YmdHis', $matches[1]);
            return $date ? $date->format('Y-m-d H:i:s') : '';
        } else {
            return '';
        }
    }

    final public static function buildError($ds, string $message): string
    {
        $diag_message = '';
        $err_message  = '';
        $message = sprintf(
            "%s\nerror: %s (%s)%s%s",
            $message,
            ldap_error($ds),
            ldap_errno($ds),
            (ldap_get_option($ds, LDAP_OPT_DIAGNOSTIC_MESSAGE, $diag_message) ? "\nextended error: " . $diag_message : ''), // @phpstan-ignore theCodingMachineSafe.function
            (ldap_get_option($ds, LDAP_OPT_ERROR_STRING, $err_message) ? "\nerr string: " . $err_message : '') // @phpstan-ignore theCodingMachineSafe.function
        );
        return $message;
    }

    /**
     * Use an LDAP connection string
     *
     * @param string $host
     * @param int $port
     *
     * @return string
     */
    final public static function buildUri(string $host, int $port): string
    {
        return sprintf(
            '%s://%s:%s',
            strtolower(parse_url($host, PHP_URL_SCHEME) ?: 'ldap'),
            preg_replace('@^ldaps?://@i', '', $host),
            $port
        );
    }

    /**
     * Remove the `is_default` flag from authentication methods that does not match the current item.
     */
    private function removeDefaultFromOtherItems(): void
    {
        if ($this->fields["is_default"]) {
            $auth = new self();
            $defaults = $auth->find(['is_default' => 1, ['NOT' => ['id' => $this->getID()]]]);
            foreach ($defaults as $default) {
                $auth = new self();
                $auth->update([
                    'id' => $default['id'],
                    'is_default' => 0,
                ]);
            }

            $auth = new AuthMail();
            $defaults = $auth->find(['is_default' => 1]);
            foreach ($defaults as $default) {
                $auth = new AuthMail();
                $auth->update([
                    'id' => $default['id'],
                    'is_default' => 0,
                ]);
            }
        }
    }
}
