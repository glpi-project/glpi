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
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use Glpi\DBAL\QueryUnion;

use function Safe\ob_get_clean;
use function Safe\ob_start;

/**
 * CommonItilObject_Item Class
 *
 * Relation between CommonItilObject_Item and Items
 */
abstract class CommonItilObject_Item extends CommonDBRelation
{
    public static function getIcon()
    {
        return 'ti ti-package';
    }

    public function getForbiddenStandardMassiveAction()
    {
        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }

    public function canCreateItem(): bool
    {
        $obj = getItemForTable(static::$itemtype_1);

        if ($obj->canUpdateItem()) {
            return true;
        }

        return parent::canCreateItem();
    }

    private function updateItemTCO(): void
    {
        //TODO Costs for changes and problems should probably affect TCO too but there should also be a way to handle costs affecting multiple assets
        //Example, A ticket with a cost of $400 with two computers shouldn't add $400 cost of ownership to both.
        $cost_class = match (static::$itemtype_1) {
            'Ticket' => TicketCost::class,
            //'Change' => ChangeCost::class,
            //'Problem' => ProblemCost::class,
            default => null
        };
        if ($cost_class) {
            $cost_obj = new $cost_class();
            $cost_obj->updateTCOItem($this->fields['itemtype'], $this->fields['items_id']);
        }
    }

    public function post_addItem()
    {
        $this->updateItemTCO();
        $obj = getItemForTable(static::$itemtype_1);
        $input  = [
            'id'            => $this->fields[static::$items_id_1],
            'date_mod'      => $_SESSION["glpi_currenttime"],
        ];

        if (!isset($this->input['_do_notif']) || $this->input['_do_notif']) {
            $input['_forcenotif'] = true;
        }
        if (isset($this->input['_disablenotif']) && $this->input['_disablenotif']) {
            $input['_disablenotif'] = true;
        }

        $obj->update($input);
        parent::post_addItem();
    }

    public function post_purgeItem()
    {
        $this->updateItemTCO();
        $obj = getItemForItemtype(static::$itemtype_1);
        $input = [
            'id'            => $this->fields[static::$items_id_1],
            'date_mod'      => $_SESSION["glpi_currenttime"],
        ];

        if (!isset($this->input['_do_notif']) || $this->input['_do_notif']) {
            $input['_forcenotif'] = true;
        }
        $obj->update($input);

        parent::post_purgeItem();
    }

    public function prepareInputForAdd($input)
    {
        // Avoid duplicate entry
        if (
            countElementsInTable(
                static::getTable(),
                [
                    'WHERE' => [
                        static::$items_id_1 => $input[static::$items_id_1],
                        static::$itemtype_2   => $input[static::$itemtype_2],
                        static::$items_id_2   => $input[static::$items_id_2],
                    ],
                    'LIMIT' => 1,
                ]
            ) > 0
        ) {
            return false;
        }

        if (!is_subclass_of(static::$itemtype_1, CommonITILObject::class)) {
            return parent::prepareInputForAdd($input);
        }

        /** @var CommonITILObject $itil */
        $itil = new static::$itemtype_1();
        $item = getItemForItemtype($input["itemtype"]);

        // Process rules based on linked item location if needed
        if (
            $itil->getFromDB($input[static::$items_id_1])
            && empty($itil->fields['locations_id'])
            && !$itil->isClosed() // Do not allow rules to modify a closed ITIL item
            && $item->getFromDB($input["items_id"])
            && $item->maybeLocated()
        ) {
            $itil->fields['_locations_id_of_item'] = $item->fields['locations_id'];

            $rules = $itil::getRuleCollectionClassInstance((int) $itil->getEntityID());
            $itil->fields = $rules->processAllRules(
                $itil->fields,
                $itil->fields,
                ['recursive' => true]
            );
            unset($itil->fields['_locations_id_of_item']);
            // Update only the location field
            $itil->updateInDB(['locations_id']);
        }

        return parent::prepareInputForAdd($input);
    }

    /**
     * Print the HTML ajax associated item add
     *
     * @param CommonITILObject|TicketRecurrent $obj  object holding the item
     * @param array $options
     *    - id                  : ID of the object holding the items
     *    - _users_id_requester : ID of the requester user
     *    - items_id            : array of elements (itemtype => array(id1, id2, id3, ...))
     *
     * @return void|false
     */
    protected static function displayItemAddForm(CommonITILObject|TicketRecurrent $obj, array $options = [])
    {
        if (!($obj instanceof static::$itemtype_1)) {
            return false;
        }

        $params = [
            'id'                  => $obj->getID(),
            'entities_id'         => $obj->getEntityID(),
            '_users_id_requester' => 0,
            'items_id'            => [],
            'itemtype'            => '',
            '_canupdate'          => false,
        ];

        foreach ($options as $key => $val) {
            if (!empty($val)) {
                $params[$key] = $val;
            }
        }

        if (!$obj->can($params['id'], READ)) {
            return false;
        }

        $is_closed = in_array($obj->fields['status'], $obj->getClosedStatusArray());
        $canedit   = $obj->can($params['id'], UPDATE) && $params['_canupdate'] && !$is_closed;
        $usedcount = 0;
        // ITIL Object update case
        if ($params['id'] > 0) {
            // Get associated elements for obj
            $used = static::getUsedItems($params['id']);
            foreach ($used as $itemtype => $items) {
                foreach ($items as $items_id) {
                    if (
                        !isset($params['items_id'][$itemtype])
                        || !in_array($items_id, $params['items_id'][$itemtype])
                    ) {
                        $params['items_id'][$itemtype][] = $items_id;
                    }
                    ++$usedcount;
                }
            }
        }

        $rand  = mt_rand();
        $count = 0;
        $twig_params = [
            'rand'               => $rand,
            'item_class'         => static::class,
            'can_edit'           => $canedit,
            'my_items_dropdown'  => '',
            'all_items_dropdown' => '',
            'items_to_add'       => [],
            'params'             => $params,
            'opt'                => [],
        ];

        $class_template = $obj::class . "Template";
        if (is_a($class_template, ITILTemplate::class, true)) {
            $class_template_lower = '_' . strtolower($class_template);
            // Get ITIL object template
            $tt = new $class_template($class_template);
            if (isset($options[$class_template_lower])) {
                $tt  = $options[$class_template_lower];
                if (isset($tt->fields['id'])) {
                    $twig_params['opt']['templates_id'] = $tt->fields['id'];
                }
            } elseif (isset($options['templates_id'])) {
                $tt->getFromDBWithData($options['templates_id']);
                if (isset($tt->fields['id'])) {
                    $twig_params['opt']['templates_id'] = $tt->fields['id'];
                }
            }
        }

        // Show associated item dropdowns
        if ($canedit) {
            $p = [
                'used'       => $params['items_id'],
                'rand'       => $rand,
                static::$items_id_1 => $params['id'],
            ];
            // My items
            if ($params['_users_id_requester'] > 0) {
                ob_start();
                static::dropdownMyDevices($params['_users_id_requester'], $params['entities_id'], $params['itemtype'], 0, $p);
                $twig_params['my_items_dropdown'] = ob_get_clean();
            }
            // Global search
            ob_start();
            static::dropdownAllDevices("itemtype", $params['itemtype'], 0, 1, $params['_users_id_requester'], $params['entities_id'], $p);
            $twig_params['all_items_dropdown'] = ob_get_clean();
        }

        // Display list
        if (!empty($params['items_id'])) {
            // No delete if mandatory and only one item
            $delete = $obj->canAddItem(static::class) && !$is_closed;
            $cpt = 0;
            foreach ($params['items_id'] as $itemtype => $items) {
                $cpt += count($items);
            }

            if ($cpt == 1 && isset($tt->mandatory['items_id'])) {
                $delete = false;
            }
            foreach ($params['items_id'] as $itemtype => $items) {
                foreach ($items as $items_id) {
                    $count++;
                    $twig_params['items_to_add'][] = static::showItemToAdd(
                        $params['id'],
                        $itemtype,
                        $items_id,
                        [
                            'rand'      => $rand,
                            'delete'    => $delete,
                            'visible'   => ($count <= 5),
                        ]
                    );
                }
            }
        }
        $twig_params['count'] = $count;
        $twig_params['usedcount'] = $usedcount;

        foreach (['id', '_users_id_requester', 'items_id', 'itemtype', '_canupdate', 'entities_id'] as $key) {
            $twig_params['opt'][$key] = $params[$key];
        }

        TemplateRenderer::getInstance()->display('components/itilobject/add_items.html.twig', $twig_params);
    }

