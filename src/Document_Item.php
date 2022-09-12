<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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


    public function canCreateItem()
    {

        if ($this->fields['itemtype'] == 'Ticket') {
            $ticket = new Ticket();
           // Not item linked for closed tickets
            if (
                $ticket->getFromDB($this->fields['items_id'])
                && in_array($ticket->fields['status'], $ticket->getClosedStatusArray())
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
            && ($input['itemtype'] != 'Entity')
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
            ($input['itemtype'] == 'Document')
            && ($input['items_id'] == $input['documents_id'])
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
                $input['timeline_position'] = $input['itemtype']::getTimelinePosition($input['items_id'], $this->getType(), $input["users_id"]);
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
            'timeline_position' => $input['timeline_position'] ?? null
        ];
        if (array_key_exists('timeline_position', $input) && !empty($input['timeline_position'])) {
            $criteria['timeline_position'] = $input['timeline_position'];
        }
        return countElementsInTable($this->getTable(), $criteria) > 0;
    }


    /**
     * @since 0.90.2
     *
     * @see CommonDBTM::pre_deleteItem()
     **/
    public function pre_deleteItem()
    {
       // fordocument mandatory
        if ($this->fields['itemtype'] == 'Ticket') {
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
                        $this->getTable(),
                        ['items_id' => $this->fields['items_id'],
                            'itemtype' => 'Ticket'
                        ]
                    ) == 1
                ) {
                    $message = sprintf(
                        __('Mandatory fields are not filled. Please correct: %s'),
                        Document::getTypeName(Session::getPluralNumber())
                    );
                    Session::addMessageAfterRedirect($message, false, ERROR);
                    return false;
                }
            }
        }
        return true;
    }


    public function post_addItem()
    {

        if ($this->fields['itemtype'] == 'Ticket' && ($this->input['_do_update_ticket'] ?? true)) {
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
        parent::post_addItem();
    }


    /**
     * @since 0.83
     *
     * @see CommonDBTM::post_purgeItem()
     **/
    public function post_purgeItem()
    {

        if ($this->fields['itemtype'] == 'Ticket') {
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
                $input['content'] = Toolbox::addslashes_deep(
                    Toolbox::cleanTagOrImage(
                        $ticket->fields['content'],
                        [$doc->fields['tag']]
                    )
                );
            }

            $ticket->update($input);
        }
        parent::post_purgeItem();
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        $nbdoc = $nbitem = 0;
        switch ($item->getType()) {
            case 'Document':
                $ong = [];
                if ($_SESSION['glpishow_count_on_tabs'] && !$item->isNewItem()) {
                    $nbdoc  = self::countForMainItem($item, ['NOT' => ['itemtype' => 'Document']]);
                    $nbitem = self::countForMainItem($item, ['itemtype' => 'Document']);
                }
                $ong[1] = self::createTabEntry(_n(
                    'Associated item',
                    'Associated items',
                    Session::getPluralNumber()
                ), $nbdoc);
                $ong[2] = self::createTabEntry(
                    Document::getTypeName(Session::getPluralNumber()),
                    $nbitem
                );
                return $ong;

            default:
               // Can exist for template
                if (
                    Document::canView()
                    || ($item->getType() == 'Ticket')
                    || ($item->getType() == 'Reminder')
                    || ($item->getType() == 'KnowbaseItem')
                ) {
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nbitem = self::countForItem($item);
                    }
                    return self::createTabEntry(
                        Document::getTypeName(Session::getPluralNumber()),
                        $nbitem
                    );
                }
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        switch ($item->getType()) {
            case 'Document':
                switch ($tabnum) {
                    case 1:
                        self::showForDocument($item);
                        break;

                    case 2:
                        self::showForItem($item, $withtemplate);
                        break;
                }
                break;

            default:
                self::showForitem($item, $withtemplate);
                break;
        }
        return true;
    }


    /**
     * Show items links to a document
     *
     * @since 0.84
     *
     * @param $doc Document object
     *
     * @return void
     **/
    public static function showForDocument(Document $doc)
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
        $number = count($types_iterator);

        $rand   = mt_rand();
        if ($canedit) {
            echo "<div class='firstbloc'>";
            echo "<form name='documentitem_form$rand' id='documentitem_form$rand' method='post'
               action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";

            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2'><th colspan='2'>" . __('Add an item') . "</th></tr>";

            echo "<tr class='tab_bg_1'><td class='right'>";
            Dropdown::showSelectItemFromItemtypes(['itemtypes'
                                                       => Document::getItemtypesThatCanHave(),
                'entity_restrict'
                                                       => ($doc->fields['is_recursive']
                                                           ? getSonsOf(
                                                               'glpi_entities',
                                                               $doc->fields['entities_id']
                                                           )
                                                           : $doc->fields['entities_id']),
                'checkright'
                                                      => true
            ]);
            echo "</td><td class='center'>";
            echo "<input type='submit' name='add' value=\"" . _sx('button', 'Add') . "\" class='btn btn-primary'>";
            echo "<input type='hidden' name='documents_id' value='$instID'>";
            echo "</td></tr>";
            echo "</table>";
            Html::closeForm();
            echo "</div>";
        }

        echo "<div class='spaced table-responsive'>";
        if ($canedit && $number) {
            Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
            $massiveactionparams = ['container' => 'mass' . __CLASS__ . $rand];
            Html::showMassiveActions($massiveactionparams);
        }
        echo "<table class='tab_cadre_fixehov'>";

        $header_begin  = "<tr>";
        $header_top    = '';
        $header_bottom = '';
        $header_end    = '';

        if ($canedit && $number) {
            $header_top    .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            $header_top    .= "</th>";
            $header_bottom .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            $header_bottom .= "</th>";
        }

        $header_end .= "<th>" . _n('Type', 'Types', 1) . "</th>";
        $header_end .= "<th>" . __('Name') . "</th>";
        $header_end .= "<th>" . Entity::getTypeName(1) . "</th>";
        $header_end .= "<th>" . __('Serial number') . "</th>";
        $header_end .= "<th>" . __('Inventory number') . "</th>";
        $header_end .= "</tr>";
        echo $header_begin . $header_top . $header_end;

        foreach ($types_iterator as $type_row) {
            $itemtype = $type_row['itemtype'];
            if (!($item = getItemForItemtype($itemtype))) {
                continue;
            }

            if ($item->canView()) {
                $iterator = self::getTypeItems($instID, $itemtype);

                if ($itemtype == 'SoftwareLicense') {
                    $soft = new Software();
                }

                foreach ($iterator as $data) {
                    $linkname_extra = "";
                    if ($item instanceof ITILFollowup || $item instanceof ITILSolution) {
                        $linkname_extra = "(" . $item::getTypeName(1) . ")";
                        $itemtype = $data['itemtype'];
                        $item = new $itemtype();
                        $item->getFromDB($data['items_id']);
                        $data['id'] = $item->fields['id'];
                        $data['entity'] = $item->fields['entities_id'];
                    } else if (
                        $item instanceof CommonITILTask
                        || $item instanceof CommonITILValidation
                    ) {
                        $linkname_extra = "(" . CommonITILTask::getTypeName(1) . ")";
                        $itemtype = $item->getItilObjectItemType();
                        $item = new $itemtype();
                        $item->getFromDB($data[$item->getForeignKeyField()]);
                        $data['id'] = $item->fields['id'];
                        $data['entity'] = $item->fields['entities_id'];
                    }

                    if ($item instanceof CommonITILObject) {
                        $data["name"] = sprintf(__('%1$s: %2$s'), $item->getTypeName(1), $data["id"]);
                    }

                    if ($itemtype == 'SoftwareLicense') {
                        $soft->getFromDB($data['softwares_id']);
                        $data["name"] = sprintf(
                            __('%1$s - %2$s'),
                            $data["name"],
                            $soft->fields['name']
                        );
                    }
                    if ($item instanceof CommonDevice) {
                        $linkname = $data["designation"];
                    } else if ($item instanceof Item_Devices) {
                        $linkname = $data["itemtype"];
                    } else {
                        $linkname = $data["name"];
                    }
                    if (
                        $_SESSION["glpiis_ids_visible"]
                        || empty($data["name"])
                    ) {
                        $linkname = sprintf(__('%1$s (%2$s)'), $linkname, $data["id"]);
                    }
                    if ($item instanceof Item_Devices) {
                        $tmpitem = new $item::$itemtype_2();
                        if ($tmpitem->getFromDB($data[$item::$items_id_2])) {
                            $linkname = $tmpitem->getLink();
                        }
                    }

                    $link     = $itemtype::getFormURLWithID($data['id']);
                    $name = "<a href='$link'>$linkname $linkname_extra</a>";

                    echo "<tr class='tab_bg_1'>";

                    if ($canedit) {
                        echo "<td width='10'>";
                        Html::showMassiveActionCheckBox(__CLASS__, $data["linkid"]);
                        echo "</td>";
                    }
                    echo "<td class='center'>" . $item->getTypeName(1) . "</td>";
                    echo "<td " .
                     (isset($data['is_deleted']) && $data['is_deleted'] ? "class='tab_bg_2_2'" : "") .
                     ">" . $name . "</td>";
                    echo "<td class='center'>" .
                    (isset($data['entity']) ? Dropdown::getDropdownName(
                        "glpi_entities",
                        $data['entity']
                    ) : "-");
                    echo "</td>";
                    echo "<td class='center'>" .
                        (isset($data["serial"]) ? "" . $data["serial"] . "" : "-") . "</td>";
                    echo "<td class='center'>" .
                        (isset($data["otherserial"]) ? "" . $data["otherserial"] . "" : "-") . "</td>";
                    echo "</tr>";
                }
            }
        }

        if ($number) {
            echo $header_begin . $header_bottom . $header_end;
        }
        echo "</table>";
        if ($canedit && $number) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
        }
        echo "</div>";
    }

    /**
     * Show documents associated to an item
     *
     * @since 0.84
     *
     * @param $item            CommonDBTM object for which associated documents must be displayed
     * @param $withtemplate    (default 0)
     **/
    public static function showForItem(CommonDBTM $item, $withtemplate = 0)
    {
        $ID = $item->getField('id');

        if ($item->isNewID($ID)) {
            return false;
        }

        if (
            ($item->getType() != 'Ticket')
            && ($item->getType() != 'KnowbaseItem')
            && ($item->getType() != 'Reminder')
            && !Document::canView()
        ) {
            return false;
        }

        $params         = [];
        $params['rand'] = mt_rand();

        self::showAddFormForItem($item, $withtemplate, $params);
        self::showListForItem($item, $withtemplate, $params);
    }


    /**
     * @since 0.90
     *
     * @param $item
     * @param $withtemplate   (default 0)
     * @param $colspan
     */
    public static function showSimpleAddForItem(CommonDBTM $item, $withtemplate = 0, $colspan = 1)
    {

        $entity = $_SESSION["glpiactive_entity"];
        if ($item->isEntityAssign()) {
           /// Case of personal items : entity = -1 : create on active entity (Reminder case))
            if ($item->getEntityID() >= 0) {
                $entity = $item->getEntityID();
            }
        }

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Add a document') . "</td>";
        echo "<td colspan='$colspan'>";
        echo "<input type='hidden' name='entities_id' value='$entity'>";
        echo "<input type='hidden' name='is_recursive' value='" . $item->isRecursive() . "'>";
        echo "<input type='hidden' name='itemtype' value='" . $item->getType() . "'>";
        echo "<input type='hidden' name='items_id' value='" . $item->getID() . "'>";
        if ($item->getType() == 'Ticket') {
            echo "<input type='hidden' name='tickets_id' value='" . $item->getID() . "'>";
        }
        Html::file(['multiple' => true]);
        echo "</td><td class='left'>(" . Document::getMaxUploadSize() . ")&nbsp;</td>";
        echo "<td></td></tr>";
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
        global $DB, $CFG_GLPI;

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
            'itemtype'  => $item->getType()
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
               /// Case of personal items : entity = -1 : create on active entity (Reminder case))
                if ($item->getEntityID() >= 0) {
                    $entity = $item->getEntityID();
                }

                if ($item->isRecursive()) {
                    $entities = getSonsOf('glpi_entities', $entity);
                } else {
                    $entities = $entity;
                }
            }
            $limit = getEntitiesRestrictRequest(" AND ", "glpi_documents", '', $entities, true);

            $count = $DB->request([
                'COUNT'     => 'cpt',
                'FROM'      => 'glpi_documents',
                'WHERE'     => [
                    'is_deleted' => 0
                ] + getEntitiesRestrictCriteria('glpi_documents', '', $entities, true)
            ])->current();
            $nb = $count['cpt'];

            if ($item->getType() == 'Document') {
                $used[$item->getID()] = $item->getID();
            }

            echo "<div class='firstbloc'>";
            echo "<form name='documentitem_form" . $params['rand'] . "' id='documentitem_form" .
               $params['rand'] . "' method='post' action='" . Toolbox::getItemTypeFormURL('Document') .
               "' enctype=\"multipart/form-data\">";

            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2'><th colspan='5'>" . __('Add a document') . "</th></tr>";
            echo "<tr class='tab_bg_1'>";

            echo "<td class='center'>";
            echo __('Heading');
            echo "</td><td width='20%'>";
            DocumentCategory::dropdown(['entity' => $entities]);
            echo "</td>";
            echo "<td class='right'>";
            echo "<input type='hidden' name='entities_id' value='$entity'>";
            echo "<input type='hidden' name='is_recursive' value='" . $item->isRecursive() . "'>";
            echo "<input type='hidden' name='itemtype' value='" . $item->getType() . "'>";
            echo "<input type='hidden' name='items_id' value='" . $item->getID() . "'>";
            if ($item->getType() == 'Ticket') {
                echo "<input type='hidden' name='tickets_id' value='" . $item->getID() . "'>";
            }
            Html::file(['multiple' => true]);
            echo "</td><td class='left'>(" . Document::getMaxUploadSize() . ")&nbsp;</td>";
            echo "<td class='center' width='20%'>";
            echo "<input type='submit' name='add' value=\"" . _sx('button', 'Add a new file') . "\"
                class='btn btn-primary'>";
            echo "</td></tr>";
            echo "</table>";
            Html::closeForm();

            if (
                Document::canView()
                && ($nb > count($used))
            ) {
                echo "<form name='document_form" . $params['rand'] . "' id='document_form" . $params['rand'] .
                  "' method='post' action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";
                echo "<table class='tab_cadre_fixe'>";
                echo "<tr class='tab_bg_1'>";
                echo "<td colspan='4' class='center'>";
                echo "<input type='hidden' name='itemtype' value='" . $item->getType() . "'>";
                echo "<input type='hidden' name='items_id' value='" . $item->getID() . "'>";
                if ($item->getType() == 'Ticket') {
                    echo "<input type='hidden' name='tickets_id' value='" . $item->getID() . "'>";
                    echo "<input type='hidden' name='documentcategories_id' value='" .
                      $CFG_GLPI["documentcategories_id_forticket"] . "'>";
                }

                Document::dropdown(['entity' => $entities ,
                    'used'   => $used
                ]);
                echo "</td><td class='center' width='20%'>";
                echo "<input type='submit' name='add' value=\"" .
                     _sx('button', 'Associate an existing document') . "\" class='btn btn-primary'>";
                echo "</td>";
                echo "</tr>";
                echo "</table>";
                Html::closeForm();
            }

            echo "</div>";
        }

        return true;
    }


    /**
     * @since 0.90
     *
     * @param $item
     * @param $withtemplate   (default 0)
     * @param $options        array
     */
    public static function showListForItem(CommonDBTM $item, $withtemplate = 0, $options = [])
    {
        global $DB;

       //default options
        $params['rand'] = mt_rand();

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $params[$key] = $val;
            }
        }

        $canedit = $item->canAddItem('Document') && Document::canView();

        $columns = [
            'name'      => __('Name'),
            'entity'    => Entity::getTypeName(1),
            'filename'  => __('File'),
            'link'      => __('Web link'),
            'headings'  => __('Heading'),
            'mime'      => __('MIME type'),
            'tag'       => __('Tag'),
            'assocdate' => _n('Date', 'Dates', 1)
        ];

        if (isset($_GET["order"]) && ($_GET["order"] == "ASC")) {
            $order = "ASC";
        } else {
            $order = "DESC";
        }

        if (
            (isset($_GET["sort"]) && !empty($_GET["sort"]))
            && isset($columns[$_GET["sort"]])
        ) {
            $sort = $_GET["sort"];
        } else {
            $sort = "assocdate";
        }

        if (empty($withtemplate)) {
            $withtemplate = 0;
        }

        $criteria = self::getDocumentForItemRequest($item, ["$sort $order"]);

       // Document : search links in both order using union
        if ($item->getType() == 'Document') {
            $owhere = $criteria['WHERE'];
            $o2where =  $owhere + ['glpi_documents_items.documents_id' => $item->getID()];
            unset($o2where['glpi_documents_items.items_id']);
            $criteria['WHERE'] = [
                'OR' => [
                    $owhere,
                    $o2where
                ]
            ];
        }

        $iterator = $DB->request($criteria);
        $number = count($iterator);
        $i      = 0;

        $documents = [];
        $used      = [];
        foreach ($iterator as $data) {
            $documents[$data['assocID']] = $data;
            $used[$data['id']]           = $data['id'];
        }

        echo "<div class='spaced table-responsive'>";
        if (
            $canedit
            && $number
            && ($withtemplate < 2)
        ) {
            Html::openMassiveActionsForm('mass' . __CLASS__ . $params['rand']);
            $massiveactionparams = ['num_displayed'  => min($_SESSION['glpilist_limit'], $number),
                'container'      => 'mass' . __CLASS__ . $params['rand']
            ];
            Html::showMassiveActions($massiveactionparams);
        }

        echo "<table class='tab_cadre_fixehov'>";

        $header_begin  = "<tr>";
        $header_top    = '';
        $header_bottom = '';
        $header_end    = '';
        if (
            $canedit
            && $number
            && ($withtemplate < 2)
        ) {
            $header_top    .= "<th width='11'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $params['rand']);
            $header_top    .= "</th>";
            $header_bottom .= "<th width='11'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $params['rand']);
            $header_bottom .= "</th>";
        }

        foreach ($columns as $key => $val) {
            $header_end .= "<th" . ($sort == "$key" ? " class='order_$order'" : '') . ">" .
                        "<a href='javascript:reloadTab(\"sort=$key&amp;order=" .
                          (($order == "ASC") ? "DESC" : "ASC") . "&amp;start=0\");'>$val</a></th>";
        }

        $header_end .= "</tr>";
        echo $header_begin . $header_top . $header_end;

        $used = [];

        if ($number) {
           // Don't use this for document associated to document
           // To not loose navigation list for current document
            if ($item->getType() != 'Document') {
                Session::initNavigateListItems(
                    'Document',
                    //TRANS : %1$s is the itemtype name,
                              //        %2$s is the name of the item (used for headings of a list)
                                           sprintf(
                                               __('%1$s = %2$s'),
                                               $item->getTypeName(1),
                                               $item->getName()
                                           )
                );
            }

            $document = new Document();
            foreach ($documents as $data) {
                $docID        = $data["id"];
                $link         = NOT_AVAILABLE;
                $downloadlink = NOT_AVAILABLE;

                if ($document->getFromDB($docID)) {
                    $link         = $document->getLink();
                    $downloadlink = $document->getDownloadLink($item);
                }

                if ($item->getType() != 'Document') {
                    Session::addToNavigateListItems('Document', $docID);
                }
                $used[$docID] = $docID;

                echo "<tr class='tab_bg_1" . ($data["is_deleted"] ? "_2" : "") . "'>";
                if (
                    $canedit
                    && ($withtemplate < 2)
                ) {
                    echo "<td width='10'>";
                    Html::showMassiveActionCheckBox(__CLASS__, $data["assocID"]);
                    echo "</td>";
                }
                echo "<td class='center'>$link</td>";
                echo "<td class='center'>" . $data['entity'] . "</td>";
                echo "<td class='center'>$downloadlink</td>";
                echo "<td class='center'>";
                if (!empty($data["link"])) {
                    echo "<a target=_blank href='" . Toolbox::formatOutputWebLink($data["link"]) . "'>" . $data["link"];
                    echo "</a>";
                } else {
                    echo "&nbsp;";
                }
                echo "</td>";
                echo "<td class='center'>" . Dropdown::getDropdownName(
                    "glpi_documentcategories",
                    $data["documentcategories_id"]
                );
                echo "</td>";
                echo "<td class='center'>" . $data["mime"] . "</td>";
                echo "<td class='center'>";
                echo !empty($data["tag"]) ? Document::getImageTag($data["tag"]) : '';
                echo "</td>";
                echo "<td class='center'>" . Html::convDateTime($data["assocdate"]) . "</td>";
                echo "</tr>";
                $i++;
            }
            echo $header_begin . $header_bottom . $header_end;
        }

        echo "</table>";
        if ($canedit && $number && ($withtemplate < 2)) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
        }
        echo "</div>";
    }


    public static function getRelationMassiveActionsPeerForSubForm(MassiveAction $ma)
    {

        switch ($ma->getAction()) {
            case 'add':
            case 'remove':
                return 1;

            case 'add_item':
            case 'remove_item':
                return 2;
        }
        return 0;
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
     * @param string  $itemtype Type for items to retrieve
     * @param boolean $noent    Flag to not compute enitty information (see Document_Item::getTypeItemsQueryParams)
     * @param array   $where    Inital WHERE clause. Defaults to []
     *
     * @return DBmysqlIterator
     */
    protected static function getTypeItemsQueryParams($items_id, $itemtype, $noent = false, $where = [])
    {
        $commonwhere = ['OR'  => [
            static::getTable() . '.' . static::$items_id_1  => $items_id,
            [
                static::getTable() . '.itemtype'                => static::$itemtype_1,
                static::getTable() . '.' . static::$items_id_2  => $items_id
            ]
        ]
        ];

        if ($itemtype != 'KnowbaseItem') {
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
                    'glpi_entities_knowbaseitems.is_recursive'   => 1
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
            $params['WHERE'][self::getTable() . '.entities_id'] = 0;
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
                static::getTable() . '.' . static::$items_id_2  => $items_id
            ]
        ]
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
        if (!is_a($this->fields['itemtype'], 'CommonITILObject', true)) {
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
        } else if (
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
                'glpi_documents.*'
            ],
            'FROM'      => 'glpi_documents_items',
            'LEFT JOIN' => [
                'glpi_documents'  => [
                    'ON' => [
                        'glpi_documents_items'  => 'documents_id',
                        'glpi_documents'        => 'id'
                    ]
                ],
                'glpi_entities'   => [
                    'ON' => [
                        'glpi_documents'  => 'entities_id',
                        'glpi_entities'   => 'id'
                    ]
                ],
                'glpi_documentcategories'  => [
                    'ON' => [
                        'glpi_documentcategories'  => 'id',
                        'glpi_documents'           => 'documentcategories_id'
                    ]
                ]
            ],
            'WHERE'     => [
                'glpi_documents_items.items_id'  => $item->getID(),
                'glpi_documents_items.itemtype'  => $item->getType()
            ],
            'ORDERBY'   => $order,
        ];

        if (Session::getLoginUserID()) {
            $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria('glpi_documents', '', '', true);
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
