<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Document_Item Class
 *
 *  Relation between Documents and Items
**/
class Document_Item extends CommonDBRelation{


   // From CommonDBRelation
   static public $itemtype_1    = 'Document';
   static public $items_id_1    = 'documents_id';
   static public $take_entity_1 = true;

   static public $itemtype_2    = 'itemtype';
   static public $items_id_2    = 'items_id';
   static public $take_entity_2 = false;


   /**
    * @since 0.84
   **/
   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   /**
    * @since 0.85.5
    * @see CommonDBRelation::canCreateItem()
   **/
   function canCreateItem() {

      if ($this->fields['itemtype'] == 'Ticket') {
         $ticket = new Ticket();
         // Not item linked for closed tickets
         if ($ticket->getFromDB($this->fields['items_id'])
             && in_array($ticket->fields['status'], $ticket->getClosedStatusArray())) {

            return false;
         }
      }

      return parent::canCreateItem();
   }


   function prepareInputForAdd($input) {

      if ((empty($input['items_id']) || ($input['items_id'] == 0))
          && ($input['itemtype'] != 'Entity')) {
         return false;
      }

      // Do not insert circular link for document
      if (($input['itemtype'] == 'Document')
          && ($input['items_id'] == $input['documents_id'])) {
         return false;
      }

      // Avoid duplicate entry
      if (countElementsInTable($this->getTable(),
                              ['documents_id' => $input['documents_id'],
                               'itemtype'     => $input['itemtype'],
                               'items_id'     => $input['items_id']]) > 0) {
         return false;
      }

      // #1476 - Inject ID of the actual user to known who attach an already existing document
      // to another item
      if (!isset($input['users_id'])) {
         $input['users_id'] = Session::getLoginUserID();
      }

      if (is_subclass_of($input['itemtype'], 'CommonITILObject')) {
         $input['timeline_position'] = CommonITILObject::TIMELINE_LEFT;
         if (isset($input["users_id"])) {
            $input['timeline_position'] = $input['itemtype']::getTimelinePosition($input['items_id'], $this->getType(), $input["users_id"]);
         }
      }

      return parent::prepareInputForAdd($input);
   }


   /**
    * @since 0.90.2
    *
    * @see CommonDBTM::pre_deleteItem()
   **/
   function pre_deleteItem() {
      global $DB;

      // fordocument mandatory
      if ($this->fields['itemtype'] == 'Ticket') {
         $ticket = new Ticket();
         $ticket->getFromDB($this->fields['items_id']);

         $tt = $ticket->getTicketTemplateToUse(0, $ticket->fields['type'],
                                               $ticket->fields['itilcategories_id'],
                                               $ticket->fields['entities_id']);

         if (isset($tt->mandatory['_documents_id'])) {
            // refuse delete if only one document
            if (countElementsInTable($this->getTable(),
                                    ['items_id' => $this->fields['items_id'],
                                     'itemtype' => 'Ticket' ]) == 1) {
               $message = sprintf(__('Mandatory fields are not filled. Please correct: %s'),
                                  _n('Document', 'Documents', 2));
               Session::addMessageAfterRedirect($message, false, ERROR);
               return false;
            }
         }
      }
      return true;
   }


