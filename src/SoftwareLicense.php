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
use Glpi\DBAL\QueryFunction;
use Glpi\Features\AssetImage;
use Glpi\Features\AssignableItem;
use Glpi\Features\AssignableItemInterface;
use Glpi\Features\Clonable;
use Glpi\Features\StateInterface;

/**
 * SoftwareLicense Class
 **/
class SoftwareLicense extends CommonTreeDropdown implements AssignableItemInterface, StateInterface
{
    use Clonable;
    use Glpi\Features\State;
    use AssetImage;
    use AssignableItem {
        prepareInputForAdd as prepareInputForAddAssignableItem;
        prepareInputForUpdate as prepareInputForUpdateAssignableItem;
        post_addItem as post_addItemAssignableItem;
        post_updateItem as post_updateItemAssignableItem;
    }

    /// TODO move to CommonDBChild ?
    // From CommonDBTM
    public $dohistory                   = true;

    protected static $forward_entity_to = ['Infocom'];

    public static $rightname                   = 'license';
    protected $usenotepad               = true;


    public function getCloneRelations(): array
    {
        return [
            Infocom::class,
            Contract_Item::class,
            Document_Item::class,
            KnowbaseItem_Item::class,
            Notepad::class,
            Certificate_Item::class,
        ];
    }

