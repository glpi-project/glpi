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

/**
 * NetworkName Class
 *
 * represent the internet name of an element.
 * It is compose of the name itself, its domain and one or several IP addresses (IPv4 and/or IPv6).
 * An address can be affected to an item, or can be "free" to be reuse by another item
 * (for instance, in case of maintenance, when you change the network card of a computer,
 *  but not its network information)
 *
 * @since 0.84
 **/
class NetworkName extends FQDNLabel
{
    // From CommonDBChild
    public static $itemtype              = 'itemtype';
    public static $items_id              = 'items_id';
    public $dohistory                    = true;

    protected static $forward_entity_to  = ['IPAddress', 'NetworkAlias'];

    public static $canDeleteOnItemClean  = false;

    public static $checkParentRights     = CommonDBConnexity::HAVE_SAME_RIGHT_ON_ITEM;

    public static $mustBeAttached        = false;

    public static $rightname                   = 'internet';


    public static function getTypeName($nb = 0)
    {
        return _n('Network name', 'Network names', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['config', CommonDropdown::class, self::class];
    }

    public function useDeletedToLockIfDynamic()
    {
        return false;
    }

    public function defineTabs($options = [])
    {
        $ong  = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(NetworkAlias::class, $ong, $options);
        $this->addStandardTab(Lock::class, $ong, $options);
        $this->addStandardTab(Log::class, $ong, $options);

        return $ong;
    }

    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);

        $recursiveItems = $this->recursivelyGetItems();
        if (count($recursiveItems) !== 0) {
            $lastItem               = $recursiveItems[count($recursiveItems) - 1];
            $options['entities_id'] = $lastItem->getField('entities_id');
        }

        $recursive_items_type_data = _n('Associated element', 'Associated elements', Session::getPluralNumber());
        if (count($recursiveItems) > 0) {
            $recursive_items_type_data = static::displayRecursiveItems($recursiveItems, 'Type', false);
        }

        $display_recursive_items_link = static::displayRecursiveItems($recursiveItems, 'Link', false);
        $display_dissociate_btn = false;
        if ((count($recursiveItems) > 0) && static::canUpdate()) {
            $display_dissociate_btn = true;
        }

        TemplateRenderer::getInstance()->display('components/form/networkname.html.twig', [
            'ID'                            => $ID,
            'display_dissociate_btn'        => $display_dissociate_btn,
            'recursive_items_type_data'     => $recursive_items_type_data,
            'display_recursive_items_link'  => $display_recursive_items_link,
            'item'                          => $this,
            'params'                        => $options,
        ]);

