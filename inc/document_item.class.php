<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2012 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

// Relation between Documents and Items
class Document_Item extends CommonDBRelation{


   // From CommonDBRelation
   static public $itemtype_1 = 'Document';
   static public $items_id_1 = 'documents_id';

   static public $itemtype_2 = 'itemtype';
   static public $items_id_2 = 'items_id';


   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   function prepareInputForAdd($input) {

      if (empty($input['itemtype'])
          || ((empty($input['items_id']) || ($input['items_id'] == 0))
              && ($input['itemtype'] != 'Entity'))
          || empty($input['documents_id'])
          || ($input['documents_id'] == 0)) {
         return false;
      }

      // Do not insert circular link for document
      if (($input['itemtype'] == 'Document')
          && ($input['items_id'] == $input['documents_id'])) {
         return false;
      }
      // Avoid duplicate entry
      $restrict = "`documents_id` = '".$input['documents_id']."'
                   AND `itemtype` = '".$input['itemtype']."'
                   AND `items_id` = '".$input['items_id']."'";
      if (countElementsInTable($this->getTable(),$restrict) > 0) {
         return false;
      }

      // Set default entities_id and is_recursive if not set.
      if (!isset($input['entities_id'])) {
         if (($item = getItemForItemtype($input['itemtype']))
             && $item->getFromDB($input['items_id'])) {
            $input['entities_id']  = $item->getEntityID();
            $input['is_recursive'] = 0;
            if ($item->isField('is_recursive')) {
               $input['is_recursive'] = $item->getField('is_recursive');
            }
         }
      }
      return $input;
   }


   function post_addItem() {

      if ($this->fields['itemtype'] == 'Ticket') {
         $ticket = new Ticket();
         $input  = array('id'            => $this->fields['items_id'],
                         'date_mod'      => $_SESSION["glpi_currenttime"],
                         '_donotadddocs' => true);

         if (!isset($this->input['_do_notif']) || $this->input['_do_notif']) {
            $input['_forcenotif'] = true;
         }
         $ticket->update($input);
      }
      parent::post_addItem();
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
         $restrict .= " AND `glpi_documents_items`.`entities_id` = '0' ";
      }

      $nb = countElementsInTable(array('glpi_documents_items'), $restrict);

      // Document case : search in both
      if ($item->getType() == 'Document') {
         $restrict = "`glpi_documents_items`.`documents_id` = '".$item->getField('id')."'
                      AND `glpi_documents_items`.`itemtype` = '".$item->getType()."'";

         if (Session::getLoginUserID()) {
            $restrict .= getEntitiesRestrictRequest(" AND ", "glpi_documents_items", '', '', true);
         } else {
            // Anonymous access from FAQ
            $restrict .= " AND `glpi_documents_items`.`entities_id` = '0' ";
         }
         $nb += countElementsInTable(array('glpi_documents_items'), $restrict);
      }
      return $nb ;
   }


   /**
    * @param $item   Document object
   **/
   static function countForDocument(Document $item) {

      $restrict = "`glpi_documents_items`.`documents_id` = '".$item->getField('id')."'
                   AND `glpi_documents_items`.`itemtype` != '".$item->getType()."'";

      return countElementsInTable(array('glpi_documents_items'), $restrict);
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (Session::haveRight('document', 'r')) {
         if ($_SESSION['glpishow_count_on_tabs']) {
            return self::createTabEntry(_n('Associated item', 'Associated items',
                                           self::countForDocument($item)));
         }
         return _n('Associated item', 'Associated items', 2);
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case 'Document' :
            self::showForDocument($item);
            return true;
      }
   }


   /**
    * Duplicate documents from an item template to its clone
    *
    * @since version 0.84
    *
    * @param $itemtype     itemtype of the item
    * @param $oldid        ID of the item to clone
    * @param $newid        ID of the item cloned
    * @param $newitemtype  itemtype of the new item (= $itemtype if empty) (default '')
   **/
   static function cloneItem($itemtype, $oldid, $newid, $newitemtype='') {
      global $DB;

      if (empty($newitemtype)) {
         $newitemtype = $itemtype;
      }

      $query  = "SELECT `documents_id`
                 FROM `glpi_documents_items`
                 WHERE `items_id` = '$oldid'
                        AND `itemtype` = '$itemtype';";

      foreach ($DB->request($query) as $data) {
         $docitem = new self();
         $docitem->add(array('documents_id' => $data["documents_id"],
                             'itemtype'     => $newitemtype,
                             'items_id'     => $newid));
      }
   }


   /**
    * Show items links to a document
    * @param $doc Document object
    * @return nothing (HTML display)
   **/
   static function showForDocument(Document $doc) {
      global $DB, $CFG_GLPI;

      $instID = $doc->fields['id'];
      if (!$doc->can($instID,"r")) {
         return false;
      }
      $canedit = $doc->can($instID,'w');

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
         Dropdown::showAllItems("items_id", 0, 0,
                                ($doc->fields['is_recursive']?-1:$doc->fields['entities_id']),
                                $CFG_GLPI["document_types"], false, true);
         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\"".__s('Add')."\" class='submit'>";
         echo "<input type='hidden' name='documents_id' value='$instID'>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($canedit && $number) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = array();
         Html::showMassiveActions(__CLASS__, $massiveactionparams);
      }
      echo "<table class='tab_cadre_fixe'>";
       echo "<tr>";

      if ($canedit && $number) {
         echo "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand)."</th>";
      }

      echo "<th>".__('Type')."</th>";
      echo "<th>".__('Name')."</th>";
      echo "<th>".__('Entity')."</th>";
      echo "<th>".__('Serial number')."</th>";
      echo "<th>".__('Inventory number')."</th>";
      echo "</tr>";

      for ($i=0 ; $i < $number ; $i++) {
         $itemtype=$DB->result($result, $i, "itemtype");
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }

         if ($item->canView()) {
            $column = "name";
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
                          FROM `glpi_documents_items`, `$itemtable`
                          LEFT JOIN `glpi_entities`
                              ON (`glpi_entities`.`id` = `$itemtable`.`entities_id`)
                          WHERE `$itemtable`.`id` = `glpi_documents_items`.`items_id`
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
                     $where = " AND (`glpi_entities_knowbaseitems`.`entities_id` = '0'
                                     AND `glpi_entities_knowbaseitems`.`is_recursive` = '1')";
                  }
               }
            } else {
               $query .= getEntitiesRestrictRequest(" AND ", $itemtable, '', '',
                                                   $item->maybeRecursive());
            }

            if ($item->maybeTemplate()) {
               $query .= " AND `$itemtable`.`is_template` = '0'";
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
                     $linkname = $data["name"];
                     if ($_SESSION["glpiis_ids_visible"] || empty($data["name"])) {
                        $linkname = sprintf(__('%1$s (%2$s)'), $linkname, $data["id"]);
                     }

                     $link = Toolbox::getItemTypeFormURL($itemtype);
                     $name = "<a href=\"".$link."?id=".$data["id"]."\">".$linkname."</a>";

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
                     echo "<td class='center'>".(isset($data["serial"])? "".
                                                 $data["serial"]."" :"-")."</td>";
                     echo "<td class='center'>".(isset($data["otherserial"])? "".
                                                 $data["otherserial"]."" :"-")."</td>";
                     echo "</tr>";
                  }
               }
            }
         }
      }
      echo "</table>";
      if ($canedit && $number) {
         $paramsma['ontop'] =false;
         Html::showMassiveActions(__CLASS__, $paramsma);
         Html::closeForm();
      }
      echo "</div>";

   }


}
?>