    /**
     * Print the HTML ajax associated item add
     *
     * @param int $object_id  object id from item_ticket but it seems to be useless UNUSED
     * @param class-string<CommonDBTM> $itemtype   type of the item t show
     * @param int $items_id   item id
     * @param array $options   array of possible options:
     *    - id                  : ID of the object holding the items
     *    - _users_id_requester : ID of the requester user
     *    - items_id            : array of elements (itemtype => array(id1, id2, id3, ...))
     *
     * @return string
     **/
    public static function showItemToAdd($object_id, $itemtype, $items_id, $options)
    {
        $params = [
            'rand'      => mt_rand(),
            'delete'    => true,
            'visible'   => true,
            'kblink'    => true,
        ];

        foreach ($options as $key => $val) {
            $params[$key] = $val;
        }

        $result = "";

        if ($item = getItemForItemtype($itemtype)) {
            if ($params['visible']) {
                $item->getFromDB($items_id);
                $result =  "<div id='" . htmlescape("{$itemtype}_{$items_id}") . "'>";
                $result .= htmlescape($item->getTypeName(1)) . " : " . $item->getLink(['comments' => true]);
                $result .= Html::hidden("items_id[$itemtype][$items_id]", ['value' => $items_id]);
                if ($params['delete']) {
                    $result .= " <i class='ti ti-circle-x pointer' onclick=\"itemAction" . htmlescape($params['rand']) . "('delete', '" . htmlescape(jsescape($itemtype)) . "', '" . htmlescape(jsescape($items_id)) . "');\"></i>";
                }
                if ($params['kblink']) {
                    $result .= ' ' . $item->getKBLinks();
                }
                $result .= "</div>";
            } else {
                $result .= Html::hidden("items_id[$itemtype][$items_id]", ['value' => $items_id]);
            }
        }

        return $result;
    }