    public static function getTypeName($nb = 0)
    {
        return _n('License', 'Licenses', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['management', self::class];
    }

    public function pre_updateInDB()
    {
        // Clean end alert if expire is after old one
        if (
            isset($this->oldvalues['expire'])
            && ($this->oldvalues['expire'] < $this->fields['expire'])
        ) {
            $alert = new Alert();
            $alert->clear(static::class, $this->fields['id'], Alert::END);
        }
    }

    public function prepareInputForAdd($input)
    {
        $input = $this->prepareInputForAddAssignableItem($input);
        if ($input === false) {
            return false;
        }
        $input = parent::prepareInputForAdd($input);

        if (isset($input["id"]) && ($input["id"] > 0)) {
            $input["_oldID"] = $input["id"];
        }
        unset($input['id'], $input['withtemplate']);

        // Unset to set to default using mysql default value
        if (empty($input['expire'])) {
            unset($input['expire']);
        }

        if (!isset($input['number'])) {
            //number is not defined when creating a child licence; and it cannot be 0
            $input['number'] = 1;
        }

        $input = $this->managePictures($input);
        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        $input = $this->prepareInputForUpdateAssignableItem($input);
        if ($input === false) {
            return false;
        }

        // Update number : compute validity indicator
        if (isset($input['number'])) {
            $input['is_valid'] = self::computeValidityIndicator($input['id'], $input['number']);
        }

        $input = $this->managePictures($input);
        return $input;
    }

    /**
     * Compute licence validity indicator.
     *
     * @param integer $ID        ID of the licence
     * @param integer $number    licence count to check (default -1)
     *
     * @since 0.85
     *
     * @return int validity indicator
     **/
    public static function computeValidityIndicator($ID, $number = -1)
    {
        if (
            ($number >= 0)
            && ($number < Item_SoftwareLicense::countForLicense($ID, -1))
        ) {
            return 0;
        }
        // Default return 1
        return 1;
    }

    /**
     * Update validity indicator of a specific license
     * @param integer $ID ID of the licence
     *
     * @since 0.85
     *
     * @return void
     **/
    public static function updateValidityIndicator($ID)
    {
        $lic = new self();
        if ($lic->getFromDB($ID)) {
            $valid = self::computeValidityIndicator($ID, $lic->fields['number']);
            if ($valid !== $lic->fields['is_valid']) {
                $lic->update(['id'       => $ID,
                    'is_valid' => $valid,
                ]);
            }
        }
    }

    public function cleanDBonPurge()
    {
        $this->deleteChildrenAndRelationsFromDb(
            [
                Item_SoftwareLicense::class,
            ]
        );

        // Alert does not extends CommonDBConnexity
        $alert = new Alert();
        $alert->cleanDBonItemDelete(static::class, $this->fields['id']);
    }

    public function post_addItem()
    {
        $this->post_addItemAssignableItem();
        // Add infocoms if exists for the licence
        $infocoms = Infocom::getItemsAssociatedTo(static::class, $this->fields['id']);
        if (!empty($infocoms)) {
            $override_input['items_id'] = $this->getID();
            $infocoms[0]->clone($override_input);
        }
        Software::updateValidityIndicator($this->fields["softwares_id"]);
    }

    public function post_updateItem($history = true)
    {
        $this->post_updateItemAssignableItem($history);
        if (in_array("is_valid", $this->updates, true)) {
            Software::updateValidityIndicator($this->fields["softwares_id"]);
        }
    }

    public function post_deleteFromDB()
    {
        Software::updateValidityIndicator($this->fields["softwares_id"]);
    }

    public function getPreAdditionalInfosForName()
    {
        $soft = new Software();
        if ($soft->getFromDB($this->fields['softwares_id'])) {
            return $soft->getName();
        }
        return '';
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addImpactTab($ong, $options);
        $this->addStandardTab(SoftwareLicense::class, $ong, $options);
        $this->addStandardTab(Item_SoftwareLicense::class, $ong, $options);
        $this->addStandardTab(Infocom::class, $ong, $options);
        $this->addStandardTab(Contract_Item::class, $ong, $options);
        $this->addStandardTab(Document_Item::class, $ong, $options);
        $this->addStandardTab(KnowbaseItem_Item::class, $ong, $options);
        $this->addStandardTab(Item_Ticket::class, $ong, $options);
        $this->addStandardTab(Item_Problem::class, $ong, $options);
        $this->addStandardTab(Change_Item::class, $ong, $options);
        $this->addStandardTab(Notepad::class, $ong, $options);
        $this->addStandardTab(Certificate_Item::class, $ong, $options);
        $this->addStandardTab(Log::class, $ong, $options);
        return $ong;
    }

    public function showForm($ID, array $options = [])
    {
        $softwares_id = $options['softwares_id'] ?? -1;

        if ($ID < 0) {
            // Create item
            $this->fields['softwares_id'] = $softwares_id;
            $this->fields['number']       = 1;
            $soft                         = new Software();
            if (
                $soft->getFromDB($softwares_id)
                && in_array($_SESSION['glpiactive_entity'], getAncestorsOf(
                    'glpi_entities',
                    $soft->getEntityID()
                ))
            ) {
                $options['entities_id'] = $soft->getEntityID();
            }
        } elseif ($this->fields['number'] == 0) {
            //fix licenses stored with number = 0
            $this->fields['number'] = 1;
        }

        $this->initForm($ID, $options);
        TemplateRenderer::getInstance()->display('pages/management/softwarelicense.html.twig', [
            'item'   => $this,
            'params' => $options,
            'licences_assigned' => Item_SoftwareLicense::countForLicense($this->getID())
                + SoftwareLicense_User::countForLicense($this->getID()),
        ]);

        return true;
    }

    /**
     * Is the license may be recursive
     *
     * @return boolean
     **/
    public function maybeRecursive()
    {
        $soft = new Software();
        if (
            isset($this->fields["softwares_id"])
            && $soft->getFromDB($this->fields["softwares_id"])
        ) {
            return $soft->isRecursive();
        }

        return true;
    }

    /**
     * Is the license recursive ?
     *
     * @return boolean
     **/
    public function isRecursive()
    {
        $soft = new Software();
        if (
            isset($this->fields["softwares_id"])
            && $soft->getFromDB($this->fields["softwares_id"])
        ) {
            return $soft->isRecursive();
        }

        return false;
    }

    public function rawSearchOptions()
    {
        $tab = [];

        // Only use for History (not by search Engine)
        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics'),
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => static::getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
            'forcegroupby'       => true,
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => static::getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number',
            'forcegroupby'       => true,
        ];

        $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

        $tab[] = [
            'id'                 => '11',
            'table'              => static::getTable(),
            'field'              => 'serial',
            'name'               => __('Serial number'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => static::getTable(),
            'field'              => 'number',
            'name'               => __('Number'),
            'datatype'           => 'number',
            'max'                => 100,
            'toadd'              => [
                '-1'                 => 'Unlimited',
            ],
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => SoftwareLicenseType::getTable(),
            'field'              => 'name',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => SoftwareVersion::getTable(),
            'field'              => 'name',
            'linkfield'          => 'softwareversions_id_buy',
            'name'               => __('Purchase version'),
            'datatype'           => 'dropdown',
            'displaywith'        => [
                '0'                  => __('states_id'),
            ],
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => SoftwareVersion::getTable(),
            'field'              => 'name',
            'linkfield'          => 'softwareversions_id_use',
            'name'               => __('Version in use'),
            'datatype'           => 'dropdown',
            'displaywith'        => [
                '0'                  => __('states_id'),
            ],
        ];

        $tab[] = [
            'id'                 => '8',
            'table'              => static::getTable(),
            'field'              => 'expire',
            'name'               => __('Expiration'),
            'datatype'           => 'date',
        ];

        $tab[] = [
            'id'                 => '9',
            'table'              => static::getTable(),
            'field'              => 'is_valid',
            'name'               => __('Valid'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '10',
            'table'              => Software::getTable(),
            'field'              => 'name',
            'name'               => Software::getTypeName(1),
            'datatype'           => 'itemlink',
        ];

        $tab[] = [
            'id'                 => '168',
            'table'              => static::getTable(),
            'field'              => 'allow_overquota',
            'name'               => __('Allow Over-Quota'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '13',
            'table'              => static::getTable(),
            'field'              => 'completename',
            'name'               => __('Father'),
            'datatype'           => 'itemlink',
            'forcegroupby'       => true,
            'joinparams'        => ['condition' => [new QueryExpression('true')]], // Add virtual condition to relink table
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => static::getTable(),
            'field'              => 'comment',
            'name'               => _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '121',
            'table'              => $this->getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '24',
            'table'              => User::getTable(),
            'field'              => 'name',
            'linkfield'          => 'users_id_tech',
            'name'               => __('Technician in charge of the license'),
            'datatype'           => 'dropdown',
            'right'              => 'own_ticket',
        ];

        $tab[] = [
            'id'                 => '31',
            'table'              => State::getTable(),
            'field'              => 'completename',
            'name'               => __('Status'),
            'datatype'           => 'dropdown',
            'condition'          => $this->getStateVisibilityCriteria(),
        ];

        $tab[] = [
            'id'                 => '49',
            'table'              => Group::getTable(),
            'field'              => 'completename',
            'linkfield'          => 'groups_id',
            'name'               => __('Group in charge of the license'),
            'condition'          => ['is_assign' => 1],
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_groups_items',
                    'joinparams'         => [
                        'jointype'           => 'itemtype_item',
                        'condition'          => ['NEWTABLE.type' => Group_Item::GROUP_TYPE_TECH],
                    ],
                ],
            ],
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '61',
            'table'              => static::getTable(),
            'field'              => 'template_name',
            'name'               => __('Template name'),
            'datatype'           => 'text',
            'massiveaction'      => false,
            'nosearch'           => true,
            'nodisplay'          => true,
        ];

        $tab[] = [
            'id'                 => '70',
            'table'              => User::getTable(),
            'field'              => 'name',
            'name'               => User::getTypeName(1),
            'datatype'           => 'dropdown',
            'right'              => 'all',
        ];

        $tab[] = [
            'id'                 => '71',
            'table'              => Group::getTable(),
            'field'              => 'completename',
            'name'               => Group::getTypeName(1),
            'condition'          => ['is_itemgroup' => 1],
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_groups_items',
                    'joinparams'         => [
                        'jointype'           => 'itemtype_item',
                        'condition'          => ['NEWTABLE.type' => Group_Item::GROUP_TYPE_NORMAL],
                    ],
                ],
            ],
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => Entity::getTable(),
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '86',
            'table'              => static::getTable(),
            'field'              => 'is_recursive',
            'name'               => __('Child entities'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '162',
            'table'              => static::getTable(),
            'field'              => 'otherserial',
            'name'               => __('Inventory number'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '163',
            'table'              => static::getTable(),
            'field'              => 'id',
            'name'               => _x('quantity', 'Number of installations'),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'datatype'           => 'specific',
            'massiveaction'      => false,
            'computation'        => '('
                . '(SELECT COUNT(*) FROM ' . Item_SoftwareLicense::getTable()
                . ' WHERE softwarelicenses_id = TABLE.id AND is_deleted = 0)'
                . ' + '
                . '(SELECT COUNT(*) FROM ' . SoftwareLicense_User::getTable()
                . ' WHERE softwarelicenses_id = TABLE.id)'
                . ')',
            'computationgroupby' => true,
            'computationtype' => 'count',
        ];

        $tab[] = [
            'id'                 => '164',
            'table'              => Item_SoftwareLicense::getTable(),
            'field'              => 'id',
            'linkfield'          => 'id',
            'name'               => _x('quantity', 'Affected items'),
            'datatype'           => 'specific',
            'massiveaction'      => false,
            'nosearch'           => true,
            'nosort'             => true,
        ];

        // add objectlock search options
        $tab = array_merge($tab, ObjectLock::rawSearchOptionsToAdd(get_class($this)));
        $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

        return $tab;
    }

    public static function rawSearchOptionsToAdd()
    {
        $tab = [];
        $name = static::getTypeName(Session::getPluralNumber());

        if (!self::canView()) {
            return $tab;
        }

        $licjoinexpire = [
            'jointype'  => 'child',
            'condition' => array_merge(
                getEntitiesRestrictCriteria("NEWTABLE", '', '', true),
                [
                    'NEWTABLE.is_template' => 0,
                    'OR'  => [
                        ['NEWTABLE.expire' => null],
                        ['NEWTABLE.expire' => ['>', QueryFunction::now()]],
                    ],
                ]
            ),
        ];

        $tab[] = [
            'id'                 => 'license',
            'name'               => $name,
        ];

        $tab[] = [
            'id'                 => '160',
            'table'              => static::getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'dropdown',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => $licjoinexpire,
        ];

        $tab[] = [
            'id'                 => '161',
            'table'              => static::getTable(),
            'field'              => 'serial',
            'datatype'           => 'string',
            'name'               => __('Serial number'),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => $licjoinexpire,
        ];

        $tab[] = [
            'id'                 => '162',
            'table'              => static::getTable(),
            'field'              => 'otherserial',
            'datatype'           => 'string',
            'name'               => __('Inventory number'),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => $licjoinexpire,
        ];

        $tab[] = [
            'id'                 => '163',
            'table'              => static::getTable(),
            'field'              => 'number',
            'name'               => __('Number of licenses'),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'datatype'           => 'number',
            'massiveaction'      => false,
            'joinparams'         => $licjoinexpire,
        ];

        $tab[] = [
            'id'                 => '164',
            'table'              => static::getTable(),
            'field'              => 'name',
            'datatype'           => 'dropdown',
            'name'               => _n('Type', 'Types', 1),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_softwarelicenses',
                    'joinparams'         => $licjoinexpire,
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '165',
            'table'              => static::getTable(),
            'field'              => 'comment',
            'name'               => _n('Comment', 'Comments', Session::getPluralNumber()),
            'forcegroupby'       => true,
            'datatype'           => 'text',
            'massiveaction'      => false,
            'joinparams'         => $licjoinexpire,
        ];

        $tab[] = [
            'id'                 => '166',
            'table'              => static::getTable(),
            'field'              => 'expire',
            'name'               => __('Expiration'),
            'forcegroupby'       => true,
            'datatype'           => 'date',
            'emptylabel'         => __('Never expire'),
            'massiveaction'      => false,
            'joinparams'         => $licjoinexpire,
        ];

        $tab[] = [
            'id'                 => '167',
            'table'              => static::getTable(),
            'field'              => 'is_valid',
            'name'               => __('Valid'),
            'forcegroupby'       => true,
            'datatype'           => 'bool',
            'massiveaction'      => false,
            'joinparams'         => $licjoinexpire,
        ];

        return $tab;
    }

    /**
     * Give cron information
     *
     * @param $name : task's name
     *
     * @return array of information
     * @used-by CronTask
     **/
    public static function cronInfo($name)
    {
        return ['description' => __('Send alarms on expired licenses')];
    }

    /**
     * Cron action on software: alert on expired licences
     *
     * @param CronTask $task Task to log, if NULL display (default NULL)
     *
     * @return integer 0 : nothing to do 1 : done with success
     * @used-by CronTask
     **/
    public static function cronSoftware($task = null)
    {
        global $CFG_GLPI, $DB;

        $cron_status = 1;

        if (!$CFG_GLPI['use_notifications']) {
            return 0;
        }


        $tonotify = Entity::getEntitiesToNotify('use_licenses_alert');
        foreach (array_keys($tonotify) as $entity) {
            $before = Entity::getUsedConfig('send_licenses_alert_before_delay', $entity);
            // Check licenses
            $criteria = [
                'SELECT' => [
                    'glpi_softwarelicenses.*',
                    'glpi_softwares.name AS softname',
                ],
                'FROM'   => 'glpi_softwarelicenses',
                'INNER JOIN'   => [
                    'glpi_softwares'  => [
                        'ON'  => [
                            'glpi_softwarelicenses' => 'softwares_id',
                            'glpi_softwares'        => 'id',
                        ],
                    ],
                ],
                'LEFT JOIN'    => [
                    'glpi_alerts'  => [
                        'ON'  => [
                            'glpi_softwarelicenses' => 'id',
                            'glpi_alerts'           => 'items_id', [
                                'AND' => [
                                    'glpi_alerts.itemtype'  => 'SoftwareLicense',
                                ],
                            ],
                        ],
                    ],
                ],
                'WHERE'        => [
                    'glpi_alerts.date'   => null,
                    'NOT'                => ['glpi_softwarelicenses.expire' => null],
                    new QueryExpression(
                        QueryFunction::datediff(
                            expression1: $DB::quoteName('glpi_softwarelicenses.expire'),
                            expression2: QueryFunction::curdate()
                        )
                    ) . ' < ' . $before,
                    'glpi_softwares.is_template'  => 0,
                    'glpi_softwares.is_deleted'   => 0,
                    'glpi_softwares.entities_id'  => $entity,
                ],
            ];
            $iterator = $DB->request($criteria);

            $messages = [];
            $items    = [];

            foreach ($iterator as $license) {
                $name     = $license['softname'] . ' - ' . $license['name'] . ' - ' . $license['serial'];
                //TRANS: %1$s the license name, %2$s is the expiration date
                $messages[] = sprintf(
                    __('License %1$s expired on %2$s'),
                    Html::convDate($license["expire"]),
                    $name
                );
                $items[$license['id']] = $license;
            }

            if ($items !== []) {
                $alert                  = new Alert();
                $options['entities_id'] = $entity;
                $options['licenses']    = $items;

                if (NotificationEvent::raiseEvent('alert', new self(), $options)) {
                    $entityname = Dropdown::getDropdownName(Entity::getTable(), $entity);
                    if ($task) {
                        //TRANS: %1$s is the entity, %2$s is the message
                        $task->log(sprintf(__('%1$s: %2$s') . "\n", $entityname, implode("\n", $messages)));
                        $task->addVolume(1);
                    } else {
                        Session::addMessageAfterRedirect(sprintf(
                            __s('%1$s: %2$s'),
                            htmlescape($entityname),
                            implode('<br>', array_map('htmlescape', $messages))
                        ));
                    }

                    $input["type"]     = Alert::END;
                    $input["itemtype"] = 'SoftwareLicense';

                    // add alerts
                    foreach (array_keys($items) as $ID) {
                        $input["items_id"] = $ID;
                        $alert->add($input);
                        unset($alert->fields['id']);
                    }
                } else {
                    $entityname = Dropdown::getDropdownName(Entity::getTable(), $entity);
                    //TRANS: %s is entity name
                    $msg = sprintf(__('%1$s: %2$s'), $entityname, __('Send licenses alert failed'));
                    if ($task) {
                        $task->log($msg);
                    } else {
                        Session::addMessageAfterRedirect(htmlescape($msg), false, ERROR);
                    }
                }
            }
        }
        return $cron_status;
    }

    /**
     * Get number of bought licenses of a version
     *
     * @param integer $softwareversions_id   version ID
     * @param integer|''|array<int> $entity  Entity to search for licenses in (default = all active entities)
     *                               (default '')
     *
     * @return integer number of installations
     */
    public static function countForVersion($softwareversions_id, $entity = '')
    {
        global $DB;

        $result = $DB->request([
            'COUNT'  => 'cpt',
            'FROM'   => 'glpi_softwarelicenses',
            'WHERE'  => [
                'softwareversions_id_buy'  => $softwareversions_id,
            ] + getEntitiesRestrictCriteria('glpi_softwarelicenses', '', $entity),
        ])->current();

        return $result['cpt'];
    }

    /**
     * Get number of licenses of a software
     *
     * @param integer $softwares_id software ID
     *
     * @return integer number of licenses
     **/
    public static function countForSoftware($softwares_id)
    {
        global $DB;

        $iterator = $DB->request([
            'COUNT'  => 'cpt',
            'FROM'   => 'glpi_softwarelicenses',
            'WHERE'  => [
                'softwares_id' => $softwares_id,
                'is_template'  => 0,
                'number'       => -1,
            ] + getEntitiesRestrictCriteria('glpi_softwarelicenses', '', '', true),
        ]);

        if ($line = $iterator->current()) {
            if ($line['cpt'] > 0) {
                // At least 1 unlimited license, means unlimited
                return -1;
            }
        }

        $result = $DB->request([
            'SELECT' => ['SUM' => 'number AS numsum'],
            'FROM'   => 'glpi_softwarelicenses',
            'WHERE'  => [
                'softwares_id' => $softwares_id,
                'is_template'  => 0,
                'number'       => ['>', 0],
            ] + getEntitiesRestrictCriteria('glpi_softwarelicenses', '', '', true),
        ])->current();
        return $result['numsum'] ?: 0;
    }

    public function getSpecificMassiveActions($checkitem = null)
    {
        $actions = parent::getSpecificMassiveActions($checkitem);
        if (static::canUpdate()) {
            $prefix                       = 'Item_SoftwareLicense' . MassiveAction::CLASS_ACTION_SEPARATOR;
            $actions[$prefix . 'add_item']  = "<i class='ti ti-package'></i>" . _sx('button', 'Add an item');
        }

        return $actions;
    }

    public function getForbiddenSingleMassiveActions()
    {
        $forbidden = parent::getForbiddenSingleMassiveActions();

        $prefix = 'Item_SoftwareLicense' . MassiveAction::CLASS_ACTION_SEPARATOR;
        $add_item_action = $prefix . 'add_item';

        if (!static::canUpdate()) {
            $forbidden[] = $add_item_action;
            return $forbidden;
        }

        if (
            !$this->fields['allow_overquota']
            && $this->fields['number'] != -1
        ) {
            $number = Item_SoftwareLicense::countForLicense($this->getID());
            $number += SoftwareLicense_User::countForLicense($this->getID());

            if ($number >= $this->fields['number']) {
                $forbidden[] = $add_item_action;
            }
        }

        return $forbidden;
    }

    /**
     * Show Licenses of a software
     *
     * @param Software $software Software object
     *
     * @return void
     **/
    public static function showForSoftware(Software $software)
    {
        global $DB;

        $softwares_id  = $software->getField('id');
        $license       = new self();

        if (!$software->can($softwares_id, READ)) {
            return;
        }

        $columns = [
            'name'      => __('Name'),
            'entity'    => Entity::getTypeName(1),
            'serial'    => __('Serial number'),
            'number'    => _x('quantity', 'Number'),
            '_affected' => [
                'label' => __('Affected items'),
                'nosort' => true,
            ],
            'typename'  => _n('Type', 'Types', 1),
            'buyname'   => __('Purchase version'),
            'usename'   => __('Version in use'),
            'expire'    => __('Expiration'),
            'statename' => __('Status'),
        ];
        if (!$software->isRecursive()) {
            unset($columns['entity']);
        }

        $start = (int) ($_GET['start'] ?? 0);
        $order = ($_GET['order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

        if (!empty($_GET["sort"]) && isset($columns[$_GET["sort"]])) {
            $sort = $_GET["sort"];
        } else {
            $sort = 'name';
        }

        // Right type is enough. Can add a License on a software we have Read access
        $canedit             = Software::canUpdate();

        // Total Number of events
        $number = countElementsInTable(
            "glpi_softwarelicenses",
            [
                'glpi_softwarelicenses.softwares_id' => $softwares_id,
                'glpi_softwarelicenses.is_template'  => 0,
            ] + getEntitiesRestrictCriteria('glpi_softwarelicenses', '', '', true)
        );

        if ($canedit) {
            $twig_params = [
                'btn_msg' => _x('button', 'Add a license'),
                'softwares_id' => $softwares_id,
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                <div class="text-center mb-3">
                    <a class="btn btn-primary" href="{{ 'SoftwareLicense'|itemtype_form_path }}?softwares_id={{ softwares_id }}">{{ btn_msg }}</a>
                </div>
TWIG, $twig_params);
        }

        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_softwarelicenses.*',
                'buyvers.name AS buyname',
                'usevers.name AS usename',
                'glpi_entities.completename AS entity',
                'glpi_softwarelicensetypes.name AS typename',
                'glpi_states.name AS statename',
            ],
            'FROM'      => 'glpi_softwarelicenses',
            'LEFT JOIN' => [
                'glpi_softwareversions AS buyvers'  => [
                    'ON' => [
                        'glpi_softwarelicenses' => 'softwareversions_id_buy',
                        'buyvers'               => 'id',
                    ],
                ],
                'glpi_softwareversions AS usevers'  => [
                    'ON' => [
                        'glpi_softwarelicenses' => 'softwareversions_id_use',
                        'usevers'               => 'id',
                    ],
                ],
                'glpi_entities'                     => [
                    'ON' => [
                        'glpi_entities'         => 'id',
                        'glpi_softwarelicenses' => 'entities_id',
                    ],
                ],
                'glpi_softwarelicensetypes'         => [
                    'ON' => [
                        'glpi_softwarelicensetypes'   => 'id',
                        'glpi_softwarelicenses'       => 'softwarelicensetypes_id',
                    ],
                ],
                'glpi_states'                       => [
                    'ON' => [
                        'glpi_softwarelicenses' => 'states_id',
                        'glpi_states'           => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                'glpi_softwarelicenses.softwares_id'   => $softwares_id,
                'glpi_softwarelicenses.is_template'    => 0,
            ] + getEntitiesRestrictCriteria('glpi_softwarelicenses', '', '', true),
            'ORDERBY'   => "$sort $order",
            'START'     => $start,
            'LIMIT'     => (int) $_SESSION['glpilist_limit'],
        ]);

        $tot_assoc = 0;
        $tot       = 0;
        $entries   = [];
        foreach ($iterator as $data) {
            $license->getFromResultSet($data);
            $expired = true;
            if (
                is_null($data['expire'])
                || ($data['expire'] > date('Y-m-d'))
            ) {
                $expired = false;
            }
            $nb_assoc   = Item_SoftwareLicense::countForLicense($data['id']);
            $nb_assoc  += SoftwareLicense_User::countForLicense($data['id']);
            $tot_assoc += $nb_assoc;

            if ($data['number'] < 0) {
                // One unlimited license, total is unlimited
                $tot = -1;
            } elseif ($tot >= 0) {
                // Expired licenses do not count
                if (!$expired) {
                    // Not unlimited, add the current number
                    $tot += $data['number'];
                }
            }
            $entries[] = [
                'itemtype' => self::class,
                'id'       => $data['id'],
                'row_class' => $expired ? 'table-danger' : '',
                'name' => $license->getLink(['complete' => true, 'comments' => true]),
                'entity' => $data['entity'],
                'serial' => $data['serial'],
                'number' => ($data['number'] > 0) ? $data['number'] : __('Unlimited'),
                '_affected' => '<span class="' . ($data['is_valid'] ? 'text-green' : 'text-red') . '">' . $nb_assoc . '</span>',
                'typename' => $data['typename'],
                'buyname' => $data['buyname'],
                'usename' => $data['usename'],
                'expire' => $data['expire'],
                'statename' => $data['statename'],
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'start' => $start,
            'limit' => $_SESSION["glpilist_limit"],
            'sort' => $sort,
            'order' => $order,
            'nofilter' => true,
            'columns' => $columns,
            'formatters' => [
                'name' => 'raw_html',
                '_affected' => 'raw_html',
                'expire' => 'date',
            ],
            'footers' => [
                ['', __('Total'), (($tot > 0) ? $tot . "" : __('Unlimited')), $tot_assoc, '', '', '', '', ''],
            ],
            'footer_class' => 'fw-bold',
            'entries' => $entries,
            'total_number' => $number,
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . mt_rand(),
                'extraparams' => [
                    'options' => [
                        'glpi_softwareversions.name' => [
                            'condition' => ["glpi_softwareversions.softwares_id" => $softwares_id],
                        ],
                        'glpi_softwarelicenses.name' => ['itemlink_as_string' => true],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Get fields to display in the unicity error message
     *
     * @return array
     */
    public function getUnicityFieldsToDisplayInErrorMessage()
    {
        return [
            'id'           => __('ID'),
            'serial'       => __('Serial number'),
            'entities_id'  => Entity::getTypeName(1),
            'softwares_id' => _n('Software', 'Software', 1),
        ];
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$withtemplate) {
            $nb = 0;
            switch (get_class($item)) {
                case Software::class:
                    if (!self::canView()) {
                        return '';
                    }
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = self::countForSoftware($item->getID());
                    }
                    return self::createTabEntry(
                        self::getTypeName(Session::getPluralNumber()),
                        (($nb >= 0) ? $nb : '&infin;'),
                        $item::class
                    );

                case self::class:
                    if (!self::canView()) {
                        return '';
                    }
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(
                            static::getTable(),
                            ['softwarelicenses_id' => $item->getID()]
                        );
                    }
                    return self::createTabEntry(
                        self::getTypeName(Session::getPluralNumber()),
                        (($nb >= 0) ? $nb : '&infin;'),
                        $item::class
                    );
            }
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item::class === Software::class && self::canView()) {
            self::showForSoftware($item);
        } elseif ($item::class === self::class && self::canView()) {
            $item->showChildren();
            return true;
        }
        return true;
    }

    public static function getIcon()
    {
        return "ti ti-key";
    }
}
