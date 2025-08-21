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
use Glpi\Features\DCBreadcrumbInterface;
use Glpi\Features\StateInterface;
use Glpi\Socket;
use Glpi\SocketModel;

/**
 * Class Cable
 */
class Cable extends CommonDBTM implements AssignableItemInterface, StateInterface
{
    use AssignableItem;
    use Clonable;
    use Glpi\Features\State;

    // From CommonDBTM
    public $dohistory         = true;
    public static $rightname         = 'cable_management';

    public static function getTypeName($nb = 0)
    {
        return _n('Cable', 'Cables', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['assets', self::class];
    }

    public static function getLogServiceName(): string
    {
        return 'management';
    }

    public static function getFieldLabel()
    {
        return self::getTypeName(1);
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong)
         ->addStandardTab(Infocom::class, $ong, $options)
         ->addStandardTab(Item_Ticket::class, $ong, $options)
         ->addStandardTab(Item_Problem::class, $ong, $options)
         ->addStandardTab(Change_Item::class, $ong, $options)
         ->addStandardTab(Log::class, $ong, $options);

        return $ong;
    }

    public function post_getEmpty()
    {
        $this->fields['color'] = '#dddddd';
        $this->fields['itemtype_endpoint_a'] = 'Computer';
        $this->fields['itemtype_endpoint_b'] = 'Computer';
    }

    public function getCloneRelations(): array
    {
        return [
            Infocom::class,
            Item_Ticket::class,
            Item_Problem::class,
            Change_Item::class,
        ];
    }

    public static function getAdditionalMenuLinks()
    {
        $links = [];
        if (static::canView()) {
            $insts = "<i class=\"fas fa-ethernet pointer\" title=\"" . htmlescape(Socket::getTypeName(Session::getPluralNumber()))
            . "\"></i><span class=\"sr-only\">" . htmlescape(Socket::getTypeName(Session::getPluralNumber())) . "</span>";
            $links[$insts] = Socket::getSearchURL(false);
        }
        if (count($links)) {
            return $links;
        }
        return false;
    }

