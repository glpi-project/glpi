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
 * Document_Item Class
 *
 *  Relation between Documents and Items
 **/
class Document_Item extends CommonDBRelation
{
    // From CommonDBRelation
    public static $itemtype_1    = 'Document';
    public static $items_id_1    = 'documents_id';
    public static $take_entity_1 = true;

    public static $itemtype_2    = 'itemtype';
    public static $items_id_2    = 'items_id';
    public static $take_entity_2 = false;

    public static function getTypeName($nb = 0)
    {
        return _n('Document item', 'Document items', $nb);
    }

    public function getForbiddenStandardMassiveAction()
    {
        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }

    public function canCreateItem(): bool
    {
        if ($this->fields['itemtype'] === 'Ticket') {
            $ticket = new Ticket();
            // Not item linked for closed tickets
            if (
                $ticket->getFromDB($this->fields['items_id'])
                && in_array($ticket->fields['status'], $ticket->getClosedStatusArray(), true)
            ) {
                return false;
            }
        }

        return parent::canCreateItem();
    }

    public function prepareInputForAdd($input)
    {
        if (empty($input['itemtype'])) {
            trigger_error('Item type is mandatory', E_USER_WARNING);
            return false;
        }

        if (!class_exists($input['itemtype'])) {
            trigger_error(sprintf('No class found for type %s', $input['itemtype']), E_USER_WARNING);
            return false;
        }

        if (
            (empty($input['items_id']))
            && ($input['itemtype'] !== 'Entity')
        ) {
            trigger_error('Item ID is mandatory', E_USER_WARNING);
            return false;
        }

        if (empty($input['documents_id'])) {
            trigger_error('Document ID is mandatory', E_USER_WARNING);
            return false;
        }

        // Do not insert circular link for document
        if (
            ($input['itemtype'] === Document::class)
            && ((int) $input['items_id'] === (int) $input['documents_id'])
        ) {
            trigger_error('Cannot link document to itself', E_USER_WARNING);
            return false;
        }

        // #1476 - Inject ID of the actual user to known who attach an already existing document
        // to another item
        if (!isset($input['users_id'])) {
            $input['users_id'] = Session::getLoginUserID();
        }

        /** FIXME: should not this be handled on CommonITILObject side? */
        if (is_subclass_of($input['itemtype'], 'CommonITILObject') && !isset($input['timeline_position'])) {
            $input['timeline_position'] = CommonITILObject::TIMELINE_LEFT;
            if (isset($input["users_id"])) {
                $input['timeline_position'] = $input['itemtype']::getTimelinePosition($input['items_id'], static::class, $input["users_id"]);
            }
        }

        // Avoid duplicate entry
        if ($this->alreadyExists($input)) {
            trigger_error('Duplicated document item relation', E_USER_WARNING);
            return false;
        }

        return parent::prepareInputForAdd($input);
    }

    /**
     * Check if relation already exists.
     *
     * @param array $input
     *
     * @return boolean
     *
     * @since 9.5.0
     */
    public function alreadyExists(array $input): bool
    {
        $criteria = [
            'documents_id'      => $input['documents_id'],
            'itemtype'          => $input['itemtype'],
            'items_id'          => $input['items_id'],
            'timeline_position' => $input['timeline_position'] ?? null,
        ];
        if (array_key_exists('timeline_position', $input) && !empty($input['timeline_position'])) {
            $criteria['timeline_position'] = $input['timeline_position'];
        }
        return countElementsInTable(static::getTable(), $criteria) > 0;
    }

    public function pre_deleteItem()
    {
        // fordocument mandatory
        if ($this->fields['itemtype'] === Ticket::class) {
            $ticket = new Ticket();
            $ticket->getFromDB($this->fields['items_id']);

            $tt = $ticket->getITILTemplateToUse(
                0,
                $ticket->fields['type'],
                $ticket->fields['itilcategories_id'],
                $ticket->fields['entities_id']
            );

            if (isset($tt->mandatory['_documents_id'])) {
                // refuse delete if only one document
                if (
                    countElementsInTable(
                        static::getTable(),
                        ['items_id' => $this->fields['items_id'],
                            'itemtype' => 'Ticket',
                        ]
                    ) === 1
                ) {
                    $message = sprintf(
                        __('Mandatory fields are not filled. Please correct: %s'),
                        Document::getTypeName(Session::getPluralNumber())
                    );
                    Session::addMessageAfterRedirect(htmlescape($message), false, ERROR);
                    return false;
                }
            }
        }
        return true;
    }

