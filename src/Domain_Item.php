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
use Glpi\DBAL\QueryFunction;

class Domain_Item extends CommonDBRelation
{
    // From CommonDBRelation
    public static $itemtype_1 = "Domain";
    public static $items_id_1 = 'domains_id';

    public static $itemtype_2 = 'itemtype';
    public static $items_id_2 = 'items_id';

    public static function getTypeName($nb = 0)
    {
        return _n('Domain item', 'Domain items', $nb);
    }

    public static function cleanForItem(CommonDBTM $item)
    {
        $temp = new self();
        $temp->deleteByCriteria(
            ['itemtype' => $item->getType(),
                'items_id' => $item->getField('id'),
            ]
        );
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$item instanceof CommonDBTM) {
            return '';
        }

        if ($item instanceof Domain && count(Domain::getTypes(false))) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = self::countForDomain($item);
            }
            return self::createTabEntry(_n('Associated item', 'Associated items', Session::getPluralNumber()), $nb, $item::getType(), 'ti ti-package');
        }

        if (
            $item instanceof DomainRelation || (in_array($item::class, Domain::getTypes(true), true)
                && Session::haveRight('domain', READ))
        ) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = self::countForItem($item);
            }
            return self::createTabEntry(Domain::getTypeName(Session::getPluralNumber()), $nb, $item::getType());
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$item instanceof CommonDBTM) {
            return false;
        }

        if ($item instanceof Domain) {
            self::showForDomain($item);
        } elseif (
            $item instanceof DomainRelation
            || in_array($item::class, Domain::getTypes(true), true)
        ) {
            self::showForItem($item);
        }
        return true;
    }

    public static function countForDomain(Domain $item)
    {
        $types = $item->getTypes();
        if (count($types) === 0) {
            return 0;
        }
        return countElementsInTable(
            'glpi_domains_items',
            [
                "domains_id"   => $item->getID(),
                "itemtype"     => $types,
            ]
        );
    }

    public static function countForItem(CommonDBTM $item)
    {
        if ($item instanceof DomainRelation) {
            $criteria = ['domainrelations_id' => $item->fields['id']];
        } else {
            $criteria = [
                'itemtype'  => $item::class,
                'items_id'  => $item->fields['id'],
            ];
        }

        return countElementsInTable(
            self::getTable(),
            $criteria
        );
    }

    public function getFromDBbyDomainsAndItem($domains_id, $items_id, $itemtype)
    {
        $criteria = ['domains_id' => $domains_id];

        if (is_a($itemtype, DomainRelation::class, true)) {
            $criteria += ['domainrelations_id' => $items_id];
        } else {
            $criteria += [
                'itemtype'  => $itemtype,
                'items_id'  => $items_id,
            ];
        }

        return $this->getFromDBByCrit($criteria);
    }

    public function addItem($values)
    {
        $this->add([
            'domains_id'         => $values['domains_id'],
            'items_id'           => $values['items_id'],
            'itemtype'           => $values['itemtype'],
            'domainrelations_id' => $values['domainrelations_id'],
        ]);
    }

    public function deleteItemByDomainsAndItem($domains_id, $items_id, $itemtype)
    {
        if ($this->getFromDBbyDomainsAndItem($domains_id, $items_id, $itemtype)) {
            $this->delete(['id' => $this->fields["id"]]);
        }
    }

    /**
     * Show items linked to a domain
     *
     * @param Domain $domain Domain object
     *
     * @return void|boolean (display) Returns false if there is a rights error.
     **/
    public static function showForDomain(Domain $domain)
    {
        global $DB;

        $instID = $domain->fields['id'];
        if (!$domain->can($instID, READ)) {
            return false;
        }
        $canedit = $domain->can($instID, UPDATE);
        $rand    = mt_rand();

        $iterator = $DB->request([
            'SELECT'    => 'itemtype',
            'DISTINCT'  => true,
            'FROM'      => self::getTable(),
            'WHERE'     => ['domains_id' => $instID],
            'ORDER'     => 'itemtype',
            'LIMIT'     => count(Domain::getTypes(true)),
        ]);

        if ($canedit) {
            $twig_params = [
                'domain' => $domain,
                'itemtypes' => Domain::getTypes(true),
                'entity_restrict' => $domain->fields['is_recursive']
                    ? getSonsOf('glpi_entities', $domain->fields['entities_id'])
                    : $domain->fields['entities_id'],
                'btn_msg' => _x('button', 'Add'),
                'items_field_label' => _n('Item', 'Items', 1),
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                {% import 'components/form/fields_macros.html.twig' as fields %}
                {% import 'components/form/basic_inputs_macros.html.twig' as inputs %}
                {% set rand = random() %}
                <div class="mb-3">
                    <form name="domain_form{{ rand }}" id="domain_form{{ rand }}" method="post"
                          action="{{ 'Domain'|itemtype_form_path }}" data-submit-once>
                        {{ inputs.hidden('_glpi_csrf_token', csrf_token()) }}
                        {{ inputs.hidden('domains_id', domain.getID()) }}

                        <div class="d-flex">
                            {{ fields.dropdownItemsFromItemtypes('', items_field_label, {
                                itemtypes: itemtypes,
                                entity_restrict: entity_restrict,
                                checkright: true
                            }) }}
                            {{ fields.dropdownField('DomainRelation', 'domainrelations_id', constant('DomainRelation::BELONGS'), 'DomainRelation'|itemtype_name, {
                                display_emptychoice: false
                            }) }}
                        </div>
                        <div class="d-flex flex-row-reverse pe-3">
                            {{ inputs.submit('additem', btn_msg, 'btn-primary') }}
                        </div>
                    </form>
                </div>
TWIG, $twig_params);
        }

        $entries = [];
        $entity_names = [];
        $relation_names = [];
        foreach ($iterator as $data) {
            $itemtype = $data['itemtype'];
            if (!($item = getItemForItemtype($itemtype))) {
                continue;
            }
            if (!$item::canView()) {
                continue;
            }

            $itemtype_name = $item::getTypeName(1);

            $itemTable = getTableForItemType($itemtype);
            $linked_criteria = [
                'SELECT' => [
                    "$itemTable.*",
                    'glpi_domains_items.id AS items_id',
                    'glpi_domains_items.domainrelations_id',
                    'glpi_entities.id AS entity',
                ],
                'FROM'   => self::getTable(),
                'INNER JOIN'   => [
                    $itemTable  => [
                        'ON'  => [
                            $itemTable  => 'id',
                            self::getTable()  => 'items_id',
                        ],
                    ],
                ],
                'LEFT JOIN'    => [
                    'glpi_entities'   => [
                        'ON'  => [
                            'glpi_entities'   => 'id',
                            $itemTable        => 'entities_id',
                        ],
                    ],
                ],
                'WHERE'        => [
                    self::getTable() . '.itemtype'   => $itemtype,
                    self::getTable() . '.domains_id' => $instID,
                ] + getEntitiesRestrictCriteria($itemTable, '', '', $item->maybeRecursive()),
            ];

            if ($item->maybeTemplate()) {
                $linked_criteria['WHERE']["$itemTable.is_template"] = 0;
            }

            $linked_iterator = $DB->request($linked_criteria);

            foreach ($linked_iterator as $linked_data) {
                $item->getFromDB($linked_data["id"]);

                $name = $linked_data["name"];
                if ($_SESSION["glpiis_ids_visible"] || $name === '') {
                    $name .= " (" . $linked_data["id"] . ")";
                }

                $entry = [
                    'itemtype' => self::class,
                    'id'       => $linked_data['items_id'], // items_id is actually the ID for the link
                    'row_class' => isset($linked_data['is_deleted']) && $linked_data['is_deleted'] ? 'table-danger' : '',
                    'type'     => $itemtype_name,
                    'name'     => sprintf(
                        '<a href="%s">%s</a>',
                        htmlescape($itemtype::getFormURLWithID($linked_data['id'])),
                        htmlescape($name)
                    ),
                    'serial'   => $linked_data["serial"] ?? '-',
                    'otherserial' => $linked_data["otherserial"] ?? '-',
                ];
                if (Session::isMultiEntitiesMode()) {
                    if (!isset($entity_names[$linked_data['entity']])) {
                        $entity_names[$linked_data['entity']] = Dropdown::getDropdownName("glpi_entities", $linked_data['entity']);
                    }
                    $entry['entity'] = $entity_names[$linked_data['entity']];
                }
                if (!isset($relation_names[$linked_data['domainrelations_id']])) {
                    $relation_names[$linked_data['domainrelations_id']] = Dropdown::getDropdownName("glpi_domainrelations", $linked_data['domainrelations_id']);
                }
                $entry['domainrelations_id'] = $relation_names[$linked_data['domainrelations_id']];
                $entries[] = $entry;
            }
        }

        $columns = [
            'type' => _n('Type', 'Types', 1),
            'name' => __('Name'),
        ];
        if (Session::isMultiEntitiesMode()) {
            $columns['entity'] = Entity::getTypeName(1);
        }
        $columns['domainrelations_id'] = DomainRelation::getTypeName(1);
        $columns['serial'] = __('Serial number');
        $columns['otherserial'] = __('Inventory number');

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'columns' => $columns,
            'formatters' => [
                'name' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . $rand,
            ],
        ]);
    }

    /**
     * Get links between the given item and domains.
     *
     * @param CommonDBTM $item
     * @return DBmysqlIterator
     */
    public static function getForItem(CommonDBTM $item): DBmysqlIterator
    {
        global $DB;

        $criteria = [
            'SELECT'    => [
                'glpi_domains_items.id AS assocID',
                'glpi_domains_items.domainrelations_id',
                'glpi_domains_items.is_deleted',
                'glpi_domains_items.is_dynamic',
                'glpi_entities.id AS entity',
                'glpi_domains.name AS assocName',
                'glpi_domains.*',
                QueryFunction::groupConcat(
                    expression: Group_Item::getTable() . '.groups_id',
                    separator: ',',
                    alias: 'groups_id_tech',
                ),
            ],
            'FROM'      => self::getTable(),
            'LEFT JOIN' => [
                Domain::getTable()   => [
                    'ON'  => [
                        Domain::getTable()   => 'id',
                        self::getTable()     => 'domains_id',
                    ],
                ],
                Entity::getTable()   => [
                    'ON'  => [
                        Domain::getTable()   => 'entities_id',
                        Entity::getTable()   => 'id',
                    ],
                ],
                Group_Item::getTable() => [
                    'ON'  => [
                        Group_Item::getTable() => 'items_id',
                        Domain::getTable()       => 'id', [
                            'AND' => [
                                Group_Item::getTable() . '.itemtype' => Domain::class,
                                Group_Item::getTable() . '.type' => Group_Item::GROUP_TYPE_TECH,
                            ],
                        ],
                    ],
                ],
            ],
            'WHERE'     => [],//to be filled
            'ORDER'     => 'assocName',
            'GROUPBY' => [
                'glpi_domains_items.id',
            ],
        ];

        if ($item instanceof DomainRelation) {
            $criteria['WHERE'] = ['glpi_domains_items.domainrelations_id' => $item->getID()];
        } else {
            $criteria['WHERE'] = [
                'glpi_domains_items.itemtype' => $item::class,
                'glpi_domains_items.items_id' => $item->getID(),
            ];
        }
        $criteria['WHERE'] += getEntitiesRestrictCriteria(Domain::getTable(), '', '', true);

        $criteria['WHERE']
            //deleted and dynamic domain_item are displayed from lock tab
            //non dynamic domain_item are always displayed
            += [
                'OR'  => [
                    'AND' => [
                        "glpi_domains_items.is_deleted" => 0,
                        "glpi_domains_items.is_dynamic" => 1,
                    ],
                    "glpi_domains_items.is_dynamic" => 0,
                ],
            ];

        return $DB->request($criteria);
    }

    /**
     * Show domains associated to an item
     *
     * @param CommonDBTM $item      Object for which associated domains must be displayed
     * @param integer $withtemplate
     *
     * @return void|false
     */
    public static function showForItem(CommonDBTM $item, $withtemplate = 0)
    {
        global $DB;

        $ID = $item->getField('id');

        if ($item->isNewID($ID) || !Session::haveRight('domain', READ)) {
            return false;
        }

        if (!$item->can($item->fields['id'], READ)) {
            return false;
        }

        if ($withtemplate < 0) {
            $withtemplate = 0;
        }

        $canedit      = $item->canAddItem('Domain');
        $rand         = mt_rand();
        $is_recursive = $item->isRecursive();

        $iterator = static::getForItem($item);

        $domains = [];
        $domain  = new Domain();
        $used    = [];
        foreach ($iterator as $data) {
            $domains[$data['assocID']] = $data;
            $used[$data['id']]         = $data['id'];
        }

        if (
            !($item instanceof DomainRelation)
            && $canedit
            && $withtemplate < 2
        ) {
            // Restrict entity for knowbase
            $entities = "";
            $entity   = $_SESSION["glpiactive_entity"];

            if ($item->isEntityAssign()) {
                // Case of personal items : entity = -1 : create on active entity (Reminder case))
                if ($item->getEntityID() >= 0) {
                    $entity = $item->getEntityID();
                }

                if ($item->isRecursive()) {
                    $entities = getSonsOf('glpi_entities', $entity);
                } else {
                    $entities = $entity;
                }
            }

            $domain_iterator = $DB->request([
                'COUNT'  => 'cpt',
                'FROM'   => Domain::getTable(),
                'WHERE'  => ['is_deleted' => 0] + getEntitiesRestrictCriteria(Domain::getTable(), '', $entities, true),
            ]);
            $result = $domain_iterator->current();
            $nb     = $result['cpt'];

            $twig_params = [
                'used' => $used,
                'nb'   => $nb,
                'entity' => $entity,
                'entities' => $entities,
                'is_recursive' => $is_recursive,
                'item'   => $item,
                'btn_msg' => __('Associate a domain'),
                'helper' => sprintf(__('%s that are already associated are not displayed'), Domain::getTypeName(Session::getPluralNumber())),
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                {% import 'components/form/fields_macros.html.twig' as fields %}
                {% import 'components/form/basic_inputs_macros.html.twig' as inputs %}
                {% set rand = random() %}
                <div class="mb-3">
                    <form name="domain_form{{ rand }}" id="domain_form{{ rand }}" method="post"
                          action="{{ 'Domain'|itemtype_form_path }}" data-submit-once>
                        {{ inputs.hidden('_glpi_csrf_token', csrf_token()) }}
                        {{ inputs.hidden('entities_id', entity) }}
                        {{ inputs.hidden('is_recursive', is_recursive ? '1' : '0') }}
                        {{ inputs.hidden('itemtype', item.getType()) }}
                        {{ inputs.hidden('items_id', item.getID()) }}
                        {% if item.getType() == 'Ticket' %}
                            {{ inputs.hidden('tickets_id', item.getID()) }}
                        {% endif %}

                        <div class="d-flex">
                            {% set domain_dropdown = call('Domain::dropdownDomains', [{
                                entity: entities,
                                used: used,
                                display: false
                            }]) %}
                            {{ fields.htmlField('', domain_dropdown, 'Domain'|itemtype_name, {
                                helper: helper
                            }) }}
                            {{ fields.dropdownField('DomainRelation', 'domainrelations_id', constant('DomainRelation::BELONGS'), 'DomainRelation'|itemtype_name, {
                                display_emptychoice: false
                            }) }}
                        </div>
                        <div class="d-flex flex-row-reverse pe-3">
                            {{ inputs.submit('additem', btn_msg, 'btn-primary') }}
                        </div>
                    </form>
                </div>
TWIG, $twig_params);
        }

        // Some caches to avoid redundant DB requests
        $entity_names = [];
        $user_names = [];
        $group_names = [];
        $type_names = [];
        $relation_names = [];

        $entries = [];
        foreach ($domains as $data) {
            $domainID = $data["id"];
            $link     = htmlescape(NOT_AVAILABLE);

            if ($domain->getFromDB($domainID)) {
                $link = $domain->getLink();
            }

            if (Session::isMultiEntitiesMode() && !isset($entity_names[$data['entity']])) {
                $entity_names[$data['entity']] = Dropdown::getDropdownName(table: "glpi_entities", id: $data['entity'], default: '');
            }

            $groups = explode(',', $data['groups_id_tech'] ?? '');
            $entry_groups = [];
            foreach ($groups as $group) {
                if (!isset($group_names[$group])) {
                    $group_names[$group] = Dropdown::getDropdownName(table: "glpi_groups", id: (int) $group, default: '');
                }
                $entry_groups[] = $group_names[$group];
            }
            if (!isset($user_names[$data['users_id_tech']])) {
                $user_names[$data['users_id_tech']] = getUserName($data['users_id_tech']);
            }
            if (!isset($type_names[$data['domaintypes_id']])) {
                $type_names[$data['domaintypes_id']] = Dropdown::getDropdownName(table: "glpi_domaintypes", id: $data['domaintypes_id'], default: '');
            }
            if (!$item instanceof DomainRelation && !isset($relation_names[$data['domainrelations_id']])) {
                $relation_names[$data['domainrelations_id']] = Dropdown::getDropdownName(table: "glpi_domainrelations", id: $data['domainrelations_id'], default: '');
            }

            $expiration = htmlescape(Html::convDate($data["date_expiration"]));
            if (
                !empty($data["date_expiration"])
                && $data["date_expiration"] <= date('Y-m-d')
            ) {
                $expiration = "<span class='table-deleted'>{$expiration}</span>";
            } elseif (empty($data["date_expiration"])) {
                $expiration = __s('Does not expire');
            }

            $entries[] = [
                'itemtype' => self::class,
                'id'       => $data['assocID'],
                'row_class' => $data['is_deleted'] ? 'table-danger' : '',
                'name'     => $link,
                'entities_id' => $entity_names[$data['entity']] ?? '',
                'groups_id_tech' => implode("\n", $entry_groups),
                'users_id_tech' => $user_names[$data['users_id_tech']] ?? '',
                'domaintypes_id' => $type_names[$data['domaintypes_id']] ?? '',
                'domainrelations_id' => $relation_names[$data['domainrelations_id']] ?? '',
                'date_creation' => $data["date_creation"],
                'date_expiration' => $expiration,
                'is_dynamic' => Dropdown::getYesNo($data['is_dynamic']),
            ];
        }

        $columns = [
            'name' => __('Name'),
        ];
        if (Session::isMultiEntitiesMode()) {
            $columns['entities_id'] = Entity::getTypeName(1);
        }
        $columns += [
            'groups_id_tech' => __('Group in charge'),
            'users_id_tech' => __('Technician in charge'),
            'domaintypes_id' => _n('Type', 'Types', 1),
        ];
        if (!$item instanceof DomainRelation) {
            $columns['domainrelations_id'] = DomainRelation::getTypeName(1);
        }
        $columns += [
            'date_creation' => __('Creation date'),
            'date_expiration' => __('Expiration date'),
            'is_dynamic' => __('Dynamic'),
        ];

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'columns' => $columns,
            'formatters' => [
                'name' => 'raw_html',
                'date_creation' => 'date',
                'date_expiration' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit && ($withtemplate < 2),
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . $rand,
            ],
        ]);
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => '2',
            'table'              => DomainRelation::getTable(),
            'field'              => 'name',
            'name'               => DomainRelation::getTypeName(),
            'datatype'           => 'itemlink',
            'itemlink_type'      => static::class,
        ];

        return $tab;
    }
}