    public static function getAdditionalMenuOptions()
    {
        if (static::canView()) {
            return [
                Socket::class => [
                    'title' => Socket::getTypeName(Session::getPluralNumber()),
                    'page'  => Socket::getSearchURL(false),
                    'links' => [
                        'add'    => '/front/socket.form.php',
                        'search' => '/front/socket.php',
                    ],
                ],
            ];
        }
        return false;
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics'),
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => $this->getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
            'autocomplete'       => true,
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number',
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => 'glpi_cabletypes',
            'field'              => 'name',
            'name'               => _n('Cable type', 'Cable types', 1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => 'glpi_cablestrands',
            'field'              => 'name',
            'name'               => _n('Cable strand', 'Cable strands', 1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => $this->getTable(),
            'field'              => 'otherserial',
            'name'               => __('Inventory number'),
            'datatype'           => 'string',
            'autocomplete'       => true,
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => $this->getTable(),
            'field'              => 'itemtype_endpoint_a',
            'name'               => sprintf(__('%s (%s)'), _n('Associated item type', 'Associated item types', 1), __('Endpoint A')),
            'datatype'           => 'itemtypename',
            'itemtype_list'      => 'socket_types',
            'forcegroupby'       => true,
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '8',
            'table'              => $this->getTable(),
            'field'              => 'items_id_endpoint_b',
            'name'               => sprintf(__('%s (%s)'), _n('Associated item', 'Associated items', 1), __('Endpoint B')),
            'massiveaction'      => false,
            'datatype'           => 'specific',
            'searchtype'         => 'equals',
            'additionalfields'   => ['itemtype_endpoint_b'],
        ];

        $tab[] = [
            'id'                 => '9',
            'table'              => $this->getTable(),
            'field'              => 'itemtype_endpoint_b',
            'name'               => sprintf(__('%s (%s)'), _n('Associated item type', 'Associated item types', 1), __('Endpoint B')),
            'datatype'           => 'itemtypename',
            'itemtype_list'      => 'socket_types',
            'forcegroupby'       => true,
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '10',
            'table'              => $this->getTable(),
            'field'              => 'items_id_endpoint_a',
            'name'               => sprintf(__('%s (%s)'), _n('Associated item', 'Associated items', 1), __('Endpoint A')),
            'massiveaction'      => false,
            'datatype'           => 'specific',
            'searchtype'         => 'equals',
            'additionalfields'   => ['itemtype_endpoint_a'],
        ];

        $tab[] = [
            'id'                 => '11',
            'table'              => SocketModel::getTable(),
            'field'              => 'name',
            'linkfield'          => 'socketmodels_id_endpoint_a',
            'name'               => sprintf(__('%s (%s)'), SocketModel::getTypeName(1), __('Endpoint A')),
            'datatype'           => 'dropdown',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '12',
            'table'              => SocketModel::getTable(),
            'field'              => 'name',
            'linkfield'          => 'socketmodels_id_endpoint_b',
            'name'               => sprintf(__('%s (%s)'), SocketModel::getTypeName(1), __('Endpoint B')),
            'datatype'           => 'dropdown',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '13',
            'table'              => Socket::getTable(),
            'field'              => 'name',
            'linkfield'          => 'sockets_id_endpoint_b',
            'name'               => sprintf(__('%s (%s)'), Socket::getTypeName(1), __('Endpoint B')),
            'datatype'           => 'dropdown',
            'massiveaction'       => false,
        ];

        $tab[] = [
            'id'                 => '14',
            'table'              => Socket::getTable(),
            'field'              => 'name',
            'linkfield'          => 'sockets_id_endpoint_a',
            'name'               => sprintf(__('%s (%s)'), Socket::getTypeName(1), __('Endpoint A')),
            'datatype'           => 'dropdown',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '15',
            'table'              => $this->getTable(),
            'field'              => 'color',
            'name'               => __('Color'),
            'datatype'           => 'color',
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => $this->getTable(),
            'field'              => 'comment',
            'name'               => _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'           => 'text',
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
            'id'                 => '19',
            'table'              => $this->getTable(),
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
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
            'id'                 => '121',
            'table'              => $this->getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
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
            'id'                 => '87',
            'table'              => $this->getTable(),
            'field'              => '_virtual_datacenter_position', // virtual field
            'additionalfields'   => [
                'items_id_endpoint_a',
                'itemtype_endpoint_a',
            ],
            'name'               => sprintf(__('%s (%s)'), __('Data center position'), __('Endpoint A')),
            'datatype'           => 'specific',
            'nosearch'           => true,
            'nosort'             => true,
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '88',
            'table'              => $this->getTable(),
            'field'              => '_virtual_datacenter_position', // virtual field
            'additionalfields'   => [
                'items_id_endpoint_b',
                'itemtype_endpoint_b',
            ],
            'name'               => sprintf(__('%s (%s)'), __('Data center position'), __('Endpoint B')),
            'datatype'           => 'specific',
            'nosearch'           => true,
            'nosort'             => true,
            'massiveaction'      => false,
        ];

        return $tab;
    }


    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;
        switch ($field) {
            case 'items_id_endpoint_a':
                if (isset($values['itemtype_endpoint_a']) && !empty($values['itemtype_endpoint_a'])) {
                    $options['name']  = $name;
                    $options['value'] = $values[$field];
                    return Dropdown::show($values['itemtype_endpoint_a'], $options);
                }
                break;
            case 'items_id_endpoint_b':
                if (isset($values['itemtype_endpoint_b']) && !empty($values['itemtype_endpoint_b'])) {
                    $options['name']  = $name;
                    $options['value'] = $values[$field];
                    return Dropdown::show($values['itemtype_endpoint_b'], $options);
                }
                break;
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }


    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }

        switch ($field) {
            case 'items_id_endpoint_a':
            case 'items_id_endpoint_b':
                $itemtype = $values[str_replace('items_id', 'itemtype', $field)] ?? null;
                if ($itemtype !== null && class_exists($itemtype) && is_a($itemtype, CommonDBTM::class, true)) {
                    if ($values[$field] > 0) {
                        $item = new $itemtype();
                        $item->getFromDB($values[$field]);
                        return "<a href='" . htmlescape($item->getLinkURL()) . "'>" . htmlescape($item->fields['name']) . "</a>";
                    }
                } else {
                    return ' ';
                }
                break;
            case '_virtual_datacenter_position':
                $itemtype = $values['itemtype_endpoint_b'] ?? $values['itemtype_endpoint_a'];
                $items_id = $values['items_id_endpoint_b'] ?? $values['items_id_endpoint_a'];

                if ($itemtype instanceof DCBreadcrumbInterface) {
                    return $itemtype::renderDcBreadcrumb($items_id);
                }
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    /**
     * Print the main form
     *
     * @param integer $ID      Integer ID of the item
     * @param array  $options  Array of possible options:
     *     - target for the Form
     *     - withtemplate : template or basic item
     *
     * @return void|boolean (display) Returns false if there is a rights error.
     **/
    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        TemplateRenderer::getInstance()->display('pages/assets/cable.html.twig', [
            'item'   => $this,
            'params' => $options,
        ]);
        return true;
    }

    public static function getIcon()
    {
        return "ti ti-line";
    }
}
