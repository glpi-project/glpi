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
use Glpi\Features\AssetImage;
use Glpi\Features\AssignableItem;
use Glpi\Features\AssignableItemInterface;
use Glpi\Features\Clonable;
use Glpi\Features\TreeBrowse;
use Glpi\Features\TreeBrowseInterface;
use Glpi\Search\DefaultSearchRequestInterface;

/** Software Class
 **/
class Software extends CommonDBTM implements TreeBrowseInterface, AssignableItemInterface, DefaultSearchRequestInterface
{
    use Clonable;
    use TreeBrowse;
    use AssetImage;
    use AssignableItem {
        prepareInputForAdd as prepareInputForAddAssignableItem;
        prepareInputForUpdate as prepareInputForUpdateAssignableItem;
        getEmpty as getEmptyAssignableItem;
    }

    // From CommonDBTM
    public $dohistory                   = true;

    protected static $forward_entity_to = ['Infocom', 'ReservationItem', 'SoftwareVersion'];

    public static $rightname                   = 'software';
    protected $usenotepad               = true;

    public function getCloneRelations(): array
    {
        return [
            Infocom::class,
            Contract_Item::class,
            Document_Item::class,
            KnowbaseItem_Item::class,
            Appliance_Item::class,
            Domain_Item::class,
            Item_Project::class,
            ManualLink::class,
        ];
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Software', 'Software', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['assets', self::class];
    }

    public static function getMenuShorcut()
    {
        return 's';
    }

    public static function getLogDefaultServiceName(): string
    {
        return 'inventory';
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (
            !$withtemplate
            && $item instanceof self
            && $item->isRecursive()
            && $item->can($item->fields['id'], UPDATE)
        ) {
            return self::createTabEntry(__('Merging'), icon: 'ti ti-arrow-merge');
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof self) {
            $item->showMergeCandidates();
        }
        return true;
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addImpactTab($ong, $options);
        $this->addStandardTab(SoftwareVersion::class, $ong, $options);
        $this->addStandardTab(SoftwareLicense::class, $ong, $options);
        $this->addStandardTab(Item_SoftwareVersion::class, $ong, $options);
        $this->addStandardTab(Infocom::class, $ong, $options);
        $this->addStandardTab(Contract_Item::class, $ong, $options);
        $this->addStandardTab(Document_Item::class, $ong, $options);
        $this->addStandardTab(KnowbaseItem_Item::class, $ong, $options);
        $this->addStandardTab(Item_Ticket::class, $ong, $options);
        $this->addStandardTab(Item_Problem::class, $ong, $options);
        $this->addStandardTab(Change_Item::class, $ong, $options);
        $this->addStandardTab(Item_Project::class, $ong, $options);
        $this->addStandardTab(ManualLink::class, $ong, $options);
        $this->addStandardTab(Notepad::class, $ong, $options);
        $this->addStandardTab(Reservation::class, $ong, $options);
        $this->addStandardTab(Domain_Item::class, $ong, $options);
        $this->addStandardTab(Appliance_Item::class, $ong, $options);
        $this->addStandardTab(Log::class, $ong, $options);
        $this->addStandardTab(self::class, $ong, $options);

        return $ong;
    }

    public function prepareInputForUpdate($input)
    {
        if (isset($input['is_update']) && !$input['is_update']) {
            $input['softwares_id'] = 0;
        }
        $input = $this->managePictures($input);
        return $this->prepareInputForUpdateAssignableItem($input);
    }

    public function prepareInputForAdd($input)
    {
        if (isset($input['is_update']) && !$input['is_update']) {
            $input['softwares_id'] = 0;
        }

        if (isset($input["id"]) && ($input["id"] > 0)) {
            $input["_oldID"] = $input["id"];
        }
        unset($input['id'], $input['withtemplate']);

        $this->handleCategoryRules($input);

        $input = $this->managePictures($input);
        return $this->prepareInputForAddAssignableItem($input);
    }

