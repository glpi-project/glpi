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

use Glpi\Event;
use Glpi\Plugin\Hooks;
use Glpi\Toolbox\Sanitizer;

/**
 * Entity class
 */
class Entity extends CommonTreeDropdown
{
    use Glpi\Features\Clonable;
    use MapGeolocation;

    public $must_be_replace              = true;
    public $dohistory                    = true;

    public $first_level_menu             = "admin";
    public $second_level_menu            = "entity";

    public static $rightname                    = 'entity';
    protected $usenotepad                = true;

    const READHELPDESK                   = 1024;
    const UPDATEHELPDESK                 = 2048;

    const CONFIG_AUTO                    = -1;
    const CONFIG_PARENT                  = -2;
    const CONFIG_NEVER                   = -10;

    const AUTO_ASSIGN_HARDWARE_CATEGORY  = 1;
    const AUTO_ASSIGN_CATEGORY_HARDWARE  = 2;

    /**
     * Possible values for "anonymize_support_agents" setting
     */
    const ANONYMIZE_DISABLED            = 0;
    const ANONYMIZE_USE_GENERIC         = 1;
    const ANONYMIZE_USE_NICKNAME        = 2;
    const ANONYMIZE_USE_GENERIC_USER    = 3;
    const ANONYMIZE_USE_NICKNAME_USER   = 4;
    const ANONYMIZE_USE_GENERIC_GROUP   = 5;

