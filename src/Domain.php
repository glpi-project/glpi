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

/// Class Domain
class Domain extends CommonDBTM
{
    use Glpi\Features\Clonable;

    public static $rightname = 'domain';
    protected static $forward_entity_to = ['DomainRecord'];

    public $can_be_translated = false;

    public $dohistory        = true;
    protected $usenotepadrights = true;
    protected $usenotepad       = true;
    public static $tags             = '[DOMAIN_NAME]';

    public function getCloneRelations(): array
    {
        return [
            DomainRecord::class,
            Domain_Item::class,
            Infocom::class,
            Item_Ticket::class,
            Item_Problem::class,
            Change_Item::class,
            Contract_Item::class,
            Document_Item::class,
            Notepad::class,
        ];
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Domain', 'Domains', $nb);
    }

    public function cleanDBonPurge()
    {
        global $DB;

        $ditem = new Domain_Item();
        $ditem->deleteByCriteria(['domains_id' => $this->fields['id']]);

        $record = new DomainRecord();

        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => $record->getTable(),
            'WHERE'  => [
                'domains_id'   => $this->fields['id']
            ]
        ]);
        foreach ($iterator as $row) {
            $row['_linked_purge'] = 1;//flag call when we remove a record from a domain
            $record->delete($row, true);
        }
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => self::getTypeName(2)
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => $this->getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
            'itemlink_type'      => $this->getType(),
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => 'glpi_domaintypes',
            'field'              => 'name',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'linkfield'          => 'users_id_tech',
            'name'               => __('Technician in charge'),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'date_domaincreation',
            'name'               => __('Registration date'),
            'datatype'           => 'date'
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => $this->getTable(),
            'field'              => 'date_expiration',
            'name'               => __('Expiration date'),
            'datatype'           => 'date'
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => $this->getTable(),
            'field'              => 'comment',
            'name'               => __('Comments'),
            'datatype'           => 'text'
        ];

        $tab[] = [
            'id'                 => '8',
            'table'              => 'glpi_domains_items',
            'field'              => 'items_id',
            'nosearch'           => true,
            'massiveaction'      => false,
            'name'               => _n('Associated item', 'Associated items', Session::getPluralNumber()),
            'forcegroupby'       => true,
            'joinparams'         => [
                'jointype'           => 'child'
            ]
        ];

        $tab[] = [
            'id'                 => '10',
            'table'              => 'glpi_groups',
            'field'              => 'name',
            'linkfield'          => 'groups_id_tech',
            'name'               => __('Group in charge'),
            'condition'          => ['is_assign' => 1],
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '12',
            'table'              => $this->getTable(),
            'field'              => 'date_mod',
            'massiveaction'      => false,
            'name'               => __('Last update'),
            'datatype'           => 'datetime'
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
            'id'                 => '18',
            'table'              => $this->getTable(),
            'field'              => 'is_recursive',
            'name'               => __('Child entities'),
            'datatype'           => 'bool'
        ];

        $tab[] = [
            'id'                 => '30',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'datatype'           => 'number'
        ];

        $tab[] = [
            'id'                 => '31',
            'table'              => $this->getTable(),
            'field'              => 'is_active',
            'name'               => __('Active'),
            'datatype'           => 'bool'
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '81',
            'table'              => 'glpi_entities',
            'field'              => 'entities_id',
            'name'               => __('Entity-ID')
        ];

        return $tab;
    }

    public static function rawSearchOptionsToAdd($itemtype = null)
    {
        $tab = [];

        if (in_array($itemtype, Domain::getTypes(true))) {
            if (Session::haveRight("domain", READ)) {
                $tab[] = [
                    'id'                 => 'domain',
                    'name'               => self::getTypeName(Session::getPluralNumber())
                ];

                $tab[] = [
                    'id'                 => '205',
                    'table'              => Domain::getTable(),
                    'field'              => 'name',
                    'name'               => __('Name'),
                    'forcegroupby'       => true,
                    'datatype'           => 'itemlink',
                    'itemlink_type'      => 'Domain',
                    'massiveaction'      => false,
                    'joinparams'         => [
                        'beforejoin' => [
                            'table'      => Domain_Item::getTable(),
                            'joinparams' => ['jointype' => 'itemtype_item']
                        ]
                    ]
                ];

                $tab[] = [
                    'id'                 => '206',
                    'table'              => DomainType::getTable(),
                    'field'              => 'name',
                    'name'               => DomainType::getTypeName(1),
                    'forcegroupby'       => true,
                    'datatype'           => 'dropdown',
                    'massiveaction'      => false,
                    'joinparams'         => [
                        'beforejoin' => [
                            'table'      => Domain::getTable(),
                            'joinparams'         => [
                                'beforejoin' => [
                                    'table'      => Domain_Item::getTable(),
                                    'joinparams' => ['jointype' => 'itemtype_item']
                                ]
                            ]
                        ]
                    ]
                ];
            }
        }

        return $tab;
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addImpactTab($ong, $options);
        $this->addStandardTab('DomainRecord', $ong, $options);
        $this->addStandardTab('Domain_Item', $ong, $options);
        $this->addStandardTab('Infocom', $ong, $options);
        $this->addStandardTab('Ticket', $ong, $options);
        $this->addStandardTab('Item_Problem', $ong, $options);
        $this->addStandardTab('Change_Item', $ong, $options);
        $this->addStandardTab('Contract_Item', $ong, $options);
        $this->addStandardTab('Document_Item', $ong, $options);
        $this->addStandardTab('Certificate_Item', $ong, $options);
        $this->addStandardTab('ManualLink', $ong, $options);
        $this->addStandardTab('Notepad', $ong, $options);
        $this->addStandardTab('Log', $ong, $options);

        return $ong;
    }

    private function prepareInput($input)
    {
        if (isset($input['date_creation']) && empty($input['date_creation'])) {
            $input['date_creation'] = 'NULL';
        }
        if (isset($input['date_expiration']) && empty($input['date_expiration'])) {
            $input['date_expiration'] = 'NULL';
        }

        return $input;
    }

    public function prepareInputForAdd($input)
    {
        return $this->prepareInput($input);
    }

    public function prepareInputForUpdate($input)
    {
        return $this->prepareInput($input);
    }


    /**
     * Make a select box for link domains
     *
     * Parameters which could be used in options array :
     *    - name : string / name of the select (default is documents_id)
     *    - entity : integer or array / restrict to a defined entity or array of entities
     *                   (default -1 : no restriction)
     *    - used : array / Already used items ID: not to display in dropdown (default empty)
     *
     * @param $options array of possible options
     *
     * @return void
     * */
    public static function dropdownDomains($options = [])
    {
        global $DB;

        $p = [
            'name'    => 'domains_id',
            'entity'  => '',
            'used'    => [],
            'display' => true,
        ];

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        $rand = mt_rand();

        $where = [
            'glpi_domains.is_deleted'  => 0
        ] + getEntitiesRestrictCriteria(self::getTable(), '', $p['entity'], true);

        if (count($p['used'])) {
            $where['NOT'] = ['id' => $p['used']];
        }

        $iterator = $DB->request([
            'FROM'      => self::getTable(),
            'WHERE'     => $where
        ]);

        $values = [0 => Dropdown::EMPTY_VALUE];
        foreach ($iterator as $data) {
            $values[$data['id']] = $data['name'];
        }

        $out = Dropdown::showFromArray(
            'domains_id',
            $values,
            [
                'width'   => '30%',
                'rand'    => $rand,
                'display' => false
            ]
        );

        if ($p['display']) {
            echo $out;
            return $rand;
        }
        return $out;
    }

    public function getSpecificMassiveActions($checkitem = null)
    {
        $isadmin = static::canUpdate();
        $actions = parent::getSpecificMassiveActions($checkitem);

        if ($_SESSION['glpiactiveprofile']['interface'] == 'central') {
            if ($isadmin) {
                $actions['Domain' . MassiveAction::CLASS_ACTION_SEPARATOR . 'install']   = _x('button', 'Associate');
                $actions['Domain' . MassiveAction::CLASS_ACTION_SEPARATOR . 'uninstall'] = _x('button', 'Dissociate');
                $actions['Domain' . MassiveAction::CLASS_ACTION_SEPARATOR . 'duplicate']  = _x('button', 'Duplicate');
            }
        }
        return $actions;
    }

    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {

        switch ($ma->getAction()) {
            case 'add_item':
                self::dropdownDomains([]);
                echo "&nbsp;" .
                 Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
                return true;
            case "install":
                Dropdown::showSelectItemFromItemtypes([
                    'items_id_name' => 'item_item',
                    'itemtype_name' => 'typeitem',
                    'itemtypes'     => self::getTypes(true),
                    'checkright'    => true,
                ]);
                echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
                return true;
            break;
            case "uninstall":
                Dropdown::showSelectItemFromItemtypes([
                    'items_id_name' => 'item_item',
                    'itemtype_name' => 'typeitem',
                    'itemtypes'     => self::getTypes(true),
                    'checkright'    => true,
                ]);
                echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
                return true;
            break;
            case "duplicate":
                Dropdown::show('Entity');
                break;
        }
        return parent::showMassiveActionsSubForm($ma);
    }

    public static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item, array $ids)
    {
        $domain_item = new Domain_Item();

        switch ($ma->getAction()) {
            case "add_item":
                $input = $ma->getInput();
                foreach ($ids as $id) {
                    $input = ['domains_id' => $input['domains_id'],
                        'items_id'                  => $id,
                        'itemtype'                  => $item->getType()
                    ];
                    if ($domain_item->can(-1, UPDATE, $input)) {
                        if ($domain_item->add($input)) {
                             $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                        } else {
                             $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
                        }
                    } else {
                        $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
                    }
                }
                return;

            case 'install':
                $input = $ma->getInput();
                foreach ($ids as $key) {
                    if ($item->can($key, UPDATE)) {
                        $values = ['domains_id' => $key,
                            'items_id'                  => $input["item_item"],
                            'itemtype'                  => $input['typeitem']
                        ];
                        if ($domain_item->add($values)) {
                            $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                        } else {
                            $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                        }
                    } else {
                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_NORIGHT);
                        $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                    }
                }
                return;

            case 'uninstall':
                $input = $ma->getInput();
                foreach ($ids as $key) {
                    if ($domain_item->deleteItemByDomainsAndItem($key, $input['item_item'], $input['typeitem'])) {
                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                    } else {
                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                    }
                }
                return;

            case "duplicate":
                if ($item->getType() == 'Domain') {
                    $input     = $ma->getInput();
                    foreach (array_keys($ids) as $key) {
                        $item->getFromDB($key);
                        unset($item->fields["id"]);
                        $item->fields["name"]    = addslashes($item->fields["name"]);
                        $item->fields["comment"] = addslashes($item->fields["comment"]);
                        $item->fields["entities_id"] = $input['entities_id'];
                        if ($item->add($item->fields)) {
                            $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                        } else {
                            $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                        }
                    }
                }
                break;
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }

    public static function cronInfo($name)
    {
        switch ($name) {
            case 'DomainsAlert':
                return [
                    'description' => __('Expired or expiring domains')
                ];
            break;
        }
        return [];
    }

    /**
     * Criteria for expired domains
     *
     * @param integer $entities_id Entity ID
     *
     * @return array
     */
    public static function expiredDomainsCriteria($entities_id): array
    {
        global $DB;

        $delay = Entity::getUsedConfig('send_domains_alert_expired_delay', $entities_id);
        return [
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'NOT' => ['date_expiration' => null],
                'entities_id'  => $entities_id,
                'is_deleted'   => 0,
                new QueryExpression("DATEDIFF(CURDATE(), " . $DB->quoteName('date_expiration') . ") > $delay"),
                new QueryExpression("DATEDIFF(CURDATE(), " . $DB->quoteName('date_expiration') . ") > 0")
            ]
        ];
    }

    /**
     * Criteria for domains closed expiries
     *
     * @param integer $entities_id Entity ID
     *
     * @return array
     */
    public static function closeExpiriesDomainsCriteria($entities_id): array
    {
        global $DB;

        $delay = Entity::getUsedConfig('send_domains_alert_close_expiries_delay', $entities_id);
        return [
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'NOT' => ['date_expiration' => null],
                'entities_id'  => $entities_id,
                'is_deleted'   => 0,
                new QueryExpression("DATEDIFF(CURDATE(), " . $DB->quoteName('date_expiration') . ") > -$delay"),
                new QueryExpression("DATEDIFF(CURDATE(), " . $DB->quoteName('date_expiration') . ") < 0")
            ]
        ];
    }

    /**
     * Cron action on domains : ExpiredDomains or DomainsWhichExpire
     *
     * @param CronTask $task CronTask for log, if NULL display
     *
     *
     * @return int
     */
    public static function cronDomainsAlert($task = null)
    {
        global $DB, $CFG_GLPI;

        if (!$CFG_GLPI["use_notifications"]) {
            return 0; // Nothing to do
        }

        $errors = 0;
        $total = 0;

        foreach (array_keys(Entity::getEntitiesToNotify('use_domains_alert')) as $entity) {
            $events = [
                'DomainsWhichExpire' => [
                    'query'      => self::closeExpiriesDomainsCriteria($entity),
                    'alert_type' => Alert::NOTICE,
                ],
                'ExpiredDomains' => [
                    'query' => self::expiredDomainsCriteria($entity),
                    'alert_type' => Alert::END,
                ]
            ];

            foreach ($events as $event => $event_specs) {
                $query      = $event_specs['query'];
                $alert_type = $event_specs['alert_type'];

                $query['SELECT']    = ['glpi_domains.id'];
                $query['LEFT JOIN'] = [
                    'glpi_alerts' => [
                        'FKEY'   => [
                            'glpi_alerts'  => 'items_id',
                            'glpi_domains' => 'id',
                            [
                                'AND' => [
                                    'glpi_alerts.itemtype' => __CLASS__,
                                    'glpi_alerts.type'     => $alert_type,
                                ],
                            ],
                        ]
                    ]
                ];
                $query['WHERE'][]   = ['glpi_alerts.date' => null];

                $iterator = $DB->request($query);

                foreach ($iterator as $domain_data) {
                    $domain_id = $domain_data['id'];
                    $domain = new self();
                    if (!$domain->getFromDB($domain_id)) {
                        $errors++;
                        trigger_error(sprintf('Unable to load Domain "%s".', $domain_id), E_USER_WARNING);
                        continue;
                    }

                    if (NotificationEvent::raiseEvent($event, $domain)) {
                        $msg = sprintf(
                            __('%1$s: %2$s'),
                            Dropdown::getDropdownName('glpi_entities', $entity),
                            sprintf(
                                $event === 'DomainsWhichExpire' ? __('Domain %1$s expires on %2$s') : __('Domain %1$s expired on %2$s'),
                                $domain->fields['name'],
                                Html::convDate($domain->fields['date_expiration'])
                            )
                        );
                        if ($task) {
                            $task->log($msg);
                            $task->addVolume(1);
                        } else {
                            Session::addMessageAfterRedirect($msg);
                        }

                        // Add alert
                        $input = [
                            'type'     => $alert_type,
                            'itemtype' => __CLASS__,
                            'items_id' => $domain_id,
                        ];
                        $alert = new Alert();
                        $alert->deleteByCriteria($input, 1);
                        $alert->add($input);

                        $total++;
                    } else {
                        $errors++;

                        $msg = sprintf(
                            __('Domains alerts sending failed for entity %1$s'),
                            Dropdown::getDropdownName("glpi_entities", $entity)
                        );
                        if ($task) {
                            $task->log($msg);
                        } else {
                            Session::addMessageAfterRedirect($msg, false, ERROR);
                        }
                    }
                }
            }
        }

        return $errors > 0 ? -1 : ($total > 0 ? 1 : 0);
    }

    /**
     * Type than could be linked to a Rack
     *
     * @param $all boolean, all type, or only allowed ones
     *
     * @return array of types
     * */
    public static function getTypes($all = false)
    {
        global $CFG_GLPI;

        $types = $CFG_GLPI['domain_types'];
        if ($all) {
            return $types;
        }

       // Only allowed types
        foreach ($types as $key => $type) {
            if (!class_exists($type)) {
                continue;
            }

            $item = new $type();
            if (!$item->canView()) {
                unset($types[$key]);
            }
        }
        return $types;
    }

    public static function generateLinkContents($link, CommonDBTM $item)
    {
        if (strstr($link, "[DOMAIN]")) {
            $link = str_replace("[DOMAIN]", $item->getName(), $link);
            return [$link];
        }

        return parent::generateLinkContents($link, $item);
    }

    public static function getUsed(array $used, $domaintype)
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'id'              => $used,
                'domaintypes_id'  => $domaintype
            ]
        ]);

        $used = [];
        foreach ($iterator as $data) {
            $used[$data['id']] = $data['id'];
        }
        return $used;
    }

    public static function canManageRecords()
    {
        return static::canView() && count($_SESSION['glpiactiveprofile']['managed_domainrecordtypes'] ?? []) > 0;
    }

    public static function getAdditionalMenuLinks()
    {
        $links = [];
        if (static::canManageRecords()) {
            $rooms = "<i class='fa fa-clipboard-list pointer' title=\"" . DomainRecord::getTypeName(Session::getPluralNumber()) . "\"></i>
            <span class='d-none d-xxl-block ps-1'>
               " . DomainRecord::getTypeName(Session::getPluralNumber()) . "
            </span>";
            $links[$rooms] = DomainRecord::getSearchURL(false);
        }
        if (count($links)) {
            return $links;
        }
        return false;
    }

    public static function getAdditionalMenuOptions()
    {
        if (static::canManageRecords()) {
            return [
                'domainrecord' => [
                    'icon'  => DomainRecord::getIcon(),
                    'title' => DomainRecord::getTypeName(Session::getPluralNumber()),
                    'page'  => DomainRecord::getSearchURL(false),
                    'links' => [
                        'add'    => '/front/domainrecord.form.php',
                        'search' => '/front/domainrecord.php',
                    ]
                ]
            ];
        }
        return false;
    }

    public function getCanonicalName()
    {
        return rtrim($this->fields['name'], '.') . '.';
    }

    public function post_getEmpty()
    {
        $this->fields['is_active'] = $this->fields['is_template'] ? 0 : 1;
    }

    public static function getIcon()
    {
        return "fas fa-globe-americas";
    }

    public function post_updateItem($history = 1)
    {
        $this->cleanAlerts([Alert::END, Alert::NOTICE]);
        parent::post_updateItem($history);
    }
}
