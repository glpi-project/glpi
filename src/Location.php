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
use Glpi\DBAL\QueryUnion;
use Glpi\Features\Clonable;
use Glpi\Socket;

/// Location class
class Location extends CommonTreeDropdown
{
    use MapGeolocation;
    use Clonable;

    // From CommonDBTM
    public $dohistory          = true;
    public $can_be_translated  = true;

    public static $rightname          = 'location';


    public function getAdditionalFields()
    {
        return [
            [
                'name'  => 'code',
                'label' => __('Code'),
                'type'  => 'text',
                'list'  => true,
            ], [
                'name'  => 'alias',
                'label' => __('Alias'),
                'type'  => 'text',
                'list'  => true,
            ], [
                'name'  => self::getForeignKeyField(),
                'label' => __('As child of'),
                'type'  => 'parent',
                'list'  => false,
            ], [
                'name'   => 'address',
                'label'  => __('Address'),
                'type'   => 'text',
                'list'   => true,
            ], [
                'name'   => 'postcode',
                'label'  => __('Postal code'),
                'type'   => 'text',
                'list'   => true,
            ], [
                'name'   => 'town',
                'label'  => __('Town'),
                'type'   => 'text',
                'list'   => true,
            ], [
                'name'   => 'state',
                'label'  => _x('location', 'State'),
                'type'   => 'text',
                'list'   => true,
            ], [
                'name'   => 'country',
                'label'  => __('Country'),
                'type'   => 'text',
                'list'   => true,
            ], [
                'name'  => 'building',
                'label' => __('Building number'),
                'type'  => 'text',
                'list'  => true,
            ], [
                'name'  => 'room',
                'label' => __('Room number'),
                'type'  => 'text',
                'list'  => true,
            ], [
                'name'  => 'latitude',
                'label' => __('Latitude'),
                'type'  => 'text',
                'list'  => true,
            ], [
                'name'  => 'longitude',
                'label' => __('Longitude'),
                'type'  => 'text',
                'list'  => true,
            ], [
                'name'  => 'altitude',
                'label' => __('Altitude'),
                'type'  => 'text',
                'list'  => true,
            ], [
                'name'   => 'setlocation',
                'type'   => 'setlocation',
                'label'  => __('Location on map'),
                'list'   => false,
                'form_params' => [
                    'full_width' => true,
                ],
            ],
        ];
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Location', 'Locations', $nb);
    }

