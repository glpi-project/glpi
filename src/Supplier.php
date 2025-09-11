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
use Glpi\Features\Clonable;

/**
 * Supplier class (suppliers)
 **/
class Supplier extends CommonDBTM
{
    use AssetImage;
    use Clonable;

    // From CommonDBTM
    public $dohistory           = true;

    public static $rightname           = 'contact_enterprise';
    protected $usenotepad       = true;

    public static function getTypeName($nb = 0)
    {
        return _n('Supplier', 'Suppliers', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['management', self::class];
    }

    public static function getLogDefaultServiceName(): string
    {
        return 'financial';
    }

    public function post_getEmpty()
    {
        $this->fields['is_active'] = 1;
    }

    public function prepareInputForAdd($input)
    {
        $input = parent::prepareInputForAdd($input);
        return $this->managePictures($input);
    }

    public function prepareInputForUpdate($input)
    {
        $input = parent::prepareInputForUpdate($input);
        return $this->managePictures($input);
    }

    public function cleanDBonPurge()
    {
        $this->deleteChildrenAndRelationsFromDb(
            [
                Change_Supplier::class,
                Contact_Supplier::class,
                Contract_Supplier::class,
                Problem_Supplier::class,
                ProjectTaskTeam::class,
                ProjectTeam::class,
                Supplier_Ticket::class,
            ]
        );

        // Ticket rules use suppliers_id_assign
        Rule::cleanForItemAction($this, 'suppliers_id%');
    }

    public function getCloneRelations(): array
    {
        return [
            KnowbaseItem_Item::class,
            ManualLink::class,
        ];
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(Contact_Supplier::class, $ong, $options);
        $this->addStandardTab(Contract_Supplier::class, $ong, $options);
        $this->addStandardTab(Infocom::class, $ong, $options);
        $this->addStandardTab(Document_Item::class, $ong, $options);
        $this->addStandardTab(Item_Ticket::class, $ong, $options);
        $this->addStandardTab(Item_Problem::class, $ong, $options);
        $this->addStandardTab(Change_Item::class, $ong, $options);
        $this->addStandardTab(ManualLink::class, $ong, $options);
        $this->addStandardTab(Notepad::class, $ong, $options);
        $this->addStandardTab(KnowbaseItem_Item::class, $ong, $options);
        $this->addStandardTab(Log::class, $ong, $options);

        return $ong;
    }

    public static function dropdown($options = [])
    {
        $condition = ['is_active' => true];
        $options['condition'] = (isset($options['condition']) ? $options['condition'] + $condition : $condition);
        return Dropdown::show(static::class, $options);
    }

    public function getSpecificMassiveActions($checkitem = null)
    {
        $isadmin = static::canUpdate();
        $actions = parent::getSpecificMassiveActions($checkitem);
        if ($isadmin) {
            $actions['Contact_Supplier' . MassiveAction::CLASS_ACTION_SEPARATOR . 'add']
               = "<i class='" . htmlescape(Contact::getIcon()) . "'></i>" . _sx('button', 'Add a contact');
            $actions['Contract_Supplier' . MassiveAction::CLASS_ACTION_SEPARATOR . 'add']
               = "<i class='" . htmlescape(Contract::getIcon()) . "'></i>" . _sx('button', 'Add a contract');
        }
        return $actions;
    }

    public function rawSearchOptions()
    {
        global $DB;

        $tab = [];

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
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => static::getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number',
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => static::getTable(),
            'field'              => 'address',
            'name'               => __('Address'),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '10',
            'table'              => static::getTable(),
            'field'              => 'fax',
            'name'               => __('Fax'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '11',
            'table'              => static::getTable(),
            'field'              => 'town',
            'name'               => __('City'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '14',
            'table'              => static::getTable(),
            'field'              => 'postcode',
            'name'               => __('Postal code'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '12',
            'table'              => static::getTable(),
            'field'              => 'state',
            'name'               => _x('location', 'State'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '13',
            'table'              => static::getTable(),
            'field'              => 'country',
            'name'               => __('Country'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => static::getTable(),
            'field'              => 'website',
            'name'               => __('Website'),
            'datatype'           => 'weblink',
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => static::getTable(),
            'field'              => 'phonenumber',
            'name'               => Phone::getTypeName(1),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => static::getTable(),
            'field'              => 'email',
            'name'               => _n('Email', 'Emails', 1),
            'datatype'           => 'email',
        ];

        $tab[] = [
            'id'                 => '9',
            'table'              => 'glpi_suppliertypes',
            'field'              => 'name',
            'name'               => SupplierType::getTypeName(1),
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

        if (($_SESSION["glpinames_format"] ?? User::REALNAME_BEFORE) === User::FIRSTNAME_BEFORE) {
            $name1 = 'firstname';
            $name2 = 'name';
        } else {
            $name1 = 'name';
            $name2 = 'firstname';
        }

        $tab[] = [
            'id'                 => '8',
            'table'              => 'glpi_contacts',
            'field'              => 'completename',
            'name'               => _n('Associated contact', 'Associated contacts', Session::getPluralNumber()),
            'forcegroupby'       => true,
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
            'computation'        => QueryFunction::concat(["TABLE.{$name1}", new QueryExpression($DB::quoteValue(' ')), "TABLE.{$name2}"]),
            'computationgroupby' => true,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_contacts_suppliers',
                    'joinparams'         => [
                        'jointype'           => 'child',
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => static::getTable(),
            'field'              => 'comment',
            'name'               => _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'           => 'text',
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
            'id'                 => '86',
            'table'              => static::getTable(),
            'field'              => 'is_recursive',
            'name'               => __('Child entities'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '29',
            'table'              => 'glpi_contracts',
            'field'              => 'name',
            'name'               => _n('Associated contract', 'Associated contracts', Session::getPluralNumber()),
            'forcegroupby'       => true,
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_contracts_suppliers',
                    'joinparams'         => [
                        'jointype'           => 'child',
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '70',
            'table'              => static::getTable(),
            'field'              => 'registration_number',
            'name'               => _x('infocom', 'Administrative number'),
            'datatype'           => 'string',
            'autocomplete'       => true,
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => static::getTable(),
            'field'              => 'is_active',
            'name'               => __('Active'),
            'datatype'           => 'bool',
        ];

        // add objectlock search options
        $tab = array_merge($tab, ObjectLock::rawSearchOptionsToAdd(get_class($this)));

        $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

        return $tab;
    }

    /**
     * Get links for an enterprise (website / edit)
     *
     * @param boolean $withname Also display name ? (false by default)
     **/
    public function getLinks($withname = false)
    {
        $ret = $withname ? ('<span class="ms-3 me-1">' . htmlescape($this->fields["name"]) . '</span>') : '';

        if (!empty($this->fields['website'])) {
            $ret .= "<a class='btn btn-icon btn-outline-secondary' href='" . htmlescape(Toolbox::formatOutputWebLink($this->fields['website'])) . "'
                target='_blank' title=\"" . __s('Web') . "\">
                <i class='ti ti-world' ></i>
                </a>";
        }
        return $ret;
    }

    /**
     * @param class-string<CommonDBTM> $itemtype
     * @return array{linktype: class-string<CommonDBTM>, entities_id: int, name: string, id: int, serial: ?string, otherserial: ?string, is_deleted: 0|1}[]
     */
    private function getInfocomsForItemtype(string $itemtype)
    {
        global $DB;
        if (!($item = getItemForItemtype($itemtype)) || !$item::canView()) {
            return [];
        }

        $linktype  = $itemtype;
        $linkfield = 'id';
        $itemtable = getTableForItemType($itemtype);

        $criteria = [
            'SELECT'       => [],
            'FROM'         => 'glpi_infocoms',
            'INNER JOIN'   => [
                $itemtable  => [
                    'ON' => [
                        'glpi_infocoms'   => 'items_id',
                        $itemtable        => 'id',
                    ],
                ],
            ],
        ];

        // Set $linktype for entity restriction AND link to search engine
        if ($itemtype === Cartridge::class) {
            $criteria['INNER JOIN']['glpi_cartridgeitems'] = [
                'ON' => [
                    'glpi_cartridgeitems'   => 'id',
                    'glpi_cartridges'       => 'cartridgeitems_id',
                ],
            ];

            $linktype  = 'CartridgeItem';
            $linkfield = 'cartridgeitems_id';
        }

        if ($itemtype === Consumable::class) {
            $criteria['INNER JOIN']['glpi_consumableitems'] = [
                'ON' => [
                    'glpi_consumableitems'  => 'id',
                    'glpi_consumables'      => 'consumableitems_id',
                ],
            ];

            $linktype  = 'ConsumableItem';
            $linkfield = 'consumableitems_id';
        }

        if ($itemtype === Item_DeviceControl::class) {
            $criteria['INNER JOIN']['glpi_devicecontrols'] = [
                'ON' => [
                    'glpi_items_devicecontrols'   => 'devicecontrols_id',
                    'glpi_devicecontrols'         => 'id',
                ],
            ];

            $linktype = 'DeviceControl';
            $linkfield = 'devicecontrols_id';
        }

        $linktable = getTableForItemType($linktype);

        $itemtable_fields = [$itemtable . '.' . $linkfield . ' AS id'];
        if ($item->isField('serial')) {
            $itemtable_fields[] = $itemtable . '.serial';
        } else {
            $itemtable_fields[] = new QueryExpression($DB::quoteValue('-'), 'serial');
        }
        if ($item->isField('otherserial')) {
            $itemtable_fields[] = $itemtable . '.otherserial';
        } else {
            $itemtable_fields[] = new QueryExpression($DB::quoteValue('-'), 'otherserial');
        }
        if ($item->maybeDeleted()) {
            $itemtable_fields[] = $itemtable . '.is_deleted';
        } else {
            $itemtable_fields[] = new QueryExpression('0', 'is_deleted');
        }

        $criteria['SELECT'] = [
            new QueryExpression($DB::quoteValue($linktype), 'linktype'),
            'glpi_infocoms.entities_id',
            $linktype::getNameField() . ' AS name',
            ...$itemtable_fields,
        ];

        $where = [
            'glpi_infocoms.itemtype'      => $itemtype,
            'glpi_infocoms.suppliers_id'  => $this->getID(),
        ];
        if ($item->maybeTemplate()) {
            $where[$itemtable . '.is_template'] = 0;
        }
        $criteria['WHERE'] = $where + getEntitiesRestrictCriteria($linktable);

        $criteria['ORDERBY'] = [
            'glpi_infocoms.entities_id',
            "$linktable." . $linktype::getNameField(),
        ];

        return iterator_to_array($DB->request($criteria), false);
    }

    /**
     * Print the HTML array for infocoms linked
     *
     * @return void|false
     **/
    public function showInfocoms()
    {
        $instID = $this->fields['id'];
        if (!$this->can($instID, READ)) {
            return false;
        }

        $types_iterator = Infocom::getTypes(['suppliers_id' => $instID]);
        $columns = [
            'type' => _n('Type', 'Types', 1),
        ];
        if (Session::isMultiEntitiesMode()) {
            $columns['entity'] = Entity::getTypeName(1);
        }
        $columns['name'] = __('Name');
        $columns['serial'] = __('Serial number');
        $columns['otherserial'] = __('Inventory number');
        $datatable_params = [
            'nofilter'      => true,
            'nosort'        => true,
            'nopager'       => true,
            'columns'       => $columns,
            'formatters'    => [
                'name' => 'raw_html',
            ],
        ];

        $num = 0;
        $entries = [];
        $entity_names_cache = [];
        Html::printPagerForm();

        foreach ($types_iterator as $row) {
            $itemtype = $row['itemtype'];
            $items = $this->getInfocomsForItemtype($itemtype);
            $nb = count($items);
            $itemtype_name = $itemtype::getTypeName($nb);

            if ($nb > $_SESSION['glpilist_limit']) {
                $first_item = reset($items);
                $linktype = $first_item['linktype'];
                $link_params = Toolbox::append_params([
                    'order'      => 'ASC',
                    'is_deleted' => 0,
                    'reset'      => 'reset',
                    'start'      => 0,
                    'sort'       => 80,
                    'criteria'   => [
                        0 => [
                            'value'      => '$$$$' . $instID,
                            'searchtype' => 'contains',
                            'field'      => 53,
                        ],
                    ],
                ]);
                $link = $linktype::getSearchURL() . (strpos($linktype::getSearchURL(), '?') ? '&' : '?') . $link_params;
                $entries[] = [
                    'type' => sprintf(__('%1$s: %2$s'), $itemtype::getTypeName($nb), $nb),
                    'name' => '<a href="' . htmlescape($link) . '">' . __s('Device list') . '</a>',
                    'entity' => '',
                    'serial' => '-',
                    'otherserial' => '-',
                ];
            } elseif ($nb) {
                $first = true;
                foreach ($items as $data) {
                    $name = $data['name'];
                    $linktype = $data['linktype'];
                    if ($_SESSION["glpiis_ids_visible"] || empty($data["name"])) {
                        $name = sprintf(__('%1$s (%2$s)'), $name, $data['id']);
                    }
                    $link = htmlescape($linktype::getFormURLWithID($data['id']));
                    $name = "<a href='$link'>" . htmlescape($name) . "</a>";

                    if (!isset($entity_names_cache[$data["entities_id"]])) {
                        $entity_names_cache[$data["entities_id"]] = Dropdown::getDropdownName("glpi_entities", $data["entities_id"]);
                    }
                    $entries[] = [
                        'row_class' => $data['is_deleted'] ? 'table-deleted' : '',
                        'type' => $first ? sprintf(__('%1$s: %2$s'), $itemtype_name, $nb) : '',
                        'entity' => $entity_names_cache[$data["entities_id"]],
                        'name' => $name,
                        'serial' => $data['serial'],
                        'otherserial' => $data['otherserial'],
                    ];
                    $first = false;
                }
            }
            $num += $nb;
        }
        $datatable_params['entries'] = $entries;
        $datatable_params['total_number'] = $num;
        $datatable_params['footers'] = [
            [
                sprintf(__s('%1$s = %2$s'), __s('Total'), $num),
            ],
        ];
        TemplateRenderer::getInstance()->display('components/datatable.html.twig', $datatable_params);
    }

    /**
     * Get suppliers matching a given email
     *
     * @since 9.5
     *
     * @param string $email Also display name ? (false by default)
     **/
    public static function getSuppliersByEmail($email)
    {
        global $DB;

        return $DB->request([
            'SELECT' => ["id"],
            'FROM' => 'glpi_suppliers',
            'WHERE' => ['email' => $email],
        ]);
    }

    public static function getIcon()
    {
        return "ti ti-truck-loading";
    }
}