    public function cleanDBonPurge()
    {
        // SoftwareLicense does not extends CommonDBConnexity
        $sl = new SoftwareLicense();
        $sl->deleteByCriteria(['softwares_id' => $this->fields['id']]);

        $this->deleteChildrenAndRelationsFromDb(
            [
                SoftwareVersion::class,
            ]
        );
    }

    /**
     * Update validity indicator of specific software
     *
     * @param integer $ID ID of the licence
     *
     * @since 0.85
     *
     * @return void
     **/
    public static function updateValidityIndicator($ID)
    {
        $soft = new self();
        if ($soft->getFromDB($ID)) {
            $valid = 1;
            if (
                countElementsInTable(
                    'glpi_softwarelicenses',
                    ['softwares_id' => $ID,
                        'NOT' => ['is_valid' => 1],
                    ]
                ) > 0
            ) {
                $valid = 0;
            }
            if ($valid != $soft->fields['is_valid']) {
                $soft->update(['id'       => $ID,
                    'is_valid' => $valid,
                ]);
            }
        }
    }

    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        TemplateRenderer::getInstance()->display('pages/assets/software.html.twig', [
            'item'   => $this,
            'params' => $options,
        ]);
        return true;
    }

    public function getEmpty()
    {
        global $CFG_GLPI;

        if (!$this->getEmptyAssignableItem()) {
            return false;
        }

        $this->fields["is_helpdesk_visible"] = $CFG_GLPI["default_software_helpdesk_visible"];
        return true;
    }

    public function getSpecificMassiveActions($checkitem = null)
    {
        $isadmin = static::canUpdate();
        $actions = parent::getSpecificMassiveActions($checkitem);
        if (
            $isadmin
            && (countElementsInTable("glpi_rules", ['sub_type' => 'RuleSoftwareCategory']) > 0)
        ) {
            $actions[self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'compute_software_category']
            = "<i class='ti ti-calculator'></i>"
              . __s('Recalculate the category');
        }

        if (
            Session::haveRightsOr("rule_dictionnary_software", [CREATE, UPDATE])
            && (countElementsInTable("glpi_rules", ['sub_type' => 'RuleDictionnarySoftware']) > 0)
        ) {
            $actions[self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'replay_dictionnary']
            = "<i class='ti ti-arrow-back-up'></i>"
              . __s('Replay the dictionary rules');
        }

        if ($isadmin) {
            KnowbaseItem_Item::getMassiveActionsForItemtype($actions, self::class, false, $checkitem);
        }

        return $actions;
    }

    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {
        /** @var Software $item */
        switch ($ma->getAction()) {
            case 'merge':
                $input = $ma->getInput();
                if (isset($input['item_items_id'])) {
                    $items = [];
                    foreach ($ids as $id) {
                        $items[$id] = 1;
                    }
                    if ($item->can($input['item_items_id'], UPDATE)) {
                        if ($item->merge($items)) {
                            $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_OK);
                        } else {
                            $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
                            $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                        }
                    } else {
                        $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_NORIGHT);
                        $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                    }
                } else {
                    $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
                }
                return;

            case 'compute_software_category':
                $softcatrule = new RuleSoftwareCategoryCollection();
                foreach ($ids as $id) {
                    $params = [];
                    //Get software name and manufacturer
                    if ($item->can($id, UPDATE)) {
                        $params["name"]             = $item->fields["name"];
                        $params["manufacturers_id"] = $item->fields["manufacturers_id"];
                        $params["comment"]          = $item->fields["comment"];
                        $output = [];
                        $output = $softcatrule->processAllRules([], $output, $params);
                        //Process rules
                        if (
                            isset($output['softwarecategories_id'])
                            && $item->update(['id' => $id,
                                'softwarecategories_id'
                                                  => $output['softwarecategories_id'],
                            ])
                        ) {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                        } else {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                            $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                        }
                    } else {
                        $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_NORIGHT);
                        $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                    }
                }
                return;

            case 'replay_dictionnary':
                $softdictionnayrule = new RuleDictionnarySoftwareCollection();
                $allowed_ids        = [];
                foreach ($ids as $id) {
                    if ($item->can($id, UPDATE)) {
                        $allowed_ids[] = $id;
                    } else {
                        $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_NORIGHT);
                        $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                    }
                }
                if ($softdictionnayrule->replayRulesOnExistingDB(0, 0, $allowed_ids) > 0) {
                    $ma->itemDone($item->getType(), $allowed_ids, MassiveAction::ACTION_OK);
                } else {
                    $ma->itemDone($item->getType(), $allowed_ids, MassiveAction::ACTION_KO);
                }

                return;
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }

    public function rawSearchOptions()
    {
        // Only use for History (not by search Engine)
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '2',
            'table'              => static::getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number',
        ];

        $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

        $tab[] = [
            'id'                 => '16',
            'table'              => static::getTable(),
            'field'              => 'comment',
            'name'               => _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '62',
            'table'              => 'glpi_softwarecategories',
            'field'              => 'completename',
            'name'               => _n('Category', 'Categories', 1),
            'datatype'           => 'dropdown',
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
            'id'                 => '23',
            'table'              => 'glpi_manufacturers',
            'field'              => 'name',
            'name'               => __('Publisher'),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '24',
            'table'              => User::getTable(),
            'field'              => 'name',
            'linkfield'          => 'users_id_tech',
            'name'               => __('Technician in charge of the software'),
            'datatype'           => 'dropdown',
            'right'              => 'own_ticket',
        ];

        $tab[] = [
            'id'                 => '49',
            'table'              => Group::getTable(),
            'field'              => 'completename',
            'linkfield'          => 'groups_id',
            'name'               => __('Group in charge'),
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
            'id'                 => '64',
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
            'table'              => 'glpi_users',
            'field'              => 'name',
            'name'               => User::getTypeName(1),
            'datatype'           => 'dropdown',
            'right'              => 'all',
        ];

        $tab[] = [
            'id'                 => '71',
            'table'              => 'glpi_groups',
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
            'id'                 => '61',
            'table'              => static::getTable(),
            'field'              => 'is_helpdesk_visible',
            'name'               => __('Associable to a ticket'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '63',
            'table'              => static::getTable(),
            'field'              => 'is_valid',
            //TRANS: Indicator to know is all licenses of the software are valids
            'name'               => __('Valid licenses'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => Entity::getTable(),
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
        ];

        $newtab = [
            'id'                 => '72',
            'table'              => Item_SoftwareVersion::getTable(),
            'field'              => 'id',
            'name'               => _x('quantity', 'Number of installations'),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'datatype'           => 'count',
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'   => 'child',
                'beforejoin' => [
                    'table'      => SoftwareVersion::getTable(),
                    'joinparams' => ['jointype' => 'child'],
                ],
                'condition'  => [
                    'NEWTABLE.is_deleted_item'  => 0,
                    'NEWTABLE.is_deleted'       => 0,
                    'NEWTABLE.is_template_item' => 0,
                ],
            ],
        ];

        if (Session::getLoginUserID()) {
            $newtab['joinparams']['condition'] = array_merge(
                $newtab['joinparams']['condition'],
                getEntitiesRestrictCriteria('NEWTABLE')
            );
        }
        $tab[] = $newtab;

        $tab[] = [
            'id'                 => '73',
            'table'              => Item_SoftwareVersion::getTable(),
            'field'              => 'date_install',
            'name'               => __('Installation date'),
            'datatype'           => 'date',
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'   => 'child',
                'beforejoin' => [
                    'table'      => 'glpi_softwareversions',
                    'joinparams' => ['jointype' => 'child'],
                ],
                'condition'  => [
                    'NEWTABLE.is_deleted_item'  => 0,
                    'NEWTABLE.is_deleted'       => 0,
                    'NEWTABLE.is_template_item' => 0,
                ],
            ],
        ];

        $tab = array_merge($tab, SoftwareLicense::rawSearchOptionsToAdd());

        $name = _n('Version', 'Versions', Session::getPluralNumber());
        $tab[] = [
            'id'                 => 'versions',
            'name'               => $name,
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => SoftwareVersion::getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'displaywith'        => ['softwares_id'],
            'joinparams'         => [
                'jointype'           => 'child',
            ],
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => SoftwareVersion::getTable(),
            'field'              => 'arch',
            'name'               => _n('Architecture', 'Architectures', 1),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'displaywith'        => ['softwares_id'],
            'joinparams'         => [
                'jointype'           => 'child',
            ],
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '31',
            'table'              => State::getTable(),
            'field'              => 'completename',
            'name'               => __('Status'),
            'datatype'           => 'dropdown',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_softwareversions',
                    'joinparams'         => [
                        'jointype'           => 'child',
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '170',
            'table'              => SoftwareVersion::getTable(),
            'field'              => 'comment',
            'name'               => _n('Comment', 'Comments', Session::getPluralNumber()),
            'forcegroupby'       => true,
            'datatype'           => 'text',
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child',
            ],
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => OperatingSystem::getTable(),
            'field'              => 'name',
            'datatype'           => 'dropdown',
            'name'               => OperatingSystem::getTypeName(1),
            'forcegroupby'       => true,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_softwareversions',
                    'joinparams'         => [
                        'jointype'           => 'child',
                    ],
                ],
            ],
        ];

        $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());
        $tab = array_merge($tab, Certificate::rawSearchOptionsToAdd());

        return $tab;
    }

    /**
     * Make a select box for software to install
     *
     * @param string $myname select name
     * @param integer|array<int> $entity_restrict restrict to a defined entity
     *
     * @return integer random part of elements id
     **/
    public static function dropdownSoftwareToInstall($myname, $entity_restrict)
    {
        global $CFG_GLPI;

        // Make a select box
        $where = getEntitiesRestrictCriteria(
            'glpi_softwares',
            'entities_id',
            $entity_restrict,
            true
        );
        $rand = Dropdown::show('Software', ['condition' => ['WHERE' => $where]]);

        $paramsselsoft = [
            'softwares_id' => '__VALUE__',
            'myname'       => $myname,
        ];

        Ajax::updateItemOnSelectEvent(
            "dropdown_softwares_id$rand",
            "show_" . $myname . $rand,
            $CFG_GLPI["root_doc"] . "/ajax/dropdownInstallVersion.php",
            $paramsselsoft
        );

        echo "<span id='show_" . htmlescape($myname . $rand) . "'>&nbsp;</span>\n";

        return $rand;
    }

    /**
     * Make a select box for license software to associate
     *
     * @param string $myname select name
     * @param integer|array<int> $entity_restrict restrict to a defined entity
     *
     * @return integer random part of elements id
     **/
    public static function dropdownLicenseToInstall($myname, $entity_restrict)
    {
        global $CFG_GLPI, $DB;

        $iterator = $DB->request([
            'SELECT'          => [
                'glpi_softwares.id',
                'glpi_softwares.name',
            ],
            'DISTINCT'        => true,
            'FROM'            => 'glpi_softwares',
            'INNER JOIN'      => [
                'glpi_softwarelicenses' => [
                    'ON' => [
                        'glpi_softwarelicenses' => 'softwares_id',
                        'glpi_softwares'        => 'id',
                    ],
                ],
            ],
            'WHERE'           => [
                'glpi_softwares.is_deleted'    => 0,
                'glpi_softwares.is_template'  => 0,
            ] + getEntitiesRestrictCriteria('glpi_softwarelicenses', 'entities_id', $entity_restrict, true),
            'ORDERBY'         => 'glpi_softwares.name',
        ]);

        $values = [];
        foreach ($iterator as $data) {
            $softwares_id          = $data["id"];
            $values[$softwares_id] = $data["name"];
        }
        $rand = Dropdown::showFromArray('softwares_id', $values, ['display_emptychoice' => true]);

        $paramsselsoft = ['softwares_id'    => '__VALUE__',
            'entity_restrict' => $entity_restrict,
            'myname'          => $myname,
        ];

        Ajax::updateItemOnSelectEvent(
            "dropdown_softwares_id$rand",
            "show_" . $myname . $rand,
            $CFG_GLPI["root_doc"] . "/ajax/dropdownSoftwareLicense.php",
            $paramsselsoft
        );

        echo "<span id='show_" . htmlescape($myname . $rand) . "'>&nbsp;</span>\n";

        return $rand;
    }

    /**
     * Create new software
     *
     * @param string   $name                the software's name
     * @param integer  $manufacturer_id     id of the software's manufacturer
     * @param integer  $entity              the entity in which the software must be added
     * @param string   $comment             (default '')
     * @param boolean  $is_recursive        must the software be recursive (false by default)
     * @param ?boolean $is_helpdesk_visible show in helpdesk, default: from config (false by default)
     *
     * @return integer the software's ID
     **/
    public function addSoftware(
        $name,
        $manufacturer_id,
        $entity,
        $comment = '',
        $is_recursive = false,
        $is_helpdesk_visible = null
    ) {
        global $CFG_GLPI;

        $input["name"]                = $name;
        $input["manufacturers_id"]    = $manufacturer_id;
        $input["entities_id"]         = $entity;
        $input["is_recursive"]        = ($is_recursive ? 1 : 0);
        // No comment
        if (is_null($is_helpdesk_visible)) {
            $input["is_helpdesk_visible"] = $CFG_GLPI["default_software_helpdesk_visible"];
        } else {
            $input["is_helpdesk_visible"] = $is_helpdesk_visible;
        }

        // Process software's category rules
        $softcatrule = new RuleSoftwareCategoryCollection();
        $result      = $softcatrule->processAllRules([], [], $input);

        if (isset($result['_ignore_import'])) {
            $input["softwarecategories_id"] = 0;
        } elseif (isset($result["softwarecategories_id"])) {
            $input["softwarecategories_id"] = $result["softwarecategories_id"];
        } elseif (isset($result["_import_category"])) {
            $softCat = new SoftwareCategory();
            $input["softwarecategories_id"] = $softCat->importExternal($result["_system_category"]);
        } else {
            $input["softwarecategories_id"] = 0;
        }

        return $this->add($input);
    }

    /**
     * Add software. If already exist in trashbin restore it
     *
     * @param string  $name                the software's name
     * @param string  $manufacturer        the software's manufacturer
     * @param integer $entity              the entity in which the software must be added
     * @param string  $comment             comment (default '')
     * @param boolean $is_recursive        must the software be recursive (false by default)
     * @param boolean $is_helpdesk_visible show in helpdesk, default = config value (false by default)
     */
    public function addOrRestoreFromTrash(
        $name,
        $manufacturer,
        $entity,
        $comment = '',
        $is_recursive = false,
        $is_helpdesk_visible = null
    ) {
        global $DB;

        // Look for the software by his name in GLPI for a specific entity
        $manufacturer_id = 0;
        if ($manufacturer !== '') {
            $manufacturer_id = Dropdown::import('Manufacturer', ['name' => $manufacturer]);
        }

        $iterator = $DB->request([
            'SELECT' => [
                'glpi_softwares.id',
                'glpi_softwares.is_deleted',
            ],
            'FROM'   => 'glpi_softwares',
            'WHERE'  => [
                'name'               => $name,
                'manufacturers_id'   => $manufacturer_id,
                'is_template'        => 0,
            ] + getEntitiesRestrictCriteria('glpi_softwares', 'entities_id', $entity, true),
        ]);

        if (count($iterator)) {
            // Software already exists for this entity, get his ID
            $data = $iterator->current();
            $ID   = $data["id"];

            // restore software
            if ($data['is_deleted']) {
                $this->removeFromTrash($ID);
            }
        } else {
            $ID = 0;
        }

        if (!$ID) {
            $ID = $this->addSoftware(
                $name,
                $manufacturer_id,
                $entity,
                $comment,
                $is_recursive,
                $is_helpdesk_visible
            );
        }
        return $ID;
    }

    /**
     * Put software in trashbin because it's been removed by GLPI software dictionary
     *
     * @param int    $ID      the ID of the software to put in trashbin
     * @param string $comment the comment to add to the already existing software's comment (default '')
     *
     * @return boolean (success)
     **/
    public function putInTrash($ID, $comment = '')
    {
        global $CFG_GLPI;

        $this->getFromDB($ID);
        $input["id"]         = $ID;
        $input["is_deleted"] = 1;

        // change category of the software on deletion (if defined in glpi_configs)
        if (
            isset($CFG_GLPI["softwarecategories_id_ondelete"])
            && ($CFG_GLPI["softwarecategories_id_ondelete"] != 0)
        ) {
            $input["softwarecategories_id"] = $CFG_GLPI["softwarecategories_id_ondelete"];
        }

        // Add dictionary comment to the current comment
        $input["comment"] = (($this->fields["comment"] !== '') ? "\n" : '') . $comment;

        return $this->update($input);
    }

    /**
     * Restore software from trashbin
     *
     * @param int $ID the ID of the software to put in trashbin
     *
     * @return boolean
     **/
    public function removeFromTrash($ID)
    {
        $res         = $this->restore(["id" => $ID]);
        $softcatrule = new RuleSoftwareCategoryCollection();
        $result      = $softcatrule->processAllRules([], [], $this->fields);

        if (
            isset($result['softwarecategories_id'])
            && ((int) $result['softwarecategories_id'] !== (int) $this->fields['softwarecategories_id'])
        ) {
            $this->update([
                'id'                    => $ID,
                'softwarecategories_id' => $result['softwarecategories_id'],
            ]);
        }

        return $res;
    }

    /**
     * Show software candidates to be merged with the current
     *
     * @return void
     **/
    public function showMergeCandidates()
    {
        global $DB;

        $ID   = $this->getField('id');
        $this->check($ID, UPDATE);

        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_softwares.id',
                'glpi_softwares.name',
                'glpi_entities.completename AS entity',
            ],
            'FROM'      => 'glpi_softwares',
            'LEFT JOIN' => [
                'glpi_entities'   => [
                    'ON' => [
                        'glpi_softwares'  => 'entities_id',
                        'glpi_entities'   => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                'glpi_softwares.id'           => ['!=', $ID],
                'glpi_softwares.name'         => $this->fields['name'],
                'glpi_softwares.is_deleted'   => 0,
                'glpi_softwares.is_template'  => 0,
            ] + getEntitiesRestrictCriteria(
                'glpi_softwares',
                'entities_id',
                getSonsOf("glpi_entities", $this->fields["entities_id"]),
                false
            ),
            'ORDERBY'   => 'entity',
        ]);

        $entries = [];
        $software = new self();
        foreach ($iterator as $data) {
            $software->getFromDB($data["id"]);
            $entries[] = [
                'name' => $software->getLink(),
                'entity' => $data["entity"],
                'installations' => Item_SoftwareVersion::countForSoftware($data["id"]),
                'licenses' => SoftwareLicense::countForSoftware($data["id"]),
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => [
                'name' => __('Name'),
                'entity' => Entity::getTypeName(1),
                'installations' => _n('Installation', 'Installations', Session::getPluralNumber()),
                'licenses' => SoftwareLicense::getTypeName(Session::getPluralNumber()),
            ],
            'formatters' => [
                'name' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => true,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . mt_rand(),
                'specific_actions' => [
                    self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'merge' => __('Merge'),
                ],
                'item'          => $this,
            ],
        ]);
    }

    /**
     * Merge software with current.
     *
     * @param array   $item array of software ID to be merged
     *
     * @return boolean about success
     */
    private function merge($item): bool
    {
        global $DB;

        $ID = $this->getField('id');

        $item = array_keys($item);

        // Search for software version
        $req = $DB->request(['FROM' => "glpi_softwareversions", 'WHERE' => ["softwares_id" => $item]]);
        $i   = 0;

        if ($nb = $req->numrows()) {
            foreach ($req as $from) {
                $found = false;

                foreach (
                    $DB->request([
                        'FROM' => "glpi_softwareversions",
                        'WHERE' => [
                            "softwares_id" => $ID,
                            "name"         => $from["name"],
                        ],
                    ]) as $dest
                ) {
                    // Update version ID on License
                    $DB->update(
                        'glpi_softwarelicenses',
                        [
                            'softwareversions_id_buy' => $dest['id'],
                        ],
                        [
                            'softwareversions_id_buy' => $from['id'],
                        ]
                    );

                    $DB->update(
                        'glpi_softwarelicenses',
                        [
                            'softwareversions_id_use' => $dest['id'],
                        ],
                        [
                            'softwareversions_id_use' => $from['id'],
                        ]
                    );

                    // Move installation to existing version in destination software
                    $found = $DB->update(
                        'glpi_items_softwareversions',
                        [
                            'softwareversions_id' => $dest['id'],
                        ],
                        [
                            'softwareversions_id' => $from['id'],
                        ]
                    );
                }

                if ($found) {
                    // Installation has be moved, delete the source version
                    $result = $DB->delete(
                        'glpi_softwareversions',
                        [
                            'id'  => $from['id'],
                        ]
                    );
                } else {
                    // Move version to destination software
                    $result = $DB->update(
                        'glpi_softwareversions',
                        [
                            'softwares_id' => $ID,
                            'entities_id'  => $this->getField('entities_id'),
                        ],
                        [
                            'id' => $from['id'],
                        ]
                    );
                }

                if ($result) {
                    $i++;
                }
            }
        }

        // Move software license
        $result = $DB->update(
            'glpi_softwarelicenses',
            [
                'softwares_id' => $ID,
            ],
            [
                'softwares_id' => $item,
            ]
        );

        if ($result) {
            $i++;
        }

        if ($i == ($nb + 1)) {
            $soft = new self();
            foreach ($item as $old) {
                $soft->putInTrash($old, __('Software deleted after merging'));
            }
        }
        return $i === ($nb + 1);
    }

    #[Override]
    public static function getDefaultSearchRequest(): array
    {
        return [
            'sort' => 0,
        ];
    }

    public static function getIcon()
    {
        return "ti ti-apps";
    }

    public function handleCategoryRules(array &$input, bool $is_dynamic = false)
    {
        // If category was not set by user (when manually adding a user)
        if ($is_dynamic || !isset($input["softwarecategories_id"]) || !$input["softwarecategories_id"]) {
            $softcatrule = new RuleSoftwareCategoryCollection();
            $result      = $softcatrule->processAllRules([], [], $input);

            if (!isset($result['_ignore_import'])) {
                if (isset($result["softwarecategories_id"])) {
                    $input["softwarecategories_id"] = $result["softwarecategories_id"];
                } elseif (isset($result["_import_category"], $input['_system_category'])) {
                    $softCat = new SoftwareCategory();
                    $input["softwarecategories_id"] = $softCat->importExternal($input["_system_category"]);
                }
            }
            if (!isset($input["softwarecategories_id"])) {
                $input["softwarecategories_id"] = 0;
            }
        }
    }

    public static function getPurgeTaskDescription(): string
    {
        return __("Purge software with no version that are deleted.");
    }

    public static function getPurgeTaskParameterDescription(): string
    {
        return __('Max items to handle in one execution');
    }

    public static function cronInfo($name)
    {
        return [
            'description' => self::getPurgeTaskDescription(),
            'parameter'   => self::getPurgeTaskParameterDescription(),
        ];
    }

    public static function cronPurgeSoftware(CronTask $task)
    {
        $max = $task->fields['param'];
        $taskInstance = new PurgeSoftwareTask();
        $total = $taskInstance->run($max);
        $task->addVolume($total);
        return 1;
    }
}