    public function post_addItem()
    {
        if ($this->fields['itemtype'] === Ticket::class) {
            $ticket = new Ticket();
            $input  = [
                'id'              => $this->fields['items_id'],
                'date_mod'        => $_SESSION["glpi_currenttime"],
            ];

            if (!isset($this->input['_do_notif']) || $this->input['_do_notif']) {
                $input['_forcenotif'] = true;
            }
            if (isset($this->input['_disablenotif']) && $this->input['_disablenotif']) {
                $input['_disablenotif'] = true;
            }

            $ticket->update($input);
        }

        self::addToMergedTickets();

        parent::post_addItem();
    }

    private function addToMergedTickets(): void
    {
        $merged = Ticket::getMergedTickets($this->fields['items_id']);
        foreach ($merged as $ticket_id) {
            $input = $this->input;
            $input['items_id'] = $ticket_id;

            $document = new self();
            $document->add($input);
        }
    }

    public function post_purgeItem()
    {
        if ($this->fields['itemtype'] === Ticket::class) {
            $ticket = new Ticket();
            $input = [
                'id'              => $this->fields['items_id'],
                'date_mod'        => $_SESSION["glpi_currenttime"],
            ];

            if (!isset($this->input['_do_notif']) || $this->input['_do_notif']) {
                $input['_forcenotif'] = true;
            }

            //Clean ticket description if an image is in it
            $doc = new Document();
            $doc->getFromDB($this->fields['documents_id']);
            if (!empty($doc->fields['tag'])) {
                $ticket->getFromDB($this->fields['items_id']);
                $input['content'] = Toolbox::cleanTagOrImage(
                    $ticket->fields['content'],
                    [$doc->fields['tag']]
                );
            }

            $ticket->update($input);
        }
        parent::post_purgeItem();
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$item instanceof CommonDBTM) {
            return '';
        }

