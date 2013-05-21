<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

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
   public $itemtype_1 = 'Document';
   public $items_id_1 = 'documents_id';

   public $itemtype_2 = 'itemtype';
   public $items_id_2 = 'items_id';


   function prepareInputForAdd($input) {

      if (empty($input['itemtype'])
          || ((empty($input['items_id']) || $input['items_id']==0)
              && $input['itemtype']!='Entity')
          || empty($input['documents_id'])
          || $input['documents_id']==0) {
         return false;
      }

      // Do not insert circular link for document
      if ($input['itemtype'] == 'Document' && $input['items_id']==$input['documents_id']) {
         return false;
      }
      // Avoid duplicate entry
      $restrict = "`documents_id` = '".$input['documents_id']."'
                   AND `itemtype` = '".$input['itemtype']."'
                   AND `items_id` = '".$input['items_id']."'";
      if (countElementsInTable($this->getTable(),$restrict)>0) {
         return false;
      }

      // Set default entities_id and is_recursive if not set.
      if (!isset($input['entities_id'])) {
         if (($item = getItemForItemtype($input['itemtype']))
            && $item->getFromDB($input['items_id'])) {
            $input['entities_id'] = $item->getEntityID();
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
         $input = array('id'            => $this->fields['items_id'],
                        'date_mod'      => $_SESSION["glpi_currenttime"],
                        '_donotadddocs' => true);

         if (!isset($this->input['_do_notif']) || $this->input['_do_notif']) {
            $input['_forcenotif'] = true;
         }
         $ticket->update($input);
      }
      parent::post_addItem();
   }

   function post_purgeItem() {

      if ($this->fields['itemtype'] == 'Ticket') {
         $ticket = new Ticket();
         $input = array('id'            => $this->fields['items_id'],
                        'date_mod'      => $_SESSION["glpi_currenttime"],
                        '_donotadddocs' => true);

         if (!isset($this->input['_do_notif']) || $this->input['_do_notif']) {
            $input['_forcenotif'] = true;
         }
         $ticket->update($input);
      }
      parent::post_purgeItem();
   }
   
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


   static function countForDocument(Document $item) {

      $restrict = "`glpi_documents_items`.`documents_id` = '".$item->getField('id')."'
                   AND `glpi_documents_items`.`itemtype` != '".$item->getType()."'";

      return countElementsInTable(array('glpi_documents_items'), $restrict);
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG;

      if (Session::haveRight('document', 'r')) {
         if ($_SESSION['glpishow_count_on_tabs']) {
            return self::createTabEntry($LANG['document'][19], self::countForDocument($item));
         }
         return $LANG['document'][19];
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case 'Document' :
            $item->showItems();
            return true;
      }
   }

}
?>
