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
use Glpi\Asset\Asset_PeripheralAsset;
use Glpi\DBAL\QueryFunction;
use Glpi\DBAL\QuerySubQuery;
use Glpi\Features\AssignableItem;
use Glpi\Features\AssignableItemInterface;
use Glpi\Features\Clonable;
use Glpi\Features\Inventoriable;
use Glpi\Features\StateInterface;
use Glpi\Socket;

/**
 * Printer Class
 **/
class Printer extends CommonDBTM implements AssignableItemInterface, StateInterface
{
    use Clonable;
    use Inventoriable;
    use Glpi\Features\State;
    use AssignableItem {
        prepareInputForAdd as prepareInputForAddAssignableItem;
        prepareInputForUpdate as prepareInputForUpdateAssignableItem;
    }

    // From CommonDBTM
    public $dohistory                   = true;

    protected static $forward_entity_to = ['Infocom', 'NetworkPort', 'ReservationItem',
        'Item_OperatingSystem', 'Item_Disk', 'Item_SoftwareVersion',
    ];

    public static $rightname                   = 'printer';
    protected $usenotepad               = true;

    public function getCloneRelations(): array
    {
        return [
            Item_OperatingSystem::class,
            Item_Devices::class,
            Infocom::class,
            NetworkPort::class,
            Contract_Item::class,
            Document_Item::class,
            Asset_PeripheralAsset::class,
            KnowbaseItem_Item::class,
            Appliance_Item::class,
            Certificate_Item::class,
            Domain_Item::class,
            Item_Disk::class,
            Item_Project::class,
            Item_SoftwareLicense::class,
            Item_SoftwareVersion::class,
            ManualLink::class,
            Socket::class,
        ];
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Printer', 'Printers', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['assets', self::class];
    }

    public static function getLogDefaultServiceName(): string
    {
        return 'inventory';
    }

    /**
     * @see CommonDBTM::useDeletedToLockIfDynamic()
     *
     * @since 0.84
     **/
    public function useDeletedToLockIfDynamic()
    {
        return false;
    }


    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addImpactTab($ong, $options);
        $this->addStandardTab(Item_OperatingSystem::class, $ong, $options);
        $this->addStandardTab(Item_SoftwareVersion::class, $ong, $options);
        $this->addStandardTab(Cartridge::class, $ong, $options);
        $this->addStandardTab(PrinterLog::class, $ong, $options);
        $this->addStandardTab(Item_Devices::class, $ong, $options);
        $this->addStandardTab(Item_Line::class, $ong, $options);
        $this->addStandardTab(Item_Disk::class, $ong, $options);
        $this->addStandardTab(Asset_PeripheralAsset::class, $ong, $options);
        $this->addStandardTab(NetworkPort::class, $ong, $options);
        $this->addStandardTab(Socket::class, $ong, $options);
        $this->addStandardTab(Infocom::class, $ong, $options);
        $this->addStandardTab(Contract_Item::class, $ong, $options);
        $this->addStandardTab(Document_Item::class, $ong, $options);
        $this->addStandardTab(KnowbaseItem_Item::class, $ong, $options);
        $this->addStandardTab(Item_Ticket::class, $ong, $options);
        $this->addStandardTab(Item_Problem::class, $ong, $options);
        $this->addStandardTab(Change_Item::class, $ong, $options);
        $this->addStandardTab(Item_Project::class, $ong, $options);
        $this->addStandardTab(ManualLink::class, $ong, $options);
        $this->addStandardTab(Lock::class, $ong, $options);
        $this->addStandardTab(Notepad::class, $ong, $options);
        $this->addStandardTab(Reservation::class, $ong, $options);
        $this->addStandardTab(Certificate_Item::class, $ong, $options);
        $this->addStandardTab(Domain_Item::class, $ong, $options);
        $this->addStandardTab(Appliance_Item::class, $ong, $options);
        $this->addStandardTab(RuleMatchedLog::class, $ong, $options);
        $this->addStandardTab(Log::class, $ong, $options);