   function post_addItem() {

      if ($this->fields['itemtype'] == 'Ticket') {
         $ticket = new Ticket();
         $input  = ['id'            => $this->fields['items_id'],
                         'date_mod'      => $_SESSION["glpi_currenttime"],
                         '_donotadddocs' => true];

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
   function post_purgeItem() {

      if ($this->fields['itemtype'] == 'Ticket') {
         $ticket = new Ticket();
         $input = ['id'            => $this->fields['items_id'],
                        'date_mod'      => $_SESSION["glpi_currenttime"],
                        '_donotadddocs' => true];

         if (!isset($this->input['_do_notif']) || $this->input['_do_notif']) {
            $input['_forcenotif'] = true;
         }

         //Clean ticket description if an image is in it
         $doc = new Document();
         $doc->getFromDB($this->fields['documents_id']);
         if (!empty($doc->fields['tag'])) {
            $ticket->getFromDB($this->fields['items_id']);
            $input['content'] = Toolbox::cleanTagOrImage($ticket->fields['content'],
                                                         [$doc->fields['tag']]);
         }

         $ticket->update($input);
      }
      parent::post_purgeItem();
   }


   /**
    * @param $item   CommonDBTM object
   **/
   static function countForItem(CommonDBTM $item) {

      $restrict = "`glpi_documents_items`.`items_id` = '".$item->getField('id')."'
                   AND `glpi_documents_items`.`itemtype` = '".$item->getType()."'";

      if (Session::getLoginUserID()) {
         $restrict .= getEntitiesRestrictRequest(" AND ", "glpi_documents_items", '', '', true);
      } else {
         // Anonymous access from FAQ
         $restrict .= " AND `glpi_documents_items`.`entities_id` = 0 ";
      }

      $nb = countElementsInTable(['glpi_documents_items'], $restrict);

      // Document case : search in both
      if ($item->getType() == 'Document') {
         $restrict = "`glpi_documents_items`.`documents_id` = '".$item->getField('id')."'
                      AND `glpi_documents_items`.`itemtype` = '".$item->getType()."'";

         if (Session::getLoginUserID()) {
            $restrict .= getEntitiesRestrictRequest(" AND ", "glpi_documents_items", '', '', true);
         } else {
            // Anonymous access from FAQ
            $restrict .= " AND `glpi_documents_items`.`entities_id` = 0 ";
         }
         $nb += countElementsInTable(['glpi_documents_items'], $restrict);
      }
      return $nb;
   }


   /**
    * @param $item   Document object
   **/
   static function countForDocument(Document $item) {
      return countElementsInTable(['glpi_documents_items'],
                                 ['glpi_documents_items.documents_id' => $item->getField('id'),
                                  'NOT' => ['glpi_documents_items.itemtype' => $item->getType()]]);
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      $nbdoc = $nbitem = 0;
      switch ($item->getType()) {
         case 'Document' :
            $ong = [];
            if ($_SESSION['glpishow_count_on_tabs']) {
               $nbdoc  = self::countForDocument($item);
               $nbitem = self::countForItem($item);
            }
            $ong[1] = self::createTabEntry(_n('Associated item', 'Associated items',
                                              Session::getPluralNumber()), $nbdoc);
            $ong[2] = self::createTabEntry(Document::getTypeName(Session::getPluralNumber()),
                                           $nbitem);
            return $ong;

         default :
            // Can exist for template
            if (Document::canView()
                || ($item->getType() == 'Ticket')
                || ($item->getType() == 'Reminder')
                || ($item->getType() == 'KnowbaseItem')) {

               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nbitem = self::countForItem($item);
               }
               return self::createTabEntry(Document::getTypeName(Session::getPluralNumber()),
                                           $nbitem);
            }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      switch ($item->getType()) {
         case 'Document' :
            switch ($tabnum) {
               case 1 :
                  self::showForDocument($item);
                  break;

               case 2 :
                  self::showForItem($item, $withtemplate);
                  break;
            }
            return true;

         default :
            self::showForitem($item, $withtemplate);
      }
   }


   /**
    * Duplicate documents from an item template to its clone
    *
    * @since 0.84
    *
    * @param $itemtype     itemtype of the item
    * @param $oldid        ID of the item to clone
    * @param $newid        ID of the item cloned
    * @param $newitemtype  itemtype of the new item (= $itemtype if empty) (default '')
   **/
   static function cloneItem($itemtype, $oldid, $newid, $newitemtype = '') {
      global $DB;

      if (empty($newitemtype)) {
         $newitemtype = $itemtype;
      }

      foreach ($DB->request('glpi_documents_items',
                            ['FIELDS' => 'documents_id',
                                  'WHERE'  => "`items_id` = '$oldid'
                                                AND `itemtype` = '$itemtype'"]) as $data) {
         $docitem = new self();
         $docitem->add(['documents_id' => $data["documents_id"],
                             'itemtype'     => $newitemtype,
                             'items_id'     => $newid]);
      }
   }


   /**
    * Show items links to a document
    *
    * @since 0.84
    *
    * @param $doc Document object
    *
    * @return nothing (HTML display)
   **/
   static function showForDocument(Document $doc) {
      global $DB, $CFG_GLPI;

      $instID = $doc->fields['id'];
      if (!$doc->can($instID, READ)) {
         return false;
      }
      $canedit = $doc->can($instID, UPDATE);
      // for a document,
      // don't show here others documents associated to this one,
      // it's done for both directions in self::showAssociated
      $query = "SELECT DISTINCT `itemtype`
                FROM `glpi_documents_items`
                WHERE `glpi_documents_items`.`documents_id` = '$instID'
                      AND `glpi_documents_items`.`itemtype` != 'Document'
                ORDER BY `itemtype`";

      $result = $DB->query($query);
      $number = $DB->numrows($result);
      $rand   = mt_rand();
      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form name='documentitem_form$rand' id='documentitem_form$rand' method='post'
               action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='2'>".__('Add an item')."</th></tr>";

         echo "<tr class='tab_bg_1'><td class='right'>";
         Dropdown::showSelectItemFromItemtypes(['itemtypes'
                                                       => Document::getItemtypesThatCanHave(),
                                                     'entity_restrict'
                                                       => ($doc->fields['is_recursive']
                                                           ?getSonsOf('glpi_entities',
                                                                      $doc->fields['entities_id'])
                                                           :$doc->fields['entities_id']),
                                                     'checkright'
                                                      => true]);
         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "<input type='hidden' name='documents_id' value='$instID'>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($canedit && $number) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = ['container' => 'mass'.__CLASS__.$rand];
         Html::showMassiveActions($massiveactionparams);
      }
      echo "<table class='tab_cadre_fixehov'>";

      $header_begin  = "<tr>";
      $header_top    = '';
      $header_bottom = '';
      $header_end    = '';

      if ($canedit && $number) {
         $header_top    .= "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         $header_top    .= "</th>";
         $header_bottom .= "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         $header_bottom .= "</th>";
      }

      $header_end .= "<th>".__('Type')."</th>";
      $header_end .= "<th>".__('Name')."</th>";
      $header_end .= "<th>".__('Entity')."</th>";
      $header_end .= "<th>".__('Serial number')."</th>";
      $header_end .= "<th>".__('Inventory number')."</th>";
      $header_end .= "</tr>";
      echo $header_begin.$header_top.$header_end;

      for ($i=0; $i < $number; $i++) {
         $itemtype=$DB->result($result, $i, "itemtype");
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }

         if ($item->canView()) {
            if ($item instanceof CommonDevice) {
               $column = "designation";
            } else if ($item instanceof Item_Devices) {
               $column = "itemtype";
            } else {
               $column = "name";
            }

            if ($itemtype == 'Ticket') {
               $column = "id";
            }

            $itemtable = getTableForItemType($itemtype);
            $query     = "SELECT `$itemtable`.*,
                                 `glpi_documents_items`.`id` AS IDD, ";

            if ($itemtype == 'KnowbaseItem') {
               $query .= "-1 AS entity
                          FROM `glpi_documents_items`, `$itemtable`
                          ".KnowbaseItem::addVisibilityJoins()."
                          WHERE `$itemtable`.`id` = `glpi_documents_items`.`items_id`
                                AND ";
            } else {
               $query .= "`glpi_entities`.`id` AS entity
                          FROM `glpi_documents_items`, `$itemtable`";

               if ($itemtype != 'Entity') {
                  $query .= " LEFT JOIN `glpi_entities`
                                 ON (`glpi_entities`.`id` = `$itemtable`.`entities_id`)";
               }
               $query .= " WHERE `$itemtable`.`id` = `glpi_documents_items`.`items_id`
                                 AND ";
            }
            $query .= "`glpi_documents_items`.`itemtype` = '$itemtype'
                       AND `glpi_documents_items`.`documents_id` = '$instID' ";

            if ($itemtype =='KnowbaseItem') {
               if (Session::getLoginUserID()) {
                  $where = "AND ".KnowbaseItem::addVisibilityRestrict();
               } else {
                  // Anonymous access
                  if (Session::isMultiEntitiesMode()) {
                     $where = " AND (`glpi_entities_knowbaseitems`.`entities_id` = 0
                                     AND `glpi_entities_knowbaseitems`.`is_recursive` = 1)";
                  }
               }
            } else {
               $query .= getEntitiesRestrictRequest(" AND ", $itemtable, '', '',
                                                   $item->maybeRecursive());
            }

            if ($item->maybeTemplate()) {
               $query .= " AND `$itemtable`.`is_template` = 0";
            }

            if ($itemtype == 'KnowbaseItem') {
               $query .= " ORDER BY `$itemtable`.`$column`";
            } else {
               $query .= " ORDER BY `glpi_entities`.`completename`, `$itemtable`.`$column`";
            }

            if ($itemtype == 'SoftwareLicense') {
               $soft = new Software();
            }

            if ($result_linked = $DB->query($query)) {
               if ($DB->numrows($result_linked)) {

                  while ($data = $DB->fetch_assoc($result_linked)) {

                     if ($itemtype == 'Ticket') {
                        $data["name"] = sprintf(__('%1$s: %2$s'), __('Ticket'), $data["id"]);
                     }

                     if ($itemtype == 'SoftwareLicense') {
                        $soft->getFromDB($data['softwares_id']);
                        $data["name"] = sprintf(__('%1$s - %2$s'), $data["name"],
                                                $soft->fields['name']);
                     }
                     if ($item instanceof CommonDevice) {
                        $linkname = $data["designation"];
                     } else if ($item instanceof Item_Devices) {
                        $linkname = $data["itemtype"];
                     } else {
                        $linkname = $data["name"];
                     }
                     if ($_SESSION["glpiis_ids_visible"]
                         || empty($data["name"])) {
                        $linkname = sprintf(__('%1$s (%2$s)'), $linkname, $data["id"]);
                     }
                     if ($item instanceof Item_Devices) {
                        $tmpitem = new $item::$itemtype_2();
                        if ($tmpitem->getFromDB($data[$item::$items_id_2])) {
                           $linkname = $tmpitem->getLink();
                        }
                     }
                     $link     = $itemtype::getFormURLWithID($data['id']);
                     $name = "<a href=\"".$link."\">".$linkname."</a>";

                     echo "<tr class='tab_bg_1'>";

                     if ($canedit) {
                        echo "<td width='10'>";
                        Html::showMassiveActionCheckBox(__CLASS__, $data["IDD"]);
                        echo "</td>";
                     }
                     echo "<td class='center'>".$item->getTypeName(1)."</td>";
                     echo "<td ".
                           (isset($data['is_deleted']) && $data['is_deleted']?"class='tab_bg_2_2'":"").
                          ">".$name."</td>";
                     echo "<td class='center'>".Dropdown::getDropdownName("glpi_entities",
                                                                          $data['entity']);
                     echo "</td>";
                     echo "<td class='center'>".
                            (isset($data["serial"])? "".$data["serial"]."" :"-")."</td>";
                     echo "<td class='center'>".
                            (isset($data["otherserial"])? "".$data["otherserial"]."" :"-")."</td>";
                     echo "</tr>";
                  }
               }
            }
         }
      }

      if ($number) {
         echo $header_begin.$header_bottom.$header_end;
      }
      echo "</table>";
      if ($canedit && $number) {
         $massiveactionparams['ontop'] =false;
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
   static function showForItem(CommonDBTM $item, $withtemplate = 0) {
      $ID = $item->getField('id');

      if ($item->isNewID($ID)) {
         return false;
      }

      if (($item->getType() != 'Ticket')
          && ($item->getType() != 'KnowbaseItem')
          && ($item->getType() != 'Reminder')
          && !Document::canView()) {
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
   static function showSimpleAddForItem(CommonDBTM $item, $withtemplate = 0, $colspan = 1) {

      $entity = $_SESSION["glpiactive_entity"];
      if ($item->isEntityAssign()) {
         /// Case of personal items : entity = -1 : create on active entity (Reminder case))
         if ($item->getEntityID() >=0) {
            $entity = $item->getEntityID();
         }
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Add a document')."</td>";
      echo "<td colspan='$colspan'>";
      echo "<input type='hidden' name='entities_id' value='$entity'>";
      echo "<input type='hidden' name='is_recursive' value='".$item->isRecursive()."'>";
      echo "<input type='hidden' name='itemtype' value='".$item->getType()."'>";
      echo "<input type='hidden' name='items_id' value='".$item->getID()."'>";
      if ($item->getType() == 'Ticket') {
         echo "<input type='hidden' name='tickets_id' value='".$item->getID()."'>";
      }
      Html::file(['multiple' => true]);
      echo "</td><td class='left'>(".Document::getMaxUploadSize().")&nbsp;</td>";
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
   static function showAddFormForItem(CommonDBTM $item, $withtemplate = 0, $options = []) {
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
      $used_found = $doc_item->find("`items_id` = '".$item->getID()."'
                                    AND `itemtype` = '".$item->getType()."'");
      $used       = array_keys($used_found);
      $used       = array_combine($used, $used);

      if ($item->canAddItem('Document')
          && $withtemplate < 2) {
         // Restrict entity for knowbase
         $entities = "";
         $entity   = $_SESSION["glpiactive_entity"];

         if ($item->isEntityAssign()) {
            /// Case of personal items : entity = -1 : create on active entity (Reminder case))
            if ($item->getEntityID() >=0) {
               $entity = $item->getEntityID();
            }

            if ($item->isRecursive()) {
               $entities = getSonsOf('glpi_entities', $entity);
            } else {
               $entities = $entity;
            }
         }
         $limit = getEntitiesRestrictRequest(" AND ", "glpi_documents", '', $entities, true);

         $q = "SELECT COUNT(*)
               FROM `glpi_documents`
               WHERE `is_deleted` = 0
               $limit";

         $result = $DB->query($q);
         $nb     = $DB->result($result, 0, 0);

         if ($item->getType() == 'Document') {
            $used[$item->getID()] = $item->getID();
         }

         echo "<div class='firstbloc'>";
         echo "<form name='documentitem_form".$params['rand']."' id='documentitem_form".
               $params['rand']."' method='post' action='".Toolbox::getItemTypeFormURL('Document').
               "' enctype=\"multipart/form-data\">";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='5'>".__('Add a document')."</th></tr>";
         echo "<tr class='tab_bg_1'>";

         echo "<td class='center'>";
         echo __('Heading');
         echo "</td><td width='20%'>";
         DocumentCategory::dropdown(['entity' => $entities]);
         echo "</td>";
         echo "<td class='right'>";
         echo "<input type='hidden' name='entities_id' value='$entity'>";
         echo "<input type='hidden' name='is_recursive' value='".$item->isRecursive()."'>";
         echo "<input type='hidden' name='itemtype' value='".$item->getType()."'>";
         echo "<input type='hidden' name='items_id' value='".$item->getID()."'>";
         if ($item->getType() == 'Ticket') {
            echo "<input type='hidden' name='tickets_id' value='".$item->getID()."'>";
         }
         Html::file(['multiple' => true]);
         echo "</td><td class='left'>(".Document::getMaxUploadSize().")&nbsp;</td>";
         echo "<td class='center' width='20%'>";
         echo "<input type='submit' name='add' value=\""._sx('button', 'Add a new file')."\"
                class='submit'>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();

         if (Document::canView()
             && ($nb > count($used))) {
            echo "<form name='document_form".$params['rand']."' id='document_form".$params['rand'].
                  "' method='post' action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='4' class='center'>";
            echo "<input type='hidden' name='itemtype' value='".$item->getType()."'>";
            echo "<input type='hidden' name='items_id' value='".$item->getID()."'>";
            if ($item->getType() == 'Ticket') {
               echo "<input type='hidden' name='tickets_id' value='".$item->getID()."'>";
               echo "<input type='hidden' name='documentcategories_id' value='".
                      $CFG_GLPI["documentcategories_id_forticket"]."'>";
            }

            Document::dropdown(['entity' => $entities ,
                                     'used'   => $used]);
            echo "</td><td class='center' width='20%'>";
            echo "<input type='submit' name='add' value=\"".
                     _sx('button', 'Associate an existing document')."\" class='submit'>";
            echo "</td>";
            echo "</tr>";
            echo "</table>";
            Html::closeForm();
         }

         echo "</div>";
      }
   }


   /**
    * @since 0.90
    *
    * @param $item
    * @param $withtemplate   (default 0)
    * @param $options        array
    */
   static function showListForItem(CommonDBTM $item, $withtemplate = 0, $options = []) {
      global $DB, $CFG_GLPI;

      //default options
      $params['rand'] = mt_rand();

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      $canedit = $item->canAddItem('Document') && Document::canView();

      $columns = ['name'      => __('Name'),
                       'entity'    => __('Entity'),
                       'filename'  => __('File'),
                       'link'      => __('Web link'),
                       'headings'  => __('Heading'),
                       'mime'      => __('MIME type')];
      if ($CFG_GLPI['use_rich_text']) {
         $columns['tag'] = __('Tag');
      }
      $columns['assocdate'] = __('Date');

      if (isset($_GET["order"]) && ($_GET["order"] == "ASC")) {
         $order = "ASC";
      } else {
         $order = "DESC";
      }

      if ((isset($_GET["sort"]) && !empty($_GET["sort"]))
         && isset($columns[$_GET["sort"]])) {
         $sort = "`".$_GET["sort"]."`";
      } else {
         $sort = "`assocdate`";
      }

      if (empty($withtemplate)) {
         $withtemplate = 0;
      }
      $linkparam = '';

      if (get_class($item) == 'Ticket') {
         $linkparam = "&amp;tickets_id=".$item->fields['id'];
      }

      $query = "SELECT `glpi_documents_items`.`id` AS assocID,
                       `glpi_documents_items`.`date_mod` AS assocdate,
                       `glpi_entities`.`id` AS entityID,
                       `glpi_entities`.`completename` AS entity,
                       `glpi_documentcategories`.`completename` AS headings,
                       `glpi_documents`.*
                FROM `glpi_documents_items`
                LEFT JOIN `glpi_documents`
                          ON (`glpi_documents_items`.`documents_id`=`glpi_documents`.`id`)
                LEFT JOIN `glpi_entities` ON (`glpi_documents`.`entities_id`=`glpi_entities`.`id`)
                LEFT JOIN `glpi_documentcategories`
                        ON (`glpi_documents`.`documentcategories_id`=`glpi_documentcategories`.`id`)
                WHERE `glpi_documents_items`.`items_id` = '".$item->getID()."'
                      AND `glpi_documents_items`.`itemtype` = '".$item->getType()."' ";

      if (Session::getLoginUserID()) {
         $query .= getEntitiesRestrictRequest(" AND", "glpi_documents", '', '', true);
      } else {
         // Anonymous access from FAQ
         $query .= " AND `glpi_documents`.`entities_id`= '0' ";
      }

      // Document : search links in both order using union
      if ($item->getType() == 'Document') {
         $query .= "UNION
                    SELECT `glpi_documents_items`.`id` AS assocID,
                           `glpi_documents_items`.`date_mod` AS assocdate,
                           `glpi_entities`.`id` AS entityID,
                           `glpi_entities`.`completename` AS entity,
                           `glpi_documentcategories`.`completename` AS headings,
                           `glpi_documents`.*
                    FROM `glpi_documents_items`
                    LEFT JOIN `glpi_documents`
                        ON (`glpi_documents_items`.`items_id`=`glpi_documents`.`id`)
                    LEFT JOIN `glpi_entities`
                        ON (`glpi_documents`.`entities_id`=`glpi_entities`.`id`)
                    LEFT JOIN `glpi_documentcategories`
                        ON (`glpi_documents`.`documentcategories_id`=`glpi_documentcategories`.`id`)
                    WHERE `glpi_documents_items`.`documents_id` = '".$item->getID()."'
                          AND `glpi_documents_items`.`itemtype` = '".$item->getType()."' ";

         if (Session::getLoginUserID()) {
            $query .= getEntitiesRestrictRequest(" AND", "glpi_documents", '', '', true);
         } else {
            // Anonymous access from FAQ
            $query .= " AND `glpi_documents`.`entities_id`='0' ";
         }
      }
      $query .= " ORDER BY $sort $order";

      $result = $DB->query($query);
      $number = $DB->numrows($result);
      $i      = 0;

      $documents = [];
      $used      = [];
      if ($numrows = $DB->numrows($result)) {
         while ($data = $DB->fetch_assoc($result)) {
            $documents[$data['assocID']] = $data;
            $used[$data['id']]           = $data['id'];
         }
      }

      echo "<div class='spaced'>";
      if ($canedit
          && $number
          && ($withtemplate < 2)) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$params['rand']);
         $massiveactionparams = ['num_displayed'  => min($_SESSION['glpilist_limit'], $number),
                                      'container'      => 'mass'.__CLASS__.$params['rand']];
         Html::showMassiveActions($massiveactionparams);
      }

      echo "<table class='tab_cadre_fixehov'>";

      $header_begin  = "<tr>";
      $header_top    = '';
      $header_bottom = '';
      $header_end    = '';
      if ($canedit
          && $number
          && ($withtemplate < 2)) {
         $header_top    .= "<th width='11'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$params['rand']);
         $header_top    .= "</th>";
         $header_bottom .= "<th width='11'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$params['rand']);
         $header_bottom .= "</th>";
      }

      foreach ($columns as $key => $val) {
         $header_end .= "<th".($sort == "`$key`" ? " class='order_$order'" : '').">".
                        "<a href='javascript:reloadTab(\"sort=$key&amp;order=".
                          (($order == "ASC") ?"DESC":"ASC")."&amp;start=0\");'>$val</a></th>";
      }

      $header_end .= "</tr>";
      echo $header_begin.$header_top.$header_end;

      $used = [];

      if ($number) {
         // Don't use this for document associated to document
         // To not loose navigation list for current document
         if ($item->getType() != 'Document') {
            Session::initNavigateListItems('Document',
                              //TRANS : %1$s is the itemtype name,
                              //        %2$s is the name of the item (used for headings of a list)
                                           sprintf(__('%1$s = %2$s'),
                                                   $item->getTypeName(1), $item->getName()));
         }

         $document = new Document();
         foreach ($documents as $data) {
            $docID        = $data["id"];
            $link         = NOT_AVAILABLE;
            $downloadlink = NOT_AVAILABLE;

            if ($document->getFromDB($docID)) {
               $link         = $document->getLink();
               $downloadlink = $document->getDownloadLink($linkparam);
            }

            if ($item->getType() != 'Document') {
               Session::addToNavigateListItems('Document', $docID);
            }
            $used[$docID] = $docID;
            $assocID      = $data["assocID"];

            echo "<tr class='tab_bg_1".($data["is_deleted"]?"_2":"")."'>";
            if ($canedit
                && ($withtemplate < 2)) {
               echo "<td width='10'>";
               Html::showMassiveActionCheckBox(__CLASS__, $data["assocID"]);
               echo "</td>";
            }
            echo "<td class='center'>$link</td>";
            echo "<td class='center'>".$data['entity']."</td>";
            echo "<td class='center'>$downloadlink</td>";
            echo "<td class='center'>";
            if (!empty($data["link"])) {
               echo "<a target=_blank href='".Toolbox::formatOutputWebLink($data["link"])."'>".$data["link"];
               echo "</a>";
            } else {
               echo "&nbsp;";
            }
            echo "</td>";
            echo "<td class='center'>".Dropdown::getDropdownName("glpi_documentcategories",
                                                                 $data["documentcategories_id"]);
            echo "</td>";
            echo "<td class='center'>".$data["mime"]."</td>";
            if ($CFG_GLPI['use_rich_text']) {
               echo "<td class='center'>";
               echo !empty($data["tag"]) ? Document::getImageTag($data["tag"]) : '';
               echo "</td>";
            }
            echo "<td class='center'>".Html::convDateTime($data["assocdate"])."</td>";
            echo "</tr>";
            $i++;
         }
         echo $header_begin.$header_bottom.$header_end;
      }

      echo "</table>";
      if ($canedit && $number && ($withtemplate < 2)) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";
   }


   /**
    * @since 0.85
    *
    * @see CommonDBRelation::getRelationMassiveActionsPeerForSubForm()
   **/
   static function getRelationMassiveActionsPeerForSubForm(MassiveAction $ma) {

      switch ($ma->getAction()) {
         case 'add' :
         case 'remove' :
            return 1;

         case 'add_item' :
         case 'remove_item' :
            return 2;
      }
      return 0;
   }


   /**
    * @since 0.85
    *
    * @see CommonDBRelation::getRelationMassiveActionsSpecificities()
   **/
   static function getRelationMassiveActionsSpecificities() {
      global $CFG_GLPI;

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

}