    /**
     * Print the HTML array for Items linked to a ITIL object
     *
     * @param CommonITILObject|TicketRecurrent $obj
     *
     * @return bool|void
     **/
    protected static function showForObject(CommonITILObject|TicketRecurrent $obj)
    {
        if (!($obj instanceof static::$itemtype_1)) {
            return false;
        }

        $instID = (int) $obj->fields['id'];

        if (!$obj->can($instID, READ)) {
            return false;
        }
        //can Add Item takes type as param but there is none here
        $canedit = $obj->canAddItem('');
        $rand    = mt_rand();

        $types_iterator = static::getDistinctTypes($instID);
        $is_closed = $obj instanceof CommonITILObject
            && in_array(
                $obj->fields['status'],
                array_merge(
                    $obj->getClosedStatusArray(),
                    $obj->getSolvedStatusArray()
                )
            );

        if ($canedit && !$is_closed) {
            $requester_id   = 0;
            if ($obj instanceof CommonITILObject) {
                $userlink_class = $obj->getActorObjectForItem(User::class);
                $obj_actors = $userlink_class->getActors($obj->fields['id']);
                if (
                    isset($obj_actors[CommonITILActor::REQUESTER])
                    && (count($obj_actors[CommonITILActor::REQUESTER]) === 1)
                ) {
                    $requester_id = reset($obj_actors[CommonITILActor::REQUESTER])['users_id'];
                }
            }

            $twig_params = [
                'itil_object' => $obj,
                'link_class' => static::class,
                'requester_id' => $requester_id,
                'btn_label' => _x('button', 'Add'),
                'used' => static::getUsedItems($instID),
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                {% import 'components/form/fields_macros.html.twig' as fields %}
                {% import 'components/form/basic_inputs_macros.html.twig' as inputs %}
                {% set rand = random() %}
                <div class="mb-3">
                    <form method="post" action="{{ link_class|itemtype_form_path }}">
                        <div class="d-flex w-100 flex-column">
                            <input type="hidden" name="{{ itil_object|itemtype_foreign_key }}" value="{{ itil_object.getID() }}">
                            <input type="hidden" name="_glpi_csrf_token" value="{{ csrf_token() }}">
                            {% if requester_id > 0 %}
                                {% do call([link_class, 'dropdownMyDevices'], [requester_id, itil_object.getEntityID(), null, 0, {(itil_object|itemtype_foreign_key): itil_object.getID()}]) %}
                            {% endif %}
                            <div>
                                {% do call([link_class, 'dropdownAllDevices'], ['itemtype', null, 0, 1, requester_id, itil_object.getEntityID(), {
                                    (itil_object|itemtype_foreign_key): itil_object.getID(),
                                    'used': used,
                                    'rand': rand
                                }]) %}
                            </div>
                        </div>
                        <div class="d-flex px-3 flex-row-reverse">
                            {{ inputs.submit('add', btn_label, 1) }}
                        </div>
                    </form>
                </div>
TWIG, $twig_params);
        }

        $entries = [];
        foreach ($types_iterator as $row) {
            $itemtype = $row['itemtype'];
            if (!($item = getItemForItemtype($itemtype)) || !in_array($itemtype, $_SESSION["glpiactiveprofile"]["helpdesk_item_type"], true)) {
                continue;
            }

            $iterator = static::getTypeItems($instID, $itemtype);
            foreach ($iterator as $data) {
                $item->getFromDB($data["id"]);
                $entry = [
                    'itemtype' => static::class,
                    'id'   => $data["linkid"],
                    'row_class' => $data['is_deleted'] ? 'table-deleted' : '',
                    'linked_itemtype' => $item::getTypeName(1),
                    'serial'  => $data["serial"] ?? "-",
                    'otherserial' => $data["otherserial"] ?? "-",
                    'entity' => Dropdown::getDropdownName("glpi_entities", $data['entity']),
                    'state' => Dropdown::getDropdownName("glpi_states", $data['states_id']),
                    'location' => Dropdown::getDropdownName("glpi_locations", $data['locations_id']),
                    'kb' => $item->getKBLinks(),
                    'showmassiveactions' => $canedit && !$is_closed,
                ];
                $name = htmlescape($data["name"]);
                if (
                    $_SESSION["glpiis_ids_visible"]
                    || empty($data["name"])
                ) {
                    $name = sprintf(__s('%1$s (%2$s)'), $name, (int) $data["id"]);
                }
                if ((Session::getCurrentInterface() !== 'helpdesk') && $item::canView()) {
                    $link     = $itemtype::getFormURLWithID($data['id']);
                    $namelink = "<a href=\"" . htmlescape($link) . "\">" . $name . "</a>";
                } else {
                    $namelink = $name;
                }
                $entry['name'] = $namelink;
                $entries[] = $entry;
            }
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nopager' => true,
            'nofilter' => true,
            'columns' => [
                'linked_itemtype' => _n('Type', 'Types', 1),
                'entity'          => Entity::getTypeName(1),
                'name'            => __('Name'),
                'serial'          => __('Serial number'),
                'otherserial'     => __('Inventory number'),
                'kb'              => __('Knowledge base entries'),
                'state'           => __('Status'),
                'location'        => Location::getTypeName(1),
            ],
            'formatters' => [
                'name' => 'raw_html',
                'kb' => 'raw_html',
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

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        global $CFG_GLPI;

        if (!$withtemplate) {
            if (!($item instanceof CommonDBTM)) {
                return '';
            }

            // This tab might be hidden on assets
            if (in_array($item::class, $CFG_GLPI['asset_types'], true) && !$this->shouldDisplayTabForAsset($item)) {
                return '';
            }

            $nb = 0;

            if ($item::class === static::$itemtype_1) {
                if ($_SESSION['glpishow_count_on_tabs']) {
                    $nb = static::countForMainItem($item);
                }
                return static::createTabEntry(_n('Item', 'Items', Session::getPluralNumber()), $nb, $item::class);
            } elseif ($_SESSION['glpishow_count_on_tabs'] && is_subclass_of(static::$itemtype_1, CommonITILObject::class)) {
                if (in_array($item::class, static::$itemtype_1::getTeamItemtypes(), true)) {
                    $nb = static::countForActor($item);
                } elseif (Session::haveRight(static::$itemtype_1::$rightname, CommonITILObject::READALL)) {
                    $nb = static::countForItemAndLinked($item);
                }
            } elseif ($_SESSION['glpishow_count_on_tabs']) {
                $nb = static::countForItem($item);
            }
            return static::createTabEntry(static::$itemtype_1::getTypeName(Session::getPluralNumber()), $nb, $item::class);
        }
        return '';
    }

    /**
     * Count number of ITIL objects for the provided item and other items linked to the requested item
     * @param CommonDBTM $item
     * @return int
     * @see Asset_PeripheralAsset
     * @see static::getLinkedItems()
     */
    public static function countForItemAndLinked(CommonDBTM $item)
    {
        // Direct links
        $nb = parent::countForItem($item);

        // Linked items
        $itil_table = static::$itemtype_1::getTable();
        $linkeditems = $item->getLinkedItems();
        foreach ($linkeditems as $type => $ids) {
            $type_item = getItemForItemtype($type);
            if (!$type_item) {
                continue;
            }
            // Only count valid links and non-deleted items
            $criteria = [
                'INNER JOIN' => [
                    $itil_table => [
                        'FKEY' => [
                            static::getTable() => static::$items_id_1,
                            $itil_table       => 'id',
                        ],
                    ],
                ],
                'WHERE' => [
                    static::$itemtype_2 => $type,
                    static::$items_id_2 => $ids,
                ],
            ];
            if ($type_item->maybeDeleted()) {
                $criteria['WHERE']['is_deleted'] = 0;
            }
            $nb += countElementsInTable(static::getTable(), $criteria);
        }

        return $nb;
    }

    /**
     * Count number of ITIL objects for the provided actor item (user, group, etc)
     * @param CommonDBTM $item
     * @return int
     */
    protected static function countForActor(CommonDBTM $item): int
    {
        global $DB;

        /** @var CommonITILObject $itil */
        $itil = getItemForItemtype(static::$itemtype_1);
        $link_class_prop = strtolower($item::class) . 'linkclass';
        if (!isset($itil->{$link_class_prop})) {
            return 0;
        }
        $link_table = ($itil->{$link_class_prop})::getTable();
        $result = $DB->request([
            'SELECT' => [
                QueryFunction::count($itil::getForeignKeyField(), true, 'cpt'),
            ],
            'FROM'   => $link_table,
            'WHERE'  => [
                $item->getForeignKeyField()   => $item->fields['id'],
            ],
        ])->current();
        return $result['cpt'] ?? 0;
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$item instanceof CommonDBTM) {
            return false;
        }

        if ($item::class === static::$itemtype_1) {
            if (
                !$item instanceof CommonITILObject
                && !$item instanceof TicketRecurrent
            ) {
                throw new LogicException("Item must be CommonItilObject or TicketRecurrent");
            }
            static::showForObject($item);
        } else {
            static::showListForItem($item, $withtemplate);
        }
        return true;
    }

    /**
     * Display object for an item
     *
     * Will also display objects of linked items
     *
     * @param CommonDBTM $item         CommonDBTM object
     * @param integer    $withtemplate (default 0)
     *
     * @return bool|void (display a table)
     **/
    public static function showListForItem(CommonDBTM $item, $withtemplate = 0, $options = [])
    {
        global $DB;

        if (!static::$itemtype_1::canView()) {
            return false;
        }

        if ($item->isNewID($item->getID())) {
            return false;
        }

        $criteria = static::$itemtype_1::getCommonCriteria();
        $params  = [
            'criteria' => [],
            'metacriteria' => $options['metacriteria'] ?? [
                [
                    'itemtype' => $item::class,
                    'field'    => Search::getOptionNumber($item::class, 'id'),
                    'searchtype' => 'equals',
                    'value'    => $item->getID(),
                    'link'     => 'AND',
                ],
            ],
            'reset'    => 'reset',
        ];
        $restrict = static::$itemtype_1::getListForItemRestrict($item);

        $params['criteria'][0]['field']      = 12;
        $params['criteria'][0]['searchtype'] = 'equals';
        $params['criteria'][0]['value']      = 'all';
        $params['criteria'][0]['link']       = 'AND';

        $criteria['WHERE'] = $restrict + getEntitiesRestrictCriteria(static::$itemtype_1::getTable());
        if (method_exists(static::$itemtype_1, 'getCriteriaFromProfile')) {
            $profile_criteria = static::$itemtype_1::getCriteriaFromProfile();
            $criteria['WHERE'] = array_merge($criteria['WHERE'], $profile_criteria['WHERE'] ?? []);
            $criteria['LEFT JOIN'] = array_merge($criteria['LEFT JOIN'], $profile_criteria['LEFT JOIN'] ?? []);
        }
        $criteria['WHERE'][static::$itemtype_1::getTable() . ".is_deleted"] = 0;
        $criteria['LIMIT'] = (int) $_SESSION['glpilist_limit'];
        $iterator = $DB->request($criteria);
        $number = count($iterator);

        $colspan = 12;
        if (count($_SESSION["glpiactiveentities"]) > 1) {
            $colspan++;
        }

        // Object for the item
        // Link to open a new ITIL object
        if (
            $item->getID()
            && !$item->isDeleted()
            && CommonITILObject::isPossibleToAssignType($item->getType())
            && static::canCreate()
            && !(!empty($withtemplate) && ($withtemplate == 2))
            && (!isset($item->fields['is_template']) || ($item->fields['is_template'] == 0))
        ) {
            $linknewitil = true;
        }

        if ($number > 0) {
            if (Session::haveRight(static::$itemtype_1::$rightname, static::$itemtype_1::READALL)) {
                $readall = true;
            }
        }

        // Object for linked items
        $linkeditems = $item->getLinkedItems();
        $restrict    = [];
        if (count($linkeditems)) {
            foreach ($linkeditems as $ltype => $tab) {
                foreach ($tab as $lID) {
                    $restrict[] = ['AND' => ['itemtype' => $ltype, 'items_id' => $lID]];
                }
            }
        }

        if (
            count($restrict)
            && Session::haveRight(static::$itemtype_1::$rightname, static::$itemtype_1::READALL)
        ) {
            $criteria = static::$itemtype_1::getCommonCriteria();
            $criteria['WHERE'] = ['OR' => $restrict]
                + getEntitiesRestrictCriteria(static::$itemtype_1::getTable());
            $iterator2 = $DB->request($criteria);
            $number2 = count($iterator2);
            $showform = true;
        } // Subquery for linked item

        TemplateRenderer::getInstance()->display('components/form/item_itilobject_item_list.html.twig', [
            'item'               => $item,
            'linknewitil'        => $linknewitil ?? false,
            'itemtype_1'         => static::$itemtype_1,
            'colspan'            => $colspan,
            'number'             => $number,
            'number2'            => $number2 ?? 0,
            'showform'           => $showform ?? false,
            'readall'            => $readall ?? false,
            'params'             => $params,
            'html_output'        => Search::HTML_OUTPUT,
            'iterator'           => $iterator,
            'iterator2'          => $iterator2 ?? [],
        ]);
    }

    /**
     * Make a select box for Object my devices
     *
     * @param integer $userID           User ID for my device section (default 0)
     * @param integer $entity_restrict  restrict to a specific entity (default -1)
     * @param string  $itemtype         of selected item (default 0)
     * @param integer $items_id         of selected item (default 0) UNUSED
     * @param array   $options          array of possible options:
     *    - used     : ID of the requester user
     *    - multiple : allow multiple choice
     *
     * @return void
     **/
    public static function dropdownMyDevices($userID = 0, $entity_restrict = -1, $itemtype = '', $items_id = 0, $options = [])
    {
        global $CFG_GLPI, $DB;

        $params = [
            static::$items_id_1 => 0,
            'used'       => [],
            'multiple'   => false,
            'rand'       => mt_rand(),
        ];

        foreach ($options as $key => $val) {
            $params[$key] = $val;
        }

        if ($userID == 0) {
            $userID = Session::getLoginUserID();
        }

        $entity_restrict = Session::getMatchingActiveEntities($entity_restrict);

        $rand        = (int) $params['rand'];

        if (
            $_SESSION["glpiactiveprofile"]["helpdesk_hardware"]
            & 2 ** CommonITILObject::HELPDESK_MY_HARDWARE
        ) {
            $my_devices = array_merge(
                ['' => Dropdown::EMPTY_VALUE],
                self::getMyDevices($userID, $entity_restrict)
            );

            echo "<div id='tracking_my_devices' class='input-group mb-1'>";
            echo "<span class='input-group-text'>" . __s('My devices') . "</span>";
            Dropdown::showFromArray('my_items', $my_devices, ['rand' => $rand]);
            echo "<span id='item_selection_information$rand' class='ms-1'></span>";
            echo "</div>";

            // Auto update summary of active or just solved Tickets
            if (static::$itemtype_1 === Ticket::class) {
                $params = ['my_items' => '__VALUE__'];
                Ajax::updateItemOnSelectEvent(
                    "dropdown_my_items$rand",
                    "item_selection_information$rand",
                    $CFG_GLPI["root_doc"] . "/ajax/ticketiteminformation.php",
                    $params
                );
            }
        }
    }

    /**
     * Retrieves a list of devices associated with a user, including their own devices,
     * devices owned by their groups, installed software, and linked items to computers.
     *
     * @param int $userID The ID of the user whose devices are to be retrieved.
     * @param int|array $entity_restrict Optional. The entity restriction to apply. Default is -1.
     *
     * @return array<string, array<string, string>> An associative array of devices associated with the user.
     *               The array keys are the categories, and the values are arrays of device descriptions.
     *
     * The categories include:
     * - 'My devices': Devices directly assigned to the user.
     * - 'Devices own by my groups': Devices owned by the user's groups.
     * - 'Installed software': Software linked to all owned items.
     * - 'Connected devices': Items linked to computers.
     */
    public static function getMyDevices(int $userID, mixed $entity_restrict = -1): array
    {
        $my_devices = [];
        $already_add = [];

        // My items
        $devices = self::getMyAssigneeDevices($userID, $entity_restrict, $already_add);
        foreach ($devices as $itemtype => $items) {
            foreach ($items as $data) {
                $output = $data[$itemtype::getNameField()];
                if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                    $output = sprintf(__('%1$s (%2$s)'), $output, $data['id']);
                }
                $output = sprintf(__('%1$s - %2$s'), $itemtype::getTypeName(), $output);
                if ($itemtype != 'Software') {
                    if (!empty($data['serial'])) {
                        $output = sprintf(__('%1$s - %2$s'), $output, $data['serial']);
                    }
                    if (!empty($data['otherserial'])) {
                        $output = sprintf(__('%1$s - %2$s'), $output, $data['otherserial']);
                    }
                }
                $my_devices[__('My devices')][$itemtype . "_" . $data["id"]] = $output;
            }
        }

        // My group items
        if (Session::haveRight("show_group_hardware", READ)) {
            $devices = self::getMyGroupsDevices($userID, $entity_restrict, $already_add);
            foreach ($devices as $itemtype => $items) {
                foreach ($items as $data) {
                    $output = $data[$itemtype::getNameField()];
                    if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                        $output = sprintf(__('%1$s (%2$s)'), $output, $data['id']);
                    }
                    $output = sprintf(__('%1$s - %2$s'), $itemtype::getTypeName(), $output);
                    if (!empty($data['serial'])) {
                        $output = sprintf(__('%1$s - %2$s'), $output, $data['serial']);
                    }
                    if (!empty($data['otherserial'])) {
                        $output = sprintf(__('%1$s - %2$s'), $output, $data['otherserial']);
                    }
                    $my_devices[__('Devices own by my groups')][$itemtype . "_" . $data["id"]] = $output;
                }
            }
        }