   // Array of "right required to update" => array of fields allowed
   // Missing field here couldn't be update (no right)
    private static $field_right = [
        'entity' => [
         // Address
            'address', 'country', 'email', 'fax', 'notepad',
            'longitude','latitude','altitude',
            'phonenumber', 'postcode', 'state', 'town',
            'website', 'registration_number',
         // Advanced (could be user_authtype ?)
            'authldaps_id', 'entity_ldapfilter', 'ldap_dn',
            'mail_domain', 'tag',
         // Inventory
            'entities_strategy_software', 'entities_id_software', 'level', 'name',
            'completename', 'entities_id',
            'ancestors_cache', 'sons_cache', 'comment', 'transfers_strategy', 'transfers_id',
            'agent_base_url'
        ],
      // Inventory
        'infocom' => [
            'autofill_buy_date', 'autofill_delivery_date',
            'autofill_order_date', 'autofill_use_date',
            'autofill_warranty_date',
            'autofill_decommission_date'
        ],
      // Notification
        'notification' => [
            'admin_email', 'replyto_email', 'from_email',
            'admin_email_name', 'replyto_email_name', 'from_email_name',
            'noreply_email_name','noreply_email',
            'delay_send_emails',
            'is_notif_enable_default',
            'default_cartridges_alarm_threshold',
            'default_consumables_alarm_threshold',
            'default_contract_alert', 'default_infocom_alert',
            'mailing_signature', 'cartridges_alert_repeat',
            'consumables_alert_repeat', 'notclosed_delay',
            'use_licenses_alert', 'use_certificates_alert',
            'send_licenses_alert_before_delay',
            'send_certificates_alert_before_delay',
            'certificates_alert_repeat_interval',
            'use_contracts_alert',
            'send_contracts_alert_before_delay',
            'use_reservations_alert', 'use_infocoms_alert',
            'send_infocoms_alert_before_delay',
            'notification_subject_tag', 'use_domains_alert',
            'send_domains_alert_close_expiries_delay', 'send_domains_alert_expired_delay'
        ],
      // Helpdesk
        'entity_helpdesk' => [
            'calendars_strategy', 'calendars_id', 'tickettype', 'auto_assign_mode',
            'autoclose_delay', 'inquest_config',
            'inquest_rate', 'inquest_delay',
            'inquest_duration','inquest_URL',
            'max_closedate', 'tickettemplates_strategy', 'tickettemplates_id',
            'changetemplates_strategy', 'changetemplates_id', 'problemtemplates_strategy', 'problemtemplates_id',
            'suppliers_as_private', 'autopurge_delay', 'anonymize_support_agents', 'display_users_initials',
            'contracts_strategy_default', 'contracts_id_default'
        ],
      // Configuration
        'config' => ['enable_custom_css', 'custom_css_code']
    ];


    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'delete';
        $forbidden[] = 'purge';
        $forbidden[] = 'restore';
        $forbidden[] = 'CommonDropdown' . MassiveAction::CLASS_ACTION_SEPARATOR . 'merge';
        return $forbidden;
    }

    public function getCloneRelations(): array
    {
        return [
        ];
    }

    public function pre_updateInDB()
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (($key = array_search('name', $this->updates)) !== false) {
            /// Check if entity does not exist
            $iterator = $DB->request([
                'FROM' => $this->getTable(),
                'WHERE' => [
                    'name' => $this->input['name'],
                    'entities_id' => $this->input['entities_id'],
                    'id' => ['<>', $this->input['id']]
                ]
            ]);

            if (count($iterator)) {
                //To display a message
                $this->fields['name'] = $this->oldvalues['name'];
                unset($this->updates[$key]);
                unset($this->oldvalues['name']);
                Session::addMessageAfterRedirect(
                    __('An entity with that name already exists at the same level.'),
                    false,
                    ERROR
                );
            }
        }
    }

    public function pre_deleteItem()
    {
        /** @var \Psr\SimpleCache\CacheInterface $GLPI_CACHE */
        global $GLPI_CACHE;

        // Security do not delete root entity
        if ($this->input['id'] == 0) {
            return false;
        }

        // Security do not delete entity with children
        if (countElementsInTable($this->getTable(), ['entities_id' => $this->input['id']])) {
            Session::addMessageAfterRedirect(
                __('You cannot delete an entity which contains sub-entities.'),
                false,
                ERROR
            );
            return false;
        }

        //Cleaning sons calls getAncestorsOf and thus... Re-create cache. Call it before clean.
        $this->cleanParentsSons();
        $ckey = 'ancestors_cache_' . $this->getTable() . '_' . $this->getID();
        $GLPI_CACHE->delete($ckey);

        return true;
    }


    public static function getTypeName($nb = 0)
    {
        return _n('Entity', 'Entities', $nb);
    }

    public static function canCreate()
    {
        // Do not show the create button if no recusive access on current entity
        return parent::canCreate() && Session::haveRecursiveAccessToEntity(Session::getActiveEntity());
    }

    public function canCreateItem()
    {
       // Check the parent
        return Session::haveRecursiveAccessToEntity($this->getField('entities_id'));
    }


    /**
     * @since 0.84
     **/
    public static function canUpdate()
    {

        return (Session::haveRightsOr(self::$rightname, [UPDATE, self::UPDATEHELPDESK])
              || Session::haveRight('notification', UPDATE));
    }


    public function canUpdateItem()
    {
       // Check the current entity
        return Session::haveAccessToEntity($this->getField('id'));
    }


    public function canViewItem()
    {
       // Check the current entity
        return Session::haveAccessToEntity($this->getField('id'));
    }


    public static function isNewID($ID)
    {
        return (($ID < 0) || !strlen($ID));
    }

    /**
     * Can object have a location
     *
     * @since 9.3
     *
     * @return boolean
     */
    public function maybeLocated()
    {
        return true;
    }

    /**
     * Check right on each field before add / update
     *
     * @since 0.84 (before in entitydata.class)
     *
     * @param $input array (form)
     *
     * @return array (filtered input)
     **/
    private function checkRightDatas($input)
    {

        $tmp = [];

        if (isset($input['id'])) {
            $tmp['id'] = $input['id'];
        }

        foreach (self::$field_right as $right => $fields) {
            if ($right == 'entity_helpdesk') {
                if (Session::haveRight(self::$rightname, self::UPDATEHELPDESK)) {
                    foreach ($fields as $field) {
                        if (isset($input[$field])) {
                             $tmp[$field] = $input[$field];
                        }
                    }
                }
            } else {
                if (Session::haveRight($right, UPDATE)) {
                    foreach ($fields as $field) {
                        if (isset($input[$field])) {
                            $tmp[$field] = $input[$field];
                        }
                    }
                }
            }
        }
       // Add framework  / internal ones
        foreach ($input as $key => $val) {
            if ($key[0] == '_') {
                $tmp[$key] = $input[$key];
            }
        }

        return $tmp;
    }


    /**
     * @since 0.84 (before in entitydata.class)
     **/
    public function prepareInputForAdd($input)
    {
        /** @var \DBmysql $DB */
        global $DB;
        $input['name'] = isset($input['name']) ? trim($input['name']) : '';
        if (empty($input["name"])) {
            Session::addMessageAfterRedirect(
                __("You can't add an entity without name"),
                false,
                ERROR
            );
            return false;
        }

        $input = parent::prepareInputForAdd($input);
        if ($input === false) {
            return false;
        }

        $input = $this->handleConfigStrategyFields($input);

        $result = $DB->request([
            'SELECT' => new \QueryExpression(
                'MAX(' . $DB->quoteName('id') . ')+1 AS newID'
            ),
            'FROM'   => $this->getTable()
        ])->current();
        $input['id'] = $result['newID'];

        $input['max_closedate'] = $_SESSION["glpi_currenttime"];

        if (
            empty($input['latitude']) && empty($input['longitude']) && empty($input['altitude']) &&
            !empty($input[static::getForeignKeyField()])
        ) {
            $parent = new static();
            $parent->getFromDB($input[static::getForeignKeyField()]);
            $input['latitude'] = $parent->fields['latitude'];
            $input['longitude'] = $parent->fields['longitude'];
            $input['altitude'] = $parent->fields['altitude'];
        }

        if (!array_key_exists('custom_css_code', $input) || $input['custom_css_code'] === null) {
            // The `custom_css_code` field is a textfield and therefore has no default value.
            // The `Entity::getUsedConfig()` does not correctly handle the `null` value found in the root entity.
            // See https://github.com/glpi-project/glpi/pull/17648
            $input['custom_css_code'] = '';
        }

        if (!Session::isCron()) { // Filter input for connected
            $input = $this->checkRightDatas($input);
        }
        return $input;
    }


    /**
     * @since 0.84 (before in entitydata.class)
     **/
    public function prepareInputForUpdate($input)
    {
        // Force entities_id = NULL for root entity
        if ($input['id'] == 0) {
            $input['entities_id'] = null;
            $input['level']       = 1;
        }

        $input = parent::prepareInputForUpdate($input);
        if ($input === false) {
            return false;
        }

        $input = $this->handleConfigStrategyFields($input);

       // Si on change le taux de déclenchement de l'enquête (enquête activée) ou le type de l'enquete,
       // cela s'applique aux prochains tickets - Pas à l'historique
        if (
            (isset($input['inquest_rate'])
            && (($this->fields['inquest_rate'] == 0)
               || is_null($this->fields['max_closedate']))
            && ($input['inquest_rate'] != $this->fields['inquest_rate']))
            || (isset($input['inquest_config'])
              && (($this->fields['inquest_config'] == self::CONFIG_PARENT)
                  || is_null($this->fields['max_closedate']))
              && ($input['inquest_config'] != $this->fields['inquest_config']))
        ) {
            $input['max_closedate'] = $_SESSION["glpi_currenttime"];
        }

        if (array_key_exists('custom_css_code', $input) && $input['custom_css_code'] === null) {
            // The `custom_css_code` field is a textfield and therefore has no default value.
            // The `Entity::getUsedConfig()` does not correctly handle the `null` value found in the root entity.
            // See https://github.com/glpi-project/glpi/pull/17648
            $input['custom_css_code'] = '';
        }

        if (!Session::isCron()) { // Filter input for connected
            $input = $this->checkRightDatas($input);
        }

        return $input;
    }

    /**
     * Handle foreign key config fields splitting between "id" and "strategy" fields.
     *
     * @param array $input
     *
     * @return array
     */
    private function handleConfigStrategyFields(array $input): array
    {
        /** @var \DBmysql $DB */
        global $DB;

        foreach ($input as $field => $value) {
            $strategy_field = str_replace('_id', '_strategy', $field);
            if (preg_match('/_id(_.+)?/', $field) === 1 && $DB->fieldExists($this->getTable(), $strategy_field)) {
                if ($value > 0 || ($value == 0 && preg_match('/^entities_id(_\w+)?/', $field) === 1)) {
                    // Value contains a valid id -> set strategy to 0 (prevent inheritance).
                    $input[$strategy_field] = 0;
                } elseif ($value < 0) {
                    // Value is negative -> move it into strategy field.
                    $input[$field] = 0;
                    $input[$strategy_field] = $value;
                }
            }
        }

        return $input;
    }


    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addImpactTab($ong, $options);
        $this->addStandardTab(__CLASS__, $ong, $options);
        $this->addStandardTab('Profile_User', $ong, $options);
        $this->addStandardTab('Rule', $ong, $options);
        $this->addStandardTab('Document_Item', $ong, $options);
        $this->addStandardTab('Notepad', $ong, $options);
        $this->addStandardTab('KnowbaseItem_Item', $ong, $options);
        $this->addStandardTab('Log', $ong, $options);

        return $ong;
    }


    /**
     * @since 0.84 (before in entitydata.class)
     **/
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate) {
            switch ($item->getType()) {
                case __CLASS__:
                    $ong    = [];
                    $ong[1] = $this->getTypeName(Session::getPluralNumber());
                    $ong[2] = __('Address');
                    $ong[3] = __('Advanced information');
                    if (Notification::canView()) {
                        $ong[4] = _n('Notification', 'Notifications', Session::getPluralNumber());
                    }
                    if (
                        Session::haveRightsOr(
                            self::$rightname,
                            [self::READHELPDESK, self::UPDATEHELPDESK]
                        )
                    ) {
                        $ong[5] = __('Assistance');
                    }
                    $ong[6] = _n('Asset', 'Assets', Session::getPluralNumber());
                    if (Session::haveRight(Config::$rightname, UPDATE)) {
                        $ong[7] = __('UI customization');
                    }

                    return $ong;
            }
        }
        return '';
    }


    /**
     * @since 0.84 (before in entitydata.class)
     **/
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item->getType() == __CLASS__) {
            /** @var Entity $item */
            switch ($tabnum) {
                case 1:
                    $item->showChildren();
                    break;

                case 2:
                    self::showStandardOptions($item);
                    break;

                case 3:
                    self::showAdvancedOptions($item);
                    break;

                case 4:
                    self::showNotificationOptions($item);
                    break;

                case 5:
                    self::showHelpdeskOptions($item);
                    break;

                case 6:
                    self::showInventoryOptions($item);
                    break;

                case 7:
                    self::showUiCustomizationOptions($item);
                    break;
            }
        }
        return true;
    }


    /**
     * Print a good title for entity pages
     *
     *@return void
     **/
    public function title()
    {
       // Empty title for entities
    }

    /**
     * Get the ID of entity assigned to the object
     *
     * simply return ID
     *
     * @return integer ID of the entity
     **/
    public function getEntityID()
    {

        if (isset($this->fields["id"])) {
            return $this->fields["id"];
        }
        return -1;
    }


    public function isEntityAssign()
    {
        return true;
    }


    public function maybeRecursive()
    {
        return true;
    }


    /**
     * Is the object recursive
     *
     * Entity are always recursive
     *
     * @return integer (0/1)
     **/
    public function isRecursive()
    {
        return true;
    }

    public function post_getFromDB()
    {
        // Copy config "strategy" fields in corresponding "id" field
        // when "strategy" is < 0.
        foreach ($this->fields as $field_key => $value) {
            if (preg_match('/_strategy(_.+)?/', $field_key) === 1 && $value < 0) {
                $id_field_key = str_replace('_strategy', '_id', $field_key);
                $this->fields[$id_field_key] = $this->fields[$field_key];
            }
        }
    }

    public function post_addItem()
    {

        parent::post_addItem();

       // Add right to current user - Hack to avoid login/logout
        $_SESSION['glpiactiveentities'][$this->fields['id']] = $this->fields['id'];
        $_SESSION['glpiactiveentities_string']              .= ",'" . $this->fields['id'] . "'";
        // Root entity cannot be deleted, so if we added an entity this means GLPI is now multi-entity
        $_SESSION['glpi_multientitiesmode'] = 1;
    }

    public function post_updateItem($history = true)
    {
        /** @var \Psr\SimpleCache\CacheInterface $GLPI_CACHE */
        global $GLPI_CACHE;

        parent::post_updateItem($history);

        // Delete any cache entry corresponding to an updated entity config
        // for current entities and all its children
        $entities_ids = array_merge([$this->fields['id']], getSonsOf(self::getTable(), $this->fields['id']));
        $ignored_fields = [
            'name',
            'completename',
            'entities_id',
            'level',
            'sons_cache',
            'ancestors_cache',
            'date_mod',
            'date_creation',
        ];
        $cache_entries = [];
        foreach ($this->updates as $field) {
            if (in_array($field, $ignored_fields)) {
                continue; // Ignore fields that cannot be used as config inheritance logic
            }
            foreach ($entities_ids as $entity_id) {
                $cache_entries[] = sprintf('entity_%d_config_%s', $entity_id, $field);
            }
        }
        $GLPI_CACHE->deleteMultiple($cache_entries);
    }


    public function cleanDBonPurge()
    {

       // most use entities_id, RuleDictionnarySoftwareCollection use new_entities_id
        Rule::cleanForItemAction($this, '%entities_id');
        Rule::cleanForItemCriteria($this);

        $pu = new Profile_User();
        $pu->deleteByCriteria(['entities_id' => $this->fields['id']]);

        $this->deleteChildrenAndRelationsFromDb(
            [
                Entity_KnowbaseItem::class,
                Entity_Reminder::class,
                Entity_RSSFeed::class,
            ]
        );
    }


    /**
     * Clean caches related to entity selector.
     *
     * @since 10.0
     *
     * @return void
     * @deprecated 10.0.12
     */
    public function cleanEntitySelectorCache()
    {
        Toolbox::deprecated('`Entity::cleanEntitySelectorCache()` no longer has any effect as the entity selector is no longer cached as a unique entry');
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics')
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => $this->getTable(),
            'field'              => 'completename',
            'name'               => __('Complete name'),
            'datatype'           => 'itemlink',
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
            'id'                 => '14',
            'table'              => $this->getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'address',
            'name'               => __('Address'),
            'massiveaction'      => false,
            'datatype'           => 'text'
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => $this->getTable(),
            'field'              => 'website',
            'name'               => __('Website'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'phonenumber',
            'name'               => Phone::getTypeName(1),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => $this->getTable(),
            'field'              => 'email',
            'name'               => _n('Email', 'Emails', 1),
            'datatype'           => 'email',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '10',
            'table'              => $this->getTable(),
            'field'              => 'fax',
            'name'               => __('Fax'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '25',
            'table'              => $this->getTable(),
            'field'              => 'postcode',
            'name'               => __('Postal code'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '11',
            'table'              => $this->getTable(),
            'field'              => 'town',
            'name'               => __('City'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '12',
            'table'              => $this->getTable(),
            'field'              => 'state',
            'name'               => _x('location', 'State'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '13',
            'table'              => $this->getTable(),
            'field'              => 'country',
            'name'               => __('Country'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '67',
            'table'              => $this->getTable(),
            'field'              => 'latitude',
            'name'               => __('Latitude'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '68',
            'table'              => $this->getTable(),
            'field'              => 'longitude',
            'name'               => __('Longitude'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '69',
            'table'              => $this->getTable(),
            'field'              => 'altitude',
            'name'               => __('Altitude'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => $this->getTable(),
            'field'              => 'comment',
            'name'               => __('Comments'),
            'datatype'           => 'text'
        ];

        $tab[] = [
            'id'                 => '122',
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
            'id'                 => '70',
            'table'              => $this->getTable(),
            'field'              => 'registration_number',
            'name'               => _x('infocom', 'Administrative number'),
            'datatype'           => 'string',
            'autocomplete'       => true
        ];

       // add objectlock search options
        $tab = array_merge($tab, ObjectLock::rawSearchOptionsToAdd(get_class($this)));

        $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

        $tab[] = [
            'id'                 => 'advanced',
            'name'               => __('Advanced information')
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => $this->getTable(),
            'field'              => 'ldap_dn',
            'name'               => __('LDAP directory information attribute representing the entity'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '8',
            'table'              => $this->getTable(),
            'field'              => 'tag',
            'name'               => __('Information in inventory tool (TAG) representing the entity'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '9',
            'table'              => 'glpi_authldaps',
            'field'              => 'name',
            'name'               => __('LDAP directory of an entity'),
            'massiveaction'      => false,
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '17',
            'table'              => $this->getTable(),
            'field'              => 'entity_ldapfilter',
            'name'               => __('Search filter (if needed)'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '20',
            'table'              => $this->getTable(),
            'field'              => 'mail_domain',
            'name'               => __('Mail domain'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => 'notif',
            'name'               => __('Notification options')
        ];

        $tab[] = [
            'id'                 => '60',
            'table'              => $this->getTable(),
            'field'              => 'delay_send_emails',
            'name'               => __('Delay to send email notifications'),
            'massiveaction'      => false,
            'nosearch'           => true,
            'datatype'           => 'number',
            'min'                => 0,
            'max'                => 60,
            'step'               => 1,
            'unit'               => 'minute',
            'toadd'              => [self::CONFIG_PARENT => __('Inheritance of the parent entity')]
        ];

        $tab[] = [
            'id'                 => '61',
            'table'              => $this->getTable(),
            'field'              => 'is_notif_enable_default',
            'name'               => __('Enable notifications by default'),
            'massiveaction'      => false,
            'nosearch'           => true,
            'datatype'           => 'string'
        ];

        $tab[] = [
            'id'                 => '18',
            'table'              => $this->getTable(),
            'field'              => 'admin_email',
            'name'               => __('Administrator email address'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => $this->getTable(),
            'field'              => 'replyto_email',
            'name'               => __('Reply-To address'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '62',
            'table'              => $this->getTable(),
            'field'              => 'from_email',
            'name'               => __('Email sender address'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '63',
            'table'              => $this->getTable(),
            'field'              => 'noreply_email',
            'name'               => __('No-Reply address'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '21',
            'table'              => $this->getTable(),
            'field'              => 'notification_subject_tag',
            'name'               => __('Prefix for notifications'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '22',
            'table'              => $this->getTable(),
            'field'              => 'admin_email_name',
            'name'               => __('Administrator name'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '23',
            'table'              => $this->getTable(),
            'field'              => 'replyto_email_name',
            'name'               => __('Reply-To name'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '64',
            'table'              => $this->getTable(),
            'field'              => 'from_email_name',
            'name'               => __('Email sender name'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '65',
            'table'              => $this->getTable(),
            'field'              => 'noreply_email_name',
            'name'               => __('No-Reply name'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '24',
            'table'              => $this->getTable(),
            'field'              => 'mailing_signature',
            'name'               => __('Email signature'),
            'datatype'           => 'text'
        ];

        $tab[] = [
            'id'                 => '26',
            'table'              => $this->getTable(),
            'field'              => 'cartridges_alert_repeat',
            'name'               => __('Alarms on cartridges'),
            'massiveaction'      => false,
            'nosearch'           => true,
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '27',
            'table'              => $this->getTable(),
            'field'              => 'consumables_alert_repeat',
            'name'               => __('Alarms on consumables'),
            'massiveaction'      => false,
            'nosearch'           => true,
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '29',
            'table'              => $this->getTable(),
            'field'              => 'use_licenses_alert',
            'name'               => __('Alarms on expired licenses'),
            'massiveaction'      => false,
            'nosearch'           => true,
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '53',
            'table'              => $this->getTable(),
            'field'              => 'send_licenses_alert_before_delay',
            'name'               => __('Send license alarms before'),
            'massiveaction'      => false,
            'nosearch'           => true,
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '30',
            'table'              => $this->getTable(),
            'field'              => 'use_contracts_alert',
            'name'               => __('Alarms on contracts'),
            'massiveaction'      => false,
            'nosearch'           => true,
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '54',
            'table'              => $this->getTable(),
            'field'              => 'send_contracts_alert_before_delay',
            'name'               => __('Send contract alarms before'),
            'massiveaction'      => false,
            'nosearch'           => true,
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '31',
            'table'              => $this->getTable(),
            'field'              => 'use_infocoms_alert',
            'name'               => __('Alarms on financial and administrative information'),
            'massiveaction'      => false,
            'nosearch'           => true,
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '55',
            'table'              => $this->getTable(),
            'field'              => 'send_infocoms_alert_before_delay',
            'name'               => __('Send financial and administrative information alarms before'),
            'massiveaction'      => false,
            'nosearch'           => true,
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '32',
            'table'              => $this->getTable(),
            'field'              => 'use_reservations_alert',
            'name'               => __('Alerts on reservations'),
            'massiveaction'      => false,
            'nosearch'           => true,
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '48',
            'table'              => $this->getTable(),
            'field'              => 'default_contract_alert',
            'name'               => __('Default value for alarms on contracts'),
            'massiveaction'      => false,
            'nosearch'           => true,
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '49',
            'table'              => $this->getTable(),
            'field'              => 'default_infocom_alert',
            'name'               => __('Default value for alarms on financial and administrative information'),
            'massiveaction'      => false,
            'nosearch'           => true,
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '50',
            'table'              => $this->getTable(),
            'field'              => 'default_cartridges_alarm_threshold',
            'name'               => __('Default threshold for cartridges count'),
            'massiveaction'      => false,
            'nosearch'           => true,
            'datatype'           => 'number'
        ];

        $tab[] = [
            'id'                 => '52',
            'table'              => $this->getTable(),
            'field'              => 'default_consumables_alarm_threshold',
            'name'               => __('Default threshold for consumables count'),
            'massiveaction'      => false,
            'nosearch'           => true,
            'datatype'           => 'number'
        ];

        $tab[] = [
            'id'                 => '57',
            'table'              => $this->getTable(),
            'field'              => 'use_certificates_alert',
            'name'               => __('Alarms on expired certificates'),
            'massiveaction'      => false,
            'nosearch'           => true,
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '58',
            'table'              => $this->getTable(),
            'field'              => 'send_certificates_alert_before_delay',
            'name'               => __('Send Certificate alarms before'),
            'massiveaction'      => false,
            'nosearch'           => true,
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => 'helpdesk',
            'name'               => __('Assistance')
        ];

        $tab[] = [
            'id'                 => '47',
            'table'              => $this->getTable(),
            'field'              => 'tickettemplates_id', // not a dropdown because of special value
            'name'               => _n('Ticket template', 'Ticket templates', 1),
            'massiveaction'      => false,
            'nosearch'           => true,
            'datatype'           => 'specific',
            'additionalfields'   => ['tickettemplates_strategy']
        ];

        $tab[] = [
            'id'                 => '33',
            'table'              => $this->getTable(),
            'field'              => 'autoclose_delay',
            'name'               => __('Automatic closing of solved tickets after'),
            'massiveaction'      => false,
            'nosearch'           => true,
            'datatype'           => 'number',
            'min'                => 1,
            'max'                => 99,
            'step'               => 1,
            'unit'               => 'day',
            'toadd'              => [
                self::CONFIG_PARENT  => __('Inheritance of the parent entity'),
                self::CONFIG_NEVER   => __('Never'),
                0                  => __('Immediatly')
            ]
        ];

        $tab[] = [
            'id'                 => '59',
            'table'              => $this->getTable(),
            'field'              => 'autopurge_delay',
            'name'               => __('Automatic purge of closed tickets after'),
            'massiveaction'      => false,
            'nosearch'           => true,
            'datatype'           => 'number',
            'min'                => 1,
            'max'                => 3650,
            'step'               => 1,
            'unit'               => 'day',
            'toadd'              => [
                self::CONFIG_PARENT  => __('Inheritance of the parent entity'),
                self::CONFIG_NEVER   => __('Never'),
                0                  => __('Immediatly')
            ]
        ];

        $tab[] = [
            'id'                 => '34',
            'table'              => $this->getTable(),
            'field'              => 'notclosed_delay',
            'name'               => __('Alerts on tickets which are not solved'),
            'massiveaction'      => false,
            'nosearch'           => true,
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '35',
            'table'              => $this->getTable(),
            'field'              => 'auto_assign_mode',
            'name'               => __('Automatic assignment of tickets'),
            'massiveaction'      => false,
            'nosearch'           => true,
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '36',
            'table'              => $this->getTable(),
            'field'              => 'calendars_id',// not a dropdown because of special valu
            'name'               => _n('Calendar', 'Calendars', 1),
            'massiveaction'      => false,
            'nosearch'           => true,
            'datatype'           => 'specific',
            'additionalfields'   => ['calendars_strategy']
        ];

        $tab[] = [
            'id'                 => '37',
            'table'              => $this->getTable(),
            'field'              => 'tickettype',
            'name'               => __('Tickets default type'),
            'massiveaction'      => false,
            'nosearch'           => true,
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '75',
            'table'              => self::getTable(),
            'field'              => 'contracts_id_default',
            'name'               => __('Default contract'),
            'datatype'           => 'specific',
            'nosearch'           => true,
            'additionalfields'   => ['contracts_strategy_default'],
            'toadd'              => [
                self::CONFIG_PARENT => __('Inheritance of the parent entity'),
                self::CONFIG_AUTO   => __('Contract in ticket entity'),
            ]
        ];

        $tab[] = [
            'id'                 => 'assets',
            'name'               => _n('Asset', 'Assets', Session::getPluralNumber())
        ];

        $tab[] = [
            'id'                 => '38',
            'table'              => $this->getTable(),
            'field'              => 'autofill_buy_date',
            'name'               => __('Date of purchase'),
            'massiveaction'      => false,
            'nosearch'           => true,
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '39',
            'table'              => $this->getTable(),
            'field'              => 'autofill_order_date',
            'name'               => __('Order date'),
            'massiveaction'      => false,
            'nosearch'           => true,
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '40',
            'table'              => $this->getTable(),
            'field'              => 'autofill_delivery_date',
            'name'               => __('Delivery date'),
            'massiveaction'      => false,
            'nosearch'           => true,
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '41',
            'table'              => $this->getTable(),
            'field'              => 'autofill_use_date',
            'name'               => __('Startup date'),
            'massiveaction'      => false,
            'nosearch'           => true,
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '42',
            'table'              => $this->getTable(),
            'field'              => 'autofill_warranty_date',
            'name'               => __('Start date of warranty'),
            'massiveaction'      => false,
            'nosearch'           => true,
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '43',
            'table'              => $this->getTable(),
            'field'              => 'inquest_config',
            'name'               => __('Satisfaction survey configuration'),
            'massiveaction'      => false,
            'nosearch'           => true,
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '44',
            'table'              => $this->getTable(),
            'field'              => 'inquest_rate',
            'name'               => __('Satisfaction survey trigger rate'),
            'massiveaction'      => false,
            'datatype'           => 'number'
        ];

        $tab[] = [
            'id'                 => '45',
            'table'              => $this->getTable(),
            'field'              => 'inquest_delay',
            'name'               => __('Create survey after'),
            'massiveaction'      => false,
            'datatype'           => 'number'
        ];

        $tab[] = [
            'id'                 => '46',
            'table'              => $this->getTable(),
            'field'              => 'inquest_URL',
            'name'               => __('URL'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '51',
            'table'              => $this->getTable(),
            'field'              => 'entities_id_software',
            'linkfield'          => 'entities_id_software', // not a dropdown because of special value
                                 //TRANS: software in plural
            'name'               => __('Entity for software creation'),
            'massiveaction'      => false,
            'nosearch'           => true,
            'datatype'           => 'specific',
            'additionalfields'   => ['entities_strategy_software']
        ];

        $tab[] = [
            'id'                 => '56',
            'table'              => $this->getTable(),
            'field'              => 'autofill_decommission_date',
            'name'               => __('Decommission date'),
            'massiveaction'      => false,
            'nosearch'           => true,
            'datatype'           => 'specific'
        ];

        return $tab;
    }


    /**
     * @since 0.83 (before addRule)
     *
     * @param $input array of values
     **/
    public function executeAddRule($input)
    {

        $this->check($_POST["affectentity"], UPDATE);

        $collection = RuleCollection::getClassByType($_POST['sub_type']);
        $rule       = $collection->getRuleClass();
        $ruleid     = $rule->add($_POST);

        if ($ruleid) {
           //Add an action associated to the rule
            $ruleAction = new RuleAction();

           //Action is : affect computer to this entity
            $ruleAction->addActionByAttributes(
                "assign",
                $ruleid,
                "entities_id",
                $_POST["affectentity"]
            );

            switch ($_POST['sub_type']) {
                case 'RuleRight':
                    if ($_POST["profiles_id"]) {
                        $ruleAction->addActionByAttributes(
                            "assign",
                            $ruleid,
                            "profiles_id",
                            $_POST["profiles_id"]
                        );
                    }
                    $ruleAction->addActionByAttributes(
                        "assign",
                        $ruleid,
                        "is_recursive",
                        $_POST["is_recursive"]
                    );
            }
        }

        Event::log(
            $ruleid,
            "rules",
            4,
            "setup",
            //TRANS: %s is the user login
            sprintf(__('%s adds the item'), $_SESSION["glpiname"])
        );

        Html::back();
    }


    /**
     * get all entities with a notification option set
     * manage CONFIG_PARENT (or NULL) value
     *
     * @param $field  String name of the field to search (>0)
     *
     * @return Array of id => value
     **/
    public static function getEntitiesToNotify($field)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $entities = [];

       // root entity first
        $ent = new self();
        if ($ent->getFromDB(0)) {  // always exists
            $val = $ent->getField($field);
            if ($val > 0) {
                $entities[0] = $val;
            }
        }

       // Others entities in level order (parent first)
        $iterator = $DB->request([
            'SELECT' => [
                'id AS entity',
                'entities_id AS parent',
                $field
            ],
            'FROM'   => self::getTable(),
            'ORDER'  => 'level ASC'
        ]);

        foreach ($iterator as $entitydata) {
            if (
                (is_null($entitydata[$field])
                || ($entitydata[$field] == self::CONFIG_PARENT))
                && isset($entities[$entitydata['parent']])
            ) {
                // config inherit from parent
                $entities[$entitydata['entity']] = $entities[$entitydata['parent']];
            } else if ($entitydata[$field] > 0) {
               // config found in entity
                $entities[$entitydata['entity']] = $entitydata[$field];
            }
        }

        return $entities;
    }


    /**
     * @since 0.84
     *
     * @param $entity Entity object
     **/
    public static function showStandardOptions(Entity $entity)
    {

        $con_spotted = false;
        $ID          = $entity->getField('id');
        if (!$entity->can($ID, READ)) {
            return false;
        }

       // Entity right applied
        $canedit = $entity->can($ID, UPDATE);

        echo "<div class='spaced'>";
        if ($canedit) {
            echo "<form method='post' name=form action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "' data-track-changes='true'>";
        }

        echo "<table class='tab_cadre_fixe'>";

        Plugin::doHook(Hooks::PRE_ITEM_FORM, ['item' => $entity, 'options' => []]);

        echo "<tr><th colspan='4'>" . __('Address') . "</th></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . Phone::getTypeName(1) . "</td>";
        echo "<td>";
        echo Html::input('phonenumber', ['value' => $entity->fields['phonenumber']]);
        echo "</td>";
        echo "<td>" . _x('infocom', 'Administrative number') . "</td>";
        echo "<td>";
        echo Html::input('registration_number', ['value' => $entity->fields['registration_number']]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Fax') . "</td>";
        echo "<td>";
        echo Html::input('fax', ['value' => $entity->fields['fax']]);
        echo "</td>";
        echo "<td rowspan='6'>" . __('Address') . "</td>";
        echo "<td rowspan='6'>";
        echo "<textarea name='address' class='form-control'>" . $entity->fields["address"] . "</textarea>";
        echo "</td></tr>";
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Website') . "</td>";
        echo "<td>";
        echo Html::input('website', ['value' => $entity->fields['website']]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . _n('Email', 'Emails', 1) . "</td>";
        echo "<td>";
        echo Html::input('email', ['value' => $entity->fields['email']]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Postal code') . "</td>";
        echo "<td>";
        echo Html::input('postcode', ['value' => $entity->fields['postcode'], 'size' => 7]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('City') . "</td>";
        echo "<td>";
        echo Html::input('town', ['value' => $entity->fields['town'], 'size' => 27]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . _x('location', 'State') . "</td>";
        echo "<td>";
        echo Html::input('state', ['value' => $entity->fields['state']]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Country') . "</td>";
        echo "<td>";
        echo Html::input('country', ['value' => $entity->fields['country']]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Location on map') . "</td>";
        echo "<td>";
        $entity->displaySpecificTypeField($ID, [
            'name'   => 'setlocation',
            'type'   => 'setlocation',
            'label'  => __('Location on map'),
            'list'   => false
        ]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . _x('location', 'Longitude') . "</td>";
        echo "<td>";
        echo Html::input('longitude', ['value' => $entity->fields['longitude']]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . _x('location', 'Latitude') . "</td>";
        echo "<td>";
        echo Html::input('latitude', ['value' => $entity->fields['latitude']]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . _x('location', 'Altitude') . "</td>";
        echo "<td>";
        echo Html::input('altitude', ['value' => $entity->fields['altitude']]);
        echo "</td></tr>";

        Plugin::doHook(Hooks::POST_ITEM_FORM, ['item' => $entity, 'options' => []]);
        echo "</table>";

        if ($canedit) {
            echo "<div class='center'>";
            echo "<input type='hidden' name='id' value='" . $entity->fields["id"] . "'>";
            echo "<input type='submit' name='update' value=\"" . _sx('button', 'Save') . "\" class='btn btn-primary'>";
            echo "</div>";
            Html::closeForm();
        }
        echo "</div>";
    }


    /**
     * @since 0.84 (before in entitydata.class)
     *
     * @param $entity Entity object
     **/
    public static function showAdvancedOptions(Entity $entity)
    {
        $con_spotted = false;
        $ID          = $entity->getField('id');
        if (!$entity->can($ID, READ)) {
            return false;
        }

       // Entity right applied (could be User::UPDATEAUTHENT)
        $canedit = $entity->can($ID, UPDATE);

        if ($canedit) {
            echo "<form method='post' name=form action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "' data-track-changes='true'>";
        }

        echo "<table class='tab_cadre_fixe'>";

        Plugin::doHook(Hooks::PRE_ITEM_FORM, ['item' => $entity, 'options' => []]);

        echo "<tr><th colspan='2'>" . __('Values for the generic rules for assignment to entities') .
           "</th></tr>";

        echo "<tr class='tab_bg_1'><td colspan='2' class='center'>" .
             __('These parameters are used as actions in generic rules for assignment to entities') .
           "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Information in inventory tool (TAG) representing the entity') . "</td>";
        echo "<td>";
        echo Html::input('tag', ['value' => $entity->fields['tag'], 'size' => 100]);
        echo "</td></tr>";

        if (Toolbox::canUseLdap()) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('LDAP directory information attribute representing the entity') . "</td>";
            echo "<td>";
            echo Html::input('ldap_dn', ['value' => $entity->fields['ldap_dn'], 'size' => 100]);
            echo "</td></tr>";
        }

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Mail domain surrogates entity') . "</td>";
        echo "<td>";
        echo Html::input('mail_domain', ['value' => $entity->fields['mail_domain'], 'size' => 100]);
        echo "</td></tr>";

        if (Toolbox::canUseLdap()) {
            echo "<tr><th colspan='2'>" .
                __('Values used in the interface to search users from a LDAP directory') .
              "</th></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('LDAP directory of an entity') . "</td>";
            echo "<td>";
            AuthLDAP::dropdown([
                'value'      => $entity->fields['authldaps_id'],
                'emptylabel' => __('Default server'),
                'condition'  => ['is_active' => 1]
            ]);
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('LDAP filter associated to the entity (if necessary)') . "</td>";
            echo "<td>";
            echo Html::input('entity_ldapfilter', ['value' => $entity->fields['entity_ldapfilter'], 'size' => 100]);
            echo "</td></tr>";
        }

        Plugin::doHook(Hooks::POST_ITEM_FORM, ['item' => $entity, 'options' => []]);

        echo "</table>";

        if ($canedit) {
            echo "<div class='center'>";
            echo "<input type='hidden' name='id' value='" . $entity->fields["id"] . "'>";
            echo "<input type='submit' name='update' value=\"" . _sx('button', 'Save') . "\" class='btn btn-primary'>";
            echo "</div>";
            Html::closeForm();
        }
    }


    /**
     * @since 0.84 (before in entitydata.class)
     *
     * @param $entity Entity object
     **/
    public static function showInventoryOptions(Entity $entity)
    {

        $ID = $entity->getField('id');
        if (!$entity->can($ID, READ)) {
            return false;
        }

        $canedit = $entity->can($ID, UPDATE);

        echo "<div class='spaced'>";
        if ($canedit) {
            echo "<form method='post' name=form action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "' data-track-changes='true'>";
        }

        echo "<table class='tab_cadre_fixe'>";

        Plugin::doHook(Hooks::PRE_ITEM_FORM, ['item' => $entity, 'options' => []]);

        echo "<tr><th colspan='4'>" . __('Autofill dates for financial and administrative information') .
           "</th></tr>";

        $options[0] = __('No autofill');
        if ($ID > 0) {
            $options[self::CONFIG_PARENT] = __('Inheritance of the parent entity');
        }

        $states = getAllDataFromTable('glpi_states');
        foreach ($states as $state) {
            $options[Infocom::ON_STATUS_CHANGE . '_' . $state['id']]
                     //TRANS: %s is the name of the state
            = sprintf(__('Fill when shifting to state %s'), $state['name']);
        }

        $options[Infocom::COPY_WARRANTY_DATE] = __('Copy the start date of warranty');
       //Buy date
        echo "<tr class='tab_bg_2'>";
        echo "<td> " . __('Date of purchase') . "</td>";
        echo "<td>";
        Dropdown::showFromArray(
            'autofill_buy_date',
            $options,
            ['value' => $entity->getField('autofill_buy_date')]
        );
        if ($entity->fields['autofill_buy_date'] == self::CONFIG_PARENT) {
            $inherited_value = self::getUsedConfig('autofill_buy_date', $entity->getField('entities_id'));
            self::inheritedValue(self::getSpecificValueToDisplay('autofill_buy_date', $inherited_value));
        }
        echo "</td>";

       //Order date
        echo "<td> " . __('Order date') . "</td>";
        echo "<td>";
        $options[Infocom::COPY_BUY_DATE] = __('Copy the date of purchase');
        Dropdown::showFromArray(
            'autofill_order_date',
            $options,
            ['value' => $entity->getField('autofill_order_date')]
        );
        if ($entity->fields['autofill_order_date'] == self::CONFIG_PARENT) {
            $inherited_value = self::getUsedConfig('autofill_order_date', $entity->getField('entities_id'));
            self::inheritedValue(self::getSpecificValueToDisplay('autofill_order_date', $inherited_value));
        }
        echo "</td></tr>";

       //Delivery date
        echo "<tr class='tab_bg_2'>";
        echo "<td> " . __('Delivery date') . "</td>";
        echo "<td>";
        $options[Infocom::COPY_ORDER_DATE] = __('Copy the order date');
        Dropdown::showFromArray(
            'autofill_delivery_date',
            $options,
            ['value' => $entity->getField('autofill_delivery_date')]
        );
        if ($entity->fields['autofill_delivery_date'] == self::CONFIG_PARENT) {
            $inherited_value = self::getUsedConfig('autofill_delivery_date', $entity->getField('entities_id'));
            self::inheritedValue(self::getSpecificValueToDisplay('autofill_delivery_date', $inherited_value));
        }
        echo "</td>";

       //Use date
        echo "<td> " . __('Startup date') . " </td>";
        echo "<td>";
        $options[Infocom::COPY_DELIVERY_DATE] = __('Copy the delivery date');
        Dropdown::showFromArray(
            'autofill_use_date',
            $options,
            ['value' => $entity->getField('autofill_use_date')]
        );
        if ($entity->fields['autofill_use_date'] == self::CONFIG_PARENT) {
            $inherited_value = self::getUsedConfig('autofill_use_date', $entity->getField('entities_id'));
            self::inheritedValue(self::getSpecificValueToDisplay('autofill_use_date', $inherited_value));
        }
        echo "</td></tr>";

       //Warranty date
        echo "<tr class='tab_bg_2'>";
        echo "<td> " . __('Start date of warranty') . "</td>";
        echo "<td>";
        $options = [0                           => __('No autofill'),
            Infocom::COPY_BUY_DATE      => __('Copy the date of purchase'),
            Infocom::COPY_ORDER_DATE    => __('Copy the order date'),
            Infocom::COPY_DELIVERY_DATE => __('Copy the delivery date')
        ];
        if ($ID > 0) {
            $options[self::CONFIG_PARENT] = __('Inheritance of the parent entity');
        }

        Dropdown::showFromArray(
            'autofill_warranty_date',
            $options,
            ['value' => $entity->getField('autofill_warranty_date')]
        );
        if ($entity->fields['autofill_warranty_date'] == self::CONFIG_PARENT) {
            $inherited_value = self::getUsedConfig('autofill_warranty_date', $entity->getField('entities_id'));
            self::inheritedValue(self::getSpecificValueToDisplay('autofill_warranty_date', $inherited_value));
        }
        echo "</td>";

       //Decommission date
        echo "<td> " . __('Decommission date') . "</td>";
        echo "<td>";

        $options = [0                           => __('No autofill'),
            Infocom::COPY_BUY_DATE      => __('Copy the date of purchase'),
            Infocom::COPY_ORDER_DATE    => __('Copy the order date'),
            Infocom::COPY_DELIVERY_DATE => __('Copy the delivery date')
        ];
        if ($ID > 0) {
            $options[self::CONFIG_PARENT] = __('Inheritance of the parent entity');
        }

        foreach ($states as $state) {
            $options[Infocom::ON_STATUS_CHANGE . '_' . $state['id']]
                     //TRANS: %s is the name of the state
            = sprintf(__('Fill when shifting to state %s'), $state['name']);
        }

        Dropdown::showFromArray(
            'autofill_decommission_date',
            $options,
            ['value' => $entity->getField('autofill_decommission_date')]
        );

        if ($entity->fields['autofill_decommission_date'] == self::CONFIG_PARENT) {
            $inherited_value = self::getUsedConfig('autofill_decommission_date', $entity->getField('entities_id'));
            self::inheritedValue(self::getSpecificValueToDisplay('autofill_decommission_date', $inherited_value));
        }
        echo "</td></tr>";

        echo "<tr><th colspan='4'>" . _n('Software', 'Software', Session::getPluralNumber()) . "</th></tr>";
        echo "<tr class='tab_bg_2'>";
        echo "<td> " . __('Entity for software creation') . "</td>";
        echo "<td>";

        $toadd = [self::CONFIG_NEVER => __('No change of entity')]; // Keep software in PC entity
        $entities = [];
        if ($ID > 0) {
            $toadd[self::CONFIG_PARENT] = __('Inheritance of the parent entity');
            $entities = [$entity->fields['entities_id']];
            foreach (getAncestorsOf('glpi_entities', $entity->fields['entities_id']) as $ent) {
                if (Session::haveAccessToEntity($ent)) {
                    $entities[] = $ent;
                }
            }
        }
        self::dropdown(['name'     => 'entities_id_software',
            'value'    => $entity->fields['entities_id_software'],
            'toadd'    => $toadd,
            'entity'   => $entities,
            'comments' => false
        ]);

        if ($entity->fields['entities_id_software'] == self::CONFIG_PARENT) {
            $inherited_strategy = self::getUsedConfig('entities_strategy_software', $entity->fields['entities_id']);
            $inherited_value    = $inherited_strategy === 0
                ? self::getUsedConfig('entities_strategy_software', $entity->fields['entities_id'], 'entities_id_software')
                : $inherited_strategy;
            self::inheritedValue(self::getSpecificValueToDisplay('entities_id_software', $inherited_value));
        }
        echo "</td><td colspan='2'></td></tr>";

        echo "<tr><th colspan='4'>" . __('Transfer') . "</th></tr>";
        echo "<tr class='tab_bg_2'>";
        echo "<td>";
        echo __('Model for automatic entity transfer on inventories');
        echo "</td>";
        echo "<td>";
        $params = [
            'name'       => 'transfers_id',
            'value'      => $entity->fields['transfers_id'],
            'display_emptychoice' => false
        ];
        $params['toadd'] = [
            self::CONFIG_NEVER => __('No automatic transfer')
        ];
        if ($entity->fields['id'] > 0) {
            $params['toadd'][self::CONFIG_PARENT] = __('Inheritance of the parent entity');
        }
        Dropdown::show('Transfer', $params);
        if ($entity->fields['transfers_strategy'] == self::CONFIG_PARENT) {
            $inherited_strategy = self::getUsedConfig('transfers_strategy', $entity->fields['entities_id']);
            $inherited_value    = $inherited_strategy === 0
                ? self::getUsedConfig('transfers_strategy', $entity->fields['entities_id'], 'transfers_id')
                : $inherited_strategy;
            self::inheritedValue(self::getSpecificValueToDisplay('transfers_id', $inherited_value));
        }
        echo "</td>";
        echo "</td><td colspan='2'></td></tr>";

        echo "<tr><th colspan='4'>" . __('Automatic inventory') . "</th></tr>";
        echo "<tr class='tab_bg_2'>";
        echo "<td><label for='agent_base_url'>" . __('Agent base URL') . "</label></td>";
        echo "<td>";
        echo Html::input('agent_base_url', ['value' => $entity->fields['agent_base_url']]);
        if (empty($entity->fields['agent_base_url']) && $ID > 0) {
            self::inheritedValue(self::getUsedConfig('agent_base_url', $ID, '', ''));
        }
        echo "</td><td colspan='2'></td></tr>";

        Plugin::doHook(Hooks::POST_ITEM_FORM, ['item' => $entity, 'options' => &$options]);

        echo "</table>";

        if ($canedit) {
            echo "<div class='center'>";
            echo "<input type='hidden' name='id' value='" . $entity->fields["id"] . "'>";
            echo "<input type='submit' name='update' value=\"" . _sx('button', 'Save') . "\" class='btn btn-primary'>";
            echo "</div>";
            Html::closeForm();
        }

        echo "</div>";
    }


    /**
     * @since 0.84 (before in entitydata.class)
     *
     * @param $entity Entity object
     **/
    public static function showNotificationOptions(Entity $entity)
    {

        $ID = $entity->getField('id');
        if (
            !$entity->can($ID, READ)
            || !Notification::canView()
        ) {
            return false;
        }

       // Notification right applied
        $canedit = (Notification::canUpdate()
                  && Session::haveAccessToEntity($ID));

        echo "<div class='spaced'>";
        if ($canedit) {
            echo "<form method='post' name=form action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "' data-track-changes='true'>";
        }

        echo "<table class='tab_cadre_fixe'>";

        Plugin::doHook(Hooks::PRE_ITEM_FORM, ['item' => $entity, 'options' => []]);

        echo "<tr><th colspan='4'>" . __('Notification options') . "</th></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Administrator email address') . "</td>";
        echo "<td>";
        echo Html::input('admin_email', ['value' => $entity->fields['admin_email'], 'type' => 'email']);
        if (empty($entity->fields['admin_email']) && $ID > 0) {
            self::inheritedValue(self::getUsedConfig('admin_email', $ID, '', ''));
        }
        if (!empty($entity->fields['admin_email']) && !NotificationMailing::isUserAddressValid($entity->fields['admin_email'])) {
            echo "<span class='red'>" . __('Invalid email address') . "</span>";
        }
        echo "</td>";
        echo "<td>" . __('Administrator name') . "</td><td>";
       // we inherit only if email inherit also
        echo Html::input('admin_email_name', ['value' => $entity->fields['admin_email_name']]);
       // warning, we rely on email field to inherit name field
        if (empty($entity->fields['admin_email']) && $ID > 0) {
            self::inheritedValue(self::getUsedConfig('admin_email_name', $ID, '', ''));
        }
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Email sender address') . "</td>";
        echo "<td>";
        echo Html::input('from_email', ['value' => $entity->fields['from_email'], 'type' => 'email']);
        if (empty($entity->fields['from_email']) && $ID > 0) {
            self::inheritedValue(self::getUsedConfig('from_email', $ID, '', ''));
        }
        if (!empty($entity->fields['from_email']) && !NotificationMailing::isUserAddressValid($entity->fields['from_email'])) {
            echo "<span class='red'>" . __('Invalid email address') . "</span>";
        }
        echo "</td>";
        echo "<td>" . __('Email sender name') . "</td><td>";
        // we inherit only if email inherit also
        echo Html::input('from_email_name', ['value' => $entity->fields['from_email_name']]);
        // warning, we rely on email field to inherit name field
        if (empty($entity->fields['from_email']) && $ID > 0) {
            self::inheritedValue(self::getUsedConfig('from_email_name', $ID, '', ''));
        }
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('No-Reply address') . "</td>";
        echo "<td>";
        echo Html::input('noreply_email', ['value' => $entity->fields['noreply_email'], 'type' => 'email']);
        if (empty($entity->fields['noreply_email']) && $ID > 0) {
            self::inheritedValue(self::getUsedConfig('noreply_email', $ID, '', ''));
        }
        if (!empty($entity->fields['noreply_email']) && !NotificationMailing::isUserAddressValid($entity->fields['noreply_email'])) {
            echo "<span class='red'>" . __('Invalid email address') . "</span>";
        }
        echo "</td>";
        echo "<td>" . __('No-Reply name') . "</td><td>";
        // we inherit only if email inherit also
        echo Html::input('noreply_email_name', ['value' => $entity->fields['noreply_email_name']]);
        // warning, we rely on email field to inherit name field
        if (empty($entity->fields['noreply_email']) && $ID > 0) {
            self::inheritedValue(self::getUsedConfig('noreply_email_name', $ID, '', ''));
        }
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td><label for='replyto_email'>" . __('Reply-To address') . "</label></td>";
        echo "<td>";
        echo Html::input('replyto_email', ['value' => $entity->fields['replyto_email'], 'type' => 'email']);
        if (empty($entity->fields['replyto_email']) && $ID > 0) {
            self::inheritedValue(self::getUsedConfig('replyto_email', $ID, '', ''));
        }
        if (!empty($entity->fields['replyto_email']) && !NotificationMailing::isUserAddressValid($entity->fields['replyto_email'])) {
            echo "<span class='red'>" . __('Invalid email address') . "</span>";
        }
        echo "</td>";
        echo "<td><label for='replyto_email_name'>" . __('Reply-To name') . "</label></td>";
        echo "<td>";
        echo Html::input('replyto_email_name', ['value' => $entity->fields['replyto_email_name']]);
       // warning, we rely on email field to inherit name field
        if (empty($entity->fields['replyto_email']) && $ID > 0) {
            self::inheritedValue(self::getUsedConfig('replyto_email_name', $ID, '', ''));
        }
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Prefix for notifications') . "</td>";
        echo "<td>";
        echo Html::input('notification_subject_tag', ['value' => $entity->fields['notification_subject_tag']]);
        if (empty($entity->fields['notification_subject_tag']) && $ID > 0) {
            self::inheritedValue(self::getUsedConfig('notification_subject_tag', $ID, '', ''));
        }
        echo "</td>";
        echo "<td>" . __('Delay to send email notifications') . "</td>";
        echo "<td>";
        $toadd = [];
        if ($ID > 0) {
            $toadd = [self::CONFIG_PARENT => __('Inheritance of the parent entity')];
        }
        Dropdown::showNumber('delay_send_emails', ['value' => $entity->fields["delay_send_emails"],
            'min'   => 0,
            'max'   => 100,
            'unit'  => 'minute',
            'toadd' => $toadd
        ]);

        if ($entity->fields['delay_send_emails'] == self::CONFIG_PARENT) {
            $tid = self::getUsedConfig('delay_send_emails', $entity->getField('entities_id'));
            self::inheritedValue($entity->getValueToDisplay('delay_send_emails', $tid, ['html' => true]));
        }
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Enable notifications by default') . "</td>";
        echo "<td>";

        Alert::dropdownYesNo(['name'           => "is_notif_enable_default",
            'value'          =>  $entity->getField('is_notif_enable_default'),
            'inherit_parent' => (($ID > 0) ? 1 : 0)
        ]);

        if ($entity->fields['is_notif_enable_default'] == self::CONFIG_PARENT) {
            $tid = self::getUsedConfig('is_notif_enable_default', $entity->getField('entities_id'));
            self::inheritedValue(self::getSpecificValueToDisplay('is_notif_enable_default', $tid));
        }
        echo "</td>";
        echo "<td colspan='2'>&nbsp;</td>";

        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Email signature') . "</td>";
        echo "<td colspan='3'>";
        echo "<textarea rows='5' name='mailing_signature' class='form-control'>" .
             $entity->fields["mailing_signature"] . "</textarea>";
        if (empty($entity->fields['mailing_signature']) && $ID > 0) {
            self::inheritedValue(self::getUsedConfig('mailing_signature', $ID, '', ''));
        }
        echo "</td></tr>";
        echo "</table>";

        echo "<table class='tab_cadre_fixe tab_spaced'>";
        echo "<tr><th colspan='4'>" . __('Alarms options') . "</th></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<th colspan='2' rowspan='2'>";
        echo _n('Cartridge', 'Cartridges', Session::getPluralNumber());
        echo "</th>";
        echo "<td>" . __('Reminders frequency for alarms on cartridges') . "</td><td>";
        $default_value = $entity->fields['cartridges_alert_repeat'];
        Alert::dropdown(['name'           => 'cartridges_alert_repeat',
            'value'          => $default_value,
            'inherit_parent' => (($ID > 0) ? 1 : 0)
        ]);

        if ($entity->fields['cartridges_alert_repeat'] == self::CONFIG_PARENT) {
            $tid = self::getUsedConfig('cartridges_alert_repeat', $entity->getField('entities_id'));
            self::inheritedValue(self::getSpecificValueToDisplay('cartridges_alert_repeat', $tid), true);
        }

        echo "</td></tr>";
        echo "<tr class='tab_bg_1'><td>" . __('Default threshold for cartridges count') . "</td><td>";
        if ($ID > 0) {
            $toadd = [self::CONFIG_PARENT => __('Inheritance of the parent entity'),
                self::CONFIG_NEVER => __('Never')
            ];
        } else {
            $toadd = [self::CONFIG_NEVER => __('Never')];
        }
        Dropdown::showNumber(
            'default_cartridges_alarm_threshold',
            ['value' => $entity->fields["default_cartridges_alarm_threshold"],
                'min'   => 0,
                'max'   => 100,
                'step'  => 1,
                'toadd' => $toadd
            ]
        );
        if ($entity->fields['default_cartridges_alarm_threshold'] == self::CONFIG_PARENT) {
            $tid = self::getUsedConfig(
                'default_cartridges_alarm_threshold',
                $entity->getField('entities_id')
            );
            self::inheritedValue(self::getSpecificValueToDisplay('default_cartridges_alarm_threshold', $tid), true);
        }
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<th colspan='2' rowspan='2'>";
        echo _n('Consumable', 'Consumables', Session::getPluralNumber());
        echo "</th>";

        echo "<td>" . __('Reminders frequency for alarms on consumables') . "</td><td>";
        $default_value = $entity->fields['consumables_alert_repeat'];
        Alert::dropdown(['name'           => 'consumables_alert_repeat',
            'value'          => $default_value,
            'inherit_parent' => (($ID > 0) ? 1 : 0)
        ]);
        if ($entity->fields['consumables_alert_repeat'] == self::CONFIG_PARENT) {
            $tid = self::getUsedConfig('consumables_alert_repeat', $entity->getField('entities_id'));
            self::inheritedValue(self::getSpecificValueToDisplay('consumables_alert_repeat', $tid), true);
        }
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . __('Default threshold for consumables count') . "</td><td>";
        if ($ID > 0) {
            $toadd = [self::CONFIG_PARENT => __('Inheritance of the parent entity'),
                self::CONFIG_NEVER => __('Never')
            ];
        } else {
            $toadd = [self::CONFIG_NEVER => __('Never')];
        }
        Dropdown::showNumber(
            'default_consumables_alarm_threshold',
            ['value' => $entity->fields["default_consumables_alarm_threshold"],
                'min'   => 0,
                'max'   => 100,
                'step'  => 1,
                'toadd' => $toadd
            ]
        );
        if ($entity->fields['default_consumables_alarm_threshold'] == self::CONFIG_PARENT) {
            $tid = self::getUsedConfig(
                'default_consumables_alarm_threshold',
                $entity->getField('entities_id')
            );
            self::inheritedValue(self::getSpecificValueToDisplay('default_consumables_alarm_threshold', $tid), true);
        }
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<th colspan='2' rowspan='3'>";
        echo _n('Contract', 'Contracts', Session::getPluralNumber());
        echo "</th>";
        echo "<td>" . __('Alarms on contracts') . "</td><td>";
        $default_value = $entity->fields['use_contracts_alert'];
        Alert::dropdownYesNo(['name'           => "use_contracts_alert",
            'value'          => $default_value,
            'inherit_parent' => (($ID > 0) ? 1 : 0)
        ]);
        if ($entity->fields['use_contracts_alert'] == self::CONFIG_PARENT) {
            $tid = self::getUsedConfig('use_contracts_alert', $entity->getField('entities_id'));
            self::inheritedValue(self::getSpecificValueToDisplay('use_contracts_alert', $tid), true);
        }
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . __('Default value') . "</td><td>";
        Contract::dropdownAlert(['name'           => "default_contract_alert",
            'value'          => $entity->fields["default_contract_alert"],
            'inherit_parent' => (($ID > 0) ? 1 : 0)
        ]);
        if ($entity->fields['default_contract_alert'] == self::CONFIG_PARENT) {
            $tid = self::getUsedConfig('default_contract_alert', $entity->getField('entities_id'));
            self::inheritedValue(self::getSpecificValueToDisplay('default_contract_alert', $tid), true);
        }

        echo "</td></tr>";
        echo "<tr class='tab_bg_1'><td>" . __('Send contract alarms before') . "</td><td>";
        Alert::dropdownIntegerNever(
            'send_contracts_alert_before_delay',
            $entity->fields['send_contracts_alert_before_delay'],
            ['max'            => 99,
                'inherit_parent' => (($ID > 0) ? 1 : 0),
                'unit'           => 'day',
                'never_string'   => __('No')
            ]
        );
        if ($entity->fields['send_contracts_alert_before_delay'] == self::CONFIG_PARENT) {
            $tid = self::getUsedConfig(
                'send_contracts_alert_before_delay',
                $entity->getField('entities_id')
            );
            self::inheritedValue(self::getSpecificValueToDisplay('send_contracts_alert_before_delay', $tid), true);
        }
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<th colspan='2' rowspan='3'>";
        echo __('Financial and administrative information');
        echo "</th>";
        echo "<td>" . __('Alarms on financial and administrative information') . "</td><td>";
        $default_value = $entity->fields['use_infocoms_alert'];
        Alert::dropdownYesNo(['name'           => "use_infocoms_alert",
            'value'          => $default_value,
            'inherit_parent' => (($ID > 0) ? 1 : 0)
        ]);
        if ($entity->fields['use_infocoms_alert'] == self::CONFIG_PARENT) {
            $tid = self::getUsedConfig('use_infocoms_alert', $entity->getField('entities_id'));
            self::inheritedValue(self::getSpecificValueToDisplay('use_infocoms_alert', $tid), true);
        }

        echo "</td></tr>";
        echo "<tr class='tab_bg_1'><td>" . __('Default value') . "</td><td>";
        Infocom::dropdownAlert(['name'           => 'default_infocom_alert',
            'value'          => $entity->fields["default_infocom_alert"],
            'inherit_parent' => (($ID > 0) ? 1 : 0)
        ]);
        if ($entity->fields['default_infocom_alert'] == self::CONFIG_PARENT) {
            $tid = self::getUsedConfig('default_infocom_alert', $entity->getField('entities_id'));
            self::inheritedValue(self::getSpecificValueToDisplay('default_infocom_alert', $tid), true);
        }

        echo "</td></tr>";
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Send financial and administrative information alarms before') . "</td><td>";
        Alert::dropdownIntegerNever(
            'send_infocoms_alert_before_delay',
            $entity->fields['send_infocoms_alert_before_delay'],
            ['max'            => 99,
                'inherit_parent' => (($ID > 0) ? 1 : 0),
                'unit'           => 'day',
                'never_string'   => __('No')
            ]
        );
        if ($entity->fields['send_infocoms_alert_before_delay'] == self::CONFIG_PARENT) {
            $tid = self::getUsedConfig(
                'send_infocoms_alert_before_delay',
                $entity->getField('entities_id')
            );
            self::inheritedValue(self::getSpecificValueToDisplay('send_infocoms_alert_before_delay', $tid), true);
        }
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<th colspan='2' rowspan='2'>";
        echo SoftwareLicense::getTypeName(Session::getPluralNumber());
        echo "</th>";
        echo "<td>" . __('Alarms on expired licenses') . "</td><td>";
        $default_value = $entity->fields['use_licenses_alert'];
        Alert::dropdownYesNo(['name'           => "use_licenses_alert",
            'value'          => $default_value,
            'inherit_parent' => (($ID > 0) ? 1 : 0)
        ]);
        if ($entity->fields['use_licenses_alert'] == self::CONFIG_PARENT) {
            $tid = self::getUsedConfig('use_licenses_alert', $entity->getField('entities_id'));
            self::inheritedValue(self::getSpecificValueToDisplay('use_licenses_alert', $tid), true);
        }
        echo "</td></tr>";
        echo "<tr class='tab_bg_1'><td>" . __('Send license alarms before') . "</td><td>";
        Alert::dropdownIntegerNever(
            'send_licenses_alert_before_delay',
            $entity->fields['send_licenses_alert_before_delay'],
            ['max'            => 99,
                'inherit_parent' => (($ID > 0) ? 1 : 0),
                'unit'           => 'day',
                'never_string'   => __('No')
            ]
        );
        if ($entity->fields['send_licenses_alert_before_delay'] == self::CONFIG_PARENT) {
            $tid = self::getUsedConfig(
                'send_licenses_alert_before_delay',
                $entity->getField('entities_id')
            );
            self::inheritedValue(self::getSpecificValueToDisplay('send_licenses_alert_before_delay', $tid), true);
        }

        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<th colspan='2' rowspan='3'>";
        echo _n('Certificate', 'Certificates', Session::getPluralNumber());
        echo "</th>";
        echo "<td>" . __('Alarms on expired certificates') . "</td><td>";
        $default_value = $entity->fields['use_certificates_alert'];
        Alert::dropdownYesNo(['name'           => "use_certificates_alert",
            'value'          => $default_value,
            'inherit_parent' => (($ID > 0) ? 1 : 0)
        ]);
        if ($entity->fields['use_certificates_alert'] == self::CONFIG_PARENT) {
            $tid = self::getUsedConfig('use_certificates_alert', $entity->getField('entities_id'));
            self::inheritedValue(self::getSpecificValueToDisplay('use_certificates_alert', $tid), true);
        }
        echo "</td></tr>";
        echo "<tr class='tab_bg_1'><td>" . __('Send certificates alarms before') . "</td><td>";
        Alert::dropdownIntegerNever(
            'send_certificates_alert_before_delay',
            $entity->fields['send_certificates_alert_before_delay'],
            ['max'            => 99,
                'inherit_parent' => (($ID > 0) ? 1 : 0),
                'unit'           => 'day',
                'never_string'   => __('No')
            ]
        );
        if ($entity->fields['send_certificates_alert_before_delay'] == self::CONFIG_PARENT) {
            $tid = self::getUsedConfig(
                'send_certificates_alert_before_delay',
                $entity->getField('entities_id')
            );
            self::inheritedValue(self::getSpecificValueToDisplay('send_certificates_alert_before_delay', $tid), true);
        }
        echo "</td></tr>";
        echo "<td>" . __('Reminders frequency for alarms on certificates') . "</td><td>";
        $default_value = $entity->fields['certificates_alert_repeat_interval'];
        Alert::dropdown(['name'           => 'certificates_alert_repeat_interval',
            'value'          => $default_value,
            'inherit_parent' => (($ID > 0) ? 1 : 0)
        ]);
        if ($entity->fields['certificates_alert_repeat_interval'] == self::CONFIG_PARENT) {
            $tid = self::getUsedConfig('certificates_alert_repeat_interval', $entity->getField('entities_id'));
            self::inheritedValue(self::getSpecificValueToDisplay('certificates_alert_repeat_interval', $tid), true);
        }

        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<th colspan='2' rowspan='1'>";
        echo _n('Reservation', 'Reservations', Session::getPluralNumber());
        echo "</th>";
        echo "<td>" . __('Alerts on reservations') . "</td><td>";
        Alert::dropdownIntegerNever(
            'use_reservations_alert',
            $entity->fields['use_reservations_alert'],
            ['max'            => 99,
                'inherit_parent' => (($ID > 0) ? 1 : 0),
                'unit'           => 'hour'
            ]
        );
        if ($entity->fields['use_reservations_alert'] == self::CONFIG_PARENT) {
            $tid = self::getUsedConfig('use_reservations_alert', $entity->getField('entities_id'));
            self::inheritedValue(self::getSpecificValueToDisplay('use_reservations_alert', $tid), true);
        }
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<th colspan='2' rowspan='1'>";
        echo _n('Ticket', 'Tickets', Session::getPluralNumber());
        echo "</th>";
        echo "<td >" . __('Alerts on tickets which are not solved since') . "</td><td>";
        Alert::dropdownIntegerNever(
            'notclosed_delay',
            $entity->fields["notclosed_delay"],
            ['max'            => 99,
                'inherit_parent' => (($ID > 0) ? 1 : 0),
                'unit'           => 'day'
            ]
        );
        if ($entity->fields['notclosed_delay'] == self::CONFIG_PARENT) {
            $tid = self::getUsedConfig('notclosed_delay', $entity->getField('entities_id'));
            self::inheritedValue(self::getSpecificValueToDisplay('notclosed_delay', $tid), true);
        }
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<th colspan='2' rowspan='3'>";
        echo Domain::getTypeName(Session::getPluralNumber());
        echo "</th>";
        echo "<td>" . __('Alarms on domains expiries') . "</td><td>";
        $default_value = $entity->fields['use_domains_alert'];
        Alert::dropdownYesNo(['name'           => "use_domains_alert",
            'value'          => $default_value,
            'inherit_parent' => (($ID > 0) ? 1 : 0)
        ]);
        if ($entity->fields['use_domains_alert'] == self::CONFIG_PARENT) {
            $tid = self::getUsedConfig('use_domains_alert', $entity->getField('entities_id'));
            self::inheritedValue(self::getSpecificValueToDisplay('use_domains_alert', $tid), true);
        }
        echo "</td></tr>";
        echo "<tr class='tab_bg_1'>";

        echo "<td>" . __('Domains closes expiries') . "</td><td>";
        Alert::dropdownIntegerNever(
            'send_domains_alert_close_expiries_delay',
            $entity->fields["send_domains_alert_close_expiries_delay"],
            [
                'max'            => 99,
                'inherit_parent' => (($ID > 0) ? 1 : 0),
                'unit'           => 'day'
            ]
        );
        if ($entity->fields['send_domains_alert_close_expiries_delay'] == self::CONFIG_PARENT) {
            $tid = self::getUsedConfig('send_domains_alert_close_expiries_delay', $entity->getField('entities_id'));
            self::inheritedValue(self::getSpecificValueToDisplay('send_domains_alert_close_expiries_delay', $tid), true);
        }
        echo "</td></tr>";
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Domains expired') . "</td><td>";
        Alert::dropdownIntegerNever(
            'send_domains_alert_expired_delay',
            $entity->fields["send_domains_alert_expired_delay"],
            [
                'max'            => 99,
                'inherit_parent' => (($ID > 0) ? 1 : 0),
                'unit'           => 'day'
            ]
        );
        if ($entity->fields['send_domains_alert_expired_delay'] == self::CONFIG_PARENT) {
            $tid = self::getUsedConfig('send_domains_alert_expired_delay', $entity->getField('entities_id'));
            self::inheritedValue(self::getSpecificValueToDisplay('send_domains_alert_expired_delay', $tid), true);
        }
        echo "</td></tr>";

        Plugin::doHook(Hooks::POST_ITEM_FORM, ['item' => $entity, 'options' => []]);

        echo "</table>";

        if ($canedit) {
            echo "<div class='center'>";
            echo "<input type='hidden' name='id' value='" . $entity->fields["id"] . "'>";
            echo "<input type='submit' name='update' value=\"" . _sx('button', 'Save') . "\" class='btn btn-primary'>";
            echo "</div>";
            Html::closeForm();
        }

        echo "</div>";
    }

    /**
     * UI customization configuration form.
     *
     * @param $entity Entity object
     *
     * @return void
     *
     * @since 9.5.0
     */
    public static function showUiCustomizationOptions(Entity $entity)
    {

        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $ID = $entity->getField('id');
        if (!$entity->can($ID, READ) || !Session::haveRight(Config::$rightname, UPDATE)) {
            return false;
        }

       // Codemirror lib
        echo Html::css('public/lib/codemirror.css');
        echo Html::script("public/lib/codemirror.js");

       // Notification right applied
        $canedit = Session::haveRight(Config::$rightname, UPDATE)
         && Session::haveAccessToEntity($ID);

        echo "<div class='spaced'>";
        if ($canedit) {
            echo "<form method='post' name=form action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "' data-track-changes='true'>";
        }

        echo "<table class='tab_cadre_fixe custom_css_configuration'>";

        Plugin::doHook(Hooks::PRE_ITEM_FORM, ['item' => $entity, 'options' => []]);

        $rand = mt_rand();

        echo "<tr><th colspan='2'>" . __('UI options') . "</th></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Enable CSS customization') . "</td>";
        echo "<td>";
        $values = [];
        if (($ID > 0) ? 1 : 0) {
            $values[Entity::CONFIG_PARENT] = __('Inherits configuration from the parent entity');
        }
        $values[0] = __('No');
        $values[1] = __('Yes');
        echo Dropdown::showFromArray(
            'enable_custom_css',
            $values,
            [
                'display' => false,
                'rand'    => $rand,
                'value'   => $entity->fields['enable_custom_css']
            ]
        );
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td colspan='2'>";
        echo "<div id='custom_css_container' class='custom_css_container'>";
        $value = $entity->fields['enable_custom_css'];
       // wrap call in function to prevent modifying variables from current scope
        call_user_func(function () use ($value, $ID) {
            $_POST  = [
                'enable_custom_css' => $value,
                'entities_id'       => $ID
            ];
            include GLPI_ROOT . '/ajax/entityCustomCssCode.php';
        });
        echo "</div>\n";
        echo "</td></tr>";

        Ajax::updateItemOnSelectEvent(
            'dropdown_enable_custom_css' . $rand,
            'custom_css_container',
            $CFG_GLPI['root_doc'] . '/ajax/entityCustomCssCode.php',
            [
                'enable_custom_css' => '__VALUE__',
                'entities_id'       => $ID
            ]
        );

        Plugin::doHook(Hooks::POST_ITEM_FORM, ['item' => $entity, 'options' => []]);

        echo "</table>";

        if ($canedit) {
            echo "<div class='center'>";
            echo "<input type='hidden' name='id' value='" . $entity->fields["id"] . "'>";
            echo "<input type='submit' name='update' value=\"" . _sx('button', 'Save') . "\" class='btn btn-primary'>";
            echo "</div>";
            Html::closeForm();
        }

        echo "</div>";
    }

    /**
     * Returns tag containing custom CSS code applied to entity.
     *
     * @return string
     */
    public function getCustomCssTag()
    {

        $enable_custom_css = self::getUsedConfig(
            'enable_custom_css',
            $this->fields['id']
        );

        if (!$enable_custom_css) {
            return '';
        }

        $custom_css_code = self::getUsedConfig(
            'enable_custom_css',
            $this->fields['id'],
            'custom_css_code'
        );
        if (empty($custom_css_code)) {
            return '';
        }

        return '<style>' . strip_tags($custom_css_code) . '</style>';
    }

    /**
     * @since 0.84 (before in entitydata.class)
     *
     * @param string $field
     * @param string $value  must be addslashes
     **/
    private static function getEntityIDByField($field, $value)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => self::getTable(),
            'WHERE'  => [$field => $value]
        ]);

        if (count($iterator) == 1) {
            $result = $iterator->current();
            return $result['id'];
        }
        return -1;
    }


    /**
     * @since 0.84 (before in entitydata.class)
     *
     * @param $value
     **/
    public static function getEntityIDByDN($value)
    {
        return self::getEntityIDByField("ldap_dn", $value);
    }


    /**
     * @since 0.84
     *
     * @param $value
     **/
    public static function getEntityIDByCompletename($value)
    {
        return self::getEntityIDByField("completename", $value);
    }


    /**
     * @since 0.84 (before in entitydata.class)
     *
     * @param $value
     **/
    public static function getEntityIDByTag($value)
    {
        return self::getEntityIDByField("tag", $value);
    }


    /**
     * @since 0.84 (before in entitydata.class)
     *
     * @param $value
     **/
    public static function getEntityIDByDomain($value)
    {
        return self::getEntityIDByField("mail_domain", $value);
    }


    /**
     * @since 0.84 (before in entitydata.class)
     *
     * @param $entities_id
     **/
    public static function isEntityDirectoryConfigured($entities_id)
    {

        $entity = new self();

        if (
            $entity->getFromDB($entities_id)
            && ($entity->getField('authldaps_id') > 0)
        ) {
            return true;
        }

       //If there's a directory marked as default
        if (AuthLDAP::getDefault()) {
            return true;
        }
        return false;
    }


    /**
     * @since 0.84 (before in entitydata.class)
     *
     * @param $entity Entity object
     **/
    public static function showHelpdeskOptions(Entity $entity)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $ID = $entity->getField('id');
        if (
            !$entity->can($ID, READ)
            || !Session::haveRightsOr(
                self::$rightname,
                [self::READHELPDESK, self::UPDATEHELPDESK]
            )
        ) {
            return false;
        }
        $canedit = (Session::haveRight(self::$rightname, self::UPDATEHELPDESK)
                  && Session::haveAccessToEntity($ID));

        echo "<div class='spaced'>";
        if ($canedit) {
            echo "<form method='post' name=form action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "' data-track-changes='true'>";
        }

        echo "<table class='tab_cadre_fixe'>";

        Plugin::doHook(Hooks::PRE_ITEM_FORM, ['item' => $entity, 'options' => []]);

        echo "<tr><th colspan='4'>" . __('Templates configuration') . "</th></tr>";

        echo "<tr class='tab_bg_1'><td colspan='2'>" . _n('Ticket template', 'Ticket templates', 1) .
           "</td>";
        echo "<td colspan='2'>";
        $toadd = [];
        if ($ID != 0) {
            $toadd = [self::CONFIG_PARENT => __('Inheritance of the parent entity')];
        }

        $options = ['value'  => $entity->fields["tickettemplates_id"],
            'entity' => $ID,
            'toadd'  => $toadd
        ];

        TicketTemplate::dropdown($options);

        if ($entity->fields["tickettemplates_id"] == self::CONFIG_PARENT) {
            $tt  = new TicketTemplate();
            $tid = self::getUsedConfig('tickettemplates_strategy', $ID, 'tickettemplates_id', 0);
            if (!$tid) {
                self::inheritedValue(Dropdown::EMPTY_VALUE, true);
            } else if ($tt->getFromDB($tid)) {
                self::inheritedValue($tt->getLink(), true);
            }
        }
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td colspan='2'>" . _n('Change template', 'Change templates', 1) .
           "</td>";
        echo "<td colspan='2'>";
        $toadd = [];
        if ($ID != 0) {
            $toadd = [self::CONFIG_PARENT => __('Inheritance of the parent entity')];
        }

        $options = ['value'  => $entity->fields["changetemplates_id"],
            'entity' => $ID,
            'toadd'  => $toadd
        ];

        ChangeTemplate::dropdown($options);

        if ($entity->fields["changetemplates_id"] == self::CONFIG_PARENT) {
            $tt  = new ChangeTemplate();
            $tid = self::getUsedConfig('changetemplates_strategy', $ID, 'changetemplates_id', 0);
            if (!$tid) {
                self::inheritedValue(Dropdown::EMPTY_VALUE, true);
            } else if ($tt->getFromDB($tid)) {
                self::inheritedValue($tt->getLink(), true);
            }
        }
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td colspan='2'>" . _n('Problem template', 'Problem templates', 1) .
           "</td>";
        echo "<td colspan='2'>";
        $toadd = [];
        if ($ID != 0) {
            $toadd = [self::CONFIG_PARENT => __('Inheritance of the parent entity')];
        }

        $options = ['value'  => $entity->fields["problemtemplates_id"],
            'entity' => $ID,
            'toadd'  => $toadd
        ];

        ProblemTemplate::dropdown($options);

        if ($entity->fields["problemtemplates_id"] == self::CONFIG_PARENT) {
            $tt  = new ProblemTemplate();
            $tid = self::getUsedConfig('problemtemplates_strategy', $ID, 'problemtemplates_id', 0);
            if (!$tid) {
                self::inheritedValue(Dropdown::EMPTY_VALUE, true);
            } else if ($tt->getFromDB($tid)) {
                self::inheritedValue($tt->getLink(), true);
            }
        }
        echo "</td></tr>";

        echo "<tr><th colspan='4'>" . __('Tickets configuration') . "</th></tr>";

        echo "<tr class='tab_bg_1'><td colspan='2'>" . _n('Calendar', 'Calendars', 1) . "</td>";
        echo "<td colspan='2'>";
        $options = ['value'      => $entity->fields["calendars_id"],
            'emptylabel' => __('24/7')
        ];

        if ($ID != 0) {
            $options['toadd'] = [self::CONFIG_PARENT => __('Inheritance of the parent entity')];
        }
        Calendar::dropdown($options);

        if ($entity->fields["calendars_id"] == self::CONFIG_PARENT) {
            $calendar = new Calendar();
            $cid = self::getUsedConfig('calendars_strategy', $ID, 'calendars_id', 0);
            if (!$cid) {
                self::inheritedValue(__('24/7'), true);
            } else if ($calendar->getFromDB($cid)) {
                self::inheritedValue($calendar->getLink(), true);
            }
        }
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td colspan='2'>" . __('Tickets default type') . "</td>";
        echo "<td colspan='2'>";
        $toadd = [];
        if ($ID != 0) {
            $toadd = [self::CONFIG_PARENT => __('Inheritance of the parent entity')];
        }
        Ticket::dropdownType('tickettype', ['value' => $entity->fields["tickettype"],
            'toadd' => $toadd
        ]);

        if ($entity->fields['tickettype'] == self::CONFIG_PARENT) {
            self::inheritedValue(Ticket::getTicketTypeName(self::getUsedConfig(
                'tickettype',
                $ID,
                '',
                Ticket::INCIDENT_TYPE
            )), true);
        }
        echo "</td></tr>";

       // Auto assign mode
        echo "<tr class='tab_bg_1'><td  colspan='2'>" . __('Automatic assignment of tickets, changes and problems') . "</td>";
        echo "<td colspan='2'>";
        $autoassign = self::getAutoAssignMode();

        if ($ID == 0) {
            unset($autoassign[self::CONFIG_PARENT]);
        }

        Dropdown::showFromArray(
            'auto_assign_mode',
            $autoassign,
            ['value' => $entity->fields["auto_assign_mode"]]
        );

        if ($entity->fields['auto_assign_mode'] == self::CONFIG_PARENT) {
            $auto_assign_mode = self::getUsedConfig('auto_assign_mode', $entity->fields['entities_id']);
            self::inheritedValue($autoassign[$auto_assign_mode], true);
        }
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td  colspan='2'>" . __('Mark followup added by a supplier though an email collector as private') . "</td>";
        echo "<td colspan='2'>";
        $supplierValues = self::getSuppliersAsPrivateValues();
        $currentSupplierValue = $entity->fields['suppliers_as_private'];

        if ($ID == 0) { // Remove parent option for root entity
            unset($supplierValues[self::CONFIG_PARENT]);
        }

        Dropdown::showFromArray(
            'suppliers_as_private',
            $supplierValues,
            ['value' => $currentSupplierValue]
        );

       // If the entity is using it's parent value, print it
        if ($currentSupplierValue == self::CONFIG_PARENT) {
            $parentSupplierValue = self::getUsedConfig(
                'suppliers_as_private',
                $entity->fields['entities_id']
            );
            self::inheritedValue($supplierValues[$parentSupplierValue], true);
        }
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td  colspan='2'>" . __('Anonymize support agents') . "</td>";
        echo "<td colspan='2'>";
        $anonymize_values = self::getAnonymizeSupportAgentsValues();
        $current_anonymize_value = $entity->fields['anonymize_support_agents'];

        if ($ID == 0) { // Remove parent option for root entity
            unset($anonymize_values[self::CONFIG_PARENT]);
        }

        Dropdown::showFromArray(
            'anonymize_support_agents',
            $anonymize_values,
            ['value' => $current_anonymize_value]
        );

       // If the entity is using it's parent value, print it
        if ($current_anonymize_value == self::CONFIG_PARENT) {
            $parent_helpdesk_value = self::getUsedConfig(
                'anonymize_support_agents',
                $entity->fields['entities_id']
            );
            self::inheritedValue($anonymize_values[$parent_helpdesk_value], true);
        }
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td  colspan='2'>" . __("Display initials for users without pictures") . "</td>";
        echo "<td colspan='2'>";
        $initialsValues = self::getDisplayUsersInitialsValues();
        $currentInitialsValue = $entity->fields['display_users_initials'];

        if ($ID == 0) { // Remove parent option for root entity
            unset($initialsValues[self::CONFIG_PARENT]);
        }

        Dropdown::showFromArray(
            'display_users_initials',
            $initialsValues,
            ['value' => $currentInitialsValue]
        );

       // If the entity is using it's parent value, print it
        if ($currentInitialsValue == self::CONFIG_PARENT) {
            $parentSupplierValue = self::getUsedConfig(
                'display_users_initials',
                $entity->fields['entities_id']
            );
            self::inheritedValue($initialsValues[$parentSupplierValue], true);
        }
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td  colspan='2'>" . __('Default contract') . "</td>";
        echo "<td colspan='2'>";
        $current_default_contract_value = $entity->fields['contracts_id_default'];

        $toadd = [
            self::CONFIG_PARENT => __('Inheritance of the parent entity'),
            self::CONFIG_AUTO   => __('Contract in ticket entity'),
        ];

        if ($ID == 0) { // Remove parent option for root entity
            unset($toadd[self::CONFIG_PARENT]);
        }

        Contract::dropdown([
            'name'      => 'contracts_id_default',
            'condition' => ['is_template' => 0, 'is_deleted' => 0] + Contract::getExpiredCriteria(),
            'entity'    => $entity->getID(),
            'toadd'     => $toadd,
            'value'     => $current_default_contract_value,
        ]);

        // If the entity is using it's parent value, print it
        if ($current_default_contract_value == self::CONFIG_PARENT) {
            $inherited_default_contract_strategy = self::getUsedConfig(
                'contracts_strategy_default',
                $entity->fields['entities_id']
            );
            $inherited_default_contract_id = self::getUsedConfig(
                'contracts_strategy_default',
                $entity->fields['entities_id'],
                'contracts_id_default',
                0
            );
            $contract = new Contract();
            if ($inherited_default_contract_strategy == self::CONFIG_AUTO) {
                $display_value = __('Contract in ticket entity');
            } elseif ($inherited_default_contract_id > 0 && $contract->getFromDB($inherited_default_contract_id)) {
                $display_value = $contract->fields['name'];
            } else {
                $display_value = Dropdown::EMPTY_VALUE;
            }

            self::inheritedValue($display_value, true);
        }
        echo "</td></tr>";

        echo "<tr><th colspan='4'>" . __('Automatic closing configuration') . "</th></tr>";

        echo "<tr class='tab_bg_1'>" .
         "<td>" . __('Automatic closing of solved tickets after');

       //Check if crontask is disabled
        $crontask = new CronTask();
        $criteria = [
            'itemtype'  => 'Ticket',
            'name'      => 'closeticket',
            'state'     => CronTask::STATE_DISABLE
        ];
        if ($crontask->getFromDBByCrit($criteria)) {
            echo "<br/><strong>" . __('Close ticket action is disabled.') . "</strong>";
        }

        echo "</td>";
        echo "<td>";
        $autoclose = [self::CONFIG_PARENT => __('Inheritance of the parent entity'),
            self::CONFIG_NEVER  => __('Never'),
            0                   => __('Immediatly')
        ];
        if ($ID == 0) {
            unset($autoclose[self::CONFIG_PARENT]);
        }

        Dropdown::showNumber(
            'autoclose_delay',
            ['value' => $entity->fields['autoclose_delay'],
                'min'   => 1,
                'max'   => 99,
                'step'  => 1,
                'toadd' => $autoclose,
                'unit'  => 'day'
            ]
        );

        if ($entity->fields['autoclose_delay'] == self::CONFIG_PARENT) {
            $autoclose_mode = self::getUsedConfig(
                'autoclose_delay',
                $entity->fields['entities_id'],
                '',
                self::CONFIG_NEVER
            );

            if ($autoclose_mode >= 0) {
                self::inheritedValue(sprintf(_n('%d day', '%d days', $autoclose_mode), $autoclose_mode), true);
            } else {
                self::inheritedValue($autoclose[$autoclose_mode], true);
            }
        }
        echo "<td>" . __('Automatic purge of closed tickets after');

       //Check if crontask is disabled
        $crontask = new CronTask();
        $criteria = [
            'itemtype'  => 'Ticket',
            'name'      => 'purgeticket',
            'state'     => CronTask::STATE_DISABLE
        ];
        if ($crontask->getFromDBByCrit($criteria)) {
            echo "<br/><strong>" . __('Purge ticket action is disabled.') . "</strong>";
        }
        echo "</td>";
        echo "<td>";
        $autopurge = [
            self::CONFIG_PARENT => __('Inheritance of the parent entity'),
            self::CONFIG_NEVER  => __('Never')
        ];
        if ($ID == 0) {
            unset($autopurge[self::CONFIG_PARENT]);
        }

        Dropdown::showNumber(
            'autopurge_delay',
            [
                'value' => $entity->fields['autopurge_delay'],
                'min'   => 1,
                'max'   => 3650,
                'step'  => 1,
                'toadd' => $autopurge,
                'unit'  => 'day'
            ]
        );

        if ($entity->fields['autopurge_delay'] == self::CONFIG_PARENT) {
            $autopurge_mode = self::getUsedConfig(
                'autopurge_delay',
                $entity->fields['entities_id'],
                '',
                self::CONFIG_NEVER
            );

            if ($autopurge_mode >= 0) {
                self::inheritedValue(sprintf(_n('%d day', '%d days', $autopurge_mode), $autopurge_mode), true);
            } else {
                self::inheritedValue($autopurge[$autopurge_mode], true);
            }
        }
        echo "</td></tr>";

        echo "<tr><th colspan='4'>" . __('Configuring the satisfaction survey') . "</th></tr>";

        echo "<tr class='tab_bg_1'>" .
           "<td colspan='2'>" . __('Configuring the satisfaction survey') . "</td>";
        echo "<td colspan='2'>";

       /// no inquest case = rate 0
        $typeinquest = [self::CONFIG_PARENT  => __('Inheritance of the parent entity'),
            1                    => __('Internal survey'),
            2                    => __('External survey')
        ];

       // No inherit from parent for root entity
        if ($ID == 0) {
            unset($typeinquest[self::CONFIG_PARENT]);
            if ($entity->fields['inquest_config'] == self::CONFIG_PARENT) {
                $entity->fields['inquest_config'] = 1;
            }
        }
        $rand = Dropdown::showFromArray(
            'inquest_config',
            $typeinquest,
            $options = ['value' => $entity->fields['inquest_config']]
        );
        echo "</td></tr>\n";

        if ($entity->fields['inquest_config'] == self::CONFIG_PARENT) {
            $inquestconfig = self::getUsedConfig('inquest_config', $entity->fields['entities_id']);
            $inquestrate   = self::getUsedConfig(
                'inquest_config',
                $entity->fields['entities_id'],
                'inquest_rate'
            );
            echo "<tr class='tab_bg_1'><td colspan='4'>";

            $inherit = "";
            if ($inquestrate == 0) {
                $inherit .= __('Disabled');
            } else {
                $inherit .= $typeinquest[$inquestconfig] . '<br>';
                $inqconf = self::getUsedConfig(
                    'inquest_config',
                    $entity->fields['entities_id'],
                    'inquest_delay'
                );

                $inherit .= sprintf(_n('%d day', '%d days', $inqconf), $inqconf);
                $inherit .= "<br>";
               //TRANS: %d is the percentage. %% to display %
                $inherit .= sprintf(__('%d%%'), $inquestrate);

                if ($inquestconfig == 2) {
                    $inherit .= "<br>";
                    $inherit .= self::getUsedConfig(
                        'inquest_config',
                        $entity->fields['entities_id'],
                        'inquest_URL'
                    );
                }
            }
            self::inheritedValue($inherit, true);
            echo "</td></tr>";
        }

        echo "<tr class='tab_bg_1'><td colspan='4'>";

        $_POST  = ['inquest_config' => $entity->fields['inquest_config'],
            'entities_id'    => $ID
        ];
        $params = ['inquest_config' => '__VALUE__',
            'entities_id'    => $ID
        ];
        echo "<div id='inquestconfig'>";
        include GLPI_ROOT . '/ajax/ticketsatisfaction.php';
        echo "</div>\n";

        echo "</td></tr>";

        Plugin::doHook(Hooks::POST_ITEM_FORM, ['item' => $entity, 'options' => &$options]);

        echo "</table>";

        if ($canedit) {
            echo "<div class='center'>";
            echo "<input type='hidden' name='id' value='" . $entity->fields["id"] . "'>";
            echo "<input type='submit' name='update' value=\"" . _sx('button', 'Save') . "\"
                  class='btn btn-primary'>";
            echo "</div>";
            Html::closeForm();
        }

        echo "</div>";

        Ajax::updateItemOnSelectEvent(
            "dropdown_inquest_config$rand",
            "inquestconfig",
            $CFG_GLPI["root_doc"] . "/ajax/ticketsatisfaction.php",
            $params
        );
    }


    /**
     * Retrieve data of current entity or parent entity
     *
     * @since 0.84 (before in entitydata.class)
     *
     * @param string  $fieldref       name of the referent field to know if we look at parent entity
     * @param integer $entities_id
     * @param string  $fieldval       name of the field that we want value (default '')
     * @param mixed   $default_value  value to return (default -2)
     **/
    public static function getUsedConfig($fieldref, $entities_id, $fieldval = '', $default_value = -2)
    {
        /**
         * @var \DBmysql $DB
         * @var \Psr\SimpleCache\CacheInterface $GLPI_CACHE
         */
        global $DB, $GLPI_CACHE;

        $id_using_strategy = [
            'calendars_id',
            'changetemplates_id',
            'contracts_id_default',
            'entities_id_software',
            'problemtemplates_id',
            'tickettemplates_id',
            'transfers_id',
        ];
        if (in_array($fieldref, $id_using_strategy)) {
            $fieldval = $fieldref;
            $fieldref = str_replace('_id', '_strategy', $fieldref);
            $default_value = 0;
            trigger_error(
                sprintf(
                    'Entity config "%s" should be get using its reference field "%s" with a "0" default value',
                    $fieldval,
                    $fieldref
                )
            );
        }

        if (empty($fieldval)) {
            $fieldval = $fieldref;
        }

        $ref_cache_key = sprintf('entity_%d_config_%s', $entities_id, $fieldref);
        $val_cache_key = sprintf('entity_%d_config_%s', $entities_id, $fieldval);

        $ref = $GLPI_CACHE->get($ref_cache_key);
        $val = $fieldref === $fieldval ? $ref : $GLPI_CACHE->get($val_cache_key);

        if ($ref === null || $val === null) {
            $entities_query = [
                'SELECT' => ['id', 'entities_id', $fieldref],
                'FROM'   => self::getTable(),
                'WHERE'  => ['id' => array_merge([$entities_id], getAncestorsOf(self::getTable(), $entities_id))]
            ];
            if ($fieldval !== $fieldref) {
                $entities_query['SELECT'][] = $fieldval;
            }
            $entities_data = iterator_to_array($DB->request($entities_query));

            $current_id = $entities_id;
            while ($current_id !== null) {
                if (!array_key_exists($current_id, $entities_data)) {
                    break; // Cannot find entity data, so cannot continue
                }

                $entity_data = $entities_data[$current_id];

                $ref = $entity_data[$fieldref];
                $inherits = (is_numeric($default_value) && $ref == self::CONFIG_PARENT)
                    || (!is_numeric($default_value) && !$ref);
                if (!$inherits) {
                    $val = $entity_data[$fieldval];
                    break;
                }

                // Value inherited: parse parent data
                $current_id = $entity_data['entities_id'];
            }
        }

        $GLPI_CACHE->setMultiple(
            [
                $ref_cache_key => $ref,
                $val_cache_key => $val,
            ]
        );

        return $val ?? $default_value;
    }


    /**
     * Generate link for ticket satisfaction
     *
     * @since 0.84 (before in entitydata.class)
     *
     * @param $ticket ticket object
     *
     * @return string url contents
     **/
    public static function generateLinkSatisfaction($ticket)
    {
        $url = self::getUsedConfig('inquest_config', $ticket->fields['entities_id'], 'inquest_URL');

        if (strstr($url, "[TICKET_ID]")) {
            $url = str_replace("[TICKET_ID]", $ticket->fields['id'], $url);
        }

        if (strstr($url, "[TICKET_NAME]")) {
            $url = str_replace("[TICKET_NAME]", urlencode($ticket->fields['name']), $url);
        }

        if (strstr($url, "[TICKET_CREATEDATE]")) {
            $url = str_replace("[TICKET_CREATEDATE]", $ticket->fields['date'], $url);
        }

        if (strstr($url, "[TICKET_SOLVEDATE]")) {
            $url = str_replace("[TICKET_SOLVEDATE]", $ticket->fields['solvedate'], $url);
        }

        if (strstr($url, "[REQUESTTYPE_ID]")) {
            $url = str_replace("[REQUESTTYPE_ID]", $ticket->fields['requesttypes_id'], $url);
        }

        if (strstr($url, "[REQUESTTYPE_NAME]")) {
            $url = str_replace(
                "[REQUESTTYPE_NAME]",
                urlencode(Dropdown::getDropdownName(
                    'glpi_requesttypes',
                    $ticket->fields['requesttypes_id']
                )),
                $url
            );
        }

        if (strstr($url, "[TICKET_PRIORITY]")) {
            $url = str_replace("[TICKET_PRIORITY]", $ticket->fields['priority'], $url);
        }

        if (strstr($url, "[TICKET_PRIORITYNAME]")) {
            $url = str_replace(
                "[TICKET_PRIORITYNAME]",
                urlencode(CommonITILObject::getPriorityName($ticket->fields['priority'])),
                $url
            );
        }

        if (strstr($url, "[TICKETCATEGORY_ID]")) {
            $url = str_replace("[TICKETCATEGORY_ID]", $ticket->fields['itilcategories_id'], $url);
        }

        if (strstr($url, "[TICKETCATEGORY_NAME]")) {
            $url = str_replace(
                "[TICKETCATEGORY_NAME]",
                urlencode(Dropdown::getDropdownName(
                    'glpi_itilcategories',
                    $ticket->fields['itilcategories_id']
                )),
                $url
            );
        }

        if (strstr($url, "[TICKETTYPE_ID]")) {
            $url = str_replace("[TICKETTYPE_ID]", $ticket->fields['type'], $url);
        }

        if (strstr($url, "[TICKET_TYPENAME]")) {
            $url = str_replace(
                "[TICKET_TYPENAME]",
                Ticket::getTicketTypeName($ticket->fields['type']),
                $url
            );
        }

        if (strstr($url, "[SOLUTIONTYPE_ID]")) {
            $url = str_replace("[SOLUTIONTYPE_ID]", $ticket->fields['solutiontypes_id'], $url);
        }

        if (strstr($url, "[SOLUTIONTYPE_NAME]")) {
            $url = str_replace(
                "[SOLUTIONTYPE_NAME]",
                urlencode(Dropdown::getDropdownName(
                    'glpi_solutiontypes',
                    $ticket->fields['solutiontypes_id']
                )),
                $url
            );
        }

        if (strstr($url, "[SLA_TTO_ID]")) {
            $url = str_replace("[SLA_TTO_ID]", $ticket->fields['slas_id_tto'], $url);
        }

        if (strstr($url, "[SLA_TTO_NAME]")) {
            $url = str_replace(
                "[SLA_TTO_NAME]",
                urlencode(Dropdown::getDropdownName(
                    'glpi_slas',
                    $ticket->fields['slas_id_tto']
                )),
                $url
            );
        }

        if (strstr($url, "[SLA_TTR_ID]")) {
            $url = str_replace("[SLA_TTR_ID]", $ticket->fields['slas_id_ttr'], $url);
        }

        if (strstr($url, "[SLA_TTR_NAME]")) {
            $url = str_replace(
                "[SLA_TTR_NAME]",
                urlencode(Dropdown::getDropdownName(
                    'glpi_slas',
                    $ticket->fields['slas_id_ttr']
                )),
                $url
            );
        }

        if (strstr($url, "[SLALEVEL_ID]")) {
            $url = str_replace("[SLALEVEL_ID]", $ticket->fields['slalevels_id_ttr'], $url);
        }

        if (strstr($url, "[SLALEVEL_NAME]")) {
            $url = str_replace(
                "[SLALEVEL_NAME]",
                urlencode(Dropdown::getDropdownName(
                    'glpi_slalevels',
                    $ticket->fields['slalevels_id_ttr']
                )),
                $url
            );
        }

        return $url;
    }

    /**
     * get value for auto_assign_mode
     *
     * @since 0.84 (created in version 0.83 in entitydata.class)
     *
     * @param integer|null $val if not set, ask for all values, else for 1 value (default NULL)
     *
     * @return string|array
     **/
    public static function getAutoAssignMode($val = null)
    {

        $tab = [
            self::CONFIG_PARENT                  => __('Inheritance of the parent entity'),
            self::CONFIG_NEVER                   => __('No'),
            self::AUTO_ASSIGN_HARDWARE_CATEGORY  => __('Based on the item then the category'),
            self::AUTO_ASSIGN_CATEGORY_HARDWARE  => __('Based on the category then the item'),
        ];

        if (is_null($val)) {
            return $tab;
        }
        if (isset($tab[$val])) {
            return $tab[$val];
        }
        return NOT_AVAILABLE;
    }

    /**
     * get value for display_users_initials
     *
     * @since 10.0.0
     *
     * @return array
     **/
    public static function getDisplayUsersInitialsValues()
    {

        return [
            self::CONFIG_PARENT => __('Inheritance of the parent entity'),
            0                   => __('No'),
            1                   => __('Yes'),
        ];
    }


    /**
     * get value for suppliers_as_private
     *
     * @since 9.5
     *
     * @return array
     **/
    public static function getSuppliersAsPrivateValues()
    {

        return [
            self::CONFIG_PARENT => __('Inheritance of the parent entity'),
            0                   => __('No'),
            1                   => __('Yes'),
        ];
    }

    /**
     * Get values for anonymize_support_agents
     *
     * @since 9.5
     *
     * @return array
     **/
    public static function getAnonymizeSupportAgentsValues()
    {

        return [
            self::CONFIG_PARENT => __('Inheritance of the parent entity'),
            self::ANONYMIZE_DISABLED => __('Disabled'),
            self::ANONYMIZE_USE_GENERIC => __("Replace the agent and group name with a generic name"),
            self::ANONYMIZE_USE_NICKNAME => __("Replace the agent and group name with a customisable nickname"),
            self::ANONYMIZE_USE_GENERIC_USER => __("Replace the agent's name with a generic name"),
            self::ANONYMIZE_USE_NICKNAME_USER => __("Replace the agent's name with a customisable nickname"),
            self::ANONYMIZE_USE_GENERIC_GROUP => __("Replace the group's name with a generic name"),
        ];
    }

    /**
     * @since 0.84
     *
     * @param $options array
     **/
    public static function dropdownAutoAssignMode(array $options)
    {

        $p['name']    = 'auto_assign_mode';
        $p['value']   = 0;
        $p['display'] = true;

        if (count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        $tab = self::getAutoAssignMode();
        return Dropdown::showFromArray($p['name'], $tab, $p);
    }


    /**
     * @since 0.84 (before in entitydata.class)
     *
     * @param $field
     * @param $values
     * @param $options   array
     **/
    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'use_licenses_alert':
            case 'use_certificates_alert':
            case 'use_contracts_alert':
            case 'use_domains_alert':
            case 'use_infocoms_alert':
            case 'is_notif_enable_default':
                if ($values[$field] == self::CONFIG_PARENT) {
                    return __('Inheritance of the parent entity');
                }
                return Dropdown::getYesNo($values[$field]);

            case 'use_reservations_alert':
                switch ($values[$field]) {
                    case self::CONFIG_PARENT:
                        return __('Inheritance of the parent entity');

                    case 0:
                        return __('Never');
                }
                return sprintf(_n('%d hour', '%d hours', $values[$field]), $values[$field]);

            case 'default_cartridges_alarm_threshold':
            case 'default_consumables_alarm_threshold':
                switch ($values[$field]) {
                    case self::CONFIG_PARENT:
                        return __('Inheritance of the parent entity');

                    case 0:
                        return __('Never');
                }
                return $values[$field];

            case 'send_contracts_alert_before_delay':
            case 'send_infocoms_alert_before_delay':
            case 'send_licenses_alert_before_delay':
            case 'send_certificates_alert_before_delay':
            case 'send_domains_alert_close_expiries_delay':
            case 'send_domains_alert_expired_delay':
                switch ($values[$field]) {
                    case self::CONFIG_PARENT:
                        return __('Inheritance of the parent entity');

                    case 0:
                        return __('No');
                }
                return sprintf(_n('%d day', '%d days', $values[$field]), $values[$field]);

            case 'cartridges_alert_repeat':
            case 'consumables_alert_repeat':
                switch ($values[$field]) {
                    case self::CONFIG_PARENT:
                        return __('Inheritance of the parent entity');

                    case self::CONFIG_NEVER:
                    case 0: // For compatibility issue
                        return __('Never');

                    case DAY_TIMESTAMP:
                        return __('Each day');

                    case WEEK_TIMESTAMP:
                        return __('Each week');

                    case MONTH_TIMESTAMP:
                        return __('Each month');

                    default:
                       // Display value if not defined
                        return $values[$field];
                }
                break;

            case 'notclosed_delay':   // 0 means never
                switch ($values[$field]) {
                    case self::CONFIG_PARENT:
                        return __('Inheritance of the parent entity');

                    case 0:
                        return __('Never');
                }
                return sprintf(_n('%d day', '%d days', $values[$field]), $values[$field]);

            case 'auto_assign_mode':
                return self::getAutoAssignMode($values[$field]);

            case 'tickettype':
                if ($values[$field] == self::CONFIG_PARENT) {
                    return __('Inheritance of the parent entity');
                }
                return Ticket::getTicketTypeName($values[$field]);

            case 'autofill_buy_date':
            case 'autofill_order_date':
            case 'autofill_delivery_date':
            case 'autofill_use_date':
            case 'autofill_warranty_date':
            case 'autofill_decommission_date':
                switch ($values[$field]) {
                    case self::CONFIG_PARENT:
                        return __('Inheritance of the parent entity');

                    case Infocom::COPY_WARRANTY_DATE:
                        return __('Copy the start date of warranty');

                    case Infocom::COPY_BUY_DATE:
                        return __('Copy the date of purchase');

                    case Infocom::COPY_ORDER_DATE:
                        return __('Copy the order date');

                    case Infocom::COPY_DELIVERY_DATE:
                        return __('Copy the delivery date');

                    default:
                        if (strstr($values[$field], '_')) {
                            list($type,$sid) = explode('_', $values[$field], 2);
                            if ($type == Infocom::ON_STATUS_CHANGE) {
                                       // TRANS %s is the name of the state
                                return sprintf(
                                    __('Fill when shifting to state %s'),
                                    Dropdown::getDropdownName('glpi_states', $sid)
                                );
                            }
                        }
                }
                return __('No autofill');

            case 'inquest_config':
                if ($values[$field] == self::CONFIG_PARENT) {
                    return __('Inheritance of the parent entity');
                }
                return TicketSatisfaction::getTypeInquestName($values[$field]);

            case 'default_contract_alert':
                return Contract::getAlertName($values[$field]);

            case 'default_infocom_alert':
                return Infocom::getAlertName($values[$field]);

            case 'entities_id_software':
                $strategy = $values['entities_strategy_software'] ?? $values[$field];
                if ($strategy == self::CONFIG_NEVER) {
                    return __('No change of entity');
                }
                if ($strategy == self::CONFIG_PARENT) {
                    return __('Inheritance of the parent entity');
                }
                return Dropdown::getDropdownName('glpi_entities', $values[$field]);

            case 'tickettemplates_id':
                $strategy = $values['tickettemplates_strategy'] ?? $values[$field];
                if ($strategy == self::CONFIG_PARENT) {
                    return __('Inheritance of the parent entity');
                }
                return Dropdown::getDropdownName(TicketTemplate::getTable(), $values[$field]);

            case 'calendars_id':
                $strategy = $values['calendars_strategy'] ?? $values[$field];
                if ($strategy == self::CONFIG_PARENT) {
                    return __('Inheritance of the parent entity');
                } elseif ($values[$field] == 0) {
                    return __('24/7');
                }
                return Dropdown::getDropdownName('glpi_calendars', $values[$field]);

            case 'transfers_id':
                $strategy = $values['transfers_strategy'] ?? $values[$field];
                if ($strategy == self::CONFIG_NEVER) {
                    return __('No automatic transfer');
                }
                if ($strategy == self::CONFIG_PARENT) {
                    return __('Inheritance of the parent entity');
                } elseif ($values[$field] == 0) {
                    return __('No automatic transfer');
                }
                return Dropdown::getDropdownName('glpi_transfers', $values[$field]);

            case 'contracts_id_default':
                $strategy = $values['contracts_strategy_default'] ?? $values[$field];
                if ($strategy === self::CONFIG_PARENT) {
                    return __('Inheritance of the parent entity');
                }
                if ($strategy === self::CONFIG_AUTO) {
                    return __('Contract in ticket entity');
                }

                return Dropdown::getDropdownName(Contract::getTable(), $values[$field]);
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }


    /**
     * @since 0.84
     *
     * @param $field
     * @param $name               (default '')
     * @param $values             (default '')
     * @param $options      array
     **/
    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;
        switch ($field) {
            case 'use_licenses_alert':
            case 'use_certificates_alert':
            case 'use_contracts_alert':
            case 'use_infocoms_alert':
                $options['name']  = $name;
                $options['value'] = $values[$field];
                return Alert::dropdownYesNo($options);

            case 'cartridges_alert_repeat':
            case 'consumables_alert_repeat':
                $options['name']  = $name;
                $options['value'] = $values[$field];
                return Alert::dropdown($options);

            case 'send_contracts_alert_before_delay':
            case 'send_infocoms_alert_before_delay':
            case 'send_licenses_alert_before_delay':
            case 'send_certificates_alert_before_delay':
                $options['unit']         = 'day';
                $options['never_string'] = __('No');
                return Alert::dropdownIntegerNever($name, $values[$field], $options);

            case 'use_reservations_alert':
                $options['unit']  = 'hour';
                return Alert::dropdownIntegerNever($name, $values[$field], $options);

            case 'notclosed_delay':
                $options['unit']  = 'hour';
                return Alert::dropdownIntegerNever($name, $values[$field], $options);

            case 'auto_assign_mode':
                $options['name']  = $name;
                $options['value'] = $values[$field];

                return self::dropdownAutoAssignMode($options);

            case 'tickettype':
                $options['value'] = $values[$field];
                $options['toadd'] = [self::CONFIG_PARENT => __('Inheritance of the parent entity')];
                return Ticket::dropdownType($name, $options);

            case 'autofill_buy_date':
            case 'autofill_order_date':
            case 'autofill_delivery_date':
            case 'autofill_use_date':
            case 'autofill_decommission_date':
                $tab[0]                   = __('No autofill');
                $tab[self::CONFIG_PARENT] = __('Inheritance of the parent entity');
                $states = getAllDataFromTable('glpi_states');
                foreach ($states as $state) {
                    $tab[Infocom::ON_STATUS_CHANGE . '_' . $state['id']]
                           //TRANS: %s is the name of the state
                    = sprintf(__('Fill when shifting to state %s'), $state['name']);
                }
                $tab[Infocom::COPY_WARRANTY_DATE] = __('Copy the start date of warranty');
                if ($field != 'autofill_buy_date') {
                    $tab[Infocom::COPY_BUY_DATE] = __('Copy the date of purchase');
                    if ($field != 'autofill_order_date') {
                        $tab[Infocom::COPY_ORDER_DATE] = __('Copy the order date');
                        if ($field != 'autofill_delivery_date') {
                             $options[Infocom::COPY_DELIVERY_DATE] = __('Copy the delivery date');
                        }
                    }
                }
                $options['value'] = $values[$field];
                return Dropdown::showFromArray($name, $tab, $options);

            case 'autofill_warranty_date':
                $tab = [0                           => __('No autofill'),
                    Infocom::COPY_BUY_DATE      => __('Copy the date of purchase'),
                    Infocom::COPY_ORDER_DATE    => __('Copy the order date'),
                    Infocom::COPY_DELIVERY_DATE => __('Copy the delivery date'),
                    self::CONFIG_PARENT         => __('Inheritance of the parent entity')
                ];
                $options['value'] = $values[$field];
                return Dropdown::showFromArray($name, $tab, $options);

            case 'inquest_config':
                $typeinquest = [self::CONFIG_PARENT  => __('Inheritance of the parent entity'),
                    1                    => __('Internal survey'),
                    2                    => __('External survey')
                ];
                $options['value'] = $values[$field];
                return Dropdown::showFromArray($name, $typeinquest, $options);

            case 'default_contract_alert':
                $options['name']  = $name;
                $options['value'] = $values[$field];
                return Contract::dropdownAlert($options);

            case 'default_infocom_alert':
                $options['name']  = $name;
                $options['value'] = $values[$field];
                return Infocom::dropdownAlert($options);

            case 'entities_id_software':
                $options['toadd'] = [self::CONFIG_NEVER => __('No change of entity')]; // Keep software in PC entity
                $options['toadd'][self::CONFIG_PARENT] = __('Inheritance of the parent entity');

                return self::dropdown($options);
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }


    public function getRights($interface = 'central')
    {

        $values = parent::getRights();
        $values[self::READHELPDESK]   = ['short' => __('Read parameters'),
            'long'  => __('Read helpdesk parameters')
        ];
        $values[self::UPDATEHELPDESK] = ['short' => __('Update parameters'),
            'long'  => __('Update helpdesk parameters')
        ];

        return $values;
    }

    public function displaySpecificTypeField($ID, $field = [], array $options = [])
    {
        switch ($field['type']) {
            case 'setlocation':
                $this->showMap();
                break;
            default:
                throw new \RuntimeException("Unknown {$field['type']}");
        }
    }

    public static function inheritedValue($value = "", bool $inline = false, bool $display = true): string
    {
        if (trim($value) == "") {
            return "";
        }

        $out = "<div class='badge bg-azure-lt m-1 py-3 " . ($inline ? "inline" : "") . "'
                   title='" . __("Value inherited from a parent entity") . "'
                   data-bs-toggle='tooltip'>
         <i class='fas fa-level-down-alt me-1'></i>
         $value
      </div>";

        if ($display) {
            echo $out;
            return "";
        }

        return $out;
    }

    public static function getIcon()
    {
        return "ti ti-stack";
    }

    /**
     * Get values for contracts_id_default field
     *
     * @since 10.0.0
     *
     * @return array
     *
     * @FIXME Remove this method in GLPI 10.1.
     */
    public static function getDefaultContractValues($entities_id): array
    {
        $values = [
            self::CONFIG_PARENT => __('Inheritance of the parent entity'),
            -1 => __('First found valid contract in ticket entity'),
        ];

        $contract = new Contract();
        $criteria = getEntitiesRestrictCriteria('', '', $entities_id, true);
        $criteria['is_deleted'] = 0;
        $criteria['is_template'] = 0;
        $criteria[] = Contract::getExpiredCriteria();
        $contracts = $contract->find($criteria);

        foreach ($contracts as $contract) {
            $values[$contract['id']] = $contract['name'];
        }

        return $values;
    }

    public static function getAnonymizeConfig(?int $entities_id = null)
    {
        if ($entities_id === null) {
            $entities_id = Session::getActiveEntity();
        }
        return Entity::getUsedConfig('anonymize_support_agents', $entities_id);
    }

    public static function getDefaultContract(int $entities_id): int
    {
        $entity_default_contract_strategy = self::getUsedConfig('contracts_strategy_default', $entities_id);

        if ($entity_default_contract_strategy == self::CONFIG_AUTO) {
            // Contract in current entity
            $contract = new Contract();
            $criteria = [
                'entities_id' => $entities_id,
                'is_deleted'  => 0,
                'is_template' => 0,
            ];
            $criteria[] = Contract::getExpiredCriteria();
            $contracts = $contract->find($criteria);

            if ($contracts) {
               // Return first contract found
                return current($contracts)['id'];
            } else {
               // No contract found for this entity
                return 0;
            }
        }

        return self::getUsedConfig('contracts_strategy_default', $entities_id, 'contracts_id_default', 0);
    }

    /**
     * Return HTML code for entity badge showing its completename.
     *
     * @param string $entity_string
     *
     * @return string
     */
    public static function badgeCompletename(string $entity_string = "", ?string $title = null): string
    {
        // `completename` is expected to be received as it is stored in DB,
        // meaning that `>` separator is not encoded, but `<`, `>` and `&` from self or parent names are encoded.
        $names  = explode(' > ', trim($entity_string));

        // Convert the whole completename into decoded HTML.
        foreach ($names as &$name) {
            $name = Sanitizer::decodeHtmlSpecialChars($name);
        }

        // Construct HTML with special chars encoded.
        if ($title === null) {
            $title = htmlspecialchars(implode(' > ', $names));
        }
        $breadcrumbs = implode(
            '<i class="fas fa-caret-right mx-1"></i>',
            array_map(
                function (string $name): string {
                    return '<span class="text-nowrap">' . htmlspecialchars($name) . '</span>';
                },
                $names
            )
        );


        return '<span class="glpi-badge" title="' . $title . '">' . $breadcrumbs . "</span>";
    }

    /**
     * Return HTML code for entity badge showing its completename.
     *
     * @param int $entity_id
     *
     * @return string|null
     */
    public static function badgeCompletenameById(int $entity_id): ?string
    {
        $entity = new self();
        if ($entity->getFromDB($entity_id)) {
            return self::badgeCompletename($entity->fields['completename']);
        }
        return null;
    }

    /**
     * Return HTML code for entity badge showing its completename with last entity as HTML link.
     *
     * @param object $entity
     *
     * @return string
     */
    public static function badgeCompletenameLink(object $entity): string
    {
        // `completename` is expected to be received as it is stored in DB,
        // meaning that `>` separator is not encoded, but `<`, `>` and `&` from self or parent names are encoded.
        $names = explode(' > ', trim($entity->fields['completename']));
        // Convert the whole completename into decoded HTML.
        foreach ($names as &$name) {
            $name = Sanitizer::decodeHtmlSpecialChars($name);
        }

        // Construct HTML with special chars encoded.
        $title       = htmlspecialchars(implode(' > ', $names));
        $last_name   = array_pop($names);
        $breadcrumbs = implode(
            '<i class="fas fa-caret-right mx-1"></i>',
            array_map(
                function (string $name): string {
                    return '<span class="text-nowrap text-muted">' . htmlspecialchars($name) . '</span>';
                },
                $names
            )
        );

        $last_url  = '<i class="fas fa-caret-right mx-1"></i>' . '<a href="' . $entity->getLinkURL() . '" title="' . $title . '">' . htmlspecialchars($last_name) . '</a>';

        return '<span class="glpi-badge" title="' . $title . '">' . $breadcrumbs . $last_url . '</span>';
    }

    /**
     * Return HTML code for entity badge showing its completename with last entity as HTML link.
     *
     * @param int $entity_id
     *
     * @return string|null
     */
    public static function badgeCompletenameLinkById(int $entity_id): ?string
    {
        $entity = new self();
        if ($entity->getFromDB($entity_id)) {
            return self::badgeCompletenameLink($entity);
        }
        return null;
    }

    private static function getEntityTree(int $entities_id_root): array
    {
        /** @var \DBmysql $DB */
        global $DB;

        $sons = getSonsOf('glpi_entities', $entities_id_root);
        if (!isset($sons[$entities_id_root])) {
            $sons[$entities_id_root] = $entities_id_root;
        }

        $iterator = $DB->request([
            'SELECT' => ['id', 'name', 'entities_id'],
            'FROM'   => 'glpi_entities',
            'WHERE'  => ['entities_id' => $sons],
            'ORDER'  => 'name'
        ]);

        $grouped = [];
        foreach ($iterator as $row) {
            if (!array_key_exists($row['entities_id'], $grouped)) {
                $grouped[$row['entities_id']] = [];
            }
            $grouped[$row['entities_id']][] = [
                'id'   => $row['id'],
                'name' => $row['name']
            ];
        }

        \Glpi\Debug\Profiler::getInstance()->start('constructTreeFromList');
        $fn_construct_tree_from_list = static function (array $list, int $root) use (&$fn_construct_tree_from_list): array {
            $tree = [];
            if (array_key_exists($root, $list)) {
                foreach ($list[$root] as $data) {
                    $tree[$data['id']] = [
                        'name' => $data['name'],
                        'tree' => $fn_construct_tree_from_list($list, $data['id']),
                    ];
                }
            }
            return $tree;
        };

        $constructed = $fn_construct_tree_from_list($grouped, $entities_id_root);
        \Glpi\Debug\Profiler::getInstance()->stop('constructTreeFromList');
        return [
            $entities_id_root => [
                'name' => Dropdown::getDropdownName('glpi_entities', $entities_id_root),
                'tree' => $constructed,
            ],
        ];
    }

    public static function getEntitySelectorTree(): array
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $base_path = $CFG_GLPI['root_doc'] . "/front/central.php";
        if (Session::getCurrentInterface() == 'helpdesk') {
            $base_path = $CFG_GLPI["root_doc"] . "/front/helpdesk.public.php";
        }

        $ancestors = getAncestorsOf('glpi_entities', $_SESSION['glpiactive_entity']);

        \Glpi\Debug\Profiler::getInstance()->start('Generate entity tree');
        $entitiestree = [];
        foreach ($_SESSION['glpiactiveprofile']['entities'] as $default_entity) {
            $default_entity_id = $default_entity['id'];
            $entitytree = $default_entity['is_recursive'] ? self::getEntityTree($default_entity_id) : [$default_entity['id'] => $default_entity];

            $adapt_tree = static function (&$entities) use (&$adapt_tree, $base_path) {
                foreach ($entities as $entities_id => &$entity) {
                    $entity['key']   = $entities_id;

                    $title = "<a href='$base_path?active_entity={$entities_id}'>{$entity['name']}</a>";
                    $entity['title'] = $title;
                    unset($entity['name']);

                    if (isset($entity['tree']) && count($entity['tree']) > 0) {
                        $entity['folder'] = true;

                        $entity['title'] .= "<a href='$base_path?active_entity={$entities_id}&is_recursive=1'>
            <i class='fas fa-angle-double-down ms-1' data-bs-toggle='tooltip' data-bs-placement='right' title='" . __('+ sub-entities') . "'></i>
            </a>";

                        $children = $adapt_tree($entity['tree']);
                        $entity['children'] = array_values($children);
                    }

                    unset($entity['tree']);
                }

                return $entities;
            };
            $adapt_tree($entitytree);

            $entitiestree = array_merge($entitiestree, $entitytree);
        }
        \Glpi\Debug\Profiler::getInstance()->stop('Generate entity tree');

        /* scans the tree to select the active entity */
        $select_tree = static function (&$entities) use (&$select_tree, $ancestors) {
            foreach ($entities as &$entity) {
                if (isset($ancestors[$entity['key']])) {
                    $entity['expanded'] = 'true';
                }
                if ($entity['key'] == $_SESSION['glpiactive_entity']) {
                    $entity['selected'] = 'true';
                }
                if (isset($entity['children'])) {
                    $select_tree($entity['children']);
                }
            }
        };
        $select_tree($entitiestree);

        return $entitiestree;
    }
}