        return $ong;
    }


    /**
     * Can I change recusvive flag to false
     * check if there is "linked" object in another entity
     *
     * Overloaded from CommonDBTM
     *
     * @return boolean
     **/
    public function canUnrecurs()
    {
        global $DB;

        $ID = $this->fields['id'];

        if (
            ($ID < 0)
            || !$this->fields['is_recursive']
        ) {
            return true;
        }

        if (!parent::canUnrecurs()) {
            return false;
        }

        $entities = getAncestorsOf("glpi_entities", $this->fields['entities_id']);
        $entities[] = $this->fields['entities_id'];

        // RELATION : printers -> _port -> _wire -> _port -> device

        // Evaluate connection in the 2 ways
        $tabend = ['networkports_id_1' => 'networkports_id_2',
            'networkports_id_2' => 'networkports_id_1',
        ];
        foreach ($tabend as $enda => $endb) {
            $criteria = [
                'SELECT'       => [
                    'itemtype',
                    QueryFunction::groupConcat(
                        expression: 'items_id',
                        distinct: true,
                        alias: 'ids'
                    ),
                ],
                'FROM'         => 'glpi_networkports_networkports',
                'INNER JOIN'   => [
                    'glpi_networkports'  => [
                        'ON'  => [
                            'glpi_networkports_networkports' => $endb,
                            'glpi_networkports'              => 'id',
                        ],
                    ],
                ],
                'WHERE'        => [
                    'glpi_networkports_networkports.' . $enda   => new QuerySubQuery([
                        'SELECT' => 'id',
                        'FROM'   => 'glpi_networkports',
                        'WHERE'  => [
                            'itemtype'  => $this->getType(),
                            'items_id'  => $ID,
                        ],
                    ]),
                ],
                'GROUPBY'      => 'itemtype',
            ];

            $iterator = $DB->request($criteria);
            foreach ($iterator as $data) {
                $itemtable = getTableForItemType($data["itemtype"]);
                if ($item = getItemForItemtype($data["itemtype"])) {
                    // For each itemtype which are entity dependant
                    if ($item->isEntityAssign()) {
                        if (
                            countElementsInTable($itemtable, ['id' => $data["ids"],
                                'NOT' => [ 'entities_id' => $entities],
                            ]) > 0
                        ) {
                            return false;
                        }
                    }
                }
            }
        }
        return true;
    }


    public function prepareInputForAdd($input)
    {
        if (isset($input["id"]) && ($input["id"] > 0)) {
            $input["_oldID"] = $input["id"];
        }
        unset($input['id']);
        unset($input['withtemplate']);

        if (isset($input['init_pages_counter'])) {
            $input['init_pages_counter'] = intval($input['init_pages_counter']);
        } else {
            $input['init_pages_counter'] = 0;
        }
        if (isset($input['last_pages_counter'])) {
            $input['last_pages_counter'] = intval($input['last_pages_counter']);
        } else {
            $input['last_pages_counter'] = $input['init_pages_counter'];
        }

        $input = $this->prepareInputForAddAssignableItem($input);
        return $input;
    }


    public function prepareInputForUpdate($input)
    {
        if (isset($input['init_pages_counter'])) {
            $input['init_pages_counter'] = intval($input['init_pages_counter']);
        }
        if (isset($input['last_pages_counter'])) {
            $input['last_pages_counter'] = intval($input['last_pages_counter']);
        }

        $input = $this->prepareInputForUpdateAssignableItem($input);
        return $input;
    }


    public function cleanDBonPurge()
    {
        global $DB;

        $DB->update(
            'glpi_cartridges',
            [
                'printers_id' => 'NULL',
            ],
            [
                'printers_id' => $this->fields['id'],
            ]
        );

        $this->deleteChildrenAndRelationsFromDb(
            [
                Printer_CartridgeInfo::class,
                PrinterLog::class,
            ]
        );
    }


    /**
     * Print the printer form
     *
     * @param $ID integer ID of the item
     * @param $options array
     *     - target filename : where to go when done.
     *     - withtemplate boolean : template or basic item
     *
     * @return boolean item found
     **/
    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        TemplateRenderer::getInstance()->display('pages/assets/printer.html.twig', [
            'item'   => $this,
            'params' => $options,
        ]);
        return true;
    }


    /**
     * Return the linked items (`Asset_PeripheralAsset` relations)
     *
     * @return array of linked items  like array('Computer' => array(1,2), 'Printer' => array(5,6))
     * @since 0.84.4
     **/
    public function getLinkedItems()
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => [
                'itemtype_asset',
                'items_id_asset',
            ],
            'FROM'   => Asset_PeripheralAsset::getTable(),
            'WHERE'  => [
                'itemtype_peripheral' => $this->getType(),
                'items_id_peripheral' => $this->fields['id'],
            ],
        ]);
        $tab = [];
        foreach ($iterator as $data) {
            $tab[$data['itemtype_asset']][$data['items_id_asset']] = $data['items_id_asset'];
        }
        return $tab;
    }

    public function getSpecificMassiveActions($checkitem = null)
    {
        $actions = parent::getSpecificMassiveActions($checkitem);
        if (static::canUpdate()) {
            Asset_PeripheralAsset::getMassiveActionsForItemtype($actions, self::class, false, $checkitem);
            $actions += [
                'Item_SoftwareLicense' . MassiveAction::CLASS_ACTION_SEPARATOR . 'add'
               => "<i class='ti ti-key'></i>"
                  . _sx('button', 'Add a license'),
            ];
            KnowbaseItem_Item::getMassiveActionsForItemtype($actions, self::class, false, $checkitem);
        }

        return $actions;
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number',
        ];

        $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

        $tab[] = [
            'id'                 => '4',
            'table'              => 'glpi_printertypes',
            'field'              => 'name',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '40',
            'table'              => 'glpi_printermodels',
            'field'              => 'name',
            'name'               => _n('Model', 'Models', 1),
            'datatype'           => 'dropdown',
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
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'serial',
            'name'               => __('Serial number'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => $this->getTable(),
            'field'              => 'otherserial',
            'name'               => __('Inventory number'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => $this->getTable(),
            'field'              => 'contact',
            'name'               => __('Alternate username'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '8',
            'table'              => $this->getTable(),
            'field'              => 'contact_num',
            'name'               => __('Alternate username number'),
            'datatype'           => 'string',
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
            'id'                 => '72',
            'table'              => 'glpi_autoupdatesystems',
            'field'              => 'name',
            'name'               => AutoUpdateSystem::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '73',
            'table'              => 'glpi_snmpcredentials',
            'field'              => 'name',
            'name'               => SNMPCredential::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => $this->getTable(),
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
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
            'id'                 => '16',
            'table'              => $this->getTable(),
            'field'              => 'comment',
            'name'               => _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '42',
            'table'              => $this->getTable(),
            'field'              => 'have_serial',
            'name'               => __('Serial'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '43',
            'table'              => $this->getTable(),
            'field'              => 'have_parallel',
            'name'               => __('Parallel'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '44',
            'table'              => $this->getTable(),
            'field'              => 'have_usb',
            'name'               => __('USB'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '45',
            'table'              => $this->getTable(),
            'field'              => 'have_ethernet',
            'name'               => __('Ethernet'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '46',
            'table'              => $this->getTable(),
            'field'              => 'have_wifi',
            'name'               => __('Wifi'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '13',
            'table'              => $this->getTable(),
            'field'              => 'memory_size',
            'name'               => _n('Memory', 'Memories', 1),
            'datatype'           => 'integer',
        ];

        $tab[] = [
            'id'                 => '11',
            'table'              => $this->getTable(),
            'field'              => 'init_pages_counter',
            'name'               => __('Initial page counter'),
            'datatype'           => 'number',
            'nosearch'           => true,
        ];

        $tab[] = [
            'id'                 => '12',
            'table'              => $this->getTable(),
            'field'              => 'last_pages_counter',
            'name'               => __('Current counter of pages'),
            'datatype'           => 'number',
        ];

        $tab[] = [
            'id'                 => '9',
            'table'              => $this->getTable(),
            'field'              => '_virtual',
            'linkfield'          => '_virtual',
            'name'               => _n('Cartridge', 'Cartridges', Session::getPluralNumber()),
            'datatype'           => 'specific',
            'massiveaction'      => false,
            'nosearch'           => true,
            'nosort'             => true,
        ];

        $tab[] = [
            'id'                 => '17',
            'table'              => 'glpi_cartridges',
            'field'              => 'id',
            'name'               => __('Number of used cartridges'),
            'datatype'           => 'count',
            'forcegroupby'       => true,
            'usehaving'          => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NOT' => ['NEWTABLE.date_use' => null],
                    'NEWTABLE.date_out' => null,
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '18',
            'table'              => 'glpi_cartridges',
            'field'              => 'id',
            'name'               => __('Number of worn cartridges'),
            'datatype'           => 'count',
            'forcegroupby'       => true,
            'usehaving'          => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NOT' => ['NEWTABLE.date_out' => null]],
            ],
        ];

        $tab[] = [
            'id'                 => '32',
            'table'              => 'glpi_networks',
            'field'              => 'name',
            'name'               => _n('Network', 'Networks', 1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '23',
            'table'              => 'glpi_manufacturers',
            'field'              => 'name',
            'name'               => Manufacturer::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '24',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'linkfield'          => 'users_id_tech',
            'name'               => __('Technician in charge'),
            'datatype'           => 'dropdown',
            'right'              => 'own_ticket',
        ];

        $tab[] = [
            'id'                 => '49',
            'table'              => 'glpi_groups',
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
            'id'                 => '61',
            'table'              => $this->getTable(),
            'field'              => 'template_name',
            'name'               => __('Template name'),
            'datatype'           => 'text',
            'massiveaction'      => false,
            'nosearch'           => true,
            'nodisplay'          => true,
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '82',
            'table'              => $this->getTable(),
            'field'              => 'is_global',
            'name'               => __('Global management'),
            'datatype'           => 'bool',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '83',
            'table'              => self::getTable(),
            'field'              => 'last_inventory_update',
            'name'               => __('Last inventory date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '47',
            'table'              => static::getTable(),
            'field'              => 'uuid',
            'name'               => __('UUID'),
            'datatype'           => 'string',
        ];

        $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

        $tab = array_merge($tab, Item_Devices::rawSearchOptionsToAdd(get_class($this)));

        $tab = array_merge($tab, Printer_CartridgeInfo::rawSearchOptionsToAdd());

        $tab = array_merge($tab, SNMPCredential::rawSearchOptionsToAdd());

        return $tab;
    }


    /**
     * @param $itemtype
     *
     * @return array
     */
    public static function rawSearchOptionsToAdd($itemtype = null)
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'printer',
            'name'               => self::getTypeName(Session::getPluralNumber()),
        ];

        $tab[] = [
            'id'                 => '1431',
            'table'              => Asset_PeripheralAsset::getTable(),
            'field'              => 'id',
            'name'               => _x('quantity', 'Number of printers'),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'datatype'           => 'count',
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'                  => 'itemtype_item',
                'specific_items_id_column'  => 'items_id_asset',
                'specific_itemtype_column'  => 'itemtype_asset',
                'condition'                 => ['NEWTABLE.' . 'itemtype_peripheral' => 'Printer'],
            ],
        ];

        return $tab;
    }


    /**
     * Add a printer. If already exist in trashbin restore it
     *
     * @param $name          the printer's name
     * @param $manufacturer  the software's manufacturer
     * @param $entity        the entity in which the software must be added
     * @param $comment       comment (default '')
     **/
    public function addOrRestoreFromTrash($name, $manufacturer, $entity, $comment = '')
    {
        global $DB;

        //Look for the software by his name in GLPI for a specific entity
        $iterator = $DB->request([
            'SELECT' => ['id', 'is_deleted'],
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'name'         => $name,
                'is_template'  => 0,
                'entities_id'  => $entity,
            ],
        ]);

        if (count($iterator) > 0) {
            //Printer already exists for this entity, get its ID
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
            $ID = $this->addPrinter($name, $manufacturer, $entity, $comment);
        }
        return $ID;
    }


    /**
     * Create a new printer
     *
     * @param string  $name         the printer's name
     * @param string  $manufacturer the printer's manufacturer
     * @param integer $entity       the entity in which the printer must be added
     * @param string  $comment      (default '')
     *
     * @return integer the printer's ID
     **/
    public function addPrinter($name, $manufacturer, $entity, $comment = '')
    {
        global $DB;

        $manufacturer_id = 0;
        if ($manufacturer != '') {
            $manufacturer_id = Dropdown::importExternal('Manufacturer', $manufacturer);
        }

        //If there's a printer in a parent entity with the same name and manufacturer
        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'manufacturers_id'   => $manufacturer_id,
                'name'               => $name,
            ] + getEntitiesRestrictCriteria(self::getTable(), 'entities_id', $entity, true),
        ]);

        if ($printer = $iterator->current()) {
            $id = $printer["id"];
        } else {
            $input["name"]             = $name;
            $input["manufacturers_id"] = $manufacturer_id;
            $input["entities_id"]      = $entity;

            $id = $this->add($input);
        }
        return $id;
    }


    /**
     * Restore a software from trashbin
     *
     * @param $ID  the ID of the software to put in trashbin
     *
     * @return boolean (success)
     **/
    public function removeFromTrash($ID)
    {
        return $this->restore(["id" => $ID]);
    }

    public static function getIcon()
    {
        return "ti ti-printer";
    }
}
