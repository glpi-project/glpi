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
use Glpi\Features\StateInterface;

/**
 * Appliances Class
 **/
class Appliance extends CommonDBTM implements AssignableItemInterface, StateInterface
{
    use Clonable;
    use Glpi\Features\State;
    use AssetImage;
    use AssignableItem {
        prepareInputForAdd as prepareInputForAddAssignableItem;
        prepareInputForUpdate as prepareInputForUpdateAssignableItem;
    }

    // From CommonDBTM
    public $dohistory                   = true;
    public static $rightname                   = 'appliance';
    protected $usenotepad               = true;

    public function getCloneRelations(): array
    {
        return [
            Appliance_Item::class,
            Contract_Item::class,
            Document_Item::class,
            Infocom::class,
            Notepad::class,
            KnowbaseItem_Item::class,
            Certificate_Item::class,
            Domain_Item::class,
            Item_Project::class,
            ManualLink::class,
        ];
    }

    public static function getSectorizedDetails(): array
    {
        return ['management', self::class];
    }

    public static function getLogDefaultServiceName(): string
    {
        return 'inventory';
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Appliance', 'Appliances', $nb);
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong)
         ->addImpactTab($ong, $options)
         ->addStandardTab(Appliance_Item::class, $ong, $options)
         ->addStandardTab(Contract_Item::class, $ong, $options)
         ->addStandardTab(Document_Item::class, $ong, $options)
         ->addStandardTab(Infocom::class, $ong, $options)
         ->addStandardTab(Certificate_Item::class, $ong, $options)
         ->addStandardTab(Domain_Item::class, $ong, $options)
         ->addStandardTab(KnowbaseItem_Item::class, $ong, $options)
         ->addStandardTab(Item_Ticket::class, $ong, $options)
         ->addStandardTab(Item_Problem::class, $ong, $options)
         ->addStandardTab(Change_Item::class, $ong, $options)
         ->addStandardTab(Item_Project::class, $ong, $options)
         ->addStandardTab(ManualLink::class, $ong, $options)
         ->addStandardTab(DatabaseInstance::class, $ong, $options)
         ->addStandardTab(Notepad::class, $ong, $options)
         ->addStandardTab(Log::class, $ong, $options);