    public static function rawSearchOptionsToAdd()
    {
        $tab = [];

        $tab[] = [
            'id'                 => '3',
            'table'              => 'glpi_locations',
            'field'              => 'completename',
            'name'               => self::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '101',
            'table'              => 'glpi_locations',
            'field'              => 'address',
            'name'               => __('Address'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '102',
            'table'              => 'glpi_locations',
            'field'              => 'postcode',
            'name'               => __('Postal code'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '103',
            'table'              => 'glpi_locations',
            'field'              => 'town',
            'name'               => __('Town'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '104',
            'table'              => 'glpi_locations',
            'field'              => 'state',
            'name'               => _x('location', 'State'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '105',
            'table'              => 'glpi_locations',
            'field'              => 'country',
            'name'               => __('Country'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '106',
            'table'              => 'glpi_locations',
            'field'              => 'code',
            'name'               => __('Location code'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '107',
            'table'              => 'glpi_locations',
            'field'              => 'alias',
            'name'               => __('Location alias'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '91',
            'table'              => 'glpi_locations',
            'field'              => 'building',
            'name'               => __('Building number'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '92',
            'table'              => 'glpi_locations',
            'field'              => 'room',
            'name'               => __('Room number'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '93',
            'table'              => 'glpi_locations',
            'field'              => 'comment',
            'name'               => __('Location comments'),
            'massiveaction'      => false,
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '998',
            'table'              => 'glpi_locations',
            'field'              => 'latitude',
            'name'               => __('Latitude'),
            'massiveaction'      => false,
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '999',
            'table'              => 'glpi_locations',
            'field'              => 'longitude',
            'name'               => __('Longitude'),
            'massiveaction'      => false,
            'datatype'           => 'text',
        ];

        return $tab;
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '11',
            'table'              => 'glpi_locations',
            'field'              => 'building',
            'name'               => __('Building number'),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '12',
            'table'              => 'glpi_locations',
            'field'              => 'room',
            'name'               => __('Room number'),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '15',
            'table'              => 'glpi_locations',
            'field'              => 'address',
            'name'               => __('Address'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '17',
            'table'              => 'glpi_locations',
            'field'              => 'postcode',
            'name'               => __('Postal code'),
            'massiveaction'      => true,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '18',
            'table'              => 'glpi_locations',
            'field'              => 'town',
            'name'               => __('Town'),
            'massiveaction'      => true,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '21',
            'table'              => 'glpi_locations',
            'field'              => 'latitude',
            'name'               => __('Latitude'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '20',
            'table'              => 'glpi_locations',
            'field'              => 'longitude',
            'name'               => __('Longitude'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '22',
            'table'              => 'glpi_locations',
            'field'              => 'altitude',
            'name'               => __('Altitude'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '101',
            'table'              => 'glpi_locations',
            'field'              => 'address',
            'name'               => __('Address'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '102',
            'table'              => 'glpi_locations',
            'field'              => 'postcode',
            'name'               => __('Postal code'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '103',
            'table'              => 'glpi_locations',
            'field'              => 'town',
            'name'               => __('Town'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '104',
            'table'              => 'glpi_locations',
            'field'              => 'state',
            'name'               => _x('location', 'State'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '105',
            'table'              => 'glpi_locations',
            'field'              => 'country',
            'name'               => __('Country'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '106',
            'table'              => 'glpi_locations',
            'field'              => 'code',
            'name'               => __('Location code'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '107',
            'table'              => 'glpi_locations',
            'field'              => 'alias',
            'name'               => __('Location alias'),
            'datatype'           => 'string',
        ];

        return $tab;
    }

    public function defineTabs($options = [])
    {
        $ong = parent::defineTabs($options);
        $this->addImpactTab($ong, $options);
        $this->addStandardTab(Socket::class, $ong, $options);
        $this->addStandardTab(Document_Item::class, $ong, $options);
        $this->addStandardTab(self::class, $ong, $options);

        return $ong;
    }

    public function cleanDBonPurge()
    {
        Rule::cleanForItemAction($this);
        Rule::cleanForItemCriteria($this, '_locations_id%');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$withtemplate) {
            switch ($item::class) {
                case self::class:
                    $ong    = [];
                    $ong[1] = self::createTabEntry(self::getTypeName(Session::getPluralNumber()));
                    $ong[2] = self::createTabEntry(_n('Item', 'Items', Session::getPluralNumber()), icon: 'ti ti-package');
                    return $ong;
            }
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$item instanceof self) {
            return false;
        }

        switch ($tabnum) {
            case 1:
                return $item->showChildren();
            case 2:
                return $item->showItems();
            default:
                return false;
        }
    }

    /**
     * get item location
     *
     * @param CommonDBTM  $item
     *
     * @return Location|null
     **/
    final public static function getFromItem(CommonDBTM $item): ?Location
    {
        if ($item->maybeLocated()) {
            $loc = new self();
            if ($loc->getFromDB($item->fields['locations_id'])) {
                return $loc;
            }
        }
        return null;
    }

    /**
     * Print the HTML array of items for a location
     *
     * @since 0.85
     *
     * @return bool
     **/
    public function showItems(): bool
    {
        global $CFG_GLPI, $DB;

        $locations_id = $this->fields['id'];
        $filters = $_GET['filters'] ?? [];
        $location_types = $CFG_GLPI['location_types'];
        $location_types = array_combine($location_types, array_map(static fn($itemtype) => $itemtype::getTypeName(1), $location_types));
        asort($location_types);

        if (!$this->can($locations_id, READ)) {
            return false;
        }

        $queries = [];
        $itemtypes = (!isset($filters['type']) || in_array('', $filters['type'], true)) ? array_keys($location_types) : $filters['type'];
        foreach ($itemtypes as $itemtype) {
            $item = getItemForItemtype($itemtype);
            if (!$item->maybeLocated()) {
                continue;
            }
            $table = getTableForItemType($itemtype);
            $itemtype_criteria = [
                'SELECT' => [
                    "$table.id",
                    new QueryExpression($DB::quoteValue($itemtype), 'type'),
                ],
                'FROM'   => $table,
                'WHERE'  => [
                    "$table.locations_id"   => $locations_id,
                ] + $item->getSystemSQLCriteria(),
            ];
            if ($item->maybeDeleted()) {
                $itemtype_criteria['WHERE']['is_deleted'] = 0;
            }

            if ($item->isEntityAssign()) {
                $itemtype_criteria['WHERE'] + getEntitiesRestrictCriteria($table, 'entities_id');
            }

            $queries[] = $itemtype_criteria;
        }
        $criteria = count($queries) === 1 ? $queries[0] : ['FROM' => new QueryUnion($queries)];

        $start  = (isset($_REQUEST['start']) ? (int) $_REQUEST['start'] : 0);
        $criteria['START'] = $start;
        $criteria['LIMIT'] = $_SESSION['glpilist_limit'];

        $iterator = $DB->request($criteria);

        // Execute a second request to get the total number of rows
        unset($criteria['SELECT'], $criteria['START'], $criteria['LIMIT']);

        $criteria['COUNT'] = 'total';
        $number = $DB->request($criteria)->current()['total'];

        $entries = [];
        $entity_name_cache = [];
        foreach ($iterator as $data) {
            $item = getItemForItemtype($data['type']);
            $item->getFromDB($data['id']);
            if (!isset($entity_name_cache[$item->getEntityID()])) {
                $entity_name_cache[$item->getEntityID()] = Dropdown::getDropdownName(
                    "glpi_entities",
                    $item->getEntityID()
                );
            }
            $entries[] = [
                'type'         => $item::getTypeName(1),
                'entity'       => $entity_name_cache[$item->getEntityID()],
                'name'         => $item->getLink(),
                'serial'       => $item->fields["serial"] ?? "-",
                'otherserial' => $item->fields["otherserial"] ?? "-",
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'start' => $start,
            'limit' => $_SESSION['glpilist_limit'],
            'is_tab' => true,
            'filters' => $filters,
            'nosort' => true,
            'columns' => [
                'type' => [
                    'label' => _n('Type', 'Types', 1),
                    'filter_formatter' => 'array',
                ],
                'entity' => [
                    'label' => Entity::getTypeName(1),
                    'no_filter' => true,
                ],
                'name' => [
                    'label' => __('Name'),
                    'no_filter' => true,
                ],
                'serial' => [
                    'label' => __('Serial number'),
                    'no_filter' => true,
                ],
                'otherserial' => [
                    'label' => __('Inventory number'),
                    'no_filter' => true,
                ],
            ],
            'columns_values' => [
                'type' => array_merge(['' => __('All')], $location_types),
            ],
            'formatters' => [
                'name' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => $number,
            'filtered_number' => $number,
            'showmassiveactions' => false,
        ]);

        return true;
    }

    public function displaySpecificTypeField($ID, $field = [], array $options = [])
    {
        switch ($field['type']) {
            case 'setlocation':
                $this->showMap();
                break;
            default:
                throw new RuntimeException("Unknown {$field['type']}");
        }
    }

    public static function getIcon()
    {
        return "ti ti-map-pin";
    }

    public function prepareInputForAdd($input)
    {
        $input = parent::prepareInputForAdd($input);
        if (
            empty($input['latitude']) && empty($input['longitude']) && empty($input['altitude'])
            && !empty($input[static::getForeignKeyField()])
        ) {
            $parent = new static();
            $parent->getFromDB($input[static::getForeignKeyField()]);
            $input['latitude'] = $parent->fields['latitude'];
            $input['longitude'] = $parent->fields['longitude'];
            $input['altitude'] = $parent->fields['altitude'];
        }
        return $input;
    }

    public function getCloneRelations(): array
    {
        return [];
    }
}