        return true;
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '12',
            'table'              => 'glpi_fqdns',
            'field'              => 'fqdn',
            'name'               => FQDN::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '13',
            'table'              => 'glpi_ipaddresses',
            'field'              => 'name',
            'name'               => IPAddress::getTypeName(1),
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
            ],
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '20',
            'table'              => static::getTable(),
            'field'              => 'itemtype',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'itemtypename',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '21',
            'table'              => static::getTable(),
            'field'              => 'items_id',
            'name'               => __('ID'),
            'datatype'           => 'integer',
            'massiveaction'      => false,
        ];

        return $tab;
    }

    /**
     * @param array $tab the array to fill
     * @param array $joinparams
     **/
    public static function rawSearchOptionsToAdd(array &$tab, array $joinparams)
    {
        $tab[] = [
            'id'                  => '126',
            'table'               => 'glpi_ipaddresses',
            'field'               => 'name',
            'name'                => __('IP'),
            'forcegroupby'        => true,
            'searchequalsonfield' => true,
            'massiveaction'       => false,
            'joinparams'          => [
                'jointype'  => 'mainitemtype_mainitem',
                'condition' => ['NEWTABLE.is_deleted' => 0,
                    'NOT' => ['NEWTABLE.name' => ''],
                ],
            ],
        ];

        $tab[] = [
            'id'                  => '127',
            'table'               => 'glpi_networknames',
            'field'               => 'name',
            'name'                => self::getTypeName(Session::getPluralNumber()),
            'forcegroupby'        => true,
            'massiveaction'       => false,
            'joinparams'          => $joinparams,
        ];

        $tab[] = [
            'id'                  => '128',
            'table'               => 'glpi_networkaliases',
            'field'               => 'name',
            'name'                => NetworkAlias::getTypeName(Session::getPluralNumber()),
            'forcegroupby'        => true,
            'massiveaction'       => false,
            'joinparams'          => [
                'jointype'   => 'child',
                'beforejoin' => [
                    'table'      => 'glpi_networknames',
                    'joinparams' => $joinparams,
                ],
            ],
        ];
    }

    /**
     * Update IPAddress database
     *
     * Update IPAddress database to remove old IPs and add new ones.
     **/
    public function post_workOnItem()
    {
        if (
            (isset($this->input['_ipaddresses']))
            && (is_array($this->input['_ipaddresses']))
        ) {
            $input = [
                'itemtype' => 'NetworkName',
                'items_id' => $this->getID(),
            ];
            foreach ($this->input['_ipaddresses'] as $id => $ip) {
                $ipaddress     = new IPAddress();
                $input['name'] = $ip;
                if ($id < 0) {
                    if (!empty($ip)) {
                        $ipaddress->add($input);
                    }
                } else {
                    if (!empty($ip)) {
                        $input['id'] = $id;
                        $ipaddress->update($input);
                        unset($input['id']);
                    } else {
                        $ipaddress->delete(['id' => $id]);
                    }
                }
            }
        }
    }

    public function post_addItem()
    {
        $this->post_workOnItem();
        parent::post_addItem();
    }

    public function post_updateItem($history = true)
    {
        global $DB;

        $this->post_workOnItem();
        if (count($this->updates)) {
            // Update Ticket Tco
            if (
                in_array("itemtype", $this->updates, true)
                || in_array("items_id", $this->updates, true)
            ) {
                $ip = new IPAddress();
                // Update IPAddress
                foreach (
                    $DB->request([
                        'FROM' => 'glpi_ipaddresses',
                        'WHERE' => [
                            'itemtype' => 'NetworkName',
                            'items_id' => $this->getID(),
                        ],
                    ]) as $data
                ) {
                    $ip->update([
                        'id'       => $data['id'],
                        'itemtype' => 'NetworkName',
                        'items_id' => $this->getID(),
                    ]);
                }
            }
        }
        parent::post_updateItem($history);
    }

    public function cleanDBonPurge()
    {
        $this->deleteChildrenAndRelationsFromDb(
            [
                IPAddress::class,
                NetworkAlias::class,
            ]
        );
    }

    /**
     * Detach an address from an item
     *
     * The address can be unaffected, and remain "free"
     *
     * @param integer $items_id  the id of the item
     * @param string  $itemtype  the type of the item
     **/
    public static function unaffectAddressesOfItem($items_id, $itemtype)
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'itemtype'  => $itemtype,
                'items_id'  => $items_id,
            ],
        ]);

        foreach ($iterator as $networkNameID) {
            self::unaffectAddressByID($networkNameID['id']);
        }
    }

    /**
     * Detach an address from an item
     *
     * The address can be unaffected, and remain "free"
     *
     * @param integer $networkNameID the id of the NetworkName
     **/
    public static function unaffectAddressByID($networkNameID)
    {
        return self::affectAddress($networkNameID, 0, '');
    }

    /**
     * @param integer $networkNameID
     * @param integer $items_id
     * @param string $itemtype
     * @return bool
     */
    public static function affectAddress($networkNameID, $items_id, $itemtype)
    {
        $networkName = new self();
        return $networkName->update([
            'id'       => $networkNameID,
            'items_id' => $items_id,
            'itemtype' => $itemtype,
        ]);
    }

    /**
     * @param integer $networkPortID
     * @used-by templates/pages/assets/networkport/form.html.twig
     **/
    public static function showFormForNetworkPort($networkPortID)
    {
        global $DB;

        $name = new self();
        $name->getEmpty();

        if ($networkPortID > 0) {
            $iterator = $DB->request([
                'SELECT' => 'id',
                'FROM'   => self::getTable(),
                'WHERE'  => [
                    'itemtype'     => 'NetworkPort',
                    'items_id'     => $networkPortID,
                    'is_deleted'   => 0,
                ],
            ]);
            $numrows = count($iterator);

            if ($numrows > 1) {
                // language=Twig
                echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                    {% import 'components/form/fields_macros.html.twig' as fields %}
                    {% set alert %}
                        <div class="alert alert-warning">{{ alert }}</div>
                    {% endset %}
                    {{ field.htmlField('', alert, 'NetworkName'|itemtype_name) }}
TWIG, ['alert' => __("Several network names available! Go to the tab 'Network Name' to manage them.")]);
            } elseif ($numrows === 1) {
                $result = $iterator->current();
                $name->getFromDB($result['id']);
            }
        }

        if ($name->isNewItem()) {
            $canedit = $name::canCreate();
        } else {
            $canedit = $name->can($name->getID(), UPDATE);
        }

        TemplateRenderer::getInstance()->display('pages/assets/networkport/networkname_short.html.twig', [
            'item' => $name,
            'canedit' => $canedit,
        ]);
    }

    /**
     * @param $itemtype
     * @param HTMLTableBase $base
     * @param HTMLTableSuperHeader|null $super
     * @param HTMLTableHeader|null $father
     * @param array $options
     * @throws Exception
     * @since 0.84
     *
     */
    public static function getHTMLTableHeader(
        $itemtype,
        HTMLTableBase $base,
        ?HTMLTableSuperHeader $super = null,
        ?HTMLTableHeader $father = null,
        array $options = []
    ) {

        $column_name = self::class;
        if (
            isset($options['massiveactionnetworkname'])
            && $options['massiveactionnetworkname']
        ) {
            $delete_all_column = $base->addHeader(
                'delete',
                Html::getCheckAllAsCheckbox('mass' . self::class . $options['rand']),
                $super,
                $father
            );
            $delete_all_column->setHTMLClass('center');
        }
        if (!isset($options['dont_display'][$column_name])) {
            $content = htmlescape(self::getTypeName());
            if (isset($options['column_links'][$column_name])) {
                $content = '<a href="' . htmlescape($options['column_links'][$column_name]) . '">'
                    . $content
                    . '</a>';
            }
            $father = $base->addHeader($column_name, $content, $super, $father);
            $father->setItemType('NetworkName');

            if (isset($options['display_isDynamic']) && ($options['display_isDynamic'])) {
                $father = $base->addHeader(
                    $column_name . '_dynamic',
                    __s('Automatic inventory'),
                    $super,
                    $father
                );
            }
        }

        NetworkAlias::getHTMLTableHeader(self::class, $base, $super, $father, $options);
        IPAddress::getHTMLTableHeader(self::class, $base, $super, $father, $options);
    }

    /**
     * @param HTMLTableRow|null $row
     * @param CommonDBTM|null $item
     * @param HTMLTableCell|null $father
     * @param array $options
     * @throws Exception
     * @since 0.84
     */
    public static function getHTMLTableCellsForItem(
        ?HTMLTableRow $row = null,
        ?CommonDBTM $item = null,
        ?HTMLTableCell $father = null,
        array $options = []
    ) {
        global $DB;

        $column_name = self::class;

        if ($item === null) {
            if ($father === null) {
                return;
            }
            $item = $father->getItem();
        }

        $table = static::getTable();
        $criteria = [
            'SELECT' => [
                "$table.id",
            ],
            'FROM'   => $table,
            'WHERE'  => [],
        ];

        switch ($item::class) {
            case FQDN::class:
                $criteria['ORDERBY'] = "$table.name";

                if (isset($options['order'])) {
                    switch ($options['order']) {
                        case 'name':
                            break;

                        case 'ip':
                            $criteria['LEFT JOIN'] = [
                                'glpi_ipaddresses'   => [
                                    'glpi_ipaddresses'   => 'items_id',
                                    $table               => 'id', [
                                        'AND' => [
                                            'glpi_ipaddresses.itemtype'   => self::class,
                                            'glpi_ipaddresses.is_deleted' => 0,
                                        ],
                                    ],
                                ],
                            ];
                            $criteria['ORDERBY'] = [
                                new QueryExpression("ISNULL (" . $DB::quoteName('glpi_ipaddresses.id') . ")"),
                                'glpi_ipaddresses.binary_3',
                                'glpi_ipaddresses.binary_2',
                                'glpi_ipaddresses.binary_1',
                                'glpi_ipaddresses.binary_0',
                            ];
                            break;

                        case 'alias':
                            $criteria['LEFT JOIN'] = [
                                'glpi_networkaliases'   => [
                                    'ON'  => [
                                        'glpi_networkaliases'   => 'networknames_id',
                                        $table                  => 'id',
                                    ],
                                ],
                            ];
                            $criteria['ORDERBY'] = [
                                new QueryExpression("ISNULL (" . $DB::quoteName('glpi_networkaliases.name') . ")"),
                                'glpi_networkaliases.name',
                            ];
                            break;
                    }
                }

                $criteria['WHERE'] = [
                    "$table.fqdns_id"    => $item->fields['id'],
                    "$table.is_deleted"  => 0,
                ];
                break;

            case NetworkPort::class:
                $criteria['WHERE'] = [
                    'itemtype'     => NetworkPort::class,
                    'items_id'     => $item->getID(),
                    'is_deleted'   => 0,
                ];
                break;

            case NetworkEquipment::class:
                $criteria['INNER JOIN'] = [
                    'glpi_networkports'  => [
                        'ON'  => [
                            'glpi_networkports'  => 'id',
                            $table               => 'items_id', [
                                'AND' => [
                                    "$table.itemtype"    => 'NetworkPort',
                                    "$table.is_deleted"  => 0,
                                ],
                            ],
                        ],
                    ],
                ];
                $criteria['WHERE'] = [
                    'glpi_networkports.itemtype'  => NetworkEquipment::class,
                    'glpi_networkports.items_id'  => $item->getID(),
                ];
                break;
        }

        if (isset($options['SQL_options'])) {
            $criteria = array_merge($criteria, $options['SQL_options']);
        }

        $createRow            = (isset($options['createRow']) && $options['createRow']);
        $options['createRow'] = false;
        $address              = new self();

        $iterator = $DB->request($criteria);
        foreach ($iterator as $line) {
            if ($address->getFromDB($line["id"])) {
                if ($createRow) {
                    $row = $row->createAnotherRow();
                }

                if (
                    isset($options['massiveactionnetworkname'])
                    && $options['massiveactionnetworkname']
                ) {
                    $header      = $row->getGroup()->getHeaderByName('Internet', 'delete');
                    $cell_value  = Html::getMassiveActionCheckBox(self::class, $line["id"]);
                    $row->addCell($header, $cell_value, $father);
                }

                $internetName = $address->getInternetName();
                if (empty($internetName)) {
                    $internetName = "(" . $line["id"] . ")";
                }
                $content  = htmlescape($internetName);
                if (Session::haveRight('internet', READ)) {
                    $content  = '<a href="' . htmlescape($address->getLinkURL()) . '">'
                        . htmlescape($internetName)
                        . '</a>';
                }

                if (!isset($options['dont_display'][$column_name])) {
                    $header              = $row->getGroup()->getHeaderByName('Internet', $column_name);
                    $name_cell           = $row->addCell($header, $content, $father, $address);
                    if (isset($options['display_isDynamic']) && ($options['display_isDynamic'])) {
                        $dyn_header   = $row->getGroup()->getHeaderByName(
                            'Internet',
                            $column_name . '_dynamic'
                        );
                        $dynamic_cell = $row->addCell(
                            $dyn_header,
                            htmlescape(Dropdown::getYesNo($address->fields['is_dynamic'])),
                            $name_cell
                        );
                        $father_for_children = $dynamic_cell;
                    } else {
                        $father_for_children = $name_cell;
                    }
                } else {
                    $father_for_children = $father;
                }

                NetworkAlias::getHTMLTableCellsForItem($row, $address, $father_for_children, $options);
                IPAddress::getHTMLTableCellsForItem($row, $address, $father_for_children, $options);
            }
        }
    }

    /**
     * Show names for an item from its form
     *
     * Beware that the rendering can be different if readden from direct item form (ie : add new
     * NetworkName, remove, ...) or if readden from item of the item (for instance from the computer
     * form through NetworkPort::ShowForItem).
     *
     * @param CommonDBTM $item
     * @param integer $withtemplate
     * @return false|void
     * @throws Exception
     */
    public static function showForItem(CommonDBTM $item, $withtemplate = 0)
    {
        $ID = $item->getID();
        if (!$item->can($ID, READ)) {
            return false;
        }

        $rand = mt_rand();

        if (
            ($item::class === NetworkPort::class)
            && Session::haveRight('internet', UPDATE)
            && $item->canUpdateItem()
        ) {
            $twig_params = [
                'item' => $item,
                'btn_label' => _x('button', 'Associate'),
                'create_label' => __('Create a new network name'),
                'can_create' => static::canCreate(),
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                {% import 'components/form/fields_macros.html.twig' as fields %}
                <div class="mb-3">
                    <form method="post" action="{{ 'NetworkName'|itemtype_form_path }}">
                        <div class="d-flex">
                            <input type="hidden" name="items_id" value="{{ item.getID() }}">
                            <input type="hidden" name="itemtype" value="{{ get_class(item) }}">
                            <input type="hidden" name="_glpi_csrf_token" value="{{ csrf_token() }}">
                            {{ fields.dropdownField('NetworkName', 'addressID', 0, null, {
                                no_label: true,
                                condition: {
                                    items_id: 0
                                }
                            }) }}
                        </div>
                        <div class="d-flex flex-row-reverse">
                            <button type="submit" name="assign_address" class="btn btn-primary mx-1">{{ btn_label }}</button>
                            {% if can_create %}
                                <a class="btn btn-outline-secondary mx-1" role="button" href="{{ 'NetworkName'|itemtype_form_path }}?items_id={{ item.getID() }}&amp;itemtype={{ get_class(item) }}">
                                    {{ create_label }}
                                </a>
                            {% endif %}
                        </div>
                    </form>
                </div>
TWIG, $twig_params);
        }

        $table_options = ['createRow' => true];
        $start = 0;

        if (
            ($item::class === FQDN::class)
            || ($item::class === NetworkEquipment::class)
        ) {
            if (isset($_GET["start"])) {
                $start = $_GET["start"];
            }

            if (!empty($_GET["order"])) {
                $table_options['order'] = $_GET["order"];
            } else {
                $table_options['order'] = 'name';
            }

            if ($item::class === FQDN::class) {
                $table_options['column_links'] = [
                    'NetworkName' => 'javascript:reloadTab("order=name");',
                    'NetworkAlias' => 'javascript:reloadTab("order=alias");',
                    'IPAddress' => 'javascript:reloadTab("order=ip");',
                ];
            }

            $table_options['SQL_options']  = [
                'LIMIT'  => $_SESSION['glpilist_limit'],
                'START'  => $start,
            ];

            $canedit = false;
        } else {
            $canedit = Session::haveRight('internet', UPDATE) && $item->canUpdateItem();
        }

        $table_options['canedit']                  = false;
        $table_options['rand']                     = $rand;
        $table_options['massiveactionnetworkname'] = $canedit;
        $table                                     = new HTMLTableMain();
        $column                                    = $table->addHeader(
            'Internet',
            htmlescape(self::getTypeName(Session::getPluralNumber()))
        );
        $t_group                                   = $table->createGroup('Main', '');

        self::getHTMLTableHeader(self::class, $t_group, $column, null, $table_options);

        $t_row   = $t_group->createRow();

        self::getHTMLTableCellsForItem($t_row, $item, null, $table_options);

        if ($table->getNumberOfRows() > 0) {
            $number = min($_SESSION['glpilist_limit'], $table->getNumberOfRows());
            Html::printAjaxPager(self::getTypeName(Session::getPluralNumber()), $start, self::countForItem($item));
            Session::initNavigateListItems(
                self::class,
                //TRANS : %1$s is the itemtype name,
                //        %2$s is the name of the item (used for headings of a list)
                sprintf(
                    __('%1$s = %2$s'),
                    $item::getTypeName(1),
                    $item->getName()
                )
            );
            if ($canedit && $number) {
                Html::openMassiveActionsForm('mass' . self::class . $rand);
                $massiveactionparams = [
                    'num_displayed'    => min($_SESSION['glpilist_limit'], $number),
                    'container'        => 'mass' . self::class . $rand,
                ];
                Html::showMassiveActions($massiveactionparams);
            }

            $table->display([
                'display_title_for_each_group'          => false,
                'display_thead'                         => false,
                'display_tfoot'                         => false,
                'display_header_on_foot_for_each_group' => true,
            ]);

            if ($canedit && $number) {
                $massiveactionparams['ontop'] = false;
                Html::showMassiveActions($massiveactionparams);
                Html::closeForm();
            }

            Html::printAjaxPager(self::getTypeName(Session::getPluralNumber()), $start, self::countForItem($item));
        } else {
            echo "<table class='tab_cadre_fixe'><tr><th>" . __s('No network name found') . "</th></tr>";
            echo "</table>";
        }
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        switch ($item::class) {
            case NetworkPort::class:
            case FQDN::class:
            case NetworkEquipment::class:
                self::showForItem($item, $withtemplate);
                break;
        }
        return true;
    }

    /**
     * @param CommonDBTM $item
     * @return int
     */
    public static function countForItem(CommonDBTM $item): int
    {
        global $DB;

        switch ($item::class) {
            case FQDN::class:
                return countElementsInTable(
                    'glpi_networknames',
                    ['fqdns_id'   => $item->fields["id"],
                        'is_deleted' => 0,
                    ]
                );

            case NetworkPort::class:
                return countElementsInTable(
                    'glpi_networknames',
                    ['itemtype'   => $item->getType(),
                        'items_id'   => $item->getID(),
                        'is_deleted' => 0,
                    ]
                );

            case NetworkEquipment::class:
                $result = $DB->request([
                    'SELECT'          => ['COUNT DISTINCT' => 'glpi_networknames.id AS cpt'],
                    'FROM'            => 'glpi_networknames',
                    'INNER JOIN'       => [
                        'glpi_networkports'  => [
                            'ON' => [
                                'glpi_networknames'  => 'items_id',
                                'glpi_networkports'  => 'id', [
                                    'AND' => [
                                        'glpi_networknames.itemtype' => 'NetworkPort',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'WHERE'           => [
                        'glpi_networkports.itemtype'     => $item->getType(),
                        'glpi_networkports.items_id'     => $item->getID(),
                        'glpi_networkports.is_deleted'   => 0,
                        'glpi_networknames.is_deleted'   => 0,
                    ],
                ])->current();

                return (int) $result['cpt'];
        }
        return 0;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (
            ($item instanceof CommonDBTM)
            && $item->getID()
            && $item->can($item->getField('id'), READ)
        ) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = self::countForItem($item);
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::class);
        }
        return '';
    }

    public function getRights($interface = 'central')
    {
        $rights = parent::getRights($interface);
        // Rename READ and UPDATE right labels to match other assets
        $rights[READ] = __('View all');
        $rights[UPDATE] = __('Update all');
        return $rights;
    }
}