        // Get software linked to all owned items
        $software = self::getLinkedSoftware($already_add, $entity_restrict, $already_add);
        foreach ($software as $data) {
            $output = sprintf(__('%1$s - %2$s'), Software::getTypeName(), $data["name"]);
            $output = sprintf(
                __('%1$s (%2$s)'),
                $output,
                sprintf(__('%1$s: %2$s'), __('version'), $data["version"])
            );
            if ($_SESSION["glpiis_ids_visible"]) {
                $output = sprintf(__('%1$s (%2$s)'), $output, $data["id"]);
            }
            $my_devices[__('Installed software')]["Software_" . $data["id"]] = $output;
        }

        // Get linked items to computers
        $linked_items = self::getLinkedItemsToComputers($already_add, $entity_restrict, $already_add);
        foreach ($linked_items as $itemtype => $items) {
            foreach ($items as $data) {
                $output = $data[$itemtype::getNameField()];
                if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                    $output = sprintf(__('%1$s (%2$s)'), $output, $data['id']);
                }
                $output = sprintf(__('%1$s - %2$s'), $itemtype::getTypeName(), $output);
                if ($itemtype !== Software::class) {
                    $output = sprintf(__('%1$s - %2$s'), $output, $data['otherserial']);
                }
                $my_devices[__('Connected devices')][$itemtype . "_" . $data["id"]] = $output;
            }
        }

        return $my_devices;
    }

    /**
     * Retrieves the devices assigned to a specific user within a restricted entity.
     *
     * @param int $userID The ID of the user for whom to retrieve the devices.
     * @param int|array $entity_restrict Optional. The entity restriction criteria. Default is -1 (no restriction).
     * @param array &$already_add Optional. An array to keep track of already added devices to avoid duplicates.
     *
     * @return array<string, array>  An associative array of devices assigned to the user, categorized by item type.
     */
    private static function getMyAssigneeDevices(int $userID, mixed $entity_restrict = -1, array &$already_add = []): array
    {
        global $CFG_GLPI, $DB;

        $devices = [];

        foreach ($CFG_GLPI["assignable_types"] as $itemtype) {
            if (
                ($item = getItemForItemtype($itemtype))
                && CommonITILObject::isPossibleToAssignType($itemtype)
            ) {
                $itemtable = getTableForItemType($itemtype);

                $criteria = [
                    'FROM'   => $itemtable,
                    'WHERE'  => [
                        'users_id' => $userID,
                    ] + getEntitiesRestrictCriteria($itemtable, '', $entity_restrict, $item->maybeRecursive())
                    + $itemtype::getSystemSQLCriteria(),
                    'ORDER'  => $item::getNameField(),
                ];

                if ($item->maybeDeleted()) {
                    $criteria['WHERE']['is_deleted'] = 0;
                }
                if ($item->maybeTemplate()) {
                    $criteria['WHERE']['is_template'] = 0;
                }
                if (in_array($itemtype, $CFG_GLPI["helpdesk_visible_types"])) {
                    $criteria['WHERE']['is_helpdesk_visible'] = 1;
                }

                $iterator = $DB->request($criteria);
                foreach ($iterator as $data) {
                    if (!isset($already_add[$itemtype]) || !in_array($data["id"], $already_add[$itemtype])) {
                        $devices[$itemtype][] = $data;
                        $already_add[$itemtype][] = $data["id"];
                    }
                }
            }
        }

        return $devices;
    }

    /**
     * Retrieves the devices associated with the groups of a given user.
     *
     * @param int $userID The ID of the user whose groups' devices are to be retrieved.
     * @param int|array $entity_restrict Optional. The entity restriction criteria. Default is -1 (no restriction).
     * @param array &$already_add Optional. An array to keep track of already added devices to avoid duplicates.
     *
     * @return array<string, array> An associative array of devices grouped by item type.
     */
    private static function getMyGroupsDevices(int $userID, mixed $entity_restrict = -1, array &$already_add = []): array
    {
        global $CFG_GLPI, $DB;

        $devices = [];
        $groups  = [];

        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_groups_users.groups_id',
                'glpi_groups.name',
            ],
            'FROM'      => 'glpi_groups_users',
            'LEFT JOIN' => [
                'glpi_groups'  => [
                    'ON' => [
                        'glpi_groups_users'  => 'groups_id',
                        'glpi_groups'        => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                'glpi_groups_users.users_id'  => $userID,
            ] + getEntitiesRestrictCriteria('glpi_groups', '', $entity_restrict, true),
        ]);

        if (count($iterator)) {
            foreach ($iterator as $data) {
                $a_groups                     = getAncestorsOf("glpi_groups", $data["groups_id"]);
                $a_groups[$data["groups_id"]] = $data["groups_id"];
                $groups = array_merge($groups, $a_groups);
            }

            foreach ($CFG_GLPI["assignable_types"] as $itemtype) {
                if (
                    ($item = getItemForItemtype($itemtype))
                    && CommonITILObject::isPossibleToAssignType($itemtype)
                ) {
                    $itemtable  = getTableForItemType($itemtype);
                    $criteria = [
                        'SELECT'  => [$itemtable . '.*'],
                        'FROM'    => $itemtable,
                        'LEFT JOIN' => [
                            Group_Item::getTable() => [
                                'ON' => [
                                    $itemtable => 'id',
                                    Group_Item::getTable() => 'items_id', [
                                        'AND' => [
                                            Group_Item::getTable() . '.itemtype' => $itemtype,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'WHERE'   => [
                            Group_Item::getTable() . '.type' => Group_Item::GROUP_TYPE_NORMAL,
                            Group_Item::getTable() . '.groups_id' => $groups,
                        ] + getEntitiesRestrictCriteria($itemtable, '', $entity_restrict, $item->maybeRecursive())
                        + $itemtype::getSystemSQLCriteria(),
                        'GROUPBY' => $itemtable . '.id',
                        'ORDER'   => $item::getNameField(),
                    ];

                    if ($item->maybeDeleted()) {
                        $criteria['WHERE']['is_deleted'] = 0;
                    }
                    if ($item->maybeTemplate()) {
                        $criteria['WHERE']['is_template'] = 0;
                    }

                    $iterator = $DB->request($criteria);
                    if (count($iterator)) {
                        if (!isset($already_add[$itemtype])) {
                            $already_add[$itemtype] = [];
                        }
                        foreach ($iterator as $data) {
                            if (!in_array($data["id"], $already_add[$itemtype])) {
                                $devices[$itemtype][] = $data;
                                $already_add[$itemtype][] = $data["id"];
                            }
                        }
                    }
                }
            }
        }

        return $devices;
    }

    /**
     * Retrieves a list of linked software for a given user.
     *
     * @param array $devices The devices to retrieve linked software for.
     * @param int|array $entity_restrict Optional. The entity restriction criteria. Default is -1 (no restriction).
     * @param array &$already_add Reference to an array that keeps track of already added items to avoid duplicates.
     *
     * @return array An array of linked software information, including software name, version, and ID.
     */
    private static function getLinkedSoftware(array $devices, mixed $entity_restrict = -1, array &$already_add = []): array
    {
        global $CFG_GLPI, $DB;

        $software = [];

        if (in_array('Software', $_SESSION["glpiactiveprofile"]["helpdesk_item_type"])) {
            $software_helpdesk_types = array_intersect($CFG_GLPI['software_types'], $_SESSION["glpiactiveprofile"]["helpdesk_item_type"]);
            foreach ($software_helpdesk_types as $itemtype) {
                if (isset($devices[$itemtype]) && count($devices[$itemtype])) {
                    $iterator = $DB->request([
                        'SELECT'          => [
                            'glpi_softwareversions.name AS version',
                            'glpi_softwares.name AS name',
                            'glpi_softwares.id',
                        ],
                        'DISTINCT'        => true,
                        'FROM'            => 'glpi_items_softwareversions',
                        'LEFT JOIN'       => [
                            'glpi_softwareversions'  => [
                                'ON' => [
                                    'glpi_items_softwareversions' => 'softwareversions_id',
                                    'glpi_softwareversions'       => 'id',
                                ],
                            ],
                            'glpi_softwares'        => [
                                'ON' => [
                                    'glpi_softwareversions' => 'softwares_id',
                                    'glpi_softwares'        => 'id',
                                ],
                            ],
                        ],
                        'WHERE'        => [
                            'glpi_items_softwareversions.items_id' => $devices[$itemtype],
                            'glpi_items_softwareversions.itemtype' => $itemtype,
                            'glpi_softwares.is_helpdesk_visible'   => 1,
                        ] + getEntitiesRestrictCriteria('glpi_softwares', '', $entity_restrict),
                        'ORDERBY'      => 'glpi_softwares.name',
                    ]);

                    if (count($iterator)) {
                        if (!isset($already_add['Software'])) {
                            $already_add['Software'] = [];
                        }
                        foreach ($iterator as $data) {
                            if (!in_array($data["id"], $already_add['Software'])) {
                                $software[] = $data;
                                $already_add['Software'][] = $data["id"];
                            }
                        }
                    }
                }
            }
        }

        return $software;
    }

    /**
     * Retrieves linked items to computers based on the given user ID and entity restriction.
     *
     * @param array $devices The computers to retrieve linked items for.
     * @param int $entity_restrict The entity restriction to apply. Default is -1 (no restriction).
     * @param array &$already_add Reference to an array that keeps track of already added items.
     *
     * @return array<class-string<CommonDBTM>, array> An array of linked items categorized by their item types.
     */
    private static function getLinkedItemsToComputers(array $devices, mixed $entity_restrict = -1, array &$already_add = []): array
    {
        global $CFG_GLPI, $DB;

        $linked_items = [];

        foreach (Asset_PeripheralAsset::getPeripheralHostItemtypes() as $peripheralhost_itemtype) {
            if (isset($devices[$peripheralhost_itemtype]) && count($devices[$peripheralhost_itemtype])) {
                foreach ($CFG_GLPI['directconnect_types'] as $peripheral_itemtype) {
                    if (
                        in_array($peripheral_itemtype, $_SESSION["glpiactiveprofile"]["helpdesk_item_type"])
                        && ($item = getItemForItemtype($peripheral_itemtype))
                    ) {
                        $itemtable = getTableForItemType($peripheral_itemtype);
                        if (!isset($already_add[$peripheral_itemtype])) {
                            $already_add[$peripheral_itemtype] = [];
                        }
                        $relation_table = Asset_PeripheralAsset::getTable();
                        $criteria = [
                            'SELECT'          => "$itemtable.*",
                            'DISTINCT'        => true,
                            'FROM'            => $relation_table,
                            'LEFT JOIN'       => [
                                $itemtable  => [
                                    'ON' => [
                                        $relation_table => 'items_id_peripheral',
                                        $itemtable      => 'id',
                                    ],
                                ],
                            ],
                            'WHERE'           => [
                                $relation_table . '.itemtype_peripheral' => $peripheral_itemtype,
                                $relation_table . '.itemtype_asset'      => $peripheralhost_itemtype,
                                $relation_table . '.items_id_asset'      => $devices[$peripheralhost_itemtype],
                            ] + getEntitiesRestrictCriteria($itemtable, '', $entity_restrict),
                            'ORDERBY'         => "$itemtable.name",
                        ];

                        if ($item->maybeDeleted()) {
                            $criteria['WHERE']["$itemtable.is_deleted"] = 0;
                        }
                        if ($item->maybeTemplate()) {
                            $criteria['WHERE']["$itemtable.is_template"] = 0;
                        }

                        $iterator = $DB->request($criteria);
                        if (count($iterator)) {
                            foreach ($iterator as $data) {
                                if (!in_array($data["id"], $already_add[$peripheral_itemtype])) {
                                    $linked_items[$peripheral_itemtype][] = $data;
                                    $already_add[$peripheral_itemtype][] = $data["id"];
                                }
                            }
                        }
                    }
                }
            }
        }

        return $linked_items;
    }

    /**
     * Make a select box with all glpi items
     *
     * @param array $options array of possible options:
     *    - name         : string / name of the select (default is users_id)
     *    - value
     *    - comments     : boolean / is the comments displayed near the dropdown (default true)
     *    - entity       : integer or array / restrict to a defined entity or array of entities
     *                      (default -1 : no restriction)
     *    - entity_sons  : boolean / if entity restrict specified auto select its sons
     *                      only available if entity is a single value not an array(default false)
     *    - rand         : integer / already computed rand value
     *    - toupdate     : array / Update a specific item on select change on dropdown
     *                      (need value_fieldname, to_update, url
     *                      (see Ajax::updateItemOnSelectEvent for information)
     *                      and may have moreparams)
     *    - used         : array / Already used items ID: not to display in dropdown (default empty)
     *    - on_change    : string / value to transmit to "onChange"
     *    - display      : boolean / display or get string (default true)
     *    - width        : specific width needed
     *    - hide_if_no_elements  : boolean / hide dropdown if there is no elements (default false)
     *
     **/
    public static function dropdown($options = [])
    {
        global $DB;

        $p = array_replace([
            'name' => 'items',
            'value' => '',
            'all' => 0,
            'on_change' => '',
            'comments' => 1,
            'width' => '',
            'entity' => -1,
            'entity_sons' => false,
            'used' => [],
            'toupdate' => '',
            'rand' => mt_rand(),
            'display' => true,
            'hide_if_no_elements' => false,
        ], $options);

        $itemtypes = ['Computer', 'Monitor', 'NetworkEquipment', 'Peripheral', 'Phone', 'Printer'];

        $union = new QueryUnion();
        foreach ($itemtypes as $type) {
            $table = getTableForItemType($type);
            $union->addQuery([
                'SELECT' => [
                    'id',
                    new QueryExpression($type, 'itemtype'),
                    "name",
                ],
                'FROM'   => $table,
                'WHERE'  => [
                    'NOT'          => ['id' => null],
                    'is_deleted'   => 0,
                    'is_template'  => 0,
                ],
            ]);
        }

        $iterator = $DB->request(['FROM' => $union]);

        if ($p['hide_if_no_elements'] && $iterator->count() === 0) {
            return false;
        }

        $output = [];

        foreach ($iterator as $data) {
            $item = getItemForItemtype($data['itemtype']);
            $output[$data['itemtype'] . "_" . $data['id']] = $item::getTypeName() . " - " . $data['name'];
        }

        return Dropdown::showFromArray($p['name'], $output, $p);
    }

    /**
     * Return used items for a ITIL object
     *
     * @param integer $items_id ITIL object on which the used item are attached
     *
     * @return array
     */
    public static function getUsedItems($items_id)
    {

        $data = getAllDataFromTable(static::getTable(), [static::$items_id_1 => $items_id]);
        $used = [];
        if (!empty($data)) {
            foreach ($data as $val) {
                $used[$val['itemtype']][] = $val['items_id'];
            }
        }

        return $used;
    }

    /**
     * Form for Followup on Massive action
     **/
    public static function showFormMassiveAction($ma)
    {
        global $CFG_GLPI;

        $dropdown_params = [
            'items_id_name'   => 'items_id',
            'itemtype_name'   => 'item_itemtype',
            'itemtypes'       => $CFG_GLPI['ticket_types'],
            'checkright'      => true,
            'entity_restrict' => $_SESSION['glpiactive_entity'],
        ];
        Dropdown::showSelectItemFromItemtypes($dropdown_params);
        switch ($ma->getAction()) {
            case 'add_item':
                echo "<br><button type='submit' name='add' value=\"1\" class='btn btn-sm btn-primary'>" . _sx('button', 'Add') . "</button>";
                break;
            case 'delete_item':
                echo "<br><button type='submit' name='delete' value=\"1\" class='btn btn-sm btn-primary'>" . _sx('button', 'Delete permanently') . "</button>";
                break;
        }
    }

    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        switch ($ma->getAction()) {
            case 'delete_item':
            case 'add_item':
                static::showFormMassiveAction($ma);
                return true;

        }

        return parent::showMassiveActionsSubForm($ma);
    }

    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {
        switch ($ma->getAction()) {
            case 'add_item':
                $input = $ma->getInput();

                $item_obj = new static();
                foreach ($ids as $id) {
                    if (!empty($input['items_id']) && $item->getFromDB($id)) {
                        $input[static::$items_id_1] = $id;
                        $input['itemtype'] = $input['item_itemtype'];

                        if ($item_obj->can(-1, CREATE, $input)) {
                            $ok = true;
                            if (!$item_obj->add($input)) {
                                $ok = false;
                            }

                            if ($ok) {
                                $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                            } else {
                                $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                                $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                            }
                        } else {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                            $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                        }
                    } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        $ma->addMessage($item->getErrorMessage(ERROR_NOT_FOUND));
                    }
                }
                return;

            case 'delete_item':
                $input = $ma->getInput();
                $item_obj = new static();
                foreach ($ids as $id) {
                    if (!empty($input['items_id']) && $item->getFromDB($id)) {
                        $item_found = $item_obj->find([
                            static::$items_id_1   => $id,
                            'itemtype'     => $input['item_itemtype'],
                            'items_id'     => $input['items_id'],
                        ]);
                        if (!empty($item_found)) {
                            $item_founds_id = array_keys($item_found);
                            $input['id'] = $item_founds_id[0];

                            if ($item_obj->can($input['id'], DELETE, $input)) {
                                $ok = true;
                                if (!$item_obj->delete($input)) {
                                    $ok = false;
                                }

                                if ($ok) {
                                    $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                                } else {
                                    $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                                    $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                                }
                            } else {
                                $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                                $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                            }
                        } else {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                            $ma->addMessage($item->getErrorMessage(ERROR_NOT_FOUND));
                        }
                    } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        $ma->addMessage($item->getErrorMessage(ERROR_NOT_FOUND));
                    }
                }
                return;
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => '3',
            'table'              => static::getTable(),
            'field'              => static::$items_id_1,
            'name'               => static::$itemtype_1::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '13',
            'table'              => static::getTable(),
            'field'              => 'items_id',
            'name'               => _n('Associated element', 'Associated elements', Session::getPluralNumber()),
            'datatype'           => 'specific',
            'comments'           => true,
            'nosort'             => true,
            'additionalfields'   => ['itemtype'],
        ];

        $tab[] = [
            'id'                 => '131',
            'table'              => static::getTable(),
            'field'              => 'itemtype',
            'name'               => _n('Associated item type', 'Associated item types', Session::getPluralNumber()),
            'datatype'           => 'itemtypename',
            'itemtype_list'      => 'ticket_types',
            'nosort'             => true,
        ];

        return $tab;
    }

    /**
     * Add a message on add action
     **/
    public function addMessageOnAddAction()
    {
        $addMessAfterRedirect = false;
        if (isset($this->input['_add'])) {
            $addMessAfterRedirect = true;
        }

        if (
            isset($this->input['_no_message'])
            || !$this->auto_message_on_action
        ) {
            $addMessAfterRedirect = false;
        }

        if ($addMessAfterRedirect) {
            $item = getItemForItemtype($this->fields['itemtype']);
            $item->getFromDB($this->fields['items_id']);

            if ($item->getName() === NOT_AVAILABLE) {
                //TRANS: %1$s is the itemtype, %2$d is the id of the item
                $item->fields['name'] = sprintf(
                    __('%1$s - ID %2$d'),
                    $item::getTypeName(1),
                    $item->fields['id']
                );
            }

            $display = (isset($this->input['_no_message_link']) ? htmlescape($item->getNameID())
                                                            : $item->getLink());

            //TRANS : %s is the description of the added item
            Session::addMessageAfterRedirect(sprintf(
                __s('%1$s: %2$s'),
                __s('Item successfully added'),
                $display
            ));
        }
    }

    /**
     * Add a message on delete action
     **/
    public function addMessageOnPurgeAction()
    {
        if (!$this->maybeDeleted()) {
            return;
        }

        $addMessAfterRedirect = false;
        if (isset($this->input['_delete'])) {
            $addMessAfterRedirect = true;
        }

        if (
            isset($this->input['_no_message'])
            || !$this->auto_message_on_action
        ) {
            $addMessAfterRedirect = false;
        }

        if ($addMessAfterRedirect) {
            $item = getItemForItemtype($this->fields['itemtype']);
            $item->getFromDB($this->fields['items_id']);

            if (isset($this->input['_no_message_link'])) {
                $display = htmlescape($item->getNameID());
            } else {
                $display = $item->getLink();
            }
            //TRANS : %s is the description of the updated item
            Session::addMessageAfterRedirect(sprintf(__s('%1$s: %2$s'), __s('Item successfully deleted'), $display));
        }
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        if ($field === 'items_id') {
            if (str_contains($values[$field], "_")) {
                $item_itemtype = explode("_", $values[$field]);
                $values['itemtype'] = $item_itemtype[0];
                $values[$field] = $item_itemtype[1];
            }

            if (isset($values['itemtype'])) {
                $table = getTableForItemType($values['itemtype']);
                $value = (int) $values[$field];
                $name = Dropdown::getDropdownName($table, $value);
                if (isset($options['comments']) && $options['comments']) {
                    $comments = Dropdown::getDropdownComments($table, $value);
                    return sprintf(
                        __s('%1$s %2$s'),
                        htmlescape($name),
                        Html::showToolTip($comments, ['display' => false])
                    );
                }
                return htmlescape($name);
            }
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;
        if ($field === 'items_id') {
            if (!empty($values['itemtype'])) {
                $options['name'] = $name;
                $options['value'] = $values[$field];
                return Dropdown::show($values['itemtype'], $options);
            } else {
                static::dropdownAllDevices($name, 0, 0);
                return ' ';
            }
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    public static function dropdownAllDevices(
        $myname,
        $itemtype,
        $items_id = 0,
        $admin = 0,
        $users_id = 0,
        $entity_restrict = -1,
        $options = []
    ) {
        global $CFG_GLPI;

        $params = [static::$items_id_1 => 0,
            'used'       => [],
            'multiple'   => 0,
            'rand'       => mt_rand(),
        ];

        foreach ($options as $key => $val) {
            $params[$key] = $val;
        }

        $rand = (int) $params['rand'];

        if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"] == 0) {
            echo "<input type='hidden' name='" . htmlescape($myname) . "' value=''>";
            echo "<input type='hidden' name='items_id' value='0'>";
        } else {
            echo "<div id='tracking_all_devices$rand' class='input-group mb-1'>";
            // KEEP Ticket::HELPDESK_ALL_HARDWARE because it is only define on ticket level
            if (
                $_SESSION["glpiactiveprofile"]["helpdesk_hardware"] & (2 ** Ticket::HELPDESK_ALL_HARDWARE)
            ) {
                // Display a message if view my hardware
                if (
                    $users_id
                    && ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"] & (2 ** Ticket::HELPDESK_MY_HARDWARE))
                ) {
                    echo "<span class='input-group-text'>" . __s('Or complete search') . "</span>";
                }

                $types = static::$itemtype_1::getAllTypesForHelpdesk();
                $types = array_filter($types, static fn($k) => $k::canView(), ARRAY_FILTER_USE_KEY);
                $emptylabel = __('General');
                if ($params[static::$items_id_1] > 0) {
                    $emptylabel = Dropdown::EMPTY_VALUE;
                }
                Dropdown::showItemTypes(
                    $myname,
                    array_keys($types),
                    [
                        'emptylabel'          => $emptylabel,
                        'value'               => $itemtype,
                        'rand'                => $rand,
                        'display_emptychoice' => true,
                    ]
                );
                $found_type = isset($types[$itemtype]);

                $p = [
                    'source_itemtype' => static::$itemtype_1,
                    'itemtype'        => '__VALUE__',
                    'entity_restrict' => $entity_restrict,
                    'admin'           => $admin,
                    'used'            => $params['used'],
                    'multiple'        => $params['multiple'],
                    'rand'            => $rand,
                    'myname'          => "add_items_id",
                ];

                if (isset($params['width'])) {
                    $p['width'] = $params['width'];
                }

                Ajax::updateItemOnSelectEvent(
                    Html::cleanId("dropdown_$myname$rand"),
                    Html::cleanId("results_$myname$rand"),
                    $CFG_GLPI["root_doc"] . "/ajax/dropdownTrackingDeviceType.php",
                    $p
                );
                echo "<span id='" . htmlescape(Html::cleanId("results_" . $myname . "$rand")) . "' class='d-flex align-items-center'>\n";

                // Display default value if itemtype is displayed
                if (
                    $found_type
                    && $itemtype
                ) {
                    if (
                        ($item = getItemForItemtype($itemtype))
                        && $items_id
                    ) {
                        if ($item->getFromDB($items_id)) {
                            Dropdown::showFromArray(
                                'items_id',
                                [$items_id => $item->getName()],
                                ['value' => $items_id]
                            );
                        }
                    } else {
                        $p['source_itemtype'] = static::$itemtype_1;
                        $p['itemtype'] = $itemtype;
                        echo Ajax::updateItem(
                            "results_" . $myname . "$rand",
                            $CFG_GLPI["root_doc"] . "/ajax/dropdownTrackingDeviceType.php",
                            $p
                        );
                    }
                }
                echo "</span>\n";
            }
            echo "</div>";
        }
        return $rand;
    }

    /**
     * ITIL tabs for assets should only be displayed if the asset already
     * has associated ITIL items OR if the current user profile is allowed to
     * link this asset to ITIL items
     *
     * @param CommonDBTM $asset
     *
     * @return bool
     */
    protected function shouldDisplayTabForAsset(CommonDBTM $asset): bool
    {
        // Always display tab if the current profile is allowed to link ITIL
        // items to this asset
        if (
            in_array(
                $asset::class,
                $_SESSION["glpiactiveprofile"]["helpdesk_item_type"] ?? []
            )
        ) {
            return true;
        }

        // Only show if at least one item is already linked
        return countElementsInTable(
            static::getTable(),
            [
                'itemtype' => $asset::getType(),
                'items_id' => $asset->getId(),
            ]
        ) > 0;
    }

    /**
     * Print the HTML ajax associated item add
     *
     * @param CommonITILObject|TicketRecurrent $object
     * @param array $options   array of possible options:
     *    - id                  : ID of the ticket
     *    - _users_id_requester : ID of the requester user
     *    - items_id            : array of elements (itemtype => array(id1, id2, id3, ...))
     *
     * @return void
     **/
    public static function itemAddForm(CommonITILObject|TicketRecurrent $object, $options = [])
    {
        if (!($object instanceof static::$itemtype_1)) {
            return;
        }

        if (($options['id'] ?? 0) > 0) {
            // Get requester
            $class  = getItemForItemtype($object->userlinkclass);
            $actors = $class->getActors($options['id']);
            if (
                isset($actors[CommonITILActor::REQUESTER])
                && (count($actors[CommonITILActor::REQUESTER]) === 1)
            ) {
                foreach ($actors[CommonITILActor::REQUESTER] as $user_id_single) {
                    $options['_users_id_requester'] = $user_id_single['users_id'];
                }
            }
        }
        self::displayItemAddForm($object, $options);
    }
}