        $nbdoc = $nbitem = 0;
        switch ($item::class) {
            case Document::class:
                $ong = [];
                if ($_SESSION['glpishow_count_on_tabs'] && !$item->isNewItem()) {
                    $nbdoc  = self::countForMainItem($item, ['NOT' => ['itemtype' => 'Document']]);
                    $nbitem = self::countForMainItem($item, ['itemtype' => 'Document']);
                }
                $ong[1] = self::createTabEntry(_n(
                    'Associated item',
                    'Associated items',
                    Session::getPluralNumber()
                ), $nbdoc, $item::class, 'ti ti-package');
                $ong[2] = self::createTabEntry(
                    Document::getTypeName(Session::getPluralNumber()),
                    $nbitem,
                    $item::class
                );
                return $ong;

            default:
                // Can exist for template
                if (
                    Document::canView()
                    || ($item::class === Ticket::class)
                    || ($item::class === Reminder::class)
                    || ($item::class === KnowbaseItem::class)
                ) {
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nbitem = self::countForItem($item);
                    }
                    return self::createTabEntry(
                        Document::getTypeName(Session::getPluralNumber()),
                        $nbitem,
                        $item::class
                    );
                }
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$item instanceof CommonDBTM) {
            return false;
        }

        if ($item instanceof Document && $tabnum === 1) {
            return self::showForDocument($item);
        }
        return self::showForItem($item, $withtemplate);
    }

    /**
     * Show items links to a document
     *
     * @since 0.84
     *
     * @param Document $doc Document object
     *
     * @return bool
     **/
    public static function showForDocument(Document $doc): bool
    {
        $instID = $doc->fields['id'];
        if (!$doc->can($instID, READ)) {
            return false;
        }
        $canedit = $doc->can($instID, UPDATE);
        // for a document,
        // don't show here others documents associated to this one,
        // it's done for both directions in self::showAssociated
        $types_iterator = self::getDistinctTypes($instID, ['NOT' => ['itemtype' => 'Document']]);

        $rand   = mt_rand();
        if ($canedit) {
            $twig_params = [
                'doc' => $doc,
                'entity_restrict' => $doc->fields['is_recursive'] ? getSonsOf('glpi_entities', $doc->fields['entities_id']) : $doc->fields['entities_id'],
                'add_item_msg' => __('Add an item'),
                'add_btn_msg' => _x('button', 'Add'),
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                {% import 'components/form/fields_macros.html.twig' as fields %}
                {% import 'components/form/basic_inputs_macros.html.twig' as inputs %}
                {% set rand = random() %}
                <div class="mb-3">
                    <form method="post" action="{{ 'Document_Item'|itemtype_form_path }}">
                        {{ inputs.hidden('_glpi_csrf_token', csrf_token()) }}
                        {{ inputs.hidden('documents_id', doc.fields['id']) }}
                        {{ fields.dropdownItemsFromItemtypes('', add_item_msg, {
                            'itemtypes': doc.getItemtypesThatCanHave(),
                            'entity_restrict': entity_restrict,
                            'checkright': true
                        }) }}
                        <div class="d-flex px-3 flex-row-reverse">
                            {{ inputs.submit('add', add_btn_msg, 1) }}
                        </div>
                    </form>
                </div>
TWIG, $twig_params);
        }

        $entries = [];
        $entity_names = [];
        foreach ($types_iterator as $type_row) {
            $main_itemtype = $type_row['itemtype'];
            if (!is_a($main_itemtype, CommonDBTM::class, true)) {
                continue;
            }

            if ($main_itemtype::canView()) {
                $iterator = self::getTypeItems($instID, $main_itemtype);

                foreach ($iterator as $data) {
                    if (!($item = getItemForItemtype($main_itemtype))) {
                        continue;
                    }
                    $linkname_extra = "";
                    if (($item instanceof ITILFollowup || $item instanceof ITILSolution) && is_a($data['itemtype'], CommonDBTM::class, true)) {
                        $linkname_extra = "(" . $item::getTypeName(1) . ")";
                        $item = new $data['itemtype']();
                        $item->getFromDB($data['items_id']);
                        $data['id'] = $item->fields['id'];
                        $data['entity'] = $item->fields['entities_id'];
                    } elseif (
                        $item instanceof CommonITILTask
                        || $item instanceof CommonITILValidation
                    ) {
                        $linkname_extra = "(" . CommonITILTask::getTypeName(1) . ")";
                        $item = $item::getItilObjectItemInstance();
                        $item->getFromDB($data[$item::getForeignKeyField()]);
                        $data['id'] = $item->fields['id'];
                        $data['entity'] = $item->fields['entities_id'];
                    }

                    if ($item instanceof Item_Devices) {
                        $tmpitem = getItemForItemtype($item::$itemtype_2);
                        $name = $tmpitem->getFromDB($data[$item::$items_id_2]) ? $tmpitem->getLink() : htmlescape(NOT_AVAILABLE);
                    } else {
                        $link = $item::getFormURLWithID($data['id']);

                        $linkname = match (true) {
                            $item instanceof CommonDevice => $data["designation"],
                            $item instanceof CommonITILObject => sprintf(__('%1$s: %2$s'), $item::getTypeName(1), $data["id"]),
                            $item instanceof Notepad => $data["itemtype"],
                            $item instanceof SoftwareLicense => ($soft = new Software())->getFromDB($data['softwares_id'])
                                ? sprintf(
                                    __('%1$s - %2$s'),
                                    $data["name"],
                                    $soft->fields['name']
                                )
                                : NOT_AVAILABLE,
                            default => $data[$item::getNameField()]
                        };

                        if (
                            $_SESSION["glpiis_ids_visible"]
                            || empty($data["name"])
                        ) {
                            $linkname = sprintf(__('%1$s (%2$s)'), $linkname, $data["id"]);
                        }

                        $name = '<a href="' . htmlescape($link) . '">' . htmlescape($linkname) . ' ' . htmlescape($linkname_extra) . "</a>";
                    }

                    $entity_name = '-';
                    if (isset($data['entity'])) {
                        if (!isset($entity_names[$data['entity']])) {
                            $entity_names[$data['entity']] = Dropdown::getDropdownName(
                                "glpi_entities",
                                $data['entity']
                            );
                        }
                        $entity_name = $entity_names[$data['entity']];
                    }
                    $entries[] = [
                        'itemtype' => self::class,
                        'row_class' => $data['is_deleted'] ? 'table-danger' : '',
                        'id'       => $data['linkid'],
                        'linked_itemtype' => $item::getTypeName(1),
                        'name'    => $name,
                        'entity'  => $entity_name,
                        'serial'  => $data["serial"] ?? "-",
                        'otherserial' => $data["otherserial"] ?? "-",
                    ];
                }
            }
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'columns' => [
                'linked_itemtype' => _n('Type', 'Types', 1),
                'name'            => __('Name'),
                'entity'          => Entity::getTypeName(1),
                'serial'          => __('Serial number'),
                'otherserial'     => __('Inventory number'),
            ],
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

        return true;
    }

    /**
     * Show documents associated to an item
     *
     * @since 0.84
     *
     * @param CommonDBTM $item         Object for which associated documents must be displayed
     * @param int        $withtemplate (default 0)
     *
     * @return bool
     **/
    public static function showForItem(CommonDBTM $item, $withtemplate = 0): bool
    {
        $ID = $item->getField('id');

        if ($item->isNewID($ID)) {
            return false;
        }

        if (
            ($item::class !== Ticket::class)
            && ($item::class !== KnowbaseItem::class)
            && ($item::class !== Reminder::class)
            && !Document::canView()
        ) {
            return false;
        }

        $params         = [];
        $params['rand'] = mt_rand();

        self::showAddFormForItem($item, $withtemplate, $params);
        self::showListForItem($item, $withtemplate, $params);

        return true;
    }

    /**
     * @since 0.90
     *
     * @param $item
     * @param $withtemplate    (default 0)
     * @param $options         array
     *
     * @return boolean
     **/
    public static function showAddFormForItem(CommonDBTM $item, $withtemplate = 0, $options = [])
    {
        global $CFG_GLPI, $DB;

        //default options
        $params['rand'] = mt_rand();
        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $params[$key] = $val;
            }
        }

        if (!$item->can($item->fields['id'], READ)) {
            return false;
        }

        if (empty($withtemplate)) {
            $withtemplate = 0;
        }

        // find documents already associated to the item
        $doc_item   = new self();
        $used_found = $doc_item->find([
            'items_id'  => $item->getID(),
            'itemtype'  => $item::class,
        ]);
        $used       = array_keys($used_found);
        $used       = array_combine($used, $used);

        if (
            $item->canAddItem('Document')
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

            $count = $DB->request([
                'COUNT'     => 'cpt',
                'FROM'      => 'glpi_documents',
                'WHERE'     => [
                    'is_deleted' => 0,
                ] + getEntitiesRestrictCriteria('glpi_documents', '', $entities, true),
            ])->current();
            $nb = $count['cpt'];

            if ($item::class === Document::class) {
                $used[$item->getID()] = $item->getID();
            }

            TemplateRenderer::getInstance()->display('pages/management/document_item.html.twig', [
                'canview' => Document::canView(),
                'item' => $item,
                'used' => $used,
                'entity' => $entity,
                'entities' => $entities,
                'nb' => $nb,
                'rand' => mt_rand(),
            ]);
        }

        return true;
    }

    /**
     * @since 0.90
     *
     * @param CommonDBTM $item
     * @param integer $withtemplate
     * @param array $options
     */
    public static function showListForItem(CommonDBTM $item, $withtemplate = 0, $options = [])
    {
        global $DB;

        $canedit = $item->canAddItem('Document') && Document::canView();

        $columns = [
            'name'      => __('Name'),
            'entity'    => Entity::getTypeName(1),
            'filename'  => __('File'),
            'link'      => __('Web link'),
            'headings'  => __('Heading'),
            'mime'      => __('MIME type'),
            'tag'       => __('Tag'),
            'assocdate' => _n('Date', 'Dates', 1),
        ];

        if (isset($_GET["order"]) && ($_GET["order"] === 'ASC')) {
            $order = "ASC";
        } else {
            $order = "DESC";
        }

        if (!empty($_GET["sort"]) && isset($columns[$_GET["sort"]])) {
            $sort = $_GET["sort"];
        } else {
            $sort = "assocdate";
        }

        if (empty($withtemplate)) {
            $withtemplate = 0;
        }

        $criteria = self::getDocumentForItemRequest($item, ["$sort $order"]);

        // Document : search links in both order using union
        if ($item::class === Document::class) {
            $owhere = $criteria['WHERE'];
            $o2where =  $owhere + ['glpi_documents_items.documents_id' => $item->getID()];
            unset($o2where['glpi_documents_items.items_id']);
            $criteria['WHERE'] = [
                'OR' => [
                    $owhere,
                    $o2where,
                ],
            ];
        }

        $iterator = $DB->request($criteria);

        $documents = [];
        foreach ($iterator as $data) {
            $documents[$data['assocID']] = $data;
        }

        $document = new Document();
        $category_names = [];
        $entries  = [];
        foreach ($documents as $data) {
            $docID        = $data["id"];
            $name         = htmlescape(NOT_AVAILABLE);
            $downloadlink = htmlescape(NOT_AVAILABLE);


            if ($document->getFromDB($docID)) {
                $name         = $document->getLink();
                $downloadlink = $document->getDownloadLink($item);
            }

            if ($item::class !== Document::class) {
                Session::addToNavigateListItems(Document::class, $docID);
            }

            $link = !empty($data["link"])
                ? '<a target="_blank" href="' . htmlescape(Toolbox::formatOutputWebLink($data["link"])) . '">' . htmlescape($data["link"]) . "</a>"
                : '';

            if (!isset($category_names[$data["documentcategories_id"]])) {
                $category_names[$data["documentcategories_id"]] = Dropdown::getDropdownName(
                    "glpi_documentcategories",
                    $data["documentcategories_id"]
                );
            }
            $entries[] = [
                'itemtype' => self::class,
                'row_class' => $data['is_deleted'] ? 'table-danger' : '',
                'id'       => $data['assocID'],
                'name'     => $name,
                'entity'   => $data['entity'],
                'filename' => $downloadlink,
                'link'     => $link,
                'headings' => $category_names[$data["documentcategories_id"]],
                'mime'     => $data["mime"],
                'tag'      => !empty($data["tag"]) ? Document::getImageTag($data["tag"]) : '',
                'assocdate' => $data["assocdate"],
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'columns' => $columns,
            'formatters' => [
                'name' => 'raw_html',
                'filename' => 'raw_html',
                'link' => 'raw_html',
                'assocdate' => 'datetime',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit && $withtemplate < 2,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . mt_rand(),
            ],
        ]);
    }

    public static function getRelationMassiveActionsPeerForSubForm(MassiveAction $ma)
    {
        return match ($ma->getAction()) {
            'add', 'remove' => 1,
            'add_item', 'remove_item' => 2,
            default => 0,
        };
    }

    public static function getRelationMassiveActionsSpecificities()
    {
        $specificities              = parent::getRelationMassiveActionsSpecificities();
        $specificities['itemtypes'] = Document::getItemtypesThatCanHave();

        // Define normalized action for add_item and remove_item
        $specificities['normalized']['add'][]          = 'add_item';
        $specificities['normalized']['remove'][]       = 'remove_item';

        // Set the labels for add_item and remove_item
        $specificities['button_labels']['add_item']    = $specificities['button_labels']['add'];
        $specificities['button_labels']['remove_item'] = $specificities['button_labels']['remove'];

        return $specificities;
    }

    /**
     * Get items for an itemtype
     *
     * @since 9.3.1
     *
     * @param integer $items_id Object id to restrict on
     * @param class-string<CommonDBTM> $itemtype Type for items to retrieve
     * @param boolean $noent    Flag to not compute enitty information (see Document_Item::getTypeItemsQueryParams)
     * @param array   $where    Inital WHERE clause. Defaults to []
     *
     * @return array Criteria to use in a request
     */
    protected static function getTypeItemsQueryParams($items_id, $itemtype, $noent = false, $where = [])
    {
        $commonwhere = ['OR'  => [
            static::getTable() . '.' . static::$items_id_1  => $items_id,
            [
                static::getTable() . '.itemtype'                => static::$itemtype_1,
                static::getTable() . '.' . static::$items_id_2  => $items_id,
            ],
        ],
        ];

        if ($itemtype !== KnowbaseItem::class) {
            $params = parent::getTypeItemsQueryParams($items_id, $itemtype, $noent, $commonwhere);
        } else {
            //KnowbaseItem case: no entity restriction, we'll manage it here
            $params = parent::getTypeItemsQueryParams($items_id, $itemtype, true, $commonwhere);
            $params['SELECT'][] = new QueryExpression('-1 AS entity');
            $kb_params = KnowbaseItem::getVisibilityCriteria();

            if (!Session::getLoginUserID()) {
                // Anonymous access
                $kb_params['WHERE'] = [
                    'glpi_entities_knowbaseitems.entities_id'    => 0,
                    'glpi_entities_knowbaseitems.is_recursive'   => 1,
                ];
            }

            $params = array_merge_recursive($params, $kb_params);
        }

        return $params;
    }

    /**
     * Get linked items list for specified item
     *
     * @since 9.3.1
     *
     * @param CommonDBTM $item  Item instance
     * @param boolean    $noent Flag to not compute entity information (see Document_Item::getTypeItemsQueryParams)
     *
     * @return array
     */
    protected static function getListForItemParams(CommonDBTM $item, $noent = false)
    {
        if (Session::getLoginUserID()) {
            $params = parent::getListForItemParams($item);
        } else {
            $params = parent::getListForItemParams($item, true);
            // Anonymous access from FAQ
            $params['WHERE'][static::getTable() . '.entities_id'] = 0;
        }

        return $params;
    }

    /**
     * Get distinct item types query parameters
     *
     * @since 9.3.1
     *
     * @param integer $items_id    Object id to restrict on
     * @param array   $extra_where Extra where clause
     *
     * @return array
     */
    public static function getDistinctTypesParams($items_id, $extra_where = [])
    {
        $commonwhere = ['OR'  => [
            static::getTable() . '.' . static::$items_id_1  => $items_id,
            [
                static::getTable() . '.itemtype'                => static::$itemtype_1,
                static::getTable() . '.' . static::$items_id_2  => $items_id,
            ],
        ],
        ];

        $params = parent::getDistinctTypesParams($items_id, $extra_where);
        $params['WHERE'] = $commonwhere;
        if (count($extra_where)) {
            $params['WHERE'][] = $extra_where;
        }

        return $params;
    }

    /**
     * Check if this item author is a support agent
     *
     * @return bool
     */
    public function isFromSupportAgent()
    {
        // If not a CommonITILObject
        if (!is_a($this->fields['itemtype'], CommonITILObject::class, true)) {
            return true;
        }

        // Get parent item
        $commonITILObject = new $this->fields['itemtype']();
        $commonITILObject->getFromDB($this->fields['items_id']);

        $actors = $commonITILObject->getITILActors();
        $user_id = $this->fields['users_id'];
        $roles = $actors[$user_id] ?? [];

        if (in_array(CommonITILActor::ASSIGN, $roles)) {
            // The author is assigned -> support agent
            return true;
        } elseif (
            in_array(CommonITILActor::OBSERVER, $roles)
            || in_array(CommonITILActor::REQUESTER, $roles)
        ) {
            // The author is an observer or a requester -> not a support agent
            return false;
        } else {
            // The author is not an actor of the ticket -> he was most likely a
            // support agent that is no longer assigned to the ticket
            return true;
        }
    }

    public static function getDocumentForItemRequest(CommonDBTM $item, array $order = []): array
    {
        $criteria = [
            'SELECT'    => [
                'glpi_documents_items.id AS assocID',
                'glpi_documents_items.date_creation AS assocdate',
                'glpi_entities.id AS entityID',
                'glpi_entities.completename AS entity',
                'glpi_documentcategories.completename AS headings',
                'glpi_documents.*',
            ],
            'FROM'      => 'glpi_documents_items',
            'LEFT JOIN' => [
                'glpi_documents'  => [
                    'ON' => [
                        'glpi_documents_items'  => 'documents_id',
                        'glpi_documents'        => 'id',
                    ],
                ],
                'glpi_entities'   => [
                    'ON' => [
                        'glpi_documents'  => 'entities_id',
                        'glpi_entities'   => 'id',
                    ],
                ],
                'glpi_documentcategories'  => [
                    'ON' => [
                        'glpi_documentcategories'  => 'id',
                        'glpi_documents'           => 'documentcategories_id',
                    ],
                ],
            ],
            'WHERE'     => [
                'glpi_documents_items.items_id'  => $item->getID(),
                'glpi_documents_items.itemtype'  => $item::class,
            ],
            'ORDERBY'   => $order,
        ];

        if (Session::getLoginUserID()) {
            $criteria['WHERE'] += getEntitiesRestrictCriteria('glpi_documents', '', '', true);
        } else {
            // Anonymous access from FAQ
            $criteria['WHERE']['glpi_documents.entities_id'] = 0;
        }

        return $criteria;
    }

    public static function getIcon()
    {
        return Document::getIcon();
    }
}