        return $ong;
    }


    public function prepareInputForAdd($input)
    {
        $input = $this->prepareInputForAddAssignableItem($input);
        if ($input === false) {
            return false;
        }
        return $this->managePictures($input);
    }

    public function prepareInputForUpdate($input)
    {
        $input = $this->prepareInputForUpdateAssignableItem($input);
        if ($input === false) {
            return false;
        }
        return $this->managePictures($input);
    }

    /**
     * Print the appliance form
     *
     * @param $ID        integer ID of the item
     * @param $options   array
     *     - target filename : where to go when done.
     *     - withtemplate boolean : template or basic item
     *
     * @return boolean item found
     */
    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        TemplateRenderer::getInstance()->display('pages/management/appliance.html.twig', [
            'item'   => $this,
            'params' => $options,
        ]);
        return true;
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'            => '4',
            'table'         => self::getTable(),
            'field'         =>  'comment',
            'name'          =>  _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'      =>  'text',
        ];

        $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

        $tab[] = [
            'id'            => '5',
            'table'         =>  Appliance_Item::getTable(),
            'field'         => 'items_id',
            'name'               => _n('Associated item', 'Associated items', 2),
            'nosearch'           => true,
            'massiveaction' => false,
            'forcegroupby'  =>  true,
            'additionalfields'   => ['itemtype'],
            'joinparams'    => ['jointype' => 'child'],
        ];

        $tab[] = [
            'id'            => '6',
            'table'         => User::getTable(),
            'field'         => 'name',
            'name'          => User::getTypeName(1),
            'datatype'      => 'dropdown',
        ];

        $tab[] = [
            'id'            => '8',
            'table'         => Group::getTable(),
            'field'         => 'completename',
            'name'          => Group::getTypeName(1),
            'condition'     => ['is_itemgroup' => 1],
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
            'id'                 => '14',
            'table'              => $this->getTable(),
            'field'              => 'contact',
            'name'               => __('Alternate username'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '15',
            'table'              => $this->getTable(),
            'field'              => 'contact_num',
            'name'               => __('Alternate username number'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'            => '23',
            'table'         => 'glpi_manufacturers',
            'field'         => 'name',
            'name'          => Manufacturer::getTypeName(1),
            'datatype'      => 'dropdown',
        ];

        $tab[] = [
            'id'            => '24',
            'table'         => User::getTable(),
            'field'         => 'name',
            'linkfield'     => 'users_id_tech',
            'name'          => __('Technician in charge'),
            'datatype'      => 'dropdown',
            'right'         => 'own_ticket',
        ];

        $tab[] = [
            'id'            => '49',
            'table'         => Group::getTable(),
            'field'         => 'completename',
            'linkfield'     => 'groups_id',
            'name'          => __('Group in charge'),
            'condition'     => ['is_assign' => 1],
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
            'id'            => '9',
            'table'         => self::getTable(),
            'field'         => 'date_mod',
            'name'          => __('Last update'),
            'massiveaction' => false,
            'datatype'      => 'datetime',
        ];

        $tab[] = [
            'id'            => '10',
            'table'         => ApplianceEnvironment::getTable(),
            'field'         => 'name',
            'name'          => _n('Environment', 'Environments', 1),
            'datatype'      => 'dropdown',
        ];

        $tab[] = [
            'id'            => '11',
            'table'         => ApplianceType::getTable(),
            'field'         => 'name',
            'name'          => _n('Type', 'Types', 1),
            'datatype'      => 'dropdown',
        ];

        $tab[] = [
            'id'            => '12',
            'table'         => self::getTable(),
            'field'         => 'serial',
            'name'          => __('Serial number'),
        ];

        $tab[] = [
            'id'            => '13',
            'table'         => self::getTable(),
            'field'         => 'otherserial',
            'name'          => __('Inventory number'),
        ];

        $tab[] = [
            'id'            => '31',
            'table'         => self::getTable(),
            'field'         => 'id',
            'name'          => __('ID'),
            'datatype'      => 'number',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'            => '80',
            'table'         => 'glpi_entities',
            'field'         => 'completename',
            'name'          => Entity::getTypeName(1),
            'datatype'      => 'dropdown',
        ];

        $tab[] = [
            'id'            => '7',
            'table'         => self::getTable(),
            'field'         => 'is_recursive',
            'name'          => __('Child entities'),
            'massiveaction' => false,
            'datatype'      => 'bool',
        ];

        $tab[] = [
            'id'            => '81',
            'table'         => Entity::getTable(),
            'field'         => 'entities_id',
            'name'          => sprintf('%s-%s', Entity::getTypeName(1), __('ID')),
        ];

        $tab[] = [
            'id'                 => '61',
            'table'              => $this->getTable(),
            'field'              => 'is_helpdesk_visible',
            'name'               => __('Associable to a ticket'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '32',
            'table'              => State::getTable(),
            'field'              => 'completename',
            'name'               => __('Status'),
            'datatype'           => 'dropdown',
            'condition'          => $this->getStateVisibilityCriteria(),
        ];

        $tab = array_merge($tab, Certificate::rawSearchOptionsToAdd());

        return $tab;
    }


    public static function rawSearchOptionsToAdd(string $itemtype)
    {
        $tab = [];

        $tab[] = [
            'id' => 'appliance',
            'name' => self::getTypeName(Session::getPluralNumber()),
        ];

        $tab[] = [
            'id'            => '1210',
            'table'         => self::getTable(),
            'field'         => 'name',
            'name'          => __('Name'),
            'datatype'      => 'itemlink',
            'massiveaction' => false,
            'forcegroupby'  => true,
            'use_subquery'  => true,
            'joinparams'    =>  [
                'condition'  => ['NEWTABLE.is_deleted' => 0],
                'beforejoin' => [
                    'table'      => Appliance_Item::getTable(),
                    'joinparams' => [
                        'jointype' => 'itemtype_item',
                        'field'    => 'items_id',
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '1211',
            'table'              => ApplianceType::getTable(),
            'field'              => 'name',
            'name'               => ApplianceType::getTypeName(1),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin' => [
                    'table'      => Appliance::getTable(),
                    'joinparams' => [
                        'beforejoin' => [
                            'table'      => Appliance_Item::getTable(),
                            'joinparams' => ['jointype' => 'itemtype_item'],
                        ],
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '1212',
            'table'              => User::getTable(),
            'field'              => 'name',
            'name'               => User::getTypeName(1),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => self::getTable(),
                    'joinparams'         => [
                        'beforejoin' => [
                            'table'      => Appliance_Item::getTable(),
                            'joinparams' => ['jointype' => 'itemtype_item'],
                        ],
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '1213',
            'table'              => Group::getTable(),
            'field'              => 'name',
            'name'               => Group::getTypeName(1),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_groups_items',
                    'joinparams'         => [
                        'jointype'           => 'itemtype_item',
                        'condition'          => ['NEWTABLE.type' => Group_Item::GROUP_TYPE_TECH],
                        'beforejoin'         => [
                            'table'              => self::getTable(),
                            'joinparams'         => [
                                'beforejoin' => [
                                    'table'      => Appliance_Item::getTable(),
                                    'joinparams' => ['jointype' => 'itemtype_item'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $tab;
    }


    public function cleanDBonPurge()
    {

        $this->deleteChildrenAndRelationsFromDb(
            [
                Appliance_Item::class,
            ]
        );
    }


    public static function getIcon()
    {
        return "ti ti-versions";
    }

    /**
     * Get item types that can be linked to an appliance
     *
     * @param boolean $all Get all possible types or only allowed ones
     *
     * @return array
     */
    public static function getTypes($all = false): array
    {
        global $CFG_GLPI;

        $types = $CFG_GLPI['appliance_types'];

        foreach ($types as $key => $type) {
            if (!class_exists($type)) {
                continue;
            }

            if ($all === false && !$type::canView()) {
                unset($types[$key]);
            }
        }
        return $types;
    }

    public function getSpecificMassiveActions($checkitem = null)
    {
        $isadmin = static::canUpdate();
        $actions = parent::getSpecificMassiveActions($checkitem);

        if ($isadmin) {
            $prefix                    = 'Appliance_Item' . MassiveAction::CLASS_ACTION_SEPARATOR;
            $actions[$prefix . 'add']    = "<i class='ti ti-package'></i>" . _sx('button', 'Add an item');
            $actions[$prefix . 'remove'] = "<i class='ti ti-package-off'></i>" . _sx('button', 'Remove an item');
        }

        KnowbaseItem_Item::getMassiveActionsForItemtype($actions, self::class, false, $checkitem);

        return $actions;
    }

    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {

        switch ($ma->getAction()) {
            case 'add_item':
                Appliance::dropdown();
                echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
                return true;
        }
        return parent::showMassiveActionsSubForm($ma);
    }

    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {
        $appli_item = new Appliance_Item();

        switch ($ma->getAction()) {
            case 'add_item':
                $input = $ma->getInput();
                foreach ($ids as $id) {
                    $input = [
                        'appliances_id'   => $input['appliances_id'],
                        'items_id'        => $id,
                        'itemtype'        => $item->getType(),
                    ];
                    if ($appli_item->can(-1, UPDATE, $input)) {
                        if ($appli_item->add($input)) {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                        } else {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        }
                    } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                    }
                }

                return;
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }
}
