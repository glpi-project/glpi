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
use Glpi\Features\AssignableItem;
use Glpi\Features\AssignableItemInterface;
use Glpi\Features\Clonable;
use Glpi\Features\Inventoriable;
use Glpi\Features\StateInterface;

class DatabaseInstance extends CommonDBTM implements AssignableItemInterface, StateInterface
{
    use Clonable;
    use Inventoriable;
    use Glpi\Features\State;
    use AssignableItem {
        prepareInputForAdd as prepareInputForAddAssignableItem;
    }

    // From CommonDBTM
    public $dohistory                   = true;
    public static $rightname            = 'database';
    protected $usenotepad               = true;
    protected static $forward_entity_to = ['Database'];

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
            Database::class,
            ManualLink::class,
        ];
    }

    public function useDeletedToLockIfDynamic()
    {
        return false;
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Database instance', 'Database instances', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['management', Database::class, self::class];
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong)
         ->addImpactTab($ong, $options)
         ->addStandardTab(DatabaseInstance::class, $ong, $options)
         ->addStandardTab(Database::class, $ong, $options)
         ->addStandardTab(Infocom::class, $ong, $options)
         ->addStandardTab(Contract_Item::class, $ong, $options)
         ->addStandardTab(Document_Item::class, $ong, $options)
         ->addStandardTab(KnowbaseItem_Item::class, $ong, $options)
         ->addStandardTab(Item_Ticket::class, $ong, $options)
         ->addStandardTab(Item_Problem::class, $ong, $options)
         ->addStandardTab(Change_Item::class, $ong, $options)
         ->addStandardTab(ManualLink::class, $ong, $options)
         ->addStandardTab(Certificate_Item::class, $ong, $options)
         ->addStandardTab(Lock::class, $ong, $options)
         ->addStandardTab(Notepad::class, $ong, $options)
         ->addStandardTab(Domain_Item::class, $ong, $options)
         ->addStandardTab(Appliance_Item::class, $ong, $options)
         ->addStandardTab(Log::class, $ong, $options);
        return $ong;
    }

    public function getDatabases(): array
    {
        global $DB;
        $dbs = [];

        $iterator = $DB->request([
            'FROM' => Database::getTable(),
            'WHERE' => [
                'databaseinstances_id' => $this->fields['id'],
                'is_deleted' => 0,
            ],
        ]);

        foreach ($iterator as $row) {
            $dbs[$row['id']] = $row;
        }

        return $dbs;
    }

    public function showForm($ID, array $options = [])
    {
        TemplateRenderer::getInstance()->display('pages/management/databaseinstance.html.twig', [
            'item' => $this,
            'params' => [
                'canedit' => $this->canUpdateItem(),
            ],
        ]);

        return true;
    }

    public function prepareInputForAdd($input)
    {
        $input = $this->prepareInputForAddAssignableItem($input);
        if ($input === false) {
            return false;
        }
        if (isset($input['date_lastbackup']) && empty($input['date_lastbackup'])) {
            unset($input['date_lastbackup']);
        }
        return $input;
    }

    public function rawSearchOptions()
    {

        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false, // implicit field is id
            'datatype'           => 'number',
        ];

        $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

        $tab[] = [
            'id'                 => '4',
            'table'              => DatabaseInstanceType::getTable(),
            'field'              => 'name',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '168',
            'table'              => self::getTable(),
            'field'              => 'port',
            'name'               => _n('Port', 'Ports', 1),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'integer',
            'joinparams'         => [
                'jointype'           => 'child',
            ],
        ];

        $tab[] = [
            'id'               => '5',
            'table'            => DatabaseInstance::getTable(),
            'field'            => 'items_id',
            'name'             => _n('Item', 'Items', 1),
            'nosearch'         => true,
            'massiveaction'    => false,
            'forcegroupby'     => true,
            'datatype'         => 'specific',
            'searchtype'       => 'equals',
            'additionalfields' => ['itemtype'],
            'joinparams'       => ['jointype' => 'child'],
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => DatabaseInstance::getTable(),
            'field'              => 'version',
            'name'               => _n('Version', 'Versions', 1),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => DatabaseInstance::getTable(),
            'field'              => 'is_active',
            'name'               => __('Is active'),
            'massiveaction'      => false,
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '253',
            'table'              => DatabaseInstance::getTable(),
            'field'              => 'path',
            'name'               => __('Path'),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '8',
            'table'              => DatabaseInstance::getTable(),
            'field'              => 'itemtype',
            'name'               => __('Item type'),
            'massiveaction'      => false,
            'datatype'           => 'itemtypename',
            'types'              => self::getTypes(),
        ];

        $tab[] = [
            'id'                 => '40',
            'table'              => DatabaseInstanceCategory::getTable(),
            'field'              => 'name',
            'name'               => _n('Category', 'Categories', 1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '41',
            'table'              => State::getTable(),
            'field'              => 'completename',
            'name'               => __('Status'),
            'datatype'           => 'dropdown',
            'condition'          => $this->getStateVisibilityCriteria(),
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => $this->getTable(),
            'field'              => 'comment',
            'name'               => _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'           => 'text',
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
            'id'                 => '171',
            'table'              => self::getTable(),
            'field'              => 'date_lastboot',
            'name'               => __('Last boot date'),
            'massiveaction'      => false,
            'datatype'           => 'date',
        ];

        $tab[] = [
            'id'                 => '23',
            'table'              => Manufacturer::getTable(),
            'field'              => 'name',
            'name'               => Manufacturer::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '24',
            'table'              => User::getTable(),
            'field'              => 'name',
            'linkfield'          => 'users_id_tech',
            'name'               => __('Technician in charge'),
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

        $tab = array_merge($tab, Database::rawSearchOptionsToAdd());
        $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

        return $tab;
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }

        switch ($field) {
            case 'items_id':
                $itemtype = $values[str_replace('items_id', 'itemtype', $field)] ?? null;
                if ($itemtype !== null && class_exists($itemtype) && is_a($itemtype, CommonDBTM::class, true)) {
                    if ($values[$field] > 0) {
                        $item = new $itemtype();
                        if ($item->getFromDB($values[$field])) {
                            return "<a href='" . htmlescape($item->getLinkURL()) . "'>" . htmlescape($item->fields['name']) . "</a>";
                        } else {
                            return ' ';
                        }
                    }
                } else {
                    return ' ';
                }
                break;
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    /**
     * Get item types that can be linked to a database
     *
     * @param boolean $all Get all possible types or only allowed ones
     *
     * @return array
     */
    public static function getTypes($all = false): array
    {
        global $CFG_GLPI;

        $types = $CFG_GLPI['databaseinstance_types'];

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

    public function cleanDBonPurge()
    {
        $this->deleteChildrenAndRelationsFromDb(
            [
                Database::class,
            ]
        );
    }

    public function pre_purgeInventory()
    {
        return true;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (
            ($item instanceof CommonDBTM)
            && self::canView()
            && in_array($item->getType(), self::getTypes(true))
        ) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = countElementsInTable(self::getTable(), ['itemtype' => $item->getType(), 'items_id' => $item->fields['id']]);
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::getType());
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$item instanceof CommonDBTM) {
            return false;
        }

        switch ($item->getType()) {
            default:
                if (in_array($item->getType(), self::getTypes())) {
                    self::showInstances($item, $withtemplate);
                }
        }
        return true;
    }

    public static function showInstances(CommonDBTM $item, $withtemplate)
    {
        global $DB;

        $instances = $DB->request([
            'SELECT' => 'id',
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'itemtype' => $item->getType(),
                'items_id' => $item->fields['id'],
            ],
        ]);

        $entries = [];
        $item = new self();
        foreach ($instances as $row) {
            $item->getFromDB($row['id']);
            $databases = $item->getDatabases();
            $databasetype = new DatabaseInstanceType();
            $databasetype_name = '';
            if ($item->fields['databaseinstancetypes_id'] > 0 && $databasetype->getFromDB($item->fields['databaseinstancetypes_id'])) {
                $databasetype_name = $databasetype->fields['name'];
            }
            $manufacturer = new Manufacturer();
            $manufacturer_name = '';
            if ($item->fields['manufacturers_id'] > 0 && $manufacturer->getFromDB($item->fields['manufacturers_id'])) {
                $manufacturer_name = $manufacturer->fields['name'];
            }

            $entries[] = [
                'itemtype' => self::class,
                'id'       => $item->getID(),
                'row_class' => $item->isDeleted() ? 'table-danger' : '',
                'name'     => $item->getLink(),
                'database_count' => sprintf(_n('%1$d database', '%1$d databases', count($databases)), count($databases)),
                'version' => $item->fields['version'],
                'databaseinstancetypes_id' => $databasetype_name,
                'manufacturers_id' => $manufacturer_name,
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'columns' => [
                'name' => __('Name'),
                'database_count' => Database::getTypeName(1),
                'version' => _n('Version', 'Versions', 1),
                'databaseinstancetypes_id' => DatabaseInstanceType::getTypeName(1),
                'manufacturers_id' => Manufacturer::getTypeName(1),
            ],
            'formatters' => [
                'name' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => false,
        ]);
    }

    public static function getIcon()
    {
        return "ti ti-database-import";
    }
}
